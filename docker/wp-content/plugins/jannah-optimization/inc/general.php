<?php

defined( 'ABSPATH' ) || exit; // Exit if accessed directly


class JANNAH_OPTIMIZATION_GENERAL {

	/**
	 * Fire Filters and actions
	 */
	function __construct(){

		// Check if the theme is enabled
		if( ! class_exists( 'TIELABS_HELPER' ) || ! function_exists( 'jannah_theme_name' ) ){
			return;
		}

		add_filter( 'TieLabs/api_connect_body', array( $this, 'api_connect_body' ) );

		if( ! is_admin() ){

			// Add defer attr
			if( tie_get_option( 'jso_js_deferred' ) ){
				add_filter( 'script_loader_tag',  array( $this, 'add_defer_attribute' ), 10, 2 );
			}

			// Remove Query Strings From Static Resources
			if( tie_get_option( 'jso_remove_query_strings' ) ){
				add_filter( 'script_loader_src',  array( $this, 'remove_query_strings' ), 15 );
				add_filter( 'style_loader_src',   array( $this, 'remove_query_strings' ), 15 );
			}

			// Preload resources
			add_action( 'wp_enqueue_scripts', array( $this, 'preload_resources' ), 8 );

			// Dns prefetch
			add_action( 'wp_enqueue_scripts', array( $this, 'dns_prefetch' ), 7 );

			// Disable Google Fonts On Slow Connections
			if( tie_get_option( 'jso_disable_fonts_2g' ) ){
				add_filter( 'TieLabs/google_fonts/js_code', array( $this, 'google_fonts_disable_2g' ), 10, 2 );
			}

			// Disable Google Fonts On Mobiles
			if( tie_get_option( 'jso_disable_fonts_mobile' ) ){
				add_filter( 'TieLabs/google_fonts/js_code', array( $this, 'google_fonts_disable_mobile' ), 15, 2 );
			}

			// Emojis and Smilies
			if( tie_get_option( 'jso_disable_emoji_smilies' ) ){
				remove_action( 'wp_print_styles',            'print_emoji_styles');
				remove_action( 'wp_head',                    'print_emoji_detection_script', 7);
				remove_filter( 'the_excerpt',                'convert_smilies' );
				remove_filter( 'the_post_thumbnail_caption', 'convert_smilies' );
				remove_filter( 'the_content',                'convert_smilies', 20 );
				remove_filter( 'comment_text',               'convert_smilies', 20 );
				remove_filter( 'widget_text_content',        'convert_smilies', 20 );
			}

			// Disable XML-RPC and RSD Link
			if( tie_get_option( 'jso_disable_xml_rpc' ) ){
				add_filter( 'xmlrpc_enabled', '__return_false', 5 );
				remove_action( 'wp_head', 'rsd_link' );
			}

			// Remove wlwmanifest Link
			if( tie_get_option( 'jso_disable_wlwmanifest' ) ){
				remove_action( 'wp_head', 'wlwmanifest_link' );
			}

			// No Need For this
			remove_filter( 'the_content', 'capital_P_dangit', 11 );
			remove_filter( 'the_title',   'capital_P_dangit', 11 );
			remove_filter( 'wp_title',    'capital_P_dangit', 11 );

			// Test Mode
			if( tie_get_option( 'jso_test_mode' ) ){
				add_filter( 'TieLabs/Ad_widget/code', '__return_false', 999999 );
				add_filter( 'TieLabs/block/ad_code',  '__return_false', 999999 );
				add_filter( 'TieLabs/custom_ad_code', '__return_false', 999999 );
				add_filter( 'TieLabs/header_code',    '__return_false', 999999 );
				add_filter( 'TieLabs/body_code',      '__return_false', 999999 );
				add_filter( 'TieLabs/footer_code',    '__return_false', 999999 );
			}

			// Ajax Requests
			/* This Feature is disabled right now, it caused 403 error on some servers
			if( tie_get_option( 'jso_ajax' ) ){
				add_filter( 'TieLabs/js_main_vars', array( $this, 'ajax_file_path' ) );
			}
			*/
		}

	}


	/**
	 * api_connect_body
	 */
	function api_connect_body( $body ){
		$body['performance'] = true;
		return $body;
	}


	/**
	 * remove_query_strings
	 * Remove Query Strings From Static Resources
	 */
	function remove_query_strings( $src ){

		if( ! is_admin() && ! current_user_can( 'switch_themes' ) ){
			$src = remove_query_arg( 'ver', $src );
		}

		return $src;
	}


	/**
	 * add_defer_attribute
	 * Add Defer to the JS files
	 */
	function add_defer_attribute( $tag, $handle ) {

		if ( strpos( $handle, 'tie-') !== false && ! is_admin() ) {
			return str_replace( ' src', ' defer="defer" src', $tag );
		}

		return $tag;
	}


	/**
	 * dns_prefetch
	 * DNS prefetch for the common used domains
	 */
	function dns_prefetch() {

		if( function_exists( 'is_amp_endpoint' ) && is_amp_endpoint() ){
			return;
		}

		echo "\n<meta http-equiv='x-dns-prefetch-control' content='on'>\n";

		$dns_domains = apply_filters( 'TieLabs/dns_prefetch/domains', array(
			"//cdnjs.cloudflare.com",
			"//ajax.googleapis.com",
			"//fonts.googleapis.com",
			"//fonts.gstatic.com",
			"//s.gravatar.com",
			"//www.google-analytics.com"
		));

		if( ! empty( $dns_domains ) && is_array( $dns_domains ) ){
			foreach ( $dns_domains as $domain ) {
				if ( ! empty( $domain ) ){
				}
					echo "<link rel='dns-prefetch' href='$domain' />\n";
			}
		}
	}


