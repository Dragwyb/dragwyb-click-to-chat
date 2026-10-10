<?php
/**
 * DCTC Support Ticket Object (Concrete OOP Model)
 *
 * Implements full Ticket OOP properties, methods, robust multi-type metadata
 * sanitization, conversation message access, WooCommerce product context,
 * and AI useful content extraction.
 *
 * @package Dragwyb_Click_To_Chat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once plugin_dir_path( __FILE__ ) . 'class-dctc-support-ticket-base.php';

/**
 * Class DCTC_Support_Ticket
 */
class DCTC_Support_Ticket extends DCTC_Support_Ticket_Base {

	/**
	 * Constructor.
	 *
	 * @param int|string|array|null $id_or_uuid_or_session Ticket ID, UUID, Session ID, or raw data array.
	 */
	public function __construct( $id_or_uuid_or_session = null ) {
		if ( ! empty( $id_or_uuid_or_session ) ) {
			$this->load( $id_or_uuid_or_session );
		}
	}

	/**
	 * Static factory to instantiate a ticket object.
	 *
	 * @param int|string|array $id_or_uuid_or_session
	 * @return self|null
	 */
	public static function get( $id_or_uuid_or_session ) {
		$ticket = new self( $id_or_uuid_or_session );
		return $ticket->is_valid() ? $ticket : null;
	}

	/**
	 * Static factory to instantiate a ticket object by session ID.
	 *
	 * @param string $session_id
	 * @return self|null
	 */
	public static function get_by_session( $session_id ) {
		if ( empty( $session_id ) ) {
			return null;
		}

		if ( class_exists( 'DCTC_Support_Ticket_Service' ) ) {
			$ticket_data = DCTC_Support_Ticket_Service::get_ticket_by_session_id( $session_id );
			if ( $ticket_data ) {
				return new self( $ticket_data );
			}
		}

		return null;
	}

	/**
	 * Load ticket data into object properties.
	 *
	 * @param int|string|array $identifier
	 * @return bool
	 */
	public function load( $identifier ) {
		if ( is_array( $identifier ) && ! empty( $identifier['id'] ) ) {
			$data = $identifier;
		} elseif ( class_exists( 'DCTC_Support_Ticket_Service' ) ) {
			if ( is_string( $identifier ) && strpos( $identifier, 'sess_' ) === 0 ) {
				$data = DCTC_Support_Ticket_Service::get_ticket_by_session_id( $identifier );
			} else {
				$data = DCTC_Support_Ticket_Service::get_ticket( $identifier );
			}
		} else {
			$data = null;
		}

		if ( ! $data || empty( $data['id'] ) ) {
			return false;
		}

		$this->id                  = (int) $data['id'];
		$this->uuid                = ! empty( $data['uuid'] ) ? (string) $data['uuid'] : '';
		$this->ticket_number       = ! empty( $data['ticket_number'] ) ? (int) $data['ticket_number'] : $this->id;
		$this->session_id          = ! empty( $data['session_id'] ) ? (string) $data['session_id'] : '';
		$this->customer_wp_user_id = ! empty( $data['customer_wp_user_id'] ) ? (int) $data['customer_wp_user_id'] : 0;
		$this->customer_name       = ! empty( $data['customer_name'] ) ? (string) $data['customer_name'] : '';
		$this->customer_email      = ! empty( $data['customer_email'] ) ? (string) $data['customer_email'] : '';
		$this->customer_phone      = ! empty( $data['customer_phone'] ) ? (string) $data['customer_phone'] : '';
		$this->subject             = ! empty( $data['subject'] ) ? (string) $data['subject'] : '';
		$this->status              = ! empty( $data['status'] ) ? (string) $data['status'] : 'open';
		$this->priority            = ! empty( $data['priority'] ) ? (string) $data['priority'] : 'normal';
		$this->control_mode        = ! empty( $data['control_mode'] ) ? (string) $data['control_mode'] : 'ai';
		$this->origin_type         = ! empty( $data['origin_type'] ) ? (string) $data['origin_type'] : 'chatbot';
		$this->reply_surface       = ! empty( $data['reply_surface'] ) ? (string) $data['reply_surface'] : 'chatbot_widget';
		$this->created_at          = ! empty( $data['created_at'] ) ? (string) $data['created_at'] : '';
		$this->updated_at          = ! empty( $data['updated_at'] ) ? (string) $data['updated_at'] : '';

		$this->raw_data   = $data;
		$this->meta_cache = isset( $data['meta'] ) && is_array( $data['meta'] ) ? $data['meta'] : null;

		// If customer_phone is in meta and not set, populate it
		if ( empty( $this->customer_phone ) && $this->meta_cache ) {
			if ( ! empty( $this->meta_cache['customer_phone'] ) ) {
				$this->customer_phone = (string) $this->meta_cache['customer_phone'];
			} elseif ( ! empty( $this->meta_cache['phone'] ) ) {
				$this->customer_phone = (string) $this->meta_cache['phone'];
			}
		}

		return true;
	}

