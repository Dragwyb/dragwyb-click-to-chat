<?php
/**
 * DCTC AI Provider: Google Gemini
 *
 * Adapter for Google Gemini GenerateContent & Embeddings APIs.
 *
 * @package Dragwyb_Click_To_Chat
 */

if (!defined('ABSPATH')) {
	exit;
}

require_once __DIR__ . '/class-dctc-ai-provider-base.php';

class DCTC_AI_Provider_Google extends DCTC_AI_Provider_Base
{
	public function get_id()
	{
		return 'google';
	}

	public function get_name()
	{
		return 'Google Gemini';
	}

	public function chat($prompt, $system_message, $model, array $options = [])
	{
		$key = $this->get_api_key();
		if (empty($key)) {
			throw new \Exception(esc_html__('Google Gemini API key is missing.', 'dragwyb-click-to-chat'));
		}

		$model = !empty($model) ? $model : 'gemini-2.5-flash';
		// Normalize model id if needed
		if (strpos($model, 'models/') === 0) {
			$model = substr($model, 7);
		}

		$temperature = isset($options['temperature']) ? floatval($options['temperature']) : 0.7;
		$max_tokens  = isset($options['max_tokens']) ? intval($options['max_tokens']) : 500;

		// Use WP AI Client if available
		if (class_exists('\WordPress\AiClient\AiClient')) {
			try {
				$registry = \WordPress\AiClient\AiClient::defaultRegistry();
				$auth = new \WordPress\AiClient\Providers\Http\DTO\ApiKeyRequestAuthentication($key);
				$registry->setProviderRequestAuthentication('google', $auth);

				$model_obj = null;
				try {
					$model_obj = $registry->getProviderModel('google', $model);
				} catch (\Throwable $t) {
					// Fallback
				}

				$builder = \WordPress\AiClient\AiClient::prompt(trim($prompt));
				$builder->usingRequestOptions(
					\WordPress\AiClient\Providers\Http\DTO\RequestOptions::fromArray([
						\WordPress\AiClient\Providers\Http\DTO\RequestOptions::KEY_TIMEOUT => 60,
					])
				);

				if ($model_obj) {
					$builder->usingModel($model_obj);
				} else {
					$builder->usingProvider('google');
				}

				$result = $builder
					->usingSystemInstruction($system_message)
					->usingTemperature($temperature)
					->usingMaxTokens($max_tokens)
					->generateTextResult();

				$text = trim($result->toText());
				if (!empty($text)) {
					return $text;
				}
			} catch (\Throwable $e) {
				// Fall back to direct REST API
			}
		}

		// Direct Google Generative Language REST API
		$url = sprintf(
			'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s',
			rawurlencode($model),
			rawurlencode($key)
		);

		$attachments = isset($options['attachments']) && is_array($options['attachments']) ? $options['attachments'] : [];
		$user_parts = [];
		if (!empty($prompt)) {
			$user_parts[] = ['text' => trim($prompt)];
		}
		if (!empty($attachments)) {
			foreach ($attachments as $att) {
				if (!empty($att['type']) && $att['type'] === 'image' && !empty($att['url'])) {
					$att_id = !empty($att['attachmentId']) ? intval($att['attachmentId']) : 0;
					$file_path = $att_id ? get_attached_file($att_id) : '';
					if ($file_path && file_exists($file_path)) {
						// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
						$image_data = base64_encode(file_get_contents($file_path));
						$mime_type = !empty($att['mime']) ? $att['mime'] : 'image/jpeg';
						$user_parts[] = [
							'inline_data' => [
								'mime_type' => $mime_type,
								'data'      => $image_data,
							],
						];
					} else {
						$user_parts[] = [
							'text' => sprintf('[Attached Image: %s - %s]', sanitize_file_name($att['name']), esc_url_raw($att['url'])),
						];
					}
				} elseif (!empty($att['name'])) {
					$user_parts[] = [
						'text' => sprintf('[Attached File: %s (%s) - %s]', sanitize_file_name($att['name']), sanitize_text_field($att['mime'] ?? ''), esc_url_raw($att['url'] ?? '')),
					];
				}
			}
		}
		if (empty($user_parts)) {
			$user_parts[] = ['text' => 'Analyze the provided content.'];
		}

		$payload = [
			'contents' => [
				[
					'role'  => 'user',
					'parts' => $user_parts,
				],
			],
			'generationConfig' => [
				'temperature'     => $temperature,
				'maxOutputTokens' => $max_tokens,
			],
		];

		if (!empty($system_message)) {
			$payload['systemInstruction'] = [
				'parts' => [
					['text' => $system_message],
				],
			];
		}

		$response = $this->http_post($url, $payload, []);

		if (isset($response['candidates'][0]['content']['parts'][0]['text'])) {
			return trim($response['candidates'][0]['content']['parts'][0]['text']);
		}

		throw new \Exception(esc_html__('Google Gemini returned an empty response.', 'dragwyb-click-to-chat'));
	}

