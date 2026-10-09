<?php
/**
 * DCTC Support Portal Shortcode & Frontend Renderer
 *
 * Implements the customer-facing support center shortcode: [dctc_support_portal]
 * Handles ticket lists, detail views, reply composer, guest access, custom styling, dynamic text, and ticket submission.
 *
 * @package Dragwyb_Click_To_Chat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class DCTC_Support_Portal
 */
class DCTC_Support_Portal {

	/**
	 * Init portal hooks.
	 */
	public static function init() {
		add_shortcode( 'dctc_support_portal', array( __CLASS__, 'render_portal_shortcode' ) );
		add_shortcode( 'dctc_support_ticket_form', array( __CLASS__, 'render_create_ticket_shortcode' ) ); // alias
		add_shortcode( 'dctc_support_user_tickets', array( __CLASS__, 'render_tickets_table_shortcode' ) ); // alias
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'maybe_enqueue_portal_assets' ) );
	}

	/**
	 * Get default settings for Support Portal styling, copy, and features.
	 *
	 * @return array
	 */
	public static function get_default_settings() {
		return array(
			// Preset Theme
			'preset'                       => 'indigo', // indigo, blue, emerald, dark, purple, amber, rose, monochrome, custom

			// Styling & Colors
			'primary_color'                => '#4F46E5',
			'primary_hover_color'          => '#4338CA',
			'primary_text_color'           => '#FFFFFF',
			'secondary_btn_bg'             => '#FFFFFF',
			'secondary_btn_text'           => '#374151',
			'secondary_btn_border'         => '#D1D5DB',
			'secondary_btn_hover_bg'       => '#F3F4F6',
			'border_color'                 => '#E5E7EB',
			'border_width'                 => 1,
			'border_style'                 => 'solid', // solid, dashed, none
			'border_radius'                => 12,
			'container_bg_color'           => '#FFFFFF',
			'header_bg_color'              => '#F9FAFB',
			'header_border_color'          => '#E5E7EB',
			'header_title_color'           => '#111827',
			'header_subtitle_color'        => '#6B7280',
			'card_bg_color'                => '#F9FAFB',
			'card_hover_bg_color'          => '#F3F4F6',
			'card_border_color'            => '#E5E7EB',
			'input_bg_color'               => '#F8FAFC',
			'input_border_color'           => '#CBD5E1',
			'font_family'                  => 'inherit',

			// Titles & Descriptions
			'portal_title'                 => __( 'Help & Support Center', 'dragwyb-click-to-chat' ),
			'portal_subtitle'              => __( 'View your recent requests, check status updates, or start a new support conversation.', 'dragwyb-click-to-chat' ),
			'btn_new_ticket_text'          => __( 'New Support Request', 'dragwyb-click-to-chat' ),
			'btn_back_tickets_text'        => __( 'Back to My Tickets', 'dragwyb-click-to-chat' ),
			'search_placeholder'           => __( 'Search your tickets by subject or number...', 'dragwyb-click-to-chat' ),
			'loading_text'                 => __( 'Loading support tickets...', 'dragwyb-click-to-chat' ),
			'empty_tickets_title'          => __( 'No support requests found', 'dragwyb-click-to-chat' ),
			'empty_tickets_desc'           => __( 'You have not submitted any support tickets yet. Click "New Support Request" to start one.', 'dragwyb-click-to-chat' ),

			// Create Ticket Form & Modal
			'modal_title'                  => __( 'Create a New Support Request', 'dragwyb-click-to-chat' ),
			'modal_subtitle'               => __( 'Submit your inquiry and our support team will assist you shortly.', 'dragwyb-click-to-chat' ),
			'category_label'               => __( 'Category', 'dragwyb-click-to-chat' ),
			'subject_label'                => __( 'Subject', 'dragwyb-click-to-chat' ),
			'subject_placeholder'          => __( 'Enter a support issue title...', 'dragwyb-click-to-chat' ),
			'message_label'                => __( 'Message', 'dragwyb-click-to-chat' ),
			'btn_submit_ticket_text'       => __( 'Submit Support Request', 'dragwyb-click-to-chat' ),
			'btn_cancel_text'              => __( 'Cancel', 'dragwyb-click-to-chat' ),

			// Single Ticket Detail
			'btn_close_ticket_text'        => __( 'Close Ticket', 'dragwyb-click-to-chat' ),
			'btn_send_reply_text'          => __( 'Send Reply', 'dragwyb-click-to-chat' ),
			'reply_placeholder'            => __( 'Type your reply message...', 'dragwyb-click-to-chat' ),

			// Guest & Logged-out User Controls
			'enable_guest_ticket_form'     => true,
			'show_login_button'            => true,
			'show_register_button'         => true,
			'guest_auth_box_title'         => __( 'Customer Support Portal', 'dragwyb-click-to-chat' ),
			'guest_auth_box_desc'          => __( 'Please log in to your account or submit a support request directly below as a guest.', 'dragwyb-click-to-chat' ),
			'btn_login_text'               => __( 'Log In to Submit Ticket', 'dragwyb-click-to-chat' ),
			'btn_register_text'            => __( 'Register Account', 'dragwyb-click-to-chat' ),
			'btn_guest_create_ticket_text' => __( 'Submit Ticket as Guest', 'dragwyb-click-to-chat' ),
			'guest_name_label'             => __( 'Your Name', 'dragwyb-click-to-chat' ),
			'guest_name_placeholder'       => __( 'John Doe', 'dragwyb-click-to-chat' ),
			'guest_email_label'            => __( 'Your Email Address', 'dragwyb-click-to-chat' ),
			'guest_email_placeholder'      => __( 'you@example.com', 'dragwyb-click-to-chat' ),
		);
	}

	/**
	 * Get current Support Portal settings merged with defaults.
	 *
	 * @return array
	 */
	public static function get_settings() {
		$saved    = get_option( 'dctc_support_portal_settings', array() );
		$defaults = self::get_default_settings();
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}
		return wp_parse_args( $saved, $defaults );
	}

	/**
	 * Sanitize and validate Support Portal settings before saving.
	 *
	 * @param array $data Input settings.
	 * @return array Sanitized settings.
	 */
	public static function sanitize_settings( $data ) {
		$defaults  = self::get_default_settings();
		$sanitized = array();

		// Helper color sanitizer
		$clean_color = function ( $val, $default ) {
			if ( empty( $val ) || ! is_string( $val ) ) {
				return $default;
			}
			$hex = sanitize_hex_color( $val );
			return $hex ? $hex : $default;
		};

		$sanitized['preset'] = isset( $data['preset'] ) ? sanitize_key( $data['preset'] ) : $defaults['preset'];

		// Styling & Colors
		$sanitized['primary_color']          = $clean_color( $data['primary_color'] ?? '', $defaults['primary_color'] );
		$sanitized['primary_hover_color']    = $clean_color( $data['primary_hover_color'] ?? '', $defaults['primary_hover_color'] );
		$sanitized['primary_text_color']     = $clean_color( $data['primary_text_color'] ?? '', $defaults['primary_text_color'] );
		$sanitized['secondary_btn_bg']       = $clean_color( $data['secondary_btn_bg'] ?? '', $defaults['secondary_btn_bg'] );
		$sanitized['secondary_btn_text']     = $clean_color( $data['secondary_btn_text'] ?? '', $defaults['secondary_btn_text'] );
		$sanitized['secondary_btn_border']   = $clean_color( $data['secondary_btn_border'] ?? '', $defaults['secondary_btn_border'] );
		$sanitized['secondary_btn_hover_bg'] = $clean_color( $data['secondary_btn_hover_bg'] ?? '', $defaults['secondary_btn_hover_bg'] );
		$sanitized['border_color']           = $clean_color( $data['border_color'] ?? '', $defaults['border_color'] );
		$sanitized['border_width']           = isset( $data['border_width'] ) ? min( 10, max( 0, absint( $data['border_width'] ) ) ) : $defaults['border_width'];
		$sanitized['border_style']           = isset( $data['border_style'] ) && in_array( $data['border_style'], array( 'solid', 'dashed', 'none' ), true ) ? $data['border_style'] : $defaults['border_style'];
		$sanitized['border_radius']          = isset( $data['border_radius'] ) ? min( 40, max( 0, absint( $data['border_radius'] ) ) ) : $defaults['border_radius'];
		$sanitized['container_bg_color']     = $clean_color( $data['container_bg_color'] ?? '', $defaults['container_bg_color'] );
		$sanitized['header_bg_color']        = $clean_color( $data['header_bg_color'] ?? '', $defaults['header_bg_color'] );
		$sanitized['header_border_color']    = $clean_color( $data['header_border_color'] ?? '', $defaults['header_border_color'] );
		$sanitized['header_title_color']     = $clean_color( $data['header_title_color'] ?? '', $defaults['header_title_color'] );
		$sanitized['header_subtitle_color']  = $clean_color( $data['header_subtitle_color'] ?? '', $defaults['header_subtitle_color'] );
		$sanitized['card_bg_color']          = $clean_color( $data['card_bg_color'] ?? '', $defaults['card_bg_color'] );
		$sanitized['card_hover_bg_color']    = $clean_color( $data['card_hover_bg_color'] ?? '', $defaults['card_hover_bg_color'] );
		$sanitized['card_border_color']      = $clean_color( $data['card_border_color'] ?? '', $defaults['card_border_color'] );
		$sanitized['input_bg_color']         = $clean_color( $data['input_bg_color'] ?? '', $defaults['input_bg_color'] );
		$sanitized['input_border_color']     = $clean_color( $data['input_border_color'] ?? '', $defaults['input_border_color'] );
		$sanitized['font_family']            = isset( $data['font_family'] ) ? sanitize_text_field( $data['font_family'] ) : $defaults['font_family'];

		// Titles & Descriptions
		$sanitized['portal_title']          = isset( $data['portal_title'] ) ? sanitize_text_field( $data['portal_title'] ) : $defaults['portal_title'];
		$sanitized['portal_subtitle']       = isset( $data['portal_subtitle'] ) ? sanitize_text_field( $data['portal_subtitle'] ) : $defaults['portal_subtitle'];
		$sanitized['btn_new_ticket_text']   = isset( $data['btn_new_ticket_text'] ) ? sanitize_text_field( $data['btn_new_ticket_text'] ) : $defaults['btn_new_ticket_text'];
		$sanitized['btn_back_tickets_text'] = isset( $data['btn_back_tickets_text'] ) ? sanitize_text_field( $data['btn_back_tickets_text'] ) : $defaults['btn_back_tickets_text'];
		$sanitized['search_placeholder']    = isset( $data['search_placeholder'] ) ? sanitize_text_field( $data['search_placeholder'] ) : $defaults['search_placeholder'];
		$sanitized['loading_text']          = isset( $data['loading_text'] ) ? sanitize_text_field( $data['loading_text'] ) : $defaults['loading_text'];
		$sanitized['empty_tickets_title']   = isset( $data['empty_tickets_title'] ) ? sanitize_text_field( $data['empty_tickets_title'] ) : $defaults['empty_tickets_title'];
		$sanitized['empty_tickets_desc']    = isset( $data['empty_tickets_desc'] ) ? sanitize_text_field( $data['empty_tickets_desc'] ) : $defaults['empty_tickets_desc'];

		// Create Ticket Form & Modal
		$sanitized['modal_title']            = isset( $data['modal_title'] ) ? sanitize_text_field( $data['modal_title'] ) : $defaults['modal_title'];
		$sanitized['modal_subtitle']         = isset( $data['modal_subtitle'] ) ? sanitize_text_field( $data['modal_subtitle'] ) : $defaults['modal_subtitle'];
		$sanitized['category_label']         = isset( $data['category_label'] ) ? sanitize_text_field( $data['category_label'] ) : $defaults['category_label'];
		$sanitized['subject_label']          = isset( $data['subject_label'] ) ? sanitize_text_field( $data['subject_label'] ) : $defaults['subject_label'];
		$sanitized['subject_placeholder']    = isset( $data['subject_placeholder'] ) ? sanitize_text_field( $data['subject_placeholder'] ) : $defaults['subject_placeholder'];
		$sanitized['message_label']          = isset( $data['message_label'] ) ? sanitize_text_field( $data['message_label'] ) : $defaults['message_label'];
		$sanitized['btn_submit_ticket_text'] = isset( $data['btn_submit_ticket_text'] ) ? sanitize_text_field( $data['btn_submit_ticket_text'] ) : $defaults['btn_submit_ticket_text'];
		$sanitized['btn_cancel_text']        = isset( $data['btn_cancel_text'] ) ? sanitize_text_field( $data['btn_cancel_text'] ) : $defaults['btn_cancel_text'];

		// Single Ticket Detail
		$sanitized['btn_close_ticket_text'] = isset( $data['btn_close_ticket_text'] ) ? sanitize_text_field( $data['btn_close_ticket_text'] ) : $defaults['btn_close_ticket_text'];
		$sanitized['btn_send_reply_text']   = isset( $data['btn_send_reply_text'] ) ? sanitize_text_field( $data['btn_send_reply_text'] ) : $defaults['btn_send_reply_text'];
		$sanitized['reply_placeholder']     = isset( $data['reply_placeholder'] ) ? sanitize_text_field( $data['reply_placeholder'] ) : $defaults['reply_placeholder'];

		// Guest / Logged-out controls
		$sanitized['enable_guest_ticket_form']     = isset( $data['enable_guest_ticket_form'] ) ? rest_sanitize_boolean( $data['enable_guest_ticket_form'] ) : $defaults['enable_guest_ticket_form'];
		$sanitized['show_login_button']            = isset( $data['show_login_button'] ) ? rest_sanitize_boolean( $data['show_login_button'] ) : $defaults['show_login_button'];
		$sanitized['show_register_button']         = isset( $data['show_register_button'] ) ? rest_sanitize_boolean( $data['show_register_button'] ) : $defaults['show_register_button'];
		$sanitized['guest_auth_box_title']         = isset( $data['guest_auth_box_title'] ) ? sanitize_text_field( $data['guest_auth_box_title'] ) : $defaults['guest_auth_box_title'];
		$sanitized['guest_auth_box_desc']          = isset( $data['guest_auth_box_desc'] ) ? sanitize_text_field( $data['guest_auth_box_desc'] ) : $defaults['guest_auth_box_desc'];
		$sanitized['btn_login_text']               = isset( $data['btn_login_text'] ) ? sanitize_text_field( $data['btn_login_text'] ) : $defaults['btn_login_text'];
		$sanitized['btn_register_text']            = isset( $data['btn_register_text'] ) ? sanitize_text_field( $data['btn_register_text'] ) : $defaults['btn_register_text'];
		$sanitized['btn_guest_create_ticket_text'] = isset( $data['btn_guest_create_ticket_text'] ) ? sanitize_text_field( $data['btn_guest_create_ticket_text'] ) : $defaults['btn_guest_create_ticket_text'];
		$sanitized['guest_name_label']             = isset( $data['guest_name_label'] ) ? sanitize_text_field( $data['guest_name_label'] ) : $defaults['guest_name_label'];
		$sanitized['guest_name_placeholder']       = isset( $data['guest_name_placeholder'] ) ? sanitize_text_field( $data['guest_name_placeholder'] ) : $defaults['guest_name_placeholder'];
		$sanitized['guest_email_label']            = isset( $data['guest_email_label'] ) ? sanitize_text_field( $data['guest_email_label'] ) : $defaults['guest_email_label'];
		$sanitized['guest_email_placeholder']      = isset( $data['guest_email_placeholder'] ) ? sanitize_text_field( $data['guest_email_placeholder'] ) : $defaults['guest_email_placeholder'];

		return $sanitized;
	}

	/**
	 * Enqueue frontend scripts and styles when shortcode or page is present.
	 */
	public static function maybe_enqueue_portal_assets() {
		if ( class_exists( 'DCTC_Helper' ) && ! DCTC_Helper::is_support_enabled() ) {
			return;
		}

		global $post;
		$has_portal_shortcode = is_a( $post, 'WP_Post' ) && (
			has_shortcode( $post->post_content, 'dctc_support_portal' ) ||
			has_shortcode( $post->post_content, 'dctc_support_ticket_form' ) ||
			has_shortcode( $post->post_content, 'dctc_support_user_tickets' )
		);
		if ( $has_portal_shortcode || is_singular() ) {
			self::enqueue_portal_styles();
			self::enqueue_portal_scripts();
		}
	}

	/**
	 * Enqueue portal styles via wp_add_inline_style on dctc-ai-frontend-style handler.
	 */
	public static function enqueue_portal_styles() {
		static $enqueued = false;
		if ( $enqueued ) {
			return;
		}
		$enqueued = true;

		$handle = 'dctc-ai-frontend-style';

		if ( ! wp_style_is( $handle, 'registered' ) && ! wp_style_is( $handle, 'enqueued' ) ) {
			$frontend_css = file_exists( DCTC_PLUGIN_DIR . 'build/ai/frontend/style-dctc-ai-frontend.css' )
				? 'build/ai/frontend/style-dctc-ai-frontend.css'
				: 'build/ai/frontend/dctc-ai-frontend.css';
			if ( file_exists( DCTC_PLUGIN_DIR . $frontend_css ) ) {
				wp_register_style( $handle, DCTC_PLUGIN_URL . $frontend_css, array( 'dashicons' ), defined( 'DCTC_VERSION' ) ? DCTC_VERSION : '1.0.0' );
			} else {
				wp_register_style( $handle, false, array( 'dashicons' ), defined( 'DCTC_VERSION' ) ? DCTC_VERSION : '1.0.0' );
			}
		}

		if ( ! wp_style_is( $handle, 'enqueued' ) ) {
			wp_enqueue_style( $handle );
		}

		wp_add_inline_style( $handle, self::get_portal_css() );
	}

	/**
	 * Enqueue portal scripts via wp_add_inline_script on dctc-ai-frontend-script handler.
	 */
	public static function enqueue_portal_scripts() {
		static $enqueued = false;
		if ( $enqueued ) {
			return;
		}
		$enqueued = true;

		if ( function_exists( 'wp_enqueue_editor' ) ) {
			wp_enqueue_editor();
		}
		if ( function_exists( 'wp_enqueue_media' ) ) {
			wp_enqueue_media();
		}

		$handle = 'dctc-ai-frontend-script';

		if ( ! wp_script_is( $handle, 'enqueued' ) ) {
			DCTC_AI_Module::get_instance()->dctc_ai_do_enqueue_frontend_assets();
		}

		wp_add_inline_script( $handle, self::get_portal_js(), 'after' );
	}

	/**
	 * Helper to test if a given hex / rgba color is dark.
	 *
	 * @param string $hex Hex or rgb/rgba color string.
	 * @return bool True if dark, false if light.
	 */
	public static function is_dark_color( $hex ) {
		if ( empty( $hex ) || ! is_string( $hex ) ) {
			return false;
		}
		$hex = trim( $hex );
		if ( 'transparent' === $hex || 'inherit' === $hex ) {
			return false;
		}
		if ( 0 === strpos( $hex, 'rgba' ) || 0 === strpos( $hex, 'rgb' ) ) {
			preg_match_all( '/\d+/', $hex, $matches );
			if ( ! empty( $matches[0] ) && count( $matches[0] ) >= 3 ) {
				$lum = ( 0.299 * intval( $matches[0][0] ) + 0.587 * intval( $matches[0][1] ) + 0.114 * intval( $matches[0][2] ) );
				return $lum < 140;
			}
			return false;
		}
		$hex = ltrim( $hex, '#' );
		if ( strlen( $hex ) === 3 ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}
		if ( strlen( $hex ) !== 6 ) {
			return false;
		}
		$r   = hexdec( substr( $hex, 0, 2 ) );
		$g   = hexdec( substr( $hex, 2, 2 ) );
		$b   = hexdec( substr( $hex, 4, 2 ) );
		$lum = ( 0.299 * $r + 0.587 * $g + 0.114 * $b );
		return $lum < 140;
	}

	/**
	 * Helper to get active categories and taxonomy map for portal & shortcodes.
	 *
	 * @return array
	 */
	public static function get_portal_taxonomy_data() {
		$categories     = class_exists( 'DCTC_Support_Category_Service' ) ? DCTC_Support_Category_Service::get_categories( array( 'status' => 'active' ) ) : array();
		$all_taxonomies = class_exists( 'DCTC_Support_Taxonomy_Service' ) ? DCTC_Support_Taxonomy_Service::get_taxonomies() : array();

		$taxonomy_map = array();
		if ( is_array( $all_taxonomies ) ) {
			foreach ( $all_taxonomies as $tax ) {
				if ( 'category' === $tax['slug'] ) {
					continue;
				}
				$terms = array();
				if ( 'product' === $tax['slug'] ) {
					if ( class_exists( 'DCTC_Support_Product_Service' ) ) {
						$products = DCTC_Support_Product_Service::get_products( array( 'status' => 'active' ) );
						if ( is_array( $products ) ) {
							foreach ( $products as $prod ) {
								$terms[] = array(
									'id'   => $prod['id'],
									'name' => $prod['name'],
									'slug' => ! empty( $prod['slug'] ) ? $prod['slug'] : sanitize_title( $prod['name'] ),
								);
							}
						}
					}
				} elseif ( 'tag' === $tax['slug'] ) {
					if ( class_exists( 'DCTC_Support_Tag_Service' ) ) {
						$raw_tags = DCTC_Support_Tag_Service::get_tags();
						if ( is_array( $raw_tags ) ) {
							foreach ( $raw_tags as $t ) {
								$terms[] = array(
									'id'   => $t['id'],
									'name' => $t['name'],
									'slug' => ! empty( $t['slug'] ) ? $t['slug'] : sanitize_title( $t['name'] ),
								);
							}
						}
					}
				} else {
					$terms = DCTC_Support_Taxonomy_Service::get_terms( $tax['slug'] );
				}

				$taxonomy_map[ $tax['slug'] ] = array(
					'slug'        => $tax['slug'],
					'name'        => $tax['name'],
					'description' => ! empty( $tax['description'] ) ? $tax['description'] : '',
					'terms'       => is_array( $terms ) ? array_values( $terms ) : array(),
				);
			}
		}

		return array(
			'categories'   => is_array( $categories ) ? $categories : array(),
			'taxonomy_map' => $taxonomy_map,
		);
	}

	/**
	 * Render the Full Customer Support Portal shortcode: [dctc_support_portal]
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public static function render_portal_shortcode( $atts = array() ) {
		if ( class_exists( 'DCTC_Helper' ) && ! DCTC_Helper::is_support_enabled() ) {
			return '';
		}

		self::enqueue_portal_styles();
		self::enqueue_portal_scripts();

		$settings = self::get_settings();

		$user_id   = get_current_user_id();
		$user      = $user_id ? get_userdata( $user_id ) : null;
		$user_name = $user ? $user->display_name : '';
		$email     = $user ? $user->user_email : '';

		$tax_data     = self::get_portal_taxonomy_data();
		$categories   = $tax_data['categories'];
		$taxonomy_map = $tax_data['taxonomy_map'];

		$rest_url         = esc_url_raw( rest_url( 'dctc-ai/v1/support/portal' ) );
		$nonce            = wp_create_nonce( 'wp_rest' );
		$guest_allowed    = ! empty( $settings['enable_guest_ticket_form'] );
		$can_render_modal = $user_id || $guest_allowed;

		ob_start();
		?>
		<div id="dctc-support-portal" class="dctc-portal-root" 
			data-rest-url="<?php echo esc_attr( $rest_url ); ?>" 
			data-nonce="<?php echo esc_attr( $nonce ); ?>" 
			data-user-logged-in="<?php echo $user_id ? '1' : '0'; ?>" 
			data-user-name="<?php echo esc_attr( $user_name ); ?>" 
			data-user-email="<?php echo esc_attr( $email ); ?>" 
			data-guest-enabled="<?php echo $guest_allowed ? '1' : '0'; ?>"
			data-taxonomies-data="<?php echo esc_attr( wp_json_encode( $taxonomy_map ) ); ?>"
			data-empty-title="<?php echo esc_attr( $settings['empty_tickets_title'] ); ?>"
			data-empty-desc="<?php echo esc_attr( $settings['empty_tickets_desc'] ); ?>"
			data-loading-text="<?php echo esc_attr( $settings['loading_text'] ); ?>"
			data-btn-submit-text="<?php echo esc_attr( $settings['btn_submit_ticket_text'] ); ?>"
			data-btn-send-reply-text="<?php echo esc_attr( $settings['btn_send_reply_text'] ); ?>">
			
			<!-- Portal Header -->
			<div class="dctc-portal-header">
				<div class="dctc-portal-header-left">
					<h2><?php echo esc_html( $settings['portal_title'] ); ?></h2>
					<p><?php echo esc_html( $settings['portal_subtitle'] ); ?></p>
				</div>
				<div class="dctc-portal-header-right">
					<?php if ( $user_id ) : ?>
						<button type="button" class="dctc-portal-btn-primary dctc-portal-btn-new-trigger">
							<span class="dashicons dashicons-plus-alt2"></span>
							<?php echo esc_html( $settings['btn_new_ticket_text'] ); ?>
						</button>
					<?php elseif ( $guest_allowed ) : ?>
						<button type="button" class="dctc-portal-btn-primary dctc-portal-btn-guest-header-trigger">
							<span class="dashicons dashicons-plus-alt2"></span>
							<?php echo esc_html( $settings['btn_guest_create_ticket_text'] ); ?>
						</button>
					<?php endif; ?>
					<button type="button" class="dctc-portal-btn-secondary dctc-portal-btn-back-trigger" style="display:none;">
						<span class="dashicons dashicons-arrow-left-alt"></span>
						<?php echo esc_html( $settings['btn_back_tickets_text'] ); ?>
					</button>
				</div>
			</div>

			<!-- View 1: Ticket List or Auth Box -->
			<div class="dctc-portal-view-list dctc-portal-view active">
				<?php if ( $user_id ) : ?>
					<div class="dctc-portal-filter-row">
						<div class="dctc-portal-filter-search-wrap">
							<span class="dashicons dashicons-search"></span>
							<input type="text" placeholder="<?php echo esc_attr( $settings['search_placeholder'] ); ?>" class="dctc-portal-input dctc-portal-filter-search" />
						</div>
						<div class="dctc-portal-filter-select-wrap">
							<select class="dctc-portal-select dctc-portal-filter-status">
								<option value="all"><?php esc_html_e( 'All Statuses', 'dragwyb-click-to-chat' ); ?></option>
								<option value="open"><?php esc_html_e( 'Open', 'dragwyb-click-to-chat' ); ?></option>
								<option value="pending"><?php esc_html_e( 'Pending', 'dragwyb-click-to-chat' ); ?></option>
								<option value="resolved"><?php esc_html_e( 'Resolved', 'dragwyb-click-to-chat' ); ?></option>
								<option value="closed"><?php esc_html_e( 'Closed', 'dragwyb-click-to-chat' ); ?></option>
							</select>
						</div>
						<?php if ( ! empty( $categories ) ) : ?>
						<div class="dctc-portal-filter-select-wrap">
							<select class="dctc-portal-select dctc-portal-filter-category">
								<option value="all"><?php esc_html_e( 'All Categories', 'dragwyb-click-to-chat' ); ?></option>
								<?php foreach ( $categories as $cat ) : ?>
									<option value="<?php echo esc_attr( $cat['id'] ); ?>"><?php echo esc_html( $cat['name'] ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<?php endif; ?>
					</div>
					<div class="dctc-portal-tickets-container dctc-portal-tickets-list">
						<div class="dctc-portal-loading"><?php echo esc_html( $settings['loading_text'] ); ?></div>
					</div>
				<?php else : ?>
					<!-- Logged-out Visitor Box -->
					<div class="dctc-portal-auth-prompt">
						<div class="dctc-portal-auth-icon">
							<span class="dashicons dashicons-lock"></span>
						</div>
						<h3><?php echo esc_html( $settings['guest_auth_box_title'] ); ?></h3>
						<p><?php echo esc_html( $settings['guest_auth_box_desc'] ); ?></p>
						<div class="dctc-portal-auth-actions">
							<?php if ( $guest_allowed ) : ?>
								<button type="button" class="dctc-portal-btn-primary dctc-portal-btn-guest-action-trigger">
									<span class="dashicons dashicons-edit"></span>
									<?php echo esc_html( $settings['btn_guest_create_ticket_text'] ); ?>
								</button>
							<?php endif; ?>
							<?php if ( ! empty( $settings['show_login_button'] ) ) : ?>
								<a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>" class="dctc-portal-btn-secondary">
									<span class="dashicons dashicons-admin-users"></span>
									<?php echo esc_html( $settings['btn_login_text'] ); ?>
								</a>
							<?php endif; ?>
							<?php if ( ! empty( $settings['show_register_button'] ) && get_option( 'users_can_register' ) ) : ?>
								<a href="<?php echo esc_url( wp_registration_url() ); ?>" class="dctc-portal-btn-secondary">
									<span class="dashicons dashicons-plus"></span>
									<?php echo esc_html( $settings['btn_register_text'] ); ?>
								</a>
							<?php endif; ?>
						</div>
					</div>

					<!-- Container for returning guest who has a saved ticket token -->
					<div class="dctc-portal-guest-recent-section" style="display:none; margin-top:24px;">
						<h4 style="margin:0 0 12px; font-size:15px; font-weight:700; color:var(--dctc-portal-header-title, #111827);"><?php esc_html_e( 'Your Recent Support Request', 'dragwyb-click-to-chat' ); ?></h4>
						<div class="dctc-portal-tickets-container dctc-portal-tickets-list"></div>
					</div>
				<?php endif; ?>
			</div>

			<?php if ( $can_render_modal ) : ?>
			<!-- Popup Modal: Create a New Support Request (Logged-in or Guest) -->
			<div class="dctc-portal-new-modal dctc-portal-modal-backdrop" style="display:none;">
				<div class="dctc-portal-modal-card" role="dialog" aria-modal="true" aria-labelledby="dctc-modal-title">
					
					<!-- Modal Top Header -->
					<div class="dctc-portal-modal-header">
						<div class="dctc-portal-modal-header-info">
							<div class="dctc-portal-modal-icon-badge">
								<span class="dashicons dashicons-format-chat"></span>
							</div>
							<div>
								<h3 class="dctc-portal-modal-title"><?php echo esc_html( $settings['modal_title'] ); ?></h3>
								<p class="dctc-portal-modal-desc"><?php echo esc_html( $settings['modal_subtitle'] ); ?></p>
							</div>
						</div>
						<button type="button" class="dctc-portal-modal-close-btn" title="<?php esc_attr_e( 'Close', 'dragwyb-click-to-chat' ); ?>">
							<span class="dashicons dashicons-no-alt"></span>
						</button>
					</div>

					<!-- Form Body -->
					<form class="dctc-portal-new-ticket-form dctc-portal-form">
						<div class="dctc-portal-modal-body-scroll">

							<?php if ( ! $user_id ) : ?>
							<!-- Guest Name & Email Input Row for Logged-out Visitors -->
							<div class="dctc-form-grid-2" style="margin-bottom: 16px;">
								<div class="dctc-form-group">
									<label>
										<?php echo esc_html( $settings['guest_name_label'] ); ?>
										<span class="dctc-portal-required">*</span>
									</label>
									<input type="text" required class="dctc-portal-input dctc-new-name" placeholder="<?php echo esc_attr( $settings['guest_name_placeholder'] ); ?>" />
								</div>
								<div class="dctc-form-group">
									<label>
										<?php echo esc_html( $settings['guest_email_label'] ); ?>
										<span class="dctc-portal-required">*</span>
									</label>
									<input type="email" required class="dctc-portal-input dctc-new-email" placeholder="<?php echo esc_attr( $settings['guest_email_placeholder'] ); ?>" />
								</div>
							</div>
							<?php endif; ?>

							<div class="dctc-form-group">
								<label>
									<?php echo esc_html( $settings['category_label'] ); ?>
									<span class="dctc-portal-required">*</span>
								</label>
								<select class="dctc-portal-select dctc-new-category">
									<option value="0" data-sub-taxonomies='["product","tag"]'><?php esc_html_e( 'General Inquiry', 'dragwyb-click-to-chat' ); ?></option>
									<?php foreach ( $categories as $cat ) : ?>
										<?php
										$sub_tax_json = ! empty( $cat['sub_taxonomies'] ) && is_array( $cat['sub_taxonomies'] )
											? wp_json_encode( $cat['sub_taxonomies'] )
											: wp_json_encode( array_filter( array( ! empty( $cat['show_product'] ) ? 'product' : '', ! empty( $cat['show_tags'] ) ? 'tag' : '' ) ) );
										?>
										<option value="<?php echo esc_attr( $cat['id'] ); ?>" data-sub-taxonomies="<?php echo esc_attr( $sub_tax_json ); ?>">
											<?php echo esc_html( $cat['name'] ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>

							<!-- Dynamic Sub-Fields (Rendered dynamically in category-defined order with term dropdowns) -->
							<div class="dctc-portal-dynamic-subfields dctc-form-grid-2" style="display:none; margin-bottom:16px;"></div>

							<div class="dctc-form-group">
								<label>
									<?php echo esc_html( $settings['subject_label'] ); ?>
									<span class="dctc-portal-required">*</span>
								</label>
								<input type="text" required class="dctc-portal-input dctc-new-subject" placeholder="<?php echo esc_attr( $settings['subject_placeholder'] ); ?>" />
							</div>

							<div class="dctc-form-group">
								<label>
									<?php echo esc_html( $settings['message_label'] ); ?>
									<span class="dctc-portal-required">*</span>
								</label>
								<div class="dctc-portal-editor-wrapper">
									<?php
									$content    = '';
									$editor_id  = 'dctcportalnewmessage';
									$editor_opt = array(
										'textarea_name' => 'ticket_message',
										'media_buttons' => true,
										'textarea_rows' => 8,
										'teeny'         => false,
										'quicktags'     => false,
										'tinymce'       => array(
											'toolbar1' => 'bold,italic,underline,strikethrough,bullist,numlist,blockquote,link,unlink,undo,redo',
											'toolbar2' => '',
											'toolbar3' => '',
											'toolbar4' => '',
										),
									);
									wp_editor( $content, $editor_id, $editor_opt );
									?>
								</div>
							</div>
						</div>

						<!-- Modal Footer Action Bar -->
						<div class="dctc-portal-modal-footer">
							<button type="button" class="dctc-portal-btn-secondary dctc-portal-modal-cancel">
								<?php echo esc_html( $settings['btn_cancel_text'] ); ?>
							</button>
							<button type="submit" class="dctc-portal-btn-primary dctc-new-submit-btn">
								<span class="dashicons dashicons-saved" style="font-size:16px;line-height:1;margin-top:1px;"></span>
								<?php echo esc_html( $settings['btn_submit_ticket_text'] ); ?>
							</button>
						</div>
					</form>
				</div>
			</div>
			<?php endif; ?>

			<!-- View 2: Single Ticket Conversation Detail -->
			<div class="dctc-portal-view-detail dctc-portal-view">
				<div class="dctc-detail-header">
					<div class="dctc-detail-header-left">
						<div class="dctc-detail-badges">
							<span class="dctc-detail-num dctc-detail-ticket-num">#0000</span>
							<span class="dctc-detail-status dctc-badge dctc-badge-open">OPEN</span>
							<span class="dctc-detail-priority dctc-badge dctc-badge-priority-normal">NORMAL</span>
							<span class="dctc-detail-category dctc-portal-badge-cat">Category</span>
							<span class="dctc-detail-agent dctc-portal-badge-agent">Agent</span>
							<span class="dctc-detail-chats dctc-portal-badge-chats">0 chats</span>
						</div>
						<div class="dctc-detail-tags-row dctc-portal-tags-row" style="margin-top: 6px;"></div>
						<h3 class="dctc-detail-subject"><?php esc_html_e( 'Ticket Subject', 'dragwyb-click-to-chat' ); ?></h3>
					</div>
					<div class="dctc-detail-header-right">
						<button type="button" class="dctc-portal-btn-secondary dctc-detail-close-btn">
							<?php echo esc_html( $settings['btn_close_ticket_text'] ); ?>
						</button>
					</div>
				</div>

				<!-- Messages Timeline -->
				<div class="dctc-portal-detail-messages dctc-portal-messages-timeline"></div>

				<!-- Reply Box -->
				<form class="dctc-portal-reply-form dctc-portal-reply-box">
					<div class="dctc-portal-editor-wrapper">
						<?php
						$content    = '';
						$editor_id  = 'dctcportalreplymessage';
						$editor_opt = array(
							'textarea_name' => 'reply_message',
							'media_buttons' => true,
							'textarea_rows' => 5,
							'teeny'         => false,
							'quicktags'     => false,
							'tinymce'       => array(
								'toolbar1' => 'bold,italic,underline,strikethrough,bullist,numlist,blockquote,link,unlink,undo,redo',
								'toolbar2' => '',
								'toolbar3' => '',
								'toolbar4' => '',
							),
						);
						wp_editor( $content, $editor_id, $editor_opt );
						?>
					</div>
					<div class="dctc-portal-reply-actions">
						<button type="submit" class="dctc-portal-btn-primary dctc-portal-send-reply-btn">
							<span class="dashicons dashicons-send" style="font-size:15px;line-height:1;margin-top:1px;"></span>
							<?php echo esc_html( $settings['btn_send_reply_text'] ); ?>
						</button>
					</div>
				</form>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render Standalone Create New Ticket Form / Custom Button Trigger shortcode: [dctc_support_ticket_form]
	 *
	 * Attributes:
	 * - trigger_id: ID or CSS selector of custom button to open the modal (e.g. "my-custom-btn" or "#my-btn" or ".open-ticket")
	 * - inline: "true" to render form directly in page content without popup modal
	 * - button_text: Custom text on default launcher button (when trigger_id not provided)
	 * - title: Custom modal / form title
	 * - subtitle: Custom description text
	 * - category_id: Preselected category ID
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public static function render_create_ticket_shortcode( $atts = array() ) {
		self::enqueue_portal_styles();
		self::enqueue_portal_scripts();

		$settings = self::get_settings();
		$a        = shortcode_atts(
			array(
				'trigger_id'        => '',
				'trigger_button_id' => '',
				'trigger_selector'  => '',
				'inline'            => 'false',
				'button_text'       => '',
				'button_class'      => 'dctc-portal-btn-primary',
				'title'             => '',
				'subtitle'          => '',
				'category_id'       => '0',
				'show_header'       => 'true',
			),
			$atts,
			'dctc_support_ticket_form'
		);

		$trigger_selector = ! empty( $a['trigger_selector'] )
			? $a['trigger_selector']
			: ( ! empty( $a['trigger_id'] ) ? $a['trigger_id'] : $a['trigger_button_id'] );

		$is_inline = filter_var( $a['inline'], FILTER_VALIDATE_BOOLEAN );

		$user_id   = get_current_user_id();
		$user      = $user_id ? get_userdata( $user_id ) : null;
		$user_name = $user ? $user->display_name : '';
		$email     = $user ? $user->user_email : '';

		$guest_allowed   = ! empty( $settings['enable_guest_ticket_form'] );
		$can_render_form = $user_id || $guest_allowed;

		if ( ! $can_render_form ) {
			return '<div class="dctc-portal-auth-prompt" style="padding:24px;text-align:center;">' .
				'<h4 style="margin:0 0 8px;font-size:16px;">' . esc_html__( 'Customer Support Ticket', 'dragwyb-click-to-chat' ) . '</h4>' .
				'<p style="margin:0 0 14px;color:#64748B;">' . esc_html__( 'Please log in to your account to submit a support request.', 'dragwyb-click-to-chat' ) . '</p>' .
				'<a href="' . esc_url( wp_login_url( get_permalink() ) ) . '" class="dctc-portal-btn-primary"><span class="dashicons dashicons-admin-users"></span> ' . esc_html__( 'Log In', 'dragwyb-click-to-chat' ) . '</a>' .
				'</div>';
		}

		$tax_data     = self::get_portal_taxonomy_data();
		$categories   = $tax_data['categories'];
		$taxonomy_map = $tax_data['taxonomy_map'];

		$rest_url       = esc_url_raw( rest_url( 'dctc-ai/v1/support/portal' ) );
		$nonce          = wp_create_nonce( 'wp_rest' );
		$uid            = wp_generate_uuid4();
		$editor_id      = 'dctcnewtkt' . str_replace( '-', '', substr( $uid, 0, 8 ) );
		$modal_title    = ! empty( $a['title'] ) ? $a['title'] : $settings['modal_title'];
		$modal_subtitle = ! empty( $a['subtitle'] ) ? $a['subtitle'] : $settings['modal_subtitle'];
		$btn_text       = ! empty( $a['button_text'] ) ? $a['button_text'] : ( $user_id ? $settings['btn_new_ticket_text'] : $settings['btn_guest_create_ticket_text'] );
		$pre_cat_id     = absint( $a['category_id'] );

		ob_start();
		?>
		<div class="dctc-create-ticket-root" 
			data-rest-url="<?php echo esc_attr( $rest_url ); ?>" 
			data-nonce="<?php echo esc_attr( $nonce ); ?>" 
			data-user-logged-in="<?php echo $user_id ? '1' : '0'; ?>" 
			data-user-name="<?php echo esc_attr( $user_name ); ?>" 
			data-user-email="<?php echo esc_attr( $email ); ?>" 
			data-guest-enabled="<?php echo $guest_allowed ? '1' : '0'; ?>"
			data-taxonomies-data="<?php echo esc_attr( wp_json_encode( $taxonomy_map ) ); ?>"
			data-trigger-selector="<?php echo esc_attr( $trigger_selector ); ?>"
			data-editor-id="<?php echo esc_attr( $editor_id ); ?>"
			data-is-inline="<?php echo $is_inline ? '1' : '0'; ?>">

			<?php if ( $is_inline ) : ?>
				<!-- Inline Create Ticket Card -->
				<div class="dctc-create-ticket-inline-card">
					<?php if ( filter_var( $a['show_header'], FILTER_VALIDATE_BOOLEAN ) ) : ?>
					<div class="dctc-portal-modal-header" style="border-radius:var(--dctc-portal-radius, 12px) var(--dctc-portal-radius, 12px) 0 0;">
						<div class="dctc-portal-modal-header-info">
							<div class="dctc-portal-modal-icon-badge">
								<span class="dashicons dashicons-format-chat"></span>
							</div>
							<div>
								<h3 class="dctc-portal-modal-title"><?php echo esc_html( $modal_title ); ?></h3>
								<p class="dctc-portal-modal-desc"><?php echo esc_html( $modal_subtitle ); ?></p>
							</div>
						</div>
					</div>
					<?php endif; ?>

					<div class="dctc-create-ticket-success-notice" style="display:none; padding:20px; text-align:center;">
						<div style="width:48px; height:48px; border-radius:50%; background:#ECFDF5; color:#059669; display:inline-flex; align-items:center; justify-content:center; margin-bottom:12px;">
							<span class="dashicons dashicons-yes-alt" style="font-size:28px; width:28px; height:28px;"></span>
						</div>
						<h4 style="margin:0 0 6px; font-size:17px; color:var(--dctc-portal-card-title, #0F172A);"><?php esc_html_e( 'Support Request Submitted!', 'dragwyb-click-to-chat' ); ?></h4>
						<p class="dctc-success-ticket-info" style="margin:0 0 16px; font-size:13.5px; color:var(--dctc-portal-header-subtitle, #64748B);"></p>
						<button type="button" class="dctc-portal-btn-primary dctc-create-another-btn">
							<span class="dashicons dashicons-plus-alt2"></span>
							<?php esc_html_e( 'Submit Another Ticket', 'dragwyb-click-to-chat' ); ?>
						</button>
					</div>

					<form class="dctc-portal-new-ticket-form dctc-portal-form" style="padding:22px;">
						<?php if ( ! $user_id ) : ?>
						<div class="dctc-form-grid-2" style="margin-bottom: 16px;">
							<div class="dctc-form-group">
								<label>
									<?php echo esc_html( $settings['guest_name_label'] ); ?>
									<span class="dctc-portal-required">*</span>
								</label>
								<input type="text" required class="dctc-portal-input dctc-new-name" placeholder="<?php echo esc_attr( $settings['guest_name_placeholder'] ); ?>" />
							</div>
							<div class="dctc-form-group">
								<label>
									<?php echo esc_html( $settings['guest_email_label'] ); ?>
									<span class="dctc-portal-required">*</span>
								</label>
								<input type="email" required class="dctc-portal-input dctc-new-email" placeholder="<?php echo esc_attr( $settings['guest_email_placeholder'] ); ?>" />
							</div>
						</div>
						<?php endif; ?>

						<div class="dctc-form-group">
							<label>
								<?php echo esc_html( $settings['category_label'] ); ?>
								<span class="dctc-portal-required">*</span>
							</label>
							<select class="dctc-portal-select dctc-new-category">
								<option value="0" data-sub-taxonomies='["product","tag"]'><?php esc_html_e( 'General Inquiry', 'dragwyb-click-to-chat' ); ?></option>
								<?php foreach ( $categories as $cat ) : ?>
									<?php
									$sub_tax_json = ! empty( $cat['sub_taxonomies'] ) && is_array( $cat['sub_taxonomies'] )
										? wp_json_encode( $cat['sub_taxonomies'] )
										: wp_json_encode( array_filter( array( ! empty( $cat['show_product'] ) ? 'product' : '', ! empty( $cat['show_tags'] ) ? 'tag' : '' ) ) );
									?>
									<option value="<?php echo esc_attr( $cat['id'] ); ?>" <?php selected( $pre_cat_id, $cat['id'] ); ?> data-sub-taxonomies="<?php echo esc_attr( $sub_tax_json ); ?>">
										<?php echo esc_html( $cat['name'] ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="dctc-portal-dynamic-subfields dctc-form-grid-2" style="display:none; margin-bottom:16px;"></div>

						<div class="dctc-form-group">
							<label>
								<?php echo esc_html( $settings['subject_label'] ); ?>
								<span class="dctc-portal-required">*</span>
							</label>
							<input type="text" required class="dctc-portal-input dctc-new-subject" placeholder="<?php echo esc_attr( $settings['subject_placeholder'] ); ?>" />
						</div>

						<div class="dctc-form-group">
							<label>
								<?php echo esc_html( $settings['message_label'] ); ?>
								<span class="dctc-portal-required">*</span>
							</label>
							<div class="dctc-portal-editor-wrapper">
								<?php
								$content    = '';
								$editor_opt = array(
									'textarea_name' => 'ticket_message',
									'media_buttons' => true,
									'textarea_rows' => 8,
									'teeny'         => false,
									'quicktags'     => false,
									'tinymce'       => array(
										'toolbar1' => 'bold,italic,underline,strikethrough,bullist,numlist,blockquote,link,unlink,undo,redo',
										'toolbar2' => '',
										'toolbar3' => '',
										'toolbar4' => '',
									),
								);
								wp_editor( $content, $editor_id, $editor_opt );
								?>
							</div>
						</div>

						<div style="display:flex; justify-content:flex-end; margin-top:16px;">
							<button type="submit" class="dctc-portal-btn-primary dctc-new-submit-btn">
								<span class="dashicons dashicons-saved" style="font-size:16px;line-height:1;margin-top:1px;"></span>
								<?php echo esc_html( $settings['btn_submit_ticket_text'] ); ?>
							</button>
						</div>
					</form>
				</div>
			<?php else : ?>
				<!-- Default Launcher Button if no custom trigger ID is provided -->
				<?php if ( empty( $trigger_selector ) ) : ?>
					<button type="button" class="<?php echo esc_attr( $a['button_class'] ); ?> dctc-portal-btn-new-trigger">
						<span class="dashicons dashicons-plus-alt2"></span>
						<?php echo esc_html( $btn_text ); ?>
					</button>
				<?php endif; ?>

				<!-- Popup Modal -->
				<div class="dctc-portal-new-modal dctc-portal-modal-backdrop" style="display:none;">
					<div class="dctc-portal-modal-card" role="dialog" aria-modal="true" aria-labelledby="dctc-modal-title">
						<div class="dctc-portal-modal-header">
							<div class="dctc-portal-modal-header-info">
								<div class="dctc-portal-modal-icon-badge">
									<span class="dashicons dashicons-format-chat"></span>
								</div>
								<div>
									<h3 class="dctc-portal-modal-title"><?php echo esc_html( $modal_title ); ?></h3>
									<p class="dctc-portal-modal-desc"><?php echo esc_html( $modal_subtitle ); ?></p>
								</div>
							</div>
							<button type="button" class="dctc-portal-modal-close-btn" title="<?php esc_attr_e( 'Close', 'dragwyb-click-to-chat' ); ?>">
								<span class="dashicons dashicons-no-alt"></span>
							</button>
						</div>

						<form class="dctc-portal-new-ticket-form dctc-portal-form">
							<div class="dctc-portal-modal-body-scroll">
								<?php if ( ! $user_id ) : ?>
								<div class="dctc-form-grid-2" style="margin-bottom: 16px;">
									<div class="dctc-form-group">
										<label>
											<?php echo esc_html( $settings['guest_name_label'] ); ?>
											<span class="dctc-portal-required">*</span>
										</label>
										<input type="text" required class="dctc-portal-input dctc-new-name" placeholder="<?php echo esc_attr( $settings['guest_name_placeholder'] ); ?>" />
									</div>
									<div class="dctc-form-group">
										<label>
											<?php echo esc_html( $settings['guest_email_label'] ); ?>
											<span class="dctc-portal-required">*</span>
										</label>
										<input type="email" required class="dctc-portal-input dctc-new-email" placeholder="<?php echo esc_attr( $settings['guest_email_placeholder'] ); ?>" />
									</div>
								</div>
								<?php endif; ?>

								<div class="dctc-form-group">
									<label>
										<?php echo esc_html( $settings['category_label'] ); ?>
										<span class="dctc-portal-required">*</span>
									</label>
									<select class="dctc-portal-select dctc-new-category">
										<option value="0" data-sub-taxonomies='["product","tag"]'><?php esc_html_e( 'General Inquiry', 'dragwyb-click-to-chat' ); ?></option>
										<?php foreach ( $categories as $cat ) : ?>
											<?php
											$sub_tax_json = ! empty( $cat['sub_taxonomies'] ) && is_array( $cat['sub_taxonomies'] )
												? wp_json_encode( $cat['sub_taxonomies'] )
												: wp_json_encode( array_filter( array( ! empty( $cat['show_product'] ) ? 'product' : '', ! empty( $cat['show_tags'] ) ? 'tag' : '' ) ) );
											?>
											<option value="<?php echo esc_attr( $cat['id'] ); ?>" <?php selected( $pre_cat_id, $cat['id'] ); ?> data-sub-taxonomies="<?php echo esc_attr( $sub_tax_json ); ?>">
												<?php echo esc_html( $cat['name'] ); ?>
											</option>
										<?php endforeach; ?>
									</select>
								</div>

								<div class="dctc-portal-dynamic-subfields dctc-form-grid-2" style="display:none; margin-bottom:16px;"></div>

								<div class="dctc-form-group">
									<label>
										<?php echo esc_html( $settings['subject_label'] ); ?>
										<span class="dctc-portal-required">*</span>
									</label>
									<input type="text" required class="dctc-portal-input dctc-new-subject" placeholder="<?php echo esc_attr( $settings['subject_placeholder'] ); ?>" />
								</div>

								<div class="dctc-form-group">
									<label>
										<?php echo esc_html( $settings['message_label'] ); ?>
										<span class="dctc-portal-required">*</span>
									</label>
									<div class="dctc-portal-editor-wrapper">
										<?php
										$content    = '';
										$editor_opt = array(
											'textarea_name' => 'ticket_message',
											'media_buttons' => true,
											'textarea_rows' => 8,
											'teeny'     => false,
											'quicktags' => false,
											'tinymce'   => array(
												'toolbar1' => 'bold,italic,underline,strikethrough,bullist,numlist,blockquote,link,unlink,undo,redo',
												'toolbar2' => '',
												'toolbar3' => '',
												'toolbar4' => '',
											),
										);
										wp_editor( $content, $editor_id, $editor_opt );
										?>
									</div>
								</div>
							</div>

							<div class="dctc-portal-modal-footer">
								<button type="button" class="dctc-portal-btn-secondary dctc-portal-modal-cancel">
									<?php echo esc_html( $settings['btn_cancel_text'] ); ?>
								</button>
								<button type="submit" class="dctc-portal-btn-primary dctc-new-submit-btn">
									<span class="dashicons dashicons-saved" style="font-size:16px;line-height:1;margin-top:1px;"></span>
									<?php echo esc_html( $settings['btn_submit_ticket_text'] ); ?>
								</button>
							</div>
						</form>
					</div>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render Standalone User Tickets Table with Search & Multi-Filters: [dctc_support_user_tickets]
	 *
	 * Attributes:
	 * - status: Initial status filter ("all", "open", "pending", "resolved", "closed")
	 * - category: Category filter ID or slug
	 * - per_page / limit: Number of tickets to load
	 * - show_search: "true" / "false"
	 * - show_filters: "true" / "false"
	 * - show_create_button: "true" / "false"
	 * - show_header: "true" / "false"
	 * - title: Custom title
	 * - subtitle: Custom description
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public static function render_tickets_table_shortcode( $atts = array() ) {
		self::enqueue_portal_styles();
		self::enqueue_portal_scripts();

		$settings = self::get_settings();
		$a        = shortcode_atts(
			array(
				'status'             => 'all',
				'category'           => 'all',
				'category_id'        => '',
				'per_page'           => 10,
				'limit'              => '',
				'show_search'        => 'true',
				'show_filters'       => 'true',
				'show_filter'        => '',
				'show_create_button' => 'true',
				'show_header'        => 'true',
				'title'              => '',
				'subtitle'           => '',
			),
			$atts,
			'dctc_support_user_tickets'
		);

		$per_page = ! empty( $a['limit'] ) ? absint( $a['limit'] ) : absint( $a['per_page'] );
		if ( ! $per_page ) {
			$per_page = 10;
		}

		$show_search  = filter_var( $a['show_search'], FILTER_VALIDATE_BOOLEAN );
		$show_filters = '' !== $a['show_filter'] ? filter_var( $a['show_filter'], FILTER_VALIDATE_BOOLEAN ) : filter_var( $a['show_filters'], FILTER_VALIDATE_BOOLEAN );
		$show_create  = filter_var( $a['show_create_button'], FILTER_VALIDATE_BOOLEAN );
		$show_header  = filter_var( $a['show_header'], FILTER_VALIDATE_BOOLEAN );

		$user_id   = get_current_user_id();
		$user      = $user_id ? get_userdata( $user_id ) : null;
		$user_name = $user ? $user->display_name : '';
		$email     = $user ? $user->user_email : '';

		$tax_data     = self::get_portal_taxonomy_data();
		$categories   = $tax_data['categories'];
		$taxonomy_map = $tax_data['taxonomy_map'];

		$rest_url         = esc_url_raw( rest_url( 'dctc-ai/v1/support/portal' ) );
		$nonce            = wp_create_nonce( 'wp_rest' );
		$guest_allowed    = ! empty( $settings['enable_guest_ticket_form'] );
		$can_render_modal = ( $user_id || $guest_allowed ) && $show_create;

		$table_title    = ! empty( $a['title'] ) ? $a['title'] : __( 'My Support Tickets', 'dragwyb-click-to-chat' );
		$table_subtitle = ! empty( $a['subtitle'] ) ? $a['subtitle'] : __( 'Track status updates, search inquiries, and view conversation history.', 'dragwyb-click-to-chat' );

		$cat_filter_val = ! empty( $a['category_id'] ) ? $a['category_id'] : $a['category'];

		ob_start();
		?>
		<div class="dctc-portal-root dctc-tickets-table-root" 
			data-rest-url="<?php echo esc_attr( $rest_url ); ?>" 
			data-nonce="<?php echo esc_attr( $nonce ); ?>" 
			data-user-logged-in="<?php echo $user_id ? '1' : '0'; ?>" 
			data-user-name="<?php echo esc_attr( $user_name ); ?>" 
			data-user-email="<?php echo esc_attr( $email ); ?>" 
			data-guest-enabled="<?php echo $guest_allowed ? '1' : '0'; ?>"
			data-taxonomies-data="<?php echo esc_attr( wp_json_encode( $taxonomy_map ) ); ?>"
			data-per-page="<?php echo esc_attr( $per_page ); ?>"
			data-initial-status="<?php echo esc_attr( $a['status'] ); ?>"
			data-initial-category="<?php echo esc_attr( $cat_filter_val ); ?>"
			data-empty-title="<?php echo esc_attr( $settings['empty_tickets_title'] ); ?>"
			data-empty-desc="<?php echo esc_attr( $settings['empty_tickets_desc'] ); ?>"
			data-loading-text="<?php echo esc_attr( $settings['loading_text'] ); ?>"
			data-btn-submit-text="<?php echo esc_attr( $settings['btn_submit_ticket_text'] ); ?>"
			data-btn-send-reply-text="<?php echo esc_attr( $settings['btn_send_reply_text'] ); ?>">

			<?php if ( $show_header ) : ?>
			<div class="dctc-portal-header">
				<div class="dctc-portal-header-left">
					<h2><?php echo esc_html( $table_title ); ?></h2>
					<p><?php echo esc_html( $table_subtitle ); ?></p>
				</div>
				<div class="dctc-portal-header-right">
					<?php if ( $show_create ) : ?>
						<?php if ( $user_id ) : ?>
							<button type="button" class="dctc-portal-btn-primary dctc-portal-btn-new-trigger">
								<span class="dashicons dashicons-plus-alt2"></span>
								<?php echo esc_html( $settings['btn_new_ticket_text'] ); ?>
							</button>
						<?php elseif ( $guest_allowed ) : ?>
							<button type="button" class="dctc-portal-btn-primary dctc-portal-btn-guest-header-trigger">
								<span class="dashicons dashicons-plus-alt2"></span>
								<?php echo esc_html( $settings['btn_guest_create_ticket_text'] ); ?>
							</button>
						<?php endif; ?>
					<?php endif; ?>
					<button type="button" class="dctc-portal-btn-secondary dctc-portal-btn-back-trigger" style="display:none;">
						<span class="dashicons dashicons-arrow-left-alt"></span>
						<?php echo esc_html( $settings['btn_back_tickets_text'] ); ?>
					</button>
				</div>
			</div>
			<?php endif; ?>

			<!-- View 1: Ticket List or Auth Box -->
			<div class="dctc-portal-view-list dctc-portal-view active">
				<?php if ( $user_id ) : ?>
					<?php if ( $show_search || $show_filters ) : ?>
					<div class="dctc-portal-filter-row">
						<?php if ( $show_search ) : ?>
						<div class="dctc-portal-filter-search-wrap">
							<span class="dashicons dashicons-search"></span>
							<input type="text" placeholder="<?php echo esc_attr( $settings['search_placeholder'] ); ?>" class="dctc-portal-input dctc-portal-filter-search" />
						</div>
						<?php endif; ?>

						<?php if ( $show_filters ) : ?>
						<div class="dctc-portal-filter-select-wrap">
							<select class="dctc-portal-select dctc-portal-filter-status">
								<option value="all" <?php selected( $a['status'], 'all' ); ?>><?php esc_html_e( 'All Statuses', 'dragwyb-click-to-chat' ); ?></option>
								<option value="open" <?php selected( $a['status'], 'open' ); ?>><?php esc_html_e( 'Open', 'dragwyb-click-to-chat' ); ?></option>
								<option value="pending" <?php selected( $a['status'], 'pending' ); ?>><?php esc_html_e( 'Pending', 'dragwyb-click-to-chat' ); ?></option>
								<option value="resolved" <?php selected( $a['status'], 'resolved' ); ?>><?php esc_html_e( 'Resolved', 'dragwyb-click-to-chat' ); ?></option>
								<option value="closed" <?php selected( $a['status'], 'closed' ); ?>><?php esc_html_e( 'Closed', 'dragwyb-click-to-chat' ); ?></option>
							</select>
						</div>

							<?php if ( ! empty( $categories ) ) : ?>
						<div class="dctc-portal-filter-select-wrap">
							<select class="dctc-portal-select dctc-portal-filter-category">
								<option value="all"><?php esc_html_e( 'All Categories', 'dragwyb-click-to-chat' ); ?></option>
								<?php foreach ( $categories as $cat ) : ?>
									<option value="<?php echo esc_attr( $cat['id'] ); ?>" <?php selected( (string) $cat_filter_val, (string) $cat['id'] ); ?>>
										<?php echo esc_html( $cat['name'] ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>
						<?php endif; ?>
						<?php endif; ?>
					</div>
					<?php endif; ?>

					<div class="dctc-portal-tickets-container dctc-portal-tickets-list">
						<div class="dctc-portal-loading"><?php echo esc_html( $settings['loading_text'] ); ?></div>
					</div>
				<?php else : ?>
					<!-- Logged-out Visitor Box -->
					<div class="dctc-portal-auth-prompt">
						<div class="dctc-portal-auth-icon">
							<span class="dashicons dashicons-lock"></span>
						</div>
						<h3><?php echo esc_html( $settings['guest_auth_box_title'] ); ?></h3>
						<p><?php echo esc_html( $settings['guest_auth_box_desc'] ); ?></p>
						<div class="dctc-portal-auth-actions">
							<?php if ( $guest_allowed && $show_create ) : ?>
								<button type="button" class="dctc-portal-btn-primary dctc-portal-btn-guest-action-trigger">
									<span class="dashicons dashicons-edit"></span>
									<?php echo esc_html( $settings['btn_guest_create_ticket_text'] ); ?>
								</button>
							<?php endif; ?>
							<?php if ( ! empty( $settings['show_login_button'] ) ) : ?>
								<a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>" class="dctc-portal-btn-secondary">
									<span class="dashicons dashicons-admin-users"></span>
									<?php echo esc_html( $settings['btn_login_text'] ); ?>
								</a>
							<?php endif; ?>
						</div>
					</div>

					<div class="dctc-portal-guest-recent-section" style="display:none; margin-top:24px;">
						<h4 style="margin:0 0 12px; font-size:15px; font-weight:700; color:var(--dctc-portal-header-title, #111827);"><?php esc_html_e( 'Your Recent Support Request', 'dragwyb-click-to-chat' ); ?></h4>
						<div class="dctc-portal-tickets-container dctc-portal-tickets-list"></div>
					</div>
				<?php endif; ?>
			</div>

			<?php if ( $can_render_modal ) : ?>
			<!-- Popup Modal: Create a New Support Request (Logged-in or Guest) -->
			<div class="dctc-portal-new-modal dctc-portal-modal-backdrop" style="display:none;">
				<div class="dctc-portal-modal-card" role="dialog" aria-modal="true" aria-labelledby="dctc-modal-title">
					<div class="dctc-portal-modal-header">
						<div class="dctc-portal-modal-header-info">
							<div class="dctc-portal-modal-icon-badge">
								<span class="dashicons dashicons-format-chat"></span>
							</div>
							<div>
								<h3 class="dctc-portal-modal-title"><?php echo esc_html( $settings['modal_title'] ); ?></h3>
								<p class="dctc-portal-modal-desc"><?php echo esc_html( $settings['modal_subtitle'] ); ?></p>
							</div>
						</div>
						<button type="button" class="dctc-portal-modal-close-btn" title="<?php esc_attr_e( 'Close', 'dragwyb-click-to-chat' ); ?>">
							<span class="dashicons dashicons-no-alt"></span>
						</button>
					</div>

					<form class="dctc-portal-new-ticket-form dctc-portal-form">
						<div class="dctc-portal-modal-body-scroll">
							<?php if ( ! $user_id ) : ?>
							<div class="dctc-form-grid-2" style="margin-bottom: 16px;">
								<div class="dctc-form-group">
									<label>
										<?php echo esc_html( $settings['guest_name_label'] ); ?>
										<span class="dctc-portal-required">*</span>
									</label>
									<input type="text" required class="dctc-portal-input dctc-new-name" placeholder="<?php echo esc_attr( $settings['guest_name_placeholder'] ); ?>" />
								</div>
								<div class="dctc-form-group">
									<label>
										<?php echo esc_html( $settings['guest_email_label'] ); ?>
										<span class="dctc-portal-required">*</span>
									</label>
									<input type="email" required class="dctc-portal-input dctc-new-email" placeholder="<?php echo esc_attr( $settings['guest_email_placeholder'] ); ?>" />
								</div>
							</div>
							<?php endif; ?>

							<div class="dctc-form-group">
								<label>
									<?php echo esc_html( $settings['category_label'] ); ?>
									<span class="dctc-portal-required">*</span>
								</label>
								<select class="dctc-portal-select dctc-new-category">
									<option value="0" data-sub-taxonomies='["product","tag"]'><?php esc_html_e( 'General Inquiry', 'dragwyb-click-to-chat' ); ?></option>
									<?php foreach ( $categories as $cat ) : ?>
										<?php
										$sub_tax_json = ! empty( $cat['sub_taxonomies'] ) && is_array( $cat['sub_taxonomies'] )
											? wp_json_encode( $cat['sub_taxonomies'] )
											: wp_json_encode( array_filter( array( ! empty( $cat['show_product'] ) ? 'product' : '', ! empty( $cat['show_tags'] ) ? 'tag' : '' ) ) );
										?>
										<option value="<?php echo esc_attr( $cat['id'] ); ?>" data-sub-taxonomies="<?php echo esc_attr( $sub_tax_json ); ?>">
											<?php echo esc_html( $cat['name'] ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>

							<div class="dctc-portal-dynamic-subfields dctc-form-grid-2" style="display:none; margin-bottom:16px;"></div>

							<div class="dctc-form-group">
								<label>
									<?php echo esc_html( $settings['subject_label'] ); ?>
									<span class="dctc-portal-required">*</span>
								</label>
								<input type="text" required class="dctc-portal-input dctc-new-subject" placeholder="<?php echo esc_attr( $settings['subject_placeholder'] ); ?>" />
							</div>

							<div class="dctc-form-group">
								<label>
									<?php echo esc_html( $settings['message_label'] ); ?>
									<span class="dctc-portal-required">*</span>
								</label>
								<div class="dctc-portal-editor-wrapper">
									<?php
									$content    = '';
									$editor_id  = 'dctctablesnewmsg' . wp_rand( 100, 999 );
									$editor_opt = array(
										'textarea_name' => 'ticket_message',
										'media_buttons' => true,
										'textarea_rows' => 8,
										'teeny'         => false,
										'quicktags'     => false,
										'tinymce'       => array(
											'toolbar1' => 'bold,italic,underline,strikethrough,bullist,numlist,blockquote,link,unlink,undo,redo',
											'toolbar2' => '',
											'toolbar3' => '',
											'toolbar4' => '',
										),
									);
									wp_editor( $content, $editor_id, $editor_opt );
									?>
								</div>
							</div>
						</div>

						<div class="dctc-portal-modal-footer">
							<button type="button" class="dctc-portal-btn-secondary dctc-portal-modal-cancel">
								<?php echo esc_html( $settings['btn_cancel_text'] ); ?>
							</button>
							<button type="submit" class="dctc-portal-btn-primary dctc-new-submit-btn">
								<span class="dashicons dashicons-saved" style="font-size:16px;line-height:1;margin-top:1px;"></span>
								<?php echo esc_html( $settings['btn_submit_ticket_text'] ); ?>
							</button>
						</div>
					</form>
				</div>
			</div>
			<?php endif; ?>

			<!-- View 2: Single Ticket Conversation Detail -->
			<div class="dctc-portal-view-detail dctc-portal-view">
				<div class="dctc-detail-header">
					<div class="dctc-detail-header-left">
						<div class="dctc-detail-badges">
							<span class="dctc-detail-num dctc-detail-ticket-num">#0000</span>
							<span class="dctc-detail-status dctc-badge dctc-badge-open">OPEN</span>
							<span class="dctc-detail-priority dctc-badge dctc-badge-priority-normal">NORMAL</span>
							<span class="dctc-detail-category dctc-portal-badge-cat">Category</span>
							<span class="dctc-detail-agent dctc-portal-badge-agent">Agent</span>
							<span class="dctc-detail-chats dctc-portal-badge-chats">0 chats</span>
						</div>
						<div class="dctc-detail-tags-row dctc-portal-tags-row" style="margin-top: 6px;"></div>
						<h3 class="dctc-detail-subject"><?php esc_html_e( 'Ticket Subject', 'dragwyb-click-to-chat' ); ?></h3>
					</div>
					<div class="dctc-detail-header-right">
						<button type="button" class="dctc-portal-btn-secondary dctc-detail-close-btn">
							<?php echo esc_html( $settings['btn_close_ticket_text'] ); ?>
						</button>
					</div>
				</div>

				<!-- Messages Timeline -->
				<div class="dctc-portal-detail-messages dctc-portal-messages-timeline"></div>

				<!-- Reply Box -->
				<form class="dctc-portal-reply-form dctc-portal-reply-box">
					<div class="dctc-portal-editor-wrapper">
						<?php
						$content    = '';
						$editor_id  = 'dctctablesreplymsg' . wp_rand( 100, 999 );
						$editor_opt = array(
							'textarea_name' => 'reply_message',
							'media_buttons' => true,
							'textarea_rows' => 5,
							'teeny'         => false,
							'quicktags'     => false,
							'tinymce'       => array(
								'toolbar1' => 'bold,italic,underline,strikethrough,bullist,numlist,blockquote,link,unlink,undo,redo',
								'toolbar2' => '',
								'toolbar3' => '',
								'toolbar4' => '',
							),
						);
						wp_editor( $content, $editor_id, $editor_opt );
						?>
					</div>
					<div class="dctc-portal-reply-actions">
						<button type="submit" class="dctc-portal-btn-primary dctc-portal-send-reply-btn">
							<span class="dashicons dashicons-send" style="font-size:15px;line-height:1;margin-top:1px;"></span>
							<?php echo esc_html( $settings['btn_send_reply_text'] ); ?>
						</button>
					</div>
				</form>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get portal JS script string.
	 *
	 * @return string JavaScript code.
	 */
	public static function get_portal_js() {
		return '
		(function() {
			function getPortalEditorContent(id) {
				if (window.tinymce && window.tinymce.get(id) && !window.tinymce.get(id).isHidden()) {
					return window.tinymce.get(id).getContent();
				}
				const el = document.getElementById(id);
				return el ? el.value : "";
			}

			function clearPortalEditorContent(id) {
				if (window.tinymce && window.tinymce.get(id)) {
					window.tinymce.get(id).setContent("");
				}
				const el = document.getElementById(id);
				if (el) {
					el.value = "";
				}
			}

			function syncTaxonomySubfields(container, catSelect, taxonomiesData) {
				if (!container || !catSelect) return;
				const opt = catSelect.options[catSelect.selectedIndex];
				let subTaxonomies = [];
				if (opt && opt.getAttribute("data-sub-taxonomies")) {
					try {
						subTaxonomies = JSON.parse(opt.getAttribute("data-sub-taxonomies"));
					} catch(e) {
						subTaxonomies = [];
					}
				}

				if (!Array.isArray(subTaxonomies) || subTaxonomies.length === 0) {
					container.style.display = "none";
					container.innerHTML = "";
					return;
				}

				const activeSubTaxes = [];
				subTaxonomies.forEach(function(slug) {
					const tax = taxonomiesData[slug];
					if (tax && Array.isArray(tax.terms) && tax.terms.length > 0) {
						activeSubTaxes.push(tax);
					}
				});

				if (activeSubTaxes.length === 0) {
					container.style.display = "none";
					container.innerHTML = "";
					return;
				}

				if (activeSubTaxes.length === 1) {
					container.className = "dctc-portal-dynamic-subfields dctc-form-grid-1";
					container.style.gridTemplateColumns = "1fr";
				} else {
					container.className = "dctc-portal-dynamic-subfields dctc-form-grid-2";
					container.style.gridTemplateColumns = "";
				}

				let fieldsHtml = "";
				activeSubTaxes.forEach(function(tax) {
					const slug = tax.slug;
					const taxName = tax.name || slug;
					const terms = tax.terms;

					fieldsHtml += "<div class=\"dctc-form-group" + (activeSubTaxes.length === 1 ? " is-full-width" : "") + "\">";
					fieldsHtml += "  <label for=\"dctc-subfield-" + slug + "\">" + taxName + "</label>";
					fieldsHtml += "  <select class=\"dctc-portal-select dctc-dynamic-subfield-input\" data-tax-slug=\"" + slug + "\">";
					fieldsHtml += "    <option value=\"\">-- Select " + taxName + " (Optional) --</option>";
					terms.forEach(function(term) {
						const termName = term.name || term.slug || "";
						fieldsHtml += "    <option value=\"" + termName + "\">" + termName + "</option>";
					});
					fieldsHtml += "  </select>";
					fieldsHtml += "</div>";
				});

				container.innerHTML = fieldsHtml;
				container.style.display = "grid";
			}

			// Initialize Support Portal & Ticket Tables
			function initPortalInstance(root) {
				if (!root || root.getAttribute("data-portal-init") === "1") return;
				root.setAttribute("data-portal-init", "1");

				const restUrl = root.getAttribute("data-rest-url");
				const nonce = root.getAttribute("data-nonce");
				const isLoggedIn = root.getAttribute("data-user-logged-in") === "1";
				const guestEnabled = root.getAttribute("data-guest-enabled") === "1";
				const perPage = parseInt(root.getAttribute("data-per-page") || "10", 10);

				const emptyTitle = root.getAttribute("data-empty-title") || "No support requests found";
				const emptyDesc = root.getAttribute("data-empty-desc") || "You have not submitted any support tickets yet.";
				const loadingText = root.getAttribute("data-loading-text") || "Loading support tickets...";
				const btnSubmitText = root.getAttribute("data-btn-submit-text") || "Submit Support Request";
				const btnSendReplyText = root.getAttribute("data-btn-send-reply-text") || "Send Reply";

				const viewList = root.querySelector(".dctc-portal-view-list");
				const viewDetail = root.querySelector(".dctc-portal-view-detail");

				const btnNew = root.querySelector(".dctc-portal-btn-new-trigger");
				const btnGuestHeader = root.querySelector(".dctc-portal-btn-guest-header-trigger");
				const btnGuestAction = root.querySelector(".dctc-portal-btn-guest-action-trigger");
				const btnBack = root.querySelector(".dctc-portal-btn-back-trigger");
				
				const filterRow = root.querySelector(".dctc-portal-filter-row");
				const ticketsContainer = root.querySelector(".dctc-portal-tickets-list");
				const searchInput = root.querySelector(".dctc-portal-filter-search");
				const statusFilter = root.querySelector(".dctc-portal-filter-status");
				const categoryFilter = root.querySelector(".dctc-portal-filter-category");
				const guestRecentSection = root.querySelector(".dctc-portal-guest-recent-section");

				const modalNew = root.querySelector(".dctc-portal-new-modal");
				const newForm = root.querySelector(".dctc-portal-new-ticket-form");
				const replyForm = root.querySelector(".dctc-portal-reply-form");
				const closeBtn = root.querySelector(".dctc-detail-close-btn");

				let currentTicketUuid = null;
				let guestToken = localStorage.getItem("dctc_guest_token") || "";
				let detailPollInterval = null;

				let taxonomiesData = {};
				try {
					const rawTaxData = root.getAttribute("data-taxonomies-data");
					taxonomiesData = rawTaxData ? JSON.parse(rawTaxData) : {};
				} catch (e) {
					taxonomiesData = {};
				}

				function showView(view) {
					if (viewList) viewList.classList.remove("active");
					if (viewDetail) viewDetail.classList.remove("active");
					if (view) view.classList.add("active");

					if (view === viewList) {
						if (detailPollInterval) {
							clearInterval(detailPollInterval);
							detailPollInterval = null;
						}
						currentTicketUuid = null;
						if (btnNew) btnNew.style.display = "inline-flex";
						if (btnGuestHeader) btnGuestHeader.style.display = "inline-flex";
						if (btnBack) btnBack.style.display = "none";
					} else {
						if (btnBack) btnBack.style.display = "inline-flex";
					}
				}

				function openNewModal() {
					if (!modalNew) return;
					modalNew.style.display = "flex";
					const catSel = modalNew.querySelector(".dctc-new-category");
					const subBox = modalNew.querySelector(".dctc-portal-dynamic-subfields");
					if (catSel && subBox) {
						syncTaxonomySubfields(subBox, catSel, taxonomiesData);
					}
					setTimeout(function() {
						const firstInput = modalNew.querySelector(".dctc-new-name") || modalNew.querySelector(".dctc-new-subject");
						if (firstInput) firstInput.focus();
					}, 60);
				}

				function closeNewModal() {
					if (!modalNew) return;
					modalNew.style.display = "none";
				}

				if (btnNew) btnNew.addEventListener("click", openNewModal);
				if (btnGuestHeader) btnGuestHeader.addEventListener("click", openNewModal);
				if (btnGuestAction) btnGuestAction.addEventListener("click", openNewModal);

				if (modalNew) {
					const closeIcon = modalNew.querySelector(".dctc-portal-modal-close-btn");
					const cancelBtn = modalNew.querySelector(".dctc-portal-modal-cancel");
					if (closeIcon) closeIcon.addEventListener("click", closeNewModal);
					if (cancelBtn) cancelBtn.addEventListener("click", closeNewModal);
					modalNew.addEventListener("click", function(e) {
						if (e.target === modalNew) closeNewModal();
					});
					const modalCat = modalNew.querySelector(".dctc-new-category");
					const modalSubBox = modalNew.querySelector(".dctc-portal-dynamic-subfields");
					if (modalCat && modalSubBox) {
						modalCat.addEventListener("change", function() {
							syncTaxonomySubfields(modalSubBox, modalCat, taxonomiesData);
						});
					}
				}

				if (btnBack) {
					btnBack.addEventListener("click", function() {
						showView(viewList);
						if (isLoggedIn || guestToken) {
							loadTickets();
						}
					});
				}

				// Load Tickets List
				async function loadTickets() {
					if (!ticketsContainer) return;
					ticketsContainer.innerHTML = "<div class=\"dctc-portal-loading\">" + loadingText + "</div>";
					try {
						const headers = { "X-WP-Nonce": nonce };
						if (guestToken) headers["X-Guest-Token"] = guestToken;

						const q = searchInput ? searchInput.value.trim() : "";
						const st = statusFilter ? statusFilter.value : (root.getAttribute("data-initial-status") || "all");
						const cat = categoryFilter ? categoryFilter.value : (root.getAttribute("data-initial-category") || "all");

						let url = restUrl + "/tickets?per_page=" + encodeURIComponent(perPage);
						if (q) url += "&search=" + encodeURIComponent(q);
						if (st && st !== "all") url += "&status=" + encodeURIComponent(st);
						if (cat && cat !== "all") url += "&category_id=" + encodeURIComponent(cat);

						const res = await fetch(url, { headers: headers });
						const data = await res.json();

						if (data.success && data.tickets && data.tickets.length > 0) {
							if (filterRow) filterRow.style.display = "";
							if (guestRecentSection) guestRecentSection.style.display = "block";
							let html = "";
							data.tickets.forEach(function(t) {
								const statusBadge = t.status === "open" ? "dctc-badge-open" : (t.status === "resolved" ? "dctc-badge-resolved" : "dctc-badge-closed");
								const categoryName = t.category_name || "General";
								const chatCount = t.chat_count !== undefined ? t.chat_count : (t.message_count || 1);
								const agentName = t.agent_name || "Assigned Agent";
								const tags = Array.isArray(t.tags) ? t.tags : [];

								function getTagLabel(tag) {
									if (!tag) return "";
									if (typeof tag === "string") return tag;
									if (typeof tag === "object") return tag.name || tag.slug || tag.title || "";
									return String(tag);
								}

								html += "<div class=\"dctc-portal-ticket-card\" data-uuid=\"" + t.uuid + "\">";
								html += "  <div class=\"dctc-portal-card-left\" style=\"flex:1;\">";
								html += "    <div class=\"dctc-portal-card-top\">";
								html += "      <span class=\"dctc-portal-card-num\">#" + t.ticket_number + "</span>";
								html += "      <span class=\"dctc-badge " + statusBadge + "\">" + (t.status || "open").toUpperCase() + "</span>";
								html += "      <span class=\"dctc-portal-badge-cat\">" + categoryName + "</span>";
								html += "      <span class=\"dctc-portal-badge-agent\">" + agentName + "</span>";
								html += "      <span class=\"dctc-portal-badge-chats\">" + chatCount + " " + (chatCount === 1 ? "chat" : "chats") + "</span>";
								html += "    </div>";
								html += "    <h4 class=\"dctc-portal-card-title\">" + (t.subject || "Support Ticket") + "</h4>";
								const validTags = tags.map(getTagLabel).filter(function(l) { return l && l !== "[object Object]"; });
								if (validTags.length > 0) {
									html += "    <div class=\"dctc-portal-card-badges-row\">";
									validTags.forEach(function(tagLabel) {
										html += "      <span class=\"dctc-portal-badge-tag\">" + tagLabel + "</span>";
									});
									html += "    </div>";
								}
								html += "  </div>";
								html += "  <span class=\"dctc-portal-card-date\">" + (t.created_at ? t.created_at.split(" ")[0] : "") + "</span>";
								html += "</div>";
							});
							ticketsContainer.innerHTML = html;

							ticketsContainer.querySelectorAll(".dctc-portal-ticket-card").forEach(function(card) {
								card.addEventListener("click", function() {
									const uuid = this.getAttribute("data-uuid");
									loadTicketDetail(uuid);
								});
							});
						} else {
							if (q || (st && st !== "all") || (cat && cat !== "all")) {
								if (filterRow) filterRow.style.display = "";
								ticketsContainer.innerHTML = "<div style=\"text-align:center;padding:30px 0;color:var(--dctc-portal-header-subtitle, #6B7280);\">No tickets match your filters.</div>";
							} else {
								if (isLoggedIn) {
									ticketsContainer.innerHTML = "<div style=\"text-align:center;padding:30px 0;color:var(--dctc-portal-header-subtitle, #6B7280);\"><strong>" + emptyTitle + "</strong><p style=\"margin:6px 0 0;font-size:13px;\">" + emptyDesc + "</p></div>";
								} else {
									if (guestRecentSection) guestRecentSection.style.display = "none";
									ticketsContainer.innerHTML = "";
								}
							}
						}
					} catch (err) {
						ticketsContainer.innerHTML = "<div style=\"color:#EF4444;text-align:center;padding:20px 0;\">Error loading support tickets. Please try again.</div>";
					}
				}

				// Load Single Ticket Detail with Real-Time Active Polling
				async function loadTicketDetail(uuid, isSilentUpdate) {
					currentTicketUuid = uuid;
					if (!isSilentUpdate) {
						showView(viewDetail);
						if (detailPollInterval) clearInterval(detailPollInterval);
						detailPollInterval = setInterval(function() {
							if (document.hidden) return;
							if (currentTicketUuid) {
								loadTicketDetail(currentTicketUuid, true);
							}
						}, 4000);
					}

					const msgContainer = root.querySelector(".dctc-portal-detail-messages");
					if (!msgContainer) return;
					if (!isSilentUpdate) {
						msgContainer.innerHTML = "<div style=\"color:var(--dctc-portal-header-subtitle, #6B7280);\">Loading conversation...</div>";
					}

					try {
						const headers = { "X-WP-Nonce": nonce };
						if (guestToken) headers["X-Guest-Token"] = guestToken;

						const res = await fetch(restUrl + "/tickets/" + uuid, { headers: headers });
						const data = await res.json();

						if (data.success && data.ticket) {
							const t = data.ticket;
							const numEl = root.querySelector(".dctc-detail-ticket-num");
							const subEl = root.querySelector(".dctc-detail-subject");
							const statEl = root.querySelector(".dctc-detail-status");
							const priEl = root.querySelector(".dctc-detail-priority");

							if (numEl) numEl.textContent = "#" + t.ticket_number;
							if (subEl) subEl.textContent = t.subject;
							
							if (statEl) {
								const st = (t.status || "open").toLowerCase();
								statEl.textContent = (t.status || "open").toUpperCase();
								statEl.className = "dctc-detail-status dctc-badge " + (st === "open" ? "dctc-badge-open" : (st === "resolved" ? "dctc-badge-resolved" : "dctc-badge-closed"));
							}
							if (priEl) {
								const pr = (t.priority || "normal").toLowerCase();
								priEl.textContent = (t.priority || "normal").toUpperCase();
								priEl.className = "dctc-detail-priority dctc-badge dctc-badge-priority-" + pr;
							}
							
							const catElem = root.querySelector(".dctc-detail-category");
							if (catElem) catElem.textContent = (t.category_name || "General");
							
							const agentElem = root.querySelector(".dctc-detail-agent");
							if (agentElem) agentElem.textContent = (t.agent_name || "Support Staff");

							const chatsElem = root.querySelector(".dctc-detail-chats");
							const count = t.chat_count !== undefined ? t.chat_count : (t.messages ? t.messages.length : 0);
							if (chatsElem) chatsElem.textContent = count + " " + (count === 1 ? "chat" : "chats");

							const tagsRow = root.querySelector(".dctc-detail-tags-row");
							if (tagsRow) {
								const rawTags = Array.isArray(t.tags) ? t.tags : [];
								const validTags = rawTags.map(function(tg) {
									if (!tg) return "";
									if (typeof tg === "string") return tg;
									if (typeof tg === "object") return tg.name || tg.slug || tg.title || "";
									return String(tg);
								}).filter(function(l) { return l && l !== "[object Object]"; });
								tagsRow.innerHTML = validTags.map(function(tag) {
									return "<span class=\"dctc-portal-badge-tag\">" + tag + "</span>";
								}).join("");
							}

							let msgHtml = "";
							(t.messages || []).forEach(function(m) {
								const isCustomer = m.sender_type === "customer" || m.role === "user";
								const senderLabel = isCustomer ? "You" : (m.sender_name ? m.sender_name : "Support Team");
								msgHtml += "<div class=\"dctc-portal-msg " + (isCustomer ? "dctc-portal-msg-customer" : "dctc-portal-msg-agent") + "\">";
								msgHtml += "  <div class=\"dctc-portal-msg-header\">";
								msgHtml += "    <span>" + senderLabel + "</span>";
								msgHtml += "    <span class=\"dctc-portal-msg-time\">" + (m.created_at || "") + "</span>";
								msgHtml += "  </div>";
								msgHtml += "  <div class=\"dctc-portal-msg-body\">" + m.content + "</div>";
								msgHtml += "</div>";
							});

							const previousScrollBottom = msgContainer.scrollHeight - msgContainer.scrollTop <= msgContainer.clientHeight + 40;
							msgContainer.innerHTML = msgHtml || "<div style=\"color:var(--dctc-portal-header-subtitle, #6B7280);\">No messages yet.</div>";
							if (!isSilentUpdate || previousScrollBottom) {
								msgContainer.scrollTop = msgContainer.scrollHeight;
							}
						}
					} catch (err) {
						if (!isSilentUpdate) {
							msgContainer.innerHTML = "<div style=\"color:#EF4444;\">Error loading ticket details.</div>";
						}
					}
				}

				// Submit New Ticket
				if (newForm) {
					newForm.addEventListener("submit", async function(e) {
						e.preventDefault();
						const submitBtn = newForm.querySelector(".dctc-new-submit-btn");
						if (submitBtn) {
							submitBtn.disabled = true;
							submitBtn.textContent = "Submitting...";
						}

						const nameInput = newForm.querySelector(".dctc-new-name");
						const emailInput = newForm.querySelector(".dctc-new-email");
						const catInput = newForm.querySelector(".dctc-new-category");
						const subjectInput = newForm.querySelector(".dctc-new-subject");
						
						let editorId = "dctcportalnewmessage";
						const editorWrapper = newForm.querySelector(".dctc-portal-editor-wrapper [id^=\'dctc\']");
						if (editorWrapper && editorWrapper.id) {
							editorId = editorWrapper.id.replace("_parent", "").replace("_ifr", "");
						}
						const msgContent = getPortalEditorContent(editorId).trim();

						if (!msgContent) {
							alert("Please enter a ticket message.");
							if (submitBtn) {
								submitBtn.disabled = false;
								submitBtn.textContent = btnSubmitText;
							}
							return;
						}

						const tagsList = [];
						const dynamicSubContainer = newForm.querySelector(".dctc-portal-dynamic-subfields");
						if (dynamicSubContainer) {
							const subInputs = dynamicSubContainer.querySelectorAll(".dctc-dynamic-subfield-input");
							subInputs.forEach(function(input) {
								const val = input.value ? input.value.trim() : "";
								if (val && !tagsList.includes(val)) {
									tagsList.push(val);
								}
							});
						}

						const customerName = nameInput ? nameInput.value.trim() : root.getAttribute("data-user-name");
						const customerEmail = emailInput ? emailInput.value.trim() : root.getAttribute("data-user-email");

						const payload = {
							subject: subjectInput ? subjectInput.value.trim() : "",
							category_id: catInput ? Number(catInput.value) : 0,
							tags: tagsList,
							initial_message: msgContent,
							customer_name: customerName,
							customer_email: customerEmail,
						};

						try {
							const headers = {
								"Content-Type": "application/json",
								"X-WP-Nonce": nonce
							};
							if (guestToken) headers["X-Guest-Token"] = guestToken;

							const res = await fetch(restUrl + "/tickets", {
								method: "POST",
								headers: headers,
								body: JSON.stringify(payload)
							});
							const data = await res.json();

							if (data.success && data.ticket) {
								if (data.ticket.guest_access_token) {
									guestToken = data.ticket.guest_access_token;
									localStorage.setItem("dctc_guest_token", guestToken);
								}
								newForm.reset();
								clearPortalEditorContent(editorId);
								closeNewModal();
								loadTicketDetail(data.ticket.uuid);
							} else {
								alert(data.message || "Could not create ticket.");
							}
						} catch (err) {
							alert("Error connecting to support server.");
						} finally {
							if (submitBtn) {
								submitBtn.disabled = false;
								submitBtn.textContent = btnSubmitText;
							}
						}
					});
				}

				// Reply to Ticket
				if (replyForm) {
					replyForm.addEventListener("submit", async function(e) {
						e.preventDefault();
						if (!currentTicketUuid) return;

						const replyBtn = replyForm.querySelector(".dctc-portal-send-reply-btn");
						let replyEditorId = "dctcportalreplymessage";
						const replyWrapper = replyForm.querySelector(".dctc-portal-editor-wrapper [id^=\'dctc\']");
						if (replyWrapper && replyWrapper.id) {
							replyEditorId = replyWrapper.id.replace("_parent", "").replace("_ifr", "");
						}
						const msgText = getPortalEditorContent(replyEditorId).trim();
						if (!msgText) return;

						if (replyBtn) {
							replyBtn.disabled = true;
							replyBtn.textContent = "Sending...";
						}

						try {
							const headers = {
								"Content-Type": "application/json",
								"X-WP-Nonce": nonce
							};
							if (guestToken) headers["X-Guest-Token"] = guestToken;

							const res = await fetch(restUrl + "/tickets/" + currentTicketUuid + "/reply", {
								method: "POST",
								headers: headers,
								body: JSON.stringify({ message: msgText })
							});
							const data = await res.json();

							if (data.success) {
								clearPortalEditorContent(replyEditorId);
								loadTicketDetail(currentTicketUuid);
							} else {
								alert(data.message || "Could not send reply.");
							}
						} catch (err) {
							alert("Error sending reply.");
						} finally {
							if (replyBtn) {
								replyBtn.disabled = false;
								replyBtn.textContent = btnSendReplyText;
							}
						}
					});
				}

				// Close Ticket
				if (closeBtn) {
					closeBtn.addEventListener("click", async function() {
						if (!currentTicketUuid) return;
						if (!confirm("Are you sure you want to mark this ticket as closed?")) return;

						try {
							const headers = { "X-WP-Nonce": nonce };
							if (guestToken) headers["X-Guest-Token"] = guestToken;

							const res = await fetch(restUrl + "/tickets/" + currentTicketUuid + "/close", {
								method: "POST",
								headers: headers
							});
							const data = await res.json();
							if (data.success) {
								loadTicketDetail(currentTicketUuid);
							}
						} catch (err) {
							alert("Could not close ticket.");
						}
					});
				}

				// Filters & Search listener
				if (searchInput) {
					let searchTimeout;
					searchInput.addEventListener("input", function() {
						clearTimeout(searchTimeout);
						searchTimeout = setTimeout(loadTickets, 350);
					});
				}
				if (statusFilter) {
					statusFilter.addEventListener("change", loadTickets);
				}
				if (categoryFilter) {
					categoryFilter.addEventListener("change", loadTickets);
				}

				// Initial tickets load
				if (isLoggedIn || guestToken) {
					loadTickets();
				}
			}

			// Initialize Standalone Create Ticket Widget (inline or custom button modal)
			function initCreateTicketInstance(root) {
				if (!root || root.getAttribute("data-create-init") === "1") return;
				root.setAttribute("data-create-init", "1");

				const restUrl = root.getAttribute("data-rest-url");
				const nonce = root.getAttribute("data-nonce");
				const isInline = root.getAttribute("data-is-inline") === "1";
				const triggerSelector = root.getAttribute("data-trigger-selector") || "";
				const editorId = root.getAttribute("data-editor-id");

				let taxonomiesData = {};
				try {
					const rawTaxData = root.getAttribute("data-taxonomies-data");
					taxonomiesData = rawTaxData ? JSON.parse(rawTaxData) : {};
				} catch (e) {
					taxonomiesData = {};
				}

				const modal = root.querySelector(".dctc-portal-new-modal");
				const form = root.querySelector(".dctc-portal-new-ticket-form");
				const successNotice = root.querySelector(".dctc-create-ticket-success-notice");
				const anotherBtn = root.querySelector(".dctc-create-another-btn");
				const catSelect = root.querySelector(".dctc-new-category");
				const subBox = root.querySelector(".dctc-portal-dynamic-subfields");

				if (catSelect && subBox) {
					syncTaxonomySubfields(subBox, catSelect, taxonomiesData);
					catSelect.addEventListener("change", function() {
						syncTaxonomySubfields(subBox, catSelect, taxonomiesData);
					});
				}

				function openModal() {
					if (!modal) return;
					modal.style.display = "flex";
					if (catSelect && subBox) {
						syncTaxonomySubfields(subBox, catSelect, taxonomiesData);
					}
					setTimeout(function() {
						const firstInput = root.querySelector(".dctc-new-name") || root.querySelector(".dctc-new-subject");
						if (firstInput) firstInput.focus();
					}, 60);
				}

				function closeModal() {
					if (!modal) return;
					modal.style.display = "none";
				}

				if (modal) {
					const closeBtn = modal.querySelector(".dctc-portal-modal-close-btn");
					const cancelBtn = modal.querySelector(".dctc-portal-modal-cancel");
					if (closeBtn) closeBtn.addEventListener("click", closeModal);
					if (cancelBtn) cancelBtn.addEventListener("click", closeModal);
					modal.addEventListener("click", function(e) {
						if (e.target === modal) closeModal();
					});
				}

				// Launch Button inside shortcode wrapper
				const innerBtn = root.querySelector(".dctc-portal-btn-new-trigger");
				if (innerBtn) {
					innerBtn.addEventListener("click", openModal);
				}

				// External Custom Trigger Button Selector (ID or CSS Selector)
				if (triggerSelector) {
					const cleanSelector = triggerSelector.trim();
					function bindExternalTriggers() {
						let elements = [];
						if (cleanSelector.startsWith("#") || cleanSelector.startsWith(".")) {
							try { elements = Array.from(document.querySelectorAll(cleanSelector)); } catch(e){}
						} else {
							const byId = document.getElementById(cleanSelector);
							if (byId) elements.push(byId);
							try {
								const byQuery = Array.from(document.querySelectorAll(cleanSelector));
								elements = elements.concat(byQuery);
							} catch(e){}
						}
						elements.forEach(function(el) {
							if (el && !el.hasAttribute("data-dctc-bound")) {
								el.setAttribute("data-dctc-bound", "1");
								el.addEventListener("click", function(e) {
									e.preventDefault();
									openModal();
								});
							}
						});
					}
					bindExternalTriggers();
					setTimeout(bindExternalTriggers, 500);

					// Document-level delegation for dynamically injected trigger buttons
					document.addEventListener("click", function(e) {
						let matched = false;
						if (cleanSelector.startsWith("#") || cleanSelector.startsWith(".")) {
							if (e.target.closest && e.target.closest(cleanSelector)) matched = true;
						} else {
							if (e.target.closest && (e.target.closest("#" + cleanSelector) || e.target.closest("." + cleanSelector) || e.target.id === cleanSelector)) matched = true;
						}
						if (matched) {
							e.preventDefault();
							openModal();
						}
					});
				}

				if (anotherBtn && form && successNotice) {
					anotherBtn.addEventListener("click", function() {
						form.style.display = "block";
						successNotice.style.display = "none";
						form.reset();
						if (editorId) clearPortalEditorContent(editorId);
					});
				}

				if (form) {
					form.addEventListener("submit", async function(e) {
						e.preventDefault();
						const submitBtn = form.querySelector(".dctc-new-submit-btn");
						const originalBtnHtml = submitBtn ? submitBtn.innerHTML : "Submit";
						if (submitBtn) {
							submitBtn.disabled = true;
							submitBtn.textContent = "Submitting...";
						}

						const nameInput = form.querySelector(".dctc-new-name");
						const emailInput = form.querySelector(".dctc-new-email");
						const catInput = form.querySelector(".dctc-new-category");
						const subjectInput = form.querySelector(".dctc-new-subject");
						const msgContent = editorId ? getPortalEditorContent(editorId).trim() : "";

						if (!msgContent) {
							alert("Please enter a ticket message.");
							if (submitBtn) {
								submitBtn.disabled = false;
								submitBtn.innerHTML = originalBtnHtml;
							}
							return;
						}

						const tagsList = [];
						if (subBox) {
							const subInputs = subBox.querySelectorAll(".dctc-dynamic-subfield-input");
							subInputs.forEach(function(input) {
								const val = input.value ? input.value.trim() : "";
								if (val && !tagsList.includes(val)) {
									tagsList.push(val);
								}
							});
						}

						const customerName = nameInput ? nameInput.value.trim() : root.getAttribute("data-user-name");
						const customerEmail = emailInput ? emailInput.value.trim() : root.getAttribute("data-user-email");

						const payload = {
							subject: subjectInput ? subjectInput.value.trim() : "",
							category_id: catInput ? Number(catInput.value) : 0,
							tags: tagsList,
							initial_message: msgContent,
							customer_name: customerName,
							customer_email: customerEmail,
						};

						try {
							const headers = {
								"Content-Type": "application/json",
								"X-WP-Nonce": nonce
							};
							const existingGuestToken = localStorage.getItem("dctc_guest_token");
							if (existingGuestToken) headers["X-Guest-Token"] = existingGuestToken;

							const res = await fetch(restUrl + "/tickets", {
								method: "POST",
								headers: headers,
								body: JSON.stringify(payload)
							});
							const data = await res.json();

							if (data.success && data.ticket) {
								if (data.ticket.guest_access_token) {
									localStorage.setItem("dctc_guest_token", data.ticket.guest_access_token);
								}
								form.reset();
								if (editorId) clearPortalEditorContent(editorId);

								if (isInline && successNotice) {
									form.style.display = "none";
									const infoEl = successNotice.querySelector(".dctc-success-ticket-info");
									if (infoEl) {
										infoEl.textContent = "Your ticket #" + data.ticket.ticket_number + " has been received. Our support team will get back to you shortly.";
									}
									successNotice.style.display = "block";
								} else {
									closeModal();
									alert("Ticket #" + data.ticket.ticket_number + " submitted successfully!");
								}

								// If there are portal lists on this page, refresh them
								document.querySelectorAll(".dctc-portal-root").forEach(function(portal) {
									if (portal.querySelector(".dctc-portal-filter-search")) {
										const evt = new Event("input");
										portal.querySelector(".dctc-portal-filter-search").dispatchEvent(evt);
									}
								});
							} else {
								alert(data.message || "Could not create ticket.");
							}
						} catch (err) {
							alert("Error connecting to support server.");
						} finally {
							if (submitBtn) {
								submitBtn.disabled = false;
								submitBtn.innerHTML = originalBtnHtml;
							}
						}
					});
				}
			}

			function initAllSupportWidgets() {
				document.querySelectorAll(".dctc-portal-root").forEach(initPortalInstance);
				document.querySelectorAll(".dctc-create-ticket-root").forEach(initCreateTicketInstance);
			}

			// Escape key closes any open modal
			document.addEventListener("keydown", function(e) {
				if (e.key === "Escape") {
					document.querySelectorAll(".dctc-portal-new-modal").forEach(function(m) {
						if (m.style.display !== "none") m.style.display = "none";
					});
				}
			});

			if (document.readyState === "loading") {
				document.addEventListener("DOMContentLoaded", initAllSupportWidgets);
			} else {
				initAllSupportWidgets();
			}
		})();
		';
	}

	/**
	 * Get portal CSS stylesheet string with dynamic theme variables.
	 *
	 * @return string CSS rules.
	 */
	public static function get_portal_css() {
		$s = self::get_settings();

		$primary_color      = esc_attr( $s['primary_color'] );
		$primary_hover      = esc_attr( $s['primary_hover_color'] );
		$primary_text       = esc_attr( $s['primary_text_color'] );
		$secondary_bg       = esc_attr( $s['secondary_btn_bg'] );
		$secondary_text     = esc_attr( $s['secondary_btn_text'] );
		$secondary_border   = esc_attr( $s['secondary_btn_border'] );
		$secondary_hover_bg = esc_attr( $s['secondary_btn_hover_bg'] );
		$border_color       = esc_attr( $s['border_color'] );
		$border_width       = intval( $s['border_width'] ) . 'px';
		$border_style       = esc_attr( $s['border_style'] );
		$border_radius      = intval( $s['border_radius'] ) . 'px';
		$inner_radius       = max( 4, intval( $s['border_radius'] ) - 4 ) . 'px';
		$btn_radius         = max( 4, intval( $s['border_radius'] * 0.65 ) ) . 'px';
		$container_bg       = esc_attr( $s['container_bg_color'] );
		$header_bg          = esc_attr( $s['header_bg_color'] );
		$header_border      = esc_attr( $s['header_border_color'] );
		$header_title_color = esc_attr( $s['header_title_color'] );
		$header_sub_color   = esc_attr( $s['header_subtitle_color'] );
		$card_bg            = esc_attr( $s['card_bg_color'] );
		$card_hover_bg      = esc_attr( $s['card_hover_bg_color'] );
		$card_border        = esc_attr( $s['card_border_color'] );
		$input_bg           = esc_attr( $s['input_bg_color'] );
		$input_border       = esc_attr( $s['input_border_color'] );

		$is_dark       = self::is_dark_color( $container_bg ) || ( isset( $s['preset'] ) && 'dark' === $s['preset'] );
		$is_dark_card  = self::is_dark_color( $card_bg ) || $is_dark;
		$is_dark_input = self::is_dark_color( $input_bg ) || $is_dark;

		$card_title_color     = $is_dark_card ? '#F8FAFC' : '#0F172A';
		$card_date_color      = $is_dark_card ? '#94A3B8' : '#64748B';
		$body_text_color      = $is_dark ? '#E2E8F0' : '#1E293B';
		$border_divider_color = $is_dark ? '#334155' : '#E2E8F0';
		$input_text_color     = $is_dark_input ? '#F8FAFC' : '#1E293B';
		$input_placeholder    = $is_dark_input ? '#64748B' : '#94A3B8';

		// Chat bubbles contrast
		$msg_cust_bg     = $is_dark ? 'rgba(99, 102, 241, 0.22)' : '#EEF2FF';
		$msg_cust_border = $is_dark ? 'rgba(99, 102, 241, 0.45)' : '#C7D2FE';
		$msg_cust_text   = $is_dark ? '#F8FAFC' : '#1E1B4B';
		$msg_cust_sender = $is_dark ? '#A5B4FC' : '#4338CA';

		$msg_agent_bg     = $is_dark ? '#1E293B' : '#F9FAFB';
		$msg_agent_border = $is_dark ? '#334155' : '#E5E7EB';
		$msg_agent_text   = $is_dark ? '#F8FAFC' : '#111827';
		$msg_agent_sender = $is_dark ? '#38BDF8' : '#047857';

		// Modal
		$modal_card_bg     = $is_dark ? '#1E293B' : '#FFFFFF';
		$modal_card_border = $is_dark ? '#334155' : '#E2E8F0';
		$modal_footer_bg   = $is_dark ? '#0F172A' : '#F8FAFC';
		$modal_label_color = $is_dark ? '#E2E8F0' : '#334155';

		// Badges for Dark & Light
		$badge_cat_bg     = $is_dark ? 'rgba(99, 102, 241, 0.2)' : '#EEF2FF';
		$badge_cat_border = $is_dark ? 'rgba(99, 102, 241, 0.4)' : '#C7D2FE';
		$badge_cat_text   = $is_dark ? '#C7D2FE' : '#4338CA';

		$badge_agent_bg     = $is_dark ? 'rgba(16, 185, 129, 0.2)' : '#ECFDF5';
		$badge_agent_border = $is_dark ? 'rgba(16, 185, 129, 0.4)' : '#A7F3D0';
		$badge_agent_text   = $is_dark ? '#6EE7B7' : '#047857';

		$badge_chats_bg     = $is_dark ? 'rgba(245, 158, 11, 0.2)' : '#FEF3C7';
		$badge_chats_border = $is_dark ? 'rgba(245, 158, 11, 0.4)' : '#FDE68A';
		$badge_chats_text   = $is_dark ? '#FCD34D' : '#B45309';

		$badge_tag_bg     = $is_dark ? 'rgba(148, 163, 184, 0.16)' : '#F1F5F9';
		$badge_tag_border = $is_dark ? 'rgba(148, 163, 184, 0.3)' : '#E2E8F0';
		$badge_tag_text   = $is_dark ? '#E2E8F0' : '#334155';

		return "
			:root, .dctc-portal-root, .dctc-create-ticket-root {
				--dctc-portal-primary: {$primary_color};
				--dctc-portal-primary-hover: {$primary_hover};
				--dctc-portal-primary-text: {$primary_text};
				--dctc-portal-secondary-bg: {$secondary_bg};
				--dctc-portal-secondary-text: {$secondary_text};
				--dctc-portal-secondary-border: {$secondary_border};
				--dctc-portal-secondary-hover-bg: {$secondary_hover_bg};
				--dctc-portal-border-color: {$border_color};
				--dctc-portal-border-width: {$border_width};
				--dctc-portal-border-style: {$border_style};
				--dctc-portal-radius: {$border_radius};
				--dctc-portal-inner-radius: {$inner_radius};
				--dctc-portal-btn-radius: {$btn_radius};
				--dctc-portal-container-bg: {$container_bg};
				--dctc-portal-header-bg: {$header_bg};
				--dctc-portal-header-border: {$header_border};
				--dctc-portal-header-title: {$header_title_color};
				--dctc-portal-header-subtitle: {$header_sub_color};
				--dctc-portal-card-bg: {$card_bg};
				--dctc-portal-card-hover-bg: {$card_hover_bg};
				--dctc-portal-card-border: {$card_border};
				--dctc-portal-card-title: {$card_title_color};
				--dctc-portal-card-date: {$card_date_color};
				--dctc-portal-body-text: {$body_text_color};
				--dctc-portal-border-divider: {$border_divider_color};
				--dctc-portal-input-bg: {$input_bg};
				--dctc-portal-input-border: {$input_border};
				--dctc-portal-input-text: {$input_text_color};
				--dctc-portal-input-placeholder: {$input_placeholder};
				--dctc-portal-msg-cust-bg: {$msg_cust_bg};
				--dctc-portal-msg-cust-border: {$msg_cust_border};
				--dctc-portal-msg-cust-text: {$msg_cust_text};
				--dctc-portal-msg-cust-sender: {$msg_cust_sender};
				--dctc-portal-msg-agent-bg: {$msg_agent_bg};
				--dctc-portal-msg-agent-border: {$msg_agent_border};
				--dctc-portal-msg-agent-text: {$msg_agent_text};
				--dctc-portal-msg-agent-sender: {$msg_agent_sender};
				--dctc-portal-modal-bg: {$modal_card_bg};
				--dctc-portal-modal-border: {$modal_card_border};
				--dctc-portal-modal-footer-bg: {$modal_footer_bg};
				--dctc-portal-modal-label: {$modal_label_color};
				--dctc-portal-badge-cat-bg: {$badge_cat_bg};
				--dctc-portal-badge-cat-border: {$badge_cat_border};
				--dctc-portal-badge-cat-text: {$badge_cat_text};
				--dctc-portal-badge-agent-bg: {$badge_agent_bg};
				--dctc-portal-badge-agent-border: {$badge_agent_border};
				--dctc-portal-badge-agent-text: {$badge_agent_text};
				--dctc-portal-badge-chats-bg: {$badge_chats_bg};
				--dctc-portal-badge-chats-border: {$badge_chats_border};
				--dctc-portal-badge-chats-text: {$badge_chats_text};
				--dctc-portal-badge-tag-bg: {$badge_tag_bg};
				--dctc-portal-badge-tag-border: {$badge_tag_border};
				--dctc-portal-badge-tag-text: {$badge_tag_text};
			}

			.dctc-portal-root {
				background: var(--dctc-portal-container-bg, #ffffff);
				color: var(--dctc-portal-body-text, #1e293b);
				border-width: var(--dctc-portal-border-width, 1px);
				border-style: var(--dctc-portal-border-style, solid);
				border-color: var(--dctc-portal-border-color, #E5E7EB);
				border-radius: var(--dctc-portal-radius, 12px);
				box-shadow: 0 4px 16px rgba(0,0,0,0.06);
				font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
				margin: 20px 0;
				overflow: hidden;
				padding: 0;
				position: relative;
			}
			.dctc-create-ticket-inline-card {
				background: var(--dctc-portal-container-bg, #ffffff);
				border: var(--dctc-portal-border-width, 1px) var(--dctc-portal-border-style, solid) var(--dctc-portal-border-color, #E5E7EB);
				border-radius: var(--dctc-portal-radius, 12px);
				box-shadow: 0 4px 16px rgba(0,0,0,0.06);
				overflow: hidden;
				margin: 20px 0;
				font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
			}
			.dctc-portal-header {
				align-items: center;
				background: var(--dctc-portal-header-bg, #F9FAFB);
				border-bottom: 1px solid var(--dctc-portal-header-border, #E5E7EB);
				display: flex;
				flex-wrap: wrap;
				gap: 16px;
				justify-content: space-between;
				padding: 22px 26px;
			}
			.dctc-portal-header-left h2 {
				color: var(--dctc-portal-header-title, #111827);
				font-size: 21px;
				font-weight: 700;
				letter-spacing: -0.3px;
				margin: 0 0 4px !important;
			}
			.dctc-portal-header-left p {
				color: var(--dctc-portal-header-subtitle, #6B7280);
				font-size: 13.5px;
				line-height: 1.4;
				margin: 0 !important;
			}
			.dctc-portal-header-right {
				align-items: center;
				display: flex;
				flex-wrap: wrap;
				gap: 10px;
			}
			.dctc-portal-btn-primary {
				align-items: center;
				background: var(--dctc-portal-primary, #4F46E5);
				border: 1px solid var(--dctc-portal-primary, #4F46E5);
				border-radius: var(--dctc-portal-btn-radius, 8px);
				box-shadow: 0 1px 2px rgba(0, 0, 0, 0.08);
				color: var(--dctc-portal-primary-text, #ffffff) !important;
				cursor: pointer;
				display: inline-flex;
				font-size: 13.5px;
				font-weight: 600;
				gap: 6px;
				padding: 9px 18px;
				text-decoration: none !important;
				transition: all 0.15s ease-in-out;
			}
			.dctc-portal-btn-primary:hover {
				background: var(--dctc-portal-primary-hover, #4338CA);
				border-color: var(--dctc-portal-primary-hover, #4338CA);
				box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
				color: var(--dctc-portal-primary-text, #ffffff) !important;
			}
			.dctc-portal-btn-secondary {
				align-items: center;
				background: var(--dctc-portal-secondary-bg, #ffffff);
				border: 1px solid var(--dctc-portal-secondary-border, #D1D5DB);
				border-radius: var(--dctc-portal-btn-radius, 8px);
				color: var(--dctc-portal-secondary-text, #374151) !important;
				cursor: pointer;
				display: inline-flex;
				font-size: 13px;
				font-weight: 600;
				gap: 6px;
				padding: 8px 16px;
				text-decoration: none !important;
				transition: all 0.15s ease-in-out;
			}
			.dctc-portal-btn-secondary:hover {
				background: var(--dctc-portal-secondary-hover-bg, #F3F4F6);
				color: var(--dctc-portal-secondary-text, #1F2937) !important;
			}
			.dctc-portal-view {
				display: none;
				padding: 24px;
			}
			.dctc-portal-view.active {
				display: block;
			}
			.dctc-portal-filter-row {
				display: flex;
				flex-wrap: wrap;
				gap: 12px;
				margin-bottom: 18px;
				align-items: center;
			}
			.dctc-portal-filter-search-wrap {
				position: relative;
				flex: 1 1 240px;
				min-width: 200px;
			}
			.dctc-portal-filter-search-wrap .dashicons {
				position: absolute;
				left: 12px;
				top: 50%;
				transform: translateY(-50%);
				color: var(--dctc-portal-input-placeholder, #94A3B8);
				font-size: 18px;
				pointer-events: none;
			}
			.dctc-portal-filter-search-wrap input {
				padding-left: 36px;
			}
			.dctc-portal-filter-select-wrap {
				flex: 0 1 180px;
				min-width: 140px;
			}
			.dctc-portal-input, .dctc-portal-select, .dctc-portal-textarea {
				background: var(--dctc-portal-input-bg, #F8FAFC);
				border: 1px solid var(--dctc-portal-input-border, #CBD5E1);
				border-radius: var(--dctc-portal-btn-radius, 8px);
				box-sizing: border-box;
				color: var(--dctc-portal-input-text, #1E293B);
				font-family: inherit;
				font-size: 13.5px;
				padding: 10px 14px;
				transition: all 0.15s ease-in-out;
				width: 100%;
				max-width: 100%;
			}
			.dctc-portal-input::placeholder, .dctc-portal-textarea::placeholder {
				color: var(--dctc-portal-input-placeholder, #94A3B8);
				opacity: 1;
			}
			.dctc-portal-select {
				appearance: none;
				-webkit-appearance: none;
				background-position: right 12px center;
				background-repeat: no-repeat;
				background-size: 16px 16px;
				padding-right: 36px;
			}
			.dctc-portal-select option {
				background: var(--dctc-portal-input-bg, #FFFFFF);
				color: var(--dctc-portal-input-text, #1E293B);
			}
			.dctc-portal-input:focus, .dctc-portal-select:focus, .dctc-portal-textarea:focus {
				background: var(--dctc-portal-input-bg, #FFFFFF);
				border-color: var(--dctc-portal-primary, #4F46E5);
				box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.18);
				outline: none;
			}
			.dctc-portal-required {
				color: #EF4444;
				font-weight: 700;
				margin-left: 2px;
			}
			.dctc-portal-tickets-list {
				display: flex;
				flex-direction: column;
				gap: 10px;
			}
			.dctc-portal-ticket-card {
				align-items: center;
				background: var(--dctc-portal-card-bg, #F9FAFB);
				border: 1px solid var(--dctc-portal-card-border, #E5E7EB);
				border-radius: var(--dctc-portal-inner-radius, 8px);
				cursor: pointer;
				display: flex;
				justify-content: space-between;
				padding: 14px 18px;
				transition: all 0.15s;
			}
			.dctc-portal-ticket-card:hover {
				background: var(--dctc-portal-card-hover-bg, #F3F4F6);
				border-color: var(--dctc-portal-primary, #CBD5E1);
				transform: translateY(-1px);
				box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
			}
			.dctc-portal-card-left {
				display: flex;
				flex-direction: column;
				gap: 6px;
			}
			.dctc-portal-card-top {
				align-items: center;
				display: flex;
				flex-wrap: wrap;
				gap: 8px;
			}
			.dctc-portal-card-num {
				color: var(--dctc-portal-primary, #4F46E5);
				font-size: 13px;
				font-weight: 800;
			}
			.dctc-portal-card-title {
				color: var(--dctc-portal-card-title, #0F172A) !important;
				font-size: 15px;
				font-weight: 600;
				line-height: 1.4;
				margin: 0;
			}
			.dctc-portal-card-date {
				color: var(--dctc-portal-card-date, #9CA3AF);
				font-size: 12px;
				font-weight: 500;
			}
			.dctc-form-grid-1 {
				display: grid;
				gap: 16px;
				grid-template-columns: 1fr;
			}
			.dctc-form-grid-2 {
				display: grid;
				gap: 16px;
				grid-template-columns: 1fr 1fr;
			}
			.dctc-form-group {
				display: flex;
				flex-direction: column;
				gap: 6px;
				margin-bottom: 16px;
			}
			.dctc-form-group label {
				align-items: center;
				color: var(--dctc-portal-modal-label, #334155);
				display: flex;
				font-size: 13px;
				font-weight: 600;
			}
			
			/* Modal Dialog Styles */
			.dctc-portal-modal-backdrop {
				align-items: center;
				animation: dctcPortalFadeIn 0.2s cubic-bezier(0.16, 1, 0.3, 1);
				backdrop-filter: blur(4px);
				background: rgba(15, 23, 42, 0.65);
				display: flex;
				inset: 0;
				justify-content: center;
				padding: 20px;
				position: fixed;
				z-index: 999999;
			}
			.dctc-portal-modal-card {
				animation: dctcPortalZoomIn 0.22s cubic-bezier(0.16, 1, 0.3, 1);
				background: var(--dctc-portal-modal-bg, #ffffff);
				border: 1px solid var(--dctc-portal-modal-border, #E2E8F0);
				border-radius: var(--dctc-portal-radius, 14px);
				box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.3), 0 0 0 1px rgba(0, 0, 0, 0.08);
				display: flex;
				flex-direction: column;
				max-height: 90vh;
				max-width: 620px;
				overflow: hidden;
				position: relative;
				width: 100%;
			}
			.dctc-portal-modal-header {
				align-items: center;
				background: var(--dctc-portal-header-bg, #F9FAFB);
				border-bottom: 1px solid var(--dctc-portal-border-divider, #F1F5F9);
				display: flex;
				justify-content: space-between;
				padding: 18px 24px;
			}
			.dctc-portal-modal-header-info {
				align-items: center;
				display: flex;
				gap: 12px;
			}
			.dctc-portal-modal-icon-badge {
				align-items: center;
				background: linear-gradient(135deg, var(--dctc-portal-primary, #4F46E5) 0%, var(--dctc-portal-primary-hover, #4338CA) 100%);
				border-radius: 10px;
				box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
				color: #ffffff;
				display: flex;
				flex-shrink: 0;
				height: 40px;
				justify-content: center;
				width: 40px;
			}
			.dctc-portal-modal-title {
				color: var(--dctc-portal-header-title, #0F172A);
				font-size: 17px;
				font-weight: 700;
				margin: 0 0 2px 0 !important;
			}
			.dctc-portal-modal-desc {
				color: var(--dctc-portal-header-subtitle, #64748B);
				font-size: 12.5px;
				line-height: 1.4;
				margin: 0 !important;
			}
			.dctc-portal-modal-close-btn {
				align-items: center;
				background: var(--dctc-portal-input-bg, #F1F5F9);
				border: 1px solid var(--dctc-portal-border-divider, transparent);
				border-radius: 8px;
				color: var(--dctc-portal-card-date, #64748B);
				cursor: pointer;
				display: flex;
				height: 32px;
				justify-content: center;
				transition: all 0.15s;
				width: 32px;
			}
			.dctc-portal-modal-close-btn:hover {
				background: var(--dctc-portal-card-hover-bg, #E2E8F0);
				color: var(--dctc-portal-header-title, #0F172A);
			}
			.dctc-portal-modal-body-scroll {
				box-sizing: border-box;
				flex: 1;
				overflow-y: auto;
				padding: 22px 24px;
			}
			.dctc-portal-modal-footer {
				align-items: center;
				background: var(--dctc-portal-modal-footer-bg, #F8FAFC);
				border-top: 1px solid var(--dctc-portal-border-divider, #F1F5F9);
				display: flex;
				gap: 12px;
				justify-content: flex-end;
				padding: 16px 24px;
			}
			@keyframes dctcPortalFadeIn {
				from { opacity: 0; }
				to { opacity: 1; }
			}
			@keyframes dctcPortalZoomIn {
				from { opacity: 0; transform: scale(0.96) translateY(8px); }
				to { opacity: 1; transform: scale(1) translateY(0); }
			}
			@media (max-width: 640px) {
				.dctc-form-grid-2 {
					grid-template-columns: 1fr;
				}
				.dctc-portal-modal-card {
					max-height: 95vh;
				}
			}

			.dctc-detail-header {
				align-items: flex-start;
				border-bottom: 1px solid var(--dctc-portal-border-divider, #E5E7EB);
				display: flex;
				flex-wrap: wrap;
				gap: 16px;
				justify-content: space-between;
				padding-bottom: 16px;
			}
			.dctc-detail-badges {
				align-items: center;
				display: flex;
				flex-wrap: wrap;
				gap: 8px;
				margin-bottom: 8px;
			}
			.dctc-detail-ticket-num {
				color: var(--dctc-portal-primary, #4F46E5);
				font-size: 15px;
				font-weight: 800;
			}
			.dctc-detail-subject {
				color: var(--dctc-portal-card-title, #111827) !important;
				font-size: 19px;
				font-weight: 700;
				line-height: 1.35;
				margin: 4px 0 0 !important;
			}
			.dctc-portal-messages-timeline {
				display: flex;
				flex-direction: column;
				gap: 14px;
				max-height: 480px;
				overflow-y: auto;
				padding: 20px 0;
			}
			.dctc-portal-msg {
				border-radius: var(--dctc-portal-inner-radius, 10px);
				box-shadow: 0 1px 3px rgba(0,0,0,0.05);
				display: flex;
				flex-direction: column;
				gap: 6px;
				max-width: 82%;
				padding: 14px 16px;
			}
			.dctc-portal-msg-customer {
				align-self: flex-end;
				background: var(--dctc-portal-msg-cust-bg, #EEF2FF);
				border: 1px solid var(--dctc-portal-msg-cust-border, #C7D2FE);
				color: var(--dctc-portal-msg-cust-text, #1E1B4B);
			}
			.dctc-portal-msg-customer .dctc-portal-msg-header span:first-child {
				color: var(--dctc-portal-msg-cust-sender, #4338CA);
				font-weight: 700;
			}
			.dctc-portal-msg-agent {
				align-self: flex-start;
				background: var(--dctc-portal-msg-agent-bg, #F9FAFB);
				border: 1px solid var(--dctc-portal-msg-agent-border, #E5E7EB);
				color: var(--dctc-portal-msg-agent-text, #111827);
			}
			.dctc-portal-msg-agent .dctc-portal-msg-header span:first-child {
				color: var(--dctc-portal-msg-agent-sender, #047857);
				font-weight: 700;
			}
			.dctc-portal-msg-header {
				align-items: center;
				display: flex;
				font-size: 12px;
				font-weight: 600;
				justify-content: space-between;
			}
			.dctc-portal-msg-time {
				color: var(--dctc-portal-card-date, #9CA3AF);
				font-size: 11px;
				font-weight: normal;
				margin-left: 12px;
			}
			.dctc-portal-msg-body {
				font-size: 14px;
				line-height: 1.55;
				white-space: pre-wrap;
				word-break: break-word;
			}
			.dctc-portal-reply-box {
				border-top: 1px solid var(--dctc-portal-border-divider, #E5E7EB);
				display: flex;
				flex-direction: column;
				gap: 12px;
				padding-top: 18px;
			}
			.dctc-portal-reply-actions {
				display: flex;
				justify-content: flex-end;
			}
			.dctc-badge {
				border-radius: 5px;
				font-size: 11px;
				font-weight: 700;
				letter-spacing: 0.3px;
				padding: 2.5px 7px;
				text-transform: uppercase;
				display: inline-flex;
				align-items: center;
			}
			.dctc-badge-open {
				background: rgba(16, 185, 129, 0.15);
				color: #059669;
				border: 1px solid rgba(16, 185, 129, 0.35);
			}
			.dctc-badge-resolved {
				background: rgba(59, 130, 246, 0.15);
				color: #2563EB;
				border: 1px solid rgba(59, 130, 246, 0.35);
			}
			.dctc-badge-closed {
				background: rgba(100, 116, 139, 0.15);
				color: #64748B;
				border: 1px solid rgba(100, 116, 139, 0.35);
			}
			.dctc-badge-priority-urgent, .dctc-badge-priority-critical {
				background: rgba(239, 68, 68, 0.15);
				color: #DC2626;
				border: 1px solid rgba(239, 68, 68, 0.35);
			}
			.dctc-badge-priority-high {
				background: rgba(234, 88, 12, 0.15);
				color: #EA580C;
				border: 1px solid rgba(234, 88, 12, 0.35);
			}
			.dctc-badge-priority-normal, .dctc-badge-priority-medium {
				background: rgba(16, 185, 129, 0.15);
				color: #10B981;
				border: 1px solid rgba(16, 185, 129, 0.35);
			}
			.dctc-badge-priority-low {
				background: rgba(100, 116, 139, 0.15);
				color: #64748B;
				border: 1px solid rgba(100, 116, 139, 0.35);
			}
			.dctc-portal-badge-cat {
				background: var(--dctc-portal-badge-cat-bg, #EEF2FF);
				border: 1px solid var(--dctc-portal-badge-cat-border, #C7D2FE);
				border-radius: 6px;
				color: var(--dctc-portal-badge-cat-text, #4338CA);
				font-size: 11.5px;
				font-weight: 600;
				padding: 2.5px 8px;
			}
			.dctc-portal-badge-tag {
				background: var(--dctc-portal-badge-tag-bg, #F1F5F9);
				border: 1px solid var(--dctc-portal-badge-tag-border, #E2E8F0);
				border-radius: 6px;
				color: var(--dctc-portal-badge-tag-text, #374151);
				font-size: 11px;
				font-weight: 500;
				padding: 2px 7px;
			}
			.dctc-portal-badge-agent {
				background: var(--dctc-portal-badge-agent-bg, #ECFDF5);
				border: 1px solid var(--dctc-portal-badge-agent-border, #A7F3D0);
				border-radius: 6px;
				color: var(--dctc-portal-badge-agent-text, #047857);
				font-size: 11.5px;
				font-weight: 600;
				padding: 2.5px 8px;
			}
			.dctc-portal-badge-chats {
				background: var(--dctc-portal-badge-chats-bg, #FEF3C7);
				border: 1px solid var(--dctc-portal-badge-chats-border, #FDE68A);
				border-radius: 6px;
				color: var(--dctc-portal-badge-chats-text, #B45309);
				font-size: 11.5px;
				font-weight: 600;
				padding: 2.5px 8px;
			}
			.dctc-portal-tags-row {
				display: flex;
				flex-wrap: wrap;
				gap: 6px;
				margin-top: 6px;
			}
			.dctc-portal-card-badges-row {
				align-items: center;
				display: flex;
				flex-wrap: wrap;
				gap: 6px;
				margin-top: 6px;
			}

			/* Clean WordPress wp_editor Styles */
			.dctc-portal-editor-wrapper .wp-editor-tabs,
			.dctc-portal-editor-wrapper .wp-switch-editor,
			.dctc-portal-editor-wrapper .mce-btn[aria-label*='Fullscreen'],
			.dctc-portal-editor-wrapper .mce-btn[aria-label*='Toolbar Toggle'],
			.dctc-portal-editor-wrapper .mce-btn[aria-label*='Align'],
			.dctc-portal-editor-wrapper .mce-btn[aria-label*='Read more'],
			.dctc-portal-editor-wrapper .mce-listbox {
				display: none !important;
			}
			.dctc-portal-editor-wrapper .wp-editor-container {
				background: var(--dctc-portal-input-bg, #ffffff);
				border: 1px solid var(--dctc-portal-input-border, #CBD5E1);
				border-radius: 8px;
				overflow: hidden;
			}
			.dctc-portal-editor-wrapper .wp-editor-container:focus-within {
				border-color: var(--dctc-portal-primary, #4F46E5);
				box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.18);
			}
			.dctc-portal-editor-wrapper .mce-tinymce {
				border: none !important;
				box-shadow: none !important;
			}
			.dctc-portal-editor-wrapper .mce-top-part {
				background: var(--dctc-portal-card-bg, #F8FAFC) !important;
				border-bottom: 1px solid var(--dctc-portal-border-divider, #E2E8F0) !important;
			}
			.dctc-portal-editor-wrapper .quicktags-toolbar {
				background: var(--dctc-portal-card-bg, #F8FAFC);
				border-bottom: 1px solid var(--dctc-portal-border-divider, #E2E8F0);
				padding: 6px 8px;
			}
			.dctc-portal-editor-wrapper .wp-media-buttons {
				margin-bottom: 8px;
			}
			
			/* Auth / Login Requirement Prompt */
			.dctc-portal-auth-prompt {
				background: var(--dctc-portal-card-bg, #ffffff);
				border: 1px solid var(--dctc-portal-card-border, #E2E8F0);
				border-radius: var(--dctc-portal-radius, 14px);
				box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
				margin: 30px auto;
				max-width: 520px;
				padding: 40px 30px;
				text-align: center;
			}
			.dctc-portal-auth-icon {
				align-items: center;
				background: rgba(79, 70, 229, 0.1);
				border-radius: 50%;
				color: var(--dctc-portal-primary, #4F46E5);
				display: inline-flex;
				height: 64px;
				justify-content: center;
				margin-bottom: 18px;
				width: 64px;
			}
			.dctc-portal-auth-icon .dashicons {
				font-size: 32px;
				height: 32px;
				width: 32px;
			}
			.dctc-portal-auth-prompt h3 {
				color: var(--dctc-portal-header-title, #0F172A);
				font-size: 20px;
				font-weight: 800;
				letter-spacing: -0.3px;
				margin: 0 0 10px 0 !important;
			}
			.dctc-portal-auth-prompt p {
				color: var(--dctc-portal-header-subtitle, #64748B);
				font-size: 14px;
				line-height: 1.6;
				margin: 0 0 24px 0 !important;
			}
			.dctc-portal-auth-actions {
				align-items: center;
				display: flex;
				flex-wrap: wrap;
				gap: 12px;
				justify-content: center;
			}
			.dctc-portal-auth-actions .dctc-portal-btn-primary,
			.dctc-portal-auth-actions .dctc-portal-btn-secondary {
				font-size: 13.5px;
				padding: 9px 18px;
				text-decoration: none !important;
			}
		";
	}
}

