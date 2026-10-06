/**
 * Support Center - Settings View
 */
import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

const PERMISSION_DEFINITIONS = [
	{
		key: 'full_admin_access',
		label: __( 'Full Admin Access', 'dragwyb-click-to-chat' ),
		desc: __( 'Unrestricted super-admin control across all support center operations, settings & agents.', 'dragwyb-click-to-chat' ),
		icon: 'dashicons-shield-alt',
		color: '#4f46e5',
	},
	{
		key: 'manage_agents',
		label: __( 'Manage Agents & Staff', 'dragwyb-click-to-chat' ),
		desc: __( 'Add new agents to roster, edit capacities/skills, or remove staff members.', 'dragwyb-click-to-chat' ),
		icon: 'dashicons-groups',
		color: '#2563eb',
	},
	{
		key: 'manage_categories',
		label: __( 'Manage Categories', 'dragwyb-click-to-chat' ),
		desc: __( 'Create, edit, auto-routing rules, and delete ticket issue categories.', 'dragwyb-click-to-chat' ),
		icon: 'dashicons-category',
		color: '#0891b2',
	},
	{
		key: 'manage_tags',
		label: __( 'Manage Tags', 'dragwyb-click-to-chat' ),
		desc: __( 'Create, color-code, and delete support ticket organizational tags.', 'dragwyb-click-to-chat' ),
		icon: 'dashicons-tag',
		color: '#059669',
	},
	{
		key: 'manage_settings',
		label: __( 'Manage Support Settings', 'dragwyb-click-to-chat' ),
		desc: __( 'Modify global helpdesk configuration, assignment algorithms, SLAs, and permissions.', 'dragwyb-click-to-chat' ),
		icon: 'dashicons-admin-generic',
		color: '#d97706',
	},
	{
		key: 'view_all_tickets',
		label: __( 'View All System Tickets', 'dragwyb-click-to-chat' ),
		desc: __( 'View entire helpdesk ticket queue vs only tickets assigned directly to this agent.', 'dragwyb-click-to-chat' ),
		icon: 'dashicons-visibility',
		color: '#475569',
	},
	{
		key: 'assign_ticket',
		label: __( 'Assign & Transfer Tickets', 'dragwyb-click-to-chat' ),
		desc: __( 'Manually assign unassigned tickets or transfer/reassign tickets to other staff members.', 'dragwyb-click-to-chat' ),
		icon: 'dashicons-migrate',
		color: '#7c3aed',
	},
	{
		key: 'take_ai_control',
		label: __( 'Take / Release AI Control', 'dragwyb-click-to-chat' ),
		desc: __( 'Manually pause AI bot to take over customer conversation or resume AI assistant.', 'dragwyb-click-to-chat' ),
		icon: 'dashicons-admin-customizer',
		color: '#0284c7',
	},
	{
		key: 'change_priority',
		label: __( 'Change Ticket Priority', 'dragwyb-click-to-chat' ),
		desc: __( 'Elevate or downgrade ticket urgency (Low, Normal, High, Urgent).', 'dragwyb-click-to-chat' ),
		icon: 'dashicons-flag',
		color: '#ea580c',
	},
	{
		key: 'delete_ticket',
		label: __( 'Delete Tickets', 'dragwyb-click-to-chat' ),
		desc: __( 'Permanently remove customer support tickets from the database.', 'dragwyb-click-to-chat' ),
		icon: 'dashicons-trash',
		color: '#dc2626',
	},
	{
		key: 'view_woocommerce_data',
		label: __( 'View WooCommerce Orders & Context', 'dragwyb-click-to-chat' ),
		desc: __( 'Access customer lifetime value, recent store orders, and shipping tracking in ticket sidebar.', 'dragwyb-click-to-chat' ),
		icon: 'dashicons-cart',
		color: '#9333ea',
	},
];

const ROLES = [
	{ key: 'admin', label: __( 'Administrator', 'dragwyb-click-to-chat' ), icon: 'dashicons-shield', badge: 'Full Admin' },
	{ key: 'manager', label: __( 'Support Manager', 'dragwyb-click-to-chat' ), icon: 'dashicons-admin-generic', badge: 'Manager' },
	{ key: 'senior', label: __( 'Senior Specialist', 'dragwyb-click-to-chat' ), icon: 'dashicons-star-filled', badge: 'Tier 2' },
	{ key: 'support', label: __( 'Support Agent', 'dragwyb-click-to-chat' ), icon: 'dashicons-businesswoman', badge: 'Standard' },
	{ key: 'fresher', label: __( 'Fresher / Junior', 'dragwyb-click-to-chat' ), icon: 'dashicons-welcome-learn-more', badge: 'Tier 1' },
];

