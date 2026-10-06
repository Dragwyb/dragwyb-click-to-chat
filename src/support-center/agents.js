/**
 * Support Center — Standalone Agents & Staff Entry Point
 */
import apiFetch from '@wordpress/api-fetch';
import { useState, useEffect, useCallback, createRoot, render } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import SupportHeader from './components/SupportHeader';
import AgentsView from './views/AgentsView';
import './style.css';

if ( ! window.wpApiSettings?.nonce && window.dctc_support_data?.nonce ) {
	apiFetch.use( apiFetch.createNonceMiddleware( window.dctc_support_data.nonce ) );
}

function AgentsApp() {
	const userPermissions = window.dctc_support_data?.permissions || {
		view_tickets: true,
		manage_agents: true,
		manage_categories: true,
		manage_tags: true,
		manage_settings: true,
		is_admin: true,
	};

	const [notice, setNotice] = useState(null);
	const [agents, setAgents] = useState([]);
	const [loading, setLoading] = useState(false);

	const showNotice = (message, type = 'success') => {
		setNotice({ message, type });
		setTimeout(() => setNotice(null), 6000);
	};

	const fetchAgents = useCallback(async () => {
		setLoading(true);
		try {
			const res = await apiFetch({ path: '/dctc-ai/v1/support/agents' });
			if (res?.success) {
				setAgents(res.agents || []);
			}
		} catch (err) {
			console.error('Error fetching agents:', err);
		} finally {
			setLoading(false);
		}
	}, []);

	useEffect(() => {
		fetchAgents();
	}, [fetchAgents]);

	return (
		<div className="dctc-sc-app-wrap">
			<SupportHeader
				activeTab="agents"
				userPermissions={userPermissions}
				notice={notice}
			/>
			<main className="dctc-sc-main-content">
				<AgentsView
					agents={agents}
					setAgents={setAgents}
					onShowNotice={showNotice}
					loading={loading}
					onRefresh={fetchAgents}
				/>
			</main>
		</div>
	);
}

document.addEventListener('DOMContentLoaded', () => {
	const container = document.getElementById('dctc-support-admin-root');
	if (!container) return;

	if (createRoot) {
		createRoot(container).render(<AgentsApp />);
	} else if (render) {
		render(<AgentsApp />, container);
	}
});
