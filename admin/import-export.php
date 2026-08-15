<?php
/**
 * Settings submenu — Import / Export.
 *
 * @package Dragwyb_Click_To_Chat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once DCTC_PLUGIN_DIR . 'includes/class-dctc-settings-import-export.php';

add_action( 'admin_menu', 'dctc_add_import_export_page', 25 );
add_action( 'admin_enqueue_scripts', 'dctc_import_export_scripts' );
add_action( 'admin_post_dctc_export_settings', 'dctc_handle_export_settings' );
add_action( 'admin_post_dctc_import_settings', 'dctc_handle_import_settings' );
add_action( 'admin_notices', 'dctc_import_export_admin_notices' );

/**
 * Register Settings submenu under Click to Chat.
 *
 * @return void
 */
function dctc_add_import_export_page() {
	add_submenu_page(
		'dragwyb-click-to-chat',
		__( 'Settings', 'dragwyb-click-to-chat' ),
		__( 'Settings', 'dragwyb-click-to-chat' ),
		'manage_options',
		'dragwyb-click-to-chat-settings',
		'dctc_import_export_page_html'
	);
}

/**
 * Enqueue assets on the Settings page only.
 *
 * @param string $hook Current admin page hook.
 * @return void
 */
function dctc_import_export_scripts( $hook ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
	$is_settings = ( 'dragwyb-click-to-chat-settings' === $page )
		|| false !== strpos( (string) $hook, 'dragwyb-click-to-chat-settings' );

	if ( ! $is_settings ) {
		return;
	}

	wp_enqueue_style( 'dashicons' );

	wp_enqueue_style(
		'dctc-admin-style',
		DCTC_PLUGIN_URL . 'admin/assets/css/admin-style.css',
		array( 'dashicons' ),
		DCTC_VERSION
	);

	wp_enqueue_script(
		'dctc-admin-import-export',
		DCTC_PLUGIN_URL . 'admin/assets/js/admin-import-export.js',
		array(),
		DCTC_VERSION,
		true
	);
}

/**
 * Render Settings (Import / Export) page.
 *
 * @return void
 */
function dctc_import_export_page_html() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'dragwyb-click-to-chat' ) );
	}

	include DCTC_PLUGIN_DIR . 'admin/views/import-export-page.php';
}

/**
 * Handle export download.
 *
 * @return void
 */
