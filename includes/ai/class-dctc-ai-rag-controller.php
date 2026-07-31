<?php
/**
 * DCTC AI RAG Controller
 *
 * Owns everything RAG-related: the admin REST endpoints for configuring and
 * triggering indexing, and building RAG-retrieved context for chat prompts.
 *
 * @package Dragwyb_Click_To_Chat
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Class DCTC_AI_RAG_Controller
 */
class DCTC_AI_RAG_Controller
{
	use DCTC_AI_REST_Helpers;

	/**
	 * REST callback: list public post types with published counts, for the
	 * "which content to index" picker.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return \WP_REST_Response
	 */
	public function get_post_types($request)
	{
		$post_types = get_post_types(['public' => true, 'exclude_from_search' => false], 'objects');
		$types = [];

		foreach ($post_types as $post_type) {
			if ('attachment' !== $post_type->name) {
				$count = wp_count_posts($post_type->name);
				$published = isset($count->publish) ? (int) $count->publish : 0;

				if ($published > 0) {
					$types[] = [
						'value' => $post_type->name,
						'label' => $post_type->label,
						'count' => $published,
					];
				}
			}
		}

		return new \WP_REST_Response(['types' => $types], 200);
	}

	/**
	 * Check whether a submitted host resolves to a public, external
	 * address rather than a private/loopback/link-local one.
	 *
	 * Used to stop an admin-configurable "host" field (e.g. Pinecone) from
	 * being usable as an SSRF vector to make this server issue
	 * authenticated requests into its own internal network.
	 *
	 * @param string $host Raw host/URL as submitted by the admin.
	 * @return bool True if the host is a safe, public target.
	 */
	private function is_safe_external_host($host)
	{
		$parsed = wp_parse_url($host);
		$hostname = isset($parsed['host']) ? strtolower($parsed['host']) : '';

		if (empty($hostname)) {
			// Allow bare hostnames without a scheme (e.g. index-xxx.svc....pinecone.io).
			$hostname = strtolower(preg_replace('#^https?://#i', '', $host));
			$hostname = explode('/', $hostname)[0];
			$hostname = explode(':', $hostname)[0];
		}

		if (empty($hostname)) {
			return false;
		}

		// Positive allowlist: only public *.pinecone.io hosts.
		$is_pinecone = ('pinecone.io' === $hostname || substr($hostname, -strlen('.pinecone.io')) === '.pinecone.io');
		if (!$is_pinecone) {
			return false;
		}

		$ip = filter_var($hostname, FILTER_VALIDATE_IP) ? $hostname : gethostbyname($hostname);

		if (!filter_var($ip, FILTER_VALIDATE_IP)) {
			return false;
		}

		return false !== filter_var(
			$ip,
			FILTER_VALIDATE_IP,
			FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
		);
	}

	/**
	 * Mask secrets in vector_db before returning settings over REST.
	 *
	 * @param array $vector_db Vector DB config.
	 * @return array
	 */
	private function sanitize_vector_db_for_response($vector_db)
	{
		if (!is_array($vector_db)) {
			return [];
		}

		if (!empty($vector_db['api_key']) && is_string($vector_db['api_key'])) {
			$key = $vector_db['api_key'];
			if (strlen($key) < 8) {
				$vector_db['api_key'] = '********';
			} else {
				$vector_db['api_key'] = substr($key, 0, 4) . '...' . substr($key, -4);
			}
		}

		return $vector_db;
	}

	/**
	 * Sanitize vector_db input from the admin.
	 *
	 * @param array $vector_db Raw vector DB config.
	 * @param array $existing  Previously saved vector DB config.
	 * @return array
	 */
	private function sanitize_vector_db_input($vector_db, $existing = [])
	{
		$sanitized = [
			'provider' => isset($vector_db['provider'])
				? sanitize_key($vector_db['provider'])
				: (isset($existing['provider']) ? sanitize_key($existing['provider']) : 'sqlite'),
		];

		if (!empty($vector_db['api_key'])) {
			$api_key = sanitize_text_field($vector_db['api_key']);
			// Keep existing key when the client sends a masked placeholder.
			if (false !== strpos($api_key, '...') || '********' === $api_key) {
				$sanitized['api_key'] = isset($existing['api_key']) ? $existing['api_key'] : '';
			} else {
				$sanitized['api_key'] = $api_key;
			}
		} elseif (isset($existing['api_key'])) {
			$sanitized['api_key'] = $existing['api_key'];
		}

		if (isset($vector_db['host'])) {
			$host = trim((string) $vector_db['host']);
			if (preg_match('#^https?://#i', $host)) {
				$sanitized['host'] = esc_url_raw($host);
			} else {
				$sanitized['host'] = sanitize_text_field($host);
			}
		} elseif (isset($existing['host'])) {
			$sanitized['host'] = $existing['host'];
		}

		if (isset($vector_db['index_name'])) {
			$sanitized['index_name'] = sanitize_text_field($vector_db['index_name']);
		} elseif (isset($existing['index_name'])) {
			$sanitized['index_name'] = $existing['index_name'];
		}

		if (isset($vector_db['environment'])) {
			$sanitized['environment'] = sanitize_text_field($vector_db['environment']);
		} elseif (isset($existing['environment'])) {
			$sanitized['environment'] = $existing['environment'];
		}

		return $sanitized;
	}

