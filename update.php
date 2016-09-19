<?php
/**
 * 主题更新
 *
 * 2016-07-09：
 *  - 修复使用子主题的情况下更新主题，会更新到子主题而不是父主题的问题。
 *
 * 2016-06-11：
 *  - 修复主题更新动作钩子在第一次启用主题时也会触发的问题。
 *
 * 2016-06-10：
 *  - 删除了主题更新到每个版本时触发的动作钩子。
 *
 * 2015-11-24：
 *  - 在启用主题时强制检测一次更新。
 *  - 主题更新内容储存到多站点 Transient。
 *
 * 2015-11-15：
 *  - 新增更新主题时自动清除之前保存的更新内容。
 *
 * 2015-11-07：
 *  - 新增可以在后台查看到新版本更新内容。
 *
 * 2015-10-24：
 *  - 解决检测一次需要发出两次请求的问题。
 */
class Bing_Theme_Update {

	/**
	 * API 服务器
	 *
	 * @var string
	 */
	private $api = 'http://apis.biji.io/wordpress/themes/update-check/';

	/**
	 * 主题更新信息
	 *
	 * @var array
	 */
	private $update_data;

	/**
	 * 初始化
	 */
	public static function init() {
		$GLOBALS['theme_update'] = new self;
	}

	/**
	 * 构造函数
	 */
	public function __construct() {
		add_action( 'after_switch_theme', array( $this, 'force_check_update' ), 18    );
		add_action( 'after_switch_theme', array( $this, 'get_update_data'    ), 18, 0 );
		add_action( 'wp_update_themes',   array( $this, 'get_update_data'    ), 18, 0 );

		add_filter( 'pre_set_site_transient_update_themes', array( $this, 'insert_update_data' ), 18 );
	}

	/**
	 * 强制检测主题更新
	 */
	public function force_check_update() {
		$last_update = get_site_transient( 'update_themes' );

		if ( isset( $last_update->last_checked ) ) {
			unset( $last_update->last_checked );

			set_site_transient( 'update_themes', $last_update );
		}

		wp_update_themes();
	}

	/**
	 * 插入主题更新信息
	 */
	public function insert_update_data( $update_data ) {
		if ( $my_update_data = $this->get_update_data() ) {
			if ( !isset( $update_data->response ) )
				$update_data->response = array();

			$update_data->response = $my_update_data + $update_data->response;
		}

		return $update_data;
	}

	/**
	 * 获取主题更新信息
	 */
	private function get_update_data() {
		if ( isset( $this->update_data ) )
			return $this->update_data;

		$all_themes = wp_get_themes();
		$themes     = array();

		foreach ( $all_themes as $theme ) {
			$stylesheet = $theme->get_stylesheet();
			$template   = $theme->get_template();

			$themes[$stylesheet] = array(
				'name'       => $theme->get( 'Name' ),
				'title'      => $theme->get( 'Name' ),
				'version'    => $theme->get( 'Version' ),
				'author'     => $theme->get( 'Author' ),
				'author_url' => $theme->get( 'AuthorURI' ),
				'theme_url'  => $theme->get( 'ThemeURI' ),

				'template'   => $stylesheet,
				'stylesheet' => $template,

				//备用参数
				'author_uri' => $theme->get( 'AuthorURI' ),
				'theme_uri'  => $theme->get( 'ThemeURI' )
			);

			//多站点问题？
			if ( ( $db_version = get_option( $stylesheet . '_db_version' ) ) !== false )
				$themes[$stylesheet]['db_version'] = $db_version;
		}

		$response = $this->get_api( array(
			'themes' => $themes,
			'active' => get_stylesheet(),

			//备用参数
			'active_theme' => get_stylesheet()
		) );

		if ( !empty( $response['themes'] ) ) {
			if ( !empty( $response['verify'] ) || !empty( $response['check'] ) || !empty( $response['validate'] ) || !empty( $response['version_compare'] ) || !empty( $response['safe_mode'] ) || !empty( $response['verifies'] ) || !empty( $response['checks'] ) || !empty( $response['validates'] ) )
				$response['themes'] = $this->filter_themes( $response['themes'] );

			if ( $response['themes'] )
				$this->update_data = $response['themes'];
		}

		if ( !isset( $this->update_data ) )
			$this->update_data = false;

		return $this->update_data;
	}

	/**
	 * 过滤主题更新信息
	 */
	private function filter_themes( $themes ) {
		$themes = (array) $themes;
		$result = array();

		foreach ( $themes as $stylesheet => $new_theme ) {
			if ( empty( $new_theme['new_version'] ) )
				continue;

			if ( !empty( $new_theme['theme'] ) )
				$stylesheet = $new_theme['theme'];

			if ( empty( $stylesheet ) )
				continue;

			$theme = wp_get_theme( $stylesheet );

			if ( !$theme->exists() )
				continue;

			$new_version     = strtolower( $new_theme['new_version'] );
			$current_version = strtolower( $theme->get( 'Version' ) );

			if ( version_compare( $new_version, $current_version, '<=' ) )
				continue;

			$result[$stylesheet] = array(
				'theme'       => $stylesheet,
				'new_version' => $new_theme['new_version'],
				'url'         => isset( $new_theme['url']     ) ? esc_url_raw( $new_theme['url']     ) : $theme->get( 'ThemeURI' ),
				'package'     => isset( $new_theme['package'] ) ? esc_url_raw( $new_theme['package'] ) : null
			);
		}

		return $result;
	}

	/**
	 * 获取远程服务器信息
	 */
	private function get_api( $body ) {
		global $wpdb;

		$timeout  = defined( 'DOING_CRON' ) && DOING_CRON ? 30 : 5;
		$timeout += round( strlen( maybe_serialize( $body ) ) / 2000 );

		$other_body = array(
			'url'             => home_url(),
			'name'            => get_bloginfo( 'name' ),
			'is_multisite'    => is_multisite(),
			'wp_version'      => get_bloginfo( 'version' ),
			'php_version'     => phpversion(),
			'server_software' => $_SERVER['SERVER_SOFTWARE'],
			'wp_locale'       => get_locale(),

			//备用参数
			'multisite_enabled' => is_multisite()
		);

		if ( method_exists( $wpdb, 'db_version' ) )
			$other_body['mysql_version'] = $wpdb->db_version();

		$response = wp_safe_remote_post( $this->api, array(
			'timeout'    => $timeout,
			'user-agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url(),
			'body'       => array_merge( $other_body, $body )
		) );

		if ( wp_remote_retrieve_response_code( $response ) == 200 ) {
			$contnet = trim( wp_remote_retrieve_body( $response ) );

			if ( !empty( $contnet ) ) {
				$contnet = json_decode( $contnet, true );

				if ( is_array( $contnet ) && $contnet )
					return $contnet;
			}
		}

		return false;
	}

}
add_action( 'after_setup_theme', array( 'Bing_Theme_Update', 'init' ), 2 );