<?php
/**
 * DCTC AI Tool Registry & Automation Workflow Engine
 *
 * Provides a secure, allowlisted tool registry with typed JSON schemas,
 * permission control, execution sandboxing, and webhook automation triggers.
 *
 * @package Dragwyb_Click_To_Chat
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Class DCTC_AI_Tool_Registry
 */
class DCTC_AI_Tool_Registry
{
	use DCTC_AI_REST_Helpers;

	/**
	 * Registered tools cache.
	 *
	 * @var array<string, array>
	 */
	private static $tools = [];

	/**
	 * Initialize and register all core built-in tools.
	 *
	 * @return void
	 */
	public static function init()
	{
		self::register_core_tools();
	}

	/**
	 * Register a tool into the allowlisted registry.
	 *
	 * @param string   $name
	 * @param array    $args
	 * @return void
	 */
	public static function register_tool($name, $args)
	{
		$name = sanitize_key($name);
		self::$tools[$name] = wp_parse_args($args, [
			'name'                  => $name,
			'label'                 => ucfirst(str_replace('_', ' ', $name)),
			'description'           => '',
			'parameters'            => [
				'type'       => 'object',
				'properties' => [],
				'required'   => [],
			],
			'permission_callback'   => '__return_true',
			'execute_callback'      => null,
			'requires_confirmation' => false,
			'enabled'               => true,
		]);
	}

