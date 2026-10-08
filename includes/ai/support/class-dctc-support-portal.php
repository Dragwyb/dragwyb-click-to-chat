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
			'preset'                     => 'indigo', // indigo, blue, emerald, dark, purple, amber, rose, monochrome, custom

			// Styling & Colors
			'primary_color'              => '#4F46E5',
			'primary_hover_color'        => '#4338CA',
			'primary_text_color'         => '#FFFFFF',
			'secondary_btn_bg'           => '#FFFFFF',
			'secondary_btn_text'         => '#374151',
			'secondary_btn_border'       => '#D1D5DB',
			'secondary_btn_hover_bg'     => '#F3F4F6',
			'border_color'               => '#E5E7EB',
			'border_width'               => 1,
			'border_style'               => 'solid', // solid, dashed, none
			'border_radius'              => 12,
			'container_bg_color'         => '#FFFFFF',
			'header_bg_color'            => '#F9FAFB',
			'header_border_color'        => '#E5E7EB',
			'header_title_color'         => '#111827',
			'header_subtitle_color'      => '#6B7280',
			'card_bg_color'              => '#F9FAFB',
			'card_hover_bg_color'        => '#F3F4F6',
			'card_border_color'          => '#E5E7EB',
			'input_bg_color'             => '#F8FAFC',
			'input_border_color'         => '#CBD5E1',
			'font_family'                => 'inherit',

			// Titles & Descriptions
			'portal_title'               => __( 'Help & Support Center', 'dragwyb-click-to-chat' ),
			'portal_subtitle'            => __( 'View your recent requests, check status updates, or start a new support conversation.', 'dragwyb-click-to-chat' ),
			'btn_new_ticket_text'        => __( 'New Support Request', 'dragwyb-click-to-chat' ),
			'btn_back_tickets_text'      => __( 'Back to My Tickets', 'dragwyb-click-to-chat' ),
			'search_placeholder'         => __( 'Search your tickets by subject or number...', 'dragwyb-click-to-chat' ),
			'loading_text'               => __( 'Loading support tickets...', 'dragwyb-click-to-chat' ),
			'empty_tickets_title'        => __( 'No support requests found', 'dragwyb-click-to-chat' ),
			'empty_tickets_desc'         => __( 'You have not submitted any support tickets yet. Click "New Support Request" to start one.', 'dragwyb-click-to-chat' ),

			// Create Ticket Form & Modal
			'modal_title'                => __( 'Create a New Support Request', 'dragwyb-click-to-chat' ),
			'modal_subtitle'             => __( 'Submit your inquiry and our support team will assist you shortly.', 'dragwyb-click-to-chat' ),
			'category_label'             => __( 'Category', 'dragwyb-click-to-chat' ),
			'subject_label'              => __( 'Subject', 'dragwyb-click-to-chat' ),
			'subject_placeholder'        => __( 'Enter a support issue title...', 'dragwyb-click-to-chat' ),
			'message_label'              => __( 'Message', 'dragwyb-click-to-chat' ),
			'btn_submit_ticket_text'     => __( 'Submit Support Request', 'dragwyb-click-to-chat' ),
			'btn_cancel_text'            => __( 'Cancel', 'dragwyb-click-to-chat' ),

			// Single Ticket Detail
			'btn_close_ticket_text'      => __( 'Close Ticket', 'dragwyb-click-to-chat' ),
			'btn_send_reply_text'        => __( 'Send Reply', 'dragwyb-click-to-chat' ),
			'reply_placeholder'          => __( 'Type your reply message...', 'dragwyb-click-to-chat' ),

			// Guest & Logged-out User Controls
			'enable_guest_ticket_form'   => true,
			'show_login_button'          => true,
			'show_register_button'       => true,
			'guest_auth_box_title'       => __( 'Customer Support Portal', 'dragwyb-click-to-chat' ),
			'guest_auth_box_desc'        => __( 'Please log in to your account or submit a support request directly below as a guest.', 'dragwyb-click-to-chat' ),
			'btn_login_text'             => __( 'Log In to Submit Ticket', 'dragwyb-click-to-chat' ),
			'btn_register_text'          => __( 'Register Account', 'dragwyb-click-to-chat' ),
			'btn_guest_create_ticket_text' => __( 'Submit Ticket as Guest', 'dragwyb-click-to-chat' ),
			'guest_name_label'           => __( 'Your Name', 'dragwyb-click-to-chat' ),
			'guest_name_placeholder'     => __( 'John Doe', 'dragwyb-click-to-chat' ),
			'guest_email_label'          => __( 'Your Email Address', 'dragwyb-click-to-chat' ),
			'guest_email_placeholder'    => __( 'you@example.com', 'dragwyb-click-to-chat' ),
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
		$clean_color = function( $val, $default ) {
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
		$sanitized['portal_title']           = isset( $data['portal_title'] ) ? sanitize_text_field( $data['portal_title'] ) : $defaults['portal_title'];
		$sanitized['portal_subtitle']        = isset( $data['portal_subtitle'] ) ? sanitize_text_field( $data['portal_subtitle'] ) : $defaults['portal_subtitle'];
		$sanitized['btn_new_ticket_text']    = isset( $data['btn_new_ticket_text'] ) ? sanitize_text_field( $data['btn_new_ticket_text'] ) : $defaults['btn_new_ticket_text'];
		$sanitized['btn_back_tickets_text']  = isset( $data['btn_back_tickets_text'] ) ? sanitize_text_field( $data['btn_back_tickets_text'] ) : $defaults['btn_back_tickets_text'];
		$sanitized['search_placeholder']     = isset( $data['search_placeholder'] ) ? sanitize_text_field( $data['search_placeholder'] ) : $defaults['search_placeholder'];
		$sanitized['loading_text']           = isset( $data['loading_text'] ) ? sanitize_text_field( $data['loading_text'] ) : $defaults['loading_text'];
		$sanitized['empty_tickets_title']    = isset( $data['empty_tickets_title'] ) ? sanitize_text_field( $data['empty_tickets_title'] ) : $defaults['empty_tickets_title'];
		$sanitized['empty_tickets_desc']     = isset( $data['empty_tickets_desc'] ) ? sanitize_text_field( $data['empty_tickets_desc'] ) : $defaults['empty_tickets_desc'];

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
		$sanitized['btn_close_ticket_text']  = isset( $data['btn_close_ticket_text'] ) ? sanitize_text_field( $data['btn_close_ticket_text'] ) : $defaults['btn_close_ticket_text'];
		$sanitized['btn_send_reply_text']    = isset( $data['btn_send_reply_text'] ) ? sanitize_text_field( $data['btn_send_reply_text'] ) : $defaults['btn_send_reply_text'];
		$sanitized['reply_placeholder']      = isset( $data['reply_placeholder'] ) ? sanitize_text_field( $data['reply_placeholder'] ) : $defaults['reply_placeholder'];

		// Guest / Logged-out controls
		$sanitized['enable_guest_ticket_form']   = isset( $data['enable_guest_ticket_form'] ) ? rest_sanitize_boolean( $data['enable_guest_ticket_form'] ) : $defaults['enable_guest_ticket_form'];
		$sanitized['show_login_button']          = isset( $data['show_login_button'] ) ? rest_sanitize_boolean( $data['show_login_button'] ) : $defaults['show_login_button'];
		$sanitized['show_register_button']       = isset( $data['show_register_button'] ) ? rest_sanitize_boolean( $data['show_register_button'] ) : $defaults['show_register_button'];
		$sanitized['guest_auth_box_title']       = isset( $data['guest_auth_box_title'] ) ? sanitize_text_field( $data['guest_auth_box_title'] ) : $defaults['guest_auth_box_title'];
		$sanitized['guest_auth_box_desc']        = isset( $data['guest_auth_box_desc'] ) ? sanitize_text_field( $data['guest_auth_box_desc'] ) : $defaults['guest_auth_box_desc'];
		$sanitized['btn_login_text']             = isset( $data['btn_login_text'] ) ? sanitize_text_field( $data['btn_login_text'] ) : $defaults['btn_login_text'];
		$sanitized['btn_register_text']          = isset( $data['btn_register_text'] ) ? sanitize_text_field( $data['btn_register_text'] ) : $defaults['btn_register_text'];
		$sanitized['btn_guest_create_ticket_text'] = isset( $data['btn_guest_create_ticket_text'] ) ? sanitize_text_field( $data['btn_guest_create_ticket_text'] ) : $defaults['btn_guest_create_ticket_text'];
		$sanitized['guest_name_label']           = isset( $data['guest_name_label'] ) ? sanitize_text_field( $data['guest_name_label'] ) : $defaults['guest_name_label'];
		$sanitized['guest_name_placeholder']     = isset( $data['guest_name_placeholder'] ) ? sanitize_text_field( $data['guest_name_placeholder'] ) : $defaults['guest_name_placeholder'];
		$sanitized['guest_email_label']          = isset( $data['guest_email_label'] ) ? sanitize_text_field( $data['guest_email_label'] ) : $defaults['guest_email_label'];
		$sanitized['guest_email_placeholder']    = isset( $data['guest_email_placeholder'] ) ? sanitize_text_field( $data['guest_email_placeholder'] ) : $defaults['guest_email_placeholder'];

		return $sanitized;
	}

	/**
	 * Enqueue frontend scripts and styles when shortcode or page is present.
	 */
	public static function maybe_enqueue_portal_assets() {
		global $post;
		$has_portal_shortcode = is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'dctc_support_portal' );
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

		if ( ! wp_script_is( $handle, 'registered' ) && ! wp_script_is( $handle, 'enqueued' ) ) {
			$asset_file = file_exists( DCTC_PLUGIN_DIR . 'build/ai/frontend/dctc-ai-frontend.asset.php' )
				? require DCTC_PLUGIN_DIR . 'build/ai/frontend/dctc-ai-frontend.asset.php'
				: array(
					'dependencies' => array( 'wp-element' ),
					'version'      => defined( 'DCTC_VERSION' ) ? DCTC_VERSION : '1.0.0',
				);
			wp_register_script(
				$handle,
				DCTC_PLUGIN_URL . 'build/ai/frontend/dctc-ai-frontend.js',
				$asset_file['dependencies'],
				$asset_file['version'],
				true
			);
		}

		if ( ! wp_script_is( $handle, 'enqueued' ) ) {
			wp_enqueue_script( $handle );
		}

		wp_add_inline_script( $handle, self::get_portal_js(), 'after' );
	}

	/**
	 * Render the Customer Support Portal shortcode: [dctc_support_portal]
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public static function render_portal_shortcode( $atts = array() ) {
		self::enqueue_portal_styles();
		self::enqueue_portal_scripts();

		$settings = self::get_settings();

		$user_id   = get_current_user_id();
		$user      = $user_id ? get_userdata( $user_id ) : null;
		$user_name = $user ? $user->display_name : '';
		$email     = $user ? $user->user_email : '';

		$categories     = class_exists( 'DCTC_Support_Category_Service' ) ? DCTC_Support_Category_Service::get_categories( array( 'status' => 'active' ) ) : array();
		$all_taxonomies = class_exists( 'DCTC_Support_Taxonomy_Service' ) ? DCTC_Support_Taxonomy_Service::get_taxonomies() : array();

		$taxonomy_map = array();
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

		$rest_url          = esc_url_raw( rest_url( 'dctc-ai/v1/support/portal' ) );
		$nonce             = wp_create_nonce( 'wp_rest' );
		$guest_allowed     = ! empty( $settings['enable_guest_ticket_form'] );
		$can_render_modal  = $user_id || $guest_allowed;

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
						<button type="button" id="dctc-portal-btn-new" class="dctc-portal-btn-primary">
							<span class="dashicons dashicons-plus-alt2"></span>
							<?php echo esc_html( $settings['btn_new_ticket_text'] ); ?>
						</button>
					<?php elseif ( $guest_allowed ) : ?>
						<button type="button" id="dctc-portal-btn-guest-header" class="dctc-portal-btn-primary">
							<span class="dashicons dashicons-plus-alt2"></span>
							<?php echo esc_html( $settings['btn_guest_create_ticket_text'] ); ?>
						</button>
					<?php endif; ?>
					<button type="button" id="dctc-portal-btn-my-tickets" class="dctc-portal-btn-secondary" style="display:none;">
						<span class="dashicons dashicons-arrow-left-alt"></span>
						<?php echo esc_html( $settings['btn_back_tickets_text'] ); ?>
					</button>
				</div>
			</div>

			<!-- View 1: Ticket List or Auth Box -->
			<div id="dctc-portal-view-list" class="dctc-portal-view active">
				<?php if ( $user_id ) : ?>
					<div id="dctc-portal-filter-row" class="dctc-portal-filter-row" style="display:none;">
						<input type="text" id="dctc-portal-search-input" placeholder="<?php echo esc_attr( $settings['search_placeholder'] ); ?>" class="dctc-portal-input" />
					</div>
					<div id="dctc-portal-tickets-container" class="dctc-portal-tickets-list">
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
								<button type="button" id="dctc-portal-btn-guest-action" class="dctc-portal-btn-primary">
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
									<?php echo esc_html( $settings['btn_register_text'] ); ?>
								</a>
							<?php endif; ?>
						</div>
					</div>

					<!-- Container for returning guest who has a saved ticket token -->
					<div id="dctc-portal-guest-recent-section" style="display:none; margin-top:24px;">
						<h4 style="margin:0 0 12px; font-size:15px; font-weight:700; color:var(--dctc-portal-header-title, #111827);"><?php esc_html_e( 'Your Recent Support Request', 'dragwyb-click-to-chat' ); ?></h4>
						<div id="dctc-portal-tickets-container" class="dctc-portal-tickets-list"></div>
					</div>
				<?php endif; ?>
			</div>

			<?php if ( $can_render_modal ) : ?>
			<!-- Popup Modal: Create a New Support Request (Logged-in or Guest) -->
			<div id="dctc-portal-new-modal" class="dctc-portal-modal-backdrop" style="display:none;">
				<div class="dctc-portal-modal-card" role="dialog" aria-modal="true" aria-labelledby="dctc-modal-title">
					
					<!-- Modal Top Header -->
					<div class="dctc-portal-modal-header">
						<div class="dctc-portal-modal-header-info">
							<div class="dctc-portal-modal-icon-badge">
								<span class="dashicons dashicons-format-chat"></span>
							</div>
							<div>
								<h3 id="dctc-modal-title" class="dctc-portal-modal-title"><?php echo esc_html( $settings['modal_title'] ); ?></h3>
								<p class="dctc-portal-modal-desc"><?php echo esc_html( $settings['modal_subtitle'] ); ?></p>
							</div>
						</div>
						<button type="button" id="dctc-portal-modal-close" class="dctc-portal-modal-close-btn" title="<?php esc_attr_e( 'Close', 'dragwyb-click-to-chat' ); ?>">
							<span class="dashicons dashicons-no-alt"></span>
						</button>
					</div>

					<!-- Form Body -->
					<form id="dctc-portal-new-ticket-form" class="dctc-portal-form">
						<div class="dctc-portal-modal-body-scroll">

							<?php if ( ! $user_id ) : ?>
							<!-- Guest Name & Email Input Row for Logged-out Visitors -->
							<div class="dctc-form-grid-2" style="margin-bottom: 16px;">
								<div class="dctc-form-group">
									<label for="dctc-new-name">
										<?php echo esc_html( $settings['guest_name_label'] ); ?>
										<span class="dctc-portal-required">*</span>
									</label>
									<input type="text" id="dctc-new-name" required class="dctc-portal-input" placeholder="<?php echo esc_attr( $settings['guest_name_placeholder'] ); ?>" />
								</div>
								<div class="dctc-form-group">
									<label for="dctc-new-email">
										<?php echo esc_html( $settings['guest_email_label'] ); ?>
										<span class="dctc-portal-required">*</span>
									</label>
									<input type="email" id="dctc-new-email" required class="dctc-portal-input" placeholder="<?php echo esc_attr( $settings['guest_email_placeholder'] ); ?>" />
								</div>
							</div>
							<?php endif; ?>

							<div class="dctc-form-group">
								<label for="dctc-new-category">
									<?php echo esc_html( $settings['category_label'] ); ?>
									<span class="dctc-portal-required">*</span>
								</label>
								<select id="dctc-new-category" class="dctc-portal-select">
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
							<div id="dctc-portal-dynamic-subfields" class="dctc-form-grid-2" style="display:none; margin-bottom:16px;"></div>

							<div class="dctc-form-group">
								<label for="dctc-new-subject">
									<?php echo esc_html( $settings['subject_label'] ); ?>
									<span class="dctc-portal-required">*</span>
								</label>
								<input type="text" id="dctc-new-subject" required class="dctc-portal-input" placeholder="<?php echo esc_attr( $settings['subject_placeholder'] ); ?>" />
							</div>

							<div class="dctc-form-group">
								<label for="dctcportalnewmessage">
									<?php echo esc_html( $settings['message_label'] ); ?>
									<span class="dctc-portal-required">*</span>
								</label>
								<div class="dctc-portal-editor-wrapper">
									<?php
									$content   = '';
									$editor_id = 'dctcportalnewmessage';
									$editor_opt  = array(
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
							<button type="button" id="dctc-portal-modal-cancel" class="dctc-portal-btn-secondary">
								<?php echo esc_html( $settings['btn_cancel_text'] ); ?>
							</button>
							<button type="submit" id="dctc-new-submit-btn" class="dctc-portal-btn-primary">
								<span class="dashicons dashicons-saved" style="font-size:16px;line-height:1;margin-top:1px;"></span>
								<?php echo esc_html( $settings['btn_submit_ticket_text'] ); ?>
							</button>
						</div>
					</form>
				</div>
			</div>
			<?php endif; ?>

			<!-- View 2: Single Ticket Conversation Detail -->
			<div id="dctc-portal-view-detail" class="dctc-portal-view">
				<div class="dctc-detail-header">
					<div class="dctc-detail-header-left">
						<div class="dctc-detail-badges">
							<span id="dctc-detail-num" class="dctc-detail-ticket-num">#0000</span>
							<span id="dctc-detail-status" class="dctc-badge">Open</span>
							<span id="dctc-detail-priority" class="dctc-badge">Normal</span>
							<span id="dctc-detail-category" class="dctc-portal-badge-cat">Category</span>
							<span id="dctc-detail-agent" class="dctc-portal-badge-agent">Agent</span>
							<span id="dctc-detail-chats" class="dctc-portal-badge-chats">0 chats</span>
						</div>
						<div id="dctc-detail-tags-row" class="dctc-portal-tags-row" style="margin-top: 6px;"></div>
						<h3 id="dctc-detail-subject" class="dctc-detail-subject"><?php esc_html_e( 'Ticket Subject', 'dragwyb-click-to-chat' ); ?></h3>
					</div>
					<div class="dctc-detail-header-right">
						<button type="button" id="dctc-detail-close-btn" class="dctc-portal-btn-secondary">
							<?php echo esc_html( $settings['btn_close_ticket_text'] ); ?>
						</button>
					</div>
				</div>

				<!-- Messages Timeline -->
				<div id="dctc-portal-detail-messages" class="dctc-portal-messages-timeline"></div>

				<!-- Reply Box -->
				<form id="dctc-portal-reply-form" class="dctc-portal-reply-box">
					<div class="dctc-portal-editor-wrapper">
						<?php
						$content   = '';
						$editor_id = 'dctcportalreplymessage';
						$editor_opt  = array(
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
						<button type="submit" id="dctc-portal-send-reply-btn" class="dctc-portal-btn-primary">
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
			function initSupportPortal() {
				const root = document.getElementById("dctc-support-portal");
				if (!root || root.getAttribute("data-initialized") === "1") return;
				root.setAttribute("data-initialized", "1");

				const restUrl = root.getAttribute("data-rest-url");
				const nonce = root.getAttribute("data-nonce");
				const isLoggedIn = root.getAttribute("data-user-logged-in") === "1";
				const guestEnabled = root.getAttribute("data-guest-enabled") === "1";

				const emptyTitle = root.getAttribute("data-empty-title") || "No support requests found";
				const emptyDesc = root.getAttribute("data-empty-desc") || "You have not submitted any support tickets yet.";
				const loadingText = root.getAttribute("data-loading-text") || "Loading support tickets...";
				const btnSubmitText = root.getAttribute("data-btn-submit-text") || "Submit Support Request";
				const btnSendReplyText = root.getAttribute("data-btn-send-reply-text") || "Send Reply";

				const viewList = document.getElementById("dctc-portal-view-list");
				const viewDetail = document.getElementById("dctc-portal-view-detail");

				const btnNew = document.getElementById("dctc-portal-btn-new");
				const btnGuestHeader = document.getElementById("dctc-portal-btn-guest-header");
				const btnGuestAction = document.getElementById("dctc-portal-btn-guest-action");
				const btnMyTickets = document.getElementById("dctc-portal-btn-my-tickets");
				const filterRow = document.getElementById("dctc-portal-filter-row");
				const ticketsContainer = document.getElementById("dctc-portal-tickets-container");
				const searchInput = document.getElementById("dctc-portal-search-input");
				const guestRecentSection = document.getElementById("dctc-portal-guest-recent-section");

				const newForm = document.getElementById("dctc-portal-new-ticket-form");
				const replyForm = document.getElementById("dctc-portal-reply-form");
				const closeBtn = document.getElementById("dctc-detail-close-btn");

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

				const modalNew = document.getElementById("dctc-portal-new-modal");
				const btnModalClose = document.getElementById("dctc-portal-modal-close");
				const btnModalCancel = document.getElementById("dctc-portal-modal-cancel");

				function showView(view) {
					[viewList, viewDetail].forEach(function(v) {
						if (v) v.classList.remove("active");
					});
					if (view) view.classList.add("active");

					if (view === viewList) {
						if (detailPollInterval) {
							clearInterval(detailPollInterval);
							detailPollInterval = null;
						}
						currentTicketUuid = null;
						if (btnNew) btnNew.style.display = "inline-flex";
						if (btnGuestHeader) btnGuestHeader.style.display = "inline-flex";
						if (btnMyTickets) btnMyTickets.style.display = "none";
					} else {
						if (btnNew) btnNew.style.display = "inline-flex";
						if (btnGuestHeader) btnGuestHeader.style.display = "inline-flex";
						if (btnMyTickets) btnMyTickets.style.display = "inline-flex";
					}
				}

				function openNewModal() {
					if (!modalNew) return;
					modalNew.style.display = "flex";
					syncFieldVisibility();
					setTimeout(function() {
						const firstInput = document.getElementById("dctc-new-name") || document.getElementById("dctc-new-subject");
						if (firstInput) firstInput.focus();
					}, 60);
				}

				function closeNewModal() {
					if (!modalNew) return;
					modalNew.style.display = "none";
				}

				if (btnNew) {
					btnNew.addEventListener("click", openNewModal);
				}
				if (btnGuestHeader) {
					btnGuestHeader.addEventListener("click", openNewModal);
				}
				if (btnGuestAction) {
					btnGuestAction.addEventListener("click", openNewModal);
				}
				if (btnModalClose) {
					btnModalClose.addEventListener("click", closeNewModal);
				}
				if (btnModalCancel) {
					btnModalCancel.addEventListener("click", closeNewModal);
				}
				if (modalNew) {
					modalNew.addEventListener("click", function(e) {
						if (e.target === modalNew) {
							closeNewModal();
						}
					});
				}
				document.addEventListener("keydown", function(e) {
					if (e.key === "Escape" && modalNew && modalNew.style.display !== "none") {
						closeNewModal();
					}
				});

				function syncFieldVisibility() {
					const catSelect = document.getElementById("dctc-new-category");
					const dynamicContainer = document.getElementById("dctc-portal-dynamic-subfields");
					if (!catSelect || !dynamicContainer) return;

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
						dynamicContainer.style.display = "none";
						dynamicContainer.innerHTML = "";
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
						dynamicContainer.style.display = "none";
						dynamicContainer.innerHTML = "";
						return;
					}

					if (activeSubTaxes.length === 1) {
						dynamicContainer.className = "dctc-form-grid-1";
						dynamicContainer.style.gridTemplateColumns = "1fr";
					} else {
						dynamicContainer.className = "dctc-form-grid-2";
						dynamicContainer.style.gridTemplateColumns = "";
					}

					let fieldsHtml = "";
					activeSubTaxes.forEach(function(tax) {
						const slug = tax.slug;
						const taxName = tax.name || slug;
						const terms = tax.terms;

						fieldsHtml += "<div class=\"dctc-form-group" + (activeSubTaxes.length === 1 ? " is-full-width" : "") + "\">";
						fieldsHtml += "  <label for=\"dctc-subfield-" + slug + "\">" + taxName + "</label>";
						fieldsHtml += "  <select id=\"dctc-subfield-" + slug + "\" class=\"dctc-portal-select dctc-dynamic-subfield-input\" data-tax-slug=\"" + slug + "\">";
						fieldsHtml += "    <option value=\"\">-- Select " + taxName + " (Optional) --</option>";
						terms.forEach(function(term) {
							const termName = term.name || term.slug || "";
							fieldsHtml += "    <option value=\"" + termName + "\">" + termName + "</option>";
						});
						fieldsHtml += "  </select>";
						fieldsHtml += "</div>";
					});

					dynamicContainer.innerHTML = fieldsHtml;
					dynamicContainer.style.display = "grid";
				}

				const catDropdown = document.getElementById("dctc-new-category");
				if (catDropdown) {
					catDropdown.addEventListener("change", syncFieldVisibility);
				}

				if (btnMyTickets) {
					btnMyTickets.addEventListener("click", function() {
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
						const url = restUrl + "/tickets" + (q ? "?search=" + encodeURIComponent(q) : "");
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

								html += "<div class=\"dctc-portal-ticket-card\" data-uuid=\"" + t.uuid + "\">";
								html += "  <div class=\"dctc-portal-card-left\" style=\"flex:1;\">";
								html += "    <div class=\"dctc-portal-card-top\">";
								html += "      <span class=\"dctc-portal-card-num\">#" + t.ticket_number + "</span>";
								html += "      <span class=\"dctc-badge " + statusBadge + "\">" + t.status + "</span>";
								html += "      <span class=\"dctc-portal-badge-cat\">" + categoryName + "</span>";
								html += "      <span class=\"dctc-portal-badge-agent\">" + agentName + "</span>";
								html += "      <span class=\"dctc-portal-badge-chats\">" + chatCount + " " + (chatCount === 1 ? "chat" : "chats") + "</span>";
								html += "    </div>";
								html += "    <h4 class=\"dctc-portal-card-title\">" + (t.subject || "Support Ticket") + "</h4>";
								if (tags.length > 0) {
									html += "    <div class=\"dctc-portal-card-badges-row\">";
									tags.forEach(function(tag) {
										html += "      <span class=\"dctc-portal-badge-tag\">" + tag + "</span>";
									});
									html += "    </div>";
								}
								html += "  </div>";
								html += "  <span class=\"dctc-portal-card-date\">" + (t.created_at ? t.created_at.split(" ")[0] : "") + "</span>";
								html += "</div>";
							});
							ticketsContainer.innerHTML = html;

							// Add click handlers
							document.querySelectorAll(".dctc-portal-ticket-card").forEach(function(card) {
								card.addEventListener("click", function() {
									const uuid = this.getAttribute("data-uuid");
									loadTicketDetail(uuid);
								});
							});
						} else {
							if (q) {
								if (filterRow) filterRow.style.display = "";
								ticketsContainer.innerHTML = "<div style=\"text-align:center;padding:30px 0;color:#6B7280;\">No tickets found matching your search.</div>";
							} else {
								if (filterRow) filterRow.style.display = "none";
								if (isLoggedIn) {
									ticketsContainer.innerHTML = "<div style=\"text-align:center;padding:30px 0;color:#6B7280;\"><strong>" + emptyTitle + "</strong><p style=\"margin:6px 0 0;font-size:13px;\">" + emptyDesc + "</p></div>";
								} else {
									if (guestRecentSection) guestRecentSection.style.display = "none";
								}
							}
						}
					} catch (err) {
						if (filterRow) filterRow.style.display = "none";
						ticketsContainer.innerHTML = "<div style=\"color:#DC2626;text-align:center;padding:20px 0;\">Error loading support tickets. Please try again.</div>";
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
						}, 4000); // Poll every 4 seconds for fresh agent replies
					}

					const msgContainer = document.getElementById("dctc-portal-detail-messages");
					if (!msgContainer) return;
					if (!isSilentUpdate) {
						msgContainer.innerHTML = "<div>Loading conversation...</div>";
					}

					try {
						const headers = { "X-WP-Nonce": nonce };
						if (guestToken) headers["X-Guest-Token"] = guestToken;

						const res = await fetch(restUrl + "/tickets/" + uuid, { headers: headers });
						const data = await res.json();

						if (data.success && data.ticket) {
							const t = data.ticket;
							const numEl = document.getElementById("dctc-detail-num");
							const subEl = document.getElementById("dctc-detail-subject");
							const statEl = document.getElementById("dctc-detail-status");
							const priEl = document.getElementById("dctc-detail-priority");

							if (numEl) numEl.textContent = "#" + t.ticket_number;
							if (subEl) subEl.textContent = t.subject;
							if (statEl) statEl.textContent = t.status;
							if (priEl) priEl.textContent = t.priority;
							
							const catElem = document.getElementById("dctc-detail-category");
							if (catElem) catElem.textContent = (t.category_name || "General");
							
							const agentElem = document.getElementById("dctc-detail-agent");
							if (agentElem) agentElem.textContent = (t.agent_name || "Support Staff");

							const chatsElem = document.getElementById("dctc-detail-chats");
							const count = t.chat_count !== undefined ? t.chat_count : (t.messages ? t.messages.length : 0);
							if (chatsElem) chatsElem.textContent = count + " " + (count === 1 ? "chat" : "chats");

							const tagsRow = document.getElementById("dctc-detail-tags-row");
							if (tagsRow) {
								const tags = Array.isArray(t.tags) ? t.tags : [];
								tagsRow.innerHTML = tags.map(function(tag) {
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
							msgContainer.innerHTML = msgHtml || "<div>No messages yet.</div>";
							if (!isSilentUpdate || previousScrollBottom) {
								msgContainer.scrollTop = msgContainer.scrollHeight;
							}
						}
					} catch (err) {
						if (!isSilentUpdate) {
							msgContainer.innerHTML = "<div style=\"color:#DC2626;\">Error loading ticket details.</div>";
						}
					}
				}

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

				// Submit New Ticket
				if (newForm) {
					newForm.addEventListener("submit", async function(e) {
						e.preventDefault();
						const submitBtn = document.getElementById("dctc-new-submit-btn");
						if (submitBtn) {
							submitBtn.disabled = true;
							submitBtn.textContent = "Submitting...";
						}

						const nameInput = document.getElementById("dctc-new-name");
						const emailInput = document.getElementById("dctc-new-email");
						const catInput = document.getElementById("dctc-new-category");
						const subjectInput = document.getElementById("dctc-new-subject");
						const msgContent = getPortalEditorContent("dctcportalnewmessage").trim();

						if (!msgContent) {
							alert("Please enter a ticket message.");
							if (submitBtn) {
								submitBtn.disabled = false;
								submitBtn.textContent = btnSubmitText;
							}
							return;
						}

						// Collect all dynamic subfield values
						const tagsList = [];
						const dynamicSubContainer = document.getElementById("dctc-portal-dynamic-subfields");
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
								clearPortalEditorContent("dctcportalnewmessage");
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

						const replyBtn = document.getElementById("dctc-portal-send-reply-btn");
						const msgText = getPortalEditorContent("dctcportalreplymessage").trim();
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
								clearPortalEditorContent("dctcportalreplymessage");
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

				if (searchInput) {
					let searchTimeout;
					searchInput.addEventListener("input", function() {
						clearTimeout(searchTimeout);
						searchTimeout = setTimeout(loadTickets, 400);
					});
				}

				// Initial load
				if (isLoggedIn || guestToken) {
					loadTickets();
				}
			}

			if (document.readyState === "loading") {
				document.addEventListener("DOMContentLoaded", initSupportPortal);
			} else {
				initSupportPortal();
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

		$primary_color        = esc_attr( $s['primary_color'] );
		$primary_hover        = esc_attr( $s['primary_hover_color'] );
		$primary_text         = esc_attr( $s['primary_text_color'] );
		$secondary_bg         = esc_attr( $s['secondary_btn_bg'] );
		$secondary_text       = esc_attr( $s['secondary_btn_text'] );
		$secondary_border     = esc_attr( $s['secondary_btn_border'] );
		$secondary_hover_bg   = esc_attr( $s['secondary_btn_hover_bg'] );
		$border_color         = esc_attr( $s['border_color'] );
		$border_width         = intval( $s['border_width'] ) . 'px';
		$border_style         = esc_attr( $s['border_style'] );
		$border_radius        = intval( $s['border_radius'] ) . 'px';
		$inner_radius         = max( 4, intval( $s['border_radius'] ) - 4 ) . 'px';
		$btn_radius           = max( 4, intval( $s['border_radius'] * 0.65 ) ) . 'px';
		$container_bg         = esc_attr( $s['container_bg_color'] );
		$header_bg            = esc_attr( $s['header_bg_color'] );
		$header_border        = esc_attr( $s['header_border_color'] );
		$header_title_color   = esc_attr( $s['header_title_color'] );
		$header_sub_color     = esc_attr( $s['header_subtitle_color'] );
		$card_bg              = esc_attr( $s['card_bg_color'] );
		$card_hover_bg        = esc_attr( $s['card_hover_bg_color'] );
		$card_border          = esc_attr( $s['card_border_color'] );
		$input_bg             = esc_attr( $s['input_bg_color'] );
		$input_border         = esc_attr( $s['input_border_color'] );

		return "
			:root, .dctc-portal-root {
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
				--dctc-portal-input-bg: {$input_bg};
				--dctc-portal-input-border: {$input_border};
			}

			.dctc-portal-root {
				background: var(--dctc-portal-container-bg, #ffffff);
				border-width: var(--dctc-portal-border-width, 1px);
				border-style: var(--dctc-portal-border-style, solid);
				border-color: var(--dctc-portal-border-color, #E5E7EB);
				border-radius: var(--dctc-portal-radius, 12px);
				box-shadow: 0 4px 16px rgba(0,0,0,0.04);
				font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
				margin: 20px 0;
				overflow: hidden;
				padding: 0;
				position: relative;
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
				margin-bottom: 16px;
			}
			.dctc-portal-input, .dctc-portal-select, .dctc-portal-textarea {
				background: var(--dctc-portal-input-bg, #F8FAFC);
				border: 1px solid var(--dctc-portal-input-border, #CBD5E1);
				border-radius: var(--dctc-portal-btn-radius, 8px);
				box-sizing: border-box;
				color: #1E293B;
				font-family: inherit;
				font-size: 13.5px;
				padding: 10px 14px;
				transition: all 0.15s ease-in-out;
				width: 100%;
				max-width: 100%;
			}
			.dctc-portal-select {
				appearance: none;
				-webkit-appearance: none;
				background-position: right 12px center;
				background-repeat: no-repeat;
				background-size: 16px 16px;
				padding-right: 36px;
			}
			.dctc-portal-input:focus, .dctc-portal-select:focus, .dctc-portal-textarea:focus {
				background: #FFFFFF;
				border-color: var(--dctc-portal-primary, #4F46E5);
				box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
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
				border-color: #CBD5E1;
			}
			.dctc-portal-card-left {
				display: flex;
				flex-direction: column;
				gap: 4px;
			}
			.dctc-portal-card-top {
				align-items: center;
				display: flex;
				gap: 8px;
			}
			.dctc-portal-card-num {
				color: var(--dctc-portal-primary, #4F46E5);
				font-size: 12px;
				font-weight: 700;
			}
			.dctc-portal-card-title {
				color: #111827;
				font-size: 14.5px;
				font-weight: 600;
				margin: 0;
			}
			.dctc-portal-card-date {
				color: #9CA3AF;
				font-size: 12px;
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
				color: #334155;
				display: flex;
				font-size: 13px;
				font-weight: 600;
			}
			
			/* Modal Dialog Styles */
			.dctc-portal-modal-backdrop {
				align-items: center;
				animation: dctcPortalFadeIn 0.2s cubic-bezier(0.16, 1, 0.3, 1);
				backdrop-filter: blur(4px);
				background: rgba(15, 23, 42, 0.55);
				display: flex;
				inset: 0;
				justify-content: center;
				padding: 20px;
				position: fixed;
				z-index: 999999;
			}
			.dctc-portal-modal-card {
				animation: dctcPortalZoomIn 0.22s cubic-bezier(0.16, 1, 0.3, 1);
				background: #ffffff;
				border: 1px solid #E2E8F0;
				border-radius: var(--dctc-portal-radius, 14px);
				box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25), 0 0 0 1px rgba(0, 0, 0, 0.05);
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
				border-bottom: 1px solid #F1F5F9;
				display: flex;
				justify-content: space-between;
				padding: 20px 24px;
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
				background: #F1F5F9;
				border: none;
				border-radius: 8px;
				color: #64748B;
				cursor: pointer;
				display: flex;
				height: 32px;
				justify-content: center;
				transition: all 0.15s;
				width: 32px;
			}
			.dctc-portal-modal-close-btn:hover {
				background: #E2E8F0;
				color: #0F172A;
			}
			.dctc-portal-modal-body-scroll {
				box-sizing: border-box;
				flex: 1;
				overflow-y: auto;
				padding: 22px 24px;
			}
			.dctc-portal-modal-footer {
				align-items: center;
				background: #F8FAFC;
				border-top: 1px solid #F1F5F9;
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
				align-items: center;
				border-bottom: 1px solid #E5E7EB;
				display: flex;
				flex-wrap: wrap;
				gap: 16px;
				justify-content: space-between;
				padding-bottom: 16px;
			}
			.dctc-detail-badges {
				align-items: center;
				display: flex;
				gap: 8px;
				margin-bottom: 6px;
			}
			.dctc-detail-ticket-num {
				color: var(--dctc-portal-primary, #4F46E5);
				font-size: 14px;
				font-weight: 700;
			}
			.dctc-detail-subject {
				color: #111827;
				font-size: 18px;
				font-weight: 700;
				margin: 0 !important;
			}
			.dctc-portal-messages-timeline {
				display: flex;
				flex-direction: column;
				gap: 12px;
				max-height: 440px;
				overflow-y: auto;
				padding: 20px 0;
			}
			.dctc-portal-msg {
				border-radius: 10px;
				display: flex;
				flex-direction: column;
				gap: 4px;
				max-width: 80%;
				padding: 12px 16px;
			}
			.dctc-portal-msg-customer {
				align-self: flex-end;
				background: #EEF2FF;
				border: 1px solid #C7D2FE;
				color: #1E1B4B;
			}
			.dctc-portal-msg-agent {
				align-self: flex-start;
				background: #F9FAFB;
				border: 1px solid #E5E7EB;
				color: #111827;
			}
			.dctc-portal-msg-header {
				align-items: center;
				display: flex;
				font-size: 11.5px;
				font-weight: 600;
				justify-content: space-between;
			}
			.dctc-portal-msg-time {
				color: #9CA3AF;
				font-weight: normal;
				margin-left: 12px;
			}
			.dctc-portal-msg-body {
				font-size: 13.5px;
				line-height: 1.5;
				white-space: pre-wrap;
			}
			.dctc-portal-reply-box {
				border-top: 1px solid #E5E7EB;
				display: flex;
				flex-direction: column;
				gap: 10px;
				padding-top: 16px;
			}
			.dctc-portal-reply-actions {
				display: flex;
				justify-content: flex-end;
			}
			.dctc-badge {
				border-radius: 4px;
				font-size: 10.5px;
				font-weight: 700;
				padding: 2px 6px;
				text-transform: uppercase;
			}
			.dctc-badge-open { background: #ECFDF5; color: #047857; }
			.dctc-badge-resolved { background: #EFF6FF; color: #1D4ED8; }
			.dctc-badge-closed { background: #F3F4F6; color: #6B7280; }
			.dctc-portal-badge-cat {
				background: #EEF2FF;
				border: 1px solid #C7D2FE;
				border-radius: 6px;
				color: #4338CA;
				font-size: 11.5px;
				font-weight: 600;
				padding: 3px 8px;
			}
			.dctc-portal-badge-tag {
				background: #F3F4F6;
				border: 1px solid #E5E7EB;
				border-radius: 6px;
				color: #374151;
				font-size: 11px;
				font-weight: 500;
				padding: 2px 7px;
			}
			.dctc-portal-badge-agent {
				background: #ECFDF5;
				border: 1px solid #A7F3D0;
				border-radius: 6px;
				color: #047857;
				font-size: 11.5px;
				font-weight: 600;
				padding: 3px 8px;
			}
			.dctc-portal-badge-chats {
				background: #FEF3C7;
				border: 1px solid #FDE68A;
				border-radius: 6px;
				color: #B45309;
				font-size: 11.5px;
				font-weight: 600;
				padding: 3px 8px;
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
				background: #ffffff;
				border: 1px solid var(--dctc-portal-input-border, #CBD5E1);
				border-radius: 8px;
				overflow: hidden;
			}
			.dctc-portal-editor-wrapper .wp-editor-container:focus-within {
				border-color: var(--dctc-portal-primary, #4F46E5);
				box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
			}
			.dctc-portal-editor-wrapper .mce-tinymce {
				border: none !important;
				box-shadow: none !important;
			}
			.dctc-portal-editor-wrapper .mce-top-part {
				background: #F8FAFC !important;
				border-bottom: 1px solid #E2E8F0 !important;
			}
			.dctc-portal-editor-wrapper .quicktags-toolbar {
				background: #F8FAFC;
				border-bottom: 1px solid #E2E8F0;
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
