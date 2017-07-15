<?php

/**
 * The admin-specific functionality of the plugin.
 */
class Hooked_Editable_Content_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since	1.0.0
	 * @access	private
	 * @var		string		$plugin_name	The ID of this plugin.
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
	 * @since		1.0.0
	 * @param		string		$plugin_name	The name of this plugin.
	 * @param		string		$version		The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->utilities = new Hooked_Editable_Content_Utility();

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since	1.0.0
	 * @hooked admin_enqueue_scripts
	 */
	public function enqueue_styles( $hook ) {

		global $post;
		
		// Stylesheet for hook custom post type.
		if ( $hook == 'post-new.php' || $hook == 'post.php' ) {
			if ( 'hec_hook' == $post->post_type ) {
				wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/hec-hook-admin.css', array(), $this->version, 'all' );
			}
		// Stylesheet for re-ordering hook editors.
		} elseif ( 'edit.php' == $hook && isset( $post->post_type ) ) {
			if ( 'hec_hook' == $post->post_type && 'menu_order title' == get_query_var('orderby') ) {
				wp_enqueue_style( $this->plugin_name . '-hook-ordering', plugin_dir_url( __FILE__ ) . 'css/hec-hook-ordering.css', array(), $this->version, 'all' );
			}
		}

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since	1.0.0
	 * @hooked admin_enqueue_scripts
	 */
	public function enqueue_scripts( $hook ) {
		
		global $post;
		
		if ( 'post-new.php' == $hook || 'post.php' == $hook ) {
			// JS for hook custom post type.
			if ( 'hec_hook' == $post->post_type ) {
				wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/hec-hook-admin.js', array( 'jquery' ), $this->version, false );
			}
			
			// JS for making ajax call to check hook firing.
			if ( 'post.php' == $hook ) {
				if ( ! in_array( $post->post_type, array( 'hec_hook', 'attaachment' ) ) ) {
					wp_enqueue_script( $this->plugin_name . '_check_hook_firing', plugin_dir_url( __FILE__ ) . 'js/hec-check-hook-firing.js', array( 'jquery' ), $this->version, false );
					wp_localize_script( $this->plugin_name . '_check_hook_firing', 'hooked_editable_content', array(
						'hecHookCheckNonce'	=>	wp_create_nonce( 'hec-check-hook-firing' )
					) );
				}
			}
			
		// JS for re-ordering hook editors.
		} elseif ( 'edit.php' == $hook && isset( $post->post_type ) ) {
			if ( 'hec_hook' == $post->post_type && 'menu_order title' == get_query_var('orderby') ) {
				wp_enqueue_script( $this->plugin_name . '-hook-ordering', plugin_dir_url( __FILE__ ) . 'js/hec-hook-ordering.js', array( 'jquery', 'jquery-ui-sortable' ), $this->version, false );
			}
		}
		
		

	}
	
	/**
	 * Add settings submenu page.
	 *
	 * @since	1.0.0
	 * @hooked admin_menu
	 */
	public function hec_settings_page() {
		
		add_submenu_page(
			'edit.php?post_type=hec_hook',
			/* translators: Plugin title */
			sprintf( __( '%s settings', 'hooked-editable-content' ), 'Hooked Editable Content' ),
			__( 'Settings', 'hooked-editable-content' ),
			'manage_options',
			'hec_settings',
			array( $this, 'hec_settings_page_html' )
		);
		
	}
	
	/**
	 * Render settings submenu page html.
	 *
	 * @since	1.0.0
	 */
	public function hec_settings_page_html() {
		
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		
		?>
		<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<form action="options.php" method="post">
		<?php
		settings_fields( 'hooked_editable_content' );
		do_settings_sections( 'hec-settings-page' );
		submit_button( 'Save Settings' );
		?>
		</form>
		</div>
		<?php
		
	}
	
	/**
	 * Initialize plugin settings.
	 *
	 * @since	1.0.0
	 * @hooked admin_init
	 */
	public function settings_init() {
		
		// Register settings.
		register_setting(
			'hooked_editable_content',
			'hooked_editable_content_options',
			array( 'sanitize_callback'	=>	array( $this, 'sanitize_settings' ) )
		);
		
		// Register uninstall section.
		add_settings_section(
			'hec_uninstall_section',
			__( 'Deletion on uninstall', 'hooked-editable-content' ),
			array( $this, 'hec_uninstall_section_cb' ),
			'hec-settings-page'
		);
		
		// Register "Delete specific content on uninstall" field.
		add_settings_field(
			'hec_rmv_content',
			__( 'Content deletion', 'hooked-editable-content' ),
			array( $this, 'hec_rmv_content_on_uninstall_cb' ),
			'hec-settings-page',
			'hec_uninstall_section'
		);
		
	}
	
	/**
	 * Display settings uninstall section content.
	 *
	 * @since	1.0.0
	 */
	public function hec_uninstall_section_cb( $args ) {
		
		// Get hooks with content stored against them (generic or specific).
		$hooks_with_content = array();
		$args = array(
			'posts_per_page'	=> -1,
			'post_type'			=> 'hec_hook',
			'post_status'		=> 'any'
		);

		if ( $hooks = get_posts( $args ) ) {
			foreach ( $hooks as $hook ) {
				$has_content = false;
				if ( ! empty( $hook->post_content ) ) {
					$has_content = true;
				} else {
					if ( $posts = $this->get_posts_with_specific_content( $hook->ID ) ) {
						$has_content = true;
					}
				}
				
				if ( $has_content ) {
					$link = '<a href="' . get_edit_post_link( $hook->ID ) . '">' . esc_html ( empty( $hook->post_title ) ? '(' . __( 'no title' ) . ')' : $hook->post_title  ) . '</a>';
					$hooks_with_content[] = $link . ' | ' . $hook->ID . ' | ' .  ucfirst( $hook->post_status );
				}
				
			}
		}
		
		// Display explanation.
		?>
		<p>
		<?php _e( 'When you uninstall the plugin, you can choose whether or not you want to delete all content that has been saved against your hooked editors.', 'hooked-editable-content' ); ?>
		</p>
		<p>
		<?php _e( 'If you choose <strong>NOT</strong> to delete your hooked content, then any hooked editors that have content stored against them will <strong>NOT</strong> be deleted. Those hooked editors and their associated content will remain in your database, although they will not be visible or accessible (unless you reinstall the plugin).', 'hooked-editable-content' ); ?>
		</p>
		<span style="font-weight: bold"><?php _e( 'Hooked editors currently with stored content', 'hooked-editable-content' ); ?></span><br />
		<?php
		// Display any hooks with content.
		if ( empty( $hooks_with_content ) ) {
			_e( 'None', 'hooked-editable-content' );
		} else {
			echo implode( '<br />', $hooks_with_content );
		}
	}
	

	/**
	 * Display "Content deletion" field.
	 *
	 * @since	1.0.0
	 */
	public function hec_rmv_content_on_uninstall_cb( $args ) {
		$settings = get_option( 'hooked_editable_content_options' );
		$content_delete = isset( $settings['content-delete'] ) ? $settings['content-delete'] : 0;
		?>
		<p><input type="radio" name="hooked_editable_content_options[content-delete]" value="1" <?php checked( $content_delete ); ?>/><span style="vertical-align: middle"><?php _e( 'Delete all hooked content on uninstall.' ); ?></span></p>
		<p><input type="radio" name="hooked_editable_content_options[content-delete]" value="0" <?php checked( ! $content_delete ); ?>/><span style="vertical-align: middle"><?php _e( 'Retain any hooked content on uninstall.' ); ?></span></p>
		<?php
		// Add in version so we don't remove it from saved settings.
		if ( isset( $settings['version'] ) ) {
		?>
		<input type="hidden" name="hooked_editable_content_options[version]" value="<?php echo esc_attr( $settings['version'] ); ?>" />
		<?php
		}
	}
	
	/**
	 * Sanitize plugin settings.
	 *
	 * @since	1.0.0
	 */
	public function sanitize_settings( $settings ) {
		
		if ( ! empty( $settings ) ) {
			foreach ( $settings as $key => $value ) {
				
				switch( $key ) {
					
					case 'content-delete' :
						$settings[ $key ] = ( intval( $value ) === 1 ? 1 : 0 );
						break;
						
					case 'version' :
						$settings[ $key ] = sanitize_text_field( $value );
						break;
					
				}
				
			}
		}
		
		return $settings;
		
	}
	
	/**
	 * Display settings errors.
	 *
	 * @since	1.0.0
	 * @hooked admin_notices
	 */
	public function display_settings_errors() {
		if ( function_exists( 'get_current_screen' ) ) {
			$screen = get_current_screen();
			if ( ! empty( $screen ) ) {
				if ( 'hec_hook_page_hec_settings' == $screen->id ) {
					settings_errors();
				}
			}
		}
	}
	
	/**
	 * Check plugin version and run upgrade processes on upgrade.
	 *
	 * @since	1.0.0
	 * @hooked plugins_loaded
	 */
	public function check_version() {
		$options = get_option( 'hooked_editable_content_options' );
		$prev_version = isset( $options['version'] ) ? $options['version'] : false;
		if ( $this->version !== $prev_version ) {
			
			$options['version'] = $this->version;
			update_option( 'hooked_editable_content_options', $options );
			
			// Run any plugin upgrade actions on admin init.
			add_action( 'admin_init', array( $this, 'plugin_upgrade_actions' ), $prev_version );
			
		}
	}
	
	/**
	 * Processes to run on plugin upgrade.
	 *
	 * @since	1.0.0
	 * @param	string		$prev_version	Previous version of plugin
	 */
	public function plugin_upgrade_actions( $prev_version ) {
		
	}
	
	/**
	 * Add meta boxes for the hec_hook custom post type.
	 *
	 * @since	1.0.0
	 * @param	object	$post	post object
	 * @hooked add_meta_boxes_hec_hook
	 */
	public function add_hook_meta_boxes( $post ) {

		// Get hook info, setting defaults if necessary.
		$hook_info = $this->utilities->get_hook_info( $post->ID );
		
		// Hook details meta box.
		add_meta_box(
			'hec-hook-details-mb',
			__( 'Editor details', 'hooked-editable-content' ),
			array( $this, 'display_hook_details_meta_box' ),
			'hec_hook',
			'advanced',
			'high',
			$hook_info
		);
		
		// Meta box for displaying / hiding specific content.
		add_meta_box(
			'hec-hook-specific-display-mb',
			__( 'Hide hooked content', 'hooked-editable-content' ),
			array( $this, 'display_hook_content_meta_box' ),
			'hec_hook',
			'side',
			'default',
			$hook_info
		);
		
		// Permissions meta box for editing specific content.
		add_meta_box(
			'hec-hook-editing-permissions-display-mb',
			__( 'Editing permissions', 'hooked-editable-content' ),
			array( $this, 'display_editing_permissions_meta_box' ),
			'hec_hook',
			'side',
			'default',
			$hook_info
		);
		
		// Meta box for excluding post types.
		add_meta_box(
			'hec-hook-excl-post-types-display-mb',
			__( 'Excluded post types', 'hooked-editable-content' ),
			array( $this, 'display_excluded_post_types_meta_box' ),
			'hec_hook',
			'side',
			'default',
			$hook_info
		);
		
		// Meta box showing which posts / pages have specific content.
		add_meta_box(
			'hec-hook-specific-locations-mb',
			__( 'Specific content locations', 'hooked-editable-content' ),
			array( $this, 'display_specific_locations_meta_box' ),
			'hec_hook',
			'advanced',
			'high',
			$hook_info
		);

	}
	
	/**
	 * Move hook details meta box to below title.
	 *
	 * @since	1.0.0
	 * @hooked edit_form_after_title
	 */
	public function move_hook_meta_boxes() {
		global $post, $wp_meta_boxes;
		if ( 'hec_hook' == $post->post_type ) {
			do_meta_boxes( get_current_screen(), 'advanced', $post );
			unset( $wp_meta_boxes['hec_hook']['advanced'] );
		}
	}

	/**
	 * When editing a hook post, add title, text editor (and opening tag to enable hiding of WP editor where necessary).
	 *
	 * @since	1.0.0
	 * @param	object	$post	post object
	 */
	public function add_hook_editor_title_and_text_editor( $post ) {
		if ( 'hec_hook' == $post->post_type ) {
			
			?><h2 style="padding: 0"><?php _e( 'Generic content (applied on all pages / posts)', 'hooked-editable-content' ); ?></h2><?php
			
			$hook_info = $this->utilities->get_hook_info( $post->ID );
			
			// Add class to hide text editor if WP editor selected.
			$textarea_class = 'wp' == $hook_info['editor'] ? ' class="hec-hide-editor"' : '';
				
			$textarea = '<textarea style="width: 100%;"' . $textarea_class . ' rows="5" id="hec-generic-content-text-editor" name="hec-generic-content-text-editor">';
			$textarea .= $post->post_content . '</textarea>';
			
			echo $textarea;
			
			// Add opening tag for WP editor.
			echo '<div id="hec-generic-content-wp-editor"' . ( 'text' == $hook_info['editor'] ? ' class="hec-hide-editor"' : '' ) . '>';

		}
	}
	
	/**
	 * Add closing tag to enable dynamic hiding (via css) of WP editor where necessary when editing hook posts.
	 *
	 * @since	1.0.0
	 * @param	object	$post	post object
	 */
	public function add_editor_closing_tag( $post ) {
		if ( 'hec_hook' == $post->post_type ) {
			?></div><?php
		}
	}
	
	/**
	 * Save generic hook content.
	 *
	 * @since	1.0.0
	 * @param	array	$data		An array of slashed post data.
	 * @param	array	$postarr	An array of sanitized, but otherwise unmodified post data.
	 * @return	array				An array of sanitized post data.
	 * @hooked wp_insert_post_data
	 */
	public function save_hook_text_editor_generic_content( $data, $postarr ) {
		if ( 'hec_hook'	== $data['post_type'] ) {
			
			if ( isset( $_POST['hec_hook'] ) ) {
				
				$hook_info = $_POST['hec_hook'];
				if ( isset( $hook_info['editor'] ) ) {
					
					// Check this is a text editor.
					$editor = sanitize_text_field( $hook_info['editor'] );
					if ( 'text' == $editor ) {
				
						// Sanitize content.
						$content = $_POST['hec-generic-content-text-editor'];
						$sanitized_content = $this->sanitize_text_editor_content( $content );

						$hook_id = 0;
						if ( isset( $postarr['ID'] ) ) { 
							$hook_id = absint( $postarr['ID'] );
						}
						
						$data['post_content'] = apply_filters( 'hec_sanitize_editor_content', $sanitized_content, $content, $hook_id, null, null, null );
						
					}
				}
				
			}
			
		}
		
		return $data;
			
	}
	
	/**
	 * Display hook details meta box.
	 *
	 * @since	1.0.0
	 * @param	object	$post	post object of the hook
	 * @param	object	$array	hook info post meta
	 */
	public function display_hook_details_meta_box( $post, $data ) {
		
		// Security.
		wp_nonce_field( basename( __FILE__ ), 'hec_hook_details_meta_box_nonce' );
		
		$hook_info = $data['args'];
		
		// Convert name and priority from array
		$hook_info['name'] = implode( ', ', $hook_info['name'] );
		$hook_info['priority'] = implode( ', ', $hook_info['priority'] );
		
		?>
		<div class="hec-field hec-text-input">
			<label for="hec-hook-name"><?php echo _x( 'Hook', 'hook info input', 'hooked-editable-content' ); ?></label><input id="hec-hook-name" type="text" name="hec_hook[name]" value="<?php echo $hook_info['name']; ?>" />
			<span><?php _e( 'The action / filter to which you want to hook content.', 'hooked-editable-content' ); ?><br /><?php _e( 'You may enter multiple comma-separated hooks - in case you want to display the same content twice, or you want to switch easily between themes.', 'hooked-editable-content' ); ?></span>
		</div>
		<div class="hec-field hec-textarea-input">
			<label for="hec-hook-description"><?php echo _x( 'Description', 'hook info input', 'hooked-editable-content' ); ?></label><textarea id="hec-hook-description" rows="3" name="hec_hook[description]"><?php echo $hook_info['description']; ?></textarea>
			<span><?php _e( 'Displayed above the content editor on pages / posts.', 'hooked-editable-content' ); ?></span>
		</div>
		<div class="hec-field hec-text-input">
			<label for="hec-hook-priority"><?php _e( 'Priority', 'hooked-editable-content' ); ?></label><input id="hec-hook-priority" type="text" name="hec_hook[priority]" size="2" value="<?php echo $hook_info['priority']; ?>" />
			<span><?php _e( 'Leave blank to set to default priority 10.', 'hooked-editable-content' ); ?></span>
		</div>
		<div class="hec-field hec-select-input">
			<label for="hec-hook-editor"><?php _e( 'Editor', 'hooked-editable-content' ); ?></label><select id="hec-hook-editor" name="hec_hook[editor]" />
				<option value="wp" <?php selected( $hook_info['editor'], 'wp' ); ?>><?php _e( 'WP Editor', 'hooked-editable-content' ); ?></option>
				<option value="text" <?php selected( $hook_info['editor'], 'text' ); ?>><?php echo _x( 'Text', 'editor choice dropdown', 'hooked-editable-content' ); ?></option>
			</select>
		</div>
		<div class="hec-field hec-select-input">
			<label for="hec-hook-type"><?php _e( 'Hook type', 'hooked-editable-content' ); ?></label><select id="hec-hook-type" name="hec_hook[type]" />
				<option value="action" <?php selected( $hook_info['type'], 'action' ); ?>><?php _e( 'Action', 'hooked-editable-content' ); ?></option>
				<option value="filter" <?php selected( $hook_info['type'], 'filter' ); ?>><?php _e( 'Filter', 'hooked-editable-content' ); ?></option>
			</select>
		</div>
		<div class="hec-field hec-select-input hide-if-js">
			<label for="hec-hook-filter_content_placement"><?php _e( 'Content', 'hooked-editable-content' ); ?></label><select id="hec-hook-filter_content_placement" name="hec_hook[filter_content_placement]" />
				<option value="before" <?php selected( $hook_info['filter_content_placement'], 'before' ); ?>><?php echo _x( 'Add before', 'filter content placement dropdown', 'hooked-editable-content' ); ?></option>
				<option value="after" <?php selected( $hook_info['filter_content_placement'], 'after' ); ?>><?php echo _x( 'Add after', 'filter content placement dropdown', 'hooked-editable-content' ); ?></option>
				<option value="replace" <?php selected( $hook_info['filter_content_placement'], 'replace' ); ?>><?php echo _x( 'Replace existing', 'filter content placement dropdown', 'hooked-editable-content' ); ?></option>
			</select>
			<span><?php _e( 'Whether the hooked content should be added before, after or instead of the existing content.', 'hooked-editable-content' ); ?></span>
			<noscript><?php _e( 'Only used for filters, not actions.', 'hooked-editable-content' ); ?></noscript>
		</div>
		<?php
	}
	
	/**
	 * Display meta box for displaying / hiding specific content.
	 *
	 * @since	1.0.0
	 * @param	object	$post	post object of the hook
	 * @param	object	$array	hook info post meta
	 */
	public function display_hook_content_meta_box( $post, $data ) {
		
		// Security - no nonce because we use nonce set in hook details meta box.
		
		// Set hook info.
		$hook_info = $data['args'];
		
		?>
		<input id="hec-hook-hide_specific_content" type="checkbox" name="hec_hook[hide_specific_content]" value="1" <?php checked( $hook_info['hide_specific_content'] ); ?>/>
		<label for="hec-hook-hide_specific_content"><?php _e( 'Specific', 'hooked-editable-content' ); ?></label><br />
		<input id="hec-hook-hide_generic_content" type="checkbox" name="hec_hook[hide_generic_content]" value="1" <?php checked( $hook_info['hide_generic_content'] ); ?>/>
		<label for="hec-hook-hide_generic_content"><?php _e( 'Generic', 'hooked-editable-content' ); ?></label>
		<p><?php _e( 'Check to disable display of content on the front end', 'hooked-editable-content' ); ?></p>
		<?php
		
	}
	
	/**
	 * Display meta box for displaying / hiding specific content.
	 *
	 * @since	1.0.0
	 * @param	object	$post	post object of the hook
	 * @param	object	$array	hook info post meta
	 */
	public function display_editing_permissions_meta_box( $post, $data ) {
		
		// Security - no nonce because we use nonce set in hook details meta box.
		
		// Set hook info.
		$hook_info = $data['args'];
		
		$roles = get_editable_roles();
	
		if ( ! empty( $roles ) ) {
			
			?>
			<p>
				<?php _e( 'Choose which roles can edit hooked specific content on a page / post. Own means only the user\'s own posts, while Others means the user can edit specific content in others\' posts.', 'hooked-editable-content' ); ?>
			</p>
			<table>
				<thead>
					<tr>
						<th><?php _e( 'Role', 'hooked-editable-content' ); ?></th>
						<th><?php _e( 'Own', 'hooked-editable-content' ); ?></th>
						<th><?php _e( 'Others', 'hooked-editable-content' ); ?></th>
					</tr>
				</thead>
				<tbody>
			<?php
			
			$permission_types = array( 'own', 'others' );
			
			foreach ( $roles as $key => $role ) {
				
				$is_admin = 'administrator' == $key ? true : false;
				
				// Work out whether checkboxes should be ticked or not
				$checked = array(
					'own'		=>	false,
					'others'	=>	false
				);
				
				if ( $is_admin ) {
					$checked = array(
						'own'		=>	true,
						'others'	=>	true
					);
				} elseif ( isset ( $hook_info['permissions'][ $key ] ) ) {
					foreach ( $permission_types as $permission_type ) {
						$checked[ $permission_type ] = false;
						if ( isset ( $hook_info['permissions'][ $key ][ $permission_type ] ) ) {
							if ( $hook_info['permissions'][ $key ][ $permission_type ] ) {
								$checked[ $permission_type ] = true;
							}
						}
					}
				}
				
				// Display the row.
				?>
				<tr>
					<td>
						<?php echo esc_html( $role['name'] ); ?>
					</td>
					<td class="hec-own">
						<input type="checkbox" name="hec_hook[permissions][<?php echo esc_attr( $key ); ?>][own]" value="1" <?php checked( $checked['own'] ); if ( $is_admin ) { echo ' disabled'; } ?>/>
					</td>
					<td class="hec-others">
						<input type="checkbox" name="hec_hook[permissions][<?php echo esc_attr( $key ); ?>][others]" value="1" <?php checked( $checked['others'] ); if ( $is_admin ) { echo ' disabled'; } ?>/>
					</td>
				</tr>
				<?php
				
			}
			
			?>
				</tbody>
			</table>
			<?php
			
		} else {
			_e( 'You do not have permission to edit permissions.', 'hooked-editable-content' );
		}
		
	}
	
	/**
	 * Display meta box for showing locations of specific content.
	 *
	 * @since	1.0.0
	 * @param	object	$hook	post object of the hook
	 * @param	object	$array	hook info post meta
	 */
	public function display_specific_locations_meta_box( $hook, $data ) {
		
		// Security - no nonce because we use nonce set in hook details meta box.
		
		// Set hook info.
		$hook_info = $data['args'];
		
		// Get posts with specific content.		
		$posts = $this->get_posts_with_specific_content( $hook->ID );
		
		echo '<p>' . __( 'Pages / posts with specific content saved against this hook, displayed in the form', 'hooked-editable-content' ) . '&nbsp;';
		echo '<span style="display: inline-block;">' . __( 'Title | ID | Post type | Status', 'hooked-editable-content' ) . '</span></p>';
		
		if ( ! empty( $posts ) ) {
			foreach ( $posts as $post ) {
				$link = '<a href="' . get_edit_post_link( $post->ID ) . '">' . esc_html ( empty( $post->post_title ) ? '(' . __( 'no title' ) . ')' : $post->post_title  ) . '</a>';
				echo $link . ' | ' . $post->ID . ' | ' .  $post->post_type . ' | ' . ucfirst( $post->post_status ) . '<br />';
			}
		} else {
			echo '<span>' . __( 'None' ) . '</span>';
		}
	
	}
	
	/**
	 * Display meta box for excluding post types that editor is available on.
	 *
	 * @since	1.0.0
	 * @param	object	$post	post object of the hook
	 * @param	object	$array	hook info post meta
	 */
	public function display_excluded_post_types_meta_box( $post, $data ) {
		
		// Security - no nonce because we use nonce set in hook details meta box.
		
		// Set hook info.
		$hook_info = $data['args'];
		
		$hook_info['excl_post_types'] = implode( ', ', $hook_info['excl_post_types'] );
		
		echo '<p>' . __( 'Add any post types (comma-separated) where this editor should not be available.', 'hooked-editable-content' ) . '</p>';
		?>
		<input id="hec-excl-post-types" type="text" name="hec_hook[excl_post_types]" value="<?php echo $hook_info['excl_post_types']; ?>" />
		<?php
	}
	
	/**
	 * Save hook meta box data on post save.
	 *
	 * @since	1.0.0
	 * @param	int		$post_id	post id
	 * @hooked save_post_hec_hook
	 */
	public function save_hook_info( $post_id ) {
		
		// Check we have defined data.
		if ( ! isset( $_POST['hec_hook_details_meta_box_nonce'] ) || ! isset( $_POST['hec_hook'] ) ) {
			return;
		}

		// Return if autosave.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		
		// Verify nonce.
		if ( ! wp_verify_nonce( $_POST['hec_hook_details_meta_box_nonce'], basename( __FILE__ ) ) ) {
			return;
		}
		
		// Check the user's permissions.
		if ( ! current_user_can( 'edit_hec_hook', $post_id ) ) {
			return;
		}
		
		$roles = get_editable_roles();

		// Sanitize the posted data, generate error messages, and save.
		if ( is_array( $_POST['hec_hook'] ) ) {

			// Do all values apart from the permissions - we sanitize them next.
			foreach ( $_POST['hec_hook'] as $key => $posted_value ) {
				
				$value = '';
				
				if ( 'permissions' == $key ) {
					continue;
				}
				
				switch ( $key ) {
					
					case 'name' :
						// Sanitize and convert to array of comma-separated values
						$value = str_replace( ' ', '', sanitize_text_field( $posted_value ) );
						if ( empty( $value ) ) {
							$args = array(
								'message'		=>	__( 'Hooked editor will not function because no valid hook name was entered.', 'hooked-editable-content' )
							);
							HEC_Admin_Notice_Manager::add_notice( $args );
						}
						$value = explode( ',', $value );
						break;
						
					case 'description' :
						$value = wp_kses_post( $posted_value );
						break;
						
					case 'priority' :
						// Sanitize and convert comma-separated list of priorities to array
						$priority_error = false;
						$value = str_replace( ' ', '', sanitize_text_field( $posted_value ) );
						if ( empty( $value ) ) {
							$value = array();
						} else {
							$value = explode( ',', $value );
							foreach ( $value as $priority_key => $priority ) {
								$value[ $priority_key ] = absint( $priority );
								if ( $value[ $priority_key ] < 1 ) {
									$value[ $priority_key ] = 10;
									$priority_error = true;
								}
							}
							
							if ( $priority_error ) {
								if ( count( $value ) > 1 ) {
									$priority_error_message = __( 'One or more of your entered priorities was not a positive integer. Default value of 10 has been used instead.', 'hooked-editable-content' );
								} else {
									$value = array();
									$priority_error_message = __( 'Priority was not a positive integer. Default value of 10 will be used.', 'hooked-editable-content' );
								}
								$args = array(
									'message'	=>	$priority_error_message,
									'type'		=>	'warning'
								);
								HEC_Admin_Notice_Manager::add_notice( $args );
							}
						}
						break;
						
					case 'editor' :
						$value = sanitize_text_field( $posted_value );
						if ( 'text' != $value ) {
							$value = 'wp';
						}
						break;
						
					case 'type' :
						$value = sanitize_text_field( $posted_value );
						if ( 'filter' != $value ) {
							$value = 'action';
						}
						break;
						
					case 'filter_content_placement' :
						$value = sanitize_text_field( $posted_value );
						if ( ! in_array( $value, array( 'after', 'replace' ) ) ) {
							$value = 'before';
						}
						break;
						
					case 'hide_specific_content' :
					case 'hide_generic_content' :
						$value = absint( $posted_value );
						if ( $value > 0 ) {
							$value = 1;
						}
						break;
						
					case 'excl_post_types' :
						// Sanitize and convert to array of comma-separated values
						$value = str_replace( ' ', '', sanitize_text_field( $posted_value ) );
						$value = explode( ',', $value );
						break;
						
				}
				$hook_info[ $key ] = $value;
			}

			// Get current permissions.
			$current_hook_info = $this->utilities->get_hook_info( $post_id );
			$current_permissions = $current_hook_info['permissions'];
			
			// Sanitize and update permissions data.
			if ( ! empty( $roles ) ) {
				foreach ( $roles as $key => $role ) {
					
					// If others is selected then set both others and own to true.
					if ( isset( $_POST['hec_hook']['permissions'][ $key ]['others'] ) ) {
						$current_permissions[ $key ] = array(
							'own'		=>	1,
							'others'	=>	1
						);
					} else { // Others not selected so only need to work out how to set own.
						$current_permissions[ $key ] = array(
							'own'		=>	isset( $_POST['hec_hook']['permissions'][ $key ]['own'] ) ? 1 : 0,
							'others'	=>	0
						);
					}

				}
			}
			
			$hook_info['permissions'] = $current_permissions;

			// Update post meta.
			update_post_meta( $post_id, 'hec_hook_info', $hook_info );
			
		}
		
	}
	
	/**
	 * Add in post updated messages for hec_hook post type.
	 *
	 * @since	1.0.0
	 * @param	array	$messages		Array of messages
	 * @return	array					Updated messages
	 */
	public function add_post_updated_messages( $messages ) {
		
		$messages['hec_hook'] = array(
			 0 => '', // Unused. Messages start at index 1.
			 1 => __( 'Hooked editor updated.', 'hooked-editable-content' ),
			 4 => __( 'Hooked editor updated.', 'hooked-editable-content' ),
			/* translators: %s: date and time of the revision */
			 5 => isset($_GET['revision']) ? sprintf( __( 'Hooked editor restored to revision from %s.', 'hooked-editable-content' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			 6 => __( 'Hooked editor published. The editor will now appear on your edit page and edit post screens.', 'hooked-editable-content' ),
			 7 => __( 'Hooked editor saved.', 'hooked-editable-content' ),
			 8 => __( 'Hooked editor submitted.', 'hooked-editable-content' ),
			10 => __( 'Draft updated.', 'hooked-editable-content' ),
		);
		return $messages;
	}
	
	/**
	 * Check if current user can edit hook content on this page / post.
	 *
	 * @since	1.0.0
	 * @access	protected
	 * @param	object	$post		post object
	 * @param	object	$hook		hook object
	 * @param	array	$hook_info	hook info post meta
	 * @return	bool
	 */
	protected function user_can_edit_content( $post, $hook, $hook_info ) {
		
		$can_edit = false;
		
		// Get current user roles.
		$user_meta = get_userdata( $user_id = get_current_user_id() );
		$user_roles = $user_meta->roles;
		
		if ( ! empty( $user_roles ) ) {
		
			if ( in_array( 'administrator', $user_roles ) ) {
				
				// Administrators can edit all content.
				$can_edit = true;
				
			} else {

				foreach( $user_roles as $user_role ) {

					// If this user's role has permissions settings...
					if ( isset( $hook_info['permissions'][ $user_role ] ) ) {
						
						if ( $post->post_author == $user_id ) {
							if ( $hook_info['permissions'][ $user_role ]['own'] ) {
								$can_edit = true;
							}
						} else {
							if ( $hook_info['permissions'][ $user_role ]['others'] ) {
								$can_edit = true;
							}
						}
						
					}
						
				}
				
			}
			
		}

		return apply_filters( 'hec_user_can_edit', $can_edit, $post, $hook, $hook_info );
	}
	
	/**
	 * Get any posts / pages with specific content for a given hooked editor.
	 *
	 * @since	1.0.0
	 * @access	protected
	 * @param	int		$hook_id		id of hook
	 * @return	array	Array of post objects
	 */
	protected function get_posts_with_specific_content( $hook_id ) {

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
			'post_type'			=>	'any',
			'meta_query'		=>	array(
										array(
											'key'			=>	'hec_content_' . $hook_id,
											'compare'		=>	'!=',
											'value'			=>	''
										)
									)
		);
		
		return get_posts( $args );
		
	}
	
	/**
	 * Add hook content editors.
	 *
	 * @since	1.0.0
	 * @param	object	$post		post object
	 * @hooked edit_page_form, edit_form_advanced
	 */
	public function add_hook_content_editors( $post ) {
		
		if ( ! empty( $hooks = $this->utilities->get_hooks() ) ) {

			foreach ( $hooks as $hook ) {
				
				// Get hook info.
				$hook_info = $this->utilities->get_hook_info( $hook->ID );
				
				$excluded_post_types = $this->utilities->get_excluded_post_types( $hook, $hook_info );
				
				// Don't display if this is an excluded post type for this hooked editor.
				if ( ! in_array( $post->post_type, $excluded_post_types  ) ) {
				
					if ( $this->user_can_edit_content( $post, $hook, $hook_info ) ) {

						// If specific content is hidden for this hook, then only show the editor if the user can edit the hook.
						if ( ! $hook_info['hide_specific_content'] || current_user_can( 'edit_hec_hook', $hook->ID ) ) {

							?>
							<div class="hec-content-editor-container" style="margin-bottom: 20px">
							<input type="hidden" class="hec-hook-id" value="<?php echo esc_attr( $hook->ID ); ?>" />
							<?php
							
								// Security.
								wp_nonce_field( basename( __FILE__ ), 'hec_hook_content_editor_' . $hook->ID . '_nonce' );

								// Display title and description.
								$this->display_hook_intro_info( $hook, $hook_info, $post );

								// Display editor.
								if ( 'wp' == $hook_info['editor'] ) {
								
									wp_editor(
										get_post_meta( $post->ID, 'hec_content_' . $hook->ID, true ),
										'hec_content_editor_' . $hook->ID,
										$settings = array(
											'textarea_rows'		=> 10
										)
									);
								
								} else {
									
									$this->specific_content_text_editor( $hook, $post );
									
								}
							
							?></div><?php
							
						}
					
					}
					
				}

			}

		}

	}
	
	/**
	 * Render textarea style editor.
	 *
	 * @since	1.0.0
	 * @access	protected
	 * @param	object	$hook	hook post object
	 * @param	object	$post	post object
	 */
	protected function specific_content_text_editor( $hook, $post ) {
		$content = get_post_meta( $post->ID, 'hec_content_' . $hook->ID, true );
		$textarea = '<textarea style="width: 100%;" rows="5" id="' . esc_attr( 'hec_content_editor_' . $hook->ID ) . '" name="' . esc_attr( 'hec_content_editor_' . $hook->ID ) . '">';
		$textarea .= $content . '</textarea>';
		echo $textarea;
	}
	
	/**
	 * Display hook title, description and messages as needed.
	 *
	 * @since	1.0.0
	 * @access	protected
	 * @param	object	$hook	hook object
	 * @param	array	$hook_info	hook info post meta
	 * @param	post	$post	post object
	 */
	protected function display_hook_intro_info( $hook, $hook_info, $post ) {
		?>
			<h3 style="margin-bottom: 0.2em">
				<?php echo __( 'Hooked Editor: ', 'hooked-editable-content' ) . esc_html( $hook->post_title  ); ?>
			</h3>
			<div id="hec-hook-fired-msg-<?php echo $hook->ID; ?>" style="margin-bottom: 0.2em; display: none;">
			<?php
			$post_group = 'page' == $post->post_type ? __( 'page', 'hooked-editable-content' ) : __( 'post', 'hooked-editable-content' );
			printf(
				/* translators: 1: opening span tag 2: closing span tag */
				__( 'Hook firing %1$sOK%2$s on this %3$s.', 'hooked-editable-content' ),
				'<span style="font-weight: bold; color: green">',
				'</span>',
				$post_group
			);
			?>
			</div>
			<div id="hec-hook-failed-msg-<?php echo $hook->ID; ?>" style="margin-bottom: 0.2em; display: none;">
			<?php
			printf(
				/* translators: 1: opening span tag 2: closing span tag 3: 'page' / 'post' depending on context 4: 'page' / 'post' depending on context */
				__( '%1$sWarning%2$s Hook does not appear to fire on this %3$s. Check that the hook name has been entered correctly and appears in this %4$s template.', 'hooked-editable-content' ),
				'<span style="font-weight: bold; color: red">',
				'</span>',
				$post_group,
				$post_group
			);
			?>
			</div>
		<?php
		// Add in hidden content warning if needed.
		if ( $hook_info['hide_specific_content'] ) {
			$edit_hook_link = '<a href="' . get_edit_post_link( $hook->ID ) . '">' . __( 'Edit Hooked Editor', 'hooked-editable-content' ) . '</a>';
			?>
			<div style="margin-bottom: 0.2em">
				<?php
				printf(
					/* translators: 1: opening span tag 2: closing span tag 3: opening span tag 4: closing span tag */
					__( '%1$sNB%2$s Content specific to a page / post is currently %3$shidden%4$s for this editor.', 'hooked-editable-content' ),
					'<span style="font-weight: bold; color: red">',
					'</span>',
					'<span style="font-weight: bold; color: red">',
					'</span>'
				);
				echo ' ';
				/* translators: Edit hook link */
				printf( __( 'You can change this setting on the %s screen.', 'hooked-editable-content' ), $edit_hook_link ); ?>
			</div>
			<?php
		}
		
		// Display description.
		if ( ! empty( $hook_info['description'] ) ) {
			?><div style="margin-bottom: 0.2em"><?php echo esc_html( $hook_info['description'] ); ?></div><?php
		}
		
	}
	
	/**
	 * Process ajax checking of hooks firing.
	 *
	 * @since	1.0.1
	 * @hooked wp_ajax_hec_check_hook_firing
	 */
	public static function check_hook_firing() {
		
		// Check nonce.
		check_ajax_referer( 'hec-check-hook-firing', 'hecHookCheckNonce' );
		
		// Check we have what we need.
		if ( ! isset( $_POST['postID'] ) || ! isset( $_POST['hookIds'] ) ) {
			die(-1);
		}
		
		// Sanitize values and set up return data array.
		$post_id = intval( $_POST['postID'] );
		$return_data = array();
		if ( is_array( $_POST['hookIds'] ) ) {
			foreach( $_POST['hookIds'] as $hook_id ) {
				$return_data[ intval( $hook_id ) ] = 0;
			}
		}

		// Check capability.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			die(-1);
		}

		$preview_link = get_preview_post_link( $post_id );
		
		$cookies = array();

		foreach ( $_COOKIE as $name => $value ) {
			$cookies[] = new WP_Http_Cookie( array( 'name' => $name, 'value' => $value ) );
		}

		// Get page / post preview content.
		$request = wp_remote_get( $preview_link, array( 'cookies' => $cookies ) );
		$response_code = wp_remote_retrieve_response_code( $request );

		// Check we have a valid response code.
		if ( 200 == $response_code ) {
		
			$body = wp_remote_retrieve_body( $request );
			
			// Check to see if hooks have fired.
			if ( ! empty( $return_data ) ) {
				foreach( $return_data as $hook_id => $value ) {
					if ( false !== strpos( $body, '<!--hooked-editable-content_' . $hook_id . '_start-->' ) ) {
						$return_data[ $hook_id ] = 1;
					}
				}
			}
		
		} else {
			// Set return data to empty so no message is given at all about whether hook is firing since page can't be loaded.
			$return_data = array();
		}
		
		die( json_encode( $return_data ) );
		
	}
	
	
	/**
	 * Save hooked content.
	 *
	 * @since	1.0.0
	 * @param	int		$post_id	post id
	 * @param	object	$post		post object
	 * @hooked save_post
	 */
	public function save_hooked_content( $post_id, $post ) {

		// Return if autosave.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ){
			return;
		}
		
		// Save content for each hook.
		if ( ! empty( $hooks = $this->utilities->get_hooks() ) ) {
			
			foreach ( $hooks as $hook ) {
				
				// Get hook info.
				$hook_info = $this->utilities->get_hook_info( $hook->ID );
				
				// Check nonce.
				if ( ! isset( $_POST['hec_hook_content_editor_' . $hook->ID . '_nonce'] ) ) {
					continue;
				}
				if ( ! wp_verify_nonce( sanitize_text_field( $_POST['hec_hook_content_editor_' . $hook->ID . '_nonce'] ), basename( __FILE__ ) ) ) {
					continue;
				}
				
				// Check user can edit.
				if ( ! $this->user_can_edit_content( $post, $hook, $hook_info ) ) {
					continue;
				}
				
				// Sanitize and save data.
				if ( isset( $_POST[ 'hec_content_editor_' . $hook->ID ] ) ) {
					if ( empty( $_POST[ 'hec_content_editor_' . $hook->ID ] ) ) {
						delete_post_meta( $post_id, 'hec_content_' . $hook->ID );
					} else {
						$sanitized_content = $this->sanitize_editor_content( $_POST[ 'hec_content_editor_' . $hook->ID ], $hook, $hook_info, $post );
						update_post_meta( $post_id, 'hec_content_' . $hook->ID, $sanitized_content );
					}
				}

			}
			
		}
	
	}
	
	/**
	 * Sanitize content submitted for save by hook editor (WP or text).
	 * For WP Editor, wp_kses_post is used.
	 * For text editor sanitize_text_field is used then all html tags and line breaks are removed.
	 *
	 * @since	1.0.0
	 * @access	protected
	 * @param	string		$content		submitted content
	 * @param	object		$hook			hook post object
	 * @param	array		$hook_info		hook info post meta
	 * @param	object		$post			post object
	 * @return	string						sanitized content
	 */
	protected function sanitize_editor_content( $content, $hook, $hook_info, $post ) {
	
		if ( 'wp' == $hook_info['editor'] ) {
			$sanitized_content = wp_kses_post( $content );
		} else {
			$sanitized_content = $this->sanitize_text_editor_content( $content );
		}
		
		return apply_filters( 'hec_sanitize_editor_content', $sanitized_content, $content, $hook->ID, $hook, $hook_info, $post );
		
	}
	
	/**
	 * Default sanitization for text editor. Sanitizes, removes all html and line breaks.
	 *
	 * @since	1.0.0
	 * @access	protected
	 * @param	string		$content	content
	 * @return	string					sanitized content
	 */
	protected function sanitize_text_editor_content( $content ) {
		return str_replace( array( "\r", "\n"), ' ', wp_strip_all_tags( wp_kses_post( $content ) ) );
	}
	
	/**
	 * Delete any specific content for a given hook on its deletion.
	 *
	 * @since	1.0.0
	 * @param	int		$postid			id of post being deleted
	 * @hooked before_delete_post
	 */
	public function delete_specific_content( $postid ) {
		if ( $post = get_post( $postid ) ) {
			if ( 'hec_hook' == $post->post_type ) {
				delete_post_meta_by_key( 'hec_content_' . $post->ID );
			}
		}
	}
	
	/**
	 * Add columns to hooks posts list.
	 *
	 * @since	1.0.0
	 * @param	array		$columns	array of columns info
	 * @return	array					array of columns info
	 * @hooked manage_hec_hook_posts_columns
	 */
	public function manage_hooks_columns( $columns ) {
		
		// Remove Date column so we can add it at the end.
		unset( $columns['date'] );
		
		$new_columns = array(
			'hook_name'				=>	_x( 'Hook', 'hook posts list table', 'hooked-editable-content' ),
			'hook_priority'			=>	__( 'Priority', 'hooked-editable-content' ),
			'hook_type'				=>	_x( 'Hook type', 'hook posts list table', 'hooked-editable-content' ),
			'hook_hidden_content'	=>	__( 'Hidden content', 'hooked-editable-content' ),
			'date'					=>	__( 'Date' )
		);
		return array_merge( $columns, $new_columns );
	}
	
	/**
	 * Add hook custom column content.
	 *
	 * @since	1.0.0
	 * @param	string	$column		column label
	 * @return	string	value to display in column
	 * @hooked manage_hec_hook_posts_custom_column
	 */
	public function manage_hooks_custom_column( $column ) {
		
		global $post;
		$hook_info = $this->utilities->get_hook_info( $post->ID );
		
		// Display default priority of 10 if not set
		if ( empty( $hook_info['priority'] ) ) {
			$hook_info['priority'] = array( 10 );
		}
		
		// Capitalise first letter of hook type.
		$hook_info['type'] = ucfirst( $hook_info['type'] );
		
		$output = false;
		
		switch ( $column ) {
			
			case 'hook_name' :
			case 'hook_priority' :
				
				// Handle conversion from array
				$identifier = str_replace( 'hook_', '', $column );
				$output = implode( '<br />', $hook_info[ $identifier ] );
				break;
			
			case 'hook_type' :
				$output = $hook_info[ str_replace( 'hook_', '', $column ) ];
				break;
				
			case 'hook_hidden_content' :
				$hidden_content = array();
				if ( $hook_info['hide_specific_content'] ) {
					$hidden_content[] = _x( 'Specific', 'hook posts list table', 'hooked-editable-content' );
				}
				if ( $hook_info['hide_generic_content'] ) {
					$hidden_content[] = _x( 'Generic', 'hook posts list table', 'hooked-editable-content' );
				}
				if ( empty ( $hidden_content ) ) {
					$output = __( '--', 'hooked-editable-content' );
				} else {
					$output = implode( '<br />', $hidden_content );
				}
				break;
			
		}
		
		echo $output;
		
	}
	
	/**
	 * Make custom columns sortable in hooks posts list.
	 *
	 * @since	1.0.0
	 * @param	array		$columns		sortable columns
	 * @return	array						sortable columns
	 * @hooked manage_edit-hec_hook_sortable_columns
	 */
	public function manage_hooks_sortable_columns( $columns ) {
		$new_columns = array(
			'hook_name'	=> 'hook_name',
			'hook_type'	=> 'hook_type'
		);
		return array_merge( $columns, $new_columns );
	}
	
	/**
	 * Hook sorting link.
	 *
	 * @since	1.0.0
	 * @param	array		$views		Views
	 * @return	array					Updated views
	 * @hooked views_edit-hec_hook
	 */
	public function hook_sorting_link( $views ) {
		global $post_type, $wp_query;

		if ( ! current_user_can( 'edit_others_hec_hooks' ) ) {
			return $views;
		}

		$class            = ( isset( $wp_query->query['orderby'] ) && 'menu_order title' === $wp_query->query['orderby'] ) ? 'current' : '';
		$query_string     = remove_query_arg( array( 'orderby', 'order' ) );
		$query_string     = add_query_arg( 'orderby', urlencode( 'menu_order title' ), $query_string );
		$query_string     = add_query_arg( 'order', urlencode( 'ASC' ), $query_string );
		$views['byorder'] = '<a href="' . esc_url( $query_string ) . '" class="' . esc_attr( $class ) . '">' . __( 'Re-order hooked editors', 'hooked-editable-content' ) . '</a>';

		return $views;
	}
	
	/**
	 * Process ajax ordering.
	 * Based on Simple Page Ordering by 10up (https://wordpress.org/extend/plugins/simple-page-ordering/)
	 *
	 * @since	1.0.0
	 * @hooked wp_ajax_hec_hook_ordering
	 */
	public static function hook_ajax_ordering() {
		
		// Check and make sure we have what we need.
		if ( empty( $_POST['id'] ) || ( !isset( $_POST['previd'] ) && !isset( $_POST['nextid'] ) ) ) {
			die(-1);
		}

		// Real post?
		if ( ! $post = get_post( intval( $_POST['id'] ) ) ) {
			die(-1);
		}

		// Check capability.
		if ( ! current_user_can( 'edit_others_hec_hooks' ) ) {
			die(-1);
		}

		// Badly written plug-in hooks for save post can break things.
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			error_reporting( 0 );
		}

		$previd = empty( $_POST['previd'] ) ? false : (int) $_POST['previd'];
		$nextid = empty( $_POST['nextid'] ) ? false : (int) $_POST['nextid'];
		$start = empty( $_POST['start'] ) ? 1 : (int) $_POST['start'];
		$excluded = empty( $_POST['excluded'] ) ? array( $post->ID ) : array_filter( (array) $_POST['excluded'], 'intval' );

		$new_pos = array(); // Store new positions for ajax.
		$return_data = new stdClass;

		// Attempt to get the intended parent... if either sibling has a matching parent ID, use that.
		$parent_id = $post->post_parent;
		$next_post_parent = $nextid ? wp_get_post_parent_id( $nextid ) : false;
		if ( $previd == $next_post_parent ) {	// If the preceding post is the parent of the next post, move it inside.
			$parent_id = $next_post_parent;
		} elseif ( $next_post_parent !== $parent_id ) {  // Otherwise, if the next post's parent isn't the same as our parent, we need to study.
			$prev_post_parent = $previd ? wp_get_post_parent_id( $previd ) : false;
			if ( $prev_post_parent !== $parent_id ) {	// If the previous post is not our parent now, make it so!
				$parent_id = ( $prev_post_parent !== false ) ? $prev_post_parent : $next_post_parent;
			}
		}
		// If the next post's parent isn't our parent, it might as well be false (irrelevant to our query).
		if ( $next_post_parent !== $parent_id ) {
			$nextid = false;
		}

		$max_sortable_posts = (int) apply_filters( 'hec_editor_ordering_limit', 50 );	// Should reliably be able to do about 50 at a time.
		if ( $max_sortable_posts < 5 ) {	// Don't be ridiculous!
			$max_sortable_posts = 50;
		}

		// We need to handle all post stati, except trash (in case of custom stati).
		$post_stati = get_post_stati( array(
			'show_in_admin_all_list' => true,
		) );

		$siblings_query = array(
			'depth'						=> 1,
			'posts_per_page'			=> $max_sortable_posts,
			'post_type' 				=> $post->post_type,
			'post_status' 				=> $post_stati,
			'post_parent' 				=> $parent_id,
			'orderby' 					=> array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
			'post__not_in'				=> $excluded,
			'update_post_term_cache'	=> false,
			'update_post_meta_cache'	=> false,
			'suppress_filters' 			=> true,
			'ignore_sticky_posts'		=> true,
		);
		$siblings = new WP_Query( $siblings_query ); // fetch all the siblings (relative ordering)

		// Don't waste overhead of revisions on a menu order change (especially since they can't *all* be rolled back at once).
		remove_action( 'pre_post_update', 'wp_save_post_revision' );

		foreach( $siblings->posts as $sibling ) :

			// Don't handle the actual post.
			if ( $sibling->ID === $post->ID ) {
				continue;
			}

			// If this is the post that comes after our repositioned post, set our repositioned post position and increment menu order.
			if ( $nextid === $sibling->ID ) {
				wp_update_post( array(
					'ID'			=> $post->ID,
					'menu_order'	=> $start,
					'post_parent'	=> $parent_id,
				) );
				$ancestors = get_post_ancestors( $post->ID );
				$new_pos[$post->ID] = array(
					'menu_order'	=> $start,
					'post_parent'	=> $parent_id,
					'depth'			=> count( $ancestors ),
				);
				$start++;
			}

			// If repositioned post has been set, and new items are already in the right order, we can stop.
			if ( isset( $new_pos[ $post->ID ] ) && $sibling->menu_order >= $start ) {
				$return_data->next = false;
				break;
			}

			// Set the menu order of the current sibling and increment the menu order.
			if ( $sibling->menu_order != $start ) {
				wp_update_post( array(
					'ID' 			=> $sibling->ID,
					'menu_order'	=> $start,
				) );
			}
			$new_pos[ $sibling->ID ] = $start;
			$start++;

			if ( !$nextid && $previd == $sibling->ID ) {
				wp_update_post( array(
					'ID' 			=> $post->ID,
					'menu_order' 	=> $start,
					'post_parent' 	=> $parent_id
				) );
				$ancestors = get_post_ancestors( $post->ID );
				$new_pos[ $post->ID ] = array(
					'menu_order'	=> $start,
					'post_parent' 	=> $parent_id,
					'depth' 		=> count($ancestors) );
				$start++;
			}

		endforeach;

		// Max per request
		if ( !isset( $return_data->next ) && $siblings->max_num_pages > 1 ) {
			$return_data->next = array(
				'id' 		=> $post->ID,
				'previd' 	=> $previd,
				'nextid' 	=> $nextid,
				'start'		=> $start,
				'excluded'	=> array_merge( array_keys( $new_pos ), $excluded ),
			);
		} else {
			$return_data->next = false;
		}

		$return_data->new_pos = $new_pos;

		die( json_encode( $return_data ) );
	}
	
}
