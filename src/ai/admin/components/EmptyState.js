/**
 * EmptyState: Standard placeholder for empty lists, search results, or tables.
 */
export default function EmptyState({
	icon = 'dashicons-info-outline',
	title,
	message,
	action = null,
	className = '',
}) {
	return (
		<div className={`dctc-ai-empty-state ${className}`}>
			<div className="dctc-ai-empty-state__icon" aria-hidden="true">
				<span className={`dashicons ${icon}`} />
			</div>
			{title && <h3 className="dctc-ai-empty-state__title">{title}</h3>}
			{message && <p className="dctc-ai-empty-state__message">{message}</p>}
			{action && <div className="dctc-ai-empty-state__action">{action}</div>}
		</div>
	);
}
