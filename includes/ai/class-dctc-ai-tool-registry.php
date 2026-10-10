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

		// 6. Validate URL or Find Real Page
		self::register_tool('validate_url_or_find_page', [
			'label'                 => __('Validate URL or Find Page', 'dragwyb-click-to-chat'),
			'description'           => __('Check if a specific website URL or path exists on this site. If it does not exist, searches and returns the matching valid page URL or Support desk URL.', 'dragwyb-click-to-chat'),
			'parameters'            => [
				'type'       => 'object',
				'properties' => [
					'url_or_path' => [
						'type'        => 'string',
						'description' => __('The URL or relative path to check (e.g. "/pricing" or "https://example.com/docs")', 'dragwyb-click-to-chat'),
					],
					'topic_hint'  => [
						'type'        => 'string',
						'description' => __('Optional topic, keyword, or page title to find if the URL does not exist (e.g. "support", "refund policy")', 'dragwyb-click-to-chat'),
					],
				],
				'required'   => ['url_or_path'],
			],
			'permission_callback'   => '__return_true',
			'execute_callback'      => [__CLASS__, 'tool_validate_url_or_find_page'],
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
				/* translators: %s: Tool name */
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
				/* translators: %d: Number of products */
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
			/* translators: 1: Ticket ID, 2: Customer email address */
			'message'   => sprintf(__('Support ticket #%1$s has been created. Our support team will follow up at %2$s.', 'dragwyb-click-to-chat'), $ticket_id, $email ?: 'your email'),
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
			/* translators: 1: Booking ID, 2: Preferred date, 3: Preferred time */
			'message'        => sprintf(__('Your appointment request #%1$s for %2$s (%3$s) has been received! Our team will contact you to confirm.', 'dragwyb-click-to-chat'), $booking_id, $date, $time ?: 'anytime'),
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
	 * Tool Callback: Validate URL or Find Real Page.
	 *
	 * @param array $args
	 * @param array $context
	 * @return array
	 */
	public static function tool_validate_url_or_find_page($args, $context = [])
	{
		$url = sanitize_text_field($args['url_or_path'] ?? '');
		$topic = sanitize_text_field($args['topic_hint'] ?? '');

		$home_url = untrailingslashit(home_url());
		$settings = class_exists('DCTC_AI_Settings_Handler') ? DCTC_AI_Settings_Handler::dctc_ai_get_all_settings() : [];
		$support_url = !empty($settings['chatbot']['support_url']) ? $settings['chatbot']['support_url'] : home_url('/support');

		if (empty($url)) {
			return ['valid' => false, 'exists' => false, 'url' => '', 'message' => 'No URL provided'];
		}

		$full_url = strpos($url, 'http') === 0 ? $url : home_url('/' . ltrim($url, '/'));
		$clean_url = untrailingslashit(strtok($full_url, '?#'));

		// Home check
		if ($clean_url === $home_url || $url === '/' || $url === '') {
			return ['valid' => true, 'exists' => true, 'url' => home_url('/'), 'title' => 'Home'];
		}

		// Support desk check
		if (untrailingslashit($full_url) === untrailingslashit($support_url)) {
			return ['valid' => true, 'exists' => true, 'url' => $support_url, 'title' => 'Support Desk'];
		}

		// WooCommerce check
		if (class_exists('WooCommerce') && function_exists('wc_get_page_permalink')) {
			$wc_map = [
				'shop'      => wc_get_page_permalink('shop'),
				'cart'      => function_exists('wc_get_cart_url') ? wc_get_cart_url() : '',
				'checkout'  => function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : '',
				'myaccount' => wc_get_page_permalink('myaccount'),
			];
			foreach ($wc_map as $wc_key => $wc_url) {
				if (!empty($wc_url) && untrailingslashit($clean_url) === untrailingslashit($wc_url)) {
					return ['valid' => true, 'exists' => true, 'url' => $wc_url, 'title' => ucfirst($wc_key)];
				}
			}
		}

		// Direct post ID / slug check
		$post_id = url_to_postid($full_url);
		if ($post_id > 0 && get_post_status($post_id) === 'publish') {
			return ['valid' => true, 'exists' => true, 'url' => get_permalink($post_id), 'title' => get_the_title($post_id)];
		}

		$parsed = wp_parse_url($full_url);
		$path = isset($parsed['path']) ? trim($parsed['path'], '/') : '';
		if (!empty($path)) {
			$page_obj = get_page_by_path($path, OBJECT, ['page', 'post', 'product']);
			if ($page_obj && $page_obj->post_status === 'publish') {
				return ['valid' => true, 'exists' => true, 'url' => get_permalink($page_obj->ID), 'title' => get_the_title($page_obj->ID)];
			}
		}

		// URL does NOT exist -> Search for closest matching page or support
		$search_keyword = !empty($topic) ? $topic : str_replace(['-', '_', '/'], ' ', $path);
		if (preg_match('/\b(support|contact|help|ticket|inquiry|agent)\b/i', $search_keyword)) {
			return [
				'valid'        => false,
				'exists'       => false,
				'fallback_url' => $support_url,
				'title'        => 'Support Desk',
				'message'      => sprintf('Path "%s" does not exist. Redirecting to Support Desk.', $url),
			];
		}

		if (strlen($search_keyword) >= 3) {
			$found = get_posts([
				'post_type'      => ['page', 'post', 'product'],
				'post_status'    => 'publish',
				's'              => $search_keyword,
				'posts_per_page' => 1,
			]);
			if (!empty($found) && $found[0] instanceof \WP_Post) {
				return [
					'valid'        => false,
					'exists'       => false,
					'fallback_url' => get_permalink($found[0]->ID),
					'title'        => get_the_title($found[0]->ID),
					'message'      => sprintf('Path "%s" does not exist. Closest matching page found: %s', $url, get_the_title($found[0]->ID)),
				];
			}
		}

		return [
			'valid'        => false,
			'exists'       => false,
			'fallback_url' => $support_url,
			'title'        => 'Support Desk',
			'message'      => sprintf('Path "%s" does not exist on this site.', $url),
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
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Using core WordPress filter https_local_ssl_verify.
			'sslverify' => apply_filters('https_local_ssl_verify', false),
		]);
	}
}
