import { __ } from '@wordpress/i18n';
import { StatusBadge, PriorityBadge } from '../SupportBadges';

export default function TicketListTable({
	isDatabaseEmpty,
	loading,
	filteredTickets = [],
	totalTickets = 0,
	activeFolder,
	sortBy,
	setSortBy,
	starredTickets = {},
	flaggedTickets = {},
	selectedTicketIds = [],
	onOpenTicket,
	onToggleTicketSelect,
	onResetFilters,
	currentPage = 1,
	totalPages = 1,
	setCurrentPage,
	getInitials,
	formatRelativeTime,
}) {
	return (
		<section className="dctc-sc-col-list fullwidth">
			{isDatabaseEmpty ? (
				<div className="dctc-sc-empty-inbox-container">
					<div className="dctc-sc-empty-inbox-icon-wrap">
						<svg viewBox="0 0 64 64" width="64" height="64" fill="none" style={{ width: '64px', height: '64px', display: 'block' }}>
							<rect width="64" height="64" rx="20" fill="url(#empty-inbox-grad)" />
							<path d="M20 24C20 21.79 21.79 20 24 20H40C42.21 20 44 21.79 44 24V40C44 42.21 42.21 44 40 44H24C21.79 44 20 42.21 20 40V24Z" stroke="#FFFFFF" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" />
							<path d="M20 26L32 34L44 26" stroke="#FFFFFF" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" />
							<circle cx="45" cy="19" r="4" fill="#10B981" stroke="#FFFFFF" strokeWidth="2" />
							<defs>
								<linearGradient id="empty-inbox-grad" x1="0" y1="0" x2="64" y2="64" gradientUnits="userSpaceOnUse">
									<stop stopColor="#6366F1" />
									<stop offset="1" stopColor="#4338CA" />
								</linearGradient>
							</defs>
						</svg>
					</div>

					<h2 className="dctc-sc-empty-inbox-title">
						{__('No Support Tickets Created Yet', 'dragwyb-click-to-chat')}
					</h2>
					<p className="dctc-sc-empty-inbox-desc">
						{__('Your support ticketing workspace is active and ready. Tickets will appear here automatically when visitors request agent support from your chat widget, submit inquiries via your frontend portal, or when staff logs a ticket.', 'dragwyb-click-to-chat')}
					</p>

					<div className="dctc-sc-empty-inbox-features">
						<div className="dctc-sc-empty-feature-card">
							<div className="dctc-sc-empty-feature-icon" style={{ background: '#ec4899' }}>💬</div>
							<h4>{__('Automated Ticket Generation', 'dragwyb-click-to-chat')}</h4>
							<p>{__('Tickets are generated automatically from your AI Assistant and chat channels whenever visitors report issues, submit product or lead inquiries, or request human support.', 'dragwyb-click-to-chat')}</p>
						</div>
						<div className="dctc-sc-empty-feature-card">
							<div className="dctc-sc-empty-feature-icon" style={{ background: '#6366f1' }}>🌐</div>
							<h4>{__('Customer Helpdesk Portal', 'dragwyb-click-to-chat')}</h4>
							<p>{__('Customers can submit tickets and view replies directly through your website frontend portal.', 'dragwyb-click-to-chat')}</p>
						</div>
						<div className="dctc-sc-empty-feature-card">
							<div className="dctc-sc-empty-feature-icon" style={{ background: '#10b981' }}>⚡</div>
							<h4>{__('Manual Ticket Creation', 'dragwyb-click-to-chat')}</h4>
							<p>{__('Agents can create tickets instantly for phone inquiries, emails, or offline requests.', 'dragwyb-click-to-chat')}</p>
						</div>
					</div>

					<div className="dctc-sc-empty-inbox-actions">
						<a
							href="admin.php?page=dragwyb-click-to-chat-guide&tab=support"
							className="dctc-sc-reset-filters-btn"
							style={{ padding: '12px 28px', fontSize: '14.5px', textDecoration: 'none', background: '#f8fafc', display: 'inline-flex', alignItems: 'center', gap: '8px', borderRadius: '8px', border: '1px solid #e2e8f0', color: '#4f46e5', fontWeight: 600 }}
						>
							<span className="dashicons dashicons-book" style={{ fontSize: '18px', width: '18px', height: '18px' }}></span>
							<span>{__('View Support Portal Setup Guide', 'dragwyb-click-to-chat')}</span>
						</a>
					</div>
				</div>
			) : (
				<>
					<div className="dctc-sc-list-header">
						<div className="dctc-sc-list-header-left">
							<h3 className="dctc-sc-list-title">
								{activeFolder ? activeFolder.charAt(0).toUpperCase() + activeFolder.slice(1) + ' Tickets' : 'Filtered Views'}
							</h3>
							<span className="dctc-sc-list-subtitle">
								{totalTickets || filteredTickets.length} {__('tickets found', 'dragwyb-click-to-chat')}
							</span>
						</div>
						<div className="dctc-sc-list-sort">
							<select
								value={sortBy}
								onChange={(e) => setSortBy(e.target.value)}
							>
								<option value="newest">{__('Newest First', 'dragwyb-click-to-chat')}</option>
								<option value="oldest">{__('Oldest First', 'dragwyb-click-to-chat')}</option>
								<option value="priority">{__('Priority High-Low', 'dragwyb-click-to-chat')}</option>
							</select>
						</div>
					</div>

					<div className="dctc-sc-ticket-grid-fullwidth">
						{loading ? (
							<div className="dctc-sc-loading-state">
								<span className="spinner is-active"></span>
								{__('Loading tickets...', 'dragwyb-click-to-chat')}
							</div>
						) : filteredTickets.length === 0 ? (
							<div className="dctc-sc-filter-empty-state">
								<div className="dctc-sc-filter-empty-icon">
									<span className="dashicons dashicons-filter"></span>
								</div>
								<h3>{__('No Support Tickets Match Your Filters', 'dragwyb-click-to-chat')}</h3>
								<p>{__('We couldn\'t find any tickets matching your selected criteria. Try adjusting your search keywords or resetting filters.', 'dragwyb-click-to-chat')}</p>
								<button
									type="button"
									className="dctc-sc-reset-filters-btn"
									onClick={onResetFilters}
								>
									<span className="dashicons dashicons-image-rotate"></span>
									{__('Reset All Filters', 'dragwyb-click-to-chat')}
								</button>
							</div>
						) : (
							filteredTickets.map((item) => {
								const initials = getInitials(item.customer_name, item.customer_email || item.session_id);
								const isStarred = starredTickets[item.id];
								const isFlagged = flaggedTickets[item.id];
								const isChecked = selectedTicketIds.includes(item.id);

								return (
									<div
										key={item.id}
										className="dctc-sc-card-fullwidth"
										onClick={() => onOpenTicket(item.id)}
									>
										<div className="dctc-sc-card-main-info">
											<div className="dctc-sc-card-top">
												<div className="dctc-sc-card-top-left">
													<input
														type="checkbox"
														checked={isChecked}
														onChange={(e) => onToggleTicketSelect(item.id, e)}
														className="dctc-sc-card-checkbox"
													/>
													<span className="dctc-sc-card-id">#{item.ticket_number || item.id}</span>
													<StatusBadge status={item.status} />
													<PriorityBadge priority={item.priority} />
												</div>
												<div className="dctc-sc-card-top-right">
													{isStarred && <span className="dashicons dashicons-star-filled" style={{ color: '#f59e0b', fontSize: '14px' }}></span>}
													{isFlagged && <span className="dashicons dashicons-flag" style={{ color: '#ef4444', fontSize: '14px' }}></span>}
												</div>
											</div>

											<div className="dctc-sc-card-subject">
												{item.subject}
											</div>

											<div className="dctc-sc-card-excerpt">
												{(item.excerpt || item.last_message || item.subject || '').replace(/<[^>]*>?/gm, '')}
											</div>
										</div>

										<div className="dctc-sc-card-meta-side">
											<div className="dctc-sc-card-customer-row">
												<div className="dctc-sc-card-customer-info">
													<div className="dctc-sc-card-avatar">
														{initials}
													</div>
													<span className="dctc-sc-card-customer-name">
														{item.customer_name || (item.session_id ? `Guest (${item.session_id.substring(0, 8)})` : 'Guest Visitor')}
													</span>
												</div>
												<span className="dctc-sc-card-time">
													{formatRelativeTime(item.created_at)}
												</span>
											</div>

											<div className="dctc-sc-card-badges-row">
												<span className="dctc-sc-pill-badge category">
													<span className="dashicons dashicons-category"></span>
													{item.category_name || __('Technical & Bugs', 'dragwyb-click-to-chat')}
												</span>
												<span className="dctc-sc-pill-badge agent">
													<span className="dashicons dashicons-admin-users"></span>
													{item.agent_name || __('Aniket Dogra', 'dragwyb-click-to-chat')}
												</span>
												<span className="dctc-sc-pill-badge chats">
													<span className="dashicons dashicons-format-chat"></span>
													{item.chat_count !== undefined ? item.chat_count : (item.message_count || 2)} {__('chats', 'dragwyb-click-to-chat')}
												</span>
											</div>
										</div>
									</div>
								);
							})
						)}
					</div>

					{totalPages > 1 && (
						<div className="dctc-sc-pagination-bar">
							<button
								type="button"
								disabled={currentPage <= 1}
								onClick={() => setCurrentPage((p) => Math.max(1, p - 1))}
								className="dctc-sc-page-nav"
							>
								&laquo; {__('Prev', 'dragwyb-click-to-chat')}
							</button>
							<span>{currentPage} / {totalPages}</span>
							<button
								type="button"
								disabled={currentPage >= totalPages}
								onClick={() => setCurrentPage((p) => Math.min(totalPages, p + 1))}
								className="dctc-sc-page-nav"
							>
								{__('Next', 'dragwyb-click-to-chat')} &raquo;
							</button>
						</div>
					)}
				</>
			)}
		</section>
	);
}
