/**
 * Support Center — Standalone Support Settings Entry Point
 */
import apiFetch from '@wordpress/api-fetch';
import { useState, useEffect, useCallback, createRoot, render } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import SupportHeader from './components/SupportHeader';
import SettingsView from './views/SettingsView';
import './style.css';

if ( ! window.wpApiSettings?.nonce && window.dctc_support_data?.nonce ) {
	apiFetch.use( apiFetch.createNonceMiddleware( window.dctc_support_data.nonce ) );
}

function SettingsApp() {
	const userPermissions = window.dctc_support_data?.permissions || {
		view_tickets: true,
		manage_agents: true,
		manage_categories: true,
		manage_tags: true,
		manage_settings: true,
		is_admin: true,
	};

	const [notice, setNotice] = useState(null);
	const [supportSettings, setSupportSettings] = useState({});
	const [permissionsMatrix, setPermissionsMatrix] = useState(null);
	const [loading, setLoading] = useState(false);

	const showNotice = (message, type = 'success') => {
		setNotice({ message, type });
		setTimeout(() => setNotice(null), 6000);
	};

	const fetchSettingsData = useCallback(async () => {
		setLoading(true);
		try {
			const [setRes, permRes] = await Promise.allSettled([
				apiFetch({ path: '/dctc-ai/v1/support/settings' }),
				apiFetch({ path: '/dctc-ai/v1/support/permissions' }),
			]);

			if (setRes.status === 'fulfilled' && setRes.value?.success) setSupportSettings(setRes.value.settings || {});
			if (permRes.status === 'fulfilled' && permRes.value?.success) {
				if (permRes.value.permissions_matrix) setPermissionsMatrix(permRes.value.permissions_matrix);
			}
		} catch (err) {
			console.error('Error fetching settings & permissions:', err);
		} finally {
			setLoading(false);
		}
	}, []);

	useEffect(() => {
		fetchSettingsData();
	}, [fetchSettingsData]);

	return (
		<div className="dctc-sc-app-wrap">
			<SupportHeader
				activeTab="settings"
				userPermissions={userPermissions}
				notice={notice}
			/>
			<main className="dctc-sc-main-content">
				<SettingsView
					supportSettings={supportSettings}
					setSupportSettings={setSupportSettings}
					permissionsMatrix={permissionsMatrix}
					setPermissionsMatrix={setPermissionsMatrix}
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
		createRoot(container).render(<SettingsApp />);
	} else if (render) {
		render(<SettingsApp />, container);
	}
});
