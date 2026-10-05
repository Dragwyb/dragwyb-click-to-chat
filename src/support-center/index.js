import apiFetch from '@wordpress/api-fetch';
import { createRoot, render } from '@wordpress/element';
import App from './App';
import './style.css';

const supportNonce = window.dctc_support_data?.nonce || window.wpApiSettings?.nonce;
if ( supportNonce ) {
	apiFetch.use( apiFetch.createNonceMiddleware( supportNonce ) );
}

document.addEventListener( 'DOMContentLoaded', () => {
	const container = document.getElementById( 'dctc-support-admin-root' );
	if ( ! container ) {
		return;
	}

	if ( createRoot ) {
		createRoot( container ).render( <App /> );
	} else if ( render ) {
		render( <App />, container );
	}
} );
