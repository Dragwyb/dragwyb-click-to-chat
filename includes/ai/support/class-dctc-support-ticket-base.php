<?php
/**
 * DCTC Support Ticket Base (Abstract Class)
 *
 * Provides the abstract foundation and standard contract for Ticket OOP models.
 *
 * @package Dragwyb_Click_To_Chat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract Class DCTC_Support_Ticket_Base
 */
abstract class DCTC_Support_Ticket_Base {

	/**
	 * Ticket Primary ID.
	 *
	 * @var int
	 */
	protected $id = 0;

	/**
	 * Ticket UUID.
	 *
	 * @var string
	 */
	protected $uuid = '';

	/**
	 * Sequential Ticket Number.
	 *
	 * @var int
	 */
	protected $ticket_number = 0;

	/**
	 * Associated Chatbot Session ID.
	 *
	 * @var string
	 */
	protected $session_id = '';

	/**
	 * Customer WordPress User ID (0 if guest).
	 *
	 * @var int
	 */
	protected $customer_wp_user_id = 0;

	/**
	 * Customer Name.
	 *
	 * @var string
	 */
	protected $customer_name = '';

	/**
	 * Customer Email.
	 *
	 * @var string
	 */
	protected $customer_email = '';

	/**
	 * Customer Phone.
	 *
	 * @var string
	 */
	protected $customer_phone = '';

	/**
	 * Ticket Subject / Title.
	 *
	 * @var string
	 */
	protected $subject = '';

	/**
	 * Ticket Status ('open', 'pending', 'waiting_customer', 'resolved', 'closed').
	 *
	 * @var string
	 */
	protected $status = 'open';

	/**
	 * Ticket Priority ('low', 'normal', 'high', 'urgent').
	 *
	 * @var string
	 */
	protected $priority = 'normal';

	/**
	 * Control Mode ('ai' | 'human').
	 *
	 * @var string
	 */
	protected $control_mode = 'ai';

	/**
	 * Origin Type ('chatbot', 'portal', 'admin', 'email').
	 *
	 * @var string
	 */
	protected $origin_type = 'chatbot';

	/**
	 * Reply Surface ('chatbot_widget' | 'portal').
	 *
	 * @var string
	 */
	protected $reply_surface = 'chatbot_widget';

	/**
	 * Created At Timestamp.
	 *
	 * @var string
	 */
	protected $created_at = '';

	/**
	 * Updated At Timestamp.
	 *
	 * @var string
	 */
	protected $updated_at = '';

	/**
	 * Full raw ticket database record.
	 *
	 * @var array<string, mixed>
	 */
	protected $raw_data = array();

	/**
	 * Ticket Metadata cache.
	 *
	 * @var array<string, mixed>|null
	 */
	protected $meta_cache = null;

	/**
	 * Check if ticket object is loaded and valid.
	 *
	 * @return bool
	 */
	public function is_valid() {
		return ! empty( $this->id ) && $this->id > 0;
	}

	/**
	 * Get Ticket ID.
	 *
	 * @return int
	 */
	public function get_id() {
		return (int) $this->id;
	}

	/**
	 * Get Ticket UUID.
	 *
	 * @return string
	 */
	public function get_uuid() {
		return (string) $this->uuid;
	}

	/**
	 * Get Ticket Number.
	 *
	 * @return int
	 */
	public function get_ticket_number() {
		return (int) $this->ticket_number;
	}

	/**
	 * Get Associated Session ID.
	 *
	 * @return string
	 */
	public function get_session_id() {
		return (string) $this->session_id;
	}

	/**
	 * Get Customer WordPress User ID.
	 *
	 * @return int
	 */
	public function get_customer_wp_user_id() {
		return (int) $this->customer_wp_user_id;
	}

	/**
	 * Get Customer Name.
	 *
	 * @return string
	 */
	public function get_customer_name() {
		return (string) $this->customer_name;
	}

	/**
	 * Get Customer Email.
	 *
	 * @return string
	 */
	public function get_customer_email() {
		return (string) $this->customer_email;
	}

