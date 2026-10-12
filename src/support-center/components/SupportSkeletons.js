/**
 * Support Center Skeletons for Tickets Workspace & Details Sidebar
 */
import { __ } from '@wordpress/i18n';

export function TicketWorkspaceSkeleton() {
	return (
		<div
			className="dctc-sc-workspace-inner dctc-sc-workspace-skeleton"
			aria-busy="true"
			aria-label={__('Loading ticket', 'dragwyb-click-to-chat')}
		>

			<div className="dctc-sc-ws-header">
				<div className="dctc-sc-ws-header-title-wrap">
					<h2 className="dctc-sc-ws-subject">
						<div className="dctc-sk dctc-sk-ticket-title" />
						<span className="dctc-sk dctc-sk-ticket-id" />
					</h2>
				</div>

				<div className="dctc-sc-ws-header-actions">
					<button type="button" className="dctc-sc-close-view-btn" disabled>
						<span className="dashicons dashicons-no-alt" />
					</button>
				</div>
			</div>

			{/* WORKSPACE SUBTABS & TOP ACTIONS BAR */}
			<div className="dctc-sc-workspace-tabs-bar">
				<div className="dctc-sc-workspace-tabs dctc-sk-tabs">
					<button type="button" className="dctc-sc-ws-tab-btn active" disabled>
						<span className="dashicons dashicons-format-chat" />
						{__('Conversation', 'dragwyb-click-to-chat')}
					</button>
					<button type="button" className="dctc-sc-ws-tab-btn" disabled>
						<span className="dashicons dashicons-lock" />
						{__('Internal Notes', 'dragwyb-click-to-chat')}
					</button>
				</div>

				<div className="dctc-sc-ws-tab-actions">
					<button
						type="button"
						className={"dctc-sc-header-action-btn reply-btn"}
					>
						<span className="dashicons dashicons-undo"></span>
						<span>{__('Add Reply', 'dragwyb-click-to-chat')}</span>
					</button>
					<button
						type="button"
						className={"dctc-sc-header-action-btn note-btn"}
					>
						<span className="dashicons dashicons-lock"></span>
						<span>{__('Add Note', 'dragwyb-click-to-chat')}</span>
					</button>
				</div>
			</div>

			<div className="dctc-sc-conversation-scroll">
				<div className="dctc-sk-message dctc-sk-message-customer">
					<div className="dctc-sk dctc-sk-avatar" />
					<div className="dctc-sk-message-body">
						<div className="dctc-sk-message-meta">
							<span className="dctc-sk dctc-sk-author" />
							<span className="dctc-sk dctc-sk-time" />
						</div>
						<div className="dctc-sk-bubble dctc-sk-bubble-large">
							<span className="dctc-sk dctc-sk-line line-95" />
							<span className="dctc-sk dctc-sk-line line-90" />
							<span className="dctc-sk dctc-sk-line line-68" />
						</div>
					</div>
				</div>

				<div className="dctc-sk-message dctc-sk-message-agent">
					<div className="dctc-sk-message-body">
						<div className="dctc-sk-message-meta dctc-sk-message-meta-right">
							<span className="dctc-sk dctc-sk-author-small" />
							<span className="dctc-sk dctc-sk-time" />
						</div>
						<div className="dctc-sk-bubble dctc-sk-bubble-agent">
							<span className="dctc-sk dctc-sk-line line-88" />
							<span className="dctc-sk dctc-sk-line line-58" />
						</div>
					</div>
					<div className="dctc-sk dctc-sk-avatar" />
				</div>

				<div className="dctc-sk-message dctc-sk-message-customer">
					<div className="dctc-sk dctc-sk-avatar" />
					<div className="dctc-sk-message-body">
						<div className="dctc-sk-message-meta">
							<span className="dctc-sk dctc-sk-author" />
							<span className="dctc-sk dctc-sk-time" />
						</div>
						<div className="dctc-sk-bubble dctc-sk-bubble-large">
							<span className="dctc-sk dctc-sk-line line-95" />
							<span className="dctc-sk dctc-sk-line line-90" />
							<span className="dctc-sk dctc-sk-line line-68" />
						</div>
					</div>
				</div>

				<div className="dctc-sk-message dctc-sk-message-agent">
					<div className="dctc-sk-message-body">
						<div className="dctc-sk-message-meta dctc-sk-message-meta-right">
							<span className="dctc-sk dctc-sk-author-small" />
							<span className="dctc-sk dctc-sk-time" />
						</div>
						<div className="dctc-sk-bubble dctc-sk-bubble-agent">
							<span className="dctc-sk dctc-sk-line line-88" />
							<span className="dctc-sk dctc-sk-line line-58" />
						</div>
					</div>
					<div className="dctc-sk dctc-sk-avatar" />
				</div>
			</div>
		</div>
	);
}

