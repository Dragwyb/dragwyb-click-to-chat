/**
 * AI Engine & Prompt Settings — Unified single-page customizer for:
 * 1. AI Providers & API Keys (Multi-Provider Card View Grid)
 * 2. Automatic Failover & High Availability (Backup Provider)
 * 3. System Persona & Instructions
 * 4. Model Parameters & Accuracy
 */
import { useState, useEffect } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import ConfirmModal from '../components/ConfirmModal';
import { PROVIDERS, formatProviderLabel } from '../utils/providers';
import {
	ProviderCard,
	FailoverSettingsCard,
	PersonaParametersCard,
} from './ai-engine';

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

	useEffect(() => {
		const handleTriggerSave = () => {
			onSubmit();
		};
		window.addEventListener('dctc_ai_trigger_save', handleTriggerSave);
		return () => {
			window.removeEventListener('dctc_ai_trigger_save', handleTriggerSave);
		};
	}, [form, settings]);

	const onSubmit = async (e) => {
		if (e) e.preventDefault();
		setSaving(true);
		window.dispatchEvent(new CustomEvent('dctc_ai_saving_start'));

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
			console.error('Failed to save settings:', err);
			showNotice(
				err.message || __('Failed to save settings.', 'dragwyb-click-to-chat'),
				'error'
			);
		} finally {
			setSaving(false);
			window.dispatchEvent(new CustomEvent('dctc_ai_saving_end'));
		}
	};

	const resetKey = async (provider) => {
		setResetting((prev) => ({ ...prev, [provider]: true }));
		try {
			const res = await apiFetch({
				path: '/dctc-ai/v1/reset-api-key',
				method: 'POST',
				data: { provider },
			});
			showNotice(
				res.message || __('API key reset successfully', 'dragwyb-click-to-chat')
			);
			onSave({
				api_keys: res.api_keys || settings?.api_keys || {},
				chatbot: {
					...chatbot,
					default_provider: res.default_provider || chatbot.default_provider,
				},
				models: res.models || settings?.models || {},
			});
			if (res.models_list) {
				setModelsList(res.models_list);
			}
			setForm((prev) => ({
				...prev,
				default_provider: res.default_provider || prev.default_provider,
			}));
		} catch (err) {
			showNotice(
				err.message || __('Failed to reset key', 'dragwyb-click-to-chat'),
				'error'
			);
		} finally {
			setResetting((prev) => ({ ...prev, [provider]: false }));
		}
	};

	// Get connected providers list for fallback selection
	const connectedProviderIds = Object.keys(PROVIDERS).filter((id) => hasKey(id));
	const fallbackCandidates = connectedProviderIds.filter((id) => id !== form.default_provider);

	const activeProviderMeta = PROVIDERS[form.default_provider];
	const hasConfiguredKeys = Object.keys(PROVIDERS).some((id) => hasKey(id));

	return (
		<div className="dctc-ai-engine-settings">
			<form onSubmit={onSubmit}>
				{/* SECTION 1: AI Providers & API Keys (Multi-Provider Grid) */}
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
									<ProviderCard
										key={id}
										id={id}
										meta={meta}
										isConnected={isConnected}
										isActive={isActive}
										maskedKey={maskedKey(id)}
										keyInputValue={form[`${id}_key`]}
										onKeyInputChange={(val) => setField(`${id}_key`, val)}
										onResetClick={() => setConfirmProvider(id)}
										resetting={resetting[id]}
										onToggleActive={(checked) => {
											if (checked) {
												setField('default_provider', id);
											} else {
												const other = Object.keys(PROVIDERS).find(
													(p) => p !== id && hasKey(p)
												);
												setField('default_provider', other || '');
											}
										}}
										modelsList={modelsList}
										activeModel={form.models[id]}
										onModelChange={(modelVal) =>
											setForm((prev) => ({
												...prev,
												models: {
													...prev.models,
													[id]: modelVal,
												},
											}))
										}
									/>
								);
							})}
						</div>
					</div>
				</section>

				{/* SECTION 2: Automatic Failover & High Availability */}
				<FailoverSettingsCard
					enableFailover={form.enable_failover}
					onToggleFailover={(val) => setField('enable_failover', val)}
					fallbackProvider={form.fallback_provider}
					fallbackModel={form.fallback_model}
					onFallbackProviderChange={(chosenProvider) => {
						setForm((prev) => ({
							...prev,
							fallback_provider: chosenProvider,
							fallback_model: prev.models[chosenProvider] || '',
						}));
					}}
					onFallbackModelChange={(val) => setField('fallback_model', val)}
					modelsList={modelsList}
					hasKey={hasKey}
					fallbackCandidates={fallbackCandidates}
					defaultProvider={form.default_provider}
				/>

				{/* SECTION 3 & 4: System Persona & Model Parameters */}
				<PersonaParametersCard
					systemPrompt={form.system_prompt}
					onSystemPromptChange={(val) => setField('system_prompt', val)}
					temperature={form.temperature}
					onTemperatureChange={(val) => setField('temperature', val)}
					maxTokens={form.max_tokens}
					onMaxTokensChange={(val) => setField('max_tokens', val)}
				/>
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
