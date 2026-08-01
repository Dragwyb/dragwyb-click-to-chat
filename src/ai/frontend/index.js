/**
 * AI frontend chat widget bootstrap.
 */
import { createElement, createRoot } from '@wordpress/element';
import ChatWidget from './ChatWidget';
import './style.css';

function mount() {
	const root = document.getElementById( 'dctc-ai-frontend-root' );
	if ( ! root || root.dataset.dctcMounted ) {
		return;
	}
	root.dataset.dctcMounted = '1';

	const inline = root.getAttribute( 'data-inline' ) === 'true';

	createRoot( root ).render(
		createElement( ChatWidget, {
			settings: window.dctc_ai_frontend_data?.settings,
			inline,
		} )
	);
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', mount );
} else {
	mount();
}
