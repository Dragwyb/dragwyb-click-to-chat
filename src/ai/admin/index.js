import './style.css';
import { createRoot } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import App from './App';

const data = window.dctc_ai_data || {};

if ( data.nonce ) {
	apiFetch.use( apiFetch.createNonceMiddleware( data.nonce ) );
}

const rootEl = document.getElementById( 'dctc-ai-admin-root' );
if ( rootEl ) {
	createRoot( rootEl ).render(
		<App settings={ data.settings || {} } />
	);
}
