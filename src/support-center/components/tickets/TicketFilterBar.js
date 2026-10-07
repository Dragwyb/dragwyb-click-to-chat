import { __ } from '@wordpress/i18n';

export default function TicketFilterBar({
	isDatabaseEmpty,
	isRefreshing,
	onManualRefresh,
	isFoldersExpanded,
	setIsFoldersExpanded,
	searchQuery,
	setSearchQuery,
	statusFilter,
	setStatusFilter,
	setCurrentPage,
	showMoreFilters,
	setShowMoreFilters,
	activeSecondaryFilterCount,
	priorityFilter,
	setPriorityFilter,
	categoryFilter,
	setCategoryFilter,
	assignedToFilter,
	setAssignedToFilter,
	productFilter,
	setProductFilter,
	tagFilter,
	setTagFilter,
	dateRangeFilter,
	setDateRangeFilter,
	customerTypeFilter,
	setCustomerTypeFilter,
	categories = [],
	agents = [],
	tags = [],
	onResetFilters,
}) {
	return (
		<div className={`dctc-sc-filter-toolbar-wrap ${isDatabaseEmpty ? 'dctc-sc-filter-toolbar-empty' : ''}`}>
			{isDatabaseEmpty ? (
				<div className="dctc-sc-filter-toolbar-main" style={{ justifyContent: 'space-between', width: '100%' }}>
					<div className="dctc-sc-toolbar-brand-badge">
						<span className="dashicons dashicons-tickets-alt" style={{ color: '#6366f1', fontSize: '20px', width: '20px', height: '20px' }}></span>
						<strong style={{ fontSize: '14.5px', color: '#0f172a' }}>{__('Tickets Workspace', 'dragwyb-click-to-chat')}</strong>
						<span style={{ fontSize: '12px', color: '#64748b', background: '#f1f5f9', padding: '3px 10px', borderRadius: '12px', fontWeight: 700 }}>
							{__('0 Tickets', 'dragwyb-click-to-chat')}
						</span>
					</div>
				</div>
			) : (
				<div className="dctc-sc-filter-toolbar-stack">
					{/* ROW 1: Search Bar + Filter Options Button */}
					<div className="dctc-sc-filter-row-top">
						<div className="dctc-sc-toolbar-search">
							<span className="dashicons dashicons-search"></span>
							<input
								type="text"
								placeholder={__('Search tickets by subject, customer, email, ID...', 'dragwyb-click-to-chat')}
								value={searchQuery}
								onChange={(e) => setSearchQuery(e.target.value)}
							/>
							{searchQuery && (
								<button
									type="button"
									className="dctc-sc-search-clear"
									onClick={() => setSearchQuery('')}
								>
									&times;
								</button>
							)}
						</div>

						<button
							type="button"
							className={`dctc-sc-more-filters-btn ${showMoreFilters || activeSecondaryFilterCount > 0 ? 'active' : ''}`}
							onClick={() => setShowMoreFilters((prev) => !prev)}
							title={__('Toggle advanced filters', 'dragwyb-click-to-chat')}
						>
							<span className="dashicons dashicons-filter"></span>
							<span>{__('Filter Options', 'dragwyb-click-to-chat')}</span>
							{activeSecondaryFilterCount > 0 && (
								<span className="dctc-sc-filter-active-count">{activeSecondaryFilterCount}</span>
							)}
							<span className={`dashicons ${showMoreFilters ? 'dashicons-arrow-up-alt2' : 'dashicons-arrow-down-alt2'}`} style={{ fontSize: '12px', width: '12px', height: '12px' }}></span>
						</button>
					</div>

					{/* ROW 2: Folders Button + Tags / Status Pills */}
					<div className="dctc-sc-filter-row-bottom">
						<button
							type="button"
							className={`dctc-sc-toggle-folders-btn ${isFoldersExpanded ? 'active' : ''}`}
							onClick={() => setIsFoldersExpanded((prev) => !prev)}
							title={isFoldersExpanded ? __('Collapse Folders Sidebar', 'dragwyb-click-to-chat') : __('Expand Folders Sidebar', 'dragwyb-click-to-chat')}
						>
							<span className="dashicons dashicons-category"></span>
							<span>{__('Folders', 'dragwyb-click-to-chat')}</span>
							<span className={`dashicons ${isFoldersExpanded ? 'dashicons-arrow-left-alt2' : 'dashicons-arrow-right-alt2'}`} style={{ fontSize: '11px', width: '11px', height: '11px' }}></span>
						</button>

						<div className="dctc-sc-quick-status-pills">
							{[
								{ id: 'all', label: __('All', 'dragwyb-click-to-chat') },
								{ id: 'open', label: __('Open', 'dragwyb-click-to-chat') },
								{ id: 'pending', label: __('Pending', 'dragwyb-click-to-chat') },
								{ id: 'resolved', label: __('Resolved', 'dragwyb-click-to-chat') },
								{ id: 'closed', label: __('Closed', 'dragwyb-click-to-chat') },
								{ id: 'ai_bot', label: __('AI Bot', 'dragwyb-click-to-chat') },
							].map((tab) => (
								<button
									key={tab.id}
									type="button"
									className={`dctc-sc-status-pill-btn ${statusFilter === tab.id ? 'active' : ''}`}
									onClick={() => { setStatusFilter(tab.id); setCurrentPage(1); }}
								>
									{tab.label}
								</button>
							))}
						</div>
					</div>
				</div>
			)}

			{/* SECONDARY FILTER DRAWER */}
			{showMoreFilters && (
				<div className="dctc-sc-filter-drawer">
					<div className="dctc-sc-drawer-dropdowns">
						{/* Priority */}
						<div className="dctc-sc-drawer-field">
							<label>{__('Priority', 'dragwyb-click-to-chat')}</label>
							<select
								value={priorityFilter}
								onChange={(e) => { setPriorityFilter(e.target.value); setCurrentPage(1); }}
							>
								<option value="all">{__('All Priorities', 'dragwyb-click-to-chat')}</option>
								<option value="urgent">{__('Urgent', 'dragwyb-click-to-chat')}</option>
								<option value="high">{__('High', 'dragwyb-click-to-chat')}</option>
								<option value="normal">{__('Normal', 'dragwyb-click-to-chat')}</option>
								<option value="low">{__('Low', 'dragwyb-click-to-chat')}</option>
							</select>
						</div>

						{/* Category */}
						<div className="dctc-sc-drawer-field">
							<label>{__('Category', 'dragwyb-click-to-chat')}</label>
							<select
								value={categoryFilter}
								onChange={(e) => { setCategoryFilter(e.target.value); setCurrentPage(1); }}
							>
								<option value="all">{__('All Categories', 'dragwyb-click-to-chat')}</option>
								{categories.map((cat) => (
									<option key={cat.id} value={cat.id}>{cat.name}</option>
								))}
							</select>
						</div>

						{/* Assigned Agent */}
						<div className="dctc-sc-drawer-field">
							<label>{__('Assigned Agent', 'dragwyb-click-to-chat')}</label>
							<select
								value={assignedToFilter}
								onChange={(e) => setAssignedToFilter(e.target.value)}
							>
								<option value="all">{__('All Agents', 'dragwyb-click-to-chat')}</option>
								<option value="0">{__('Unassigned', 'dragwyb-click-to-chat')}</option>
								{agents.map((ag) => (
									<option key={ag.id} value={ag.id}>{ag.display_name}</option>
								))}
							</select>
						</div>

						{/* Product */}
						<div className="dctc-sc-drawer-field">
							<label>{__('Product', 'dragwyb-click-to-chat')}</label>
							<select
								value={productFilter}
								onChange={(e) => setProductFilter(e.target.value)}
							>
								<option value="all">{__('All Products', 'dragwyb-click-to-chat')}</option>
								<option value="chatbot">{__('Chatbot Widget', 'dragwyb-click-to-chat')}</option>
								<option value="portal">{__('Support Portal', 'dragwyb-click-to-chat')}</option>
							</select>
						</div>

						{/* Tags */}
						<div className="dctc-sc-drawer-field">
							<label>{__('Tag', 'dragwyb-click-to-chat')}</label>
							<select
								value={tagFilter}
								onChange={(e) => setTagFilter(e.target.value)}
							>
								<option value="all">{__('All Tags', 'dragwyb-click-to-chat')}</option>
								{tags.map((tg) => (
									<option key={tg.id} value={tg.name}>{tg.name}</option>
								))}
							</select>
						</div>

						{/* Date Range */}
						<div className="dctc-sc-drawer-field">
							<label>{__('Date Range', 'dragwyb-click-to-chat')}</label>
							<select
								value={dateRangeFilter}
								onChange={(e) => setDateRangeFilter(e.target.value)}
							>
								<option value="all">{__('All Time', 'dragwyb-click-to-chat')}</option>
								<option value="today">{__('Today', 'dragwyb-click-to-chat')}</option>
								<option value="7days">{__('Last 7 Days', 'dragwyb-click-to-chat')}</option>
								<option value="30days">{__('Last 30 Days', 'dragwyb-click-to-chat')}</option>
							</select>
						</div>

						{/* Customer Type */}
						<div className="dctc-sc-drawer-field">
							<label>{__('Customer Type', 'dragwyb-click-to-chat')}</label>
							<select
								value={customerTypeFilter}
								onChange={(e) => setCustomerTypeFilter(e.target.value)}
							>
								<option value="all">{__('All Types', 'dragwyb-click-to-chat')}</option>
								<option value="registered">{__('Registered User', 'dragwyb-click-to-chat')}</option>
								<option value="guest">{__('Guest Visitor', 'dragwyb-click-to-chat')}</option>
							</select>
						</div>
					</div>

					<div className="dctc-sc-drawer-footer">
						<button
							type="button"
							className="dctc-sc-reset-filters-btn"
							onClick={onResetFilters}
						>
							<span className="dashicons dashicons-image-rotate"></span>
							{__('Reset All Filters', 'dragwyb-click-to-chat')}
						</button>
					</div>
				</div>
			)}
		</div>
	);
}
