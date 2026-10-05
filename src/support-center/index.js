import apiFetch from '@wordpress/api-fetch';
import { createRoot, render } from '@wordpress/element';
import App from './App';
import './style.css';

// Only attach nonce middleware if not already configured by WordPress admin
if ( ! window.wpApiSettings?.nonce && window.dctc_support_data?.nonce ) {
	apiFetch.use( apiFetch.createNonceMiddleware( window.dctc_support_data.nonce ) );
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
