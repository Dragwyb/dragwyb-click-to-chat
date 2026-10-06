/**
 * Dragwyb Click to Chat — Unified 4-Step Onboarding Wizard
 *
 * Screen 1: Welcome to Dragwyb Click to Chat
 * Screen 2: Choose Your Features (Social Chat, AI Assistant, Support Center)
 * Screen 3: Essential Setup (Config for enabled features)
 * Screen 4: Review Your Setup & Complete
 *
 * @package Dragwyb_Click_To_Chat
 */
import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import './onboarding.css';

/* --- Bulletproof SVG Icons with fixed inline sizing --- */
const Icons = {
	logo: (
		<svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor" style={ { width: '22px', height: '22px', display: 'block' } }>
			<path d="M12 2C6.48 2 2 6.48 2 12c0 1.82.49 3.53 1.34 5L2 22l5.22-1.31C8.63 21.49 10.27 22 12 22c5.52 0 10-4.48 10-10S17.52 2 12 2zm0 18c-1.52 0-2.95-.42-4.18-1.15l-.3-.18-3.1.78.82-3.02-.2-.32C4.38 14.88 4 13.48 4 12c0-4.41 3.59-8 8-8s8 3.59 8 8-3.59 8-8 8z" />
		</svg>
	),
	socialChat: (
		<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" style={ { width: '20px', height: '20px', display: 'block' } }>
			<path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H6l-2 2V4h16v12z" />
		</svg>
	),
	aiAssistant: (
		<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" style={ { width: '20px', height: '20px', display: 'block' } }>
			<path d="M12 2a2 2 0 0 1 2 2c0 .74-.4 1.39-1 1.73V7h1a7 7 0 0 1 7 7h1a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v1a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-1H2a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h1a7 7 0 0 1 7-7h1V5.73c-.6-.34-1-.99-1-1.73a2 2 0 0 1 2-2zM7.5 13a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3zm9 0a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3z" />
		</svg>
	),
	supportCenter: (
		<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" style={ { width: '20px', height: '20px', display: 'block' } }>
			<path d="M22 10V6a2 2 0 0 0-2-2H4c-1.1 0-1.99.9-1.99 2v4c1.1 0 1.99.9 1.99 2s-.89 2-2 2v4c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2v-4c-1.1 0-2-.9-2-2s.9-2 2-2zm-2-1.54c-1.21.75-2 2.06-2 3.54s.79 2.79 2 3.54V18H4v-2.46c1.21-.75 2-2.06 2-3.54s-.79-2.79-2-3.54V6h16v2.46z" />
		</svg>
	),
	whatsapp: (
		<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" style={ { width: '18px', height: '18px', display: 'block' } }>
			<path d="M17.472 14.382c-.301-.15-1.782-.879-2.058-.98-.276-.1-.476-.15-.677.15-.2.301-.776.98-.952 1.18-.175.201-.351.226-.652.075-.301-.15-1.27-.468-2.42-1.493-.894-.798-1.498-1.783-1.674-2.084-.175-.301-.019-.464.132-.614.136-.135.301-.351.451-.527.151-.175.201-.301.301-.501.1-.2.05-.376-.025-.526-.075-.15-.677-1.633-.928-2.238-.244-.589-.493-.509-.677-.518-.175-.009-.376-.009-.577-.009-.2 0-.526.075-.802.376-.276.301-1.053 1.028-1.053 2.508 0 1.48 1.078 2.909 1.229 3.11.15.2 2.122 3.24 5.141 4.544.718.31 1.278.496 1.716.635.722.23 1.38.197 1.9.12.58-.087 1.782-.728 2.032-1.431.251-.703.251-1.305.176-1.431-.075-.126-.276-.201-.577-.351z" />
		</svg>
	),
	phone: (
		<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" style={ { width: '18px', height: '18px', display: 'block' } }>
			<path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z" />
		</svg>
	),
	email: (
		<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" style={ { width: '18px', height: '18px', display: 'block' } }>
			<path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z" />
		</svg>
	),
	link: (
		<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" style={ { width: '18px', height: '18px', display: 'block' } }>
			<path d="M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h4v-1.9H7c-1.71 0-3.1-1.39-3.1-3.1zM8 13h8v-2H8v2zm9-6h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1s-1.39 3.1-3.1 3.1h-4V17h4c2.76 0 5-2.24 5-5s-2.24-5-5-5z" />
		</svg>
	),
	check: (
		<svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" style={ { width: '14px', height: '14px', display: 'block' } }>
			<path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z" />
		</svg>
	),
	eye: (
		<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" style={ { width: '18px', height: '18px', display: 'block' } }>
			<path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z" />
		</svg>
	),
	eyeOff: (
		<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" style={ { width: '18px', height: '18px', display: 'block' } }>
			<path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.44-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z" />
		</svg>
	),
	play: (
		<svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" style={ { width: '14px', height: '14px', display: 'block' } }>
			<path d="M8 5v14l11-7z" />
		</svg>
	),
	info: (
		<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" style={ { width: '18px', height: '18px', display: 'block', flexShrink: 0 } }>
			<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z" />
		</svg>
	),
	confetti: (
		<svg viewBox="0 0 64 64" width="64" height="64" fill="none" style={ { width: '64px', height: '64px', display: 'block' } }>
			<circle cx="12" cy="14" r="3" fill="#EC4899" />
			<circle cx="52" cy="18" r="3.5" fill="#6366F1" />
			<circle cx="28" cy="8" r="2.5" fill="#F59E0B" />
			<circle cx="48" cy="48" r="3" fill="#10B981" />
			<polygon points="34,22 38,28 30,26" fill="#3B82F6" />
			<polygon points="16,36 22,40 18,44" fill="#F43F5E" />
			<polygon points="44,32 50,30 46,38" fill="#8B5CF6" />
		</svg>
	),
};

