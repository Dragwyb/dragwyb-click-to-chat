import { useState, useEffect, useRef, useCallback } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import Markdown from 'react-markdown';

/**
 * Safely decode HTML entities (e.g. &#8377; -> ₹) in strings for UI rendering.
 *
 * @param {string} str Raw text containing possible HTML entities.
 * @return {string} Decoded string.
 */
function decodeHtmlEntities(str) {
	if (!str || typeof str !== 'string') {
		return str || '';
	}
	if (!str.includes('&')) {
		return str;
	}
	try {
		const txt = document.createElement('textarea');
		txt.innerHTML = str;
		return txt.value;
	} catch (e) {
		return str;
	}
}

/**
 * Auto-linkify bare URLs in markdown, leaving existing links/tags alone.
 *
 * @param {string} content Message content.
 * @return {string}
 */
function linkifyContent(content) {
	if (!content) {
		return '';
	}

	const decoded = decodeHtmlEntities(content);

	return decoded
		.split(/(\[[^\]]+\]\([^)]+\)|<[^>]+>)/g)
		.map((part) => {
			if (/^\[.+\]\(.+\)$/.test(part) || /^<.+>$/.test(part)) {
				return part;
			}

			return part.replace(/(https?:\/\/[^\s\)<>"]+)/gi, (url) => {
				let clean = url;
				let trailing = '';

				if (/[.,;:!]$/.test(clean)) {
					trailing = clean.slice(-1);
					clean = clean.slice(0, -1);
				}

				return `[${clean}](${clean})${trailing}`;
			});
		})
		.join('');
}

/**
 * Format conversation history into standard message array for /chat payload.
 *
 * @param {Array} msgs Array of chat messages.
 * @param {string} botName Default bot name.
 * @return {Array}
 */
function formatConversationHistory(msgs, botName) {
	if (!Array.isArray(msgs)) {
		return [];
	}

	return msgs
		.filter((m) => m && m.content && m.role !== 'system' && m.sender_type !== 'system' && m.role !== 'error')
		.map((m) => {
			const isBot =
				m.role === 'bot' ||
				m.role === 'assistant' ||
				m.sender_type === 'ai_agent' ||
				m.sender_type === 'support_agent' ||
				m.sender_type === 'agent';
			const role = isBot ? 'assistant' : 'user';
			const senderType =
				m.sender_type || (isBot ? 'ai_agent' : 'customer');
			const senderName =
				m.sender_name || (isBot ? (botName || 'AI Assistant') : 'Customer');
			const createdAt = m.created_at || new Date().toISOString();

			return {
				role,
				sender_type: senderType,
				sender_name: senderName,
				content:
					typeof m.content === 'string'
						? m.content
						: (m.content?.text || JSON.stringify(m.content)),
				created_at: createdAt,
			};
		});
}

/**
 * Helper to render launcher icon based on preset or custom image.
 */
function renderLauncherIcon(preset, customUrl) {
	if (customUrl) {
		return <img className="dctc-ai-chat-launcher__icon" src={customUrl} alt="" />;
	}

	switch (preset) {
		case 'bot':
			return (
				<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
					<rect x="3" y="11" width="18" height="10" rx="2" />
					<circle cx="12" cy="5" r="2" />
					<path d="M12 7v4M8 16h.01M16 16h.01" />
				</svg>
			);
		case 'sparkle':
			return (
				<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
					<path d="m12 3-1.9 5.8a2 2 0 0 1-1.3 1.3L3 12l5.8 1.9a2 2 0 0 1 1.3 1.3L12 21l1.9-5.8a2 2 0 0 1 1.3-1.3L21 12l-5.8-1.9a2 2 0 0 1-1.3-1.3Z" />
					<path d="M19 4v4M21 6h-4" />
				</svg>
			);
		case 'support':
		case 'headset':
			return (
				<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
					<path d="M3 18v-6a9 9 0 0 1 18 0v6" />
					<path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z" />
				</svg>
			);
		case 'help':
			return (
				<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
					<circle cx="12" cy="12" r="10" />
					<path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3M12 17h.01" />
				</svg>
			);
		case 'whatsapp':
			return (
				<svg width="26" height="26" viewBox="0 0 24 24" fill="currentColor">
					<path d="M17.472 14.382c-.301-.15-1.782-.879-2.057-.98-.276-.1-.476-.15-.677.15-.2.301-.777.98-.953 1.18-.175.2-.351.226-.652.075-.301-.15-1.272-.469-2.423-1.496-.896-.799-1.501-1.786-1.677-2.087-.175-.301-.019-.464.132-.614.136-.135.301-.351.451-.527.15-.175.2-.301.301-.501.1-.2.05-.376-.025-.526-.075-.15-.677-1.633-.928-2.235-.244-.587-.493-.507-.677-.517-.175-.008-.376-.01-.577-.01-.201 0-.527.075-.803.376-.276.301-1.053 1.028-1.053 2.508 0 1.479 1.078 2.908 1.229 3.109.15.2 2.122 3.24 5.141 4.544.718.31 1.279.496 1.716.635.722.23 1.379.197 1.898.12.578-.087 1.782-.728 2.033-1.431.25-.703.25-1.305.175-1.431-.075-.126-.276-.201-.577-.351zm-5.467 7.518h-.005a10.84 10.84 0 0 1-5.526-1.509l-.396-.235-4.108 1.077 1.096-4.004-.258-.411a10.835 10.835 0 0 1-1.666-5.783c0-5.99 4.874-10.865 10.869-10.865a10.81 10.81 0 0 1 7.684 3.184 10.812 10.812 0 0 1 3.18 7.686c0 5.992-4.874 10.866-10.868 10.866z" />
				</svg>
			);
		case 'chat':
		default:
			return (
				<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
					<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
				</svg>
			);
	}
}

/**
 * Helper to render bot avatar based on preset or custom image.
 */
function renderBotAvatar(preset, customUrl, botName) {
	if (customUrl) {
		return <img src={customUrl} alt={botName || 'AI'} />;
	}

	if (preset === 'initial') {
		const initial = (botName || 'B').charAt(0).toUpperCase();
		return <span>{initial}</span>;
	}

	switch (preset) {
		case 'sparkle':
			return (
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
					<path d="m12 3-1.9 5.8a2 2 0 0 1-1.3 1.3L3 12l5.8 1.9a2 2 0 0 1 1.3 1.3L12 21l1.9-5.8a2 2 0 0 1 1.3-1.3L21 12l-5.8-1.9a2 2 0 0 1-1.3-1.3Z" />
					<path d="M19 4v4M21 6h-4" />
				</svg>
			);
		case 'support':
			return (
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
					<path d="M3 18v-6a9 9 0 0 1 18 0v6" />
					<path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z" />
				</svg>
			);
		case 'chat':
			return (
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
					<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
				</svg>
			);
		case 'bot':
		default:
			return (
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
					<rect x="3" y="11" width="18" height="10" rx="2" />
					<circle cx="12" cy="5" r="2" />
					<path d="M12 7v4M8 16h.01M16 16h.01" />
				</svg>
			);
	}
}

/**
 * Curated lightweight Unicode emoji categories.
 */
