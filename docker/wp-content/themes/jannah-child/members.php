<?php

/**
 * Template Name:Members
 *
 */

defined('ABSPATH') || exit; // Exit if accessed directly

get_header(); ?>
<div class="main-content tie-col-md-8 tie-col-xs-12" role="main">


    <article id="the-post" class="container-wrapper post-content tie-standard">


        <header class="entry-header-outer">

            <div class="entry-header">
                <h1 class="post-title entry-title"><?php the_title(); ?></h1>
            </div><!-- .entry-header /-->


        </header><!-- .entry-header-outer /-->


        <div class="entry-content entry clearfix">

            <?php the_content();  ?>

        </div><!-- .entry-content /-->

        <div>
            <?php get_all_team_members() ?>
        </div>



    </article><!-- #the-post /-->


    <div class="post-components">


    </div><!-- .post-components /-->


</div>
<?php ?>
<aside class="sidebar tie-col-md-4 tie-col-xs-12 normal-side" aria-label="<?php esc_html_e('Primary Sidebar', TIELABS_TEXTDOMAIN); ?>">

    <div class="paper">
        <?php
        // Display the primary menu
        wp_nav_menu(array(
            'theme_location' => 'about-menu',
            'menu_class' => 'sub-menu__list'
            // You can add more parameters here as needed
        ));
        ?>
    </div>
</aside><!-- .sidebar /-->

<?php
get_footer();
