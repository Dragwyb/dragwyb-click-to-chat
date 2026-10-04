/**
 * Support Center - Agents & Staff View
 */
import { __ } from '@wordpress/i18n';

export default function AgentsView( { agents, onRefresh } ) {
	return (
		<div className="dctc-sc-panel-box">
			<div className="dctc-sc-panel-header">
				<div className="dctc-sc-panel-icon-wrap icon-indigo">
					<span className="dashicons dashicons-groups"></span>
				</div>
				<div>
					<h3>{ __( 'Support Staff & Agent Roster', 'dragwyb-click-to-chat' ) }</h3>
					<p className="dctc-sc-panel-sub">{ __( 'Manage support specialist availability, routing skills, and active ticket capacities.', 'dragwyb-click-to-chat' ) }</p>
				</div>
				<button
					type="button"
					className="dctc-sc-panel-header-btn"
					onClick={ onRefresh }
					style={ { marginLeft: 'auto' } }
				>
					<span className="dashicons dashicons-update"></span>
					{ __( 'Refresh Roster', 'dragwyb-click-to-chat' ) }
				</button>
			</div>

			<table className="wp-list-table widefat fixed striped dctc-sc-table">
				<thead>
					<tr>
						<th>{ __( 'Agent Name', 'dragwyb-click-to-chat' ) }</th>
						<th>{ __( 'Email', 'dragwyb-click-to-chat' ) }</th>
						<th>{ __( 'Support Role', 'dragwyb-click-to-chat' ) }</th>
						<th>{ __( 'Seniority', 'dragwyb-click-to-chat' ) }</th>
						<th>{ __( 'Availability', 'dragwyb-click-to-chat' ) }</th>
						<th>{ __( 'Active Load', 'dragwyb-click-to-chat' ) }</th>
						<th>{ __( 'Assigned Skills', 'dragwyb-click-to-chat' ) }</th>
					</tr>
				</thead>
				<tbody>
					{ agents.length === 0 ? (
						<tr>
							<td colSpan="7" style={ { textAlign: 'center', padding: '30px' } }>
								{ __( 'No active staff agents found.', 'dragwyb-click-to-chat' ) }
							</td>
						</tr>
					) : (
						agents.map( ( ag ) => (
							<tr key={ ag.id }>
								<td>
									<div className="dctc-sc-agent-cell">
										<span className={ `dctc-sc-status-indicator ${ ag.availability_status }` }></span>
										<strong>{ ag.display_name }</strong>
									</div>
								</td>
								<td>{ ag.user_email }</td>
								<td><span className="dctc-sc-role-pill">{ ag.support_role }</span></td>
								<td><code>{ ag.seniority }</code></td>
								<td>
									<span className={ `dctc-sc-avail-badge ${ ag.availability_status }` }>
										{ ag.availability_status === 'available' && '🟢 Available' }
										{ ag.availability_status === 'away' && '🟡 Away' }
										{ ag.availability_status === 'offline' && '🔴 Offline' }
									</span>
								</td>
								<td>
									<strong>{ ag.current_active_tickets || 0 }</strong> / { ag.max_active_tickets || 10 }
								</td>
								<td>
									<div className="dctc-sc-skills-wrap">
										{ ( ag.skills || [] ).map( ( s ) => (
											<span key={ s } className="dctc-sc-skill-tag">{ s }</span>
										) ) }
									</div>
								</td>
							</tr>
						) )
					) }
				</tbody>
			</table>
		</div>
	);
}