	/**
	 * Retrieve all conversation messages for this ticket.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_chats() {
		if ( ! $this->is_valid() ) {
			return array();
		}

		global $wpdb;
		$table_sessions = $wpdb->prefix . 'dctc_ai_sessions';
		$messages       = array();

		// Check session table by session_id or support_ticket_id
		$session_row = null;
		if ( ! empty( $this->session_id ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$session_row = $wpdb->get_row(
				$wpdb->prepare( "SELECT id, content FROM `$table_sessions` WHERE session_id = %s LIMIT 1", $this->session_id ),
				ARRAY_A
			);
		}

		if ( ! $session_row ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$session_row = $wpdb->get_row(
				$wpdb->prepare( "SELECT id, content FROM `$table_sessions` WHERE support_ticket_id = %d LIMIT 1", $this->id ),
				ARRAY_A
			);
		}

		if ( $session_row && ! empty( $session_row['content'] ) ) {
			$decoded = json_decode( $session_row['content'], true );
			if ( is_array( $decoded ) ) {
				$messages = $decoded;
			}
		}

		if ( empty( $messages ) ) {
			$meta_msgs = $this->get_ticket_meta( '_dctc_ticket_messages', true );
			if ( is_array( $meta_msgs ) ) {
				$messages = $meta_msgs;
			}
		}

		return is_array( $messages ) ? $messages : array();
	}

	/**
	 * Get ticket meta value.
	 *
	 * @param string $key Meta key.
	 * @param bool   $single Single value or array.
	 * @return mixed
	 */
	public function get_ticket_meta( $key = '', $single = true ) {
		if ( ! $this->is_valid() ) {
			return $single ? null : array();
		}

		if ( null === $this->meta_cache ) {
			if ( class_exists( 'DCTC_Support_Ticket_Service' ) ) {
				$this->meta_cache = DCTC_Support_Ticket_Service::get_all_ticket_meta( $this->id );
			} else {
				$this->meta_cache = array();
			}
		}

		if ( empty( $key ) ) {
			return $this->meta_cache;
		}

		$key = sanitize_key( $key );

		if ( isset( $this->meta_cache[ $key ] ) ) {
			return $this->meta_cache[ $key ];
		}

		// Direct query fallback if key was inserted recently
		if ( class_exists( 'DCTC_Support_Ticket_Service' ) ) {
			$val                      = DCTC_Support_Ticket_Service::get_ticket_meta( $this->id, $key, $single );
			$this->meta_cache[ $key ] = $val;
			return $val;
		}

		return $single ? null : array();
	}

	/**
	 * Alias for get_ticket_meta.
	 *
	 * @param string $key Meta key.
	 * @param bool   $single Single value.
	 * @return mixed
	 */
	public function get_meta( $key = '', $single = true ) {
		return $this->get_ticket_meta( $key, $single );
	}

	/**
	 * Get all metadata as associative map.
	 *
	 * @return array<string, mixed>
	 */
	public function get_all_meta() {
		return $this->get_ticket_meta( '', false );
	}

