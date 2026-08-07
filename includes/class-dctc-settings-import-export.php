<?php
/**
 * Settings import / export for Channels and AI Assistant.
 *
 * Never includes API keys or runtime data (sessions / RAG tables).
 *
 * @package Dragwyb_Click_To_Chat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class DCTC_Settings_Import_Export
 */
class DCTC_Settings_Import_Export {

	const OPTION_CHANNELS = 'dctc_settings';
	const OPTION_AI       = 'dctc_ai_chat_assistant_settings';
	const MAX_UPLOAD_BYTES = 2097152; // 2MB.

	/**
	 * Allowed export / import formats.
	 *
	 * @return string[]
	 */
	public static function dctc_allowed_formats() {
		return array( 'json', 'csv', 'sql' );
	}

	/**
	 * Allowed module keys.
	 *
	 * @return string[]
	 */
	public static function dctc_allowed_modules() {
		return array( 'channels', 'ai' );
	}

	/**
	 * Normalize selected modules from request input.
	 *
	 * @param mixed $modules Raw modules (array or single string).
	 * @return string[]
	 */
	public static function dctc_normalize_modules( $modules ) {
		if ( ! is_array( $modules ) ) {
			$modules = array( $modules );
		}
		$out = array();
		foreach ( $modules as $module ) {
			$module = sanitize_key( (string) $module );
			if ( in_array( $module, self::dctc_allowed_modules(), true ) ) {
				$out[] = $module;
			}
		}
		return array_values( array_unique( $out ) );
	}

	/**
	 * Build export payload array (secrets already redacted).
	 *
	 * @param string[] $modules Module keys.
	 * @return array|WP_Error
	 */
	public static function dctc_build_payload( $modules ) {
		$modules = self::dctc_normalize_modules( $modules );
		if ( empty( $modules ) ) {
			return new WP_Error(
				'dctc_ie_no_modules',
				__( 'Select at least one module to export.', 'dragwyb-click-to-chat' )
			);
		}

		$payload = array(
			'plugin'      => 'dragwyb-click-to-chat',
			'version'     => defined( 'DCTC_VERSION' ) ? DCTC_VERSION : '1.0.0',
			'exported_at' => gmdate( 'c' ),
			'format'      => 'json',
			'modules'     => $modules,
		);

		if ( in_array( 'channels', $modules, true ) ) {
			$channels = get_option( self::OPTION_CHANNELS, array() );
			$payload['channels'] = is_array( $channels ) ? $channels : array();
		}

		if ( in_array( 'ai', $modules, true ) ) {
			$ai = get_option( self::OPTION_AI, array() );
			if ( ! is_array( $ai ) ) {
				$ai = array();
			}
			$payload['ai'] = self::dctc_redact_ai_settings( $ai );
		}

		return $payload;
	}

	/**
	 * Strip secrets from AI settings before export.
	 *
	 * @param array $ai AI settings array.
	 * @return array
	 */
	public static function dctc_redact_ai_settings( $ai ) {
		if ( ! is_array( $ai ) ) {
			return array();
		}

		unset( $ai['api_keys'] );

		if ( isset( $ai['rag']['vector_db']['api_key'] ) ) {
			$ai['rag']['vector_db']['api_key'] = '';
		}

		if ( ! empty( $ai['mcp_servers'] ) && is_array( $ai['mcp_servers'] ) ) {
			foreach ( $ai['mcp_servers'] as $i => $server ) {
				if ( ! is_array( $server ) ) {
					continue;
				}
				unset( $ai['mcp_servers'][ $i ]['apiKey'], $ai['mcp_servers'][ $i ]['api_key'] );
			}
		}

		return $ai;
	}

	/**
	 * Encode payload as JSON string.
	 *
	 * @param array $payload Export payload.
	 * @return string|WP_Error
	 */
	public static function dctc_encode_json( $payload ) {
		$json = wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		if ( false === $json ) {
			return new WP_Error(
				'dctc_ie_json_encode',
				__( 'Could not encode settings as JSON.', 'dragwyb-click-to-chat' )
			);
		}
		return $json;
	}

