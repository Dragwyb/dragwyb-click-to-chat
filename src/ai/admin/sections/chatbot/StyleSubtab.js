import { useState, useRef, useEffect } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { ColorField, SettingCard } from '../../components';

const LAUNCHER_PRESETS = [
	{ id: 'chat', label: __('💬 Chat Bubble (Default)', 'dragwyb-click-to-chat') },
	{ id: 'bot', label: __('🤖 AI Robot', 'dragwyb-click-to-chat') },
	{ id: 'sparkle', label: __('✨ AI Magic Sparkle', 'dragwyb-click-to-chat') },
	{ id: 'support', label: __('🎧 Support Agent', 'dragwyb-click-to-chat') },
	{ id: 'help', label: __('❓ Help Desk', 'dragwyb-click-to-chat') },
	{ id: 'whatsapp', label: __('📱 WhatsApp Chat', 'dragwyb-click-to-chat') },
];

const STYLE_PRESETS = [
	{
		id: 'modern_indigo',
		label: __('Modern Indigo (Default)', 'dragwyb-click-to-chat'),
		primary: '#6366f1',
		botBg: '#f1f5f9',
		botText: '#0f172a',
		userBg: '#6366f1',
		userText: '#ffffff',
		desc: __('Clean, modern gradient indigo theme for high-converting SaaS & stores.', 'dragwyb-click-to-chat'),
	},
	{
		id: 'dark_slate',
		label: __('Dark Slate / Midnight', 'dragwyb-click-to-chat'),
		primary: '#3b82f6',
		botBg: '#1e293b',
		botText: '#f8fafc',
		userBg: '#3b82f6',
		userText: '#ffffff',
		desc: __('Sleek dark mode aesthetic with midnight slate containers.', 'dragwyb-click-to-chat'),
	},
	{
		id: 'emerald_forest',
		label: __('Emerald Forest', 'dragwyb-click-to-chat'),
		primary: '#10b981',
		botBg: '#f0fdf4',
		botText: '#064e3b',
		userBg: '#10b981',
		userText: '#ffffff',
		desc: __('Fresh emerald green style for eco, health, and finance services.', 'dragwyb-click-to-chat'),
	},
	{
		id: 'ocean_blue',
		label: __('Ocean Blue', 'dragwyb-click-to-chat'),
		primary: '#0284c7',
		botBg: '#f0f9ff',
		botText: '#0c4a6e',
		userBg: '#0284c7',
		userText: '#ffffff',
		desc: __('Trustworthy azure corporate aesthetic with crisp blue accents.', 'dragwyb-click-to-chat'),
	},
	{
		id: 'royal_purple',
		label: __('Royal Purple', 'dragwyb-click-to-chat'),
		primary: '#8b5cf6',
		botBg: '#faf5ff',
		botText: '#4c1d95',
		userBg: '#8b5cf6',
		userText: '#ffffff',
		desc: __('Vibrant luxury vibe tailored for agencies, tech, and creators.', 'dragwyb-click-to-chat'),
	},
	{
		id: 'sunset_amber',
		label: __('Sunset Amber', 'dragwyb-click-to-chat'),
		primary: '#f59e0b',
		botBg: '#fffbeb',
		botText: '#78350f',
		userBg: '#f59e0b',
		userText: '#ffffff',
		desc: __('Warm amber tone ideal for food, hospitality, and creative brands.', 'dragwyb-click-to-chat'),
	},
	{
		id: 'crimson_rose',
		label: __('Crimson Rose', 'dragwyb-click-to-chat'),
		primary: '#e11d48',
		botBg: '#fff1f2',
		botText: '#881337',
		userBg: '#e11d48',
		userText: '#ffffff',
		desc: __('Bold crimson style for energetic sales and boutique shops.', 'dragwyb-click-to-chat'),
	},
	{
		id: 'clean_monochrome',
		label: __('Clean Monochrome', 'dragwyb-click-to-chat'),
		primary: '#18181b',
		botBg: '#f4f4f5',
		botText: '#18181b',
		userBg: '#18181b',
		userText: '#ffffff',
		desc: __('Minimalist monochrome design for luxury, architecture, and portfolios.', 'dragwyb-click-to-chat'),
	},
];

