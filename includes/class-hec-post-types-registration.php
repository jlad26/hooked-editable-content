<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 *
 * Post Types
 *
 * Registers post types.
 */
class Hooked_Editable_Content_Post_Types_Registration {

	/**
	 * Hook in methods.
	 *
	 * @since	1.0.0
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_types' ) );
	}
	
	/**
	 * Register core post types.
	 *
	 * @since	1.0.0
	 * @hooked init
	 */
	public static function register_post_types() {
		
		if ( ! is_blog_installed() || post_type_exists( 'hec_hook' ) ) {
			return;
		}
		
		$labels = array(			
			'name'					=> _x( 'Editors', 'post type general name', 'hooked-editable-content' ),
			'singular_name'			=> _x( 'Editor', 'post type singular name', 'hooked-editable-content' ),
			'menu_name'             => __( 'Hooked Editors', 'hooked-editable-content' ),
			'add_new'				=> _x( 'Add New', 'HEC hook item', 'hooked-editable-content' ),
			'add_new_item'			=> __( 'Add New Editor', 'hooked-editable-content'),
			'new_item'              => __( 'New Editor', 'hooked-editable-content' ),
			'edit_item'				=> __( 'Edit Editor', 'hooked-editable-content' ),
			'view_item'				=> __( 'View Editor', 'hooked-editable-content' ),
			'search_items'			=> __( 'Search Editors', 'hooked-editable-content' ),
			'not_found'				=> __( 'No Editors found', 'hooked-editable-content' ),
			'not_found_in_trash'	=> __('No Editors found in Trash', 'hooked-editable-content'), 
			'all_items'				=> __( 'Editors', 'hooked-editable-content' )
		);
		
		$args = array(
			'labels'				=>	$labels,
			'description'         	=>	__( 'This is where you enter the hooks to which you wish to attach editable content.', 'hooked-editable-content' ),
			'public'				=>	false,
			'publicly_queryable'	=>	false,
			'show_ui'				=>	true, 
			'show_in_menu'			=>	true, 
			'show_in_nav_menus'		=>	false,
			'menu_position'			=>	23,
			'menu_icon'				=>	'dashicons-paperclip',
			'query_var'				=>	false,
			'has_archive'			=>	false, 
			'hierarchical'			=>	false,
			'supports'				=>	array( 'title', 'editor' ),
			'map_meta_cap'			=>	true,
			'capability_type'		=>	'hec_hook'
		);
		
		register_post_type( 'hec_hook', $args );
		
	}

}

Hooked_Editable_Content_Post_Types_Registration::init();