	/**
	 * Encode payload as CSV (module,option_key,setting_key,value).
	 *
	 * Nested values use JSON in the value cell. Top-level option maps use
	 * setting_key = path (dot notation for nested keys under the option).
	 *
	 * @param array $payload Export payload.
	 * @return string
	 */
	public static function dctc_encode_csv( $payload ) {
		$lines   = array();
		$lines[] = 'module,option_key,setting_key,value';

		if ( ! empty( $payload['channels'] ) && is_array( $payload['channels'] ) ) {
			foreach ( self::dctc_flatten_settings( $payload['channels'] ) as $key => $value ) {
				$lines[] = self::dctc_csv_row(
					array(
						'channels',
						self::OPTION_CHANNELS,
						$key,
						self::dctc_csv_cell_value( $value ),
					)
				);
			}
		}

		if ( ! empty( $payload['ai'] ) && is_array( $payload['ai'] ) ) {
			foreach ( self::dctc_flatten_settings( $payload['ai'] ) as $key => $value ) {
				$lines[] = self::dctc_csv_row(
					array(
						'ai',
						self::OPTION_AI,
						$key,
						self::dctc_csv_cell_value( $value ),
					)
				);
			}
		}

		return implode( "\n", $lines ) . "\n";
	}

	/**
	 * Encode payload as portable wp_options SQL.
	 *
	 * @param array $payload Export payload.
	 * @return string
	 */
	public static function dctc_encode_sql( $payload ) {
		global $wpdb;

		$table   = $wpdb->options;
		$lines   = array();
		$lines[] = '-- Click to Chat settings export';
		$lines[] = '-- Plugin: dragwyb-click-to-chat';
		$lines[] = '-- Generated: ' . gmdate( 'c' );
		$lines[] = '-- API keys are never included. Import via the plugin Settings page or run in phpMyAdmin.';
		$lines[] = '';

		if ( isset( $payload['channels'] ) && is_array( $payload['channels'] ) ) {
			$lines[] = self::dctc_sql_upsert_option( $table, self::OPTION_CHANNELS, $payload['channels'] );
			$lines[] = '';
		}

		if ( isset( $payload['ai'] ) && is_array( $payload['ai'] ) ) {
			$lines[] = self::dctc_sql_upsert_option( $table, self::OPTION_AI, $payload['ai'] );
			$lines[] = '';
		}

		return implode( "\n", $lines );
	}

	/**
	 * Build INSERT ... ON DUPLICATE KEY UPDATE for one option.
	 *
	 * @param string $table  Options table name.
	 * @param string $name   Option name.
	 * @param mixed  $value  Option value (array/object will be serialized).
	 * @return string
	 */
	private static function dctc_sql_upsert_option( $table, $name, $value ) {
		$serialized = maybe_serialize( $value );
		$esc_name   = self::dctc_sql_escape_string( $name );
		$esc_value  = self::dctc_sql_escape_string( $serialized );
		$autoload   = ( self::OPTION_AI === $name ) ? 'no' : 'yes';

		return "INSERT INTO `{$table}` (`option_name`, `option_value`, `autoload`)\n"
			. "VALUES ('{$esc_name}', '{$esc_value}', '{$autoload}')\n"
			. "ON DUPLICATE KEY UPDATE `option_value` = VALUES(`option_value`);";
	}

	/**
	 * Escape a string for inclusion in a SQL single-quoted literal.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	private static function dctc_sql_escape_string( $value ) {
		return str_replace(
			array( '\\', "\0", "\n", "\r", "'", '"', "\x1a" ),
			array( '\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z' ),
			(string) $value
		);
	}

	/**
	 * Flatten nested array to dot-path => scalar/json leaf map.
	 *
	 * @param array  $data   Settings.
	 * @param string $prefix Path prefix.
	 * @return array<string, mixed>
	 */
	private static function dctc_flatten_settings( $data, $prefix = '' ) {
		$flat = array();
		foreach ( $data as $key => $value ) {
			$path = '' === $prefix ? (string) $key : $prefix . '.' . $key;
			if ( is_array( $value ) ) {
				// Indexed lists (knowledge_urls, mcp_servers, etc.) stay as JSON cells.
				if ( self::dctc_is_list_array( $value ) ) {
					$flat[ $path ] = $value;
				} else {
					$flat = array_merge( $flat, self::dctc_flatten_settings( $value, $path ) );
				}
			} else {
				$flat[ $path ] = $value;
			}
		}
		return $flat;
	}

	/**
	 * Whether array is a sequential list.
	 *
	 * @param array $arr Array.
	 * @return bool
	 */
	private static function dctc_is_list_array( $arr ) {
		if ( array() === $arr ) {
			return true;
		}
		return array_keys( $arr ) === range( 0, count( $arr ) - 1 );
	}

