<?php
/**
 * DCTC AI MCP Controller
 *
 * Owns the admin-configured MCP server list: REST CRUD for that list, and
 * building MCP-derived context for chat prompts. Distinct from
 * DCTC_AI_MCP_Server (includes/mcp/class-dctc-ai-mcp-server.php), which builds
 * site-content context (posts/products) rather than managing this list.
 *
 * @package Dragwyb_Click_To_Chat
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Class DCTC_AI_MCP_Controller
 */
class DCTC_AI_MCP_Controller
{
	use DCTC_AI_REST_Helpers;

	/**
	 * Key Store
	 *
	 * @var DCTC_AI_Key_Store
	 */
	private $key_store;

	/**
	 * Constructor
	 *
	 * @param DCTC_AI_Key_Store $key_store Used to encrypt/decrypt server API keys.
	 */
	public function __construct(DCTC_AI_Key_Store $key_store)
	{
		$this->key_store = $key_store;
	}

	/**
	 * REST callback: list configured MCP servers.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return \WP_REST_Response
	 */
	public function get_servers($request)
	{
		$settings = DCTC_AI_Settings_Handler::dctc_ai_get_all_settings();
		$servers = isset($settings['mcp_servers']) ? $settings['mcp_servers'] : [];
		$servers = array_map([$this, 'redact_server'], $servers);

		return new \WP_REST_Response(['servers' => $servers], 200);
	}

	/**
	 * REST callback: add an MCP server.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return \WP_REST_Response
	 */
	public function save_server($request)
	{
		$params = $request->get_json_params();

		$name = isset($params['name']) ? sanitize_text_field($params['name']) : '';
		$url = isset($params['url']) ? esc_url_raw($params['url']) : '';
		$type = isset($params['type']) ? sanitize_text_field($params['type']) : 'http';
		$apiKey = isset($params['apiKey']) ? sanitize_text_field($params['apiKey']) : '';
		$enabled = isset($params['enabled']) ? (bool) $params['enabled'] : true;
		$isDefault = isset($params['isDefault']) ? (bool) $params['isDefault'] : false;

		if (empty($name) || empty($url)) {
			return new \WP_REST_Response(
				['error' => esc_html__('Name and URL are required', 'dragwyb-click-to-chat')],
				400
			);
		}

		$server_id = 'mcp_' . sanitize_title($name) . '_' . time();

		$settings = DCTC_AI_Settings_Handler::dctc_ai_get_all_settings();
		if (!isset($settings['mcp_servers'])) {
			$settings['mcp_servers'] = [];
		}

		$server = [
			'id' => $server_id,
			'name' => $name,
			'url' => $url,
			'type' => $type,
			'enabled' => $enabled,
			'isDefault' => $isDefault,
			'addedAt' => current_time('mysql'),
		];

		if (!empty($apiKey)) {
			$server['apiKey'] = $this->key_store->encrypt_secret($apiKey);
		}

		$settings['mcp_servers'][] = $server;
		DCTC_AI_Settings_Handler::dctc_ai_persist_settings($settings);

		return new \WP_REST_Response(
			[
				'success' => true,
				'message' => esc_html__('MCP server added successfully!', 'dragwyb-click-to-chat'),
				'server' => $this->redact_server($server),
			],
			200
		);
	}

	/**
	 * REST callback: update an MCP server (currently: enabled flag only).
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return \WP_REST_Response
	 */
	public function update_server($request)
	{
		$server_id = sanitize_text_field($request->get_param('id'));
		$params = $request->get_json_params();

		$settings = DCTC_AI_Settings_Handler::dctc_ai_get_all_settings();
		$servers = isset($settings['mcp_servers']) ? $settings['mcp_servers'] : [];

		$found = false;
		foreach ($servers as &$server) {
			if ($server['id'] === $server_id) {
				if (isset($params['enabled'])) {
					$server['enabled'] = (bool) $params['enabled'];
				}
				$found = true;
				break;
			}
		}

		if (!$found) {
			return new \WP_REST_Response(
				['error' => esc_html__('Server not found', 'dragwyb-click-to-chat')],
				404
			);
		}

		$settings['mcp_servers'] = $servers;
		DCTC_AI_Settings_Handler::dctc_ai_persist_settings($settings);

		return new \WP_REST_Response(
			[
				'success' => true,
				'message' => esc_html__('MCP server updated successfully!', 'dragwyb-click-to-chat'),
			],
			200
		);
	}