	/**
	 * Update ticket meta with comprehensive multi-type sanitization.
	 *
	 * Handles array, integer, float, boolean, HTML (wp_kses_post), and strings
	 * without destroying data types or formatting.
	 *
	 * @param string $key Meta key.
	 * @param mixed  $value Meta value.
	 * @param string $sanitize_type Explicit sanitization hint ('auto', 'html', 'wp_kses', 'email', 'url', 'textarea', 'raw').
	 * @return bool
	 */
	public function update_meta( $key, $value, $sanitize_type = 'auto' ) {
		if ( ! $this->is_valid() || empty( $key ) ) {
			return false;
		}

		$key             = sanitize_key( $key );
		$sanitized_value = $this->sanitize_meta_value( $value, $sanitize_type );

		if ( class_exists( 'DCTC_Support_Ticket_Service' ) ) {
			$result = DCTC_Support_Ticket_Service::update_ticket_meta( $this->id, $key, $sanitized_value );
		} else {
			global $wpdb;
			$table   = $wpdb->prefix . 'dctc_support_ticket_meta';
			$val_str = is_array( $sanitized_value ) || is_object( $sanitized_value )
				? wp_json_encode( $sanitized_value )
				: (string) $sanitized_value;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$existing = $wpdb->get_var(
				$wpdb->prepare( "SELECT meta_id FROM `$table` WHERE ticket_id = %d AND meta_key = %s", $this->id, $key )
			);

			if ( $existing ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->update(
					$table,
					array( 'meta_value' => $val_str ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
					array( 'meta_id' => (int) $existing ),
					array( '%s' ),
					array( '%d' )
				);
			} else {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->insert(
					$table,
					array(
						'ticket_id'  => $this->id,
						'meta_key'   => $key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
						'meta_value' => $val_str, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
					),
					array( '%d', '%s', '%s' )
				);
			}
			$result = true;
		}

		if ( $result ) {
			if ( null === $this->meta_cache ) {
				$this->meta_cache = array();
			}
			$this->meta_cache[ $key ] = $sanitized_value;
			if ( isset( $this->raw_data['meta'] ) && is_array( $this->raw_data['meta'] ) ) {
				$this->raw_data['meta'][ $key ] = $sanitized_value;
			}
		}

		return (bool) $result;
	}

	/**
	 * High-precision recursive value sanitizer.
	 *
	 * @param mixed  $value Raw value.
	 * @param string $sanitize_type Type hint.
	 * @return mixed Sanitized value.
	 */
	protected function sanitize_meta_value( $value, $sanitize_type = 'auto' ) {
		if ( null === $value ) {
			return null;
		}

		if ( is_bool( $value ) ) {
			return (bool) $value;
		}

		if ( is_int( $value ) ) {
			return (int) $value;
		}

		if ( is_float( $value ) ) {
			return (float) $value;
		}

		if ( is_array( $value ) ) {
			$cleaned = array();
			foreach ( $value as $k => $v ) {
				$clean_k             = is_string( $k ) ? sanitize_key( $k ) : $k;
				$cleaned[ $clean_k ] = $this->sanitize_meta_value( $v, $sanitize_type );
			}
			return $cleaned;
		}

		if ( is_object( $value ) ) {
			$value = (array) $value;
			return $this->sanitize_meta_value( $value, $sanitize_type );
		}

		$str_val = (string) $value;

		switch ( $sanitize_type ) {
			case 'email':
				return sanitize_email( $str_val );

			case 'url':
				return esc_url_raw( $str_val );

			case 'textarea':
				return sanitize_textarea_field( $str_val );

			case 'html':
			case 'wp_kses':
				return wp_kses_post( $str_val );

			case 'raw':
				return $str_val;

			case 'auto':
			default:
				// If string contains HTML tags, apply wp_kses_post
				if ( preg_match( '/<[^>]+>/', $str_val ) ) {
					return wp_kses_post( $str_val );
				}
				// If string has newlines, preserve multiline text formatting
				if ( strpos( $str_val, "\n" ) !== false ) {
					return sanitize_textarea_field( $str_val );
				}
				return sanitize_text_field( $str_val );
		}
	}

	/**
	 * Update customer email across ticket row, session row, and metadata.
	 *
	 * @param string $email New email address.
	 * @return bool
	 */
	public function update_email( $email ) {
		if ( ! $this->is_valid() ) {
			return false;
		}

		$clean_email = sanitize_email( $email );
		if ( empty( $clean_email ) || ! is_email( $clean_email ) ) {
			return false;
		}

		global $wpdb;
		$table_tickets  = $wpdb->prefix . 'dctc_support_tickets';
		$table_sessions = $wpdb->prefix . 'dctc_ai_sessions';

		// 1. Update tickets table
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$table_tickets,
			array(
				'customer_email' => $clean_email,
				'updated_at'     => current_time( 'mysql' ),
			),
			array( 'id' => $this->id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		// 2. Update session table
		if ( ! empty( $this->session_id ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$table_sessions,
				array(
					'email'      => $clean_email,
					'updated_at' => current_time( 'mysql' ),
				),
				array( 'session_id' => $this->session_id ),
				array( '%s', '%s' ),
				array( '%s' )
			);
		}

		// 3. Update meta
		$this->update_meta( 'customer_email', $clean_email, 'email' );

		// 4. Update local state
		$this->customer_email             = $clean_email;
		$this->raw_data['customer_email'] = $clean_email;

		return true;
	}

	/**
	 * Update customer phone number.
	 *
	 * @param string $phone Phone number.
	 * @return bool
	 */
	public function update_phone( $phone ) {
		if ( ! $this->is_valid() ) {
			return false;
		}

		$clean_phone = sanitize_text_field( $phone );
		$this->update_meta( 'customer_phone', $clean_phone, 'auto' );
		$this->update_meta( 'phone', $clean_phone, 'auto' );

		$this->customer_phone             = $clean_phone;
		$this->raw_data['customer_phone'] = $clean_phone;

		return true;
	}

	/**
	 * Update customer display name.
	 *
	 * @param string $name
	 * @return bool
	 */
	public function update_name( $name ) {
		if ( ! $this->is_valid() ) {
			return false;
		}

		$clean_name = sanitize_text_field( $name );
		if ( empty( $clean_name ) ) {
			return false;
		}

		global $wpdb;
		$table_tickets = $wpdb->prefix . 'dctc_support_tickets';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$table_tickets,
			array(
				'customer_name' => $clean_name,
				'updated_at'    => current_time( 'mysql' ),
			),
			array( 'id' => $this->id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		$this->update_meta( 'customer_name', $clean_name, 'auto' );

		$this->customer_name             = $clean_name;
		$this->raw_data['customer_name'] = $clean_name;

		return true;
	}

	/**
	 * Update ticket status.
	 *
	 * @param string $status
	 * @param string $actor_type
	 * @param int    $actor_id
	 * @param string $actor_name
	 * @return bool
	 */
	public function update_status( $status, $actor_type = 'agent', $actor_id = 0, $actor_name = '' ) {
		if ( ! $this->is_valid() ) {
			return false;
		}

		if ( class_exists( 'DCTC_Support_Ticket_Service' ) ) {
			$res = DCTC_Support_Ticket_Service::change_status( $this->id, $status, $actor_type, $actor_id, $actor_name );
			if ( $res ) {
				$this->status             = sanitize_key( $status );
				$this->raw_data['status'] = $this->status;
				return true;
			}
		}

		return false;
	}

	/**
	 * Update ticket priority.
	 *
	 * @param string $priority
	 * @param string $actor_type
	 * @param int    $actor_id
	 * @param string $actor_name
	 * @return bool
	 */
	public function update_priority( $priority, $actor_type = 'agent', $actor_id = 0, $actor_name = '' ) {
		if ( ! $this->is_valid() ) {
			return false;
		}

		if ( class_exists( 'DCTC_Support_Ticket_Service' ) ) {
			$res = DCTC_Support_Ticket_Service::change_priority( $this->id, $priority, $actor_type, $actor_id, $actor_name );
			if ( $res ) {
				$this->priority             = sanitize_key( $priority );
				$this->raw_data['priority'] = $this->priority;
				return true;
			}
		}

		return false;
	}

	/**
	 * Get WooCommerce product details for this ticket.
	 *
	 * @return array<string, mixed>|null
	 */
	public function get_woocommerce_product_info() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return null;
		}

		$product_id   = (int) $this->get_ticket_meta( 'product_id', true );
		$product_name = (string) $this->get_ticket_meta( 'product', true );
		if ( empty( $product_name ) ) {
			$product_name = (string) $this->get_ticket_meta( 'product_name', true );
		}
		if ( empty( $product_name ) ) {
			$product_name = (string) $this->get_ticket_meta( 'interest', true );
		}

		$wc_product = null;

		if ( $product_id > 0 && function_exists( 'wc_get_product' ) ) {
			$wc_product = wc_get_product( $product_id );
		} elseif ( ! empty( $product_name ) && function_exists( 'wc_get_products' ) ) {
			$matches = wc_get_products(
				array(
					'status' => 'publish',
					'limit'  => 1,
					's'      => $product_name,
				)
			);
			if ( ! empty( $matches ) && is_array( $matches ) && isset( $matches[0] ) ) {
				$wc_product = $matches[0];
			}
		}

		if ( ! $wc_product instanceof WC_Product ) {
			return null;
		}

		$image_id  = $wc_product->get_image_id();
		$image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'thumbnail' ) : wc_placeholder_img_src( 'thumbnail' );

		return array(
			'id'                => $wc_product->get_id(),
			'name'              => $wc_product->get_name(),
			'sku'               => $wc_product->get_sku() ?: 'N/A',
			'price'             => $wc_product->get_price(),
			'regular_price'     => $wc_product->get_regular_price(),
			'sale_price'        => $wc_product->get_sale_price(),
			'price_html'        => $wc_product->get_price_html(),
			'is_on_sale'        => $wc_product->is_on_sale(),
			'stock_status'      => $wc_product->get_stock_status(),
			'stock_quantity'    => $wc_product->get_stock_quantity(),
			'permalink'         => get_permalink( $wc_product->get_id() ),
			'edit_url'          => admin_url( 'post.php?post=' . $wc_product->get_id() . '&action=edit' ),
			'image_url'         => $image_url,
			'short_description' => wp_strip_all_tags( $wc_product->get_short_description() ),
			'categories'        => wc_get_product_category_list( $wc_product->get_id() ),
		);
	}