const EMOJI_CATEGORIES = [
	{
		id: 'smileys',
		label: __('Smileys & Emotion', 'dragwyb-click-to-chat'),
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
		label: __('Hands & People', 'dragwyb-click-to-chat'),
		icon: '👍',
		emojis: [
			'👍', '👎', '👊', '✌️', '🤞', '🤝', '👏', '🙌', '👐', '🤲',
			'🙏', '✍️', '👋', '🤙', '👂', '👀', '🧠', '👤', '👥', '🧑‍💻',
			'💪', '🖐️', '👌', '👈', '👉', '👆', '👇', '☝️', '✋', '🖖',
		],
	},
	{
		id: 'symbols',
		label: __('Symbols & Objects', 'dragwyb-click-to-chat'),
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
function formatFileSize(bytes) {
	if (!bytes || bytes === 0) {
		return '0 B';
	}
	const k = 1024;
	const sizes = ['B', 'KB', 'MB', 'GB'];
	const i = Math.floor(Math.log(bytes) / Math.log(k));
	return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}

/**
 * Lightweight accessible Emoji Picker Component.
 */
function EmojiPicker({ onSelect, onClose }) {
	const [activeTab, setActiveTab] = useState(0);
	const pickerRef = useRef(null);

	useEffect(() => {
		const handleKeyDown = (e) => {
			if (e.key === 'Escape') {
				onClose();
			}
		};
		document.addEventListener('keydown', handleKeyDown);
		return () => {
			document.removeEventListener('keydown', handleKeyDown);
		};
	}, [onClose]);

	return (
		<div
			ref={pickerRef}
			id="dctc-ai-emoji-picker"
			className="dctc-ai-emoji-picker"
			role="dialog"
			aria-label={__('Emoji Picker', 'dragwyb-click-to-chat')}
		>
			<div className="dctc-ai-emoji-tabs">
				{EMOJI_CATEGORIES.map((cat, idx) => (
					<button
						key={cat.id}
						type="button"
						className={`dctc-ai-emoji-tab ${idx === activeTab ? 'active' : ''}`}
						onClick={() => setActiveTab(idx)}
						title={cat.label}
						aria-label={cat.label}
					>
						{cat.icon}
					</button>
				))}
			</div>
			<div className="dctc-ai-emoji-grid">
				{EMOJI_CATEGORIES[activeTab].emojis.map((emoji, idx) => (
					<button
						key={idx}
						type="button"
						className="dctc-ai-emoji-btn"
						onClick={() => onSelect(emoji)}
						aria-label={`Emoji ${emoji}`}
					>
						{emoji}
					</button>
				))}
			</div>
		</div>
	);
}

/**
 * Helper to render user avatar based on preset or custom image.
 */
function renderUserAvatar(preset, customUrl) {
	if (customUrl) {
		return <img src={customUrl} alt="User" />;
	}

	switch (preset) {
		case 'user-circle':
			return (
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
					<circle cx="12" cy="12" r="10" />
					<circle cx="12" cy="10" r="3" />
					<path d="M7 20.662V19a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v1.662" />
				</svg>
			);
		case 'business':
			return (
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
					<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
					<circle cx="9" cy="7" r="4" />
					<path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" />
				</svg>
			);
		case 'smile':
			return (
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
					<circle cx="12" cy="12" r="10" />
					<path d="M8 14s1.5 2 4 2 4-2 4-2M9 9h.01M15 9h.01" />
				</svg>
			);
		case 'star':
			return (
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
					<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
				</svg>
			);
		case 'user':
		default:
			return (
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
					<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2" />
					<circle cx="12" cy="7" r="4" />
				</svg>
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
export default function ChatWidget({ settings, inline }) {
	const chatbot = settings?.chatbot || {};
	const display = settings?.display || {};
	const hasApiKey = settings?.has_api_key !== false;
	const isAdmin = !!settings?.is_admin;
	const settingsUrl = settings?.settings_url || '';

	const getErrorMessage = () => {
		const template =
			chatbot.api_error_msg ||
			'There is some error on server, please contact our [support agent]({support_url}).';
		const supportUrl = chatbot.support_url || '#';
		return template.replace('{support_url}', supportUrl);
	};

	const suggestedQuestions = chatbot.enable_pre_questions
		? [
			chatbot.pre_question_1,
			chatbot.pre_question_2,
			chatbot.pre_question_3,
			chatbot.pre_question_4,
		].filter((q) => q && q.trim())
		: [];

	const actionButtons = Array.isArray(chatbot.action_buttons)
		? chatbot.action_buttons.filter((b) => b && b.label && b.label.trim())
		: [];

	const showBotAvatarInChat = !!chatbot.show_bot_avatar_in_chat;
	const showUserAvatarInChat = !!chatbot.show_user_avatar_in_chat;
	const showSources = chatbot.show_sources !== false;
	const enableUploads = !!chatbot.enable_uploads;
	const maxUploadSizeMb = parseInt(chatbot.max_upload_size, 10) || 5;
	const maxFilesPerMsg = parseInt(chatbot.max_files_per_message, 10) || 3;
	const allowedExts = (
		chatbot.allowed_file_types ||
		'jpg, jpeg, png, webp, gif, pdf, txt, doc, docx'
	)
		.toLowerCase()
		.split(',')
		.map((s) => s.trim().replace(/^\./, ''))
		.filter(Boolean);
	const excludedExts = (
		chatbot.excluded_file_types ||
		'php, php3, php4, php5, phtml, phar, cgi, pl, py, sh, exe, bat, cmd, js, html, htm, svg'
	)
		.toLowerCase()
		.split(',')
		.map((s) => s.trim().replace(/^\./, ''))
		.filter(Boolean);

	const [isOpen, setIsOpen] = useState(false);
	const [proactiveBubbleOpen, setProactiveBubbleOpen] = useState(false);
	const [launcherVisible, setLauncherVisible] = useState(() => {
		const delay = parseInt(display.time_delay, 10) || 0;
		return delay <= 0;
	});
	const [input, setInput] = useState('');
	const [isLoading, setIsLoading] = useState(false);
	const [attachments, setAttachments] = useState([]);
	const [isEmojiOpen, setIsEmojiOpen] = useState(false);
	const [isAttachMenuOpen, setIsAttachMenuOpen] = useState(false);
	const [uploadError, setUploadError] = useState('');
	const [isListening, setIsListening] = useState(false);
	const [speakingIndex, setSpeakingIndex] = useState(null);
	const recognitionRef = useRef(null);
	const [sessionId, setSessionId] = useState(
		() =>
			window.dctc_ai_frontend_data?.session_id ||
			'sess_' + Math.random().toString(36).substr(2, 9)
	);
	const [clearAllowed, setClearAllowed] = useState(() => {
		const value = window.dctc_ai_frontend_data?.clear_allowed;
		return value === null || value === undefined || value;
	});
	const [messages, setMessages] = useState([]);
	const messagesRef = useRef(messages);
	useEffect(() => {
		messagesRef.current = messages;
	}, [messages]);
	const [email, setEmail] = useState('');
	const [emailDraft, setEmailDraft] = useState('');
	const [emailError, setEmailError] = useState('');
	const [pendingPrompt, setPendingPrompt] = useState('');

	// Live Support & Session Control State
	const [activeControlMode, setActiveControlMode] = useState('ai');
	const [assignedAgentName, setAssignedAgentName] = useState('');
	const [hasActiveTicket, setHasActiveTicket] = useState(false);
	const hasActiveTicketRef = useRef(hasActiveTicket);
	useEffect(() => {
		hasActiveTicketRef.current = hasActiveTicket;
	}, [hasActiveTicket]);

	// Human agent waiting & auto-fallback states
	const [waitingAgentStatusText, setWaitingAgentStatusText] = useState('');
	const aiResponseWaiting = useRef(false);
	const wait30sTimerRef = useRef(null);
	const maxWaitTimeoutRef = useRef(null);

	const clearAgentWaitTimers = useCallback(() => {
		if (wait30sTimerRef.current) {
			clearTimeout(wait30sTimerRef.current);
			wait30sTimerRef.current = null;
		}
		if (maxWaitTimeoutRef.current) {
			clearTimeout(maxWaitTimeoutRef.current);
			maxWaitTimeoutRef.current = null;
		}
	}, []);

	useEffect(() => {
		return () => {
			clearAgentWaitTimers();
		};
	}, [clearAgentWaitTimers]);

	const triggerAiFallback = useCallback(async (fallbackPrompt) => {
		if (!sessionId || !isMountedRef.current) return;
		try {
			aiResponseWaiting.current = true;

			const activePageContext = window.dctc_ai_frontend_data?.page_context || {
				url: window.location.href,
				title: document.title,
			};

			const isSaveChatEnabled = !!chatbot.save_chat;
			const ticketAlreadyCreated = Boolean(hasActiveTicket || hasActiveTicketRef.current);
			const chatPayload = {
				prompt: fallbackPrompt || 'Please assist me with my inquiry.',
				session_id: sessionId,
				email,
				page_context: activePageContext,
				visitor_lang: navigator.language || navigator.userLanguage || '',
				fallback_trigger: true,
			};

			if (!isSaveChatEnabled && !ticketAlreadyCreated) {
				chatPayload.messages = formatConversationHistory(
					messagesRef.current,
					chatbot.bot_name
				);
			}

			const response = await apiFetch({
				path: '/dctc-ai/v1/chat',
				method: 'POST',
				data: chatPayload,
			});

			if (!isMountedRef.current) return;

			if (response?.success) {
				clearAgentWaitTimers();

				if (response.is_human_handled) {
					return;
				}

				setWaitingAgentStatusText('');
				setIsLoading(false);

				let botMessage = response.message;
				if (typeof botMessage === 'object' && botMessage !== null) {
					botMessage = botMessage.text || botMessage.content || JSON.stringify(botMessage);
				}

				if (botMessage) {
					const isLeadPrompt = Boolean(
						typeof botMessage === 'string' && (botMessage.toLowerCase().includes('form below') || botMessage.toLowerCase().includes('fill out the form'))
					);
					const isOrderTrackerPrompt = Boolean(
						response.show_order_tracker || (response.show_form?.form_type === 'order_track' && response.show_form?.show !== false)
					);
					setMessages((prev) => [
						...prev.map((m) => {
							let updated = m;
							if (isLeadPrompt && updated.show_form && updated.show_form.show && !updated.show_form.form_filled) {
								updated = {
									...updated,
									show_form: { ...updated.show_form, show: false },
								};
							}
							if (isOrderTrackerPrompt && (updated.show_order_tracker || (updated.show_form && updated.show_form.form_type === 'order_track' && updated.show_form.show))) {
								updated = {
									...updated,
									show_order_tracker: false,
									show_form: updated.show_form ? { ...updated.show_form, show: false } : { form_type: 'order_track', show: false, form_filled: false },
								};
							}
							return updated;
						}),
						{
							role: 'bot',
							content: botMessage,
							sources: Array.isArray(response.sources) ? response.sources : [],
							action_buttons: Array.isArray(response.action_buttons) ? response.action_buttons : [],
							products: Array.isArray(response.products) ? response.products : [],
							show_order_tracker: isOrderTrackerPrompt,
							show_form: isOrderTrackerPrompt
								? { form_type: 'order_track', show: true, form_filled: false }
								: (isLeadPrompt ? { form_type: 'lead_generate', show: true } : (response.show_form || null)),
							form_type: isLeadPrompt,
							form_filled: false,
						},
					]);
				}

				if (response.control_mode) {
					setActiveControlMode(response.control_mode);
				}

				if ((response.ticket && response.ticket.id) || response.has_ticket) {
					setHasActiveTicket(true);
					hasActiveTicketRef.current = true;
					if (response.ticket && response.ticket.agent_name) {
						setAssignedAgentName(response.ticket.agent_name);
					}
				}
			}
		} catch (e) {
			// Silently catch background fallback network error
		}

		aiResponseWaiting.current = false;
	}, [sessionId, email, clearAgentWaitTimers]);

	// AI Lead Capture State
	const loggedInUserEmail = window.dctc_ai_frontend_data?.user_email || window.dctc_ai_frontend_data?.page_context?.user_email || '';
	const loggedInUserName = window.dctc_ai_frontend_data?.user_name || window.dctc_ai_frontend_data?.page_context?.user_name || '';
	const pageProductOrTitle = window.dctc_ai_frontend_data?.page_context?.product?.name || (window.dctc_ai_frontend_data?.page_context?.is_single ? window.dctc_ai_frontend_data?.page_context?.title : '') || '';

	const [leadFormSubmitted, setLeadFormSubmitted] = useState(false);
	const [leadFormSubmitting, setLeadFormSubmitting] = useState(false);
	const [leadFormError, setLeadFormError] = useState('');
	const [closedFormIndexes, setClosedFormIndexes] = useState({});
	const [formsDataByKey, setFormsDataByKey] = useState({});

	const getFormKey = (msg, index) => {
		const timestamp = msg?.created_at || (msg?.id ? String(msg.id) : `msg_${index}`);
		const sender = msg?.sender_name || (msg?.role === 'user' ? 'customer' : 'bot');
		return `${timestamp}_${sender}`;
	};

	const getFormDataForMessage = (msg, index) => {
		const key = getFormKey(msg, index);
		const existingData = formsDataByKey[key];

		return {
			name: existingData?.name || loggedInUserName,
			email: existingData?.email || loggedInUserEmail,
			phone: existingData?.phone || '',
			company: existingData?.company || '',
			company_size: existingData?.company_size || '',
			budget: existingData?.budget || '',
			timeline: existingData?.timeline || '',
			interest: existingData?.interest || '',
			requirement: existingData?.requirement || '',
		};
	};

	const updateFormDataForMessage = (msg, index, field, value) => {
		const key = getFormKey(msg, index);
		setFormsDataByKey((prev) => ({
			...prev,
			[key]: {
				...getFormDataForMessage(msg, index),
				[field]: value,
			},
		}));
	};

	// Feature 11: WooCommerce Sales & Order Tracker State
	const [orderLookupId, setOrderLookupId] = useState('');
	const [orderLookupResult, setOrderLookupResult] = useState(null);
	const [orderLookupLoading, setOrderLookupLoading] = useState(false);
	const [orderLookupError, setOrderLookupError] = useState('');

	// AI Chatbot Session Keepalive / Heartbeat: ping every 25 seconds when widget is active
	useEffect(() => {
		if (!isOpen && !inline) return;
		if (!sessionId) return;

		// Immediate initial heartbeat ping
		apiFetch({
			path: '/dctc-ai/v1/support/session/heartbeat',
			method: 'POST',
			data: { session_id: sessionId },
		}).catch(() => { });

		const interval = setInterval(() => {
			if (typeof document !== 'undefined' && document.hidden) return;
			apiFetch({
				path: '/dctc-ai/v1/support/session/heartbeat',
				method: 'POST',
				data: { session_id: sessionId },
			}).catch(() => { });
		}, 25000); // 25 seconds

		return () => clearInterval(interval);
	}, [isOpen, inline, sessionId]);

	const handleOrderLookup = async (e) => {
		if (e && e.preventDefault) {
			e.preventDefault();
		}
		setOrderLookupError('');
		setOrderLookupResult(null);

		const orderNum = parseInt(orderLookupId, 10);
		if (!orderNum || isNaN(orderNum)) {
			setOrderLookupError(__('Please enter a valid numeric Order ID.', 'dragwyb-click-to-chat'));
			return;
		}

		setOrderLookupLoading(true);
		try {
			const res = await apiFetch({
				path: '/dctc-ai/v1/woocommerce/order-status',
				method: 'POST',
				data: {
					order_id: orderNum,
				},
			});

			if (res && res.success) {
				setOrderLookupResult(res);
			} else {
				setOrderLookupError(res?.message || __('Order not found. Please verify details.', 'dragwyb-click-to-chat'));
			}
		} catch (err) {
			setOrderLookupError(err?.message || __('Could not find order. Please verify your order number.', 'dragwyb-click-to-chat'));
		} finally {
			setOrderLookupLoading(false);
		}
	};

	const renderOrderTrackerCard = (message, msgIndex) => {
		return (
			<div
				key={`order_tracker_${msgIndex}`}
				className="dctc-ai-order-tracker-card"
				style={{
					background: '#ffffff',
					border: '1px solid #e2e8f0',
					borderRadius: '12px',
					padding: '1.25rem',
					margin: '0.75rem 0 0.25rem 0',
					boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.07)',
				}}
			>
				<div
					style={{
						display: 'flex',
						justifyContent: 'space-between',
						alignItems: 'flex-start',
						marginBottom: '0.75rem',
					}}
				>
					<div>
						<strong
							style={{
								display: 'block',
								fontSize: '0.95rem',
								color: '#0f172a',
							}}
						>
							{__('📦 Track Your Order Status', 'dragwyb-click-to-chat')}
						</strong>
						<span
							style={{
								fontSize: '0.8rem',
								color: '#64748b',
							}}
						>
							{__('Enter your Order ID for instant live updates.', 'dragwyb-click-to-chat')}
						</span>
					</div>
					<button
						type="button"
						onClick={() => {
							setMessages((prev) =>
								prev.map((m, i) =>
									i === msgIndex
										? {
											...m,
											show_order_tracker: false,
											show_form: m.show_form ? { ...m.show_form, show: false } : { form_type: 'order_track', show: false, form_filled: false },
										}
										: m
								)
							);
							setOrderLookupResult(null);
							setOrderLookupError('');
						}}
						style={{
							background: 'none',
							border: 'none',
							cursor: 'pointer',
							color: '#94a3b8',
							fontSize: '1rem',
							padding: '2px 6px',
						}}
						title={__('Close', 'dragwyb-click-to-chat')}
					>
						✕
					</button>
				</div>
				<form
					onSubmit={handleOrderLookup}
					style={{
						display: 'flex',
						flexDirection: 'column',
						gap: '0.6rem',
					}}
				>
					<input
						type="number"
						placeholder={__('Order ID (e.g. 1042)', 'dragwyb-click-to-chat')}
						value={orderLookupId}
						onChange={(e) => setOrderLookupId(e.target.value)}
						required
						style={{
							padding: '0.45rem 0.75rem',
							borderRadius: '6px',
							border: '1px solid #cbd5e1',
							fontSize: '0.85rem',
						}}
					/>
					{orderLookupError && (
						<span
							style={{
								color: '#ef4444',
								fontSize: '0.8rem',
							}}
						>
							{orderLookupError}
						</span>
					)}
					<button
						type="submit"
						disabled={orderLookupLoading}
						style={{
							padding: '0.45rem 0.9rem',
							borderRadius: '6px',
							border: 'none',
							background: primaryColor,
							color: '#ffffff',
							fontSize: '0.85rem',
							fontWeight: 600,
							cursor: orderLookupLoading ? 'not-allowed' : 'pointer',
							opacity: orderLookupLoading ? 0.7 : 1,
						}}
					>
						{orderLookupLoading
							? __('Verifying Order...', 'dragwyb-click-to-chat')
							: __('Look Up Order Status', 'dragwyb-click-to-chat')}
					</button>
				</form>

				{/* Order Lookup Result Card */}
				{orderLookupResult && (
					<div
						style={{
							marginTop: '0.85rem',
							padding: '0.85rem',
							background: '#f8fafc',
							borderRadius: '8px',
							border: '1px solid #e2e8f0',
						}}
					>
						<div
							style={{
								display: 'flex',
								justifyContent: 'space-between',
								alignItems: 'center',
								marginBottom: '0.5rem',
							}}
						>
							<strong style={{ color: '#0f172a', fontSize: '0.9rem' }}>
								{`Order #${orderLookupResult.order_number}`}
							</strong>
							<span
								style={{
									padding: '2px 8px',
									borderRadius: '999px',
									fontSize: '0.75rem',
									fontWeight: 600,
									textTransform: 'uppercase',
									background:
										orderLookupResult.status === 'completed'
											? '#dcfce7'
											: orderLookupResult.status === 'processing'
												? '#e0f2fe'
												: '#fef3c7',
									color:
										orderLookupResult.status === 'completed'
											? '#15803d'
											: orderLookupResult.status === 'processing'
												? '#0369a1'
												: '#b45309',
								}}
							>
								{orderLookupResult.status_label || orderLookupResult.status}
							</span>
						</div>
						<div style={{ fontSize: '0.8rem', color: '#475569', marginBottom: '0.4rem' }}>
							{`📅 ${orderLookupResult.date_created} • 💰 ${decodeHtmlEntities(orderLookupResult.formatted_total || orderLookupResult.formated_total || '')}`}
						</div>
						{orderLookupResult.items && orderLookupResult.items.length > 0 && (
							<ul
								style={{
									margin: '0.4rem 0 0 0',
									paddingLeft: '1.1rem',
									fontSize: '0.8rem',
									color: '#334155',
								}}
							>
								{orderLookupResult.items.map((it, itIdx) => (
									<li key={itIdx}>
										{`${it.name} × ${it.quantity} (${decodeHtmlEntities(it.subtotal || '')})`}
									</li>
								))}
							</ul>
						)}
					</div>
				)}
			</div>
		);
	};

	const renderLeadCard = (message, msgIndex) => {
		if (closedFormIndexes[msgIndex]) {
			return null;
		}

		const formData = getFormDataForMessage(message, msgIndex);
		const cardPrimaryColor = chatbot.primary_color || '#6366f1';

		return (
			<div
				key={`lead_form_${getFormKey(message, msgIndex)}`}
				className="dctc-ai-lead-card"
				style={{
					background: '#ffffff',
					border: '1px solid #e2e8f0',
					borderRadius: '12px',
					padding: '1.25rem',
					margin: '0.75rem 0 0.25rem 0',
					boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.07)',
					width: '100%',
					boxSizing: 'border-box',
				}}
			>
				<div
					style={{
						display: 'flex',
						justifyContent: 'space-between',
						alignItems: 'flex-start',
						marginBottom: '0.75rem',
					}}
				>
					<div>
						<strong
							style={{
								display: 'block',
								fontSize: '0.95rem',
								color: '#0f172a',
							}}
						>
							{chatbot.lead_form_title ||
								__('Contact Our Team', 'dragwyb-click-to-chat')}
						</strong>
						<span
							style={{
								fontSize: '0.8rem',
								color: '#64748b',
							}}
						>
							{chatbot.lead_form_subtitle ||
								__(
									'Leave your details and our team will get back to you shortly.',
									'dragwyb-click-to-chat'
								)}
						</span>
					</div>
					<button
						type="button"
						onClick={() => {
							setClosedFormIndexes((prev) => ({ ...prev, [msgIndex]: true }));
						}}
						style={{
							background: 'none',
							border: 'none',
							cursor: 'pointer',
							color: '#94a3b8',
							fontSize: '1rem',
							padding: '2px 6px',
						}}
						title={__('Close', 'dragwyb-click-to-chat')}
					>
						✕
					</button>
				</div>
				<form
					onSubmit={(e) => handleLeadSubmit(e, message, msgIndex)}
					style={{
						display: 'flex',
						flexDirection: 'column',
						gap: '0.6rem',
					}}
				>
					{(!chatbot.lead_fields || chatbot.lead_fields.name !== false) && (
						<input
							type="text"
							placeholder={__('Your Name', 'dragwyb-click-to-chat')}
							value={formData.name}
							onChange={(e) =>
								updateFormDataForMessage(message, msgIndex, 'name', e.target.value)
							}
							style={{
								padding: '0.45rem 0.75rem',
								borderRadius: '6px',
								border: '1px solid #cbd5e1',
								fontSize: '0.85rem',
							}}
						/>
					)}
					{(!chatbot.lead_fields || chatbot.lead_fields.email !== false) && (
						<input
							type="email"
							placeholder={__(
								'Your Email Address',
								'dragwyb-click-to-chat'
							)}
							value={formData.email}
							onChange={(e) =>
								updateFormDataForMessage(message, msgIndex, 'email', e.target.value)
							}
							style={{
								padding: '0.45rem 0.75rem',
								borderRadius: '6px',
								border: '1px solid #cbd5e1',
								fontSize: '0.85rem',
							}}
						/>
					)}
					{(!chatbot.lead_fields || chatbot.lead_fields.phone !== false) && (
						<input
							type="tel"
							placeholder={__(
								'Your Phone / WhatsApp',
								'dragwyb-click-to-chat'
							)}
							value={formData.phone}
							onChange={(e) =>
								updateFormDataForMessage(message, msgIndex, 'phone', e.target.value)
							}
							style={{
								padding: '0.45rem 0.75rem',
								borderRadius: '6px',
								border: '1px solid #cbd5e1',
								fontSize: '0.85rem',
							}}
						/>
					)}
					{chatbot.lead_fields && chatbot.lead_fields.company && (
						<input
							type="text"
							placeholder={__(
								'Company / Organization',
								'dragwyb-click-to-chat'
							)}
							value={formData.company}
							onChange={(e) =>
								updateFormDataForMessage(message, msgIndex, 'company', e.target.value)
							}
							style={{
								padding: '0.45rem 0.75rem',
								borderRadius: '6px',
								border: '1px solid #cbd5e1',
								fontSize: '0.85rem',
							}}
						/>
					)}
					{chatbot.lead_fields && chatbot.lead_fields.company_size && (
						<select
							value={formData.company_size}
							onChange={(e) =>
								updateFormDataForMessage(message, msgIndex, 'company_size', e.target.value)
							}
							style={{
								padding: '0.45rem 0.75rem',
								borderRadius: '6px',
								border: '1px solid #cbd5e1',
								fontSize: '0.85rem',
								background: '#ffffff',
							}}
						>
							<option value="">
								{__(
									'-- Select Company Size --',
									'dragwyb-click-to-chat'
								)}
							</option>
							<option value="1-10">
								{__(
									'1 - 10 employees',
									'dragwyb-click-to-chat'
								)}
							</option>
							<option value="11-50">
								{__(
									'11 - 50 employees',
									'dragwyb-click-to-chat'
								)}
							</option>
							<option value="51-200">
								{__(
									'51 - 200 employees',
									'dragwyb-click-to-chat'
								)}
							</option>
							<option value="200+">
								{__(
									'200+ Enterprise',
									'dragwyb-click-to-chat'
								)}
							</option>
						</select>
					)}
					{chatbot.lead_fields && chatbot.lead_fields.budget && (
						<select
							value={formData.budget}
							onChange={(e) =>
								updateFormDataForMessage(message, msgIndex, 'budget', e.target.value)
							}
							style={{
								padding: '0.45rem 0.75rem',
								borderRadius: '6px',
								border: '1px solid #cbd5e1',
								fontSize: '0.85rem',
								background: '#ffffff',
							}}
						>
							<option value="">
								{__(
									'-- Project Budget --',
									'dragwyb-click-to-chat'
								)}
							</option>
							<option value="< $1,000">&lt; $1,000</option>
							<option value="$1,000 - $5,000">$1,000 - $5,000</option>
							<option value="$5,000 - $20,000">$5,000 - $20,000</option>
							<option value="$20,000+">$20,000+</option>
						</select>
					)}
					{chatbot.lead_fields && chatbot.lead_fields.timeline && (
						<select
							value={formData.timeline}
							onChange={(e) =>
								updateFormDataForMessage(message, msgIndex, 'timeline', e.target.value)
							}
							style={{
								padding: '0.45rem 0.75rem',
								borderRadius: '6px',
								border: '1px solid #cbd5e1',
								fontSize: '0.85rem',
								background: '#ffffff',
							}}
						>
							<option value="">
								{__(
									'-- Purchase Timeline --',
									'dragwyb-click-to-chat'
								)}
							</option>
							<option value="Immediate / ASAP">
								{__(
									'Immediate / ASAP',
									'dragwyb-click-to-chat'
								)}
							</option>
							<option value="Within 1 Month">
								{__(
									'Within 1 Month',
									'dragwyb-click-to-chat'
								)}
							</option>
							<option value="1 - 3 Months">
								{__(
									'1 - 3 Months',
									'dragwyb-click-to-chat'
								)}
							</option>
							<option value="Just Exploring">
								{__(
									'Just Exploring',
									'dragwyb-click-to-chat'
								)}
							</option>
						</select>
					)}
					{chatbot.lead_fields && chatbot.lead_fields.interest && (
						<input
							type="text"
							placeholder={__(
								'Product / Service Interest',
								'dragwyb-click-to-chat'
							)}
							value={formData.interest}
							onChange={(e) =>
								updateFormDataForMessage(message, msgIndex, 'interest', e.target.value)
							}
							style={{
								padding: '0.45rem 0.75rem',
								borderRadius: '6px',
								border: '1px solid #cbd5e1',
								fontSize: '0.85rem',
							}}
						/>
					)}
					{(!chatbot.lead_fields || chatbot.lead_fields.requirement !== false) && (
						<textarea
							rows={2}
							placeholder={__(
								'Specific Requirements or Questions',
								'dragwyb-click-to-chat'
							)}
							value={formData.requirement}
							onChange={(e) =>
								updateFormDataForMessage(message, msgIndex, 'requirement', e.target.value)
							}
							style={{
								padding: '0.45rem 0.75rem',
								borderRadius: '6px',
								border: '1px solid #cbd5e1',
								fontSize: '0.85rem',
								resize: 'vertical',
							}}
						/>
					)}
					{leadFormError && (
						<span
							style={{
								color: '#ef4444',
								fontSize: '0.8rem',
							}}
						>
							{leadFormError}
						</span>
					)}
					<div
						style={{
							display: 'flex',
							justifyContent: 'flex-end',
							gap: '0.5rem',
							marginTop: '0.3rem',
						}}
					>
						<button
							type="button"
							onClick={() => {
								setClosedFormIndexes((prev) => ({ ...prev, [msgIndex]: true }));
							}}
							style={{
								padding: '0.4rem 0.8rem',
								borderRadius: '6px',
								border: '1px solid #cbd5e1',
								background: '#f8fafc',
								color: '#475569',
								fontSize: '0.85rem',
								cursor: 'pointer',
							}}
						>
							{__('Cancel', 'dragwyb-click-to-chat')}
						</button>
						<button
							type="submit"
							disabled={leadFormSubmitting}
							style={{
								padding: '0.5rem 1rem',
								borderRadius: '6px',
								border: 'none',
								background: cardPrimaryColor,
								color: '#ffffff',
								fontSize: '0.85rem',
								fontWeight: 600,
								cursor: leadFormSubmitting
									? 'not-allowed'
									: 'pointer',
								opacity: leadFormSubmitting ? 0.7 : 1,
							}}
						>
							{leadFormSubmitting
								? __('Submitting...', 'dragwyb-click-to-chat')
								: chatbot.lead_submit_button_text ||
								__('Submit Request', 'dragwyb-click-to-chat')}
						</button>
					</div>
				</form>
			</div>
		);
	};

	const messagesEndRef = useRef(null);
	const inputRef = useRef(null);
	const fileInputRef = useRef(null);
	const imageFileInputRef = useRef(null);
	const attachMenuRef = useRef(null);
	const emojiWrapRef = useRef(null);
	const wrapperRef = useRef(null);
	const isMountedRef = useRef(true);
	const baseInputRef = useRef('');
	const attachmentsRef = useRef(attachments);
	const pollSessionRunningRef = useRef(false);
	attachmentsRef.current = attachments;

	const isAnyUploading = attachments.some((a) => a.status === 'uploading');

	useEffect(() => {
		isMountedRef.current = true;
		return () => {
			isMountedRef.current = false;
			attachmentsRef.current.forEach((a) => {
				if (a.previewUrl) {
					URL.revokeObjectURL(a.previewUrl);
				}
			});
		};
	}, []);

	// Real-time Session Sync Polling for Live Agent Replies & State
	useEffect(() => {
		if (!isOpen || !sessionId) {
			return;
		}

		let isCancelled = false;

		const pollSession = async () => {
			if ((typeof document !== 'undefined' && document.hidden) || pollSessionRunningRef.current) {
				return;
			}

			try {
				pollSessionRunningRef.current = true;
				const res = await apiFetch({
					path: `/dctc-ai/v1/chat/sync?session_id=${encodeURIComponent(sessionId)}`,
					method: 'GET',
				});

				if (isCancelled || !res || !res.success) {
					return;
				}

				// Check whether a valid support ticket exists
				const ticketExists = Boolean(res.ticket && res.ticket.id);
				setHasActiveTicket(ticketExists);

				if (ticketExists && res.ticket) {
					if (res.ticket.agent_name) {
						setAssignedAgentName(res.ticket.agent_name);
					}
				}

				const currentControlMode = res.control_mode || 'ai';
				setActiveControlMode(currentControlMode);

				if (Array.isArray(res.messages)) {
					const rawServerMsgs = res.messages.filter(
						(m) => m && m.role !== 'system' && m.sender_type !== 'system'
					);

					// Deduplicate consecutive identical customer messages
					const validServerMsgs = [];
					for (let i = 0; i < rawServerMsgs.length; i++) {
						const curr = rawServerMsgs[i];
						const prev = validServerMsgs[validServerMsgs.length - 1];
						if (
							prev &&
							(curr.sender_type === 'customer' || curr.role === 'user') &&
							(prev.sender_type === 'customer' || prev.role === 'user') &&
							curr.content === prev.content
						) {
							continue;
						}
						validServerMsgs.push(curr);
					}

					if (validServerMsgs.length > 0) {
						const lastMsg = validServerMsgs[validServerMsgs.length - 1];
						const isCustomerLastMsg = lastMsg && (lastMsg.sender_type === 'customer' || lastMsg.role === 'user');
						const hasAgentOrBotReply = lastMsg && (lastMsg.sender_type === 'agent' || lastMsg.role === 'assistant' || lastMsg.role === 'bot');

						const isAiResponding = Boolean(aiResponseWaiting.current || res.ai_response_waiting || res.ai_response);

						if (currentControlMode === 'human' && isCustomerLastMsg) {
							// In human control mode waiting for human agent reply: keep typing dots & waiting text alive
							setIsLoading(true);

							if (!wait30sTimerRef.current) {
								const waitingMsg = chatbot.human_agent_waiting_message || __('Sorry to keep you waiting...', 'dragwyb-click-to-chat');
								wait30sTimerRef.current = setTimeout(() => {
									if (isMountedRef.current) {
										setWaitingAgentStatusText(waitingMsg);
									}
								}, 30000);
							}

							if (!maxWaitTimeoutRef.current) {
								const maxWaitSec = parseInt(chatbot.human_agent_max_wait_time, 10) || 60;
								maxWaitTimeoutRef.current = setTimeout(() => {
									if (isMountedRef.current) {
										triggerAiFallback(lastMsg.content || '');
									}
								}, Math.max(10, maxWaitSec) * 1000);
							}
						} else if (isAiResponding) {
							// Keep loading active while AI response is waiting/generating
							setIsLoading(true);
						} else if (hasAgentOrBotReply) {
							clearAgentWaitTimers();
							setWaitingAgentStatusText('');
							setIsLoading(false);
						} else if (currentControlMode === 'ai' && isCustomerLastMsg && maxWaitTimeoutRef.current && !aiResponseWaiting.current) {
							clearAgentWaitTimers();
							triggerAiFallback(lastMsg.content || '');
						}

						setMessages((prev) => {
							// If AI response is in progress, only merge/update if a new non-AI message (e.g. from human agent or user) was added
							if (isAiResponding) {
								const serverNonAiMsgs = validServerMsgs.filter(
									(m) => m && m.sender_type !== 'ai_agent' && m.sender_type !== 'bot' && m.role !== 'assistant'
								);
								const prevNonAiMsgs = (prev || []).filter(
									(m) => m && m.sender_type !== 'ai_agent' && m.sender_type !== 'bot' && m.role !== 'assistant'
								);

								if (serverNonAiMsgs.length <= prevNonAiMsgs.length) {
									return prev;
								}
							}

							const formattedMsgs = validServerMsgs.map((m, idx) => {
								const rawShowForm = m.show_form || (m.form_type ? { form_type: (typeof m.form_type === 'string' ? m.form_type : 'lead_generate'), show: !m.form_filled } : null);
								const isLeadPromptText = typeof m.content === 'string' && (m.content.toLowerCase().includes('form below') || m.content.toLowerCase().includes('fill out the form'));
								const isOrderTrackPromptText = typeof m.content === 'string' && (
									m.content.toLowerCase().includes('track your order') ||
									m.content.toLowerCase().includes('order id below') ||
									m.content.toLowerCase().includes('live details for order')
								);

								let showFormObj = null;
								if (rawShowForm) {
									showFormObj = {
										form_type: rawShowForm.form_type || 'lead_generate',
										show: rawShowForm.show === true || rawShowForm.show === 'true' || rawShowForm.show === 1,
										form_filled: Boolean(rawShowForm.form_filled),
									};
								} else if (isLeadPromptText && !m.form_filled) {
									showFormObj = { form_type: 'lead_generate', show: true, form_filled: false };
								} else if (isOrderTrackPromptText && !m.form_filled) {
									showFormObj = { form_type: 'order_track', show: true, form_filled: false };
								}

								const isOrderTracker = Boolean(
									m.show_order_tracker ||
									(showFormObj && showFormObj.form_type === 'order_track' && showFormObj.show)
								);

								return {
									id: m.id || `srv_${idx}_${m.created_at || idx}`,
									role: m.sender_type === 'agent' ? 'bot' : (m.role === 'assistant' ? 'bot' : (m.role === 'user' ? 'user' : 'bot')),
									content: m.content || '',
									sender_type: m.sender_type || (m.role === 'assistant' ? 'bot' : 'customer'),
									sender_name: m.sender_name || (m.sender_type === 'agent' ? (res.ticket?.agent_name || __('Support Agent', 'dragwyb-click-to-chat')) : ''),
									is_agent: m.sender_type === 'agent',
									created_at: m.created_at || '',
									sources: m.sources || [],
									show_form: showFormObj,
									show_order_tracker: isOrderTracker,
									form_type: Boolean(showFormObj && showFormObj.show),
									form_filled: Boolean(m.form_filled || (showFormObj && !showFormObj.show)),
								};
							});

							// Identify index of latest active order tracker so previous messages set show to false
							let latestOrderTrackerIdx = -1;
							for (let i = 0; i < formattedMsgs.length; i++) {
								if (formattedMsgs[i].show_order_tracker || (formattedMsgs[i].show_form && formattedMsgs[i].show_form.form_type === 'order_track' && formattedMsgs[i].show_form.show)) {
									latestOrderTrackerIdx = i;
								}
							}

							if (latestOrderTrackerIdx >= 0) {
								for (let i = 0; i < latestOrderTrackerIdx; i++) {
									if (formattedMsgs[i].show_order_tracker || (formattedMsgs[i].show_form && formattedMsgs[i].show_form.form_type === 'order_track')) {
										formattedMsgs[i].show_order_tracker = false;
										if (formattedMsgs[i].show_form) {
											formattedMsgs[i].show_form = { ...formattedMsgs[i].show_form, show: false };
										}
									}
								}
							}

							let mergedMsgs = [...formattedMsgs];

							// Preserve any trailing or in-flight local messages in prev not yet returned by server
							if (prev && prev.length > 0) {
								for (let pIdx = 0; pIdx < prev.length; pIdx++) {
									const pMsg = prev[pIdx];
									if (pMsg.role === 'user') {
										const countInFormatted = formattedMsgs.filter(m => m.role === 'user' && m.content === pMsg.content).length;
										const countInPrevSoFar = prev.slice(0, pIdx + 1).filter(m => m.role === 'user' && m.content === pMsg.content).length;
										if (countInPrevSoFar > countInFormatted) {
											// mergedMsgs.push(pMsg);
										}
									} else if (pMsg.role === 'error') {
										// mergedMsgs.push(pMsg);
									}
								}

								// Preserve rich UI metadata (sources, action_buttons, products, attachments, show_order_tracker, show_form)
								mergedMsgs = mergedMsgs.map((m, mIdx) => {
									const matchingPrev = prev.find(p => p.content === m.content && p.role === m.role);
									if (matchingPrev) {
										const prevHasOrderTracker = Boolean(
											matchingPrev.show_order_tracker ||
											(matchingPrev.show_form && matchingPrev.show_form.form_type === 'order_track' && matchingPrev.show_form.show !== false)
										);
										const isLaterTrackerPresent = latestOrderTrackerIdx > mIdx;
										const effectiveShowOrderTracker = isLaterTrackerPresent
											? false
											: (m.show_order_tracker !== undefined && m.show_order_tracker !== false
												? m.show_order_tracker
												: prevHasOrderTracker);

										let effectiveShowForm = m.show_form;
										if (!effectiveShowForm && matchingPrev.show_form) {
											effectiveShowForm = matchingPrev.show_form;
										} else if (!effectiveShowForm && prevHasOrderTracker) {
											effectiveShowForm = { form_type: 'order_track', show: !isLaterTrackerPresent, form_filled: false };
										}
										if (isLaterTrackerPresent && effectiveShowForm && effectiveShowForm.form_type === 'order_track') {
											effectiveShowForm = { ...effectiveShowForm, show: false };
										}

										return {
											...m,
											sources: m.sources && m.sources.length > 0 ? m.sources : (matchingPrev.sources || []),
											action_buttons: matchingPrev.action_buttons || [],
											products: matchingPrev.products || [],
											attachments: matchingPrev.attachments || [],
											show_order_tracker: effectiveShowOrderTracker,
											show_form: effectiveShowForm,
											form_type: m.form_type !== undefined ? m.form_type : (matchingPrev.form_type || false),
											form_filled: m.form_filled !== undefined ? m.form_filled : (matchingPrev.form_filled || false),
										};
									}
									return m;
								});
							}

							if (
								prev.length === mergedMsgs.length &&
								prev.every((p, i) =>
									p.content === mergedMsgs[i].content &&
									p.role === mergedMsgs[i].role &&
									p.is_agent === mergedMsgs[i].is_agent &&
									JSON.stringify(p.show_form) === JSON.stringify(mergedMsgs[i].show_form) &&
									p.form_type === mergedMsgs[i].form_type &&
									p.form_filled === mergedMsgs[i].form_filled
								)
							) {
								return prev;
							}


							return mergedMsgs;
						});
					}
				}
			} catch (err) {
				// Silently catch background poll errors
			}

			pollSessionRunningRef.current = false;
		};

		// Initial check on load/open
		pollSession();

		// Only start periodic polling interval if an active ticket exists
		let interval = null;
		if (hasActiveTicket) {
			interval = setInterval(pollSession, 3500);
		}

		return () => {
			isCancelled = true;
			if (interval) {
				clearInterval(interval);
			}
		};
	}, [isOpen, sessionId, hasActiveTicket, triggerAiFallback, clearAgentWaitTimers, chatbot.human_agent_waiting_message, chatbot.human_agent_max_wait_time]);

	// Click outside to close attachment menu
	useEffect(() => {
		const handleAttachMenuOutside = (e) => {
			if (
				attachMenuRef.current &&
				!attachMenuRef.current.contains(e.target)
			) {
				setIsAttachMenuOpen(false);
			}
		};

		if (isAttachMenuOpen) {
			document.addEventListener('mousedown', handleAttachMenuOutside);
			document.addEventListener('touchstart', handleAttachMenuOutside);
		}

		return () => {
			document.removeEventListener('mousedown', handleAttachMenuOutside);
			document.removeEventListener('touchstart', handleAttachMenuOutside);
		};
	}, [isAttachMenuOpen]);

	// Click outside to close emoji picker
	useEffect(() => {
		const handleEmojiOutside = (e) => {
			if (
				emojiWrapRef.current &&
				!emojiWrapRef.current.contains(e.target)
			) {
				setIsEmojiOpen(false);
			}
		};

		if (isEmojiOpen) {
			document.addEventListener('mousedown', handleEmojiOutside);
			document.addEventListener('touchstart', handleEmojiOutside);
		}

		return () => {
			document.removeEventListener('mousedown', handleEmojiOutside);
			document.removeEventListener('touchstart', handleEmojiOutside);
		};
	}, [isEmojiOpen]);

	// Voice recognition and TTS cleanup on unmount
	useEffect(() => {
		return () => {
			if (recognitionRef.current) {
				try {
					recognitionRef.current.stop();
				} catch (e) { }
			}
			if (typeof window !== 'undefined' && window.speechSynthesis) {
				window.speechSynthesis.cancel();
			}
		};
	}, []);

	const toggleVoiceRecognition = useCallback(() => {
		const SpeechRecognition =
			window.SpeechRecognition || window.webkitSpeechRecognition;

		if (!SpeechRecognition) {
			setUploadError(
				__(
					'Voice input is not supported in this browser.',
					'dragwyb-click-to-chat'
				)
			);
			setTimeout(() => setUploadError(''), 4000);
			return;
		}

		if (isListening && recognitionRef.current) {
			try {
				recognitionRef.current.stop();
			} catch (e) { }
			setIsListening(false);
			return;
		}

		try {
			const recognition = new SpeechRecognition();
			recognition.continuous = false;
			recognition.interimResults = true;
			const voiceLang =
				chatbot?.voice_language && chatbot.voice_language !== 'auto'
					? chatbot.voice_language
					: (navigator.language || 'en-US');
			recognition.lang = voiceLang;

			recognition.onstart = () => {
				setIsListening(true);
				baseInputRef.current = input.trim();
			};

			recognition.onresult = (event) => {
				let transcript = '';
				for (let i = 0; i < event.results.length; i++) {
					transcript += event.results[i][0].transcript;
				}
				const cleanTranscript = transcript.trim();
				if (cleanTranscript) {
					const base = baseInputRef.current;
					setInput(base ? `${base} ${cleanTranscript}` : cleanTranscript);
				}
			};

			recognition.onerror = () => {
				setIsListening(false);
			};

			recognition.onend = () => {
				setIsListening(false);
			};

			recognitionRef.current = recognition;
			recognition.start();
		} catch (err) {
			setIsListening(false);
		}
	}, [isListening, input, chatbot?.voice_language]);

	const toggleSpeakMessage = useCallback((text, msgIdx) => {
		if (typeof window === 'undefined' || !window.speechSynthesis) {
			return;
		}

		if (speakingIndex === msgIdx) {
			window.speechSynthesis.cancel();
			setSpeakingIndex(null);
			return;
		}

		window.speechSynthesis.cancel();
		const cleanText = (text || '').replace(/[*_#`[\]()]/g, '');
		const utterance = new SpeechSynthesisUtterance(cleanText);
		if (chatbot?.voice_language && chatbot.voice_language !== 'auto') {
			utterance.lang = chatbot.voice_language;
		}
		utterance.onend = () => {
			setSpeakingIndex(null);
		};
		utterance.onerror = () => {
			setSpeakingIndex(null);
		};
		setSpeakingIndex(msgIdx);
		window.speechSynthesis.speak(utterance);
	}, [speakingIndex, chatbot?.voice_language]);

	// Click / tap outside closes floating chat.
	useEffect(() => {
		const handleOutside = (event) => {
			if (
				wrapperRef.current &&
				!wrapperRef.current.contains(event.target)
			) {
				setIsOpen(false);
			}
		};

		if (isOpen && !inline) {
			document.addEventListener('mousedown', handleOutside);
			document.addEventListener('touchstart', handleOutside);
		}

		return () => {
			document.removeEventListener('mousedown', handleOutside);
			document.removeEventListener('touchstart', handleOutside);
		};
	}, [isOpen, inline]);

	const fireProactiveTrigger = () => {
		try {
			if (window.sessionStorage && window.sessionStorage.getItem('dctc_ai_trigger_fired')) {
				return;
			}
			if (window.sessionStorage) {
				window.sessionStorage.setItem('dctc_ai_trigger_fired', '1');
			}
		} catch (e) {
			// ignore storage quota/security restrictions
		}

		if (display.trigger_action === 'open_chat') {
			setIsOpen(true);
		} else if (display.enable_proactive_bubble) {
			setProactiveBubbleOpen(true);
		}
	};

	// Auto-open full chat panel window after page load delay when configured
	useEffect(() => {
		if (display.trigger_type === 'delay' && !inline && !isOpen) {
			try {
				if (window.sessionStorage && window.sessionStorage.getItem('dctc_ai_auto_opened')) {
					return;
				}
			} catch (e) {
				// ignore
			}

			const ms = 1000 * (parseInt(display.trigger_delay, 10) || 5);
			const timer = setTimeout(() => {
				try {
					if (window.sessionStorage) {
						window.sessionStorage.setItem('dctc_ai_auto_opened', '1');
					}
				} catch (e) {
					// ignore
				}
				setIsOpen(true);
			}, ms);
			return () => clearTimeout(timer);
		}
	}, [
		display.trigger_type,
		display.trigger_delay,
		inline,
		isOpen,
	]);

	// Show Proactive Teaser Bubble on page load if enabled (and chat window is not configured to auto-open)
	useEffect(() => {
		if (
			display.enable_proactive_bubble &&
			display.trigger_type !== 'delay' &&
			!inline &&
			!isOpen &&
			!proactiveBubbleOpen
		) {
			try {
				if (window.sessionStorage && window.sessionStorage.getItem('dctc_ai_bubble_dismissed')) {
					return;
				}
			} catch (e) {
				// ignore
			}

			const timer = setTimeout(() => {
				setProactiveBubbleOpen(true);
			}, 3000);
			return () => clearTimeout(timer);
		}
	}, [
		display.enable_proactive_bubble,
		display.trigger_type,
		inline,
		isOpen,
		proactiveBubbleOpen,
	]);

	// Smart Behavioral Trigger: Scroll Depth
	useEffect(() => {
		if (!display.enable_smart_triggers || inline || isOpen || proactiveBubbleOpen) {
			return;
		}
		const targetDepth = parseInt(display.trigger_scroll_depth, 10) || 50;
		const handleScroll = () => {
			const docH = document.documentElement.scrollHeight - window.innerHeight;
			if (docH <= 0) {
				return;
			}
			const scrollPct = (window.scrollY / docH) * 100;
			if (scrollPct >= targetDepth) {
				fireProactiveTrigger();
			}
		};
		window.addEventListener('scroll', handleScroll, { passive: true });
		return () => window.removeEventListener('scroll', handleScroll);
	}, [
		display.enable_smart_triggers,
		display.trigger_scroll_depth,
		display.trigger_action,
		inline,
		isOpen,
		proactiveBubbleOpen,
	]);

	// Smart Behavioral Trigger: Exit Intent (Desktop)
	useEffect(() => {
		if (!display.enable_smart_triggers || !display.trigger_exit_intent || inline || isOpen || proactiveBubbleOpen) {
			return;
		}
		const handleMouseLeave = (e) => {
			if (e.clientY <= 15) {
				fireProactiveTrigger();
			}
		};
		document.documentElement.addEventListener('mouseleave', handleMouseLeave);
		return () => document.documentElement.removeEventListener('mouseleave', handleMouseLeave);
	}, [
		display.enable_smart_triggers,
		display.trigger_exit_intent,
		display.trigger_action,
		inline,
		isOpen,
		proactiveBubbleOpen,
	]);

	// Smart Behavioral Trigger: User Inactivity
	useEffect(() => {
		if (!display.enable_smart_triggers || inline || isOpen || proactiveBubbleOpen) {
			return;
		}
		const idleSec = parseInt(display.trigger_inactivity, 10) || 30;
		let idleTimer = setTimeout(() => {
			fireProactiveTrigger();
		}, idleSec * 1000);

		const resetIdle = () => {
			clearTimeout(idleTimer);
			idleTimer = setTimeout(() => {
				fireProactiveTrigger();
			}, idleSec * 1000);
		};

		const events = ['mousemove', 'keydown', 'touchstart', 'scroll'];
		events.forEach((evt) => window.addEventListener(evt, resetIdle, { passive: true }));
		return () => {
			clearTimeout(idleTimer);
			events.forEach((evt) => window.removeEventListener(evt, resetIdle));
		};
	}, [
		display.enable_smart_triggers,
		display.trigger_inactivity,
		display.trigger_action,
		inline,
		isOpen,
		proactiveBubbleOpen,
	]);

	// Delay launcher appearance after page load.
	useEffect(() => {
		if (inline) {
			setLauncherVisible(true);
			return;
		}
		const delay = parseInt(display.time_delay, 10) || 0;
		if (delay <= 0) {
			setLauncherVisible(true);
			return;
		}
		setLauncherVisible(false);
		const timer = setTimeout(() => {
			setLauncherVisible(true);
		}, delay * 1000);
		return () => clearTimeout(timer);
	}, [display.time_delay, inline]);

	useEffect(() => {
		if (messagesEndRef.current) {
			messagesEndRef.current.scrollIntoView({ behavior: 'smooth' });
		}
	}, [messages, isLoading, attachments]);



	const toggleOpen = () => setIsOpen((open) => !open);

	const needsEmail = !!chatbot.save_chat && !!chatbot.ask_email;

	const handleFilesSelected = (e) => {
		const rawFiles = Array.from(e.target.files || []);
		if (fileInputRef.current) {
			fileInputRef.current.value = '';
		}
		if (imageFileInputRef.current) {
			imageFileInputRef.current.value = '';
		}
		if (rawFiles.length === 0) {
			return;
		}

		setUploadError('');

		if (attachments.length + rawFiles.length > maxFilesPerMsg) {
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

		for (const file of rawFiles) {
			if (file.size > maxBytes) {
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

			const ext = (file.name.split('.').pop() || '').toLowerCase();
			if (excludedExts.includes(ext)) {
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

			if (allowedExts.length > 0 && !allowedExts.includes(ext)) {
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

			const id = 'att_' + Math.random().toString(36).substr(2, 9);
			const isImg =
				file.type.startsWith('image/') ||
				['jpg', 'jpeg', 'png', 'webp', 'gif'].includes(ext);
			const previewUrl = isImg ? URL.createObjectURL(file) : '';

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

			newAttachments.push(attObj);

			// Trigger upload
			const formData = new FormData();
			formData.append('file', file);

			apiFetch({
				path: '/dctc-ai/v1/upload',
				method: 'POST',
				body: formData,
			})
				.then((res) => {
					if (!isMountedRef.current) {
						return;
					}
					const data = res?.attachment || res;
					setAttachments((prev) =>
						prev.map((a) =>
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
				})
				.catch((err) => {
					if (!isMountedRef.current) {
						return;
					}
					setAttachments((prev) =>
						prev.map((a) =>
							a.id === id
								? {
									...a,
									status: 'failed',
									error:
										err?.message ||
										__('Upload failed', 'dragwyb-click-to-chat'),
								}
								: a
						)
					);
				});
		}

		if (newAttachments.length > 0) {
			setAttachments((prev) => [...prev, ...newAttachments]);
		}
	};

	const removeAttachment = (id) => {
		setAttachments((prev) => {
			const item = prev.find((a) => a.id === id);
			if (item && item.previewUrl) {
				URL.revokeObjectURL(item.previewUrl);
			}
			return prev.filter((a) => a.id !== id);
		});
	};

	const handleSelectEmoji = (emoji) => {
		const textarea = inputRef.current;
		if (!textarea) {
			setInput((prev) => prev + emoji);
			setIsEmojiOpen(false);
			return;
		}

		const start = textarea.selectionStart ?? input.length;
		const end = textarea.selectionEnd ?? input.length;
		const nextValue =
			input.substring(0, start) + emoji + input.substring(end);

		setInput(nextValue);
		setIsEmojiOpen(false);

		setTimeout(() => {
			if (textarea) {
				textarea.focus();
				const newPos = start + emoji.length;
				textarea.setSelectionRange(newPos, newPos);
				textarea.style.height = 'auto';
				textarea.style.height =
					Math.min(textarea.scrollHeight, 150) + 'px';
			}
		}, 10);
	};

	const handleSend = async (event, promptOverride = null) => {
		if (event && event.preventDefault) {
			event.preventDefault();
		}
		if (isLoading) {
			return;
		}

		if (isAnyUploading) {
			setUploadError(
				__(
					'Please wait for attachments to finish uploading.',
					'dragwyb-click-to-chat'
				)
			);
			return;
		}

		let prompt = (promptOverride !== null ? promptOverride : input).trim();
		const readyAttachments = attachments
			.filter((a) => a.status === 'uploaded')
			.map((a) => ({
				id: a.id,
				type: a.type,
				name: a.name,
				size: a.size,
				mime: a.mime,
				url: a.url,
				attachmentId: a.attachmentId,
			}));

		if (!prompt && readyAttachments.length === 0) {
			return;
		}

		// Email gate: stash the first prompt until email is collected.
		if (needsEmail && !email) {
			setMessages([
				{
					role: 'user',
					content:
						prompt ||
						__('[Attached Files]', 'dragwyb-click-to-chat'),
					attachments: readyAttachments,
				},
			]);
			setPendingPrompt(prompt);
			setInput('');
			attachments.forEach((a) => {
				if (a.previewUrl) {
					URL.revokeObjectURL(a.previewUrl);
				}
			});
			setAttachments([]);
			setUploadError('');
			setIsEmojiOpen(false);
			return;
		}

		// API key check
		if (!hasApiKey) {
			const warningMsg = isAdmin
				? `⚠️ **AI Provider API Key Not Configured**: Please [configure your AI Provider API key](${settingsUrl}) in settings to enable chatbot responses.`
				: __(
					'AI Assistant is currently offline for maintenance. Please check back later.',
					'dragwyb-click-to-chat'
				);
			setMessages((prev) => [
				...prev,
				{
					role: 'user',
					content: prompt,
					attachments: readyAttachments,
				},
				{ role: 'error', content: warningMsg },
			]);
			if (promptOverride === null) {
				setInput('');
			}
			attachments.forEach((a) => {
				if (a.previewUrl) {
					URL.revokeObjectURL(a.previewUrl);
				}
			});
			setAttachments([]);
			setUploadError('');
			setIsEmojiOpen(false);
			return;
		}

		const userCreatedAt = new Date().toISOString().slice(0, 19).replace('T', ' ');
		const userMessageObj = {
			role: 'user',
			sender_type: 'customer',
			sender_name: 'Customer',
			content: prompt,
			attachments: readyAttachments,
			created_at: userCreatedAt,
		};

		setMessages((prev) => [...prev, userMessageObj]);

		// Clean up object URLs and reset composer attachment tray
		attachments.forEach((a) => {
			if (a.previewUrl) {
				URL.revokeObjectURL(a.previewUrl);
			}
		});
		setAttachments([]);
		setUploadError('');
		setIsEmojiOpen(false);

		if (fileInputRef.current) {
			fileInputRef.current.value = '';
		}
		if (imageFileInputRef.current) {
			imageFileInputRef.current.value = '';
		}

		if (promptOverride === null) {
			setInput('');
		}
		aiResponseWaiting.current = true;
		setIsLoading(true);

		if (inputRef.current) {
			inputRef.current.style.height = 'auto';
		}

		try {
			const activePageContext = window.dctc_ai_frontend_data?.page_context || {
				url: window.location.href,
				title: document.title,
			};

			const isSaveChatEnabled = !!chatbot.save_chat;
			const ticketAlreadyCreated = Boolean(hasActiveTicket || hasActiveTicketRef.current);
			const chatPayload = {
				prompt: prompt || (readyAttachments.length > 0 ? 'Please analyze the attached file(s).' : ''),
				session_id: sessionId,
				email,
				attachments: readyAttachments,
				page_context: activePageContext,
				visitor_lang: navigator.language || navigator.userLanguage || '',
			};

			if (!isSaveChatEnabled && !ticketAlreadyCreated) {
				chatPayload.messages = formatConversationHistory(
					messagesRef.current,
					chatbot.bot_name
				);
			}

			const response = await apiFetch({
				path: '/dctc-ai/v1/chat',
				method: 'POST',
				data: chatPayload,
			});

			if (!isMountedRef.current) {
				return;
			}

			if (response.success) {
				if (
					response.session_id &&
					response.session_id !== sessionId
				) {
					setSessionId(response.session_id);
				}

				if (response.is_human_handled) {
					// Human control mode active: do NOT show a static text notice
					setIsLoading(true); // Keep typing animation active

					clearAgentWaitTimers();

					// Set 30-second timer to show waiting status message
					const waitingMsg = chatbot.human_agent_waiting_message || __('Sorry to keep you waiting...', 'dragwyb-click-to-chat');
					wait30sTimerRef.current = setTimeout(() => {
						if (isMountedRef.current) {
							setWaitingAgentStatusText(waitingMsg);
						}
					}, 30000);

					// Set maximum wait time timer (default 60s)
					const maxWaitSec = parseInt(chatbot.human_agent_max_wait_time, 10) || 60;
					maxWaitTimeoutRef.current = setTimeout(() => {
						if (isMountedRef.current) {
							triggerAiFallback(prompt);
						}
					}, Math.max(10, maxWaitSec) * 1000);

					if ((response.ticket && response.ticket.id) || response.has_ticket) {
						setHasActiveTicket(true);
						hasActiveTicketRef.current = true;
						if (response.ticket && response.ticket.agent_name) {
							setAssignedAgentName(response.ticket.agent_name);
						}
						if (response.control_mode) {
							setActiveControlMode(response.control_mode);
						}
					}
					return;
				}

				clearAgentWaitTimers();
				setWaitingAgentStatusText('');

				let botMessage = response.message;
				if (
					typeof botMessage === 'object' &&
					botMessage !== null
				) {
					botMessage =
						botMessage.text ||
						botMessage.content ||
						JSON.stringify(botMessage);
				}
				const responseSources = Array.isArray(response.sources)
					? response.sources
					: (Array.isArray(response.reference_links) ? response.reference_links : []);
				const responseActionButtons = Array.isArray(response.action_buttons)
					? response.action_buttons
					: [];
				const responseProducts = Array.isArray(response.products)
					? response.products
					: [];

				const isLeadPrompt = Boolean(
					typeof botMessage === 'string' && (botMessage.toLowerCase().includes('form below') || botMessage.toLowerCase().includes('fill out the form'))
				);

				const botCreatedAt = new Date().toISOString().slice(0, 19).replace('T', ' ');
				const botSenderName = 'AI Assistant';

				if (response.lead_data && isLeadPrompt) {
					const formKey = `${botCreatedAt}_${botSenderName}`;
					setFormsDataByKey((prev) => ({
						...prev,
						[formKey]: {
							name: response.lead_data.name || loggedInUserName,
							email: response.lead_data.email || loggedInUserEmail,
							phone: response.lead_data.phone || '',
							company: response.lead_data.company || '',
							company_size: '',
							budget: '',
							timeline: '',
							interest: response.lead_data.interest || pageProductOrTitle,
							requirement: response.lead_data.requirement || '',
						},
					}));
				}

				const isOrderTrackerPrompt = Boolean(
					response.show_order_tracker || (response.show_form?.form_type === 'order_track' && response.show_form?.show !== false)
				);

				setMessages((prev) => [
					...prev.map((m) => {
						let updated = m;
						if (isLeadPrompt && updated.show_form && updated.show_form.show && !updated.show_form.form_filled) {
							updated = {
								...updated,
								show_form: { ...updated.show_form, show: false },
							};
						}
						if (isOrderTrackerPrompt && (updated.show_order_tracker || (updated.show_form && updated.show_form.form_type === 'order_track' && updated.show_form.show))) {
							updated = {
								...updated,
								show_order_tracker: false,
								show_form: updated.show_form ? { ...updated.show_form, show: false } : { form_type: 'order_track', show: false, form_filled: false },
							};
						}
						return updated;
					}),
					{
						role: 'bot',
						sender_type: 'ai_agent',
						sender_name: botSenderName,
						created_at: botCreatedAt,
						content: botMessage,
						sources: responseSources,
						action_buttons: responseActionButtons,
						products: responseProducts,
						show_order_tracker: isOrderTrackerPrompt,
						show_form: isOrderTrackerPrompt
							? { form_type: 'order_track', show: true, form_filled: false }
							: (isLeadPrompt ? { form_type: 'lead_generate', show: true, form_filled: false } : (response.show_form || null)),
					},
				]);

				if ((response.ticket && response.ticket.id) || response.has_ticket) {
					setHasActiveTicket(true);
					hasActiveTicketRef.current = true;
					if (response.ticket && response.ticket.agent_name) {
						setAssignedAgentName(response.ticket.agent_name);
					}
					if (response.control_mode) {
						setActiveControlMode(response.control_mode);
					}
				}

				if (response.show_order_tracker) {
					if (response.order_lookup_data) {
						setOrderLookupResult(response.order_lookup_data);
						if (response.order_lookup_data.order_id) {
							setOrderLookupId(response.order_lookup_data.order_id);
						}
					}
				}
			} else {
				setMessages((prev) => [
					...prev,
					{ role: 'error', content: response.message || getErrorMessage() },
				]);
			}
		} catch (err) {
			if (isMountedRef.current) {
				const errMsg =
					(err && (err.message || err.error)) ||
					getErrorMessage();
				setMessages((prev) => [
					...prev,
					{ role: 'error', content: errMsg },
				]);
			}
		} finally {
			aiResponseWaiting.current = false;
			if (isMountedRef.current) {
				setIsLoading(false);
			}
		}
	};

	const handleClear = async () => {
		try {
			const response = await apiFetch({
				path: '/dctc-ai/v1/clear-session',
				method: 'POST',
			});

			if (response.success && response.session_id) {
				setMessages([]);
				setSessionId(response.session_id);
				setEmail('');
				setEmailDraft('');
				setPendingPrompt('');
				setClearAllowed(true);
				setActiveControlMode('ai');
				setAssignedAgentName('');
				setHasActiveTicket(false);
				setWaitingAgentStatusText('');
				clearAgentWaitTimers();
			}
		} catch (err) {
			setMessages([]);
			setEmail('');
			setEmailDraft('');
			setPendingPrompt('');
			const newId =
				'sess_' + Math.random().toString(36).substr(2, 9);
			setSessionId(newId);
			setClearAllowed(true);
			setActiveControlMode('ai');
			setAssignedAgentName('');
			setHasActiveTicket(false);
			setWaitingAgentStatusText('');
			clearAgentWaitTimers();
		}
	};

	const handleEmailSubmit = async (event) => {
		if (event && event.preventDefault) {
			event.preventDefault();
		}

		const value = emailDraft.trim();
		if (!value) {
			setEmailError(
				__('Please enter your email.', 'dragwyb-click-to-chat')
			);
			return;
		}

		const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
		if (!emailRegex.test(value)) {
			setEmailError(
				__(
					'Please enter a valid email address.',
					'dragwyb-click-to-chat'
				)
			);
			return;
		}

		setEmail(value);
		setEmailError('');

		if (pendingPrompt) {
			const promptToSend = pendingPrompt;
			setPendingPrompt('');
			handleSend(null, promptToSend);
		}
	};

	const handleLeadSubmit = async (e, targetMessage = null, msgIndex = null) => {
		if (e && e.preventDefault) {
			e.preventDefault();
		}
		setLeadFormError('');

		const currentFormData = targetMessage ? getFormDataForMessage(targetMessage, msgIndex) : {
			name: loggedInUserName,
			email: loggedInUserEmail,
			phone: '',
			company: '',
			company_size: '',
			budget: '',
			timeline: '',
			interest: pageProductOrTitle,
			requirement: '',
		};

		if (
			!currentFormData.name &&
			!currentFormData.email &&
			!currentFormData.phone
		) {
			setLeadFormError(
				__(
					'Please provide at least a name, email or phone number.',
					'dragwyb-click-to-chat'
				)
			);
			return;
		}

		if (
			currentFormData.email &&
			! /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(currentFormData.email)
		) {
			setLeadFormError(
				__(
					'Please enter a valid email address.',
					'dragwyb-click-to-chat'
				)
			);
			return;
		}

		setLeadFormSubmitting(true);
		try {
			const res = await apiFetch({
				path: '/dctc-ai/v1/leads/capture',
				method: 'POST',
				data: {
					...currentFormData,
					session_id: sessionId,
					source_url: window.location.href,
				},
			});

			if (res && res.success) {
				setLeadFormSubmitted(true);
				setMessages((prev) => [
					...prev.map((m) => ({
						...m,
						show_form: m.show_form ? { ...m.show_form, show: false, form_filled: true } : null,
					})),
					{
						role: 'bot',
						sender_type: 'ai_agent',
						sender_name: botName || 'AI Assistant',
						created_at: new Date().toISOString().slice(0, 19).replace('T', ' '),
						content:
							res.message ||
							__(
								'Thank you! Your information has been received. Our team will contact you shortly.',
								'dragwyb-click-to-chat'
							),
					},
				]);
			} else {
				setLeadFormError(
					res?.message ||
					__(
						'Failed to submit form. Please try again.',
						'dragwyb-click-to-chat'
					)
				);
			}
		} catch (err) {
			setLeadFormError(
				err?.message ||
				__(
					'Error submitting contact info. Please try again.',
					'dragwyb-click-to-chat'
				)
			);
		} finally {
			setLeadFormSubmitting(false);
		}
	};

	const primaryColor = chatbot.primary_color || '#6366f1';
	const botAvatar = chatbot.bot_avatar || '';
	const botPreset = chatbot.bot_icon_preset || 'bot';
	const userAvatar = chatbot.user_avatar || '';
	const userPreset = chatbot.user_icon_preset || 'user';
	const botName = chatbot.bot_name || __('AI Assistant', 'dragwyb-click-to-chat');
	const bubbleStyle = chatbot.bubble_style || 'rounded';
	const bubbleClass = `dctc-ai-bubble-${bubbleStyle}`;
	const assistantIcon = display.assistant_icon || '';
	const launcherPreset = display.launcher_icon_preset || 'chat';
	const showEmailGate = needsEmail && !email && messages.length > 0;
	const showWindow = isOpen || inline;
	const launcherSize = `${display.widget_size || 64}${display.widget_size_unit || 'px'
		}`;
	const isCustomPosition = !inline && display.position === 'custom';
	const customSide = display.custom_side === 'left' ? 'left' : 'right';
	const customVert =
		display.custom_vertical_align === 'top' ? 'top' : 'bottom';
	const floatingClass = inline
		? 'dctc-ai-chat-inline'
		: isCustomPosition
			? `dctc-ai-chat-floating custom custom-${customSide} custom-${customVert}`
			: `dctc-ai-chat-floating ${display.position || 'bottom-right'}`;
	const userMsgBg = chatbot.user_msg_bg_color || primaryColor;
	const userMsgText = chatbot.user_msg_text_color || '#ffffff';
	const botMsgBg = chatbot.bot_msg_bg_color || '#ffffff';
	const botMsgText = chatbot.bot_msg_text_color || '#0f172a';
	const containerRadius = `${chatbot.container_border_radius ?? 24}px`;
	const containerWidth = chatbot.container_width ? `${chatbot.container_width}${chatbot.container_width_unit || 'px'}` : '380px';
	const containerHeight = chatbot.container_height ? `${chatbot.container_height}${chatbot.container_height_unit || 'px'}` : '520px';
	const containerBorder = `${chatbot.container_border_width ?? 1}px ${chatbot.container_border_style || 'solid'} ${chatbot.container_border_color || 'hsla(0, 0%, 100%, 0.5)'}`;
	const chatBgImage = chatbot.chat_bg_image ? `url(${chatbot.chat_bg_image})` : 'none';

	const wrapperStyle = {
		'--dctc-ai-primary': primaryColor,
		'--dctc-ai-launcher-size': launcherSize,
		'--dctc-ai-user-msg-bg': userMsgBg,
		'--dctc-ai-user-msg-color': userMsgText,
		'--dctc-ai-bot-msg-bg': botMsgBg,
		'--dctc-ai-bot-msg-color': botMsgText,
		'--dctc-ai-container-radius': containerRadius,
		'--dctc-ai-container-width': containerWidth,
		'--dctc-ai-container-height': containerHeight,
		'--dctc-ai-container-border': containerBorder,
		'--dctc-ai-chat-bg-image': chatBgImage,
	};
	if (isCustomPosition) {
		const vertDist = `${display.custom_vertical ?? 24}${display.custom_vertical_unit || 'px'
			}`;
		const horizDist = `${display.custom_horizontal ?? 24}${display.custom_horizontal_unit || 'px'
			}`;
		wrapperStyle.top = customVert === 'top' ? vertDist : 'auto';
		wrapperStyle.bottom = customVert === 'bottom' ? vertDist : 'auto';
		wrapperStyle.left = customSide === 'left' ? horizDist : 'auto';
		wrapperStyle.right = customSide === 'right' ? horizDist : 'auto';
	}

	const markdownComponents = {
		a: ({ node, ...props }) => (
			<a
				{...props}
				target="_blank"
				rel="noopener noreferrer"
			/>
		),
		p: ({ node, ...props }) => <p {...props} />,
		h3: ({ node, ...props }) => <h3 {...props} />,
		strong: ({ node, ...props }) => <strong {...props} />,
		em: ({ node, ...props }) => <em {...props} />,
		ul: ({ node, ...props }) => <ul {...props} />,
		li: ({ node, ...props }) => <li {...props} />,
		hr: ({ node, ...props }) => <hr {...props} />,
	};

	return (
		<div
			ref={wrapperRef}
			className={'dctc-ai-chat-wrapper ' + floatingClass}
			style={wrapperStyle}
		>
			{showWindow && (
				<div
					id="dctc-ai-chat-window"
					className={`dctc-ai-chat-window ${bubbleClass}`}
					style={{ display: 'flex' }}
				>
					{/* Header */}
					<div className="dctc-ai-chat-header">
						<div className="dctc-ai-chat-avatar">
							{renderBotAvatar(botPreset, botAvatar, botName)}
						</div>
						<div className="dctc-ai-chat-info">
							<strong>{botName}</strong>
							<span className="dctc-ai-status-indicator">
								<span
									className="dctc-ai-status-dot"
									style={activeControlMode === 'human' ? { background: '#10B981' } : {}}
								/>
								{activeControlMode === 'human'
									? (assignedAgentName ? `${assignedAgentName} (Live)` : __('Live Agent', 'dragwyb-click-to-chat'))
									: __('Online', 'dragwyb-click-to-chat')}
							</span>
						</div>
						{clearAllowed && messages.length > 0 && (
							<button
								className="dctc-ai-chat-clear"
								onClick={handleClear}
								title={__('Clear Conversation', 'dragwyb-click-to-chat')}
								aria-label={__('Clear Conversation', 'dragwyb-click-to-chat')}
							>
								<span
									className="dashicons dashicons-trash"
									aria-hidden="true"
								/>
							</button>
						)}
						{!inline && (
							<button
								className="dctc-ai-chat-close"
								onClick={toggleOpen}
								title={__('Close', 'dragwyb-click-to-chat')}
								aria-label={__('Close chat window', 'dragwyb-click-to-chat')}
							>
								<span
									className="dashicons dashicons-no-alt"
									aria-hidden="true"
								/>
							</button>
						)}
					</div>

					{/* Messages Body */}
					<div
						className="dctc-ai-chat-messages"
						id="dctc-ai-messages"
					>
						{/* Default Greeting */}
						{chatbot.greeting_msg &&
							messages.length === 0 &&
							suggestedQuestions.length === 0 && (
								<div className="dctc-ai-message-row dctc-ai-message-row--bot">
									{showBotAvatarInChat && (
										<div className="dctc-ai-msg-avatar dctc-ai-msg-avatar--bot">
											{renderBotAvatar(botPreset, botAvatar, botName)}
										</div>
									)}
									<div className="dctc-ai-message dctc-ai-message-bot">
										<div className="dctc-ai-message-content">
											<Markdown components={markdownComponents}>
												{!hasApiKey && isAdmin
													? `👋 ${chatbot.greeting_msg}\n\n⚠️ **Action Required**: Please [configure your AI Provider API key](${settingsUrl}) in AI Assistant settings to enable responses.`
													: linkifyContent(chatbot.greeting_msg)}
											</Markdown>
										</div>
									</div>
								</div>
							)}

						{/* Suggested Questions */}
						{messages.length === 0 &&
							suggestedQuestions.length > 0 && (
								<div className="dctc-ai-suggested-questions">
									{suggestedQuestions.map((question, index) => (
										<button
											key={index}
											type="button"
											className={`dctc-ai-suggested-question-btn dctc-ai-suggested-question-btn--${chatbot.pre_questions_border_radius || 'rounded'}`}
											style={{
												backgroundColor: chatbot.pre_questions_bg_color || '#ffffff',
												color: chatbot.pre_questions_text_color || '#475569',
												borderColor: chatbot.pre_questions_border_color || '#e2e8f0',
											}}
											onClick={(e) => handleSend(e, question)}
										>
											{question}
										</button>
									))}
								</div>
							)}

						{/* Custom Quick Action Buttons (Contact Us, Products, Custom Links) */}
						{actionButtons.length > 0 && (
							<div className="dctc-ai-action-buttons-bar">
								{actionButtons.map((btn, index) => (
									<a
										key={btn.id || index}
										href={btn.url || '#'}
										target={btn.target || '_blank'}
										rel="noopener noreferrer"
										className="dctc-ai-action-btn"
										style={{
											borderColor: primaryColor + '40',
										}}
									>
										{btn.label}
									</a>
								))}
							</div>
						)}

						{/* Message Stream */}
						{messages.map((message, index) => {
							const isBot = message.role === 'bot' || message.role === 'error';
							const roleClass =
								message.role === 'bot'
									? 'dctc-ai-message-bot'
									: message.role === 'error'
										? 'dctc-ai-message-error'
										: 'dctc-ai-message-user';

							return (
								<div
									key={index}
									className={`dctc-ai-message-row ${isBot ? 'dctc-ai-message-row--bot' : 'dctc-ai-message-row--user'}`}
								>
									{isBot && showBotAvatarInChat && (
										<div className="dctc-ai-msg-avatar dctc-ai-msg-avatar--bot">
											{renderBotAvatar(botPreset, botAvatar, botName)}
										</div>
									)}
									<div
										className={`dctc-ai-message-body ${isBot
											? 'dctc-ai-message-body--bot'
											: 'dctc-ai-message-body--user'
											}`}
									>
										{message.is_agent && (
											<div
												className="dctc-ai-agent-sender-tag"
												style={{
													fontSize: '11px',
													fontWeight: 600,
													color: '#4F46E5',
													marginBottom: '4px',
													display: 'flex',
													alignItems: 'center',
													gap: '4px',
												}}
											>
												{`👤 ${message.sender_name || assignedAgentName || __('Support Agent', 'dragwyb-click-to-chat')}`}
											</div>
										)}
										{/* 1. Attachments rendered as clean standalone cards above text */}
										{message.attachments &&
											message.attachments.length > 0 && (
												<div className="dctc-ai-msg-attachments">
													{message.attachments.map((att, attIdx) =>
														att.type === 'image' ? (
															<a
																key={att.id || attIdx}
																href={att.url || '#'}
																target="_blank"
																rel="noopener noreferrer"
																className="dctc-ai-msg-att-img-link"
																title={att.name}
															>
																<img
																	src={att.url}
																	alt={
																		att.name ||
																		__(
																			'Attachment',
																			'dragwyb-click-to-chat'
																		)
																	}
																	className="dctc-ai-msg-att-img"
																	loading="lazy"
																/>
															</a>
														) : (
															<a
																key={att.id || attIdx}
																href={att.url || '#'}
																target="_blank"
																rel="noopener noreferrer"
																className="dctc-ai-msg-att-file"
																title={att.name}
															>
																<span className="dctc-ai-msg-att-file-icon">
																	📄
																</span>
																<div className="dctc-ai-msg-att-file-meta">
																	<span className="dctc-ai-msg-att-file-name">
																		{att.name}
																	</span>
																	{att.size > 0 && (
																		<span className="dctc-ai-msg-att-file-size">
																			{formatFileSize(att.size)}
																		</span>
																	)}
																</div>
																<span
																	className="dashicons dashicons-download"
																	aria-hidden="true"
																/>
															</a>
														)
													)}
												</div>
											)}
										{/* 2. Text message bubble rendered directly below the attachments */}
										{isBot ? (
											<div className={'dctc-ai-message ' + roleClass}>
												<div className="dctc-ai-message-content">
													<Markdown components={markdownComponents}>
														{linkifyContent(message.content)}
													</Markdown>
												</div>
												{showSources &&
													message.sources &&
													message.sources.length > 0 && (
														<div className="dctc-ai-sources">
															<span className="dctc-ai-sources__label">
																{__('Sources:', 'dragwyb-click-to-chat')}
															</span>
															<div className="dctc-ai-sources__list">
																{message.sources.slice(0, 3).map((src, srcIdx) => (
																	<a
																		key={srcIdx}
																		href={src.url || '#'}
																		target="_blank"
																		rel="noopener noreferrer"
																		className="dctc-ai-source-chip"
																		title={src.title}
																	>
																		<svg
																			width="12"
																			height="12"
																			viewBox="0 0 24 24"
																			fill="none"
																			stroke="currentColor"
																			strokeWidth="2"
																			strokeLinecap="round"
																			strokeLinejoin="round"
																			className="dctc-ai-source-chip__icon"
																			aria-hidden="true"
																		>
																			<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6" />
																			<polyline points="15 3 21 3 21 9" />
																			<line x1="10" y1="14" x2="21" y2="3" />
																		</svg>
																		<span className="dctc-ai-source-chip__title">
																			{src.title || src.url}
																		</span>
																	</a>
																))}
															</div>
														</div>
													)}
												{chatbot?.enable_voice_output !== false &&
													<div className="dctc-ai-msg-footer">
														{typeof window !== 'undefined' && !!window.speechSynthesis && (
															<button
																type="button"
																className={`dctc-ai-msg-tts-btn ${speakingIndex === index
																	? 'is-speaking'
																	: ''
																	}`}
																onClick={() =>
																	toggleSpeakMessage(
																		message.content,
																		index
																	)
																}
																title={
																	speakingIndex === index
																		? __(
																			'Stop reading',
																			'dragwyb-click-to-chat'
																		)
																		: __(
																			'Read aloud',
																			'dragwyb-click-to-chat'
																		)
																}
																aria-label={
																	speakingIndex === index
																		? __(
																			'Stop reading',
																			'dragwyb-click-to-chat'
																		)
																		: __(
																			'Read aloud',
																			'dragwyb-click-to-chat'
																		)
																}
															>
																{speakingIndex === index ? '⏹️' : '🔊'}
															</button>
														)}
													</div>
												}
												{message.action_buttons &&
													message.action_buttons.length > 0 && (
														<div className="dctc-ai-msg-actions">
															{message.action_buttons.map((btn, bIdx) => (
																<a
																	key={bIdx}
																	href={btn.url}
																	target={btn.target || '_blank'}
																	rel="noopener noreferrer"
																	className={
																		'dctc-ai-msg-action-btn' +
																		(btn.type === 'whatsapp'
																			? ' is-whatsapp'
																			: '')
																	}
																>
																	{btn.type === 'whatsapp' && (
																		<svg
																			width="13"
																			height="13"
																			viewBox="0 0 24 24"
																			fill="currentColor"
																			style={{ marginRight: '4px' }}
																		>
																			<path d="M17.472 14.382c-.301-.15-1.782-.879-2.057-.98-.276-.1-.476-.15-.677.15-.2.301-.777.98-.953 1.18-.175.2-.351.226-.652.075-.301-.15-1.272-.469-2.423-1.496-.896-.799-1.501-1.786-1.677-2.087-.175-.301-.019-.464.132-.614.136-.135.301-.351.451-.527.15-.175.2-.301.301-.501.1-.2.05-.376-.025-.526-.075-.15-.677-1.633-.928-2.235-.244-.587-.493-.507-.677-.517-.175-.008-.376-.01-.577-.01-.201 0-.527.075-.803.376-.276.301-1.053 1.028-1.053 2.508 0 1.479 1.078 2.908 1.229 3.109.15.2 2.122 3.24 5.141 4.544.718.31 1.279.496 1.716.635.722.23 1.379.197 1.898.12.578-.087 1.782-.728 2.033-1.431.25-.703.25-1.305.175-1.431-.075-.126-.276-.201-.577-.351zm-5.467 7.518h-.005a10.84 10.84 0 0 1-5.526-1.509l-.396-.235-4.108 1.077 1.096-4.004-.258-.411a10.835 10.835 0 0 1-1.666-5.783c0-5.99 4.874-10.865 10.869-10.865a10.81 10.81 0 0 1 7.684 3.184 10.812 10.812 0 0 1 3.18 7.686c0 5.992-4.874 10.866-10.868 10.866z" />
																		</svg>
																	)}
																	<span>{btn.label}</span>
																</a>
															))}
														</div>
													)}
												{message.products &&
													message.products.length > 0 && (
														<div className="dctc-ai-products-grid">
															{message.products.map((prod, pIdx) => (
																<div
																	key={prod.id || pIdx}
																	className="dctc-ai-product-card"
																>
																	{prod.image && (
																		<div className="dctc-ai-product-thumb-wrap">
																			<img
																				src={prod.image}
																				alt={prod.title}
																				className="dctc-ai-product-thumb"
																				loading="lazy"
																			/>
																			{prod.is_on_sale && (
																				<span className="dctc-ai-product-badge">
																					{__('SALE', 'dragwyb-click-to-chat')}
																				</span>
																			)}
																		</div>
																	)}
																	<div className="dctc-ai-product-info">
																		<strong
																			className="dctc-ai-product-title"
																			title={prod.title}
																		>
																			{prod.title}
																		</strong>
																		<div className="dctc-ai-product-meta-row">
																			<span className="dctc-ai-product-price">
																				{decodeHtmlEntities(prod.formatted_price)}
																			</span>
																			<span
																				className={`dctc-ai-stock-pill ${prod.in_stock ? 'is-in-stock' : 'is-out-of-stock'}`}
																			>
																				{prod.in_stock
																					? __('In Stock', 'dragwyb-click-to-chat')
																					: __('Out of Stock', 'dragwyb-click-to-chat')}
																			</span>
																		</div>
																		{prod.rating > 0 && (
																			<div className="dctc-ai-product-rating">
																				{'★'.repeat(Math.round(prod.rating))}
																				<span className="dctc-ai-rating-num">
																					{` (${prod.rating.toFixed(1)})`}
																				</span>
																			</div>
																		)}
																		<div className="dctc-ai-product-buttons">
																			<a
																				href={prod.permalink}
																				target="_blank"
																				rel="noopener noreferrer"
																				className="dctc-ai-product-btn dctc-ai-product-btn--view"
																			>
																				{__('View', 'dragwyb-click-to-chat')}
																			</a>
																			{prod.in_stock && (
																				<a
																					href={prod.add_to_cart_url}
																					target="_blank"
																					rel="noopener noreferrer"
																					className="dctc-ai-product-btn dctc-ai-product-btn--cart"
																					style={{ background: primaryColor }}
																				>
																					{'🛒 ' + __('Add', 'dragwyb-click-to-chat')}
																				</a>
																			)}
																		</div>
																	</div>
																</div>
															))}
														</div>
													)}
											</div>
										) : message.content &&
											message.content.trim() ? (
											<div className={'dctc-ai-message ' + roleClass}>
												<div className="dctc-ai-message-text">
													{message.content}
												</div>
											</div>
										) : null}
										{(message.show_order_tracker || (message.show_form && message.show_form.form_type === 'order_track' && message.show_form.show !== false) || (message.show_form && message.show_form.form_type === 'order_track' && message.show_form.form_filled)) &&
											renderOrderTrackerCard(message, index)}
										{message.show_form &&
											message.show_form.show === true &&
											message.show_form.form_type === 'lead_generate' &&
											!message.show_form.form_filled &&
											!leadFormSubmitted &&
											renderLeadCard(message, index)}
									</div>
									{!isBot && showUserAvatarInChat && (
										<div className="dctc-ai-msg-avatar dctc-ai-msg-avatar--user">
											{renderUserAvatar(userPreset, userAvatar)}
										</div>
									)}
								</div>
							);
						})}

						{/* Typing Indicator */}
						{isLoading && (
							<div className="dctc-ai-message-row dctc-ai-message-row--bot">
								{showBotAvatarInChat && (
									<div className="dctc-ai-msg-avatar dctc-ai-msg-avatar--bot">
										{renderBotAvatar(botPreset, botAvatar, botName)}
									</div>
								)}
								<div className="dctc-ai-message dctc-ai-message-bot dctc-ai-message-typing-wrap">
									{waitingAgentStatusText && (
										<div
											className="dctc-ai-waiting-status-text"
											style={{
												fontSize: '0.8rem',
												color: '#64748b',
												marginBottom: '6px',
												fontStyle: 'italic',
											}}
										>
											{waitingAgentStatusText}
										</div>
									)}
									<div className="dctc-ai-typing">
										<span />
										<span />
										<span />
									</div>
								</div>
							</div>
						)}
						<div ref={messagesEndRef} />
					</div>

					{/* Email Capture Gate OR Input Bar */}
					{showEmailGate ? (
						<div className="dctc-ai-email-capture-container">
							<form
								className="dctc-ai-email-capture-form"
								onSubmit={handleEmailSubmit}
							>
								<h3>{__('One last step!', 'dragwyb-click-to-chat')}</h3>
								<p>
									{__(
										'Please enter your email to get your response and continue.',
										'dragwyb-click-to-chat'
									)}
								</p>
								<div className="dctc-ai-email-input-group">
									<input
										type="email"
										className="dctc-ai-email-input"
										placeholder={__(
											'your.email@example.com',
											'dragwyb-click-to-chat'
										)}
										value={emailDraft}
										onChange={(e) => {
											setEmailDraft(e.target.value);
											setEmailError('');
										}}
										required
									/>
									{emailError && (
										<span className="dctc-ai-email-error">
											{emailError}
										</span>
									)}
								</div>
								<button
									type="submit"
									className="dctc-ai-email-submit"
									style={{ background: primaryColor }}
								>
									{__('Get Response', 'dragwyb-click-to-chat')}
								</button>
							</form>
						</div>
					) : (
						<div className="dctc-ai-chat-input-wrapper">
							{/* Upload Error Notice (if any) */}
							{uploadError && (
								<div className="dctc-ai-upload-error-notice">
									<span>{uploadError}</span>
									<button
										type="button"
										className="dctc-ai-upload-error-close"
										onClick={() => setUploadError('')}
										aria-label={__('Dismiss', 'dragwyb-click-to-chat')}
									>
										✕
									</button>
								</div>
							)}

							{/* Unified Modern Composer Container */}
							<div className="dctc-ai-composer">
								{/* Hidden file inputs */}
								{enableUploads && (
									<input
										ref={fileInputRef}
										type="file"
										multiple={maxFilesPerMsg > 1}
										style={{ display: 'none' }}
										onChange={handleFilesSelected}
										accept={
											allowedExts
												.map((ext) => '.' + ext)
												.join(',') || undefined
										}
									/>
								)}
								{enableUploads && (
									<input
										ref={imageFileInputRef}
										type="file"
										multiple={maxFilesPerMsg > 1}
										style={{ display: 'none' }}
										onChange={handleFilesSelected}
										accept="image/png,image/jpeg,image/webp,image/gif,image/*"
									/>
								)}

								{/* Attachments tray inside composer (rendered above the input row) */}
								{attachments.length > 0 && (
									<div className="dctc-ai-composer-attachments">
										{attachments.map((att) => (
											<div
												key={att.id}
												className={`dctc-ai-composer-att-card ${att.type === 'image'
													? 'dctc-ai-composer-att-card--image'
													: 'dctc-ai-composer-att-card--file'
													}`}
											>
												{att.type === 'image' ? (
													<div className="dctc-ai-composer-att-img-box">
														<img
															src={att.previewUrl || att.url}
															alt={att.name}
															className="dctc-ai-composer-att-thumb"
														/>
														<div className="dctc-ai-composer-att-img-overlay">
															<span className="dctc-ai-composer-att-img-icon">
																🖼️
															</span>
															<span
																className="dctc-ai-composer-att-img-name"
																title={att.name}
															>
																{att.name}
															</span>
														</div>
													</div>
												) : (
													<div className="dctc-ai-composer-att-file-box">
														<span className="dctc-ai-composer-att-file-icon">
															📄
														</span>
														<div className="dctc-ai-composer-att-file-info">
															<span
																className="dctc-ai-composer-att-file-name"
																title={att.name}
															>
																{att.name}
															</span>
															{att.size > 0 && (
																<span className="dctc-ai-composer-att-file-size">
																	{formatFileSize(att.size)}
																</span>
															)}
														</div>
													</div>
												)}
												{att.status === 'uploading' && (
													<div className="dctc-ai-composer-att-loading-mask">
														<span className="dctc-ai-composer-att-spinner" />
													</div>
												)}
												<button
													type="button"
													className="dctc-ai-composer-att-remove"
													onClick={() => removeAttachment(att.id)}
													title={__(
														'Remove attachment',
														'dragwyb-click-to-chat'
													)}
													aria-label={sprintf(
														__(
															'Remove %s',
															'dragwyb-click-to-chat'
														),
														att.name
													)}
												>
													×
												</button>
											</div>
										))}
									</div>
								)}

								{/* Composer Row: [ + Menu ] [ Textarea ] [ 😊 Emoji ] [ ➤ Send ] */}
								<div className="dctc-ai-composer-row">
									{/* Plus (+) Button with Popup Menu (on left inside input/composer) */}
									{enableUploads && (
										<div
											ref={attachMenuRef}
											className="dctc-ai-composer-plus-wrap"
										>
											<button
												type="button"
												className={`dctc-ai-composer-plus-btn ${isAttachMenuOpen
													? 'is-active'
													: ''
													}`}
												onClick={() =>
													setIsAttachMenuOpen(
														(prev) => !prev
													)
												}
												title={__(
													'Add attachment',
													'dragwyb-click-to-chat'
												)}
												aria-label={__(
													'Add attachment',
													'dragwyb-click-to-chat'
												)}
												aria-expanded={isAttachMenuOpen}
												aria-haspopup="true"
												disabled={
													isLoading || isAnyUploading
												}
											>
												＋
											</button>
											{isAttachMenuOpen && (
												<div className="dctc-ai-attach-menu">
													<button
														type="button"
														className="dctc-ai-attach-menu-item"
														onClick={() => {
															setIsAttachMenuOpen(
																false
															);
															if (fileInputRef.current) {
																fileInputRef.current.click();
															}
														}}
													>
														<span className="dctc-ai-attach-menu-icon">
															📎
														</span>
														<span className="dctc-ai-attach-menu-label">
															{__(
																'Attach File',
																'dragwyb-click-to-chat'
															)}
														</span>
													</button>
													<button
														type="button"
														className="dctc-ai-attach-menu-item"
														onClick={() => {
															setIsAttachMenuOpen(
																false
															);
															if (imageFileInputRef.current) {
																imageFileInputRef.current.click();
															}
														}}
													>
														<span className="dctc-ai-attach-menu-icon">
															🖼️
														</span>
														<span className="dctc-ai-attach-menu-label">
															{__(
																'Upload Image',
																'dragwyb-click-to-chat'
															)}
														</span>
													</button>
												</div>
											)}
										</div>
									)}

									{/* Textarea in the middle */}
									<textarea
										ref={inputRef}
										className="dctc-ai-composer-textarea"
										value={input}
										onChange={(e) => {
											setInput(e.target.value);
											e.target.style.height = 'auto';
											e.target.style.height =
												Math.min(e.target.scrollHeight, 140) +
												'px';
										}}
										onKeyDown={(e) => {
											if (e.key === 'Enter' && !e.shiftKey) {
												e.preventDefault();
												handleSend(e);
											}
										}}
										placeholder={__(
											'Type your message...',
											'dragwyb-click-to-chat'
										)}
										rows={1}
										disabled={isLoading}
									/>

									{/* Voice Speech-to-Text Button */}
									{chatbot?.enable_voice_input !== false && (
										<button
											type="button"
											className={`dctc-ai-composer-voice-btn ${isListening ? 'is-listening' : ''}`}
											onClick={toggleVoiceRecognition}
											title={
												isListening
													? __(
														'Listening... Click to stop',
														'dragwyb-click-to-chat'
													)
													: __(
														'Voice input',
														'dragwyb-click-to-chat'
													)
											}
											aria-label={
												isListening
													? __(
														'Stop voice input',
														'dragwyb-click-to-chat'
													)
													: __(
														'Voice input',
														'dragwyb-click-to-chat'
													)
											}
										>
											{isListening ? (
												<span className="dctc-ai-voice-active-indicator" />
											) : (
												'🎙️'
											)}
										</button>
									)}

									{/* Emoji Button on right of input (before Send) */}
									<div
										ref={emojiWrapRef}
										className="dctc-ai-composer-emoji-wrap"
									>
										<button
											type="button"
											className={`dctc-ai-composer-emoji-btn ${isEmojiOpen ? 'is-active' : ''}`}
											onClick={() =>
												setIsEmojiOpen(
													(prev) => !prev
												)
											}
											title={__(
												'Insert emoji',
												'dragwyb-click-to-chat'
											)}
											aria-label={__(
												'Insert emoji',
												'dragwyb-click-to-chat'
											)}
											aria-expanded={isEmojiOpen}
											aria-controls="dctc-ai-emoji-picker"
										>
											😊
										</button>
										{isEmojiOpen && (
											<EmojiPicker
												onSelect={handleSelectEmoji}
												onClose={() =>
													setIsEmojiOpen(false)
												}
											/>
										)}
									</div>

									{/* Send Button on far right */}
									<button
										type="button"
										className="dctc-ai-composer-send-btn"
										onClick={handleSend}
										disabled={
											isLoading ||
											isAnyUploading ||
											(!input.trim() &&
												attachments.length === 0)
										}
										style={{ background: primaryColor }}
										title={__(
											'Send message',
											'dragwyb-click-to-chat'
										)}
										aria-label={__(
											'Send message',
											'dragwyb-click-to-chat'
										)}
									>
										{isAnyUploading ? (
											<span className="dctc-ai-spinner" />
										) : (
											<svg
												width="16"
												height="16"
												viewBox="0 0 24 24"
												fill="none"
												aria-hidden="true"
											>
												<path
													d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"
													stroke="currentColor"
													strokeWidth="2.2"
													strokeLinecap="round"
													strokeLinejoin="round"
												/>
											</svg>
										)}
									</button>
								</div>
							</div>
						</div>
					)}
				</div>
			)}

			{/* Proactive Teaser Bubble (Behavioral Triggers) */}
			{!inline &&
				!isOpen &&
				display.enable_proactive_bubble &&
				proactiveBubbleOpen &&
				launcherVisible && (
					<div
						id="dctc-ai-proactive-bubble"
						className="dctc-ai-proactive-bubble"
						role="dialog"
						aria-label={__('AI Assistant Greeting', 'dragwyb-click-to-chat')}
					>
						<button
							type="button"
							className="dctc-ai-proactive-bubble-close"
							onClick={(e) => {
								e.stopPropagation();
								setProactiveBubbleOpen(false);
								try {
									if (window.sessionStorage) {
										window.sessionStorage.setItem('dctc_ai_bubble_dismissed', '1');
									}
								} catch (err) { }
							}}
							title={__('Dismiss', 'dragwyb-click-to-chat')}
							aria-label={__('Dismiss greeting', 'dragwyb-click-to-chat')}
						>
							×
						</button>
						<div
							className="dctc-ai-proactive-bubble-inner"
							onClick={() => {
								setProactiveBubbleOpen(false);
								setIsOpen(true);
							}}
						>
							<div className="dctc-ai-proactive-bubble-avatar">
								{renderBotAvatar(botPreset, botAvatar, botName)}
							</div>
							<div className="dctc-ai-proactive-bubble-content">
								<p className="dctc-ai-proactive-bubble-text">
									{display.proactive_bubble_message ||
										__('👋 Hi there! Have a question about this page? Let me know!', 'dragwyb-click-to-chat')}
								</p>
								<span
									className="dctc-ai-proactive-bubble-cta"
									style={{ color: primaryColor }}
								>
									{__('Chat with us →', 'dragwyb-click-to-chat')}
								</span>
							</div>
						</div>
					</div>
				)}

			{/* Launcher Button */}
			{!inline &&
				!isOpen && (
					<div
						id="dctc-ai-launcher"
						className={
							'dctc-ai-chat-launcher' +
							(assistantIcon ? ' dctc-ai-chat-launcher--custom-icon' : '') +
							(launcherVisible ? '' : ' dctc-ai-chat-launcher--hidden')
						}
						onClick={toggleOpen}
						style={{ background: primaryColor }}
						aria-hidden={launcherVisible ? undefined : 'true'}
					>
						{display.enable_launcher_text &&
							display.launcher_text && (
								<span className="dctc-ai-launcher-text">
									{display.launcher_text}
								</span>
							)}
						<div className="dctc-ai-launcher-icon-inner">
							{renderLauncherIcon(launcherPreset, assistantIcon)}
						</div>
					</div>
				)}
		</div>
	);
}