	public function embed(array $texts, $model = '')
	{
		$key = $this->get_api_key();
		if (empty($key)) {
			throw new \Exception(esc_html__('Google Gemini API key is missing.', 'dragwyb-click-to-chat'));
		}

		$model = !empty($model) ? $model : 'text-embedding-004';
		if (strpos($model, 'models/') === 0) {
			$model = substr($model, 7);
		}

		$url = sprintf(
			'https://generativelanguage.googleapis.com/v1beta/models/%s:batchEmbedContents?key=%s',
			rawurlencode($model),
			rawurlencode($key)
		);

		$requests = [];
		foreach ($texts as $text) {
			$requests[] = [
				'model'   => 'models/' . $model,
				'content' => [
					'parts' => [
						['text' => $text],
					],
				],
			];
		}

		$response = $this->http_post($url, ['requests' => $requests], []);

		if (isset($response['embeddings']) && is_array($response['embeddings'])) {
			$vectors = [];
			foreach ($response['embeddings'] as $emb) {
				if (isset($emb['values'])) {
					$vectors[] = $emb['values'];
				}
			}
			return $vectors;
		}

		throw new \Exception(esc_html__('Failed to generate embeddings with Google Gemini.', 'dragwyb-click-to-chat'));
	}

	public function get_models()
	{
		$key = $this->get_api_key();
		if (empty($key)) {
			return $this->get_static_models();
		}

		$cache_key = 'dctc_ai_models_google_' . md5($key);
		$cached = get_transient($cache_key);
		if (false !== $cached && is_array($cached)) {
			return $cached;
		}

		try {
			$url = sprintf('https://generativelanguage.googleapis.com/v1beta/models?key=%s', rawurlencode($key));
			$response = $this->http_get($url, [], 15);

			if (isset($response['models']) && is_array($response['models'])) {
				$models = [];
				foreach ($response['models'] as $m) {
					if (isset($m['supportedGenerationMethods']) && in_array('generateContent', $m['supportedGenerationMethods'], true)) {
						$raw_name = isset($m['name']) ? $m['name'] : '';
						$clean_id = str_replace('models/', '', $raw_name);
						$display  = isset($m['displayName']) ? $m['displayName'] : $clean_id;
						$models[$clean_id] = $display;
					}
				}
				if (!empty($models)) {
					set_transient($cache_key, $models, HOUR_IN_SECONDS);
					return $models;
				}
			}
		} catch (\Throwable $e) {
			// Fall through
		}

		$models = $this->get_static_models();
		set_transient($cache_key, $models, HOUR_IN_SECONDS);
		return $models;
	}

	public function validate_key($api_key)
	{
		if (empty($api_key)) {
			return new \WP_Error('empty_key', __('Google Gemini API key cannot be empty.', 'dragwyb-click-to-chat'));
		}

		try {
			$url = sprintf('https://generativelanguage.googleapis.com/v1beta/models?key=%s', rawurlencode($api_key));
			$response = $this->http_get($url, [], 15);

			if (isset($response['models']) && is_array($response['models'])) {
				return true;
			}

			return new \WP_Error('invalid_key', __('Invalid Google Gemini API key.', 'dragwyb-click-to-chat'));
		} catch (\Exception $e) {
			return new \WP_Error('api_error', $e->getMessage());
		}
	}

	private function get_static_models()
	{
		return [
			'gemini-2.5-flash' => 'Gemini 2.5 Flash (Ultra Fast & Smart)',
			'gemini-2.5-pro'   => 'Gemini 2.5 Pro (Deep Reasoning)',
			'gemini-1.5-flash' => 'Gemini 1.5 Flash',
			'gemini-1.5-pro'   => 'Gemini 1.5 Pro',
		];
	}
}
