<?php

/**
 * The public-facing functionality of the plugin.
 */
class Hooked_Editable_Content_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since	1.0.0
	 * @access	private
	 * @var		string	$plugin_name	The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since	1.0.0
	 * @access	private
	 * @var		string	$version	The current version of this plugin.
	 */
	private $version;
	
	/**
	 * Instance of Hooked_Editable_Content_Utility class.
	 *
	 * @since	1.0.0
	 * @access	private
	 * @var		Hooked_Editable_Content_Utility	$utilities	Instance of class giving access to helper functions.
	 */
	private $utilities;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since	1.0.0
	 * @param	string		$plugin_name	The name of the plugin.
	 * @param	string		$version		The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->utilities = new Hooked_Editable_Content_Utility();

	}
	
	/**
	 * Parse function called by hook into a function and variables.
	 *
	 * @since	1.0.0
	 * @param	string		$method		Name of function called.
	 * @param	array		$args		Any arguments to the called function.
	 */
	public function __call( $method, $args ) {
		
		// Only do something if method called is in the form 'display_hooked_content_[positive integer]'.
		$method = sanitize_text_field( $method );
		$hook_id = absint( str_replace( 'display_hooked_content_', '', $method ) );
		if ( $hook_id > 0 ) {
			return $this->display_hooked_content( $hook_id, $args[0] );
		}
		
	}

	/**
	 * Add active hooks for displaying content.
	 *
	 * @since	1.0.0
	 * @hooked init
	 */
	public function add_active_hooks() {
		
		if ( ! empty( $hooks = $this->utilities->get_hooks() ) ) {

			foreach ( $hooks as $hook ) {
				
				if ( $hook_info = get_post_meta( $hook->ID, 'hec_hook_info', true ) ) {

					// Set priority to default 10 if no priority supplied.
					$priority = empty( $hook_info['priority'] ) ? array( 10 ) : $hook_info['priority'];
					
					foreach ( $hook_info['name'] as $key => $hook_name ) {
						
						// Don't add actions / filters for any stored hook where a hook name has not been set.
						if ( ! empty( $hook_name ) ) {
						
							// Set default priority to 10 if not set
							$hook_priority = isset( $priority[ $key ] ) ? $priority[ $key ] : 10;
							
							if ( 'action' == $hook_info['type'] ) {
								add_action( $hook_name, array( $this, 'display_hooked_content_' . $hook->ID ), $hook_priority );
							} elseif ( 'filter' == $hook_info['type'] ) {
								add_filter( $hook_name, array( $this, 'display_hooked_content_' . $hook-> ID ), $hook_priority );
							}
							
						}
						
					}

				}
			}
			
		}
		
	}
	
	/**
	 * Display hooked content.
	 *
	 * @since	1.0.0
	 * @access	protected
	 * @param	int			$hook_id			id of hook for which content is to be displayed
	 * @param	string		$existing_content	existing content (only set if a filter is hooked to)
	 * @return	string		$content			amended content (only returned if a filter is hooked to)
	 */
	protected function display_hooked_content( $hook_id, $existing_content ) {

		if ( ! empty( $hook = get_post( $hook_id ) ) ) {

			$queried_object = get_queried_object();
			$post_type = isset( $queried_object->post_type ) ? $queried_object->post_type : 'no_post_type_set';
			
			// Get hook info.
			$hook_info = $this->utilities->get_hook_info( $hook->ID );
			
			$excluded_post_types = $this->utilities->get_excluded_post_types( $hook, $hook_info );
			
			// Don't display if this is an excluded post type for this hooked editor.
			if ( ! in_array( $post_type, $excluded_post_types  ) ) {
			
				// Get post id.
				if ( is_front_page() ) {
					$post_id = get_option( 'page_on_front' );					
				} else {
					$post_id = get_queried_object_id();
				}

				$content = false;
				
				// Get specific content if we have any and the option to hide is not set.
				if ( ! $hook_info['hide_specific_content'] ) {
					$content = get_post_meta( $post_id, 'hec_content_' . $hook->ID, true );
				}
				
				// If no specific content, then look at using generic content instead.
				if ( empty( $content ) ) {
					// Don't display hidden content if option to hide selected.
					if ( ! $hook_info['hide_generic_content'] ) {
						$content = $hook->post_content;
					}
				}
				
				// Display content.
				$output = $this->display_hook_content( $hook, $hook_info, $content, $existing_content );
				
				// Html comments for marking plugin content.
				$comment_start = '<!--hooked-editable-content_' . $hook->ID . '_start-->';
				$comment_end = '<!--hooked-editable-content_' . $hook->ID . '_end-->';
				
				$output = $comment_start . $output . $comment_end;
				
				if ( 'filter' == $hook_info['type'] ) {
					return $output;
				} else {
					echo ( empty( $content ) ? $comment_start . $comment_end : $output );
				}
				
			}
			
		}
		
	}
	
	/**
	 * Display content for a given hook.
	 *
	 * @since	1.0.0
	 * @access	protected
	 * @param	object	$hook				hook post
	 * @param	array	$hook_info			hook info post meta
	 * @param	string	$content			hooked content to be displayed
	 * @param	string	$existing_content	existing content (only set if a filter is hooked to)
	 * @return	string						updated content for display
	 */
	protected function display_hook_content( $hook, $hook_info, $content, $existing_content ) {
		
		// Set opening and closing tags.
		$opening_tag = apply_filters(
			'hec_content_opening_tag',
			'action' == $hook_info['type'] ? '<div class="hec-content hec-content-' . esc_attr( sanitize_title( $hook->post_title ) ) . '">' : '',
			$hook,
			$hook_info
		);
		$closing_tag = apply_filters( 'hec_content_closing_tag', 'action' == $hook_info['type'] ? '</div>' : '', $hook, $hook_info );
		
		// Apply 'the_content' filter if WP Editor has been used.
		if ( 'wp' == $hook_info['editor'] ) {
			
			$wpautop = false;
			if ( $hook_info['disable_wpautop'] && has_filter( 'the_content', 'wpautop' ) ) {
				$wpautop = true;
				remove_filter( 'the_content', 'wpautop' );
			}
			
			$content = apply_filters( 'the_content', $content );
			$content = str_replace( ']]>', ']]&gt;', $content );
			
			if ( $wpautop ) {
				add_filter( 'the_content', 'wpautop' );
			}
			
		}
		$content = $opening_tag . $content . $closing_tag;

		// If we are filtering, add in existing content where appropriate.
		if ( 'filter' == $hook_info['type'] ) {
			
			switch ( $hook_info['filter_content_placement'] ) {
				
				case 'before' :
					$content = $content . wp_kses_post( $existing_content );
					break;
					
				case 'after' :
					$content = wp_kses_post( $existing_content ) . $content;
					break;
				
			}
			
		}
		
		return $content;
	
	}
	
}
