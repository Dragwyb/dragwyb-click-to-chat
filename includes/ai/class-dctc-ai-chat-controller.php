<?php
/**
 * DCTC AI Chat Controller
 *
 * Owns the public chat endpoint: permission/rate-limit checks, building the
 * system prompt (chatbot config + RAG context + MCP context + memory), the
 * actual AI provider call, and conversation/session persistence.
 *
 * @package Dragwyb_Click_To_Chat
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Class DCTC_AI_Chat_Controller
 */
class DCTC_AI_Chat_Controller
{
	use DCTC_AI_REST_Helpers;

	/**
	 * RAG Controller
	 *
	 * @var DCTC_AI_RAG_Controller
	 */
	private $rag_controller;

	/**
	 * MCP Controller
	 *
	 * @var DCTC_AI_MCP_Controller
	 */
	private $mcp_controller;

	/**
	 * Constructor
	 *
	 * @param DCTC_AI_RAG_Controller $rag_controller Used to retrieve RAG context for prompts.
	 * @param DCTC_AI_MCP_Controller $mcp_controller Used to retrieve MCP context for prompts.
	 */
	public function __construct(DCTC_AI_RAG_Controller $rag_controller, DCTC_AI_MCP_Controller $mcp_controller)
	{
		$this->rag_controller = $rag_controller;
		$this->mcp_controller = $mcp_controller;
	}

	/**
	 * Public Chat Permission Check
	 *
	 * Allows access if the user is logged in (capability check) OR
	 * if the visitor provides a valid REST API nonce (proving they are using our frontend).
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return bool True if authorized.
	 */
	public function permission_check($request)
	{
		if (current_user_can('read')) {
			return true;
		}
		$nonce = $request->get_header('X-WP-Nonce');
		if ($nonce && wp_verify_nonce($nonce, 'wp_rest')) {
			return true;
		}
		return false;
	}