	/**
	 * REST callback: save RAG configuration settings.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return \WP_REST_Response
	 */
	public function save_settings($request)
	{
		$params = $request->get_json_params();

		$settings = DCTC_AI_Settings_Handler::dctc_ai_get_all_settings();
		$existing_rag = isset($settings['rag']) ? $settings['rag'] : [];
		$existing_embeddings = isset($existing_rag['embeddings']) ? $existing_rag['embeddings'] : [];
		$existing_vector_db = isset($existing_rag['vector_db']) && is_array($existing_rag['vector_db'])
			? $existing_rag['vector_db']
			: ['provider' => 'sqlite'];

		$raw_post_types = isset($params['post_types'])
			? (array) $params['post_types']
			: (isset($existing_rag['post_types']) ? $existing_rag['post_types'] : ['post', 'page']);

		$post_types = [];
		foreach ($raw_post_types as $post_type) {
			$key = sanitize_key($post_type);
			if ('' !== $key && post_type_exists($key)) {
				$post_types[] = $key;
			}
		}
		$post_types = array_values(array_unique($post_types));
		if (empty($post_types)) {
			$post_types = ['post', 'page'];
		}

		$chunk_size = isset($params['chunk_size'])
			? (int) $params['chunk_size']
			: (isset($existing_rag['chunk_size']) ? (int) $existing_rag['chunk_size'] : 1000);

		$max_results = isset($params['max_results'])
			? (int) $params['max_results']
			: (isset($existing_rag['max_results']) ? (int) $existing_rag['max_results'] : 5);

		$auto_index = isset($params['auto_index'])
			? (bool) $params['auto_index']
			: (isset($existing_rag['auto_index']) ? (bool) $existing_rag['auto_index'] : true);

		$vector_db = isset($params['vector_db'])
			? $this->sanitize_vector_db_input((array) $params['vector_db'], $existing_vector_db)
			: $this->sanitize_vector_db_input($existing_vector_db, $existing_vector_db);

		$require_indexed_data = isset($params['require_indexed_data'])
			? (bool) $params['require_indexed_data']
			: (isset($existing_rag['require_indexed_data']) ? (bool) $existing_rag['require_indexed_data'] : false);

		$no_data_message = isset($params['no_data_message'])
			? sanitize_textarea_field($params['no_data_message'])
			: (isset($existing_rag['no_data_message']) ? $existing_rag['no_data_message'] : 'I don\'t have information about your question in my knowledge base.');

		$settings['rag'] = [
			'enabled' => true,
			'post_types' => $post_types,
			'chunk_size' => $chunk_size,
			'max_results' => $max_results,
			'auto_index' => $auto_index,
			'vector_db' => $vector_db,
			'require_indexed_data' => $require_indexed_data,
			'no_data_message' => $no_data_message,
			'indexing' => [
				'chunk_size' => $chunk_size,
				'chunk_overlap' => isset($params['chunk_overlap'])
					? (int) $params['chunk_overlap']
					: 100,
				'auto_update' => isset($params['auto_update'])
					? (bool) $params['auto_update']
					: true,
			],
			'embeddings' => [
				'provider' => isset($params['embeddings']['provider'])
					? sanitize_text_field($params['embeddings']['provider'])
					: (isset($params['embedding_provider']) ? sanitize_text_field($params['embedding_provider']) : (isset($existing_embeddings['provider']) ? $existing_embeddings['provider'] : '')),
				'model' => isset($params['embeddings']['model'])
					? sanitize_text_field($params['embeddings']['model'])
					: (isset($params['embedding_model']) ? sanitize_text_field($params['embedding_model']) : (isset($existing_embeddings['model']) ? $existing_embeddings['model'] : '')),
			],
		];

		/*
		 * Test Pinecone connection immediately before saving settings
		 */
		if (
			!empty($vector_db['provider']) &&
			$vector_db['provider'] === 'pinecone' &&
			!empty($vector_db['api_key'])
		) {

			// Pinecone is a cloud-only service — a legitimate host is
			// always a public *.pinecone.io address, never a private or
			// loopback one. Reject anything else so this field can't be
			// used to make the server issue authenticated requests into
			// its own internal network (SSRF).
			if (!empty($vector_db['host']) && !$this->is_safe_external_host($vector_db['host'])) {
				return new \WP_REST_Response(
					[
						'success' => false,
						'message' => esc_html__('Pinecone host must be a public *.pinecone.io address.', 'dragwyb-click-to-chat'),
					],
					400
				);
			}

			try {

				require_once DCTC_PLUGIN_DIR . 'includes/ai/class-dctc-ai-vector-db-pinecone.php';

				$pinecone = new DCTC_AI_Vector_DB_Pinecone(
					$vector_db
				);

				$new_provider = isset($settings['rag']['embeddings']['provider']) ? $settings['rag']['embeddings']['provider'] : 'openai';
				$new_model = isset($settings['rag']['embeddings']['model']) ? $settings['rag']['embeddings']['model'] : '';
				$expected_dimensions = 1536;
				if ($new_provider === 'google') {
					$expected_dimensions = 768;
				} elseif ($new_provider === 'local') {
					$expected_dimensions = 1024;
				} elseif ($new_provider === 'openai' && $new_model === 'text-embedding-3-large') {
					$expected_dimensions = 3072;
				}

				$test = $pinecone->test_connection($expected_dimensions);

				if (is_wp_error($test)) {

					return new \WP_REST_Response(
						[
							'success' => false,
							'message' => $test->get_error_message(),
						],
						400
					);
				}

			} catch (Exception $e) {

				return new \WP_REST_Response(
					[
						'success' => false,
						'message' => $e->getMessage(),
					],
					500
				);
			}
		}

		DCTC_AI_Settings_Handler::dctc_ai_persist_settings($settings);

		$rag_response = $settings['rag'];
		$rag_response['vector_db'] = $this->sanitize_vector_db_for_response(
			isset($rag_response['vector_db']) ? $rag_response['vector_db'] : []
		);

		return new \WP_REST_Response(
			[
				'success' => true,
				'message' => esc_html__(
					'RAG settings saved successfully!',
					'dragwyb-click-to-chat'
				),
				'rag' => $rag_response,
			],
			200
		);
	}