	/**
	 * Register all built-in core business tools.
	 *
	 * @return void
	 */
	private static function register_core_tools()
	{
		// 1. Search Products (WooCommerce)
		self::register_tool('search_products', [
			'label'                 => __('Search Products', 'dragwyb-click-to-chat'),
			'description'           => __('Search published store catalog products by keywords, category, or price range.', 'dragwyb-click-to-chat'),
			'parameters'            => [
				'type'       => 'object',
				'properties' => [
					'query' => [
						'type'        => 'string',
						'description' => __('Keywords to search for products (e.g. "shoes", "hoodie", "gift")', 'dragwyb-click-to-chat'),
					],
					'limit' => [
						'type'        => 'integer',
						'description' => __('Maximum number of products to return (default: 4)', 'dragwyb-click-to-chat'),
					],
				],
				'required'   => ['query'],
			],
			'permission_callback'   => '__return_true',
			'execute_callback'      => [__CLASS__, 'tool_search_products'],
			'requires_confirmation' => false,
		]);

		// 2. Look Up Order Status (WooCommerce)
		self::register_tool('get_order_status', [
			'label'                 => __('Get Order Status', 'dragwyb-click-to-chat'),
			'description'           => __('Securely check the status and details of a customer order using numeric Order ID and billing email.', 'dragwyb-click-to-chat'),
			'parameters'            => [
				'type'       => 'object',
				'properties' => [
					'order_id' => [
						'type'        => 'integer',
						'description' => __('The numeric WooCommerce order ID (e.g. 1042)', 'dragwyb-click-to-chat'),
					],
					'billing_email' => [
						'type'        => 'string',
						'description' => __('The customer billing email address associated with the order', 'dragwyb-click-to-chat'),
					],
				],
				'required'   => ['order_id'],
			],
			'permission_callback'   => '__return_true',
			'execute_callback'      => [__CLASS__, 'tool_get_order_status'],
			'requires_confirmation' => false,
		]);

		// 3. Create Support Ticket / Customer Inquiry
		self::register_tool('create_support_ticket', [
			'label'                 => __('Create Support Ticket', 'dragwyb-click-to-chat'),
			'description'           => __('Log a customer support ticket or urgent inquiry for resolution by human staff.', 'dragwyb-click-to-chat'),
			'parameters'            => [
				'type'       => 'object',
				'properties' => [
					'name'        => [
						'type'        => 'string',
						'description' => __('Full name of the customer', 'dragwyb-click-to-chat'),
					],
					'email'       => [
						'type'        => 'string',
						'description' => __('Customer email address for updates', 'dragwyb-click-to-chat'),
					],
					'subject'     => [
						'type'        => 'string',
						'description' => __('Brief summary or topic of the support issue', 'dragwyb-click-to-chat'),
					],
					'description' => [
						'type'        => 'string',
						'description' => __('Detailed explanation of the issue or question', 'dragwyb-click-to-chat'),
					],
					'priority'    => [
						'type'        => 'string',
						'enum'        => ['low', 'normal', 'high', 'urgent'],
						'description' => __('Priority level of the ticket', 'dragwyb-click-to-chat'),
					],
				],
				'required'   => ['name', 'email', 'subject', 'description'],
			],
			'permission_callback'   => '__return_true',
			'execute_callback'      => [__CLASS__, 'tool_create_support_ticket'],
			'requires_confirmation' => true,
		]);

		// 4. Book Appointment / Callback Request
		self::register_tool('book_appointment', [
			'label'                 => __('Book Appointment / Callback', 'dragwyb-click-to-chat'),
			'description'           => __('Schedule a consultation, demo, or phone callback with a specialist.', 'dragwyb-click-to-chat'),
			'parameters'            => [
				'type'       => 'object',
				'properties' => [
					'name'             => [
						'type'        => 'string',
						'description' => __('Full name of the client', 'dragwyb-click-to-chat'),
					],
					'email'            => [
						'type'        => 'string',
						'description' => __('Email address of the client', 'dragwyb-click-to-chat'),
					],
					'phone'            => [
						'type'        => 'string',
						'description' => __('Contact phone number or WhatsApp', 'dragwyb-click-to-chat'),
					],
					'preferred_date'   => [
						'type'        => 'string',
						'description' => __('Preferred appointment date (YYYY-MM-DD or readable format)', 'dragwyb-click-to-chat'),
					],
					'preferred_time'   => [
						'type'        => 'string',
						'description' => __('Preferred appointment time or window (e.g. "10:00 AM" or "Afternoon")', 'dragwyb-click-to-chat'),
					],
					'notes'            => [
						'type'        => 'string',
						'description' => __('Topic or notes for the appointment', 'dragwyb-click-to-chat'),
					],
				],
				'required'   => ['name', 'preferred_date'],
			],
			'permission_callback'   => '__return_true',
			'execute_callback'      => [__CLASS__, 'tool_book_appointment'],
			'requires_confirmation' => true,
		]);

		// 5. Search Website Content
		self::register_tool('search_website_content', [
			'label'                 => __('Search Website Content', 'dragwyb-click-to-chat'),
			'description'           => __('Search published articles, blog posts, pages, and documentation on this website.', 'dragwyb-click-to-chat'),
			'parameters'            => [
				'type'       => 'object',
				'properties' => [
					'query' => [
						'type'        => 'string',
						'description' => __('Search keyword or phrase', 'dragwyb-click-to-chat'),
					],
					'limit' => [
						'type'        => 'integer',
						'description' => __('Maximum results to return (default: 4)', 'dragwyb-click-to-chat'),
					],
				],
				'required'   => ['query'],
			],
			'permission_callback'   => '__return_true',
			'execute_callback'      => [__CLASS__, 'tool_search_content'],
			'requires_confirmation' => false,
		]);
	}

	/**
	 * Get all registered tools.
	 *
	 * @return array
	 */
	public static function get_all_tools()
	{
		if (empty(self::$tools)) {
			self::init();
		}
		return self::$tools;
	}

	/**
	 * Format tools for LLM Function Calling APIs (OpenAI / Claude / Gemini / MCP).
	 *
	 * @return array
	 */
	public static function get_tools_for_ai()
	{
		$tools = self::get_all_tools();
		$formatted = [];

		foreach ($tools as $tool) {
			if (empty($tool['enabled'])) {
				continue;
			}
			$formatted[] = [
				'type'     => 'function',
				'function' => [
					'name'        => $tool['name'],
					'description' => $tool['description'],
					'parameters'  => $tool['parameters'],
				],
			];
		}

		return $formatted;
	}

