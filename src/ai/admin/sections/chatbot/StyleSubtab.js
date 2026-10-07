/**
 * Style Subtab: Unified Live Customizer, Floating Launcher, Avatars, Branding Colors & Positioning
 */
import { __ } from '@wordpress/i18n';
import { ColorField, SettingCard } from '../../components';

const LAUNCHER_PRESETS = [
	{ id: 'chat', label: __('💬 Chat Bubble (Default)', 'dragwyb-click-to-chat') },
	{ id: 'bot', label: __('🤖 AI Robot', 'dragwyb-click-to-chat') },
	{ id: 'sparkle', label: __('✨ AI Magic Sparkle', 'dragwyb-click-to-chat') },
	{ id: 'support', label: __('🎧 Support Agent', 'dragwyb-click-to-chat') },
	{ id: 'help', label: __('❓ Help Desk', 'dragwyb-click-to-chat') },
	{ id: 'whatsapp', label: __('📱 WhatsApp Chat', 'dragwyb-click-to-chat') },
];

export default function StyleSubtab({
	form,
	setField,
	openBotMedia,
	openUserMedia,
	openAssistantIconMedia,
	renderBotAvatarIcon,
	renderUserAvatarIcon,
	renderLauncherIcon,
	getBubbleRadiusStyle,
}) {
	return (
		<div className="dctc-ai-tab-panel-section">
			{/* 1. UNIFIED LIVE CUSTOMIZER: Floating Launcher, Avatars, Branding Colors & Real-Time Simulation */}
			<section className="dctc-ai-card">
				<header className="dctc-ai-card__header">
					<div className="dctc-ai-card__header-left">
						<div className="dctc-ai-card-icon">
							<span className="dashicons dashicons-admin-customizer" />
						</div>
						<div>
							<h2 className="dctc-ai-card__title">
								{__('Chatbot Visual Styling & Live Preview', 'dragwyb-click-to-chat')}
							</h2>
							<p className="dctc-ai-card__desc">
								{__('Customize launcher button, bot & visitor avatars, brand colors, and message bubble styles with instant live preview.', 'dragwyb-click-to-chat')}
							</p>
						</div>
					</div>
				</header>

				<div className="dctc-ai-card__body">
					<div className="dctc-ai-live-customizer-layout">
						{/* Left Column: UI & Styling Controls */}
						<div className="dctc-ai-customizer-controls">
							{/* Group A: Floating Launcher Button */}
							<div className="dctc-ai-customizer-group">
								<div className="dctc-ai-customizer-group__header">
									<span className="dashicons dashicons-art" />
									<h3 className="dctc-ai-customizer-group__title">
										{__('Floating Launcher Button', 'dragwyb-click-to-chat')}
									</h3>
								</div>

								<div className="dctc-ai-grid-2col">
									<div className="dctc-ai-bot-field">
										<label htmlFor="launcher_icon_preset">
											{__('Preset Icon', 'dragwyb-click-to-chat')}
										</label>
										<select
											id="launcher_icon_preset"
											className="dctc-ai-bot-select"
											value={form.launcher_icon_preset}
											onChange={(e) => setField('launcher_icon_preset', e.target.value)}
										>
											{LAUNCHER_PRESETS.map((preset) => (
												<option key={preset.id} value={preset.id}>
													{preset.label}
												</option>
											))}
										</select>
									</div>

									<div className="dctc-ai-bot-field">
										<label htmlFor="widget_size">
											{__('Button Size', 'dragwyb-click-to-chat')}
										</label>
										<div className="dctc-ai-widget-size-row">
											<input
												type="number"
												id="widget_size"
												className="dctc-ai-bot-input dctc-ai-widget-size-input"
												min="24"
												max="120"
												step="1"
												value={form.widget_size}
												onChange={(e) =>
													setField(
														'widget_size',
														e.target.value === '' ? '' : Number(e.target.value)
													)
												}
											/>
											<select
												id="widget_size_unit"
												className="dctc-ai-bot-select dctc-ai-widget-size-unit"
												value={form.widget_size_unit}
												onChange={(e) => setField('widget_size_unit', e.target.value)}
											>
												<option value="px">px</option>
												<option value="rem">rem</option>
												<option value="em">em</option>
											</select>
										</div>
									</div>
								</div>

								<div className="dctc-ai-bot-field">
									<label>
										{__('Custom Uploaded Launcher Icon (Optional)', 'dragwyb-click-to-chat')}
									</label>
									<div className="dctc-ai-assistant-icon-row">
										<div className="dctc-ai-avatar-btn-group">
											<button
												type="button"
												className="dctc-ai-btn dctc-ai-btn-secondary dctc-ai-btn-sm"
												onClick={openAssistantIconMedia}
											>
												<span className="dashicons dashicons-upload" />
												{form.assistant_icon
													? __('Change Custom Icon', 'dragwyb-click-to-chat')
													: __('Upload Custom Icon', 'dragwyb-click-to-chat')}
											</button>
											{!!form.assistant_icon && (
												<button
													type="button"
													className="dctc-ai-btn dctc-ai-btn-secondary dctc-ai-btn-sm"
													onClick={() => setField('assistant_icon', '')}
												>
													{__('Reset to Preset', 'dragwyb-click-to-chat')}
												</button>
											)}
										</div>
										{form.assistant_icon ? (
											<span className="dctc-ai-filename-chip">
												{form.assistant_icon.split('/').pop()}
											</span>
										) : (
											<span className="dctc-ai-filename-chip is-muted">
												{__('Using preset icon', 'dragwyb-click-to-chat')}
											</span>
										)}
									</div>
								</div>
							</div>

							{/* Group B: Branding Colors & Message Bubbles */}
							<div className="dctc-ai-customizer-group">
								<div className="dctc-ai-customizer-group__header">
									<span className="dashicons dashicons-color-picker" />
									<h3 className="dctc-ai-customizer-group__title">
										{__('Branding Colors & Message Bubbles', 'dragwyb-click-to-chat')}
									</h3>
								</div>

								<div className="dctc-ai-grid-2col">
									<ColorField
										id="primary_color"
										label={__('Brand Primary Color', 'dragwyb-click-to-chat')}
										value={form.primary_color}
										onChange={(v) => setField('primary_color', v)}
									/>

									<div className="dctc-ai-bot-field">
										<label htmlFor="bubble_style">
											{__('Message Bubble Corners', 'dragwyb-click-to-chat')}
										</label>
										<select
											id="bubble_style"
											className="dctc-ai-bot-select"
											value={form.bubble_style}
											onChange={(e) => setField('bubble_style', e.target.value)}
										>
											<option value="rounded">{__('Rounded Corners', 'dragwyb-click-to-chat')}</option>
											<option value="square">{__('Square / Sharp', 'dragwyb-click-to-chat')}</option>
											<option value="pill">{__('Pill / Smooth', 'dragwyb-click-to-chat')}</option>
										</select>
									</div>
								</div>

								<div className="dctc-ai-features-grid">
									<SettingCard
										id="show_bot_avatar_in_chat"
										title={__('Show Bot Avatar in Messages', 'dragwyb-click-to-chat')}
										desc={__('Display assistant avatar badge beside bot answers.', 'dragwyb-click-to-chat')}
										checked={form.show_bot_avatar_in_chat}
										onChange={(v) => setField('show_bot_avatar_in_chat', v)}
									/>
									<SettingCard
										id="show_user_avatar_in_chat"
										title={__('Show User Avatar in Messages', 'dragwyb-click-to-chat')}
										desc={__('Display visitor avatar badge beside visitor messages.', 'dragwyb-click-to-chat')}
										checked={form.show_user_avatar_in_chat}
										onChange={(v) => setField('show_user_avatar_in_chat', v)}
									/>
									<SettingCard
										id="show_sources"
										title={__('Show Source Links in Answers', 'dragwyb-click-to-chat')}
										desc={__('Display clickable source citation pills under AI answers when Knowledge Base content is cited.', 'dragwyb-click-to-chat')}
										checked={form.show_sources}
										onChange={(v) => setField('show_sources', v)}
									/>
								</div>
							</div>

							{/* Group C: Bot Avatar & Identity */}
							<div className="dctc-ai-customizer-group">
								<div className="dctc-ai-customizer-group__header">
									<span className="dashicons dashicons-superhero-alt" />
									<h3 className="dctc-ai-customizer-group__title">
										{__('Bot Avatar & Icon', 'dragwyb-click-to-chat')}
									</h3>
								</div>

								<div className="dctc-ai-avatar-editor">
									<div className="dctc-ai-avatar-preview-badge" style={{ backgroundColor: `${form.primary_color}15`, color: form.primary_color }}>
										{renderBotAvatarIcon(32)}
									</div>

									<div className="dctc-ai-avatar-controls">
										<div className="dctc-ai-bot-field">
											<label htmlFor="bot_icon_preset">
												{__('Preset Icon Style', 'dragwyb-click-to-chat')}
											</label>
											<select
												id="bot_icon_preset"
												className="dctc-ai-bot-select"
												value={form.bot_icon_preset || 'bot'}
												onChange={(e) => setField('bot_icon_preset', e.target.value)}
											>
												<option value="bot">{__('🤖 Robot Assistant', 'dragwyb-click-to-chat')}</option>
												<option value="sparkle">{__('✨ Sparkle AI', 'dragwyb-click-to-chat')}</option>
												<option value="support">{__('🎧 Support Agent', 'dragwyb-click-to-chat')}</option>
												<option value="chat">{__('💬 Chat Bubble', 'dragwyb-click-to-chat')}</option>
												<option value="initial">{__('🔤 Bot Name Initial', 'dragwyb-click-to-chat')}</option>
											</select>
										</div>

										<div className="dctc-ai-avatar-btn-group">
											<button
												type="button"
												className="dctc-ai-btn dctc-ai-btn-secondary dctc-ai-btn-sm"
												onClick={openBotMedia}
											>
												<span className="dashicons dashicons-upload" />
												{form.bot_avatar
													? __('Change Custom Image', 'dragwyb-click-to-chat')
													: __('Upload Custom Image', 'dragwyb-click-to-chat')}
											</button>
											{!!form.bot_avatar && (
												<button
													type="button"
													className="dctc-ai-btn dctc-ai-btn-secondary dctc-ai-btn-sm"
													onClick={() => setField('bot_avatar', '')}
												>
													{__('Reset to Preset', 'dragwyb-click-to-chat')}
												</button>
											)}
										</div>

										{form.bot_avatar ? (
											<span className="dctc-ai-filename-chip">
												{form.bot_avatar.split('/').pop()}
											</span>
										) : (
											<span className="dctc-ai-filename-chip is-muted">
												{__('Using preset vector icon', 'dragwyb-click-to-chat')}
											</span>
										)}
									</div>
								</div>
							</div>

							{/* Group D: Visitor Avatar (conditionally shown only if show_user_avatar_in_chat is true) */}
							{form.show_user_avatar_in_chat && (
								<div className="dctc-ai-customizer-group">
									<div className="dctc-ai-customizer-group__header">
										<span className="dashicons dashicons-admin-users" />
										<h3 className="dctc-ai-customizer-group__title">
											{__('Visitor Avatar & Icon', 'dragwyb-click-to-chat')}
										</h3>
									</div>

									<div className="dctc-ai-avatar-editor">
										<div className="dctc-ai-avatar-preview-badge dctc-ai-avatar-preview-badge--user">
											{renderUserAvatarIcon(32)}
										</div>

										<div className="dctc-ai-avatar-controls">
											<div className="dctc-ai-bot-field">
												<label htmlFor="user_icon_preset">
													{__('Preset Icon Style', 'dragwyb-click-to-chat')}
												</label>
												<select
													id="user_icon_preset"
													className="dctc-ai-bot-select"
													value={form.user_icon_preset || 'user'}
													onChange={(e) => setField('user_icon_preset', e.target.value)}
												>
													<option value="user">{__('👤 Default User', 'dragwyb-click-to-chat')}</option>
													<option value="user-circle">{__('🔘 User Circle', 'dragwyb-click-to-chat')}</option>
													<option value="business">{__('👔 Business Client', 'dragwyb-click-to-chat')}</option>
													<option value="smile">{__('😊 Friendly Smile', 'dragwyb-click-to-chat')}</option>
													<option value="star">{__('⭐ Star Member', 'dragwyb-click-to-chat')}</option>
												</select>
											</div>

											<div className="dctc-ai-avatar-btn-group">
												<button
													type="button"
													className="dctc-ai-btn dctc-ai-btn-secondary dctc-ai-btn-sm"
													onClick={openUserMedia}
												>
													<span className="dashicons dashicons-upload" />
													{form.user_avatar
														? __('Change Custom Image', 'dragwyb-click-to-chat')
														: __('Upload Custom Image', 'dragwyb-click-to-chat')}
												</button>
												{!!form.user_avatar && (
													<button
														type="button"
														className="dctc-ai-btn dctc-ai-btn-secondary dctc-ai-btn-sm"
														onClick={() => setField('user_avatar', '')}
													>
														{__('Reset to Preset', 'dragwyb-click-to-chat')}
													</button>
												)}
											</div>

											{form.user_avatar ? (
												<span className="dctc-ai-filename-chip">
													{form.user_avatar.split('/').pop()}
												</span>
											) : (
												<span className="dctc-ai-filename-chip is-muted">
													{__('Using preset vector icon', 'dragwyb-click-to-chat')}
												</span>
											)}
										</div>
									</div>
								</div>
							)}
						</div>

						{/* Right Column: Unified Real-Time Live Preview */}
						<div className="dctc-ai-live-preview-panel">
							<div className="dctc-ai-live-preview-panel__header">
								<h4 className="dctc-ai-live-preview-panel__title">
									<span className="dashicons dashicons-visibility" />
									{__('Interactive Live Preview', 'dragwyb-click-to-chat')}
								</h4>
								<span className="dctc-ai-status-pill is-active">
									{__('Real-Time', 'dragwyb-click-to-chat')}
								</span>
							</div>

							<div className="dctc-ai-preview-stage">
								{/* Chat Window Simulation */}
								<div className="dctc-ai-chat-live-mockup">
									<div className="dctc-ai-chat-live-mockup__header" style={{ backgroundColor: form.primary_color }}>
										<div className="dctc-ai-mockup-avatar">
											{renderBotAvatarIcon(18)}
										</div>
										<span className="dctc-ai-mockup-title">{form.bot_name || 'AI Assistant'}</span>
									</div>

									<div className="dctc-ai-chat-live-mockup__body">
										{/* Bot Message */}
										<div className="dctc-ai-mockup-msg-row is-bot">
											{form.show_bot_avatar_in_chat && (
												<div className="dctc-ai-mockup-msg-avatar" style={{ backgroundColor: `${form.primary_color}20`, color: form.primary_color }}>
													{renderBotAvatarIcon(14)}
												</div>
											)}
											<div
												className="dctc-ai-mockup-msg-bubble is-bot"
												style={{ borderRadius: getBubbleRadiusStyle() }}
											>
												{form.greeting_msg || __('Hello! How can I help you today?', 'dragwyb-click-to-chat')}
												{form.show_sources && (
													<div className="dctc-ai-sources" style={{ marginTop: '8px', paddingTop: '6px' }}>
														<span className="dctc-ai-sources__label">
															{__('Sources:', 'dragwyb-click-to-chat')}
														</span>
														<div className="dctc-ai-sources__list">
															<span className="dctc-ai-source-chip">
																<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="dctc-ai-source-chip__icon" aria-hidden="true">
																	<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6" />
																	<polyline points="15 3 21 3 21 9" />
																	<line x1="10" y1="14" x2="21" y2="3" />
																</svg>
																<span className="dctc-ai-source-chip__title">{__('Knowledge Base', 'dragwyb-click-to-chat')}</span>
															</span>
														</div>
													</div>
												)}
											</div>
										</div>

										{/* User Message */}
										<div className="dctc-ai-mockup-msg-row is-user">
											<div
												className="dctc-ai-mockup-msg-bubble is-user"
												style={{
													backgroundColor: form.primary_color,
													borderRadius: getBubbleRadiusStyle(),
												}}
											>
												{__('Can you tell me more about your services?', 'dragwyb-click-to-chat')}
											</div>
											{form.show_user_avatar_in_chat && (
												<div className="dctc-ai-mockup-msg-avatar is-user">
													{renderUserAvatarIcon(14)}
												</div>
											)}
										</div>
									</div>
								</div>

								{/* Floating Launcher Button Simulation */}
								<div className="dctc-ai-preview-launcher-row">
									{form.launcher_text && (
										<div className="dctc-ai-launcher-sim-callout">
											{form.launcher_text}
										</div>
									)}
									<div
										className="dctc-ai-launcher-sim-btn"
										style={{
											width: `${Math.min(Math.max(Number(form.widget_size) || 64, 36), 68)}px`,
											height: `${Math.min(Math.max(Number(form.widget_size) || 64, 36), 68)}px`,
											backgroundColor: form.primary_color || '#6366f1',
										}}
									>
										{renderLauncherIcon()}
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</section>

			{/* 2. Screen Positioning & Custom Coordinates */}
			<section className="dctc-ai-card">
				<header className="dctc-ai-card__header">
					<div className="dctc-ai-card__header-left">
						<div className="dctc-ai-card-icon">
							<span className="dashicons dashicons-location-alt" />
						</div>
						<div>
							<h2 className="dctc-ai-card__title">
								{__('Screen Positioning & Coordinates', 'dragwyb-click-to-chat')}
							</h2>
							<p className="dctc-ai-card__desc">
								{__('Choose the corner anchor or configure custom pixel/percentage offsets.', 'dragwyb-click-to-chat')}
							</p>
						</div>
					</div>
				</header>

				<div className="dctc-ai-card__body">
					<div className="dctc-ai-position-selector" role="radiogroup">
						{[
							{
								value: 'bottom-left',
								label: __('Bottom Left', 'dragwyb-click-to-chat'),
								kind: 'corner',
								cx: 10,
							},
							{
								value: 'bottom-right',
								label: __('Bottom Right (Standard)', 'dragwyb-click-to-chat'),
								kind: 'corner',
								cx: 50,
							},
							{
								value: 'custom',
								label: __('Custom Coordinates', 'dragwyb-click-to-chat'),
								kind: 'custom',
							},
						].map((opt) => (
							<label
								key={opt.value}
								className={`dctc-ai-position-option ${form.position === opt.value ? 'is-active' : ''}`}
							>
								<input
									type="radio"
									name="dctc_ai_widget_position"
									value={opt.value}
									checked={form.position === opt.value}
									onChange={() => setField('position', opt.value)}
								/>
								<span className="dctc-ai-position-card">
									<svg width="60" height="40" viewBox="0 0 60 40" fill="none" aria-hidden="true">
										<rect width="60" height="40" rx="4" fill="#F3F4F6" />
										{opt.kind === 'corner' ? (
											<circle cx={opt.cx} cy="30" r="5" fill="currentColor" />
										) : (
											<>
												<path d="M25 15 L35 15 L30 10 Z" fill="currentColor" />
												<path d="M25 25 L35 25 L30 30 Z" fill="currentColor" />
												<path d="M15 20 L20 25 L20 15 Z" fill="currentColor" />
												<path d="M40 20 L35 25 L35 15 Z" fill="currentColor" />
											</>
										)}
									</svg>
									<span>{opt.label}</span>
								</span>
							</label>
						))}
					</div>

					{form.position === 'custom' && (
						<div className="dctc-ai-custom-position-box">
							<h4 className="dctc-ai-custom-position-box__title">
								{__('Custom Position Coordinates', 'dragwyb-click-to-chat')}
							</h4>
							<div className="dctc-ai-grid-2col">
								<div className="dctc-ai-bot-field">
									<label htmlFor="custom_vertical_align">
										{__('Vertical Anchor', 'dragwyb-click-to-chat')}
									</label>
									<select
										id="custom_vertical_align"
										className="dctc-ai-bot-select"
										value={form.custom_vertical_align}
										onChange={(e) => setField('custom_vertical_align', e.target.value)}
									>
										<option value="bottom">{__('Bottom', 'dragwyb-click-to-chat')}</option>
										<option value="top">{__('Top', 'dragwyb-click-to-chat')}</option>
									</select>

									<label htmlFor="custom_vertical" style={{ marginTop: '0.75rem' }}>
										{__('Vertical Distance Offset', 'dragwyb-click-to-chat')}
									</label>
									<div className="dctc-ai-widget-size-row">
										<input
											type="number"
											id="custom_vertical"
											className="dctc-ai-bot-input dctc-ai-widget-size-input"
											min="0"
											step="1"
											value={form.custom_vertical}
											onChange={(e) =>
												setField(
													'custom_vertical',
													e.target.value === '' ? '' : Number(e.target.value)
												)
											}
										/>
										<select
											className="dctc-ai-bot-select dctc-ai-widget-size-unit"
											value={form.custom_vertical_unit}
											onChange={(e) => setField('custom_vertical_unit', e.target.value)}
										>
											<option value="px">px</option>
											<option value="rem">rem</option>
											<option value="em">em</option>
											<option value="%">%</option>
										</select>
									</div>
								</div>

								<div className="dctc-ai-bot-field">
									<label htmlFor="custom_side">
										{__('Horizontal Anchor', 'dragwyb-click-to-chat')}
									</label>
									<select
										id="custom_side"
										className="dctc-ai-bot-select"
										value={form.custom_side}
										onChange={(e) => setField('custom_side', e.target.value)}
									>
										<option value="right">{__('Right', 'dragwyb-click-to-chat')}</option>
										<option value="left">{__('Left', 'dragwyb-click-to-chat')}</option>
									</select>

									<label htmlFor="custom_horizontal" style={{ marginTop: '0.75rem' }}>
										{__('Horizontal Distance Offset', 'dragwyb-click-to-chat')}
									</label>
									<div className="dctc-ai-widget-size-row">
										<input
											type="number"
											id="custom_horizontal"
											className="dctc-ai-bot-input dctc-ai-widget-size-input"
											min="0"
											step="1"
											value={form.custom_horizontal}
											onChange={(e) =>
												setField(
													'custom_horizontal',
													e.target.value === '' ? '' : Number(e.target.value)
												)
											}
										/>
										<select
											className="dctc-ai-bot-select dctc-ai-widget-size-unit"
											value={form.custom_horizontal_unit}
											onChange={(e) => setField('custom_horizontal_unit', e.target.value)}
										>
											<option value="px">px</option>
											<option value="rem">rem</option>
											<option value="em">em</option>
											<option value="%">%</option>
										</select>
									</div>
								</div>
							</div>
						</div>
					)}
				</div>
			</section>
		</div>
	);
}
