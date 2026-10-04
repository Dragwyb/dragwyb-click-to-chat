/**
 * Support Center - Tags View (Taxonomy Hub forwarder)
 */
import CategoriesView from './CategoriesView';

export default function TagsView( props ) {
	return <CategoriesView { ...props } initialSubTab="tags" />;
}