	/**
	 * REST callback: RAG indexing statistics for the dashboard.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return \WP_REST_Response
	 */
	public function get_stats($request)
	{
		if (!class_exists('DCTC_AI_RAG_Engine')) {
			return new \WP_REST_Response(
				[
					'total_documents' => 0,
					'total_chunks' => 0,
					'pending_embeddings' => 0,
					'embeddings_completed' => 0,
					'total_tokens' => 0,
					'indexed_post_types' => [],
				],
				200
			);
		}

		try {
			require_once DCTC_PLUGIN_DIR . 'includes/ai/class-dctc-ai-rag-engine.php';
			$rag_engine = DCTC_AI_RAG_Engine::get_instance();
			$stats = $rag_engine->get_retriever()->get_statistics();

			return new \WP_REST_Response($stats, 200);
		} catch (Exception $e) {
			return new \WP_REST_Response(
				[
					'error' => $e->getMessage(),
					'total_documents' => 0,
					'total_chunks' => 0,
					'pending_embeddings' => 0,
					'embeddings_completed' => 0,
					'total_tokens' => 0,
					'indexed_post_types' => [],
				],
				500
			);
		}
	}

	/**
	 * REST callback: queue a background indexing job (see DCTC_AI_RAG_Engine
	 * and DCTC_AI_Indexer for the batch-processing implementation).
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return \WP_REST_Response
	 */
	public function index_content($request)
	{
		if (!class_exists('DCTC_AI_RAG_Engine')) {

			return new \WP_REST_Response(
				[
					'success' => false,
					'error' => esc_html__(
						'RAG Engine not available',
						'dragwyb-click-to-chat'
					),
				],
				400
			);
		}

		try {

			require_once DCTC_PLUGIN_DIR . 'includes/ai/class-dctc-ai-rag-engine.php';

			$settings = DCTC_AI_Settings_Handler::dctc_ai_get_all_settings();

			if (empty($settings['rag']['enabled'])) {

				$settings['rag']['enabled'] = true;

				DCTC_AI_Settings_Handler::dctc_ai_persist_settings($settings);
			}

			$params = $request->get_json_params();

			$post_types = !empty(
				$params['post_types']
			)
				? array_map(
					'sanitize_text_field',
					(array) $params['post_types']
				)
				: ['post', 'page'];
			$index_sources =
				$params['index_sources']
				?? [];

			$vector_provider =
				$settings['rag']['vector_db']['provider']
				?? 'sqlite';

			/*
			 * Validate Pinecone
			 */
			if ('pinecone' === $vector_provider) {

				require_once DCTC_PLUGIN_DIR . 'includes/ai/class-dctc-ai-vector-db-pinecone.php';

				$pinecone =
					new DCTC_AI_Vector_DB_Pinecone(
						$settings['rag']['vector_db']
					);

				$connection =
					$pinecone->test_connection();

				if (is_wp_error($connection)) {

					return new \WP_REST_Response(
						[
							'success' => false,
							'error' =>
								$connection->get_error_message(),
						],
						400
					);
				}
			}

			$rag_engine =
				DCTC_AI_RAG_Engine::get_instance();

			$indexer =
				$rag_engine->get_indexer();

			if (!$indexer) {

				return new \WP_REST_Response(
					[
						'success' => false,
						'error' => 'Indexer not available',
					],
					500
				);
			}

			$queue_result = ['total' => 0];
			$website_queued = false;

			/*
			 * Website content
			 *
			 * Indexing and embedding run in small background batches
			 * (chained via wp_schedule_single_event) instead of looping
			 * over every post and calling the embedding API inline here,
			 * so this request returns immediately instead of risking
			 * max_execution_time or provider rate limits on larger sites.
			 */
			if (
				!empty(
				$index_sources['website']
			)
			) {

				$queue_result =
					$rag_engine->start_index_job(
						$post_types
					);

				$website_queued = true;
			}

			/*
			 * Direct Knowledge Text
			 */
			if (
				!empty(
				$index_sources['knowledge_text']
			)
			) {

				do_action(
					'dctc_ai_index_knowledge_text'
				);
			}

			/*
			 * Knowledge URLs
			 */
			if (
				!empty(
				$index_sources['urls']
			)
			) {

				do_action(
					'dctc_ai_index_urls'
				);
			}

			/*
			 * Training Files
			 */
			if (
				!empty(
				$index_sources['files']
			)
			) {

				do_action(
					'dctc_ai_index_training_files'
				);
			}

			$queued_count = (int) ($queue_result['total'] ?? 0);
			$reason = $queue_result['reason'] ?? '';

			if (!$website_queued) {
				$result_message = esc_html__(
					'Indexing request received.',
					'dragwyb-click-to-chat'
				);
				$result_status = 'completed';
			} elseif ($queued_count > 0) {
				$result_message = sprintf(
					/* translators: %d: number of posts queued for indexing */
					esc_html__(
						'Indexing started in the background for %d item(s).',
						'dragwyb-click-to-chat'
					),
					$queued_count
				);
				$result_status = 'queued';
				if (function_exists('spawn_cron')) {
					spawn_cron();
				}
			} elseif ('no_published_posts' === $reason || 'invalid_post_types' === $reason) {
				$result_message = esc_html__(
					'No published posts found for the selected content types. Publish some posts/pages (or pick types that have content), then try again.',
					'dragwyb-click-to-chat'
				);
				$result_status = 'completed';
			} elseif ('already_up_to_date' === $reason) {
				$result_message = esc_html__(
					'Website content is already up to date — nothing new to index. Edit a post or add content, then index again.',
					'dragwyb-click-to-chat'
				);
				$result_status = 'completed';
			} else {
				$result_message = esc_html__(
					'No items were queued for indexing.',
					'dragwyb-click-to-chat'
				);
				$result_status = 'completed';
			}

			return new \WP_REST_Response(
				[
					'success' => true,
					'provider' => $vector_provider,
					'status' => $result_status,
					'queued' => $queued_count,
					'candidates' => (int) ($queue_result['candidates'] ?? 0),
					'reason' => $reason,
					'message' => $result_message,
				],
				200
			);

		} catch (Exception $e) {
			return new \WP_REST_Response(
				[
					'success' => false,
					'error' => $e->getMessage(),
				],
				500
			);
		}
	}

