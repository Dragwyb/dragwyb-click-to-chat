<?php
/**
 * DCTC AI Leads Controller
 *
 * Owns structured lead capture from AI conversations, email alerts,
 * outbound webhook dispatch, and admin lead management REST endpoints.
 *
 * @package Dragwyb_Click_To_Chat
 */

if (!defined('ABSPATH')) {
	exit;
}

require_once DCTC_PLUGIN_DIR . 'includes/ai/trait-dctc-ai-rest-helpers.php';

class DCTC_AI_Leads_Controller
{
	use DCTC_AI_REST_Helpers;

	/**
	 * Public REST permission check for capturing leads.
	 *
	 * @param \WP_REST_Request $request
	 * @return bool
	 */
	public function permission_check_capture($request)
	{
		if (current_user_can('read')) {
			return true;
		}

		$nonce = $request->get_header('X-WP-Nonce');
		if (empty($nonce)) {
			$nonce = $request->get_param('_wpnonce');
		}

		if (!empty($nonce) && wp_verify_nonce($nonce, 'wp_rest')) {
			return true;
		}

		return false;
	}

	/**
	 * Admin REST permission check.
	 *
	 * @return bool
	 */
	public function permission_check_admin()
	{
		return current_user_can('manage_options');
	}

	/**
	 * REST callback: Capture new lead from chat.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function capture_lead($request)
	{
		// Simple IP rate-limiting for lead submissions (max 10 submissions per 10 minutes)
		$ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
		if (!empty($ip)) {
			$rl_key = 'dctc_ai_lead_rl_' . md5($ip);
			$count = (int) get_transient($rl_key);
			if ($count >= 10) {
				return $this->error_response(__('Too many submissions. Please wait a few minutes before trying again.', 'dragwyb-click-to-chat'), 429);
			}
			set_transient($rl_key, $count + 1, 600);
		}

		$params = $request->get_json_params();

		$name         = isset($params['name']) ? sanitize_text_field($params['name']) : '';
		$email        = isset($params['email']) ? sanitize_email($params['email']) : '';
		$phone        = isset($params['phone']) ? sanitize_text_field($params['phone']) : '';
		$company      = isset($params['company']) ? sanitize_text_field($params['company']) : '';
		$company_size = isset($params['company_size']) ? sanitize_text_field($params['company_size']) : '';
		$budget       = isset($params['budget']) ? sanitize_text_field($params['budget']) : '';
		$timeline     = isset($params['timeline']) ? sanitize_text_field($params['timeline']) : '';
		$interest     = isset($params['interest']) ? sanitize_text_field($params['interest']) : '';
		$requirement  = isset($params['requirement']) ? sanitize_textarea_field($params['requirement']) : '';
		$source_url   = isset($params['source_url']) ? esc_url_raw($params['source_url']) : '';
		$session_id   = isset($params['session_id']) ? sanitize_text_field($params['session_id']) : '';
		$consent      = isset($params['consent']) ? (bool) $params['consent'] : true;

		if (empty($name) && empty($email) && empty($phone)) {
			return $this->error_response(__('Please provide at least a name, email address, or phone number.', 'dragwyb-click-to-chat'), 400);
		}

		if (!empty($email) && !is_email($email)) {
			return $this->error_response(__('Please provide a valid email address.', 'dragwyb-click-to-chat'), 400);
		}

		// Calculate Multi-Factor Explainable Lead Qualification & Scoring
		$scoring = $this->calculate_lead_score([
			'name'         => $name,
			'email'        => $email,
			'phone'        => $phone,
			'company'      => $company,
			'company_size' => $company_size,
			'budget'       => $budget,
			'timeline'     => $timeline,
			'interest'     => $interest,
			'requirement'  => $requirement,
		]);

		$settings = DCTC_AI_Settings_Handler::dctc_ai_get_all_settings();
		$bot = isset($settings['chatbot']) ? $settings['chatbot'] : [];
		$threshold = isset($bot['lead_qualification_threshold']) ? intval($bot['lead_qualification_threshold']) : 70;

		$initial_status = ($scoring['score'] >= $threshold) ? 'qualified' : 'new';

		$lead_data = [
			'session_id'      => $session_id,
			'name'            => $name,
			'email'           => $email,
			'phone'           => $phone,
			'company'         => $company,
			'company_size'    => $company_size,
			'budget'          => $budget,
			'timeline'        => $timeline,
			'interest'        => $interest,
			'requirement'     => $requirement,
			'source_url'      => $source_url,
			'score'           => $scoring['score'],
			'intent_level'    => $scoring['intent_level'],
			'score_breakdown' => $scoring['breakdown'],
			'status'          => $initial_status,
			'consent'         => $consent ? 1 : 0,
		];

		$lead_id = DCTC_AI_DB::save_lead($lead_data);

		if (!$lead_id) {
			return $this->error_response(__('Failed to save lead. Please try again.', 'dragwyb-click-to-chat'), 500);
		}

		$lead_data['id'] = $lead_id;

		// 1. Link lead to session in wp_dctc_ai_sessions table
		if ( ! empty( $session_id ) ) {
			global $wpdb;
			$table_sessions = $wpdb->prefix . 'dctc_ai_sessions';
			$wpdb->update(
				$table_sessions,
				[
					'lead_id' => $lead_id,
					'email'   => ! empty( $email ) ? $email : $wpdb->get_var( $wpdb->prepare( "SELECT email FROM `$table_sessions` WHERE session_id = %s", $session_id ) ),
				],
				[ 'session_id' => $session_id ]
			);
		}

		// 2. Connect with Support Center if Support Center is enabled
		if ( class_exists( 'DCTC_Support_Ticket_Service' ) ) {
			$support_settings = get_option( 'dctc_support_settings', [] );
			if ( ! empty( $support_settings['enabled'] ) ) {
				$summary_parts = [];
				if ( ! empty( $name ) ) $summary_parts[] = 'Name: ' . $name;
				if ( ! empty( $email ) ) $summary_parts[] = 'Email: ' . $email;
				if ( ! empty( $phone ) ) $summary_parts[] = 'Phone: ' . $phone;
				if ( ! empty( $company ) ) $summary_parts[] = 'Company: ' . $company;
				if ( ! empty( $company_size ) ) $summary_parts[] = 'Company Size: ' . $company_size;
				if ( ! empty( $budget ) ) $summary_parts[] = 'Budget: ' . $budget;
				if ( ! empty( $timeline ) ) $summary_parts[] = 'Timeline: ' . $timeline;
				if ( ! empty( $interest ) ) $summary_parts[] = 'Interest: ' . $interest;
				if ( ! empty( $requirement ) ) $summary_parts[] = 'Requirement: ' . $requirement;

				$lead_summary = implode( "\n", $summary_parts );

				$existing_ticket = ! empty( $session_id ) ? DCTC_Support_Ticket_Service::get_ticket_by_session_id( $session_id ) : null;

				if ( $existing_ticket && ! empty( $existing_ticket['id'] ) ) {
					// UPDATE EXISTING TICKET for same session
					$ticket_id  = (int) $existing_ticket['id'];
					$ticket_obj = class_exists( 'DCTC_Support_Ticket' ) ? new DCTC_Support_Ticket( $ticket_id ) : null;

					if ( $ticket_obj && $ticket_obj->is_valid() ) {
						if ( ! empty( $email ) ) {
							$ticket_obj->update_email( $email );
						}
						if ( ! empty( $phone ) ) {
							$ticket_obj->update_phone( $phone );
						}
						if ( ! empty( $name ) ) {
							$ticket_obj->update_name( $name );
						}

						$ticket_obj->update_meta( 'lead_id', $lead_id, 'auto' );
						$ticket_obj->update_meta( 'lead_score', $scoring['score'], 'auto' );
						$ticket_obj->update_meta( 'intent_level', $scoring['intent_level'], 'auto' );
						if ( ! empty( $company ) ) $ticket_obj->update_meta( 'company', $company, 'auto' );
						if ( ! empty( $company_size ) ) $ticket_obj->update_meta( 'company_size', $company_size, 'auto' );
						if ( ! empty( $budget ) ) $ticket_obj->update_meta( 'budget', $budget, 'auto' );
						if ( ! empty( $timeline ) ) $ticket_obj->update_meta( 'timeline', $timeline, 'auto' );
						if ( ! empty( $interest ) ) {
							$ticket_obj->update_meta( 'interest', $interest, 'auto' );
							$ticket_obj->update_meta( 'product', $interest, 'auto' );
						}
						if ( ! empty( $requirement ) ) $ticket_obj->update_meta( 'requirement', $requirement, 'textarea' );
						if ( ! empty( $source_url ) ) $ticket_obj->update_meta( 'source_url', $source_url, 'url' );
						$ticket_obj->update_meta( 'interaction_type', 'LEAD_GENERATION', 'auto' );

						// Update subject if generic
						if ( ! empty( $name ) && ( strpos( $existing_ticket['subject'], 'Support Request' ) !== false || strpos( $existing_ticket['subject'], 'Guest' ) !== false || empty( $existing_ticket['subject'] ) ) ) {
							DCTC_Support_Ticket_Service::update_ticket_properties( $ticket_id, [ 'subject' => '[Lead] ' . $name ] );
						}
					} else {
						// Fallback via DCTC_Support_Ticket_Service
						if ( ! empty( $email ) ) {
							global $wpdb;
							$wpdb->update( $wpdb->prefix . 'dctc_support_tickets', [ 'customer_email' => $email, 'updated_at' => current_time( 'mysql' ) ], [ 'id' => $ticket_id ] );
						}
						if ( ! empty( $phone ) ) {
							DCTC_Support_Ticket_Service::update_ticket_meta( $ticket_id, 'customer_phone', $phone );
							DCTC_Support_Ticket_Service::update_ticket_meta( $ticket_id, 'phone', $phone );
						}
						if ( ! empty( $name ) ) {
							global $wpdb;
							$wpdb->update( $wpdb->prefix . 'dctc_support_tickets', [ 'customer_name' => $name ], [ 'id' => $ticket_id ] );
						}
						DCTC_Support_Ticket_Service::update_ticket_meta( $ticket_id, 'lead_id', $lead_id );
						DCTC_Support_Ticket_Service::update_ticket_meta( $ticket_id, 'lead_score', $scoring['score'] );
						DCTC_Support_Ticket_Service::update_ticket_meta( $ticket_id, 'intent_level', $scoring['intent_level'] );
						if ( ! empty( $company ) ) DCTC_Support_Ticket_Service::update_ticket_meta( $ticket_id, 'company', $company );
						if ( ! empty( $budget ) ) DCTC_Support_Ticket_Service::update_ticket_meta( $ticket_id, 'budget', $budget );
						if ( ! empty( $timeline ) ) DCTC_Support_Ticket_Service::update_ticket_meta( $ticket_id, 'timeline', $timeline );
						if ( ! empty( $interest ) ) DCTC_Support_Ticket_Service::update_ticket_meta( $ticket_id, 'interest', $interest );
						if ( ! empty( $requirement ) ) DCTC_Support_Ticket_Service::update_ticket_meta( $ticket_id, 'requirement', $requirement );
					}

					// Log Event in support ticket activity
					if ( class_exists( 'DCTC_Support_Event_Service' ) ) {
						DCTC_Support_Event_Service::log_event(
							$ticket_id,
							'lead_captured',
							'Inquiry received',
							'Lead details submitted: ' . ( ! empty( $email ) ? $email : $name ),
							'customer',
							0,
							! empty( $name ) ? $name : 'Visitor'
						);
					}

					// Add internal note with lead details
					if ( class_exists( 'DCTC_Support_Note_Service' ) && ! empty( $lead_summary ) ) {
						DCTC_Support_Note_Service::add_note(
							$ticket_id,
							"📋 AI Lead Capture Form Submitted:\n" . $lead_summary . "\nScore: " . $scoring['score'] . "/100 (" . strtoupper( $scoring['intent_level'] ) . ")",
							0,
							false
						);
					}
				} else {
					// CREATE NEW TICKET if no ticket existed for this session
					$created = DCTC_Support_Ticket_Service::create_ticket( [
						'subject'          => '[Lead] ' . ( ! empty( $name ) ? $name : ( ! empty( $email ) ? $email : 'Website Lead Inquiry' ) ),
						'session_id'       => $session_id,
						'customer_email'   => $email,
						'customer_name'    => $name,
						'origin_type'      => 'chatbot',
						'reply_surface'    => 'chatbot_widget',
						'interaction_type' => 'LEAD_GENERATION',
						'control_mode'     => 'ai',
						'initial_message'  => $lead_summary,
					] );

					if ( is_array( $created ) && ! empty( $created['id'] ) ) {
						$new_ticket_id = (int) $created['id'];
						if ( ! empty( $phone ) ) {
							DCTC_Support_Ticket_Service::update_ticket_meta( $new_ticket_id, 'customer_phone', $phone );
							DCTC_Support_Ticket_Service::update_ticket_meta( $new_ticket_id, 'phone', $phone );
						}
						DCTC_Support_Ticket_Service::update_ticket_meta( $new_ticket_id, 'lead_id', $lead_id );
						DCTC_Support_Ticket_Service::update_ticket_meta( $new_ticket_id, 'lead_score', $scoring['score'] );
						DCTC_Support_Ticket_Service::update_ticket_meta( $new_ticket_id, 'intent_level', $scoring['intent_level'] );
						if ( ! empty( $company ) ) DCTC_Support_Ticket_Service::update_ticket_meta( $new_ticket_id, 'company', $company );
						if ( ! empty( $company_size ) ) DCTC_Support_Ticket_Service::update_ticket_meta( $new_ticket_id, 'company_size', $company_size );
						if ( ! empty( $budget ) ) DCTC_Support_Ticket_Service::update_ticket_meta( $new_ticket_id, 'budget', $budget );
						if ( ! empty( $timeline ) ) DCTC_Support_Ticket_Service::update_ticket_meta( $new_ticket_id, 'timeline', $timeline );
						if ( ! empty( $interest ) ) DCTC_Support_Ticket_Service::update_ticket_meta( $new_ticket_id, 'interest', $interest );
						if ( ! empty( $requirement ) ) DCTC_Support_Ticket_Service::update_ticket_meta( $new_ticket_id, 'requirement', $requirement );
						if ( ! empty( $source_url ) ) DCTC_Support_Ticket_Service::update_ticket_meta( $new_ticket_id, 'source_url', $source_url );
					}
				}
			}
		}

		// 3. Dispatch Email Notification
		$this->maybe_send_lead_email($lead_data);

		// 4. Dispatch Webhook
		$this->maybe_dispatch_webhook($lead_data);

		return new \WP_REST_Response([
			'success' => true,
			'message' => __('Thank you! Your information has been received. Our team will contact you shortly.', 'dragwyb-click-to-chat'),
			'lead_id' => $lead_id,
			'score'   => $scoring['score'],
			'status'  => $initial_status,
		], 200);
	}

	/**
	 * Compute explainable multi-factor score and intent level for a lead.
	 *
	 * @param array $params
	 * @return array{score: int, intent_level: string, breakdown: array}
	 */
	public function calculate_lead_score(array $params)
	{
		$breakdown = [];
		$total = 0;

		// 1. Contact Information Completeness (Max 55)
		$contact_pts = 0;
		if (!empty($params['name'])) $contact_pts += 15;
		if (!empty($params['email']) && is_email($params['email'])) $contact_pts += 20;
		if (!empty($params['phone']) && strlen($params['phone']) >= 7) $contact_pts += 20;

		$breakdown[] = [
			'factor' => __('Contact Information', 'dragwyb-click-to-chat'),
			'points' => $contact_pts,
			'max'    => 55,
			'detail' => sprintf(__('Name (+15), Email (+20), Phone (+20) -> %d pts', 'dragwyb-click-to-chat'), $contact_pts),
		];
		$total += $contact_pts;

		// 2. Budget Range Signals (Max 20)
		$budget_pts = 0;
		$budget = strtolower($params['budget'] ?? '');
		if (!empty($budget)) {
			if (strpos($budget, '20k') !== false || strpos($budget, 'enterprise') !== false || strpos($budget, '10,000') !== false || strpos($budget, 'high') !== false) {
				$budget_pts = 20;
			} elseif (strpos($budget, '5k') !== false || strpos($budget, '1k') !== false || strpos($budget, 'medium') !== false) {
				$budget_pts = 15;
			} else {
				$budget_pts = 10;
			}
		}
		if ($budget_pts > 0) {
			$breakdown[] = [
				'factor' => __('Budget Specified', 'dragwyb-click-to-chat'),
				'points' => $budget_pts,
				'max'    => 20,
				'detail' => sprintf(__('Budget range "%s" -> +%d pts', 'dragwyb-click-to-chat'), esc_html($params['budget']), $budget_pts),
			];
			$total += $budget_pts;
		}

		// 3. Purchasing Timeline / Urgency (Max 15)
		$timeline_pts = 0;
		$timeline = strtolower($params['timeline'] ?? '');
		if (!empty($timeline)) {
			if (strpos($timeline, 'immediate') !== false || strpos($timeline, 'asap') !== false || strpos($timeline, 'urgent') !== false || strpos($timeline, 'week') !== false) {
				$timeline_pts = 15;
			} elseif (strpos($timeline, 'month') !== false || strpos($timeline, 'quarter') !== false) {
				$timeline_pts = 10;
			} else {
				$timeline_pts = 5;
			}
		}
		if ($timeline_pts > 0) {
			$breakdown[] = [
				'factor' => __('Purchase Timeline', 'dragwyb-click-to-chat'),
				'points' => $timeline_pts,
				'max'    => 15,
				'detail' => sprintf(__('Timeline "%s" -> +%d pts', 'dragwyb-click-to-chat'), esc_html($params['timeline']), $timeline_pts),
			];
			$total += $timeline_pts;
		}

		// 4. Product / Service Specificity & Company Profile (Max 20)
		$profile_pts = 0;
		if (!empty($params['company'])) $profile_pts += 5;
		if (!empty($params['company_size'])) $profile_pts += 5;
		if (!empty($params['interest'])) $profile_pts += 10;

		if ($profile_pts > 0) {
			$breakdown[] = [
				'factor' => __('Business & Interest Specificity', 'dragwyb-click-to-chat'),
				'points' => min(20, $profile_pts),
				'max'    => 20,
				'detail' => sprintf(__('Company, size & product interest specified -> +%d pts', 'dragwyb-click-to-chat'), min(20, $profile_pts)),
			];
			$total += min(20, $profile_pts);
		}

		// 5. Requirement & AI Intent signals (Max 15)
		$intent_level = 'medium';
		$requirement_text = strtolower($params['requirement'] ?? '');
		$intent_pts = 0;

		$high_intent_words = ['quote', 'pricing', 'buy', 'purchase', 'cost', 'demo', 'hire', 'contract', 'order', 'call me', 'proposal', 'deal', 'urgent', 'asap'];
		$matched = [];
		foreach ($high_intent_words as $w) {
			if (strpos($requirement_text, $w) !== false) {
				$matched[] = $w;
			}
		}

		if (count($matched) >= 2 || strpos($requirement_text, 'urgent') !== false || strpos($requirement_text, 'asap') !== false) {
			$intent_level = 'urgent';
			$intent_pts = 15;
		} elseif (count($matched) >= 1) {
			$intent_level = 'high';
			$intent_pts = 10;
		} elseif (strlen($requirement_text) >= 20) {
			$intent_level = 'medium';
			$intent_pts = 5;
		} else {
			$intent_level = 'low';
		}

		if ($intent_pts > 0) {
			$breakdown[] = [
				'factor' => __('AI Buying Intent & Urgency', 'dragwyb-click-to-chat'),
				'points' => $intent_pts,
				'max'    => 15,
				'detail' => sprintf(__('Intent level: %s (signals: %s) -> +%d pts', 'dragwyb-click-to-chat'), ucfirst($intent_level), !empty($matched) ? implode(', ', array_slice($matched, 0, 3)) : __('detailed requirement', 'dragwyb-click-to-chat'), $intent_pts),
			];
			$total += $intent_pts;
		}

		return [
			'score'        => min(100, $total),
			'intent_level' => $intent_level,
			'breakdown'    => $breakdown,
		];
	}

