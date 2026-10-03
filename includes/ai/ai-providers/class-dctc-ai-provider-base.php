<?php
/**
 * DCTC AI Provider Base Class
 *
 * Abstract base class providing common HTTP helpers, secure error handling,
 * and standard interfaces for AI provider adapters.
 *
 * @package Dragwyb_Click_To_Chat
 */

if (!defined('ABSPATH')) {
	exit;
}

require_once __DIR__ . '/interface-dctc-ai-provider.php';

abstract class DCTC_AI_Provider_Base implements DCTC_AI_Provider_Interface
{
	/**
	 * Default HTTP request timeout in seconds.
	 */
	const HTTP_TIMEOUT = 60;

	/**
	 * Get the stored API key for this provider.
	 *
	 * @return string
	 */
	public function get_api_key()
	{
		return DCTC_AI_Key_Store::get_provider_key($this->get_id());
	}

	/**
	 * Send an HTTP POST request.
	 *
	 * @param string $url Endpoint URL.
	 * @param array  $body Request payload (will be JSON-encoded).
	 * @param array  $headers HTTP headers.
	 * @param int    $timeout Timeout in seconds.
	 * @return array Decoded response JSON array.
	 * @throws \Exception On HTTP or API error.
	 */
	protected function http_post($url, array $body, array $headers = [], $timeout = self::HTTP_TIMEOUT)
	{
		$default_headers = [
			'Content-Type' => 'application/json',
			'User-Agent'   => 'WordPress/' . get_bloginfo('version') . '; DragwybAIChatbot/' . (defined('DCTC_VERSION') ? DCTC_VERSION : '1.0'),
		];

		$response = wp_remote_post($url, [
			'headers' => array_merge($default_headers, $headers),
			'body'    => wp_json_encode($body),
			'timeout' => $timeout,
		]);

		return $this->handle_response($response, $url);
	}

	/**
	 * Send an HTTP GET request.
	 *
	 * @param string $url Endpoint URL.
	 * @param array  $headers HTTP headers.
	 * @param int    $timeout Timeout in seconds.
	 * @return array Decoded response JSON array.
	 * @throws \Exception On HTTP or API error.
	 */
	protected function http_get($url, array $headers = [], $timeout = 30)
	{
		$default_headers = [
			'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; DragwybAIChatbot/' . (defined('DCTC_VERSION') ? DCTC_VERSION : '1.0'),
		];

		$response = wp_remote_get($url, [
			'headers' => array_merge($default_headers, $headers),
			'timeout' => $timeout,
		]);

		return $this->handle_response($response, $url);
	}

	/**
	 * Handle raw HTTP response, normalize errors, and return decoded JSON.
	 *
	 * @param array|\WP_Error $response wp_remote response.
	 * @param string          $url Request URL for error context.
	 * @return array
	 * @throws \Exception On error.
	 */
	protected function handle_response($response, $url)
	{
		if (is_wp_error($response)) {
			$error_msg = $this->sanitize_error($response->get_error_message());
			throw new \Exception(sprintf(
				/* translators: 1: Provider name, 2: Error message */
				esc_html__('%1$s Network Error: %2$s', 'dragwyb-click-to-chat'),
				esc_html($this->get_name()),
				esc_html($error_msg)
			));
		}

		$status_code = wp_remote_retrieve_response_code($response);
		$body_raw    = wp_remote_retrieve_body($response);
		$data        = json_decode($body_raw, true);

		if ($status_code < 200 || $status_code >= 300) {
			$err_message = $this->extract_error_message($data, $status_code, $body_raw);
			throw new \Exception(sprintf(
				/* translators: 1: Provider name, 2: HTTP status code, 3: Error message */
				esc_html__('%1$s API Error [%2$d]: %3$s', 'dragwyb-click-to-chat'),
				esc_html($this->get_name()),
				intval($status_code),
				esc_html($this->sanitize_error($err_message))
			), intval($status_code));
		}

		if (!is_array($data)) {
			throw new \Exception(sprintf(
				/* translators: %s: Provider name */
				esc_html__('%s returned an unparseable response.', 'dragwyb-click-to-chat'),
				esc_html($this->get_name())
			));
		}

		return $data;
	}

	/**
	 * Extract human-readable error message from provider payload.
	 *
	 * @param array|null $data Parsed JSON body.
	 * @param int        $status HTTP status code.
	 * @param string     $raw Raw body string.
	 * @return string
	 */
	protected function extract_error_message($data, $status, $raw)
	{
		if (is_array($data)) {
			if (isset($data['error']['message']) && is_string($data['error']['message'])) {
				return $data['error']['message'];
			}
			if (isset($data['error']) && is_string($data['error'])) {
				return $data['error'];
			}
			if (isset($data['message']) && is_string($data['message'])) {
				return $data['message'];
			}
		}

		if (401 === $status || 403 === $status) {
			return __('Unauthorized or Invalid API key. Please check your credentials.', 'dragwyb-click-to-chat');
		}

		if (429 === $status) {
			return __('Rate limit reached or quota exceeded. Please check your provider account or try again later.', 'dragwyb-click-to-chat');
		}

		if ($status >= 500) {
			return __('Remote AI service is temporarily unavailable. Please try again later.', 'dragwyb-click-to-chat');
		}

		return !empty($raw) ? substr(wp_strip_all_tags($raw), 0, 200) : __('Unknown API error.', 'dragwyb-click-to-chat');
	}

	/**
	 * Strip any API key or sensitive data from error messages before logging or rendering.
	 *
	 * @param string $text
	 * @return string
	 */
	protected function sanitize_error($text)
	{
		if (empty($text) || !is_string($text)) {
			return '';
		}

		$key = $this->get_api_key();
		if (!empty($key) && strlen($key) >= 6) {
			$text = str_replace($key, '[REDACTED_API_KEY]', $text);
		}

		// Strip common API key patterns (sk-..., AIzaSy..., ant-api-key...)
		$text = preg_replace('/(sk-[a-zA-Z0-9_-]{10,})/i', '[REDACTED_KEY]', $text);
		$text = preg_replace('/(AIzaSy[a-zA-Z0-9_-]{25,})/i', '[REDACTED_KEY]', $text);

		return $text;
	}
}