const DEFAULT_MODELS = {
	openai: [
		{ id: 'gpt-4o-mini', name: 'GPT-4o Mini (Fast & Affordable)' },
		{ id: 'gpt-4o', name: 'GPT-4o (Flagship Omni)' },
		{ id: 'o3-mini', name: 'o3 Mini (High Reasoning)' },
		{ id: 'gpt-3.5-turbo', name: 'GPT-3.5 Turbo' },
	],
	google: [
		{ id: 'gemini-1.5-flash', name: 'Gemini 1.5 Flash' },
		{ id: 'gemini-1.5-pro', name: 'Gemini 1.5 Pro' },
		{ id: 'gemini-2.0-flash-exp', name: 'Gemini 2.0 Flash' },
	],
	anthropic: [
		{ id: 'claude-3-5-sonnet-20241022', name: 'Claude 3.5 Sonnet' },
		{ id: 'claude-3-5-haiku-20241022', name: 'Claude 3.5 Haiku' },
		{ id: 'claude-3-opus-20240229', name: 'Claude 3 Opus' },
	],
	groq: [
		{ id: 'llama-3.3-70b-versatile', name: 'Llama 3.3 70B' },
		{ id: 'llama-3.1-8b-instant', name: 'Llama 3.1 8B' },
		{ id: 'mixtral-8x7b-32768', name: 'Mixtral 8x7B' },
	],
	deepseek: [
		{ id: 'deepseek-chat', name: 'DeepSeek Chat (V3)' },
		{ id: 'deepseek-reasoner', name: 'DeepSeek Reasoner (R1)' },
	],
	openrouter: [
		{ id: 'openai/gpt-4o-mini', name: 'OpenAI GPT-4o Mini' },
		{ id: 'anthropic/claude-3.5-sonnet', name: 'Claude 3.5 Sonnet' },
		{ id: 'deepseek/deepseek-r1', name: 'DeepSeek R1' },
	],
	ollama: [
		{ id: 'llama3.2', name: 'Llama 3.2' },
		{ id: 'mistral', name: 'Mistral 7B' },
		{ id: 'qwen2.5', name: 'Qwen 2.5' },
	],
};

function normalizeModels( rawModels ) {
	if ( ! rawModels ) {
		return [ { id: 'default', name: 'Default Model' } ];
	}
	if ( Array.isArray( rawModels ) ) {
		return rawModels.map( ( m ) =>
			typeof m === 'object' && m !== null
				? { id: m.id || m.value || 'default', name: m.name || m.label || m.id || 'Default' }
				: { id: String( m ), name: String( m ) }
		);
	}
	if ( typeof rawModels === 'object' ) {
		return Object.entries( rawModels ).map( ( [ id, name ] ) => ( {
			id: String( id ),
			name: typeof name === 'string' ? name : String( id ),
		} ) );
	}
	return [ { id: 'default', name: 'Default Model' } ];
}

