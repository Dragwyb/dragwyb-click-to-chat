/**
 * Display / visibility settings for the frontend widget.
 */
import { useState, useEffect, useRef } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import Toggle from '../components/Toggle';

function SettingCard({ id, title, desc, checked, onChange, icon = null, badge = null }) {
	return (
		<div className={`dctc-ai-bot-setting-card ${checked ? 'is-active' : ''}`}>
			<div className="dctc-ai-bot-setting-card__main">
				{icon && (
					<div className="dctc-ai-card-icon" aria-hidden="true">
						<span className={`dashicons ${icon}`} />
					</div>
				)}
				<div className="dctc-ai-bot-setting-card__text">
					<div className="dctc-ai-bot-setting-card__title-row">
						<strong>{title}</strong>
						{badge && <span className="dctc-ai-mini-badge">{badge}</span>}
					</div>
					<span>{desc}</span>
				</div>
			</div>
			<Toggle id={id} checked={checked} onChange={onChange} />
		</div>
	);
}

const LAUNCHER_PRESETS = [
	{ id: 'chat', label: __('💬 Chat Bubble (Default)', 'dragwyb-click-to-chat'), icon: 'dashicons-format-chat' },
	{ id: 'bot', label: __('🤖 AI Robot', 'dragwyb-click-to-chat'), icon: 'dashicons-superhero-alt' },
	{ id: 'sparkle', label: __('✨ AI Magic Sparkle', 'dragwyb-click-to-chat'), icon: 'dashicons-star-filled' },
	{ id: 'support', label: __('🎧 Support Agent', 'dragwyb-click-to-chat'), icon: 'dashicons-format-audio' },
	{ id: 'help', label: __('❓ Help Desk', 'dragwyb-click-to-chat'), icon: 'dashicons-editor-help' },
	{ id: 'whatsapp', label: __('📱 WhatsApp Chat', 'dragwyb-click-to-chat'), icon: 'dashicons-smartphone' },
];