	/**
	 * REST callback: progress of the current/most recent background
	 * indexing job, polled by the dashboard while one is running.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return \WP_REST_Response
	 */
	public function get_index_status($request)
	{
		if (!class_exists('DCTC_AI_RAG_Engine')) {
			return new \WP_REST_Response(['status' => 'idle'], 200);
		}

		try {
			require_once DCTC_PLUGIN_DIR . 'includes/ai/class-dctc-ai-rag-engine.php';
			$rag_engine = DCTC_AI_RAG_Engine::get_instance();

			return new \WP_REST_Response($rag_engine->get_index_progress(), 200);
		} catch (Exception $e) {
			return new \WP_REST_Response(
				[
					'status' => 'idle',
					'error' => $e->getMessage(),
				],
				500
			);
		}
	}

	/**
	 * Get RAG context for a chat prompt, and whether the site is configured
	 * to refuse to answer when no indexed data matches.
	 *
	 * @param string $prompt     User prompt.
	 * @param string $session_id Session ID for follow-up-question context.
	 * @param array  $bot        Chatbot settings (currently unused, kept for
	 *                           interface symmetry with other context builders).
	 * @param array  $settings   Plugin settings.
	 * @return array {
	 *     @type string $context               Formatted context, or ''.
	 *     @type array  $links                 Reference links extracted from the context.
	 *     @type bool   $require_data_missing  True if no context was found and the site
	 *                                         requires indexed data before answering.
	 *     @type string $message               Message to show when require_data_missing is true.
	 * }
	 */
	public function get_chat_context($prompt, $session_id, $bot, $settings)
	{
		$result = [
			'context' => '',
			'links' => [],
			'require_data_missing' => false,
			'message' => '',
		];

		if (!class_exists('DCTC_AI_RAG_Engine')) {
			require_once DCTC_PLUGIN_DIR . 'includes/ai/class-dctc-ai-rag-engine.php';
		}

		$rag_engine = DCTC_AI_RAG_Engine::get_instance();

		if (!$rag_engine->is_enabled()) {
			return $result;
		}

		// Get RAG context
		$rag_data = $this->get_context_with_links($prompt, $session_id);
		$result['context'] = $rag_data['context'];
		$result['links'] = $rag_data['links'];

		// Check if indexed data is required
		$rag_settings = !empty($settings['rag']) ? $settings['rag'] : [];
		$require_indexed_data = !empty($rag_settings['require_indexed_data']);
		$no_data_message = isset($rag_settings['no_data_message']) ? $rag_settings['no_data_message'] : '';

		if ($require_indexed_data && empty($result['context'])) {
			$result['require_data_missing'] = true;
			$result['message'] = !empty($no_data_message)
				? $no_data_message
				: '❌ ' . esc_html__('I don\'t have information about your question in my knowledge base.', 'dragwyb-click-to-chat');
		}

		return $result;
	}

