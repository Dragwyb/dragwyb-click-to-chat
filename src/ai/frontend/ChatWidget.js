import { createElement, useState, useEffect, useRef } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import Markdown from 'react-markdown';

/**
 * Auto-linkify bare URLs in markdown, leaving existing links/tags alone.
 *
 * @param {string} content Message content.
 * @return {string}
 */
function linkifyContent( content ) {
	if ( ! content ) {
		return '';
	}

	return content
		.split( /(\[[^\]]+\]\([^)]+\)|<[^>]+>)/g )
		.map( ( part ) => {
			if ( /^\[.+\]\(.+\)$/.test( part ) || /^<.+>$/.test( part ) ) {
				return part;
			}

			return part.replace( /(https?:\/\/[^\s\)<>"]+)/gi, ( url ) => {
				let clean = url;
				let trailing = '';

				if ( /[.,;:!]$/.test( clean ) ) {
					trailing = clean.slice( -1 );
					clean = clean.slice( 0, -1 );
				}

				return `[${ clean }](${ clean })${ trailing }`;
			} );
		} )
		.join( '' );
}

/**
 * Helper to render launcher icon based on preset or custom image.
 */
function renderLauncherIcon( preset, customUrl ) {
	if ( customUrl ) {
		return createElement( 'img', {
			className: 'dctc-ai-chat-launcher__icon',
			src: customUrl,
			alt: '',
		} );
	}

	switch ( preset ) {
		case 'bot':
			return createElement(
				'svg',
				{ width: '26', height: '26', viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', strokeWidth: '2', strokeLinecap: 'round', strokeLinejoin: 'round' },
				createElement( 'rect', { x: '3', y: '11', width: '18', height: '10', rx: '2' } ),
				createElement( 'circle', { cx: '12', cy: '5', r: '2' } ),
				createElement( 'path', { d: 'M12 7v4M8 16h.01M16 16h.01' } )
			);
		case 'sparkle':
			return createElement(
				'svg',
				{ width: '26', height: '26', viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', strokeWidth: '2', strokeLinecap: 'round', strokeLinejoin: 'round' },
				createElement( 'path', { d: 'm12 3-1.9 5.8a2 2 0 0 1-1.3 1.3L3 12l5.8 1.9a2 2 0 0 1 1.3 1.3L12 21l1.9-5.8a2 2 0 0 1 1.3-1.3L21 12l-5.8-1.9a2 2 0 0 1-1.3-1.3Z' } ),
				createElement( 'path', { d: 'M19 4v4M21 6h-4' } )
			);
		case 'support':
		case 'headset':
			return createElement(
				'svg',
				{ width: '26', height: '26', viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', strokeWidth: '2', strokeLinecap: 'round', strokeLinejoin: 'round' },
				createElement( 'path', { d: 'M3 18v-6a9 9 0 0 1 18 0v6' } ),
				createElement( 'path', { d: 'M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z' } )
			);
		case 'help':
			return createElement(
				'svg',
				{ width: '26', height: '26', viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', strokeWidth: '2', strokeLinecap: 'round', strokeLinejoin: 'round' },
				createElement( 'circle', { cx: '12', cy: '12', r: '10' } ),
				createElement( 'path', { d: 'M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3M12 17h.01' } )
			);
		case 'whatsapp':
			return createElement(
				'svg',
				{ width: '26', height: '26', viewBox: '0 0 24 24', fill: 'currentColor' },
				createElement( 'path', { d: 'M17.472 14.382c-.301-.15-1.782-.879-2.057-.98-.276-.1-.476-.15-.677.15-.2.301-.777.98-.953 1.18-.175.2-.351.226-.652.075-.301-.15-1.272-.469-2.423-1.496-.896-.799-1.501-1.786-1.677-2.087-.175-.301-.019-.464.132-.614.136-.135.301-.351.451-.527.15-.175.2-.301.301-.501.1-.2.05-.376-.025-.526-.075-.15-.677-1.633-.928-2.235-.244-.587-.493-.507-.677-.517-.175-.008-.376-.01-.577-.01-.201 0-.527.075-.803.376-.276.301-1.053 1.028-1.053 2.508 0 1.479 1.078 2.908 1.229 3.109.15.2 2.122 3.24 5.141 4.544.718.31 1.279.496 1.716.635.722.23 1.379.197 1.898.12.578-.087 1.782-.728 2.033-1.431.25-.703.25-1.305.175-1.431-.075-.126-.276-.201-.577-.351zm-5.467 7.518h-.005a10.84 10.84 0 0 1-5.526-1.509l-.396-.235-4.108 1.077 1.096-4.004-.258-.411a10.835 10.835 0 0 1-1.666-5.783c0-5.99 4.874-10.865 10.869-10.865a10.81 10.81 0 0 1 7.684 3.184 10.812 10.812 0 0 1 3.18 7.686c0 5.992-4.874 10.866-10.868 10.866z' } )
			);
		case 'chat':
		default:
			return createElement(
				'svg',
				{ width: '26', height: '26', viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', strokeWidth: '2', strokeLinecap: 'round', strokeLinejoin: 'round' },
				createElement( 'path', { d: 'M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z' } )
			);
	}
}

/**
 * Helper to render bot avatar based on preset or custom image.
 */
function renderBotAvatar( preset, customUrl, botName ) {
	if ( customUrl ) {
		return createElement( 'img', {
			src: customUrl,
			alt: botName || 'AI',
		} );
	}

	if ( preset === 'initial' ) {
		const initial = ( botName || 'B' ).charAt( 0 ).toUpperCase();
		return createElement( 'span', null, initial );
	}

	switch ( preset ) {
		case 'sparkle':
			return createElement(
				'svg',
				{ width: '18', height: '18', viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', strokeWidth: '2', strokeLinecap: 'round', strokeLinejoin: 'round' },
				createElement( 'path', { d: 'm12 3-1.9 5.8a2 2 0 0 1-1.3 1.3L3 12l5.8 1.9a2 2 0 0 1 1.3 1.3L12 21l1.9-5.8a2 2 0 0 1 1.3-1.3L21 12l-5.8-1.9a2 2 0 0 1-1.3-1.3Z' } ),
				createElement( 'path', { d: 'M19 4v4M21 6h-4' } )
			);
		case 'support':
			return createElement(
				'svg',
				{ width: '18', height: '18', viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', strokeWidth: '2', strokeLinecap: 'round', strokeLinejoin: 'round' },
				createElement( 'path', { d: 'M3 18v-6a9 9 0 0 1 18 0v6' } ),
				createElement( 'path', { d: 'M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z' } )
			);
		case 'chat':
			return createElement(
				'svg',
				{ width: '18', height: '18', viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', strokeWidth: '2', strokeLinecap: 'round', strokeLinejoin: 'round' },
				createElement( 'path', { d: 'M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z' } )
			);
		case 'bot':
		default:
			return createElement(
				'svg',
				{ width: '18', height: '18', viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', strokeWidth: '2', strokeLinecap: 'round', strokeLinejoin: 'round' },
				createElement( 'rect', { x: '3', y: '11', width: '18', height: '10', rx: '2' } ),
				createElement( 'circle', { cx: '12', cy: '5', r: '2' } ),
				createElement( 'path', { d: 'M12 7v4M8 16h.01M16 16h.01' } )
			);
	}
}

/**
 * Curated lightweight Unicode emoji categories.
 */
const EMOJI_CATEGORIES = [
	{
		id: 'smileys',
		label: __( 'Smileys & Emotion', 'dragwyb-click-to-chat' ),
		icon: '😀',
		emojis: [
			'😀', '😃', '😄', '😁', '😆', '😅', '😂', '🤣', '😊', '😇',
			'🙂', '😉', '😌', '😍', '🥰', '😘', '😋', '😛', '😜', '🤪',
			'😎', '🤓', '🧐', '🥳', '🤩', '🤖', '👻', '💀', '🤡', '👽',
			'🤔', '🤗', '🤐', '🤨', '😐', '😑', '😶', '😏', '😒', '🙄',
		],
	},
	{
		id: 'gestures',
		label: __( 'Hands & People', 'dragwyb-click-to-chat' ),
		icon: '👍',
		emojis: [
			'👍', '👎', '👊', '✌️', '🤞', '🤝', '👏', '🙌', '👐', '🤲',
			'🙏', '✍️', '👋', '🤙', '👂', '👀', '🧠', '👤', '👥', '🧑‍💻',
			'💪', '🖐️', '👌', '👈', '👉', '👆', '👇', '☝️', '✋', '🖖',
		],
	},
	{
		id: 'symbols',
		label: __( 'Symbols & Objects', 'dragwyb-click-to-chat' ),
		icon: '🔥',
		emojis: [
			'🔥', '⭐', '✨', '🌟', '💡', '⚡', '❤️', '🧡', '💛', '💚',
			'💙', '💜', '🖤', '💔', '💯', '🎯', '🚀', '💬', '🗨️', '📩',
			'📌', '📍', '📎', '📄', '📑', '🔍', '🔎', '⏳', '⏰', '🔒',
			'🔑', '🛡️', '✅', '❌', '⚠️', '❓', '❗', '🎁', '🏆', '🎉',
		],
	},
];

/**
 * Format bytes to readable string (e.g. 1.2 MB).
 */
function formatFileSize( bytes ) {
	if ( ! bytes || bytes === 0 ) {
		return '0 B';
	}
	const k = 1024;
	const sizes = [ 'B', 'KB', 'MB', 'GB' ];
	const i = Math.floor( Math.log( bytes ) / Math.log( k ) );
	return parseFloat( ( bytes / Math.pow( k, i ) ).toFixed( 1 ) ) + ' ' + sizes[ i ];
}

/**
 * Lightweight accessible Emoji Picker Component.
 */
function EmojiPicker( { onSelect, onClose } ) {
	const [ activeTab, setActiveTab ] = useState( 0 );
	const pickerRef = useRef( null );

	useEffect( () => {
		const handleKeyDown = ( e ) => {
			if ( e.key === 'Escape' ) {
				onClose();
			}
		};
		document.addEventListener( 'keydown', handleKeyDown );
		return () => {
			document.removeEventListener( 'keydown', handleKeyDown );
		};
	}, [ onClose ] );

	return createElement(
		'div',
		{
			ref: pickerRef,
			id: 'dctc-ai-emoji-picker',
			className: 'dctc-ai-emoji-picker',
			role: 'dialog',
			'aria-label': __( 'Emoji Picker', 'dragwyb-click-to-chat' ),
		},
		createElement(
			'div',
			{ className: 'dctc-ai-emoji-tabs' },
			EMOJI_CATEGORIES.map( ( cat, idx ) =>
				createElement(
					'button',
					{
						key: cat.id,
						type: 'button',
						className: `dctc-ai-emoji-tab ${ idx === activeTab ? 'active' : '' }`,
						onClick: () => setActiveTab( idx ),
						title: cat.label,
						'aria-label': cat.label,
					},
					cat.icon
				)
			)
		),
		createElement(
			'div',
			{ className: 'dctc-ai-emoji-grid' },
			EMOJI_CATEGORIES[ activeTab ].emojis.map( ( emoji, idx ) =>
				createElement(
					'button',
					{
						key: idx,
						type: 'button',
						className: 'dctc-ai-emoji-btn',
						onClick: () => onSelect( emoji ),
						'aria-label': `Emoji ${ emoji }`,
					},
					emoji
				)
			)
		)
	);
}

/**
 * Helper to render user avatar based on preset or custom image.
 */
function renderUserAvatar( preset, customUrl ) {
	if ( customUrl ) {
		return createElement( 'img', {
			src: customUrl,
			alt: 'User',
		} );
	}

	switch ( preset ) {
		case 'user-circle':
			return createElement(
				'svg',
				{ width: '18', height: '18', viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', strokeWidth: '2', strokeLinecap: 'round', strokeLinejoin: 'round' },
				createElement( 'circle', { cx: '12', cy: '12', r: '10' } ),
				createElement( 'circle', { cx: '12', cy: '10', r: '3' } ),
				createElement( 'path', { d: 'M7 20.662V19a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v1.662' } )
			);
		case 'business':
			return createElement(
				'svg',
				{ width: '18', height: '18', viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', strokeWidth: '2', strokeLinecap: 'round', strokeLinejoin: 'round' },
				createElement( 'path', { d: 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2' } ),
				createElement( 'circle', { cx: '9', cy: '7', r: '4' } ),
				createElement( 'path', { d: 'M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75' } )
			);
		case 'smile':
			return createElement(
				'svg',
				{ width: '18', height: '18', viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', strokeWidth: '2', strokeLinecap: 'round', strokeLinejoin: 'round' },
				createElement( 'circle', { cx: '12', cy: '12', r: '10' } ),
				createElement( 'path', { d: 'M8 14s1.5 2 4 2 4-2 4-2M9 9h.01M15 9h.01' } )
			);
		case 'star':
			return createElement(
				'svg',
				{ width: '18', height: '18', viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', strokeWidth: '2', strokeLinecap: 'round', strokeLinejoin: 'round' },
				createElement( 'polygon', { points: '12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2' } )
			);
		case 'user':
		default:
			return createElement(
				'svg',
				{ width: '18', height: '18', viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', strokeWidth: '2', strokeLinecap: 'round', strokeLinejoin: 'round' },
				createElement( 'path', { d: 'M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2' } ),
				createElement( 'circle', { cx: '12', cy: '7', r: '4' } )
			);
	}
}

/**
 * Frontend AI chat widget (floating or inline).
 *
 * @param {Object}  props
 * @param {Object}  props.settings Full AI settings from window.dctc_ai_frontend_data.
 * @param {boolean} props.inline   Whether mounted as shortcode/inline variant.
 * @return {JSX.Element}
 */
export default function ChatWidget( { settings, inline } ) {
	const chatbot = settings?.chatbot || {};
	const display = settings?.display || {};
	const hasApiKey = settings?.has_api_key !== false;
	const isAdmin = !! settings?.is_admin;
	const settingsUrl = settings?.settings_url || '';

	const getErrorMessage = () => {
		const template =
			chatbot.api_error_msg ||
			'There is some error on server, please contact our [support agent]({support_url}).';
		const supportUrl = chatbot.support_url || '#';
		return template.replace( '{support_url}', supportUrl );
	};

	const suggestedQuestions = chatbot.enable_pre_questions
		? [
				chatbot.pre_question_1,
				chatbot.pre_question_2,
				chatbot.pre_question_3,
				chatbot.pre_question_4,
		  ].filter( ( q ) => q && q.trim() )
		: [];

	const actionButtons = Array.isArray( chatbot.action_buttons )
		? chatbot.action_buttons.filter( ( b ) => b && b.label && b.label.trim() )
		: [];

	const showBotAvatarInChat = !! chatbot.show_bot_avatar_in_chat;
	const showUserAvatarInChat = !! chatbot.show_user_avatar_in_chat;
	const showSources = chatbot.show_sources !== false;
	const enableUploads = !! chatbot.enable_uploads;
	const maxUploadSizeMb = parseInt( chatbot.max_upload_size, 10 ) || 5;
	const maxFilesPerMsg = parseInt( chatbot.max_files_per_message, 10 ) || 3;
	const allowedExts = (
		chatbot.allowed_file_types ||
		'jpg, jpeg, png, webp, gif, pdf, txt, doc, docx'
	)
		.toLowerCase()
		.split( ',' )
		.map( ( s ) => s.trim().replace( /^\./, '' ) )
		.filter( Boolean );
	const excludedExts = (
		chatbot.excluded_file_types ||
		'php, php3, php4, php5, phtml, phar, cgi, pl, py, sh, exe, bat, cmd, js, html, htm, svg'
	)
		.toLowerCase()
		.split( ',' )
		.map( ( s ) => s.trim().replace( /^\./, '' ) )
		.filter( Boolean );

	const [ isOpen, setIsOpen ] = useState( false );
	const [ launcherVisible, setLauncherVisible ] = useState( () => {
		const delay = parseInt( display.time_delay, 10 ) || 0;
		return delay <= 0;
	} );
	const [ input, setInput ] = useState( '' );
	const [ isLoading, setIsLoading ] = useState( false );
	const [ attachments, setAttachments ] = useState( [] );
	const [ isEmojiOpen, setIsEmojiOpen ] = useState( false );
	const [ isAttachMenuOpen, setIsAttachMenuOpen ] = useState( false );
	const [ uploadError, setUploadError ] = useState( '' );
	const [ sessionId, setSessionId ] = useState(
		() =>
			window.dctc_ai_frontend_data?.session_id ||
			'sess_' + Math.random().toString( 36 ).substr( 2, 9 )
	);
	const [ clearAllowed, setClearAllowed ] = useState( () => {
		const value = window.dctc_ai_frontend_data?.clear_allowed;
		return value === null || value === undefined || value;
	} );
	const [ messages, setMessages ] = useState( [] );
	const [ email, setEmail ] = useState( '' );
	const [ emailDraft, setEmailDraft ] = useState( '' );
	const [ emailError, setEmailError ] = useState( '' );
	const [ pendingPrompt, setPendingPrompt ] = useState( '' );

	const messagesEndRef = useRef( null );
	const inputRef = useRef( null );
	const fileInputRef = useRef( null );
	const imageFileInputRef = useRef( null );
	const attachMenuRef = useRef( null );
	const emojiWrapRef = useRef( null );
	const wrapperRef = useRef( null );
	const isMountedRef = useRef( true );
	const attachmentsRef = useRef( attachments );
	attachmentsRef.current = attachments;

	const isAnyUploading = attachments.some( ( a ) => a.status === 'uploading' );

	useEffect( () => {
		isMountedRef.current = true;
		return () => {
			isMountedRef.current = false;
			attachmentsRef.current.forEach( ( a ) => {
				if ( a.previewUrl ) {
					URL.revokeObjectURL( a.previewUrl );
				}
			} );
		};
	}, [] );

	// Click outside to close attachment menu
	useEffect( () => {
		const handleAttachMenuOutside = ( e ) => {
			if (
				attachMenuRef.current &&
				! attachMenuRef.current.contains( e.target )
			) {
				setIsAttachMenuOpen( false );
			}
		};

		if ( isAttachMenuOpen ) {
			document.addEventListener( 'mousedown', handleAttachMenuOutside );
			document.addEventListener( 'touchstart', handleAttachMenuOutside );
		}

		return () => {
			document.removeEventListener( 'mousedown', handleAttachMenuOutside );
			document.removeEventListener( 'touchstart', handleAttachMenuOutside );
		};
	}, [ isAttachMenuOpen ] );

	// Click outside to close emoji picker
	useEffect( () => {
		const handleEmojiOutside = ( e ) => {
			if (
				emojiWrapRef.current &&
				! emojiWrapRef.current.contains( e.target )
			) {
				setIsEmojiOpen( false );
			}
		};

		if ( isEmojiOpen ) {
			document.addEventListener( 'mousedown', handleEmojiOutside );
			document.addEventListener( 'touchstart', handleEmojiOutside );
		}

		return () => {
			document.removeEventListener( 'mousedown', handleEmojiOutside );
			document.removeEventListener( 'touchstart', handleEmojiOutside );
		};
	}, [ isEmojiOpen ] );

	// Click / tap outside closes floating chat.
	useEffect( () => {
		const handleOutside = ( event ) => {
			if (
				wrapperRef.current &&
				! wrapperRef.current.contains( event.target )
			) {
				setIsOpen( false );
			}
		};

		if ( isOpen && ! inline ) {
			document.addEventListener( 'mousedown', handleOutside );
			document.addEventListener( 'touchstart', handleOutside );
		}

		return () => {
			document.removeEventListener( 'mousedown', handleOutside );
			document.removeEventListener( 'touchstart', handleOutside );
		};
	}, [ isOpen, inline ] );

	// Auto-open after delay when configured for sitewide floating widget.
	useEffect( () => {
		if (
			display.trigger_type === 'delay' &&
			display.entire_site &&
			! inline
		) {
			const ms = 1000 * ( parseInt( display.trigger_delay, 10 ) || 3 );
			const timer = setTimeout( () => {
				setIsOpen( true );
			}, ms );
			return () => clearTimeout( timer );
		}
	}, [
		display.trigger_type,
		display.entire_site,
		display.trigger_delay,
		inline,
	] );

	// Delay launcher appearance after page load.
	useEffect( () => {
		if ( inline ) {
			setLauncherVisible( true );
			return;
		}
		const delay = parseInt( display.time_delay, 10 ) || 0;
		if ( delay <= 0 ) {
			setLauncherVisible( true );
			return;
		}
		setLauncherVisible( false );
		const timer = setTimeout( () => {
			setLauncherVisible( true );
		}, delay * 1000 );
		return () => clearTimeout( timer );
	}, [ display.time_delay, inline ] );

	useEffect( () => {
		if ( messagesEndRef.current ) {
			messagesEndRef.current.scrollIntoView( { behavior: 'smooth' } );
		}
	}, [ messages, isLoading, attachments ] );

	const toggleOpen = () => setIsOpen( ( open ) => ! open );

	const needsEmail = !! chatbot.save_chat && !! chatbot.ask_email;

	const handleFilesSelected = ( e ) => {
		const rawFiles = Array.from( e.target.files || [] );
		if ( fileInputRef.current ) {
			fileInputRef.current.value = '';
		}
		if ( imageFileInputRef.current ) {
			imageFileInputRef.current.value = '';
		}
		if ( rawFiles.length === 0 ) {
			return;
		}

		setUploadError( '' );

		if ( attachments.length + rawFiles.length > maxFilesPerMsg ) {
			setUploadError(
				sprintf(
					__(
						'You can upload a maximum of %d files per message.',
						'dragwyb-click-to-chat'
					),
					maxFilesPerMsg
				)
			);
			return;
		}

		const maxBytes = maxUploadSizeMb * 1024 * 1024;
		const newAttachments = [];

		for ( const file of rawFiles ) {
			if ( file.size > maxBytes ) {
				setUploadError(
					sprintf(
						__(
							'File "%s" exceeds the %d MB size limit.',
							'dragwyb-click-to-chat'
						),
						file.name,
						maxUploadSizeMb
					)
				);
				continue;
			}

			const ext = ( file.name.split( '.' ).pop() || '' ).toLowerCase();
			if ( excludedExts.includes( ext ) ) {
				setUploadError(
					sprintf(
						__(
							'.%s files are not permitted for security reasons.',
							'dragwyb-click-to-chat'
						),
						ext
					)
				);
				continue;
			}

			if ( allowedExts.length > 0 && ! allowedExts.includes( ext ) ) {
				setUploadError(
					sprintf(
						__(
							'.%s is not an allowed file type.',
							'dragwyb-click-to-chat'
						),
						ext
					)
				);
				continue;
			}

			const id = 'att_' + Math.random().toString( 36 ).substr( 2, 9 );
			const isImg =
				file.type.startsWith( 'image/' ) ||
				[ 'jpg', 'jpeg', 'png', 'webp', 'gif' ].includes( ext );
			const previewUrl = isImg ? URL.createObjectURL( file ) : '';

			const attObj = {
				id,
				file,
				name: file.name,
				size: file.size,
				type: isImg ? 'image' : 'file',
				previewUrl,
				status: 'uploading',
				url: '',
				attachmentId: null,
				mime: file.type,
				error: null,
			};

			newAttachments.push( attObj );

			// Trigger upload
			const formData = new FormData();
			formData.append( 'file', file );

			apiFetch( {
				path: '/dctc-ai/v1/upload',
				method: 'POST',
				body: formData,
			} )
				.then( ( res ) => {
					if ( ! isMountedRef.current ) {
						return;
					}
					const data = res?.attachment || res;
					setAttachments( ( prev ) =>
						prev.map( ( a ) =>
							a.id === id
								? {
										...a,
										status: 'uploaded',
										url: data?.url || '',
										attachmentId:
											data?.attachmentId || data?.id || null,
										mime: data?.mime || file.type,
								  }
								: a
						)
					);
				} )
				.catch( ( err ) => {
					if ( ! isMountedRef.current ) {
						return;
					}
					setAttachments( ( prev ) =>
						prev.map( ( a ) =>
							a.id === id
								? {
										...a,
										status: 'failed',
										error:
											err?.message ||
											__( 'Upload failed', 'dragwyb-click-to-chat' ),
								  }
								: a
						)
					);
				} );
		}

		if ( newAttachments.length > 0 ) {
			setAttachments( ( prev ) => [ ...prev, ...newAttachments ] );
		}
	};

	const removeAttachment = ( id ) => {
		setAttachments( ( prev ) => {
			const item = prev.find( ( a ) => a.id === id );
			if ( item && item.previewUrl ) {
				URL.revokeObjectURL( item.previewUrl );
			}
			return prev.filter( ( a ) => a.id !== id );
		} );
	};

	const handleSelectEmoji = ( emoji ) => {
		const textarea = inputRef.current;
		if ( ! textarea ) {
			setInput( ( prev ) => prev + emoji );
			setIsEmojiOpen( false );
			return;
		}

		const start = textarea.selectionStart ?? input.length;
		const end = textarea.selectionEnd ?? input.length;
		const nextValue =
			input.substring( 0, start ) + emoji + input.substring( end );

		setInput( nextValue );
		setIsEmojiOpen( false );

		setTimeout( () => {
			if ( textarea ) {
				textarea.focus();
				const newPos = start + emoji.length;
				textarea.setSelectionRange( newPos, newPos );
				textarea.style.height = 'auto';
				textarea.style.height =
					Math.min( textarea.scrollHeight, 150 ) + 'px';
			}
		}, 10 );
	};

	const handleSend = async ( event, promptOverride = null ) => {
		if ( event && event.preventDefault ) {
			event.preventDefault();
		}
		if ( isLoading ) {
			return;
		}

		if ( isAnyUploading ) {
			setUploadError(
				__(
					'Please wait for attachments to finish uploading.',
					'dragwyb-click-to-chat'
				)
			);
			return;
		}

		let prompt = ( promptOverride !== null ? promptOverride : input ).trim();
		const readyAttachments = attachments
			.filter( ( a ) => a.status === 'uploaded' )
			.map( ( a ) => ( {
				id: a.id,
				type: a.type,
				name: a.name,
				size: a.size,
				mime: a.mime,
				url: a.url,
				attachmentId: a.attachmentId,
			} ) );

		if ( ! prompt && readyAttachments.length === 0 ) {
			return;
		}

		// Email gate: stash the first prompt until email is collected.
		if ( needsEmail && ! email ) {
			setMessages( [
				{
					role: 'user',
					content:
						prompt ||
						__( '[Attached Files]', 'dragwyb-click-to-chat' ),
					attachments: readyAttachments,
				},
			] );
			setPendingPrompt( prompt );
			setInput( '' );
			attachments.forEach( ( a ) => {
				if ( a.previewUrl ) {
					URL.revokeObjectURL( a.previewUrl );
				}
			} );
			setAttachments( [] );
			setUploadError( '' );
			setIsEmojiOpen( false );
			return;
		}

		// API key check
		if ( ! hasApiKey ) {
			const warningMsg = isAdmin
				? `⚠️ **AI Provider API Key Not Configured**: Please [configure your AI Provider API key](${ settingsUrl }) in settings to enable chatbot responses.`
				: __(
						'AI Assistant is currently offline for maintenance. Please check back later.',
						'dragwyb-click-to-chat'
				  );
			setMessages( ( prev ) => [
				...prev,
				{
					role: 'user',
					content: prompt,
					attachments: readyAttachments,
				},
				{ role: 'error', content: warningMsg },
			] );
			if ( promptOverride === null ) {
				setInput( '' );
			}
			attachments.forEach( ( a ) => {
				if ( a.previewUrl ) {
					URL.revokeObjectURL( a.previewUrl );
				}
			} );
			setAttachments( [] );
			setUploadError( '' );
			setIsEmojiOpen( false );
			return;
		}

		const userMessageObj = {
			role: 'user',
			content: prompt,
			attachments: readyAttachments,
		};

		setMessages( ( prev ) => [ ...prev, userMessageObj ] );

		// Clean up object URLs and reset composer attachment tray
		attachments.forEach( ( a ) => {
			if ( a.previewUrl ) {
				URL.revokeObjectURL( a.previewUrl );
			}
		} );
		setAttachments( [] );
		setUploadError( '' );
		setIsEmojiOpen( false );

		if ( fileInputRef.current ) {
			fileInputRef.current.value = '';
		}
		if ( imageFileInputRef.current ) {
			imageFileInputRef.current.value = '';
		}

		if ( promptOverride === null ) {
			setInput( '' );
		}
		setIsLoading( true );

		if ( inputRef.current ) {
			inputRef.current.style.height = 'auto';
		}

		try {
			const response = await apiFetch( {
				path: '/dctc-ai/v1/chat',
				method: 'POST',
				data: {
					prompt: prompt || ( readyAttachments.length > 0 ? 'Please analyze the attached file(s).' : '' ),
					session_id: sessionId,
					email,
					attachments: readyAttachments,
				},
			} );

			if ( ! isMountedRef.current ) {
				return;
			}

			if ( response.success ) {
				if (
					response.session_id &&
					response.session_id !== sessionId
				) {
					setSessionId( response.session_id );
				}

				let botMessage = response.message;
				if (
					typeof botMessage === 'object' &&
					botMessage !== null
				) {
					botMessage =
						botMessage.text ||
						botMessage.content ||
						JSON.stringify( botMessage );
				}
				const responseSources = Array.isArray( response.sources )
					? response.sources
					: ( Array.isArray( response.reference_links ) ? response.reference_links : [] );
				const responseActionButtons = Array.isArray( response.action_buttons )
					? response.action_buttons
					: [];

				setMessages( ( prev ) => [
					...prev,
					{
						role: 'bot',
						content: botMessage,
						sources: responseSources,
						action_buttons: responseActionButtons,
					},
				] );
			} else {
				setMessages( ( prev ) => [
					...prev,
					{ role: 'error', content: response.message || getErrorMessage() },
				] );
			}
		} catch ( err ) {
			if ( isMountedRef.current ) {
				const errMsg =
					( err && ( err.message || err.error ) ) ||
					getErrorMessage();
				setMessages( ( prev ) => [
					...prev,
					{ role: 'error', content: errMsg },
				] );
			}
		} finally {
			if ( isMountedRef.current ) {
				setIsLoading( false );
			}
		}
	};

	const handleClear = async () => {
		try {
			const response = await apiFetch( {
				path: '/dctc-ai/v1/clear-session',
				method: 'POST',
			} );

			if ( response.success && response.session_id ) {
				setMessages( [] );
				setSessionId( response.session_id );
				setEmail( '' );
				setEmailDraft( '' );
				setPendingPrompt( '' );
				setClearAllowed( true );
			}
		} catch ( err ) {
			setMessages( [] );
			setEmail( '' );
			setEmailDraft( '' );
			setPendingPrompt( '' );
			const newId =
				'sess_' + Math.random().toString( 36 ).substr( 2, 9 );
			setSessionId( newId );
			setClearAllowed( true );
		}
	};

	const handleEmailSubmit = async ( event ) => {
		if ( event && event.preventDefault ) {
			event.preventDefault();
		}

		const value = emailDraft.trim();
		if ( ! value ) {
			setEmailError(
				__( 'Please enter your email.', 'dragwyb-click-to-chat' )
			);
			return;
		}

		const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
		if ( ! emailRegex.test( value ) ) {
			setEmailError(
				__(
					'Please enter a valid email address.',
					'dragwyb-click-to-chat'
				)
			);
			return;
		}

		setEmail( value );
		setEmailError( '' );

		if ( pendingPrompt ) {
			const promptToSend = pendingPrompt;
			setPendingPrompt( '' );
			handleSend( null, promptToSend );
		}
	};

	const primaryColor = chatbot.primary_color || '#6366f1';
	const botAvatar = chatbot.bot_avatar || '';
	const botPreset = chatbot.bot_icon_preset || 'bot';
	const userAvatar = chatbot.user_avatar || '';
	const userPreset = chatbot.user_icon_preset || 'user';
	const botName = chatbot.bot_name || __( 'AI Assistant', 'dragwyb-click-to-chat' );
	const bubbleStyle = chatbot.bubble_style || 'rounded';
	const bubbleClass = `dctc-ai-bubble-${ bubbleStyle }`;
	const assistantIcon = display.assistant_icon || '';
	const launcherPreset = display.launcher_icon_preset || 'chat';
	const showEmailGate = needsEmail && ! email && messages.length > 0;
	const showWindow = isOpen || inline;
	const launcherSize = `${ display.widget_size || 64 }${
		display.widget_size_unit || 'px'
	}`;
	const isCustomPosition = ! inline && display.position === 'custom';
	const customSide = display.custom_side === 'left' ? 'left' : 'right';
	const customVert =
		display.custom_vertical_align === 'top' ? 'top' : 'bottom';
	const floatingClass = inline
		? 'dctc-ai-chat-inline'
		: isCustomPosition
		? `dctc-ai-chat-floating custom custom-${ customSide } custom-${ customVert }`
		: `dctc-ai-chat-floating ${ display.position || 'bottom-right' }`;
	const wrapperStyle = {
		'--dctc-ai-primary': primaryColor,
		'--dctc-ai-launcher-size': launcherSize,
	};
	if ( isCustomPosition ) {
		const vertDist = `${ display.custom_vertical ?? 24 }${
			display.custom_vertical_unit || 'px'
		}`;
		const horizDist = `${ display.custom_horizontal ?? 24 }${
			display.custom_horizontal_unit || 'px'
		}`;
		wrapperStyle.top = customVert === 'top' ? vertDist : 'auto';
		wrapperStyle.bottom = customVert === 'bottom' ? vertDist : 'auto';
		wrapperStyle.left = customSide === 'left' ? horizDist : 'auto';
		wrapperStyle.right = customSide === 'right' ? horizDist : 'auto';
	}

	const markdownComponents = {
		a: ( { node, ...props } ) =>
			createElement( 'a', {
				...props,
				target: '_blank',
				rel: 'noopener noreferrer',
			} ),
		p: ( { node, ...props } ) => createElement( 'p', props ),
		h3: ( { node, ...props } ) => createElement( 'h3', props ),
		strong: ( { node, ...props } ) => createElement( 'strong', props ),
		em: ( { node, ...props } ) => createElement( 'em', props ),
		ul: ( { node, ...props } ) => createElement( 'ul', props ),
		li: ( { node, ...props } ) => createElement( 'li', props ),
		hr: ( { node, ...props } ) => createElement( 'hr', props ),
	};

	return createElement(
		'div',
		{
			ref: wrapperRef,
			className: 'dctc-ai-chat-wrapper ' + floatingClass,
			style: wrapperStyle,
		},
		showWindow &&
			createElement(
				'div',
				{
					id: 'dctc-ai-chat-window',
					className: `dctc-ai-chat-window ${ bubbleClass }`,
					style: { display: 'flex' },
				},
				// Header
				createElement(
					'div',
					{ className: 'dctc-ai-chat-header' },
					createElement(
						'div',
						{ className: 'dctc-ai-chat-avatar' },
						renderBotAvatar( botPreset, botAvatar, botName )
					),
					createElement(
						'div',
						{ className: 'dctc-ai-chat-info' },
						createElement(
							'strong',
							null,
							botName
						),
						createElement(
							'span',
							{ className: 'dctc-ai-status-indicator' },
							createElement( 'span', { className: 'dctc-ai-status-dot' } ),
							__( 'Online', 'dragwyb-click-to-chat' )
						)
					),
					clearAllowed &&
						messages.length > 0 &&
						createElement(
							'button',
							{
								className: 'dctc-ai-chat-clear',
								onClick: handleClear,
								title: __( 'Clear Conversation', 'dragwyb-click-to-chat' ),
								'aria-label': __( 'Clear Conversation', 'dragwyb-click-to-chat' ),
							},
							createElement( 'span', {
								className: 'dashicons dashicons-trash',
								'aria-hidden': 'true',
							} )
						),
					! inline &&
						createElement(
							'button',
							{
								className: 'dctc-ai-chat-close',
								onClick: toggleOpen,
								title: __( 'Close', 'dragwyb-click-to-chat' ),
								'aria-label': __( 'Close chat window', 'dragwyb-click-to-chat' ),
							},
							createElement( 'span', {
								className: 'dashicons dashicons-no-alt',
								'aria-hidden': 'true',
							} )
						)
				),

				// Messages Body
				createElement(
					'div',
					{
						className: 'dctc-ai-chat-messages',
						id: 'dctc-ai-messages',
					},
					// Default Greeting
					chatbot.greeting_msg &&
						messages.length === 0 &&
						suggestedQuestions.length === 0 &&
						createElement(
							'div',
							{
								className: 'dctc-ai-message-row dctc-ai-message-row--bot',
							},
							showBotAvatarInChat &&
								createElement(
									'div',
									{ className: 'dctc-ai-msg-avatar dctc-ai-msg-avatar--bot' },
									renderBotAvatar( botPreset, botAvatar, botName )
								),
							createElement(
								'div',
								{ className: 'dctc-ai-message dctc-ai-message-bot' },
								createElement(
									'div',
									{ className: 'dctc-ai-message-content' },
									createElement(
										Markdown,
										{ components: markdownComponents },
										! hasApiKey && isAdmin
											? `👋 ${ chatbot.greeting_msg }\n\n⚠️ **Action Required**: Please [configure your AI Provider API key](${ settingsUrl }) in AI Assistant settings to enable responses.`
											: linkifyContent( chatbot.greeting_msg )
									)
								)
							)
						),

					// Suggested Questions
					messages.length === 0 &&
						suggestedQuestions.length > 0 &&
						createElement(
							'div',
							{ className: 'dctc-ai-suggested-questions' },
							suggestedQuestions.map( ( question, index ) =>
								createElement(
									'button',
									{
										key: index,
										type: 'button',
										className: `dctc-ai-suggested-question-btn dctc-ai-suggested-question-btn--${
											chatbot.pre_questions_border_radius || 'rounded'
										}`,
										style: {
											backgroundColor: chatbot.pre_questions_bg_color || '#ffffff',
											color: chatbot.pre_questions_text_color || '#475569',
											borderColor: chatbot.pre_questions_border_color || '#e2e8f0',
										},
										onClick: ( e ) => handleSend( e, question ),
									},
									question
								)
							)
						),

					// Custom Quick Action Buttons (Contact Us, Products, Custom Links)
					actionButtons.length > 0 &&
						createElement(
							'div',
							{ className: 'dctc-ai-action-buttons-bar' },
							actionButtons.map( ( btn, index ) =>
								createElement(
									'a',
									{
										key: btn.id || index,
										href: btn.url || '#',
										target: btn.target || '_blank',
										rel: 'noopener noreferrer',
										className: 'dctc-ai-action-btn',
										style: {
											borderColor: primaryColor + '40',
										},
									},
									btn.label
								)
							)
						),

					// Message Stream
					messages.map( ( message, index ) => {
						const isBot = message.role === 'bot' || message.role === 'error';
						const roleClass =
							message.role === 'bot'
								? 'dctc-ai-message-bot'
								: message.role === 'error'
								? 'dctc-ai-message-error'
								: 'dctc-ai-message-user';

						return createElement(
							'div',
							{
								key: index,
								className: `dctc-ai-message-row ${ isBot ? 'dctc-ai-message-row--bot' : 'dctc-ai-message-row--user' }`,
							},
							isBot && showBotAvatarInChat &&
								createElement(
									'div',
									{ className: 'dctc-ai-msg-avatar dctc-ai-msg-avatar--bot' },
									renderBotAvatar( botPreset, botAvatar, botName )
								),
							createElement(
								'div',
								{
									className: `dctc-ai-message-body ${
										isBot
											? 'dctc-ai-message-body--bot'
											: 'dctc-ai-message-body--user'
									}`,
								},
								// 1. Attachments rendered as clean standalone cards above text
								message.attachments &&
									message.attachments.length > 0 &&
									createElement(
										'div',
										{ className: 'dctc-ai-msg-attachments' },
										message.attachments.map( ( att, attIdx ) =>
											att.type === 'image'
												? createElement(
														'a',
														{
															key: att.id || attIdx,
															href: att.url || '#',
															target: '_blank',
															rel: 'noopener noreferrer',
															className: 'dctc-ai-msg-att-img-link',
															title: att.name,
														},
														createElement( 'img', {
															src: att.url,
															alt:
																att.name ||
																__(
																	'Attachment',
																	'dragwyb-click-to-chat'
																),
															className: 'dctc-ai-msg-att-img',
															loading: 'lazy',
														} )
												  )
												: createElement(
														'a',
														{
															key: att.id || attIdx,
															href: att.url || '#',
															target: '_blank',
															rel: 'noopener noreferrer',
															className: 'dctc-ai-msg-att-file',
															title: att.name,
														},
														createElement(
															'span',
															{
																className:
																	'dctc-ai-msg-att-file-icon',
															},
															'📄'
														),
														createElement(
															'div',
															{
																className:
																	'dctc-ai-msg-att-file-meta',
															},
															createElement(
																'span',
																{
																	className:
																		'dctc-ai-msg-att-file-name',
																},
																att.name
															),
															att.size > 0 &&
																createElement(
																	'span',
																	{
																		className:
																			'dctc-ai-msg-att-file-size',
																	},
																	formatFileSize(
																		att.size
																	)
																)
														),
														createElement( 'span', {
															className:
																'dashicons dashicons-download',
															'aria-hidden': 'true',
														} )
												  )
										)
									),
								// 2. Text message bubble rendered directly below the attachments
								isBot
									? createElement(
											'div',
											{
												className:
													'dctc-ai-message ' +
													roleClass,
											},
											createElement(
												'div',
												{
													className:
														'dctc-ai-message-content',
												},
												createElement(
													Markdown,
													{
														components:
															markdownComponents,
													},
													linkifyContent(
														message.content
													)
												)
											),
											showSources &&
												message.sources &&
												message.sources.length > 0 &&
												createElement(
													'div',
													{ className: 'dctc-ai-sources' },
													createElement(
														'span',
														{ className: 'dctc-ai-sources__label' },
														__( 'Sources:', 'dragwyb-click-to-chat' )
													),
													createElement(
														'div',
														{ className: 'dctc-ai-sources__list' },
														message.sources.slice( 0, 3 ).map( ( src, srcIdx ) =>
															createElement(
																'a',
																{
																	key: srcIdx,
																	href: src.url || '#',
																	target: '_blank',
																	rel: 'noopener noreferrer',
																	className: 'dctc-ai-source-chip',
																	title: src.title,
																},
																createElement(
																	'svg',
																	{
																		width: '12',
																		height: '12',
																		viewBox: '0 0 24 24',
																		fill: 'none',
																		stroke: 'currentColor',
																		strokeWidth: '2',
																		strokeLinecap: 'round',
																		strokeLinejoin: 'round',
																		className: 'dctc-ai-source-chip__icon',
																		'aria-hidden': 'true',
																	},
																	createElement( 'path', {
																		d: 'M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6',
																	} ),
																	createElement( 'polyline', {
																		points: '15 3 21 3 21 9',
																	} ),
																	createElement( 'line', {
																		x1: '10',
																		y1: '14',
																		x2: '21',
																		y2: '3',
																	} )
																),
																createElement(
																	'span',
																	{ className: 'dctc-ai-source-chip__title' },
																	src.title || src.url
																)
															)
														)
													)
												),
											message.action_buttons &&
												message.action_buttons.length > 0 &&
												createElement(
													'div',
													{ className: 'dctc-ai-msg-actions' },
													message.action_buttons.map( ( btn, bIdx ) =>
														createElement(
															'a',
															{
																key: bIdx,
																href: btn.url,
																target: btn.target || '_blank',
																rel: 'noopener noreferrer',
																className:
																	'dctc-ai-msg-action-btn' +
																	( btn.type === 'whatsapp'
																		? ' is-whatsapp'
																		: '' ),
															},
															btn.type === 'whatsapp' &&
																createElement(
																	'svg',
																	{
																		width: '13',
																		height: '13',
																		viewBox: '0 0 24 24',
																		fill: 'currentColor',
																		style: { marginRight: '4px' },
																	},
																	createElement( 'path', {
																		d: 'M17.472 14.382c-.301-.15-1.782-.879-2.057-.98-.276-.1-.476-.15-.677.15-.2.301-.777.98-.953 1.18-.175.2-.351.226-.652.075-.301-.15-1.272-.469-2.423-1.496-.896-.799-1.501-1.786-1.677-2.087-.175-.301-.019-.464.132-.614.136-.135.301-.351.451-.527.15-.175.2-.301.301-.501.1-.2.05-.376-.025-.526-.075-.15-.677-1.633-.928-2.235-.244-.587-.493-.507-.677-.517-.175-.008-.376-.01-.577-.01-.201 0-.527.075-.803.376-.276.301-1.053 1.028-1.053 2.508 0 1.479 1.078 2.908 1.229 3.109.15.2 2.122 3.24 5.141 4.544.718.31 1.279.496 1.716.635.722.23 1.379.197 1.898.12.578-.087 1.782-.728 2.033-1.431.25-.703.25-1.305.175-1.431-.075-.126-.276-.201-.577-.351zm-5.467 7.518h-.005a10.84 10.84 0 0 1-5.526-1.509l-.396-.235-4.108 1.077 1.096-4.004-.258-.411a10.835 10.835 0 0 1-1.666-5.783c0-5.99 4.874-10.865 10.869-10.865a10.81 10.81 0 0 1 7.684 3.184 10.812 10.812 0 0 1 3.18 7.686c0 5.992-4.874 10.866-10.868 10.866z',
																	} )
																),
															createElement( 'span', null, btn.label )
														)
													)
												)
									  )
									: message.content &&
									  message.content.trim()
									? createElement(
											'div',
											{
												className:
													'dctc-ai-message ' +
													roleClass,
											},
											createElement(
												'div',
												{
													className:
														'dctc-ai-message-text',
												},
												message.content
											)
									  )
									: null
							),
							! isBot && showUserAvatarInChat &&
								createElement(
									'div',
									{ className: 'dctc-ai-msg-avatar dctc-ai-msg-avatar--user' },
									renderUserAvatar( userPreset, userAvatar )
								)
						);
					} ),

					// Typing Indicator
					isLoading &&
						createElement(
							'div',
							{ className: 'dctc-ai-message-row dctc-ai-message-row--bot' },
							showBotAvatarInChat &&
								createElement(
									'div',
									{ className: 'dctc-ai-msg-avatar dctc-ai-msg-avatar--bot' },
									renderBotAvatar( botPreset, botAvatar, botName )
								),
							createElement(
								'div',
								{
									className: 'dctc-ai-message dctc-ai-message-bot',
								},
								createElement(
									'div',
									{ className: 'dctc-ai-typing' },
									createElement( 'span', null ),
									createElement( 'span', null ),
									createElement( 'span', null )
								)
							)
						),
					createElement( 'div', { ref: messagesEndRef } )
				),

				// Email Capture Gate OR Input Bar
				showEmailGate
					? createElement(
							'div',
							{
								className: 'dctc-ai-email-capture-container',
							},
							createElement(
								'form',
								{
									className: 'dctc-ai-email-capture-form',
									onSubmit: handleEmailSubmit,
								},
								createElement(
									'h3',
									null,
									__( 'One last step!', 'dragwyb-click-to-chat' )
								),
								createElement(
									'p',
									null,
									__(
										'Please enter your email to get your response and continue.',
										'dragwyb-click-to-chat'
									)
								),
								createElement(
									'div',
									{
										className: 'dctc-ai-email-input-group',
									},
									createElement( 'input', {
										type: 'email',
										className: 'dctc-ai-email-input',
										placeholder: __(
											'your.email@example.com',
											'dragwyb-click-to-chat'
										),
										value: emailDraft,
										onChange: ( e ) => {
											setEmailDraft( e.target.value );
											setEmailError( '' );
										},
										required: true,
									} ),
									emailError &&
										createElement(
											'span',
											{
												className: 'dctc-ai-email-error',
											},
											emailError
										)
								),
								createElement(
									'button',
									{
										type: 'submit',
										className: 'dctc-ai-email-submit',
										style: { background: primaryColor },
									},
									__( 'Get Response', 'dragwyb-click-to-chat' )
								)
							)
					  )
					: createElement(
							'div',
							{ className: 'dctc-ai-chat-input-wrapper' },

							// Upload Error Notice (if any)
							uploadError &&
								createElement(
									'div',
									{ className: 'dctc-ai-upload-error-notice' },
									createElement( 'span', null, uploadError ),
									createElement(
										'button',
										{
											type: 'button',
											className: 'dctc-ai-upload-error-close',
											onClick: () => setUploadError( '' ),
											'aria-label': __( 'Dismiss', 'dragwyb-click-to-chat' ),
										},
										'✕'
									)
								),

							// Unified Modern Composer Container
							createElement(
								'div',
								{ className: 'dctc-ai-composer' },

								// Hidden file inputs
								enableUploads &&
									createElement( 'input', {
										ref: fileInputRef,
										type: 'file',
										multiple: maxFilesPerMsg > 1,
										style: { display: 'none' },
										onChange: handleFilesSelected,
										accept:
											allowedExts
												.map( ( ext ) => '.' + ext )
												.join( ',' ) || undefined,
									} ),
								enableUploads &&
									createElement( 'input', {
										ref: imageFileInputRef,
										type: 'file',
										multiple: maxFilesPerMsg > 1,
										style: { display: 'none' },
										onChange: handleFilesSelected,
										accept: 'image/png,image/jpeg,image/webp,image/gif,image/*',
									} ),

								// Attachments tray inside composer (rendered above the input row)
								attachments.length > 0 &&
									createElement(
										'div',
										{ className: 'dctc-ai-composer-attachments' },
										attachments.map( ( att ) =>
											createElement(
												'div',
												{
													key: att.id,
													className: `dctc-ai-composer-att-card ${
														att.type === 'image'
															? 'dctc-ai-composer-att-card--image'
															: 'dctc-ai-composer-att-card--file'
													}`,
												},
												att.type === 'image'
													? createElement(
															'div',
															{
																className:
																	'dctc-ai-composer-att-img-box',
															},
															createElement( 'img', {
																src:
																	att.previewUrl ||
																	att.url,
																alt: att.name,
																className:
																	'dctc-ai-composer-att-thumb',
															} ),
															createElement(
																'div',
																{
																	className:
																		'dctc-ai-composer-att-img-overlay',
																},
																createElement(
																	'span',
																	{
																		className:
																			'dctc-ai-composer-att-img-icon',
																	},
																	'🖼️'
																),
																createElement(
																	'span',
																	{
																		className:
																			'dctc-ai-composer-att-img-name',
																		title: att.name,
																	},
																	att.name
																)
															)
													  )
													: createElement(
															'div',
															{
																className:
																	'dctc-ai-composer-att-file-box',
															},
															createElement(
																'span',
																{
																	className:
																		'dctc-ai-composer-att-file-icon',
																},
																'📄'
															),
															createElement(
																'div',
																{
																	className:
																		'dctc-ai-composer-att-file-info',
																},
																createElement(
																	'span',
																	{
																		className:
																			'dctc-ai-composer-att-file-name',
																		title: att.name,
																	},
																	att.name
																),
																createElement(
																	'span',
																	{
																		className:
																			'dctc-ai-composer-att-file-size',
																	},
																	formatFileSize(
																		att.size
																	)
																)
															)
													  ),
												att.status === 'uploading' &&
													createElement(
														'div',
														{
															className:
																'dctc-ai-composer-att-loading-mask',
														},
														createElement( 'span', {
															className:
																'dctc-ai-composer-att-spinner',
														} )
													),
												createElement(
													'button',
													{
														type: 'button',
														className:
															'dctc-ai-composer-att-remove',
														onClick: () =>
															removeAttachment( att.id ),
														title: __(
															'Remove attachment',
															'dragwyb-click-to-chat'
														),
														'aria-label': sprintf(
															__(
																'Remove %s',
																'dragwyb-click-to-chat'
															),
															att.name
														),
													},
													'×'
												)
											)
										)
									),

								// Composer Row: [ + Menu ] [ Textarea ] [ 😊 Emoji ] [ ➤ Send ]
								createElement(
									'div',
									{ className: 'dctc-ai-composer-row' },

									// Plus (+) Button with Popup Menu (on left inside input/composer)
									enableUploads &&
										createElement(
											'div',
											{
												ref: attachMenuRef,
												className:
													'dctc-ai-composer-plus-wrap',
											},
											createElement(
												'button',
												{
													type: 'button',
													className: `dctc-ai-composer-plus-btn ${
														isAttachMenuOpen
															? 'is-active'
															: ''
													}`,
													onClick: () =>
														setIsAttachMenuOpen(
															( prev ) => ! prev
														),
													title: __(
														'Add attachment',
														'dragwyb-click-to-chat'
													),
													'aria-label': __(
														'Add attachment',
														'dragwyb-click-to-chat'
													),
													'aria-expanded':
														isAttachMenuOpen,
													'aria-haspopup': 'true',
													disabled:
														isLoading || isAnyUploading,
												},
												'＋'
											),
											isAttachMenuOpen &&
												createElement(
													'div',
													{
														className:
															'dctc-ai-attach-menu',
													},
													createElement(
														'button',
														{
															type: 'button',
															className:
																'dctc-ai-attach-menu-item',
															onClick: () => {
																setIsAttachMenuOpen(
																	false
																);
																if (
																	fileInputRef.current
																) {
																	fileInputRef.current.click();
																}
															},
														},
														createElement(
															'span',
															{
																className:
																	'dctc-ai-attach-menu-icon',
															},
															'📎'
														),
														createElement(
															'span',
															{
																className:
																	'dctc-ai-attach-menu-label',
															},
															__(
																'Attach File',
																'dragwyb-click-to-chat'
															)
														)
													),
													createElement(
														'button',
														{
															type: 'button',
															className:
																'dctc-ai-attach-menu-item',
															onClick: () => {
																setIsAttachMenuOpen(
																	false
																);
																if (
																	imageFileInputRef.current
																) {
																	imageFileInputRef.current.click();
																}
															},
														},
														createElement(
															'span',
															{
																className:
																	'dctc-ai-attach-menu-icon',
															},
															'🖼️'
														),
														createElement(
															'span',
															{
																className:
																	'dctc-ai-attach-menu-label',
															},
															__(
																'Upload Image',
																'dragwyb-click-to-chat'
															)
														)
													)
												)
										),

									// Textarea in the middle
									createElement( 'textarea', {
										ref: inputRef,
										className: 'dctc-ai-composer-textarea',
										value: input,
										onChange: ( e ) => {
											setInput( e.target.value );
											e.target.style.height = 'auto';
											e.target.style.height =
												Math.min( e.target.scrollHeight, 140 ) +
												'px';
										},
										onKeyDown: ( e ) => {
											if ( e.key === 'Enter' && ! e.shiftKey ) {
												e.preventDefault();
												handleSend( e );
											}
										},
										placeholder: __(
											'Type your message...',
											'dragwyb-click-to-chat'
										),
										rows: 1,
										disabled: isLoading,
									} ),

									// Emoji Button on right of input (before Send)
									createElement(
										'div',
										{
											ref: emojiWrapRef,
											className:
												'dctc-ai-composer-emoji-wrap',
										},
										createElement(
											'button',
											{
												type: 'button',
												className: `dctc-ai-composer-emoji-btn ${
													isEmojiOpen ? 'is-active' : ''
												}`,
												onClick: () =>
													setIsEmojiOpen(
														( prev ) => ! prev
													),
												title: __(
													'Insert emoji',
													'dragwyb-click-to-chat'
												),
												'aria-label': __(
													'Insert emoji',
													'dragwyb-click-to-chat'
												),
												'aria-expanded': isEmojiOpen,
												'aria-controls':
													'dctc-ai-emoji-picker',
											},
											'😊'
										),
										isEmojiOpen &&
											createElement( EmojiPicker, {
												onSelect: handleSelectEmoji,
												onClose: () =>
													setIsEmojiOpen( false ),
											} )
									),

									// Send Button on far right
									createElement(
										'button',
										{
											type: 'button',
											className: 'dctc-ai-composer-send-btn',
											onClick: handleSend,
											disabled:
												isLoading ||
												isAnyUploading ||
												( ! input.trim() &&
													attachments.length === 0 ),
											style: { background: primaryColor },
											title: __(
												'Send message',
												'dragwyb-click-to-chat'
											),
											'aria-label': __(
												'Send message',
												'dragwyb-click-to-chat'
											),
										},
										isAnyUploading
											? createElement( 'span', {
													className: 'dctc-ai-spinner',
											  } )
											: createElement(
													'svg',
													{
														width: '16',
														height: '16',
														viewBox: '0 0 24 24',
														fill: 'none',
														'aria-hidden': 'true',
													},
													createElement( 'path', {
														d: 'M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z',
														stroke: 'currentColor',
														strokeWidth: '2.2',
														strokeLinecap: 'round',
														strokeLinejoin: 'round',
													} )
											  )
									)
								)
							)
					  )
			),

		// Launcher Button
		! inline &&
			! isOpen &&
			createElement(
				'div',
				{
					id: 'dctc-ai-launcher',
					className:
						'dctc-ai-chat-launcher' +
						( assistantIcon ? ' dctc-ai-chat-launcher--custom-icon' : '' ) +
						( launcherVisible ? '' : ' dctc-ai-chat-launcher--hidden' ),
					onClick: toggleOpen,
					style: { background: primaryColor },
					'aria-hidden': launcherVisible ? undefined : 'true',
				},
				display.launcher_text &&
					createElement(
						'span',
						{ className: 'dctc-ai-launcher-text' },
						display.launcher_text
					),
				createElement(
					'div',
					{ className: 'dctc-ai-launcher-icon-inner' },
					renderLauncherIcon( launcherPreset, assistantIcon )
				)
			)
	);
}
