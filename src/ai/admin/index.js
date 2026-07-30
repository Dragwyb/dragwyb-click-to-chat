import './public-path';
import './styles/core.css';
import { createRoot } from '@wordpress/element';
import App from './App';

const rootEl = document.getElementById( 'dctc-ai-admin-root' );
if ( rootEl ) {
	createRoot( rootEl ).render(
		<App settings={ window.dctc_ai_data?.settings || {} } />
	);
}
