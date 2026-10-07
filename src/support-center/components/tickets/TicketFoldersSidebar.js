import { __ } from '@wordpress/i18n';

export default function TicketFoldersSidebar({
	activeFolder,
	activeView,
	folderCounts,
	onSelectFolder,
	onSelectView,
	onShowNotice,
}) {
	const folderItems = [
		{ key: 'all', label: __('All Tickets', 'dragwyb-click-to-chat'), icon: 'dashicons-laptop', count: folderCounts.all },
		{ key: 'open', label: __('Open', 'dragwyb-click-to-chat'), icon: 'dashicons-inbox', count: folderCounts.open },
		{ key: 'pending', label: __('Pending', 'dragwyb-click-to-chat'), icon: 'dashicons-clock', count: folderCounts.pending },
		{ key: 'my', label: __('My Tickets', 'dragwyb-click-to-chat'), icon: 'dashicons-admin-users', count: folderCounts.my },
		{ key: 'unassigned', label: __('Unassigned', 'dragwyb-click-to-chat'), icon: 'dashicons-groups', count: folderCounts.unassigned },
		{ key: 'resolved', label: __('Resolved', 'dragwyb-click-to-chat'), icon: 'dashicons-yes-alt', count: folderCounts.resolved },
		{ key: 'closed', label: __('Closed', 'dragwyb-click-to-chat'), icon: 'dashicons-dismiss', count: folderCounts.closed },
		{ key: 'spam', label: __('Spam', 'dragwyb-click-to-chat'), icon: 'dashicons-warning', count: folderCounts.spam },
		{ key: 'trash', label: __('Trash', 'dragwyb-click-to-chat'), icon: 'dashicons-trash', count: folderCounts.trash },
	];

	const viewItems = [
		{ key: 'high_priority', label: __('High Priority', 'dragwyb-click-to-chat'), icon: 'dashicons-flag', iconColor: '#ef4444', count: folderCounts.high_priority },
		{ key: 'waiting_reply', label: __('Waiting for Reply', 'dragwyb-click-to-chat'), icon: 'dashicons-clock', iconColor: '#f59e0b', count: folderCounts.waiting_reply },
		{ key: 'today', label: __('Today', 'dragwyb-click-to-chat'), icon: 'dashicons-calendar-alt', iconColor: '#6366f1', count: folderCounts.today },
		{ key: 'this_week', label: __('This Week', 'dragwyb-click-to-chat'), icon: 'dashicons-calendar', iconColor: '#6366f1', count: folderCounts.this_week },
		{ key: 'ai_suggested', label: __('AI Suggested', 'dragwyb-click-to-chat'), icon: 'dashicons-superhero', iconColor: '#8b5cf6', count: folderCounts.ai_suggested },
		{ key: 'overdue', label: __('Overdue', 'dragwyb-click-to-chat'), icon: 'dashicons-backup', iconColor: '#ef4444', count: folderCounts.overdue },
	];

	return (
		<aside className="dctc-sc-col-folders">
			<div className="dctc-sc-folder-group">
				{folderItems.map((item) => (
					<button
						key={item.key}
						type="button"
						className={`dctc-sc-folder-item ${activeFolder === item.key ? 'active' : ''}`}
						onClick={() => onSelectFolder(item.key)}
					>
						<span className={`dashicons ${item.icon}`}></span>
						<span className="dctc-sc-folder-name">{item.label}</span>
						<span className="dctc-sc-folder-count">{item.count}</span>
					</button>
				))}
			</div>

			<div className="dctc-sc-views-section">
				<div className="dctc-sc-views-header">
					<span>{__('VIEWS', 'dragwyb-click-to-chat')}</span>
					<span className="dashicons dashicons-admin-generic"></span>
				</div>
				<div className="dctc-sc-views-group">
					{viewItems.map((v) => (
						<button
							key={v.key}
							type="button"
							className={`dctc-sc-folder-item ${activeView === v.key ? 'active' : ''}`}
							onClick={() => onSelectView(v.key)}
						>
							<span className={`dashicons ${v.icon}`} style={{ color: v.iconColor }}></span>
							<span className="dctc-sc-folder-name">{v.label}</span>
							<span className="dctc-sc-folder-count">{v.count}</span>
						</button>
					))}

					<button
						type="button"
						className="dctc-sc-create-view-btn"
						onClick={() => onShowNotice(__('Custom view creator coming soon.', 'dragwyb-click-to-chat'), 'info')}
					>
						<span className="dashicons dashicons-plus"></span>
						{__('Create View', 'dragwyb-click-to-chat')}
					</button>
				</div>
			</div>
		</aside>
	);
}
