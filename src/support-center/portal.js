/**
 * Support Center — Standalone Support Portal Customizer Entry Point
 */
import apiFetch from '@wordpress/api-fetch';
import { useState, useEffect, useCallback, createRoot, render } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import SupportHeader from './components/SupportHeader';
import PortalView from './views/PortalView';
import './style.css';

if ( ! window.wpApiSettings?.nonce && window.dctc_support_data?.nonce ) {
	apiFetch.use( apiFetch.createNonceMiddleware( window.dctc_support_data.nonce ) );
}

function PortalApp() {
	const userPermissions = window.dctc_support_data?.permissions || {
		view_tickets: true,
		manage_agents: true,
		manage_categories: true,
		manage_tags: true,
		manage_settings: true,
		is_admin: true,
	};

	const [notice, setNotice] = useState(null);
	const [portalSettings, setPortalSettings] = useState({});
	const [loading, setLoading] = useState(false);

	const showNotice = (message, type = 'success') => {
		setNotice({ message, type });
		setTimeout(() => setNotice(null), 6000);
	};

	const fetchPortalData = useCallback(async () => {
		setLoading(true);
		try {
			const res = await apiFetch({ path: '/dctc-ai/v1/support/portal-settings' });
			if (res?.success && res?.settings) {
				setPortalSettings(res.settings);
			}
		} catch (err) {
			console.error('Error fetching portal settings:', err);
		} finally {
			setLoading(false);
		}
	}, []);

	useEffect(() => {
		fetchPortalData();
	}, [fetchPortalData]);

	return (
		<div className="dctc-sc-app-wrap">
			<SupportHeader
				activeTab="portal"
				userPermissions={userPermissions}
				notice={notice}
			/>
			<main className="dctc-sc-main-content">
				<PortalView
					portalSettings={portalSettings}
					setPortalSettings={setPortalSettings}
					onShowNotice={showNotice}
					loading={loading}
				/>
			</main>
		</div>
	);
}

document.addEventListener('DOMContentLoaded', () => {
	const container = document.getElementById('dctc-support-admin-root');
	if (!container) return;

	if (createRoot) {
		createRoot(container).render(<PortalApp />);
	} else if (render) {
		render(<PortalApp />, container);
	}
});