	/**
	 * Value for CSV cell.
	 *
	 * @param mixed $value Value.
	 * @return string
	 */
	private static function dctc_csv_cell_value( $value ) {
		if ( is_bool( $value ) ) {
			return $value ? '1' : '0';
		}
		if ( is_array( $value ) || is_object( $value ) ) {
			$encoded = wp_json_encode( $value );
			return false !== $encoded ? $encoded : '';
		}
		return (string) $value;
	}

	/**
	 * One CSV row.
	 *
	 * @param string[] $fields Fields.
	 * @return string
	 */
	private static function dctc_csv_row( $fields ) {
		$escaped = array();
		foreach ( $fields as $field ) {
			$field = (string) $field;
			if ( false !== strpos( $field, '"' ) || false !== strpos( $field, ',' ) || false !== strpos( $field, "\n" ) ) {
				$field = '"' . str_replace( '"', '""', $field ) . '"';
			}
			$escaped[] = $field;
		}
		return implode( ',', $escaped );
	}

	/**
	 * Encode payload in the requested format.
	 *
	 * @param array  $payload Export payload.
	 * @param string $format  json|csv|sql.
	 * @return string|WP_Error
	 */
	public static function dctc_encode( $payload, $format ) {
		$format = sanitize_key( $format );
		if ( ! in_array( $format, self::dctc_allowed_formats(), true ) ) {
			return new WP_Error(
				'dctc_ie_bad_format',
				__( 'Invalid export format.', 'dragwyb-click-to-chat' )
			);
		}

		$payload['format'] = $format;

		if ( 'json' === $format ) {
			return self::dctc_encode_json( $payload );
		}
		if ( 'csv' === $format ) {
			return self::dctc_encode_csv( $payload );
		}
		return self::dctc_encode_sql( $payload );
	}

	/**
	 * Parse uploaded file contents into a payload array.
	 *
	 * @param string $contents File contents.
	 * @param string $ext      Extension: json|csv|sql.
	 * @return array|WP_Error Payload with optional channels/ai keys.
	 */
	public static function dctc_parse_file( $contents, $ext ) {
		$ext = sanitize_key( $ext );
		if ( 'json' === $ext ) {
			return self::dctc_parse_json( $contents );
		}
		if ( 'csv' === $ext ) {
			return self::dctc_parse_csv( $contents );
		}
		if ( 'sql' === $ext ) {
			return self::dctc_parse_sql( $contents );
		}
		return new WP_Error(
			'dctc_ie_bad_ext',
			__( 'Unsupported file type. Use .json, .csv, or .sql.', 'dragwyb-click-to-chat' )
		);
	}

	/**
	 * Parse JSON export.
	 *
	 * @param string $contents JSON.
	 * @return array|WP_Error
	 */
	private static function dctc_parse_json( $contents ) {
		$data = json_decode( $contents, true );
		if ( ! is_array( $data ) ) {
			return new WP_Error(
				'dctc_ie_bad_json',
				__( 'Invalid JSON file.', 'dragwyb-click-to-chat' )
			);
		}

		if ( isset( $data['plugin'] ) && 'dragwyb-click-to-chat' !== $data['plugin'] ) {
			return new WP_Error(
				'dctc_ie_wrong_plugin',
				__( 'This file does not look like a Click to Chat settings export.', 'dragwyb-click-to-chat' )
			);
		}

		$payload = array(
			'modules'  => array(),
			'channels' => null,
			'ai'       => null,
		);

		if ( isset( $data['channels'] ) && is_array( $data['channels'] ) ) {
			$payload['channels']  = $data['channels'];
			$payload['modules'][] = 'channels';
		}
		if ( isset( $data['ai'] ) && is_array( $data['ai'] ) ) {
			$payload['ai']        = self::dctc_redact_ai_settings( $data['ai'] );
			$payload['modules'][] = 'ai';
		}

		if ( empty( $payload['modules'] ) ) {
			return new WP_Error(
				'dctc_ie_empty_file',
				__( 'No Channels or AI Assistant settings found in this file.', 'dragwyb-click-to-chat' )
			);
		}

		return $payload;
	}

