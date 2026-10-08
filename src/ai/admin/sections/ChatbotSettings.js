/**
 * Chatbot Settings — Unified General, Advance, Style & Messages Management.
 * Modular, component-based controller for all chatbot configuration subtabs.
 */
import { useState, useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

import GeneralSubtab from './chatbot/GeneralSubtab';
import AdvancedSubtab from './chatbot/AdvancedSubtab';
import StyleSubtab from './chatbot/StyleSubtab';
import MessagesSubtab from './chatbot/MessagesSubtab';

const SUBTABS = [
	{
		id: 'general',
		label: __('General', 'dragwyb-click-to-chat'),
		icon: 'dashicons-admin-generic',
		desc: __('Identity & site visibility', 'dragwyb-click-to-chat'),
	},
	{
		id: 'advanced',
		label: __('Advance', 'dragwyb-click-to-chat'),
		icon: 'dashicons-admin-tools',
		desc: __('Triggers, rate limits & uploads', 'dragwyb-click-to-chat'),
	},
	{
		id: 'style',
		label: __('Style', 'dragwyb-click-to-chat'),
		icon: 'dashicons-art',
		desc: __('Launcher, positions & avatars', 'dragwyb-click-to-chat'),
	},
	{
		id: 'messages',
		label: __('Messages', 'dragwyb-click-to-chat'),
		icon: 'dashicons-format-chat',
		desc: __('Greetings, notices, fallbacks & tracking texts', 'dragwyb-click-to-chat'),
	},
];

export default function ChatbotSettings({ settings, onSave, showNotice }) {
	const isWcActive = !!window.dctc_ai_data?.is_woocommerce_active;
	const chatbot = settings?.chatbot || {};
	const display = settings?.display || {};
	const [subtab, setSubtab] = useState('general');
	const [saving, setSaving] = useState(false);
	const [copiedShortcode, setCopiedShortcode] = useState(false);

	const copyShortcode = () => {
		if (navigator?.clipboard?.writeText) {
			navigator.clipboard.writeText('[dctc_ai]');
			setCopiedShortcode(true);
			setTimeout(() => setCopiedShortcode(false), 2200);
		}
	};

	// Content search for page exclusions
	const [content, setContent] = useState([]);
	const [loadingContent, setLoadingContent] = useState(false);
	const [search, setSearch] = useState('');
	const [openSearch, setOpenSearch] = useState(false);
	const [usageStats, setUsageStats] = useState(null);
	const comboRef = useRef(null);

	const buildForm = () => ({
		// General / Bot Identity
		bot_name: chatbot.bot_name || '',
		primary_color: chatbot.primary_color || '#6366f1',
		greeting_msg: chatbot.greeting_msg || '',
		api_error_msg: chatbot.api_error_msg || '',
		support_url: chatbot.support_url || '',

		// General / Visibility
		entire_site: !!display.entire_site,
		show_on_mobile: display.show_on_mobile !== false,
		exclude_pages: display.exclude_pages || '',
		launcher_text: display.launcher_text || '',

		// Advance / Triggers & Timing
		trigger_type: display.trigger_type || 'click',
		trigger_delay: display.trigger_delay ?? 5,
		time_delay: display.time_delay ?? 0,

		// Advance / Security & Cost Controls
		rate_limit_per_minute: chatbot.rate_limit_per_minute ?? 20,
		enable_usage_limits: chatbot.enable_usage_limits !== false,
		visitor_daily_message_limit: chatbot.visitor_daily_message_limit ?? 50,
		monthly_request_budget: chatbot.monthly_request_budget ?? 5000,
		budget_limit_message:
			chatbot.budget_limit_message ||
			__(
				'You have reached the daily chat limit. Please connect with our team directly via WhatsApp or Support.',
				'dragwyb-click-to-chat'
			),
		enable_budget_email_alerts: chatbot.enable_budget_email_alerts !== false,
		alert_email: chatbot.alert_email || '',

		// Advance / Lead Capture & CRM Integration
		enable_lead_capture: !!chatbot.enable_lead_capture,
		lead_form_title: chatbot.lead_form_title || __('Contact Our Team', 'dragwyb-click-to-chat'),
		lead_form_subtitle:
			chatbot.lead_form_subtitle ||
			__(
				'Leave your details and our team will get back to you shortly.',
				'dragwyb-click-to-chat'
			),
		lead_fields: chatbot.lead_fields || {
			name: true,
			email: true,
			phone: true,
			company: false,
			company_size: false,
			budget: true,
			timeline: true,
			interest: true,
			requirement: true,
		},
		lead_qualification_threshold: chatbot.lead_qualification_threshold ?? 70,
		enable_ai_intent_scoring: chatbot.enable_ai_intent_scoring !== false,
		enable_lead_email_alerts: chatbot.enable_lead_email_alerts !== false,
		lead_notification_email: chatbot.lead_notification_email || '',
		lead_webhook_url: chatbot.lead_webhook_url || '',

		// Advance / Connect Chatbot with Support Tickets
		enable_support_escalation: chatbot.enable_support_escalation !== false,
		auto_assign_support_tickets: chatbot.auto_assign_support_tickets !== false,
		auto_pause_ai_on_ticket: chatbot.auto_pause_ai_on_ticket !== false,
		human_agent_max_wait_time: chatbot.human_agent_max_wait_time ?? 60,
		human_agent_waiting_message:
			chatbot.human_agent_waiting_message ||
			__('Sorry to keep you waiting...', 'dragwyb-click-to-chat'),

		// Messages & Notifications (Centralized Messages Tab)
		order_tracking_prompt_msg:
			chatbot.order_tracking_prompt_msg ||
			__(
				'Please enter your Order ID and billing email below to view your real-time order and shipment tracking details.',
				'dragwyb-click-to-chat'
			),
		order_tracking_login_msg:
			chatbot.order_tracking_login_msg ||
			__(
				'To securely track your order status, please [log in to your account]({login_url}) first.',
				'dragwyb-click-to-chat'
			),
		order_mismatch_msg:
			chatbot.order_mismatch_msg ||
			__(
				'This order was purchased with a different email address. For privacy and security reasons, order details cannot be displayed.',
				'dragwyb-click-to-chat'
			),
		support_ticket_msg:
			chatbot.support_ticket_msg ||
			__(
				'I have logged your inquiry with our support team and created a support ticket for this session. A support specialist will review your message and assist you shortly.',
				'dragwyb-click-to-chat'
			),
		no_data_message:
			chatbot.no_data_message ||
			settings?.rag?.no_data_message ||
			__(
				"I don't have information about your question in my knowledge base. Please rephrase or ask about topics I have knowledge of.",
				'dragwyb-click-to-chat'
			),

		// Advance / AI Agents, Tools & Automation Workflows
		enable_ai_tools: chatbot.enable_ai_tools !== false,
		enabled_tools: Array.isArray(chatbot.enabled_tools)
			? chatbot.enabled_tools
			: ['search_products', 'get_order_status', 'create_support_ticket', 'book_appointment', 'search_website_content'],
		workflow_webhook_url: chatbot.workflow_webhook_url || '',
		ticket_notification_email: chatbot.ticket_notification_email || '',
		appointment_notification_email: chatbot.appointment_notification_email || '',
		enable_page_context: chatbot.enable_page_context !== false,
		enable_multilingual: chatbot.enable_multilingual !== false,
		preferred_language: chatbot.preferred_language || 'auto',
		visitor_language_override: chatbot.visitor_language_override !== false,
		enable_voice_input: chatbot.enable_voice_input !== false,
		enable_voice_output: !!chatbot.enable_voice_output,
		voice_language: chatbot.voice_language || 'auto',
		enable_vision_understanding: chatbot.enable_vision_understanding !== false,

		// Advance / Smart Triggers & Targeting
		enable_smart_triggers: !!display.enable_smart_triggers,
		trigger_scroll_depth: display.trigger_scroll_depth ?? 50,
		trigger_exit_intent: !!display.trigger_exit_intent,
		trigger_inactivity: display.trigger_inactivity ?? 30,
		trigger_action: display.trigger_action || 'show_bubble',
		proactive_bubble_message: display.proactive_bubble_message || '👋 Hi there! Have a question about this page? Let me know if I can help!',
		target_devices: display.target_devices || 'all',
		target_users: display.target_users || 'all',
		url_rules: display.url_rules || '',

		// Advance / Conversation Features
		save_chat: !!chatbot.save_chat,
		chat_retention_days: chatbot.chat_retention_days ?? 0,
		memory_window_size: chatbot.memory_window_size ?? 10,
		ask_email: !!chatbot.ask_email,
		enable_pre_questions: !!chatbot.enable_pre_questions,
		enable_uploads: !!chatbot.enable_uploads,

		// Advance / File Uploads
		allowed_file_types:
			chatbot.allowed_file_types ||
			'jpg, jpeg, png, webp, gif, pdf, txt, doc, docx',
		excluded_file_types:
			chatbot.excluded_file_types ||
			'php, php3, php4, php5, phtml, phar, cgi, pl, py, sh, exe, bat, cmd, js, html, htm, svg',
		max_upload_size: chatbot.max_upload_size ?? 5,
		max_files_per_message: chatbot.max_files_per_message ?? 3,
		store_chat_attachments: chatbot.store_chat_attachments || 'temp',

		// Advance / Action Buttons
		action_buttons: Array.isArray(chatbot.action_buttons)
			? chatbot.action_buttons
			: [],

		// Advance / Suggested Questions
		pre_question_1: chatbot.pre_question_1 || '',
		pre_question_2: chatbot.pre_question_2 || '',
		pre_question_3: chatbot.pre_question_3 || '',
		pre_question_4: chatbot.pre_question_4 || '',
		pre_questions_bg_color: chatbot.pre_questions_bg_color || '#ffffff',
		pre_questions_text_color: chatbot.pre_questions_text_color || '#475569',
		pre_questions_border_color: chatbot.pre_questions_border_color || '#e2e8f0',
		pre_questions_border_radius: chatbot.pre_questions_border_radius || 'rounded',

		// Advance / Error Logging
		enable_error_log: chatbot.enable_error_log !== undefined ? !!chatbot.enable_error_log : true,
		error_log_retention_days: chatbot.error_log_retention_days ?? 0,

		// Style / Floating Launcher Button
		launcher_icon_preset: display.launcher_icon_preset || 'chat',
		assistant_icon: display.assistant_icon || '',
		widget_size: display.widget_size ?? 64,
		widget_size_unit: display.widget_size_unit || 'px',

		// Style / Positioning
		position: display.position || 'bottom-right',
		custom_vertical_align: display.custom_vertical_align || 'bottom',
		custom_vertical: display.custom_vertical ?? 24,
		custom_vertical_unit: display.custom_vertical_unit || 'px',
		custom_side: display.custom_side || 'right',
		custom_horizontal: display.custom_horizontal ?? 24,
		custom_horizontal_unit: display.custom_horizontal_unit || 'px',

		// Style / Avatars, Bubbles & Theme Presets
		style_preset: chatbot.style_preset || 'modern_indigo',
		chat_bg_image: chatbot.chat_bg_image || '',
		chat_bg_opacity: chatbot.chat_bg_opacity ?? 100,
		user_msg_bg_color: chatbot.user_msg_bg_color || chatbot.primary_color || '#6366f1',
		user_msg_text_color: chatbot.user_msg_text_color || '#ffffff',
		bot_msg_bg_color: chatbot.bot_msg_bg_color || '#f1f5f9',
		bot_msg_text_color: chatbot.bot_msg_text_color || '#0f172a',
		container_border_radius: chatbot.container_border_radius ?? 16,
		container_border_style: chatbot.container_border_style || 'solid',
		container_border_color: chatbot.container_border_color || '#e2e8f0',
		container_border_width: chatbot.container_border_width ?? 1,
		container_width: chatbot.container_width ?? 380,
		container_width_unit: chatbot.container_width_unit || 'px',
		container_height: chatbot.container_height ?? 600,
		container_height_unit: chatbot.container_height_unit || 'px',
		bot_avatar: chatbot.bot_avatar || '',
		bot_icon_preset: chatbot.bot_icon_preset || 'bot',
		user_avatar: chatbot.user_avatar || '',
		user_icon_preset: chatbot.user_icon_preset || 'user',
		show_bot_avatar_in_chat: chatbot.show_bot_avatar_in_chat !== false,
		show_user_avatar_in_chat: !!chatbot.show_user_avatar_in_chat,
		bubble_style: chatbot.bubble_style || 'rounded',
		show_sources: chatbot.show_sources !== false,
	});

	const [form, setForm] = useState(buildForm);
	const [saved, setSaved] = useState(buildForm);

	// Sync when settings change from external update
	useEffect(() => {
		const next = buildForm();
		setForm(next);
		setSaved(next);
	}, [settings]);

	const dirty = JSON.stringify(form) !== JSON.stringify(saved);

	// Fetch site content for page exclusion search
	useEffect(() => {
		let cancelled = false;
		setLoadingContent(true);
		apiFetch({ path: '/dctc-ai/v1/content?limit=300' })
			.then((res) => {
				if (!cancelled && res?.items) {
					setContent(res.items);
				}
			})
			.catch(() => {})
			.finally(() => {
				if (!cancelled) setLoadingContent(false);
			});

		return () => {
			cancelled = true;
		};
	}, []);

	// Fetch live usage meter stats
	useEffect(() => {
		let isSubscribed = true;
		apiFetch({ path: '/dctc-ai/v1/usage-stats' })
			.then((res) => {
				if (isSubscribed && res?.success && res?.stats) {
					setUsageStats(res.stats);
				}
			})
			.catch(() => {});

		return () => {
			isSubscribed = false;
		};
	}, []);

	// Click outside exclusion search dropdown
	useEffect(() => {
		const onDown = (e) => {
			if (comboRef.current && !comboRef.current.contains(e.target)) {
				setOpenSearch(false);
			}
		};
		document.addEventListener('mousedown', onDown);
		return () => document.removeEventListener('mousedown', onDown);
	}, []);

	const setField = (key, value) => {
		setForm((prev) => ({ ...prev, [key]: value }));
	};

	const initial = (form.bot_name || 'AI').charAt(0).toUpperCase();

	// Page exclusion token helpers
	const selectedIds = () =>
		form.exclude_pages
			? form.exclude_pages
				.split(',')
				.map((s) => s.trim())
				.filter(Boolean)
			: [];

	const addExclude = (raw) => {
		const id = (raw || search).trim();
		if (!id) return;
		const ids = selectedIds();
		if (!ids.includes(id)) {
			setField('exclude_pages', [...ids, id].join(', '));
		}
		setSearch('');
		setOpenSearch(false);
	};

	const toggleExclude = (id) => {
		const ids = selectedIds();
		const next = ids.includes(id)
			? ids.filter((x) => x !== id)
			: [...ids, id];
		setField('exclude_pages', next.join(', '));
	};

	const ids = selectedIds();
	const filtered = content.filter((item) => {
		if (!search.trim()) return true;
		const q = search.toLowerCase();
		return (
			item.title.toLowerCase().includes(q) ||
			String(item.id).includes(q) ||
			item.type.toLowerCase().includes(q)
		);
	});

	// Action buttons handlers
	const addActionButton = () => {
		const newBtn = {
			id: 'btn_' + Math.random().toString(36).substr(2, 7),
			label: __('Contact Us', 'dragwyb-click-to-chat'),
			url: '',
			target: '_blank',
			type: 'link',
		};
		setField('action_buttons', [...form.action_buttons, newBtn]);
	};

	const updateActionButton = (index, field, val) => {
		const updated = [...form.action_buttons];
		updated[index] = { ...updated[index], [field]: val };
		setField('action_buttons', updated);
	};

	const removeActionButton = (index) => {
		const updated = form.action_buttons.filter((_, i) => i !== index);
		setField('action_buttons', updated);
	};

	// Media library upload helpers
	const openBotMedia = () => {
		if (!window.wp?.media) {
			showNotice(__('WordPress media modal is not available.', 'dragwyb-click-to-chat'), 'error');
			return;
		}
		const frame = window.wp.media({
			title: __('Select Bot Avatar', 'dragwyb-click-to-chat'),
			button: { text: __('Use as Bot Avatar', 'dragwyb-click-to-chat') },
			multiple: false,
		});
		frame.on('select', () => {
			const attachment = frame.state().get('selection').first().toJSON();
			setField('bot_avatar', attachment.url);
		});
		frame.open();
	};

	const openUserMedia = () => {
		if (!window.wp?.media) {
			showNotice(__('WordPress media modal is not available.', 'dragwyb-click-to-chat'), 'error');
			return;
		}
		const frame = window.wp.media({
			title: __('Select Visitor Avatar', 'dragwyb-click-to-chat'),
			button: { text: __('Use as Visitor Avatar', 'dragwyb-click-to-chat') },
			multiple: false,
		});
		frame.on('select', () => {
			const attachment = frame.state().get('selection').first().toJSON();
			setField('user_avatar', attachment.url);
		});
		frame.open();
	};

	const openAssistantIconMedia = () => {
		if (!window.wp?.media) {
			showNotice(__('WordPress media modal is not available.', 'dragwyb-click-to-chat'), 'error');
			return;
		}
		const frame = window.wp.media({
			title: __('Select Floating Launcher Custom Icon', 'dragwyb-click-to-chat'),
			button: { text: __('Use as Launcher Icon', 'dragwyb-click-to-chat') },
			multiple: false,
		});
		frame.on('select', () => {
			const attachment = frame.state().get('selection').first().toJSON();
			setField('assistant_icon', attachment.url);
		});
		frame.open();
	};

	const openChatBgMedia = () => {
		if (!window.wp?.media) {
			showNotice(__('WordPress media modal is not available.', 'dragwyb-click-to-chat'), 'error');
			return;
		}
		const frame = window.wp.media({
			title: __('Select Chat Section Background Image', 'dragwyb-click-to-chat'),
			button: { text: __('Use Background Image', 'dragwyb-click-to-chat') },
			multiple: false,
		});
		frame.on('select', () => {
			const attachment = frame.state().get('selection').first().toJSON();
			setField('chat_bg_image', attachment.url);
		});
		frame.open();
	};

	const applyStylePreset = (presetKey) => {
		const PRESETS = {
			modern_indigo: {
				style_preset: 'modern_indigo',
				primary_color: '#6366f1',
				user_msg_bg_color: '#6366f1',
				user_msg_text_color: '#ffffff',
				bot_msg_bg_color: '#f1f5f9',
				bot_msg_text_color: '#0f172a',
				container_border_radius: 16,
				container_border_style: 'solid',
				container_border_color: '#e2e8f0',
				container_border_width: 1,
			},
			dark_slate: {
				style_preset: 'dark_slate',
				primary_color: '#3b82f6',
				user_msg_bg_color: '#3b82f6',
				user_msg_text_color: '#ffffff',
				bot_msg_bg_color: '#1e293b',
				bot_msg_text_color: '#f8fafc',
				container_border_radius: 16,
				container_border_style: 'solid',
				container_border_color: '#334155',
				container_border_width: 1,
			},
			emerald_forest: {
				style_preset: 'emerald_forest',
				primary_color: '#10b981',
				user_msg_bg_color: '#10b981',
				user_msg_text_color: '#ffffff',
				bot_msg_bg_color: '#f0fdf4',
				bot_msg_text_color: '#064e3b',
				container_border_radius: 18,
				container_border_style: 'solid',
				container_border_color: '#bbf7d0',
				container_border_width: 1,
			},
			ocean_blue: {
				style_preset: 'ocean_blue',
				primary_color: '#0284c7',
				user_msg_bg_color: '#0284c7',
				user_msg_text_color: '#ffffff',
				bot_msg_bg_color: '#f0f9ff',
				bot_msg_text_color: '#0c4a6e',
				container_border_radius: 14,
				container_border_style: 'solid',
				container_border_color: '#bae6fd',
				container_border_width: 1,
			},
			royal_purple: {
				style_preset: 'royal_purple',
				primary_color: '#8b5cf6',
				user_msg_bg_color: '#8b5cf6',
				user_msg_text_color: '#ffffff',
				bot_msg_bg_color: '#faf5ff',
				bot_msg_text_color: '#4c1d95',
				container_border_radius: 20,
				container_border_style: 'solid',
				container_border_color: '#e9d5ff',
				container_border_width: 1,
			},
			sunset_amber: {
				style_preset: 'sunset_amber',
				primary_color: '#f59e0b',
				user_msg_bg_color: '#f59e0b',
				user_msg_text_color: '#ffffff',
				bot_msg_bg_color: '#fffbeb',
				bot_msg_text_color: '#78350f',
				container_border_radius: 16,
				container_border_style: 'solid',
				container_border_color: '#fde68a',
				container_border_width: 1,
			},
			crimson_rose: {
				style_preset: 'crimson_rose',
				primary_color: '#e11d48',
				user_msg_bg_color: '#e11d48',
				user_msg_text_color: '#ffffff',
				bot_msg_bg_color: '#fff1f2',
				bot_msg_text_color: '#881337',
				container_border_radius: 16,
				container_border_style: 'solid',
				container_border_color: '#fecdd3',
				container_border_width: 1,
			},
			clean_monochrome: {
				style_preset: 'clean_monochrome',
				primary_color: '#18181b',
				user_msg_bg_color: '#18181b',
				user_msg_text_color: '#ffffff',
				bot_msg_bg_color: '#f4f4f5',
				bot_msg_text_color: '#18181b',
				container_border_radius: 12,
				container_border_style: 'solid',
				container_border_color: '#e4e4e7',
				container_border_width: 1,
			},
		};
		if (PRESETS[presetKey]) {
			setForm((prev) => ({ ...prev, ...PRESETS[presetKey] }));
		}
	};

	const onSubmit = async (e) => {
		if (e?.preventDefault) e.preventDefault();
		setSaving(true);
		window.dispatchEvent(new CustomEvent('dctc_ai_saving_start'));

		const botPayload = {
			bot_name: form.bot_name,
			primary_color: form.primary_color,
			greeting_msg: form.greeting_msg,
			api_error_msg: form.api_error_msg,
			support_url: form.support_url,
			rate_limit_per_minute: form.rate_limit_per_minute,
			enable_usage_limits: form.enable_usage_limits,
			visitor_daily_message_limit: form.visitor_daily_message_limit,
			monthly_request_budget: form.monthly_request_budget,
			budget_limit_message: form.budget_limit_message,
			enable_budget_email_alerts: form.enable_budget_email_alerts,
			alert_email: form.alert_email,
			enable_lead_capture: form.enable_lead_capture,
			lead_form_title: form.lead_form_title,
			lead_form_subtitle: form.lead_form_subtitle,
			lead_fields: form.lead_fields,
			lead_qualification_threshold: form.lead_qualification_threshold,
			enable_ai_intent_scoring: form.enable_ai_intent_scoring,
			enable_lead_email_alerts: form.enable_lead_email_alerts,
			lead_notification_email: form.lead_notification_email,
			lead_webhook_url: form.lead_webhook_url,
			enable_support_escalation: form.enable_support_escalation,
			auto_assign_support_tickets: form.auto_assign_support_tickets,
			auto_pause_ai_on_ticket: form.auto_pause_ai_on_ticket,
			human_agent_max_wait_time: form.human_agent_max_wait_time,
			human_agent_waiting_message: form.human_agent_waiting_message,
			save_chat: form.save_chat,
			chat_retention_days: form.chat_retention_days,
			memory_window_size: form.memory_window_size,
			ask_email: form.ask_email,
			enable_pre_questions: form.enable_pre_questions,
			pre_question_1: form.pre_question_1,
			pre_question_2: form.pre_question_2,
			pre_question_3: form.pre_question_3,
			pre_question_4: form.pre_question_4,
			pre_questions_bg_color: form.pre_questions_bg_color,
			pre_questions_text_color: form.pre_questions_text_color,
			pre_questions_border_color: form.pre_questions_border_color,
			pre_questions_border_radius: form.pre_questions_border_radius,
			action_buttons: form.action_buttons,
			style_preset: form.style_preset,
			chat_bg_image: form.chat_bg_image,
			chat_bg_opacity: form.chat_bg_opacity,
			user_msg_bg_color: form.user_msg_bg_color,
			user_msg_text_color: form.user_msg_text_color,
			bot_msg_bg_color: form.bot_msg_bg_color,
			bot_msg_text_color: form.bot_msg_text_color,
			container_border_radius: form.container_border_radius,
			container_border_style: form.container_border_style,
			container_border_color: form.container_border_color,
			container_border_width: form.container_border_width,
			container_width: form.container_width,
			container_width_unit: form.container_width_unit,
			container_height: form.container_height,
			container_height_unit: form.container_height_unit,
			bot_avatar: form.bot_avatar,
			bot_icon_preset: form.bot_icon_preset,
			user_avatar: form.user_avatar,
			user_icon_preset: form.user_icon_preset,
			show_bot_avatar_in_chat: form.show_bot_avatar_in_chat,
			show_user_avatar_in_chat: form.show_user_avatar_in_chat,
			bubble_style: form.bubble_style,
			show_sources: form.show_sources,
			enable_uploads: form.enable_uploads,
			allowed_file_types: form.allowed_file_types,
			excluded_file_types: form.excluded_file_types,
			max_upload_size: form.max_upload_size,
			max_files_per_message: form.max_files_per_message,
			store_chat_attachments: form.store_chat_attachments,
			enable_ai_tools: form.enable_ai_tools,
			enabled_tools: form.enabled_tools,
			workflow_webhook_url: form.workflow_webhook_url,
			ticket_notification_email: form.ticket_notification_email,
			appointment_notification_email: form.appointment_notification_email,
			enable_page_context: form.enable_page_context,
			enable_multilingual: form.enable_multilingual,
			preferred_language: form.preferred_language,
			visitor_language_override: form.visitor_language_override,
			enable_voice_input: form.enable_voice_input,
			enable_voice_output: form.enable_voice_output,
			voice_language: form.voice_language,
			enable_vision_understanding: form.enable_vision_understanding,
			enable_error_log: form.enable_error_log,
			error_log_retention_days: form.error_log_retention_days,
			order_tracking_prompt_msg: form.order_tracking_prompt_msg,
			order_tracking_login_msg: form.order_tracking_login_msg,
			order_mismatch_msg: form.order_mismatch_msg,
			support_ticket_msg: form.support_ticket_msg,
			no_data_message: form.no_data_message,
		};

		const displayPayload = {
			entire_site: form.entire_site,
			show_on_mobile: form.show_on_mobile,
			exclude_pages: form.exclude_pages,
			position: form.position,
			widget_size: form.widget_size,
			widget_size_unit: form.widget_size_unit,
			custom_vertical_align: form.custom_vertical_align,
			custom_vertical: form.custom_vertical,
			custom_vertical_unit: form.custom_vertical_unit,
			custom_side: form.custom_side,
			custom_horizontal: form.custom_horizontal,
			custom_horizontal_unit: form.custom_horizontal_unit,
			launcher_text: form.launcher_text,
			assistant_icon: form.assistant_icon,
			launcher_icon_preset: form.launcher_icon_preset,
			trigger_type: form.trigger_type,
			trigger_delay: form.trigger_delay,
			time_delay: form.time_delay,
			enable_smart_triggers: form.enable_smart_triggers,
			trigger_scroll_depth: form.trigger_scroll_depth,
			trigger_exit_intent: form.trigger_exit_intent,
			trigger_inactivity: form.trigger_inactivity,
			trigger_action: form.trigger_action,
			proactive_bubble_message: form.proactive_bubble_message,
			target_devices: form.target_devices,
			target_users: form.target_users,
			url_rules: form.url_rules,
		};

		try {
			await Promise.all([
				apiFetch({
					path: '/dctc-ai/v1/save-bot-settings',
					method: 'POST',
					data: botPayload,
				}),
				apiFetch({
					path: '/dctc-ai/v1/save-display-settings',
					method: 'POST',
					data: displayPayload,
				}),
			]);

			onSave({
				chatbot: { ...chatbot, ...botPayload },
				display: { ...display, ...displayPayload },
			});
			setSaved(form);
			showNotice(__('All chatbot & display settings saved successfully!', 'dragwyb-click-to-chat'));
		} catch (err) {
			showNotice(err.message || __('Failed to save settings', 'dragwyb-click-to-chat'), 'error');
		} finally {
			setSaving(false);
			window.dispatchEvent(new CustomEvent('dctc_ai_saving_end'));
		}
	};

	useEffect(() => {
		const handleTriggerSave = () => {
			onSubmit();
		};
		window.addEventListener('dctc_ai_trigger_save', handleTriggerSave);
		return () => window.removeEventListener('dctc_ai_trigger_save', handleTriggerSave);
	}, [onSubmit]);

	const getBubbleRadiusStyle = () => {
		if (form.bubble_style === 'square') return '4px';
		if (form.bubble_style === 'pill') return '20px';
		return '12px';
	};

	const getQuestionRadiusStyle = () => {
		if (form.pre_questions_border_radius === 'square') return '4px';
		if (form.pre_questions_border_radius === 'pill') return '999px';
		return '8px';
	};

	const renderBotAvatarIcon = (size = 28) => {
		if (form.bot_avatar) {
			return <img src={form.bot_avatar} alt="" />;
		}
		switch (form.bot_icon_preset) {
			case 'initial':
				return <span>{initial}</span>;
			case 'sparkle':
				return <span className="dashicons dashicons-star-filled" style={{ fontSize: `${size}px`, width: `${size}px`, height: `${size}px` }} />;
			case 'support':
				return <span className="dashicons dashicons-format-audio" style={{ fontSize: `${size}px`, width: `${size}px`, height: `${size}px` }} />;
			case 'chat':
				return <span className="dashicons dashicons-format-chat" style={{ fontSize: `${size}px`, width: `${size}px`, height: `${size}px` }} />;
			case 'bot':
			default:
				return <span className="dashicons dashicons-superhero-alt" style={{ fontSize: `${size}px`, width: `${size}px`, height: `${size}px` }} />;
		}
	};

	const renderUserAvatarIcon = (size = 28) => {
		if (form.user_avatar) {
			return <img src={form.user_avatar} alt="" />;
		}
		switch (form.user_icon_preset) {
			case 'user-circle':
				return <span className="dashicons dashicons-admin-users" style={{ fontSize: `${size}px`, width: `${size}px`, height: `${size}px` }} />;
			case 'business':
				return <span className="dashicons dashicons-businessman" style={{ fontSize: `${size}px`, width: `${size}px`, height: `${size}px` }} />;
			case 'smile':
				return <span className="dashicons dashicons-smiley" style={{ fontSize: `${size}px`, width: `${size}px`, height: `${size}px` }} />;
			case 'star':
				return <span className="dashicons dashicons-star-empty" style={{ fontSize: `${size}px`, width: `${size}px`, height: `${size}px` }} />;
			case 'user':
			default:
				return <span className="dashicons dashicons-admin-users" style={{ fontSize: `${size}px`, width: `${size}px`, height: `${size}px` }} />;
		}
	};

	const renderLauncherIcon = () => {
		if (form.assistant_icon) {
			return <img src={form.assistant_icon} alt="" style={{ width: '100%', height: '100%', objectFit: 'cover', borderRadius: '50%' }} />;
		}
		switch (form.launcher_icon_preset) {
			case 'bot':
				return <span className="dashicons dashicons-superhero-alt" style={{ fontSize: '26px' }} />;
			case 'sparkle':
				return <span className="dashicons dashicons-star-filled" style={{ fontSize: '26px' }} />;
			case 'support':
				return <span className="dashicons dashicons-format-audio" style={{ fontSize: '26px' }} />;
			case 'help':
				return <span className="dashicons dashicons-editor-help" style={{ fontSize: '26px' }} />;
			case 'whatsapp':
				return <span className="dashicons dashicons-smartphone" style={{ fontSize: '26px' }} />;
			case 'chat':
			default:
				return <span className="dashicons dashicons-format-chat" style={{ fontSize: '26px' }} />;
		}
	};

	return (
		<div className="dctc-ai-bot-settings">
			{/* Subtabs Navigation Bar */}
			<nav
				className="dctc-ai-subtab-nav"
				role="tablist"
				aria-label={__('Chatbot settings sections', 'dragwyb-click-to-chat')}
			>
				{SUBTABS.map((tab) => (
					<button
						key={tab.id}
						type="button"
						role="tab"
						aria-selected={subtab === tab.id}
						className={`dctc-ai-subtab-btn ${subtab === tab.id ? 'active' : ''}`}
						onClick={() => setSubtab(tab.id)}
					>
						<span className={`dashicons ${tab.icon}`} aria-hidden="true" />
						<div className="dctc-ai-subtab-btn__content">
							<span className="dctc-ai-subtab-btn__label">{tab.label}</span>
							<span className="dctc-ai-subtab-btn__hint">{tab.desc}</span>
						</div>
					</button>
				))}
			</nav>

			<form onSubmit={onSubmit}>
				{subtab === 'general' && (
					<GeneralSubtab
						form={form}
						setField={setField}
						copiedShortcode={copiedShortcode}
						copyShortcode={copyShortcode}
						comboRef={comboRef}
						search={search}
						setSearch={setSearch}
						openSearch={openSearch}
						setOpenSearch={setOpenSearch}
						loadingContent={loadingContent}
						filtered={filtered}
						ids={ids}
						selectedIds={selectedIds}
						content={content}
						addExclude={addExclude}
						toggleExclude={toggleExclude}
						onOpenMessages={() => setSubtab('messages')}
					/>
				)}

				{subtab === 'advanced' && (
					<AdvancedSubtab
						form={form}
						setField={setField}
						usageStats={usageStats}
						isWcActive={isWcActive}
					/>
				)}

				{subtab === 'style' && (
					<StyleSubtab
						form={form}
						setField={setField}
						openBotMedia={openBotMedia}
						openUserMedia={openUserMedia}
						openAssistantIconMedia={openAssistantIconMedia}
						openChatBgMedia={openChatBgMedia}
						applyStylePreset={applyStylePreset}
						renderBotAvatarIcon={renderBotAvatarIcon}
						renderUserAvatarIcon={renderUserAvatarIcon}
						renderLauncherIcon={renderLauncherIcon}
						getBubbleRadiusStyle={getBubbleRadiusStyle}
					/>
				)}

				{subtab === 'messages' && (
					<MessagesSubtab
						form={form}
						setField={setField}
						addActionButton={addActionButton}
						updateActionButton={updateActionButton}
						removeActionButton={removeActionButton}
						getQuestionRadiusStyle={getQuestionRadiusStyle}
						isWcActive={isWcActive}
					/>
				)}
			</form>
		</div>
	);
}
