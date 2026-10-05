<?php
/**
 * Click to Chat - Admin Settings & Menu Router
 *
 * @package Dragwyb_Click_To_Chat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_menu', 'dctc_add_settings_page' );
add_action( 'wp_ajax_dctc_save_settings', 'dctc_save_settings' );
add_action( 'admin_enqueue_scripts', 'dctc_admin_scripts' );

/**
 * Register Click to Chat main menu and organized submenus.
 * Default landing page is the AI Assistant dashboard.
 *
 * @return void
 */
function dctc_add_settings_page() {
	// Top-level menu: Clicking "Click to Chat" opens AI Assistant by default
	add_menu_page(
		__( 'Click to Chat', 'dragwyb-click-to-chat' ),
		__( 'Click to Chat', 'dragwyb-click-to-chat' ),
		'manage_options',
		'dragwyb-click-to-chat',
		'dctc_render_ai_assistant_page',
		'dashicons-format-chat',
		90
	);

	// 1. Submenu: AI Assistant (matches parent slug to rename the first submenu item)
	add_submenu_page(
		'dragwyb-click-to-chat',
		__( 'AI Assistant', 'dragwyb-click-to-chat' ),
		__( 'AI Assistant', 'dragwyb-click-to-chat' ),
		'manage_options',
		'dragwyb-click-to-chat',
		'dctc_render_ai_assistant_page'
	);

	// 2. Submenu: Channels (Social multi-channel widget builder)
	add_submenu_page(
		'dragwyb-click-to-chat',
		__( 'Channels', 'dragwyb-click-to-chat' ),
		__( 'Channels', 'dragwyb-click-to-chat' ),
		'manage_options',
		'dragwyb-click-to-chat-channels',
		'dctc_channels_page_html'
	);

	// 3. Submenu: Guide (Documentation & Walkthroughs for Channels, AI, Support)
	add_submenu_page(
		'dragwyb-click-to-chat',
		__( 'Guide', 'dragwyb-click-to-chat' ),
		__( 'Guide', 'dragwyb-click-to-chat' ),
		'manage_options',
		'dragwyb-click-to-chat-guide',
		'dctc_guide_page_html'
	);

	// 4. Submenu: Settings (Dedicated settings page with General & Import/Export tabs)
	add_submenu_page(
		'dragwyb-click-to-chat',
		__( 'Settings', 'dragwyb-click-to-chat' ),
		__( 'Settings', 'dragwyb-click-to-chat' ),
		'manage_options',
		'dragwyb-click-to-chat-settings',
		'dctc_settings_page_html'
	);

	// Hidden submenu alias for dragwyb-click-to-chat-ai direct links
	add_submenu_page(
		null,
		__( 'AI Assistant', 'dragwyb-click-to-chat' ),
		__( 'AI Assistant', 'dragwyb-click-to-chat' ),
		'manage_options',
		'dragwyb-click-to-chat-ai',
		'dctc_render_ai_assistant_page'
	);
}

/**
 * Render AI Assistant admin screen.
 *
 * @return void
 */
function dctc_render_ai_assistant_page() {
	if ( class_exists( 'DCTC_AI_Module' ) ) {
		DCTC_AI_Module::get_instance()->dctc_ai_render_admin_page();
	} else {
		require_once DCTC_PLUGIN_DIR . 'admin/ai/dctc-ai-dashboard.php';
	}
}

/**
 * Enqueue scripts and styles for Channels and Settings screens.
 *
 * @param string $hook Admin page hook suffix.
 * @return void
 */
