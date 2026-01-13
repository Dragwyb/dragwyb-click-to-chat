<?php

/**
 * Fired when the plugin is deleted.
 *
 * @package Social Chat Widget
 */

// If uninstall not called from WordPress, then exit.
if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Global database access
global $wpdb;

// Delete all options starting with 'dctc_'
// This handles all settings: channel enabled/values, visibility, triggers, customization, etc.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'dctc_%'");
