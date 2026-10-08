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
			<div className="dctc-sc-ws-header dctc-sk-header">
				<div className="dctc-sc-ws-header-left">
					<button
						type="button"
						className="dctc-sc-back-to-list-btn"
						disabled
					>
						<span className="dashicons dashicons-arrow-left-alt" />
						<span>{__('All Tickets', 'dragwyb-click-to-chat')}</span>
					</button>
					<span className="dctc-sk dctc-sk-ticket-id" />
					<span className="dctc-sk dctc-sk-status" />
					<span className="dctc-sk dctc-sk-priority" />
					<button type="button" className="dctc-sc-ws-icon-btn" disabled>
						<span className="dashicons dashicons-flag" />
					</button>
					<button type="button" className="dctc-sc-ws-icon-btn" disabled>
						<span className="dashicons dashicons-star-empty" />
					</button>
				</div>
				<div className="dctc-sc-ws-header-right">
					<button type="button" className="dctc-sc-ws-icon-btn" disabled>
						<span className="dashicons dashicons-arrow-left-alt2" />
					</button>
					<button type="button" className="dctc-sc-ws-icon-btn" disabled>
						<span className="dashicons dashicons-arrow-right-alt2" />
					</button>
					<button type="button" className="dctc-sc-ws-close-btn" disabled>
						<span className="dashicons dashicons-no-alt" />
					</button>
				</div>
			</div>

			<div className="dctc-sc-ws-ticket-info" style={{ padding: "14px 22px 12px" }}>
				<div className="dctc-sk dctc-sk-ticket-title" />
				<div className="dctc-sk-ticket-meta" style={{ display: "flex", alignItems: "center", gap: "14px", flexWrap: "wrap" }}>
					<span className="dctc-sk dctc-sk-meta-item" />
					<span className="dctc-sk dctc-sk-meta-item meta-medium" />
					<span className="dctc-sk dctc-sk-meta-item meta-small" />
					<span className="dctc-sk dctc-sk-meta-item meta-medium" />
				</div>
			</div>

			<div className="dctc-sc-workspace-tabs dctc-sk-tabs">
				<button type="button" className="dctc-sc-ws-tab-btn active" disabled>
					<span className="dashicons dashicons-format-chat" />
					{__('Conversation', 'dragwyb-click-to-chat')}
				</button>
				<button type="button" className="dctc-sc-ws-tab-btn" disabled>
					<span className="dashicons dashicons-lock" />
					{__('Internal Notes', 'dragwyb-click-to-chat')}
				</button>
				<button type="button" className="dctc-sc-ws-tab-btn" disabled>
					<span className="dashicons dashicons-backup" />
					{__('Activity Logs', 'dragwyb-click-to-chat')}
				</button>
			</div>

			<div className="dctc-sc-skeleton-conversation">
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

			<div className="dctc-sc-skeleton-composer">
				<div className="dctc-sk-composer-header">
					<div className="dctc-sk-composer-tabs">
						<span className="dctc-sk dctc-sk-composer-tab active" />
						<span className="dctc-sk dctc-sk-composer-tab" />
					</div>
					<span className="dctc-sk dctc-sk-ai-button" />
				</div>
				<div className="dctc-sk-composer-input">
					<span className="dctc-sk dctc-sk-input-line" />
					<span className="dctc-sk dctc-sk-input-line input-medium" />
				</div>
				<div className="dctc-sk-composer-footer">
					<div className="dctc-sk-composer-footer-left">
						<span className="dctc-sk dctc-sk-footer-control" />
						<span className="dctc-sk dctc-sk-footer-control footer-small" />
					</div>
					<span className="dctc-sk dctc-sk-send-button" />
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
			<div className="dctc-sk-sidebar-card">
				<div className="dctc-sk-sidebar-header">
					<div className="dctc-sk-sidebar-heading">
						<span className="dctc-sk dctc-sk-sidebar-icon" />
						<span className="dctc-sk dctc-sk-customer-heading" />
					</div>
					<span className="dctc-sk dctc-sk-collapse-icon" />
				</div>
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

			<div className="dctc-sk-sidebar-card dctc-sk-properties-card">
				<div className="dctc-sk-sidebar-header">
					<div className="dctc-sk-sidebar-heading">
						<span className="dctc-sk dctc-sk-sidebar-icon" />
						<span className="dctc-sk dctc-sk-properties-heading" />
					</div>
					<span className="dctc-sk dctc-sk-collapse-icon" />
				</div>
				<div className="dctc-sk-property">
					<span className="dctc-sk dctc-sk-property-label" />
					<div className="dctc-sk dctc-sk-property-select" />
				</div>
				<div className="dctc-sk-property">
					<span className="dctc-sk dctc-sk-property-label property-medium" />
					<div className="dctc-sk dctc-sk-property-select" />
				</div>
				<div className="dctc-sk-property">
					<span className="dctc-sk dctc-sk-property-label property-large" />
					<div className="dctc-sk dctc-sk-property-select" />
				</div>
				<div className="dctc-sk-sidebar-expand">
					<div className="dctc-sk-sidebar-expand-left">
						<span className="dctc-sk dctc-sk-folder-icon" />
						<span className="dctc-sk dctc-sk-more-properties" />
					</div>
					<span className="dctc-sk dctc-sk-expand-arrow" />
				</div>
			</div>
		</aside>
	);
}
