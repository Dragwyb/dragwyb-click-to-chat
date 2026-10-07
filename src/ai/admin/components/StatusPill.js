/**
 * StatusPill: Reusable status and state badge with indicator dot.
 */
export default function StatusPill({
	active,
	activeLabel,
	inactiveLabel,
	type = null, // 'success' | 'error' | 'warning' | 'info' | 'neutral'
	label = null,
	className = '',
}) {
	if (type) {
		return (
			<span className={`dctc-ai-status-pill is-${type} ${className}`}>
				<span className="dctc-ai-status-indicator" />
				{label}
			</span>
		);
	}

	return (
		<span className={`dctc-ai-status-pill ${active ? 'is-active' : 'is-inactive'} ${className}`}>
			<span className="dctc-ai-status-indicator" />
			{active ? activeLabel : inactiveLabel}
		</span>
	);
}
