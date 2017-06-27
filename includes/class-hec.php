<?php

/**
 * The core plugin class.
 */
class Hooked_Editable_Content {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Hooked_Editable_Content_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		$this->plugin_name = 'hec';
		$this->version = '1.0.0';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Hooked_Editable_Content_Loader. Orchestrates the hooks of the plugin.
	 * - Hooked_Editable_Content_i18n. Defines internationalization functionality.
	 * - Hooked_Editable_Content_Admin. Defines all hooks for the admin area.
	 * - Hooked_Editable_Content_Public. Defines all hooks for the public side of the site
	 * - Hooked_Editable_Content_Utility. Defines helper function common to other classes.
	 * - Hooked_Editable_Content_Post_Types_Registration. Registers the hec_hook post type.
	 * - HEC_Admin_Notice_Manager. Provides admin notice functionality to the rest of the plugin.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-hec-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-hec-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-hec-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-hec-public.php';
		
		/**
		 * Utility class responsible for common functions.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-hec-utility.php';
		
		/**
		 * The class responsible for registering post types.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-hec-post-types-registration.php';

		$this->loader = new Hooked_Editable_Content_Loader();
		
		/**
		 * Load and initialize the class responsible for admin notices in this plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/admin-notice-manager/class-admin-notice-manager.php';
		HEC_Admin_Notice_Manager::init( array(
			'manager_id'	=>	$this->plugin_name,
			'text_domain'	=>	'hooked-editable-content',
			'version'		=>	$this->version
		) );

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Hooked_Editable_Content_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Hooked_Editable_Content_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Hooked_Editable_Content_Admin( $this->get_plugin_name(), $this->get_version() );

		// Enqueue scripts and styles.
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		
		// Add Settings.
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'hec_settings_page', 10 );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'settings_init', 10 );
		$this->loader->add_action( 'admin_notices', $plugin_admin, 'display_settings_errors', 10 );
		
		// Plugin upgrade processes.
		$this->loader->add_action( 'plugins_loaded', $plugin_admin, 'check_version', 10 );
		
		// Add hook meta boxes.
		$this->loader->add_action( 'add_meta_boxes_hec_hook', $plugin_admin, 'add_hook_meta_boxes', 10 );
		
		// Move hook meta boxes to above default editor.
		$this->loader->add_action( 'edit_form_after_title', $plugin_admin, 'move_hook_meta_boxes', 10 );
		
		// Add text editor when editing hook posts.
		$this->loader->add_action( 'edit_form_after_title', $plugin_admin, 'add_hook_editor_title_and_text_editor', 10 );
		$this->loader->add_action( 'edit_form_after_editor', $plugin_admin, 'add_editor_closing_tag', 10 );
		
		// Save generic hook content.
		$this->loader->add_action( 'wp_insert_post_data', $plugin_admin, 'save_hook_text_editor_generic_content', 10, 2 );
		
		// Save hec_hook post type meta data.
		$this->loader->add_action( 'save_post_hec_hook', $plugin_admin, 'save_hook_info', 10 );
		
		// Save hec_hook post type meta data.
		$this->loader->add_filter( 'post_updated_messages', $plugin_admin, 'add_post_updated_messages', 10 );
		
		// Add specific hook content editors (WP and text) to pages and posts.
		$this->loader->add_action( 'edit_page_form', $plugin_admin, 'add_hook_content_editors', 10 );
		$this->loader->add_action( 'edit_form_advanced', $plugin_admin, 'add_hook_content_editors', 10 );

		// Save specific hook content.
		$this->loader->add_action( 'save_post', $plugin_admin, 'save_hooked_content', 10, 2 );
		
		// Delete specific hook content on hook deletion.
		$this->loader->add_filter( 'before_delete_post', $plugin_admin, 'delete_specific_content' );
		
		// Add columns to hook posts list table.
		$this->loader->add_action( 'manage_hec_hook_posts_columns', $plugin_admin, 'manage_hooks_columns', 10 );
		$this->loader->add_action( 'manage_hec_hook_posts_custom_column', $plugin_admin, 'manage_hooks_custom_column', 10 );
		$this->loader->add_action( 'manage_edit-hec_hook_sortable_columns', $plugin_admin, 'manage_hooks_sortable_columns', 10 );
		
		// Handle hook ordering.
		$this->loader->add_filter( 'views_edit-hec_hook', $plugin_admin, 'hook_sorting_link', 10 );
		$this->loader->add_action( 'wp_ajax_hec_hook_ordering', $plugin_admin, 'hec_hook_ajax_ordering', 10 );
		
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Hooked_Editable_Content_Public( $this->get_plugin_name(), $this->get_version() );
		
		// Add in active hooks for displaying hooked content
		$this->loader->add_action( 'init', $plugin_public, 'add_active_hooks' );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Hooked_Editable_Content_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
