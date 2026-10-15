/**
 * Support Center Pagination - wraps the global shared Pagination component.
 */
import Pagination from '../../common/components/Pagination';

export default function SupportPagination({
	page = 1,
	currentPage,
	totalPages = 1,
	total = null,
	totalItems,
	onPageChange,
	setPage,
	className = '',
	itemLabel,
}) {
	const activePage = currentPage !== undefined ? currentPage : page;
	const activeTotal = totalItems !== undefined ? totalItems : total;
	const handleChange = onPageChange || setPage;

	return (
		<Pagination
			currentPage={activePage}
			totalPages={totalPages}
			totalItems={activeTotal}
			onPageChange={handleChange}
			className={`dctc-sc-pagination ${className}`}
			itemLabel={itemLabel}
		/>
	);
}
