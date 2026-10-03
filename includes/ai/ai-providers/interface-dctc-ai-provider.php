<?php
/**
 * DCTC AI Provider Interface
 *
 * Contract for all AI provider adapters (OpenAI, Google Gemini, Anthropic, OpenRouter, Groq, DeepSeek).
 *
 * @package Dragwyb_Click_To_Chat
 */

if (!defined('ABSPATH')) {
	exit;
}

interface DCTC_AI_Provider_Interface
{
	/**
	 * Get the provider identifier (e.g. 'openai', 'google', 'anthropic', etc.)
	 *
	 * @return string
	 */
	public function get_id();

	/**
	 * Get the provider human-readable name.
	 *
	 * @return string
	 */
	public function get_name();

	/**
	 * Generate a chat completion.
	 *
	 * @param string $prompt User message or latest prompt.
	 * @param string $system_message System instruction.
	 * @param string $model Model identifier.
	 * @param array  $options Additional options (temperature, max_tokens, etc.).
	 * @return string Generated response text.
	 * @throws \Exception On failure.
	 */
	public function chat($prompt, $system_message, $model, array $options = []);

	/**
	 * Generate embeddings for an array of texts.
	 *
	 * @param array  $texts Array of text strings.
	 * @param string $model Embedding model identifier.
	 * @return array Array of float vectors.
	 * @throws \Exception On failure.
	 */
	public function embed(array $texts, $model = '');

	/**
	 * Get available models for this provider.
	 *
	 * @return array Map of model ID => Model Display Name.
	 */
	public function get_models();

	/**
	 * Validate an API key by making a test request or listing models.
	 *
	 * @param string $api_key Key to validate.
	 * @return bool|\WP_Error True if valid, WP_Error otherwise.
	 */
	public function validate_key($api_key);
}