	/**
	 * Parse CSV export into nested settings.
	 *
	 * @param string $contents CSV text.
	 * @return array|WP_Error
	 */
	private static function dctc_parse_csv( $contents ) {
		$lines = preg_split( '/\r\n|\r|\n/', $contents );
		if ( empty( $lines ) ) {
			return new WP_Error(
				'dctc_ie_bad_csv',
				__( 'Empty CSV file.', 'dragwyb-click-to-chat' )
			);
		}

		$header = str_getcsv( array_shift( $lines ) );
		$header = array_map( 'strtolower', array_map( 'trim', $header ) );
		$needed = array( 'module', 'option_key', 'setting_key', 'value' );
		foreach ( $needed as $col ) {
			if ( ! in_array( $col, $header, true ) ) {
				return new WP_Error(
					'dctc_ie_bad_csv_header',
					__( 'CSV must include columns: module, option_key, setting_key, value.', 'dragwyb-click-to-chat' )
				);
			}
		}

		$channels_flat = array();
		$ai_flat       = array();

		foreach ( $lines as $line ) {
			if ( '' === trim( $line ) ) {
				continue;
			}
			$row = str_getcsv( $line );
			if ( count( $row ) < 4 ) {
				continue;
			}
			$map = array();
			foreach ( $header as $i => $col ) {
				$map[ $col ] = isset( $row[ $i ] ) ? $row[ $i ] : '';
			}
			$module = sanitize_key( $map['module'] );
			$key    = (string) $map['setting_key'];
			$value  = self::dctc_decode_csv_value( $map['value'] );

			if ( 'channels' === $module ) {
				$channels_flat[ $key ] = $value;
			} elseif ( 'ai' === $module ) {
				$ai_flat[ $key ] = $value;
			}
		}

		$payload = array(
			'modules'  => array(),
			'channels' => null,
			'ai'       => null,
		);

		if ( ! empty( $channels_flat ) ) {
			$payload['channels']  = self::dctc_unflatten_settings( $channels_flat );
			$payload['modules'][] = 'channels';
		}
		if ( ! empty( $ai_flat ) ) {
			$payload['ai']        = self::dctc_redact_ai_settings( self::dctc_unflatten_settings( $ai_flat ) );
			$payload['modules'][] = 'ai';
		}

		if ( empty( $payload['modules'] ) ) {
			return new WP_Error(
				'dctc_ie_empty_file',
				__( 'No Channels or AI Assistant settings found in this file.', 'dragwyb-click-to-chat' )
			);
		}

		return $payload;
	}

	/**
	 * Decode a CSV cell (JSON array/object or scalar).
	 *
	 * @param string $value Cell.
	 * @return mixed
	 */
	private static function dctc_decode_csv_value( $value ) {
		$trim = trim( (string) $value );
		if ( '' === $trim ) {
			return '';
		}
		if ( ( '{' === $trim[0] && '}' === substr( $trim, -1 ) ) || ( '[' === $trim[0] && ']' === substr( $trim, -1 ) ) ) {
			$decoded = json_decode( $trim, true );
			if ( null !== $decoded ) {
				return $decoded;
			}
		}
		return $value;
	}

	/**
	 * Rebuild nested array from dot paths.
	 *
	 * @param array<string, mixed> $flat Flat map.
	 * @return array
	 */
	private static function dctc_unflatten_settings( $flat ) {
		$out = array();
		foreach ( $flat as $path => $value ) {
			$parts = explode( '.', $path );
			$ref   =& $out;
			foreach ( $parts as $i => $part ) {
				if ( $i === count( $parts ) - 1 ) {
					$ref[ $part ] = $value;
				} else {
					if ( ! isset( $ref[ $part ] ) || ! is_array( $ref[ $part ] ) ) {
						$ref[ $part ] = array();
					}
					$ref =& $ref[ $part ];
				}
			}
			unset( $ref );
		}
		return $out;
	}

