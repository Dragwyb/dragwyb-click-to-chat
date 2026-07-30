<?php

/**
 * Fired when the plugin is deleted.
 *
 * @package Dragwyb Click To Chat
 */

// If uninstall not called from WordPress, then exit.
if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Global database access
global $wpdb;

// Delete Social Chat options (dctc_settings and related).
// phpcs:ignore WordPress.DB.DirectDatabaseQuery
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'dctc_%' AND option_name NOT LIKE 'dctc_ai_%'");

// Delete AI module options.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'dctc_ai_%'");

// Delete AI user meta.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery
$wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'dctc_ai_%'");

// Drop AI custom tables.
$tables = array(
    $wpdb->prefix . 'dctc_ai_sessions',
    $wpdb->prefix . 'dctc_ai_rag_documents',
    $wpdb->prefix . 'dctc_ai_rag_chunks',
    $wpdb->prefix . 'dctc_ai_rag_metadata',
    $wpdb->prefix . 'dctc_ai_embeddings',
);

foreach ($tables as $table) {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $wpdb->query("DROP TABLE IF EXISTS `{$table}`");
}
