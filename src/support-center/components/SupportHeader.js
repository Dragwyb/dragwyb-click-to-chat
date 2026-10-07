import { __ } from '@wordpress/i18n';
import GlobalHeader from '../../common/components/GlobalHeader';

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

	const menuItems = [
		{
			id: 'dashboard',
			label: __('Dashboard', 'dragwyb-click-to-chat'),
			icon: 'dashicons-dashboard',
			href: 'admin.php?page=dragwyb-support-center',
		},
		{
			id: 'tickets',
			label: __('Tickets', 'dragwyb-click-to-chat'),
			icon: 'dashicons-tickets',
			href: 'admin.php?page=dragwyb-support-tickets',
		},
		...(canManageAgents
			? [
					{
						id: 'agents',
						label: __('Agents & Staff', 'dragwyb-click-to-chat'),
						icon: 'dashicons-groups',
						href: 'admin.php?page=dragwyb-support-agents',
					},
			  ]
			: []),
		...(canManageTaxonomies
			? [
					{
						id: 'taxonomies',
						label: __('Categories & Tags', 'dragwyb-click-to-chat'),
						icon: 'dashicons-tag',
						href: 'admin.php?page=dragwyb-support-taxonomies',
					},
			  ]
			: []),
		...(canManageSettings
			? [
					{
						id: 'settings',
						label: __('Support Settings', 'dragwyb-click-to-chat'),
						icon: 'dashicons-admin-generic',
						href: 'admin.php?page=dragwyb-support-settings',
					},
			  ]
			: []),
	];

	const rightActions = (
		<div className="dctc-sc-user-pill">
			<div className="dctc-sc-user-avatar">
				{(perms.agent_name || 'Admin').substring(0, 2).toUpperCase()}
			</div>
			<div className="dctc-sc-user-meta">
				<span className="dctc-sc-user-name">
					{perms.agent_name || 'Staff Member'}
				</span>
				<span className="dctc-sc-user-role">
					{perms.is_admin
						? __('Administrator', 'dragwyb-click-to-chat')
						: __('Support Agent', 'dragwyb-click-to-chat')}
				</span>
			</div>
		</div>
	);

	return (
		<>
			<GlobalHeader
				icon="dashicons-format-chat"
				title={__('Support Center', 'dragwyb-click-to-chat')}
				subheading={__('Hybrid AI & Agent Helpdesk', 'dragwyb-click-to-chat')}
				menuItems={menuItems}
				activeTab={activeTab}
				rightActions={rightActions}
			/>

			{notice && (
				<div className={`dctc-sc-global-toast ${notice.type}`}>
					<span
						className={`dashicons ${
							notice.type === 'success'
								? 'dashicons-yes-alt'
								: notice.type === 'error'
								? 'dashicons-warning'
								: 'dashicons-info'
						}`}
					/>
					<span>{notice.message}</span>
				</div>
			)}
		</>
	);
}
