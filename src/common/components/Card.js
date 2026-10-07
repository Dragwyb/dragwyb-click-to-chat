/**
 * Global Card component for sections and panels across the plugin.
 */
export default function Card({
	title,
	desc = null,
	icon = null,
	iconClass = '',
	badge = null,
	actions = null,
	children,
	className = '',
	headerClassName = '',
	bodyClassName = '',
	headerContent = null,
}) {
	const hasHeader = title || desc || icon || badge || actions || headerContent;

	return (
		<section className={`dctc-ai-card ${className}`}>
			{hasHeader && (
				<header className={`dctc-ai-card__header ${headerClassName}`}>
					{headerContent ? (
						headerContent
					) : (
						<>
							<div className="dctc-ai-card__header-left">
								{icon && (
									<div className={`dctc-ai-card-icon ${iconClass}`} aria-hidden="true">
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
						</>
					)}
				</header>
			)}
			<div className={`dctc-ai-card__body ${bodyClassName}`}>
				{children}
			</div>
		</section>
	);
}