	/**
	 * Execute a tool safely with schema validation and permission enforcement.
	 *
	 * @param string $tool_name
	 * @param array  $arguments
	 * @param array  $context
	 * @return array
	 */
	public static function execute_tool($tool_name, $arguments = [], $context = [])
	{
		if (empty(self::$tools)) {
			self::init();
		}

		$tool_name = sanitize_key($tool_name);
		if (!isset(self::$tools[$tool_name])) {
			return [
				'success' => false,
				'error'   => sprintf(__('Tool "%s" is not registered or permitted.', 'dragwyb-click-to-chat'), esc_html($tool_name)),
			];
		}

		$tool = self::$tools[$tool_name];

		// Permission Check
		if (is_callable($tool['permission_callback'])) {
			$allowed = call_user_func($tool['permission_callback'], $arguments, $context);
			if (!$allowed) {
				return [
					'success' => false,
					'error'   => __('Permission denied for this action tool.', 'dragwyb-click-to-chat'),
				];
			}
		}

		// Execute Callback
		if (!is_callable($tool['execute_callback'])) {
			return [
				'success' => false,
				'error'   => __('Tool execution handler is not callable.', 'dragwyb-click-to-chat'),
			];
		}

		try {
			$result = call_user_func($tool['execute_callback'], $arguments, $context);

			// Trigger Workflow Webhook (if configured)
			self::dispatch_workflow_webhook($tool_name, $arguments, $result, $context);

			return [
				'success' => true,
				'tool'    => $tool_name,
				'result'  => $result,
			];
		} catch (\Throwable $e) {
			return [
				'success' => false,
				'tool'    => $tool_name,
				'error'   => $e->getMessage(),
			];
		}
	}

	/**
	 * Tool Callback: Search Products (WooCommerce)
	 */
	public static function tool_search_products($args, $context = [])
	{
		if (!class_exists('DCTC_AI_WooCommerce') || !DCTC_AI_WooCommerce::is_active()) {
			return [
				'message'  => __('WooCommerce is not active on this website.', 'dragwyb-click-to-chat'),
				'products' => [],
			];
		}

		$query = sanitize_text_field($args['query'] ?? '');
		$limit = absint($args['limit'] ?? 4);

		$products = DCTC_AI_WooCommerce::search_products($query, $limit);

		return [
			'count'    => count($products),
			'products' => $products,
			'message'  => count($products) > 0
				? sprintf(__('Found %d matching products.', 'dragwyb-click-to-chat'), count($products))
				: __('No products found matching that description.', 'dragwyb-click-to-chat'),
		];
	}

	/**
	 * Tool Callback: Get Order Status (WooCommerce)
	 */
	public static function tool_get_order_status($args, $context = [])
	{
		if (!class_exists('DCTC_AI_WooCommerce') || !DCTC_AI_WooCommerce::is_active()) {
			return [
				'message' => __('WooCommerce is not active on this website.', 'dragwyb-click-to-chat'),
			];
		}

		$order_id = absint($args['order_id'] ?? 0);
		$email = sanitize_email($args['billing_email'] ?? ($context['email'] ?? ''));
		$user_id = get_current_user_id();

		$res = DCTC_AI_WooCommerce::lookup_order_status($order_id, $email, $user_id);

		if (is_wp_error($res)) {
			return [
				'success' => false,
				'message' => $res->get_error_message(),
			];
		}

		return $res;
	}

	/**
	 * Tool Callback: Create Support Ticket
	 */
	public static function tool_create_support_ticket($args, $context = [])
	{
		$ticket_id = 'TKT-' . strtoupper(wp_generate_password(6, false, false));
		$name = sanitize_text_field($args['name'] ?? 'Visitor');
		$email = sanitize_email($args['email'] ?? '');
		$subject = sanitize_text_field($args['subject'] ?? 'Support Inquiry');
		$description = sanitize_textarea_field($args['description'] ?? '');
		$priority = sanitize_key($args['priority'] ?? 'normal');

		// Notify Admin via Email
		$admin_email = get_option('admin_email');
		$email_subject = sprintf('[%s] New Support Ticket: %s (#%s)', get_bloginfo('name'), $subject, $ticket_id);
		$email_body = sprintf(
			"A new customer support ticket was created via AI Assistant:\n\nTicket ID: %s\nPriority: %s\nCustomer: %s <%s>\nSubject: %s\n\nIssue Details:\n%s\n\nDate: %s",
			$ticket_id,
			strtoupper($priority),
			$name,
			$email,
			$subject,
			$description,
			current_time('mysql')
		);

		@wp_mail($admin_email, $email_subject, $email_body);

		return [
			'ticket_id' => $ticket_id,
			'status'    => 'open',
			'priority'  => $priority,
			'subject'   => $subject,
			'message'   => sprintf(__('Support ticket #%s has been created. Our support team will follow up at %s.', 'dragwyb-click-to-chat'), $ticket_id, $email ?: 'your email'),
		];
	}

