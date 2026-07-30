/**
 * AI Assistant admin shell: sidebar nav, hash routing, toast, setup wizard gate.
 */
import { useState, useEffect, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import Toast from './components/Toast';
import SetupWizard from './wizard/SetupWizard';
import ApiKeys from './sections/ApiKeys';
import ChatbotSettings from './sections/ChatbotSettings';
import DisplaySettings from './sections/DisplaySettings';
import Instructions from './sections/Instructions';
import KnowledgeBase from './sections/KnowledgeBase';
import ChatSessions from './sections/ChatSessions';
import ChatPreview from './sections/ChatPreview';

const TABS = [
	{
		id: 'api-keys',
		label: __( 'API Keys', 'dragwyb-click-to-chat' ),
		icon: 'dashicons-rest-api',
		desc: __(
			'Configure your AI providers and select your preferred chatbot models.',
			'dragwyb-click-to-chat'
		),
		component: ApiKeys,
	},
	{
		id: 'chatbot-settings',
		label: __( 'Chatbot', 'dragwyb-click-to-chat' ),
		icon: 'dashicons-admin-settings',
		desc: __(
			'Customize your chatbot name, greeting message, and behavioral features.',
			'dragwyb-click-to-chat'
		),
		component: ChatbotSettings,
	},
	{
		id: 'display-settings',
		label: __( 'Display Options', 'dragwyb-click-to-chat' ),
		icon: 'dashicons-align-center',
		desc: __(
			'Configure where and how your chatbot appears on the frontend.',
			'dragwyb-click-to-chat'
		),
		component: DisplaySettings,
	},
	{
		id: 'instructions',
		label: __( 'Instructions', 'dragwyb-click-to-chat' ),
		icon: 'dashicons-edit',
		desc: __(
			'Set how your chatbot talks, what it helps with, and how creative its replies are.',
			'dragwyb-click-to-chat'
		),
		component: Instructions,
	},
	{
		id: 'knowledge-base',
		label: __( 'Knowledge Base', 'dragwyb-click-to-chat' ),
		icon: 'dashicons-database',
		desc: __(
			'Provide custom text, links, documents, and index your website content for the chatbot to learn from.',
			'dragwyb-click-to-chat'
		),
		component: KnowledgeBase,
	},
	{
		id: 'chat-sessions',
		label: __( 'Chat Sessions', 'dragwyb-click-to-chat' ),
		icon: 'dashicons-format-chat',
		desc: __(
			'View and manage recent conversations with your AI assistant.',
			'dragwyb-click-to-chat'
		),
		component: ChatSessions,
	},
	{
		id: 'chat-preview',
		label: __( 'Chat Preview', 'dragwyb-click-to-chat' ),
		icon: 'dashicons-welcome-view-site',
		desc: __(
			'See how your chatbot appears to your website visitors.',
			'dragwyb-click-to-chat'
		),
		component: ChatPreview,
	},
];

const NAV_GROUPS = [
	{
		label: __( 'Setup', 'dragwyb-click-to-chat' ),
		items: [ 'api-keys', 'chatbot-settings', 'display-settings', 'instructions' ],
	},
	{
		label: __( 'Knowledge', 'dragwyb-click-to-chat' ),
		items: [ 'knowledge-base' ],
	},
	{
		label: __( 'Monitor', 'dragwyb-click-to-chat' ),
		items: [ 'chat-sessions', 'chat-preview' ],
	},
];

export default function App( { settings: initialSettings } ) {
	const [ settings, setSettings ] = useState( initialSettings );
	const [ notice, setNotice ] = useState( null );
	const [ showWizard, setShowWizard ] = useState(
		!! window.dctc_ai_data?.show_setup_wizard
	);
	const [ wizardKey, setWizardKey ] = useState( 0 );

	const saveChat = !! settings?.chatbot?.save_chat;
	const visibleTabs = TABS.filter(
		( tab ) => tab.id !== 'chat-sessions' || saveChat
	);

	const [ activeTab, setActiveTab ] = useState( () => {
		const hash = window.location.hash.replace( '#', '' );
		if ( hash && visibleTabs.some( ( t ) => t.id === hash ) ) {
			return hash;
		}
		return visibleTabs[ 0 ]?.id || 'api-keys';
	} );

	const showNotice = useCallback( ( message, type = 'success' ) => {
		setNotice( { message, type } );
		setTimeout( () => setNotice( null ), 10000 );
	}, [] );

	const onSave = useCallback( ( partial ) => {
		setSettings( ( prev ) => ( { ...prev, ...partial } ) );
	}, [] );

	const current = visibleTabs.find( ( t ) => t.id === activeTab ) || visibleTabs[ 0 ];
	const Panel = current?.component;

	useEffect( () => {
		if ( ! visibleTabs.some( ( t ) => t.id === activeTab ) ) {
			const id = visibleTabs[ 0 ].id;
			setActiveTab( id );
			window.location.hash = id;
		}
	}, [ activeTab, visibleTabs ] );

	useEffect( () => {
		const el = document.querySelector( '.dctc-ai-tabs .dctc-ai-tab.active' );
		if ( el && typeof el.scrollIntoView === 'function' ) {
			el.scrollIntoView( { block: 'nearest', inline: 'nearest' } );
		}
	}, [ activeTab ] );

	useEffect( () => {
		const onHash = () => {
			const hash = window.location.hash.replace( '#', '' );
			if ( hash && visibleTabs.some( ( t ) => t.id === hash ) ) {
				setActiveTab( hash );
			}
		};
		window.addEventListener( 'hashchange', onHash );
		return () => window.removeEventListener( 'hashchange', onHash );
	}, [ visibleTabs ] );

	useEffect( () => {
		if ( ! showWizard ) {
			return;
		}
		const url = new URL( window.location.href );
		if ( url.searchParams.has( 'dctc_ai_open_wizard' ) ) {
			url.searchParams.delete( 'dctc_ai_open_wizard' );
			window.history.replaceState( {}, '', url.toString() );
		}
	}, [ showWizard ] );

	const goToTab = ( id ) => () => {
		setActiveTab( id );
		window.location.hash = id;
	};

	return (
		<div className="dctc-ai-dashboard-wrapper">
			<SetupWizard
				open={ showWizard }
				settings={ settings }
				onSave={ onSave }
				onClose={ () => {
					setShowWizard( false );
					setWizardKey( ( k ) => k + 1 );
				} }
				showNotice={ showNotice }
			/>

			<header className="dctc-ai-dashboard-header">
				<div className="dctc-ai-brand">
					<span className="dashicons dashicons-format-chat" aria-hidden="true" />
					<span>{ __( 'AI Assistant', 'dragwyb-click-to-chat' ) }</span>
				</div>
				<nav
					className="dctc-ai-tabs"
					role="tablist"
					aria-label={ __( 'Settings sections', 'dragwyb-click-to-chat' ) }
				>
					{ NAV_GROUPS.map( ( group ) => (
						<div className="dctc-ai-nav-group" key={ group.label }>
							<span className="dctc-ai-nav-group__label">{ group.label }</span>
							{ group.items.map( ( itemId ) => {
								const tab = visibleTabs.find( ( t ) => t.id === itemId );
								if ( ! tab ) {
									return null;
								}
								return (
									<button
										key={ tab.id }
										type="button"
										role="tab"
										title={ tab.label }
										aria-selected={ activeTab === tab.id }
										tabIndex={ activeTab === tab.id ? 0 : -1 }
										className={
											'dctc-ai-tab ' +
											( activeTab === tab.id ? 'active' : '' )
										}
										onClick={ goToTab( tab.id ) }
									>
										<span
											className={ `dashicons ${ tab.icon }` }
											aria-hidden="true"
										/>
										<span className="dctc-ai-tab-label">{ tab.label }</span>
									</button>
								);
							} ) }
						</div>
					) ) }
				</nav>
			</header>

			<main className="dctc-ai-content">
				<header className="dctc-ai-content-header">
					<h1 id="dctc-ai-tab-title">{ current?.label }</h1>
					<p id="dctc-ai-tab-desc">{ current?.desc }</p>
				</header>

				{ notice && (
					<Toast
						message={ notice.message }
						type={ notice.type }
						onClose={ () => setNotice( null ) }
					/>
				) }

				<div className="dctc-ai-tab-panel active">
					{ Panel && (
						<Panel
							key={ `${ activeTab }-${ wizardKey }` }
							settings={ settings }
							onSave={ onSave }
							showNotice={ showNotice }
						/>
					) }
				</div>
			</main>
		</div>
	);
}
