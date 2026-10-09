import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

export default function TicketSidebar({
	selectedTicket,
	categories = [],
	products = [],
	agents = [],
	tags = [],
	wcData = null,
	activeViewers = [],
	starredTickets = {},
	flaggedTickets = {},
	toggleStar,
	toggleFlag,
	onCloseTicket,
	onRefreshTicketDetails,
	onRefreshTickets,
	onShowNotice,
	handleStatusChange,
	handlePriorityChange,
	handleAssignAgent,
	handleDeleteTicket,
	getInitials,
}) {
	const [showCustomerCard, setShowCustomerCard] = useState(true);
	const [showAiUsefulContentCard, setShowAiUsefulContentCard] = useState(false);
	const [showTicketPropertiesCard, setShowTicketPropertiesCard] = useState(false);
	const [showCommerceCard, setShowCommerceCard] = useState(false);
	const [showQuickActionsCard, setShowQuickActionsCard] = useState(false);
	const [showCustomerSession, setShowCustomerSession] = useState(false);
	const [showAdvancedProps, setShowAdvancedProps] = useState(false);

	if (!selectedTicket) return null;

	const displayViewers = activeViewers || [];

	const handleCategoryChange = async (val) => {
		if (!selectedTicket?.id) return;
		try {
			await apiFetch({
				path: `/dctc-ai/v1/support/tickets/${selectedTicket.id}`,
				method: 'PUT',
				data: { category_id: val },
			});
			onRefreshTicketDetails(selectedTicket.id);
			onRefreshTickets();
			onShowNotice(__('Category updated.', 'dragwyb-click-to-chat'), 'success');
		} catch (err) {
			console.error(err);
			onShowNotice(__('Failed to update category.', 'dragwyb-click-to-chat'), 'error');
		}
	};

	const handleProductChange = async (val) => {
		if (!selectedTicket?.id) return;
		try {
			await apiFetch({
				path: `/dctc-ai/v1/support/tickets/${selectedTicket.id}`,
				method: 'PUT',
				data: { product: val },
			});
			onRefreshTicketDetails(selectedTicket.id);
			onRefreshTickets();
			onShowNotice(__('Product updated.', 'dragwyb-click-to-chat'), 'success');
		} catch (err) {
			console.error(err);
			onShowNotice(__('Failed to update product.', 'dragwyb-click-to-chat'), 'error');
		}
	};

	const handleRemoveTag = async (tagName) => {
		const updatedTags = (selectedTicket.tags || []).filter((item) => (typeof item === 'object' ? item.name : item) !== tagName);
		try {
			await apiFetch({
				path: `/dctc-ai/v1/support/tickets/${selectedTicket.id}`,
				method: 'PUT',
				data: { tags: updatedTags },
			});
			onRefreshTicketDetails(selectedTicket.id);
			onRefreshTickets();
		} catch (err) {
			console.error(err);
		}
	};

	const handleAddTag = async (selectedTag) => {
		if (!selectedTag || !selectedTicket?.id) return;
		const currentTags = Array.isArray(selectedTicket.tags)
			? selectedTicket.tags.map((item) => (typeof item === 'object' ? item.name : item))
			: [];
		if (!currentTags.includes(selectedTag)) {
			const updatedTags = [...currentTags, selectedTag];
			try {
				await apiFetch({
					path: `/dctc-ai/v1/support/tickets/${selectedTicket.id}`,
					method: 'PUT',
					data: { tags: updatedTags },
				});
				onRefreshTicketDetails(selectedTicket.id);
				onRefreshTickets();
				onShowNotice(__('Tag added.', 'dragwyb-click-to-chat'), 'success');
			} catch (err) {
				console.error(err);
			}
		}
	};

	return (
		<aside className="dctc-sc-col-details">
			{/* TOP ACTIONS BAR: Back, Flag, Star */}
			<div className="dctc-sc-sidebar-top-bar">
				<button
					type="button"
					className="dctc-sc-sidebar-back-btn"
					onClick={onCloseTicket}
					title={__('Back to all tickets', 'dragwyb-click-to-chat')}
				>
					<span className="dashicons dashicons-arrow-left-alt"></span>
					<span>{__('Back', 'dragwyb-click-to-chat')}</span>
				</button>

				<div className="dctc-sc-sidebar-top-actions">
					{selectedTicket?.id && (
						<>
							<button
								type="button"
								className={`dctc-sc-sidebar-icon-btn ${flaggedTickets?.[selectedTicket.id] ? 'active-flag' : ''}`}
								onClick={() => toggleFlag?.(selectedTicket.id)}
								title={__('Flag Ticket', 'dragwyb-click-to-chat')}
							>
								<span className="dashicons dashicons-flag"></span>
							</button>

							<button
								type="button"
								className={`dctc-sc-sidebar-icon-btn ${starredTickets?.[selectedTicket.id] ? 'active-star' : ''}`}
								onClick={() => toggleStar?.(selectedTicket.id)}
								title={__('Star Ticket', 'dragwyb-click-to-chat')}
							>
								<span className={`dashicons ${starredTickets?.[selectedTicket.id] ? 'dashicons-star-filled' : 'dashicons-star-empty'}`}></span>
							</button>
						</>
					)}
				</div>
			</div>

			{/* CUSTOMER CARD */}
			<div className="dctc-sc-details-card">
				<button
					type="button"
					className="dctc-sc-card-head dctc-sc-card-head-toggle"
					onClick={() => setShowCustomerCard((prev) => !prev)}
					aria-expanded={showCustomerCard}
				>
					<div className="dctc-sc-card-head-title">
						<span className="dashicons dashicons-admin-users"></span>
						<h4>{__('Customer Details', 'dragwyb-click-to-chat')}</h4>
					</div>
					<span className={`dashicons ${showCustomerCard ? 'dashicons-arrow-up-alt2' : 'dashicons-arrow-down-alt2'}`}></span>
				</button>

				{showCustomerCard && (
					<div className="dctc-sc-card-body">
						<div className="dctc-sc-customer-summary">
							<div className="dctc-sc-cust-avatar-large">
								{getInitials(selectedTicket?.customer_name, selectedTicket?.customer_email || selectedTicket?.session_id)}
							</div>
							<div className="dctc-sc-cust-identity">
								<span className="dctc-sc-cust-fullname">
									{selectedTicket?.customer_name || (selectedTicket?.session_id ? `Guest (${selectedTicket.session_id.substring(0, 8)})` : 'Guest Visitor')}
								</span>
								<span className="dctc-sc-guest-tag">{__('Guest', 'dragwyb-click-to-chat')}</span>
							</div>
						</div>

						<div className="dctc-sc-customer-info-rows">
							<div className="dctc-sc-info-row">
								<span className="dashicons dashicons-email"></span>
								<span className="dctc-sc-info-text">
									{selectedTicket?.customer_email || __('Not provided (Live Chat)', 'dragwyb-click-to-chat')}
								</span>
							</div>
							{selectedTicket?.customer_phone && (
								<div className="dctc-sc-info-row">
									<span className="dashicons dashicons-phone"></span>
									<span className="dctc-sc-info-text">{selectedTicket.customer_phone}</span>
								</div>
							)}
						</div>

						{/* Expand Session & Technical Details Toggle */}
						<button
							type="button"
							className="dctc-sc-expand-toggle-btn"
							onClick={() => setShowCustomerSession((prev) => !prev)}
						>
							<span className="dashicons dashicons-admin-generic"></span>
							<span>{__('Session & Technical Info', 'dragwyb-click-to-chat')}</span>
							<span className={`dashicons ${showCustomerSession ? 'dashicons-arrow-up-alt2' : 'dashicons-arrow-down-alt2'}`}></span>
						</button>

						{showCustomerSession && (
							<div className="dctc-sc-expanded-section">
								<div className="dctc-sc-stat-item">
									<span className="stat-name">{__('First Seen', 'dragwyb-click-to-chat')}</span>
									<span className="stat-data">{selectedTicket?.created_at || 'Oct 04, 2026'}</span>
								</div>
								<div className="dctc-sc-stat-item">
									<span className="stat-name">{__('Total Chats', 'dragwyb-click-to-chat')}</span>
									<span className="stat-data">{selectedTicket?.chat_count !== undefined ? selectedTicket.chat_count : (selectedTicket?.messages?.length || 0)}</span>
								</div>
								<div className="dctc-sc-stat-item">
									<span className="stat-name">{__('Total Tickets', 'dragwyb-click-to-chat')}</span>
									<span className="stat-data">1</span>
								</div>
								<div className="dctc-sc-stat-item">
									<span className="stat-name">{__('Origin Surface', 'dragwyb-click-to-chat')}</span>
									<span className="stat-data">{selectedTicket?.reply_surface === 'chatbot_widget' ? 'Chatbot Widget' : 'Support Portal'}</span>
								</div>
							</div>
						)}
					</div>
				)}
			</div>

			{/* AI Insights CARD */}
			{((selectedTicket?.ai_useful_content && selectedTicket.ai_useful_content.has_ai_content) || selectedTicket?.meta?.lead_id || selectedTicket?.meta?.budget || selectedTicket?.meta?.requirement || selectedTicket?.meta?.company || selectedTicket?.customer_phone) && (
				<div className="dctc-sc-details-card dctc-sc-ai-useful-card">
					<button
						type="button"
						className="dctc-sc-card-head dctc-sc-card-head-toggle"
						onClick={() => setShowAiUsefulContentCard((prev) => !prev)}
						aria-expanded={showAiUsefulContentCard}
					>
						<div className="dctc-sc-card-head-title">
							<span className="dashicons dashicons-format-chat" style={{ color: '#0ea5e9' }}></span>
							<h4>{__('AI Insights', 'dragwyb-click-to-chat')}</h4>
						</div>
						<div style={{ display: 'flex', alignItems: 'center', gap: '6px' }}>
							{selectedTicket?.ai_useful_content?.lead_score > 0 && (
								<span className="dctc-sc-badge dctc-sc-badge-resolved" style={{ fontSize: '10px', padding: '2px 6px', background: '#ecfdf5', color: '#059669', border: '1px solid #a7f3d0' }}>
									{`Score: ${selectedTicket.ai_useful_content.lead_score}/100`}
								</span>
							)}
							<span className={`dashicons ${showAiUsefulContentCard ? 'dashicons-arrow-up-alt2' : 'dashicons-arrow-down-alt2'}`}></span>
						</div>
					</button>

					{showAiUsefulContentCard && (
						<div className="dctc-sc-card-body dctc-sc-ai-useful-body">
							<div className="dctc-sc-ai-fields-grid">
								{(selectedTicket?.customer_email || selectedTicket?.ai_useful_content?.customer_email) && (
									<div className="dctc-sc-ai-field-item">
										<span className="dctc-sc-ai-field-label">{__('Email', 'dragwyb-click-to-chat')}</span>
										<span className="dctc-sc-ai-field-val email">
											<a href={`mailto:${selectedTicket?.customer_email || selectedTicket?.ai_useful_content?.customer_email}`}>
												{selectedTicket?.customer_email || selectedTicket?.ai_useful_content?.customer_email}
											</a>
										</span>
									</div>
								)}

								{(selectedTicket?.customer_phone || selectedTicket?.ai_useful_content?.customer_phone) && (
									<div className="dctc-sc-ai-field-item">
										<span className="dctc-sc-ai-field-label">{__('Phone Number', 'dragwyb-click-to-chat')}</span>
										<span className="dctc-sc-ai-field-val phone">
											<a href={`tel:${selectedTicket?.customer_phone || selectedTicket?.ai_useful_content?.customer_phone}`}>
												{selectedTicket?.customer_phone || selectedTicket?.ai_useful_content?.customer_phone}
											</a>
										</span>
									</div>
								)}

								{selectedTicket?.ai_useful_content?.company && (
									<div className="dctc-sc-ai-field-item">
										<span className="dctc-sc-ai-field-label">{__('Company', 'dragwyb-click-to-chat')}</span>
										<span className="dctc-sc-ai-field-val">{selectedTicket.ai_useful_content.company}</span>
									</div>
								)}

								{selectedTicket?.ai_useful_content?.company_size && (
									<div className="dctc-sc-ai-field-item">
										<span className="dctc-sc-ai-field-label">{__('Company Size', 'dragwyb-click-to-chat')}</span>
										<span className="dctc-sc-ai-field-val">{selectedTicket.ai_useful_content.company_size}</span>
									</div>
								)}

								{selectedTicket?.ai_useful_content?.budget && (
									<div className="dctc-sc-ai-field-item">
										<span className="dctc-sc-ai-field-label">{__('Budget', 'dragwyb-click-to-chat')}</span>
										<span className="dctc-sc-ai-field-val highlight">{selectedTicket.ai_useful_content.budget}</span>
									</div>
								)}

								{selectedTicket?.ai_useful_content?.timeline && (
									<div className="dctc-sc-ai-field-item">
										<span className="dctc-sc-ai-field-label">{__('Timeline', 'dragwyb-click-to-chat')}</span>
										<span className="dctc-sc-ai-field-val">{selectedTicket.ai_useful_content.timeline}</span>
									</div>
								)}

								{selectedTicket?.ai_useful_content?.interest && (
									<div className="dctc-sc-ai-field-item">
										<span className="dctc-sc-ai-field-label">{__('Product Interest', 'dragwyb-click-to-chat')}</span>
										<span className="dctc-sc-ai-field-val">{selectedTicket.ai_useful_content.interest}</span>
									</div>
								)}
							</div>

							{/* Requirement / Query Note */}
							{selectedTicket?.ai_useful_content?.requirement && (
								<div className="dctc-sc-ai-requirement-box">
									<span className="dctc-sc-ai-field-label">{__('Requirement / Notes', 'dragwyb-click-to-chat')}</span>
									<p className="dctc-sc-ai-requirement-text">{selectedTicket.ai_useful_content.requirement}</p>
								</div>
							)}

							{/* Related WooCommerce Product */}
							{selectedTicket?.ai_useful_content?.wc_product_info && (
								<div className="dctc-sc-ai-wc-product-card">
									<span className="dctc-sc-ai-field-label">{__('Related WooCommerce Product', 'dragwyb-click-to-chat')}</span>
									<div className="dctc-sc-ai-wc-row">
										{selectedTicket.ai_useful_content.wc_product_info.image_url && (
											<img
												src={selectedTicket.ai_useful_content.wc_product_info.image_url}
												alt={selectedTicket.ai_useful_content.wc_product_info.name}
												className="dctc-sc-ai-wc-thumb"
											/>
										)}
										<div className="dctc-sc-ai-wc-details">
											<a
												href={selectedTicket.ai_useful_content.wc_product_info.edit_url || selectedTicket.ai_useful_content.wc_product_info.permalink}
												target="_blank"
												rel="noreferrer"
												className="dctc-sc-ai-wc-title"
											>
												{selectedTicket.ai_useful_content.wc_product_info.name}
											</a>
											<div className="dctc-sc-ai-wc-meta">
												<span className="dctc-sc-ai-wc-price" dangerouslySetInnerHTML={{ __html: selectedTicket.ai_useful_content.wc_product_info.price_html || `$${selectedTicket.ai_useful_content.wc_product_info.price}` }}></span>
												{selectedTicket.ai_useful_content.wc_product_info.sku && selectedTicket.ai_useful_content.wc_product_info.sku !== 'N/A' && (
													<span className="dctc-sc-ai-wc-sku">SKU: {selectedTicket.ai_useful_content.wc_product_info.sku}</span>
												)}
											</div>
										</div>
									</div>
								</div>
							)}
						</div>
					)}
				</div>
			)}

			{/* TICKET PROPERTIES CARD */}
			<div className="dctc-sc-details-card">
				<button
					type="button"
					className="dctc-sc-card-head dctc-sc-card-head-toggle"
					onClick={() => setShowTicketPropertiesCard((prev) => !prev)}
					aria-expanded={showTicketPropertiesCard}
				>
					<div className="dctc-sc-card-head-title">
						<span className="dashicons dashicons-clipboard"></span>
						<h4>{__('Ticket Properties', 'dragwyb-click-to-chat')}</h4>
					</div>
					<span className={`dashicons ${showTicketPropertiesCard ? 'dashicons-arrow-up-alt2' : 'dashicons-arrow-down-alt2'}`}></span>
				</button>

				{showTicketPropertiesCard && (
					<div className="dctc-sc-card-body dctc-sc-ticket-fields">
						{/* Daily Vital 1: Status */}
						<div className="dctc-sc-field-row">
							<label>{__('Status', 'dragwyb-click-to-chat')}</label>
							<select
								value={selectedTicket?.status || 'open'}
								onChange={(e) => handleStatusChange(e.target.value)}
								className="dctc-sc-detail-select"
							>
								<option value="open">Open</option>
								<option value="pending">Pending</option>
								<option value="waiting_customer">Waiting Customer</option>
								<option value="resolved">Resolved</option>
								<option value="closed">Closed</option>
							</select>
						</div>

						{/* Daily Vital 2: Priority */}
						<div className="dctc-sc-field-row">
							<label>{__('Priority', 'dragwyb-click-to-chat')}</label>
							<select
								value={selectedTicket?.priority || 'normal'}
								onChange={(e) => handlePriorityChange(e.target.value)}
								className="dctc-sc-detail-select"
							>
								<option value="low">Low</option>
								<option value="normal">Normal</option>
								<option value="high">High</option>
								<option value="urgent">Urgent</option>
							</select>
						</div>

						{/* Daily Vital 3: Assigned To */}
						<div className="dctc-sc-field-row">
							<label>{__('Assigned To', 'dragwyb-click-to-chat')}</label>
							<select
								value={selectedTicket?.assigned_agent_id || 0}
								onChange={(e) => handleAssignAgent(Number(e.target.value))}
								className="dctc-sc-detail-select"
							>
								<option value="0">{__('Unassigned', 'dragwyb-click-to-chat')}</option>
								{agents.map((ag) => (
									<option key={ag.id} value={ag.id}>
										{ag.display_name}
									</option>
								))}
							</select>
						</div>

						{/* Expand Secondary Properties Toggle */}
						<button
							type="button"
							className="dctc-sc-expand-toggle-btn"
							onClick={() => setShowAdvancedProps((prev) => !prev)}
						>
							<span className="dashicons dashicons-category"></span>
							<span>{__('More Properties', 'dragwyb-click-to-chat')}</span>
							<span className={`dashicons ${showAdvancedProps ? 'dashicons-arrow-up-alt2' : 'dashicons-arrow-down-alt2'}`} style={{ fontSize: '11px', width: '11px', height: '11px' }}></span>
						</button>

						{showAdvancedProps && (
							<div className="dctc-sc-expanded-section">
								{/* Category */}
								<div className="dctc-sc-field-row">
									<label>{__('Category', 'dragwyb-click-to-chat')}</label>
									<select
										value={selectedTicket?.category_id || 0}
										onChange={(e) => handleCategoryChange(Number(e.target.value))}
										className="dctc-sc-detail-select"
									>
										<option value="0">{__('-- Select Category --', 'dragwyb-click-to-chat')}</option>
										{categories.map((cat) => (
											<option key={cat.id} value={cat.id}>{cat.name}</option>
										))}
									</select>
								</div>

								{/* Product */}
								<div className="dctc-sc-field-row">
									<label>{__('Product', 'dragwyb-click-to-chat')}</label>
									<select
										value={selectedTicket?.product || ''}
										onChange={(e) => handleProductChange(e.target.value)}
										className="dctc-sc-detail-select"
									>
										<option value="">{__('-- Select Product (Optional) --', 'dragwyb-click-to-chat')}</option>
										{products.map((prod) => (
											<option key={prod.id || prod.name} value={prod.name || prod.title}>
												{prod.name || prod.title}
											</option>
										))}
									</select>
								</div>

								{/* Tags */}
								<div className="dctc-sc-field-row">
									<label>{__('Tags', 'dragwyb-click-to-chat')}</label>
									<div className="dctc-sc-tags-manager-wrap">
										<div className="dctc-sc-tags-list">
											{(Array.isArray(selectedTicket?.tags) && selectedTicket.tags.length > 0) ? (
												selectedTicket.tags.map((tg, idx) => {
													const tagName = typeof tg === 'object' ? tg.name : tg;
													return (
														<span key={idx} className="dctc-sc-tag-pill">
															{tagName}
															<button
																type="button"
																className="dctc-sc-tag-remove"
																onClick={() => handleRemoveTag(tagName)}
																title={__('Remove tag', 'dragwyb-click-to-chat')}
															>
																&times;
															</button>
														</span>
													);
												})
											) : (
												<span className="dctc-sc-tag-empty">{__('No tags assigned', 'dragwyb-click-to-chat')}</span>
											)}
										</div>

										<div className="dctc-sc-add-tag-row">
											<select
												value=""
												onChange={(e) => handleAddTag(e.target.value)}
												className="dctc-sc-add-tag-select"
											>
												<option value="">{__('+ Add Tag...', 'dragwyb-click-to-chat')}</option>
												{tags.map((tg) => (
													<option key={tg.id || tg.name} value={tg.name}>
														{tg.name}
													</option>
												))}
											</select>
										</div>
									</div>
								</div>

								{/* Source */}
								<div className="dctc-sc-field-row">
									<label>{__('Source', 'dragwyb-click-to-chat')}</label>
									<span className="dctc-sc-static-val">
										{selectedTicket?.reply_surface === 'chatbot_widget' ? 'Chatbot Widget' : 'Support Portal'}
									</span>
								</div>
							</div>
						)}
					</div>
				)}
			</div>

			{/* WOOCOMMERCE ASSOCIATED ORDERS & PRODUCTS CARD */}
			{wcData && wcData.is_active && Array.isArray(wcData.recent_orders) && wcData.recent_orders.length > 0 && (
				<div className="dctc-sc-details-card">
					<button
						type="button"
						className="dctc-sc-card-head dctc-sc-card-head-toggle"
						onClick={() => setShowCommerceCard((prev) => !prev)}
						aria-expanded={showCommerceCard}
					>
						<div className="dctc-sc-card-head-title">
							<span className="dashicons dashicons-cart" style={{ color: '#7c3aed' }}></span>
							<h4>{__('WooCommerce Orders', 'dragwyb-click-to-chat')}</h4>
						</div>
						{wcData.total_spent && (
							<span className="dctc-sc-guest-tag" style={{ background: '#f5f3ff', color: '#6d28d9', borderColor: '#ddd6fe' }}>
								{wcData.total_spent}
							</span>
						)}
						<span className={`dashicons ${showCommerceCard ? 'dashicons-arrow-up-alt2' : 'dashicons-arrow-down-alt2'}`}></span>
					</button>

					{showCommerceCard && (
						<div className="dctc-sc-card-body dctc-sc-wc-orders-list" style={{ display: 'flex', flexDirection: 'column', gap: '10px' }}>
							{wcData.recent_orders.map((order) => (
								<div key={order.id} style={{ background: '#f8fafc', border: '1px solid #e2e8f0', borderRadius: '8px', padding: '10px 12px' }}>
									<div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '6px' }}>
										<a href={order.view_url} target="_blank" rel="noreferrer" style={{ fontWeight: 700, color: '#4f46e5', textDecoration: 'none', fontSize: '12.5px' }}>
											#{order.number}
										</a>
										<span className={`dctc-sc-badge ${order.status === 'completed' ? 'dctc-sc-badge-resolved' : 'dctc-sc-badge-pending'}`} style={{ fontSize: '10px', padding: '2px 6px' }}>
											{order.status_name || order.status}
										</span>
										<span style={{ fontSize: '12px', fontWeight: 600, color: '#0f172a' }}>{order.total}</span>
									</div>
									{order.items_summary && (
										<div style={{ fontSize: '12px', color: '#475569', lineHeight: '1.4', marginBottom: '4px' }}>
											<span className="dashicons dashicons-products" style={{ fontSize: '12px', width: '12px', height: '12px', verticalAlign: 'middle', marginRight: '3px', color: '#64748b' }}></span>
											{order.items_summary}
										</div>
									)}
									<div style={{ fontSize: '11px', color: '#94a3b8' }}>{order.date}</div>
								</div>
							))}
						</div>
					)}
				</div>
			)}

			{/* QUICK ACTIONS CARD */}
			<div className="dctc-sc-details-card">
				<button
					type="button"
					className="dctc-sc-card-head dctc-sc-card-head-toggle"
					onClick={() => setShowQuickActionsCard((prev) => !prev)}
					aria-expanded={showQuickActionsCard}
				>
					<div className="dctc-sc-card-head-title">
						<span className="dashicons dashicons-admin-generic"></span>
						<h4>{__('Quick Actions', 'dragwyb-click-to-chat')}</h4>
					</div>
					<span className={`dashicons ${showQuickActionsCard ? 'dashicons-arrow-up-alt2' : 'dashicons-arrow-down-alt2'}`}></span>
				</button>

				{showQuickActionsCard && (
					<div className="dctc-sc-card-body dctc-sc-quick-actions-grid">
						<button
							type="button"
							className="dctc-sc-quick-action-btn"
							onClick={() => onShowNotice(__('Ticket merge dialog coming soon.', 'dragwyb-click-to-chat'), 'info')}
						>
							<span className="dashicons dashicons-randomize"></span>
							{__('Merge', 'dragwyb-click-to-chat')}
						</button>

						<button
							type="button"
							className="dctc-sc-quick-action-btn"
							onClick={() => onShowNotice(__('Converted to email thread.', 'dragwyb-click-to-chat'), 'info')}
						>
							<span className="dashicons dashicons-email-alt"></span>
							{__('Email', 'dragwyb-click-to-chat')}
						</button>

						<button
							type="button"
							className="dctc-sc-quick-action-btn"
							onClick={() => window.print()}
						>
							<span className="dashicons dashicons-printer"></span>
							{__('Print', 'dragwyb-click-to-chat')}
						</button>

						<button
							type="button"
							className="dctc-sc-quick-action-btn delete"
							onClick={handleDeleteTicket}
						>
							<span className="dashicons dashicons-trash"></span>
							{__('Delete', 'dragwyb-click-to-chat')}
						</button>
					</div>
				)}
			</div>

			{/* VIEWERS CARD (RIGHT SIDE BOTTOM) */}
			<div className="dctc-sc-details-card dctc-sc-viewers-card">
				<div className="dctc-sc-card-head" style={{ cursor: 'default' }}>
					<div className="dctc-sc-card-head-title">
						<span className="dashicons dashicons-visibility" style={{ color: '#0f172a' }}></span>
						<h4>{__('Viewers', 'dragwyb-click-to-chat')} ({displayViewers.length})</h4>
					</div>
				</div>
				<div className="dctc-sc-card-body" style={{ paddingTop: '10px', paddingBottom: '14px' }}>
					<div className="dctc-sc-sidebar-viewers-stack">
						{displayViewers.map((viewer, vIdx) => {
							const colors = [
								'#4f46e5', // Indigo
								'#ea580c', // Orange
								'#059669', // Emerald
								'#e11d48', // Rose / Pink
								'#7c3aed', // Purple
								'#0284c7', // Sky
							];
							const matchedAgent = (agents || []).find(
								(a) => String(a.wp_user_id) === String(viewer.user_id) || String(a.id) === String(viewer.user_id)
							);
							const bg = viewer.color || matchedAgent?.color || viewer.bg || colors[vIdx % colors.length];
							const initials = viewer.initials || getInitials(viewer.name || viewer.display_name || 'Staff');
							const name = viewer.name || viewer.display_name || 'Staff Member';

							return (
								<div
									key={viewer.user_id || viewer.id || `v-${vIdx}`}
									className="dctc-sc-sidebar-viewer-avatar"
									style={{ backgroundColor: bg }}
									title={`${name} (Viewing now)`}
								>
									<span>{initials}</span>
									<span className="dctc-sc-viewer-online-dot"></span>
								</div>
							);
						})}
					</div>
				</div>
			</div>
		</aside>
	);
}
