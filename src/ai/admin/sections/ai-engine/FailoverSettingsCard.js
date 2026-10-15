import { __ } from '@wordpress/i18n';
import ProBadge from '../../../../common/components/ProBadge';
import { getProUrl } from '../../utils/providers';

export default function FailoverSettingsCard() {
	return (
		<section className="dctc-pro dctc-ai-card" data-pro-feature="provider-failover">
			<header className="dctc-ai-card__header">
				<div className="dctc-ai-card__header-left">
					<div className="dctc-ai-card-icon" style={{ background: '#f5f3ff', color: '#7c3aed' }}>
						<span className="dashicons dashicons-shield" />
					</div>
					<div>
						<div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
							<h2 className="dctc-ai-card__title" style={{ margin: 0 }}>
								{__('Automatic Failover & High Availability', 'dragwyb-click-to-chat')}
							</h2>
							<ProBadge />
						</div>
						<p className="dctc-ai-card__desc">
							{__('Seamlessly route chat requests to secondary backup providers if your primary AI provider experiences rate limits or downtime.', 'dragwyb-click-to-chat')}
						</p>
					</div>
				</div>
				<div className="dctc-ai-card__header-right">
					<span className="dctc-status-badge" style={{ background: '#f5f3ff', color: '#6d28d9', borderColor: '#ddd6fe', display: 'inline-flex', alignItems: 'center', gap: '4px' }}>
						<span className="dashicons dashicons-lock" style={{ fontSize: '12px', width: '12px', height: '12px' }} />
						{__('Pro Feature', 'dragwyb-click-to-chat')}
					</span>
				</div>
			</header>

			<div className="dctc-ai-card__body">
				<div className="dctc-ai-grid-2col" style={{ opacity: 0.75 }}>
					<div className="dctc-ai-bot-field">
						<label htmlFor="fallback_provider">
							{__('Backup / Fallback Provider', 'dragwyb-click-to-chat')}
						</label>
						<select
							id="fallback_provider"
							className="dctc-ai-bot-select dctc-pro-control"
							disabled={true}
						>
							<option>{__('Anthropic Claude 3.5 (Recommended Backup)', 'dragwyb-click-to-chat')}</option>
							<option>{__('Google Gemini 2.5 Flash', 'dragwyb-click-to-chat')}</option>
							<option>{__('OpenAI GPT-4o Mini', 'dragwyb-click-to-chat')}</option>
							<option>{__('Groq LPU (Ultra-Low Latency)', 'dragwyb-click-to-chat')}</option>
						</select>
						<p className="dctc-ai-bot-hint">
							{__('When your primary provider encounters rate limits (HTTP 429) or timeouts, the chatbot automatically switches to backup without customer disruption.', 'dragwyb-click-to-chat')}
						</p>
					</div>

					<div className="dctc-ai-bot-field">
						<label htmlFor="fallback_model">
							{__('Fallback Routing Strategy', 'dragwyb-click-to-chat')}
						</label>
						<select
							id="fallback_model"
							className="dctc-ai-bot-select dctc-pro-control"
							disabled={true}
						>
							<option>{__('Smart Multi-Tier Chain (Claude -> Gemini -> OpenAI)', 'dragwyb-click-to-chat')}</option>
							<option>{__('Lowest Latency Provider', 'dragwyb-click-to-chat')}</option>
							<option>{__('Strict Secondary Provider', 'dragwyb-click-to-chat')}</option>
						</select>
						<p className="dctc-ai-bot-hint">
							{__('Guarantees 99.99% chatbot uptime during OpenAI or provider outages.', 'dragwyb-click-to-chat')}
						</p>
					</div>
				</div>

				<div style={{ marginTop: '1.25rem', padding: '12px 16px', background: 'linear-gradient(135deg, #fbfaff 0%, #f5f3ff 100%)', border: '1px solid #e9d5ff', borderRadius: '8px', display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap', gap: '12px' }}>
					<div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
						<span className="dashicons dashicons-info" style={{ color: '#7c3aed', fontSize: '18px' }} />
						<span style={{ fontSize: '13px', color: '#4c1d95', fontWeight: '500' }}>
							{__('High Availability Failover is included in Dragwyb Pro add-on.', 'dragwyb-click-to-chat')}
						</span>
					</div>
					<a
						href={getProUrl('ai_engine_failover')}
						target="_blank"
						rel="noopener noreferrer"
						className="dctc-pro-upgrade-btn"
						style={{
							display: 'inline-flex',
							alignItems: 'center',
							gap: '6px',
							background: 'linear-gradient(135deg, #7c3aed 0%, #4f46e5 100%)',
							color: '#fff',
							padding: '6px 14px',
							borderRadius: '6px',
							fontSize: '12px',
							fontWeight: '600',
							textDecoration: 'none',
							boxShadow: '0 2px 4px rgba(124, 58, 237, 0.25)',
						}}
					>
						<span className="dashicons dashicons-star-filled" style={{ fontSize: '14px', width: '14px', height: '14px' }} />
						{__('Unlock Failover in Pro', 'dragwyb-click-to-chat')}
					</a>
				</div>
			</div>
		</section>
	);
}
