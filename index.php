<?php
/*
 Plugin Name: Super Simple XML Image Sitemap
 Description: Your sitemap is created when you install the plugin. It is updated automatically when you save a post, and removed when you deactivate the plugin. No overhead and no database calls. Super Simple! View at yourwebsite.com/image-sitemap.xml.
 Author: Sam Pedraza
 Author URI: https://samuelpedraza.com
 Version: 0.1.1
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// create new sitemap, triggered on activation and adding new media to media folder
function super_simple_xml_image_sitemap_create_xml_sitemap(){

	// fetch all images from posts
	$all_custom_post_types = array_values(get_post_types(array('public' => true, '_builtin' => false)));
	$post_types = array_merge(array( 'page', 'post' ), $all_custom_post_types);
	$args = array('post_type' => $post_types, 'post_status' => 'publish', 'posts_per_page' => -1);

	$img_url_arr = array();

	$posts = new WP_Query($args);

	foreach ($posts->posts as $post):
		$arr_of_images = array();
		$permalink = get_permalink($post->ID);
		$featured_image = get_the_post_thumbnail_url($post->ID);

		if($featured_image):
			array_push($arr_of_images, array($permalink, $featured_image));
		endif;


		$dom = new domDocument;
		$dom->loadHtml($post->post_content);

		$images = $dom->getElementsByTagName('img');

			foreach ($images as $image):
				array_push($arr_of_images, array($permalink, $image->getAttribute('src')));
			endforeach;

			if(count($arr_of_images) > 0):
				array_push($img_url_arr, array($permalink, $arr_of_images));
			endif;

	endforeach;



	// construct XML page from images
	$xml = "<?xml version='1.0' encoding='UTF-8'?>
  <urlset xmlns='http://www.sitemaps.org/schemas/sitemap/0.9' xmlns:image='http://www.google.com/schemas/sitemap-image/1.1'>";
  foreach ( $img_url_arr as $img ):
			$xml .= "<url><loc>" . $img[0] . "</loc>";
			foreach ($img[1] as $img_src):
				$xml .= "<image:image><image:loc>" . $img_src[1] . "</image:loc></image:image>";
			endforeach;
			$xml .= "</url>";
  endforeach;


  $xml .= "</urlset>";

  file_put_contents(ABSPATH . "/image-sitemap.xml", $xml);
}

// remove sitemap on deactive
function super_simple_xml_image_sitemap_remove_sitemap_xml_file(){
  unlink(ABSPATH . "/image-sitemap.xml");
}

add_action("save_post", "super_simple_xml_image_sitemap_create_xml_sitemap");
register_activation_hook(__FILE__, 'super_simple_xml_image_sitemap_create_xml_sitemap');
register_deactivation_hook(__FILE__,'super_simple_xml_image_sitemap_remove_sitemap_xml_file');
