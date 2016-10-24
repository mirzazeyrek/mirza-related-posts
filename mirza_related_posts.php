<?php
/*
Plugin Name: Mirza Related Posts with Ajax
Plugin URI: https://github.com/mirzazeyrek/mirza-related-posts
Description: Shows related posts via ajax based system.
Tags: related posts,related post, related articles
Version: 1.0
Author: Ugur Mirza Zeyrek
Author URI: http://mirzazeyrek.wordpress.com
*/

// Prevent direct calls
if(!defined('ABSPATH')) exit;

// Make sure there won't be conflicts.
if ( !class_exists('mirza_related_posts') ) {
	class mirza_related_posts {

		private $debug = false;

		function __construct() {

			if($this->debug) {
				ini_set('display_errors', 1);
				ini_set('display_startup_errors', 1);
				error_reporting(E_ALL);
			}

			// inject jquery
			add_action( 'wp_enqueue_scripts', array(&$this, 'enqueue_related_posts_jquery') );
			// enable ajax calls for logged out users
			add_action('wp_ajax_nopriv_get_related_posts', array(&$this, 'get_related_posts') );
			// enable ajax calls for logged in users
			add_action('wp_ajax_get_related_posts', array(&$this, 'get_related_posts') );
			// append related articles link into the content
			// https://codex.wordpress.org/Plugin_API/Filter_Reference/the_content
			add_action( 'the_content', array( &$this, 'create_related_posts_link' ), 10 );

		}


		public function create_related_posts_link($content = '') {
			// show related links only for single posts
			if(is_singular( 'post' )) {
				$append_html = "";
				$post_id = get_the_ID();
				$related_posts_link = "<a id='get-related-posts'  data-post='".$post_id."' name='".$post_id."'>See related posts</a>";
				$related_posts_div = "<div id='related-posts' style='display: block;'> </div>";
				$append_html .= $related_posts_link." ";
				$append_html .= $related_posts_div." ";
				return $content.$append_html;
			} else {
				return $content;
			}
		}

		public function enqueue_related_posts_jquery(){
			wp_enqueue_script( 'related-posts-jquery', plugins_url('/mirza-related-posts.js',__FILE__), array( 'jquery' ) );
			wp_localize_script( 'related-posts-jquery', 'mrp',
				array( 'ajax_url' => admin_url( 'admin-ajax.php' ),
				       'ajax_check' => wp_create_nonce('get-related-posts-nonce') )
			);
		}


		public function get_categories_by_post($post_id) {
			$categories_array = implode(",",wp_get_post_categories($post_id));
			return $categories_array;
		}


		function get_related_posts($post_id = null, $order_by = "DESC", $limit = 3){
			// prevent unauthorized requests by checking ajax referer
			check_ajax_referer('get-related-posts-nonce', 'security');

			// Get inputs
			$post_id    = (isset($_REQUEST["post_id"]))  ? $_REQUEST["post_id"]  : $post_id;
			$order_by   = (isset($_REQUEST["order_by"])) ? $_REQUEST["order_by"] : $order_by;
			$limit      = (isset($_REQUEST["limit"]))    ? $_REQUEST["limit"]    : $limit;

			// Input validation
			$return_array["status"]  = true;
			$return_array["message"] = false;
			if(!is_numeric($post_id)) {
				$return_array["status"]  = false;
				$return_array["message"] = "Post ID should be a numeric value. ";
			}

			if(!is_numeric($limit)) {
				$return_array["status"]  = false;
				$return_array["message"] .= "Limit should be a numeric value. ";
			}

			if(!in_array(strtoupper($order_by),['RAND','DESC','ASC'])) {
				$return_array["status"]  = false;
				$return_array["message"] .= "Order By is not a proper value. ";
			}

			// Fail if inputs are not proper
			if($return_array["status"] == false) {
				echo json_encode( $return_array );
				wp_die();
			}

			if ( !get_post_status ( $post_id ) ) {
				$return_array["status"]     = false;
				$return_array["message"]    = "The post is not exists anymore or you are in wrong place.";
				echo json_encode($return_array);
				wp_die();
			}

			$ajax_posts = array();
			$args = array(
				'numberposts'   => $limit,
				'category'      => $this->get_categories_by_post($post_id),
				'exclude'       => [$post_id],
				'order'         => "$order_by"
			);

			$get_posts = get_posts($args);
			if(count($get_posts)) {
				echo $this->stylize_related_posts( $get_posts );
			} else {
				$return_array["status"]     = false;
				$return_array["message"]    = "There are no related posts.";
				echo json_encode($return_array);
			}
			wp_die();
		}

		public function stylize_related_posts($get_posts) {
			$html_output = "";
			foreach ( $get_posts as $post ) {
				$id = $post->ID;
				$link = get_post_permalink($id);
				$title = $post->post_title;
				$content = substr( $post->post_content, 0, 150 )."...";
				$thumbnail = ( has_post_thumbnail( $post->ID ) ) ? get_the_post_thumbnail( $post->ID, "thumbnail","style=float: left; margin-right: 10px; margin-bottom: 10px; padding-left: 10px; padding-right: 20px; padding-top:10px;" ) : "<img style=\"float: left;  margin-right: 10px;  margin-bottom: 10px; padding-left: 10px; padding-right: 20px; padding-bottom: 10px;\" width=\"150\" height=\"150\" src=\"".plugin_dir_url( __FILE__ )."/no-images.png\" class=\"attachment-thumbnail size-thumbnail wp-post-image\" alt=\"no-thumbnail\">";

				$html_output .= "<div style='border-style: solid; border-color: black; height: 155px; padding: 10px;'>";
				if($thumbnail)
				$html_output .= "<div style='padding-right: 5px; clear: both;'>$thumbnail </div>";
				$html_output .= "<div> <a href='$link' alt='title'>$title</a> <br /> $content <br /> </div>";
				$html_output .= "</div>";
			}

			$return_array["status"]         = true;
			$return_array["related_posts"]  = $html_output;
			$return_array["message"]        = false;

			return json_encode($return_array);
		}

	}

	$mrp = new mirza_related_posts();
}