export default function DisplaySettings({ settings, onSave, showNotice }) {
	const [saving, setSaving] = useState(false);
	const [content, setContent] = useState([]);
	const [loadingContent, setLoadingContent] = useState(false);
	const [search, setSearch] = useState('');
	const [open, setOpen] = useState(false);
	const comboRef = useRef(null);
	const display = settings?.display || {};

	const buildForm = () => ({
		entire_site: !!display.entire_site,
		show_on_mobile: display.show_on_mobile !== false,
		exclude_pages: display.exclude_pages || '',
		position: display.position || 'bottom-right',
		widget_size: display.widget_size ?? 64,
		widget_size_unit: display.widget_size_unit || 'px',
		custom_vertical_align: display.custom_vertical_align || 'bottom',
		custom_vertical: display.custom_vertical ?? 24,
		custom_vertical_unit: display.custom_vertical_unit || 'px',
		custom_side: display.custom_side || 'right',
		custom_horizontal: display.custom_horizontal ?? 24,
		custom_horizontal_unit: display.custom_horizontal_unit || 'px',
		launcher_text: display.launcher_text || '',
		assistant_icon: display.assistant_icon || '',
		launcher_icon_preset: display.launcher_icon_preset || 'chat',
		trigger_type: display.trigger_type || 'click',
		trigger_delay: display.trigger_delay ?? 5,
		time_delay: display.time_delay ?? 0,
	});

	const [form, setForm] = useState(buildForm);
	const [saved, setSaved] = useState(buildForm);
	const dirty = JSON.stringify(form) !== JSON.stringify(saved);

	useEffect(() => {
		let alive = true;
		setLoadingContent(true);
		apiFetch({ path: '/dctc-ai/v1/all-content' })
			.then((items) => {
				if (alive && Array.isArray(items)) {
					setContent(items);
				}
			})
			.finally(() => {
				if (alive) {
					setLoadingContent(false);
				}
			});
		return () => {
			alive = false;
		};
	}, []);

	useEffect(() => {
		const onDown = (e) => {
			if (comboRef.current && !comboRef.current.contains(e.target)) {
				setOpen(false);
			}
		};
		document.addEventListener('mousedown', onDown);
		return () => document.removeEventListener('mousedown', onDown);
	}, []);

	const setField = (key, value) => {
		setForm((prev) => ({ ...prev, [key]: value }));
	};

	const selectedIds = () =>
		form.exclude_pages
			? form.exclude_pages
				.split(',')
				.map((s) => s.trim())
				.filter(Boolean)
			: [];

	const addExclude = (raw) => {
		const id = (raw || search).trim();
		if (!id) {
			return;
		}
		const ids = selectedIds();
		if (!ids.includes(id)) {
			setField('exclude_pages', [...ids, id].join(', '));
		}
		setSearch('');
		setOpen(false);
	};

	const toggleExclude = (id) => {
		const ids = selectedIds();
		const next = ids.includes(id)
			? ids.filter((x) => x !== id)
			: [...ids, id];
		setField('exclude_pages', next.join(', '));
	};

	const ids = selectedIds();
	const filtered = content.filter((item) => {
		if (!search.trim()) {
			return true;
		}
		const q = search.toLowerCase();
		return (
			item.title.toLowerCase().includes(q) ||
			String(item.id).includes(q) ||
			item.type.toLowerCase().includes(q)
		);
	});

	const openAssistantIconMedia = () => {
		if (!window.wp?.media) {
			showNotice(
				__('WordPress media modal is not available.', 'dragwyb-click-to-chat'),
				'error'
			);
			return;
		}
		const frame = window.wp.media({
			title: __('Select Assistant Icon', 'dragwyb-click-to-chat'),
			button: { text: __('Use as Icon', 'dragwyb-click-to-chat') },
			library: { type: 'image' },
			multiple: false,
		});
		frame.on('select', () => {
			const attachment = frame.state().get('selection').first().toJSON();
			setField('assistant_icon', attachment.url);
		});
		frame.open();
	};

	const onSubmit = async (e) => {
		if (e) e.preventDefault();
		setSaving(true);
		try {
			await apiFetch({
				path: '/dctc-ai/v1/save-display-settings',
				method: 'POST',
				data: form,
			});
			onSave({ display: { ...display, ...form } });
			setSaved(form);
			showNotice(
				__('Display settings saved successfully!', 'dragwyb-click-to-chat')
			);
		} catch (err) {
			showNotice(
				err.message || __('Failed to save settings', 'dragwyb-click-to-chat'),
				'error'
			);
		} finally {
			setSaving(false);
		}
	};

	useEffect(() => {
		const handleTriggerSave = () => {
			onSubmit();
		};
		window.addEventListener('dctc_ai_trigger_save', handleTriggerSave);
		return () => {
			window.removeEventListener('dctc_ai_trigger_save', handleTriggerSave);
		};
	}, [form, display]);

	const renderLauncherIcon = () => {
		if (form.assistant_icon) {
			return <img src={form.assistant_icon} alt="" style={{ width: '100%', height: '100%', objectFit: 'cover', borderRadius: '50%' }} />;
		}
		switch (form.launcher_icon_preset) {
			case 'bot':
				return <span className="dashicons dashicons-superhero-alt" style={{ fontSize: '26px' }} />;
			case 'sparkle':
				return <span className="dashicons dashicons-star-filled" style={{ fontSize: '26px' }} />;
			case 'support':
				return <span className="dashicons dashicons-format-audio" style={{ fontSize: '26px' }} />;
			case 'help':
				return <span className="dashicons dashicons-editor-help" style={{ fontSize: '26px' }} />;
			case 'whatsapp':
				return <span className="dashicons dashicons-smartphone" style={{ fontSize: '26px' }} />;
			case 'chat':
			default:
				return <span className="dashicons dashicons-format-chat" style={{ fontSize: '26px' }} />;
		}
	};

	return (
		<div className="dctc-ai-display-settings">
			<form onSubmit={onSubmit}>
				{ /* Master Activation Hero Card */}
				<section className="dctc-ai-card dctc-ai-hero-card">
					<div className="dctc-ai-hero-card__body">
						<div className="dctc-ai-hero-card__left">
							<div className="dctc-ai-card-icon dctc-ai-card-icon--hero">
								<span className="dashicons dashicons-desktop" />
							</div>
							<div>
								<div className="dctc-ai-header-with-badge">
									<h2 className="dctc-ai-hero-card__title">
										{__('Site-Wide Chatbot Widget', 'dragwyb-click-to-chat')}
									</h2>
									<span className={`dctc-ai-status-pill ${form.entire_site ? 'is-active' : 'is-inactive'}`}>
										{form.entire_site ? __('Active & Live', 'dragwyb-click-to-chat') : __('Disabled', 'dragwyb-click-to-chat')}
									</span>
								</div>
								<p className="dctc-ai-hero-card__desc">
									{__('Activate the AI assistant button across your entire public website.', 'dragwyb-click-to-chat')}
								</p>
							</div>
						</div>
						<div className="dctc-ai-hero-card__right">
							<Toggle
								id="entire_site"
								checked={form.entire_site}
								onChange={(v) => setField('entire_site', v)}
							/>
						</div>
					</div>
				</section>

				{ /* Visibility & Page Targeting */}
				<section className="dctc-ai-card">
					<header className="dctc-ai-card__header">
						<div className="dctc-ai-card__header-left">
							<div className="dctc-ai-card-icon">
								<span className="dashicons dashicons-visibility" />
							</div>
							<div>
								<h2 className="dctc-ai-card__title">
									{__('Visibility & Targeting Rules', 'dragwyb-click-to-chat')}
								</h2>
								<p className="dctc-ai-card__desc">
									{__('Configure button label text, mobile support, and pages to exclude.', 'dragwyb-click-to-chat')}
								</p>
							</div>
						</div>
					</header>

					<div className="dctc-ai-card__body">
						<div className="dctc-ai-grid-2col">
							<div className="dctc-ai-bot-field">
								<label htmlFor="launcher_text">
									{__('Chat Button Call-to-Action Label', 'dragwyb-click-to-chat')}
								</label>
								<div className="dctc-ai-input-with-icon">
									<span className="dashicons dashicons-testimonial" />
									<input
										type="text"
										id="launcher_text"
										className="dctc-ai-bot-input"
										value={form.launcher_text}
										onChange={(e) => setField('launcher_text', e.target.value)}
										placeholder={__('How can we help?', 'dragwyb-click-to-chat')}
									/>
								</div>
								<p className="dctc-ai-bot-hint">
									{__('Optional text badge displayed next to the floating launcher icon.', 'dragwyb-click-to-chat')}
								</p>
							</div>

							<div className="dctc-ai-features-grid">
								<SettingCard
									id="show_on_mobile"
									title={__('Show on Mobile Devices', 'dragwyb-click-to-chat')}
									desc={__('Display optimized compact chat launcher on smartphones and tablets.', 'dragwyb-click-to-chat')}
									icon="dashicons-smartphone"
									checked={form.show_on_mobile}
									onChange={(v) => setField('show_on_mobile', v)}
								/>
							</div>
						</div>

						{ /* Page Exclusions Combobox */}
						<div className="dctc-ai-bot-field" style={{ marginTop: '1.25rem' }}>
							<label htmlFor="exclude_search_input">
								{__('Exclude Specific Pages & Posts', 'dragwyb-click-to-chat')}
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
											setOpen(true);
										}}
										onFocus={() => setOpen(true)}
										onKeyDown={(e) => {
											if (e.key === 'Enter') {
												e.preventDefault();
												addExclude();
											}
										}}
										placeholder={
											loadingContent
												? __('Loading website content…', 'dragwyb-click-to-chat')
												: __('Search pages, posts, or custom post types by title or ID…', 'dragwyb-click-to-chat')
										}
									/>
									<span className="dctc-ai-combobox-arrow">▼</span>
								</div>

								{open && (
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
													__('No pages or posts available.', 'dragwyb-click-to-chat')
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
										<span className="dctc-ai-no-tokens">
											{__('No pages currently excluded (active everywhere).', 'dragwyb-click-to-chat')}
										</span>
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
				</section>

				{ /* Trigger & Auto-Open Rules */}
				<section className="dctc-ai-card">
					<header className="dctc-ai-card__header">
						<div className="dctc-ai-card__header-left">
							<div className="dctc-ai-card-icon">
								<span className="dashicons dashicons-clock" />
							</div>
							<div>
								<h2 className="dctc-ai-card__title">
									{__('Trigger & Timing Behaviors', 'dragwyb-click-to-chat')}
								</h2>
								<p className="dctc-ai-card__desc">
									{__('Control when the floating button appears and when the chat automatically opens.', 'dragwyb-click-to-chat')}
								</p>
							</div>
						</div>
					</header>

					<div className="dctc-ai-card__body">
						<div className="dctc-ai-grid-2col">
							<div className="dctc-ai-bot-field">
								<label htmlFor="trigger_type">
									{__('Chat Window Auto-Open Mode', 'dragwyb-click-to-chat')}
								</label>
								<select
									id="trigger_type"
									className="dctc-ai-bot-select"
									value={form.trigger_type}
									onChange={(e) => setField('trigger_type', e.target.value)}
								>
									<option value="click">{__('Wait for Visitor Click (Default)', 'dragwyb-click-to-chat')}</option>
									<option value="delay">{__('Auto-Open After Page Load Delay', 'dragwyb-click-to-chat')}</option>
								</select>
								<p className="dctc-ai-bot-hint">
									{__('Choose whether to automatically pop up the chat window on visit.', 'dragwyb-click-to-chat')}
								</p>
							</div>

							{form.trigger_type === 'delay' && (
								<div className="dctc-ai-bot-field">
									<label htmlFor="trigger_delay">
										{__('Auto-Open Delay (Seconds)', 'dragwyb-click-to-chat')}
									</label>
									<div className="dctc-ai-inline-input-row">
										<input
											type="number"
											id="trigger_delay"
											className="dctc-ai-bot-input dctc-ai-input--narrow"
											min="1"
											max="60"
											value={form.trigger_delay}
											onChange={(e) => setField('trigger_delay', parseInt(e.target.value, 10) || 5)}
										/>
										<span className="dctc-ai-input-unit-label">{__('seconds', 'dragwyb-click-to-chat')}</span>
									</div>
									<p className="dctc-ai-bot-hint">
										{__('Seconds to wait after page load before opening chat modal.', 'dragwyb-click-to-chat')}
									</p>
								</div>
							)}

							<div className="dctc-ai-bot-field">
								<label htmlFor="time_delay">
									{__('Floating Launcher Appearance Delay', 'dragwyb-click-to-chat')}
								</label>
								<div className="dctc-ai-inline-input-row">
									<input
										type="number"
										id="time_delay"
										className="dctc-ai-bot-input dctc-ai-input--narrow"
										min="0"
										max="60"
										step="1"
										value={form.time_delay}
										onChange={(e) =>
											setField(
												'time_delay',
												e.target.value === '' ? '' : Number(e.target.value)
											)
										}
									/>
									<span className="dctc-ai-input-unit-label">{__('seconds', 'dragwyb-click-to-chat')}</span>
								</div>
								<p className="dctc-ai-bot-hint">
									{__('Set 0 for immediate display. 2–4s delay gives smoother page load experience.', 'dragwyb-click-to-chat')}
								</p>
							</div>
						</div>
					</div>
				</section>

				{ /* Button Icon & Appearance */}
				<section className="dctc-ai-card">
					<header className="dctc-ai-card__header">
						<div className="dctc-ai-card__header-left">
							<div className="dctc-ai-card-icon">
								<span className="dashicons dashicons-art" />
							</div>
							<div>
								<h2 className="dctc-ai-card__title">
									{__('Launcher Button Design & Icon', 'dragwyb-click-to-chat')}
								</h2>
								<p className="dctc-ai-card__desc">
									{__('Select preset icon styles or upload a custom button graphic.', 'dragwyb-click-to-chat')}
								</p>
							</div>
						</div>
					</header>

					<div className="dctc-ai-card__body">
						<div className="dctc-ai-grid-2col">
							<div className="dctc-ai-form-stack">
								<div className="dctc-ai-bot-field">
									<label htmlFor="launcher_icon_preset">
										{__('Launcher Preset Icon', 'dragwyb-click-to-chat')}
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
										{__('Floating Button Size', 'dragwyb-click-to-chat')}
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
									<p className="dctc-ai-bot-hint">
										{__('Default: 64px. Controls the width and height of the circular button.', 'dragwyb-click-to-chat')}
									</p>
								</div>

								{ /* Custom Assistant Icon */}
								<div className="dctc-ai-bot-field">
									<label>
										{__('Custom Uploaded Button Icon (Optional)', 'dragwyb-click-to-chat')}
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

							{ /* Live Launcher Preview */}
							<div className="dctc-ai-preview-box-col">
								<div className="dctc-ai-preview-box-title">
									{__('Live Button Simulation', 'dragwyb-click-to-chat')}
								</div>
								<div className="dctc-ai-launcher-preview-wrapper">
									<div className="dctc-ai-launcher-sim-badge">
										{form.launcher_text && (
											<div className="dctc-ai-launcher-sim-callout">
												{form.launcher_text}
											</div>
										)}
										<div
											className="dctc-ai-launcher-sim-btn"
											style={{
												width: `${Math.min(Math.max(Number(form.widget_size) || 64, 40), 80)}px`,
												height: `${Math.min(Math.max(Number(form.widget_size) || 64, 40), 80)}px`,
												backgroundColor: settings?.chatbot?.primary_color || '#6366f1',
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

				{ /* Screen Positioning & Custom Coordinates */}
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
									{__('Choose where the floating widget is fixed on the visitor screen.', 'dragwyb-click-to-chat')}
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
			</form>
		</div>
	);
}