	/**
	 * REST callback: delete an MCP server.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return \WP_REST_Response
	 */
	public function delete_server($request)
	{
		$server_id = sanitize_text_field($request->get_param('id'));

		$settings = DCTC_AI_Settings_Handler::dctc_ai_get_all_settings();
		$servers = isset($settings['mcp_servers']) ? $settings['mcp_servers'] : [];

		$filtered_servers = array_filter($servers, function ($server) use ($server_id) {
			return $server['id'] !== $server_id;
		});

		if (count($filtered_servers) === count($servers)) {
			return new \WP_REST_Response(
				['error' => esc_html__('Server not found', 'dragwyb-click-to-chat')],
				404
			);
		}

		$settings['mcp_servers'] = array_values($filtered_servers);
		DCTC_AI_Settings_Handler::dctc_ai_persist_settings($settings);

		return new \WP_REST_Response(
			[
				'success' => true,
				'message' => esc_html__('MCP server deleted successfully!', 'dragwyb-click-to-chat'),
			],
			200
		);
	}

	/**
	 * Build MCP-derived context for a chat prompt: site content context from
	 * DCTC_AI_MCP_Server, combined with a summary of enabled custom MCP
	 * servers. Both are included (not one as a fallback for the other) so a
	 * configured custom server is actually used even when the site already
	 * has content of its own — which is virtually always.
	 *
	 * @param string $prompt   User prompt.
	 * @param array  $settings Plugin settings.
	 * @return string Context to append to the system prompt, or ''.
	 */
	public function get_chat_context($prompt, $settings)
	{
		try {
			if (!class_exists('DCTC_AI_MCP_Server')) {
				require_once DCTC_PLUGIN_DIR . 'includes/ai/mcp/class-dctc-ai-mcp-server.php';
			}

			$mcp_server = DCTC_AI_MCP_Server::get_instance();
			$site_context = $mcp_server ? $mcp_server->get_site_context() : '';

			$custom_context = $this->get_custom_servers_context($prompt, $settings);

			$context = $site_context;
			if (!empty($custom_context)) {
				$context .= "\n\n## Connected MCP Servers\n" . $custom_context;
			}

			return $context;
		} catch (Exception $e) {
			self::log_debug('Dragwyb AI AI MCP Context Retrieval Error: ' . $e->getMessage());
			return '';
		}
	}

	/**
	 * Summarize enabled custom MCP servers for the chat context.
	 *
	 * @param string $query    User query (currently unused, reserved for
	 *                         future per-server querying).
	 * @param array  $settings Plugin settings.
	 * @return string Context from custom MCP servers.
	 */
	private function get_custom_servers_context($query, $settings)
	{
		$mcp_servers = isset($settings['mcp_servers']) ? $settings['mcp_servers'] : [];

		if (empty($mcp_servers)) {
			return '';
		}

		$context = '';
		foreach ($mcp_servers as $server) {
			if (!empty($server['enabled'])) {
				// Custom MCP server integration point
				$context .= "MCP Server: " . sanitize_text_field($server['name']) . "\n";
			}
		}

		return $context;
	}

	/**
	 * Redact MCP Server Secret
	 *
	 * The stored apiKey (even encrypted) should never round-trip back to
	 * the browser — callers only need to know whether one is set.
	 *
	 * @param array $server MCP server record.
	 * @return array Server record safe to return over the REST API.
	 */
	private function redact_server($server)
	{
		$server['hasApiKey'] = !empty($server['apiKey']);
		unset($server['apiKey']);

		return $server;
	}

