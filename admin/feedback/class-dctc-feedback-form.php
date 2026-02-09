<?php

if (! defined('ABSPATH')) {
	exit;
}

if (! class_exists('DCTC_Feedback_Form')) {
	/**
	 * DCTC_Feedback_Form class.
	 *
	 * This class is responsible for handling the feedback form data and sending it when the plugin is deactivated.
	 */
	class DCTC_Feedback_Form
	{
		/**
		 * Holds the single instance of the class.
		 *
		 * @var self|null
		 */
		private static $instance;
		/**
		 * Stores the feedback data to be sent.
		 *
		 * @var array
		 */
		private $feedback_data;
		/**
		 * The URL to send feedback data to.
		 *
		 * @var string
		 */
		private $route;
		/**
		 * The name of the plugin.
		 *
		 * @var string
		 */
		private $plugin_name;
		/**
		 * The slug of the plugin.
		 *
		 * @var string
		 */
		private $plugin_slug;
		/**
		 * The version of the plugin.
		 *
		 * @var string
		 */
		private $plugin_version;

		/**
		 * The instance of the class.
		 *
		 * @var self|null
		 */
		public static function get_instance()
		{
			if (! isset(self::$instance) && ! (self::$instance instanceof DCTC_Feedback_Form)) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Constructor.
		 */
		public function __construct()
		{
			$this->route 		  = "https://feedback.dragwyb.com/wp-json/wpfd/v1/feedback";
			$this->plugin_name    = 'Click to Chat';
			$this->plugin_slug    = 'dragwyb-click-to-chat';
			$this->plugin_version = defined('DCTC_VERSION') ? DCTC_VERSION : '1.0.0';
			add_action('wp_ajax_dctc_send_feedback', array($this, 'dctc_send_feedback'));
			add_action('admin_enqueue_scripts', array($this, 'enqueue_styles_and_scripts'));
			add_action('admin_head', array($this, 'display_plugin_installed_form'));
		}


		/**
		 * Displays a custom form on the plugin installed page.
		 */
		public function display_plugin_installed_form()
		{

			$screen = get_current_screen();

			if (! current_user_can('manage_options') || 'plugins' !== $screen->id) {
				return;
			}

			$deactivation_options = array(
				'plugin_performance_issues'     => array(
					'title'             => __('Technical Difficulties with the Plugin', 'dragwyb-click-to-chat'),
					'input_placeholder' => __('Please describe the technical issues you encountered with the plugin', 'dragwyb-click-to-chat'),
				),
				'alternative_plugin_discovered' => array(
					'title'             => __('Switched to a More Suitable Plugin', 'dragwyb-click-to-chat'),
					'input_placeholder' => __('Please specify the alternative plugin you are using instead', 'dragwyb-click-to-chat'),
				),
				'configuration_challenges'      => array(
					'title'             => __('Difficulty with Plugin Configuration', 'dragwyb-click-to-chat'),
					'input_placeholder' => __('Please explain the configuration difficulties you faced', 'dragwyb-click-to-chat'),
				),
				'temporary_plugin_pause'        => array(
					'title'             => __('Temporary Plugin Disablement', 'dragwyb-click-to-chat'),
					'input_placeholder' => __('Please state the reason for temporarily disabling the plugin', 'dragwyb-click-to-chat'),
				),
				'other_reasons'                 => array(
					'title'             => __('Other Reasons for Deactivation', 'dragwyb-click-to-chat'),
					'input_placeholder' => __('Please provide additional information about your reason for deactivating the plugin', 'dragwyb-click-to-chat'),
				),
			);

			echo '<div class="dctc-deactivate-feedback-form-wrapper dctc-form-hide" data-slug="' . esc_attr($this->plugin_slug) . '">';
			echo '<div class="dctc-deactivate-feedback-form">';
			echo '<h2>' . __('Request Plugin Feedback', 'dragwyb-click-to-chat') . '</h2>';
			echo '<span class="dashicons dashicons-no dctc-deactivate-close"></span>';
			echo '<form method="post">';
			echo '<input type="hidden" name="action" value="dctc_send_feedback" />';
			echo '<hr>';
			echo '<div class="form-body">';
			echo '<h4>' . __('Your feedback is invaluable to us. If you have a moment, kindly let us know why you are deactivating this plugin.', 'dragwyb-click-to-chat') . '</h4>';
			echo '<div id="empty-field-msg"><p>' . __('!Please select a reason for your feedback before submitting the form.', 'dragwyb-click-to-chat') . '</p></div>';
			wp_nonce_field('dctc_send_feedback_nonce', 'dctc_send_feedback_nonce');
			foreach ($deactivation_options as $key => $option) {
				echo '<div class="form-group">';
				echo '<input type="radio" id="' . esc_attr($key) . '" name="reason" value="' . esc_attr($key) . '">';
				echo '<label for="' . esc_attr($key) . '">' . esc_html($option['title']) . '</label>';
				if ('temporary_plugin_pause' !== $key) {
					echo '<textarea name="message" id="message" placeholder="' . esc_attr($option['input_placeholder']) . '"></textarea>';
				}
				echo '</div>';
			}
			echo '<div class="form-group">';
			echo '<input type="checkbox" id="confirm" name="confirm">';
			echo '<label for="confirm">' . wp_kses_post(
				sprintf(
					__('By submitting, you agree to share your %1$s, %2$s, %3$s, and %4$s to help improve the plugin. Your data will remain private.', 'dragwyb-click-to-chat'),
					'<strong>' . esc_html__('Admin Email', 'dragwyb-click-to-chat') . '</strong>',
					'<strong>' . esc_html__('Site URL', 'dragwyb-click-to-chat') . '</strong>',
					'<strong>' . esc_html__('Plugin Version', 'dragwyb-click-to-chat') . '</strong>',
					'<strong>' . esc_html__('WP/PHP Versions', 'dragwyb-click-to-chat') . '</strong>'
				),
				'dragwyb-click-to-chat'
			) . '</label>';
			echo '</div>';
			echo '</div>';
			echo '<hr>';
			echo '<div class="dctc-button-wrapper">';
			echo '<button type="submit" class="button button-feedback">' . __('Submit Feedback', 'dragwyb-click-to-chat') . '</button>';
			echo '<button type="submit" class="button button-primary">' . __('Skip Feedback', 'dragwyb-click-to-chat') . '</button>';
			echo '</div>';
			echo '</form>';
			echo '</div>';
			echo '</div>';
		}

		/**
		 * Enqueues styles and scripts on the plugin installed page only.
		 */
		public function enqueue_styles_and_scripts()
		{
			wp_enqueue_style('dctc-deactivate-styles', plugin_dir_url(__FILE__) . 'assets/css/dctc-feedback-form.min.css', array(), esc_attr($this->plugin_version), 'all');
			wp_enqueue_script('dctc-deactivate-scripts', plugin_dir_url(__FILE__) . 'assets/js/dctc-feedback-form.min.js', array('jquery'), esc_attr($this->plugin_version), true);

			wp_localize_script(
				'dctc-deactivate-scripts',
				'dctcFeedbackData',
				array(
					'ajax_url'    => admin_url('admin-ajax.php'),
					'pluing_slug' => esc_attr($this->plugin_slug),
				)
			);
		}

		/**
		 * Sends feedback data via AJAX.
		 */
		public function dctc_send_feedback()
		{
			if (isset($_POST['action']) && 'dctc_send_feedback' === $_POST['action']) {
				check_ajax_referer('dctc_send_feedback_nonce', 'nonce');

				$wordpress_version = get_bloginfo('version');
				$php_version = phpversion();

				$this->feedback_data = array(
					'plugin_name'     => esc_attr($this->plugin_name),
					'plugin_version'  => esc_attr($this->plugin_version),
					'email'           => get_option('admin_email'),
					'website_url'     => home_url(),
					'deactive_reason' => sanitize_text_field($_POST['reason']),
					'plugin_slug'     => esc_attr($this->plugin_slug),
					'wp_version' =>   esc_attr($wordpress_version),
					'php_version' => esc_attr($php_version)
				);

				$route_url = esc_url($this->route);


				$this->feedback_data['message'] = isset($_POST['message']) && ! empty($_POST['message']) ? sanitize_textarea_field($_POST['message']) : 'N/A';

				$response = wp_remote_get(
					$route_url,
					array(
						'body'    => $this->feedback_data,
						'timeout' => 30,
					)
				);

				if (is_wp_error($response)) {
					$error_message = $response->get_error_message();
					wp_send_json_error($error_message);
				}

				wp_send_json_success();
			}
		}
	}

	DCTC_Feedback_Form::get_instance();
}