export default function StyleSubtab({
	form,
	setField,
	openBotMedia,
	openUserMedia,
	openAssistantIconMedia,
	openChatBgMedia,
	applyStylePreset,
	renderBotAvatarIcon,
	renderUserAvatarIcon,
	renderLauncherIcon,
	getBubbleRadiusStyle,
	getQuestionRadiusStyle,
}) {
	const [previewInput, setPreviewInput] = useState('');
	const [customMessages, setCustomMessages] = useState([]);
	const [isTyping, setIsTyping] = useState(false);
	const chatBodyRef = useRef(null);

	const scrollToBottom = () => {
		if (chatBodyRef.current) {
			chatBodyRef.current.scrollTop = chatBodyRef.current.scrollHeight;
		}
	};

	useEffect(() => {
		scrollToBottom();
	}, [customMessages, isTyping]);

	const handleSendPreview = (textOverride) => {
		const text = (typeof textOverride === 'string' ? textOverride : previewInput).trim();
		if (!text) {
			return;
		}

		setCustomMessages((prev) => [...prev, { role: 'user', text }]);
		setPreviewInput('');
		setIsTyping(true);

		setTimeout(() => {
			setIsTyping(false);
			const lower = text.toLowerCase();
			let replyText = sprintf(
				__('Thanks for your message! This is a live preview of %s showing your custom visual design.', 'dragwyb-click-to-chat'),
				form.bot_name || 'AI Assistant'
			);

			if (lower.includes('hour') || lower.includes('time') || lower.includes('open') || lower.includes('schedule')) {
				replyText = __('Our support team operates Monday through Friday, 9:00 AM – 6:00 PM EST. Our AI assistant is online 24/7 to assist with your needs!', 'dragwyb-click-to-chat');
			} else if (lower.includes('contact') || lower.includes('support') || lower.includes('human') || lower.includes('agent')) {
				replyText = __('You can reach our team directly at support@example.com or via live chat during working hours.', 'dragwyb-click-to-chat');
			} else if (lower.includes('service') || lower.includes('offer') || lower.includes('pricing') || lower.includes('cost') || lower.includes('plan')) {
				replyText = __('We provide automated 24/7 customer assistance, live chat routing, and instant knowledge base integration.', 'dragwyb-click-to-chat');
			} else if (lower.includes('order') || lower.includes('track')) {
				replyText = __('To check your order status, please provide your Order ID and billing email. I will look it up right away!', 'dragwyb-click-to-chat');
			}

			setCustomMessages((prev) => [
				...prev,
				{
					role: 'bot',
					text: replyText,
				},
			]);
		}, 600);
	};

	const handleResetPreview = () => {
		setCustomMessages([]);
		setPreviewInput('');
		setIsTyping(false);
	};

	const activePreQuestions = [
		form.pre_question_1,
		form.pre_question_2,
		form.pre_question_3,
		form.pre_question_4,
	].filter((q) => q && q.trim());

	const previewPreQuestions = activePreQuestions.length > 0
		? activePreQuestions
		: [
			__('What are your operating hours?', 'dragwyb-click-to-chat'),
			__('How can I contact support?', 'dragwyb-click-to-chat'),
			__('What services do you offer?', 'dragwyb-click-to-chat'),
		];

	const previewActionButtons = Array.isArray(form.action_buttons)
		? form.action_buttons.filter((b) => b && b.label && b.label.trim())
		: [];

	const questionChipRadius = typeof getQuestionRadiusStyle === 'function'
		? getQuestionRadiusStyle()
		: form.pre_questions_border_radius === 'square'
			? '2px'
			: form.pre_questions_border_radius === 'pill'
				? '999px'
				: '8px';
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
								{__('Customize preset themes, launcher, background images, message colors, container borders, and dimensions with instant real-time live preview.', 'dragwyb-click-to-chat')}
							</p>
						</div>
					</div>
				</header>

				<div className="dctc-ai-card__body">
					<div className="dctc-ai-live-customizer-layout">
						{/* Left Column: UI & Styling Controls */}
						<div className="dctc-ai-customizer-controls">
							{/* Group 1: Style Presets */}
							<div className="dctc-ai-customizer-group">
								<div className="dctc-ai-customizer-group__header">
									<span className="dashicons dashicons-layout" />
									<h3 className="dctc-ai-customizer-group__title">
										{__('Style Presets', 'dragwyb-click-to-chat')}
									</h3>
								</div>
								<p className="dctc-ai-customizer-group__desc" style={{ margin: '0 0 12px', fontSize: '12px', color: '#64748b' }}>
									{__('Choose a curated color palette and layout preset, then fine-tune any individual color or dimension below.', 'dragwyb-click-to-chat')}
								</p>

								<div className="dctc-ai-presets-grid" style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(180px, 1fr))', gap: '10px' }}>
									{STYLE_PRESETS.map((preset) => {
										const isSelected = form.style_preset === preset.id;
										return (
											<div
												key={preset.id}
												onClick={() => applyStylePreset(preset.id)}
												className={`dctc-ai-preset-card ${isSelected ? 'is-selected' : ''}`}
												style={{
													border: isSelected ? '2px solid #4f46e5' : '1px solid #e2e8f0',
													borderRadius: '8px',
													padding: '10px',
													cursor: 'pointer',
													background: isSelected ? '#f5f3ff' : '#ffffff',
													transition: 'all 0.15s ease',
													boxShadow: isSelected ? '0 2px 8px rgba(79, 70, 229, 0.15)' : 'none',
												}}
											>
												<div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '6px' }}>
													<strong style={{ fontSize: '12px', color: isSelected ? '#4338ca' : '#1e293b' }}>
														{preset.label.split(' ')[0]} {preset.label.split(' ')[1] || ''}
													</strong>
													{isSelected && (
														<span className="dashicons dashicons-yes-alt" style={{ color: '#4f46e5', fontSize: '16px', width: '16px', height: '16px' }}></span>
													)}
												</div>

												{/* Swatches preview */}
												<div style={{ display: 'flex', gap: '4px', alignItems: 'center', marginBottom: '6px' }}>
													<span style={{ width: '16px', height: '16px', borderRadius: '50%', background: preset.primary, display: 'inline-block', border: '1px solid rgba(0,0,0,0.1)' }} title="Primary" />
													<span style={{ width: '16px', height: '16px', borderRadius: '4px', background: preset.botBg, border: `1px solid ${preset.botText}30`, display: 'inline-block' }} title="Bot Bubble" />
													<span style={{ width: '16px', height: '16px', borderRadius: '4px', background: preset.userBg, display: 'inline-block' }} title="User Bubble" />
												</div>

												<span style={{ fontSize: '11px', color: '#64748b', lineHeight: 1.3, display: '-webkit-box', WebkitLineClamp: 2, WebkitBoxOrient: 'vertical', overflow: 'hidden' }}>
													{preset.desc}
												</span>
											</div>
										);
									})}
								</div>
							</div>

							{/* Group 2: Chat Section Background Image */}
							<div className="dctc-ai-customizer-group">
								<div className="dctc-ai-customizer-group__header">
									<span className="dashicons dashicons-format-image" />
									<h3 className="dctc-ai-customizer-group__title">
										{__('Chat Section Background Image', 'dragwyb-click-to-chat')}
									</h3>
								</div>

								<div className="dctc-ai-bot-field">
									<label>
										{__('Background Pattern / Wallpaper (Optional)', 'dragwyb-click-to-chat')}
									</label>
									<div className="dctc-ai-assistant-icon-row" style={{ alignItems: 'flex-start' }}>
										<div className="dctc-ai-avatar-btn-group">
											<button
												type="button"
												className="dctc-ai-btn dctc-ai-btn-secondary dctc-ai-btn-sm"
												onClick={openChatBgMedia}
											>
												<span className="dashicons dashicons-upload" />
												{form.chat_bg_image
													? __('Change Background Image', 'dragwyb-click-to-chat')
													: __('Upload Background Image', 'dragwyb-click-to-chat')}
											</button>
											{!!form.chat_bg_image && (
												<button
													type="button"
													className="dctc-ai-btn dctc-ai-btn-secondary dctc-ai-btn-sm"
													onClick={() => setField('chat_bg_image', '')}
												>
													{__('Remove Background', 'dragwyb-click-to-chat')}
												</button>
											)}
										</div>

										{form.chat_bg_image ? (
											<div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
												<img
													src={form.chat_bg_image}
													alt=""
													style={{ width: '40px', height: '40px', objectFit: 'cover', borderRadius: '6px', border: '1px solid #e2e8f0' }}
												/>
												<span className="dctc-ai-filename-chip">
													{form.chat_bg_image.split('/').pop()}
												</span>
											</div>
										) : (
											<span className="dctc-ai-filename-chip is-muted">
												{__('No background image (using clean solid color)', 'dragwyb-click-to-chat')}
											</span>
										)}
									</div>
								</div>

								<div className="dctc-ai-grid-2col">
									<div className="dctc-ai-bot-field">
										<label htmlFor="chat_bg_image_url">
											{__('Direct Image URL', 'dragwyb-click-to-chat')}
										</label>
										<input
											type="url"
											id="chat_bg_image_url"
											className="dctc-ai-bot-input"
											placeholder="https://example.com/chat-pattern.png"
											value={form.chat_bg_image}
											onChange={(e) => setField('chat_bg_image', e.target.value)}
										/>
									</div>

									<div className="dctc-ai-bot-field">
										<label htmlFor="chat_bg_opacity">
											{__('Background Opacity (%)', 'dragwyb-click-to-chat')}
										</label>
										<input
											type="number"
											id="chat_bg_opacity"
											className="dctc-ai-bot-input"
											min="5"
											max="100"
											step="5"
											value={form.chat_bg_opacity ?? 100}
											onChange={(e) => setField('chat_bg_opacity', Number(e.target.value))}
										/>
									</div>
								</div>
							</div>

							{/* Group 3: Message Colors & Bubbles */}
							<div className="dctc-ai-customizer-group">
								<div className="dctc-ai-customizer-group__header">
									<span className="dashicons dashicons-color-picker" />
									<h3 className="dctc-ai-customizer-group__title">
										{__('Message Colors & Bubble Styles', 'dragwyb-click-to-chat')}
									</h3>
								</div>

								<div className="dctc-ai-grid-2col">
									<ColorField
										id="primary_color"
										label={__('Brand Primary Color (Header & Accents)', 'dragwyb-click-to-chat')}
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

								<div className="dctc-ai-grid-2col">
									<ColorField
										id="user_msg_bg_color"
										label={__('User Message Background Color', 'dragwyb-click-to-chat')}
										value={form.user_msg_bg_color || form.primary_color}
										onChange={(v) => setField('user_msg_bg_color', v)}
									/>

									<ColorField
										id="user_msg_text_color"
										label={__('User Message Text Color', 'dragwyb-click-to-chat')}
										value={form.user_msg_text_color || '#ffffff'}
										onChange={(v) => setField('user_msg_text_color', v)}
									/>
								</div>

								<div className="dctc-ai-grid-2col">
									<ColorField
										id="bot_msg_bg_color"
										label={__('Bot/Agent Message Background Color', 'dragwyb-click-to-chat')}
										value={form.bot_msg_bg_color || '#f1f5f9'}
										onChange={(v) => setField('bot_msg_bg_color', v)}
									/>

									<ColorField
										id="bot_msg_text_color"
										label={__('Bot/Agent Message Text Color', 'dragwyb-click-to-chat')}
										value={form.bot_msg_text_color || '#0f172a'}
										onChange={(v) => setField('bot_msg_text_color', v)}
									/>
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
									<SettingCard
										id="enable_emoji"
										title={__('Show Emoji Picker in Chat Input', 'dragwyb-click-to-chat')}
										desc={__('Display emoji picker button inside the chat composer for visitors.', 'dragwyb-click-to-chat')}
										checked={form.enable_emoji !== false}
										onChange={(v) => setField('enable_emoji', v)}
									/>
								</div>
							</div>

							{/* Group 4: Container Dimensions & Border Styles */}
							<div className="dctc-ai-customizer-group">
								<div className="dctc-ai-customizer-group__header">
									<span className="dashicons dashicons-screenoptions" />
									<h3 className="dctc-ai-customizer-group__title">
										{__('Chat Container Dimensions & Borders', 'dragwyb-click-to-chat')}
									</h3>
								</div>

								<div className="dctc-ai-grid-2col">
									<div className="dctc-ai-bot-field">
										<label htmlFor="container_width">
											{__('Container Width', 'dragwyb-click-to-chat')}
										</label>
										<div className="dctc-ai-widget-size-row">
											<input
												type="number"
												id="container_width"
												className="dctc-ai-bot-input dctc-ai-widget-size-input"
												min="260"
												max="1200"
												step="10"
												value={form.container_width ?? 380}
												onChange={(e) =>
													setField(
														'container_width',
														e.target.value === '' ? '' : Number(e.target.value)
													)
												}
											/>
											<select
												id="container_width_unit"
												className="dctc-ai-bot-select dctc-ai-widget-size-unit"
												value={form.container_width_unit || 'px'}
												onChange={(e) => setField('container_width_unit', e.target.value)}
											>
												<option value="px">px</option>
												<option value="vw">vw</option>
												<option value="%">%</option>
											</select>
										</div>
									</div>

									<div className="dctc-ai-bot-field">
										<label htmlFor="container_height">
											{__('Container Height', 'dragwyb-click-to-chat')}
										</label>
										<div className="dctc-ai-widget-size-row">
											<input
												type="number"
												id="container_height"
												className="dctc-ai-bot-input dctc-ai-widget-size-input"
												min="320"
												max="1200"
												step="10"
												value={form.container_height ?? 520}
												onChange={(e) =>
													setField(
														'container_height',
														e.target.value === '' ? '' : Number(e.target.value)
													)
												}
											/>
											<select
												id="container_height_unit"
												className="dctc-ai-bot-select dctc-ai-widget-size-unit"
												value={form.container_height_unit || 'px'}
												onChange={(e) => setField('container_height_unit', e.target.value)}
											>
												<option value="px">px</option>
												<option value="vh">vh</option>
												<option value="%">%</option>
											</select>
										</div>
									</div>
								</div>

								<div className="dctc-ai-grid-2col">
									<div className="dctc-ai-bot-field">
										<label htmlFor="container_border_radius">
											{__('Container Border Radius (px)', 'dragwyb-click-to-chat')}
										</label>
										<input
											type="number"
											id="container_border_radius"
											className="dctc-ai-bot-input"
											min="0"
											max="60"
											step="1"
											value={form.container_border_radius ?? 16}
											onChange={(e) =>
												setField(
													'container_border_radius',
													e.target.value === '' ? '' : Number(e.target.value)
												)
											}
										/>
									</div>

									<div className="dctc-ai-bot-field">
										<label htmlFor="container_border_style">
											{__('Container Border Style', 'dragwyb-click-to-chat')}
										</label>
										<select
											id="container_border_style"
											className="dctc-ai-bot-select"
											value={form.container_border_style || 'solid'}
											onChange={(e) => setField('container_border_style', e.target.value)}
										>
											<option value="solid">{__('Solid', 'dragwyb-click-to-chat')}</option>
											<option value="dashed">{__('Dashed', 'dragwyb-click-to-chat')}</option>
											<option value="dotted">{__('Dotted', 'dragwyb-click-to-chat')}</option>
											<option value="double">{__('Double', 'dragwyb-click-to-chat')}</option>
											<option value="none">{__('None', 'dragwyb-click-to-chat')}</option>
										</select>
									</div>
								</div>

								<div className="dctc-ai-grid-2col">
									<div className="dctc-ai-bot-field">
										<label htmlFor="container_border_width">
											{__('Container Border Width (px)', 'dragwyb-click-to-chat')}
										</label>
										<input
											type="number"
											id="container_border_width"
											className="dctc-ai-bot-input"
											min="0"
											max="12"
											step="1"
											value={form.container_border_width ?? 1}
											onChange={(e) =>
												setField(
													'container_border_width',
													e.target.value === '' ? '' : Number(e.target.value)
												)
											}
										/>
									</div>

									<ColorField
										id="container_border_color"
										label={__('Container Border Color', 'dragwyb-click-to-chat')}
										value={form.container_border_color || '#e2e8f0'}
										onChange={(v) => setField('container_border_color', v)}
									/>
								</div>
							</div>

							{/* Group 5: Floating Launcher Button */}
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

							{/* Group 6: Bot Avatar & Identity */}
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

							{/* Group 7: Visitor Avatar (conditionally shown only if show_user_avatar_in_chat is true) */}
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
								{/* Chat Window Simulation with dynamic Container Width, Height, Border Radius, Border Style, and BG Image */}
								<div
									className="dctc-ai-chat-live-mockup"
									style={{
										borderRadius: `${form.container_border_radius ?? 16}px`,
										border: `${form.container_border_width ?? 1}px ${form.container_border_style || 'solid'} ${form.container_border_color || '#e2e8f0'}`,
										boxShadow: '0 12px 36px rgba(15, 23, 42, 0.12)',
										overflow: 'hidden',
										display: 'flex',
										flexDirection: 'column',
									}}
								>
									{/* Top Chat Header - Matching Frontend .dctc-ai-chat-header */}
									<div
										className="dctc-ai-chat-live-mockup__header"
										style={{
											backgroundColor: form.primary_color || '#6366f1',
											background: `linear-gradient(135deg, ${form.primary_color || '#6366f1'} 0%, ${form.primary_color || '#6366f1'}ee 100%)`,
										}}
									>
										<div className="dctc-ai-mockup-avatar">
											{renderBotAvatarIcon(18)}
										</div>
										<div className="dctc-ai-mockup-info">
											<strong className="dctc-ai-mockup-title">{form.bot_name || 'AI Assistant'}</strong>
											<span className="dctc-ai-mockup-status">
												<span className="dctc-ai-mockup-status-dot" />
												{__('Online', 'dragwyb-click-to-chat')}
											</span>
										</div>
										<div className="dctc-ai-mockup-header-actions">
											<button
												type="button"
												className="dctc-ai-mockup-header-btn"
												onClick={handleResetPreview}
												title={__('Reset Live Preview Conversation', 'dragwyb-click-to-chat')}
												aria-label={__('Reset Live Preview Conversation', 'dragwyb-click-to-chat')}
											>
												<span className="dashicons dashicons-trash" />
											</button>
											<button
												type="button"
												className="dctc-ai-mockup-header-btn"
												title={__('Close Preview Window', 'dragwyb-click-to-chat')}
												aria-label={__('Close Preview Window', 'dragwyb-click-to-chat')}
											>
												<span className="dashicons dashicons-no-alt" />
											</button>
										</div>
									</div>

									{/* Messages Stream Body */}
									<div
										ref={chatBodyRef}
										className="dctc-ai-chat-live-mockup__body"
										style={{
											backgroundImage: form.chat_bg_image ? `url(${form.chat_bg_image})` : undefined,
											backgroundSize: 'cover',
											backgroundPosition: 'center',
										}}
									>
										{/* 1. Bot Greeting Message */}
										<div className="dctc-ai-mockup-msg-row is-bot">
											{form.show_bot_avatar_in_chat && (
												<div className="dctc-ai-mockup-msg-avatar" style={{ backgroundColor: `${form.primary_color}20`, color: form.primary_color }}>
													{renderBotAvatarIcon(14)}
												</div>
											)}
											<div
												className="dctc-ai-mockup-msg-bubble is-bot"
												style={{
													backgroundColor: form.bot_msg_bg_color || '#f1f5f9',
													color: form.bot_msg_text_color || '#0f172a',
													borderRadius: getBubbleRadiusStyle(),
												}}
											>
												{form.greeting_msg || __('Hi there! 👋 How can I help you today?', 'dragwyb-click-to-chat')}
											</div>
										</div>

										{/* Suggested Starter Questions - Rendered only if enable_pre_questions is true */}
										{form.enable_pre_questions && (
											<div className="dctc-ai-mockup-suggested-questions">
												{previewPreQuestions.map((q, idx) => (
													<button
														key={idx}
														type="button"
														className="dctc-ai-mockup-question-chip"
														style={{
															backgroundColor: form.pre_questions_bg_color || '#ffffff',
															color: form.pre_questions_text_color || '#475569',
															borderColor: form.pre_questions_border_color || '#e2e8f0',
															borderRadius: questionChipRadius,
														}}
														onClick={() => handleSendPreview(q)}
													>
														{q}
													</button>
												))}
											</div>
										)}

										{/* 2. User Message - Normal Customer Inquiry */}
										<div className="dctc-ai-mockup-msg-row is-user">
											<div
												className="dctc-ai-mockup-msg-bubble is-user"
												style={{
													backgroundColor: form.user_msg_bg_color || form.primary_color,
													color: form.user_msg_text_color || '#ffffff',
													borderRadius: getBubbleRadiusStyle(),
												}}
											>
												{__('Hi! What are your business hours and how do I contact support?', 'dragwyb-click-to-chat')}
											</div>
											{form.show_user_avatar_in_chat && (
												<div className="dctc-ai-mockup-msg-avatar is-user">
													{renderUserAvatarIcon(14)}
												</div>
											)}
										</div>

										{/* 3. Bot Detailed Response Message with Actions & Sources */}
										<div className="dctc-ai-mockup-msg-row is-bot">
											{form.show_bot_avatar_in_chat && (
												<div className="dctc-ai-mockup-msg-avatar" style={{ backgroundColor: `${form.primary_color}20`, color: form.primary_color }}>
													{renderBotAvatarIcon(14)}
												</div>
											)}
											<div
												className="dctc-ai-mockup-msg-bubble is-bot"
												style={{
													backgroundColor: form.bot_msg_bg_color || '#f1f5f9',
													color: form.bot_msg_text_color || '#0f172a',
													borderRadius: getBubbleRadiusStyle(),
												}}
											>
												<div>
													{__('Our support team is available Monday through Friday from 9:00 AM to 6:00 PM EST. However, our AI assistant is online 24/7 to answer questions, guide you through our services, and help with orders.', 'dragwyb-click-to-chat')}
												</div>

												{/* Quick Action Buttons - Rendered only if action_buttons configured */}
												{previewActionButtons.length > 0 && (
													<div className="dctc-ai-mockup-action-buttons">
														{previewActionButtons.map((btn, bIdx) => (
															<span
																key={bIdx}
																className="dctc-ai-mockup-action-btn"
																style={{ borderColor: `${form.primary_color || '#6366f1'}40`, color: form.primary_color || '#4f46e5' }}
																onClick={() => handleSendPreview(btn.label)}
																title={btn.url || ''}
															>
																{btn.label} {btn.url ? '↗' : ''}
															</span>
														))}
													</div>
												)}

												{/* Source Links - Rendered only if show_sources is enabled */}
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
																<span className="dctc-ai-source-chip__title">{__('Knowledge Base: Business Hours & Support', 'dragwyb-click-to-chat')}</span>
															</span>
														</div>
													</div>
												)}
											</div>
										</div>

										{/* 4. Dynamic Messages sent during preview interaction */}
										{customMessages.map((msg, idx) => (
											<div key={idx} className={`dctc-ai-mockup-msg-row is-${msg.role}`}>
												{msg.role === 'bot' && form.show_bot_avatar_in_chat && (
													<div className="dctc-ai-mockup-msg-avatar" style={{ backgroundColor: `${form.primary_color}20`, color: form.primary_color }}>
														{renderBotAvatarIcon(14)}
													</div>
												)}
												<div
													className={`dctc-ai-mockup-msg-bubble is-${msg.role}`}
													style={{
														backgroundColor: msg.role === 'user' ? (form.user_msg_bg_color || form.primary_color) : (form.bot_msg_bg_color || '#f1f5f9'),
														color: msg.role === 'user' ? (form.user_msg_text_color || '#ffffff') : (form.bot_msg_text_color || '#0f172a'),
														borderRadius: getBubbleRadiusStyle(),
													}}
												>
													{msg.text}
												</div>
												{msg.role === 'user' && form.show_user_avatar_in_chat && (
													<div className="dctc-ai-mockup-msg-avatar is-user">
														{renderUserAvatarIcon(14)}
													</div>
												)}
											</div>
										))}

										{/* 5. Typing Indicator */}
										{isTyping && (
											<div className="dctc-ai-mockup-msg-row is-bot">
												{form.show_bot_avatar_in_chat && (
													<div className="dctc-ai-mockup-msg-avatar" style={{ backgroundColor: `${form.primary_color}20`, color: form.primary_color }}>
														{renderBotAvatarIcon(14)}
													</div>
												)}
												<div
													className="dctc-ai-mockup-msg-bubble is-bot dctc-ai-mockup-typing"
													style={{
														backgroundColor: form.bot_msg_bg_color || '#f1f5f9',
														borderRadius: getBubbleRadiusStyle(),
													}}
												>
													<span className="dctc-ai-typing-dot" />
													<span className="dctc-ai-typing-dot" />
													<span className="dctc-ai-typing-dot" />
												</div>
											</div>
										)}
									</div>

									{/* Frontend-Identical Chat Input Field / Composer with Dynamic Setting Checks */}
									<div className="dctc-ai-mockup-composer-container">
										<div className="dctc-ai-composer">
											<div className="dctc-ai-composer-row">
												{/* Plus (+) Button - Only rendered if file uploads are enabled */}
												{form.enable_uploads && (
													<div className="dctc-ai-composer-plus-wrap">
														<button
															type="button"
															className="dctc-ai-composer-plus-btn"
															title={__('Add attachment', 'dragwyb-click-to-chat')}
															aria-label={__('Add attachment', 'dragwyb-click-to-chat')}
															onClick={() => setPreviewInput((prev) => (prev ? `${prev} 📎` : '📎 '))}
														>
															＋
														</button>
													</div>
												)}

												{/* Textarea */}
												<textarea
													className="dctc-ai-composer-textarea"
													placeholder={__('Type your message...', 'dragwyb-click-to-chat')}
													rows={1}
													value={previewInput}
													onChange={(e) => setPreviewInput(e.target.value)}
													onKeyDown={(e) => {
														if (e.key === 'Enter' && !e.shiftKey) {
															e.preventDefault();
															handleSendPreview();
														}
													}}
												/>

												{/* Voice Button - Only rendered if voice input is enabled */}
												{form.enable_voice_input !== false && (
													<button
														type="button"
														className="dctc-ai-composer-voice-btn"
														title={__('Voice input', 'dragwyb-click-to-chat')}
														aria-label={__('Voice input', 'dragwyb-click-to-chat')}
														onClick={() => setPreviewInput((prev) => (prev ? `${prev} 🎙️` : '🎙️ '))}
													>
														🎙️
													</button>
												)}

												{/* Emoji Button - Only rendered if emoji picker is enabled */}
												{form.enable_emoji !== false && (
													<div className="dctc-ai-composer-emoji-wrap">
														<button
															type="button"
															className="dctc-ai-composer-emoji-btn"
															title={__('Insert emoji', 'dragwyb-click-to-chat')}
															aria-label={__('Insert emoji', 'dragwyb-click-to-chat')}
															onClick={() => setPreviewInput((prev) => (prev ? `${prev} 😊` : '😊 '))}
														>
															😊
														</button>
													</div>
												)}

												{/* Send Button */}
												<button
													type="button"
													className="dctc-ai-composer-send-btn"
													style={{ background: form.primary_color || '#6366f1' }}
													onClick={() => handleSendPreview()}
													title={__('Send message', 'dragwyb-click-to-chat')}
													aria-label={__('Send message', 'dragwyb-click-to-chat')}
												>
													<svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true">
														<path
															d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"
															stroke="#ffffff"
															strokeWidth="2.2"
															strokeLinecap="round"
															strokeLinejoin="round"
														/>
													</svg>
												</button>
											</div>
										</div>
									</div>
								</div>

								{/* Floating Launcher Button Simulation */}
								<div className="dctc-ai-preview-launcher-row">
									{form.enable_launcher_text && form.launcher_text && (
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
