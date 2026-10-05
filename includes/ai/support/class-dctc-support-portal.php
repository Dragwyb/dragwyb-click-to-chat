<?php
/**
 * DCTC Support Portal Shortcode & Frontend Renderer
 *
 * Implements the customer-facing support center shortcode: [dragwyb_support]
 * Handles ticket lists, detail views, reply composer, guest access, and ticket submission.
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
		add_shortcode( 'dragwyb_support', array( __CLASS__, 'render_portal_shortcode' ) );
		add_shortcode( 'dctc_support_portal', array( __CLASS__, 'render_portal_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'maybe_enqueue_portal_assets' ) );
	}

	/**
	 * Enqueue frontend scripts and styles when shortcode or page is present.
	 */
	public static function maybe_enqueue_portal_assets() {
		global $post;
		if ( ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'dragwyb_support' ) ) || is_singular() ) {
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
	 * Render the Customer Support Portal shortcode: [dragwyb_support]
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public static function render_portal_shortcode( $atts = array() ) {
		self::enqueue_portal_styles();
		self::enqueue_portal_scripts();

		$user_id   = get_current_user_id();
		$user      = $user_id ? get_userdata( $user_id ) : null;
		$user_name = $user ? $user->display_name : '';
		$email     = $user ? $user->user_email : '';

		$categories     = DCTC_Support_Category_Service::get_categories( array( 'status' => 'active' ) );
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

		$rest_url = esc_url_raw( rest_url( 'dctc-ai/v1/support/portal' ) );
		$nonce    = wp_create_nonce( 'wp_rest' );

		ob_start();
		?>
		<div id="dctc-support-portal" class="dctc-portal-root" data-rest-url="<?php echo esc_attr( $rest_url ); ?>" data-nonce="<?php echo esc_attr( $nonce ); ?>" data-user-logged-in="<?php echo $user_id ? '1' : '0'; ?>" data-user-name="<?php echo esc_attr( $user_name ); ?>" data-user-email="<?php echo esc_attr( $email ); ?>" data-taxonomies-data="<?php echo esc_attr( wp_json_encode( $taxonomy_map ) ); ?>">
			
			<!-- Portal Header -->
			<div class="dctc-portal-header">
				<div class="dctc-portal-header-left">
					<h2><?php esc_html_e( 'Help & Support Center', 'dragwyb-click-to-chat' ); ?></h2>
					<p><?php esc_html_e( 'View your recent requests, check status updates, or start a new support conversation.', 'dragwyb-click-to-chat' ); ?></p>
				</div>
				<div class="dctc-portal-header-right">
					<button type="button" id="dctc-portal-btn-new" class="dctc-portal-btn-primary">
						<span class="dashicons dashicons-plus-alt2"></span>
						<?php esc_html_e( 'New Support Request', 'dragwyb-click-to-chat' ); ?>
					</button>
					<button type="button" id="dctc-portal-btn-my-tickets" class="dctc-portal-btn-secondary" style="display:none;">
						<span class="dashicons dashicons-arrow-left-alt"></span>
						<?php esc_html_e( 'Back to My Tickets', 'dragwyb-click-to-chat' ); ?>
					</button>
				</div>
			</div>

			<!-- View 1: Ticket List -->
			<div id="dctc-portal-view-list" class="dctc-portal-view active">
				<div id="dctc-portal-filter-row" class="dctc-portal-filter-row" style="display:none;">
					<input type="text" id="dctc-portal-search-input" placeholder="<?php esc_attr_e( 'Search your tickets by subject or number...', 'dragwyb-click-to-chat' ); ?>" class="dctc-portal-input" />
				</div>
				<div id="dctc-portal-tickets-container" class="dctc-portal-tickets-list">
					<div class="dctc-portal-loading"><?php esc_html_e( 'Loading support tickets...', 'dragwyb-click-to-chat' ); ?></div>
				</div>
			</div>

			<!-- Popup Modal: Create a New Support Request -->
			<div id="dctc-portal-new-modal" class="dctc-portal-modal-backdrop" style="display:none;">
				<div class="dctc-portal-modal-card" role="dialog" aria-modal="true" aria-labelledby="dctc-modal-title">
					
					<!-- Modal Top Header -->
					<div class="dctc-portal-modal-header">
						<div class="dctc-portal-modal-header-info">
							<div class="dctc-portal-modal-icon-badge">
								<span class="dashicons dashicons-format-chat"></span>
							</div>
							<div>
								<h3 id="dctc-modal-title" class="dctc-portal-modal-title"><?php esc_html_e( 'Create a New Support Request', 'dragwyb-click-to-chat' ); ?></h3>
								<p class="dctc-portal-modal-desc"><?php esc_html_e( 'Submit your inquiry and our support team will assist you shortly.', 'dragwyb-click-to-chat' ); ?></p>
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
								<div class="dctc-form-grid-2">
									<div class="dctc-form-group">
										<label for="dctc-new-name">
											<?php esc_html_e( 'Your Name', 'dragwyb-click-to-chat' ); ?>
											<span class="dctc-portal-required">*</span>
										</label>
										<input type="text" id="dctc-new-name" required class="dctc-portal-input" placeholder="e.g. Jane Doe" />
									</div>
									<div class="dctc-form-group">
										<label for="dctc-new-email">
											<?php esc_html_e( 'Your Email', 'dragwyb-click-to-chat' ); ?>
											<span class="dctc-portal-required">*</span>
										</label>
										<input type="email" id="dctc-new-email" required class="dctc-portal-input" placeholder="e.g. jane@example.com" />
									</div>
								</div>
							<?php endif; ?>

							<div class="dctc-form-group">
								<label for="dctc-new-category">
									<?php esc_html_e( 'Category', 'dragwyb-click-to-chat' ); ?>
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
									<?php esc_html_e( 'Subject', 'dragwyb-click-to-chat' ); ?>
									<span class="dctc-portal-required">*</span>
								</label>
								<input type="text" id="dctc-new-subject" required class="dctc-portal-input" placeholder="<?php esc_attr_e( 'Brief summary of what you need help with', 'dragwyb-click-to-chat' ); ?>" />
							</div>

							<div class="dctc-form-group">
								<label for="dctc-new-message">
									<?php esc_html_e( 'Message Details', 'dragwyb-click-to-chat' ); ?>
									<span class="dctc-portal-required">*</span>
								</label>
								<textarea id="dctc-new-message" rows="4" required class="dctc-portal-textarea" placeholder="<?php esc_attr_e( 'Please provide detailed information to help us assist you faster...', 'dragwyb-click-to-chat' ); ?>"></textarea>
							</div>
						</div>

						<!-- Modal Footer Action Bar -->
						<div class="dctc-portal-modal-footer">
							<button type="button" id="dctc-portal-modal-cancel" class="dctc-portal-btn-secondary">
								<?php esc_html_e( 'Cancel', 'dragwyb-click-to-chat' ); ?>
							</button>
							<button type="submit" id="dctc-new-submit-btn" class="dctc-portal-btn-primary">
								<span class="dashicons dashicons-saved" style="font-size:16px;line-height:1;margin-top:1px;"></span>
								<?php esc_html_e( 'Submit Support Request', 'dragwyb-click-to-chat' ); ?>
							</button>
						</div>
					</form>
				</div>
			</div>

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
						<h3 id="dctc-detail-subject" class="dctc-detail-subject">Ticket Subject</h3>
					</div>
					<div class="dctc-detail-header-right">
						<button type="button" id="dctc-detail-close-btn" class="dctc-portal-btn-secondary">
							<?php esc_html_e( 'Close Ticket', 'dragwyb-click-to-chat' ); ?>
						</button>
					</div>
				</div>

				<!-- Messages Timeline -->
				<div id="dctc-portal-detail-messages" class="dctc-portal-messages-timeline"></div>

				<!-- Reply Box -->
				<form id="dctc-portal-reply-form" class="dctc-portal-reply-box">
					<textarea id="dctc-portal-reply-text" rows="3" required placeholder="<?php esc_attr_e( 'Type your reply here...', 'dragwyb-click-to-chat' ); ?>" class="dctc-portal-textarea"></textarea>
					<div class="dctc-portal-reply-actions">
						<button type="submit" id="dctc-portal-send-reply-btn" class="dctc-portal-btn-primary">
							<?php esc_html_e( 'Send Reply', 'dragwyb-click-to-chat' ); ?>
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

				const viewList = document.getElementById("dctc-portal-view-list");
				const viewDetail = document.getElementById("dctc-portal-view-detail");

				const btnNew = document.getElementById("dctc-portal-btn-new");
				const btnMyTickets = document.getElementById("dctc-portal-btn-my-tickets");
				const filterRow = document.getElementById("dctc-portal-filter-row");
				const ticketsContainer = document.getElementById("dctc-portal-tickets-container");
				const searchInput = document.getElementById("dctc-portal-search-input");

				const newForm = document.getElementById("dctc-portal-new-ticket-form");
				const replyForm = document.getElementById("dctc-portal-reply-form");
				const closeBtn = document.getElementById("dctc-detail-close-btn");

				let currentTicketUuid = null;
				let guestToken = localStorage.getItem("dctc_guest_token") || "";

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
						if (btnNew) btnNew.style.display = "inline-flex";
						if (btnMyTickets) btnMyTickets.style.display = "none";
					} else {
						if (btnNew) btnNew.style.display = "inline-flex";
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

					// Collect active sub-taxonomies that actually have terms
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

					// If only 1 field is present, display full width; if 2 or more, use 2-column grid
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
						loadTickets();
					});
				}

				// Load Tickets List
				async function loadTickets() {
					if (!ticketsContainer) return;
					ticketsContainer.innerHTML = "<div class=\"dctc-portal-loading\">Loading support tickets...</div>";
					try {
						const headers = { "X-WP-Nonce": nonce };
						if (guestToken) headers["X-Guest-Token"] = guestToken;

						const q = searchInput ? searchInput.value.trim() : "";
						const url = restUrl + "/tickets" + (q ? "?search=" + encodeURIComponent(q) : "");
						const res = await fetch(url, { headers: headers });
						const data = await res.json();

						if (data.success && data.tickets && data.tickets.length > 0) {
							if (filterRow) filterRow.style.display = "";
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
								ticketsContainer.innerHTML = "<div style=\"text-align:center;padding:30px 0;color:#6B7280;\">No support requests found. Click \"New Support Request\" to start one.</div>";
							}
						}
					} catch (err) {
						if (filterRow) filterRow.style.display = "none";
						ticketsContainer.innerHTML = "<div style=\"color:#DC2626;text-align:center;padding:20px 0;\">Error loading support tickets. Please try again.</div>";
					}
				}

				// Load Single Ticket Detail
				async function loadTicketDetail(uuid) {
					currentTicketUuid = uuid;
					showView(viewDetail);
					const msgContainer = document.getElementById("dctc-portal-detail-messages");
					if (!msgContainer) return;
					msgContainer.innerHTML = "<div>Loading conversation...</div>";

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

							msgContainer.innerHTML = msgHtml || "<div>No messages yet.</div>";
							msgContainer.scrollTop = msgContainer.scrollHeight;
						}
					} catch (err) {
						msgContainer.innerHTML = "<div style=\"color:#DC2626;\">Error loading ticket details.</div>";
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
						const msgInput = document.getElementById("dctc-new-message");

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

						const payload = {
							subject: subjectInput ? subjectInput.value : "",
							category_id: catInput ? Number(catInput.value) : 0,
							tags: tagsList,
							initial_message: msgInput ? msgInput.value : "",
							customer_name: nameInput ? nameInput.value : root.getAttribute("data-user-name"),
							customer_email: emailInput ? emailInput.value : root.getAttribute("data-user-email"),
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
								submitBtn.textContent = "Submit Support Request";
							}
						}
					});
				}

				// Reply to Ticket
				if (replyForm) {
					replyForm.addEventListener("submit", async function(e) {
						e.preventDefault();
						if (!currentTicketUuid) return;

						const replyInput = document.getElementById("dctc-portal-reply-text");
						const replyBtn = document.getElementById("dctc-portal-send-reply-btn");
						const msgText = replyInput ? replyInput.value.trim() : "";
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
								if (replyInput) replyInput.value = "";
								loadTicketDetail(currentTicketUuid);
							} else {
								alert(data.message || "Could not send reply.");
							}
						} catch (err) {
							alert("Error sending reply.");
						} finally {
							if (replyBtn) {
								replyBtn.disabled = false;
								replyBtn.textContent = "Send Reply";
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
						searchTimeout = setTimeout(loadTickets, 300);
					});
				}

				// Initial load
				loadTickets();
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
	 * Get portal CSS stylesheet string.
	 *
	 * @return string CSS rules.
	 */
	public static function get_portal_css() {
		return '
			.dctc-portal-root {
				background: #ffffff;
				border: 1px solid #E5E7EB;
				border-radius: 12px;
				box-shadow: 0 4px 12px rgba(0,0,0,0.04);
				font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
				margin: 20px 0;
				overflow: hidden;
				padding: 0;
				position: relative;
			}
			.dctc-portal-header {
				align-items: center;
				background: #F9FAFB;
				border-bottom: 1px solid #E5E7EB;
				display: flex;
				flex-wrap: wrap;
				gap: 16px;
				justify-content: space-between;
				padding: 20px 24px;
			}
			.dctc-portal-header-left h2 {
				color: #111827;
				font-size: 20px;
				font-weight: 700;
				margin: 0 0 4px !important;
			}
			.dctc-portal-header-left p {
				color: #6B7280;
				font-size: 13px;
				margin: 0 !important;
			}
			.dctc-portal-btn-primary {
				align-items: center;
				background: #4F46E5;
				border: 1px solid #4338CA;
				border-radius: 8px;
				box-shadow: 0 1px 2px rgba(79, 70, 229, 0.2);
				color: #fff !important;
				cursor: pointer;
				display: inline-flex;
				font-size: 13px;
				font-weight: 600;
				gap: 6px;
				padding: 9px 18px;
				text-decoration: none !important;
				transition: all 0.15s ease-in-out;
			}
			.dctc-portal-btn-primary:hover {
				background: #4338CA;
				box-shadow: 0 4px 8px rgba(79, 70, 229, 0.3);
			}
			.dctc-portal-btn-secondary {
				align-items: center;
				background: #fff;
				border: 1px solid #D1D5DB;
				border-radius: 8px;
				color: #374151 !important;
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
				background: #F3F4F6;
				border-color: #9CA3AF;
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
				background: #F8FAFC;
				border: 1px solid #CBD5E1;
				border-radius: 8px;
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
				background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'%2364748b\' stroke-width=\'2\' stroke-linecap=\'round\' stroke-linejoin=\'round\'%3e%3cpolyline points=\'6 9 12 15 18 9\'%3e%3c/polyline%3e%3c/svg%3e");
				background-position: right 12px center;
				background-repeat: no-repeat;
				background-size: 16px 16px;
				padding-right: 36px;
			}
			.dctc-portal-input:focus, .dctc-portal-select:focus, .dctc-portal-textarea:focus {
				background: #FFFFFF;
				border-color: #4F46E5;
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
				background: #F9FAFB;
				border: 1px solid #E5E7EB;
				border-radius: 8px;
				cursor: pointer;
				display: flex;
				justify-content: space-between;
				padding: 14px 18px;
				transition: all 0.15s;
			}
			.dctc-portal-ticket-card:hover {
				background: #F3F4F6;
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
				color: #4F46E5;
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
				border-radius: 16px;
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
				background: #ffffff;
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
				background: linear-gradient(135deg, #4F46E5 0%, #6366F1 100%);
				border-radius: 10px;
				box-shadow: 0 4px 10px rgba(79, 70, 229, 0.25);
				color: #ffffff;
				display: flex;
				flex-shrink: 0;
				height: 40px;
				justify-content: center;
				width: 40px;
			}
			.dctc-portal-modal-title {
				color: #0F172A;
				font-size: 17px;
				font-weight: 700;
				margin: 0 0 2px 0 !important;
			}
			.dctc-portal-modal-desc {
				color: #64748B;
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
				color: #4F46E5;
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
		';
	}
}
