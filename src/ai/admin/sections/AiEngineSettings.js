/**
 * AI Engine & Prompt Settings — Unified single-page customizer for:
 * 1. AI Providers & API Keys (Multi-Provider Card View Grid)
 * 2. Automatic Failover & High Availability (Backup Provider)
 * 3. System Persona & Instructions
 * 4. Model Parameters & Accuracy
 */
import { useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import ConfirmModal from '../components/ConfirmModal';
import Toggle from '../components/Toggle';
import { PROVIDERS, formatProviderLabel } from '../utils/providers';

const PROMPT_HINT = __(
	'Explain how your chatbot should sound and behave—its tone, what it helps with, and any rules it should follow.',
	'dragwyb-click-to-chat'
);

export default function AiEngineSettings({ settings, onSave, showNotice }) {
	const [saving, setSaving] = useState(false);
	const [resetting, setResetting] = useState({});
	const [confirmProvider, setConfirmProvider] = useState(null);
	const [modelsList, setModelsList] = useState(
		() => window.dctc_ai_data?.models_list || {}
	);

	const chatbot = settings?.chatbot || {};

	const buildForm = () => ({
		// Providers & Models
		openai_key: '',
		google_key: '',
		anthropic_key: '',
		openrouter_key: '',
		groq_key: '',
		deepseek_key: '',
		models: settings?.models || {},
		default_provider: chatbot.default_provider || 'openai',

		// Failover & High Availability
		enable_failover: chatbot.enable_failover !== false,
		fallback_provider: chatbot.fallback_provider || '',
		fallback_model: chatbot.fallback_model || '',

		// System Prompt & Parameters
		system_prompt: chatbot.system_prompt || '',
		temperature: chatbot.temperature ?? 0.7,
		max_tokens: chatbot.max_tokens ?? 500,
	});

	const [form, setForm] = useState(buildForm);
	const [saved, setSaved] = useState(buildForm);
	const dirty = JSON.stringify(form) !== JSON.stringify(saved);

	const hasKey = (id) => !!settings?.api_keys?.[id];
	const maskedKey = (id) => settings?.api_keys?.[id] || '';

	const setField = (key, value) => {
		setForm((prev) => ({ ...prev, [key]: value }));
	};

	const onSubmit = async (e) => {
		e.preventDefault();
		setSaving(true);

		try {
			// 1. Save API keys, models & provider routing
			const apiPayload = {
				openai_key: form.openai_key,
				google_key: form.google_key,
				anthropic_key: form.anthropic_key,
				openrouter_key: form.openrouter_key,
				groq_key: form.groq_key,
				deepseek_key: form.deepseek_key,
				models: form.models,
				default_provider: form.default_provider,
				fallback_provider: form.fallback_provider,
				fallback_model: form.fallback_model,
				enable_failover: form.enable_failover,
			};

			// 2. Save prompt instructions & parameters
			const botPayload = {
				system_prompt: form.system_prompt,
				temperature: form.temperature,
				max_tokens: form.max_tokens,
				default_provider: form.default_provider,
				fallback_provider: form.fallback_provider,
				fallback_model: form.fallback_model,
				enable_failover: form.enable_failover,
			};

			const [resApi, resBot] = await Promise.all([
				apiFetch({
					path: '/dctc-ai/v1/save-settings',
					method: 'POST',
					data: apiPayload,
				}),
				apiFetch({
					path: '/dctc-ai/v1/save-bot-settings',
					method: 'POST',
					data: botPayload,
				}),
			]);

			showNotice(
				__('AI Engine & Prompt settings saved successfully!', 'dragwyb-click-to-chat'),
				'success'
			);

			onSave({
				api_keys: resApi.api_keys || settings?.api_keys || {},
				chatbot: {
					...chatbot,
					...(resBot || {}),
					...(resApi.chatbot || {}),
					...botPayload,
				},
				models: resApi.models || form.models,
			});

			if (resApi.models_list) {
				setModelsList(resApi.models_list);
			}

			const nextSaved = {
				...form,
				openai_key: '',
				google_key: '',
				anthropic_key: '',
				openrouter_key: '',
				groq_key: '',
				deepseek_key: '',
				default_provider: resApi.chatbot?.default_provider || form.default_provider,
				fallback_provider: resApi.chatbot?.fallback_provider || form.fallback_provider,
				fallback_model: resApi.chatbot?.fallback_model || form.fallback_model,
			};

			setForm((prev) => ({
				...prev,
				openai_key: '',
				google_key: '',
				anthropic_key: '',
				openrouter_key: '',
				groq_key: '',
				deepseek_key: '',
				default_provider: resApi.chatbot?.default_provider || prev.default_provider,
				fallback_provider: resApi.chatbot?.fallback_provider || prev.fallback_provider,
				fallback_model: resApi.chatbot?.fallback_model || prev.fallback_model,
			}));
			setSaved(nextSaved);
		} catch (err) {
			let msg = err.message;
			if (err.errors && typeof err.errors === 'object') {
				msg = Object.values(err.errors).join(' | ');
			}
			showNotice(
				msg || __('Failed to save AI engine settings', 'dragwyb-click-to-chat'),
				'error'
			);
		} finally {
			setSaving(false);
		}
	};

	const resetKey = async (provider) => {
		setResetting((prev) => ({ ...prev, [provider]: true }));
		try {
			const res = await apiFetch({
				path: '/dctc-ai/v1/reset-key',
				method: 'POST',
				data: { provider },
			});
			showNotice(
				__('Key reset successfully!', 'dragwyb-click-to-chat'),
				'success'
			);
			const apiKeys = { ...settings?.api_keys };
			delete apiKeys[provider];
			const models = { ...settings?.models };
			delete models[provider];
			onSave({
				api_keys: apiKeys,
				models,
				chatbot: res.chatbot || settings?.chatbot,
			});
			setForm((prev) => {
				const next = {
					...prev,
					[`${provider}_key`]: '',
					default_provider: res.chatbot?.default_provider || '',
					fallback_provider: res.chatbot?.fallback_provider || '',
					fallback_model: res.chatbot?.fallback_model || '',
					models: { ...prev.models, [provider]: '' },
				};
				setSaved(next);
				return next;
			});
		} catch (err) {
			showNotice(
				err.message || __('Failed to reset key', 'dragwyb-click-to-chat'),
				'error'
			);
		} finally {
			setResetting((prev) => ({ ...prev, [provider]: false }));
		}
	};

	const temp = Number(form.temperature);

	// Get connected providers list for fallback selection
	const connectedProviderIds = Object.keys(PROVIDERS).filter((id) => hasKey(id));
	const fallbackCandidates = connectedProviderIds.filter((id) => id !== form.default_provider);

	const activeProviderMeta = PROVIDERS[form.default_provider];
	const hasConfiguredKeys = Object.keys(PROVIDERS).some((id) => hasKey(id));

	return (
		<div className="dctc-ai-engine-settings">
			<header className="dctc-ai-section-header">
				<div className="dctc-ai-section-header__left">
					<div className="dctc-ai-section-header__icon-box">
						<span className="dashicons dashicons-rest-api" />
					</div>
					<div>
						<div className="dctc-ai-section-header__title-row">
							<h1 className="dctc-ai-section-header__title">
								{__('AI Engine & Prompt Configuration', 'dragwyb-click-to-chat')}
							</h1>
							<span className={`dctc-ai-status-pill ${hasConfiguredKeys ? 'is-active' : ''}`}>
								{hasConfiguredKeys
									? sprintf(__('Active: %s', 'dragwyb-click-to-chat'), activeProviderMeta?.name || formatProviderLabel(form.default_provider))
									: __('No Provider Configured', 'dragwyb-click-to-chat')}
							</span>
						</div>
						<p className="dctc-ai-section-header__desc">
							{__('Connect your AI provider API keys, select your primary intelligence model, define system instructions, and configure fallback failover.', 'dragwyb-click-to-chat')}
						</p>
					</div>
				</div>

				<div className="dctc-ai-section-header__right">
					<button
						type="button"
						className="dctc-ai-btn dctc-ai-btn-primary dctc-ai-btn-header-save"
						onClick={onSubmit}
						disabled={saving || !dirty}
					>
						<span className={`dashicons ${saving ? 'dashicons-update spin-anim' : 'dashicons-saved'}`} />
						{saving ? __('Saving…', 'dragwyb-click-to-chat') : dirty ? __('Save Changes', 'dragwyb-click-to-chat') : __('Saved', 'dragwyb-click-to-chat')}
					</button>
				</div>
			</header>

			<form onSubmit={onSubmit}>
				{ /* =========================================================================
				     SECTION 1: AI Providers & API Keys (Multi-Provider Grid)
				   ========================================================================= */ }
				<section className="dctc-ai-card">
					<header className="dctc-ai-card__header">
						<div className="dctc-ai-card__header-left">
							<div className="dctc-ai-card-icon">
								<span className="dashicons dashicons-cloud" />
							</div>
							<div>
								<h2 className="dctc-ai-card__title">
									{__('AI Providers & API Keys', 'dragwyb-click-to-chat')}
								</h2>
								<p className="dctc-ai-card__desc">
									{__('Connect your AI provider API keys, select your primary intelligence model, and choose your active provider.', 'dragwyb-click-to-chat')}
								</p>
							</div>
						</div>
					</header>

					<div className="dctc-ai-card__body">
						<div className="dctc-ai-providers-grid">
							{Object.entries(PROVIDERS).map(([id, meta]) => {
								const isConnected = hasKey(id);
								const isActive = form.default_provider === id;

								return (
									<article
										key={id}
										className={`dctc-ai-provider-card ${isConnected ? 'is-connected' : ''} ${isActive ? 'is-active-provider' : ''}`}
									>
										<header className="dctc-ai-provider-card__header">
											<div className="dctc-ai-provider-card__header-left">
												<div className="dctc-ai-provider-icon-box">
													<span className={`dashicons dashicons-${meta.icon || 'admin-generic'}`} />
												</div>
												<div>
													<h3 className="dctc-ai-provider-card__title">{meta.name}</h3>
													<span className={`dctc-ai-provider-status-badge ${isConnected ? 'is-connected' : 'is-unconfigured'}`}>
														<span className="dctc-ai-status-indicator" />
														{isConnected
															? (isActive ? __('Active Provider', 'dragwyb-click-to-chat') : __('Connected', 'dragwyb-click-to-chat'))
															: __('Not Configured', 'dragwyb-click-to-chat')
														}
													</span>
												</div>
											</div>

											{isConnected && (
												<div className="dctc-ai-provider-card__toggle-wrap">
													<label className="dctc-ai-provider-toggle-label">
														<span className="dctc-ai-provider-toggle-text">
															{isActive ? __('Active', 'dragwyb-click-to-chat') : __('Use as Active', 'dragwyb-click-to-chat')}
														</span>
														<Toggle
															id={`toggle_${id}`}
															checked={isActive}
															onChange={(checked) => {
																if (checked) {
																	setField('default_provider', id);
																} else {
																	const other = Object.keys(PROVIDERS).find(
																		(p) => p !== id && hasKey(p)
																	);
																	setField('default_provider', other || '');
																}
															}}
														/>
													</label>
												</div>
											)}
										</header>

										<div className="dctc-ai-provider-card__body">
											<p className="dctc-ai-bot-hint" style={{ marginTop: 0, marginBottom: '0.75rem' }}>
												{meta.desc}
											</p>

											<div className="dctc-ai-bot-field">
												<div className="dctc-ai-api-field__label-row">
													<label htmlFor={`${id}_key`}>
														{__('API Key', 'dragwyb-click-to-chat')}
													</label>
													{isConnected && (
														<button
															type="button"
															className="dctc-ai-api-btn-reset"
															onClick={() => setConfirmProvider(id)}
															disabled={resetting[id]}
														>
															{resetting[id] ? (
																<span className="dctc-ai-spinner" aria-hidden="true" />
															) : (
																__('Reset Key', 'dragwyb-click-to-chat')
															)}
														</button>
													)}
												</div>

												<div className="dctc-ai-input-with-icon">
													<span className="dashicons dashicons-admin-network" />
													<input
														type="text"
														id={`${id}_key`}
														className={
															'dctc-ai-bot-input' +
															(isConnected ? ' dctc-ai-api-input--masked' : '')
														}
														value={isConnected ? maskedKey(id) : form[`${id}_key`]}
														onChange={(e) => setField(`${id}_key`, e.target.value)}
														placeholder={sprintf(
															/* translators: %s: provider name */
															__('Enter your %s API key', 'dragwyb-click-to-chat'),
															meta.name
														)}
														disabled={isConnected}
													/>
												</div>

												<div className="dctc-ai-api-help-row">
													<a
														href={meta.link}
														className="dctc-ai-api-help-link"
														target="_blank"
														rel="noopener noreferrer"
													>
														<span className="dashicons dashicons-external" aria-hidden="true" />
														{sprintf(
															/* translators: %s: provider name */
															__('Get %s API key', 'dragwyb-click-to-chat'),
															meta.name
														)}
													</a>
												</div>
											</div>

											{isConnected &&
												modelsList[id] &&
												Object.keys(modelsList[id]).length > 0 && (
													<div className="dctc-ai-bot-field" style={{ marginTop: '0.875rem' }}>
														<label htmlFor={`models_${id}`}>
															{__('Active Model', 'dragwyb-click-to-chat')}
														</label>
														<select
															id={`models_${id}`}
															className="dctc-ai-bot-select"
															value={form.models[id] || ''}
															onChange={(e) =>
																setForm((prev) => ({
																	...prev,
																	models: {
																		...prev.models,
																		[id]: e.target.value,
																	},
																}))
															}
														>
															{Object.entries(modelsList[id]).map(
																([modelId, label]) => (
																	<option key={modelId} value={modelId}>
																		{label}
																	</option>
																)
															)}
														</select>
													</div>
												)}
										</div>
									</article>
								);
							})}
						</div>
					</div>
				</section>

				{ /* =========================================================================
				     SECTION 2: Automatic Failover & High Availability
				   ========================================================================= */ }
				<section className="dctc-ai-card">
					<header className="dctc-ai-card__header">
						<div className="dctc-ai-card__header-left">
							<div className="dctc-ai-card-icon">
								<span className="dashicons dashicons-shield" />
							</div>
							<div>
								<h2 className="dctc-ai-card__title">
									{__('Automatic Failover & High Availability', 'dragwyb-click-to-chat')}
								</h2>
								<p className="dctc-ai-card__desc">
									{__('Seamlessly route chat requests to a secondary backup provider if your primary AI provider experiences rate limits or downtime.', 'dragwyb-click-to-chat')}
								</p>
							</div>
						</div>
						<div className="dctc-ai-card__header-right">
							<Toggle
								id="enable_failover"
								checked={form.enable_failover}
								onChange={(val) => setField('enable_failover', val)}
								label={__('Enable Failover Chain', 'dragwyb-click-to-chat')}
							/>
						</div>
					</header>

					{form.enable_failover && (
						<div className="dctc-ai-card__body">
							<div className="dctc-ai-grid-2col">
								<div className="dctc-ai-bot-field">
									<label htmlFor="fallback_provider">
										{__('Backup / Fallback Provider', 'dragwyb-click-to-chat')}
									</label>
									<select
										id="fallback_provider"
										className="dctc-ai-bot-select"
										value={form.fallback_provider}
										onChange={(e) => {
											const chosenProvider = e.target.value;
											setForm((prev) => ({
												...prev,
												fallback_provider: chosenProvider,
												fallback_model: prev.models[chosenProvider] || '',
											}));
										}}
									>
										<option value="">
											{fallbackCandidates.length > 0
												? __('Auto-select other connected provider', 'dragwyb-click-to-chat')
												: __('None (Connect 2+ providers for failover)', 'dragwyb-click-to-chat')}
										</option>
										{Object.keys(PROVIDERS).map((pId) => {
											if (pId === form.default_provider) return null;
											const isConn = hasKey(pId);
											return (
												<option key={pId} value={pId}>
													{formatProviderLabel(pId)} {isConn ? `(${__('Connected', 'dragwyb-click-to-chat')})` : `(${__('Key Missing', 'dragwyb-click-to-chat')})`}
												</option>
											);
										})}
									</select>
									<p className="dctc-ai-bot-hint">
										{__('If the primary provider hits a 429 quota limit or network timeout, the chatbot will automatically query this backup provider.', 'dragwyb-click-to-chat')}
									</p>
								</div>

								{form.fallback_provider && (
									<div className="dctc-ai-bot-field">
										<label htmlFor="fallback_model">
											{__('Backup Model', 'dragwyb-click-to-chat')}
										</label>
										<select
											id="fallback_model"
											className="dctc-ai-bot-select"
											value={form.fallback_model}
											onChange={(e) => setField('fallback_model', e.target.value)}
										>
											{modelsList[form.fallback_provider] && Object.keys(modelsList[form.fallback_provider]).length > 0 ? (
												Object.entries(modelsList[form.fallback_provider]).map(([mId, label]) => (
													<option key={mId} value={mId}>
														{label}
													</option>
												))
											) : (
												<option value="">{__('Default Provider Model', 'dragwyb-click-to-chat')}</option>
											)}
										</select>
										<p className="dctc-ai-bot-hint">
											{__('Select specific model to use when executing backup failover.', 'dragwyb-click-to-chat')}
										</p>
									</div>
								)}
							</div>
						</div>
					)}
				</section>

				{ /* =========================================================================
				     SECTION 3: System Persona & Instructions Card
				   ========================================================================= */ }
				<section className="dctc-ai-card">
					<header className="dctc-ai-card__header">
						<div className="dctc-ai-card__header-left">
							<div className="dctc-ai-card-icon">
								<span className="dashicons dashicons-edit" />
							</div>
							<div>
								<h2 className="dctc-ai-card__title">
									{__('System Persona & Instructions', 'dragwyb-click-to-chat')}
								</h2>
								<p className="dctc-ai-card__desc">
									{__('Define how your AI assistant speaks, behaves, answers questions, and represents your brand.', 'dragwyb-click-to-chat')}
								</p>
							</div>
						</div>
					</header>

					<div className="dctc-ai-card__body">
						<div className="dctc-ai-bot-field">
							<label htmlFor="system_prompt">
								{__('Custom ChatBot System Prompt', 'dragwyb-click-to-chat')}
							</label>
							<textarea
								id="system_prompt"
								className="dctc-ai-bot-input dctc-ai-bot-textarea"
								style={{ minHeight: '120px' }}
								rows="5"
								value={form.system_prompt}
								onChange={(e) => setField('system_prompt', e.target.value)}
								placeholder={__(
									"For example: You are a friendly customer assistant for our website. Answer visitor questions clearly, recommend relevant products, and keep responses concise and helpful.",
									'dragwyb-click-to-chat'
								)}
							/>
							<p className="dctc-ai-bot-hint">{PROMPT_HINT}</p>
						</div>
					</div>
				</section>

				{ /* =========================================================================
				     SECTION 4: Model Parameters & Chat Accuracy Card
				   ========================================================================= */ }
				<section className="dctc-ai-card">
					<header className="dctc-ai-card__header">
						<div className="dctc-ai-card__header-left">
							<div className="dctc-ai-card-icon">
								<span className="dashicons dashicons-admin-settings" />
							</div>
							<div>
								<h2 className="dctc-ai-card__title">
									{__('Model Parameters & Accuracy', 'dragwyb-click-to-chat')}
								</h2>
								<p className="dctc-ai-card__desc">
									{__('Fine-tune AI response creativity, precision, and maximum output length.', 'dragwyb-click-to-chat')}
								</p>
							</div>
						</div>
					</header>

					<div className="dctc-ai-card__body">
						<div className="dctc-ai-grid-2col">
							<div className="dctc-ai-bot-field">
								<div className="dctc-ai-instructions-param__head">
									<label htmlFor="temperature">
										{__('Chat Accuracy / Temperature', 'dragwyb-click-to-chat')}
									</label>
									<span className="dctc-ai-instructions-value-badge">
										{temp.toFixed(1)}
									</span>
								</div>
								<input
									type="range"
									id="temperature"
									className="dctc-ai-instructions-range"
									min="0"
									max="2"
									step="0.1"
									value={form.temperature}
									onChange={(e) => setField('temperature', e.target.value)}
								/>
								<div className="dctc-ai-instructions-range-labels">
									<span>{__('Focused / Precise (0.0)', 'dragwyb-click-to-chat')}</span>
									<span>{__('Balanced (0.7)', 'dragwyb-click-to-chat')}</span>
									<span>{__('Creative (2.0)', 'dragwyb-click-to-chat')}</span>
								</div>
								<p className="dctc-ai-bot-hint">
									{__('Lower values produce precise, predictable responses; higher values encourage creative answers.', 'dragwyb-click-to-chat')}
								</p>
							</div>

							<div className="dctc-ai-bot-field">
								<label htmlFor="max_tokens">
									{__('Max Tokens (Response Length)', 'dragwyb-click-to-chat')}
								</label>
								<input
									type="number"
									id="max_tokens"
									className="dctc-ai-bot-input"
									value={form.max_tokens}
									onChange={(e) => setField('max_tokens', e.target.value)}
									min="100"
									max="8192"
									step="50"
								/>
								<p className="dctc-ai-bot-hint">
									{__('Sets the maximum length limit for chatbot responses in a single message.', 'dragwyb-click-to-chat')}
								</p>
							</div>
						</div>
					</div>
				</section>

				{ /* =========================================================================
				     FORM FOOTER: Save Action Bar
				   ========================================================================= */ }
				<footer className="dctc-ai-form-footer">
					<div className="dctc-ai-form-footer__status">
						{dirty ? (
							<span className="dctc-ai-unsaved-badge">
								<span className="dctc-ai-dot is-warning" />
								{__('Unsaved changes', 'dragwyb-click-to-chat')}
							</span>
						) : (
							<span className="dctc-ai-saved-badge">
								<span className="dctc-ai-dot is-success" />
								{__('All settings saved', 'dragwyb-click-to-chat')}
							</span>
						)}
					</div>

					<button
						type="submit"
						className="dctc-ai-btn dctc-ai-btn-primary"
						disabled={saving || !dirty}
					>
						{saving ? (
							<>
								<span className="dctc-ai-spinner" aria-hidden="true" />
								{__('Saving Settings…', 'dragwyb-click-to-chat')}
							</>
						) : (
							__('Save Settings', 'dragwyb-click-to-chat')
						)}
					</button>
				</footer>
			</form>

			<ConfirmModal
				open={!!confirmProvider}
				title={__('Reset API key', 'dragwyb-click-to-chat')}
				message={sprintf(
					/* translators: %s: provider name */
					__(
						'Are you sure you want to reset the %s API key? This cannot be undone.',
						'dragwyb-click-to-chat'
					),
					PROVIDERS[confirmProvider]?.name || ''
				)}
				confirmLabel={__('Reset Key', 'dragwyb-click-to-chat')}
				busy={resetting[confirmProvider]}
				onCancel={() => setConfirmProvider(null)}
				onConfirm={() => {
					const provider = confirmProvider;
					setConfirmProvider(null);
					resetKey(provider);
				}}
			/>
		</div>
	);
}
