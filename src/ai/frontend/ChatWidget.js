import { createElement, useState, useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
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

	const [ isOpen, setIsOpen ] = useState( false );
	const [ launcherVisible, setLauncherVisible ] = useState( () => {
		const delay = parseInt( display.time_delay, 10 ) || 0;
		return delay <= 0;
	} );
	const [ input, setInput ] = useState( '' );
	const [ isLoading, setIsLoading ] = useState( false );
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
	const wrapperRef = useRef( null );
	const isMountedRef = useRef( true );

	useEffect( () => {
		return () => {
			isMountedRef.current = false;
		};
	}, [] );

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

	// Delay launcher appearance after page load (like Channels widget time delay).
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
	}, [ messages, isLoading ] );

	const toggleOpen = () => setIsOpen( ( open ) => ! open );

	const needsEmail = !! chatbot.save_chat && !! chatbot.ask_email;

	const handleSend = async ( event, promptOverride = null ) => {
		if ( event && event.preventDefault ) {
			event.preventDefault();
		}
		if ( isLoading ) {
			return;
		}

		const prompt = ( promptOverride !== null ? promptOverride : input ).trim();
		if ( ! prompt ) {
			return;
		}

		// Email gate: stash the first prompt until email is collected.
		if ( needsEmail && ! email ) {
			setMessages( [ { role: 'user', content: prompt } ] );
			setPendingPrompt( prompt );
			setInput( '' );
			return;
		}

		setMessages( ( prev ) => [ ...prev, { role: 'user', content: prompt } ] );
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
					prompt,
					session_id: sessionId,
					email,
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

				if ( response.messages && response.messages.length > 0 ) {
					setMessages( response.messages );
				} else {
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
					setMessages( ( prev ) => [
						...prev,
						{ role: 'bot', content: botMessage },
					] );
				}
			} else {
				setMessages( ( prev ) => [
					...prev,
					{ role: 'error', content: getErrorMessage() },
				] );
			}
		} catch ( err ) {
			if ( isMountedRef.current ) {
				setMessages( ( prev ) => [
					...prev,
					{ role: 'error', content: getErrorMessage() },
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
		if ( ! /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( value ) ) {
			setEmailError(
				__(
					'Please enter a valid email address.',
					'dragwyb-click-to-chat'
				)
			);
			return;
		}

		setEmail( value );

		const prompt =
			pendingPrompt ||
			( messages.length === 1 ? messages[ 0 ].content : '' );

		if ( ! prompt ) {
			return;
		}

		setIsLoading( true );
		setPendingPrompt( '' );

		try {
			const response = await apiFetch( {
				path: '/dctc-ai/v1/chat',
				method: 'POST',
				data: {
					prompt,
					session_id: sessionId,
					email: value,
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

				if ( response.messages && response.messages.length > 0 ) {
					setMessages( response.messages );
				} else {
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
					setMessages( [
						{ role: 'user', content: prompt },
						{ role: 'bot', content: botMessage },
					] );
				}
			} else {
				setMessages( ( prev ) => [
					...prev,
					{ role: 'error', content: getErrorMessage() },
				] );
			}
		} catch ( err ) {
			if ( isMountedRef.current ) {
				setMessages( ( prev ) => [
					...prev,
					{ role: 'error', content: getErrorMessage() },
				] );
			}
		} finally {
			if ( isMountedRef.current ) {
				setIsLoading( false );
			}
		}
	};

	const primaryColor = chatbot.primary_color || '#2563eb';
	const botAvatar = chatbot.bot_avatar || '';
	const bubbleClass = `dctc-ai-bubble-${ chatbot.bubble_style || 'rounded' }`;
	const assistantIcon = display.assistant_icon || '';
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
				createElement(
					'div',
					{ className: 'dctc-ai-chat-header' },
					createElement(
						'div',
						{ className: 'dctc-ai-chat-avatar' },
						botAvatar
							? createElement( 'img', {
									src: botAvatar,
									alt: __(
										'Avatar',
										'dragwyb-click-to-chat'
									),
							  } )
							: createElement(
									'span',
									null,
									(
										chatbot.bot_name || 'V'
									)
										.charAt( 0 )
										.toUpperCase()
							  )
					),
					createElement(
						'div',
						{ className: 'dctc-ai-chat-info' },
						createElement(
							'strong',
							null,
							chatbot.bot_name || 'Virtual Assistant'
						),
						createElement(
							'span',
							null,
							__( 'Online', 'dragwyb-click-to-chat' )
						)
					),
					clearAllowed &&
						createElement(
							'button',
							{
								className: 'dctc-ai-chat-clear',
								onClick: handleClear,
								title: __(
									'Clear Chat',
									'dragwyb-click-to-chat'
								),
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
							},
							createElement( 'span', {
								className: 'dashicons dashicons-no-alt',
								'aria-hidden': 'true',
							} )
						)
				),
				createElement(
					'div',
					{
						className: 'dctc-ai-chat-messages',
						id: 'dctc-ai-messages',
					},
					chatbot.greeting_msg &&
						messages.length === 0 &&
						suggestedQuestions.length === 0 &&
						createElement(
							'div',
							{
								className:
									'dctc-ai-message dctc-ai-message-bot',
							},
							createElement( 'p', null, chatbot.greeting_msg )
						),
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
											chatbot.pre_questions_border_radius ||
											'rounded'
										}`,
										style: {
											backgroundColor:
												chatbot.pre_questions_bg_color ||
												'#ffffff',
											color:
												chatbot.pre_questions_text_color ||
												'#475569',
											borderColor:
												chatbot.pre_questions_border_color ||
												'#e2e8f0',
										},
										onClick: ( e ) =>
											handleSend( e, question ),
									},
									question
								)
							)
						),
					messages.map( ( message, index ) => {
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
								className: 'dctc-ai-message ' + roleClass,
							},
							message.role === 'bot' ||
								message.role === 'error'
								? createElement(
										'div',
										{
											className:
												'dctc-ai-message-content',
										},
										createElement(
											Markdown,
											{ components: markdownComponents },
											linkifyContent( message.content )
										)
								  )
								: createElement( 'p', null, message.content )
						);
					} ),
					isLoading &&
						createElement(
							'div',
							{
								className:
									'dctc-ai-message dctc-ai-message-bot',
							},
							createElement(
								'div',
								{ className: 'dctc-ai-typing' },
								createElement( 'span', null ),
								createElement( 'span', null ),
								createElement( 'span', null )
							)
						),
					createElement( 'div', { ref: messagesEndRef } )
				),
				showEmailGate
					? createElement(
							'div',
							{
								className:
									'dctc-ai-email-capture-container',
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
									__(
										'One last step!',
										'dragwyb-click-to-chat'
									)
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
										className:
											'dctc-ai-email-input-group',
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
												className:
													'dctc-ai-email-error',
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
									__(
										'Get Response',
										'dragwyb-click-to-chat'
									)
								)
							)
					  )
					: createElement(
							'div',
							{ className: 'dctc-ai-chat-input-area' },
							createElement( 'textarea', {
								ref: inputRef,
								className: 'dctc-ai-chat-input',
								value: input,
								onChange: ( e ) => {
									setInput( e.target.value );
									e.target.style.height = 'auto';
									e.target.style.height =
										Math.min( e.target.scrollHeight, 150 ) +
										'px';
								},
								onKeyDown: ( e ) => {
									if (
										e.key === 'Enter' &&
										! e.shiftKey
									) {
										e.preventDefault();
										handleSend( e );
									}
								},
								placeholder: __(
									'Type a message...',
									'dragwyb-click-to-chat'
								),
								rows: 1,
								disabled: isLoading,
								style: {
									resize: 'none',
									overflowY: 'auto',
									minHeight: '44px',
									lineHeight: '20px',
									padding: '12px 20px',
									boxSizing: 'border-box',
								},
							} ),
							createElement(
								'button',
								{
									className: 'dctc-ai-chat-send',
									onClick: handleSend,
									disabled: isLoading,
									'aria-label': __(
										'Send message',
										'dragwyb-click-to-chat'
									),
								},
								createElement(
									'svg',
									{
										width: '18',
										height: '18',
										viewBox: '0 0 24 24',
										fill: 'none',
										'aria-hidden': 'true',
									},
									createElement( 'path', {
										d: 'M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z',
										stroke: 'currentColor',
										strokeWidth: '2',
										strokeLinecap: 'round',
										strokeLinejoin: 'round',
									} )
								)
							)
					  )
			),
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
					style: assistantIcon ? undefined : { background: primaryColor },
					'aria-hidden': launcherVisible ? undefined : 'true',
				},
				createElement(
					'span',
					{ className: 'dctc-ai-launcher-text' },
					display.launcher_text
				),
				assistantIcon
					? createElement( 'img', {
							className: 'dctc-ai-chat-launcher__icon',
							src: assistantIcon,
							alt: '',
					  } )
					: createElement( 'span', {
							className: 'dashicons dashicons-format-chat',
					  } )
			)
	);
}
