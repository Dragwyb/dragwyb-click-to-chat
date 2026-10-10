import { __, sprintf } from '@wordpress/i18n';
import Toggle from '../../components/Toggle';
import ProBadge from '../../../../common/components/ProBadge';
import { getProUrl } from '../../utils/providers';

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
	const isPro = !!meta?.isPro;

	if (isPro) {
		return (
			<article
				className="dctc-pro dctc-ai-provider-card dctc-pro-provider-card"
				data-pro-feature={`provider-${id}`}
			>
				<header className="dctc-ai-provider-card__header">
					<div className="dctc-ai-provider-card__header-left">
						<div className="dctc-ai-provider-icon-box" style={{ background: '#f5f3ff', color: '#7c3aed' }}>
							<span className={`dashicons dashicons-${meta.icon || 'admin-generic'}`} />
						</div>
						<div>
							<div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
								<h3 className="dctc-ai-provider-card__title" style={{ margin: 0 }}>{meta.name}</h3>
								<ProBadge />
							</div>
							<span className="dctc-ai-provider-status-badge" style={{ background: '#f5f3ff', color: '#6d28d9', borderColor: '#ddd6fe' }}>
								<span className="dashicons dashicons-lock" style={{ fontSize: '12px', width: '12px', height: '12px', marginRight: '3px' }} />
								{__('Dragwyb Pro Add-on', 'dragwyb-click-to-chat')}
							</span>
						</div>
					</div>
				</header>

				<div className="dctc-ai-provider-card__body">
					<p className="dctc-ai-bot-hint" style={{ marginTop: 0, marginBottom: '0.875rem' }}>
						{meta.desc}
					</p>

					<div className="dctc-ai-bot-field">
						<label htmlFor={`${id}_key`}>
							{__('API Key', 'dragwyb-click-to-chat')}
						</label>
						<div className="dctc-ai-input-with-icon">
							<span className="dashicons dashicons-lock" style={{ color: '#9ca3af' }} />
							<input
								type="text"
								id={`${id}_key`}
								className="dctc-ai-bot-input dctc-pro-control"
								placeholder={__('Requires Dragwyb Pro Add-on', 'dragwyb-click-to-chat')}
								disabled={true}
								readOnly={true}
							/>
						</div>
					</div>

					<div className="dctc-pro-card-footer" style={{ marginTop: '1rem', paddingTop: '0.875rem', borderTop: '1px solid #f3f4f6', display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
						<span style={{ fontSize: '12px', color: '#6b7280' }}>
							{__('Unlock with Pro Add-on', 'dragwyb-click-to-chat')}
						</span>
						<a
							href={getProUrl(`ai_engine_provider_${id}`)}
							target="_blank"
							rel="noopener noreferrer"
							className="dctc-pro-upgrade-btn"
							style={{
								display: 'inline-flex',
								alignItems: 'center',
								gap: '4px',
								background: 'linear-gradient(135deg, #7c3aed 0%, #4f46e5 100%)',
								color: '#fff',
								padding: '5px 12px',
								borderRadius: '6px',
								fontSize: '12px',
								fontWeight: '600',
								textDecoration: 'none',
								boxShadow: '0 1px 2px rgba(124, 58, 237, 0.2)',
							}}
						>
							<span className="dashicons dashicons-star-filled" style={{ fontSize: '13px', width: '13px', height: '13px' }} />
							{__('Upgrade to Pro', 'dragwyb-click-to-chat')}
						</a>
					</div>
				</div>
			</article>
		);
	}

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
