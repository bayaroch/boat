<?php

/**
 * Template Name:About
 *
 */

defined('ABSPATH') || exit; // Exit if accessed directly

get_header(); ?>

<?php

/**
 * Page Builder
 */
if (TIELABS_HELPER::has_builder()) :

    // Get Blocks
    TIELABS_HELPER::get_template_part('framework/blocks');

    // After the page builder contents
    do_action('TieLabs/after_builder_content');


    /**
     * Normal Page
     */
else :

    if (have_posts()) :

        while (have_posts()) : the_post();

            TIELABS_HELPER::get_template_part('templates/single-post/content');

        endwhile;

    endif; ?>
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

endif;

get_footer();
