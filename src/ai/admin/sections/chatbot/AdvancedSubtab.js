/**
 * Advanced Subtab: Triggers, Security, Rate Limits, File Uploads, Logs, Lead Capture, Tools & Automation
 */
import { __, sprintf } from '@wordpress/i18n';
import { SwitcherCard, SettingCard, Toggle } from '../../components';

export default function AdvancedSubtab({
	form,
	setField,
	usageStats,
	isWcActive,
}) {
	return (
		<div className="dctc-ai-tab-panel-section">
			{/* 1. Triggers & Auto-Open Timing */}
			<section className="dctc-ai-card">
				<header className="dctc-ai-card__header">
					<div className="dctc-ai-card__header-left">
						<div className="dctc-ai-card-icon">
							<span className="dashicons dashicons-clock" />
						</div>
						<div>
							<h2 className="dctc-ai-card__title">
								{__('Trigger & Auto-Open Behaviors', 'dragwyb-click-to-chat')}
							</h2>
							<p className="dctc-ai-card__desc">
								{__('Control when the floating button appears and when the chat modal pops open.', 'dragwyb-click-to-chat')}
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
								{__('Floating Button Appearance Delay', 'dragwyb-click-to-chat')}
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
								{__('Set 0 for immediate display. 2–4s gives a smoother page load feel.', 'dragwyb-click-to-chat')}
							</p>
						</div>
					</div>
				</div>
			</section>

			{/* 2. Security & Rate Limiting */}
			<section className="dctc-ai-card">
				<header className="dctc-ai-card__header">
					<div className="dctc-ai-card__header-left">
						<div className="dctc-ai-card-icon">
							<span className="dashicons dashicons-shield" />
						</div>
						<div>
							<h2 className="dctc-ai-card__title">
								{__('Security & Rate Limiting', 'dragwyb-click-to-chat')}
							</h2>
							<p className="dctc-ai-card__desc">
								{__('Throttle requests per visitor IP to protect your AI API billing.', 'dragwyb-click-to-chat')}
							</p>
						</div>
					</div>
				</header>

				<div className="dctc-ai-card__body">
					<div className="dctc-ai-bot-field">
						<label htmlFor="rate_limit_per_minute">
							{__('Rate Limit (Requests per minute per visitor)', 'dragwyb-click-to-chat')}
						</label>
						<div className="dctc-ai-inline-input-row">
							<input
								type="number"
								id="rate_limit_per_minute"
								className="dctc-ai-bot-input dctc-ai-input--narrow"
								min="1"
								max="300"
								value={form.rate_limit_per_minute}
								onChange={(e) =>
									setField('rate_limit_per_minute', parseInt(e.target.value, 10) || 1)
								}
							/>
							<span className="dctc-ai-input-unit-label">
								{__('requests / minute', 'dragwyb-click-to-chat')}
							</span>
						</div>
						<p className="dctc-ai-bot-hint">
							{__('Recommended: 15–30 requests/min. Blocks aggressive automated scripts.', 'dragwyb-click-to-chat')}
						</p>
					</div>
				</div>
			</section>

			{/* 3. AI Usage & Cost Controls (Conditional Switcher Card) */}
			<SwitcherCard
				id="enable_usage_limits"
				title={__('AI Usage & Cost Controls', 'dragwyb-click-to-chat')}
				desc={__('Set visitor daily message limits and monthly budget ceilings to prevent unexpected AI API costs.', 'dragwyb-click-to-chat')}
				icon="dashicons-chart-pie"
				badge={__('Cost Protection', 'dragwyb-click-to-chat')}
				checked={form.enable_usage_limits}
				onChange={(v) => setField('enable_usage_limits', v)}
				disabledNotice={__('Enable usage controls to enforce daily per-visitor limits and monthly budget ceilings.', 'dragwyb-click-to-chat')}
			>
				{usageStats && (
					<div className="dctc-ai-usage-meter-card" style={{ marginBottom: '1.25rem', padding: '1rem', background: '#f8fafc', borderRadius: '8px', border: '1px solid #e2e8f0' }}>
						<div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '0.5rem' }}>
							<span style={{ fontWeight: 600, fontSize: '0.9rem', color: '#1e293b' }}>
								{__('Monthly Request Usage', 'dragwyb-click-to-chat')}
							</span>
							<span style={{ fontSize: '0.85rem', color: '#64748b' }}>
								{sprintf(
									/* translators: 1: Current requests, 2: Budget */
									__('%1$s / %2$s requests (%3$s%%)', 'dragwyb-click-to-chat'),
									Number(usageStats.month_requests || 0).toLocaleString(),
									Number(form.monthly_request_budget || 0).toLocaleString(),
									usageStats.budget_percent || 0
								)}
							</span>
						</div>
						<div style={{ width: '100%', height: '8px', background: '#e2e8f0', borderRadius: '4px', overflow: 'hidden' }}>
							<div
								style={{
									width: `${Math.min(100, usageStats.budget_percent || 0)}%`,
									height: '100%',
									background: (usageStats.budget_percent || 0) > 85 ? '#ef4444' : (usageStats.budget_percent || 0) > 60 ? '#f59e0b' : '#10b981',
									transition: 'width 0.3s ease',
								}}
							/>
						</div>
						<div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '0.78rem', color: '#64748b', marginTop: '0.5rem' }}>
							<span>{sprintf(__('Today: %d requests', 'dragwyb-click-to-chat'), usageStats.today_requests || 0)}</span>
							<span>{sprintf(__('Est. Tokens this month: %s', 'dragwyb-click-to-chat'), Number(usageStats.month_tokens || 0).toLocaleString())}</span>
						</div>
					</div>
				)}

				<div className="dctc-ai-grid-2col">
					<div className="dctc-ai-bot-field">
						<label htmlFor="visitor_daily_message_limit">
							{__('Daily Message Limit per Visitor', 'dragwyb-click-to-chat')}
						</label>
						<div className="dctc-ai-inline-input-row">
							<input
								type="number"
								id="visitor_daily_message_limit"
								className="dctc-ai-bot-input dctc-ai-input--narrow"
								min="1"
								max="500"
								value={form.visitor_daily_message_limit}
								onChange={(e) =>
									setField('visitor_daily_message_limit', parseInt(e.target.value, 10) || 50)
								}
							/>
							<span className="dctc-ai-input-unit-label">
								{__('messages / day', 'dragwyb-click-to-chat')}
							</span>
						</div>
						<p className="dctc-ai-bot-hint">
							{__('Prevents a single user from running excessive queries in 24 hours. Recommended: 30–50.', 'dragwyb-click-to-chat')}
						</p>
					</div>

					<div className="dctc-ai-bot-field">
						<label htmlFor="monthly_request_budget">
							{__('Monthly Request Budget Ceiling', 'dragwyb-click-to-chat')}
						</label>
						<div className="dctc-ai-inline-input-row">
							<input
								type="number"
								id="monthly_request_budget"
								className="dctc-ai-bot-input dctc-ai-input--narrow"
								min="0"
								max="1000000"
								step="1"
								value={form.monthly_request_budget}
								onChange={(e) =>
									setField('monthly_request_budget', e.target.value === '' ? '' : Math.max(0, parseInt(e.target.value, 10) || 0))
								}
							/>
							<span className="dctc-ai-input-unit-label">
								{__('requests / month', 'dragwyb-click-to-chat')}
							</span>
						</div>
						<p className="dctc-ai-bot-hint">
							{__('Total AI requests permitted for the whole website per month. 0 = unlimited.', 'dragwyb-click-to-chat')}
						</p>
					</div>
				</div>

				<div className="dctc-ai-features-grid" style={{ marginTop: '1.25rem', paddingTop: '1.25rem', borderTop: '1px solid #f1f5f9' }}>
					<SettingCard
						id="enable_budget_email_alerts"
						title={__('Send Budget Threshold Email Alerts', 'dragwyb-click-to-chat')}
						desc={__('Receive an automated email alert when your monthly AI budget hits 80% and 100% capacity.', 'dragwyb-click-to-chat')}
						icon="dashicons-email"
						badge={__('Proactive', 'dragwyb-click-to-chat')}
						checked={!!form.enable_budget_email_alerts}
						onChange={(v) => setField('enable_budget_email_alerts', v)}
					/>

					{form.enable_budget_email_alerts && (
						<div className="dctc-ai-bot-field" style={{ marginTop: '0.75rem' }}>
							<label htmlFor="alert_email">
								{__('Alert Notification Email (Optional)', 'dragwyb-click-to-chat')}
							</label>
							<input
								type="email"
								id="alert_email"
								className="dctc-ai-bot-input"
								value={form.alert_email}
								onChange={(e) => setField('alert_email', e.target.value)}
								placeholder={__('Leave blank to use WordPress admin email', 'dragwyb-click-to-chat')}
							/>
						</div>
					)}
				</div>
			</SwitcherCard>

			{/* 4. AI Lead Capture & CRM Webhook (Conditional Switcher Card) */}
			<SwitcherCard
				id="enable_lead_capture"
				title={__('AI Lead Capture & CRM Integration', 'dragwyb-click-to-chat')}
				desc={__('Capture high-intent prospective visitor contacts directly in chat, send instant email alerts, and sync with CRM webhooks.', 'dragwyb-click-to-chat')}
				icon="dashicons-id"
				badge={__('Lead Generation', 'dragwyb-click-to-chat')}
				checked={form.enable_lead_capture}
				onChange={(v) => setField('enable_lead_capture', v)}
				disabledNotice={__('Enable lead capture to collect contact inquiries, notify sales via email, and sync with webhooks.', 'dragwyb-click-to-chat')}
			>
				<div className="dctc-ai-grid-2col">
					<div className="dctc-ai-bot-field">
						<label htmlFor="lead_trigger_type">
							{__('Lead Capture Trigger Mode', 'dragwyb-click-to-chat')}
						</label>
						<select
							id="lead_trigger_type"
							className="dctc-ai-bot-select"
							value={form.lead_trigger_type}
							onChange={(e) => setField('lead_trigger_type', e.target.value)}
						>
							<option value="manual">{__('Manual Button in Chat Header / Menu', 'dragwyb-click-to-chat')}</option>
							<option value="time_delay">{__('Auto-Prompt After Time Delay', 'dragwyb-click-to-chat')}</option>
							<option value="message_count">{__('Auto-Prompt After Message Count', 'dragwyb-click-to-chat')}</option>
							<option value="intent">{__('Auto-Prompt on AI Purchase / Contact Intent', 'dragwyb-click-to-chat')}</option>
						</select>
						<p className="dctc-ai-bot-hint">
							{__('Decide when the lead capture card should be presented to visitors.', 'dragwyb-click-to-chat')}
						</p>
					</div>

					{form.lead_trigger_type === 'time_delay' && (
						<div className="dctc-ai-bot-field">
							<label htmlFor="lead_trigger_delay">
								{__('Trigger Time Delay (Seconds)', 'dragwyb-click-to-chat')}
							</label>
							<div className="dctc-ai-inline-input-row">
								<input
									type="number"
									id="lead_trigger_delay"
									className="dctc-ai-bot-input dctc-ai-input--narrow"
									min="5"
									max="300"
									value={form.lead_trigger_delay}
									onChange={(e) =>
										setField('lead_trigger_delay', parseInt(e.target.value, 10) || 30)
									}
								/>
								<span className="dctc-ai-input-unit-label">{__('seconds', 'dragwyb-click-to-chat')}</span>
							</div>
						</div>
					)}

					{form.lead_trigger_type === 'message_count' && (
						<div className="dctc-ai-bot-field">
							<label htmlFor="lead_trigger_message_count">
								{__('Trigger After Visitor Messages', 'dragwyb-click-to-chat')}
							</label>
							<div className="dctc-ai-inline-input-row">
								<input
									type="number"
									id="lead_trigger_message_count"
									className="dctc-ai-bot-input dctc-ai-input--narrow"
									min="1"
									max="20"
									value={form.lead_trigger_message_count}
									onChange={(e) =>
										setField('lead_trigger_message_count', parseInt(e.target.value, 10) || 3)
									}
								/>
								<span className="dctc-ai-input-unit-label">{__('messages', 'dragwyb-click-to-chat')}</span>
							</div>
						</div>
					)}
				</div>

				<div style={{ marginTop: '1.25rem', padding: '1rem', background: '#f8fafc', borderRadius: '8px', border: '1px solid #e2e8f0' }}>
					<strong style={{ display: 'block', marginBottom: '0.5rem', color: '#1e293b', fontSize: '0.9rem' }}>
						{__('Lead Capture & Qualification Fields to Show in Chat', 'dragwyb-click-to-chat')}
					</strong>
					<div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(180px, 1fr))', gap: '0.85rem' }}>
						{[
							{ key: 'name', label: __('Full Name', 'dragwyb-click-to-chat') },
							{ key: 'email', label: __('Email Address', 'dragwyb-click-to-chat') },
							{ key: 'phone', label: __('Phone / WhatsApp', 'dragwyb-click-to-chat') },
							{ key: 'company', label: __('Company / Org', 'dragwyb-click-to-chat') },
							{ key: 'company_size', label: __('Company Size', 'dragwyb-click-to-chat') },
							{ key: 'budget', label: __('Budget Range', 'dragwyb-click-to-chat') },
							{ key: 'timeline', label: __('Purchase Timeline', 'dragwyb-click-to-chat') },
							{ key: 'interest', label: __('Product / Service Interest', 'dragwyb-click-to-chat') },
							{ key: 'requirement', label: __('Requirement / Notes', 'dragwyb-click-to-chat') },
						].map((f) => (
							<label key={f.key} style={{ display: 'inline-flex', alignItems: 'center', gap: '0.4rem', fontSize: '0.85rem', cursor: 'pointer', color: '#334155' }}>
								<input
									type="checkbox"
									checked={form.lead_fields?.[f.key] !== false}
									onChange={(e) =>
										setField('lead_fields', {
											...form.lead_fields,
											[f.key]: e.target.checked,
										})
									}
								/>
								{f.label}
							</label>
						))}
					</div>
				</div>

				<div className="dctc-ai-grid-2col" style={{ marginTop: '1.25rem' }}>
					<div className="dctc-ai-bot-field">
						<label htmlFor="lead_qualification_threshold">
							{__('Auto-Qualification Score Threshold (0 - 100)', 'dragwyb-click-to-chat')}
						</label>
						<div className="dctc-ai-inline-input-row">
							<input
								type="number"
								id="lead_qualification_threshold"
								className="dctc-ai-bot-input dctc-ai-input--narrow"
								min="0"
								max="100"
								value={form.lead_qualification_threshold}
								onChange={(e) =>
									setField('lead_qualification_threshold', parseInt(e.target.value, 10) || 70)
								}
							/>
							<span className="dctc-ai-input-unit-label">{__('points (marks lead as Qualified)', 'dragwyb-click-to-chat')}</span>
						</div>
						<p className="dctc-ai-bot-hint">
							{__('Leads scoring at or above this score are automatically classified as Qualified.', 'dragwyb-click-to-chat')}
						</p>
					</div>

					<div className="dctc-ai-bot-field">
						<div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '0.4rem' }}>
							<label htmlFor="enable_ai_intent_scoring" style={{ margin: 0, fontWeight: 600 }}>
								{__('AI Intent & Urgency Analysis', 'dragwyb-click-to-chat')}
							</label>
							<Toggle
								id="enable_ai_intent_scoring"
								checked={form.enable_ai_intent_scoring}
								onChange={(v) => setField('enable_ai_intent_scoring', v)}
							/>
						</div>
						<p className="dctc-ai-bot-hint" style={{ marginTop: '0.5rem' }}>
							{__('Analyzes visitor requirement text and conversation signals for buying intent and urgency tags.', 'dragwyb-click-to-chat')}
						</p>
					</div>
				</div>

				<div className="dctc-ai-grid-2col" style={{ marginTop: '1.25rem' }}>
					<div className="dctc-ai-bot-field">
						<div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '0.4rem' }}>
							<label htmlFor="enable_lead_email_alerts" style={{ margin: 0, fontWeight: 600 }}>
								{__('Instant Admin Email Alerts', 'dragwyb-click-to-chat')}
							</label>
							<Toggle
								id="enable_lead_email_alerts"
								checked={form.enable_lead_email_alerts}
								onChange={(v) => setField('enable_lead_email_alerts', v)}
							/>
						</div>
						{form.enable_lead_email_alerts && (
							<input
								type="email"
								id="lead_notification_email"
								className="dctc-ai-bot-input"
								value={form.lead_notification_email}
								onChange={(e) => setField('lead_notification_email', e.target.value)}
								placeholder={__('Leave blank to use admin email', 'dragwyb-click-to-chat')}
							/>
						)}
						<p className="dctc-ai-bot-hint">
							{__('Receive an instant email with lead details and score whenever a visitor submits their contact info.', 'dragwyb-click-to-chat')}
						</p>
					</div>

					<div className="dctc-ai-bot-field">
						<label htmlFor="lead_webhook_url">
							{__('Outbound Webhook URL (Zapier / Make / CRM)', 'dragwyb-click-to-chat')}
						</label>
						<input
							type="url"
							id="lead_webhook_url"
							className="dctc-ai-bot-input"
							value={form.lead_webhook_url}
							onChange={(e) => setField('lead_webhook_url', e.target.value)}
							placeholder="https://hooks.zapier.com/hooks/catch/..."
						/>
						<p className="dctc-ai-bot-hint">
							{__('Real-time asynchronous JSON POST sent to your CRM or automation workflow on every lead capture.', 'dragwyb-click-to-chat')}
						</p>
					</div>
				</div>
			</SwitcherCard>

			{/* 5. Connect Chatbot with Support Tickets */}
			{(window.dctc_ai_data?.is_support_enabled || window.dctc_support_data) ? (
				<SwitcherCard
					id="enable_support_escalation"
					title={__('Connect Chatbot with Support Tickets', 'dragwyb-click-to-chat')}
					desc={__('Seamlessly connect live AI chatbot conversations with Support Center tickets for automatic inquiry escalation and agent takeover.', 'dragwyb-click-to-chat')}
					icon="dashicons-tickets-alt"
					badge={__('Support Center', 'dragwyb-click-to-chat')}
					checked={form.enable_support_escalation}
					onChange={(v) => setField('enable_support_escalation', v)}
					disabledNotice={__('Turn on to automatically escalate unresolved customer queries and support issues from chatbot conversations into tracked support tickets.', 'dragwyb-click-to-chat')}
				>
					<div style={{ display: 'flex', flexDirection: 'column', gap: '0.85rem' }}>
						<div style={{ padding: '1rem', background: '#f8fafc', borderRadius: '8px', border: '1px solid #e2e8f0', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
							<div style={{ paddingRight: '1rem' }}>
								<strong style={{ display: 'block', color: '#1e293b', fontSize: '0.9rem', marginBottom: '0.25rem' }}>
									{__('Smart AI Ticket Routing & Auto-Assignment', 'dragwyb-click-to-chat')}
								</strong>
								<span style={{ fontSize: '0.825rem', color: '#64748b' }}>
									{__('Automatically classify customer query intent (technical support, billing, sales) and assign newly generated tickets to the most qualified agent.', 'dragwyb-click-to-chat')}
								</span>
							</div>
							<Toggle
								id="auto_assign_support_tickets"
								checked={form.auto_assign_support_tickets}
								onChange={(v) => setField('auto_assign_support_tickets', v)}
							/>
						</div>

						<div style={{ padding: '1rem', background: '#f8fafc', borderRadius: '8px', border: '1px solid #e2e8f0', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
							<div style={{ paddingRight: '1rem' }}>
								<strong style={{ display: 'block', color: '#1e293b', fontSize: '0.9rem', marginBottom: '0.25rem' }}>
									{__('Pause AI when Human Agent Takes Control', 'dragwyb-click-to-chat')}
								</strong>
								<span style={{ fontSize: '0.825rem', color: '#64748b' }}>
									{__('When an agent claims the ticket or sends a live reply from the Support Center, automatically silence bot responses so the human conversation stays smooth.', 'dragwyb-click-to-chat')}
								</span>
							</div>
							<Toggle
								id="auto_pause_ai_on_ticket"
								checked={form.auto_pause_ai_on_ticket}
								onChange={(v) => setField('auto_pause_ai_on_ticket', v)}
							/>
						</div>

						<div style={{ padding: '1rem', background: '#f8fafc', borderRadius: '8px', border: '1px solid #e2e8f0', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
							<div style={{ paddingRight: '1rem' }}>
								<strong style={{ display: 'block', color: '#1e293b', fontSize: '0.9rem', marginBottom: '0.25rem' }}>
									{__('Maximum Wait Time for Agent Response', 'dragwyb-click-to-chat')}
								</strong>
								<span style={{ fontSize: '0.825rem', color: '#64748b' }}>
									{__('If a live human agent takes longer than this duration without replying and is not actively viewing the ticket, automatically fallback to AI assistant and resume AI control mode.', 'dragwyb-click-to-chat')}
								</span>
							</div>
							<div style={{ display: 'flex', alignItems: 'center', flexDirection: 'column', textWrap: 'nowrap', gap: '0.5rem' }}>
								<input
									type="number"
									id="human_agent_max_wait_time"
									className="dctc-ai-bot-input"
									style={{ width: '90px', textAlign: 'center' }}
									min="10"
									max="1800"
									step="5"
									value={form.human_agent_max_wait_time}
									onChange={(e) => setField('human_agent_max_wait_time', e.target.value === '' ? '' : Math.max(10, Number(e.target.value)))}
								/>
								<span style={{ fontSize: '0.85rem', color: '#64748b', fontWeight: 500 }}>
									{__('sec (default 60s)', 'dragwyb-click-to-chat')}
								</span>
							</div>
						</div>
					</div>
				</SwitcherCard>
			) : (
				<section className="dctc-ai-card dctc-ai-conditional-card is-disabled" style={{ borderLeft: '4px solid #f59e0b' }}>
					<header className="dctc-ai-card__header dctc-ai-conditional-card__header">
						<div className="dctc-ai-card__header-left">
							<div className="dctc-ai-card-icon" style={{ background: '#fef3c7', color: '#d97706' }} aria-hidden="true">
								<span className="dashicons dashicons-tickets-alt" />
							</div>
							<div>
								<div className="dctc-ai-header-with-badge">
									<h2 className="dctc-ai-card__title">
										{__('Connect Chatbot with Support Tickets', 'dragwyb-click-to-chat')}
									</h2>
									<span className="dctc-ai-mini-badge" style={{ background: '#fef3c7', color: '#92400e', borderColor: '#fde68a' }}>
										{__('Requires Support Center', 'dragwyb-click-to-chat')}
									</span>
								</div>
								<p className="dctc-ai-card__desc">
									{__('Connect your AI chatbot directly to Support Center tickets, enable intelligent agent routing, and allow live human takeover.', 'dragwyb-click-to-chat')}
								</p>
							</div>
						</div>
					</header>
					<div className="dctc-ai-card__body dctc-ai-conditional-card__body" style={{ background: '#fffbeb', borderTop: '1px solid #fef3c7', padding: '1.25rem 1.5rem', display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap', gap: '1rem' }}>
						<div style={{ maxWidth: '650px' }}>
							<strong style={{ display: 'block', color: '#92400e', fontSize: '0.925rem', marginBottom: '0.35rem' }}>
								{__('Support Center is currently disabled or not configured.', 'dragwyb-click-to-chat')}
							</strong>
							<p style={{ margin: 0, color: '#78350f', fontSize: '0.85rem', lineHeight: 1.5 }}>
								{__('To route chatbot conversations into support tickets and assign them to staff agents, enable the Support Center module first.', 'dragwyb-click-to-chat')}
							</p>
						</div>
						<a
							href="#support-center"
							onClick={(e) => {
								e.preventDefault();
								const supportTabBtn = document.querySelector('[data-tab="support-center"], button[id*="support-center"]');
								if (supportTabBtn) {
									supportTabBtn.click();
								} else {
									window.location.hash = 'support-center';
									window.location.reload();
								}
							}}
							className="dctc-ai-btn dctc-ai-btn-primary"
							style={{ textDecoration: 'none', display: 'inline-flex', alignItems: 'center', gap: '0.5rem', whiteSpace: 'nowrap' }}
						>
							<span className="dashicons dashicons-admin-generic" style={{ fontSize: '16px', width: '16px', height: '16px', lineHeight: '16px' }} />
							{__('Enable Support Center', 'dragwyb-click-to-chat')}
						</a>
					</div>
				</section>
			)}

			{/* 6. AI Business Tools & Workflow Automation (Conditional Switcher Card) */}
			<SwitcherCard
				id="enable_ai_tools"
				title={__('AI Business Tools & Workflow Automation', 'dragwyb-click-to-chat')}
				desc={__('Allow AI models to safely execute real-world business actions (products, order checks, ticket logging, appointment scheduling, and webhooks).', 'dragwyb-click-to-chat')}
				icon="dashicons-admin-generic"
				badge={__('Agent Actions', 'dragwyb-click-to-chat')}
				checked={form.enable_ai_tools}
				onChange={(v) => setField('enable_ai_tools', v)}
				disabledNotice={__('Turn on to equip your AI assistant with real business actions and workflow automation.', 'dragwyb-click-to-chat')}
			>
				<div style={{ padding: '1rem', background: '#f8fafc', borderRadius: '8px', border: '1px solid #e2e8f0' }}>
					<strong style={{ display: 'block', marginBottom: '0.6rem', color: '#1e293b', fontSize: '0.9rem' }}>
						{__('Active AI Business Tools & Capabilities', 'dragwyb-click-to-chat')}
					</strong>
					<div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(260px, 1fr))', gap: '0.85rem' }}>
						{[
							...(isWcActive ? [
								{ id: 'search_products', label: __('🛍️ WooCommerce Product Search', 'dragwyb-click-to-chat'), desc: __('Find matching store items with live prices and stock', 'dragwyb-click-to-chat') },
								{ id: 'get_order_status', label: __('📦 Order Status Verification', 'dragwyb-click-to-chat'), desc: __('Check order delivery status with billing email protection', 'dragwyb-click-to-chat') },
							] : []),
							{ id: 'create_support_ticket', label: __('🎫 Support Ticket Dispatcher', 'dragwyb-click-to-chat'), desc: __('File customer tickets with priority and notify team', 'dragwyb-click-to-chat') },
							{ id: 'book_appointment', label: __('📅 Appointment & Demo Booking', 'dragwyb-click-to-chat'), desc: __('Schedule consultations and callbacks with clients', 'dragwyb-click-to-chat') },
							{ id: 'search_website_content', label: __('🔍 Website Content Search', 'dragwyb-click-to-chat'), desc: __('Find published articles, guides, and pages', 'dragwyb-click-to-chat') },
						].map((tool) => {
							const isToolActive = Array.isArray(form.enabled_tools) && form.enabled_tools.includes(tool.id);
							return (
								<div key={tool.id} style={{ display: 'flex', alignItems: 'flex-start', gap: '0.5rem', background: '#ffffff', padding: '0.75rem', borderRadius: '6px', border: '1px solid #e2e8f0' }}>
									<input
										type="checkbox"
										id={`tool_${tool.id}`}
										checked={isToolActive}
										onChange={(e) => {
											const current = Array.isArray(form.enabled_tools) ? [...form.enabled_tools] : [];
											const next = e.target.checked
												? [...current, tool.id]
												: current.filter((x) => x !== tool.id);
											setField('enabled_tools', next);
										}}
										style={{ marginTop: '3px' }}
									/>
									<label htmlFor={`tool_${tool.id}`} style={{ cursor: 'pointer', margin: 0 }}>
										<strong style={{ display: 'block', fontSize: '0.85rem', color: '#1e293b' }}>{tool.label}</strong>
										<span style={{ fontSize: '0.75rem', color: '#64748b' }}>{tool.desc}</span>
									</label>
								</div>
							);
						})}
					</div>
				</div>

				<div className="dctc-ai-grid-2col" style={{ marginTop: '1.25rem' }}>
					<div className="dctc-ai-bot-field">
						<label htmlFor="ticket_notification_email">
							{__('Support Ticket Alert Email', 'dragwyb-click-to-chat')}
						</label>
						<input
							type="email"
							id="ticket_notification_email"
							className="dctc-ai-bot-input"
							value={form.ticket_notification_email}
							onChange={(e) => setField('ticket_notification_email', e.target.value)}
							placeholder={__('Leave blank to use admin email', 'dragwyb-click-to-chat')}
						/>
						<p className="dctc-ai-bot-hint">
							{__('Email address to receive immediate alerts when tickets are created.', 'dragwyb-click-to-chat')}
						</p>
					</div>

					<div className="dctc-ai-bot-field">
						<label htmlFor="appointment_notification_email">
							{__('Appointment Booking Alert Email', 'dragwyb-click-to-chat')}
						</label>
						<input
							type="email"
							id="appointment_notification_email"
							className="dctc-ai-bot-input"
							value={form.appointment_notification_email}
							onChange={(e) => setField('appointment_notification_email', e.target.value)}
							placeholder={__('Leave blank to use admin email', 'dragwyb-click-to-chat')}
						/>
						<p className="dctc-ai-bot-hint">
							{__('Email address to receive consultation and demo booking requests.', 'dragwyb-click-to-chat')}
						</p>
					</div>
				</div>

				<div className="dctc-ai-bot-field" style={{ marginTop: '1.25rem' }}>
					<label htmlFor="workflow_webhook_url">
						{__('Automation Action Webhook URL (Zapier / Make / Slack / CRM)', 'dragwyb-click-to-chat')}
					</label>
					<input
						type="url"
						id="workflow_webhook_url"
						className="dctc-ai-bot-input"
						value={form.workflow_webhook_url}
						onChange={(e) => setField('workflow_webhook_url', e.target.value)}
						placeholder="https://hooks.zapier.com/hooks/catch/..."
					/>
					<p className="dctc-ai-bot-hint">
						{__('Dispatches real-time JSON payload whenever AI tools or actions are executed.', 'dragwyb-click-to-chat')}
					</p>
				</div>
			</SwitcherCard>

			{/* 7. Page-Aware AI & Smart Behavioral Triggers */}
			<SwitcherCard
				id="enable_smart_triggers"
				title={__('Page-Aware AI & Smart Behavioral Triggers', 'dragwyb-click-to-chat')}
				desc={__('Dynamically inject visited page, post & WooCommerce product context into AI prompts, and trigger proactive greetings on user interaction.', 'dragwyb-click-to-chat')}
				icon="dashicons-visibility"
				badge={__('Proactive AI', 'dragwyb-click-to-chat')}
				checked={form.enable_smart_triggers}
				onChange={(v) => setField('enable_smart_triggers', v)}
				disabledNotice={__('Enable to automatically inject page context and activate smart behavioral triggers (scroll depth, exit intent, inactivity, proactive teaser bubbles).', 'dragwyb-click-to-chat')}
			>
				<div className="dctc-ai-features-grid" style={{ marginBottom: '1.25rem' }}>
					<SettingCard
						id="enable_page_context"
						title={__('Page Context Injection', 'dragwyb-click-to-chat')}
						desc={__('Pass current visited page title, URL, post type & WooCommerce product details into the AI prompt for page-tailored answers.', 'dragwyb-click-to-chat')}
						icon="dashicons-admin-page"
						badge={__('Context Aware', 'dragwyb-click-to-chat')}
						checked={form.enable_page_context}
						onChange={(v) => setField('enable_page_context', v)}
					/>
					<SettingCard
						id="trigger_exit_intent"
						title={__('Exit Intent Trigger (Desktop)', 'dragwyb-click-to-chat')}
						desc={__('Detect when user cursor moves toward browser top bar or tab bar to show proactive greeting before leaving.', 'dragwyb-click-to-chat')}
						icon="dashicons-external"
						badge={__('Conversion', 'dragwyb-click-to-chat')}
						checked={form.trigger_exit_intent}
						onChange={(v) => setField('trigger_exit_intent', v)}
					/>
				</div>

				<div className="dctc-ai-grid-2col">
					<div className="dctc-ai-bot-field">
						<label htmlFor="trigger_action">
							{__('Trigger Response Action', 'dragwyb-click-to-chat')}
						</label>
						<select
							id="trigger_action"
							className="dctc-ai-bot-select"
							value={form.trigger_action}
							onChange={(e) => setField('trigger_action', e.target.value)}
						>
							<option value="show_bubble">{__('Show Proactive Teaser Bubble (Subtle & Non-Intrusive)', 'dragwyb-click-to-chat')}</option>
							<option value="open_chat">{__('Auto-Open Full Chat Window', 'dragwyb-click-to-chat')}</option>
						</select>
						<p className="dctc-ai-bot-hint">
							{__('Choose how the widget responds when a behavioral trigger condition is met.', 'dragwyb-click-to-chat')}
						</p>
					</div>

					<div className="dctc-ai-bot-field">
						<label htmlFor="trigger_scroll_depth">
							{__('Scroll Depth Trigger Percentage', 'dragwyb-click-to-chat')}
						</label>
						<div className="dctc-ai-inline-input-row">
							<input
								type="number"
								id="trigger_scroll_depth"
								className="dctc-ai-bot-input dctc-ai-input--narrow"
								min="10"
								max="100"
								step="5"
								value={form.trigger_scroll_depth}
								onChange={(e) =>
									setField('trigger_scroll_depth', parseInt(e.target.value, 10) || 50)
								}
							/>
							<span className="dctc-ai-input-unit-label">% of page</span>
						</div>
						<p className="dctc-ai-bot-hint">
							{__('Fires when user scrolls past this percentage of page height.', 'dragwyb-click-to-chat')}
						</p>
					</div>
				</div>

				<div className="dctc-ai-grid-2col" style={{ marginTop: '1rem' }}>
					<div className="dctc-ai-bot-field">
						<label htmlFor="trigger_inactivity">
							{__('User Inactivity Timeout (Seconds)', 'dragwyb-click-to-chat')}
						</label>
						<div className="dctc-ai-inline-input-row">
							<input
								type="number"
								id="trigger_inactivity"
								className="dctc-ai-bot-input dctc-ai-input--narrow"
								min="5"
								max="300"
								value={form.trigger_inactivity}
								onChange={(e) =>
									setField('trigger_inactivity', parseInt(e.target.value, 10) || 30)
								}
							/>
							<span className="dctc-ai-input-unit-label">seconds</span>
						</div>
						<p className="dctc-ai-bot-hint">
							{__('Triggers if visitor is idle on the page without scrolling or clicking.', 'dragwyb-click-to-chat')}
						</p>
					</div>

					<div className="dctc-ai-bot-field">
						<label htmlFor="target_devices">
							{__('Device Targeting Filter', 'dragwyb-click-to-chat')}
						</label>
						<select
							id="target_devices"
							className="dctc-ai-bot-select"
							value={form.target_devices}
							onChange={(e) => setField('target_devices', e.target.value)}
						>
							<option value="all">{__('All Devices (Desktop & Mobile)', 'dragwyb-click-to-chat')}</option>
							<option value="desktop_only">{__('Desktop Only', 'dragwyb-click-to-chat')}</option>
							<option value="mobile_only">{__('Mobile Only', 'dragwyb-click-to-chat')}</option>
						</select>
					</div>
				</div>

				<div className="dctc-ai-bot-field" style={{ marginTop: '1rem' }}>
					<label htmlFor="target_users">
						{__('Visitor Audience Targeting', 'dragwyb-click-to-chat')}
					</label>
					<select
						id="target_users"
						className="dctc-ai-bot-select"
						value={form.target_users}
						onChange={(e) => setField('target_users', e.target.value)}
					>
						<option value="all">{__('All Visitors (Logged-in & Guests)', 'dragwyb-click-to-chat')}</option>
						<option value="guests">{__('Guest Visitors Only', 'dragwyb-click-to-chat')}</option>
						<option value="logged_in">{__('Logged-in Users Only', 'dragwyb-click-to-chat')}</option>
					</select>
				</div>

				<div className="dctc-ai-bot-field" style={{ marginTop: '1rem' }}>
					<label htmlFor="url_rules">
						{__('Target Specific URL Paths (Optional)', 'dragwyb-click-to-chat')}
					</label>
					<textarea
						id="url_rules"
						className="dctc-ai-bot-textarea"
						rows="2"
						value={form.url_rules}
						onChange={(e) => setField('url_rules', e.target.value)}
						placeholder="/pricing&#10;/shop/*&#10;/contact"
					/>
					<p className="dctc-ai-bot-hint">
						{__('Leave empty to apply sitewide. Enter one path or wildcard pattern per line to restrict triggers to specific pages.', 'dragwyb-click-to-chat')}
					</p>
				</div>
			</SwitcherCard>

			{/* 8. Save Chat History & Retention Policy (Conditional Switcher Card) */}
			<SwitcherCard
				id="save_chat"
				title={__('Conversation History & Retention Policy', 'dragwyb-click-to-chat')}
				desc={__('Persist conversation logs for visitors, power AI contextual memory, and enforce automatic retention cleanup.', 'dragwyb-click-to-chat')}
				icon="dashicons-backup"
				badge={__('Privacy & Memory', 'dragwyb-click-to-chat')}
				checked={form.save_chat}
				onChange={(v) => setField('save_chat', v)}
				disabledNotice={__('Enable chat history to maintain conversation sessions, context memory, and automated retention.', 'dragwyb-click-to-chat')}
			>
				<div className="dctc-ai-grid-2col">
					<div className="dctc-ai-bot-field">
						<label htmlFor="chat_retention_days">
							{__('Conversation Retention Policy (WP-Cron)', 'dragwyb-click-to-chat')}
						</label>
						<select
							id="chat_retention_days"
							className="dctc-ai-bot-select"
							value={form.chat_retention_days}
							onChange={(e) =>
								setField('chat_retention_days', parseInt(e.target.value, 10) || 0)
							}
						>
							<option value="0">{__('Keep Forever (No Auto-Delete)', 'dragwyb-click-to-chat')}</option>
							<option value="7">{__('Older than 7 days', 'dragwyb-click-to-chat')}</option>
							<option value="14">{__('Older than 14 days', 'dragwyb-click-to-chat')}</option>
							<option value="30">{__('Older than 30 days (Recommended)', 'dragwyb-click-to-chat')}</option>
							<option value="60">{__('Older than 60 days', 'dragwyb-click-to-chat')}</option>
							<option value="90">{__('Older than 90 days', 'dragwyb-click-to-chat')}</option>
							<option value="180">{__('Older than 180 days (6 months)', 'dragwyb-click-to-chat')}</option>
							<option value="365">{__('Older than 365 days (1 year)', 'dragwyb-click-to-chat')}</option>
						</select>
						<p className="dctc-ai-bot-hint">
							{__('Automatically purges old chat sessions on daily schedule to comply with privacy policies and keep database clean.', 'dragwyb-click-to-chat')}
						</p>
					</div>

					<div className="dctc-ai-bot-field">
						<label htmlFor="memory_window_size">
							{__('Context Memory Window (Messages)', 'dragwyb-click-to-chat')}
						</label>
						<div className="dctc-ai-inline-input-row">
							<input
								type="number"
								id="memory_window_size"
								className="dctc-ai-bot-input dctc-ai-input--narrow"
								min="2"
								max="50"
								value={form.memory_window_size}
								onChange={(e) =>
									setField('memory_window_size', parseInt(e.target.value, 10) || 10)
								}
							/>
							<span className="dctc-ai-input-unit-label">
								{__('recent messages', 'dragwyb-click-to-chat')}
							</span>
						</div>
						<p className="dctc-ai-bot-hint">
							{__('Number of preceding conversation turns sent to LLM for follow-up and pronoun resolution.', 'dragwyb-click-to-chat')}
						</p>
					</div>
				</div>

				<div className="dctc-ai-features-grid" style={{ marginTop: '1.25rem', paddingTop: '1.25rem', borderTop: '1px solid #f1f5f9' }}>
					<SettingCard
						id="ask_email"
						title={__('Ask Visitor Email for Continuity', 'dragwyb-click-to-chat')}
						desc={__('Optionally request email to restore chat sessions across different devices.', 'dragwyb-click-to-chat')}
						icon="dashicons-email-alt"
						badge={__('Lead Capture', 'dragwyb-click-to-chat')}
						checked={!!form.ask_email}
						onChange={(v) => setField('ask_email', v)}
					/>
				</div>
			</SwitcherCard>

			{/* 9. Multilingual AI & Auto-Detection */}
			<SwitcherCard
				id="enable_multilingual"
				title={__('Multilingual AI & Language Auto-Detection', 'dragwyb-click-to-chat')}
				desc={__('Detect visitor language automatically and respond fluently in over 50+ languages.', 'dragwyb-click-to-chat')}
				icon="dashicons-translation"
				badge={__('Global AI', 'dragwyb-click-to-chat')}
				checked={form.enable_multilingual}
				onChange={(v) => setField('enable_multilingual', v)}
				disabledNotice={__('Enable to automatically reply in the visitor’s language or enforce a specific language.', 'dragwyb-click-to-chat')}
			>
				<div className="dctc-ai-grid-2col">
					<div className="dctc-ai-bot-field">
						<label htmlFor="preferred_language">
							{__('Primary Bot Language', 'dragwyb-click-to-chat')}
						</label>
						<select
							id="preferred_language"
							className="dctc-ai-bot-select"
							value={form.preferred_language}
							onChange={(e) => setField('preferred_language', e.target.value)}
						>
							<option value="auto">{__('🌐 Auto-Detect Visitor Language (Recommended)', 'dragwyb-click-to-chat')}</option>
							<option value="en">English (US/UK)</option>
							<option value="es">Español (Spanish)</option>
							<option value="fr">Français (French)</option>
							<option value="de">Deutsch (German)</option>
							<option value="it">Italiano (Italian)</option>
							<option value="pt">Português (Portuguese)</option>
							<option value="hi">हिन्दी (Hindi)</option>
							<option value="ar">العربية (Arabic)</option>
							<option value="zh">中文 (Chinese)</option>
							<option value="ja">日本語 (Japanese)</option>
							<option value="nl">Nederlands (Dutch)</option>
							<option value="ru">Русский (Russian)</option>
							<option value="tr">Türkçe (Turkish)</option>
							<option value="id">Bahasa Indonesia</option>
						</select>
						<p className="dctc-ai-bot-hint">
							{__('When Auto-Detect is chosen, the assistant speaks in whatever language the visitor uses in chat.', 'dragwyb-click-to-chat')}
						</p>
					</div>

					<div className="dctc-ai-bot-field">
						<SettingCard
							id="visitor_language_override"
							title={__('Auto-Adapt to Visitor Locale', 'dragwyb-click-to-chat')}
							desc={__('Use browser language detection as secondary fallback for multilingual visitors.', 'dragwyb-click-to-chat')}
							icon="dashicons-admin-site"
							checked={form.visitor_language_override}
							onChange={(v) => setField('visitor_language_override', v)}
						/>
					</div>
				</div>
			</SwitcherCard>

			{/* 10. Voice AI & Audio Experience */}
			<SwitcherCard
				id="enable_voice_input"
				title={__('Voice AI & Speech Recognition', 'dragwyb-click-to-chat')}
				desc={__('Enable microphone button for voice input and audio response read-aloud capabilities.', 'dragwyb-click-to-chat')}
				icon="dashicons-microphone"
				badge={__('Voice AI', 'dragwyb-click-to-chat')}
				checked={form.enable_voice_input}
				onChange={(v) => setField('enable_voice_input', v)}
				disabledNotice={__('Enable voice AI to let visitors speak their questions hands-free via Web Speech API.', 'dragwyb-click-to-chat')}
			>
				<div className="dctc-ai-features-grid">
					<SettingCard
						id="enable_voice_output"
						title={__('Text-to-Speech Read Aloud (TTS)', 'dragwyb-click-to-chat')}
						desc={__('Displays speaker icon on assistant replies so visitors can listen to answers.', 'dragwyb-click-to-chat')}
						icon="dashicons-controls-volumeon"
						checked={form.enable_voice_output}
						onChange={(v) => setField('enable_voice_output', v)}
					/>
				</div>

				<div className="dctc-ai-bot-field" style={{ marginTop: '1rem' }}>
					<label htmlFor="voice_language">
						{__('Speech Recognition & Accent Language', 'dragwyb-click-to-chat')}
					</label>
					<select
						id="voice_language"
						className="dctc-ai-bot-select"
						value={form.voice_language}
						onChange={(e) => setField('voice_language', e.target.value)}
					>
						<option value="auto">{__('Auto (Browser Locale Default)', 'dragwyb-click-to-chat')}</option>
						<option value="en-US">English (US)</option>
						<option value="en-GB">English (UK)</option>
						<option value="es-ES">Spanish (Spain)</option>
						<option value="es-MX">Spanish (Latin America)</option>
						<option value="fr-FR">French</option>
						<option value="de-DE">German</option>
						<option value="it-IT">Italian</option>
						<option value="pt-BR">Portuguese (Brazil)</option>
						<option value="hi-IN">Hindi (India)</option>
						<option value="ar-SA">Arabic (Saudi Arabia)</option>
						<option value="zh-CN">Chinese (Mandarin)</option>
						<option value="ja-JP">Japanese</option>
					</select>
				</div>
			</SwitcherCard>

			{/* 11. Visitor File & Image Uploads (Conditional Switcher Card) */}
			<SwitcherCard
				id="enable_uploads"
				title={__('Visitor File & Image Uploads', 'dragwyb-click-to-chat')}
				desc={__('Allow visitors to attach photos, screenshots, and documents directly in chat composer.', 'dragwyb-click-to-chat')}
				icon="dashicons-paperclip"
				badge={__('Interactive', 'dragwyb-click-to-chat')}
				checked={form.enable_uploads}
				onChange={(v) => setField('enable_uploads', v)}
				disabledNotice={__('Turn on to let visitors upload images, PDFs, and documents during chat.', 'dragwyb-click-to-chat')}
			>
				<div className="dctc-ai-features-grid" style={{ marginBottom: '1.25rem' }}>
					<SettingCard
						id="enable_vision_understanding"
						title={__('Multimodal Vision AI Processing', 'dragwyb-click-to-chat')}
						desc={__('Sends uploaded image attachments to vision-capable models (GPT-4o, Claude 3.5, Gemini) for direct visual analysis.', 'dragwyb-click-to-chat')}
						icon="dashicons-visibility"
						badge={__('Vision AI', 'dragwyb-click-to-chat')}
						checked={form.enable_vision_understanding}
						onChange={(v) => setField('enable_vision_understanding', v)}
					/>
				</div>
				<div className="dctc-ai-grid-2col">
					<div className="dctc-ai-bot-field">
						<label htmlFor="allowed_file_types">
							{__('Allowed Extensions (Whitelist)', 'dragwyb-click-to-chat')}
						</label>
						<input
							type="text"
							id="allowed_file_types"
							className="dctc-ai-bot-input"
							value={form.allowed_file_types}
							onChange={(e) => setField('allowed_file_types', e.target.value)}
							placeholder="jpg, jpeg, png, webp, gif, pdf, txt, doc, docx"
						/>
						<p className="dctc-ai-bot-hint">
							{__('Comma-separated file extensions permitted for upload.', 'dragwyb-click-to-chat')}
						</p>
					</div>

					<div className="dctc-ai-bot-field">
						<label htmlFor="excluded_file_types">
							{__('Blocked Extensions (Strict Blacklist)', 'dragwyb-click-to-chat')}
						</label>
						<input
							type="text"
							id="excluded_file_types"
							className="dctc-ai-bot-input"
							value={form.excluded_file_types}
							onChange={(e) => setField('excluded_file_types', e.target.value)}
							placeholder="php, js, html, exe, svg"
						/>
						<p className="dctc-ai-bot-hint">
							{__('Extensions that are strictly forbidden. Takes priority over whitelist.', 'dragwyb-click-to-chat')}
						</p>
					</div>
				</div>

				<div className="dctc-ai-grid-3col" style={{ marginTop: '1.25rem' }}>
					<div className="dctc-ai-bot-field">
						<label htmlFor="max_upload_size">
							{__('Max Size Per File', 'dragwyb-click-to-chat')}
						</label>
						<div className="dctc-ai-inline-input-row">
							<input
								type="number"
								id="max_upload_size"
								className="dctc-ai-bot-input"
								min="1"
								max="50"
								value={form.max_upload_size}
								onChange={(e) =>
									setField('max_upload_size', parseInt(e.target.value, 10) || 5)
								}
							/>
							<span className="dctc-ai-input-unit-label">MB</span>
						</div>
					</div>

					<div className="dctc-ai-bot-field">
						<label htmlFor="max_files_per_message">
							{__('Max Files Per Message', 'dragwyb-click-to-chat')}
						</label>
						<div className="dctc-ai-inline-input-row">
							<input
								type="number"
								id="max_files_per_message"
								className="dctc-ai-bot-input"
								min="1"
								max="10"
								value={form.max_files_per_message}
								onChange={(e) =>
									setField('max_files_per_message', parseInt(e.target.value, 10) || 3)
								}
							/>
							<span className="dctc-ai-input-unit-label">
								{__('files', 'dragwyb-click-to-chat')}
							</span>
						</div>
					</div>

					<div className="dctc-ai-bot-field">
						<label htmlFor="store_chat_attachments">
							{__('Attachment Storage Mode', 'dragwyb-click-to-chat')}
						</label>
						<select
							id="store_chat_attachments"
							className="dctc-ai-bot-select"
							value={form.store_chat_attachments || 'temp'}
							onChange={(e) => setField('store_chat_attachments', e.target.value)}
						>
							<option value="temp">{__('Temporary Storage (Recommended)', 'dragwyb-click-to-chat')}</option>
							<option value="save_with_history">{__('Save with History (Media Library)', 'dragwyb-click-to-chat')}</option>
							<option value="do_not_store">{__('Do Not Store After Processing', 'dragwyb-click-to-chat')}</option>
						</select>
					</div>
				</div>
			</SwitcherCard>

			{/* 12. Error Logging & Retention (Conditional Switcher Card) */}
			<SwitcherCard
				id="enable_error_log"
				title={__('Error Logging & Retention', 'dragwyb-click-to-chat')}
				desc={__('Capture AI API failures, provider errors, and system exceptions for troubleshooting.', 'dragwyb-click-to-chat')}
				icon="dashicons-warning"
				checked={form.enable_error_log}
				onChange={(v) => setField('enable_error_log', v)}
				disabledNotice={__('Enable error logging to capture API timeouts, provider errors, and system exceptions.', 'dragwyb-click-to-chat')}
			>
				<div className="dctc-ai-bot-field">
					<label htmlFor="error_log_retention_days">
						{__('Auto-delete Logs (Daily WordPress Cron)', 'dragwyb-click-to-chat')}
					</label>
					<select
						id="error_log_retention_days"
						className="dctc-ai-bot-select dctc-ai-input--medium"
						value={form.error_log_retention_days}
						onChange={(e) =>
							setField('error_log_retention_days', parseInt(e.target.value, 10) || 0)
						}
					>
						<option value="0">{__('Never / Permanent (Default)', 'dragwyb-click-to-chat')}</option>
						<option value="1">{__('Older than 1 day', 'dragwyb-click-to-chat')}</option>
						<option value="7">{__('Older than 7 days', 'dragwyb-click-to-chat')}</option>
						<option value="14">{__('Older than 14 days', 'dragwyb-click-to-chat')}</option>
						<option value="30">{__('Older than 30 days', 'dragwyb-click-to-chat')}</option>
						<option value="60">{__('Older than 60 days', 'dragwyb-click-to-chat')}</option>
						<option value="90">{__('Older than 90 days', 'dragwyb-click-to-chat')}</option>
					</select>
					<p className="dctc-ai-bot-hint">
						{__('Keep the database lean by automatically pruning older logs.', 'dragwyb-click-to-chat')}
					</p>
				</div>
			</SwitcherCard>
		</div>
	);
}
