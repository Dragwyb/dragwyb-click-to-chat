/**
 * Support Center - Tags View
 */
import { __ } from '@wordpress/i18n';

export default function TagsView( { tags } ) {
	return (
		<div className="dctc-sc-panel-box">
			<div className="dctc-sc-panel-header">
				<div className="dctc-sc-panel-icon-wrap icon-amber">
					<span className="dashicons dashicons-tag"></span>
				</div>
				<div>
					<h3>{ __( 'Support Tags Taxonomy', 'dragwyb-click-to-chat' ) }</h3>
					<p className="dctc-sc-panel-sub">{ __( 'Visual tags for quick identification and categorization of customer tickets.', 'dragwyb-click-to-chat' ) }</p>
				</div>
			</div>

			<div className="dctc-sc-tags-grid">
				{ tags.length === 0 ? (
					<p>{ __( 'No tags created yet.', 'dragwyb-click-to-chat' ) }</p>
				) : (
					tags.map( ( tag ) => (
						<div key={ tag.id } className="dctc-sc-tag-pill" style={ { borderColor: tag.color } }>
							<span className="dctc-sc-tag-dot" style={ { backgroundColor: tag.color } }></span>
							<strong>{ tag.name }</strong>
							<code>{ tag.slug }</code>
						</div>
					) )
				) }
			</div>
		</div>
	);
}
