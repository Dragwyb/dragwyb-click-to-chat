import { useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

export default function Modal({
	isOpen = false,
	open = false,
	onClose,
	title,
	subtitle,
	children,
	footer,
	maxWidth = '600px',
	className = '',
	icon = null,
}) {
	const visible = isOpen || open;

	useEffect(() => {
		if (!visible) return;
		document.body.classList.add('dctc-modal-open');
		const handleKeyDown = (e) => {
			if (e.key === 'Escape') {
				onClose?.();
			}
		};
		window.addEventListener('keydown', handleKeyDown);
		return () => {
			document.body.classList.remove('dctc-modal-open');
			window.removeEventListener('keydown', handleKeyDown);
		};
	}, [visible, onClose]);

	if (!visible) return null;

	return (
		<div
			className={`dctc-global-modal-backdrop dctc-sc-modal-backdrop ${className}`}
			onClick={onClose}
			role="dialog"
			aria-modal="true"
		>
			<div
				className="dctc-global-modal-dialog dctc-sc-modal-card"
				style={{ maxWidth }}
				onClick={(e) => e.stopPropagation()}
			>
				{(title || onClose) && (
					<div className="dctc-global-modal-header dctc-sc-modal-header-bar">
						<div className="dctc-global-modal-title-group">
							{icon && (
								<span className="dctc-global-modal-icon-wrap" aria-hidden="true">
									{typeof icon === 'string' ? (
										<span className={`dashicons ${icon.startsWith('dashicons-') ? icon : `dashicons-${icon}`}`} />
									) : (
										icon
									)}
								</span>
							)}
							<div>
								{title && <h3 className="dctc-global-modal-title dctc-sc-modal-heading">{title}</h3>}
								{subtitle && <p className="dctc-global-modal-subtitle dctc-sc-modal-subheading">{subtitle}</p>}
							</div>
						</div>
						{onClose && (
							<button
								type="button"
								className="dctc-global-modal-close dctc-sc-btn-close-modal"
								onClick={onClose}
								aria-label={__('Close modal', 'dragwyb-click-to-chat')}
							>
								&times;
							</button>
						)}
					</div>
				)}

				<div className="dctc-global-modal-body dctc-sc-modal-body-scroll">
					{children}
				</div>

				{footer && (
					<div className="dctc-global-modal-footer dctc-sc-modal-footer-bar">
						{footer}
					</div>
				)}
			</div>
		</div>
	);
}