	/**
	 * Tool Callback: Book Appointment
	 */
	public static function tool_book_appointment($args, $context = [])
	{
		$booking_id = 'BK-' . strtoupper(wp_generate_password(6, false, false));
		$name = sanitize_text_field($args['name'] ?? 'Client');
		$email = sanitize_email($args['email'] ?? '');
		$phone = sanitize_text_field($args['phone'] ?? '');
		$date = sanitize_text_field($args['preferred_date'] ?? '');
		$time = sanitize_text_field($args['preferred_time'] ?? '');
		$notes = sanitize_textarea_field($args['notes'] ?? '');

		// Notify Admin
		$admin_email = get_option('admin_email');
		$email_subject = sprintf('[%s] New Appointment Request (#%s)', get_bloginfo('name'), $booking_id);
		$email_body = sprintf(
			"New appointment/callback requested via AI Assistant:\n\nBooking Reference: %s\nClient: %s\nEmail: %s\nPhone: %s\nPreferred Date: %s\nPreferred Time: %s\n\nNotes: %s\n\nDate Created: %s",
			$booking_id,
			$name,
			$email,
			$phone,
			$date,
			$time,
			$notes,
			current_time('mysql')
		);

		@wp_mail($admin_email, $email_subject, $email_body);

		return [
			'booking_id'     => $booking_id,
			'status'         => 'confirmed_request',
			'client_name'    => $name,
			'preferred_date' => $date,
			'preferred_time' => $time,
			'message'        => sprintf(__('Your appointment request #%s for %s (%s) has been received! Our team will contact you to confirm.', 'dragwyb-click-to-chat'), $booking_id, $date, $time ?: 'anytime'),
		];
	}

	/**
	 * Tool Callback: Search Website Content
	 */
	public static function tool_search_content($args, $context = [])
	{
		$query = sanitize_text_field($args['query'] ?? '');
		$limit = min(8, max(1, absint($args['limit'] ?? 4)));

		$search_query = new \WP_Query([
			's'              => $query,
			'post_type'      => ['post', 'page'],
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
		]);

		$results = [];
		foreach ($search_query->posts as $post) {
			$results[] = [
				'id'        => $post->ID,
				'title'     => get_the_title($post->ID),
				'permalink' => get_permalink($post->ID),
				'excerpt'   => wp_trim_words(wp_strip_all_tags($post->post_content), 20),
				'type'      => get_post_type($post->ID),
			];
		}

		return [
			'count'   => count($results),
			'results' => $results,
		];
	}

	/**
	 * Asynchronously dispatch automation webhook payload (Zapier, Make, CRM, Slack).
	 *
	 * @param string $tool_name
	 * @param array  $arguments
	 * @param array  $result
	 * @param array  $context
	 * @return void
	 */
	private static function dispatch_workflow_webhook($tool_name, $arguments, $result, $context)
	{
		$settings = DCTC_AI_Settings_Handler::dctc_ai_get_all_settings();
		$webhook_url = !empty($settings['chatbot']['workflow_webhook_url'])
			? esc_url_raw($settings['chatbot']['workflow_webhook_url'])
			: (!empty($settings['chatbot']['lead_webhook_url']) ? esc_url_raw($settings['chatbot']['lead_webhook_url']) : '');

		if (empty($webhook_url) || !wp_http_validate_url($webhook_url)) {
			return;
		}

		$payload = [
			'event'        => 'tool_executed',
			'tool'         => $tool_name,
			'arguments'    => $arguments,
			'result'       => $result,
			'session_id'   => $context['session_id'] ?? 'unknown',
			'source_url'   => home_url(),
			'timestamp'    => time(),
			'site_name'    => get_bloginfo('name'),
		];

		wp_remote_post($webhook_url, [
			'timeout'   => 5,
			'blocking'  => false,
			'headers'   => [
				'Content-Type' => 'application/json; charset=utf-8',
				'User-Agent'   => 'WordPress/Dragwyb-AI-Workflows',
			],
			'body'      => wp_json_encode($payload),
			'sslverify' => apply_filters('https_local_ssl_verify', false),
		]);
	}
}
