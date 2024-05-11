<?php
/*
	Plugin Name: Social Counters - Arqam Lite
	Plugin URI: http://tielabs.com
	Description: Lite Version of the Arqam Plugin - WordPress Social Counter Plugin
	Author: TieLabs
	Version: 1.0.12
	Author URI: http://tielabs.com
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


if( ! class_exists( 'ARQAM_LITE_PLUGIN' )){

	class ARQAM_LITE_PLUGIN{


		/**
		 * __construct
		 *
		 * Class constructor where we will call our filter and action hooks.
		 */
		function __construct(){

			// Disable if the Arqam Plugin is installed
			if( function_exists( 'arq_counters_data' ) ){
				return;
			}

			// Include the plugin's files
			require_once( 'arqam-lite-admin.php' );
			require_once( 'arqam-lite-counters.php' );

			// Load Text Domain
			add_action( 'plugins_loaded', array( $this, 'plugin_textdomain' ));
		}


		/**
		 * plugin_activate
		 *
		 * Store Defaults settings
		 */
		static function plugin_activate(){

			if( ! get_option( 'arq_options' ) ){

				$default_data = array(
					'social' => array(
						'facebook'   => array( 'text' => esc_html__( 'Fans',			 'arqam-lite' )),
						'twitter'    => array( 'text' => esc_html__( 'Followers',	 'arqam-lite' )),
						'dribbble'   => array( 'text' => esc_html__( 'Followers',	 'arqam-lite' )),
						'soundcloud' => array( 'text' => esc_html__( 'Followers',	 'arqam-lite' )),
						'behance'    => array( 'text' => esc_html__( 'Followers',	 'arqam-lite' )),
						'github'     => array( 'text' => esc_html__( 'Followers',	 'arqam-lite' )),
						'instagram'  => array( 'text' => esc_html__( 'Followers',	 'arqam-lite' )),
						'youtube'    => array( 'text' => esc_html__( 'Subscribers','arqam-lite' )),
						'vimeo'      => array( 'text' => esc_html__( 'Subscribers','arqam-lite' )),
						'rss'        => array( 'text' => esc_html__( 'Subscribers','arqam-lite' )),
					),
				);

				update_option( 'arq_options', $default_data );
			}
		}


		/**
		 * plugin_textdomain
		 *
		 * Load Text Domain
		 */
		function plugin_textdomain() {
			load_plugin_textdomain( 'arqam-lite', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
		}
	}


	new ARQAM_LITE_PLUGIN();

	// Store the default settings
	register_activation_hook( __FILE__, array( 'ARQAM_LITE_PLUGIN', 'plugin_activate' ));
}
