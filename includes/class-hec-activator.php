<?php

/**
 * Fired during plugin activation
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 */
class Hooked_Editable_Content_Activator {
	
	/**
	 * On activation: Registers post types, flushes rewrite rules and adds default capabilities.
	 *
	 * @since	1.0.0
	 */
	public static function activate() {
		Hooked_Editable_Content_Post_Types_Registration::register_post_types();
		flush_rewrite_rules();
		
		// Add all capabilites to administrator
		$utilities = new Hooked_Editable_Content_Utility();
		$utilities->add_remove_capabilities( 'administrator', 'add' );
		
	}

}
