<?php
/**
 * DCTC AI WordPress Abilities Registry
 *
 * Implements discrete, modular, self-describing capabilities (Abilities)
 * conforming to WordPress ecosystem specifications and Model Context Protocol (MCP).
 *
 * @package Dragwyb_Click_To_Chat
 */

if (!defined('ABSPATH')) {
	exit;
}

class DCTC_AI_Abilities
{
	/**
	 * Internal registered abilities map.
	 *
	 * @var array<string, array>
	 */
	private static $abilities = [];

	/**
	 * Initialize core abilities.
	 *
	 * @return void
	 */
	public static function init()
	{
		self::register_core_abilities();
	}

	/**
	 * Register an ability.
	 *
	 * @param string $id Unique identifier (e.g. 'dragwyb/search-knowledge').
	 * @param array  $args Specification arguments.
	 * @return void
	 */
	public static function register_ability($id, array $args)
	{
		$id = sanitize_text_field($id);
		self::$abilities[$id] = wp_parse_args($args, [
			'id'                  => $id,
			'label'               => $args['label'] ?? ucfirst(str_replace(['dragwyb/', '_', '-'], ['', ' ', ' '], $id)),
			'description'         => $args['description'] ?? '',
			'parameters'          => [
				'type'       => 'object',
				'properties' => [],
				'required'   => [],
			],
			'returns'             => [
				'type'       => 'object',
				'properties' => [
					'success' => ['type' => 'boolean'],
				],
			],
			'execute_callback'    => null,
			'permission_callback' => '__return_true',
			'public_exposure'     => true,
			'requires_auth'       => false,
		]);
	}

	/**
	 * Get all registered abilities.
	 *
	 * @param bool $public_only Whether to return only publicly exposable abilities.
	 * @return array<string, array>
	 */
	public static function get_abilities($public_only = false)
	{
		if (empty(self::$abilities)) {
			self::init();
		}

		$abilities = self::$abilities;

		/**
		 * Filters registered AI abilities.
		 *
		 * @param array $abilities Map of registered abilities.
		 */
		$abilities = apply_filters('dctc_ai_registered_abilities', $abilities);

		if ($public_only) {
			return array_filter($abilities, function($ab) {
				return !empty($ab['public_exposure']);
			});
		}

		return $abilities;
	}

	/**
	 * Get single ability by ID.
	 *
	 * @param string $id
	 * @return array|null
	 */
	public static function get_ability($id)
	{
		$abilities = self::get_abilities();
		$normalized = sanitize_text_field($id);

		if (isset($abilities[$normalized])) {
			return $abilities[$normalized];
		}

		// Also check with/without 'dragwyb/' prefix
		$prefixed = 'dragwyb/' . ltrim($normalized, 'dragwyb/');
		if (isset($abilities[$prefixed])) {
			return $abilities[$prefixed];
		}

		$unprefixed = str_replace('dragwyb/', '', $normalized);
		if (isset($abilities[$unprefixed])) {
			return $abilities[$unprefixed];
		}

		return null;
	}

	/**
	 * Execute an ability with authorization and validation.
	 *
	 * @param string $id Ability name or ID.
	 * @param array  $arguments Input arguments matching parameter schema.
	 * @param array  $context Execution context (session, user, email).
	 * @return array Standard result payload.
	 */
	public static function execute_ability($id, array $arguments = [], array $context = [])
	{
		$ability = self::get_ability($id);

		if (!$ability) {
			return [
				'success' => false,
				'code'    => 'ABILITY_NOT_FOUND',
				'message' => sprintf(
					/* translators: %s: Ability identifier */
					esc_html__('Ability "%s" is not registered.', 'dragwyb-click-to-chat'),
					esc_html($id)
				),
			];
		}

		// Check permission
		if (is_callable($ability['permission_callback'])) {
			$allowed = call_user_func($ability['permission_callback'], $arguments, $context);
			if (!$allowed) {
				return [
					'success' => false,
					'code'    => 'PERMISSION_DENIED',
					'message' => esc_html__('You do not have permission to execute this ability.', 'dragwyb-click-to-chat'),
				];
			}
		}

		// Execute pre-execution hook
		do_action('dctc_ai_before_ability_execute', $ability['id'], $arguments, $context);

		if (!is_callable($ability['execute_callback'])) {
			return [
				'success' => false,
				'code'    => 'INVALID_CALLBACK',
				'message' => esc_html__('The ability has no executable callback configured.', 'dragwyb-click-to-chat'),
			];
		}

		try {
			$result = call_user_func($ability['execute_callback'], $arguments, $context);
			
			// Normalize result
			if (!is_array($result)) {
				$result = [
					'success' => true,
					'data'    => $result,
				];
			} elseif (!isset($result['success'])) {
				$result['success'] = true;
			}

			// Post-execution hook
			do_action('dctc_ai_after_ability_execute', $ability['id'], $arguments, $result, $context);

			return $result;
		} catch (\Throwable $e) {
			return [
				'success' => false,
				'code'    => 'EXECUTION_FAILED',
				'message' => $e->getMessage(),
			];
		}
	}

