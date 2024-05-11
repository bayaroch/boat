<?php

add_action('wp_enqueue_scripts', 'tie_theme_child_styles_scripts', 80);
function tie_theme_child_styles_scripts()
{

	/* Load the RTL.css file of the parent theme */
	if (is_rtl()) {
		wp_enqueue_style('tie-theme-rtl-css', get_template_directory_uri() . '/rtl.css', '');
	}

	/* THIS WILL ALLOW ADDING CUSTOM CSS TO THE style.css */
	wp_enqueue_style('tie-theme-child-css', get_stylesheet_directory_uri() . '/style.css', '');

	/* Uncomment this line if you want to add custom javascript */
	//wp_enqueue_script( 'jannah-child-js', get_stylesheet_directory_uri() .'/js/scripts.js', '', false, true );
}



function create_post_types()
{

	// Custom Post Type: Team Members
	register_post_type(
		'members',
		array(
			'labels' => array(
				'name' => __('Team Members', 'team_members'),
				'singular_name' => __('Team Member', 'team_members'),
				'add_new' => __('Add New', 'team_members'),
				'add_new_item' => __('Add New Team Member', 'team_members'),
				'edit' => __('Edit', 'team_members'),
				'edit_item' => __('Edit Team Member', 'team_members'),
				'new_item' => __('New Team Member', 'team_members'),
				'view' => __('View Team Member', 'team_members'),
				'view_item' => __('View Team Member', 'team_members'),
				'search_items' => __('Search Team Members', 'team_members'),
				'not_found' => __('No team members found', 'team_members'),
				'not_found_in_trash' => __('No team members found in Trash', 'team_members')
			),
			'public' => true,
			'supports' => array(
				'title',
				'editor',
				'thumbnail',
				'custom-fields'
			),
			'rewrite' => array(
				'slug' => 'team-members',

			)
		)
	);



	// Add more custom post types as needed
}
add_action('init', 'create_post_types');


if (function_exists('register_nav_menus')) {
	/**
	 * Nav menus.
	 *
	 * @since v1.0
	 *
	 * @return void
	 */
	register_nav_menus(
		array(
			'about-menu'   => 'About Menu',
		)
	);
}



function get_all_team_members()
{
	$args = array(
		'post_type'      => 'members', // Custom post type
		'posts_per_page' => -1, // Number of posts to retrieve
		'orderby'        => 'date', // Order by date
		'order'          => 'ASC', // Descending order (latest first)
	);

	$team_members_query = new WP_Query($args);

	// The Loop
	if ($team_members_query->have_posts()) {
		while ($team_members_query->have_posts()) {
			$team_members_query->the_post();
?>

			<div class="team-card">
				<div class="team-image">
					<?php
					if (has_post_thumbnail()) {
						the_post_thumbnail('normal', array('class' => 'img-fluid'));
					} else {
					?>
						<img src="<?php echo esc_url(get_template_directory_uri()); ?>/assets/images/default-team-image.jpg" alt="Team">
					<?php
					}
					?>
				</div>
				<div class="team-content">
					<h4 class="team-name"><?php the_title(); ?></h4>
					<span class="sub-title"><?php echo get_field('title'); ?></span>
					<ul class="fields">
						<li>
							<span>DOB:</span> <?php echo get_field('dob'); ?>
						</li>
						<li>
							<span>Gender:</span> <?php echo get_field('gender'); ?>
						</li>
						<li>
							<span>Club:</span> <?php echo get_field('club'); ?>
						</li>
					</ul>
					<p>
						<?php the_content() ?>
					</p>


				</div>
			</div>

<?php
		}
		// Restore original Post Data
		wp_reset_postdata();
	} else {
		// No posts found
		echo 'No team members found.';
	}
}


add_filter('show_admin_bar', '__return_false');
