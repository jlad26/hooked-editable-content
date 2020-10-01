<?php

/**
 *
 * Utility class
 *
 * Provides common helper functions.
 */
class Hooked_Editable_Content_Utility {

	/**
	 * Get all hooks.
	 *
	 * @since	1.0.0
	 * @param	array		$statuses	post statuses to be included
	 * @return	array		hooks
	 */
	public function get_hooks( $statuses = array( 'publish' ) ) {
		
		// Get stored hooks
		$args = array(
			'posts_per_page'	=> -1,
			'post_type'			=> 'hec_hook',
			'post_status'		=> $statuses,
			'orderby'			=> array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
		);
		if ( $hooks = get_posts( $args ) ) {
			return $hooks;
		} else {
			return array();
		}

	}
	
	/**
	 * Get hook info, setting defaults if necessary.
	 *
	 * @since	1.0.0
	 * @param	int		$post_id	post id
	 * @return	array	hook info post meta
	 */
	public function get_hook_info( $post_id ) {
		$hook_info = get_post_meta( $post_id, 'hec_hook_info', true );
		
		$hook_info_defaults = array(
			'name'						=>	array(),
			'description'				=>	'',
			'priority'					=>	array(),
			'type'						=>	'action',
			'editor'					=>	'wp',
			'disable_wpautop'			=>	0,
			'hide_specific_content'		=>	0,
			'hide_generic_content'		=>	0,
			'permissions'				=>	array(),
			'filter_content_placement'	=>	'before',
			'incl_post_types'			=>	array( 'post' => true, 'page' => true )
		);
		
		$hook_info = wp_parse_args( $hook_info, $hook_info_defaults );
		
		return $hook_info;
		
	}
	
	/**
	 * Get included post types for a hooked editor.
	 *
	 * @since	1.0.0
	 * @param	object		$hook		hook object
	 * @param	array		$hook_info	Array of hook info
	 * @return	array		Array of included post types
	 */
	public function get_included_post_types( $hook, $hook_info ) {
		$included_post_types = array();
		foreach ( $hook_info['incl_post_types'] as $post_type => $value ) {
			$included_post_types[] = $post_type;
		}
		return apply_filters( 'hec_included_post_types', $included_post_types, $hook, $hook_info );;
	}
	
	/**
	 * Add / remove capabilities for a given role.
	 *
	 * @since	1.0.0
	 * @param	string		$role_to_be_changed		role to be changed
	 * @param	string		$action					'add' or 'remove'
	 * @param	array		$caps					capabilities to be added / removed
	 */
	public function add_remove_capabilities( $role_to_be_changed, $action, $caps = array() ) {
		$role = get_role( $role_to_be_changed );
		if ( empty( $caps ) ) {
			$caps = array(
				// Primitive / meta cap.
				'create_hec_hooks',
											
				// Primitive caps used outside of map_meta_cap().
				'edit_hec_hooks',
				'edit_others_hec_hooks',
				'publish_hec_hooks',
				'read_private_hec_hooks',
				
				// Primitive caps used inside of map_meta_cap().
				'delete_hec_hooks',
				'delete_private_hec_hooks',
				'delete_published_hec_hooks',
				'delete_others_hec_hooks',
				'edit_private_hec_hooks',
				'edit_published_hec_hooks'
			);
		}
		foreach ( $caps as $cap ) {
			if ( 'add' == $action ) {
				$role->add_cap( $cap );
			} elseif( 'remove' == $action ) {
				$role->remove_cap( $cap );
			}
		}
	}

}
