import { __ } from '@wordpress/i18n';

/**
 * Reusable PRO Badge Component
 * Compliant with Dragwyb Free vs Pro Plan (dctc-pro marker)
 */
export default function ProBadge({ className = '', tooltip = '' }) {
	return (
		<span
			className={`dctc-pro-badge ${className}`}
			title={tooltip || __('Available in Dragwyb Pro', 'dragwyb-click-to-chat')}
		>
			<span className="dashicons dashicons-lock" style={{ fontSize: '11px', width: '11px', height: '11px', marginRight: '2px', verticalAlign: 'middle' }} />
			{__('PRO', 'dragwyb-click-to-chat')}
		</span>
	);
}
