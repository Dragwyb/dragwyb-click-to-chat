<?php

/**
 * Plugin Name: Dragwyb Click to Chat - AI Chatbot & Social Messaging
 * Description: AI Chatbot plus multi-channel social chat widget with floating button.
 * Plugin URI: https://dragwyb.com/product/ai-chatbot/?utm_source=wpplugin&utm_medium=plugin_uri&utm_campaign=chatbot_demo
 * Author: Dragwyb
 * Author URI: https://dragwyb.com/?utm_source=wpplugin&utm_medium=author_uri&utm_campaign=chatbot_demo
 * Version: 1.1.3
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * Text Domain: dragwyb-click-to-chat
 * License: GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use DRAGWYB_CTC\Admin\Review\DCTC_Review_Form;

! defined( 'DCTC_FILE' ) && define( 'DCTC_FILE', __FILE__ );
! defined( 'DCTC_PLUGIN_DIR' ) && define( 'DCTC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
! defined( 'DCTC_PLUGIN_URL' ) && define( 'DCTC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
! defined( 'DCTC_VERSION' ) && define( 'DCTC_VERSION', '1.1.3' );
! defined( 'DCTC_BASENAME' ) && define( 'DCTC_BASENAME', plugin_basename( __FILE__ ) );

if ( ! class_exists( 'DCTC_Click_To_Chat' ) ) {

	class DCTC_Click_To_Chat {


		private static $instance = null;

		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		public function __construct() {
			require_once DCTC_PLUGIN_DIR . 'includes/class-dctc-error-logger.php';
			DCTC_Error_Logger::init();

			$this->required_files();
			add_action( 'plugins_loaded', array( $this, 'init' ) );
			register_activation_hook( DCTC_FILE, array( $this, 'plugin_activated' ) );
		}


		public function init() {
			if ( is_admin() ) {
				require_once DCTC_PLUGIN_DIR . 'admin/settings.php';
				require_once DCTC_PLUGIN_DIR . 'admin/import-export.php';
			}
			require_once DCTC_PLUGIN_DIR . 'includes/channel-registry.php';
			require_once DCTC_PLUGIN_DIR . 'includes/frontend.php';

			// Isolated AI Assistant module (opt-in; does not affect Channels widget).
			if ( file_exists( DCTC_PLUGIN_DIR . 'includes/ai/class-dctc-ai-module.php' ) ) {
				require_once DCTC_PLUGIN_DIR . 'includes/ai/class-dctc-ai-module.php';
				DCTC_AI_Module::get_instance();
			}
		}

		/**
		 * Includes necessary files for the plugin based on the current context.
		 */
		public function required_files() {
			// Include the class for registering plugin functionality
			if ( is_admin() ) {
				if ( file_exists( DCTC_PLUGIN_DIR . 'admin/feedback/class-dctc-feedback-form.php' ) ) {
					require_once DCTC_PLUGIN_DIR . 'admin/feedback/class-dctc-feedback-form.php';
				}

				if ( file_exists( DCTC_PLUGIN_DIR . 'admin/review/class-dctc-review-form.php' ) ) {
					require_once DCTC_PLUGIN_DIR . 'admin/review/class-dctc-review-form.php';
				}
				// Include the class for handling feedback form data in the admin area
				if ( class_exists( 'DCTC_Feedback_Form' ) ) {
					DCTC_Feedback_Form::get_instance();
				}

				$already_rated = get_option( 'dragwyb_ctc_already_reviewd', false );

				if ( ! $already_rated && class_exists( DCTC_Review_Form::class ) ) {
					DCTC_Review_Form::get_instance();
				}
			}
		}

		/**
		 * Placeholder for activation logic.
		 * This method is called when the plugin is activated.
		 * It updates options for installation date and plugin version.
		 */
		public function plugin_activated() {
			// Installation data
			if ( ! get_option( 'dragwyb_ctc_installation_date' ) ) {
				update_option( 'dragwyb_ctc_installation_date', gmdate( 'Y-m-d H:i:s' ) );
			}
			// Plugin version
			update_option( 'dragwyb_ctc_version', DCTC_VERSION );

			// AI module tables + wizard flag; redirects to AI Assistant on next admin load.
			if ( file_exists( DCTC_PLUGIN_DIR . 'includes/ai/class-dctc-ai-module.php' ) ) {
				require_once DCTC_PLUGIN_DIR . 'includes/ai/class-dctc-ai-module.php';
				DCTC_AI_Module::activate();
			}
		}
	}

	// Initialize the plugin.
	$dragwyb_click_to_chat = DCTC_Click_To_Chat::get_instance();
}