	/**
	 * preload_resources
	 */
	function preload_resources(){

		if( function_exists( 'is_amp_endpoint' ) && is_amp_endpoint() ){
			return;
		}
		
		// Styles
		/*
		$min = TIELABS_STYLES::is_minified();

		$styles = apply_filters( 'TieLabs/preload_resources/styles', array(
			TIELABS_TEMPLATE_URL . '/assets/css/style'. $min .'.css',
		));

		foreach ( $styles as $style ) {
			echo "<link rel='preload' as='style' href='$style' />\n";
		}
		*/


		$images = array();

		// Logos
		$logo = tie_logo_args();

		if( $logo['logo_type'] != 'title' && tie_get_option( 'preload_logos' ) ){

			$images[] = $logo['logo_img'];

			
			/* Don't preload 2x logo
			if( $logo['logo_retina'] != $logo['logo_img'] ){
				$images[] = $logo['logo_retina'];
			}
			*/
		}

		// Featured Image
		if( is_single() && tie_get_option( 'preload_featured_image' ) ){
			$size = ( tie_get_object_option( 'sidebar_pos', 'cat_posts_sidebar_pos', 'tie_sidebar_pos' ) == 'full' ) ? 'full' : TIELABS_THEME_SLUG.'-image-post';
			$images[] = get_the_post_thumbnail_url( null, $size );
		}

		// HomePage Slider Images
		elseif( ( is_home() || is_front_page() ) && TIELABS_HELPER::has_builder() && tie_get_option( 'preload_home_slider' ) ){

			$home_slider_key    = tie_get_cache_key( 'home-slider' );
			$home_slider_images = get_transient( $home_slider_key );

			if( false !== $home_slider_images ){
				$images = array_merge( $images, $home_slider_images );
			}
		}

		// ---
		$images = apply_filters( 'TieLabs/preload_resources/images', $images );

		if( ! empty( $images ) && is_array( $images ) ){

			foreach ( $images as $img ) {
				if( ! empty( $img ) ){
					echo "<link rel='preload' as='image' href='$img'>\n";
				}
			}
		}


		// Fonts
		$fonts = array();

		if( tie_get_option( 'preload_icon_fonts') ){
			$fonts['tielabs-fonticon/tielabs-fonticon.woff'] = 'woff';

			if( ! tie_get_option( 'jso_disable_fontawesome' ) ){
				$fonts['fontawesome/fa-solid-900.woff2']   = 'woff2';
				$fonts['fontawesome/fa-brands-400.woff2']  = 'woff2';
				$fonts['fontawesome/fa-regular-400.woff2'] = 'woff2';
			}
		}


		$fonts = apply_filters( 'TieLabs/preload_resources/fonts', $fonts );

		if( ! empty( $fonts ) && is_array( $fonts ) ){
			foreach ( $fonts as $font => $type ) {
				echo "<link rel='preload' as='font' href='".TIELABS_TEMPLATE_URL."/assets/fonts/$font' type='font/$type' crossorigin='anonymous' />\n";
			}
		}

		// Google Fonts Loader
		if( tie_get_option( 'jso_disable_fonts_mobile' ) && tie_is_mobile() ){
			echo '<!-- Google Fonts Disabled on Mobiles -->';
		}
		else{
			if( ! empty( $GLOBALS['tie_google_fonts'] ) ){
				echo "<link rel='preload' as='script' href='https://ajax.googleapis.com/ajax/libs/webfont/1/webfont.js'>\n";
			}
		}
	}


	/**
	 * Get the CSS file path
	 */
	function style_path( $handle = '' ){

		global $wp_styles;

		if ( is_a( $wp_styles, 'WP_Styles' ) && ! empty( $wp_styles->registered[$handle]->src ) ){
			return $wp_styles->registered[$handle];
		}

		return false;
	}


	/**
	 * google_fonts_disable_2g
	 * Disable Google Fonts On Slow Connections
	 */
	function google_fonts_disable_2g( $js_code, $fonts ){

		if( ! empty( $js_code ) ){

			$js_code = "
				var connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
				if ( typeof connection != 'undefined' && (/\slow-2g|2g/.test(connection.effectiveType))) {
					console.warn( 'Slow Connection Google Fonts Disabled' );
				}
				else{
					$js_code
				}
			";
		}

		return $js_code;
	}


	/**
	 * google_fonts_disable_mobile
	 * Disable Google Fonts On Mobiles
	 */
	function google_fonts_disable_mobile( $js_code, $fonts ){

		if( tie_is_mobile() ){
			return '';
		}

		return $js_code;
	}


	/**
	 * ajax_file_path
	 */
	function ajax_file_path( $vars ){

		if( ! empty( $vars['ajaxurl'] ) ){
			$vars['ajaxurl'] = plugins_url( 'ajax.php', __FILE__ );
		}

		return $vars;
	}

} // class


//
add_filter( 'init', 'jannah_optimization_general_init' );
function jannah_optimization_general_init(){

	// This method available in v4.0.0 and above
	if( method_exists( 'TIELABS_HELPER','has_builder' ) ){
		new JANNAH_OPTIMIZATION_GENERAL();
	}
}
