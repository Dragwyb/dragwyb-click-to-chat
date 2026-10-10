<?php
/**
 * DCTC AI Database Handler
 *
 * @package Dragwyb_Click_To_Chat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class DCTC_AI_DB
 */
class DCTC_AI_DB {

	/**
	 * Create Tables
	 *
	 * Uses dbDelta to create or update the custom tables required.
	 *
	 * @return void
	 */
	public static function dctc_ai_create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$sessions_table  = $wpdb->prefix . 'dctc_ai_sessions';

		$sql_sessions = "CREATE TABLE `$sessions_table` (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			session_id varchar(100) NOT NULL,
			email varchar(100) DEFAULT '' NOT NULL,
			model varchar(100) NOT NULL,
			provider varchar(100) NOT NULL,
			content longtext NOT NULL,
			summary text DEFAULT NULL,
			sentiment varchar(30) DEFAULT 'neutral' NOT NULL,
			intent_tag varchar(50) DEFAULT 'general' NOT NULL,
			channel varchar(30) DEFAULT 'chatbot' NOT NULL,
			assigned_to bigint(20) unsigned DEFAULT 0 NOT NULL,
			unread_count int(11) DEFAULT 0 NOT NULL,
			tags text DEFAULT NULL,
			internal_notes longtext DEFAULT NULL,
			lead_id bigint(20) unsigned DEFAULT 0 NOT NULL,
			control_mode varchar(20) DEFAULT 'ai' NOT NULL,
			reply_surface varchar(30) DEFAULT 'chatbot_widget' NOT NULL,
			support_ticket_id bigint(20) unsigned DEFAULT 0 NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
			status varchar(20) DEFAULT 'active' NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY session_id (session_id),
			KEY status (status),
			KEY channel (channel),
			KEY assigned_to (assigned_to),
			KEY sentiment (sentiment),
			KEY intent_tag (intent_tag),
			KEY control_mode (control_mode),
			KEY support_ticket_id (support_ticket_id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql_sessions );