function dctc_admin_scripts( $hook ) {
	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
	$is_channels = ( 'dragwyb-click-to-chat-channels' === $page ) || false !== strpos( (string) $hook, 'dragwyb-click-to-chat-channels' );
	$is_settings = ( 'dragwyb-click-to-chat-settings' === $page ) || false !== strpos( (string) $hook, 'dragwyb-click-to-chat-settings' );
	$is_guide    = ( 'dragwyb-click-to-chat-guide' === $page ) || false !== strpos( (string) $hook, 'dragwyb-click-to-chat-guide' );

	if ( ! $is_channels && ! $is_settings && ! $is_guide ) {
		return;
	}

	// Base styles
	wp_enqueue_style( 'dashicons' );
	wp_enqueue_style( 'wp-color-picker' );
	wp_enqueue_script( 'wp-color-picker' );
	wp_enqueue_media();

	wp_enqueue_style(
		'dctc-admin-style',
		DCTC_PLUGIN_URL . 'admin/assets/css/admin-style.css',
		array( 'wp-color-picker', 'dashicons' ),
		DCTC_VERSION
	);

	if ( $is_channels ) {
		wp_enqueue_script(
			'dctc-admin-script',
			DCTC_PLUGIN_URL . 'admin/assets/js/admin-script.js',
			array( 'jquery', 'wp-color-picker' ),
			DCTC_VERSION,
			true
		);

		wp_localize_script(
			'dctc-admin-script',
			'dctc_admin',
			array(
				'nonce'   => wp_create_nonce( 'dctc_nonce' ),
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
			)
		);
	}

	if ( $is_settings ) {
		wp_enqueue_script(
			'dctc-admin-settings',
			DCTC_PLUGIN_URL . 'admin/assets/js/admin-settings.js',
			array( 'jquery' ),
			DCTC_VERSION,
			true
		);

		wp_localize_script(
			'dctc-admin-settings',
			'dctc_admin',
			array(
				'nonce'   => wp_create_nonce( 'dctc_nonce' ),
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
			)
		);
	}

	if ( $is_guide ) {
		wp_enqueue_script(
			'dctc-admin-guide',
			DCTC_PLUGIN_URL . 'admin/assets/js/admin-guide.js',
			array( 'jquery' ),
			DCTC_VERSION,
			true
		);

		wp_localize_script(
			'dctc-admin-guide',
			'dctc_admin',
			array(
				'nonce'   => wp_create_nonce( 'dctc_nonce' ),
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
			)
		);
	}
}

/**
 * Render Interactive User Guide Page (Channels, AI Assistant, Support Center).
 *
 * @return void
 */
function dctc_guide_page_html() {
	include DCTC_PLUGIN_DIR . 'admin/guide-page.php';
}

/**
 * Render Channels Builder (3-step wizard).
 *
 * @return void
 */
function dctc_channels_page_html() {
	include DCTC_PLUGIN_DIR . 'admin/admin-main.php';
}

/**
 * Render Dedicated Settings Page (General & Import/Export tabs).
 *
 * @return void
 */
function dctc_settings_page_html() {
	include DCTC_PLUGIN_DIR . 'admin/settings-page.php';
}

/**
 * Handle AJAX saving for channels, widget customization, triggers, and module switches.
 *
 * @return void
 */