	/**
	 * Retrieve context and extract reference links for frontend display.
	 *
	 * @param string $query      User query to search for.
	 * @param string $session_id Session ID for context.
	 * @return array Array with 'context' and 'links' keys.
	 */
	private function get_context_with_links($query, $session_id = '')
	{
		$context = $this->get_context($query, $session_id);
		$links = [];

		// Extract links from context using regex
		if (!empty($context)) {
			preg_match_all('/👉\s+(.+?):\s+(https?:\/\/[^\s\n]+)/i', $context, $matches, PREG_SET_ORDER);
			if (!empty($matches)) {
				foreach ($matches as $match) {
					$links[] = [
						'title' => trim($match[1]),
						'url' => esc_url($match[2]),
					];
				}
			}
		}

		return [
			'context' => $context,
			'links' => $links,
		];
	}

	/**
	 * Retrieve relevant documents from the RAG system, if enabled.
	 *
	 * @param string $query      User query to search for.
	 * @param string $session_id Session ID for context.
	 * @return string Formatted context string or empty if RAG disabled.
	 */
	private function get_context($query, $session_id = '')
	{
		if (!class_exists('DCTC_AI_RAG_Engine')) {
			return '';
		}

		try {

			require_once DCTC_PLUGIN_DIR . 'includes/ai/class-dctc-ai-rag-engine.php';

			$rag_engine = DCTC_AI_RAG_Engine::get_instance();

			if (!$rag_engine->is_enabled()) {
				return '';
			}

			/*
			 * Detect follow-up questions. Only phrases that specifically
			 * mean "continue/expand on what we were just discussing" — not
			 * generic question words like "how"/"why"/"when", which are
			 * how most brand-new questions start and would otherwise get
			 * misrouted to search using the previous topic instead of the
			 * user's actual new question.
			 */
			$followup_keywords = [
				'more',
				'details',
				'detail',
				'tell me more',
				'explain more',
				'continue',
				'elaborate',
				'full details',
				'more information',
				'what about',
				'can you explain',
			];

			$query_lower = strtolower(trim($query));

			$asking_for_more = false;

			foreach ($followup_keywords as $keyword) {

				if (strpos($query_lower, $keyword) !== false) {
					$asking_for_more = true;
					break;
				}
			}

			/*
			 * If user asks a follow-up question,
			 * search using previous topic
			 */
			$search_query = $query;

			if ($asking_for_more && !empty($session_id)) {

				$previous_question = $this->get_previous_user_message(
					$session_id
				);

				if (!empty($previous_question)) {
					$search_query = $previous_question;
				}
			}

			/*
			 * Retrieve more results for follow-up questions
			 */
			$limit = $asking_for_more ? 10 : 5;

			$documents = $rag_engine->retrieve_context(
				$search_query,
				$limit
			);

			if (empty($documents)) {
				return '';
			}

			$context = "📚 KNOWLEDGE BASE CONTENT\n\n";

			$read_more_links = [];

			$index = 1;

			foreach ($documents as $doc) {

				if (empty($doc['content'])) {
					continue;
				}

				$title = !empty($doc['title'])
					? wp_strip_all_tags($doc['title'])
					: 'Knowledge Article';

				$context .= "[{$index}] {$title}\n";

				$context .= trim(
					wp_strip_all_tags($doc['content'])
				);

				$context .= "\n\n";

				if (
					!empty($doc['url']) &&
					filter_var($doc['url'], FILTER_VALIDATE_URL)
				) {

					$read_more_links[] = [
						'title' => $title,
						'url' => esc_url($doc['url']),
					];
				}

				$index++;
			}

			/*
			 * Add links section
			 */
			if (!empty($read_more_links)) {

				$context .= "RELATED LINKS:\n";

				foreach ($read_more_links as $link) {

					$context .= '- '
						. $link['title']
						. ': '
						. $link['url']
						. "\n";
				}
			}

			/*
			 * Important instruction for AI
			 */
			$context .= "\n\nAI INSTRUCTIONS:\n";
			$context .= "- Use the information above to answer.\n";
			$context .= "- If user asks for more details, expand the previous topic.\n";
			$context .= "- Do not ask what topic they mean if conversation history exists.\n";
			$context .= "- Continue the discussion naturally.\n";
			$context .= "- Give examples when possible.\n";

			return trim($context);

		} catch (Exception $e) {
			self::log_debug('Dragwyb AI AI Memory Formatter Error: ' . $e->getMessage());
			return '';
		}
	}