	/**
	 * Get Customer Phone.
	 *
	 * @return string
	 */
	public function get_customer_phone() {
		return (string) $this->customer_phone;
	}

	/**
	 * Get Ticket Subject.
	 *
	 * @return string
	 */
	public function get_subject() {
		return (string) $this->subject;
	}

	/**
	 * Get Ticket Status.
	 *
	 * @return string
	 */
	public function get_status() {
		return (string) $this->status;
	}

	/**
	 * Get Ticket Priority.
	 *
	 * @return string
	 */
	public function get_priority() {
		return (string) $this->priority;
	}

	/**
	 * Get Control Mode.
	 *
	 * @return string
	 */
	public function get_control_mode() {
		return (string) $this->control_mode;
	}

	/**
	 * Get Origin Type.
	 *
	 * @return string
	 */
	public function get_origin_type() {
		return (string) $this->origin_type;
	}

	/**
	 * Get Reply Surface.
	 *
	 * @return string
	 */
	public function get_reply_surface() {
		return (string) $this->reply_surface;
	}

	/**
	 * Get Created At.
	 *
	 * @return string
	 */
	public function get_created_at() {
		return (string) $this->created_at;
	}

	/**
	 * Get Updated At.
	 *
	 * @return string
	 */
	public function get_updated_at() {
		return (string) $this->updated_at;
	}

	/**
	 * Get Raw Data array.
	 *
	 * @return array<string, mixed>
	 */
	public function get_data() {
		return $this->raw_data;
	}

	/**
	 * Convert ticket to array representation.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array() {
		return $this->raw_data;
	}

	/**
	 * Abstract: Retrieve all conversation chats / messages for this ticket.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	abstract public function get_chats();

	/**
	 * Abstract: Get ticket meta value.
	 *
	 * @param string $key Meta key.
	 * @param bool   $single Whether to return single value or array.
	 * @return mixed
	 */
	abstract public function get_ticket_meta( $key = '', $single = true );

	/**
	 * Abstract: Update ticket meta with comprehensive sanitization.
	 *
	 * @param string $key Meta key.
	 * @param mixed  $value Meta value (scalar, array, HTML, boolean, number).
	 * @param string $sanitize_type Explicit sanitization hint ('auto', 'html', 'wp_kses', 'email', 'url', 'textarea', 'raw').
	 * @return bool
	 */
	abstract public function update_meta( $key, $value, $sanitize_type = 'auto' );

	/**
	 * Abstract: Get WooCommerce product information related to this ticket/session.
	 *
	 * @return array<string, mixed>|null
	 */
	abstract public function get_woocommerce_product_info();

	/**
	 * Abstract: Update customer email across ticket, session, and metadata.
	 *
	 * @param string $email New email address.
	 * @return bool
	 */
	abstract public function update_email( $email );

	/**
	 * Abstract: Update customer phone number.
	 *
	 * @param string $phone Phone number string.
	 * @return bool
	 */
	abstract public function update_phone( $phone );

	/**
	 * Abstract: Update customer display name.
	 *
	 * @param string $name Name string.
	 * @return bool
	 */
	abstract public function update_name( $name );

	/**
	 * Abstract: Update ticket status.
	 *
	 * @param string $status New status.
	 * @param string $actor_type Actor type ('agent', 'customer', 'system').
	 * @param int    $actor_id Actor WP user ID.
	 * @param string $actor_name Actor display name.
	 * @return bool
	 */
	abstract public function update_status( $status, $actor_type = 'agent', $actor_id = 0, $actor_name = '' );

	/**
	 * Abstract: Update ticket priority.
	 *
	 * @param string $priority New priority.
	 * @param string $actor_type Actor type.
	 * @param int    $actor_id Actor WP user ID.
	 * @param string $actor_name Actor display name.
	 * @return bool
	 */
	abstract public function update_priority( $priority, $actor_type = 'agent', $actor_id = 0, $actor_name = '' );

	/**
	 * Abstract: Retrieve structured AI useful conversation content (Lead details, score, requirements, WC info).
	 *
	 * @return array<string, mixed>
	 */
	abstract public function get_ai_useful_content();
}