	/**
	 * Parse SQL export — only extract known option_name / option_value pairs.
	 * Never executes arbitrary SQL.
	 *
	 * @param string $contents SQL text.
	 * @return array|WP_Error
	 */
	private static function dctc_parse_sql( $contents ) {
		$allowed = array( self::OPTION_CHANNELS, self::OPTION_AI );
		$found   = array();

		// Match INSERT ... VALUES ('option_name', 'option_value', ...).
		$pattern = "/INSERT\\s+INTO\\s+`?[a-zA-Z0-9_]+`?\\s*\\([^)]*option_name[^)]*\\)\\s*VALUES\\s*\\(\\s*'("
			. implode( '|', array_map( 'preg_quote', $allowed ) )
			. ")'\\s*,\\s*'((?:\\\\'|[^'])*)'/is";

		if ( preg_match_all( $pattern, $contents, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $match ) {
				$name  = $match[1];
				$value = self::dctc_sql_unescape_string( $match[2] );
				$found[ $name ] = maybe_unserialize( $value );
			}
		}

		// Also match UPDATE ... SET option_value = '...' WHERE option_name = '...'.
		$update_pattern = "/UPDATE\\s+`?[a-zA-Z0-9_]+`?\\s+SET\\s+`?option_value`?\\s*=\\s*'((?:\\\\'|[^'])*)'\\s*WHERE\\s+`?option_name`?\\s*=\\s*'("
			. implode( '|', array_map( 'preg_quote', $allowed ) )
			. ")'/is";

		if ( preg_match_all( $update_pattern, $contents, $umatches, PREG_SET_ORDER ) ) {
			foreach ( $umatches as $match ) {
				$value = self::dctc_sql_unescape_string( $match[1] );
				$name  = $match[2];
				$found[ $name ] = maybe_unserialize( $value );
			}
		}

		$payload = array(
			'modules'  => array(),
			'channels' => null,
			'ai'       => null,
		);

		if ( isset( $found[ self::OPTION_CHANNELS ] ) && is_array( $found[ self::OPTION_CHANNELS ] ) ) {
			$payload['channels']  = $found[ self::OPTION_CHANNELS ];
			$payload['modules'][] = 'channels';
		}
		if ( isset( $found[ self::OPTION_AI ] ) && is_array( $found[ self::OPTION_AI ] ) ) {
			$payload['ai']        = self::dctc_redact_ai_settings( $found[ self::OPTION_AI ] );
			$payload['modules'][] = 'ai';
		}

		if ( empty( $payload['modules'] ) ) {
			return new WP_Error(
				'dctc_ie_sql_empty',
				__( 'No recognized Click to Chat options found in this SQL file.', 'dragwyb-click-to-chat' )
			);
		}

		return $payload;
	}

	/**
	 * Unescape SQL string literal contents.
	 *
	 * @param string $value Escaped value.
	 * @return string
	 */
	private static function dctc_sql_unescape_string( $value ) {
		return str_replace(
			array( "\\'", '\\"', '\\\\', '\\n', '\\r', '\\0', '\\Z' ),
			array( "'", '"', '\\', "\n", "\r", "\0", "\x1a" ),
			$value
		);
	}

