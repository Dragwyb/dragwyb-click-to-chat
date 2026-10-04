/**
 * Support Center - Categories & Skills View
 */
import { __ } from '@wordpress/i18n';

export default function CategoriesView( { categories } ) {
	const getPriorityBadgeClass = ( priority ) => {
		switch ( priority ) {
			case 'urgent': return 'dctc-sc-badge-urgent';
			case 'high': return 'dctc-sc-badge-high';
			case 'normal': return 'dctc-sc-badge-normal';
			default: return 'dctc-sc-badge-low';
		}
	};

	return (
		<div className="dctc-sc-panel-box">
			<div className="dctc-sc-panel-header">
				<div className="dctc-sc-panel-icon-wrap icon-purple">
					<span className="dashicons dashicons-category"></span>
				</div>
				<div>
					<h3>{ __( 'Support Categories & Skill Routing', 'dragwyb-click-to-chat' ) }</h3>
					<p className="dctc-sc-panel-sub">{ __( 'Define ticket classification taxonomies, default priorities, and skill-matching requirements.', 'dragwyb-click-to-chat' ) }</p>
				</div>
			</div>

			<table className="wp-list-table widefat fixed striped dctc-sc-table">
				<thead>
					<tr>
						<th>{ __( 'Category Name', 'dragwyb-click-to-chat' ) }</th>
						<th>{ __( 'Slug', 'dragwyb-click-to-chat' ) }</th>
						<th>{ __( 'Default Priority', 'dragwyb-click-to-chat' ) }</th>
						<th>{ __( 'Required Skills', 'dragwyb-click-to-chat' ) }</th>
						<th>{ __( 'AI Allowed', 'dragwyb-click-to-chat' ) }</th>
					</tr>
				</thead>
				<tbody>
					{ categories.length === 0 ? (
						<tr>
							<td colSpan="5" style={ { textAlign: 'center', padding: '30px' } }>
								{ __( 'No support categories found.', 'dragwyb-click-to-chat' ) }
							</td>
						</tr>
					) : (
						categories.map( ( cat ) => (
							<tr key={ cat.id }>
								<td><strong>{ cat.name }</strong></td>
								<td><code>{ cat.slug }</code></td>
								<td><span className={ `dctc-sc-badge ${ getPriorityBadgeClass( cat.default_priority ) }` }>{ cat.default_priority }</span></td>
								<td>
									<div className="dctc-sc-skills-wrap">
										{ ( cat.required_skills || [] ).map( ( s ) => (
											<span key={ s } className="dctc-sc-skill-tag">{ s }</span>
										) ) }
									</div>
								</td>
								<td>{ cat.ai_allowed ? '✅ Yes' : '❌ Human Staff Only' }</td>
							</tr>
						) )
					) }
				</tbody>
			</table>
		</div>
	);
}
