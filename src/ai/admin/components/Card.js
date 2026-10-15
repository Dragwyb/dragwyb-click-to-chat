/**
 * Standard Section Card component with header, title, description, actions, and body.
 */
export default function Card({
	title,
	desc = null,
	icon = null,
	badge = null,
	actions = null,
	children,
	className = '',
	headerClassName = '',
	bodyClassName = '',
}) {
	return (
		<section className={`dctc-ai-card ${className}`}>
			{(title || desc || icon || badge || actions) && (
				<header className={`dctc-ai-card__header ${headerClassName}`}>
					<div className="dctc-ai-card__header-left">
						{icon && (
							<div className="dctc-ai-card-icon" aria-hidden="true">
								<span className={`dashicons ${icon}`} />
							</div>
						)}
						<div>
							<div className="dctc-ai-header-with-badge">
								{title && <h2 className="dctc-ai-card__title">{title}</h2>}
								{badge && <span className="dctc-ai-mini-badge">{badge}</span>}
							</div>
							{desc && <p className="dctc-ai-card__desc">{desc}</p>}
						</div>
					</div>
					{actions && <div className="dctc-ai-card__header-right">{actions}</div>}
				</header>
			)}
			<div className={`dctc-ai-card__body ${bodyClassName}`}>
				{children}
			</div>
		</section>
	);
}
