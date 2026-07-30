/**
 * Lazy-load admin section CSS chunks (CSS rules unchanged; split for on-demand loading).
 */
const loaded = new Set();

const SECTION_STYLES = {
	'api-keys': () => import( '../styles/api-keys.css' ),
	'chatbot-settings': () =>
		Promise.all( [
			import( '../styles/chatbot.css' ),
			import( '../styles/shared-subnav.css' ),
		] ),
	'display-settings': () => import( '../styles/display.css' ),
	instructions: () => import( '../styles/instructions.css' ),
	'knowledge-base': () =>
		Promise.all( [
			import( '../styles/knowledge.css' ),
			import( '../styles/shared-subnav.css' ),
		] ),
	'chat-sessions': () => import( '../styles/sessions.css' ),
	'chat-preview': () => import( '../styles/preview.css' ),
};

/**
 * @param {string} sectionId Tab id from App nav.
 * @return {Promise<void>}
 */
export function loadSectionStyles( sectionId ) {
	if ( ! sectionId || loaded.has( sectionId ) ) {
		return Promise.resolve();
	}
	const loader = SECTION_STYLES[ sectionId ];
	if ( ! loader ) {
		return Promise.resolve();
	}
	loaded.add( sectionId );
	return loader().then( () => undefined );
}

/**
 * @return {Promise<void>}
 */
export function loadWizardStyles() {
	if ( loaded.has( 'wizard' ) ) {
		return Promise.resolve();
	}
	loaded.add( 'wizard' );
	return import( '../styles/wizard.css' ).then( () => undefined );
}
