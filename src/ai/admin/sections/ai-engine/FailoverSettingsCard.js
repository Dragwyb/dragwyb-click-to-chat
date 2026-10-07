import { __ } from '@wordpress/i18n';
import Toggle from '../../components/Toggle';
import { PROVIDERS, formatProviderLabel } from '../../utils/providers';

export default function FailoverSettingsCard({
	enableFailover,
	onToggleFailover,
	fallbackProvider,
	fallbackModel,
	onFallbackProviderChange,
	onFallbackModelChange,
	modelsList = {},
	hasKey,
	fallbackCandidates = [],
	defaultProvider,
}) {
	return (
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
						checked={enableFailover}
						onChange={onToggleFailover}
						label={__('Enable Failover Chain', 'dragwyb-click-to-chat')}
					/>
				</div>
			</header>

			{enableFailover && (
				<div className="dctc-ai-card__body">
					<div className="dctc-ai-grid-2col">
						<div className="dctc-ai-bot-field">
							<label htmlFor="fallback_provider">
								{__('Backup / Fallback Provider', 'dragwyb-click-to-chat')}
							</label>
							<select
								id="fallback_provider"
								className="dctc-ai-bot-select"
								value={fallbackProvider}
								onChange={(e) => onFallbackProviderChange(e.target.value)}
							>
								<option value="">
									{fallbackCandidates.length > 0
										? __('Auto-select other connected provider', 'dragwyb-click-to-chat')
										: __('None (Connect 2+ providers for failover)', 'dragwyb-click-to-chat')}
								</option>
								{Object.keys(PROVIDERS).map((pId) => {
									if (pId === defaultProvider) return null;
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

						{fallbackProvider && (
							<div className="dctc-ai-bot-field">
								<label htmlFor="fallback_model">
									{__('Backup Model', 'dragwyb-click-to-chat')}
								</label>
								<select
									id="fallback_model"
									className="dctc-ai-bot-select"
									value={fallbackModel}
									onChange={(e) => onFallbackModelChange(e.target.value)}
								>
									{modelsList[fallbackProvider] && Object.keys(modelsList[fallbackProvider]).length > 0 ? (
										Object.entries(modelsList[fallbackProvider]).map(([mId, label]) => (
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
	);
}
