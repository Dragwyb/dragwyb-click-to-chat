import { __, sprintf } from '@wordpress/i18n';
import Toggle from '../../components/Toggle';

export default function ProviderCard({
	id,
	meta,
	isConnected,
	isActive,
	maskedKey,
	keyInputValue,
	onKeyInputChange,
	onResetClick,
	resetting,
	onToggleActive,
	modelsList = {},
	activeModel,
	onModelChange,
}) {
	return (
		<article
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
								onChange={onToggleActive}
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
								onClick={onResetClick}
								disabled={resetting}
							>
								{resetting ? (
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
							value={isConnected ? maskedKey : keyInputValue}
							onChange={(e) => onKeyInputChange(e.target.value)}
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
								value={activeModel || ''}
								onChange={(e) => onModelChange(e.target.value)}
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
}