	/**
	 * REST callback: handle a chat message end-to-end.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return \WP_REST_Response Formatted answer response.
	 */
	public function handle($request)
	{
		if ($this->is_rate_limited()) {
			return $this->error_response(
				esc_html__('Too many requests. Please wait a moment and try again.', 'dragwyb-click-to-chat'),
				429
			);
		}

		$params = $request->get_json_params();

		$prompt = isset($params['prompt']) ? sanitize_textarea_field($params['prompt']) : '';
		$session_id = isset($params['session_id']) ? sanitize_text_field($params['session_id']) : 'default';
		$email = isset($params['email']) ? sanitize_email($params['email']) : '';
		$attachments = isset($params['attachments']) && is_array($params['attachments']) ? array_values(array_filter(array_map(function($att) {
			if (!is_array($att)) {
				return null;
			}
			return [
				'id' => isset($att['id']) ? sanitize_text_field($att['id']) : uniqid('att_'),
				'attachmentId' => isset($att['attachmentId']) ? intval($att['attachmentId']) : 0,
				'type' => (isset($att['type']) && $att['type'] === 'image') ? 'image' : 'file',
				'name' => isset($att['name']) ? sanitize_file_name($att['name']) : '',
				'size' => isset($att['size']) ? intval($att['size']) : 0,
				'mime' => isset($att['mime']) ? sanitize_text_field($att['mime']) : '',
				'url' => isset($att['url']) ? esc_url_raw($att['url']) : '',
			];
		}, $params['attachments']))) : [];

		$settings = DCTC_AI_Settings_Handler::dctc_ai_get_all_settings();

		if (empty($settings)) {
			return $this->error_response(
				esc_html__('Plugin settings not configured.', 'dragwyb-click-to-chat'),
				500
			);
		}

		$bot = isset($settings['chatbot']) ? $settings['chatbot'] : [];
		$models = isset($settings['models']) ? $settings['models'] : [];

		// Check AI Usage & Budget Limits
		if (class_exists('DCTC_AI_Usage_Tracker')) {
			$budget_check = DCTC_AI_Usage_Tracker::check_budget_and_limits($session_id);
			if (isset($budget_check['allowed']) && !$budget_check['allowed']) {
				return $this->save_and_respond(
					$budget_check['message'],
					$session_id,
					$bot,
					$email,
					$prompt,
					!empty($budget_check['action_buttons']) ? $budget_check['action_buttons'] : []
				);
			}
		}

		if (!current_user_can('manage_options')) {
			$cookie_session_id = isset($_COOKIE['dctc_ai_session_id']) ? sanitize_text_field(wp_unslash($_COOKIE['dctc_ai_session_id'])) : '';
			if (empty($cookie_session_id) || $cookie_session_id !== $session_id) {
				return $this->error_response(
					esc_html__('Unauthorized session access.', 'dragwyb-click-to-chat'),
					403
				);
			}
		}

		if (empty($prompt) && empty($attachments)) {
			return $this->error_response(
				esc_html__('Empty prompt provided.', 'dragwyb-click-to-chat'),
				400
			);
		}

		if (empty($prompt) && !empty($attachments)) {
			$prompt = esc_html__('Please analyze the attached file(s).', 'dragwyb-click-to-chat');
		}

		// Hybrid Support Check: Block automatic AI generation if HUMAN_CONTROL is active
		if (class_exists('DCTC_Support_AI_Handoff_Service') && DCTC_Support_AI_Handoff_Service::should_block_ai_response($session_id)) {
			$human_response = DCTC_Support_AI_Handoff_Service::handle_customer_message_in_human_mode($session_id, $prompt, $email);
			return new \WP_REST_Response($human_response, 200);
		}

		// Intelligent WooCommerce Order Tracker Intent
		if (!empty($prompt) && class_exists('WooCommerce') && self::detect_order_tracking_intent($prompt)) {
			$is_logged_in = is_user_logged_in() || (!empty($page_context) && !empty($page_context['is_logged_in']));
			if ($is_logged_in) {
				$tracker_msg = esc_html__('Please enter your Order ID and billing email below to view your real-time order and shipment tracking details.', 'dragwyb-click-to-chat');
				return new \WP_REST_Response([
					'success'            => true,
					'message'            => $tracker_msg,
					'show_order_tracker' => true,
					'session_id'         => $session_id,
				], 200);
			} else {
				$login_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : wp_login_url();
				$not_logged_in_msg = sprintf(
					/* translators: %s: Login URL */
					esc_html__('To securely track your order status, please [log in to your account](%s) first.', 'dragwyb-click-to-chat'),
					esc_url($login_url)
				);
				return new \WP_REST_Response([
					'success'            => true,
					'message'            => $not_logged_in_msg,
					'show_order_tracker' => false,
					'session_id'         => $session_id,
				], 200);
			}
		}

		// Intelligent Support Escalation & Ticket Logging
		if (!empty($prompt) && (!isset($bot['enable_support_escalation']) || (bool) $bot['enable_support_escalation']) && self::detect_human_handoff_intent($prompt)) {
			$classification = self::classify_user_intent($prompt);
			// Auto-create/link support ticket if Support Center is enabled
			if (class_exists('DCTC_Support_Ticket_Service')) {
				$support_settings = get_option('dctc_support_settings', []);
				if (!empty($support_settings['enabled'])) {
					global $wpdb;
					$table_tickets = $wpdb->prefix . 'dctc_support_tickets';
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$existing_ticket = $wpdb->get_row($wpdb->prepare("SELECT id FROM `$table_tickets` WHERE session_id = %s", $session_id), ARRAY_A);
					if (!$existing_ticket) {
						$prefix = '';
						if ('lead_generation' === $classification['intent']) {
							$prefix = '[Lead] ';
						} elseif ('support_ticket' === $classification['intent']) {
							$prefix = '[Support] ';
						}

						$auto_pause = isset($bot['auto_pause_ai_on_ticket']) ? (bool) $bot['auto_pause_ai_on_ticket'] : (!empty($support_settings['auto_pause_ai']));

						DCTC_Support_Ticket_Service::create_ticket([
							'subject'          => $prefix . wp_trim_words($prompt, 8, '...'),
							'session_id'       => $session_id,
							'customer_email'   => $email,
							'origin_type'      => 'chatbot',
							'reply_surface'    => 'chatbot_widget',
							'interaction_type' => 'HYBRID_SUPPORT',
							'control_mode'     => $auto_pause ? 'human' : 'ai',
							'initial_message'  => $prompt,
						]);
					}
				}
			}

			$escalation_text = esc_html__('I have logged your inquiry with our support team and created a support ticket for this session. A support specialist will review your message and assist you shortly.', 'dragwyb-click-to-chat');

			return $this->save_and_respond(
				$escalation_text,
				$session_id,
				$bot,
				$email,
				$prompt,
				[],
				true
			);
		}

		if (empty($bot) || empty($models)) {
			return $this->error_response(
				esc_html__('Chatbot settings incomplete.', 'dragwyb-click-to-chat'),
				500
			);
		}

		try {
			$provider = $this->get_active_provider($bot);
			$model_id = $this->get_model_id($provider, $models);

			if (empty($model_id)) {
				return $this->error_response(
					esc_html__('No AI computational model selected for processing.', 'dragwyb-click-to-chat'),
					400
				);
			}
		} catch (Exception $e) {
			self::log_debug('Dragwyb AI AI Chat Active Provider/Model Error: ' . $e->getMessage());
			$is_admin = current_user_can('manage_options');
			if ($is_admin) {
				$settings_url = admin_url('admin.php?page=dragwyb-click-to-chat-ai');
				$error_message = sprintf(
					/* translators: %s: AI Assistant settings URL */
					__('AI Provider API key is not configured. Please [configure your AI Provider API key](%s) in settings.', 'dragwyb-click-to-chat'),
					esc_url($settings_url)
				);
			} else {
				$error_message = esc_html__('AI Assistant is currently offline for maintenance. Please check back later.', 'dragwyb-click-to-chat');
			}
			return $this->error_response($error_message, 400);
		}
		try {
			$system_message = $this->build_system_prompt($bot, $settings);
		} catch (Exception $e) {
			self::log_debug('Dragwyb AI AI Chat System Prompt Error: ' . $e->getMessage());
			$error_message = current_user_can('manage_options') ? $e->getMessage() : esc_html__('An error occurred while processing your request.', 'dragwyb-click-to-chat');
			return $this->error_response($error_message, 500);
		}
		try {
			$rag_data = $this->rag_controller->get_chat_context($prompt, $session_id, $bot, $settings);

			if ($rag_data['require_data_missing']) {
				return $this->save_and_respond(
					$rag_data['message'],
					$session_id,
					$bot,
					$email,
					$prompt,
					!empty($rag_data['action_buttons']) ? $rag_data['action_buttons'] : []
				);
			}

			if (!empty($rag_data['context'])) {
				$system_message .= "\n\nCRITICAL INSTRUCTION: Answer the user's question concisely based ONLY on the facts provided in the 'Knowledge Base Information' below. Do not hallucinate, over-explain, or add external general knowledge that is not explicitly stated in the context. If the user asks for customizations, code tweaks, or topics not covered in our data, guide them to contact our support team.\n\nKnowledge Base Information:\n" . $rag_data['context'];
			}

			$rag_links = $rag_data['links'];
		} catch (Exception $e) {
			self::log_debug('Dragwyb AI AI RAG Link Fetch Error: ' . $e->getMessage());
			$rag_links = [];
		}

		try {
			$mcp_context = $this->mcp_controller->get_chat_context($prompt, $settings);
			if (!empty($mcp_context)) {
				$system_message .= "\n\nCustom Data:\n" . $mcp_context;
			}
		} catch (Exception $e) {
			self::log_debug('Dragwyb AI AI MCP Context Fetch Error: ' . $e->getMessage());
		}

		try {
			if (class_exists('DCTC_AI_WooCommerce')) {
				$wc_context = DCTC_AI_WooCommerce::build_llm_product_context($prompt);
				if (!empty($wc_context)) {
					$system_message .= $wc_context;
				}
			}
		} catch (Exception $e) {
			self::log_debug('Dragwyb AI WooCommerce Context Error: ' . $e->getMessage());
		}

		// Feature 13: Page-Aware Context Injection
		try {
			$page_context = $request->get_param('page_context');
			if (!empty($page_context) && is_array($page_context) && !empty($bot['enable_page_context'])) {
				$context_str = "\n\nCURRENT VISITED PAGE CONTEXT:\n";
				if (!empty($page_context['url'])) {
					$context_str .= "- Page URL: " . esc_url_raw($page_context['url']) . "\n";
				}
				if (!empty($page_context['title'])) {
					$context_str .= "- Page Title: " . sanitize_text_field($page_context['title']) . "\n";
				}
				if (!empty($page_context['post_type'])) {
					$context_str .= "- Page Type: " . sanitize_text_field($page_context['post_type']) . "\n";
				}
				if (!empty($page_context['product']) && is_array($page_context['product'])) {
					$p = $page_context['product'];
					$currency = sanitize_text_field($p['currency'] ?? '$');
					$price = sanitize_text_field($p['price'] ?? '');
					$stock = !empty($p['in_stock']) ? 'In Stock' : 'Out of Stock';
					$sku = !empty($p['sku']) ? ", SKU: " . sanitize_text_field($p['sku']) : "";
					$context_str .= "- Product Currently Viewed: " . sanitize_text_field($p['name'] ?? '') . " (Price: {$currency}{$price}, Stock: {$stock}{$sku})\n";
				}
				$context_str .= "When the user asks questions referring to 'this page', 'this product', 'how much is this', or 'what is this', use the above page context directly.\n";
				$system_message .= $context_str;
			}
		} catch (Exception $e) {
			self::log_debug('Dragwyb AI Page Context Error: ' . $e->getMessage());
		}

		// Feature 14: Multilingual Prompt Handling
		try {
			if (!isset($bot['enable_multilingual']) || (bool) $bot['enable_multilingual']) {
				$pref_lang = !empty($bot['preferred_language']) ? $bot['preferred_language'] : 'auto';
				$visitor_lang = sanitize_text_field($params['visitor_lang'] ?? '');
				if ($pref_lang === 'auto') {
					$system_message .= "\n\nMULTILINGUAL INSTRUCTION: Automatically detect the language of the user's message (e.g., English, Spanish, French, German, Italian, Portuguese, Hindi, Arabic, Chinese, Japanese, etc.) and reply fluently, naturally, and accurately in that EXACT same language, unless the user explicitly requests another language.";
					if (!empty($visitor_lang)) {
						$system_message .= " The visitor's browser/locale language is detected as: {$visitor_lang}.";
					}
				} else {
					$system_message .= "\n\nMULTILINGUAL INSTRUCTION: The primary language configured is '{$pref_lang}'. Always respond in '{$pref_lang}' or the language chosen by the visitor.";
				}
			}
		} catch (Exception $e) {
			self::log_debug('Dragwyb AI Multilingual Prompt Error: ' . $e->getMessage());
		}

		try {
			$memory = $this->get_optimized_memory($session_id, $prompt, $system_message);
			$system_message = $memory['system_message'];
		} catch (Exception $e) {
			self::log_debug('Dragwyb AI AI Memory Optimization Error: ' . $e->getMessage());
		}

		/**
		 * Filters the final compiled system prompt before sending to AI provider.
		 *
		 * @param string $system_message
		 * @param string $session_id
		 * @param array  $bot
		 */
		$system_message = apply_filters('dctc_ai_chat_system_prompt', $system_message, $session_id, $bot);

		try {
			$ai_result = $this->call_ai_api(
				$prompt,
				$system_message,
				$provider,
				$model_id,
				$bot,
				$models,
				$attachments
			);

			$ai_message = isset($ai_result['message']) ? $ai_result['message'] : '';
			$used_provider = isset($ai_result['provider']) ? $ai_result['provider'] : $provider;
			$used_model = isset($ai_result['model']) ? $ai_result['model'] : $model_id;

			/**
			 * Filters the raw AI assistant response text.
			 *
			 * @param string $ai_message
			 * @param string $prompt
			 * @param string $session_id
			 */
			$ai_message = apply_filters('dctc_ai_chat_response', $ai_message, $prompt, $session_id);

			if (empty($ai_message)) {
				if (class_exists('DCTC_Error_Logger')) {
					DCTC_Error_Logger::log_ai_error(
						$provider,
						$model_id,
						$prompt,
						__('AI connection returned an empty response.', 'dragwyb-click-to-chat'),
						[
							'type' => 'Empty Response',
							'context' => 'Chat API Response',
						]
					);
				}
				return $this->error_response(
					esc_html__('AI connection returned an empty response.', 'dragwyb-click-to-chat'),
					500
				);
			}
		} catch (\Throwable $e) {
			if (class_exists('DCTC_Error_Logger')) {
				DCTC_Error_Logger::log_ai_error(
					$provider,
					$model_id,
					$prompt,
					$e->getMessage(),
					[
						'type' => 'Model Error',
						'code' => (string) $e->getCode(),
						'context' => 'Chat Completion API',
					]
				);
			}
			self::log_debug('Dragwyb AI AI Chat API/Processing Error: ' . $e->getMessage());
			$error_message = current_user_can('manage_options') ? $e->getMessage() : esc_html__('An error occurred while processing your request.', 'dragwyb-click-to-chat');
			return $this->error_response($error_message, 500);
		}

		// Race Condition Guard: If an agent took control while LLM API was running, discard the AI output
		if (class_exists('DCTC_Support_AI_Handoff_Service') && DCTC_Support_AI_Handoff_Service::should_block_ai_response($session_id)) {
			$human_response = DCTC_Support_AI_Handoff_Service::handle_customer_message_in_human_mode($session_id, $prompt, $email);
			return new \WP_REST_Response($human_response, 200);
		}

		$show_sources = ! isset( $bot['show_sources'] ) || (bool) $bot['show_sources'];
		$sources = ( $show_sources && ! empty( $rag_links ) ) ? array_slice( $rag_links, 0, 3 ) : [];

		try {
			$this->save_conversation($prompt, $ai_message, $session_id, $used_provider, $used_model, $bot, $email, $sources);
		} catch (Exception $e) {
			self::log_debug('Dragwyb AI AI Save Conversation Error: ' . $e->getMessage());
		}

		try {
			if (class_exists('DCTC_AI_Usage_Tracker')) {
				$est_tokens = max(10, intval((strlen($prompt) + strlen($ai_message)) / 4));
				DCTC_AI_Usage_Tracker::record_usage($session_id, $used_provider, $used_model, $est_tokens);
			}
		} catch (Exception $e) {
			self::log_debug('Dragwyb AI AI Record Usage Error: ' . $e->getMessage());
		}

		try {
			$formatted_messages = $this->get_formatted_messages($session_id);
		} catch (Exception $e) {
			self::log_debug('Dragwyb AI AI Get Formatted Messages Error: ' . $e->getMessage());
			$formatted_messages = [];
		}

		$wc_products = [];
		if (class_exists('DCTC_AI_WooCommerce') && DCTC_AI_WooCommerce::is_active()) {
			if (preg_match('/\b(product|products|buy|purchase|price|cost|recommend|shop|shoes|shirt|item|items|store|catalog)\b/i', $prompt)) {
				$wc_products = DCTC_AI_WooCommerce::search_products($prompt, 3);
				if (empty($wc_products)) {
					$wc_products = DCTC_AI_WooCommerce::get_recommendations('popular', 3);
				}
			}
		}

		/**
		 * Action triggered whenever a chat round completes successfully.
		 *
		 * @param string $session_id
		 * @param string $prompt
		 * @param string $ai_message
		 * @param string $used_model
		 * @param string $used_provider
		 */
		do_action('dctc_ai_chat_completed', $session_id, $prompt, $ai_message, $used_model, $used_provider);

		return new \WP_REST_Response(
			[
				'success' => true,
				'message' => $ai_message,
				'session_id' => $session_id,
				'messages' => $formatted_messages,
				'sources' => $sources,
				'reference_links' => $sources,
				'products' => $wc_products,
			],
			200
		);
	}

