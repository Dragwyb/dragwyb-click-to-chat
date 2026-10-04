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
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'maybe_enqueue_portal_assets' ) );
	}

	/**
	 * Enqueue frontend scripts and styles when shortcode or page is present.
	 */
	public static function maybe_enqueue_portal_assets() {
		// Assets are inlined with the shortcode output for maximum theme compatibility and zero external script delays.
	}

	/**
	 * Render the Customer Support Portal shortcode: [dragwyb_support]
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public static function render_portal_shortcode( $atts = array() ) {
		$user_id   = get_current_user_id();
		$user      = $user_id ? get_userdata( $user_id ) : null;
		$user_name = $user ? $user->display_name : '';
		$email     = $user ? $user->user_email : '';

		$categories = DCTC_Support_Category_Service::get_categories( array( 'status' => 'active' ) );
		$products   = array();
		if ( post_type_exists( 'product' ) ) {
			$wc_products = get_posts( array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'orderby'        => 'title',
				'order'          => 'ASC',
			) );
			if ( ! empty( $wc_products ) && is_array( $wc_products ) ) {
				foreach ( $wc_products as $prod ) {
					$products[] = array(
						'id'    => $prod->ID,
						'title' => $prod->post_title,
					);
				}
			}
		}

		$rest_url   = esc_url_raw( rest_url( 'dctc-ai/v1/support/portal' ) );
		$nonce      = wp_create_nonce( 'wp_rest' );

		ob_start();
		?>
		<div id="dctc-support-portal" class="dctc-portal-root" data-rest-url="<?php echo esc_attr( $rest_url ); ?>" data-nonce="<?php echo esc_attr( $nonce ); ?>" data-user-logged-in="<?php echo $user_id ? '1' : '0'; ?>" data-user-name="<?php echo esc_attr( $user_name ); ?>" data-user-email="<?php echo esc_attr( $email ); ?>">
			
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
				<div class="dctc-portal-filter-row">
					<input type="text" id="dctc-portal-search-input" placeholder="<?php esc_attr_e( 'Search your tickets by subject or number...', 'dragwyb-click-to-chat' ); ?>" class="dctc-portal-input" />
				</div>
				<div id="dctc-portal-tickets-container" class="dctc-portal-tickets-list">
					<div class="dctc-portal-loading"><?php esc_html_e( 'Loading support tickets...', 'dragwyb-click-to-chat' ); ?></div>
				</div>
			</div>

			<!-- View 2: New Ticket Form -->
			<div id="dctc-portal-view-new" class="dctc-portal-view">
				<form id="dctc-portal-new-ticket-form" class="dctc-portal-form">
					<h3><?php esc_html_e( 'Create a New Support Request', 'dragwyb-click-to-chat' ); ?></h3>

					<?php if ( ! $user_id ) : ?>
						<div class="dctc-form-grid-2">
							<div class="dctc-form-group">
								<label for="dctc-new-name"><?php esc_html_e( 'Your Name *', 'dragwyb-click-to-chat' ); ?></label>
								<input type="text" id="dctc-new-name" required class="dctc-portal-input" placeholder="e.g. Jane Doe" />
							</div>
							<div class="dctc-form-group">
								<label for="dctc-new-email"><?php esc_html_e( 'Your Email *', 'dragwyb-click-to-chat' ); ?></label>
								<input type="email" id="dctc-new-email" required class="dctc-portal-input" placeholder="e.g. jane@example.com" />
							</div>
						</div>
					<?php endif; ?>

					<div class="dctc-form-grid-2">
						<div class="dctc-form-group">
							<label for="dctc-new-category"><?php esc_html_e( 'Category', 'dragwyb-click-to-chat' ); ?></label>
							<select id="dctc-new-category" class="dctc-portal-select">
								<option value="0"><?php esc_html_e( 'General Inquiry', 'dragwyb-click-to-chat' ); ?></option>
								<?php foreach ( $categories as $cat ) : ?>
									<option value="<?php echo esc_attr( $cat['id'] ); ?>"><?php echo esc_html( $cat['name'] ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="dctc-form-group">
							<label for="dctc-new-product"><?php esc_html_e( 'Related Product / Tag', 'dragwyb-click-to-chat' ); ?></label>
							<?php if ( ! empty( $products ) ) : ?>
								<select id="dctc-new-product" class="dctc-portal-select">
									<option value=""><?php esc_html_e( 'Select Product (Optional)', 'dragwyb-click-to-chat' ); ?></option>
									<?php foreach ( $products as $prod ) : ?>
										<option value="<?php echo esc_attr( $prod['title'] ); ?>"><?php echo esc_html( $prod['title'] ); ?></option>
									<?php endforeach; ?>
								</select>
							<?php else : ?>
								<input type="text" id="dctc-new-product" class="dctc-portal-input" placeholder="<?php esc_attr_e( 'e.g. Product Name or Topic', 'dragwyb-click-to-chat' ); ?>" />
							<?php endif; ?>
						</div>
					</div>

					<div class="dctc-form-group">
						<label for="dctc-new-subject"><?php esc_html_e( 'Subject *', 'dragwyb-click-to-chat' ); ?></label>
						<input type="text" id="dctc-new-subject" required class="dctc-portal-input" placeholder="<?php esc_attr_e( 'Brief summary of what you need help with', 'dragwyb-click-to-chat' ); ?>" />
					</div>

					<div class="dctc-form-group">
						<label for="dctc-new-tags"><?php esc_html_e( 'Additional Tags (Comma separated)', 'dragwyb-click-to-chat' ); ?></label>
						<input type="text" id="dctc-new-tags" class="dctc-portal-input" placeholder="<?php esc_attr_e( 'e.g. Refund, Billing, Urgent', 'dragwyb-click-to-chat' ); ?>" />
					</div>

					<div class="dctc-form-group">
						<label for="dctc-new-message"><?php esc_html_e( 'Message Details *', 'dragwyb-click-to-chat' ); ?></label>
						<textarea id="dctc-new-message" rows="5" required class="dctc-portal-textarea" placeholder="<?php esc_attr_e( 'Please provide detailed information to help us assist you faster...', 'dragwyb-click-to-chat' ); ?>"></textarea>
					</div>

					<div class="dctc-form-actions">
						<button type="submit" id="dctc-new-submit-btn" class="dctc-portal-btn-primary">
							<?php esc_html_e( 'Submit Support Request', 'dragwyb-click-to-chat' ); ?>
						</button>
					</div>
				</form>
			</div>

			<!-- View 3: Single Ticket Conversation Detail -->
			<div id="dctc-portal-view-detail" class="dctc-portal-view">
				<div class="dctc-portal-detail-header">
					<div class="dctc-detail-header-left">
						<div class="dctc-detail-badges">
							<span id="dctc-detail-num" class="dctc-detail-ticket-num">#0000</span>
							<span id="dctc-detail-status" class="dctc-badge">Open</span>
							<span id="dctc-detail-priority" class="dctc-badge">Normal</span>
							<span id="dctc-detail-category" class="dctc-portal-badge-cat">📁 Category</span>
							<span id="dctc-detail-agent" class="dctc-portal-badge-agent">👤 Agent</span>
							<span id="dctc-detail-chats" class="dctc-portal-badge-chats">💬 0 chats</span>
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

		<style>
			/* -------------------------------------------------------------
			   Customer Support Portal Styles
			   ------------------------------------------------------------- */
			.dctc-portal-root {
				background: #ffffff;
				border: 1px solid #E5E7EB;
				border-radius: 12px;
				box-shadow: 0 4px 12px rgba(0,0,0,0.04);
				font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
				margin: 20px 0;
				overflow: hidden;
				padding: 0;
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
				border: none;
				border-radius: 8px;
				color: #fff !important;
				cursor: pointer;
				display: inline-flex;
				font-size: 13px;
				font-weight: 600;
				gap: 6px;
				padding: 9px 18px;
				text-decoration: none !important;
				transition: background 0.15s;
			}
			.dctc-portal-btn-primary:hover {
				background: #4338CA;
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
				transition: all 0.15s;
			}
			.dctc-portal-btn-secondary:hover {
				background: #F3F4F6;
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
				border: 1px solid #D1D5DB;
				border-radius: 8px;
				box-sizing: border-box;
				font-family: inherit;
				font-size: 13.5px;
				padding: 10px 12px;
				width: 100%;
			}
			.dctc-portal-input:focus, .dctc-portal-select:focus, .dctc-portal-textarea:focus {
				border-color: #4F46E5;
				box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
				outline: none;
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
				color: #374151;
				font-size: 13px;
				font-weight: 600;
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
		</style>

		<script>
		(function() {
			const root = document.getElementById('dctc-support-portal');
			if (!root) return;

			const restUrl = root.getAttribute('data-rest-url');
			const nonce = root.getAttribute('data-nonce');
			const isLoggedIn = root.getAttribute('data-user-logged-in') === '1';

			const viewList = document.getElementById('dctc-portal-view-list');
			const viewNew = document.getElementById('dctc-portal-view-new');
			const viewDetail = document.getElementById('dctc-portal-view-detail');

			const btnNew = document.getElementById('dctc-portal-btn-new');
			const btnMyTickets = document.getElementById('dctc-portal-btn-my-tickets');
			const ticketsContainer = document.getElementById('dctc-portal-tickets-container');
			const searchInput = document.getElementById('dctc-portal-search-input');

			const newForm = document.getElementById('dctc-portal-new-ticket-form');
			const replyForm = document.getElementById('dctc-portal-reply-form');
			const closeBtn = document.getElementById('dctc-detail-close-btn');

			let currentTicketUuid = null;
			let guestToken = localStorage.getItem('dctc_guest_token') || '';

			function showView(view) {
				[viewList, viewNew, viewDetail].forEach(v => v.classList.remove('active'));
				view.classList.add('active');

				if (view === viewList) {
					btnNew.style.display = 'inline-flex';
					btnMyTickets.style.display = 'none';
				} else {
					btnNew.style.display = 'none';
					btnMyTickets.style.display = 'inline-flex';
				}
			}

			btnNew.addEventListener('click', function() {
				showView(viewNew);
			});

			btnMyTickets.addEventListener('click', function() {
				showView(viewList);
				loadTickets();
			});

			// Load Tickets List
			async function loadTickets() {
				ticketsContainer.innerHTML = '<div class="dctc-portal-loading">Loading support tickets...</div>';
				try {
					const headers = { 'X-WP-Nonce': nonce };
					if (guestToken) headers['X-Guest-Token'] = guestToken;

					const q = searchInput.value.trim();
					const url = restUrl + '/tickets' + (q ? '?search=' + encodeURIComponent(q) : '');
					const res = await fetch(url, { headers: headers });
					const data = await res.json();

					if (data.success && data.tickets && data.tickets.length > 0) {
						let html = '';
						data.tickets.forEach(function(t) {
							const statusBadge = t.status === 'open' ? 'dctc-badge-open' : (t.status === 'resolved' ? 'dctc-badge-resolved' : 'dctc-badge-closed');
							const categoryName = t.category_name || 'General';
							const chatCount = t.chat_count !== undefined ? t.chat_count : (t.message_count || 1);
							const agentName = t.agent_name || 'Assigned Agent';
							const tags = Array.isArray(t.tags) ? t.tags : [];

							html += '<div class="dctc-portal-ticket-card" data-uuid="' + t.uuid + '">';
							html += '  <div class="dctc-portal-card-left" style="flex:1;">';
							html += '    <div class="dctc-portal-card-top">';
							html += '      <span class="dctc-portal-card-num">#' + t.ticket_number + '</span>';
							html += '      <span class="dctc-badge ' + statusBadge + '">' + t.status + '</span>';
							html += '      <span class="dctc-portal-badge-cat">📁 ' + categoryName + '</span>';
							html += '      <span class="dctc-portal-badge-agent">👤 ' + agentName + '</span>';
							html += '      <span class="dctc-portal-badge-chats">💬 ' + chatCount + ' ' + (chatCount === 1 ? 'chat' : 'chats') + '</span>';
							html += '    </div>';
							html += '    <h4 class="dctc-portal-card-title">' + (t.subject || 'Support Ticket') + '</h4>';
							if (tags.length > 0) {
								html += '    <div class="dctc-portal-card-badges-row">';
								tags.forEach(function(tag) {
									html += '      <span class="dctc-portal-badge-tag">🏷️ ' + tag + '</span>';
								});
								html += '    </div>';
							}
							html += '  </div>';
							html += '  <span class="dctc-portal-card-date">' + (t.created_at ? t.created_at.split(' ')[0] : '') + '</span>';
							html += '</div>';
						});
						ticketsContainer.innerHTML = html;

						// Add click handlers
						document.querySelectorAll('.dctc-portal-ticket-card').forEach(function(card) {
							card.addEventListener('click', function() {
								const uuid = this.getAttribute('data-uuid');
								loadTicketDetail(uuid);
							});
						});
					} else {
						ticketsContainer.innerHTML = '<div style="text-align:center;padding:30px 0;color:#6B7280;">No support requests found. Click "New Support Request" to start one.</div>';
					}
				} catch (err) {
					ticketsContainer.innerHTML = '<div style="color:#DC2626;">Error loading support tickets. Please try again.</div>';
				}
			}

			// Load Single Ticket Detail
			async function loadTicketDetail(uuid) {
				currentTicketUuid = uuid;
				showView(viewDetail);
				const msgContainer = document.getElementById('dctc-portal-detail-messages');
				msgContainer.innerHTML = '<div>Loading conversation...</div>';

				try {
					const headers = { 'X-WP-Nonce': nonce };
					if (guestToken) headers['X-Guest-Token'] = guestToken;

					const res = await fetch(restUrl + '/tickets/' + uuid, { headers: headers });
					const data = await res.json();

					if (data.success && data.ticket) {
						const t = data.ticket;
						document.getElementById('dctc-detail-num').textContent = '#' + t.ticket_number;
						document.getElementById('dctc-detail-subject').textContent = t.subject;
						document.getElementById('dctc-detail-status').textContent = t.status;
						document.getElementById('dctc-detail-priority').textContent = t.priority;
						
						const catElem = document.getElementById('dctc-detail-category');
						if (catElem) catElem.textContent = '📁 ' + (t.category_name || 'General');
						
						const agentElem = document.getElementById('dctc-detail-agent');
						if (agentElem) agentElem.textContent = '👤 ' + (t.agent_name || 'Support Staff');

						const chatsElem = document.getElementById('dctc-detail-chats');
						const count = t.chat_count !== undefined ? t.chat_count : (t.messages ? t.messages.length : 0);
						if (chatsElem) chatsElem.textContent = '💬 ' + count + ' ' + (count === 1 ? 'chat' : 'chats');

						const tagsRow = document.getElementById('dctc-detail-tags-row');
						if (tagsRow) {
							const tags = Array.isArray(t.tags) ? t.tags : [];
							tagsRow.innerHTML = tags.map(function(tag) {
								return '<span class="dctc-portal-badge-tag">🏷️ ' + tag + '</span>';
							}).join('');
						}

						let msgHtml = '';
						(t.messages || []).forEach(function(m) {
							const isCustomer = m.sender_type === 'customer' || m.role === 'user';
							const senderLabel = isCustomer ? '👤 You' : (m.sender_name ? '🧑‍💼 ' + m.sender_name : '🧑‍💼 Support Team');
							msgHtml += '<div class="dctc-portal-msg ' + (isCustomer ? 'dctc-portal-msg-customer' : 'dctc-portal-msg-agent') + '">';
							msgHtml += '  <div class="dctc-portal-msg-header">';
							msgHtml += '    <span>' + senderLabel + '</span>';
							msgHtml += '    <span class="dctc-portal-msg-time">' + (m.created_at || '') + '</span>';
							msgHtml += '  </div>';
							msgHtml += '  <div class="dctc-portal-msg-body">' + m.content + '</div>';
							msgHtml += '</div>';
						});

						msgContainer.innerHTML = msgHtml || '<div>No messages yet.</div>';
						msgContainer.scrollTop = msgContainer.scrollHeight;
					}
				} catch (err) {
					msgContainer.innerHTML = '<div style="color:#DC2626;">Error loading ticket details.</div>';
				}
			}

			// Submit New Ticket
			newForm.addEventListener('submit', async function(e) {
				e.preventDefault();
				const submitBtn = document.getElementById('dctc-new-submit-btn');
				submitBtn.disabled = true;
				submitBtn.textContent = 'Submitting...';

				const nameInput = document.getElementById('dctc-new-name');
				const emailInput = document.getElementById('dctc-new-email');
				const catInput = document.getElementById('dctc-new-category');
				const prodInput = document.getElementById('dctc-new-product');
				const tagsInput = document.getElementById('dctc-new-tags');
				const subjectInput = document.getElementById('dctc-new-subject');
				const msgInput = document.getElementById('dctc-new-message');

				// Collect tags
				const tagsList = [];
				if (prodInput && prodInput.value.trim()) {
					tagsList.push(prodInput.value.trim());
				}
				if (tagsInput && tagsInput.value.trim()) {
					tagsInput.value.split(',').forEach(function(item) {
						const cleaned = item.trim();
						if (cleaned && !tagsList.includes(cleaned)) {
							tagsList.push(cleaned);
						}
					});
				}

				const payload = {
					subject: subjectInput.value,
					category_id: catInput ? Number(catInput.value) : 0,
					tags: tagsList,
					initial_message: msgInput.value,
					customer_name: nameInput ? nameInput.value : root.getAttribute('data-user-name'),
					customer_email: emailInput ? emailInput.value : root.getAttribute('data-user-email'),
				};

				try {
					const headers = {
						'Content-Type': 'application/json',
						'X-WP-Nonce': nonce
					};
					if (guestToken) headers['X-Guest-Token'] = guestToken;

					const res = await fetch(restUrl + '/tickets', {
						method: 'POST',
						headers: headers,
						body: JSON.stringify(payload)
					});
					const data = await res.json();

					if (data.success && data.ticket) {
						if (data.ticket.guest_access_token) {
							guestToken = data.ticket.guest_access_token;
							localStorage.setItem('dctc_guest_token', guestToken);
						}
						newForm.reset();
						loadTicketDetail(data.ticket.uuid);
					} else {
						alert(data.message || 'Could not create ticket.');
					}
				} catch (err) {
					alert('Error connecting to support server.');
				} finally {
					submitBtn.disabled = false;
					submitBtn.textContent = 'Submit Support Request';
				}
			});

			// Reply to Ticket
			replyForm.addEventListener('submit', async function(e) {
				e.preventDefault();
				if (!currentTicketUuid) return;

				const replyInput = document.getElementById('dctc-portal-reply-text');
				const replyBtn = document.getElementById('dctc-portal-send-reply-btn');
				const msgText = replyInput.value.trim();
				if (!msgText) return;

				replyBtn.disabled = true;
				replyBtn.textContent = 'Sending...';

				try {
					const headers = {
						'Content-Type': 'application/json',
						'X-WP-Nonce': nonce
					};
					if (guestToken) headers['X-Guest-Token'] = guestToken;

					const res = await fetch(restUrl + '/tickets/' + currentTicketUuid + '/reply', {
						method: 'POST',
						headers: headers,
						body: JSON.stringify({ message: msgText })
					});
					const data = await res.json();

					if (data.success) {
						replyInput.value = '';
						loadTicketDetail(currentTicketUuid);
					} else {
						alert(data.message || 'Could not send reply.');
					}
				} catch (err) {
					alert('Error sending reply.');
				} finally {
					replyBtn.disabled = false;
					replyBtn.textContent = 'Send Reply';
				}
			});

			// Close Ticket
			closeBtn.addEventListener('click', async function() {
				if (!currentTicketUuid) return;
				if (!confirm('Are you sure you want to mark this ticket as closed?')) return;

				try {
					const headers = { 'X-WP-Nonce': nonce };
					if (guestToken) headers['X-Guest-Token'] = guestToken;

					const res = await fetch(restUrl + '/tickets/' + currentTicketUuid + '/close', {
						method: 'POST',
						headers: headers
					});
					const data = await res.json();
					if (data.success) {
						loadTicketDetail(currentTicketUuid);
					}
				} catch (err) {
					alert('Could not close ticket.');
				}
			});

			if (searchInput) {
				let searchTimeout;
				searchInput.addEventListener('input', function() {
					clearTimeout(searchTimeout);
					searchTimeout = setTimeout(loadTickets, 300);
				});
			}

			// Initial load
			loadTickets();
		})();
		</script>
		<?php
		return ob_get_clean();
	}
}
