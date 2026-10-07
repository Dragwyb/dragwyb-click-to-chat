/**
 * Global NoticeBanner: Accessible inline alert / banner for success, warning, error, info.
 */
import { __ } from '@wordpress/i18n';

export default function NoticeBanner({
	type = 'info', // 'success' | 'error' | 'warning' | 'info'
	message,
	children,
	onDismiss = null,
	className = '',
}) {
	if (!message && !children) {
		return null;
	}

	const iconMap = {
		success: 'dashicons-yes-alt',
		error: 'dashicons-warning',
		warning: 'dashicons-warning',
		info: 'dashicons-info',
	};

	return (
		<div className={`dctc-global-notice dctc-global-notice--${type} ${className}`} role="alert">
			<div className="dctc-global-notice__content">
				<span className={`dashicons ${iconMap[type] || 'dashicons-info'} dctc-global-notice__icon`} aria-hidden="true" />
				<div className="dctc-global-notice__text">
					{message && <span>{message}</span>}
					{children}
				</div>
			</div>
			{onDismiss && (
				<button
					type="button"
					className="dctc-global-notice__dismiss"
					onClick={onDismiss}
					aria-label={__('Dismiss', 'dragwyb-click-to-chat')}
				>
					<span className="dashicons dashicons-no-alt" aria-hidden="true" />
				</button>
			)}
		</div>
	);
}
