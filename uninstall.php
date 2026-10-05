<?php
/**
 * Fired when the plugin is deleted via WordPress Admin Plugins screen.
 *
 * @package Dragwyb_Click_To_Chat
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Execute cleanup routine for a single site/blog.
 *
 * @return void
 */
function dctc_execute_uninstall_cleanup() {
	global $wpdb;

	// Fetch user-configured privacy & uninstall cleanup rules
	$uninstall_settings = get_option( 'dctc_uninstall_settings', null );
	if ( ! is_array( $uninstall_settings ) ) {
		// Check fallback inside main settings
		$main_settings = get_option( 'dctc_settings', array() );
		if ( isset( $main_settings['uninstall'] ) && is_array( $main_settings['uninstall'] ) ) {
			$uninstall_settings = $main_settings['uninstall'];
		} else {
			// By default, preserve critical data (options, chat sessions, knowledge base, support tickets, user meta)
			// so that settings and records are retained if the user reinstalls the plugin.
			$uninstall_settings = array(
				'delete_options'      => 0,
				'delete_ai_data'      => 0,
				'delete_rag_data'     => 0,
				'delete_support_data' => 0,
				'delete_error_logs'   => 1,
				'delete_user_meta'    => 0,
				'delete_transients'   => 1,
			);
		}
	}

	$delete_options      = ! empty( $uninstall_settings['delete_options'] );
	$delete_ai_data      = ! empty( $uninstall_settings['delete_ai_data'] );
	$delete_rag_data     = ! empty( $uninstall_settings['delete_rag_data'] );
	$delete_support_data = ! empty( $uninstall_settings['delete_support_data'] );
	$delete_error_logs   = ! empty( $uninstall_settings['delete_error_logs'] );
	$delete_user_meta    = ! empty( $uninstall_settings['delete_user_meta'] );
	$delete_transients   = ! isset( $uninstall_settings['delete_transients'] ) || ! empty( $uninstall_settings['delete_transients'] );

	// 1. Clear all scheduled cron hooks
	$cron_hooks = array(
		'dctc_cleanup_error_logs_cron',
		'dctc_ai_daily_cleanup_cron',
		'dctc_ai_async_index_post',
		'dctc_ai_rag_process_index_batch',
		'dctc_ai_rag_reindex_post',
		'dctc_ai_rag_auto_index',
	);

	foreach ( $cron_hooks as $hook ) {
		wp_clear_scheduled_hook( $hook );
	}

	// 2. Drop Custom Database Tables based on granular preferences
	$tables_to_drop = array();

	// AI Chat Sessions & Captured Leads
	if ( $delete_ai_data ) {
		$tables_to_drop[] = $wpdb->prefix . 'dctc_ai_sessions';
		$tables_to_drop[] = $wpdb->prefix . 'dctc_ai_leads';
	}

	// Knowledge Base RAG Documents, Chunks, Embeddings
	if ( $delete_rag_data ) {
		$tables_to_drop[] = $wpdb->prefix . 'dctc_ai_rag_documents';
		$tables_to_drop[] = $wpdb->prefix . 'dctc_ai_rag_chunks';
		$tables_to_drop[] = $wpdb->prefix . 'dctc_ai_rag_metadata';
		$tables_to_drop[] = $wpdb->prefix . 'dctc_ai_embeddings';
	}

	// Support Center & Helpdesk Ticketing
	if ( $delete_support_data ) {
		$tables_to_drop[] = $wpdb->prefix . 'dctc_support_tickets';
		$tables_to_drop[] = $wpdb->prefix . 'dctc_support_ticket_meta';
		$tables_to_drop[] = $wpdb->prefix . 'dctc_support_taxonomies';
		$tables_to_drop[] = $wpdb->prefix . 'dctc_support_terms';
		$tables_to_drop[] = $wpdb->prefix . 'dctc_support_term_meta';
		$tables_to_drop[] = $wpdb->prefix . 'dctc_support_term_relationships';
		$tables_to_drop[] = $wpdb->prefix . 'dctc_support_categories';
		$tables_to_drop[] = $wpdb->prefix . 'dctc_support_products';
		$tables_to_drop[] = $wpdb->prefix . 'dctc_support_tags';
		$tables_to_drop[] = $wpdb->prefix . 'dctc_support_taxonomy_terms';
		$tables_to_drop[] = $wpdb->prefix . 'dctc_support_ticket_tags';
		$tables_to_drop[] = $wpdb->prefix . 'dctc_support_agents';
		$tables_to_drop[] = $wpdb->prefix . 'dctc_support_events';
		$tables_to_drop[] = $wpdb->prefix . 'dctc_support_notes';
		$tables_to_drop[] = $wpdb->prefix . 'dctc_support_assignments';
		$tables_to_drop[] = $wpdb->prefix . 'dctc_support_notif_log';
	}

	// Error Logs
	if ( $delete_error_logs ) {
		$tables_to_drop[] = $wpdb->prefix . 'dctc_error_logs';
	}

	foreach ( $tables_to_drop as $table ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "DROP TABLE IF EXISTS `{$table}`" );
	}

	// 3. Delete Transients & Cached Data
	if ( $delete_transients ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_dctc_%' OR option_name LIKE '_transient_timeout_dctc_%'" );
	}

	// 4. Delete User Meta
	if ( $delete_user_meta ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'dctc_%' OR meta_key LIKE 'dctc_ai_%'" );
	}

	// 5. Delete Plugin Options (Settings, API Keys, Preferences)
	if ( $delete_options ) {
		// Delete all plugin options
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'dctc_%'" );
	} else {
		// Always clean the uninstall settings option itself if settings are kept
		delete_option( 'dctc_uninstall_settings' );
	}
}

// Support WordPress Multisite networks
global $wpdb;
if ( is_multisite() ) {
	$blog_ids = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs}" );
	if ( ! empty( $blog_ids ) ) {
		foreach ( $blog_ids as $blog_id ) {
			switch_to_blog( $blog_id );
			dctc_execute_uninstall_cleanup();
			restore_current_blog();
		}
	} else {
		dctc_execute_uninstall_cleanup();
	}
} else {
	dctc_execute_uninstall_cleanup();
}
