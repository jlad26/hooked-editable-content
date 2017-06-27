<?php

/**
 * Plugin Name:       Hooked Editable Content
 * Plugin URI:        https://wordpress.org/plugins/hooked-editable-content/
 * Description:       Hook editable content onto actions and filters
 * Version:           1.0.1
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