	/**
	 * Register built-in standard abilities.
	 *
	 * @return void
	 */
	private static function register_core_abilities()
	{
		// 1. Search Knowledge Base
		self::register_ability('dragwyb/search-knowledge', [
			'label'               => __('Search Knowledge Base', 'dragwyb-click-to-chat'),
			'description'         => __('Searches the site Knowledge Base using semantic vectors and full-text keyword indexing.', 'dragwyb-click-to-chat'),
			'parameters'          => [
				'type'       => 'object',
				'properties' => [
					'query' => [
						'type'        => 'string',
						'description' => __('Search query, topic, or question to lookup in knowledge base.', 'dragwyb-click-to-chat'),
					],
					'limit' => [
						'type'        => 'integer',
						'description' => __('Maximum number of relevant chunks to retrieve (1-10).', 'dragwyb-click-to-chat'),
					],
				],
				'required'   => ['query'],
			],
			'returns'             => [
				'type'       => 'object',
				'properties' => [
					'success' => ['type' => 'boolean'],
					'results' => ['type' => 'array'],
				],
			],
			'execute_callback'    => function($args) {
				$query = sanitize_text_field($args['query'] ?? '');
				$limit = isset($args['limit']) ? max(1, min(10, intval($args['limit']))) : 4;
				if (empty($query)) {
					return ['success' => false, 'message' => __('Query is empty.', 'dragwyb-click-to-chat')];
				}
				require_once DCTC_PLUGIN_DIR . 'includes/ai/class-dctc-ai-retriever.php';
				$retriever = new DCTC_AI_Retriever();
				$chunks = $retriever->retrieve($query, $limit);
				return [
					'success' => true,
					'query'   => $query,
					'count'   => count($chunks),
					'results' => $chunks,
				];
			},
			'permission_callback' => '__return_true',
			'public_exposure'     => true,
		]);

		// 2. Get Business Information
		self::register_ability('dragwyb/get-business-info', [
			'label'               => __('Get Business Information', 'dragwyb-click-to-chat'),
			'description'         => __('Retrieves public company details, operating hours, active channels, and business contact information.', 'dragwyb-click-to-chat'),
			'parameters'          => [
				'type'       => 'object',
				'properties' => [],
			],
			'returns'             => [
				'type'       => 'object',
				'properties' => [
					'success'      => ['type' => 'boolean'],
					'site_name'    => ['type' => 'string'],
					'site_url'     => ['type' => 'string'],
					'is_open_now'  => ['type' => 'boolean'],
					'channels'     => ['type' => 'object'],
				],
			],
			'execute_callback'    => function() {
				$settings = DCTC_AI_Settings_Handler::dctc_ai_get_all_settings();
				$bot = $settings['chatbot'] ?? [];
				$is_open = class_exists('DCTC_AI_Chat_Controller') ? DCTC_AI_Chat_Controller::is_within_business_hours($bot) : true;
				return [
					'success'        => true,
					'site_name'      => get_bloginfo('name'),
					'site_url'       => home_url(),
					'description'    => get_bloginfo('description'),
					'is_open_now'    => $is_open,
					'business_hours' => [
						'enabled'  => !empty($bot['enable_business_hours']),
						'start'    => $bot['business_hours_start'] ?? '09:00',
						'end'      => $bot['business_hours_end'] ?? '18:00',
						'days'     => $bot['business_hours_days'] ?? ['mon', 'tue', 'wed', 'thu', 'fri'],
						'timezone' => $bot['business_hours_timezone'] ?? 'UTC',
					],
					'channels'       => [
						'whatsapp' => !empty($bot['handoff_whatsapp_number']) ? $bot['handoff_whatsapp_number'] : '',
						'phone'    => !empty($bot['handoff_phone_number']) ? $bot['handoff_phone_number'] : '',
						'email'    => !empty($bot['handoff_email_address']) ? $bot['handoff_email_address'] : get_option('admin_email'),
					],
				];
			},
			'permission_callback' => '__return_true',
			'public_exposure'     => true,
		]);

		// 3. Search Products (WooCommerce)
		self::register_ability('dragwyb/search-products', [
			'label'               => __('Search Products', 'dragwyb-click-to-chat'),
			'description'         => __('Searches WooCommerce store products by keyword, category, price, or inventory status.', 'dragwyb-click-to-chat'),
			'parameters'          => [
				'type'       => 'object',
				'properties' => [
					'query' => [
						'type'        => 'string',
						'description' => __('Keywords or product search terms.', 'dragwyb-click-to-chat'),
					],
					'limit' => [
						'type'        => 'integer',
						'description' => __('Maximum products to return (default: 4).', 'dragwyb-click-to-chat'),
					],
				],
				'required'   => ['query'],
			],
			'returns'             => [
				'type'       => 'object',
				'properties' => [
					'success'  => ['type' => 'boolean'],
					'products' => ['type' => 'array'],
				],
			],
			'execute_callback'    => function($args) {
				$query = sanitize_text_field($args['query'] ?? '');
				$limit = isset($args['limit']) ? max(1, min(10, intval($args['limit']))) : 4;
				if (class_exists('DCTC_AI_WooCommerce')) {
					$products = DCTC_AI_WooCommerce::search_products($query, $limit);
					return [
						'success'  => true,
						'count'    => count($products),
						'products' => $products,
					];
				}
				return [
					'success'  => false,
					'message'  => __('WooCommerce is not installed or active.', 'dragwyb-click-to-chat'),
					'products' => [],
				];
			},
			'permission_callback' => '__return_true',
			'public_exposure'     => true,
		]);

		// 4. Get Product Details (WooCommerce)
		self::register_ability('dragwyb/get-product', [
			'label'               => __('Get Product Details', 'dragwyb-click-to-chat'),
			'description'         => __('Looks up detailed specifications, price, stock status, variations, and image URL for a single product by ID or SKU.', 'dragwyb-click-to-chat'),
			'parameters'          => [
				'type'       => 'object',
				'properties' => [
					'product_id' => [
						'type'        => 'integer',
						'description' => __('WooCommerce product ID.', 'dragwyb-click-to-chat'),
					],
					'sku'        => [
						'type'        => 'string',
						'description' => __('Product SKU code (optional if product_id provided).', 'dragwyb-click-to-chat'),
					],
				],
			],
			'returns'             => [
				'type'       => 'object',
				'properties' => [
					'success' => ['type' => 'boolean'],
					'product' => ['type' => 'object'],
				],
			],
			'execute_callback'    => function($args) {
				if (!function_exists('wc_get_product')) {
					return ['success' => false, 'message' => __('WooCommerce is not active.', 'dragwyb-click-to-chat')];
				}
				$product_id = isset($args['product_id']) ? intval($args['product_id']) : 0;
				if (!$product_id && !empty($args['sku'])) {
					$product_id = wc_get_product_id_by_sku(sanitize_text_field($args['sku']));
				}
				if (!$product_id) {
					return ['success' => false, 'message' => __('Product ID or valid SKU required.', 'dragwyb-click-to-chat')];
				}
				$product = wc_get_product($product_id);
				if (!$product) {
					return ['success' => false, 'message' => __('Product not found.', 'dragwyb-click-to-chat')];
				}
				return [
					'success' => true,
					'product' => [
						'id'          => $product->get_id(),
						'name'        => $product->get_name(),
						'price'       => $product->get_price(),
						'regular_price' => $product->get_regular_price(),
						'sale_price'  => $product->get_sale_price(),
						'in_stock'    => $product->is_in_stock(),
						'stock_qty'   => $product->get_stock_quantity(),
						'sku'         => $product->get_sku(),
						'url'         => $product->get_permalink(),
						'description' => wp_strip_all_tags($product->get_short_description() ?: $product->get_description()),
					],
				];
			},
			'permission_callback' => '__return_true',
			'public_exposure'     => true,
		]);

		// 5. Create Sales Lead
		self::register_ability('dragwyb/create-lead', [
			'label'               => __('Capture Sales Lead', 'dragwyb-click-to-chat'),
			'description'         => __('Captures a sales lead with qualification scoring, requirement summary, and automated email alert.', 'dragwyb-click-to-chat'),
			'parameters'          => [
				'type'       => 'object',
				'properties' => [
					'name'        => ['type' => 'string', 'description' => __('Full Name of the lead.', 'dragwyb-click-to-chat')],
					'email'       => ['type' => 'string', 'description' => __('Email Address.', 'dragwyb-click-to-chat')],
					'phone'       => ['type' => 'string', 'description' => __('Phone or WhatsApp Number.', 'dragwyb-click-to-chat')],
					'company'     => ['type' => 'string', 'description' => __('Company name.', 'dragwyb-click-to-chat')],
					'budget'      => ['type' => 'string', 'description' => __('Estimated budget.', 'dragwyb-click-to-chat')],
					'timeline'    => ['type' => 'string', 'description' => __('Project timeline.', 'dragwyb-click-to-chat')],
					'requirement' => ['type' => 'string', 'description' => __('Specific inquiry or requirements.', 'dragwyb-click-to-chat')],
				],
				'required'   => ['email'],
			],
			'returns'             => [
				'type'       => 'object',
				'properties' => [
					'success' => ['type' => 'boolean'],
					'lead_id' => ['type' => 'integer'],
					'score'   => ['type' => 'integer'],
				],
			],
			'execute_callback'    => function($args, $context = []) {
				require_once DCTC_PLUGIN_DIR . 'includes/ai/class-dctc-ai-leads-controller.php';
				$controller = new DCTC_AI_Leads_Controller();
				$req = new \WP_REST_Request('POST', '/dctc-ai/v1/leads');
				$req->set_body_params(array_merge($args, [
					'session_id' => $context['session_id'] ?? ('sess_' . uniqid()),
				]));
				$res = $controller->handle_submission($req);
				$data = $res->get_data();
				return is_array($data) ? $data : ['success' => true, 'response' => $data];
			},
			'permission_callback' => '__return_true',
			'public_exposure'     => true,
		]);

		// 6. Get Analytics & Copilot Summary
		self::register_ability('dragwyb/get-analytics', [
			'label'               => __('Get Site AI Analytics', 'dragwyb-click-to-chat'),
			'description'         => __('Queries chatbot metrics, unanswered questions, sentiment breakdown, and lead generation totals.', 'dragwyb-click-to-chat'),
			'parameters'          => [
				'type'       => 'object',
				'properties' => [],
			],
			'returns'             => [
				'type'       => 'object',
				'properties' => [
					'success' => ['type' => 'boolean'],
					'stats'   => ['type' => 'object'],
				],
			],
			'execute_callback'    => function() {
				require_once DCTC_PLUGIN_DIR . 'includes/ai/class-dctc-ai-admin-copilot.php';
				$stats = DCTC_AI_Admin_Copilot::get_analytics_summary();
				return [
					'success' => true,
					'stats'   => $stats,
				];
			},
			'permission_callback' => function() {
				return current_user_can('manage_options');
			},
			'public_exposure'     => false,
			'requires_auth'       => true,
		]);

		// 7. Create Support Ticket
		self::register_ability('dragwyb/create-support-ticket', [
			'label'               => __('Create Support Ticket', 'dragwyb-click-to-chat'),
			'description'         => __('Creates an email support inquiry or ticket for customer issues.', 'dragwyb-click-to-chat'),
			'parameters'          => [
				'type'       => 'object',
				'properties' => [
					'email'   => ['type' => 'string', 'description' => __('Customer Email address.', 'dragwyb-click-to-chat')],
					'subject' => ['type' => 'string', 'description' => __('Inquiry subject.', 'dragwyb-click-to-chat')],
					'message' => ['type' => 'string', 'description' => __('Ticket description / details.', 'dragwyb-click-to-chat')],
				],
				'required'   => ['email', 'message'],
			],
			'returns'             => [
				'type'       => 'object',
				'properties' => [
					'success'   => ['type' => 'boolean'],
					'ticket_id' => ['type' => 'string'],
				],
			],
			'execute_callback'    => function($args, $context = []) {
				require_once DCTC_PLUGIN_DIR . 'includes/ai/class-dctc-ai-tool-registry.php';
				return DCTC_AI_Tool_Registry::execute_tool('create_support_ticket', $args, $context);
			},
			'permission_callback' => '__return_true',
			'public_exposure'     => true,
		]);

		// 8. Book Appointment
		self::register_ability('dragwyb/book-appointment', [
			'label'               => __('Book Appointment', 'dragwyb-click-to-chat'),
			'description'         => __('Schedules a consultation or service appointment with customer details.', 'dragwyb-click-to-chat'),
			'parameters'          => [
				'type'       => 'object',
				'properties' => [
					'name'  => ['type' => 'string', 'description' => __('Customer full name.', 'dragwyb-click-to-chat')],
					'email' => ['type' => 'string', 'description' => __('Customer email address.', 'dragwyb-click-to-chat')],
					'date'  => ['type' => 'string', 'description' => __('Preferred appointment date.', 'dragwyb-click-to-chat')],
					'time'  => ['type' => 'string', 'description' => __('Preferred appointment time slot.', 'dragwyb-click-to-chat')],
					'notes' => ['type' => 'string', 'description' => __('Additional notes.', 'dragwyb-click-to-chat')],
				],
				'required'   => ['email', 'date'],
			],
			'returns'             => [
				'type'       => 'object',
				'properties' => [
					'success'        => ['type' => 'boolean'],
					'appointment_id' => ['type' => 'string'],
				],
			],
			'execute_callback'    => function($args, $context = []) {
				require_once DCTC_PLUGIN_DIR . 'includes/ai/class-dctc-ai-tool-registry.php';
				return DCTC_AI_Tool_Registry::execute_tool('book_appointment', $args, $context);
			},
			'permission_callback' => '__return_true',
			'public_exposure'     => true,
		]);
	}
}
