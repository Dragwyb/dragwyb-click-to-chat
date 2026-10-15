import { __ } from '@wordpress/i18n';

export default function EmptyState({
	icon = 'dashicons-info',
	title = __('No items found', 'dragwyb-click-to-chat'),
	description = '',
	action = null,
	className = '',
	style = {},
}) {
	const iconClass = typeof icon === 'string' && icon.startsWith('dashicons-') ? icon : `dashicons-${icon}`;

	return (
		<div className={`dctc-global-empty-state ${className}`} style={style}>
			<div className="dctc-global-empty-state-icon">
				<span className={`dashicons ${iconClass}`} aria-hidden="true" />
			</div>
			{title && <h4 className="dctc-global-empty-state-title">{title}</h4>}
			{description && <p className="dctc-global-empty-state-desc">{description}</p>}
			{action && <div className="dctc-global-empty-state-action">{action}</div>}
		</div>
	);
}