	/**
	 * Get Previous User Message from Session
	 *
	 * @param string $session_id Session ID.
	 * @return string Previous user message or empty string
	 */
	private function get_previous_user_message($session_id)
	{
		global $wpdb;

		if (empty($session_id)) {
			return '';
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

		if (!is_array($messages) || empty($messages)) {
			return '';
		}

		// Same reasoning as the follow-up keyword list in get_context():
		// only phrases that specifically mean "continue the previous
		// topic," not generic question words that any brand-new question
		// could start with.
		$followup_phrases = [
			'more',
			'more details',
			'give me more details',
			'tell me more',
			'explain more',
			'continue',
			'elaborate',
			'what about that',
			'what about this',
			'can you explain',
		];

		/*
		 * Search backwards through messages
		 */
		for ($i = count($messages) - 1; $i >= 0; $i--) {

			if (
				!isset($messages[$i]['role']) ||
				$messages[$i]['role'] !== 'user'
			) {
				continue;
			}

			$content = trim($messages[$i]['content']);

			if (empty($content)) {
				continue;
			}

			$content_lower = strtolower($content);

			$is_followup = false;

			foreach ($followup_phrases as $phrase) {

				if (
					$content_lower === $phrase ||
					strpos($content_lower, $phrase) !== false
				) {
					$is_followup = true;
					break;
				}
			}

			/*
			 * Return first real topic question
			 */
			if (!$is_followup) {
				return $content;
			}
		}

		return '';
	}
}