export default function SettingsView( {
	supportSettings,
	setSupportSettings,
	permissionsMatrix: initialMatrix,
	setPermissionsMatrix: setParentMatrix,
	onShowNotice,
} ) {
	const [ saving, setSaving ] = useState( false );
	const [ localNotice, setLocalNotice ] = useState( null );
	const [ matrix, setMatrix ] = useState( initialMatrix || {} );

	useEffect( () => {
		if ( initialMatrix ) {
			setMatrix( initialMatrix );
		} else {
			apiFetch( { path: '/dctc-ai/v1/support/permissions' } )
				.then( ( res ) => {
					if ( res?.permissions_matrix ) {
						setMatrix( res.permissions_matrix );
						if ( setParentMatrix ) setParentMatrix( res.permissions_matrix );
					}
				} )
				.catch( ( err ) => console.error( 'Error fetching permissions matrix:', err ) );
		}
	}, [ initialMatrix, setParentMatrix ] );

	const handleTogglePermission = ( roleKey, capKey ) => {
		if ( roleKey === 'admin' ) return; // Admin always retains full privileges
		setMatrix( ( prev ) => {
			const roleCaps = prev[ roleKey ] || {};
			const updated = {
				...prev,
				[ roleKey ]: {
					...roleCaps,
					[ capKey ]: ! roleCaps[ capKey ],
				},
			};
			return updated;
		} );
	};

	const handleSubmit = async ( e ) => {
		if ( e ) e.preventDefault();
		setSaving( true );
		setLocalNotice( null );
		try {
			const [ setRes, permRes ] = await Promise.all( [
				apiFetch( {
					path: '/dctc-ai/v1/support/settings',
					method: 'POST',
					data: supportSettings,
				} ),
				apiFetch( {
					path: '/dctc-ai/v1/support/permissions',
					method: 'POST',
					data: matrix,
				} ),
			] );

			if ( setRes?.success ) {
				setSupportSettings( setRes.settings || supportSettings );
			}
			if ( permRes?.success && permRes?.permissions_matrix ) {
				setMatrix( permRes.permissions_matrix );
				if ( setParentMatrix ) setParentMatrix( permRes.permissions_matrix );
			}

			setLocalNotice( { type: 'success', message: __( 'Support settings & staff permissions saved successfully!', 'dragwyb-click-to-chat' ) } );
			if ( onShowNotice ) onShowNotice( __( 'Support settings & staff permissions saved successfully!', 'dragwyb-click-to-chat' ), 'success' );
		} catch ( err ) {
			console.error( 'Error saving support settings:', err );
			setLocalNotice( { type: 'error', message: err?.message || __( 'Error saving settings.', 'dragwyb-click-to-chat' ) } );
		} finally {
			setSaving( false );
			setTimeout( () => setLocalNotice( null ), 5000 );
		}
	};

	const isWcActive = !!window.dctc_support_data?.is_woocommerce_active;
	const activePermissions = PERMISSION_DEFINITIONS.filter( ( p ) => p.key !== 'view_woocommerce_data' || isWcActive );

	return (
		<form onSubmit={ handleSubmit } className="dctc-sc-settings-wrap">
			{ localNotice && (
				<div className={ `dctc-sc-settings-banner ${ localNotice.type }` }>
					<span className={ `dashicons ${ localNotice.type === 'success' ? 'dashicons-yes-alt' : 'dashicons-warning' }` }></span>
					<span>{ localNotice.message }</span>
				</div>
			) }

			{ /* Section 1: Staff Roles & Access Permissions Matrix (Displayed First) */ }
			<div className="dctc-sc-panel-box">
				<div className="dctc-sc-panel-header">
					<div className="dctc-sc-panel-icon-wrap icon-purple">
						<span className="dashicons dashicons-lock"></span>
					</div>
					<div>
						<h3>{ __( 'Staff Roles & Granular Permission Matrix', 'dragwyb-click-to-chat' ) }</h3>
						<p className="dctc-sc-panel-sub">
							{ __( 'Control which staff roles can modify/remove agents, categories, tags, settings, and perform full admin operations.', 'dragwyb-click-to-chat' ) }
						</p>
					</div>
				</div>

				<div style={ { padding: '16px 20px', overflowX: 'auto' } }>
					<table className="wp-list-table widefat fixed striped dctc-sc-perm-table" style={ { width: '100%', borderCollapse: 'collapse' } }>
						<thead>
							<tr>
								<th style={ { width: '38%', textAlign: 'left', padding: '12px 14px' } }>
									{ __( 'Capability / Action', 'dragwyb-click-to-chat' ) }
								</th>
								{ ROLES.map( ( r ) => (
									<th key={ r.key } style={ { textAlign: 'center', padding: '12px 8px', width: '12%' } }>
										<div style={ { display: 'flex', justifyContent: 'center', marginBottom: '4px' } }>
											<span className={ `dashicons ${ r.icon }` } style={ { fontSize: '18px', width: '18px', height: '18px', color: '#4f46e5' } }></span>
										</div>
										<div style={ { fontWeight: 700, fontSize: '12px', color: '#0f172a' } }>{ r.label }</div>
										<span style={ { fontSize: '10.5px', color: '#64748b', fontWeight: 500 } }>{ r.badge }</span>
									</th>
								) ) }
							</tr>
						</thead>
						<tbody>
							{ activePermissions.map( ( p ) => (
								<tr key={ p.key }>
									<td style={ { padding: '12px 14px', verticalAlign: 'middle' } }>
										<div style={ { display: 'flex', alignItems: 'center', gap: '8px' } }>
											<span className={ `dashicons ${ p.icon }` } style={ { color: p.color, fontSize: '18px', width: '18px', height: '18px' } }></span>
											<div>
												<strong style={ { fontSize: '13px', color: '#0f172a' } }>{ p.label }</strong>
												<p style={ { margin: '2px 0 0', fontSize: '11.5px', color: '#64748b', lineHeight: 1.3 } }>{ p.desc }</p>
											</div>
										</div>
									</td>
									{ ROLES.map( ( r ) => {
										const isChecked = r.key === 'admin' ? true : !! ( matrix[ r.key ] && matrix[ r.key ][ p.key ] );
										const isDisabled = r.key === 'admin';
										return (
											<td key={ r.key } style={ { textAlign: 'center', verticalAlign: 'middle', padding: '10px 8px' } }>
												<label style={ { display: 'inline-flex', alignItems: 'center', justifyContent: 'center', cursor: isDisabled ? 'default' : 'pointer' } }>
													<input
														type="checkbox"
														checked={ isChecked }
														disabled={ isDisabled }
														onChange={ () => handleTogglePermission( r.key, p.key ) }
														style={ {
															width: '18px',
															height: '18px',
															borderRadius: '4px',
															accentColor: '#4f46e5',
															cursor: isDisabled ? 'default' : 'pointer',
														} }
													/>
												</label>
											</td>
										);
									} ) }
								</tr>
							) ) }
						</tbody>
					</table>
				</div>
			</div>

			<div className="dctc-sc-settings-columns" style={ { marginTop: '24px' } }>
				{ /* Section 2: General & Routing */ }
				<div className="dctc-sc-panel-box">
					<div className="dctc-sc-panel-header">
						<div className="dctc-sc-panel-icon-wrap icon-indigo">
							<span className="dashicons dashicons-admin-settings"></span>
						</div>
						<div>
							<h3>{ __( 'General & Ticket Routing', 'dragwyb-click-to-chat' ) }</h3>
							<p className="dctc-sc-panel-sub">{ __( 'Configure support system state, assignment algorithms, and bot handoff policies.', 'dragwyb-click-to-chat' ) }</p>
						</div>
					</div>

					<div className="dctc-sc-settings-form-body">
						{ /* Toggle: Enable Support */ }
						<div className="dctc-sc-setting-field-toggle">
							<div className="dctc-sc-toggle-info">
								<label className="dctc-sc-toggle-label">{ __( 'Enable Support Center', 'dragwyb-click-to-chat' ) }</label>
								<span className="dctc-sc-toggle-desc">{ __( 'Activate hybrid ticketing, agent routing, customer portal, and bot takeover.', 'dragwyb-click-to-chat' ) }</span>
							</div>
							<label className="dctc-sc-switch">
								<input
									type="checkbox"
									checked={ !! supportSettings.enabled }
									onChange={ ( e ) => setSupportSettings( ( prev ) => ( { ...prev, enabled: e.target.checked } ) ) }
								/>
								<span className="dctc-sc-slider"></span>
							</label>
						</div>

						{ /* Ticket Prefix */ }
						<div className="dctc-sc-setting-field">
							<label>{ __( 'Ticket Number Prefix', 'dragwyb-click-to-chat' ) }</label>
							<input
								type="text"
								className="regular-text"
								placeholder="TCK-"
								value={ supportSettings.ticket_prefix || 'TCK-' }
								onChange={ ( e ) => setSupportSettings( ( prev ) => ( { ...prev, ticket_prefix: e.target.value } ) ) }
							/>
							<p className="description">{ __( 'Prefix added to generated ticket reference codes (e.g. TCK-1001, SUP-2045).', 'dragwyb-click-to-chat' ) }</p>
						</div>

						{ /* Default Priority */ }
						<div className="dctc-sc-setting-field">
							<label>{ __( 'Default Ticket Priority', 'dragwyb-click-to-chat' ) }</label>
							<select
								value={ supportSettings.default_priority || 'normal' }
								onChange={ ( e ) => setSupportSettings( ( prev ) => ( { ...prev, default_priority: e.target.value } ) ) }
							>
								<option value="low">{ __( 'Low', 'dragwyb-click-to-chat' ) }</option>
								<option value="normal">{ __( 'Normal (Recommended)', 'dragwyb-click-to-chat' ) }</option>
								<option value="high">{ __( 'High', 'dragwyb-click-to-chat' ) }</option>
								<option value="urgent">{ __( 'Urgent', 'dragwyb-click-to-chat' ) }</option>
							</select>
						</div>

						{ /* Assignment Algorithm */ }
						<div className="dctc-sc-setting-field">
							<label>{ __( 'Auto-Assignment Strategy', 'dragwyb-click-to-chat' ) }</label>
							<select
								value={ supportSettings.assignment_algorithm || 'least_loaded' }
								onChange={ ( e ) => setSupportSettings( ( prev ) => ( { ...prev, assignment_algorithm: e.target.value } ) ) }
							>
								<option value="least_loaded">{ __( 'Least Loaded Agent (Workload Balanced)', 'dragwyb-click-to-chat' ) }</option>
								<option value="round_robin">{ __( 'Round Robin (Equally Distributed)', 'dragwyb-click-to-chat' ) }</option>
								<option value="skill_match">{ __( 'Skill & Category Matching Only', 'dragwyb-click-to-chat' ) }</option>
								<option value="manual">{ __( 'Manual Assignment Only (No Auto-Assign)', 'dragwyb-click-to-chat' ) }</option>
							</select>
							<p className="description">{ __( 'Algorithm used when incoming tickets are routed to active staff agents.', 'dragwyb-click-to-chat' ) }</p>
						</div>

						{ /* Toggle: Respect Availability */ }
						<div className="dctc-sc-setting-field-toggle">
							<div className="dctc-sc-toggle-info">
								<label className="dctc-sc-toggle-label">{ __( 'Respect Agent Availability', 'dragwyb-click-to-chat' ) }</label>
								<span className="dctc-sc-toggle-desc">{ __( 'Only assign new tickets to agents with status set to "Available" (skips Away & Offline).', 'dragwyb-click-to-chat' ) }</span>
							</div>
							<label className="dctc-sc-switch">
								<input
									type="checkbox"
									checked={ supportSettings.respect_availability !== false }
									onChange={ ( e ) => setSupportSettings( ( prev ) => ( { ...prev, respect_availability: e.target.checked } ) ) }
								/>
								<span className="dctc-sc-slider"></span>
							</label>
						</div>

						{ /* Toggle: Auto Pause AI on Staff Reply */ }
						<div className="dctc-sc-setting-field-toggle">
							<div className="dctc-sc-toggle-info">
								<label className="dctc-sc-toggle-label">{ __( 'Auto-Pause AI on Staff Reply', 'dragwyb-click-to-chat' ) }</label>
								<span className="dctc-sc-toggle-desc">{ __( 'Automatically pause chatbot AI when a human agent replies to a ticket.', 'dragwyb-click-to-chat' ) }</span>
							</div>
							<label className="dctc-sc-switch">
								<input
									type="checkbox"
									checked={ supportSettings.auto_pause_ai !== false }
									onChange={ ( e ) => setSupportSettings( ( prev ) => ( { ...prev, auto_pause_ai: e.target.checked } ) ) }
								/>
								<span className="dctc-sc-slider"></span>
							</label>
						</div>
					</div>
				</div>

				{ /* Section 3: Email Notifications & Policies */ }
				<div className="dctc-sc-panel-box">
					<div className="dctc-sc-panel-header">
						<div className="dctc-sc-panel-icon-wrap icon-amber">
							<span className="dashicons dashicons-email-alt"></span>
						</div>
						<div>
							<h3>{ __( 'Email & Notification Policies', 'dragwyb-click-to-chat' ) }</h3>
							<p className="dctc-sc-panel-sub">{ __( 'Manage transactional notifications sent to staff agents and customers.', 'dragwyb-click-to-chat' ) }</p>
						</div>
					</div>

					<div className="dctc-sc-settings-form-body">
						{ /* Customer Email Enabled */ }
						<div className="dctc-sc-setting-field-toggle">
							<div className="dctc-sc-toggle-info">
								<label className="dctc-sc-toggle-label">{ __( 'Customer Email Notifications', 'dragwyb-click-to-chat' ) }</label>
								<span className="dctc-sc-toggle-desc">{ __( 'Send email updates to customers when tickets are created, updated, or replied to.', 'dragwyb-click-to-chat' ) }</span>
							</div>
							<label className="dctc-sc-switch">
								<input
									type="checkbox"
									checked={ supportSettings.notifications?.customer_email_enabled !== false }
									onChange={ ( e ) => setSupportSettings( ( prev ) => ( {
										...prev,
										notifications: { ...( prev.notifications || {} ), customer_email_enabled: e.target.checked },
									} ) ) }
								/>
								<span className="dctc-sc-slider"></span>
							</label>
						</div>

						{ /* Agent Assignment Notification */ }
						<div className="dctc-sc-setting-field-toggle">
							<div className="dctc-sc-toggle-info">
								<label className="dctc-sc-toggle-label">{ __( 'Staff Agent Assignment Alerts', 'dragwyb-click-to-chat' ) }</label>
								<span className="dctc-sc-toggle-desc">{ __( 'Notify the agent via email when a new ticket is assigned to them.', 'dragwyb-click-to-chat' ) }</span>
							</div>
							<label className="dctc-sc-switch">
								<input
									type="checkbox"
									checked={ supportSettings.notifications?.agent_assignment !== false }
									onChange={ ( e ) => setSupportSettings( ( prev ) => ( {
										...prev,
										notifications: { ...( prev.notifications || {} ), agent_assignment: e.target.checked },
									} ) ) }
								/>
								<span className="dctc-sc-slider"></span>
							</label>
						</div>

						{ /* Allow Guest Tickets */ }
						<div className="dctc-sc-setting-field-toggle">
							<div className="dctc-sc-toggle-info">
								<label className="dctc-sc-toggle-label">{ __( 'Allow Guest Ticket Submissions', 'dragwyb-click-to-chat' ) }</label>
								<span className="dctc-sc-toggle-desc">{ __( 'Permit non-logged-in visitors to submit support tickets with their email.', 'dragwyb-click-to-chat' ) }</span>
							</div>
							<label className="dctc-sc-switch">
								<input
									type="checkbox"
									checked={ supportSettings.allow_guest_tickets !== false }
									onChange={ ( e ) => setSupportSettings( ( prev ) => ( { ...prev, allow_guest_tickets: e.target.checked } ) ) }
								/>
								<span className="dctc-sc-slider"></span>
							</label>
						</div>
					</div>
				</div>
			</div>

			{ /* Save Footer Bar */ }
			<div className="dctc-sc-settings-save-bar" style={ { marginTop: '24px' } }>
				<button
					type="submit"
					className="button button-primary button-hero dctc-sc-save-settings-btn"
					disabled={ saving }
				>
					{ saving ? (
						<>
							<span className="spinner is-active" style={ { float: 'none', margin: '0 8px 0 0' } }></span>
							{ __( 'Saving Settings & Permissions...', 'dragwyb-click-to-chat' ) }
						</>
					) : (
						<>
							<span className="dashicons dashicons-saved"></span>
							{ __( 'Save All Settings & Permissions', 'dragwyb-click-to-chat' ) }
						</>
					) }
				</button>
			</div>
		</form>
	);
}

