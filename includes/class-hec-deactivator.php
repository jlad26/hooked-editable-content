<?php

/**
 * Fired during plugin deactivation
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 */
class Hooked_Editable_Content_Deactivator {

	/**
	 * On de-activation: Removes default capabilities.
	 *
	 * @since	1.0.0
	 */
	public static function deactivate() {
		$utilities = new Hooked_Editable_Content_Utility();
		$utilities->add_remove_capabilities( 'administrator', 'remove' );
	}

}