function dctc_save_settings() {
	check_ajax_referer( 'dctc_nonce', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'Permission denied.', 'dragwyb-click-to-chat' ) ), 403 );
		return;
	}

	$settings = get_option( 'dctc_settings', array() );
	if ( ! is_array( $settings ) ) {
		$settings = array();
	}

	// 1. Social channels list
	$phase1_channels = array( 'whatsapp', 'facebook', 'phone', 'email', 'instagram', 'telegram', 'sms', 'twitter', 'linkedin' );

	foreach ( $phase1_channels as $slug ) {
		// Enabled
		$enabled_key = $slug . '_enabled';
		if ( isset( $_POST[ $enabled_key ] ) ) {
			$settings[ $slug . '_enabled' ] = $_POST[ $enabled_key ] === '1' ? '1' : '0';
		}

		// Value
		$value_key = $slug . '_value';
		if ( isset( $_POST[ $value_key ] ) ) {
			if ( $slug === 'email' ) {
				$settings[ $slug . '_value' ] = sanitize_email( wp_unslash( $_POST[ $value_key ] ) );
			} elseif ( in_array( $slug, array( 'linkedin', 'maps', 'waze', 'contact', 'poptin', 'slack', 'discord' ), true ) ) {
				$settings[ $slug . '_value' ] = esc_url( sanitize_text_field( wp_unslash( $_POST[ $value_key ] ) ) );
			} else {
				$settings[ $slug . '_value' ] = sanitize_text_field( wp_unslash( $_POST[ $value_key ] ) );
			}
		}

		// Device visibility
		$desktop_key                    = $slug . '_desktop';
		$mobile_key                     = $slug . '_mobile';
		$settings[ $slug . '_desktop' ] = isset( $_POST[ $desktop_key ] ) && $_POST[ $desktop_key ] === '1' ? '1' : '0';
		$settings[ $slug . '_mobile' ]  = isset( $_POST[ $mobile_key ] ) && $_POST[ $mobile_key ] === '1' ? '1' : '0';

		// Custom Icon
		$icon_key = $slug . '_custom_icon';
		if ( isset( $_POST[ $icon_key ] ) ) {
			$settings[ $slug . '_custom_icon' ] = esc_url_raw( wp_unslash( $_POST[ $icon_key ] ) );
		}

		// Chat Widget Settings
		$chat_widget_key = $slug . '_chat_widget_enabled';
		if ( isset( $_POST[ $chat_widget_key ] ) ) {
			$settings[ $slug . '_chat_widget_enabled' ] = $_POST[ $chat_widget_key ] === '1' ? '1' : '0';
		}

		$default_message_key = $slug . '_default_message';
		if ( isset( $_POST[ $default_message_key ] ) ) {
			$settings[ $slug . '_default_message' ] = sanitize_textarea_field( wp_unslash( $_POST[ $default_message_key ] ) );
		}
	}

	// 2. Widget Customization
	if ( isset( $_POST['show_widget'] ) ) {
		$settings['show_widget'] = ( '1' === $_POST['show_widget'] ) ? '1' : '0';
	}
	if ( isset( $_POST['widget_position'] ) ) {
		$settings['widget_position'] = sanitize_text_field( wp_unslash( $_POST['widget_position'] ) );
	}
	if ( isset( $_POST['widget_color'] ) ) {
		$settings['widget_color'] = sanitize_hex_color( wp_unslash( $_POST['widget_color'] ) );
	}
	if ( isset( $_POST['widget_size'] ) ) {
		$settings['widget_size'] = floatval( $_POST['widget_size'] );
	}
	if ( isset( $_POST['widget_size_unit'] ) ) {
		$settings['widget_size_unit'] = sanitize_text_field( wp_unslash( $_POST['widget_size_unit'] ) );
	}
	if ( isset( $_POST['custom_vertical_align'] ) ) {
		$settings['custom_vertical_align'] = sanitize_text_field( wp_unslash( $_POST['custom_vertical_align'] ) );
	}

	// Custom Position
	if ( isset( $_POST['custom_bottom'] ) ) {
		$settings['custom_bottom'] = floatval( $_POST['custom_bottom'] );
	}
	if ( isset( $_POST['custom_bottom_unit'] ) ) {
		$settings['custom_bottom_unit'] = sanitize_text_field( wp_unslash( $_POST['custom_bottom_unit'] ) );
	}
	if ( isset( $_POST['custom_horizontal'] ) ) {
		$settings['custom_horizontal'] = floatval( $_POST['custom_horizontal'] );
	}
	if ( isset( $_POST['custom_horizontal_unit'] ) ) {
		$settings['custom_horizontal_unit'] = sanitize_text_field( wp_unslash( $_POST['custom_horizontal_unit'] ) );
	}
	if ( isset( $_POST['custom_side'] ) ) {
		$settings['custom_side'] = sanitize_text_field( wp_unslash( $_POST['custom_side'] ) );
	}
	if ( isset( $_POST['dctc_greeting_message'] ) ) {
		$settings['greeting_message'] = sanitize_text_field( wp_unslash( $_POST['dctc_greeting_message'] ) );
	}

	// Icon Settings
	if ( isset( $_POST['icon_type'] ) ) {
		$settings['icon_type'] = sanitize_text_field( wp_unslash( $_POST['icon_type'] ) );
	}
	if ( isset( $_POST['custom_icon_url'] ) ) {
		$settings['custom_icon_url'] = esc_url_raw( wp_unslash( $_POST['custom_icon_url'] ) );
	}
	if ( isset( $_POST['icon_rotation'] ) ) {
		$rotation = intval( $_POST['icon_rotation'] );
		if ( $rotation >= 0 && $rotation <= 360 ) {
			$settings['icon_rotation'] = $rotation;
		}
	}
	if ( isset( $_POST['icon_scale'] ) ) {
		$scale = floatval( $_POST['icon_scale'] );
		if ( $scale >= 0.5 && $scale <= 2 ) {
			$settings['icon_scale'] = $scale;
		}
	}

	// Triggers and Targeting
	if ( isset( $_POST['show_on_desktop'] ) ) {
		$settings['show_on_desktop'] = $_POST['show_on_desktop'] === '1' ? '1' : '0';
	}
	if ( isset( $_POST['show_on_mobile'] ) ) {
		$settings['show_on_mobile'] = $_POST['show_on_mobile'] === '1' ? '1' : '0';
	}
	if ( isset( $_POST['time_delay'] ) ) {
		$delay = intval( $_POST['time_delay'] );
		if ( $delay >= 0 && $delay <= 60 ) {
			$settings['time_delay'] = $delay;
		}
	}

	// Display Rules
	if ( isset( $_POST['dctc_display_mode'] ) ) {
		$settings['display_mode'] = sanitize_text_field( wp_unslash( $_POST['dctc_display_mode'] ) );
	}
	if ( isset( $_POST['dctc_display_post_types'] ) && is_array( $_POST['dctc_display_post_types'] ) ) {
		$settings['display_post_types'] = array_map( 'sanitize_text_field', wp_unslash( $_POST['dctc_display_post_types'] ) );
	} else {
		$settings['display_post_types'] = array();
	}

	// 3. Module Master Toggles
	// Channels Module Toggle
	if ( isset( $_POST['channels_enabled'] ) ) {
		$settings['channels_enabled'] = ( '1' === $_POST['channels_enabled'] ) ? '1' : '0';
	}

	// AI Assistant Module Toggle
	if ( isset( $_POST['ai_assistant_enabled'] ) ) {
		$ai_settings = get_option( 'dctc_ai_chat_assistant_settings', array() );
		if ( ! is_array( $ai_settings ) ) {
			$ai_settings = array();
		}
		if ( ! isset( $ai_settings['display'] ) || ! is_array( $ai_settings['display'] ) ) {
			$ai_settings['display'] = array();
		}
		$ai_settings['display']['entire_site'] = ( '1' === $_POST['ai_assistant_enabled'] );
		update_option( 'dctc_ai_chat_assistant_settings', $ai_settings );
		$settings['ai_assistant_enabled'] = ( '1' === $_POST['ai_assistant_enabled'] ) ? '1' : '0';
	}

	// Support Center Module Toggle
	if ( isset( $_POST['support_center_enabled'] ) ) {
		$support_settings            = get_option( 'dctc_support_settings', array() );
		if ( ! is_array( $support_settings ) ) {
			$support_settings = array();
		}
		$enabled                     = ( '1' === $_POST['support_center_enabled'] );
		$support_settings['enabled'] = $enabled;
		update_option( 'dctc_support_settings', $support_settings );

		if ( $enabled && file_exists( DCTC_PLUGIN_DIR . 'includes/ai/support/class-dctc-support-db.php' ) ) {
			require_once DCTC_PLUGIN_DIR . 'includes/ai/support/class-dctc-support-db.php';
			DCTC_Support_DB::create_tables();
		}
	}

	// 4. Privacy & Uninstall Cleanup Settings
	if ( isset( $_POST['is_uninstall_settings'] ) ) {
		$uninstall_settings = array(
			'delete_options'      => isset( $_POST['delete_options'] ) && '1' === $_POST['delete_options'] ? 1 : 0,
			'delete_ai_data'      => isset( $_POST['delete_ai_data'] ) && '1' === $_POST['delete_ai_data'] ? 1 : 0,
			'delete_rag_data'     => isset( $_POST['delete_rag_data'] ) && '1' === $_POST['delete_rag_data'] ? 1 : 0,
			'delete_support_data' => isset( $_POST['delete_support_data'] ) && '1' === $_POST['delete_support_data'] ? 1 : 0,
			'delete_error_logs'   => isset( $_POST['delete_error_logs'] ) && '1' === $_POST['delete_error_logs'] ? 1 : 0,
			'delete_user_meta'    => isset( $_POST['delete_user_meta'] ) && '1' === $_POST['delete_user_meta'] ? 1 : 0,
			'delete_transients'   => isset( $_POST['delete_transients'] ) && '1' === $_POST['delete_transients'] ? 1 : 0,
		);
		update_option( 'dctc_uninstall_settings', $uninstall_settings );
		$settings['uninstall'] = $uninstall_settings;
	}

	// Save all to main option
	update_option( 'dctc_settings', $settings );

	wp_send_json_success( array( 'message' => __( 'Settings saved successfully!', 'dragwyb-click-to-chat' ) ) );
}
