<?php
/**
 * DCTC Support Database & Migration Handler
 *
 * Manages custom tables for Support Center:
 * - Tickets
 * - Unified Taxonomies, Terms, Term Meta, and Relationships
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
	const DB_VERSION = '2.0.0';

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

		// 1b. Support Ticket Meta Table
		$table_ticket_meta = $wpdb->prefix . 'dctc_support_ticket_meta';
		$sql_ticket_meta   = "CREATE TABLE `$table_ticket_meta` (
			meta_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			ticket_id bigint(20) unsigned NOT NULL,
			meta_key varchar(255) DEFAULT NULL,
			meta_value longtext,
			PRIMARY KEY  (meta_id),
			KEY ticket_id (ticket_id),
			KEY meta_key (meta_key(191))
		) $charset_collate;";
		dbDelta( $sql_ticket_meta );

		// 2. Unified Taxonomies Table
		$table_taxonomies = $wpdb->prefix . 'dctc_support_taxonomies';
		$sql_taxonomies   = "CREATE TABLE `$table_taxonomies` (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			slug varchar(100) NOT NULL,
			name varchar(150) NOT NULL,
			description text,
			is_system tinyint(1) DEFAULT 0 NOT NULL,
			hierarchical tinyint(1) DEFAULT 0 NOT NULL,
			icon_type varchar(20) DEFAULT 'preset' NOT NULL,
			icon_dashicon varchar(100) DEFAULT 'dashicons-category' NOT NULL,
			image_url text,
			color varchar(30) DEFAULT '#4F46E5' NOT NULL,
			display_order int(11) DEFAULT 0 NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY slug (slug),
			KEY display_order (display_order)
		) $charset_collate;";
		dbDelta( $sql_taxonomies );

		// 3. Unified Terms Table
		$table_terms = $wpdb->prefix . 'dctc_support_terms';
		$sql_terms   = "CREATE TABLE `$table_terms` (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			taxonomy_slug varchar(100) NOT NULL,
			parent_id bigint(20) unsigned DEFAULT 0 NOT NULL,
			name varchar(255) NOT NULL,
			slug varchar(255) NOT NULL,
			description text,
			color varchar(30) DEFAULT '#4F46E5' NOT NULL,
			status varchar(20) DEFAULT 'active' NOT NULL,
			display_order int(11) DEFAULT 0 NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY  (id),
			KEY taxonomy_slug (taxonomy_slug),
			KEY parent_id (parent_id),
			KEY slug (slug(191)),
			KEY status (status),
			KEY display_order (display_order)
		) $charset_collate;";
		dbDelta( $sql_terms );

		// 4. Term Meta Table
		$table_term_meta = $wpdb->prefix . 'dctc_support_term_meta';
		$sql_term_meta   = "CREATE TABLE `$table_term_meta` (
			meta_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			term_id bigint(20) unsigned NOT NULL,
			meta_key varchar(255) DEFAULT NULL,
			meta_value longtext,
			PRIMARY KEY  (meta_id),
			KEY term_id (term_id),
			KEY meta_key (meta_key(191))
		) $charset_collate;";
		dbDelta( $sql_term_meta );

		// 5. Term Relationships Table
		$table_relationships = $wpdb->prefix . 'dctc_support_term_relationships';
		$sql_relationships   = "CREATE TABLE `$table_relationships` (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			object_id bigint(20) unsigned NOT NULL,
			object_type varchar(50) DEFAULT 'ticket' NOT NULL,
			term_id bigint(20) unsigned NOT NULL,
			taxonomy_slug varchar(100) NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY object_term_rel (object_id, object_type, term_id),
			KEY object_id (object_id),
			KEY term_id (term_id),
			KEY taxonomy_slug (taxonomy_slug)
		) $charset_collate;";
		dbDelta( $sql_relationships );

		// 6. Support Agents Profile Table
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

		// 7. Support Events / Audit Timeline Table
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

		// 8. Internal Notes Table (Staff Only)
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

		// 9. Support Assignments History Table
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

		// 10. Support Notification Log Table
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

		// Migrate any legacy table data if present
		self::migrate_legacy_data();

		// Seed initial default categories, tags, and settings if not already present.
		self::seed_default_data();

		update_option( self::DB_VERSION_OPTION, self::DB_VERSION );
	}

	/**
	 * Migrate legacy table data into unified taxonomy tables if present.
	 *
	 * @return void
	 */
	public static function migrate_legacy_data() {
		global $wpdb;

		$table_taxonomies    = $wpdb->prefix . 'dctc_support_taxonomies';
		$table_terms         = $wpdb->prefix . 'dctc_support_terms';
		$table_term_meta     = $wpdb->prefix . 'dctc_support_term_meta';
		$table_relationships = $wpdb->prefix . 'dctc_support_term_relationships';

		// 1. Seed / Migrate custom taxonomies from option into taxonomies table
		$custom_option = get_option( 'dctc_support_custom_taxonomies', array() );
		if ( is_array( $custom_option ) && ! empty( $custom_option ) ) {
			foreach ( $custom_option as $idx => $ct ) {
				if ( empty( $ct['slug'] ) ) {
					continue;
				}
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM `$table_taxonomies` WHERE slug = %s", $ct['slug'] ) );
				if ( ! $exists ) {
					$wpdb->insert(
						$table_taxonomies,
						array(
							'slug'          => $ct['slug'],
							'name'          => ! empty( $ct['name'] ) ? $ct['name'] : ucfirst( $ct['slug'] ),
							'description'   => ! empty( $ct['description'] ) ? $ct['description'] : '',
							'is_system'     => 0,
							'hierarchical'  => ! empty( $ct['hierarchical'] ) ? 1 : 0,
							'icon_type'     => ! empty( $ct['icon_type'] ) ? $ct['icon_type'] : 'preset',
							'icon_dashicon' => ! empty( $ct['icon_dashicon'] ) ? $ct['icon_dashicon'] : 'dashicons-category',
							'image_url'     => ! empty( $ct['image_url'] ) ? $ct['image_url'] : '',
							'color'         => ! empty( $ct['color'] ) ? $ct['color'] : '#6366F1',
							'display_order' => $idx + 10,
							'created_at'    => current_time( 'mysql' ),
							'updated_at'    => current_time( 'mysql' ),
						)
					);
				}
			}
		}

		// 2. Migrate legacy categories table (dctc_support_categories)
		$legacy_cats_table = $wpdb->prefix . 'dctc_support_categories';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$legacy_cats_table'" ) === $legacy_cats_table ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$legacy_cats = $wpdb->get_results( "SELECT * FROM `$legacy_cats_table`", ARRAY_A );
			if ( ! empty( $legacy_cats ) ) {
				foreach ( $legacy_cats as $cat ) {
					// Check if term already exists by id or slug in taxonomy 'category'
					$existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM `$table_terms` WHERE (id = %d OR slug = %s) AND taxonomy_slug = 'category'", absint( $cat['id'] ), $cat['slug'] ) );
					$term_id  = $existing ? (int) $existing : (int) $cat['id'];

					if ( ! $existing ) {
						$wpdb->insert(
							$table_terms,
							array(
								'id'            => $term_id,
								'taxonomy_slug' => 'category',
								'parent_id'     => ! empty( $cat['parent_id'] ) ? absint( $cat['parent_id'] ) : 0,
								'name'          => $cat['name'],
								'slug'          => $cat['slug'],
								'description'   => isset( $cat['description'] ) ? $cat['description'] : '',
								'color'         => ! empty( $cat['color'] ) ? $cat['color'] : '#4F46E5',
								'status'        => ! empty( $cat['status'] ) ? $cat['status'] : 'active',
								'display_order' => isset( $cat['display_order'] ) ? (int) $cat['display_order'] : 0,
								'created_at'    => ! empty( $cat['created_at'] ) ? $cat['created_at'] : current_time( 'mysql' ),
								'updated_at'    => ! empty( $cat['updated_at'] ) ? $cat['updated_at'] : current_time( 'mysql' ),
							)
						);
					}

					// Migrate category metadata
					$meta_map = array(
						'default_priority' => isset( $cat['default_priority'] ) ? $cat['default_priority'] : 'normal',
						'default_team_id'  => isset( $cat['default_team_id'] ) ? $cat['default_team_id'] : 0,
						'required_skills'  => isset( $cat['required_skills'] ) ? $cat['required_skills'] : '[]',
						'requires_human'   => isset( $cat['requires_human'] ) ? $cat['requires_human'] : 0,
						'ai_allowed'       => isset( $cat['ai_allowed'] ) ? $cat['ai_allowed'] : 1,
						'auto_assign'      => isset( $cat['auto_assign'] ) ? $cat['auto_assign'] : 1,
						'show_product'     => isset( $cat['show_product'] ) ? $cat['show_product'] : 1,
						'show_tags'        => isset( $cat['show_tags'] ) ? $cat['show_tags'] : 1,
						'sub_taxonomies'   => isset( $cat['sub_taxonomies'] ) ? $cat['sub_taxonomies'] : '["product","tag"]',
					);

					foreach ( $meta_map as $mkey => $mval ) {
						// phpcs:ignore WordPress.DB.DirectDatabaseQuery
						$m_exists = $wpdb->get_var( $wpdb->prepare( "SELECT meta_id FROM `$table_term_meta` WHERE term_id = %d AND meta_key = %s", $term_id, $mkey ) );
						if ( ! $m_exists ) {
							$wpdb->insert(
								$table_term_meta,
								array(
									'term_id'    => $term_id,
									'meta_key'   => $mkey,
									'meta_value' => is_array( $mval ) ? wp_json_encode( $mval ) : (string) $mval,
								)
							);
						}
					}
				}
			}
		}

		// 3. Migrate legacy products table (dctc_support_products)
		$legacy_prods_table = $wpdb->prefix . 'dctc_support_products';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$legacy_prods_table'" ) === $legacy_prods_table ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$legacy_prods = $wpdb->get_results( "SELECT * FROM `$legacy_prods_table`", ARRAY_A );
			if ( ! empty( $legacy_prods ) ) {
				foreach ( $legacy_prods as $prod ) {
					$existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM `$table_terms` WHERE slug = %s AND taxonomy_slug = 'product'", $prod['slug'] ) );
					if ( ! $existing ) {
						$wpdb->insert(
							$table_terms,
							array(
								'taxonomy_slug' => 'product',
								'parent_id'     => 0,
								'name'          => $prod['name'],
								'slug'          => $prod['slug'],
								'description'   => '',
								'color'         => '#059669',
								'status'        => ! empty( $prod['status'] ) ? $prod['status'] : 'active',
								'display_order' => 0,
								'created_at'    => ! empty( $prod['created_at'] ) ? $prod['created_at'] : current_time( 'mysql' ),
								'updated_at'    => ! empty( $prod['updated_at'] ) ? $prod['updated_at'] : current_time( 'mysql' ),
							)
						);
						$term_id = $wpdb->insert_id;
					} else {
						$term_id = (int) $existing;
					}

					if ( $term_id ) {
						$prod_meta = array(
							'category_id'   => isset( $prod['category_id'] ) ? $prod['category_id'] : 0,
							'wc_product_id' => isset( $prod['wc_product_id'] ) ? $prod['wc_product_id'] : 0,
							'sku'           => isset( $prod['sku'] ) ? $prod['sku'] : '',
							'price'         => isset( $prod['price'] ) ? $prod['price'] : 0.00,
						);
						foreach ( $prod_meta as $mkey => $mval ) {
							$m_exists = $wpdb->get_var( $wpdb->prepare( "SELECT meta_id FROM `$table_term_meta` WHERE term_id = %d AND meta_key = %s", $term_id, $mkey ) );
							if ( ! $m_exists ) {
								$wpdb->insert(
									$table_term_meta,
									array(
										'term_id'    => $term_id,
										'meta_key'   => $mkey,
										'meta_value' => (string) $mval,
									)
								);
							}
						}

						// Link product to category in term_relationships if category_id exists
						if ( ! empty( $prod['category_id'] ) ) {
							$rel_exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM `$table_relationships` WHERE object_id = %d AND object_type = 'term' AND term_id = %d", $term_id, absint( $prod['category_id'] ) ) );
							if ( ! $rel_exists ) {
								$wpdb->insert(
									$table_relationships,
									array(
										'object_id'     => $term_id,
										'object_type'   => 'term',
										'term_id'       => absint( $prod['category_id'] ),
										'taxonomy_slug' => 'category',
										'created_at'    => current_time( 'mysql' ),
									)
								);
							}
						}
					}
				}
			}
		}

		// 4. Migrate legacy tags table (dctc_support_tags)
		$legacy_tags_table = $wpdb->prefix . 'dctc_support_tags';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$legacy_tags_table'" ) === $legacy_tags_table ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$legacy_tags = $wpdb->get_results( "SELECT * FROM `$legacy_tags_table`", ARRAY_A );
			if ( ! empty( $legacy_tags ) ) {
				foreach ( $legacy_tags as $tag ) {
					$existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM `$table_terms` WHERE (id = %d OR slug = %s) AND taxonomy_slug = 'tag'", absint( $tag['id'] ), $tag['slug'] ) );
					if ( ! $existing ) {
						$wpdb->insert(
							$table_terms,
							array(
								'id'            => absint( $tag['id'] ),
								'taxonomy_slug' => 'tag',
								'parent_id'     => 0,
								'name'          => $tag['name'],
								'slug'          => $tag['slug'],
								'description'   => '',
								'color'         => ! empty( $tag['color'] ) ? $tag['color'] : '#4F46E5',
								'status'        => ! empty( $tag['status'] ) ? $tag['status'] : 'active',
								'display_order' => 0,
								'created_at'    => ! empty( $tag['created_at'] ) ? $tag['created_at'] : current_time( 'mysql' ),
								'updated_at'    => current_time( 'mysql' ),
							)
						);
					}
				}
			}
		}

		// 5. Migrate legacy custom taxonomy terms (dctc_support_taxonomy_terms)
		$legacy_tax_terms = $wpdb->prefix . 'dctc_support_taxonomy_terms';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$legacy_tax_terms'" ) === $legacy_tax_terms ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$legacy_tterms = $wpdb->get_results( "SELECT * FROM `$legacy_tax_terms`", ARRAY_A );
			if ( ! empty( $legacy_tterms ) ) {
				foreach ( $legacy_tterms as $tt ) {
					$existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM `$table_terms` WHERE slug = %s AND taxonomy_slug = %s", $tt['slug'], $tt['taxonomy_slug'] ) );
					if ( ! $existing ) {
						$wpdb->insert(
							$table_terms,
							array(
								'taxonomy_slug' => $tt['taxonomy_slug'],
								'parent_id'     => 0,
								'name'          => $tt['name'],
								'slug'          => $tt['slug'],
								'description'   => isset( $tt['description'] ) ? $tt['description'] : '',
								'color'         => ! empty( $tt['color'] ) ? $tt['color'] : '#4F46E5',
								'status'        => ! empty( $tt['status'] ) ? $tt['status'] : 'active',
								'display_order' => 0,
								'created_at'    => ! empty( $tt['created_at'] ) ? $tt['created_at'] : current_time( 'mysql' ),
								'updated_at'    => current_time( 'mysql' ),
							)
						);
					}
				}
			}
		}

		// 6. Migrate legacy ticket tags pivot (dctc_support_ticket_tags)
		$legacy_ticket_tags = $wpdb->prefix . 'dctc_support_ticket_tags';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$legacy_ticket_tags'" ) === $legacy_ticket_tags ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$legacy_ttags = $wpdb->get_results( "SELECT * FROM `$legacy_ticket_tags`", ARRAY_A );
			if ( ! empty( $legacy_ttags ) ) {
				foreach ( $legacy_ttags as $rel ) {
					$wpdb->replace(
						$table_relationships,
						array(
							'object_id'     => absint( $rel['ticket_id'] ),
							'object_type'   => 'ticket',
							'term_id'       => absint( $rel['tag_id'] ),
							'taxonomy_slug' => 'tag',
							'created_at'    => ! empty( $rel['created_at'] ) ? $rel['created_at'] : current_time( 'mysql' ),
						),
						array( '%d', '%s', '%d', '%s', '%s' )
					);
				}
			}
		}
	}

	/**
	 * Seed initial default categories, tags, and support configuration.
	 *
	 * @return void
	 */
	public static function seed_default_data() {
		global $wpdb;

		$table_taxonomies = $wpdb->prefix . 'dctc_support_taxonomies';
		$table_terms      = $wpdb->prefix . 'dctc_support_terms';
		$table_term_meta  = $wpdb->prefix . 'dctc_support_term_meta';

		// Seed Taxonomies (category, tag, product)
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$count_tax = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `$table_taxonomies`" );
		if ( 0 === $count_tax ) {
			$default_taxonomies = array(
				array(
					'slug'          => 'category',
					'name'          => 'Categories',
					'description'   => 'Main routing taxonomy with priority and staff assignment rules.',
					'is_system'     => 1,
					'hierarchical'  => 1,
					'icon_type'     => 'preset',
					'icon_dashicon' => 'dashicons-category',
					'color'         => '#4F46E5',
					'display_order' => 1,
				),
				array(
					'slug'          => 'tag',
					'name'          => 'Tags',
					'description'   => 'Visual classification tags for fast identification and badge design.',
					'is_system'     => 1,
					'hierarchical'  => 0,
					'icon_type'     => 'preset',
					'icon_dashicon' => 'dashicons-tag',
					'color'         => '#D97706',
					'display_order' => 2,
				),
				array(
					'slug'          => 'product',
					'name'          => 'Products',
					'description'   => 'WooCommerce and custom products for support catalog item routing.',
					'is_system'     => 1,
					'hierarchical'  => 0,
					'icon_type'     => 'preset',
					'icon_dashicon' => 'dashicons-products',
					'color'         => '#059669',
					'display_order' => 3,
				),
			);

			foreach ( $default_taxonomies as $dt ) {
				$wpdb->insert(
					$table_taxonomies,
					array(
						'slug'          => $dt['slug'],
						'name'          => $dt['name'],
						'description'   => $dt['description'],
						'is_system'     => $dt['is_system'],
						'hierarchical'  => $dt['hierarchical'],
						'icon_type'     => $dt['icon_type'],
						'icon_dashicon' => $dt['icon_dashicon'],
						'image_url'     => '',
						'color'         => $dt['color'],
						'display_order' => $dt['display_order'],
						'created_at'    => current_time( 'mysql' ),
						'updated_at'    => current_time( 'mysql' ),
					)
				);
			}
		}

		// Seed Category Terms
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$count_categories = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `$table_terms` WHERE taxonomy_slug = %s", 'category' ) );

		if ( 0 === $count_categories ) {
			$default_categories = array(
				array(
					'name'             => 'Product Support',
					'slug'             => 'product-support',
					'description'      => 'General questions and troubleshooting for products.',
					'color'            => '#4F46E5',
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
					'color'            => '#7C3AED',
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
					'color'            => '#DC2626',
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
					'color'            => '#059669',
					'default_priority' => 'normal',
					'required_skills'  => wp_json_encode( array( 'billing' ) ),
					'requires_human'   => 1,
					'ai_allowed'       => 0,
					'auto_assign'      => 1,
				),
			);

			foreach ( $default_categories as $idx => $cat ) {
				$wpdb->insert(
					$table_terms,
					array(
						'taxonomy_slug' => 'category',
						'parent_id'     => 0,
						'name'          => $cat['name'],
						'slug'          => $cat['slug'],
						'description'   => $cat['description'],
						'color'         => $cat['color'],
						'status'        => 'active',
						'display_order' => $idx + 1,
						'created_at'    => current_time( 'mysql' ),
						'updated_at'    => current_time( 'mysql' ),
					)
				);
				$term_id = $wpdb->insert_id;

				if ( $term_id ) {
					$meta_fields = array(
						'default_priority' => $cat['default_priority'],
						'default_team_id'  => 0,
						'required_skills'  => $cat['required_skills'],
						'requires_human'   => $cat['requires_human'],
						'ai_allowed'       => $cat['ai_allowed'],
						'auto_assign'      => $cat['auto_assign'],
						'show_product'     => 1,
						'show_tags'        => 1,
						'sub_taxonomies'   => wp_json_encode( array( 'product', 'tag' ) ),
					);
					foreach ( $meta_fields as $mkey => $mval ) {
						$wpdb->insert(
							$table_term_meta,
							array(
								'term_id'    => $term_id,
								'meta_key'   => $mkey,
								'meta_value' => (string) $mval,
							)
						);
					}
				}
			}
		}

		// Seed Tag Terms
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$count_tags = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `$table_terms` WHERE taxonomy_slug = %s", 'tag' ) );

		if ( 0 === $count_tags ) {
			$default_tags = array(
				array( 'name' => 'Urgent', 'slug' => 'urgent', 'color' => '#EF4444' ),
				array( 'name' => 'WooCommerce', 'slug' => 'woocommerce', 'color' => '#9333EA' ),
				array( 'name' => 'Bug', 'slug' => 'bug', 'color' => '#F59E0B' ),
				array( 'name' => 'Refund', 'slug' => 'refund', 'color' => '#DC2626' ),
				array( 'name' => 'Feature Request', 'slug' => 'feature-request', 'color' => '#3B82F6' ),
				array( 'name' => 'AI Escalation', 'slug' => 'ai-escalation', 'color' => '#10B981' ),
			);

			foreach ( $default_tags as $idx => $tag ) {
				$wpdb->insert(
					$table_terms,
					array(
						'taxonomy_slug' => 'tag',
						'parent_id'     => 0,
						'name'          => $tag['name'],
						'slug'          => $tag['slug'],
						'description'   => '',
						'color'         => $tag['color'],
						'status'        => 'active',
						'display_order' => $idx + 1,
						'created_at'    => current_time( 'mysql' ),
						'updated_at'    => current_time( 'mysql' ),
					)
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
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$count_agents = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `$table_agents`" );

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
					)
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

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$max_number = $wpdb->get_var( "SELECT MAX(ticket_number) FROM `$table_tickets`" );

		return $max_number ? ( (int) $max_number + 1 ) : 10001;
	}
}