	/**
	 * REST callback: return all chat sessions for the admin dashboard.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return \WP_REST_Response
	 */
	public function get_sessions($request)
	{
		$user_id = get_current_user_id();

		// Handle user-specific loading limit.
		$limit = $request->get_param('limit');
		if (!is_null($limit)) {
			$limit = sanitize_text_field($limit);
			update_user_meta($user_id, 'dctc_ai_sessions_load_limit', $limit);
		} else {
			$limit = get_user_meta($user_id, 'dctc_ai_sessions_load_limit', true);
			if (empty($limit)) {
				$limit = '100'; // Default limit.
			}
		}

		// Handle user-specific sorting order.
		$order = $request->get_param('order');
		if (!is_null($order)) {
			$order = sanitize_text_field($order);
			update_user_meta($user_id, 'dctc_ai_sessions_sort_order', $order);
		} else {
			$order = get_user_meta($user_id, 'dctc_ai_sessions_sort_order', true);
			if (empty($order)) {
				$order = 'desc'; // Default order.
			}
		}

		$sessions = DCTC_AI_DB::dctc_ai_get_all_sessions($limit, $order);

		return new \WP_REST_Response(
			[
				'sessions' => $sessions,
				'total' => count($sessions),
				'load_limit' => $limit,
				'sort_order' => $order,
			],
			200
		);
	}