	/**
	 * Sanitize Channels settings from import.
	 *
	 * @param array $raw Raw channels array.
	 * @return array
	 */
	public static function dctc_sanitize_channels( $raw ) {
		if ( ! is_array( $raw ) ) {
			return array();
		}

		$channels = array( 'whatsapp', 'facebook', 'phone', 'email', 'instagram', 'telegram', 'sms', 'twitter', 'linkedin' );
		$out      = array();

		foreach ( $channels as $slug ) {
			if ( isset( $raw[ $slug . '_enabled' ] ) ) {
				$out[ $slug . '_enabled' ] = ( '1' === (string) $raw[ $slug . '_enabled' ] ) ? '1' : '0';
			}
			if ( isset( $raw[ $slug . '_value' ] ) ) {
				if ( 'email' === $slug ) {
					$out[ $slug . '_value' ] = sanitize_email( $raw[ $slug . '_value' ] );
				} elseif ( 'linkedin' === $slug ) {
					$out[ $slug . '_value' ] = esc_url_raw( $raw[ $slug . '_value' ] );
				} else {
					$out[ $slug . '_value' ] = sanitize_text_field( $raw[ $slug . '_value' ] );
				}
			}
			if ( isset( $raw[ $slug . '_desktop' ] ) ) {
				$out[ $slug . '_desktop' ] = ( '1' === (string) $raw[ $slug . '_desktop' ] ) ? '1' : '0';
			}
			if ( isset( $raw[ $slug . '_mobile' ] ) ) {
				$out[ $slug . '_mobile' ] = ( '1' === (string) $raw[ $slug . '_mobile' ] ) ? '1' : '0';
			}
			if ( isset( $raw[ $slug . '_custom_icon' ] ) ) {
				$out[ $slug . '_custom_icon' ] = esc_url_raw( $raw[ $slug . '_custom_icon' ] );
			}
			if ( isset( $raw[ $slug . '_chat_widget_enabled' ] ) ) {
				$out[ $slug . '_chat_widget_enabled' ] = ( '1' === (string) $raw[ $slug . '_chat_widget_enabled' ] ) ? '1' : '0';
			}
			if ( isset( $raw[ $slug . '_default_message' ] ) ) {
				$out[ $slug . '_default_message' ] = sanitize_textarea_field( $raw[ $slug . '_default_message' ] );
			}
		}

		$string_keys = array(
			'widget_position',
			'widget_size_unit',
			'custom_bottom_unit',
			'custom_horizontal_unit',
			'custom_side',
			'greeting_message',
			'icon_type',
			'display_mode',
		);
		foreach ( $string_keys as $key ) {
			if ( isset( $raw[ $key ] ) ) {
				$out[ $key ] = sanitize_text_field( $raw[ $key ] );
			}
		}

		if ( isset( $raw['widget_color'] ) ) {
			$color = sanitize_hex_color( $raw['widget_color'] );
			if ( $color ) {
				$out['widget_color'] = $color;
			}
		}
		if ( isset( $raw['widget_size'] ) ) {
			$out['widget_size'] = floatval( $raw['widget_size'] );
		}
		if ( isset( $raw['custom_bottom'] ) ) {
			$out['custom_bottom'] = floatval( $raw['custom_bottom'] );
		}
		if ( isset( $raw['custom_horizontal'] ) ) {
			$out['custom_horizontal'] = floatval( $raw['custom_horizontal'] );
		}
		if ( isset( $raw['custom_icon_url'] ) ) {
			$out['custom_icon_url'] = esc_url_raw( $raw['custom_icon_url'] );
		}
		if ( isset( $raw['icon_rotation'] ) ) {
			$rotation = intval( $raw['icon_rotation'] );
			if ( $rotation >= 0 && $rotation <= 360 ) {
				$out['icon_rotation'] = $rotation;
			}
		}
		if ( isset( $raw['icon_scale'] ) ) {
			$scale = floatval( $raw['icon_scale'] );
			if ( $scale >= 0.5 && $scale <= 2 ) {
				$out['icon_scale'] = $scale;
			}
		}
		if ( isset( $raw['show_on_desktop'] ) ) {
			$out['show_on_desktop'] = ( '1' === (string) $raw['show_on_desktop'] ) ? '1' : '0';
		}
		if ( isset( $raw['show_on_mobile'] ) ) {
			$out['show_on_mobile'] = ( '1' === (string) $raw['show_on_mobile'] ) ? '1' : '0';
		}
		if ( isset( $raw['time_delay'] ) ) {
			$delay = intval( $raw['time_delay'] );
			if ( $delay >= 0 && $delay <= 60 ) {
				$out['time_delay'] = $delay;
			}
		}
		if ( isset( $raw['display_post_types'] ) && is_array( $raw['display_post_types'] ) ) {
			$out['display_post_types'] = array_values( array_map( 'sanitize_text_field', $raw['display_post_types'] ) );
		}

		// Preserve any other non-secret string keys from existing flat schema.
		foreach ( $raw as $key => $value ) {
			if ( isset( $out[ $key ] ) ) {
				continue;
			}
			$key = sanitize_key( $key );
			if ( '' === $key || is_array( $value ) ) {
				continue;
			}
			$out[ $key ] = sanitize_text_field( (string) $value );
		}

		return $out;
	}