function dctc_handle_export_settings() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to export settings.', 'dragwyb-click-to-chat' ) );
	}

	check_admin_referer( 'dctc_import_export' );

	$modules = isset( $_POST['dctc_modules'] ) ? wp_unslash( $_POST['dctc_modules'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$modules = DCTC_Settings_Import_Export::dctc_normalize_modules( $modules );

	$format = isset( $_POST['dctc_format'] ) ? sanitize_key( wp_unslash( $_POST['dctc_format'] ) ) : 'json';
	if ( ! in_array( $format, DCTC_Settings_Import_Export::dctc_allowed_formats(), true ) ) {
		$format = 'json';
	}

	$payload = DCTC_Settings_Import_Export::dctc_build_payload( $modules );
	if ( is_wp_error( $payload ) ) {
		dctc_ie_redirect_with_error( $payload->get_error_message() );
	}

	$body = DCTC_Settings_Import_Export::dctc_encode( $payload, $format );
	if ( is_wp_error( $body ) ) {
		dctc_ie_redirect_with_error( $body->get_error_message() );
	}

	$filename = 'click-to-chat-settings-' . gmdate( 'Y-m-d' ) . '.' . $format;

	$mime = array(
		'json' => 'application/json',
		'csv'  => 'text/csv',
		'sql'  => 'application/sql',
	);

	nocache_headers();
	header( 'Content-Type: ' . $mime[ $format ] . '; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
	header( 'Content-Length: ' . strlen( $body ) );

	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Binary download body.
	echo $body;
	exit;
}

/**
 * Handle import upload.
 *
 * @return void
 */
function dctc_handle_import_settings() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to import settings.', 'dragwyb-click-to-chat' ) );
	}

	check_admin_referer( 'dctc_import_export' );

	$modules = isset( $_POST['dctc_import_modules'] ) ? wp_unslash( $_POST['dctc_import_modules'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$modules = DCTC_Settings_Import_Export::dctc_normalize_modules( $modules );

	if ( empty( $_FILES['dctc_import_file'] ) || ! isset( $_FILES['dctc_import_file']['tmp_name'] ) ) {
		dctc_ie_redirect_with_error( __( 'Please choose a file to import.', 'dragwyb-click-to-chat' ) );
	}

	$file = $_FILES['dctc_import_file']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

	if ( ! empty( $file['error'] ) && UPLOAD_ERR_OK !== (int) $file['error'] ) {
		dctc_ie_redirect_with_error( __( 'File upload failed. Please try again.', 'dragwyb-click-to-chat' ) );
	}

	if ( empty( $file['size'] ) || (int) $file['size'] > DCTC_Settings_Import_Export::MAX_UPLOAD_BYTES ) {
		dctc_ie_redirect_with_error( __( 'File is empty or larger than 2 MB.', 'dragwyb-click-to-chat' ) );
	}

	$filename = isset( $file['name'] ) ? sanitize_file_name( $file['name'] ) : '';
	$ext      = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
	if ( ! in_array( $ext, DCTC_Settings_Import_Export::dctc_allowed_formats(), true ) ) {
		dctc_ie_redirect_with_error( __( 'Unsupported file type. Use .json, .csv, or .sql.', 'dragwyb-click-to-chat' ) );
	}

	$tmp = $file['tmp_name'];
	if ( ! is_uploaded_file( $tmp ) ) {
		dctc_ie_redirect_with_error( __( 'Invalid upload.', 'dragwyb-click-to-chat' ) );
	}

	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading verified upload.
	$contents = file_get_contents( $tmp );
	if ( false === $contents || '' === $contents ) {
		dctc_ie_redirect_with_error( __( 'Could not read the uploaded file.', 'dragwyb-click-to-chat' ) );
	}

	$payload = DCTC_Settings_Import_Export::dctc_parse_file( $contents, $ext );
	if ( is_wp_error( $payload ) ) {
		dctc_ie_redirect_with_error( $payload->get_error_message() );
	}

	// If user left modules empty, apply whatever the file contains.
	if ( empty( $modules ) ) {
		$modules = isset( $payload['modules'] ) ? $payload['modules'] : array();
	}

	$result = DCTC_Settings_Import_Export::dctc_apply_import( $payload, $modules );
	if ( is_wp_error( $result ) ) {
		dctc_ie_redirect_with_error( $result->get_error_message() );
	}

	$redirect = add_query_arg(
		array(
			'page'          => 'dragwyb-click-to-chat-settings',
			'dctc_imported' => '1',
		),
		admin_url( 'admin.php' )
	);
	wp_safe_redirect( $redirect );
	exit;
}

/**
 * Redirect back to Settings with an error message in a transient.
 *
 * @param string $message Error message.
 * @return void
 */
function dctc_ie_redirect_with_error( $message ) {
	set_transient(
		'dctc_ie_error_' . get_current_user_id(),
		sanitize_text_field( $message ),
		60
	);
	$redirect = add_query_arg(
		array(
			'page'            => 'dragwyb-click-to-chat-settings',
			'dctc_ie_failed'  => '1',
		),
		admin_url( 'admin.php' )
	);
	wp_safe_redirect( $redirect );
	exit;
}

/**
 * Show success / error notices on the Settings page.
 *
 * @return void
 */
function dctc_import_export_admin_notices() {
	if ( ! isset( $_GET['page'] ) || 'dragwyb-click-to-chat-settings' !== $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['dctc_imported'] ) && '1' === $_GET['dctc_imported'] ) {
		echo '<div class="notice notice-success is-dismissible"><p>';
		echo esc_html__( 'Settings imported successfully. API keys were not changed.', 'dragwyb-click-to-chat' );
		echo '</p></div>';
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['dctc_ie_failed'] ) && '1' === $_GET['dctc_ie_failed'] ) {
		$key     = 'dctc_ie_error_' . get_current_user_id();
		$message = get_transient( $key );
		delete_transient( $key );
		if ( $message ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
		}
	}
}
