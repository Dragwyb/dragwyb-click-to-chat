<?php
/**
 * DCTC AI Unified Inbox Controller
 *
 * Multi-channel conversation hub bridging AI chatbot, WhatsApp handoffs,
 * customer emails, agent assignments, internal staff notes & live human interventions.
 *
 * @package Dragwyb_Click_To_Chat
 */

if (!defined('ABSPATH')) {
	exit;
}

class DCTC_AI_Inbox_Controller
{
	use DCTC_AI_REST_Helpers;

	/**
	 * Permission check: only logged-in WordPress users with edit/manage rights.
	 *
	 * @param \WP_REST_Request $request
	 * @return bool
	 */
	public function permission_check($request)
	{
		return current_user_can('edit_posts') || current_user_can('manage_options');
	}

	/**
	 * List unified conversations with multi-channel and status filters.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function get_conversations($request)
	{
		global $wpdb;
		$table = $wpdb->prefix . 'dctc_ai_sessions';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
		if ($table_exists !== $table) {
			return new \WP_REST_Response([
				'success'       => true,
				'conversations' => [],
				'total'         => 0,
				'page'          => 1,
				'total_pages'   => 1,
			], 200);
		}

		$channel     = sanitize_text_field($request->get_param('channel') ?: 'all');
		$status      = sanitize_text_field($request->get_param('status') ?: 'all');
		$search      = sanitize_text_field($request->get_param('search') ?: '');
		$assigned_to = $request->get_param('assigned_to');
		$page        = max(1, intval($request->get_param('page') ?: 1));
		$per_page    = max(1, min(100, intval($request->get_param('per_page') ?: 20)));
		$offset      = ($page - 1) * $per_page;

		$where_clauses = ['1=1'];
		$params = [];

		if ($channel !== 'all' && in_array($channel, ['chatbot', 'whatsapp', 'telegram', 'email', 'phone'], true)) {
			$where_clauses[] = 'channel = %s';
			$params[] = $channel;
		}

		if ($status !== 'all' && in_array($status, ['active', 'waiting_human', 'assigned', 'resolved', 'closed'], true)) {
			$where_clauses[] = 'status = %s';
			$params[] = $status;
		}

		if (!empty($assigned_to)) {
			if ($assigned_to === 'me') {
				$where_clauses[] = 'assigned_to = %d';
				$params[] = get_current_user_id();
			} elseif ($assigned_to === 'unassigned') {
				$where_clauses[] = 'assigned_to = 0';
			} elseif (is_numeric($assigned_to)) {
				$where_clauses[] = 'assigned_to = %d';
				$params[] = intval($assigned_to);
			}
		}

		if (!empty($search)) {
			$like = '%' . $wpdb->esc_like($search) . '%';
			$where_clauses[] = '(session_id LIKE %s OR email LIKE %s OR content LIKE %s OR summary LIKE %s)';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
		}

		$where_sql = implode(' AND ', $where_clauses);

		// Total count
		if (!empty($params)) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$total = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM `{$table}` WHERE {$where_sql}", $params));
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$total = (int) $wpdb->get_var("SELECT COUNT(*) FROM `{$table}` WHERE {$where_sql}");
		}

		// Fetch rows
		$sql = "SELECT id, session_id, email, model, provider, content, summary, sentiment, intent_tag, channel, assigned_to, unread_count, tags, lead_id, created_at, updated_at, status FROM `{$table}` WHERE {$where_sql} ORDER BY updated_at DESC LIMIT %d OFFSET %d";
		$query_params = array_merge($params, [$per_page, $offset]);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results($wpdb->prepare($sql, $query_params), ARRAY_A);

		$conversations = [];
		if (!empty($rows)) {
			foreach ($rows as $row) {
				$msgs = json_decode($row['content'] ?? '', true);
				$last_msg = '';
				$last_role = 'user';
				if (is_array($msgs) && !empty($msgs)) {
					$last_item = end($msgs);
					$last_msg = mb_substr(wp_strip_all_tags($last_item['content'] ?? ''), 0, 100);
					$last_role = $last_item['role'] ?? 'user';
				}

				$assigned_user = null;
				if (!empty($row['assigned_to'])) {
					$u = get_userdata($row['assigned_to']);
					if ($u) {
						$assigned_user = [
							'id'   => $u->ID,
							'name' => $u->display_name,
						];
					}
				}

				$conversations[] = [
					'id'            => (int) $row['id'],
					'session_id'    => $row['session_id'],
					'email'         => $row['email'],
					'channel'       => !empty($row['channel']) ? $row['channel'] : 'chatbot',
					'status'        => !empty($row['status']) ? $row['status'] : 'active',
					'sentiment'     => !empty($row['sentiment']) ? $row['sentiment'] : 'neutral',
					'intent_tag'    => !empty($row['intent_tag']) ? $row['intent_tag'] : 'general',
					'unread_count'  => (int) ($row['unread_count'] ?? 0),
					'summary'       => $row['summary'],
					'assigned_to'   => $assigned_user,
					'last_message'  => $last_msg,
					'last_role'     => $last_role,
					'message_count' => is_array($msgs) ? count($msgs) : 0,
					'created_at'    => $row['created_at'],
					'updated_at'    => $row['updated_at'],
				];
			}
		}

		return new \WP_REST_Response([
			'success'       => true,
			'conversations' => $conversations,
			'total'         => $total,
			'page'          => $page,
			'total_pages'   => ceil($total / $per_page),
		], 200);
	}

	/**
	 * Get single conversation thread details.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function get_conversation($request)
	{
		global $wpdb;
		$table = $wpdb->prefix . 'dctc_ai_sessions';
		$id    = sanitize_text_field($request->get_param('id'));

		if (is_numeric($id)) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$row = $wpdb->get_row($wpdb->prepare("SELECT * FROM `{$table}` WHERE id = %d", intval($id)), ARRAY_A);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$row = $wpdb->get_row($wpdb->prepare("SELECT * FROM `{$table}` WHERE session_id = %s", $id), ARRAY_A);
		}

		if (!$row) {
			return new \WP_REST_Response([
				'success' => false,
				'message' => esc_html__('Conversation not found.', 'dragwyb-click-to-chat'),
			], 404);
		}

		// Clear unread count when viewed by agent
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->update($table, ['unread_count' => 0], ['id' => $row['id']]);

		$messages = json_decode($row['content'] ?? '', true) ?: [];
		$notes    = json_decode($row['internal_notes'] ?? '', true) ?: [];

		// Check linked lead
		$lead = null;
		if (!empty($row['lead_id'])) {
			$leads_table = $wpdb->prefix . 'dctc_ai_leads';
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$lead = $wpdb->get_row($wpdb->prepare("SELECT * FROM `{$leads_table}` WHERE id = %d", intval($row['lead_id'])), ARRAY_A);
		} elseif (!empty($row['session_id'])) {
			$leads_table = $wpdb->prefix . 'dctc_ai_leads';
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$lead = $wpdb->get_row($wpdb->prepare("SELECT * FROM `{$leads_table}` WHERE session_id = %s LIMIT 1", $row['session_id']), ARRAY_A);
		}

		$assigned_user = null;
		if (!empty($row['assigned_to'])) {
			$u = get_userdata($row['assigned_to']);
			if ($u) {
				$assigned_user = [
					'id'    => $u->ID,
					'name'  => $u->display_name,
					'email' => $u->user_email,
				];
			}
		}

		return new \WP_REST_Response([
			'success'      => true,
			'conversation' => [
				'id'             => (int) $row['id'],
				'session_id'     => $row['session_id'],
				'email'          => $row['email'],
				'channel'        => !empty($row['channel']) ? $row['channel'] : 'chatbot',
				'status'         => !empty($row['status']) ? $row['status'] : 'active',
				'sentiment'      => !empty($row['sentiment']) ? $row['sentiment'] : 'neutral',
				'intent_tag'     => !empty($row['intent_tag']) ? $row['intent_tag'] : 'general',
				'summary'        => $row['summary'],
				'provider'       => $row['provider'],
				'model'          => $row['model'],
				'assigned_to'    => $assigned_user,
				'messages'       => $messages,
				'internal_notes' => $notes,
				'lead'           => $lead,
				'created_at'     => $row['created_at'],
				'updated_at'     => $row['updated_at'],
			],
		], 200);
	}

	/**
	 * Send a human agent live reply into the conversation.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function send_reply($request)
	{
		global $wpdb;
		$table = $wpdb->prefix . 'dctc_ai_sessions';
		$id    = sanitize_text_field($request->get_param('id'));
		$body  = $request->get_json_params() ?: [];
		$text  = sanitize_textarea_field($body['message'] ?? '');

		if (empty($text)) {
			return new \WP_REST_Response([
				'success' => false,
				'message' => esc_html__('Reply message cannot be empty.', 'dragwyb-click-to-chat'),
			], 400);
		}

		if (is_numeric($id)) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$row = $wpdb->get_row($wpdb->prepare("SELECT * FROM `{$table}` WHERE id = %d", intval($id)), ARRAY_A);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$row = $wpdb->get_row($wpdb->prepare("SELECT * FROM `{$table}` WHERE session_id = %s", $id), ARRAY_A);
		}

		if (!$row) {
			return new \WP_REST_Response([
				'success' => false,
				'message' => esc_html__('Conversation not found.', 'dragwyb-click-to-chat'),
			], 404);
		}

		$current_user = wp_get_current_user();
		$messages = json_decode($row['content'] ?? '', true) ?: [];

		$agent_msg = [
			'role'        => 'agent',
			'content'     => $text,
			'author_name' => $current_user->display_name ?: 'Human Agent',
			'author_id'   => $current_user->ID,
			'timestamp'   => current_time('mysql'),
		];

		$messages[] = $agent_msg;

		// Update database
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->update(
			$table,
			[
				'content'     => wp_json_encode($messages),
				'status'      => 'assigned',
				'assigned_to' => $current_user->ID,
				'updated_at'  => current_time('mysql'),
			],
			['id' => $row['id']]
		);

		do_action('dctc_ai_inbox_agent_reply', $row['session_id'], $text, $current_user);

		return new \WP_REST_Response([
			'success' => true,
			'message' => esc_html__('Reply sent successfully.', 'dragwyb-click-to-chat'),
			'reply'   => $agent_msg,
		], 200);
	}

	/**
	 * Update conversation status (active, waiting_human, assigned, resolved, closed).
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function update_status($request)
	{
		global $wpdb;
		$table  = $wpdb->prefix . 'dctc_ai_sessions';
		$id     = sanitize_text_field($request->get_param('id'));
		$body   = $request->get_json_params() ?: [];
		$status = sanitize_key($body['status'] ?? 'active');

		if (!in_array($status, ['active', 'waiting_human', 'assigned', 'resolved', 'closed'], true)) {
			return new \WP_REST_Response([
				'success' => false,
				'message' => esc_html__('Invalid status value.', 'dragwyb-click-to-chat'),
			], 400);
		}

		$where = is_numeric($id) ? ['id' => intval($id)] : ['session_id' => $id];
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->update($table, ['status' => $status, 'updated_at' => current_time('mysql')], $where);

		return new \WP_REST_Response([
			'success' => true,
			'message' => esc_html__('Status updated.', 'dragwyb-click-to-chat'),
			'status'  => $status,
		], 200);
	}

	/**
	 * Assign conversation to a staff member.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function assign_agent($request)
	{
		global $wpdb;
		$table    = $wpdb->prefix . 'dctc_ai_sessions';
		$id       = sanitize_text_field($request->get_param('id'));
		$body     = $request->get_json_params() ?: [];
		$agent_id = isset($body['assigned_to']) ? intval($body['assigned_to']) : 0;

		$where = is_numeric($id) ? ['id' => intval($id)] : ['session_id' => $id];
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->update($table, ['assigned_to' => $agent_id, 'updated_at' => current_time('mysql')], $where);

		$user = $agent_id ? get_userdata($agent_id) : null;

		return new \WP_REST_Response([
			'success'     => true,
			'message'     => esc_html__('Assignment updated.', 'dragwyb-click-to-chat'),
			'assigned_to' => $user ? ['id' => $user->ID, 'name' => $user->display_name] : null,
		], 200);
	}

	/**
	 * Add an internal staff note to the conversation.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function add_internal_note($request)
	{
		global $wpdb;
		$table = $wpdb->prefix . 'dctc_ai_sessions';
		$id    = sanitize_text_field($request->get_param('id'));
		$body  = $request->get_json_params() ?: [];
		$note  = sanitize_textarea_field($body['note'] ?? '');

		if (empty($note)) {
			return new \WP_REST_Response([
				'success' => false,
				'message' => esc_html__('Note cannot be empty.', 'dragwyb-click-to-chat'),
			], 400);
		}

		if (is_numeric($id)) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$row = $wpdb->get_row($wpdb->prepare("SELECT * FROM `{$table}` WHERE id = %d", intval($id)), ARRAY_A);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$row = $wpdb->get_row($wpdb->prepare("SELECT * FROM `{$table}` WHERE session_id = %s", $id), ARRAY_A);
		}

		if (!$row) {
			return new \WP_REST_Response([
				'success' => false,
				'message' => esc_html__('Conversation not found.', 'dragwyb-click-to-chat'),
			], 404);
		}

		$current_user = wp_get_current_user();
		$notes = json_decode($row['internal_notes'] ?? '', true) ?: [];

		$new_note = [
			'id'         => 'note_' . uniqid(),
			'user_id'    => $current_user->ID,
			'user_name'  => $current_user->display_name ?: 'Staff',
			'note'       => $note,
			'created_at' => current_time('mysql'),
		];

		$notes[] = $new_note;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->update($table, ['internal_notes' => wp_json_encode($notes)], ['id' => $row['id']]);

		return new \WP_REST_Response([
			'success' => true,
			'message' => esc_html__('Internal note added.', 'dragwyb-click-to-chat'),
			'note'    => $new_note,
		], 200);
	}

	/**
	 * List WordPress users capable of handling support inbox.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function get_agents($request)
	{
		$users = get_users([
			'role__in' => ['administrator', 'editor', 'author', 'shop_manager'],
			'number'   => 50,
			'fields'   => ['ID', 'display_name', 'user_email'],
		]);

		$agents = array_map(function($u) {
			return [
				'id'    => $u->ID,
				'name'  => $u->display_name,
				'email' => $u->user_email,
			];
		}, $users);

		return new \WP_REST_Response([
			'success' => true,
			'agents'  => $agents,
		], 200);
	}
}