	/**
	 * Get MCP Server Manifest & Information.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function get_manifest($request)
	{
		return new \WP_REST_Response([
			'name'            => 'dragwyb-click-to-chat-mcp',
			'version'         => defined('DCTC_VERSION') ? DCTC_VERSION : '1.1.0',
			'protocolVersion' => '2024-11-05',
			'capabilities'    => [
				'tools'     => ['listChanged' => false],
				'resources' => ['subscribe' => false, 'listChanged' => false],
				'prompts'   => ['listChanged' => false],
			],
			'serverInfo'      => [
				'name'    => 'Dragwyb Click to Chat WordPress AI MCP Server',
				'version' => defined('DCTC_VERSION') ? DCTC_VERSION : '1.1.0',
				'website' => home_url(),
			],
		], 200);
	}

	/**
	 * REST callback: list all MCP tools (derived from registered WordPress abilities).
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function get_tools($request)
	{
		require_once DCTC_PLUGIN_DIR . 'includes/ai/class-dctc-ai-abilities.php';
		$abilities = DCTC_AI_Abilities::get_abilities(true);

		$tools = [];
		foreach ($abilities as $id => $ab) {
			$tools[] = [
				'name'        => str_replace(['dragwyb/', '/'], ['', '_'], $ab['id']),
				'description' => $ab['description'],
				'inputSchema' => $ab['parameters'],
			];
		}

		return new \WP_REST_Response(['tools' => $tools], 200);
	}

	/**
	 * REST callback: execute an MCP tool.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function execute_tool($request)
	{
		$tool_name = sanitize_text_field($request->get_param('tool'));
		$params    = $request->get_json_params() ?: [];
		$arguments = isset($params['arguments']) && is_array($params['arguments']) ? $params['arguments'] : $params;

		require_once DCTC_PLUGIN_DIR . 'includes/ai/class-dctc-ai-abilities.php';
		$result = DCTC_AI_Abilities::execute_ability($tool_name, $arguments);

		return new \WP_REST_Response([
			'content' => [
				[
					'type' => 'text',
					'text' => wp_json_encode($result, JSON_PRETTY_PRINT),
				],
			],
			'isError' => empty($result['success']),
		], !empty($result['success']) ? 200 : 400);
	}

	/**
	 * Handle standard MCP JSON-RPC 2.0 requests.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function handle_jsonrpc($request)
	{
		$body = $request->get_json_params();
		if (!is_array($body) || empty($body['jsonrpc']) || $body['jsonrpc'] !== '2.0') {
			return new \WP_REST_Response([
				'jsonrpc' => '2.0',
				'id'      => $body['id'] ?? null,
				'error'   => [
					'code'    => -32600,
					'message' => 'Invalid JSON-RPC 2.0 Request',
				],
			], 400);
		}

		$id     = $body['id'] ?? null;
		$method = sanitize_text_field($body['method'] ?? '');
		$params = is_array($body['params'] ?? null) ? $body['params'] : [];

		require_once DCTC_PLUGIN_DIR . 'includes/ai/class-dctc-ai-abilities.php';

		switch ($method) {
			case 'initialize':
				return new \WP_REST_Response([
					'jsonrpc' => '2.0',
					'id'      => $id,
					'result'  => [
						'protocolVersion' => '2024-11-05',
						'capabilities'    => [
							'tools'     => ['listChanged' => false],
							'resources' => ['subscribe' => false, 'listChanged' => false],
							'prompts'   => ['listChanged' => false],
						],
						'serverInfo'      => [
							'name'    => 'Dragwyb AI WordPress MCP Server',
							'version' => defined('DCTC_VERSION') ? DCTC_VERSION : '1.1.0',
						],
					],
				], 200);

			case 'ping':
				return new \WP_REST_Response([
					'jsonrpc' => '2.0',
					'id'      => $id,
					'result'  => (object) [],
				], 200);

			case 'tools/list':
				$abilities = DCTC_AI_Abilities::get_abilities(true);
				$tools = [];
				foreach ($abilities as $ab) {
					$tools[] = [
						'name'        => str_replace(['dragwyb/', '/'], ['', '_'], $ab['id']),
						'description' => $ab['description'],
						'inputSchema' => $ab['parameters'],
					];
				}
				return new \WP_REST_Response([
					'jsonrpc' => '2.0',
					'id'      => $id,
					'result'  => ['tools' => $tools],
				], 200);

			case 'tools/call':
				$tool_name = sanitize_text_field($params['name'] ?? '');
				$args      = is_array($params['arguments'] ?? null) ? $params['arguments'] : [];
				$result    = DCTC_AI_Abilities::execute_ability($tool_name, $args);

				return new \WP_REST_Response([
					'jsonrpc' => '2.0',
					'id'      => $id,
					'result'  => [
						'content' => [
							[
								'type' => 'text',
								'text' => wp_json_encode($result, JSON_PRETTY_PRINT),
							],
						],
						'isError' => empty($result['success']),
					],
				], 200);

			case 'resources/list':
				return new \WP_REST_Response([
					'jsonrpc' => '2.0',
					'id'      => $id,
					'result'  => [
						'resources' => [
							[
								'uri'         => 'wordpress://site/info',
								'name'        => 'WordPress Site Information',
								'description' => 'Current site title, description, and base URL',
								'mimeType'    => 'application/json',
							],
						],
					],
				], 200);

			case 'resources/read':
				$uri = sanitize_text_field($params['uri'] ?? '');
				if ($uri === 'wordpress://site/info') {
					$site_info = [
						'name'        => get_bloginfo('name'),
						'url'         => home_url(),
						'description' => get_bloginfo('description'),
					];
					return new \WP_REST_Response([
						'jsonrpc' => '2.0',
						'id'      => $id,
						'result'  => [
							'contents' => [
								[
									'uri'      => $uri,
									'mimeType' => 'application/json',
									'text'     => wp_json_encode($site_info, JSON_PRETTY_PRINT),
								],
							],
						],
					], 200);
				}
				return new \WP_REST_Response([
					'jsonrpc' => '2.0',
					'id'      => $id,
					'error'   => [
						'code'    => -32602,
						'message' => 'Resource URI not found',
					],
				], 404);

			default:
				return new \WP_REST_Response([
					'jsonrpc' => '2.0',
					'id'      => $id,
					'error'   => [
						'code'    => -32601,
						'message' => sprintf('Method not found: %s', esc_html($method)),
					],
				], 404);
		}
	}
}