	/**
	 * Sanitize AI settings and merge with existing so secrets are preserved.
	 *
	 * @param array $raw Imported (already redacted) AI settings.
	 * @return array Full settings ready to persist.
	 */
	public static function dctc_sanitize_ai_merge( $raw ) {
		if ( ! is_array( $raw ) ) {
			$raw = array();
		}

		$raw = self::dctc_redact_ai_settings( $raw );

		$existing = get_option( self::OPTION_AI, array() );
		if ( ! is_array( $existing ) ) {
			$existing = array();
		}

		// Start from imported, then restore secrets from current site.
		$merged = $raw;

		if ( ! empty( $existing['rag']['vector_db']['api_key'] ) ) {
			if ( ! isset( $merged['rag'] ) || ! is_array( $merged['rag'] ) ) {
				$merged['rag'] = isset( $existing['rag'] ) ? $existing['rag'] : array();
			}
			if ( ! isset( $merged['rag']['vector_db'] ) || ! is_array( $merged['rag']['vector_db'] ) ) {
				$merged['rag']['vector_db'] = isset( $existing['rag']['vector_db'] ) ? $existing['rag']['vector_db'] : array();
			}
			$merged['rag']['vector_db']['api_key'] = $existing['rag']['vector_db']['api_key'];
		}

		// Keep existing MCP server secrets by id/name when possible; otherwise keep existing list if import omitted apiKey.
		if ( ! empty( $existing['mcp_servers'] ) && is_array( $existing['mcp_servers'] ) ) {
			if ( empty( $merged['mcp_servers'] ) || ! is_array( $merged['mcp_servers'] ) ) {
				$merged['mcp_servers'] = $existing['mcp_servers'];
			} else {
				$existing_by_name = array();
				foreach ( $existing['mcp_servers'] as $server ) {
					if ( is_array( $server ) && ! empty( $server['name'] ) ) {
						$existing_by_name[ $server['name'] ] = $server;
					}
				}
				foreach ( $merged['mcp_servers'] as $i => $server ) {
					if ( ! is_array( $server ) ) {
						continue;
					}
					$name = isset( $server['name'] ) ? $server['name'] : '';
					if ( $name && isset( $existing_by_name[ $name ]['apiKey'] ) ) {
						$merged['mcp_servers'][ $i ]['apiKey'] = $existing_by_name[ $name ]['apiKey'];
					}
				}
			}
		}

		// Never persist imported api_keys blob; live keys stay in connectors_ai_* options.
		unset( $merged['api_keys'] );
		if ( isset( $existing['api_keys'] ) ) {
			// Drop leftover plaintext keys from option if any.
			unset( $merged['api_keys'] );
		}

		if ( class_exists( 'DCTC_AI_Settings_Handler' ) ) {
			$defaults = DCTC_AI_Settings_Handler::dctc_ai_get_all_settings();
			foreach ( $defaults as $section => $default_value ) {
				if ( ! isset( $merged[ $section ] ) ) {
					$merged[ $section ] = isset( $existing[ $section ] ) ? $existing[ $section ] : $default_value;
				} elseif ( is_array( $default_value ) && is_array( $merged[ $section ] ) ) {
					$merged[ $section ] = wp_parse_args( $merged[ $section ], $default_value );
				}
			}
		}

		return $merged;
	}

	/**
	 * Apply import for selected modules.
	 *
	 * @param array    $payload Parsed payload.
	 * @param string[] $modules Modules to apply.
	 * @return true|WP_Error
	 */
	public static function dctc_apply_import( $payload, $modules ) {
		$modules = self::dctc_normalize_modules( $modules );
		if ( empty( $modules ) ) {
			return new WP_Error(
				'dctc_ie_no_modules',
				__( 'Select at least one module to import.', 'dragwyb-click-to-chat' )
			);
		}

		$applied = 0;

		if ( in_array( 'channels', $modules, true ) ) {
			if ( empty( $payload['channels'] ) || ! is_array( $payload['channels'] ) ) {
				return new WP_Error(
					'dctc_ie_no_channels',
					__( 'The file does not contain Channels settings.', 'dragwyb-click-to-chat' )
				);
			}
			$clean = self::dctc_sanitize_channels( $payload['channels'] );
			update_option( self::OPTION_CHANNELS, $clean );
			++$applied;
		}

		if ( in_array( 'ai', $modules, true ) ) {
			if ( empty( $payload['ai'] ) || ! is_array( $payload['ai'] ) ) {
				return new WP_Error(
					'dctc_ie_no_ai',
					__( 'The file does not contain AI Assistant settings.', 'dragwyb-click-to-chat' )
				);
			}
			$merged = self::dctc_sanitize_ai_merge( $payload['ai'] );
			if ( class_exists( 'DCTC_AI_Settings_Handler' ) ) {
				DCTC_AI_Settings_Handler::dctc_ai_persist_settings( $merged );
			} else {
				update_option( self::OPTION_AI, $merged, false );
			}
			++$applied;
		}

		if ( $applied < 1 ) {
			return new WP_Error(
				'dctc_ie_nothing',
				__( 'Nothing was imported.', 'dragwyb-click-to-chat' )
			);
		}

		return true;
	}
}