	/**
	 * REST callback: delete a chat session from the database.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function delete_session($request)
	{
		$session_id = sanitize_text_field($request->get_param('session_id'));

		if (empty($session_id)) {
			return new \WP_Error(
				'dctc_ai_invalid_session',
				__('Session ID is required.', 'dragwyb-click-to-chat'),
				['status' => 400]
			);
		}

		if (!DCTC_AI_DB::dctc_ai_delete_session($session_id)) {
			return new \WP_Error(
				'dctc_ai_delete_failed',
				__('Session could not be deleted.', 'dragwyb-click-to-chat'),
				['status' => 404]
			);
		}

		return new \WP_REST_Response(['success' => true], 200);
	}

	/**
	 * REST callback: clear session and reset cookies.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return \WP_REST_Response
	 */
	public function clear_session($request)
	{
		$new_session_id = 'sess_' . wp_generate_password(9, false);

		setcookie('dctc_ai_session_id', $new_session_id, time() + 3600, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true);
		setcookie('dctc_ai_clear_allowed', 'true', time() + 1800, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true);

		return new \WP_REST_Response(
			[
				'success' => true,
				'session_id' => $new_session_id,
			],
			200
		);
	}

	/**
	 * REST callback: Generate or retrieve an AI executive summary for a session.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function summarize_session($request)
	{
		$session_id = sanitize_text_field($request->get_param('session_id'));
		if (empty($session_id)) {
			return $this->error_response(__('Session ID is required.', 'dragwyb-click-to-chat'), 400);
		}

		global $wpdb;
		$table = $wpdb->prefix . 'dctc_ai_sessions';
		$session = $wpdb->get_row(
			$wpdb->prepare("SELECT * FROM {$table} WHERE session_id = %s", $session_id),
			ARRAY_A
		);

		if (!$session) {
			return $this->error_response(__('Session not found.', 'dragwyb-click-to-chat'), 404);
		}

		$messages_raw = json_decode($session['content'] ?? '[]', true);
		if (!is_array($messages_raw) || empty($messages_raw)) {
			return $this->error_response(__('Conversation has no messages to summarize.', 'dragwyb-click-to-chat'), 400);
		}

		// Prepare conversation transcript for AI analysis
		$transcript_lines = [];
		foreach ($messages_raw as $m) {
			$role = ($m['role'] ?? 'user') === 'user' ? 'Visitor' : 'Assistant';
			$text = wp_strip_all_tags($m['content'] ?? '');
			if (!empty($text)) {
				$transcript_lines[] = "{$role}: {$text}";
			}
		}
		$transcript = implode("\n", array_slice($transcript_lines, -30));

		$system_prompt = "You are an executive conversation analyst. Analyze the provided customer chat transcript and output ONLY a valid JSON object without markdown formatting or backticks:
{
  \"goal\": \"1-2 sentence summary of what the customer wanted\",
  \"questions\": [\"key question 1\", \"key question 2\"],
  \"topics\": [\"topic or product 1\", \"topic 2\"],
  \"sentiment\": \"positive\" or \"neutral\" or \"frustrated\",
  \"intent_tag\": \"inquiry\" or \"support\" or \"purchase\" or \"feedback\",
  \"next_action\": \"recommended followup or next step\"
}";

		$user_prompt = "Transcript:\n" . $transcript;

		$settings = DCTC_AI_Settings_Handler::dctc_ai_get_all_settings();
		$bot = isset($settings['chatbot']) ? $settings['chatbot'] : [];
		$models = isset($settings['models']) ? $settings['models'] : [];
		$provider = !empty($bot['default_provider']) ? $bot['default_provider'] : 'openai';
		$model_id = !empty($models[$provider]) ? $models[$provider] : '';

		$summary_data = null;

		try {
			$ai_res = $this->call_ai_api($user_prompt, $system_prompt, $provider, $model_id, $bot, $models);
			$raw_text = trim($ai_res['message'] ?? '');
			$raw_text = preg_replace('/^```(?:json)?\s*/i', '', $raw_text);
			$raw_text = preg_replace('/\s*```$/', '', $raw_text);
			$parsed = json_decode($raw_text, true);

