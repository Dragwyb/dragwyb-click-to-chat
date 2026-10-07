import { __, sprintf } from '@wordpress/i18n';

export default function Pagination({
	currentPage = 1,
	totalPages = 1,
	totalItems = null,
	onPageChange,
	className = '',
	itemLabel = __('items', 'dragwyb-click-to-chat'),
}) {
	if (totalPages <= 1) return null;

	const handlePrev = () => {
		if (currentPage > 1 && onPageChange) {
			onPageChange(currentPage - 1);
		}
	};

	const handleNext = () => {
		if (currentPage < totalPages && onPageChange) {
			onPageChange(currentPage + 1);
		}
	};

	return (
		<div className={`dctc-global-pagination ${className}`}>
			<div className="dctc-global-pagination-info">
				{totalItems !== null ? (
					sprintf(
						/* translators: 1: Current page, 2: Total pages, 3: Total items count, 4: Item label */
						__('Page %1$d of %2$d (%3$d %4$s)', 'dragwyb-click-to-chat'),
						currentPage,
						totalPages,
						totalItems,
						itemLabel
					)
				) : (
					sprintf(
						/* translators: 1: Current page, 2: Total pages */
						__('Page %1$d of %2$d', 'dragwyb-click-to-chat'),
						currentPage,
						totalPages
					)
				)}
			</div>
			<div className="dctc-global-pagination-nav">
				<button
					type="button"
					className="dctc-global-page-btn"
					disabled={currentPage <= 1}
					onClick={handlePrev}
					aria-label={__('Previous page', 'dragwyb-click-to-chat')}
				>
					&laquo; {__('Previous', 'dragwyb-click-to-chat')}
				</button>
				<span className="dctc-global-page-indicator">
					{currentPage} / {totalPages}
				</span>
				<button
					type="button"
					className="dctc-global-page-btn"
					disabled={currentPage >= totalPages}
					onClick={handleNext}
					aria-label={__('Next page', 'dragwyb-click-to-chat')}
				>
					{__('Next', 'dragwyb-click-to-chat')} &raquo;
				</button>
			</div>
		</div>
	);
}
