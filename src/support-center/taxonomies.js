/**
 * Support Center — Standalone Categories, Tags & Taxonomies Entry Point
 */
import apiFetch from '@wordpress/api-fetch';
import { useState, useEffect, useCallback, createRoot, render } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import SupportHeader from './components/SupportHeader';
import TaxonomiesView from './views/TaxonomiesView';
import './style.css';

if ( ! window.wpApiSettings?.nonce && window.dctc_support_data?.nonce ) {
	apiFetch.use( apiFetch.createNonceMiddleware( window.dctc_support_data.nonce ) );
}

function TaxonomiesApp() {
	const userPermissions = window.dctc_support_data?.permissions || {
		view_tickets: true,
		manage_agents: true,
		manage_categories: true,
		manage_tags: true,
		manage_settings: true,
		is_admin: true,
	};

	const [notice, setNotice] = useState(null);
	const [categories, setCategories] = useState([]);
	const [tags, setTags] = useState([]);
	const [products, setProducts] = useState([]);
	const [loading, setLoading] = useState(false);

	const showNotice = (message, type = 'success') => {
		setNotice({ message, type });
		setTimeout(() => setNotice(null), 6000);
	};

	const fetchTaxonomyData = useCallback(async () => {
		setLoading(true);
		try {
			const [catRes, tagRes, prodRes] = await Promise.allSettled([
				apiFetch({ path: '/dctc-ai/v1/support/categories' }),
				apiFetch({ path: '/dctc-ai/v1/support/tags' }),
				apiFetch({ path: '/dctc-ai/v1/support/products' }),
			]);

			if (catRes.status === 'fulfilled' && catRes.value?.success) setCategories(catRes.value.categories || []);
			if (tagRes.status === 'fulfilled' && tagRes.value?.success) setTags(tagRes.value.tags || []);
			if (prodRes.status === 'fulfilled' && prodRes.value?.success) setProducts(prodRes.value.products || []);
		} catch (err) {
			console.error('Error fetching taxonomies data:', err);
		} finally {
			setLoading(false);
		}
	}, []);

	useEffect(() => {
		fetchTaxonomyData();
	}, [fetchTaxonomyData]);

	return (
		<div className="dctc-sc-app-wrap">
			<SupportHeader
				activeTab="taxonomies"
				userPermissions={userPermissions}
				notice={notice}
			/>
			<main className="dctc-sc-main-content">
				<TaxonomiesView
					categories={categories}
					setCategories={setCategories}
					tags={tags}
					setTags={setTags}
					products={products}
					setProducts={setProducts}
					onShowNotice={showNotice}
					loading={loading}
					onRefresh={fetchTaxonomyData}
				/>
			</main>
		</div>
	);
}

document.addEventListener('DOMContentLoaded', () => {
	const container = document.getElementById('dctc-support-admin-root');
	if (!container) return;

	if (createRoot) {
		createRoot(container).render(<TaxonomiesApp />);
	} else if (render) {
		render(<TaxonomiesApp />, container);
	}
});
