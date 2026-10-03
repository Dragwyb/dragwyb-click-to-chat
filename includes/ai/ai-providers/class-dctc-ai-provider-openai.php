<?php
/**
 * DCTC AI Provider: OpenAI
 *
 * Adapter for OpenAI Chat Completions & Embeddings APIs.
 *
 * @package Dragwyb_Click_To_Chat
 */

if (!defined('ABSPATH')) {
	exit;
}

require_once __DIR__ . '/class-dctc-ai-provider-base.php';

class DCTC_AI_Provider_OpenAI extends DCTC_AI_Provider_Base
{
	public function get_id()
	{
		return 'openai';
	}

	public function get_name()
	{
		return 'OpenAI';
	}

	public function chat($prompt, $system_message, $model, array $options = [])
	{
		$key = $this->get_api_key();
		if (empty($key)) {
			throw new \Exception(esc_html__('OpenAI API key is missing.', 'dragwyb-click-to-chat'));
		}

		$model = !empty($model) ? $model : 'gpt-4o-mini';
		$temperature = isset($options['temperature']) ? floatval($options['temperature']) : 0.7;
		$max_tokens  = isset($options['max_tokens']) ? intval($options['max_tokens']) : 500;

		// Use WP AI Client if available, else direct HTTP.
		if (class_exists('\WordPress\AiClient\AiClient')) {
			try {
				$registry = \WordPress\AiClient\AiClient::defaultRegistry();
				$auth = new \WordPress\AiClient\Providers\Http\DTO\ApiKeyRequestAuthentication($key);
				$registry->setProviderRequestAuthentication('openai', $auth);

				$model_obj = null;
				try {
					$model_obj = $registry->getProviderModel('openai', $model);
				} catch (\Throwable $t) {
					// Fallback if model not in directory
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
					$builder->usingProvider('openai');
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
				// Fall back to direct HTTP on SDK exception
			}
		}

		// Direct HTTP fallback
		$messages = [];
		if (!empty($system_message)) {
			$messages[] = ['role' => 'system', 'content' => $system_message];
		}
		$messages[] = ['role' => 'user', 'content' => trim($prompt)];

		$payload = [
			'model'       => $model,
			'messages'    => $messages,
			'temperature' => $temperature,
			'max_tokens'  => $max_tokens,
		];

		$response = $this->http_post(
			'https://api.openai.com/v1/chat/completions',
			$payload,
			['Authorization' => 'Bearer ' . $key]
		);

		if (isset($response['choices'][0]['message']['content'])) {
			return trim($response['choices'][0]['message']['content']);
		}

		throw new \Exception(esc_html__('OpenAI returned an empty response.', 'dragwyb-click-to-chat'));
	}

	public function embed(array $texts, $model = '')
	{
		$key = $this->get_api_key();
		if (empty($key)) {
			throw new \Exception(esc_html__('OpenAI API key is missing.', 'dragwyb-click-to-chat'));
		}

		$model = !empty($model) ? $model : 'text-embedding-3-small';

		$payload = [
			'model' => $model,
			'input' => $texts,
		];

		$response = $this->http_post(
			'https://api.openai.com/v1/embeddings',
			$payload,
			['Authorization' => 'Bearer ' . $key]
		);

		if (isset($response['data']) && is_array($response['data'])) {
			$vectors = [];
			foreach ($response['data'] as $item) {
				if (isset($item['embedding'])) {
					$vectors[] = $item['embedding'];
				}
			}
			return $vectors;
		}

		throw new \Exception(esc_html__('Failed to generate embeddings with OpenAI.', 'dragwyb-click-to-chat'));
	}

	public function get_models()
	{
		$key = $this->get_api_key();
		if (empty($key)) {
			return $this->get_static_models();
		}

		$cache_key = 'dctc_ai_models_openai_' . md5($key);
		$cached = get_transient($cache_key);
		if (false !== $cached && is_array($cached)) {
			return $cached;
		}

		if (class_exists('\WordPress\AiClient\AiClient')) {
			try {
				$registry = \WordPress\AiClient\AiClient::defaultRegistry();
				$className = $registry->getProviderClassName('openai');
				$auth = new \WordPress\AiClient\Providers\Http\DTO\ApiKeyRequestAuthentication($key);
				$registry->setProviderRequestAuthentication('openai', $auth);

				$modelDirectory = $className::modelMetadataDirectory();
				$models = [];
				foreach ($modelDirectory->listModelMetadata() as $m) {
					$models[$m->getId()] = $m->getName();
				}
				if (!empty($models)) {
					set_transient($cache_key, $models, HOUR_IN_SECONDS);
					return $models;
				}
			} catch (\Throwable $e) {
				// Fall through to static
			}
		}

		$models = $this->get_static_models();
		set_transient($cache_key, $models, HOUR_IN_SECONDS);
		return $models;
	}

	public function validate_key($api_key)
	{
		if (empty($api_key)) {
			return new \WP_Error('empty_key', __('OpenAI API key cannot be empty.', 'dragwyb-click-to-chat'));
		}

		try {
			$response = $this->http_get('https://api.openai.com/v1/models', [
				'Authorization' => 'Bearer ' . $api_key,
			], 15);

			if (isset($response['data']) && is_array($response['data'])) {
				return true;
			}

			return new \WP_Error('invalid_key', __('Invalid OpenAI API key.', 'dragwyb-click-to-chat'));
		} catch (\Exception $e) {
			return new \WP_Error('api_error', $e->getMessage());
		}
	}

	private function get_static_models()
	{
		return [
			'gpt-4o-mini'    => 'GPT-4o Mini (Fast & Affordable)',
			'gpt-4o'         => 'GPT-4o (Flagship Omni)',
			'gpt-4.1-turbo'  => 'GPT-4.1 Turbo',
			'o3-mini'        => 'o3 Mini (High Reasoning)',
			'gpt-3.5-turbo'  => 'GPT-3.5 Turbo',
		];
	}
}
