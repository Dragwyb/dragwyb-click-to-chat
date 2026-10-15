/**
 * Global SwitcherCard: Standalone card with a master switch directly in its header.
 * Conditionally reveals child settings when active or shows a clean summary when disabled.
 */
import { __ } from '@wordpress/i18n';
import Toggle from './Toggle';

export default function SwitcherCard({
	id,
	title,
	desc,
	icon,
	checked,
	onChange,
	badge = null,
	children,
	disabledNotice = null,
	disabledContent = null,
	className = '',
}) {
	return (
		<section className={`dctc-ai-card dctc-ai-conditional-card ${checked ? 'is-active' : 'is-disabled'} ${className}`}>
			<header className="dctc-ai-card__header dctc-ai-conditional-card__header">
				<div className="dctc-ai-card__header-left">
					<div className={`dctc-ai-card-icon ${checked ? 'dctc-ai-card-icon--active' : ''}`} aria-hidden="true">
						<span className={`dashicons ${icon}`} />
					</div>
					<div>
						<div className="dctc-ai-header-with-badge">
							<h2 className="dctc-ai-card__title">{title}</h2>
							{badge && <span className="dctc-ai-mini-badge">{badge}</span>}
							<span className={`dctc-ai-status-pill ${checked ? 'is-active' : 'is-inactive'}`}>
								<span className="dctc-ai-status-indicator" />
								{checked ? __('Enabled', 'dragwyb-click-to-chat') : __('Disabled', 'dragwyb-click-to-chat')}
							</span>
						</div>
						<p className="dctc-ai-card__desc">{desc}</p>
					</div>
				</div>
				<div className="dctc-ai-conditional-card__toggle-wrap">
					<Toggle id={id} checked={checked} onChange={onChange} />
				</div>
			</header>

			{checked ? (
				<div className="dctc-ai-card__body dctc-ai-conditional-card__body">
					{children}
				</div>
			) : (
				(disabledNotice || disabledContent) && (
					<div className="dctc-ai-conditional-card__disabled-notice">
						{disabledNotice && (
							<div className="dctc-ai-disabled-notice-row">
								<span className="dashicons dashicons-info" aria-hidden="true" />
								<span>{disabledNotice}</span>
							</div>
						)}
						{disabledContent}
					</div>
				)
			)}
		</section>
	);
}
