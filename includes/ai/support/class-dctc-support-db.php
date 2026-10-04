<?php
/**
 * DCTC Support Database & Migration Handler
 *
 * Manages custom tables for Support Center:
 * - Tickets
 * - Categories
 * - Tags & Ticket-Tags
 * - Support Agents
 * - Ticket Events (Timeline & Audit)
 * - Internal Notes
 * - Assignment History
 * - Notification Logs
 *
 * @package Dragwyb_Click_To_Chat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class DCTC_Support_DB
 */
class DCTC_Support_DB {

	/**
	 * Database schema version
	 */
	const DB_VERSION = '1.0.0';

	/**
	 * Option key for support DB version
	 */
	const DB_VERSION_OPTION = 'dctc_support_db_version';

	/**
	 * Create or update all support tables via dbDelta.
	 *
	 * @return void
	 */
	public static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// 1. Support Tickets Table
		$table_tickets = $wpdb->prefix . 'dctc_support_tickets';
		$sql_tickets   = "CREATE TABLE `$table_tickets` (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			uuid varchar(64) NOT NULL,
			ticket_number bigint(20) unsigned NOT NULL,
			session_id varchar(100) NOT NULL,
			customer_wp_user_id bigint(20) unsigned DEFAULT 0 NOT NULL,
			customer_email varchar(100) DEFAULT '' NOT NULL,
			customer_name varchar(150) DEFAULT '' NOT NULL,
			guest_access_token varchar(64) DEFAULT '' NOT NULL,
			subject varchar(255) NOT NULL,
			status varchar(30) DEFAULT 'open' NOT NULL,
			priority varchar(20) DEFAULT 'normal' NOT NULL,
			control_mode varchar(20) DEFAULT 'ai' NOT NULL,
			origin_type varchar(30) DEFAULT 'chatbot' NOT NULL,
			reply_surface varchar(30) DEFAULT 'chatbot_widget' NOT NULL,
			interaction_type varchar(30) DEFAULT 'AI_CHAT' NOT NULL,
			category_id bigint(20) unsigned DEFAULT 0 NOT NULL,
			assigned_agent_id bigint(20) unsigned DEFAULT 0 NOT NULL,
			assigned_team_id bigint(20) unsigned DEFAULT 0 NOT NULL,
			ai_classification_confidence decimal(5,2) DEFAULT 0.00 NOT NULL,
			ai_summary text,
			customer_last_seen_at datetime DEFAULT NULL,
			customer_last_read_message_id varchar(64) DEFAULT '' NOT NULL,
			agent_last_read_message_id varchar(64) DEFAULT '' NOT NULL,
			first_response_at datetime DEFAULT NULL,
			resolved_at datetime DEFAULT NULL,
			closed_at datetime DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY uuid (uuid),
			KEY ticket_number (ticket_number),
			KEY session_id (session_id),
			KEY customer_wp_user_id (customer_wp_user_id),
			KEY customer_email (customer_email),
			KEY status (status),
			KEY priority (priority),
			KEY control_mode (control_mode),
			KEY assigned_agent_id (assigned_agent_id),
			KEY category_id (category_id),
			KEY origin_type (origin_type),
			KEY reply_surface (reply_surface),
			KEY created_at (created_at)
		) $charset_collate;";
		dbDelta( $sql_tickets );

		// 2. Support Categories Table
		$table_categories = $wpdb->prefix . 'dctc_support_categories';
		$sql_categories   = "CREATE TABLE `$table_categories` (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			parent_id bigint(20) unsigned DEFAULT 0 NOT NULL,
			name varchar(150) NOT NULL,
			slug varchar(150) NOT NULL,
			description text,
			default_priority varchar(20) DEFAULT 'normal' NOT NULL,
			default_team_id bigint(20) unsigned DEFAULT 0 NOT NULL,
			required_skills text,
			requires_human tinyint(1) DEFAULT 0 NOT NULL,
			ai_allowed tinyint(1) DEFAULT 1 NOT NULL,
			auto_assign tinyint(1) DEFAULT 1 NOT NULL,
			status varchar(20) DEFAULT 'active' NOT NULL,
			display_order int(11) DEFAULT 0 NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY slug (slug),
			KEY parent_id (parent_id),
			KEY status (status)
		) $charset_collate;";
		dbDelta( $sql_categories );

		// 3. Support Tags Table
		$table_tags = $wpdb->prefix . 'dctc_support_tags';
		$sql_tags   = "CREATE TABLE `$table_tags` (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(100) NOT NULL,
			slug varchar(100) NOT NULL,
			color varchar(20) DEFAULT '#4F46E5' NOT NULL,
			status varchar(20) DEFAULT 'active' NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY slug (slug),
			KEY status (status)
		) $charset_collate;";
		dbDelta( $sql_tags );

		// 4. Ticket Tags Pivot Table
		$table_ticket_tags = $wpdb->prefix . 'dctc_support_ticket_tags';
		$sql_ticket_tags   = "CREATE TABLE `$table_ticket_tags` (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			ticket_id bigint(20) unsigned NOT NULL,
			tag_id bigint(20) unsigned NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY ticket_tag (ticket_id,tag_id),
			KEY ticket_id (ticket_id),
			KEY tag_id (tag_id)
		) $charset_collate;";
		dbDelta( $sql_ticket_tags );

		// 5. Support Agents Profile Table
		$table_agents = $wpdb->prefix . 'dctc_support_agents';
		$sql_agents   = "CREATE TABLE `$table_agents` (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			wp_user_id bigint(20) unsigned NOT NULL,
			support_role varchar(50) DEFAULT 'support' NOT NULL,
			seniority varchar(30) DEFAULT 'support' NOT NULL,
			skills longtext,
			allowed_categories longtext,
			active tinyint(1) DEFAULT 1 NOT NULL,
			assignment_enabled tinyint(1) DEFAULT 1 NOT NULL,
			availability_status varchar(20) DEFAULT 'available' NOT NULL,
			max_active_tickets int(11) DEFAULT 10 NOT NULL,
			current_active_tickets int(11) DEFAULT 0 NOT NULL,
			last_assigned_at datetime DEFAULT NULL,
			notification_email_enabled tinyint(1) DEFAULT 1 NOT NULL,
			notification_preferences longtext,
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY wp_user_id (wp_user_id),
			KEY support_role (support_role),
			KEY seniority (seniority),
			KEY active (active),
			KEY assignment_enabled (assignment_enabled),
			KEY availability_status (availability_status)
		) $charset_collate;";
		dbDelta( $sql_agents );

		// 6. Support Events / Audit Timeline Table
		$table_events = $wpdb->prefix . 'dctc_support_events';
		$sql_events   = "CREATE TABLE `$table_events` (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			ticket_id bigint(20) unsigned NOT NULL,
			actor_type varchar(30) NOT NULL,
			actor_id bigint(20) unsigned DEFAULT 0 NOT NULL,
			actor_name varchar(150) DEFAULT '' NOT NULL,
			event_type varchar(50) NOT NULL,
			old_value longtext,
			new_value longtext,
			metadata longtext,
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY  (id),
			KEY ticket_id (ticket_id),
			KEY event_type (event_type),
			KEY actor_type (actor_type),
			KEY created_at (created_at)
		) $charset_collate;";
		dbDelta( $sql_events );

		// 7. Internal Notes Table (Staff Only)
		$table_notes = $wpdb->prefix . 'dctc_support_notes';
		$sql_notes   = "CREATE TABLE `$table_notes` (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			uuid varchar(64) NOT NULL,
			ticket_id bigint(20) unsigned NOT NULL,
			agent_wp_user_id bigint(20) unsigned NOT NULL,
			agent_name varchar(150) DEFAULT '' NOT NULL,
			content longtext NOT NULL,
			is_pinned tinyint(1) DEFAULT 0 NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY uuid (uuid),
			KEY ticket_id (ticket_id),
			KEY agent_wp_user_id (agent_wp_user_id),
			KEY created_at (created_at)
		) $charset_collate;";
		dbDelta( $sql_notes );

		// 8. Support Assignments History Table
		$table_assignments = $wpdb->prefix . 'dctc_support_assignments';
		$sql_assignments   = "CREATE TABLE `$table_assignments` (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			ticket_id bigint(20) unsigned NOT NULL,
			agent_id bigint(20) unsigned NOT NULL,
			team_id bigint(20) unsigned DEFAULT 0 NOT NULL,
			assigned_by bigint(20) unsigned DEFAULT 0 NOT NULL,
			assignment_reason varchar(255) DEFAULT '' NOT NULL,
			assignment_method varchar(50) DEFAULT 'manual' NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			ended_at datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY ticket_id (ticket_id),
			KEY agent_id (agent_id),
			KEY created_at (created_at)
		) $charset_collate;";
		dbDelta( $sql_assignments );

		// 9. Support Notification Log Table
		$table_notif_log = $wpdb->prefix . 'dctc_support_notification_log';
		$sql_notif_log   = "CREATE TABLE `$table_notif_log` (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			event_type varchar(50) NOT NULL,
			recipient_type varchar(30) NOT NULL,
			recipient_id bigint(20) unsigned DEFAULT 0 NOT NULL,
			email varchar(100) NOT NULL,
			ticket_id bigint(20) unsigned NOT NULL,
			status varchar(20) DEFAULT 'sent' NOT NULL,
			suppress_reason varchar(100) DEFAULT '' NOT NULL,
			error_message text,
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY  (id),
			KEY ticket_id (ticket_id),
			KEY event_type (event_type),
			KEY recipient_type (recipient_type),
			KEY created_at (created_at)
		) $charset_collate;";
		dbDelta( $sql_notif_log );

		// Seed initial default categories, tags, and settings if not already present.
		self::seed_default_data();

		update_option( self::DB_VERSION_OPTION, self::DB_VERSION );
	}

	/**
	 * Seed initial default categories, tags, and support configuration.
	 *
	 * @return void
	 */
	public static function seed_default_data() {
		global $wpdb;

		// Seed Categories
		$table_categories = $wpdb->prefix . 'dctc_support_categories';
		$count_categories = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `$table_categories`" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( 0 === $count_categories ) {
			$default_categories = array(
				array(
					'name'             => 'Product Support',
					'slug'             => 'product-support',
					'description'      => 'General questions and troubleshooting for products.',
					'default_priority' => 'normal',
					'required_skills'  => wp_json_encode( array( 'product' ) ),
					'requires_human'   => 0,
					'ai_allowed'       => 1,
					'auto_assign'      => 1,
				),
				array(
					'name'             => 'WooCommerce & Orders',
					'slug'             => 'woocommerce-orders',
					'description'      => 'Order tracking, checkout issues, cart problems, and payments.',
					'default_priority' => 'high',
					'required_skills'  => wp_json_encode( array( 'woocommerce', 'orders' ) ),
					'requires_human'   => 0,
					'ai_allowed'       => 1,
					'auto_assign'      => 1,
				),
				array(
					'name'             => 'Technical & Bugs',
					'slug'             => 'technical-bugs',
					'description'      => 'Technical errors, bug reports, and integration issues.',
					'default_priority' => 'high',
					'required_skills'  => wp_json_encode( array( 'technical' ) ),
					'requires_human'   => 1,
					'ai_allowed'       => 1,
					'auto_assign'      => 1,
				),
				array(
					'name'             => 'Billing & License',
					'slug'             => 'billing-license',
					'description'      => 'Invoices, refunds, subscriptions, and license key activations.',
					'default_priority' => 'normal',
					'required_skills'  => wp_json_encode( array( 'billing' ) ),
					'requires_human'   => 1,
					'ai_allowed'       => 0,
					'auto_assign'      => 1,
				),
			);

			foreach ( $default_categories as $idx => $cat ) {
				$wpdb->insert(
					$table_categories,
					array(
						'parent_id'        => 0,
						'name'             => $cat['name'],
						'slug'             => $cat['slug'],
						'description'      => $cat['description'],
						'default_priority' => $cat['default_priority'],
						'required_skills'  => $cat['required_skills'],
						'requires_human'   => $cat['requires_human'],
						'ai_allowed'       => $cat['ai_allowed'],
						'auto_assign'      => $cat['auto_assign'],
						'status'           => 'active',
						'display_order'    => $idx + 1,
						'created_at'       => current_time( 'mysql' ),
					),
					array( '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%d', '%s' )
				);
			}
		}

		// Seed Tags
		$table_tags = $wpdb->prefix . 'dctc_support_tags';
		$count_tags = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `$table_tags`" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( 0 === $count_tags ) {
			$default_tags = array(
				array( 'name' => 'Urgent', 'slug' => 'urgent', 'color' => '#EF4444' ),
				array( 'name' => 'WooCommerce', 'slug' => 'woocommerce', 'color' => '#9333EA' ),
				array( 'name' => 'Bug', 'slug' => 'bug', 'color' => '#F59E0B' ),
				array( 'name' => 'Refund', 'slug' => 'refund', 'color' => '#DC2626' ),
				array( 'name' => 'Feature Request', 'slug' => 'feature-request', 'color' => '#3B82F6' ),
				array( 'name' => 'AI Escalation', 'slug' => 'ai-escalation', 'color' => '#10B981' ),
			);

			foreach ( $default_tags as $tag ) {
				$wpdb->insert(
					$table_tags,
					array(
						'name'       => $tag['name'],
						'slug'       => $tag['slug'],
						'color'      => $tag['color'],
						'status'     => 'active',
						'created_at' => current_time( 'mysql' ),
					),
					array( '%s', '%s', '%s', '%s', '%s' )
				);
			}
		}

		// Seed Default Support Settings
		$default_support_settings = array(
			'enabled'               => false,
			'ticket_prefix'         => 'TCK-',
			'allow_guest_tickets'   => true,
			'default_status'        => 'open',
			'default_priority'      => 'normal',
			'auto_assign'           => true,
			'assignment_algorithm'  => 'least_loaded', // 'round_robin', 'least_loaded', 'skill_match'
			'respect_availability'  => true,
			'respect_workload'      => true,
			'human_request_trigger' => true,
			'auto_pause_ai'         => true,
			'notifications'         => array(
				'agent_assignment'        => true,
				'agent_reassignment'      => true,
				'agent_reply'             => true,
				'customer_reply'          => true,
				'ticket_resolved'         => true,
				'ticket_reopened'         => true,
				'customer_email_enabled'  => true,
				'suppress_active_session' => true,
			),
		);

		if ( ! get_option( 'dctc_support_settings' ) ) {
			update_option( 'dctc_support_settings', $default_support_settings );
		}

		// Auto-register the current admin user as a Support Administrator agent if no agents exist
		$table_agents = $wpdb->prefix . 'dctc_support_agents';
		$count_agents = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `$table_agents`" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( 0 === $count_agents ) {
			$admins = get_users( array( 'role' => 'administrator', 'number' => 1 ) );
			if ( ! empty( $admins ) ) {
				$first_admin = $admins[0];
				$wpdb->insert(
					$table_agents,
					array(
						'wp_user_id'                 => $first_admin->ID,
						'support_role'               => 'admin',
						'seniority'                  => 'manager',
						'skills'                     => wp_json_encode( array( 'product', 'woocommerce', 'technical', 'billing', 'orders' ) ),
						'allowed_categories'         => wp_json_encode( array() ),
						'active'                     => 1,
						'assignment_enabled'         => 1,
						'availability_status'        => 'available',
						'max_active_tickets'         => 20,
						'current_active_tickets'     => 0,
						'notification_email_enabled' => 1,
						'notification_preferences'   => wp_json_encode( array( 'all' => true ) ),
						'created_at'                 => current_time( 'mysql' ),
					),
					array( '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%d', '%d', '%d', '%s', '%s' )
				);
			}
		}
	}

	/**
	 * Get next sequential ticket number atomically.
	 *
	 * @return int
	 */
	public static function get_next_ticket_number() {
		global $wpdb;
		$table_tickets = $wpdb->prefix . 'dctc_support_tickets';

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$max_number = $wpdb->get_var( "SELECT MAX(ticket_number) FROM `$table_tickets`" );

		return $max_number ? ( (int) $max_number + 1 ) : 10001;
	}
}
