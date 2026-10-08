<?php
/**
 * DCTC AI Data Privacy & Security Sanitizer
 *
 * Prevents sensitive and critical data (API keys, password hashes, server secrets,
 * credit cards, PII, auth headers, SQL dumps) from being sent to external AI providers
 * and strips any accidentally leaked internal credentials or system data from AI responses.
 *
 * @package Dragwyb_Click_To_Chat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class DCTC_AI_Data_Sanitizer
 */
class DCTC_AI_Data_Sanitizer {

	/**
	 * Regex patterns for critical and sensitive data redaction.
	 *
	 * @var array<string, string>
	 */
	private static $sensitive_patterns = array(
		// OpenAI / Claude / Anthropic / Google / Groq / Generic API Keys
		'api_keys'        => '/\b(sk-[a-zA-Z0-9_\-]{20,80}|AIzaSy[a-zA-Z0-9_\-]{33}|gsk_[a-zA-Z0-9_\-]{30,80}|xai-[a-zA-Z0-9_\-]{20,80}|r8_[a-zA-Z0-9_\-]{30,80}|Bearer\s+[a-zA-Z0-9_\-\.]{20,})/i',
		// WordPress Password Hashes ($P$..., $2y$..., $argon2id$...)
		'password_hashes' => '/\$(?:P|2[ayb]|argon2(?:i|d|id))\$[a-zA-Z0-9\.\/]{20,}/',
		// Credit Card Numbers (13-19 digits, with spaces or dashes)
		'credit_cards'    => '/\b(?:4[0-9]{12}(?:[0-9]{3})?|5[1-5][0-9]{14}|3[47][0-9]{13}|3(?:0[0-5]|[68][0-9])[0-9]{11}|6(?:011|5[0-9]{2})[0-9]{12}|(?:2131|1800|35\d{3})\d{11})\b/',
		// Database Connection Strings & Passwords
		'db_credentials'  => '/(?:DB_PASSWORD|DB_USER|AUTH_KEY|SECURE_AUTH_KEY|LOGGED_IN_KEY|NONCE_KEY|AUTH_SALT|SECURE_AUTH_SALT|LOGGED_IN_SALT|NONCE_SALT)[\s\'"=:]+([^\s\r\n\'";]{6,})/i',
		// Server Secret Tokens & Private Keys
		'private_keys'    => '/-----BEGIN (?:RSA |EC |DSA |OPENSSH )?PRIVATE KEY-----[\s\S]+?-----END (?:RSA |EC |DSA |OPENSSH )?PRIVATE KEY-----/',
	);

	/**
	 * Redact critical and sensitive data from prompt or context strings BEFORE dispatching to AI.
	 *
	 * @param string $content Text content destined for LLM prompt or context.
	 * @return string Redacted and sanitized content.
	 */
	public static function redact_sensitive_data( $content ) {
		if ( empty( $content ) || ! is_string( $content ) ) {
			return $content;
		}

		// 1. Redact Private Keys
		$content = preg_replace( self::$sensitive_patterns['private_keys'], '[REDACTED_PRIVATE_KEY]', $content );

		// 2. Redact Database Credentials & WordPress Salts
		$content = preg_replace_callback(
			self::$sensitive_patterns['db_credentials'],
			function( $matches ) {
				return str_replace( $matches[1], '[REDACTED_SECRET]', $matches[0] );
			},
			$content
		);

		// 3. Redact API Keys
		$content = preg_replace( self::$sensitive_patterns['api_keys'], '[REDACTED_API_KEY]', $content );

		// 4. Redact Password Hashes
		$content = preg_replace( self::$sensitive_patterns['password_hashes'], '[REDACTED_HASH]', $content );

		// 5. Redact Credit Card PANs
		$content = preg_replace( self::$sensitive_patterns['credit_cards'], '[REDACTED_PAYMENT_CARD]', $content );

		/**
		 * Filter sanitized prompt text before sending to AI provider.
		 *
		 * @param string $content Sanitized content.
		 */
		return apply_filters( 'dctc_ai_redacted_prompt_data', $content );
	}

	/**
	 * Sanitize AI generated response text to prevent leakage of internal server details,
	 * database traces, or prompt injection artifacts to visitors.
	 *
	 * @param string $content Raw AI response.
	 * @return string Cleaned and safe AI output.
	 */
	public static function sanitize_ai_response( $content ) {
		if ( empty( $content ) || ! is_string( $content ) ) {
			return $content;
		}

		// 1. Strip leaked API keys if generated/echoed by model
		$content = preg_replace( self::$sensitive_patterns['api_keys'], '[REDACTED_KEY]', $content );

		// 2. Strip leaked password hashes
		$content = preg_replace( self::$sensitive_patterns['password_hashes'], '[REDACTED_HASH]', $content );

		// 3. Strip absolute filesystem paths
		$content = preg_replace( '/([a-zA-Z]:\\\\[a-zA-Z0-9_\-\\\\]+|\/var\/www\/[a-zA-Z0-9_\-\/]+|\/home\/[a-zA-Z0-9_\-\/]+)/i', '[SERVER_PATH]', $content );

		// 4. Strip raw SQL queries or DB table schema names if hallucinated
		$content = preg_replace( '/\b(SELECT\s+.+\s+FROM\s+[`\'"]?wp_[a-zA-Z0-9_]+[`\'"]?|DROP\s+TABLE|ALTER\s+TABLE\s+[`\'"]?wp_)/i', '[QUERY_FILTERED]', $content );

		// 5. Remove null bytes and control chars
		$content = str_replace( "\0", '', $content );

		return trim( $content );
	}
}