		// RAG Tables
		$rag_documents_table = $wpdb->prefix . 'dctc_ai_rag_documents';
		$sql_rag_documents   = "CREATE TABLE `$rag_documents_table` (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			document_id varchar(255) NOT NULL,
			post_id bigint(20) unsigned NOT NULL,
			post_type varchar(100) NOT NULL,
			title text NOT NULL,
			content longtext NOT NULL,
			excerpt text,
			url varchar(2083),
			hash varchar(32),
			indexed_at datetime,
			PRIMARY KEY (id),
			UNIQUE KEY document_id (document_id),
			KEY post_id (post_id),
			KEY post_type (post_type)
		) $charset_collate;";

		dbDelta( $sql_rag_documents );

		$rag_chunks_table = $wpdb->prefix . 'dctc_ai_rag_chunks';
		$sql_rag_chunks   = "CREATE TABLE `$rag_chunks_table` (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			document_id varchar(255) NOT NULL,
			chunk_index int(11) NOT NULL,
			content longtext NOT NULL,
			chunk_hash varchar(32),
			tokens_count int(11),
			vector_id varchar(255),
			embedding_status varchar(20) DEFAULT 'pending',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY document_id (document_id),
			KEY embedding_status (embedding_status),
			KEY vector_id (vector_id)
		) $charset_collate;";

		dbDelta( $sql_rag_chunks );

		$rag_metadata_table = $wpdb->prefix . 'dctc_ai_rag_metadata';
		$sql_rag_metadata   = "CREATE TABLE `$rag_metadata_table` (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			meta_key varchar(255) NOT NULL,
			meta_value longtext,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY meta_key (meta_key)
		) $charset_collate;";

		dbDelta( $sql_rag_metadata );

		$embeddings_table = $wpdb->prefix . 'dctc_ai_embeddings';
		$sql_embeddings   = "CREATE TABLE `$embeddings_table` (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			chunk_id bigint(20) unsigned NOT NULL,
			embedding longblob NOT NULL,
			model varchar(100),
			provider varchar(100),
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY chunk_id (chunk_id)
		) $charset_collate;";

		dbDelta( $sql_embeddings );

		// Leads Table
		$leads_table = $wpdb->prefix . 'dctc_ai_leads';
		$sql_leads   = "CREATE TABLE `$leads_table` (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			session_id varchar(100) DEFAULT '' NOT NULL,
			name varchar(150) DEFAULT '' NOT NULL,
			email varchar(150) DEFAULT '' NOT NULL,
			phone varchar(50) DEFAULT '' NOT NULL,
			company varchar(150) DEFAULT '' NOT NULL,
			company_size varchar(100) DEFAULT '' NOT NULL,
			budget varchar(100) DEFAULT '' NOT NULL,
			timeline varchar(100) DEFAULT '' NOT NULL,
			interest varchar(255) DEFAULT '' NOT NULL,
			requirement text,
			source_url varchar(2083) DEFAULT '' NOT NULL,
			score int(11) DEFAULT 0 NOT NULL,
			intent_level varchar(50) DEFAULT 'medium' NOT NULL,
			score_breakdown longtext,
			status varchar(30) DEFAULT 'new' NOT NULL,
			consent tinyint(1) DEFAULT 1 NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY (id),
			KEY session_id (session_id),
			KEY email (email),
			KEY status (status),
			KEY score (score),
			KEY created_at (created_at)
		) $charset_collate;";

		dbDelta( $sql_leads );

		// Leads column migrations for existing tables
		$leads_table_esc = esc_sql( $leads_table );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$leads_cols = $wpdb->get_col(
			$wpdb->prepare(
				'SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = %s AND TABLE_SCHEMA = DATABASE()',
				$leads_table_esc
			)
		);

		if ( ! empty( $leads_cols ) ) {
			$col_defs = array(
				'company_size'    => "ADD COLUMN `company_size` varchar(100) DEFAULT '' NOT NULL AFTER `company`",
				'budget'          => "ADD COLUMN `budget` varchar(100) DEFAULT '' NOT NULL AFTER `company_size`",
				'timeline'        => "ADD COLUMN `timeline` varchar(100) DEFAULT '' NOT NULL AFTER `budget`",
				'interest'        => "ADD COLUMN `interest` varchar(255) DEFAULT '' NOT NULL AFTER `timeline`",
				'intent_level'    => "ADD COLUMN `intent_level` varchar(50) DEFAULT 'medium' NOT NULL AFTER `score`",
				'score_breakdown' => 'ADD COLUMN `score_breakdown` longtext AFTER `intent_level`',
			);
			foreach ( $col_defs as $col => $sql_part ) {
				if ( ! in_array( $col, $leads_cols, true ) ) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$wpdb->query( "ALTER TABLE `$leads_table_esc` $sql_part" );
				}
			}
		}

		// Sessions column migrations for existing tables
		$sessions_table_esc = esc_sql( $sessions_table );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$session_cols = $wpdb->get_col(
			$wpdb->prepare(
				'SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = %s AND TABLE_SCHEMA = DATABASE()',
				$sessions_table_esc
			)
		);

		if ( ! empty( $session_cols ) ) {
			$sess_defs = array(
				'summary'           => 'ADD COLUMN `summary` text DEFAULT NULL AFTER `content`',
				'sentiment'         => "ADD COLUMN `sentiment` varchar(30) DEFAULT 'neutral' NOT NULL AFTER `summary`",
				'intent_tag'        => "ADD COLUMN `intent_tag` varchar(50) DEFAULT 'general' NOT NULL AFTER `sentiment`",
				'channel'           => "ADD COLUMN `channel` varchar(30) DEFAULT 'chatbot' NOT NULL AFTER `intent_tag`",
				'assigned_to'       => 'ADD COLUMN `assigned_to` bigint(20) unsigned DEFAULT 0 NOT NULL AFTER `channel`',
				'unread_count'      => 'ADD COLUMN `unread_count` int(11) DEFAULT 0 NOT NULL AFTER `assigned_to`',
				'tags'              => 'ADD COLUMN `tags` text DEFAULT NULL AFTER `unread_count`',
				'internal_notes'    => 'ADD COLUMN `internal_notes` longtext DEFAULT NULL AFTER `tags`',
				'lead_id'           => 'ADD COLUMN `lead_id` bigint(20) unsigned DEFAULT 0 NOT NULL AFTER `internal_notes`',
				'control_mode'      => "ADD COLUMN `control_mode` varchar(20) DEFAULT 'ai' NOT NULL AFTER `lead_id`",
				'reply_surface'     => "ADD COLUMN `reply_surface` varchar(30) DEFAULT 'chatbot_widget' NOT NULL AFTER `control_mode`",
				'support_ticket_id' => 'ADD COLUMN `support_ticket_id` bigint(20) unsigned DEFAULT 0 NOT NULL AFTER `reply_surface`',
			);
			foreach ( $sess_defs as $col => $sql_part ) {
				if ( ! in_array( $col, $session_cols, true ) ) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$wpdb->query( "ALTER TABLE `$sessions_table_esc` $sql_part" );
				}
			}
		}

		// Add vector_id column if it doesn't exist (migration)
		$chunks_table = esc_sql( $wpdb->prefix . 'dctc_ai_rag_chunks' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for custom schema column check, caching is not applicable.
		$column_exists = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = %s AND COLUMN_NAME = %s AND TABLE_SCHEMA = DATABASE()',
				$chunks_table,
				'vector_id'
			)
		);

		if ( empty( $column_exists ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Safe schema alteration, no user input, caching not applicable.
			$wpdb->query( "ALTER TABLE `$chunks_table` ADD COLUMN `vector_id` varchar(255) AFTER `tokens_count`" );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Safe schema alteration, no user input, caching not applicable.
			$wpdb->query( "ALTER TABLE `$chunks_table` ADD KEY `vector_id` (`vector_id`)" );
		}

		// Ensure Error Logs table is created
		if ( class_exists( 'DCTC_Error_Logger' ) ) {
			DCTC_Error_Logger::create_table();
		}

		// Initialize Support Center Custom Tables ONLY if feature is enabled
		$support_settings = get_option( 'dctc_support_settings', array() );
		if ( ! empty( $support_settings['enabled'] ) && file_exists( DCTC_PLUGIN_DIR . 'includes/ai/support/class-dctc-support-db.php' ) ) {
			require_once DCTC_PLUGIN_DIR . 'includes/ai/support/class-dctc-support-db.php';
			DCTC_Support_DB::create_tables();
		}

		// Ensure RAG settings are initialized
		require_once DCTC_PLUGIN_DIR . 'includes/ai/class-dctc-ai-settings-handler.php';
		$current_settings = get_option( 'dctc_ai_chat_assistant_settings', array() );
		if ( empty( $current_settings ) || empty( $current_settings['rag'] ) ) {
			$all_settings = DCTC_AI_Settings_Handler::dctc_ai_get_all_settings();
			update_option( 'dctc_ai_chat_assistant_settings', $all_settings );
		}
	}

	/**
	 * Ensure all modern columns exist on existing sessions and leads tables.
	 * Can be safely called during runtime without overhead.
	 *
	 * @return void
	 */
	public static function ensure_session_columns() {
		global $wpdb;
		$sessions_table     = $wpdb->prefix . 'dctc_ai_sessions';
		$sessions_table_esc = esc_sql( $sessions_table );

		// Quick check if table exists
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_exists = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = %s AND TABLE_SCHEMA = DATABASE()',
				$sessions_table_esc
			)
		);

		if ( ! $table_exists ) {
			self::dctc_ai_create_tables();
			return;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$session_cols = $wpdb->get_col(
			$wpdb->prepare(
				'SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = %s AND TABLE_SCHEMA = DATABASE()',
				$sessions_table_esc
			)
		);

		if ( ! empty( $session_cols ) ) {
			$sess_defs = array(
				'summary'           => 'ADD COLUMN `summary` text DEFAULT NULL AFTER `content`',
				'sentiment'         => "ADD COLUMN `sentiment` varchar(30) DEFAULT 'neutral' NOT NULL AFTER `summary`",
				'intent_tag'        => "ADD COLUMN `intent_tag` varchar(50) DEFAULT 'general' NOT NULL AFTER `sentiment`",
				'channel'           => "ADD COLUMN `channel` varchar(30) DEFAULT 'chatbot' NOT NULL AFTER `intent_tag`",
				'assigned_to'       => 'ADD COLUMN `assigned_to` bigint(20) unsigned DEFAULT 0 NOT NULL AFTER `channel`',
				'unread_count'      => 'ADD COLUMN `unread_count` int(11) DEFAULT 0 NOT NULL AFTER `assigned_to`',
				'tags'              => 'ADD COLUMN `tags` text DEFAULT NULL AFTER `unread_count`',
				'internal_notes'    => 'ADD COLUMN `internal_notes` longtext DEFAULT NULL AFTER `tags`',
				'lead_id'           => 'ADD COLUMN `lead_id` bigint(20) unsigned DEFAULT 0 NOT NULL AFTER `internal_notes`',
				'control_mode'      => "ADD COLUMN `control_mode` varchar(20) DEFAULT 'ai' NOT NULL AFTER `lead_id`",
				'reply_surface'     => "ADD COLUMN `reply_surface` varchar(30) DEFAULT 'chatbot_widget' NOT NULL AFTER `control_mode`",
				'support_ticket_id' => 'ADD COLUMN `support_ticket_id` bigint(20) unsigned DEFAULT 0 NOT NULL AFTER `reply_surface`',
			);
			foreach ( $sess_defs as $col => $sql_part ) {
				if ( ! in_array( $col, $session_cols, true ) ) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$wpdb->query( "ALTER TABLE `$sessions_table_esc` $sql_part" );
				}
			}
		}
	}

	/**
	 * Save Message
	 *
	 * @param string $prompt     The user prompt.
	 * @param string $response   The AI response.
	 * @param string $session_id The session identifier.
	 * @param string $provider   The AI provider used.
	 * @param string $model      The AI model used.
	 * @param string $email      The user email.
	 * @param array  $sources    Optional source links/citations.
	 * @return string The final session_id used.
	 */
	public function dctc_ai_save_message( $prompt, $response, $session_id = 'default', $provider = '', $model = '', $email = '', $sources = array(), $extra = array() ) {
		$prompt     = sanitize_textarea_field( $prompt );
		$response   = wp_kses_post( $response );
		$session_id = sanitize_text_field( $session_id );
		$provider   = sanitize_text_field( $provider );
		$model      = sanitize_text_field( $model );
		$email      = sanitize_email( $email );

		global $wpdb;

		$table = esc_sql( $wpdb->prefix . 'dctc_ai_sessions' );
		$time  = current_time( 'mysql' );

		$cache_key   = 'dctc_ai_session_' . md5( $session_id );
		$cache_group = 'dctc_ai';

		$existing_messages = wp_cache_get( $cache_key, $cache_group );

		if ( false === $existing_messages ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Required for custom table query.
			$existing_messages = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT content FROM {$table} WHERE session_id = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
					$session_id
				)
			);
		}

		$assistant_entry = array(
			'role'        => 'assistant',
			'sender_type' => 'ai_agent',
			'sender_name' => 'AI Assistant',
			'content'     => $response,
			'created_at'  => $time,
		);

		if ( ! empty( $extra['show_form'] ) && is_array( $extra['show_form'] ) ) {
			$assistant_entry['show_form'] = array(
				'form_type'   => ! empty( $extra['show_form']['form_type'] ) ? sanitize_text_field( $extra['show_form']['form_type'] ) : 'lead_generate',
				'show'        => isset( $extra['show_form']['show'] ) ? (bool) $extra['show_form']['show'] : true,
				'form_filled' => ! empty( $extra['show_form']['form_filled'] ),
			);
		} elseif ( ! empty( $extra['form_type'] ) ) {
			$assistant_entry['show_form'] = array(
				'form_type'   => 'lead_generate',
				'show'        => true,
				'form_filled' => false,
			);
		}

		if ( ! empty( $sources ) && is_array( $sources ) ) {
			$assistant_entry['sources'] = array_values(
				array_filter(
					array_map(
						function ( $s ) {
							if ( ! is_array( $s ) || empty( $s['title'] ) ) {
									return null;
							}
							return array(
								'title' => sanitize_text_field( $s['title'] ),
								'url'   => esc_url_raw( $s['url'] ?? '' ),
							);
						},
						$sources
					)
				)
			);
		}

		$new_messages = array(
			array(
				'role'        => 'user',
				'sender_type' => 'customer',
				'sender_name' => ! empty( $email ) ? $email : 'Customer',
				'content'     => $prompt,
				'created_at'  => $time,
			),
			$assistant_entry,
		);

		if ( $existing_messages ) {
			$messages = json_decode( $existing_messages, true );
			$messages = is_array( $messages ) ? $messages : array();

			// If new message displays a form, hide previous unsubmitted forms in conversation history
			if ( ! empty( $assistant_entry['show_form']['show'] ) ) {
				foreach ( $messages as &$prev_msg ) {
					if ( is_array( $prev_msg ) && isset( $prev_msg['show_form'] ) && is_array( $prev_msg['show_form'] ) ) {
						if ( ! empty( $prev_msg['show_form']['show'] ) && empty( $prev_msg['show_form']['form_filled'] ) ) {
							$prev_msg['show_form']['show'] = false;
						}
					}
				}
				unset( $prev_msg );
			}

			$last_idx = count( $messages ) - 1;
			// Prevent duplicate user message if the last message in history is already this user prompt
			if ( $last_idx >= 0 && isset( $messages[ $last_idx ]['role'] ) && 'user' === $messages[ $last_idx ]['role'] && trim( (string) $messages[ $last_idx ]['content'] ) === trim( (string) $prompt ) ) {
				$messages[] = $assistant_entry;
			} else {
				$messages = array_merge( $messages, $new_messages );
			}

			$messages = array_slice( $messages, -50 );

			$messages_json = wp_json_encode( $messages );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Updating custom table.
			$wpdb->update(
				$table,
				array(
					'content'    => $messages_json,
					'provider'   => $provider,
					'model'      => $model,
					'email'      => $email,
					'updated_at' => $time,
				),
				array(
					'session_id' => $session_id,
				),
				array( '%s', '%s', '%s', '%s', '%s' ),
				array( '%s' )
			);

			wp_cache_set( $cache_key, $messages_json, $cache_group, HOUR_IN_SECONDS );

		} else {
			$messages      = $new_messages;
			$messages_json = wp_json_encode( $new_messages );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Inserting into custom table.
			$wpdb->insert(
				$table,
				array(
					'session_id' => $session_id,
					'email'      => $email,
					'model'      => $model,
					'provider'   => $provider,
					'content'    => $messages_json,
					'created_at' => $time,
					'updated_at' => $time,
				),
				array(
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
				)
			);

			wp_cache_set( $cache_key, $messages_json, $cache_group, HOUR_IN_SECONDS );
		}

		// If a support ticket exists for this session, touch ticket updated_at & sync messages
		$linked_ticket = class_exists( 'DCTC_Support_Ticket_Service' )
			? DCTC_Support_Ticket_Service::get_ticket_by_session_id( $session_id )
			: null;

		if ( $linked_ticket && ! empty( $linked_ticket['id'] ) ) {
			$table_tickets = esc_sql( $wpdb->prefix . 'dctc_support_tickets' );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->update(
				$table_tickets,
				array(
					'updated_at'            => $time,
					'customer_last_seen_at' => $time,
				),
				array( 'id' => absint( $linked_ticket['id'] ) )
			);

			// Keep ticket meta synchronized with full conversation including AI assistant response
			DCTC_Support_Ticket_Service::update_ticket_meta( $linked_ticket['id'], '_dctc_ticket_messages', $messages );
		}

		return $session_id;
	}

	/**
	 * Get all chat sessions ordered by chronological activity.
	 *
	 * @param string|int $limit The limit of sessions to fetch.
	 * @param string     $order The sorting order ('ASC' or 'DESC').
	 * @return array<int, array<string, mixed>>
	 */
	public static function dctc_ai_get_all_sessions( $limit = '100', $order = 'desc' ) {
		global $wpdb;

		$table = esc_sql( $wpdb->prefix . 'dctc_ai_sessions' );

		// Normalize and validate order.
		$order_clean = esc_sql( strtoupper( $order ) === 'ASC' ? 'ASC' : 'DESC' );

		// Normalize and build limit SQL segment. A limit of exactly 0 (or a
		// negative value) should mean "zero rows", not "no LIMIT clause at
		// all" — so the LIMIT is always applied whenever $limit isn't 'all'.
		$limit_sql = esc_sql( '' );
		if ( $limit !== 'all' ) {
			$limit_val = max( 0, intval( $limit ) );
			$limit_sql = esc_sql( " LIMIT {$limit_val}" );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom table read.
		$sessions = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY created_at {$order_clean}{$limit_sql}", ARRAY_A );

		return is_array( $sessions ) ? $sessions : array();
	}

	/**
	 * Delete a session by session_id.
	 *
	 * @param string $session_id The session identifier.
	 * @return bool True when a row was deleted.
	 */
	public static function dctc_ai_delete_session( $session_id ) {
		global $wpdb;

		$session_id = sanitize_text_field( $session_id );
		if ( empty( $session_id ) ) {
			return false;
		}

		$table = $wpdb->prefix . 'dctc_ai_sessions';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table delete.
		$deleted = $wpdb->delete(
			$table,
			array( 'session_id' => $session_id ),
			array( '%s' )
		);

		wp_cache_delete( 'dctc_ai_session_' . md5( $session_id ), 'dctc_ai' );

		return (bool) $deleted;
	}

	/**
	 * Clean up old chat sessions based on retention policy.
	 *
	 * @param int $days Number of days to keep. 0 means keep forever.
	 * @return int Number of deleted rows.
	 */
	public static function dctc_ai_clean_old_sessions( $days ) {
		$days = absint( $days );
		if ( $days <= 0 ) {
			return 0;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'dctc_ai_sessions';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table} WHERE updated_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
				$days
			)
		);

		return is_numeric( $deleted ) ? intval( $deleted ) : 0;
	}

	/**
	 * Insert or update a captured lead.
	 *
	 * @param array $lead
	 * @return int Lead ID
	 */
	public static function save_lead( array $lead ) {
		global $wpdb;
		$table = $wpdb->prefix . 'dctc_ai_leads';
		$time  = current_time( 'mysql' );

		$session_id = sanitize_text_field( $lead['session_id'] ?? '' );
		$lead_id    = ! empty( $lead['id'] ) ? absint( $lead['id'] ) : 0;

		// If no explicit lead_id is provided, check if a lead already exists for this session
		if ( ! $lead_id && ! empty( $session_id ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$existing_lead = $wpdb->get_row(
				$wpdb->prepare( "SELECT * FROM `{$table}` WHERE session_id = %s ORDER BY id DESC LIMIT 1", $session_id ),
				ARRAY_A
			);

			if ( $existing_lead ) {
				$lead_id = absint( $existing_lead['id'] );

				// Smart merge non-empty values
				$new_name    = sanitize_text_field( $lead['name'] ?? '' );
				$merged_name = ( ! empty( $new_name ) && 'Chat Visitor' !== $new_name )
					? $new_name
					: ( ! empty( $existing_lead['name'] ) ? $existing_lead['name'] : ( $new_name ?: 'Chat Visitor' ) );

				$new_email      = sanitize_email( $lead['email'] ?? '' );
				$is_placeholder = empty( $new_email ) || false !== strpos( $new_email, '@lead.local' );
				$merged_email   = ( ! $is_placeholder )
					? $new_email
					: ( ! empty( $existing_lead['email'] ) && false === strpos( $existing_lead['email'], '@lead.local' ) ? $existing_lead['email'] : $new_email );

				$new_phone    = sanitize_text_field( $lead['phone'] ?? '' );
				$merged_phone = ! empty( $new_phone ) ? $new_phone : ( $existing_lead['phone'] ?? '' );

				$new_company    = sanitize_text_field( $lead['company'] ?? '' );
				$merged_company = ! empty( $new_company ) ? $new_company : ( $existing_lead['company'] ?? '' );

				$new_size    = sanitize_text_field( $lead['company_size'] ?? '' );
				$merged_size = ! empty( $new_size ) ? $new_size : ( $existing_lead['company_size'] ?? '' );

				$new_budget    = sanitize_text_field( $lead['budget'] ?? '' );
				$merged_budget = ! empty( $new_budget ) ? $new_budget : ( $existing_lead['budget'] ?? '' );

				$new_timeline    = sanitize_text_field( $lead['timeline'] ?? '' );
				$merged_timeline = ! empty( $new_timeline ) ? $new_timeline : ( $existing_lead['timeline'] ?? '' );

				$new_interest    = sanitize_text_field( $lead['interest'] ?? '' );
				$merged_interest = ! empty( $new_interest ) ? $new_interest : ( $existing_lead['interest'] ?? '' );

				$new_req      = sanitize_textarea_field( $lead['requirement'] ?? '' );
				$existing_req = $existing_lead['requirement'] ?? '';
				if ( ! empty( $new_req ) && ! empty( $existing_req ) && false === strpos( $existing_req, $new_req ) ) {
					$merged_req = $existing_req . "\n" . $new_req;
				} else {
					$merged_req = ! empty( $new_req ) ? $new_req : $existing_req;
				}

				$new_score    = intval( $lead['score'] ?? 0 );
				$merged_score = max( intval( $existing_lead['score'] ?? 0 ), $new_score );

				$merged_data = array(
					'session_id'      => $session_id,
					'name'            => $merged_name,
					'email'           => $merged_email,
					'phone'           => $merged_phone,
					'company'         => $merged_company,
					'company_size'    => $merged_size,
					'budget'          => $merged_budget,
					'timeline'        => $merged_timeline,
					'interest'        => $merged_interest,
					'requirement'     => $merged_req,
					'source_url'      => ! empty( $lead['source_url'] ) ? esc_url_raw( $lead['source_url'] ) : ( $existing_lead['source_url'] ?? '' ),
					'score'           => $merged_score,
					'intent_level'    => ! empty( $lead['intent_level'] ) ? sanitize_key( $lead['intent_level'] ) : ( $existing_lead['intent_level'] ?? 'medium' ),
					'score_breakdown' => is_array( $lead['score_breakdown'] ?? null ) ? wp_json_encode( $lead['score_breakdown'] ) : ( $lead['score_breakdown'] ?? ( $existing_lead['score_breakdown'] ?? '' ) ),
					'status'          => ! empty( $lead['status'] ) ? sanitize_key( $lead['status'] ) : ( $existing_lead['status'] ?? 'new' ),
					'consent'         => ! empty( $lead['consent'] ) ? 1 : ( ! empty( $existing_lead['consent'] ) ? 1 : 0 ),
					'updated_at'      => $time,
				);

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->update( $table, $merged_data, array( 'id' => $lead_id ) );
				return $lead_id;
			}
		}

		$data = array(
			'session_id'      => $session_id,
			'name'            => sanitize_text_field( $lead['name'] ?? '' ),
			'email'           => sanitize_email( $lead['email'] ?? '' ),
			'phone'           => sanitize_text_field( $lead['phone'] ?? '' ),
			'company'         => sanitize_text_field( $lead['company'] ?? '' ),
			'company_size'    => sanitize_text_field( $lead['company_size'] ?? '' ),
			'budget'          => sanitize_text_field( $lead['budget'] ?? '' ),
			'timeline'        => sanitize_text_field( $lead['timeline'] ?? '' ),
			'interest'        => sanitize_text_field( $lead['interest'] ?? '' ),
			'requirement'     => sanitize_textarea_field( $lead['requirement'] ?? '' ),
			'source_url'      => esc_url_raw( $lead['source_url'] ?? '' ),
			'score'           => intval( $lead['score'] ?? 0 ),
			'intent_level'    => sanitize_key( $lead['intent_level'] ?? 'medium' ),
			'score_breakdown' => is_array( $lead['score_breakdown'] ?? null ) ? wp_json_encode( $lead['score_breakdown'] ) : ( $lead['score_breakdown'] ?? '' ),
			'status'          => sanitize_key( $lead['status'] ?? 'new' ),
			'consent'         => ! empty( $lead['consent'] ) ? 1 : 0,
			'updated_at'      => $time,
		);

		if ( ! empty( $lead_id ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->update( $table, $data, array( 'id' => $lead_id ) );
			return $lead_id;
		}

		$data['created_at'] = $time;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert( $table, $data );
		return $wpdb->insert_id;
	}

	/**
	 * Get leads with optional status filter and search.
	 *
	 * @param int    $limit
	 * @param int    $offset
	 * @param string $status
	 * @param string $search
	 * @return array
	 */
	public static function get_leads( $limit = 50, $offset = 0, $status = '', $search = '' ) {
		global $wpdb;
		$table = $wpdb->prefix . 'dctc_ai_leads';

		$where  = ' WHERE 1=1';
		$params = array();

		if ( ! empty( $status ) && 'all' !== $status ) {
			$where   .= ' AND status = %s';
			$params[] = sanitize_key( $status );
		}

		if ( ! empty( $search ) ) {
			$like     = '%' . $wpdb->esc_like( sanitize_text_field( $search ) ) . '%';
			$where   .= ' AND (name LIKE %s OR email LIKE %s OR phone LIKE %s OR company LIKE %s OR requirement LIKE %s)';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
		}

		$limit_sql = sprintf( ' ORDER BY id DESC LIMIT %d OFFSET %d', max( 1, intval( $limit ) ), max( 0, intval( $offset ) ) );

		if ( ! empty( $params ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			$query = $wpdb->prepare( "SELECT * FROM {$table}{$where}{$limit_sql}", ...$params );
		} else {
			$query = "SELECT * FROM {$table}{$where}{$limit_sql}";
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$results = $wpdb->get_results( $query, ARRAY_A );
		if ( ! is_array( $results ) ) {
			return array();
		}

		return self::enrich_leads_with_conversation( $results );
	}

	/**
	 * Get a single lead by ID with conversation history.
	 *
	 * @param int $id
	 * @return array|null
	 */
	public static function get_lead( $id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'dctc_ai_leads';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$table}` WHERE id = %d LIMIT 1", absint( $id ) ), ARRAY_A );
		if ( ! $row ) {
			return null;
		}

		$enriched = self::enrich_leads_with_conversation( array( $row ) );
		return ! empty( $enriched[0] ) ? $enriched[0] : $row;
	}

	/**
	 * Attach full conversation transcripts to lead records.
	 *
	 * @param array $leads
	 * @return array
	 */
	public static function enrich_leads_with_conversation( $leads ) {
		if ( empty( $leads ) || ! is_array( $leads ) ) {
			return array();
		}

		global $wpdb;
		$table_sessions = $wpdb->prefix . 'dctc_ai_sessions';

		$session_ids = array();
		$lead_ids    = array();

		foreach ( $leads as $l ) {
			if ( ! empty( $l['session_id'] ) ) {
				$session_ids[] = sanitize_text_field( $l['session_id'] );
			}
			if ( ! empty( $l['id'] ) ) {
				$lead_ids[] = (int) $l['id'];
			}
		}

		$session_map = array();

		if ( ! empty( $session_ids ) ) {
			$unique_sessions = array_unique( $session_ids );
			$placeholders    = implode( ',', array_fill( 0, count( $unique_sessions ), '%s' ) );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$session_rows = $wpdb->get_results(
				$wpdb->prepare( "SELECT session_id, lead_id, content, support_ticket_id FROM `{$table_sessions}` WHERE session_id IN ({$placeholders})", ...$unique_sessions ),
				ARRAY_A
			);

			if ( is_array( $session_rows ) ) {
				foreach ( $session_rows as $srow ) {
					$raw_content = $srow['content'] ?? '';
					$decoded     = ! empty( $raw_content ) ? json_decode( $raw_content, true ) : array();
					if ( is_array( $decoded ) ) {
						$session_map[ $srow['session_id'] ] = array(
							'messages'          => $decoded,
							'support_ticket_id' => ! empty( $srow['support_ticket_id'] ) ? (int) $srow['support_ticket_id'] : 0,
						);
					}
				}
			}
		}

		// Also check by lead_id in session table for sessions that didn't have session_id match
		if ( ! empty( $lead_ids ) ) {
			$unique_leads = array_unique( $lead_ids );
			$placeholders = implode( ',', array_fill( 0, count( $unique_leads ), '%d' ) );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$lead_session_rows = $wpdb->get_results(
				$wpdb->prepare( "SELECT session_id, lead_id, content, support_ticket_id FROM `{$table_sessions}` WHERE lead_id IN ({$placeholders})", ...$unique_leads ),
				ARRAY_A
			);

			if ( is_array( $lead_session_rows ) ) {
				foreach ( $lead_session_rows as $lsrow ) {
					$lid         = (int) $lsrow['lead_id'];
					$raw_content = $lsrow['content'] ?? '';
					$decoded     = ! empty( $raw_content ) ? json_decode( $raw_content, true ) : array();
					if ( is_array( $decoded ) && ! empty( $decoded ) ) {
						if ( empty( $session_map[ $lsrow['session_id'] ] ) ) {
							$session_map[ $lsrow['session_id'] ] = array(
								'messages'          => $decoded,
								'support_ticket_id' => ! empty( $lsrow['support_ticket_id'] ) ? (int) $lsrow['support_ticket_id'] : 0,
							);
						}
						$session_map[ "lead_{$lid}" ] = array(
							'messages'          => $decoded,
							'support_ticket_id' => ! empty( $lsrow['support_ticket_id'] ) ? (int) $lsrow['support_ticket_id'] : 0,
						);
					}
				}
			}
		}

		foreach ( $leads as &$lead ) {
			$sid = $lead['session_id'] ?? '';
			$lid = (int) ( $lead['id'] ?? 0 );

			$session_data = null;
			if ( ! empty( $sid ) && isset( $session_map[ $sid ] ) ) {
				$session_data = $session_map[ $sid ];
			} elseif ( isset( $session_map[ "lead_{$lid}" ] ) ) {
				$session_data = $session_map[ "lead_{$lid}" ];
			}

			$raw_messages = $session_data['messages'] ?? array();
			$ticket_id    = $session_data['support_ticket_id'] ?? 0;

			// If ticket exists and session table has empty messages, check ticket messages meta
			if ( empty( $raw_messages ) && $ticket_id > 0 && class_exists( 'DCTC_Support_Ticket_Service' ) ) {
				$ticket_meta_msgs = DCTC_Support_Ticket_Service::get_ticket_meta( $ticket_id, '_dctc_ticket_messages', true );
				if ( is_array( $ticket_meta_msgs ) && ! empty( $ticket_meta_msgs ) ) {
					$raw_messages = $ticket_meta_msgs;
				}
			}

			// Clean and standardize conversation messages for the admin view
			$clean_conversation = array();
			if ( is_array( $raw_messages ) ) {
				foreach ( $raw_messages as $m ) {
					if ( ! is_array( $m ) ) {
						continue;
					}

					// Filter out internal system logs if any
					$role        = sanitize_key( $m['role'] ?? ( ( isset( $m['type'] ) && 'user' === $m['type'] ) ? 'user' : 'assistant' ) );
					$sender_type = sanitize_key( $m['sender_type'] ?? ( 'user' === $role ? 'customer' : 'ai_agent' ) );

					if ( 'system' === $role || 'system' === $sender_type ) {
						continue;
					}

					$content = isset( $m['content'] ) ? (string) $m['content'] : ( $m['text'] ?? ( $m['message'] ?? '' ) );
					// Remove internal intent comments and AI thinking tokens
					$content = preg_replace( '/<!--INTENT:.*?-->/s', '', $content );
					$content = preg_replace( '/<think>.*?<\/think>/s', '', $content );
					$content = trim( $content );

					if ( empty( $content ) ) {
						continue;
					}

					// Determine clean sender type: 'customer' (visitor/user), 'human_agent' (human support), 'ai_agent' (AI response)
					if ( 'human_agent' === $sender_type || 'agent' === $sender_type || 'support' === $sender_type ) {
						$normalized_type = 'human_agent';
						$sender_name     = ! empty( $m['sender_name'] ) ? $m['sender_name'] : __( 'Support Agent', 'dragwyb-click-to-chat' );
					} elseif ( 'user' === $role || 'customer' === $sender_type || 'visitor' === $sender_type ) {
						$normalized_type = 'customer';
						$sender_name     = ! empty( $m['sender_name'] ) ? $m['sender_name'] : ( ! empty( $lead['name'] ) ? $lead['name'] : __( 'Visitor', 'dragwyb-click-to-chat' ) );
					} else {
						$normalized_type = 'ai_agent';
						$sender_name     = ! empty( $m['sender_name'] ) ? $m['sender_name'] : __( 'AI Assistant', 'dragwyb-click-to-chat' );
					}

					$clean_conversation[] = array(
						'role'        => ( 'customer' === $normalized_type ) ? 'user' : 'assistant',
						'sender_type' => $normalized_type,
						'sender_name' => sanitize_text_field( $sender_name ),
						'content'     => $content,
						'created_at'  => ! empty( $m['created_at'] ) ? $m['created_at'] : ( ! empty( $m['timestamp'] ) ? $m['timestamp'] : '' ),
					);
				}
			}

			// Fallback: If no session history existed, but requirement message was recorded, construct initial visitor message
			if ( empty( $clean_conversation ) && ! empty( $lead['requirement'] ) ) {
				$clean_conversation[] = array(
					'role'        => 'user',
					'sender_type' => 'customer',
					'sender_name' => ! empty( $lead['name'] ) ? $lead['name'] : __( 'Visitor', 'dragwyb-click-to-chat' ),
					'content'     => $lead['requirement'],
					'created_at'  => $lead['created_at'] ?? '',
				);
			}

			$lead['conversation'] = $clean_conversation;
		}
		unset( $lead );

		return $leads;
	}

	/**
	 * Get total count of leads for pagination.
	 *
	 * @param string $status
	 * @param string $search
	 * @return int
	 */
	public static function get_leads_count( $status = '', $search = '' ) {
		global $wpdb;
		$table = $wpdb->prefix . 'dctc_ai_leads';

		$where  = ' WHERE 1=1';
		$params = array();

		if ( ! empty( $status ) && 'all' !== $status ) {
			$where   .= ' AND status = %s';
			$params[] = sanitize_key( $status );
		}

		if ( ! empty( $search ) ) {
			$like     = '%' . $wpdb->esc_like( sanitize_text_field( $search ) ) . '%';
			$where   .= ' AND (name LIKE %s OR email LIKE %s OR phone LIKE %s OR company LIKE %s OR requirement LIKE %s)';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
		}

		if ( ! empty( $params ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table}{$where}", ...$params ) );
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$table}{$where}" );
		}

		return intval( $count );
	}

	/**
	 * Delete a lead by ID.
	 *
	 * @param int $id
	 * @return bool
	 */
	public static function delete_lead( $id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'dctc_ai_leads';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (bool) $wpdb->delete( $table, array( 'id' => absint( $id ) ), array( '%d' ) );
	}

	/**
	 * Update lead status.
	 *
	 * @param int    $id
	 * @param string $status
	 * @return bool
	 */
	public static function update_lead_status( $id, $status ) {
		global $wpdb;
		$table = $wpdb->prefix . 'dctc_ai_leads';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (bool) $wpdb->update(
			$table,
			array(
				'status'     => sanitize_key( $status ),
				'updated_at' => current_time( 'mysql' ),
			),
			array( 'id' => absint( $id ) ),
			array( '%s', '%s' ),
			array( '%d' )
		);
	}
	/**
	 * Update session summary, sentiment, and intent tag.
	 *
	 * @param string $session_id
	 * @param string $summary
	 * @param string $sentiment
	 * @param string $intent_tag
	 * @return bool
	 */
	public static function update_session_summary( $session_id, $summary, $sentiment = 'neutral', $intent_tag = 'general' ) {
		global $wpdb;
		$table = $wpdb->prefix . 'dctc_ai_sessions';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (bool) $wpdb->update(
			$table,
			array(
				'summary'    => sanitize_textarea_field( $summary ),
				'sentiment'  => sanitize_key( $sentiment ),
				'intent_tag' => sanitize_key( $intent_tag ),
				'updated_at' => current_time( 'mysql' ),
			),
			array( 'session_id' => sanitize_text_field( $session_id ) ),
			array( '%s', '%s', '%s', '%s' ),
			array( '%s' )
		);
	}

	/**
	 * Get comprehensive conversation analytics & business intelligence.
	 *
	 * @return array
	 */
	public static function get_conversation_analytics() {
		global $wpdb;
		$sess_table  = $wpdb->prefix . 'dctc_ai_sessions';
		$leads_table = $wpdb->prefix . 'dctc_ai_leads';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$total_conversations = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$sess_table}`" );
		$today_start         = current_time( 'Y-m-d 00:00:00' );
		$today_conversations = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$sess_table}` WHERE created_at >= %s", $today_start ) );

		$total_leads     = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$leads_table}`" );
		$qualified_leads = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$leads_table}` WHERE status = 'qualified' OR score >= 70" );

		$conversion_rate = $total_conversations > 0 ? round( ( $total_leads / $total_conversations ) * 100, 1 ) : 0;

		// Sentiment Breakdown
		$sentiment_rows = $wpdb->get_results( "SELECT sentiment, COUNT(*) as count FROM `{$sess_table}` GROUP BY sentiment", ARRAY_A );
		$sentiments     = array(
			'positive'   => 0,
			'neutral'    => 0,
			'frustrated' => 0,
		);
		if ( is_array( $sentiment_rows ) ) {
			foreach ( $sentiment_rows as $sr ) {
				$key                = sanitize_key( $sr['sentiment'] ?: 'neutral' );
				$sentiments[ $key ] = (int) $sr['count'];
			}
		}

		// Intent Breakdown
		$intent_rows = $wpdb->get_results( "SELECT intent_tag, COUNT(*) as count FROM `{$sess_table}` GROUP BY intent_tag ORDER BY count DESC LIMIT 6", ARRAY_A );
		$intents     = array();
		if ( is_array( $intent_rows ) ) {
			foreach ( $intent_rows as $ir ) {
				$key             = $ir['intent_tag'] ? sanitize_key( $ir['intent_tag'] ) : 'general';
				$intents[ $key ] = (int) $ir['count'];
			}
		}
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

		// Estimated AI Resolution Rate (conversations with positive/neutral sentiment and no errors)
		$frustrated_count = $sentiments['frustrated'] ?? 0;
		$resolved_count   = max( 0, $total_conversations - $frustrated_count );
		$resolution_rate  = $total_conversations > 0 ? round( ( $resolved_count / $total_conversations ) * 100, 1 ) : 100;

		return array(
			'total_conversations' => $total_conversations,
			'today_conversations' => $today_conversations,
			'total_leads'         => $total_leads,
			'qualified_leads'     => $qualified_leads,
			'conversion_rate'     => $conversion_rate,
			'resolution_rate'     => $resolution_rate,
			'sentiments'          => $sentiments,
			'intents'             => $intents,
		);
	}
}
