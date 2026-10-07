/**
 * Support Center Status & Priority Badge Components
 */
import { __ } from '@wordpress/i18n';

export function PriorityBadge({ priority, className = '' }) {
	const p = (priority || 'normal').toLowerCase();
	const labels = {
		urgent: __('Urgent', 'dragwyb-click-to-chat'),
		high: __('High', 'dragwyb-click-to-chat'),
		normal: __('Normal', 'dragwyb-click-to-chat'),
		low: __('Low', 'dragwyb-click-to-chat'),
	};

	return (
		<span className={`dctc-sc-badge-priority ${p} ${className}`}>
			{labels[p] || p}
		</span>
	);
}

export function StatusBadge({ status, className = '' }) {
	const s = (status || 'open').toLowerCase();
	const labels = {
		open: __('Open', 'dragwyb-click-to-chat'),
		in_progress: __('In Progress', 'dragwyb-click-to-chat'),
		pending: __('Pending', 'dragwyb-click-to-chat'),
		resolved: __('Resolved', 'dragwyb-click-to-chat'),
		closed: __('Closed', 'dragwyb-click-to-chat'),
	};

	return (
		<span className={`dctc-sc-badge-status ${s} ${className}`}>
			<span className="dctc-sc-status-dot" />
			{labels[s] || s.replace(/_/g, ' ')}
		</span>
	);
}
