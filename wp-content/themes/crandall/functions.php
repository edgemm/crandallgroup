<?php
/**
 * Theme functions for NOO Citilights Child Theme.
 *
 * @package    NOO Citilights Child Theme
 * @version    1.0.0
 * @author     Kan Nguyen <khanhnq@nootheme.com>
 * @copyright  Copyright (c) 2014, NooTheme
 * @license    http://opensource.org/licenses/gpl-2.0.php GPL v2 or later
 * @link       http://nootheme.com
 */

// If you want to override function file ( like noo-property.php, noo-agent.php ... ),
// you should copy function file to the same folder ( like /framework/admin/ ) on child theme, then use similar require_one 
// statement like this code.
// require_once dirname(__FILE__) . '/framework/admin/noo-property.php';

// override parent function files
require_once dirname(__FILE__) . '/framework/admin/noo-agent.php';
require_once dirname(__FILE__) . '/framework/admin/noo-property.php';
require_once dirname(__FILE__) . '/framework/functions/noo-html-shortcodes.php';
require_once dirname(__FILE__) . '/framework/functions/noo-style.php';
require_once dirname(__FILE__) . '/framework/functions/noo-utilities.php';


// ENQUEUE 'EM IF YOU GOT 'EM
function crandall_scripts() {
	// single property scripts
	if ( is_singular( 'noo_property' ) ) wp_enqueue_script( 'property-scripts', get_stylesheet_directory_uri() . '/assets/js/properties.js', array(), '1.0.0', true );
}
add_action( "wp_enqueue_scripts", "crandall_scripts" );

// THUMBNAILS
add_image_size('cg-property-thumb', 226, 127, true);
add_image_size('cg-property-medium', 700, 394, false);
add_image_size('cg-agent', 585, 820, false);
add_image_size('cg-builder-logo', 585, 820, false);


// SHORTCODES


// property contact form default message
function property_contact_message() {
	$message = "Hello, please send me more information about the property " . get_the_title() . " that I found on your website. Thanks!";
	return $message;
}
add_shortcode( "property_contact_message", "property_contact_message" );


// TAXONOMIES

// register taxonomies
if ( ! function_exists( 'taxonomy_builders' ) ) {

function taxonomy_builders() {

	$labels = array(
		'name'                       => 'Builders',
		'singular_name'              => 'Builder',
		'menu_name'                  => 'Builder',
		'all_items'                  => 'All Builders',
		'parent_item'                => 'Parent Builder',
		'parent_item_colon'          => 'Parent Builder:',
		'new_item_name'              => 'New Builder Name',
		'add_new_item'               => 'Add New Builder',
		'edit_item'                  => 'Edit Builder',
		'update_item'                => 'Update Builder',
		'separate_items_with_commas' => 'Separate builders with commas',
		'search_items'               => 'Search Builders',
		'add_or_remove_items'        => 'Add or remove builders',
		'choose_from_most_used'      => 'Choose from the most used builders',
		'not_found'                  => 'Not Found',
	);
	$args = array(
		'labels'                     => $labels,
		'hierarchical'               => true,
		'public'                     => true,
		'show_ui'                    => true,
		'show_admin_column'          => true,
		'show_in_nav_menus'          => true,
		'show_tagcloud'              => true,
	);

	register_taxonomy( 'builder', array( 'noo_property' ), $args );

}

// Hook into the 'init' action
add_action( 'init', 'taxonomy_builders', 0 );

}

// class for adding meta fields to taxonomies
require_once("Tax-meta-class/Tax-meta-class.php");

$builders_meta_config = array(
	'id'			=> 'meta_box_builders',
	'title'			=> 'Builders Meta Box',
	'pages'			=> array( 'builder' ),
	'context'		=> 'normal',
	'fields'		=> array(),
	'local_images'		=> true,
	'use_with_theme'	=> true
);

$builders_meta = new Tax_Meta_Class( $builders_meta_config );

$builders_meta_prefix = "builders_meta_";

$builders_meta->addText( $builders_meta_prefix . "contact_name", array( "name" => __( "Contact Name ", "tax-meta" ), "desc" => "Builder's Contact Name" ) );
$builders_meta->addText( $builders_meta_prefix . "contact_phone", array( "name" => __( "Contact Phone ", "tax-meta" ), "desc" => "Builder's Phone" ) );
$builders_meta->addText( $builders_meta_prefix . "contact_email", array( "name" => __( "Contact Email ", "tax-meta" ), "desc" => "Builder's Email" ) );
$builders_meta->addImage( $builders_meta_prefix . "photo", array( "name" => __( "Builder Photo ", "tax-meta" ) ) );
$builders_meta->addText( $builders_meta_prefix . "sort", array( "name" => __( "Sort Order ", "tax-meta" ), "desc" => "Order to be placed on Builder page (ascending)" ) );

$builders_meta->Finish();



?>
