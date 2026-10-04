/**
 * Support Center Entry Point
 *
 * Standalone React root for Dragwyb Support Center.
 */
import { createRoot, render } from '@wordpress/element';
import App from './App';
import './style.css';

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