	/**
	 * Extract and return structured AI Useful Content / Lead Details for Support Sidebar.
	 *
	 * @return array<string, mixed>
	 */
	public function get_ai_useful_content() {
		$meta = $this->get_all_meta();

		$lead_id      = isset( $meta['lead_id'] ) ? (int) $meta['lead_id'] : 0;
		$lead_score   = isset( $meta['lead_score'] ) ? (int) $meta['lead_score'] : ( isset( $meta['score'] ) ? (int) $meta['score'] : 0 );
		$intent_level = isset( $meta['intent_level'] ) ? (string) $meta['intent_level'] : '';
		$company      = isset( $meta['company'] ) ? (string) $meta['company'] : '';
		$company_size = isset( $meta['company_size'] ) ? (string) $meta['company_size'] : '';
		$budget       = isset( $meta['budget'] ) ? (string) $meta['budget'] : '';
		$timeline     = isset( $meta['timeline'] ) ? (string) $meta['timeline'] : '';
		$interest     = isset( $meta['interest'] ) ? (string) $meta['interest'] : ( isset( $meta['product'] ) ? (string) $meta['product'] : '' );
		$requirement  = isset( $meta['requirement'] ) ? (string) $meta['requirement'] : '';
		$source_url   = isset( $meta['source_url'] ) ? (string) $meta['source_url'] : '';
		$ai_summary   = isset( $meta['ai_summary'] ) ? (string) $meta['ai_summary'] : '';

		// If lead_id is present, query leads DB if some fields are missing
		if ( $lead_id > 0 && class_exists( 'DCTC_AI_DB' ) && ( empty( $budget ) || empty( $company ) || empty( $requirement ) ) ) {
			global $wpdb;
			$table_leads = $wpdb->prefix . 'dctc_ai_leads';
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$lead_row = $wpdb->get_row(
				$wpdb->prepare( "SELECT * FROM `$table_leads` WHERE id = %d LIMIT 1", $lead_id ),
				ARRAY_A
			);
			if ( $lead_row ) {
				$company      = empty( $company ) && ! empty( $lead_row['company'] ) ? $lead_row['company'] : $company;
				$company_size = empty( $company_size ) && ! empty( $lead_row['company_size'] ) ? $lead_row['company_size'] : $company_size;
				$budget       = empty( $budget ) && ! empty( $lead_row['budget'] ) ? $lead_row['budget'] : $budget;
				$timeline     = empty( $timeline ) && ! empty( $lead_row['timeline'] ) ? $lead_row['timeline'] : $timeline;
				$interest     = empty( $interest ) && ! empty( $lead_row['interest'] ) ? $lead_row['interest'] : $interest;
				$requirement  = empty( $requirement ) && ! empty( $lead_row['requirement'] ) ? $lead_row['requirement'] : $requirement;
				$lead_score   = empty( $lead_score ) && ! empty( $lead_row['score'] ) ? (int) $lead_row['score'] : $lead_score;
				$intent_level = empty( $intent_level ) && ! empty( $lead_row['intent_level'] ) ? $lead_row['intent_level'] : $intent_level;
			}
		}

		$wc_product_info = $this->get_woocommerce_product_info();

		return array(
			'has_ai_content'  => ( $lead_id > 0 || ! empty( $budget ) || ! empty( $company ) || ! empty( $requirement ) || ! empty( $this->customer_phone ) || ! empty( $interest ) || ! empty( $wc_product_info ) || ! empty( $ai_summary ) ),
			'lead_id'         => $lead_id,
			'customer_name'   => $this->customer_name,
			'customer_email'  => $this->customer_email,
			'customer_phone'  => $this->customer_phone,
			'company'         => $company,
			'company_size'    => $company_size,
			'budget'          => $budget,
			'timeline'        => $timeline,
			'interest'        => $interest,
			'requirement'     => $requirement,
			'lead_score'      => $lead_score,
			'intent_level'    => $intent_level,
			'ai_summary'      => $ai_summary,
			'source_url'      => $source_url,
			'wc_product_info' => $wc_product_info,
		);
	}

