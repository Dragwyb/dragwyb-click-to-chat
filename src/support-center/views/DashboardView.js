/**
 * Support Center - Overview Dashboard View
 */
import { __ } from '@wordpress/i18n';

export default function DashboardView( {
	dashboardStats,
	statsLoading,
	statusUpdating,
	onUpdateStatus,
	onRefresh,
	onJumpToTickets,
	onSwitchTab,
} ) {
	const agent = dashboardStats?.agent || {};

	return (
		<div className="dctc-sc-dashboard-container">
			{ /* Agent Profile & Workload Hero Banner */ }
			<div className="dctc-sc-hero-banner">
				<div className="dctc-sc-hero-agent-info">
					<div className="dctc-sc-hero-avatar-wrap">
						{ agent?.avatar ? (
							<img
								src={ agent.avatar }
								alt={ agent.display_name || '' }
								className="dctc-sc-hero-avatar-img"
							/>
						) : (
							<div className="dctc-sc-hero-avatar-placeholder">
								<span className="dashicons dashicons-admin-users"></span>
							</div>
						) }
						<span
							className={ `dctc-sc-avatar-status-dot ${ agent?.availability_status || 'available' }` }
							title={ `Status: ${ agent?.availability_status || 'available' }` }
						/>
					</div>

					<div className="dctc-sc-hero-agent-text">
						<div className="dctc-sc-hero-name-row">
							<h2 className="dctc-sc-hero-title">
								{ __( 'Welcome back,', 'dragwyb-click-to-chat' ) }{ ' ' }
								<span className="dctc-sc-highlight-name">
									{ agent?.display_name || __( 'Support Specialist', 'dragwyb-click-to-chat' ) }
								</span>
							</h2>
							<span className="dctc-sc-hero-role-badge">
								{ agent?.support_role || __( 'Specialist', 'dragwyb-click-to-chat' ) }
							</span>
						</div>
						<p className="dctc-sc-hero-subtitle">
							{ agent?.user_email || __( 'Support Staff Member', 'dragwyb-click-to-chat' ) } •{ ' ' }
							<span className="dctc-sc-hero-workload-text">
								{ __( 'Workload:', 'dragwyb-click-to-chat' ) }{ ' ' }
								<strong>{ agent?.current_active ?? 0 }</strong> / { agent?.max_active ?? 10 } { __( 'active tickets', 'dragwyb-click-to-chat' ) }
							</span>
						</p>
					</div>
				</div>

				{ /* Availability Controls & Refresh */ }
				<div className="dctc-sc-hero-actions">
					<div className="dctc-sc-presence-pill-group">
						<span className="dctc-sc-presence-label">{ __( 'Availability:', 'dragwyb-click-to-chat' ) }</span>
						{ [
							{ id: 'available', label: __( 'Available', 'dragwyb-click-to-chat' ), icon: '🟢' },
							{ id: 'away', label: __( 'Away', 'dragwyb-click-to-chat' ), icon: '🟡' },
							{ id: 'offline', label: __( 'Offline', 'dragwyb-click-to-chat' ), icon: '🔴' },
						].map( ( item ) => (
							<button
								key={ item.id }
								type="button"
								disabled={ statusUpdating }
								className={ `dctc-sc-presence-btn ${ ( agent?.availability_status || 'available' ) === item.id ? 'active ' + item.id : '' }` }
								onClick={ () => onUpdateStatus( item.id ) }
							>
								<span>{ item.icon }</span> { item.label }
							</button>
						) ) }
					</div>

					<button
						type="button"
						className="dctc-sc-dash-refresh-btn"
						onClick={ onRefresh }
						disabled={ statsLoading }
						title={ __( 'Refresh Dashboard Data', 'dragwyb-click-to-chat' ) }
					>
						<span className={ `dashicons dashicons-update ${ statsLoading ? 'rotating' : '' }` }></span>
						{ __( 'Refresh', 'dragwyb-click-to-chat' ) }
					</button>
				</div>
			</div>

			{ /* 4 Modern Glass KPI Metric Cards */ }
			<div className="dctc-sc-kpi-grid">
				{ /* Card 1: Today Assigned */ }
				<div className="dctc-sc-kpi-card card-blue" onClick={ () => onJumpToTickets( 'all' ) }>
					<div className="dctc-sc-kpi-header">
						<span className="dctc-sc-kpi-title">{ __( "Today's Assigned", 'dragwyb-click-to-chat' ) }</span>
						<span className="dctc-sc-kpi-icon-wrap icon-blue">
							<span className="dashicons dashicons-calendar-alt"></span>
						</span>
					</div>
					<div className="dctc-sc-kpi-value">{ dashboardStats?.today_assigned_tickets ?? 0 }</div>
					<div className="dctc-sc-kpi-footer">
						<span className="dctc-sc-kpi-subtext">
							{ dashboardStats?.today_created ?? 0 } { __( 'new tickets site-wide today', 'dragwyb-click-to-chat' ) }
						</span>
						<span className="dctc-sc-kpi-arrow">→</span>
					</div>
				</div>

				{ /* Card 2: Total Pending Action */ }
				<div className="dctc-sc-kpi-card card-amber" onClick={ () => onJumpToTickets( 'pending' ) }>
					<div className="dctc-sc-kpi-header">
						<span className="dctc-sc-kpi-title">{ __( 'Pending Action', 'dragwyb-click-to-chat' ) }</span>
						<span className="dctc-sc-kpi-icon-wrap icon-amber">
							<span className="dashicons dashicons-clock"></span>
						</span>
					</div>
					<div className="dctc-sc-kpi-value">{ dashboardStats?.total_pending ?? 0 }</div>
					<div className="dctc-sc-kpi-footer">
						<span className="dctc-sc-kpi-subtext">
							{ __( 'Requires response or customer follow-up', 'dragwyb-click-to-chat' ) }
						</span>
						<span className="dctc-sc-kpi-arrow">→</span>
					</div>
				</div>

				{ /* Card 3: Total Active Open */ }
				<div className="dctc-sc-kpi-card card-indigo" onClick={ () => onJumpToTickets( 'open' ) }>
					<div className="dctc-sc-kpi-header">
						<span className="dctc-sc-kpi-title">{ __( 'Active Open Tickets', 'dragwyb-click-to-chat' ) }</span>
						<span className="dctc-sc-kpi-icon-wrap icon-indigo">
							<span className="dashicons dashicons-tickets-alt"></span>
						</span>
					</div>
					<div className="dctc-sc-kpi-value">{ dashboardStats?.total_open ?? 0 }</div>
					<div className="dctc-sc-kpi-footer">
						<span className="dctc-sc-kpi-subtext">
							{ dashboardStats?.total_tickets ?? 0 } { __( 'total tickets', 'dragwyb-click-to-chat' ) } ({ dashboardStats?.total_resolved ?? 0 } { __( 'resolved', 'dragwyb-click-to-chat' ) })
						</span>
						<span className="dctc-sc-kpi-arrow">→</span>
					</div>
				</div>

				{ /* Card 4: Hybrid Control */ }
				<div className="dctc-sc-kpi-card card-purple" onClick={ () => onSwitchTab( 'tickets' ) }>
					<div className="dctc-sc-kpi-header">
						<span className="dctc-sc-kpi-title">{ __( 'AI vs Agent Control', 'dragwyb-click-to-chat' ) }</span>
						<span className="dctc-sc-kpi-icon-wrap icon-purple">
							<span className="dashicons dashicons-admin-generic"></span>
						</span>
					</div>
					<div className="dctc-sc-kpi-split-value">
						<div className="dctc-sc-split-item">
							<span className="dctc-sc-split-num">{ dashboardStats?.ai_controlled_tickets ?? 0 }</span>
							<span className="dctc-sc-split-tag">🤖 { __( 'AI Bot', 'dragwyb-click-to-chat' ) }</span>
						</div>
						<div className="dctc-sc-split-divider">/</div>
						<div className="dctc-sc-split-item">
							<span className="dctc-sc-split-num">{ dashboardStats?.human_controlled_tickets ?? 0 }</span>
							<span className="dctc-sc-split-tag">👤 { __( 'Human Staff', 'dragwyb-click-to-chat' ) }</span>
						</div>
					</div>
					<div className="dctc-sc-kpi-footer">
						<span className="dctc-sc-kpi-subtext">
							{ __( 'Live hybrid handoff & takeover active', 'dragwyb-click-to-chat' ) }
						</span>
						<span className="dctc-sc-kpi-arrow">→</span>
					</div>
				</div>
			</div>

			{ /* Quick Jump Launchpad */ }
			<div className="dctc-sc-section-card">
				<div className="dctc-sc-card-header">
					<h3>
						<span className="dashicons dashicons-randomize"></span>
						{ __( 'Quick Filter Launchers', 'dragwyb-click-to-chat' ) }
					</h3>
				</div>
				<div className="dctc-sc-quick-jump-bar">
					<button type="button" className="dctc-sc-quick-jump-btn" onClick={ () => onJumpToTickets( 'all' ) }>
						<span className="dashicons dashicons-list-view"></span>
						{ __( 'All Tickets', 'dragwyb-click-to-chat' ) } ({ dashboardStats?.total_tickets ?? 0 })
					</button>
					<button type="button" className="dctc-sc-quick-jump-btn" onClick={ () => onJumpToTickets( 'open' ) }>
						<span className="dashicons dashicons-flag"></span>
						{ __( 'Open Tickets', 'dragwyb-click-to-chat' ) } ({ dashboardStats?.total_open ?? 0 })
					</button>
					<button type="button" className="dctc-sc-quick-jump-btn" onClick={ () => onJumpToTickets( 'pending' ) }>
						<span className="dashicons dashicons-clock"></span>
						{ __( 'Pending Follow-up', 'dragwyb-click-to-chat' ) } ({ dashboardStats?.total_pending ?? 0 })
					</button>
					<button type="button" className="dctc-sc-quick-jump-btn" onClick={ () => onJumpToTickets( 'resolved' ) }>
						<span className="dashicons dashicons-yes-alt"></span>
						{ __( 'Resolved & Closed', 'dragwyb-click-to-chat' ) } ({ dashboardStats?.total_resolved ?? 0 })
					</button>
					<button type="button" className="dctc-sc-quick-jump-btn btn-outline" onClick={ () => onSwitchTab( 'agents' ) }>
						<span className="dashicons dashicons-groups"></span>
						{ __( 'Agents & Staff Roster', 'dragwyb-click-to-chat' ) }
					</button>
					<button type="button" className="dctc-sc-quick-jump-btn btn-outline" onClick={ () => onSwitchTab( 'taxonomies' ) }>
						<span className="dashicons dashicons-category"></span>
						{ __( 'Taxonomies & Routing', 'dragwyb-click-to-chat' ) }
					</button>
				</div>
			</div>

			{ /* Live Activity Stream */ }
			<div className="dctc-sc-section-card">
				<div className="dctc-sc-card-header">
					<h3>
						<span className="dashicons dashicons-rss"></span>
						{ __( 'Live Support Activity & Audit Stream', 'dragwyb-click-to-chat' ) }
					</h3>
					<span className="dctc-sc-badge-live">● { __( 'Live Feed', 'dragwyb-click-to-chat' ) }</span>
				</div>

				{ ( ! dashboardStats?.recent_activity || dashboardStats.recent_activity.length === 0 ) ? (
					<div className="dctc-sc-empty-activity">
						<span className="dashicons dashicons-info-outline"></span>
						<p>{ __( 'No recent support ticket events recorded yet.', 'dragwyb-click-to-chat' ) }</p>
					</div>
				) : (
					<div className="dctc-sc-activity-stream-list">
						{ dashboardStats.recent_activity.map( ( ev ) => (
							<div
								key={ ev.id }
								className="dctc-sc-activity-stream-item"
								onClick={ () => onJumpToTickets( 'all', ev.ticket_id ) }
								title={ __( 'Click to view ticket', 'dragwyb-click-to-chat' ) }
							>
								<div className="dctc-sc-activity-icon">
									{ ev.event_type === 'created' && '➕' }
									{ ev.event_type === 'customer_reply' && '💬' }
									{ ev.event_type === 'agent_reply' && '👤' }
									{ ev.event_type === 'control_mode_changed' && '🔄' }
									{ ev.event_type === 'status_changed' && '🏷️' }
									{ ev.event_type === 'priority_changed' && '⚡' }
									{ ev.event_type === 'assigned' && '🎯' }
									{ ev.event_type === 'note_added' && '📝' }
									{ ! [ 'created', 'customer_reply', 'agent_reply', 'control_mode_changed', 'status_changed', 'priority_changed', 'assigned', 'note_added' ].includes( ev.event_type ) && '📌' }
								</div>
								<div className="dctc-sc-activity-content">
									<div className="dctc-sc-activity-top-line">
										<span className="dctc-sc-activity-ticket-badge">
											#{ ev.ticket_number || ev.ticket_id }
										</span>
										<span className="dctc-sc-activity-subject">
											{ ev.ticket_subject || __( 'Support Ticket', 'dragwyb-click-to-chat' ) }
										</span>
										<span className="dctc-sc-activity-time">{ ev.created_at }</span>
									</div>
									<div className="dctc-sc-activity-event-desc">
										{ ev.event_data?.message || ev.event_data?.note || `${ ev.event_type.replace( /_/g, ' ' ) }` }
									</div>
								</div>
								<div className="dctc-sc-activity-jump-icon">
									<span className="dashicons dashicons-arrow-right-alt2"></span>
								</div>
							</div>
						) ) }
					</div>
				) }
			</div>
		</div>
	);
}
