<?php
/**
 * Plugin Name: Big Boom Rule Of Three
 * Description: Uses shortcode to insert a responsive, custom-defined rule of 3 (or 4) into a page or post
 * Version: 1.6.0
 * Author: Big Boom Design
 * Author URI: https://bigboomdesign.com
 */


/**
 * Load main class
 */
require_once ro3_dir("lib/class-ro3.php");

/**
 * Admin routine
 */

if( is_admin() && !( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
	
	# Scripts and styles
	add_action("admin_enqueue_scripts", array('RO3', 'admin_enqueue'));

	# define sections and fields for options page
	add_action( 'admin_init', array( 'RO3_Options', 'register_settings' ) );

	# add main plugin options page to the WP Admin menu
	add_action('admin_menu', 'ro3_settings_page');
	function ro3_settings_page() {
		add_menu_page('Rule of Three Settings', 'Rule of Three', 'manage_options', 'ro3_settings', 'ro3_do_settings_page');
	}

	function ro3_do_settings_page(){ RO3_Options::settings_page(); }

} #end: admin routines


/**
 * Front end routine
 */

else{
	# scripts and styles
	add_action('wp_enqueue_scripts', array('RO3','enqueue'));
	
	# Main container shortcode
	add_shortcode("rule-of-three", array('RO3', 'container_html'));
	
} # end: front end routines

/**
 * AJAX actions
 * 
 * - wp_ajax_ro3_get_posts_for_type
 * - wp_ajax_get_block_data_for_post
 */

/**
 * Output the post select dropdown HTML when post type radio button is toggled in backend settings
 * 
 * @param 	string 		$_POST['post_type'] 	The post type being selected
 * @param	string 		$_POST['section'] 		Which Rule of Three section we are selecting posts for
 * @since 	1.0.0
 */
add_action('wp_ajax_ro3_get_posts_for_type', 'ro3_get_posts_for_type');
function ro3_get_posts_for_type(){
	RO3::select_post_for_type($_POST['post_type'], $_POST['section']);
	die();

}

/**
 * Output a JSON string of post data when a post is selected in backend settings
 *
 * @type 	JSON 	$output {
 *
 *		@type 	string 		$post_title 	The title of the selected post
 * 		@type 	string 		$thumb			The URL of the selected post's featured image
 * 		@type 	string 		$url			The permalink of the selected post
 * 		@type 	string 		$excerpt		The seleted post's excerpt
 * }
 *
 * @param 	string 		$_POST['post_id'] 		The ID of the selected post
 * @since 	1.0.0
 */
add_action('wp_ajax_ro3_get_block_data_for_post', 'ro3_get_block_data_for_post');
function ro3_get_block_data_for_post(){
	$post = get_post($_POST['post_id']);
	$out = array(
		'post_title' => $post->post_title,
		'thumb' => wp_get_attachment_url( get_post_thumbnail_id($post->ID) ),
		'url' => get_permalink($post->ID),
	);
	$excerpt = $post->post_excerpt ? $post->post_excerpt : substr($post->post_content,0,250);
	$out['post_excerpt'] = $excerpt;
	echo json_encode($out);
	die();

}

/**
 * Helper functions
 * 
 * - ro3_url()
 * - ro3_dir()
 */

/**
 * Return the URL (ro3_url) or folder path (ro3_dir) for this plugin
 * 
 * @param 	string 	$s 	Optional string to append to the path
 * @since 	1.0.0
 */
function ro3_url( $s ) { return plugins_url($s, __FILE__ ); }
function ro3_dir( $s ) { return plugin_dir_path( __FILE__ ) . $s; }
