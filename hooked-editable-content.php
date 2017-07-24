<?php

/**
 * Plugin Name:       Hooked Editable Content
 * Plugin URI:        https://wordpress.org/plugins/hooked-editable-content/
 * Description:       Creates WP or text editors on Edit Post and Edit Page screens for content to be hooked into actions and filters.
 * Version:           1.0.2
 * Author:            Jon Anwyl
 * Author URI:        http://www.sneezingtrees.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       hooked-editable-content
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/*--------------------------------------------------------------------------------------------------*/
/* Code for integration with Freemius functionality (https://freemius.com/wordpress/insights/) */

// Create a helper function for easy SDK access.
function hec_fs() {
	global $hec_fs;

	if ( ! isset( $hec_fs ) ) {
		// Include Freemius SDK.
		require_once dirname(__FILE__) . '/freemius/start.php';

		$hec_fs = fs_dynamic_init( array(
			'id'                  => '1231',
			'slug'                => 'hooked-editable-content',
			'type'                => 'plugin',
			'public_key'          => 'pk_3577291d27a65031a555c1992de85',
			'is_premium'          => false,
			'has_addons'          => false,
			'has_paid_plans'      => false,
			'menu'                => array(
				'slug'           => 'edit.php?post_type=hec_hook',
				'account'        => false,
				'support'        => false,
				'contact'        => false,
			),
		) );
	}

    return $hec_fs;
}

// Init Freemius.
hec_fs();
// Signal that SDK was initiated.
do_action( 'hec_fs_loaded' );
// Hook in uninstall actions.
hec_fs()->add_action( 'after_uninstall', 'hec_fs_uninstall_cleanup' );

/**
 * Code to run on uninstall.
 * @hooked after_uninstall
 */
function hec_fs_uninstall_cleanup() {

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

	// Delete settings and storage.
	delete_option('hooked_editable_content_options');
	delete_option('hooked_editable_content_storage');
	
}

/*--------------------------------------------------------------------------------------------------*/
/* Main plugin code */

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-hec-activator.php
 */
function activate_hooked_editable_content() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-hec-activator.php';
	Hooked_Editable_Content_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-hec-deactivator.php
 */
function deactivate_hooked_editable_content() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-hec-deactivator.php';
	Hooked_Editable_Content_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_hooked_editable_content' );
register_deactivation_hook( __FILE__, 'deactivate_hooked_editable_content' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-hec.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_hooked_editable_content() {

	$plugin = new Hooked_Editable_Content();
	$plugin->run();

}
run_hooked_editable_content();
