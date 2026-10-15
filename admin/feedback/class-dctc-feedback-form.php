<?php

/**
 * DCTC_Feedback_Form class.
 *
 * Handles plugin deactivation feedback.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'DCTC_Feedback_Form' ) ) {

	/**
	 * DCTC_Feedback_Form class.
	 */
	class DCTC_Feedback_Form {

		/**
		 * Single instance.
		 *
		 * @var self|null
		 */
		private static $instance = null;

		/**
		 * Feedback API route.
		 *
		 * @var string
		 */
		private $route;

		/**
		 * Plugin name.
		 *
		 * @var string
		 */
		private $plugin_name;

		/**
		 * Plugin slug.
		 *
		 * @var string
		 */
		private $plugin_slug;

		/**
		 * Plugin version.
		 *
		 * @var string
		 */
		private $plugin_version;

		/**
		 * Get instance.
		 *
		 * @return self
		 */
		public static function get_instance() {

			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Constructor.
		 */
		public function __construct() {

			$this->route = esc_url( 'https://feedback.dragwyb.com/wp-json/wpfd/v1/feedback' );

			$this->plugin_name = 'Click to Chat';

			$this->plugin_slug = 'dragwyb-click-to-chat';

			$this->plugin_version = defined( 'DCTC_VERSION' ) ? DCTC_VERSION : '1.0.0';

			add_action( 'wp_ajax_dctc_send_feedback', array( $this, 'dctc_send_feedback' ) );

			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles_and_scripts' ) );

			add_action( 'admin_head', array( $this, 'display_plugin_installed_form' ) );
		}

		/**
		 * Display feedback form on Plugins screen.
		 *
		 * @return void
		 */
		public function display_plugin_installed_form() {

			$screen = get_current_screen();

			if (
				! $screen ||
				'plugins' !== $screen->id ||
				! current_user_can( 'manage_options' )
			) {
				return;
			}

			$deactivation_options = array(

				'plugin_performance_issues'     => array(
					'title'             => __(
						'Technical difficulties with the plugin',
						'dragwyb-click-to-chat'
					),
					'input_placeholder' => __(
						'Please describe the technical issues you encountered.',
						'dragwyb-click-to-chat'
					),
				),

				'alternative_plugin_discovered' => array(
					'title'             => __(
						'Switched to another plugin',
						'dragwyb-click-to-chat'
					),
					'input_placeholder' => __(
						'Which plugin are you using instead?',
						'dragwyb-click-to-chat'
					),
				),

				'configuration_challenges'      => array(
					'title'             => __(
						'Difficulty with plugin configuration',
						'dragwyb-click-to-chat'
					),
					'input_placeholder' => __(
						'What configuration issue did you experience?',
						'dragwyb-click-to-chat'
					),
				),

				'temporary_plugin_pause'        => array(
					'title'             => __(
						'Temporarily disabling the plugin',
						'dragwyb-click-to-chat'
					),
					'input_placeholder' => '',
				),

				'other_reasons'                 => array(
					'title'             => __(
						'Other reason',
						'dragwyb-click-to-chat'
					),
					'input_placeholder' => __(
						'Please tell us why you are deactivating the plugin.',
						'dragwyb-click-to-chat'
					),
				),
			);
			?>

			<div
				class="dctc-deactivate-feedback-form-wrapper dctc-form-hide"
				data-slug="<?php echo esc_attr( $this->plugin_slug ); ?>"
			>

				<div class="dctc-deactivate-feedback-form">

					<div class="dctc-feedback-header">

						<div class="dctc-feedback-header-content">

							<h2>
								<?php
								echo esc_html__(
									'Help us improve',
									'dragwyb-click-to-chat'
								);
								?>
							</h2>

							<p>
								<?php
								echo esc_html__(
									'Why are you deactivating this plugin?',
									'dragwyb-click-to-chat'
								);
								?>
							</p>

						</div>

						<button
							type="button"
							class="dctc-deactivate-close"
							aria-label="<?php echo esc_attr__( 'Close feedback form', 'dragwyb-click-to-chat' ); ?>"
						>
							<span class="dashicons dashicons-no-alt"></span>
						</button>

					</div>

					<form
						method="post"
						class="dctc-feedback-form"
					>

						<?php
						wp_nonce_field(
							'dctc_send_feedback_nonce',
							'dctc_send_feedback_nonce'
						);
						?>

						<div class="dctc-feedback-body">

							<div
								id="dctc-empty-field-msg"
								class="dctc-message"
								role="alert"
								aria-live="polite"
							>
								<?php
								echo esc_html__(
									'Please select a reason before submitting your feedback.',
									'dragwyb-click-to-chat'
								);
								?>
							</div>

							<div class="dctc-section-label">
								<?php
								echo esc_html__(
									'Select a reason',
									'dragwyb-click-to-chat'
								);
								?>
							</div>

							<div class="dctc-reasons">

								<?php foreach ( $deactivation_options as $key => $option ) : ?>

									<label class="dctc-reason">

										<input
											type="radio"
											name="reason"
											value="<?php echo esc_attr( $key ); ?>"
											data-placeholder="<?php echo esc_attr( $option['input_placeholder'] ); ?>"
										/>

										<span
											class="dctc-radio-ui"
											aria-hidden="true"
										></span>

										<span class="dctc-reason-label">
											<?php echo esc_html( $option['title'] ); ?>
										</span>

									</label>

								<?php endforeach; ?>

							</div>

							<div
								class="dctc-message-section"
								hidden
							>

								<div class="dctc-section-label">
									<?php
									echo esc_html__(
										'Tell us more',
										'dragwyb-click-to-chat'
									);
									?>

									<span class="dctc-optional">
										<?php
										echo esc_html__(
											'(Optional)',
											'dragwyb-click-to-chat'
										);
										?>
									</span>
								</div>

								<textarea
									id="dctc-feedback-message"
									name="message"
									rows="3"
									maxlength="1000"
									placeholder="<?php echo esc_attr__( 'Tell us more about your experience...', 'dragwyb-click-to-chat' ); ?>"
								></textarea>

								<div class="dctc-character-count">
									<span class="dctc-character-current">0</span>/1000
								</div>

							</div>

							<div class="dctc-diagnostics">

								<label
									class="dctc-diagnostics-label"
									for="dctc-share-diagnostics"
								>

									<input
										type="checkbox"
										id="dctc-share-diagnostics"
										name="share_diagnostics"
										value="1"
									/>

									<span
										class="dctc-checkbox-ui"
										aria-hidden="true"
									></span>

									<span class="dctc-diagnostics-content">

										<strong>
											<?php
											echo esc_html__(
												'Share technical details for debugging',
												'dragwyb-click-to-chat'
											);
											?>

											<span class="dctc-optional">
												<?php
												echo esc_html__(
													'(Optional)',
													'dragwyb-click-to-chat'
												);
												?>
											</span>
										</strong>

										<small>
											<?php
											echo esc_html__(
												'If enabled, we may collect your admin email, site URL, plugin version, WordPress version, and PHP version to help diagnose issues.',
												'dragwyb-click-to-chat'
											);
											?>

											<a
												href="<?php echo esc_url( 'https://dragwyb.com/privacy-policy/' ); ?>"
												target="_blank"
												rel="noopener noreferrer"
											>
												<?php
												echo esc_html__(
													'Read more',
													'dragwyb-click-to-chat'
												);
												?>
											</a>
										</small>

									</span>

								</label>

							</div>

						</div>

						<div class="dctc-feedback-footer">

							<button
								type="button"
								class="button dctc-button-skip"
							>
								<?php
								echo esc_html__(
									'Skip & Deactivate',
									'dragwyb-click-to-chat'
								);
								?>
							</button>

							<button
								type="submit"
								class="button button-primary dctc-button-feedback"
							>

								<span class="dctc-button-text">
									<?php
									echo esc_html__(
										'Submit Feedback',
										'dragwyb-click-to-chat'
									);
									?>
								</span>

								<span
									class="dctc-button-spinner"
									aria-hidden="true"
								></span>

							</button>

						</div>

					</form>

				</div>

			</div>

			<?php
		}

		/**
		 * Enqueue assets.
		 *
		 * @return void
		 */
		public function enqueue_styles_and_scripts() {

			$screen = get_current_screen();

			if (
				! $screen ||
				'plugins' !== $screen->id ||
				! current_user_can( 'manage_options' )
			) {
				return;
			}

			wp_enqueue_style(
				'dctc-deactivate-styles',
				plugin_dir_url( __FILE__ ) . 'assets/css/dctc-feedback-form.min.css',
				array(),
				$this->plugin_version
			);

			wp_enqueue_script(
				'dctc-deactivate-scripts',
				plugin_dir_url( __FILE__ ) . 'assets/js/dctc-feedback-form.min.js',
				array( 'jquery' ),
				$this->plugin_version,
				true
			);

			wp_localize_script(
				'dctc-deactivate-scripts',
				'dctcFeedbackData',
				array(
					'ajax_url'    => admin_url( 'admin-ajax.php' ),
					'plugin_slug' => $this->plugin_slug,
				)
			);
		}

		/**
		 * Process feedback.
		 *
		 * @return void
		 */
		public function dctc_send_feedback() {

			if ( ! current_user_can( 'manage_options' ) ) {

				wp_send_json_error(
					array(
						'message' => __(
							'You do not have permission to submit feedback.',
							'dragwyb-click-to-chat'
						),
					),
					403
				);
			}

			check_ajax_referer(
				'dctc_send_feedback_nonce',
				'nonce'
			);

			$allowed_reasons = array(
				'plugin_performance_issues',
				'alternative_plugin_discovered',
				'configuration_challenges',
				'temporary_plugin_pause',
				'other_reasons',
			);

			$reason = isset( $_POST['reason'] )
				? sanitize_key( wp_unslash( $_POST['reason'] ) )
				: '';

			if ( ! in_array( $reason, $allowed_reasons, true ) ) {

				wp_send_json_error(
					array(
						'message' => __(
							'Please select a valid feedback reason.',
							'dragwyb-click-to-chat'
						),
					),
					400
				);
			}

			$message = isset( $_POST['message'] )
				? sanitize_textarea_field(
					wp_unslash( $_POST['message'] )
				)
				: '';

			/*
			 * Optional technical-details consent.
			 *
			 * Only collect technical/site information when
			 * the user explicitly enables this option.
			 */
			$share_diagnostics = isset( $_POST['share_diagnostics'] )
				&& '1' === sanitize_text_field(
					wp_unslash( $_POST['share_diagnostics'] )
				);

			/*
			 * Always send basic feedback information.
			 */
			$this->feedback_data = array(
				'plugin_name'     => sanitize_text_field( $this->plugin_name ),
				'plugin_slug'     => sanitize_text_field( $this->plugin_slug ),
				'plugin_version'  => sanitize_text_field( $this->plugin_version ),
				'deactive_reason' => $reason,
				'message'         => $message,
			);

			/*
			 * Only send technical/site information when
			 * the user explicitly checks the optional checkbox.
			 */
			if ( $share_diagnostics ) {

				$this->feedback_data['email'] = sanitize_email(
					get_option( 'admin_email' )
				);

				$this->feedback_data['website_url'] = esc_url_raw(
					home_url()
				);

				$this->feedback_data['plugin_version'] = sanitize_text_field(
					$this->plugin_version
				);

				$this->feedback_data['wp_version'] = sanitize_text_field(
					get_bloginfo( 'version' )
				);

				$this->feedback_data['php_version'] = sanitize_text_field(
					PHP_VERSION
				);
			}

			$response = wp_remote_post(
				$this->route,
				array(
					'timeout' => 15,
					'body'    => $this->feedback_data,
					'headers' => array(
						'Accept' => 'application/json',
					),
				)
			);

			if ( is_wp_error( $response ) ) {

				wp_send_json_error(
					array(
						'message' => __(
							'Unable to send feedback. Please try again.',
							'dragwyb-click-to-chat'
						),
					),
					500
				);
			}

			$status_code = wp_remote_retrieve_response_code( $response );

			if (
				$status_code < 200 ||
				$status_code >= 300
			) {

				wp_send_json_error(
					array(
						'message' => __(
							'The feedback server could not process your request.',
							'dragwyb-click-to-chat'
						),
					),
					500
				);
			}

			wp_send_json_success(
				array(
					'message' => __(
						'Thank you for your feedback.',
						'dragwyb-click-to-chat'
					),
				)
			);
		}
	}

	DCTC_Feedback_Form::get_instance();
}