	/**
	 * REST callback: Get all leads for admin dashboard.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function get_leads($request)
	{
		$limit  = max(1, min(200, intval($request->get_param('limit') ?: 50)));
		$page   = max(1, intval($request->get_param('page') ?: 1));
		$offset = ($page - 1) * $limit;
		$status = sanitize_text_field($request->get_param('status') ?: 'all');
		$search = sanitize_text_field($request->get_param('search') ?: '');

		$leads = DCTC_AI_DB::get_leads($limit, $offset, $status, $search);
		$total = DCTC_AI_DB::get_leads_count($status, $search);

		return new \WP_REST_Response([
			'success' => true,
			'leads'   => $leads,
			'total'   => $total,
			'page'    => $page,
			'limit'   => $limit,
			'pages'   => ceil($total / $limit),
		], 200);
	}

	/**
	 * REST callback: Get single lead details with full conversation transcript.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function get_lead($request)
	{
		$id   = absint($request->get_param('id'));
		$lead = DCTC_AI_DB::get_lead($id);

		if (!$lead) {
			return $this->error_response(__('Lead not found.', 'dragwyb-click-to-chat'), 404);
		}

		return new \WP_REST_Response([
			'success' => true,
			'lead'    => $lead,
		], 200);
	}

	/**
	 * REST callback: Update status of a lead.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function update_status($request)
	{
		$id     = absint($request->get_param('id'));
		$params = $request->get_json_params();
		$status = isset($params['status']) ? sanitize_key($params['status']) : 'new';

		$updated = DCTC_AI_DB::update_lead_status($id, $status);
		if ($updated) {
			return new \WP_REST_Response(['success' => true, 'message' => __('Lead status updated.', 'dragwyb-click-to-chat')], 200);
		}

		return $this->error_response(__('Failed to update lead status.', 'dragwyb-click-to-chat'), 400);
	}

	/**
	 * REST callback: Delete a lead.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function delete_lead($request)
	{
		$id = absint($request->get_param('id'));
		if (DCTC_AI_DB::delete_lead($id)) {
			return new \WP_REST_Response(['success' => true, 'message' => __('Lead deleted.', 'dragwyb-click-to-chat')], 200);
		}
		return $this->error_response(__('Lead not found or could not be deleted.', 'dragwyb-click-to-chat'), 404);
	}

	/**
	 * REST callback: Export leads as CSV.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function export_csv($request)
	{
		$leads = DCTC_AI_DB::get_leads(1000, 0, 'all', '');

		$csv_rows = [];
		$csv_rows[] = ['ID', 'Name', 'Email', 'Phone', 'Company', 'Company Size', 'Budget', 'Timeline', 'Interest', 'Requirement', 'Lead Score', 'Intent Level', 'Status', 'Session ID', 'Created At'];

		foreach ($leads as $l) {
			$csv_rows[] = [
				$l['id'],
				$l['name'],
				$l['email'],
				$l['phone'],
				$l['company'],
				$l['company_size'] ?? '',
				$l['budget'] ?? '',
				$l['timeline'] ?? '',
				$l['interest'] ?? '',
				str_replace(["\r", "\n"], ' ', $l['requirement']),
				$l['score'],
				$l['intent_level'] ?? 'medium',
				$l['status'],
				$l['session_id'],
				$l['created_at'],
			];
		}

		$output = fopen('php://temp', 'r+');
		foreach ($csv_rows as $row) {
			fputcsv($output, $row);
		}
		rewind($output);
		$csv_content = stream_get_contents($output);
		fclose($output);

		return new \WP_REST_Response([
			'success'  => true,
			'csv'      => $csv_content,
			'filename' => 'leads-export-' . date('Y-m-d-His') . '.csv',
		], 200);
	}

	/**
	 * Send email notification to admin when new lead arrives.
	 *
	 * @param array $lead
	 * @return void
	 */
	public function maybe_send_lead_email(array $lead)
	{
		$settings = DCTC_AI_Settings_Handler::dctc_ai_get_all_settings();
		$bot = isset($settings['chatbot']) ? $settings['chatbot'] : [];

		$send_email = !isset($bot['enable_lead_email_alerts']) || (bool) $bot['enable_lead_email_alerts'];
		if (!$send_email) {
			return;
		}

		$to = !empty($bot['lead_notification_email']) ? sanitize_email($bot['lead_notification_email']) : get_option('admin_email');
		if (empty($to) || !is_email($to)) {
			return;
		}

		$site_name = get_bloginfo('name');
		$subject   = sprintf(
			/* translators: 1: Site name, 2: Lead name/email */
			__('[%1$s] New AI Chat Lead: %2$s', 'dragwyb-click-to-chat'),
			$site_name,
			!empty($lead['name']) ? $lead['name'] : (!empty($lead['email']) ? $lead['email'] : __('Visitor', 'dragwyb-click-to-chat'))
		);

		$body = sprintf(
			__("A new prospective lead has been captured by your AI Chatbot!\n\n" .
			   "• Name: %s\n" .
			   "• Email: %s\n" .
			   "• Phone: %s\n" .
			   "• Company: %s\n" .
			   "• Lead Score: %d / 100\n" .
			   "• Requirement: %s\n" .
			   "• Source Page: %s\n" .
			   "• Captured At: %s\n\n" .
			   "View and manage all leads in your WordPress dashboard:\n%s\n", 'dragwyb-click-to-chat'),
			!empty($lead['name']) ? $lead['name'] : '-',
			!empty($lead['email']) ? $lead['email'] : '-',
			!empty($lead['phone']) ? $lead['phone'] : '-',
			!empty($lead['company']) ? $lead['company'] : '-',
			intval($lead['score'] ?? 0),
			!empty($lead['requirement']) ? $lead['requirement'] : '-',
			!empty($lead['source_url']) ? $lead['source_url'] : '-',
			current_time('mysql'),
			admin_url('admin.php?page=dragwyb-click-to-chat-ai')
		);

		wp_mail($to, $subject, $body);
	}

	/**
	 * Dispatch outbound Webhook payload (Zapier, Make, CRM).
	 *
	 * @param array $lead
	 * @return void
	 */
	public function maybe_dispatch_webhook(array $lead)
	{
		$settings = DCTC_AI_Settings_Handler::dctc_ai_get_all_settings();
		$webhook_url = !empty($settings['chatbot']['lead_webhook_url']) ? esc_url_raw($settings['chatbot']['lead_webhook_url']) : '';

		if (empty($webhook_url) || !wp_http_validate_url($webhook_url)) {
			return;
		}

		$payload = [
			'event'     => 'ai_lead_captured',
			'site_url'  => home_url(),
			'site_name' => get_bloginfo('name'),
			'timestamp' => current_time('c'),
			'lead'      => $lead,
		];

		wp_remote_post($webhook_url, [
			'headers'   => ['Content-Type' => 'application/json'],
			'body'      => wp_json_encode($payload),
			'timeout'   => 10,
			'blocking'  => false, // Non-blocking asynchronous dispatch
			'sslverify' => apply_filters('https_local_ssl_verify', false),
		]);
	}
}
