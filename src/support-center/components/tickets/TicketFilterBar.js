import { __ } from '@wordpress/i18n';

export default function TicketFilterBar({
	isDatabaseEmpty,
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
	ticketTypeFilter = 'all',
	setTicketTypeFilter,
	taxFilters = {},
	setTaxFilters,
	taxonomies = [],
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
	products = [],
	agents = [],
	tags = [],
	onResetFilters,
}) {
	// Helper to determine if we should render taxonomies dynamically
	const hasDynamicTaxonomies = Array.isArray(taxonomies) && taxonomies.length > 0;

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

					{/* EXPANDABLE FILTER DRAWER - Placed above Folders & Tags row */}
					{showMoreFilters && (
						<div className="dctc-sc-filter-drawer">
							<div className="dctc-sc-drawer-dropdowns">
								{/* 1. Priority */}
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

								{/* 2. Ticket Type: Human vs AI Bot */}
								{setTicketTypeFilter && (
									<div className="dctc-sc-drawer-field">
										<label>{__('Ticket Type', 'dragwyb-click-to-chat')}</label>
										<select
											value={ticketTypeFilter}
											onChange={(e) => { setTicketTypeFilter(e.target.value); setCurrentPage(1); }}
										>
											<option value="all">{__('All Types', 'dragwyb-click-to-chat')}</option>
											<option value="human">{__('Human Agent', 'dragwyb-click-to-chat')}</option>
											<option value="ai">{__('AI Bot', 'dragwyb-click-to-chat')}</option>
										</select>
									</div>
								)}

								{/* 3. Dynamic Taxonomies (Only shown if configured terms list is NOT empty) */}
								{hasDynamicTaxonomies ? (
									taxonomies.map((tax) => {
										const termsList = Array.isArray(tax.terms) ? tax.terms : [];
										if (termsList.length === 0) return null; // Hide if empty

										let currentVal = 'all';
										let onChangeHandler = (e) => {
											if (setTaxFilters) {
												setTaxFilters((prev) => ({ ...prev, [tax.slug]: e.target.value }));
											}
											setCurrentPage(1);
										};

										if (tax.slug === 'category') {
											currentVal = categoryFilter;
											onChangeHandler = (e) => {
												setCategoryFilter(e.target.value);
												if (setTaxFilters) {
													setTaxFilters((prev) => ({ ...prev, category: e.target.value }));
												}
												setCurrentPage(1);
											};
										} else if (tax.slug === 'product') {
											currentVal = productFilter;
											onChangeHandler = (e) => {
												setProductFilter(e.target.value);
												if (setTaxFilters) {
													setTaxFilters((prev) => ({ ...prev, product: e.target.value }));
												}
												setCurrentPage(1);
											};
										} else if (tax.slug === 'tag') {
											currentVal = tagFilter;
											onChangeHandler = (e) => {
												setTagFilter(e.target.value);
												if (setTaxFilters) {
													setTaxFilters((prev) => ({ ...prev, tag: e.target.value }));
												}
												setCurrentPage(1);
											};
										} else if (taxFilters && taxFilters[tax.slug]) {
											currentVal = taxFilters[tax.slug];
										}

										return (
											<div key={tax.slug || tax.id} className="dctc-sc-drawer-field">
												<label>{tax.name || tax.singular_name || __('Taxonomy', 'dragwyb-click-to-chat')}</label>
												<select
													value={currentVal}
													onChange={onChangeHandler}
												>
													<option value="all">
														{__('All', 'dragwyb-click-to-chat')} {tax.name || ''}
													</option>
													{termsList.map((term) => (
														<option key={term.id} value={term.id || term.slug || term.name}>
															{term.name}
														</option>
													))}
												</select>
											</div>
										);
									})
								) : (
									<>
										{/* Fallback 3a: Category (Only show if categories list is NOT empty) */}
										{Array.isArray(categories) && categories.length > 0 && (
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
										)}

										{/* Fallback 3b: Product (Only show if products list is NOT empty) */}
										{Array.isArray(products) && products.length > 0 && (
											<div className="dctc-sc-drawer-field">
												<label>{__('Product', 'dragwyb-click-to-chat')}</label>
												<select
													value={productFilter}
													onChange={(e) => { setProductFilter(e.target.value); setCurrentPage(1); }}
												>
													<option value="all">{__('All Products', 'dragwyb-click-to-chat')}</option>
													{products.map((prod) => (
														<option key={prod.id} value={prod.id}>{prod.name}</option>
													))}
												</select>
											</div>
										)}

										{/* Fallback 3c: Tag (Only show if tags list is NOT empty) */}
										{Array.isArray(tags) && tags.length > 0 && (
											<div className="dctc-sc-drawer-field">
												<label>{__('Tag', 'dragwyb-click-to-chat')}</label>
												<select
													value={tagFilter}
													onChange={(e) => { setTagFilter(e.target.value); setCurrentPage(1); }}
												>
													<option value="all">{__('All Tags', 'dragwyb-click-to-chat')}</option>
													{tags.map((tg) => (
														<option key={tg.id} value={tg.name}>{tg.name}</option>
													))}
												</select>
											</div>
										)}
									</>
								)}

								{/* 4. Assigned Agent (Only show if agents list is NOT empty) */}
								{Array.isArray(agents) && agents.length > 0 && (
									<div className="dctc-sc-drawer-field">
										<label>{__('Assigned Agent', 'dragwyb-click-to-chat')}</label>
										<select
											value={assignedToFilter}
											onChange={(e) => {
												setAssignedToFilter(e.target.value);
												if (setCurrentPage) setCurrentPage(1);
											}}
										>
											<option value="all">{__('All Agents', 'dragwyb-click-to-chat')}</option>
											<option value="0">{__('Unassigned', 'dragwyb-click-to-chat')}</option>
											{agents.map((ag) => (
												<option key={ag.id} value={ag.id}>{ag.display_name}</option>
											))}
										</select>
									</div>
								)}

								{/* 5. Date Range */}
								<div className="dctc-sc-drawer-field">
									<label>{__('Date Range', 'dragwyb-click-to-chat')}</label>
									<select
										value={dateRangeFilter}
										onChange={(e) => {
											setDateRangeFilter(e.target.value);
											if (setCurrentPage) setCurrentPage(1);
										}}
									>
										<option value="all">{__('All Time', 'dragwyb-click-to-chat')}</option>
										<option value="today">{__('Today', 'dragwyb-click-to-chat')}</option>
										<option value="7days">{__('Last 7 Days', 'dragwyb-click-to-chat')}</option>
										<option value="30days">{__('Last 30 Days', 'dragwyb-click-to-chat')}</option>
									</select>
								</div>

								{/* 6. Customer Type */}
								<div className="dctc-sc-drawer-field">
									<label>{__('Customer Type', 'dragwyb-click-to-chat')}</label>
									<select
										value={customerTypeFilter}
										onChange={(e) => {
											setCustomerTypeFilter(e.target.value);
											if (setCurrentPage) setCurrentPage(1);
										}}
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
		</div>
	);
}
