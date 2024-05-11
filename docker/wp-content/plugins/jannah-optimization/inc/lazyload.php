<?php
/**
 * LazyLoad
 *
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly


class JANNAH_OPTIMIZATION_LAZYLOAD {

	/**
	 * Runs on class initialization. Adds filters and actions.
	 */
	function __construct() {

		// Check if the theme is enabled
		if( ! class_exists( 'TIELABS_HELPER' ) || ! function_exists( 'jannah_theme_name' ) ){
			return;
		}
		
		add_filter( 'tie/extensions/shortcodes/author/avatar', array( $this, 'lazyload_avatar' ) );
		add_filter( 'get_avatar',            array( $this, 'lazyload_avatar' ) );
		add_filter( 'the_content',           array( $this, 'lazyload_post_content' ) );
		add_action( 'enqueue_embed_scripts', array( $this, 'lazyload_embed_iframe' ) );
		add_filter( 'wp_kses_allowed_html',  array( $this, 'lazyload_allow_attrs' ), 10, 2 );
		add_filter( 'wp_calculate_image_srcset',          array( $this, 'lazyload_disable_srcset' ) );
		add_filter( 'wp_get_attachment_image_attributes', array( $this, 'lazyload_image_attributes' ), 8, 3 );


		add_filter( 'TieLabs/CSS/after_theme_color', array( $this, 'inline_css_code' ), 100 );

		// Ads Lazyload images
		add_filter( 'TieLabs/Ad_widget/code',  array( $this, 'lazyload_ads' ) );
		add_filter( 'TieLabs/Ad_widget/image', array( $this, 'lazyload_ads' ) );
		add_filter( 'TieLabs/block/ad_code',   array( $this, 'lazyload_ads' ) );
		add_filter( 'TieLabs/block/ad_image',  array( $this, 'lazyload_ads' ) );
		add_filter( 'TieLabs/custom_ad_code',  array( $this, 'lazyload_ads' ) );

		// Adsense - Beta
		add_filter( 'TieLabs/custom_ad_code', array( $this, 'lazyload_adsense' ) );
		add_filter( 'TieLabs/Ad_widget/code', array( $this, 'lazyload_adsense' ) );
		add_filter( 'TieLabs/block/ad_code',  array( $this, 'lazyload_adsense' ) );

		add_filter( 'wp_footer',  array( $this, 'lazyload_delay_adsense' ) );


		// TieLabs Instagaram Plugin
		add_filter( 'TieLabs/Instagram_Feed/avatar_img', array( $this, 'instagaram_lazyload' ) );
		add_filter( 'TieLabs/Instagram_Feed/media_img',  array( $this, 'instagaram_lazyload' ) );

		// 
		add_filter( 'TieLabs/video_output', array( $this, 'lazyload_youtube_videos' ) );
	}


	/**
	 * Lazyload Featured images
	 */
	function is_lazyload_active(){

		// Return Early, avoid expensive checking
		if( ! tie_get_option( 'lazy_load' ) || is_admin() || is_feed() ){
			return false;
		}

		// Avoid lazyLoad in the AMP pages
		if( function_exists( 'is_amp_endpoint' ) && is_amp_endpoint() ){
			return false;
		}

		// JetPack Plugin is active & the Photon option is enabled & Current images displayed in the post content
		if( TIELABS_JETPACK_IS_ACTIVE && in_array( 'photon', Jetpack::get_active_modules() ) && in_array( 'the_content', $GLOBALS['wp_current_filter'] ) ){
			return false;
		}

		// WooCommerce
		if( TIELABS_WOOCOMMERCE_IS_ACTIVE && in_array( 'woocommerce_review_before', $GLOBALS['wp_current_filter'] ) ){
			return false;
		}

		// Adminbar avatars
		if( in_array( 'admin_bar_menu', $GLOBALS['wp_current_filter'] ) ){
			return false;
		}

		// Gravity Forms Front-End Forms
		if ( isset( $_POST['gform_send_resume_link'] ) || isset( $_POST['gform_submit'] ) ) {
			return false;
		}

		// Active
		return true;
	}


	/**
	 * Lazyload Featured images
	 */
	function lazyload_image_attributes( $attr = array(), $attachment = false, $size = false ) {

		if( ! $this->is_lazyload_active() ){
			return $attr;
		}

		// Exclude the main featured image in the single post page
		if( tie_get_option( 'lazy_load_exclude_main_featured_image' ) && isset( $attr['data-main-img'] ) ){

			if( isset( $attr['loading'] ) ){
				unset( $attr['loading'] );
			}

			return $attr;
		}

		// Get the LazyLoad placeholder image
		$blank_image = ( $size == TIELABS_THEME_SLUG.'-image-small' ) ? tie_lazyload_placeholder('small') : tie_lazyload_placeholder();

		$attr['class']   .= ' lazy-img';
		$attr['data-src'] = $attr['src'];
		$attr['src']      = $blank_image;

		if( TIELABS_DB_VERSION >= '5.0.0' ){
			$attr['loading'] = 'lazy';
		}

		return $attr;
	}


	/**
	 * Lazyload images in post content
	 */
	function lazyload_post_content( $content ){

		if( ! $this->is_lazyload_active() || ! tie_get_option( 'lazy_load_post_content' ) || ! is_singular('post') ){
			return $content;
		}

		return preg_replace_callback( '/(<\s*img[^>]+)(src\s*=\s*"[^"]+")([^>]+>)/i', array( $this, '_lazyload_post_content_preg' ), $content );
	}

	function _lazyload_post_content_preg( $img_match ){

		$site_url   = is_multisite() ? network_site_url() : get_site_url();
		$image_path = substr( $img_match[2], 5); // there is " at the end

		$site_host  = wp_parse_url( $site_url );
		$image_host = wp_parse_url( $image_path );

		if( ! empty( $image_host['host'] ) && ! empty( $site_host['host'] ) && strpos( $image_host['host'], $site_host['host'] ) !== false ) {

			if( TIELABS_DB_VERSION >= '5.0.0' ){
				return $img_match[1] . 'src="'. tie_lazyload_placeholder() . '" loading="lazy" data-src="'. $image_path . $img_match[3];
			}

			return $img_match[1] . 'src="'. tie_lazyload_placeholder() . '" data-src="'. $image_path . $img_match[3];
		}

		return $img_match[1] . 'src="'. $image_path . $img_match[3];
	}


	/**
	 * Disable srcset if LazyLoad is active
	 */
	function lazyload_disable_srcset( $sources ) {

		if( $this->is_lazyload_active() ){
			return false;
		}

		return $sources;
	}


	/**
	 * Allow the data-src in the wp_kses function
	 * WooCommerce uses the wp_kses to output the products thumbs.
	 */
	function lazyload_allow_attrs( $allowedtags, $context ){

		if( $this->is_lazyload_active() ){
			$allowedtags['img']['data-src'] = true;
			$allowedtags['img']['loading']  = true;
		}

		return $allowedtags;
	}


	/**
	 * Run the lazy load on the embed iframe
	 */
	function lazyload_embed_iframe(){

		if( ! $this->is_lazyload_active() ){
			return;
		}

		echo '
			<script>
				document.addEventListener("DOMContentLoaded", function(){
					var x = document.getElementsByClassName("lazy-img"), i;
					for (i = 0; i < x.length; i++) {
						x[i].setAttribute("src", x[i].getAttribute("data-src"));
					}
				});
			</script>
		';
	}


	/**
	 * Avatar Lazyload
	 */
	function lazyload_avatar( $avatar ){

		// Check if LazyLoad is active and the data-src didn't add before
		if( ! $this->is_lazyload_active() || strpos( $avatar, 'data-src' ) !== false ){
			return $avatar;
		}

		$blank_image = tie_lazyload_placeholder('square');

		$avatar = str_replace( '"', "'", $avatar );
		$avatar = str_replace( 'srcset=', 'data-2x=', $avatar );
		$avatar = str_replace( "src='", "src='". $blank_image ."' data-src='", $avatar );
		$avatar = str_replace( "class='", "class='lazy-img ", $avatar );
		return $avatar;
	}


	/**
	 * lazyload_ads
	 */
	function lazyload_ads( $image = false ){

		if( empty( $image ) ){
			return false;
		}

		if( $this->is_lazyload_active() && tie_get_option( 'lazy_load_ads' ) && strpos( $image, 'script' ) === false ){

			// Get the LazyLoad placeholder image
			$blank_image = tie_lazyload_placeholder('wide');

			$image = str_replace( 'src', 'src="'.$blank_image.'" loading="lazy" data-src', $image );
		}

		return $image;
	}


	/**
	 * lazyload_delay_adsense
	 */
	function lazyload_delay_adsense( ){

		if( tie_get_option( 'lazy_load_adsense' ) ){
			echo '
				<script type="text/javascript">
					function tieDownloadAdsenseJSAtOnload() {
						var element = document.createElement("script");
						element.src = "https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js";
						document.body.appendChild(element);
					}
					if (window.addEventListener){
						window.addEventListener("load", tieDownloadAdsenseJSAtOnload, false);
					}
					else if (window.attachEvent){
						window.attachEvent("onload", tieDownloadAdsenseJSAtOnload);
					}
					else{
						window.onload = tieDownloadAdsenseJSAtOnload;
					}
				</script>
			';
		}

	}


	/**
	 * lazyload_adsense
	 */
	function lazyload_adsense( $code = false ){

		if( ! empty( $code ) && tie_get_option( 'lazy_load_adsense' ) ){
			$code = str_replace( 'src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js', 'disabled="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js', $code );
		}

		return $code;
	}


	/**
	 * instagaram_lazyload
	 */
	function instagaram_lazyload( $image ){

		if( empty( $image ) ){
			return false;
		}

		if( $this->is_lazyload_active() ){

			// Get the LazyLoad placeholder image
			$blank_image = tie_lazyload_placeholder('square');

			$image = str_replace( 'src', 'src="'.$blank_image.'" loading="lazy" data-src', $image );
		}

		return $image;
	}


	/**
	 * inline_css_code
	 */
	function inline_css_code( $css = '' ){

		if( empty( $css ) ){
			return;
		}

		// Not active
		if( ! $this->is_lazyload_active() ){
			return $css;
		}

		// LazyLoad Image
		if( tie_get_option( 'lazy_load_img' ) ){
			$css .='
				.tie-slick-slider:not(.slick-initialized) .lazy-bg,
				.lazy-img[data-src],
				[data-lazy-bg] .post-thumb,
				[data-lazy-bg].post-thumb{
					background-image: url('. tie_get_option( 'lazy_load_img' ) .');
				}
			';
		}

		if( tie_get_option( 'lazy_load_dark_img' ) ){
			$css .='
				.dark-skin .tie-slick-slider:not(.slick-initialized) .lazy-bg,
				.dark-skin .lazy-img[data-src],
				.dark-skin [data-lazy-bg] .post-thumb,
				.dark-skin [data-lazy-bg].post-thumb{
					background-image: url('. tie_get_option( 'lazy_load_dark_img' ) .');
				}
			';
		}

		return $css;
	}


	/**
	 * Lazy Load Youtube Videos
	 */
	function lazyload_youtube_videos( $video_code = '' ){

		if( ! tie_get_option( 'lazy_load_youtube_videos' ) || empty( $video_code ) ){
			return $video_code;
		}

		if ( preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $video_code, $match ) ) {

			$video_id = ! empty( $match[1] ) ? $match[1] : '';

			if( empty( $video_id ) ){
				return $video_code;
			}

			$video_code = str_replace( '?feature=oembed', "?autoplay=1&feature=oembed", $video_code );
			$video_code = str_replace( '?rel=0', "?autoplay=1&?rel=0", $video_code );
			
			return '
				<style>
					.ytp-large-play-button {
						border: none;
						background-color: transparent;
						padding: 0;
						color: inherit;
						text-align: inherit;
						font-size: 100%;
						font-family: inherit;
						line-height: inherit;
						position: absolute;
						left: 50%;
						top: 50%;
						width: 68px;
						height: 48px;
						margin-left: -34px;
						margin-top: -24px;
						-webkit-transition: opacity .25s cubic-bezier(0,0,0.2,1);
						transition: opacity .25s cubic-bezier(0,0,0.2,1);
						z-index: 64;
						cursor: pointer;
					}
					.ytp-cued-thumbnail-overlay{
						cursor: pointer;
						position: absolute;
						top: 0;
					}
					.ytp-large-play-button-bg {
						-webkit-transition: fill .1s cubic-bezier(0.4,0,1,1),fill-opacity .1s cubic-bezier(0.4,0,1,1);
						transition: fill .1s cubic-bezier(0.4,0,1,1),fill-opacity .1s cubic-bezier(0.4,0,1,1);
						fill: #212121;
						fill-opacity: .8;
					}
					.ytp-cued-thumbnail-overlay:hover .ytp-large-play-button-bg {
						-webkit-transition: fill .1s cubic-bezier(0,0,0.2,1),fill-opacity .1s cubic-bezier(0,0,0.2,1);
						transition: fill .1s cubic-bezier(0,0,0.2,1),fill-opacity .1s cubic-bezier(0,0,0.2,1);
						fill: #f00;
						fill-opacity: 1;
					}
				</style>

				<div id="vid-'. $video_id .'-wrap"></div>
				<div class="ytp-cued-thumbnail-overlay" id="vid-'. $video_id .'">
					<img src="https://i.ytimg.com/vi/'. $video_id .'/maxresdefault.jpg" alt="" />
					<button class="ytp-large-play-button ytp-button" aria-label="Play"><svg height="100%" version="1.1" viewBox="0 0 68 48" width="100%"><path class="ytp-large-play-button-bg" d="M66.52,7.74c-0.78-2.93-2.49-5.41-5.42-6.19C55.79,.13,34,0,34,0S12.21,.13,6.9,1.55 C3.97,2.33,2.27,4.81,1.48,7.74C0.06,13.05,0,24,0,24s0.06,10.95,1.48,16.26c0.78,2.93,2.49,5.41,5.42,6.19 C12.21,47.87,34,48,34,48s21.79-0.13,27.1-1.55c2.93-0.78,4.64-3.26,5.42-6.19C67.94,34.95,68,24,68,24S67.94,13.05,66.52,7.74z" fill="#f00"></path><path d="M 45,24 27,14 27,34" fill="#fff"></path></svg></button>
				</div>

				<script>
					var theVideo = document.getElementById("vid-'. $video_id .'");
					theVideo.addEventListener( "click", function(e){
						theVideo.parentNode.removeChild(theVideo);
						var target = document.getElementById("vid-'. $video_id .'-wrap");
						var temp = document.createElement("div");
						temp.innerHTML = \''. $video_code  .'\';
						while (temp.firstChild) {
							target.appendChild(temp.firstChild);
						}

						e.preventDefault();
					});
				</script>
			';
		}

		return  $video_code;
	}

}


//
add_filter( 'init', 'jannah_optimization_lazyload_init' );
function jannah_optimization_lazyload_init(){

	// This method available in v4.0.0 and above
	if( method_exists( 'TIELABS_HELPER','has_builder' ) ){
		new JANNAH_OPTIMIZATION_LAZYLOAD();
	}
}
