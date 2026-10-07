/**
 * General Subtab: Site-Wide Activation, Identity & Support Desk Integration
 */
import { __, sprintf } from '@wordpress/i18n';
import { SwitcherCard, SettingCard } from '../../components';

export default function GeneralSubtab({
	form,
	setField,
	copiedShortcode,
	copyShortcode,
	comboRef,
	search,
	setSearch,
	openSearch,
	setOpenSearch,
	loadingContent,
	filtered,
	ids,
	selectedIds,
	content,
	addExclude,
	toggleExclude,
	onOpenMessages,
}) {
	return (
		<div className="dctc-ai-tab-panel-section">
			{/* 1. Site-Wide Activation & Visibility Rules (Conditional Switcher Card) */}
			<SwitcherCard
				id="entire_site"
				title={__('Site-Wide Chatbot Widget', 'dragwyb-click-to-chat')}
				desc={__('Activate the floating AI chat assistant across your public website pages.', 'dragwyb-click-to-chat')}
				icon="dashicons-desktop"
				checked={form.entire_site}
				onChange={(v) => setField('entire_site', v)}
				disabledNotice={__('Site-wide floating button is currently turned off.', 'dragwyb-click-to-chat')}
				disabledContent={
					<div className="dctc-ai-shortcode-guide-card">
						<div className="dctc-ai-shortcode-guide-header">
							<div className="dctc-ai-shortcode-guide-icon-box">
								<span className="dashicons dashicons-shortcode" />
							</div>
							<div>
								<h3 className="dctc-ai-shortcode-guide-title">
									{__('Show Chatbot on Specific Pages Using Shortcode', 'dragwyb-click-to-chat')}
								</h3>
								<p className="dctc-ai-shortcode-guide-subtitle">
									{__('Since site-wide floating widget is disabled, you can embed the AI Chatbot directly on specific pages or posts using the shortcode below.', 'dragwyb-click-to-chat')}
								</p>
							</div>
						</div>

						<div className="dctc-ai-shortcode-box">
							<div className="dctc-ai-shortcode-box__tag-wrap">
								<code className="dctc-ai-shortcode-code">[dctc_ai]</code>
							</div>
							<button
								type="button"
								className={`dctc-ai-btn dctc-ai-btn-sm ${copiedShortcode ? 'dctc-ai-btn-primary is-copied' : 'dctc-ai-btn-secondary'}`}
								onClick={copyShortcode}
							>
								<span className={`dashicons ${copiedShortcode ? 'dashicons-yes-alt' : 'dashicons-admin-page'}`} />
								{copiedShortcode ? __('Copied to Clipboard!', 'dragwyb-click-to-chat') : __('Copy Shortcode', 'dragwyb-click-to-chat')}
							</button>
						</div>

						<div className="dctc-ai-shortcode-instructions-grid">
							<div className="dctc-ai-shortcode-inst-item">
								<span className="dashicons dashicons-block-default" />
								<div className="dctc-ai-shortcode-inst-text">
									<strong>{__('Gutenberg Block Editor', 'dragwyb-click-to-chat')}</strong>
									<span>{__('Add a Shortcode block and paste [dctc_ai].', 'dragwyb-click-to-chat')}</span>
								</div>
							</div>
							<div className="dctc-ai-shortcode-inst-item">
								<span className="dashicons dashicons-layout" />
								<div className="dctc-ai-shortcode-inst-text">
									<strong>{__('Elementor & Page Builders', 'dragwyb-click-to-chat')}</strong>
									<span>{__('Drag a Shortcode or HTML widget into your template and paste [dctc_ai].', 'dragwyb-click-to-chat')}</span>
								</div>
							</div>
							<div className="dctc-ai-shortcode-inst-item">
								<span className="dashicons dashicons-media-code" />
								<div className="dctc-ai-shortcode-inst-text">
									<strong>{__('PHP Templates', 'dragwyb-click-to-chat')}</strong>
									<span><code>&lt;?php echo do_shortcode( '[dctc_ai]' ); ?&gt;</code></span>
								</div>
							</div>
						</div>
					</div>
				}
			>
				<div className="dctc-ai-form-stack">
					<div className="dctc-ai-bot-field">
						<label htmlFor="launcher_text">
							{__('Button Call-to-Action Text Badge (Optional)', 'dragwyb-click-to-chat')}
						</label>
						<div className="dctc-ai-input-with-icon">
							<span className="dashicons dashicons-testimonial" />
							<input
								type="text"
								id="launcher_text"
								className="dctc-ai-bot-input"
								value={form.launcher_text}
								onChange={(e) => setField('launcher_text', e.target.value)}
								placeholder={__('e.g. Chat with us, How can we help?', 'dragwyb-click-to-chat')}
							/>
						</div>
						<p className="dctc-ai-bot-hint">
							{__('Small text pill displayed adjacent to the circular floating button.', 'dragwyb-click-to-chat')}
						</p>
					</div>

					<SettingCard
						id="show_on_mobile"
						title={__('Enable on Mobile Devices', 'dragwyb-click-to-chat')}
						desc={__('Optimize touch targets and modal dimensions for smartphones and tablets.', 'dragwyb-click-to-chat')}
						icon="dashicons-smartphone"
						checked={form.show_on_mobile}
						onChange={(v) => setField('show_on_mobile', v)}
					/>

					{/* Exclusion Search Combobox */}
					<div className="dctc-ai-bot-field">
						<label htmlFor="exclude_search_input">
							{__('Exclude Content (Pages, Posts, Custom Types)', 'dragwyb-click-to-chat')}
						</label>
						<div ref={comboRef} className="dctc-ai-combobox-wrapper">
							<div className="dctc-ai-combobox-input-wrap">
								<span className="dashicons dashicons-search dctc-ai-combobox-search-icon" />
								<input
									type="text"
									id="exclude_search_input"
									className="dctc-ai-bot-input dctc-ai-combobox-input"
									value={search}
									onChange={(e) => {
										setSearch(e.target.value);
										setOpenSearch(true);
									}}
									onFocus={() => setOpenSearch(true)}
									onKeyDown={(e) => {
										if (e.key === 'Enter') {
											e.preventDefault();
											addExclude();
										}
									}}
									placeholder={
										loadingContent
											? __('Loading website content…', 'dragwyb-click-to-chat')
											: __('Search pages or posts to exclude by title or ID…', 'dragwyb-click-to-chat')
									}
								/>
								<span className="dctc-ai-combobox-arrow">▼</span>
							</div>

							{openSearch && (
								<div className="dctc-ai-combobox-dropdown">
									{filtered.length === 0 ? (
										<div className="dctc-ai-combobox-empty">
											{search.trim() ? (
												<div>
													<div>{__('No matching page/post found.', 'dragwyb-click-to-chat')}</div>
													<button
														type="button"
														className="dctc-ai-btn dctc-ai-btn-primary dctc-ai-btn-sm"
														style={{ marginTop: '6px' }}
														onClick={() => addExclude()}
													>
														{sprintf(
															/* translators: %s: custom ID */
															__('Add "%s" as custom ID', 'dragwyb-click-to-chat'),
															search.trim()
														)}
													</button>
												</div>
											) : (
												__('No content available.', 'dragwyb-click-to-chat')
											)}
										</div>
									) : (
										filtered.map((item) => {
											const idStr = String(item.id);
											const selected = ids.includes(idStr);
											return (
												<div
													key={item.id}
													className={`dctc-ai-combobox-item ${selected ? 'is-selected' : ''}`}
													onClick={() => toggleExclude(idStr)}
												>
													<div className="dctc-ai-combobox-item__main">
														<span className={`dctc-ai-type-pill is-${item.type.toLowerCase()}`}>
															{item.type}
														</span>
														<span className="dctc-ai-combobox-item__title">{item.title}</span>
														<span className="dctc-ai-combobox-item__id">(ID: {item.id})</span>
													</div>
													{selected && <span className="dashicons dashicons-yes-alt dctc-ai-check-icon" />}
												</div>
											);
										})
									)}
								</div>
							)}

							<div className="dctc-ai-selected-tokens">
								{ids.length === 0 ? (
									<div className="dctc-ai-no-tokens-banner">
										<span className="dashicons dashicons-yes-alt" />
										<span>{__('Chatbot active on all pages (no content currently excluded).', 'dragwyb-click-to-chat')}</span>
									</div>
								) : (
									ids.map((id) => {
										const item = content.find((c) => String(c.id) === id);
										const label = item
											? `[${item.type}] ${item.title}`
											: sprintf(
												/* translators: %s: post ID */
												__('ID %s', 'dragwyb-click-to-chat'),
												id
											);
										return (
											<span key={id} className="dctc-ai-token-chip">
												<span>{label}</span>
												<button
													type="button"
													className="dctc-ai-token-chip__remove"
													onClick={() => {
														const next = selectedIds().filter((x) => x !== id);
														setField('exclude_pages', next.join(', '));
													}}
													aria-label={__('Remove', 'dragwyb-click-to-chat')}
												>
													✕
												</button>
											</span>
										);
									})
								)}
							</div>
						</div>
					</div>
				</div>
			</SwitcherCard>

			{/* 2. Bot Identity & Support Desk Integration */}
			<section className="dctc-ai-card">
				<header className="dctc-ai-card__header">
					<div className="dctc-ai-card__header-left">
						<div className="dctc-ai-card-icon">
							<span className="dashicons dashicons-id-alt" />
						</div>
						<div>
							<h2 className="dctc-ai-card__title">
								{__('AI Persona & Helpdesk Integration', 'dragwyb-click-to-chat')}
							</h2>
							<p className="dctc-ai-card__desc">
								{__('Define the public assistant name and target support URL for handoffs and inquiries.', 'dragwyb-click-to-chat')}
							</p>
						</div>
					</div>
				</header>

				<div className="dctc-ai-card__body">
					<div className="dctc-ai-form-stack">
						<div className="dctc-ai-grid-2col">
							<div className="dctc-ai-bot-field">
								<label htmlFor="bot_name">
									{__('AI Assistant Public Name', 'dragwyb-click-to-chat')}
								</label>
								<div className="dctc-ai-input-with-icon">
									<span className="dashicons dashicons-admin-users" />
									<input
										type="text"
										id="bot_name"
										className="dctc-ai-bot-input"
										value={form.bot_name}
										onChange={(e) => setField('bot_name', e.target.value)}
										placeholder={__('AI Assistant', 'dragwyb-click-to-chat')}
									/>
								</div>
								<p className="dctc-ai-bot-hint">
									{__('Shown at the top of the chat window and alongside assistant replies.', 'dragwyb-click-to-chat')}
								</p>
							</div>

							<div className="dctc-ai-bot-field">
								<label htmlFor="support_url">
									{__('Support Helpdesk URL', 'dragwyb-click-to-chat')}
								</label>
								<div className="dctc-ai-input-with-icon">
									<span className="dashicons dashicons-admin-links" />
									<input
										type="url"
										id="support_url"
										className="dctc-ai-bot-input"
										value={form.support_url}
										onChange={(e) => setField('support_url', e.target.value)}
										placeholder={__('https://example.com/support', 'dragwyb-click-to-chat')}
									/>
								</div>
								<p className="dctc-ai-bot-hint">
									{__('Destination URL for support links in notifications and error fallback responses.', 'dragwyb-click-to-chat')}
								</p>
							</div>
						</div>

						<div style={{ padding: '0.85rem 1rem', background: '#f8fafc', borderRadius: '8px', border: '1px solid #e2e8f0', display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: '1rem', marginTop: '0.5rem' }}>
							<div style={{ display: 'flex', alignItems: 'center', gap: '0.65rem' }}>
								<span className="dashicons dashicons-format-chat" style={{ color: '#6366f1', fontSize: '1.25rem' }} />
								<span style={{ fontSize: '0.875rem', color: '#475569' }}>
									{__('Looking to customize Greeting, API Errors, Order Tracking, or Support notices?', 'dragwyb-click-to-chat')}
								</span>
							</div>
							<button
								type="button"
								className="dctc-ai-btn dctc-ai-btn-sm dctc-ai-btn-secondary"
								onClick={onOpenMessages}
							>
								{__('Open Messages Tab →', 'dragwyb-click-to-chat')}
							</button>
						</div>
					</div>
				</div>
			</section>
		</div>
	);
}
