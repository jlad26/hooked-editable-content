<?php

/**
 * Fired when the plugin is uninstalled.
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Check capability.
if ( ! current_user_can( 'activate_plugins' ) ) {
	return;
}

// Delete all hooks and specific content where appropriate.
$args = array(
	'posts_per_page'	=>	-1,
	'post_type'			=>	'hec_hook',
	'post_status'		=>	array(
								'publish',
								'future',
								'draft',
								'pending',
								'private',
								'trash',
								'auto-draft',
								'inherit'
							),
);

if ( $hooks = get_posts( $args ) ) {

	// Work out whether we need to retain hooks with content.
	$settings = get_option( 'hooked_editable_content_options' );
	$retain_content = true;
	if ( isset( $settings['content-delete'] ) ) {
		if ( $settings['content-delete'] ) {
			$retain_content = false;
		}
	}
	
	$args = array(
		'posts_per_page'	=>	-1,
		'post_status'		=>	array(
									'publish',
									'future',
									'draft',
									'pending',
									'private',
									'trash',
									'auto-draft',
									'inherit'
								),
		'post_type'			=>	'any'
	);
	
	foreach ( $hooks as $hook ) {
		
		$remove = true;
		if ( $retain_content ) {
			if ( ! empty( $hook->post_content ) ) {
				$remove = false;
			} else {
				
				$args['meta_query'] = array(
					array(
						'key'			=>	'hec_content_' . $hook->ID,
						'compare'		=>	'!=',
						'value'			=>	''
					)
				);
				
				if ( $posts_with_content = get_posts( $args ) ) {
					$remove = false;
				}
			}
		}
		
		if ( $remove ) {
			delete_post_meta_by_key( 'hec_content_' . $hook->ID );
			wp_delete_post( $hook->ID, true );
		}
		
	}
	
}

// Delete options.
delete_option('hooked_editable_content_options');