export function TicketDetailsSidebarSkeleton() {
	return (
		<aside
			className="dctc-sc-col-details dctc-sc-details-skeleton"
			aria-busy="true"
			aria-label={__('Loading ticket details', 'dragwyb-click-to-chat')}
		>
			{/* TOP ACTIONS BAR: Back, Flag, Star */}
			<div className="dctc-sc-sidebar-top-bar">
				<button
					type="button"
					className="dctc-sc-sidebar-back-btn"
					title={__('Back to all tickets', 'dragwyb-click-to-chat')}
				>
					<span className="dashicons dashicons-arrow-left-alt"></span>
					<span>{__('Back', 'dragwyb-click-to-chat')}</span>
				</button>

				<div className="dctc-sc-sidebar-top-actions">
					<>
						<button
							type="button"
							className={`dctc-sc-sidebar-icon-btn`}
							title={__('Flag Ticket', 'dragwyb-click-to-chat')}
							disabled
						>
							<span className="dashicons dashicons-flag"></span>
						</button>

						<button
							type="button"
							className={`dctc-sc-sidebar-icon-btn`}
							title={__('Star Ticket', 'dragwyb-click-to-chat')}
							disabled
						>
							<span className={`dashicons dashicons-star-empty`}></span>
						</button>
					</>
				</div>
			</div>
			<div className="dctc-sc-details-card">
				<button
					type="button"
					className="dctc-sc-card-head dctc-sc-card-head-toggle"
				>
					<div className="dctc-sc-card-head-title">
						<span className="dctc-sk dctc-sk-sidebar-icon" />
						<span className="dctc-sk dctc-sk-properties-heading" />
					</div>
					<span className="dctc-sk dctc-sk-collapse-icon" />
				</button>
				<div className='dctc-sc-card-body'>
					<div className="dctc-sk-customer">
						<div className="dctc-sk dctc-sk-customer-avatar" />
						<div className="dctc-sk-customer-info">
							<span className="dctc-sk dctc-sk-customer-name" />
							<span className="dctc-sk dctc-sk-guest-badge" />
						</div>
					</div>
					<div className="dctc-sk-email-row">
						<span className="dctc-sk dctc-sk-email-icon" />
						<span className="dctc-sk dctc-sk-email" />
					</div>
					<div className="dctc-sk-sidebar-expand">
						<div className="dctc-sk-sidebar-expand-left">
							<span className="dctc-sk dctc-sk-expand-icon" />
							<span className="dctc-sk dctc-sk-expand-title" />
						</div>
						<span className="dctc-sk dctc-sk-expand-arrow" />
					</div>
				</div>
			</div>

			<div className="dctc-sc-details-card">
				<button
					type="button"
					className="dctc-sc-card-head dctc-sc-card-head-toggle"
				>
					<div className="dctc-sc-card-head-title">
						<span className="dctc-sk dctc-sk-sidebar-icon" />
						<span className="dctc-sk dctc-sk-properties-heading" />
					</div>
					<span className="dctc-sk dctc-sk-collapse-icon" />
				</button>
			</div>

			<div className="dctc-sc-details-card">
				<button
					type="button"
					className="dctc-sc-card-head dctc-sc-card-head-toggle"
				>
					<div className="dctc-sc-card-head-title">
						<span className="dctc-sk dctc-sk-sidebar-icon" />
						<span className="dctc-sk dctc-sk-properties-heading" />
					</div>
					<span className="dctc-sk dctc-sk-collapse-icon" />
				</button>
			</div>

			{/* VIEWERS CARD (RIGHT SIDE BOTTOM) */}
			<div className="dctc-sc-details-card dctc-sc-viewers-card">
				<div className="dctc-sc-card-head" style={{ cursor: 'default' }}>
					<div className="dctc-sc-card-head-title">
						<span className="dashicons dashicons-visibility" style={{ color: '#0f172a' }}></span>
						<h4>{__('Viewers', 'dragwyb-click-to-chat')} (1)</h4>
					</div>
				</div>
				<div className="dctc-sc-card-body">
					<div className="dctc-sc-sidebar-viewers-stack">
						<div
							className="dctc-sc-sidebar-viewer-avatar"
						>
							<span className="dctc-sk dctc-sk-avatar" style={{ width: '32px', height: '32px' }} />
							<span className="dctc-sc-viewer-online-dot"></span>
						</div>
					</div>
				</div>
			</div>
		</aside>
	);
}
