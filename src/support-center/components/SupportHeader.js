/**
 * Support Center Header Component
 *
 * Renders the top navigation bar with page-based redirect links and staff user profile badge.
 */
import { __ } from '@wordpress/i18n';

export default function SupportHeader({ activeTab, userPermissions, notice }) {
	const perms = userPermissions || window.dctc_support_data?.permissions || {
		view_tickets: true,
		manage_agents: true,
		manage_categories: true,
		manage_tags: true,
		manage_settings: true,
		is_admin: true,
	};

	const canManageAgents = !!(perms.is_admin || perms.manage_agents);
	const canManageTaxonomies = !!(perms.is_admin || perms.manage_categories || perms.manage_tags);
	const canManageSettings = !!(perms.is_admin || perms.manage_settings);

	return (
		<>
			<header className="dctc-sc-header-bar">
				<div className="dctc-sc-brand">
					<div className="dctc-sc-brand-icon">
						<span className="dashicons dashicons-format-chat"></span>
					</div>
					<div>
						<h1 className="dctc-sc-app-title">{__('Support Center', 'dragwyb-click-to-chat')}</h1>
						<span className="dctc-sc-app-tagline">
							{__('Hybrid AI & Agent Helpdesk', 'dragwyb-click-to-chat')}
						</span>
					</div>
				</div>

				<nav className="dctc-sc-top-nav">
					<a
						href="admin.php?page=dragwyb-support-center"
						className={`dctc-sc-nav-link ${activeTab === 'dashboard' ? 'active' : ''}`}
					>
						<span className="dashicons dashicons-dashboard"></span>
						{__('Dashboard', 'dragwyb-click-to-chat')}
					</a>

					<a
						href="admin.php?page=dragwyb-support-tickets"
						className={`dctc-sc-nav-link ${activeTab === 'tickets' ? 'active' : ''}`}
					>
						<span className="dashicons dashicons-tickets"></span>
						{__('Tickets', 'dragwyb-click-to-chat')}
					</a>

					{canManageAgents && (
						<a
							href="admin.php?page=dragwyb-support-agents"
							className={`dctc-sc-nav-link ${activeTab === 'agents' ? 'active' : ''}`}
						>
							<span className="dashicons dashicons-groups"></span>
							{__('Agents & Staff', 'dragwyb-click-to-chat')}
						</a>
					)}

					{canManageTaxonomies && (
						<a
							href="admin.php?page=dragwyb-support-taxonomies"
							className={`dctc-sc-nav-link ${activeTab === 'taxonomies' ? 'active' : ''}`}
						>
							<span className="dashicons dashicons-tag"></span>
							{__('Categories & Tags', 'dragwyb-click-to-chat')}
						</a>
					)}

					{canManageSettings && (
						<a
							href="admin.php?page=dragwyb-support-settings"
							className={`dctc-sc-nav-link ${activeTab === 'settings' ? 'active' : ''}`}
						>
							<span className="dashicons dashicons-admin-generic"></span>
							{__('Support Settings', 'dragwyb-click-to-chat')}
						</a>
					)}
				</nav>

				<div className="dctc-sc-header-right">
					<div className="dctc-sc-user-pill">
						<div className="dctc-sc-user-avatar">
							{(perms.agent_name || 'Admin').substring(0, 2).toUpperCase()}
						</div>
						<div className="dctc-sc-user-meta">
							<span className="dctc-sc-user-name">
								{perms.agent_name || 'Staff Member'}
							</span>
							<span className="dctc-sc-user-role">
								{perms.is_admin ? __('Administrator', 'dragwyb-click-to-chat') : __('Support Agent', 'dragwyb-click-to-chat')}
							</span>
						</div>
					</div>
				</div>
			</header>

			{notice && (
				<div className={`dctc-sc-global-toast ${notice.type}`}>
					<span className={`dashicons ${notice.type === 'success' ? 'dashicons-yes-alt' : notice.type === 'error' ? 'dashicons-warning' : 'dashicons-info'}`}></span>
					<span>{notice.message}</span>
				</div>
			)}
		</>
	);
}
