import { __ } from '@wordpress/i18n';
import { formatProviderLabel } from '../../utils/providers';

function parseMessages(content) {
	if (!content) return [];
	try {
		const parsed = typeof content === 'string' ? JSON.parse(content) : content;
		return Array.isArray(parsed) ? parsed : [];
	} catch {
		return [];
	}
}

function parseSummary(summary) {
	if (!summary) return null;
	if (typeof summary === 'object') return summary;
	try {
		return JSON.parse(summary);
	} catch {
		return null;
	}
}

function formatDate(value) {
	if (!value) return '';
	const iso = String(value).includes('T') ? value : String(value).replace(' ', 'T');
	const date = new Date(iso);
	if (Number.isNaN(date.getTime())) return value;
	const now = new Date();
	const opts = {
		month: 'short',
		day: 'numeric',
		...(date.getFullYear() !== now.getFullYear() ? { year: 'numeric' } : {}),
	};
	return `${date.toLocaleDateString(undefined, opts)} · ${date.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' })}`;
}

export default function SessionTranscriptModal({
	session,
	isOpen,
	onClose,
	onPrint,
	onSummarize,
	summarizing = false,
}) {
	if (!isOpen || !session) return null;

	const msgs = parseMessages(session.content);
	const summ = parseSummary(session.summary);
	const meta = {
		provider: session?.provider ? formatProviderLabel(session.provider) : __('Unknown', 'dragwyb-click-to-chat'),
		model: session?.model || __('Unknown', 'dragwyb-click-to-chat'),
	};

	return (
		<div className="dctc-ai-modal dctc-ai-sessions-modal is-visible" role="dialog" aria-modal="true">
			<div className="dctc-ai-modal-overlay" onClick={onClose} />
			<div className="dctc-ai-modal-content">
				<div className="dctc-ai-modal-header">
					<div className="dctc-ai-modal-title">
						<span className="dctc-ai-modal-title-icon" aria-hidden="true">
							<span className="dashicons dashicons-format-chat" />
						</span>
						<h3>{__('Session Transcript', 'dragwyb-click-to-chat')}</h3>
					</div>
					<button
						type="button"
						className="dctc-ai-modal-close"
						onClick={onClose}
						aria-label={__('Close', 'dragwyb-click-to-chat')}
					>
						<span className="dashicons dashicons-no-alt" aria-hidden="true" />
					</button>
				</div>

				<div className="dctc-ai-sessions-modal-meta">
					<div className="dctc-ai-sessions-modal-meta__item">
						<span className="dctc-ai-sessions-modal-meta__label">
							{__('Provider', 'dragwyb-click-to-chat')}
						</span>
						<span className="dctc-ai-sessions-modal-meta__value">
							{meta.provider}
						</span>
					</div>
					<div className="dctc-ai-sessions-modal-meta__item">
						<span className="dctc-ai-sessions-modal-meta__label">
							{__('Model', 'dragwyb-click-to-chat')}
						</span>
						<span className="dctc-ai-sessions-modal-meta__value">
							{meta.model}
						</span>
					</div>
				</div>

				<div className="dctc-ai-modal-body">
					{/* AI Executive Summary Section */}
					<div style={{ marginBottom: '1.25rem', background: '#f8fafc', border: '1px solid #e2e8f0', borderRadius: '10px', padding: '1rem' }}>
						<div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '0.75rem' }}>
							<strong style={{ fontSize: '0.9rem', color: '#0f172a', display: 'flex', alignItems: 'center', gap: '0.4rem' }}>
								✨ {__('AI Executive Summary', 'dragwyb-click-to-chat')}
							</strong>
							<button
								type="button"
								className="button button-small button-secondary"
								disabled={summarizing}
								onClick={() => onSummarize(session.session_id)}
								style={{ borderRadius: '6px', fontSize: '0.8rem' }}
							>
								{summarizing ? __('Analyzing...', 'dragwyb-click-to-chat') : (summ ? __('Regenerate', 'dragwyb-click-to-chat') : __('Generate Summary', 'dragwyb-click-to-chat'))}
							</button>
						</div>

						{summ ? (
							<div style={{ display: 'flex', flexDirection: 'column', gap: '0.5rem', fontSize: '0.85rem' }}>
								<div>
									<strong style={{ color: '#475569', fontSize: '0.75rem', textTransform: 'uppercase' }}>{__('Goal / Intent', 'dragwyb-click-to-chat')}:</strong>
									<div style={{ color: '#0f172a', marginTop: '0.1rem' }}>{summ.goal}</div>
								</div>
								{Array.isArray(summ.questions) && summ.questions.length > 0 && (
									<div>
										<strong style={{ color: '#475569', fontSize: '0.75rem', textTransform: 'uppercase' }}>{__('Key Questions', 'dragwyb-click-to-chat')}:</strong>
										<ul style={{ margin: '0.2rem 0 0 1.2rem', color: '#334155' }}>
											{summ.questions.map((q, qIdx) => <li key={qIdx}>{q}</li>)}
										</ul>
									</div>
								)}
								{summ.next_action && (
									<div>
										<strong style={{ color: '#475569', fontSize: '0.75rem', textTransform: 'uppercase' }}>{__('Next Action', 'dragwyb-click-to-chat')}:</strong>
										<div style={{ color: '#1e40af', fontWeight: 500, marginTop: '0.1rem' }}>👉 {summ.next_action}</div>
									</div>
								)}
							</div>
						) : (
							<p style={{ margin: 0, fontSize: '0.85rem', color: '#64748b' }}>
								{__('Generate a structured executive summary with customer goals, key questions, and sentiment analysis for this chat.', 'dragwyb-click-to-chat')}
							</p>
						)}
					</div>

					{msgs.length ? (
						msgs.map((msg, i) => (
							<div
								key={i}
								className={`dctc-ai-modal-msg ${msg.role === 'user' ? 'dctc-ai-modal-msg-user' : 'dctc-ai-modal-msg-assistant'}`}
							>
								<div className="dctc-ai-modal-msg-content">
									{msg.content}
								</div>
								{msg.created_at && (
									<div className="dctc-ai-modal-msg-time">
										{formatDate(msg.created_at)}
									</div>
								)}
							</div>
						))
					) : (
						<p className="dctc-ai-text-muted">
							{__('No messages in this session.', 'dragwyb-click-to-chat')}
						</p>
					)}
				</div>

				<div className="dctc-ai-modal-footer">
					<button
						type="button"
						className="dctc-ai-modal-footer-link"
						onClick={onPrint}
					>
						{__('Print Chat Transcript', 'dragwyb-click-to-chat')}
					</button>
					<button
						type="button"
						className="dctc-ai-btn dctc-ai-btn-primary"
						onClick={onClose}
					>
						{__('Return to Sessions', 'dragwyb-click-to-chat')}
					</button>
				</div>
			</div>
		</div>
	);
}