			if (is_array($parsed) && !empty($parsed['goal'])) {
				$summary_data = [
					'goal'        => sanitize_text_field($parsed['goal']),
					'questions'   => array_map('sanitize_text_field', (array) ($parsed['questions'] ?? [])),
					'topics'      => array_map('sanitize_text_field', (array) ($parsed['topics'] ?? [])),
					'sentiment'   => in_array($parsed['sentiment'] ?? '', ['positive', 'neutral', 'frustrated'], true) ? $parsed['sentiment'] : 'neutral',
					'intent_tag'  => in_array($parsed['intent_tag'] ?? '', ['inquiry', 'support', 'purchase', 'feedback'], true) ? $parsed['intent_tag'] : 'general',
					'next_action' => sanitize_text_field($parsed['next_action'] ?? ''),
				];
			}
		} catch (\Throwable $e) {
			self::log_debug('Session summarize AI error: ' . $e->getMessage());
		}

		// Fallback heuristic if AI call fails or is unavailable
		if (!$summary_data) {
			$first_user_msg = '';
			foreach ($messages_raw as $m) {
				if (($m['role'] ?? '') === 'user') {
					$first_user_msg = wp_strip_all_tags($m['content'] ?? '');
					break;
				}
			}
			$summary_data = [
				'goal'        => !empty($first_user_msg) ? sprintf(__('Customer inquired about: %s', 'dragwyb-click-to-chat'), substr($first_user_msg, 0, 100)) : __('General conversation with AI Assistant', 'dragwyb-click-to-chat'),
				'questions'   => [!empty($first_user_msg) ? substr($first_user_msg, 0, 100) : __('General inquiry', 'dragwyb-click-to-chat')],
				'topics'      => [__('General', 'dragwyb-click-to-chat')],
				'sentiment'   => 'neutral',
				'intent_tag'  => 'inquiry',
				'next_action' => __('Review full conversation log', 'dragwyb-click-to-chat'),
			];
		}

		// Save in database
		DCTC_AI_DB::update_session_summary(
			$session_id,
			wp_json_encode($summary_data),
			$summary_data['sentiment'],
			$summary_data['intent_tag']
		);

		return new \WP_REST_Response([
			'success'    => true,
			'session_id' => $session_id,
			'summary'    => $summary_data,
		], 200);
	}

	/**
	 * REST callback: Return conversation analytics.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function get_analytics($request)
	{
		$analytics = DCTC_AI_DB::get_conversation_analytics();
		return new \WP_REST_Response([
			'success'   => true,
			'analytics' => $analytics,
		], 200);
	}

	/**
	 * Get Client IP
	 *
	 * Best-effort caller IP for rate limiting. Only REMOTE_ADDR is trusted;
	 * headers like X-Forwarded-For are attacker-controlled unless a proxy
	 * is explicitly configured to set them, so they're not used here.
	 *
	 * @return string
	 */
	private function get_client_ip()
	{
		return isset($_SERVER['REMOTE_ADDR'])
			? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']))
			: '';
	}

	/**
	 * Is Chat Rate Limited
	 *
	 * permission_check() accepts any valid "wp_rest" nonce, which
	 * WordPress issues identically to every anonymous visitor and is
	 * readable in every page's HTML source — it does not identify a
	 * caller. Throttle by IP instead, so one client can't run up the
	 * site owner's AI provider costs by hammering the endpoint.
	 *
	 * @return bool True if the current caller has exceeded the limit.
	 */
	private function is_rate_limited()
	{
		$ip = $this->get_client_ip();

		// Fail closed: without a usable IP we cannot throttle fairly.
		if (empty($ip)) {
			return true;
		}

		$settings = DCTC_AI_Settings_Handler::dctc_ai_get_all_settings();
		$limit = isset($settings['chatbot']['rate_limit_per_minute'])
			? intval($settings['chatbot']['rate_limit_per_minute'])
			: 20;

		if ($limit <= 0) {
			return false;
		}

		$key = 'dctc_ai_chat_rl_' . md5($ip);
		$count = (int) get_transient($key);

		if ($count >= $limit) {
			return true;
		}

		set_transient($key, $count + 1, MINUTE_IN_SECONDS);

		return false;
	}

	/**
	 * Get active AI provider
	 */
	private function get_active_provider($bot)
	{
		$available_providers = [];
		$supported = DCTC_AI_Key_Store::get_supported_providers();

		foreach ($supported as $provider) {
			if (!empty(DCTC_AI_Key_Store::get_provider_key($provider))) {
				$available_providers[] = $provider;
			}
		}

		if (count($available_providers) === 0) {
			throw new Exception(esc_html__('No AI provider API keys configured.', 'dragwyb-click-to-chat'));
		}

		if (count($available_providers) === 1) {
			return $available_providers[0];
		}

		// Multiple providers available - use default or first available
		$default_provider = !empty($bot['default_provider']) ? $bot['default_provider'] : 'openai';

		return in_array($default_provider, $available_providers, true) ? $default_provider : $available_providers[0];
	}

	/**
	 * Get model ID for provider
	 */
	private function get_model_id($provider, $models)
	{
		$model_id = isset($models[$provider]) ? $models[$provider] : '';

		if (!empty($model_id)) {
			return $model_id;
		}

		// Fallback to default models
		$defaults = [
			'openai'     => 'gpt-4o-mini',
			'google'     => 'gemini-3.5-flash-lite',
			'anthropic'  => 'claude-3-5-sonnet-20241022',
			'openrouter' => 'anthropic/claude-3.5-sonnet',
			'groq'       => 'llama-3.3-70b-versatile',
			'deepseek'   => 'deepseek-chat',
		];

		return isset($defaults[$provider]) ? $defaults[$provider] : '';
	}

	/**
	 * Build comprehensive system prompt with strict domain grounding and custom support referral.
	 */
	private function build_system_prompt($bot, $settings)
	{
		$site_name   = get_bloginfo('name');
		$support_url = !empty($bot['support_url']) ? esc_url_raw($bot['support_url']) : home_url();
		$bot_name    = !empty($bot['bot_name']) ? sanitize_text_field($bot['bot_name']) : 'AI Assistant';

		$system_message = '';

		// Admin custom prompt if set
		if (!empty($bot['system_prompt'])) {
			$system_message .= trim(wp_kses_post($bot['system_prompt'])) . "\n\n";
		}

		// Manual knowledge base if set
		if (!empty($bot['knowledge_text'])) {
			$system_message .= "KNOWLEDGE BASE:\n" . wp_kses_post($bot['knowledge_text']) . "\n\n";
		}

		$system_message .= "
CORE IDENTITY & DOMAIN RESTRICTIONS:
- You are the official, specialized AI Assistant ({$bot_name}) representing {$site_name}.
- Your primary purpose is to assist visitors with information regarding {$site_name}, including our products, services, store catalog, order tracking, policies, documentation, and customer support.
- You are NOT a generic open-ended AI (like raw ChatGPT or Gemini). You must NEVER generate generic programming tutorials, general code solutions (e.g. how to style unrelated HTML/CSS, generic JavaScript, generic Python), homework answers, or off-topic general knowledge.

OFF-TOPIC, UNRELATED, OR CUSTOMIZATION REQUESTS:
- If a user asks a question that is outside the scope of {$site_name}'s official products, documentation, and knowledge base (such as generic CSS/design modifications, custom coding, external tutorials, or unrelated topics):
  1. Do NOT generate generic web tutorials or open-ended external code.
  2. Politely and professionally inform the user that you are the dedicated assistant for {$site_name} and specialize in our official products, features, and documentation.
  3. If they need custom development, specialized CSS styling, or custom assistance, provide a helpful and warm response encouraging them to reach out directly to our human support team: [Contact Support]({$support_url}) or submit a request on our Support page so our specialists can assist them with custom requirements.

ACCURACY & KNOWLEDGE BASE GROUNDING:
- Answer based strictly on the provided Knowledge Base, Products, and Page context.
- Never invent facts, prices, policies, or technical claims.
- If information is not in our data, acknowledge it honestly and direct the user to our support team.
- Never say robotic phrases like 'Based on the context provided' or 'According to the knowledge base'—speak naturally as {$site_name}'s representative.

LANGUAGE & TONE:
- Always respond in the same language used by the user.
- Keep responses professional, warm, concise, and beautifully formatted with clear headings or bullet points when appropriate.

CONVERSATION MEMORY:
- Use conversation history to resolve pronouns and follow-up requests ('more details', 'tell me more', 'why', 'how', 'continue') seamlessly.
";

		return trim($system_message);
	}

	/**
	 * Get optimized memory and conversation history
	 */
	private function get_optimized_memory($session_id, $prompt, $system_message)
	{
		try {

			$rag_context = '';

			if (!class_exists('DCTC_AI_Memory_Optimizer')) {
				require_once DCTC_PLUGIN_DIR . 'includes/ai/class-dctc-ai-memory-optimizer.php';
			}

			$memory_optimizer = DCTC_AI_Memory_Optimizer::get_instance();

			$optimized_memory = $memory_optimizer->build_optimized_context(
				$session_id,
				$prompt,
				$rag_context
			);

			$memory_text = $memory_optimizer->format_for_prompt(
				$optimized_memory
			);

			if (!empty($memory_text)) {
				$system_message .= "\n\nMEMORY CONTEXT:\n";
				$system_message .= $memory_text;
			}

			/*
			 * Add recent conversation
			 */
			$conversation_history = $this->get_recent_conversation(
				$session_id,
				10
			);

			if (!empty($conversation_history)) {

				$system_message .= "\n\nCONVERSATION HISTORY:\n";
				$system_message .= $conversation_history;

				$system_message .= "\n\nFOLLOW-UP RULES:
- If user asks 'why', 'how', 'who', 'when', 'where', 'which', 'what about', 'tell me more', 'continue', 'can you explain', assume they are referring to the previous topic.
- Resolve pronouns such as 'it', 'that', 'this', 'they' using the conversation history.
- Never ignore previous messages in the same session.
";
			}

		} catch (Exception $e) {
			self::log_debug('Dragwyb AI AI Memory Build Context Error: ' . $e->getMessage());
		}

		return [
			'system_message' => $system_message,
		];
	}

	/**
	 * Call AI API with automatic failover support
	 */
	private function call_ai_api($prompt, $system_message, $provider, $model_id, $bot, $models = [], $attachments = [])
	{
		$options = [
			'temperature' => isset($bot['temperature']) ? (float) $bot['temperature'] : 0.7,
			'max_tokens'  => isset($bot['max_tokens']) ? (int) $bot['max_tokens'] : 500,
			'attachments' => $attachments,
		];

		$enable_failover = !isset($bot['enable_failover']) || (bool) $bot['enable_failover'];
		$fallback_provider = '';
		$fallback_model = '';

		if ($enable_failover) {
			$fallback_provider = !empty($bot['fallback_provider']) ? $bot['fallback_provider'] : '';
			$fallback_model    = !empty($bot['fallback_model']) ? $bot['fallback_model'] : (isset($models[$fallback_provider]) ? $models[$fallback_provider] : '');
		}

		return DCTC_AI_Provider_Manager::get_instance()->chat_with_fallback(
			$prompt,
			$system_message,
			$provider,
			$model_id,
			$options,
			$fallback_provider,
			$fallback_model
		);
	}

	/**
	 * Save conversation to database
	 */
	private function save_conversation($prompt, $ai_message, $session_id, $provider, $model_id, $bot, $email, $sources = [])
	{
		// Save to database if enabled
		if (isset($bot['save_chat']) && (bool) $bot['save_chat']) {
			if (class_exists('DCTC_AI_DB')) {
				$db = new DCTC_AI_DB();
				$db->dctc_ai_save_message($prompt, $ai_message, $session_id, $provider, $model_id, $email, $sources);
			}
		}

	}

	/**
	 * Get formatted messages from database
	 */
	private function get_formatted_messages($session_id)
	{
		$formatted_messages = [];

		if (!class_exists('DCTC_AI_DB')) {
			return $formatted_messages;
		}

		try {
			global $wpdb;

			// Use prepared statement properly
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Safe table prefix, direct query required.
			$existing_messages = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT content FROM {$wpdb->prefix}dctc_ai_sessions WHERE session_id = %s",
					$session_id
				)
			);

			if ($existing_messages) {
				$complete_messages = json_decode($existing_messages, true);

				if (is_array($complete_messages)) {
					foreach ($complete_messages as $msg) {
						if (isset($msg['role']) && isset($msg['content'])) {
							$formatted_messages[] = [
								'role' => ($msg['role'] === 'assistant') ? 'bot' : 'user',
								'content' => $msg['content'],
								'sources' => isset($msg['sources']) && is_array($msg['sources']) ? $msg['sources'] : [],
							];
						}
					}
				}
			}
		} catch (Exception $e) {
			self::log_debug('Dragwyb AI AI DB Messages Retrieval Error: ' . $e->getMessage());
		}

		return $formatted_messages;
	}

	/**
	 * Detect if visitor prompt expresses intent to speak with a human agent.
	/**
	 * Classify user intent: 'human_handoff', 'support_ticket', 'lead_generation', or 'general_qa'.
	 *
	 * @param string $prompt User message.
	 * @return array<string, mixed>
	 */
	public static function classify_user_intent($prompt)
	{
		if (empty($prompt)) {
			return ['intent' => 'general_qa', 'category' => 'general', 'confidence' => 0.0];
		}

		$patterns_handoff = [
			'/\b(human|real person|live agent|support agent|human agent|representative|talk to someone|talk to a human|talk to an agent|connect with human|customer care|customer support|operator|live chat with human)\b/i',
			'/\b(call me|call support|phone support|speak with someone|speak to someone|speak with an agent|whatsapp support|chat on whatsapp|escalate to manager|transfer me)\b/i',
		];

		foreach ($patterns_handoff as $pattern) {
			if (preg_match($pattern, $prompt)) {
				return ['intent' => 'human_handoff', 'category' => 'support', 'confidence' => 0.95];
			}
		}

		$patterns_support = [
			'/\b(broken|damaged|defective|faulty|warranty|refund|return item|cancel order|billing issue|chargeback|not working|error code|bug in|failed transaction|invoice incorrect)\b/i',
			'/\b(ticket number|my ticket|open a ticket|file a claim|technical support|troubleshoot problem)\b/i',
		];

		foreach ($patterns_support as $pattern) {
			if (preg_match($pattern, $prompt)) {
				return ['intent' => 'support_ticket', 'category' => 'troubleshooting', 'confidence' => 0.88];
			}
		}

		$patterns_lead = [
			'/\b(custom quote|request quote|price estimate|bulk pricing|enterprise plan|schedule demo|book a call|partnership inquiry|hire you|contact sales)\b/i',
		];

		foreach ($patterns_lead as $pattern) {
			if (preg_match($pattern, $prompt)) {
				return ['intent' => 'lead_generation', 'category' => 'sales', 'confidence' => 0.85];
			}
		}

		$patterns_order = [
			'/\b(track order|track my order|tracking order|order status|where is my order|check my order|check order status|find my order|order tracking|track shipment|tracking number|order update|track product|track my product|delivery status|package tracking|track my package|shipping status)\b/i',
		];

		foreach ($patterns_order as $pattern) {
			if (preg_match($pattern, $prompt)) {
				return ['intent' => 'order_tracking', 'category' => 'order', 'confidence' => 0.92];
			}
		}

		return ['intent' => 'general_qa', 'category' => 'general', 'confidence' => 0.5];
	}

	/**
	 * Detect if the message is requesting WooCommerce order tracking or status lookup.
	 *
	 * @param string $prompt
	 * @return bool
	 */
	public static function detect_order_tracking_intent($prompt)
	{
		$classification = self::classify_user_intent($prompt);
		return 'order_tracking' === $classification['intent'];
	}

	/**
	 * Detect if the message indicates a human handoff / escalation or lead request.
	 *
	 * @param string $prompt
	 * @return bool
	 */
	public static function detect_human_handoff_intent($prompt)
	{
		$classification = self::classify_user_intent($prompt);
		return in_array($classification['intent'], ['human_handoff', 'support_ticket', 'lead_generation'], true);
	}

	/**
	 * Check if current time falls within configured business hours.
	 *
	 * @param array $bot_settings
	 * @return bool
	 */
	public static function is_within_business_hours($bot_settings)
	{
		if (empty($bot_settings['enable_business_hours'])) {
			return true;
		}

		try {
			$tz_str = !empty($bot_settings['business_hours_timezone']) ? $bot_settings['business_hours_timezone'] : (function_exists('wp_timezone_string') ? wp_timezone_string() : 'UTC');
			$tz = new DateTimeZone($tz_str ?: 'UTC');
			$now = new DateTime('now', $tz);

			$day_map = [
				1 => 'mon',
				2 => 'tue',
				3 => 'wed',
				4 => 'thu',
				5 => 'fri',
				6 => 'sat',
				7 => 'sun',
			];
			$current_day_num = (int) $now->format('N');
			$current_day_slug = $day_map[$current_day_num] ?? 'mon';

			$allowed_days = !empty($bot_settings['business_hours_days']) && is_array($bot_settings['business_hours_days'])
				? $bot_settings['business_hours_days']
				: ['mon', 'tue', 'wed', 'thu', 'fri'];

			if (!in_array($current_day_slug, $allowed_days, true)) {
				return false;
			}

			$start_str = !empty($bot_settings['business_hours_start']) ? $bot_settings['business_hours_start'] : '09:00';
			$end_str = !empty($bot_settings['business_hours_end']) ? $bot_settings['business_hours_end'] : '18:00';

			$current_time = $now->format('H:i');
			return ($current_time >= $start_str && $current_time <= $end_str);
		} catch (\Throwable $e) {
			return true;
		}
	}

	/**
	 * Build human handoff action buttons with contextual WhatsApp link.
	 *
	 * @param array  $bot
	 * @param string $prompt
	 * @param bool   $is_online
	 * @return array
	 */
	public static function build_handoff_action_buttons($bot, $prompt, $is_online = true)
	{
		$action_buttons = [];
		$parent_settings = get_option('dctc_settings', []);

		$wa_num = !empty($bot['handoff_whatsapp_number']) ? $bot['handoff_whatsapp_number'] : (!empty($parent_settings['whatsapp_value']) ? $parent_settings['whatsapp_value'] : '');
		$phone_num = !empty($bot['handoff_phone_number']) ? $bot['handoff_phone_number'] : (!empty($parent_settings['phone_value']) ? $parent_settings['phone_value'] : '');
		$email_addr = !empty($bot['handoff_email_address']) ? $bot['handoff_email_address'] : (!empty($parent_settings['email_value']) ? $parent_settings['email_value'] : get_option('admin_email'));

		$safe_prompt = substr(wp_strip_all_tags($prompt), 0, 150);

		if (!empty($wa_num)) {
			$clean_phone = preg_replace('/[^0-9]/', '', $wa_num);
			$template = !empty($bot['handoff_template'])
				? $bot['handoff_template']
				: "Hi! I was chatting with your AI assistant on {page_url} regarding: \"{summary}\". My question: \"{question}\".";

			$wa_msg = str_replace(
				['{question}', '{summary}', '{page_url}', '{visitor_name}'],
				[$safe_prompt, $safe_prompt, home_url(), 'Visitor'],
				$template
			);

			$action_buttons[] = [
				'id' => 'btn_wa_handoff',
				'label' => __('💬 Chat on WhatsApp', 'dragwyb-click-to-chat'),
				'url' => 'https://wa.me/' . $clean_phone . '?text=' . rawurlencode($wa_msg),
				'target' => '_blank',
				'type' => 'whatsapp',
			];
		}

		if (!empty($phone_num) && $is_online) {
			$action_buttons[] = [
				'id' => 'btn_phone_handoff',
				'label' => __('📞 Call Human Agent', 'dragwyb-click-to-chat'),
				'url' => 'tel:' . preg_replace('/[^0-9+]/', '', $phone_num),
				'target' => '_self',
				'type' => 'phone',
			];
		}

		if (!empty($email_addr)) {
			$action_buttons[] = [
				'id' => 'btn_email_handoff',
				'label' => __('✉️ Email Support Team', 'dragwyb-click-to-chat'),
				'url' => 'mailto:' . antispambot($email_addr) . '?subject=' . rawurlencode(__('Customer Inquiry from AI Chat', 'dragwyb-click-to-chat')) . '&body=' . rawurlencode($safe_prompt),
				'target' => '_blank',
				'type' => 'email',
			];
		}

		return $action_buttons;
	}

	/**
	 * Save response and return
	 */
	private function save_and_respond($message, $session_id, $bot, $email, $prompt, $action_buttons = [], $is_handoff = false)
	{
		if (isset($bot['save_chat']) && (bool) $bot['save_chat']) {
			if (class_exists('DCTC_AI_DB')) {
				$db = new DCTC_AI_DB();
				$source_type = $is_handoff ? 'human-handoff' : 'knowledge-base';
				$model_used = $is_handoff ? 'handoff' : 'no-data';
				$db->dctc_ai_save_message($prompt, $message, $session_id, $source_type, $model_used, $email);
			}
		}

		return new \WP_REST_Response(
			[
				'success' => true,
				'message' => $message,
				'session_id' => $session_id,
				'from_kb' => false,
				'is_handoff' => $is_handoff,
				'action_buttons' => $action_buttons,
			],
			200
		);
	}

	/**
	 * Get recent conversation history.
	 *
	 * @param string $session_id Session ID.
	 * @param int    $limit Number of messages.
	 * @return string
	 */
	private function get_recent_conversation($session_id, $limit = 10)
	{
		global $wpdb;

		if (empty($session_id)) {
			return '';
		}

		$settings = DCTC_AI_Settings_Handler::dctc_ai_get_all_settings();
		if (isset($settings['chatbot']['memory_window_size']) && intval($settings['chatbot']['memory_window_size']) > 0) {
			$limit = min(50, max(2, intval($settings['chatbot']['memory_window_size'])));
		}

		$table = esc_sql( $wpdb->prefix . 'dctc_ai_sessions' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct database query on custom table.
		$messages_json = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT content FROM {$table} WHERE session_id = %s ORDER BY id DESC LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is dynamic but safe.
				$session_id
			)
		);

		if (empty($messages_json)) {
			return '';
		}

		$messages = json_decode($messages_json, true);

		if (!is_array($messages)) {
			return '';
		}

		$messages = array_slice(
			$messages,
			-$limit
		);

		$history = '';

		foreach ($messages as $message) {

			if (empty($message['content'])) {
				continue;
			}

			$role = (
				isset($message['role']) &&
				$message['role'] === 'assistant'
			)
				? 'Assistant'
				: 'User';

			$history .= sprintf(
				"%s: %s\n",
				$role,
				trim($message['content'])
			);
		}

		return trim($history);
	}

	/**
	 * Sync session state and messages for real-time live support.
	 *
	 * @param \WP_REST_Request $request The REST request.
	 * @return \WP_REST_Response
	 */
	public function sync_session($request)
	{
		global $wpdb;
		$session_id = sanitize_text_field($request->get_param('session_id'));
		if (empty($session_id)) {
			return new \WP_REST_Response(['success' => false, 'message' => 'session_id required'], 400);
		}

		$table_sessions = $wpdb->prefix . 'dctc_ai_sessions';
		$table_tickets  = $wpdb->prefix . 'dctc_support_tickets';
		$table_agents   = $wpdb->prefix . 'dctc_support_agents';

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$session = $wpdb->get_row($wpdb->prepare("SELECT * FROM `$table_sessions` WHERE session_id = %s", $session_id), ARRAY_A);

		if (!$session) {
			return new \WP_REST_Response([
				'success' => true,
				'session_id' => $session_id,
				'control_mode' => 'ai',
				'messages' => [],
				'has_ticket' => false,
			], 200);
		}

		$messages = !empty($session['content']) ? json_decode($session['content'], true) : [];
		$messages = is_array($messages) ? $messages : [];

		$control_mode = !empty($session['control_mode']) ? $session['control_mode'] : 'ai';
		$ticket_info = null;

		if (!empty($session['support_ticket_id'])) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$ticket = $wpdb->get_row($wpdb->prepare("SELECT * FROM `$table_tickets` WHERE id = %d", absint($session['support_ticket_id'])), ARRAY_A);
			if ($ticket) {
				$agent_name = '';
				if (!empty($ticket['assigned_agent_id'])) {
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$agent = $wpdb->get_row($wpdb->prepare("SELECT wp_user_id FROM `$table_agents` WHERE id = %d", absint($ticket['assigned_agent_id'])), ARRAY_A);
					if ($agent && !empty($agent['wp_user_id'])) {
						$user = get_userdata($agent['wp_user_id']);
						if ($user) {
							$agent_name = $user->display_name;
						}
					}
				}

				if (!empty($ticket['control_mode'])) {
					$control_mode = $ticket['control_mode'];
				}

				$ticket_info = [
					'id'            => (int) $ticket['id'],
					'ticket_number' => (int) $ticket['ticket_number'],
					'status'        => $ticket['status'],
					'control_mode'  => $control_mode,
					'agent_name'    => $agent_name,
				];
			}
		}

		return new \WP_REST_Response([
			'success'      => true,
			'session_id'   => $session_id,
			'control_mode' => $control_mode,
			'messages'     => $messages,
			'ticket'       => $ticket_info,
			'updated_at'   => $session['updated_at'] ?? current_time('mysql'),
		], 200);
	}
}
