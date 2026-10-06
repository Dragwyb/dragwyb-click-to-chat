/**
 * Support Center — Standalone Dashboard Entry Point
 */
import apiFetch from '@wordpress/api-fetch';
import { useState, useEffect, useCallback, createRoot, render } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import SupportHeader from './components/SupportHeader';
import DashboardView from './views/DashboardView';
import './style.css';

if ( ! window.wpApiSettings?.nonce && window.dctc_support_data?.nonce ) {
	apiFetch.use( apiFetch.createNonceMiddleware( window.dctc_support_data.nonce ) );
}

function DashboardApp() {
	const userPermissions = window.dctc_support_data?.permissions || {
		view_tickets: true,
		manage_agents: true,
		manage_categories: true,
		manage_tags: true,
		manage_settings: true,
		is_admin: true,
	};

	const [notice, setNotice] = useState(null);
	const [dashboardStats, setDashboardStats] = useState(null);
	const [statsLoading, setStatsLoading] = useState(false);
	const [statusUpdating, setStatusUpdating] = useState(false);

	const showNotice = (message, type = 'success') => {
		setNotice({ message, type });
		setTimeout(() => setNotice(null), 6000);
	};

	const fetchDashboardStats = useCallback(async () => {
		setStatsLoading(true);
		try {
			const data = await apiFetch({ path: '/dctc-ai/v1/support/dashboard' });
			if (data?.success) {
				setDashboardStats(data.stats || data);
			}
		} catch (err) {
			console.error('Error fetching dashboard stats:', err);
		} finally {
			setStatsLoading(false);
		}
	}, []);

	const handleUpdateStatus = async (newStatus) => {
		setStatusUpdating(true);
		try {
			const data = await apiFetch({
				path: '/dctc-ai/v1/support/agents/me/status',
				method: 'POST',
				data: { availability_status: newStatus },
			});
			if (data?.success) {
				setDashboardStats((prev) => {
					if (!prev) return prev;
					return {
						...prev,
						agent: {
							...(prev.agent || {}),
							availability_status: newStatus,
						},
					};
				});
				showNotice(__('Availability status updated.', 'dragwyb-click-to-chat'), 'success');
			}
		} catch (err) {
			console.error('Error updating agent status:', err);
		} finally {
			setStatusUpdating(false);
		}
	};

	useEffect(() => {
		fetchDashboardStats();
	}, [fetchDashboardStats]);

	const handleJumpToTickets = (status) => {
		window.location.href = `admin.php?page=dragwyb-support-tickets&status=${status || 'all'}`;
	};

	const handleSwitchTab = (tab) => {
		const targetPage = tab === 'tickets' ? 'dragwyb-support-tickets' :
			tab === 'agents' ? 'dragwyb-support-agents' :
			tab === 'taxonomies' ? 'dragwyb-support-taxonomies' :
			tab === 'settings' ? 'dragwyb-support-settings' : 'dragwyb-support-center';
		window.location.href = `admin.php?page=${targetPage}`;
	};

	return (
		<div className="dctc-sc-app-wrap">
			<SupportHeader
				activeTab="dashboard"
				userPermissions={userPermissions}
				notice={notice}
			/>
			<main className="dctc-sc-main-content">
				<DashboardView
					dashboardStats={dashboardStats}
					statsLoading={statsLoading}
					statusUpdating={statusUpdating}
					onUpdateStatus={handleUpdateStatus}
					onRefresh={fetchDashboardStats}
					onJumpToTickets={handleJumpToTickets}
					onSwitchTab={handleSwitchTab}
					userPermissions={userPermissions}
				/>
			</main>
		</div>
	);
}

document.addEventListener('DOMContentLoaded', () => {
	const container = document.getElementById('dctc-support-admin-root');
	if (!container) return;

	if (createRoot) {
		createRoot(container).render(<DashboardApp />);
	} else if (render) {
		render(<DashboardApp />, container);
	}
});
