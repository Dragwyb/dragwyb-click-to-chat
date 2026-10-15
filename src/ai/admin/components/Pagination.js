/**
 * Pagination: Standard pagination controls for list and table views.
 */
import { __, sprintf } from '@wordpress/i18n';

export default function Pagination({
	currentPage,
	totalPages,
	totalItems = null,
	onPageChange,
	itemsPerPage = null,
	onItemsPerPageChange = null,
	itemsPerPageOptions = [10, 20, 50, 100],
	className = '',
}) {
	if (totalPages <= 1 && !totalItems) {
		return null;
	}

	return (
		<div className={`dctc-ai-pagination ${className}`}>
			<div className="dctc-ai-pagination__info">
				{totalItems !== null && (
					<span className="dctc-ai-pagination__total">
						{sprintf(__('Total: %d', 'dragwyb-click-to-chat'), totalItems)}
					</span>
				)}
				{itemsPerPage !== null && onItemsPerPageChange && (
					<div className="dctc-ai-pagination__per-page">
						<span>{__('Show:', 'dragwyb-click-to-chat')}</span>
						<select
							value={itemsPerPage}
							onChange={(e) => onItemsPerPageChange(Number(e.target.value))}
							className="dctc-ai-pagination__select"
						>
							{itemsPerPageOptions.map((opt) => (
								<option key={opt} value={opt}>
									{opt}
								</option>
							))}
						</select>
					</div>
				)}
			</div>

			<div className="dctc-ai-pagination__controls">
				<button
					type="button"
					className="dctc-ai-pagination__btn"
					disabled={currentPage <= 1}
					onClick={() => onPageChange(currentPage - 1)}
					aria-label={__('Previous page', 'dragwyb-click-to-chat')}
				>
					<span className="dashicons dashicons-arrow-left-alt2" />
					<span>{__('Prev', 'dragwyb-click-to-chat')}</span>
				</button>

				<span className="dctc-ai-pagination__current">
					{sprintf(
						__('Page %1$d of %2$d', 'dragwyb-click-to-chat'),
						currentPage,
						Math.max(1, totalPages)
					)}
				</span>

				<button
					type="button"
					className="dctc-ai-pagination__btn"
					disabled={currentPage >= totalPages}
					onClick={() => onPageChange(currentPage + 1)}
					aria-label={__('Next page', 'dragwyb-click-to-chat')}
				>
					<span>{__('Next', 'dragwyb-click-to-chat')}</span>
					<span className="dashicons dashicons-arrow-right-alt2" />
				</button>
			</div>
		</div>
	);
}