export default function OnboardingWizard( { open, onClose, showNotice } ) {
	const [ currentStep, setCurrentStep ] = useState( 1 );
	const [ isSaving, setIsSaving ] = useState( false );
	const [ isCompleted, setIsCompleted ] = useState( false );
	const [ showPassword, setShowPassword ] = useState( false );
	const [ showVideoModal, setShowVideoModal ] = useState( false );
	const [ createdPortalUrl, setCreatedPortalUrl ] = useState( '' );

	// Module Enable States (Step 2)
	const [ features, setFeatures ] = useState( {
		socialChat: true,
		aiAssistant: true,
		supportCenter: true,
	} );

	// Module Settings (Step 3)
	const [ socialChatSettings, setSocialChatSettings ] = useState( {
		enabled: true,
		whatsapp_enabled: true,
		whatsapp_number: '+91 98765 43210',
		whatsapp_message: 'Hello, I need help with your website.',
		phone_enabled: false,
		phone_number: '+91 98765 43210',
		email_enabled: true,
		email_address: window.dctc_ai_data?.admin_email || 'support@yourdomain.com',
		custom_link_enabled: true,
		custom_link_url: window.dctc_ai_data?.home_url ? `${ window.dctc_ai_data.home_url }/contact` : 'https://yourdomain.com/contact',
	} );

	const [ aiSettings, setAiSettings ] = useState( {
		enabled: true,
		provider: 'openai',
		api_key: '',
		model: 'gpt-4o-mini',
		assistant_name: 'Dragwyb Assistant',
		welcome_message: 'Hi! How can I help you today?',
	} );

	const [ supportSettings, setSupportSettings ] = useState( {
		enabled: true,
		default_category: 'Product Support',
		default_priority: 'normal',
		support_email: window.dctc_ai_data?.admin_email || 'support@yourdomain.com',
		customer_portal: true,
		auto_create_portal_page: true,
	} );

	// Pre-fill existing settings if available
	useEffect( () => {
		const s = window.dctc_ai_data?.social_settings || {};
		const ai = window.dctc_ai_data?.settings || {};
		const supp = window.dctc_ai_data?.support_settings || {};

		if ( s.whatsapp_value ) {
			setSocialChatSettings( ( prev ) => ( {
				...prev,
				whatsapp_enabled: s.whatsapp_enabled !== '0',
				whatsapp_number: s.whatsapp_value,
				phone_enabled: s.phone_enabled === '1',
				phone_number: s.phone_value || prev.phone_number,
				email_enabled: s.email_enabled !== '0',
				email_address: s.email_value || prev.email_address,
				custom_link_enabled: s.contact_enabled !== '0',
				custom_link_url: s.contact_value || prev.custom_link_url,
			} ) );
		}

		if ( ai.chatbot?.default_provider ) {
			setAiSettings( ( prev ) => ( {
				...prev,
				provider: ai.chatbot.default_provider || 'openai',
				model: ai.chatbot.default_model || 'gpt-4o-mini',
				assistant_name: ai.chatbot.bot_name || 'Dragwyb Assistant',
				welcome_message: ai.chatbot.welcome_message || 'Hi! How can I help you today?',
			} ) );
		}

		if ( supp.support_email ) {
			setSupportSettings( ( prev ) => ( {
				...prev,
				support_email: supp.support_email,
				default_priority: supp.default_priority || 'normal',
				customer_portal: supp.customer_portal !== false,
			} ) );
		}
	}, [] );

	if ( ! open ) {
		return null;
	}

	const toggleFeature = ( key ) => {
		setFeatures( ( prev ) => {
			const updated = { ...prev, [ key ]: ! prev[ key ] };
			// Prevent disabling all features
			if ( ! updated.socialChat && ! updated.aiAssistant && ! updated.supportCenter ) {
				return prev;
			}
			return updated;
		} );
	};

	const removeOpenParamFromUrl = () => {
		if ( typeof window !== 'undefined' && window.history && window.history.replaceState ) {
			const url = new URL( window.location.href );
			if ( url.searchParams.has( 'open' ) || url.searchParams.has( 'dctc_open_onboarding' ) ) {
				url.searchParams.delete( 'open' );
				url.searchParams.delete( 'dctc_open_onboarding' );
				window.history.replaceState( {}, document.title, url.pathname + url.search + url.hash );
			}
		}
	};

	const handleSkip = async () => {
		removeOpenParamFromUrl();
		setIsSaving( true );
		try {
			await apiFetch( {
				path: '/dctc-ai/v1/onboarding/skip',
				method: 'POST',
			} );
			if ( showNotice ) {
				showNotice( __( 'Onboarding skipped. You can configure all features anytime.', 'dragwyb-click-to-chat' ) );
			}
			onClose();
		} catch ( e ) {
			onClose();
		} finally {
			setIsSaving( false );
		}
	};

	const handleCompleteSetup = async () => {
		removeOpenParamFromUrl();
		setIsSaving( true );
		try {
			const res = await apiFetch( {
				path: '/dctc-ai/v1/onboarding/complete',
				method: 'POST',
				data: {
					social_chat: {
						...socialChatSettings,
						widget_position: ( features.socialChat && features.aiAssistant ) ? 'left' : ( socialChatSettings.widget_position || 'right' ),
						enabled: features.socialChat && socialChatSettings.enabled,
					},
					ai_assistant: {
						...aiSettings,
						enabled: features.aiAssistant && aiSettings.enabled,
					},
					support_center: {
						...supportSettings,
						enabled: features.supportCenter && supportSettings.enabled,
					},
				},
			} );

			if ( res.portal_url ) {
				setCreatedPortalUrl( res.portal_url );
			}

			setIsCompleted( true );
			if ( showNotice ) {
				showNotice( __( 'Setup completed successfully!', 'dragwyb-click-to-chat' ) );
			}
		} catch ( err ) {
			if ( showNotice ) {
				showNotice( err.message || __( 'Failed to save onboarding settings.', 'dragwyb-click-to-chat' ), 'error' );
			}
		} finally {
			setIsSaving( false );
		}
	};

	// Models for currently selected AI provider (normalized cleanly)
	const rawProviderModels = window.dctc_ai_data?.models_list?.[ aiSettings.provider ] || DEFAULT_MODELS[ aiSettings.provider ];
	const availableModels = normalizeModels( rawProviderModels );

	return (
		<div className="dctc-onboarding-overlay" role="main" aria-label="Onboarding Setup">
			<div className="dctc-onboarding-modal">
				{ /* Top Header Bar */ }
				<header className="dctc-onboarding-header">
					<div className="dctc-onboarding-brand">
						<div className="dctc-onboarding-brand__icon">
							{ Icons.logo }
						</div>
						<span className="dctc-onboarding-brand__name">Dragwyb</span>
					</div>

					{ /* Stepper */ }
					<nav className="dctc-onboarding-stepper" aria-label="Onboarding Progress">
						{ [
							{ num: 1, label: __( 'Welcome', 'dragwyb-click-to-chat' ) },
							{ num: 2, label: __( 'Features', 'dragwyb-click-to-chat' ) },
							{ num: 3, label: __( 'Setup', 'dragwyb-click-to-chat' ) },
							{ num: 4, label: __( 'Review', 'dragwyb-click-to-chat' ) },
						].map( ( s, idx ) => {
							const isActive = currentStep === s.num;
							const isCompletedStep = currentStep > s.num;
							const isClickable = s.num < currentStep;

							return (
								<div className="dctc-onboarding-step-item" key={ s.num }>
									{ idx > 0 && (
										<div className={ `dctc-onboarding-step-line ${ isCompletedStep || isActive ? 'is-completed' : '' }` } />
									) }
									<div
										className={ `dctc-onboarding-step-node ${ isActive ? 'is-active' : '' } ${ isCompletedStep ? 'is-completed' : '' } ${ isClickable ? 'is-clickable' : '' }` }
										onClick={ () => isClickable && setCurrentStep( s.num ) }
									>
										<div className="dctc-onboarding-step-circle">
											{ isCompletedStep ? Icons.check : s.num }
										</div>
										<span className="dctc-onboarding-step-label">{ s.label }</span>
									</div>
								</div>
							);
						} ) }
					</nav>

					<button
						type="button"
						className="dctc-onboarding-skip-btn"
						onClick={ handleSkip }
						disabled={ isSaving }
					>
						{ __( 'Skip Setup →', 'dragwyb-click-to-chat' ) }
					</button>
				</header>

				{ /* Content Area */ }
				{ isCompleted ? (
					/* Success Celebration Screen */
					<div className="dctc-success-screen">
						<div className="dctc-success-icon-badge">
							{ Icons.check }
						</div>
						<h2 className="dctc-success-title">
							{ __( '🎉 You Are All Set!', 'dragwyb-click-to-chat' ) }
						</h2>
						<p className="dctc-success-desc">
							{ __( 'Dragwyb Click to Chat has been configured and is ready to connect with your visitors.', 'dragwyb-click-to-chat' ) }
						</p>

						<div className="dctc-success-actions">
							<button
								type="button"
								className="dctc-btn-primary"
								onClick={ () => {
									removeOpenParamFromUrl();
									onClose();
								} }
							>
								{ __( 'Go to Plugin Dashboard →', 'dragwyb-click-to-chat' ) }
							</button>

							{ createdPortalUrl && (
								<a
									href={ createdPortalUrl }
									target="_blank"
									rel="noreferrer"
									className="dctc-btn-secondary"
								>
									{ __( 'View Support Portal ↗', 'dragwyb-click-to-chat' ) }
								</a>
							) }
						</div>
					</div>
				) : (
					<>
						{ /* ================= STEP 1: WELCOME ================= */ }
						{ currentStep === 1 && (
							<div className="dctc-welcome-screen">
								{ /* Left Column */ }
								<div className="dctc-welcome-left">
									<div className="dctc-welcome-badge">
										<span>✦</span> { __( 'Version 1.2.0', 'dragwyb-click-to-chat' ) }
									</div>

									<h1 className="dctc-welcome-title">
										{ __( 'Welcome to', 'dragwyb-click-to-chat' ) }<br />
										<span className="dctc-welcome-title__accent">Dragwyb Click to Chat</span>
									</h1>

									<p className="dctc-welcome-desc">
										{ __( 'Connect with your customers, automate conversations with AI, and manage customer support from one place — inside WordPress.', 'dragwyb-click-to-chat' ) }
									</p>

									{ /* 3 Feature Preview Cards */ }
									<div className="dctc-welcome-features-grid">
										<div className="dctc-welcome-feature-card">
											<div className="dctc-welcome-feature-card__icon dctc-welcome-feature-card__icon--social">
												{ Icons.socialChat }
											</div>
											<h3 className="dctc-welcome-feature-card__title">
												{ __( 'Social Chat', 'dragwyb-click-to-chat' ) }
											</h3>
											<p className="dctc-welcome-feature-card__desc">
												{ __( 'WhatsApp, phone, email and custom links.', 'dragwyb-click-to-chat' ) }
											</p>
										</div>

										<div className="dctc-welcome-feature-card">
											<div className="dctc-welcome-feature-card__icon dctc-welcome-feature-card__icon--ai">
												{ Icons.aiAssistant }
											</div>
											<h3 className="dctc-welcome-feature-card__title">
												{ __( 'AI Assistant', 'dragwyb-click-to-chat' ) }
											</h3>
											<p className="dctc-welcome-feature-card__desc">
												{ __( 'Automated answers, capture leads and assist customers.', 'dragwyb-click-to-chat' ) }
											</p>
										</div>

										<div className="dctc-welcome-feature-card">
											<div className="dctc-welcome-feature-card__icon dctc-welcome-feature-card__icon--support">
												{ Icons.supportCenter }
											</div>
											<h3 className="dctc-welcome-feature-card__title">
												{ __( 'Support Center', 'dragwyb-click-to-chat' ) }
											</h3>
											<p className="dctc-welcome-feature-card__desc">
												{ __( 'Manage support tickets, assign agents and provide human support.', 'dragwyb-click-to-chat' ) }
											</p>
										</div>
									</div>

									{ /* 4 Check Badges */ }
									<div className="dctc-welcome-pills">
										<span className="dctc-welcome-pill">
											{ Icons.check } { __( 'Easy Setup', 'dragwyb-click-to-chat' ) }
										</span>
										<span className="dctc-welcome-pill">
											{ Icons.check } { __( 'Fully Modular', 'dragwyb-click-to-chat' ) }
										</span>
										<span className="dctc-welcome-pill">
											{ Icons.check } { __( 'Works with WordPress', 'dragwyb-click-to-chat' ) }
										</span>
										<span className="dctc-welcome-pill">
											{ Icons.check } { __( 'Grow Your Business', 'dragwyb-click-to-chat' ) }
										</span>
									</div>

									{ /* Action Buttons */ }
									<div className="dctc-welcome-actions">
										<button
											type="button"
											className="dctc-btn-primary"
											onClick={ () => setCurrentStep( 2 ) }
										>
											{ __( 'Get Started →', 'dragwyb-click-to-chat' ) }
										</button>
										<button
											type="button"
											className="dctc-btn-secondary"
											onClick={ () => setShowVideoModal( true ) }
										>
											{ Icons.play } { __( 'Watch Video (2 min)', 'dragwyb-click-to-chat' ) }
										</button>
									</div>
								</div>

								{ /* Right Column Mockup */ }
								<div className="dctc-welcome-mockup-wrapper">
									<div className="dctc-welcome-mockup-card">
										<div className="dctc-welcome-mockup-header">
											<div className="dctc-welcome-mockup-avatar">
												{ Icons.aiAssistant }
											</div>
											<div className="dctc-welcome-mockup-botinfo">
												<span className="dctc-welcome-mockup-botname">Dragwyb Assistant</span>
												<span className="dctc-welcome-mockup-status">Online</span>
											</div>
										</div>

										<div className="dctc-welcome-mockup-body">
											<div className="dctc-welcome-mockup-bubble">
												Hi! How can I help you today?<br />
												You can ask about our products, orders or create a support ticket.
											</div>

											<div className="dctc-welcome-mockup-chips">
												<div className="dctc-welcome-mockup-chip">
													<svg viewBox="0 0 24 24" width="12" height="12" fill="currentColor"><circle cx="12" cy="12" r="6" /></svg>
													Track My Order
												</div>
												<div className="dctc-welcome-mockup-chip">
													<svg viewBox="0 0 24 24" width="12" height="12" fill="currentColor"><circle cx="12" cy="12" r="6" /></svg>
													Product Information
												</div>
												<div className="dctc-welcome-mockup-chip">
													<svg viewBox="0 0 24 24" width="12" height="12" fill="currentColor"><circle cx="12" cy="12" r="6" /></svg>
													Talk to Support
												</div>
											</div>
										</div>

										<div className="dctc-welcome-mockup-input">
											<span>Type your message...</span>
											<div className="dctc-welcome-mockup-send">
												<svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z" /></svg>
											</div>
										</div>
									</div>

									{ /* Side Floating Channels */ }
									<div className="dctc-welcome-mockup-channels">
										<div className="dctc-mockup-chan-btn dctc-mockup-chan-btn--whatsapp">
											{ Icons.whatsapp }
										</div>
										<div className="dctc-mockup-chan-btn dctc-mockup-chan-btn--phone">
											{ Icons.phone }
										</div>
										<div className="dctc-mockup-chan-btn dctc-mockup-chan-btn--email">
											{ Icons.email }
										</div>
										<div className="dctc-mockup-chan-btn dctc-mockup-chan-btn--link">
											{ Icons.link }
										</div>
										<div className="dctc-mockup-chan-btn dctc-mockup-chan-btn--close">
											✕
										</div>
									</div>

									<div className="dctc-welcome-handwritten-note">
										<span>⤷ All in one customer communication platform.</span>
									</div>
								</div>
							</div>
						) }

						{ /* ================= STEP 2: CHOOSE FEATURES ================= */ }
						{ currentStep === 2 && (
							<div>
								<div className="dctc-screen-header">
									<h2 className="dctc-screen-title">
										{ __( 'Choose Your Features', 'dragwyb-click-to-chat' ) }
									</h2>
									<p className="dctc-screen-desc">
										{ __( 'Select the features you want to enable. You can configure additional options and advanced settings later from your Dragwyb dashboard.', 'dragwyb-click-to-chat' ) }
									</p>
								</div>

								<div className="dctc-features-grid">
									{ /* Card 1: Social Chat */ }
									<div
										className={ `dctc-feature-select-card ${ features.socialChat ? 'is-selected' : '' }` }
										onClick={ () => toggleFeature( 'socialChat' ) }
									>
										<div className="dctc-feature-select-card__top">
											<div className="dctc-feature-select-card__icon dctc-feature-select-card__icon--social">
												{ Icons.socialChat }
											</div>
											<div className="dctc-custom-checkbox">
												{ Icons.check }
											</div>
										</div>

										<h3 className="dctc-feature-select-card__title">
											{ __( 'Social Chat (Click to Chat)', 'dragwyb-click-to-chat' ) }
										</h3>
										<p className="dctc-feature-select-card__desc">
											{ __( 'Connect customers directly via WhatsApp, phone, email and custom links.', 'dragwyb-click-to-chat' ) }
										</p>

										<ul className="dctc-feature-select-card__bullets">
											<li className="dctc-feature-select-card__bullet">
												{ Icons.check } { __( 'WhatsApp', 'dragwyb-click-to-chat' ) }
											</li>
											<li className="dctc-feature-select-card__bullet">
												{ Icons.check } { __( 'Phone Call', 'dragwyb-click-to-chat' ) }
											</li>
											<li className="dctc-feature-select-card__bullet">
												{ Icons.check } { __( 'Email', 'dragwyb-click-to-chat' ) }
											</li>
											<li className="dctc-feature-select-card__bullet">
												{ Icons.check } { __( 'Custom Link', 'dragwyb-click-to-chat' ) }
											</li>
										</ul>
									</div>

									{ /* Card 2: AI Assistant */ }
									<div
										className={ `dctc-feature-select-card ${ features.aiAssistant ? 'is-selected' : '' }` }
										onClick={ () => toggleFeature( 'aiAssistant' ) }
									>
										<div className="dctc-feature-select-card__top">
											<div className="dctc-feature-select-card__icon dctc-feature-select-card__icon--ai">
												{ Icons.aiAssistant }
											</div>
											<div className="dctc-custom-checkbox">
												{ Icons.check }
											</div>
										</div>

										<h3 className="dctc-feature-select-card__title">
											{ __( 'AI Assistant', 'dragwyb-click-to-chat' ) }
										</h3>
										<p className="dctc-feature-select-card__desc">
											{ __( 'Let AI answer customer questions, capture leads and automate support.', 'dragwyb-click-to-chat' ) }
										</p>

										<ul className="dctc-feature-select-card__bullets">
											<li className="dctc-feature-select-card__bullet">
												{ Icons.check } { __( 'Multi AI Providers', 'dragwyb-click-to-chat' ) }
											</li>
											<li className="dctc-feature-select-card__bullet">
												{ Icons.check } { __( 'Instant Responses', 'dragwyb-click-to-chat' ) }
											</li>
											<li className="dctc-feature-select-card__bullet">
												{ Icons.check } { __( 'Lead Capture', 'dragwyb-click-to-chat' ) }
											</li>
											<li className="dctc-feature-select-card__bullet">
												{ Icons.check } { __( 'Works with your content', 'dragwyb-click-to-chat' ) }
											</li>
										</ul>
									</div>

									{ /* Card 3: Support Center */ }
									<div
										className={ `dctc-feature-select-card ${ features.supportCenter ? 'is-selected' : '' }` }
										onClick={ () => toggleFeature( 'supportCenter' ) }
									>
										<div className="dctc-feature-select-card__top">
											<div className="dctc-feature-select-card__icon dctc-feature-select-card__icon--support">
												{ Icons.supportCenter }
											</div>
											<div className="dctc-custom-checkbox">
												{ Icons.check }
											</div>
										</div>

										<h3 className="dctc-feature-select-card__title">
											{ __( 'Support Center', 'dragwyb-click-to-chat' ) }
										</h3>
										<p className="dctc-feature-select-card__desc">
											{ __( 'Manage support tickets, assign agents and provide human support.', 'dragwyb-click-to-chat' ) }
										</p>

										<ul className="dctc-feature-select-card__bullets">
											<li className="dctc-feature-select-card__bullet">
												{ Icons.check } { __( 'Ticket Management', 'dragwyb-click-to-chat' ) }
											</li>
											<li className="dctc-feature-select-card__bullet">
												{ Icons.check } { __( 'Agent Assignment', 'dragwyb-click-to-chat' ) }
											</li>
											<li className="dctc-feature-select-card__bullet">
												{ Icons.check } { __( 'Customer Portal', 'dragwyb-click-to-chat' ) }
											</li>
											<li className="dctc-feature-select-card__bullet">
												{ Icons.check } { __( 'Email Notifications', 'dragwyb-click-to-chat' ) }
											</li>
										</ul>
									</div>
								</div>

								<div className="dctc-info-note">
									{ Icons.info }
									<span>{ __( 'At least one feature must be enabled to continue. You can change these anytime from settings.', 'dragwyb-click-to-chat' ) }</span>
								</div>

								<footer className="dctc-onboarding-footer">
									<button
										type="button"
										className="dctc-btn-secondary"
										onClick={ () => setCurrentStep( 1 ) }
									>
										{ __( '← Back', 'dragwyb-click-to-chat' ) }
									</button>
									<button
										type="button"
										className="dctc-btn-primary"
										onClick={ () => setCurrentStep( 3 ) }
									>
										{ __( 'Continue →', 'dragwyb-click-to-chat' ) }
									</button>
								</footer>
							</div>
						) }

						{ /* ================= STEP 3: ESSENTIAL SETUP ================= */ }
						{ currentStep === 3 && (
							<div>
								<div className="dctc-screen-header">
									<h2 className="dctc-screen-title">
										{ __( 'Essential Setup', 'dragwyb-click-to-chat' ) }
									</h2>
									<p className="dctc-screen-desc">
										{ __( 'Configure the basic settings to start using your selected features. You can change these settings later from the plugin dashboard.', 'dragwyb-click-to-chat' ) }
									</p>
								</div>

								<div className="dctc-setup-grid">
									{ /* Column 1: Social Chat (If Selected) */ }
									{ features.socialChat && (
										<div className="dctc-setup-card">
											<div className="dctc-setup-card__header">
												<div className="dctc-setup-card__title-wrap">
													<div className="dctc-setup-card__icon dctc-setup-card__icon--social">
														{ Icons.socialChat }
													</div>
													<h3 className="dctc-setup-card__title">
														{ __( 'Social Chat', 'dragwyb-click-to-chat' ) }
													</h3>
												</div>
												<label className="dctc-toggle-switch">
													<input
														type="checkbox"
														checked={ socialChatSettings.enabled }
														onChange={ ( e ) => setSocialChatSettings( ( prev ) => ( { ...prev, enabled: e.target.checked } ) ) }
													/>
													<span className="dctc-toggle-slider" />
													<span>{ socialChatSettings.enabled ? __( 'Enabled', 'dragwyb-click-to-chat' ) : __( 'Disabled', 'dragwyb-click-to-chat' ) }</span>
												</label>
											</div>

											<div className="dctc-form-group">
												<label className="dctc-form-label">
													{ __( 'Contact Channels', 'dragwyb-click-to-chat' ) }
												</label>
												<p className="dctc-form-helper">
													{ __( 'Add at least one contact channel.', 'dragwyb-click-to-chat' ) }
												</p>
											</div>

											<div className="dctc-channel-list">
												{ /* WhatsApp */ }
												<div className="dctc-channel-row">
													<input
														type="checkbox"
														className="dctc-channel-checkbox"
														checked={ socialChatSettings.whatsapp_enabled }
														onChange={ ( e ) => setSocialChatSettings( ( prev ) => ( { ...prev, whatsapp_enabled: e.target.checked } ) ) }
													/>
													<div className="dctc-channel-icon-pill dctc-channel-icon-pill--whatsapp">
														{ Icons.whatsapp } WhatsApp
													</div>
													<input
														type="text"
														className="dctc-input"
														placeholder="+91 98765 43210"
														value={ socialChatSettings.whatsapp_number }
														onChange={ ( e ) => setSocialChatSettings( ( prev ) => ( { ...prev, whatsapp_number: e.target.value } ) ) }
													/>
												</div>

												{ /* Phone Call */ }
												<div className="dctc-channel-row">
													<input
														type="checkbox"
														className="dctc-channel-checkbox"
														checked={ socialChatSettings.phone_enabled }
														onChange={ ( e ) => setSocialChatSettings( ( prev ) => ( { ...prev, phone_enabled: e.target.checked } ) ) }
													/>
													<div className="dctc-channel-icon-pill dctc-channel-icon-pill--phone">
														{ Icons.phone } Phone Call
													</div>
													<input
														type="text"
														className="dctc-input"
														placeholder="+91 98765 43210"
														value={ socialChatSettings.phone_number }
														onChange={ ( e ) => setSocialChatSettings( ( prev ) => ( { ...prev, phone_number: e.target.value } ) ) }
													/>
												</div>

												{ /* Email */ }
												<div className="dctc-channel-row">
													<input
														type="checkbox"
														className="dctc-channel-checkbox"
														checked={ socialChatSettings.email_enabled }
														onChange={ ( e ) => setSocialChatSettings( ( prev ) => ( { ...prev, email_enabled: e.target.checked } ) ) }
													/>
													<div className="dctc-channel-icon-pill dctc-channel-icon-pill--email">
														{ Icons.email } Email
													</div>
													<input
														type="email"
														className="dctc-input"
														placeholder="support@yourdomain.com"
														value={ socialChatSettings.email_address }
														onChange={ ( e ) => setSocialChatSettings( ( prev ) => ( { ...prev, email_address: e.target.value } ) ) }
													/>
												</div>

												{ /* Custom Link */ }
												<div className="dctc-channel-row">
													<input
														type="checkbox"
														className="dctc-channel-checkbox"
														checked={ socialChatSettings.custom_link_enabled }
														onChange={ ( e ) => setSocialChatSettings( ( prev ) => ( { ...prev, custom_link_enabled: e.target.checked } ) ) }
													/>
													<div className="dctc-channel-icon-pill dctc-channel-icon-pill--link">
														{ Icons.link } Custom Link
													</div>
													<input
														type="url"
														className="dctc-input"
														placeholder="https://yourdomain.com/contact"
														value={ socialChatSettings.custom_link_url }
														onChange={ ( e ) => setSocialChatSettings( ( prev ) => ( { ...prev, custom_link_url: e.target.value } ) ) }
													/>
												</div>
											</div>
										</div>
									) }

									{ /* Column 2: AI Assistant (If Selected) */ }
									{ features.aiAssistant && (
										<div className="dctc-setup-card">
											<div className="dctc-setup-card__header">
												<div className="dctc-setup-card__title-wrap">
													<div className="dctc-setup-card__icon dctc-setup-card__icon--ai">
														{ Icons.aiAssistant }
													</div>
													<h3 className="dctc-setup-card__title">
														{ __( 'AI Assistant', 'dragwyb-click-to-chat' ) }
													</h3>
												</div>
												<label className="dctc-toggle-switch">
													<input
														type="checkbox"
														checked={ aiSettings.enabled }
														onChange={ ( e ) => setAiSettings( ( prev ) => ( { ...prev, enabled: e.target.checked } ) ) }
													/>
													<span className="dctc-toggle-slider" />
													<span>{ aiSettings.enabled ? __( 'Enabled', 'dragwyb-click-to-chat' ) : __( 'Disabled', 'dragwyb-click-to-chat' ) }</span>
												</label>
											</div>

											<div className="dctc-form-group">
												<label className="dctc-form-label">
													{ __( 'AI Provider', 'dragwyb-click-to-chat' ) }
												</label>
												<select
													className="dctc-select"
													value={ aiSettings.provider }
													onChange={ ( e ) => {
														const p = e.target.value;
														const mList = normalizeModels( window.dctc_ai_data?.models_list?.[ p ] || DEFAULT_MODELS[ p ] );
														setAiSettings( ( prev ) => ( {
															...prev,
															provider: p,
															model: mList[ 0 ]?.id || 'default',
														} ) );
													} }
												>
													<option value="openai">OpenAI</option>
													<option value="google">Google Gemini</option>
													<option value="anthropic">Anthropic Claude</option>
													<option value="groq">Groq</option>
													<option value="deepseek">DeepSeek</option>
													<option value="openrouter">OpenRouter</option>
													<option value="ollama">Ollama (Local)</option>
												</select>
											</div>

											<div className="dctc-form-group">
												<label className="dctc-form-label">
													{ __( 'API Key', 'dragwyb-click-to-chat' ) }
												</label>
												<div className="dctc-password-wrap">
													<input
														type={ showPassword ? 'text' : 'password' }
														className="dctc-input"
														placeholder="sk-..."
														value={ aiSettings.api_key }
														onChange={ ( e ) => setAiSettings( ( prev ) => ( { ...prev, api_key: e.target.value } ) ) }
													/>
													<button
														type="button"
														className="dctc-password-toggle-btn"
														onClick={ () => setShowPassword( ! showPassword ) }
														title="Toggle visibility"
													>
														{ showPassword ? Icons.eyeOff : Icons.eye }
													</button>
												</div>
											</div>

											<div className="dctc-form-group">
												<label className="dctc-form-label">
													{ __( 'Model', 'dragwyb-click-to-chat' ) }
												</label>
												<select
													className="dctc-select"
													value={ aiSettings.model }
													onChange={ ( e ) => setAiSettings( ( prev ) => ( { ...prev, model: e.target.value } ) ) }
												>
													{ availableModels.map( ( m ) => (
														<option key={ m.id } value={ m.id }>{ m.name }</option>
													) ) }
												</select>
											</div>

											<div className="dctc-form-group">
												<label className="dctc-form-label">
													{ __( 'Assistant Name', 'dragwyb-click-to-chat' ) }
												</label>
												<input
													type="text"
													className="dctc-input"
													placeholder="Dragwyb Assistant"
													value={ aiSettings.assistant_name }
													onChange={ ( e ) => setAiSettings( ( prev ) => ( { ...prev, assistant_name: e.target.value } ) ) }
												/>
											</div>

											<div className="dctc-form-group">
												<label className="dctc-form-label">
													<span>{ __( 'Welcome Message', 'dragwyb-click-to-chat' ) }</span>
													<span className="dctc-char-counter">{ aiSettings.welcome_message.length }/200</span>
												</label>
												<textarea
													className="dctc-textarea"
													rows="2"
													maxLength={ 200 }
													value={ aiSettings.welcome_message }
													onChange={ ( e ) => setAiSettings( ( prev ) => ( { ...prev, welcome_message: e.target.value } ) ) }
												/>
											</div>
										</div>
									) }

									{ /* Column 3: Support Center (If Selected) */ }
									{ features.supportCenter && (
										<div className="dctc-setup-card">
											<div className="dctc-setup-card__header">
												<div className="dctc-setup-card__title-wrap">
													<div className="dctc-setup-card__icon dctc-setup-card__icon--support">
														{ Icons.supportCenter }
													</div>
													<h3 className="dctc-setup-card__title">
														{ __( 'Support Center', 'dragwyb-click-to-chat' ) }
													</h3>
												</div>
												<label className="dctc-toggle-switch">
													<input
														type="checkbox"
														checked={ supportSettings.enabled }
														onChange={ ( e ) => setSupportSettings( ( prev ) => ( { ...prev, enabled: e.target.checked } ) ) }
													/>
													<span className="dctc-toggle-slider" />
													<span>{ supportSettings.enabled ? __( 'Enabled', 'dragwyb-click-to-chat' ) : __( 'Disabled', 'dragwyb-click-to-chat' ) }</span>
												</label>
											</div>

											<div className="dctc-form-group">
												<label className="dctc-form-label">
													{ __( 'Default Category', 'dragwyb-click-to-chat' ) }
												</label>
												<select
													className="dctc-select"
													value={ supportSettings.default_category }
													onChange={ ( e ) => setSupportSettings( ( prev ) => ( { ...prev, default_category: e.target.value } ) ) }
												>
													<option value="Product Support">Product Support</option>
													<option value="General Inquiry">General Inquiry</option>
													<option value="Billing & Account">Billing & Account</option>
													<option value="Technical Support">Technical Support</option>
												</select>
												<p className="dctc-form-helper">
													{ __( 'Initial category for new tickets.', 'dragwyb-click-to-chat' ) }
												</p>
											</div>

											<div className="dctc-form-group">
												<label className="dctc-form-label">
													{ __( 'Default Priority', 'dragwyb-click-to-chat' ) }
												</label>
												<select
													className="dctc-select"
													value={ supportSettings.default_priority }
													onChange={ ( e ) => setSupportSettings( ( prev ) => ( { ...prev, default_priority: e.target.value } ) ) }
												>
													<option value="low">Low</option>
													<option value="normal">Normal</option>
													<option value="high">High</option>
													<option value="urgent">Urgent</option>
												</select>
												<p className="dctc-form-helper">
													{ __( 'Default priority for new tickets.', 'dragwyb-click-to-chat' ) }
												</p>
											</div>

											<div className="dctc-form-group">
												<label className="dctc-form-label">
													{ __( 'Support Email', 'dragwyb-click-to-chat' ) }
												</label>
												<input
													type="email"
													className="dctc-input"
													placeholder="support@yourdomain.com"
													value={ supportSettings.support_email }
													onChange={ ( e ) => setSupportSettings( ( prev ) => ( { ...prev, support_email: e.target.value } ) ) }
												/>
												<p className="dctc-form-helper">
													{ __( 'Notifications will be sent to this email.', 'dragwyb-click-to-chat' ) }
												</p>
											</div>

											<div className="dctc-form-group">
												<label className="dctc-toggle-switch">
													<input
														type="checkbox"
														checked={ supportSettings.customer_portal }
														onChange={ ( e ) => setSupportSettings( ( prev ) => ( { ...prev, customer_portal: e.target.checked } ) ) }
													/>
													<span className="dctc-toggle-slider" />
													<span>{ __( 'Customer Portal', 'dragwyb-click-to-chat' ) }</span>
												</label>
												<p className="dctc-form-helper">
													{ __( 'Allow customers to create and track tickets.', 'dragwyb-click-to-chat' ) }
												</p>
											</div>

											<div className="dctc-form-group" style={ { marginTop: '4px' } }>
												<label style={ { display: 'flex', alignItems: 'center', gap: '8px', fontSize: '12.5px', fontWeight: '600', color: '#1e293b', cursor: 'pointer' } }>
													<input
														type="checkbox"
														style={ { width: '16px', height: '16px', borderRadius: '4px' } }
														checked={ supportSettings.auto_create_portal_page }
														onChange={ ( e ) => setSupportSettings( ( prev ) => ( { ...prev, auto_create_portal_page: e.target.checked } ) ) }
													/>
													<span>{ __( 'Auto-create Support Portal Page', 'dragwyb-click-to-chat' ) }</span>
												</label>
												<p className="dctc-form-helper" style={ { marginLeft: '24px' } }>
													{ __( 'Creates a new page with [dctc_support_portal] shortcode.', 'dragwyb-click-to-chat' ) }
												</p>
											</div>
										</div>
									) }
								</div>

								<footer className="dctc-onboarding-footer">
									<button
										type="button"
										className="dctc-btn-secondary"
										onClick={ () => setCurrentStep( 2 ) }
									>
										{ __( '← Back', 'dragwyb-click-to-chat' ) }
									</button>
									<button
										type="button"
										className="dctc-btn-primary"
										onClick={ () => setCurrentStep( 4 ) }
									>
										{ __( 'Continue →', 'dragwyb-click-to-chat' ) }
									</button>
								</footer>
							</div>
						) }

						{ /* ================= STEP 4: REVIEW YOUR SETUP ================= */ }
						{ currentStep === 4 && (
							<div className="dctc-review-screen">
								<div className="dctc-confetti-badge">
									{ Icons.confetti }
								</div>

								<div className="dctc-screen-header">
									<h2 className="dctc-screen-title">
										{ __( 'Review Your Setup', 'dragwyb-click-to-chat' ) }
									</h2>
									<p className="dctc-screen-desc">
										{ __( "Here's a summary of your configuration. You can change these settings anytime from your Dragwyb dashboard.", 'dragwyb-click-to-chat' ) }
									</p>
								</div>

								<div className="dctc-review-grid">
									{ /* Social Chat Review Card */ }
									<div className="dctc-review-card">
										<div className="dctc-review-card__header">
											<div className="dctc-review-card__left">
												<div className="dctc-setup-card__icon dctc-setup-card__icon--social">
													{ Icons.socialChat }
												</div>
												<span style={ { fontWeight: 700, fontSize: '14.5px', color: '#0f172a' } }>
													{ __( 'Social Chat', 'dragwyb-click-to-chat' ) }
												</span>
												<span className={ features.socialChat && socialChatSettings.enabled ? 'dctc-badge-enabled' : 'dctc-badge-disabled' }>
													● { features.socialChat && socialChatSettings.enabled ? __( 'Enabled', 'dragwyb-click-to-chat' ) : __( 'Disabled', 'dragwyb-click-to-chat' ) }
												</span>
											</div>
											<button
												type="button"
												className="dctc-review-edit-btn"
												onClick={ () => setCurrentStep( 3 ) }
											>
												✏ { __( 'Edit', 'dragwyb-click-to-chat' ) }
											</button>
										</div>

										<table className="dctc-review-card__table">
											<tbody>
												<tr>
													<td>WhatsApp</td>
													<td>{ socialChatSettings.whatsapp_enabled && socialChatSettings.whatsapp_number ? socialChatSettings.whatsapp_number : 'Not configured' }</td>
												</tr>
												<tr>
													<td>Email</td>
													<td>{ socialChatSettings.email_enabled && socialChatSettings.email_address ? socialChatSettings.email_address : 'Not configured' }</td>
												</tr>
												<tr>
													<td>Phone Call</td>
													<td>{ socialChatSettings.phone_enabled && socialChatSettings.phone_number ? socialChatSettings.phone_number : 'Not configured' }</td>
												</tr>
												<tr>
													<td>Custom Link</td>
													<td>{ socialChatSettings.custom_link_enabled && socialChatSettings.custom_link_url ? socialChatSettings.custom_link_url : 'Not configured' }</td>
												</tr>
											</tbody>
										</table>
									</div>

									{ /* AI Assistant Review Card */ }
									<div className="dctc-review-card">
										<div className="dctc-review-card__header">
											<div className="dctc-review-card__left">
												<div className="dctc-setup-card__icon dctc-setup-card__icon--ai">
													{ Icons.aiAssistant }
												</div>
												<span style={ { fontWeight: 700, fontSize: '14.5px', color: '#0f172a' } }>
													{ __( 'AI Assistant', 'dragwyb-click-to-chat' ) }
												</span>
												<span className={ features.aiAssistant && aiSettings.enabled ? 'dctc-badge-enabled' : 'dctc-badge-disabled' }>
													● { features.aiAssistant && aiSettings.enabled ? __( 'Enabled', 'dragwyb-click-to-chat' ) : __( 'Disabled', 'dragwyb-click-to-chat' ) }
												</span>
											</div>
											<button
												type="button"
												className="dctc-review-edit-btn"
												onClick={ () => setCurrentStep( 3 ) }
											>
												✏ { __( 'Edit', 'dragwyb-click-to-chat' ) }
											</button>
										</div>

										<table className="dctc-review-card__table">
											<tbody>
												<tr>
													<td>Provider</td>
													<td>{ aiSettings.provider.toUpperCase() }</td>
												</tr>
												<tr>
													<td>Model</td>
													<td>{ aiSettings.model }</td>
												</tr>
												<tr>
													<td>Assistant Name</td>
													<td>{ aiSettings.assistant_name }</td>
												</tr>
												<tr>
													<td>Welcome Message</td>
													<td>{ aiSettings.welcome_message }</td>
												</tr>
											</tbody>
										</table>
									</div>

									{ /* Support Center Review Card */ }
									<div className="dctc-review-card">
										<div className="dctc-review-card__header">
											<div className="dctc-review-card__left">
												<div className="dctc-setup-card__icon dctc-setup-card__icon--support">
													{ Icons.supportCenter }
												</div>
												<span style={ { fontWeight: 700, fontSize: '14.5px', color: '#0f172a' } }>
													{ __( 'Support Center', 'dragwyb-click-to-chat' ) }
												</span>
												<span className={ features.supportCenter && supportSettings.enabled ? 'dctc-badge-enabled' : 'dctc-badge-disabled' }>
													● { features.supportCenter && supportSettings.enabled ? __( 'Enabled', 'dragwyb-click-to-chat' ) : __( 'Disabled', 'dragwyb-click-to-chat' ) }
												</span>
											</div>
											<button
												type="button"
												className="dctc-review-edit-btn"
												onClick={ () => setCurrentStep( 3 ) }
											>
												✏ { __( 'Edit', 'dragwyb-click-to-chat' ) }
											</button>
										</div>

										<table className="dctc-review-card__table">
											<tbody>
												<tr>
													<td>Default Category</td>
													<td>{ supportSettings.default_category }</td>
												</tr>
												<tr>
													<td>Default Priority</td>
													<td>{ supportSettings.default_priority.charAt( 0 ).toUpperCase() + supportSettings.default_priority.slice( 1 ) }</td>
												</tr>
												<tr>
													<td>Support Email</td>
													<td>{ supportSettings.support_email }</td>
												</tr>
												<tr>
													<td>Customer Portal</td>
													<td>{ supportSettings.customer_portal ? 'Enabled' : 'Disabled' }</td>
												</tr>
												<tr>
													<td>Portal Page</td>
													<td>
														{ supportSettings.auto_create_portal_page ? (
															<span style={ { color: '#4f46e5', fontWeight: 700 } }>{ __( 'Auto-create [dctc_support_portal]', 'dragwyb-click-to-chat' ) }</span>
														) : (
															<span>{ __( 'Manual shortcode', 'dragwyb-click-to-chat' ) }</span>
														) }
													</td>
												</tr>
											</tbody>
										</table>
									</div>
								</div>

								{ /* Bottom Complete Setup CTA */ }
								<div className="dctc-review-complete-wrap">
									<button
										type="button"
										className="dctc-btn-complete"
										onClick={ handleCompleteSetup }
										disabled={ isSaving }
									>
										{ isSaving ? (
											<span>{ __( 'Saving Configuration...', 'dragwyb-click-to-chat' ) }</span>
										) : (
											<>
												<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" style={ { width: '20px', height: '20px', display: 'block' } }>
													<path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z" />
												</svg>
												<span>{ __( 'Complete Setup →', 'dragwyb-click-to-chat' ) }</span>
											</>
										) }
									</button>

									<div className="dctc-review-ready-text">
										<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" style={ { width: '16px', height: '16px', display: 'block' } }>
											<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" />
										</svg>
										<span>{ __( 'Your Dragwyb Click to Chat is almost ready to use!', 'dragwyb-click-to-chat' ) }</span>
									</div>
								</div>
							</div>
						) }
					</>
				) }
			</div>

			{ /* Optional Video Tour Modal */ }
			{ showVideoModal && (
				<div
					style={ {
						position: 'fixed',
						top: 0,
						left: 0,
						right: 0,
						bottom: 0,
						background: 'rgba(0,0,0,0.85)',
						zIndex: 1000000,
						display: 'flex',
						alignItems: 'center',
						justifyContent: 'center',
						padding: '20px',
					} }
					onClick={ () => setShowVideoModal( false ) }
				>
					<div
						style={ {
							background: '#fff',
							borderRadius: '16px',
							maxWidth: '720px',
							width: '100%',
							padding: '24px',
							textAlign: 'center',
						} }
						onClick={ ( e ) => e.stopPropagation() }
					>
						<h3 style={ { marginTop: 0, marginBottom: '12px', fontSize: '20px', fontWeight: 800 } }>
							{ __( 'Dragwyb Click to Chat — Quick Video Tour', 'dragwyb-click-to-chat' ) }
						</h3>
						<div style={ { padding: '40px 20px', background: '#f8fafc', borderRadius: '12px', marginBottom: '20px' } }>
							<div style={ { fontSize: '48px', marginBottom: '10px' } }>🎬</div>
							<p style={ { fontSize: '14px', color: '#64748b', margin: 0 } }>
								{ __( 'Learn how Social Chat, AI Assistant, and Support Center work harmoniously to automate customer communication in WordPress.', 'dragwyb-click-to-chat' ) }
							</p>
						</div>
						<button
							type="button"
							className="dctc-btn-primary"
							onClick={ () => setShowVideoModal( false ) }
						>
							{ __( 'Close Video Tour', 'dragwyb-click-to-chat' ) }
						</button>
					</div>
				</div>
			) }
		</div>
	);
}
