/**
 * Reusable accessible modal dialog with backdrop and escape key handling.
 */
import { useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

export default function Modal({
	open,
	title,
	icon = null,
	onClose,
	children,
	footer = null,
	maxWidth = '580px',
	className = '',
}) {
	useEffect(() => {
		if (!open) return;
		document.body.classList.add('dctc-ai-modal-open');
		const handleKey = (e) => {
			if (e.key === 'Escape') {
				onClose?.();
			}
		};
		document.addEventListener('keydown', handleKey);
		return () => {
			document.body.classList.remove('dctc-ai-modal-open');
			document.removeEventListener('keydown', handleKey);
		};
	}, [open, onClose]);

	if (!open) return null;

	return (
		<div
			className={`dctc-ai-modal is-visible ${className}`}
			role="dialog"
			aria-modal="true"
			aria-label={typeof title === 'string' ? title : undefined}
		>
			<div className="dctc-ai-modal-overlay" onClick={onClose} />
			<div className="dctc-ai-modal-content" style={{ maxWidth }}>
				<div className="dctc-ai-modal-header">
					<div className="dctc-ai-modal-title">
						{icon && (
							<span className="dctc-ai-modal-title-icon" aria-hidden="true">
								<span className={`dashicons ${icon}`} />
							</span>
						)}
						<h3>{title}</h3>
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
				<div className="dctc-ai-modal-body">
					{children}
				</div>
				{footer && (
					<div className="dctc-ai-modal-footer">
						{footer}
					</div>
				)}
			</div>
		</div>
	);
}
