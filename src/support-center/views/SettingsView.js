/**
 * Support Center - Settings View
 */
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

export default function SettingsView( {
	supportSettings,
	setSupportSettings,
	onShowNotice,
} ) {
	const [ saving, setSaving ] = useState( false );
	const [ localNotice, setLocalNotice ] = useState( null );

	const handleSubmit = async ( e ) => {
		if ( e ) e.preventDefault();
		setSaving( true );
		setLocalNotice( null );
		try {
			const data = await apiFetch( {
				path: '/dctc-ai/v1/support/settings',
				method: 'POST',
				data: supportSettings,
			} );
			if ( data?.success ) {
				setSupportSettings( data.settings || supportSettings );
				setLocalNotice( { type: 'success', message: __( 'Support settings saved successfully!', 'dragwyb-click-to-chat' ) } );
				if ( onShowNotice ) onShowNotice( __( 'Support settings saved successfully!', 'dragwyb-click-to-chat' ), 'success' );
			} else {
				setLocalNotice( { type: 'error', message: __( 'Failed to save support settings.', 'dragwyb-click-to-chat' ) } );
			}
		} catch ( err ) {
			console.error( 'Error saving support settings:', err );
			setLocalNotice( { type: 'error', message: err?.message || __( 'Error saving support settings.', 'dragwyb-click-to-chat' ) } );
		} finally {
			setSaving( false );
			setTimeout( () => setLocalNotice( null ), 5000 );
		}
	};

	return (
		<form onSubmit={ handleSubmit } className="dctc-sc-settings-wrap">
			{ localNotice && (
				<div className={ `dctc-sc-settings-banner ${ localNotice.type }` }>
					<span className={ `dashicons ${ localNotice.type === 'success' ? 'dashicons-yes-alt' : 'dashicons-warning' }` }></span>
					<span>{ localNotice.message }</span>
				</div>
			) }

			<div className="dctc-sc-settings-columns">
				{ /* Section 1: General & Routing */ }
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

						{ /* Toggle: Human Request Trigger in Chatbot */ }
						<div className="dctc-sc-setting-field-toggle">
							<div className="dctc-sc-toggle-info">
								<label className="dctc-sc-toggle-label">{ __( 'AI Chatbot Escalation Trigger', 'dragwyb-click-to-chat' ) }</label>
								<span className="dctc-sc-toggle-desc">{ __( 'Automatically create a support ticket when visitor asks to speak with a human in the chatbot.', 'dragwyb-click-to-chat' ) }</span>
							</div>
							<label className="dctc-sc-switch">
								<input
									type="checkbox"
									checked={ supportSettings.human_request_trigger !== false }
									onChange={ ( e ) => setSupportSettings( ( prev ) => ( { ...prev, human_request_trigger: e.target.checked } ) ) }
								/>
								<span className="dctc-sc-slider"></span>
							</label>
						</div>
					</div>
				</div>

				{ /* Section 2: Email Notifications & Portal */ }
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

						{ /* Customer Reply Notification */ }
						<div className="dctc-sc-setting-field-toggle">
							<div className="dctc-sc-toggle-info">
								<label className="dctc-sc-toggle-label">{ __( 'Staff Alert on Customer Reply', 'dragwyb-click-to-chat' ) }</label>
								<span className="dctc-sc-toggle-desc">{ __( 'Send email notification to assigned agent when the customer sends a new message.', 'dragwyb-click-to-chat' ) }</span>
							</div>
							<label className="dctc-sc-switch">
								<input
									type="checkbox"
									checked={ supportSettings.notifications?.customer_reply !== false }
									onChange={ ( e ) => setSupportSettings( ( prev ) => ( {
										...prev,
										notifications: { ...( prev.notifications || {} ), customer_reply: e.target.checked },
									} ) ) }
								/>
								<span className="dctc-sc-slider"></span>
							</label>
						</div>

						{ /* Ticket Resolved Notification */ }
						<div className="dctc-sc-setting-field-toggle">
							<div className="dctc-sc-toggle-info">
								<label className="dctc-sc-toggle-label">{ __( 'Customer Notification on Resolved', 'dragwyb-click-to-chat' ) }</label>
								<span className="dctc-sc-toggle-desc">{ __( 'Send confirmation email to customer when ticket status is changed to Resolved.', 'dragwyb-click-to-chat' ) }</span>
							</div>
							<label className="dctc-sc-switch">
								<input
									type="checkbox"
									checked={ supportSettings.notifications?.ticket_resolved !== false }
									onChange={ ( e ) => setSupportSettings( ( prev ) => ( {
										...prev,
										notifications: { ...( prev.notifications || {} ), ticket_resolved: e.target.checked },
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
			<div className="dctc-sc-settings-save-bar">
				<button
					type="submit"
					className="button button-primary button-hero dctc-sc-save-settings-btn"
					disabled={ saving }
				>
					{ saving ? (
						<>
							<span className="spinner is-active" style={ { float: 'none', margin: '0 8px 0 0' } }></span>
							{ __( 'Saving Settings...', 'dragwyb-click-to-chat' ) }
						</>
					) : (
						<>
							<span className="dashicons dashicons-saved"></span>
							{ __( 'Save Support Settings', 'dragwyb-click-to-chat' ) }
						</>
					) }
				</button>
			</div>
		</form>
	);
}
