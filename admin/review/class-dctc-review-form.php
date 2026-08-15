<?php

namespace DRAGWYB_CTC\Admin\Review;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! class_exists( 'DCTC_Review_Form' ) ) {

	/**
	 * DCTC_Review_Form class
	 */
	class DCTC_Review_Form {


		/**
		 * The single instance of the class.
		 *
		 * @var DCTC_Review_Form
		 */
		private static $instance;

		/**
		 * Returns the single instance of the class.
		 *
		 * @return DCTC_Review_Form
		 */
		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * DCTC_Review_Form constructor.
		 */
		public function __construct() {
			add_action( 'admin_notices', array( $this, 'print_admin_notice' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
			add_action( 'wp_ajax_dragwyb_ctc_review_dismiss', array( $this, 'dragwyb_ctc_review_dismiss' ) );
		}

		/**
		 * Prints the admin notice.
		 */
		public function print_admin_notice() {
			$installation_date = get_option( 'dragwyb_ctc_installation_date' );

			// Fallback: set installation date if it doesn't exist
			if ( false === $installation_date ) {
				$installation_date = gmdate( 'Y-m-d H:i:s' );
				update_option( 'dragwyb_ctc_installation_date', $installation_date );
				return; // don't show the notice immediately
			}

			$installed_timestamp = strtotime( $installation_date );
			$current_timestamp   = time();

			$day_in_seconds = 86400;

			// Show notice only if 3 or more days have passed
			if ( ( $current_timestamp - $installed_timestamp ) < ( 3 * $day_in_seconds ) ) {
				return;
			}

			printf(
				'<div class="notice notice-info is-dismissible dragwyb-ctc-review-notice" style="padding: 1rem;">
			<h2 style="margin: 0px">%s</h2>
			<p>%s</p>
			<div class="dragwyb-ctc-review-notice-buttons">
				<a href="' . esc_url( 'https://wordpress.org/support/plugin/dragwyb-click-to-chat/reviews/' ) . '" class="dragwyb-ctc-review-notice-button button" target="_blank" >%s</a>
				<button type="button" class="dragwyb-ctc-review-notice-button button">%s</button>
			</div>
		</div>',
				esc_html__( 'Thank you for using Click to Chat.', 'dragwyb-click-to-chat' ),
				sprintf( esc_html__( 'Enjoying the Click to Chat? Your feedback is invaluable in shaping the plugin\'s future.%sPlease consider leaving a review on the WordPress Plugin Directory to help others and support our growth.', 'dragwyb-click-to-chat' ), '<br>' ),
				esc_html__( 'Leave a Review', 'dragwyb-click-to-chat' ),
				esc_html__( 'Already Review.', 'dragwyb-click-to-chat' )
			);
		}

		/**
		 * Enqueues admin scripts and styles.
		 */
		public function enqueue_admin_scripts() {
			wp_enqueue_script( 'dragwyb-ctc-review-script', esc_url( DCTC_PLUGIN_URL . 'assets/js/admin.js' ), array( 'jquery' ), esc_attr( DCTC_VERSION ), true );
			wp_enqueue_style( 'dragwyb-ctc-review-style', esc_url( DCTC_PLUGIN_URL . 'assets/css/admin.css' ), array(), esc_attr( DCTC_VERSION ) );

			wp_localize_script(
				'dragwyb-ctc-review-script',
				'dragwyb_ctc_review_obj',
				array(
					'ajax_url' => esc_url( admin_url( 'admin-ajax.php' ) ),
					'nonce'    => esc_attr( wp_create_nonce( 'dragwyb-ctc-review-nonce' ) ),
				)
			);
		}

		/**
		 * Dismisses the review notice.
		 */
		public function dragwyb_ctc_review_dismiss() {
			check_ajax_referer( 'dragwyb-ctc-review-nonce', 'nonce' );

			if ( isset( $_POST['dragwyb_ctc_review_dismiss'] ) ) {
				update_option( 'dragwyb_ctc_already_reviewd', true );
			}

			exit;
		}
	}
}