	/**
	 * Append a chat message to session.
	 *
	 * @param string $sender_type 'customer' | 'ai_agent' | 'human_agent' | 'system'.
	 * @param string $message Text content.
	 * @param array  $meta Additional message metadata.
	 * @return bool
	 */
	public function add_chat_message( $sender_type, $message, $meta = array() ) {
		if ( ! $this->is_valid() || empty( $message ) ) {
			return false;
		}

		$clean_message = wp_kses_post( $message );
		$sender_type   = sanitize_key( $sender_type );
		$time          = current_time( 'mysql' );

		global $wpdb;
		$table_sessions = $wpdb->prefix . 'dctc_ai_sessions';

		$new_entry = array(
			'role'        => ( 'customer' === $sender_type ) ? 'user' : 'assistant',
			'sender_type' => $sender_type,
			'sender_name' => ( 'customer' === $sender_type ) ? ( $this->customer_name ?: 'Customer' ) : ( 'ai_agent' === $sender_type ? 'AI Assistant' : 'Support Agent' ),
			'content'     => $clean_message,
			'created_at'  => $time,
		);

		if ( ! empty( $meta ) && is_array( $meta ) ) {
			$new_entry['meta'] = $this->sanitize_meta_value( $meta, 'auto' );
		}

		$current_messages   = $this->get_chats();
		$current_messages[] = $new_entry;
		$messages_json      = wp_json_encode( array_slice( $current_messages, -50 ) );

		if ( ! empty( $this->session_id ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$table_sessions,
				array(
					'content'    => $messages_json,
					'updated_at' => $time,
				),
				array( 'session_id' => $this->session_id ),
				array( '%s', '%s' ),
				array( '%s' )
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$table_sessions,
				array(
					'content'    => $messages_json,
					'updated_at' => $time,
				),
				array( 'support_ticket_id' => $this->id ),
				array( '%s', '%s' ),
				array( '%d' )
			);
		}

		$this->update_meta( '_dctc_ticket_messages', $current_messages, 'auto' );

		return true;
	}
}
