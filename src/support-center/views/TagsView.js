/**
 * Support Center - Tags View (Taxonomy Hub forwarder)
 */
import TaxonomiesView from './TaxonomiesView';

export default function TagsView( props ) {
	return <TaxonomiesView { ...props } initialSubTab="tags" />;
}
