/**
 * AI admin dashboard bootstrap.
 */
import { createRoot } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import App from './App';
import './style.css';

const data = window.dctc_ai_data || {};

if ( data.nonce ) {
	apiFetch.use( apiFetch.createNonceMiddleware( data.nonce ) );
}

function mount() {
	const el = document.getElementById( 'dctc-ai-admin-root' );
	if ( ! el || el.dataset.dctcMounted ) {
		return;
	}
	el.dataset.dctcMounted = '1';
	createRoot( el ).render( <App settings={ data.settings || {} } /> );
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', mount );
} else {
	mount();
}
