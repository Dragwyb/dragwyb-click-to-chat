/**
 * Chatbot Settings — Unified General, Advance & Style Management.
 * Features conditional-based cards where switchers are placed directly on card headers
 * and associated settings expand seamlessly when enabled.
 */
import { useState, useEffect, useRef } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import Toggle from '../components/Toggle';

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

const LAUNCHER_PRESETS = [
	{ id: 'chat', label: __('💬 Chat Bubble (Default)', 'dragwyb-click-to-chat') },
	{ id: 'bot', label: __('🤖 AI Robot', 'dragwyb-click-to-chat') },
	{ id: 'sparkle', label: __('✨ AI Magic Sparkle', 'dragwyb-click-to-chat') },
	{ id: 'support', label: __('🎧 Support Agent', 'dragwyb-click-to-chat') },
	{ id: 'help', label: __('❓ Help Desk', 'dragwyb-click-to-chat') },
	{ id: 'whatsapp', label: __('📱 WhatsApp Chat', 'dragwyb-click-to-chat') },
];

/**
 * SwitcherCard: Standalone card with a master switch directly in its header.
 * Conditionally reveals child settings when active or shows a clean summary when disabled.
 */
function SwitcherCard({
	id,
	title,
	desc,
	icon,
	checked,
	onChange,
	badge = null,
	children,
	disabledNotice = null,
	disabledContent = null,
}) {
	return (
		<section className={`dctc-ai-card dctc-ai-conditional-card ${checked ? 'is-active' : 'is-disabled'}`}>
			<header className="dctc-ai-card__header dctc-ai-conditional-card__header">
				<div className="dctc-ai-card__header-left">
					<div className={`dctc-ai-card-icon ${checked ? 'dctc-ai-card-icon--active' : ''}`} aria-hidden="true">
						<span className={`dashicons ${icon}`} />
					</div>
					<div>
						<div className="dctc-ai-header-with-badge">
							<h2 className="dctc-ai-card__title">{title}</h2>
							{badge && <span className="dctc-ai-mini-badge">{badge}</span>}
							<span className={`dctc-ai-status-pill ${checked ? 'is-active' : 'is-inactive'}`}>
								<span className="dctc-ai-status-indicator" />
								{checked ? __('Enabled', 'dragwyb-click-to-chat') : __('Disabled', 'dragwyb-click-to-chat')}
							</span>
						</div>
						<p className="dctc-ai-card__desc">{desc}</p>
					</div>
				</div>
				<div className="dctc-ai-conditional-card__toggle-wrap">
					<Toggle id={id} checked={checked} onChange={onChange} />
				</div>
			</header>

			{checked ? (
				<div className="dctc-ai-card__body dctc-ai-conditional-card__body">
					{children}
				</div>
			) : (
				(disabledNotice || disabledContent) && (
					<div className="dctc-ai-conditional-card__disabled-notice">
						{disabledNotice && (
							<div className="dctc-ai-disabled-notice-row">
								<span className="dashicons dashicons-info" aria-hidden="true" />
								<span>{disabledNotice}</span>
							</div>
						)}
						{disabledContent}
					</div>
				)
			)}
		</section>
	);
}

function SettingCard({ id, title, desc, checked, onChange, icon = null, badge = null }) {
	return (
		<div className={`dctc-ai-bot-setting-card ${checked ? 'is-active' : ''}`}>
			<div className="dctc-ai-bot-setting-card__main">
				{icon && (
					<span className={`dashicons ${icon} dctc-ai-setting-row-icon`} aria-hidden="true" />
				)}
				<div className="dctc-ai-bot-setting-card__text">
					<div className="dctc-ai-bot-setting-card__title-row">
						<strong>{title}</strong>
						{badge && <span className="dctc-ai-mini-badge">{badge}</span>}
					</div>
					{desc && <span className="dctc-ai-bot-setting-card__desc">{desc}</span>}
				</div>
			</div>
			<Toggle id={id} checked={checked} onChange={onChange} />
		</div>
	);
}

function ColorField({ id, label, value, onChange, desc = null }) {
	return (
		<div className="dctc-ai-bot-field dctc-ai-color-field-wrap">
			<label htmlFor={id}>{label}</label>
			{desc && <p className="dctc-ai-bot-hint">{desc}</p>}
			<div className="dctc-ai-bot-color-field">
				<input
					type="color"
					id={id}
					className="dctc-ai-bot-color-picker"
					value={value}
					onChange={(e) => onChange(e.target.value)}
				/>
				<span className="dctc-ai-bot-color-value">{value}</span>
			</div>
		</div>
	);
}

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
		lead_trigger_type: chatbot.lead_trigger_type || 'manual',
		lead_trigger_delay: chatbot.lead_trigger_delay ?? 30,
		lead_trigger_message_count: chatbot.lead_trigger_message_count ?? 3,
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

		// Style / Avatars & Bubbles
		bot_avatar: chatbot.bot_avatar || '',
		bot_icon_preset: chatbot.bot_icon_preset || 'bot',
		user_avatar: chatbot.user_avatar || '',
		user_icon_preset: chatbot.user_icon_preset || 'user',
		bubble_style: chatbot.bubble_style || 'rounded',
		show_bot_avatar_in_chat: chatbot.show_bot_avatar_in_chat !== false,
		show_user_avatar_in_chat: chatbot.show_user_avatar_in_chat !== false,
		show_sources: chatbot.show_sources !== false,
	});

	const [form, setForm] = useState(buildForm);
	const [saved, setSaved] = useState(buildForm);
	const dirty = JSON.stringify(form) !== JSON.stringify(saved);

	useEffect(() => {
		let alive = true;
		setLoadingContent(true);
		apiFetch({ path: '/dctc-ai/v1/all-content' })
			.then((items) => {
				if (alive && Array.isArray(items)) {
					setContent(items);
				}
			})
			.finally(() => {
				if (alive) {
					setLoadingContent(false);
				}
			});

		apiFetch({ path: '/dctc-ai/v1/usage-stats' })
			.then((res) => {
				if (alive && res?.stats) {
					setUsageStats(res.stats);
				}
			})
			.catch(() => {});

		return () => {
			alive = false;
		};
	}, []);

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
			title: __('Select User Avatar', 'dragwyb-click-to-chat'),
			button: { text: __('Use as User Avatar', 'dragwyb-click-to-chat') },
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
			title: __('Select Assistant Launcher Icon', 'dragwyb-click-to-chat'),
			button: { text: __('Use as Icon', 'dragwyb-click-to-chat') },
			library: { type: 'image' },
			multiple: false,
		});
		frame.on('select', () => {
			const attachment = frame.state().get('selection').first().toJSON();
			setField('assistant_icon', attachment.url);
		});
		frame.open();
	};

	// Form Submission: saves to both bot and display settings endpoints
	const onSubmit = async (e) => {
		e.preventDefault();
		setSaving(true);

		const botPayload = {
			bot_name: form.bot_name,
			primary_color: form.primary_color,
			greeting_msg: form.greeting_msg,
			api_error_msg: form.api_error_msg,
			support_url: form.support_url,
			pre_question_1: form.pre_question_1,
			pre_question_2: form.pre_question_2,
			pre_question_3: form.pre_question_3,
			pre_question_4: form.pre_question_4,
			pre_questions_bg_color: form.pre_questions_bg_color,
			pre_questions_text_color: form.pre_questions_text_color,
			pre_questions_border_color: form.pre_questions_border_color,
			pre_questions_border_radius: form.pre_questions_border_radius,
			bot_avatar: form.bot_avatar,
			bubble_style: form.bubble_style,
			show_bot_avatar_in_chat: form.show_bot_avatar_in_chat,
			show_user_avatar_in_chat: form.show_user_avatar_in_chat,
			show_sources: form.show_sources,
			bot_icon_preset: form.bot_icon_preset,
			user_avatar: form.user_avatar,
			user_icon_preset: form.user_icon_preset,
			enable_uploads: form.enable_uploads,
			allowed_file_types: form.allowed_file_types,
			excluded_file_types: form.excluded_file_types,
			max_upload_size: form.max_upload_size,
			max_files_per_message: form.max_files_per_message,
			store_chat_attachments: form.store_chat_attachments,
			action_buttons: form.action_buttons,
			save_chat: form.save_chat,
			chat_retention_days: form.chat_retention_days,
			memory_window_size: form.memory_window_size,
			ask_email: form.ask_email,
			enable_pre_questions: form.enable_pre_questions,
			rate_limit_per_minute: form.rate_limit_per_minute,
			enable_usage_limits: form.enable_usage_limits,
			visitor_daily_message_limit: form.visitor_daily_message_limit,
			monthly_request_budget: form.monthly_request_budget,
			budget_limit_message: form.budget_limit_message,
			enable_budget_email_alerts: form.enable_budget_email_alerts,
			alert_email: form.alert_email,
			enable_lead_capture: form.enable_lead_capture,
			lead_trigger_type: form.lead_trigger_type,
			lead_trigger_delay: form.lead_trigger_delay,
			lead_trigger_message_count: form.lead_trigger_message_count,
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
		}
	};

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
			{ /* Professional Header Banner */}
			<header className="dctc-ai-section-header">
				<div className="dctc-ai-section-header__left">
					<div className="dctc-ai-section-header__icon-box">
						<span className="dashicons dashicons-admin-settings" />
					</div>
					<div>
						<div className="dctc-ai-section-header__title-row">
							<h1 className="dctc-ai-section-header__title">
								{__('Chatbot Settings & Customizer', 'dragwyb-click-to-chat')}
							</h1>
							<span className={`dctc-ai-status-pill ${form.entire_site ? 'is-active' : ''}`}>
								{form.entire_site ? __('Widget Active Site-Wide', 'dragwyb-click-to-chat') : __('Shortcode Only', 'dragwyb-click-to-chat')}
							</span>
						</div>
						<p className="dctc-ai-section-header__desc">
							{subtab === 'general' && __('Configure bot identity, site visibility rules, chat session retention, and language support.', 'dragwyb-click-to-chat')}
							{subtab === 'advanced' && __('Fine-tune auto triggers, rate limits, monthly request budget, lead qualification, and tools.', 'dragwyb-click-to-chat')}
							{subtab === 'style' && __('Design the circular launcher, custom bot avatars, branding colors, and screen positioning.', 'dragwyb-click-to-chat')}
							{subtab === 'messages' && __('Customize welcome greetings, starter question pills, action header buttons, and fallback notices.', 'dragwyb-click-to-chat')}
						</p>
					</div>
				</div>

				<div className="dctc-ai-section-header__right">
					<button
						type="button"
						className="dctc-ai-btn dctc-ai-btn-primary dctc-ai-btn-header-save"
						onClick={onSubmit}
						disabled={saving || !dirty}
					>
						<span className={`dashicons ${saving ? 'dashicons-update spin-anim' : 'dashicons-saved'}`} />
						{saving ? __('Saving…', 'dragwyb-click-to-chat') : dirty ? __('Save Changes', 'dragwyb-click-to-chat') : __('Saved', 'dragwyb-click-to-chat')}
					</button>
				</div>
			</header>

			{ /* Modern Subtabs Navigation Bar */}
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
				{ /* =========================================================================
				     GENERAL SUBTAB: Bot Identity, Continuity & Site Display
				   ========================================================================= */ }
				{subtab === 'general' && (
					<div className="dctc-ai-tab-panel-section">
						{ /* 1. Site-Wide Activation & Visibility Rules (Conditional Switcher Card) */}
						<SwitcherCard
							id="entire_site"
							title={__('Site-Wide Chatbot Widget', 'dragwyb-click-to-chat')}
							desc={__('Activate the floating AI chat assistant across your public website pages.', 'dragwyb-click-to-chat')}
							icon="dashicons-desktop"
							checked={form.entire_site}
							onChange={(v) => setField('entire_site', v)}
							disabledNotice={__('Site-wide floating button is currently turned off.', 'dragwyb-click-to-chat')}
							disabledContent={
								<div className="dctc-ai-shortcode-guide-card">
									<div className="dctc-ai-shortcode-guide-header">
										<div className="dctc-ai-shortcode-guide-icon-box">
											<span className="dashicons dashicons-shortcode" />
										</div>
										<div>
											<h3 className="dctc-ai-shortcode-guide-title">
												{__('Show Chatbot on Specific Pages Using Shortcode', 'dragwyb-click-to-chat')}
											</h3>
											<p className="dctc-ai-shortcode-guide-subtitle">
												{__('Since site-wide floating widget is disabled, you can embed the AI Chatbot directly on specific pages or posts using the shortcode below.', 'dragwyb-click-to-chat')}
											</p>
										</div>
									</div>

									<div className="dctc-ai-shortcode-box">
										<div className="dctc-ai-shortcode-box__tag-wrap">
											<code className="dctc-ai-shortcode-code">[dctc_ai]</code>
										</div>
										<button
											type="button"
											className={`dctc-ai-btn dctc-ai-btn-sm ${copiedShortcode ? 'dctc-ai-btn-primary is-copied' : 'dctc-ai-btn-secondary'}`}
											onClick={copyShortcode}
										>
											<span className={`dashicons ${copiedShortcode ? 'dashicons-yes-alt' : 'dashicons-admin-page'}`} />
											{copiedShortcode ? __('Copied to Clipboard!', 'dragwyb-click-to-chat') : __('Copy Shortcode', 'dragwyb-click-to-chat')}
										</button>
									</div>

									<div className="dctc-ai-shortcode-instructions-grid">
										<div className="dctc-ai-shortcode-inst-item">
											<span className="dashicons dashicons-block-default" />
											<div className="dctc-ai-shortcode-inst-text">
												<strong>{__('Gutenberg Block Editor', 'dragwyb-click-to-chat')}</strong>
												<span>{__('Add a Shortcode block and paste [dctc_ai].', 'dragwyb-click-to-chat')}</span>
											</div>
										</div>
										<div className="dctc-ai-shortcode-inst-item">
											<span className="dashicons dashicons-layout" />
											<div className="dctc-ai-shortcode-inst-text">
												<strong>{__('Elementor & Page Builders', 'dragwyb-click-to-chat')}</strong>
												<span>{__('Drag a Shortcode or HTML widget into your template and paste [dctc_ai].', 'dragwyb-click-to-chat')}</span>
											</div>
										</div>
										<div className="dctc-ai-shortcode-inst-item">
											<span className="dashicons dashicons-media-code" />
											<div className="dctc-ai-shortcode-inst-text">
												<strong>{__('PHP Templates', 'dragwyb-click-to-chat')}</strong>
												<span><code>&lt;?php echo do_shortcode( '[dctc_ai]' ); ?&gt;</code></span>
											</div>
										</div>
									</div>
								</div>
							}
						>
							<div className="dctc-ai-form-stack">
								<div className="dctc-ai-bot-field">
									<label htmlFor="launcher_text">
										{__('Button Call-to-Action Text Badge (Optional)', 'dragwyb-click-to-chat')}
									</label>
									<div className="dctc-ai-input-with-icon">
										<span className="dashicons dashicons-testimonial" />
										<input
											type="text"
											id="launcher_text"
											className="dctc-ai-bot-input"
											value={form.launcher_text}
											onChange={(e) => setField('launcher_text', e.target.value)}
											placeholder={__('e.g. Chat with us, How can we help?', 'dragwyb-click-to-chat')}
										/>
									</div>
									<p className="dctc-ai-bot-hint">
										{__('Small text pill displayed adjacent to the circular floating button.', 'dragwyb-click-to-chat')}
									</p>
								</div>

								<SettingCard
									id="show_on_mobile"
									title={__('Enable on Mobile Devices', 'dragwyb-click-to-chat')}
									desc={__('Optimize touch targets and modal dimensions for smartphones and tablets.', 'dragwyb-click-to-chat')}
									icon="dashicons-smartphone"
									checked={form.show_on_mobile}
									onChange={(v) => setField('show_on_mobile', v)}
								/>

								{ /* Exclusion Search Combobox */}
								<div className="dctc-ai-bot-field">
									<label htmlFor="exclude_search_input">
										{__('Exclude Content (Pages, Posts, Custom Types)', 'dragwyb-click-to-chat')}
									</label>
									<div ref={comboRef} className="dctc-ai-combobox-wrapper">
										<div className="dctc-ai-combobox-input-wrap">
											<span className="dashicons dashicons-search dctc-ai-combobox-search-icon" />
											<input
												type="text"
												id="exclude_search_input"
												className="dctc-ai-bot-input dctc-ai-combobox-input"
												value={search}
												onChange={(e) => {
													setSearch(e.target.value);
													setOpenSearch(true);
												}}
												onFocus={() => setOpenSearch(true)}
												onKeyDown={(e) => {
													if (e.key === 'Enter') {
														e.preventDefault();
														addExclude();
													}
												}}
												placeholder={
													loadingContent
														? __('Loading website content…', 'dragwyb-click-to-chat')
														: __('Search pages or posts to exclude by title or ID…', 'dragwyb-click-to-chat')
												}
											/>
											<span className="dctc-ai-combobox-arrow">▼</span>
										</div>

										{openSearch && (
											<div className="dctc-ai-combobox-dropdown">
												{filtered.length === 0 ? (
													<div className="dctc-ai-combobox-empty">
														{search.trim() ? (
															<div>
																<div>{__('No matching page/post found.', 'dragwyb-click-to-chat')}</div>
																<button
																	type="button"
																	className="dctc-ai-btn dctc-ai-btn-primary dctc-ai-btn-sm"
																	style={{ marginTop: '6px' }}
																	onClick={() => addExclude()}
																>
																	{sprintf(
																		/* translators: %s: custom ID */
																		__('Add "%s" as custom ID', 'dragwyb-click-to-chat'),
																		search.trim()
																	)}
																</button>
															</div>
														) : (
															__('No content available.', 'dragwyb-click-to-chat')
														)}
													</div>
												) : (
													filtered.map((item) => {
														const idStr = String(item.id);
														const selected = ids.includes(idStr);
														return (
															<div
																key={item.id}
																className={`dctc-ai-combobox-item ${selected ? 'is-selected' : ''}`}
																onClick={() => toggleExclude(idStr)}
															>
																<div className="dctc-ai-combobox-item__main">
																	<span className={`dctc-ai-type-pill is-${item.type.toLowerCase()}`}>
																		{item.type}
																	</span>
																	<span className="dctc-ai-combobox-item__title">{item.title}</span>
																	<span className="dctc-ai-combobox-item__id">(ID: {item.id})</span>
																</div>
																{selected && <span className="dashicons dashicons-yes-alt dctc-ai-check-icon" />}
															</div>
														);
													})
												)}
											</div>
										)}

										<div className="dctc-ai-selected-tokens">
											{ids.length === 0 ? (
												<div className="dctc-ai-no-tokens-banner">
													<span className="dashicons dashicons-yes-alt" />
													<span>{__('Chatbot active on all pages (no content currently excluded).', 'dragwyb-click-to-chat')}</span>
												</div>
											) : (
												ids.map((id) => {
													const item = content.find((c) => String(c.id) === id);
													const label = item
														? `[${item.type}] ${item.title}`
														: sprintf(
															/* translators: %s: post ID */
															__('ID %s', 'dragwyb-click-to-chat'),
															id
														);
													return (
														<span key={id} className="dctc-ai-token-chip">
															<span>{label}</span>
															<button
																type="button"
																className="dctc-ai-token-chip__remove"
																onClick={() => {
																	const next = selectedIds().filter((x) => x !== id);
																	setField('exclude_pages', next.join(', '));
																}}
																aria-label={__('Remove', 'dragwyb-click-to-chat')}
															>
																✕
															</button>
														</span>
													);
												})
											)}
										</div>
									</div>
								</div>
							</div>
						</SwitcherCard>

						{ /* 2. Bot Identity & Support Desk Integration */}
						<section className="dctc-ai-card">
							<header className="dctc-ai-card__header">
								<div className="dctc-ai-card__header-left">
									<div className="dctc-ai-card-icon">
										<span className="dashicons dashicons-id-alt" />
									</div>
									<div>
										<h2 className="dctc-ai-card__title">
											{__('AI Persona & Helpdesk Integration', 'dragwyb-click-to-chat')}
										</h2>
										<p className="dctc-ai-card__desc">
											{__('Define the public assistant name and target support URL for handoffs and inquiries.', 'dragwyb-click-to-chat')}
										</p>
									</div>
								</div>
							</header>

							<div className="dctc-ai-card__body">
								<div className="dctc-ai-form-stack">
									<div className="dctc-ai-grid-2col">
										<div className="dctc-ai-bot-field">
											<label htmlFor="bot_name">
												{__('AI Assistant Public Name', 'dragwyb-click-to-chat')}
											</label>
											<div className="dctc-ai-input-with-icon">
												<span className="dashicons dashicons-admin-users" />
												<input
													type="text"
													id="bot_name"
													className="dctc-ai-bot-input"
													value={form.bot_name}
													onChange={(e) => setField('bot_name', e.target.value)}
													placeholder={__('AI Assistant', 'dragwyb-click-to-chat')}
												/>
											</div>
											<p className="dctc-ai-bot-hint">
												{__('Shown at the top of the chat window and alongside assistant replies.', 'dragwyb-click-to-chat')}
											</p>
										</div>

										<div className="dctc-ai-bot-field">
											<label htmlFor="support_url">
												{__('Support Helpdesk URL', 'dragwyb-click-to-chat')}
											</label>
											<div className="dctc-ai-input-with-icon">
												<span className="dashicons dashicons-admin-links" />
												<input
													type="url"
													id="support_url"
													className="dctc-ai-bot-input"
													value={form.support_url}
													onChange={(e) => setField('support_url', e.target.value)}
													placeholder={__('https://example.com/support', 'dragwyb-click-to-chat')}
												/>
											</div>
											<p className="dctc-ai-bot-hint">
												{__('Destination URL for support links in notifications and error fallback responses.', 'dragwyb-click-to-chat')}
											</p>
										</div>
									</div>

									<div style={{ padding: '0.85rem 1rem', background: '#f8fafc', borderRadius: '8px', border: '1px solid #e2e8f0', display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: '1rem', marginTop: '0.5rem' }}>
										<div style={{ display: 'flex', alignItems: 'center', gap: '0.65rem' }}>
											<span className="dashicons dashicons-format-chat" style={{ color: '#6366f1', fontSize: '1.25rem' }} />
											<span style={{ fontSize: '0.875rem', color: '#475569' }}>
												{__('Looking to customize Greeting, API Errors, Order Tracking, or Support notices?', 'dragwyb-click-to-chat')}
											</span>
										</div>
										<button
											type="button"
											className="dctc-ai-btn dctc-ai-btn-sm dctc-ai-btn-secondary"
											onClick={() => setSubtab('messages')}
										>
											{__('Open Messages Tab →', 'dragwyb-click-to-chat')}
										</button>
									</div>
								</div>
							</div>
						</section>
					</div>
				)}

				{ /* =========================================================================
				     ADVANCE SUBTAB: Triggers, Security, File Uploads, Rate Limits, Logs
				   ========================================================================= */ }
				{subtab === 'advanced' && (
					<div className="dctc-ai-tab-panel-section">
						{ /* 1. Triggers & Auto-Open Timing */}
						<section className="dctc-ai-card">
							<header className="dctc-ai-card__header">
								<div className="dctc-ai-card__header-left">
									<div className="dctc-ai-card-icon">
										<span className="dashicons dashicons-clock" />
									</div>
									<div>
										<h2 className="dctc-ai-card__title">
											{__('Trigger & Auto-Open Behaviors', 'dragwyb-click-to-chat')}
										</h2>
										<p className="dctc-ai-card__desc">
											{__('Control when the floating button appears and when the chat modal pops open.', 'dragwyb-click-to-chat')}
										</p>
									</div>
								</div>
							</header>

							<div className="dctc-ai-card__body">
								<div className="dctc-ai-grid-2col">
									<div className="dctc-ai-bot-field">
										<label htmlFor="trigger_type">
											{__('Chat Window Auto-Open Mode', 'dragwyb-click-to-chat')}
										</label>
										<select
											id="trigger_type"
											className="dctc-ai-bot-select"
											value={form.trigger_type}
											onChange={(e) => setField('trigger_type', e.target.value)}
										>
											<option value="click">{__('Wait for Visitor Click (Default)', 'dragwyb-click-to-chat')}</option>
											<option value="delay">{__('Auto-Open After Page Load Delay', 'dragwyb-click-to-chat')}</option>
										</select>
										<p className="dctc-ai-bot-hint">
											{__('Choose whether to automatically pop up the chat window on visit.', 'dragwyb-click-to-chat')}
										</p>
									</div>

									{form.trigger_type === 'delay' && (
										<div className="dctc-ai-bot-field">
											<label htmlFor="trigger_delay">
												{__('Auto-Open Delay (Seconds)', 'dragwyb-click-to-chat')}
											</label>
											<div className="dctc-ai-inline-input-row">
												<input
													type="number"
													id="trigger_delay"
													className="dctc-ai-bot-input dctc-ai-input--narrow"
													min="1"
													max="60"
													value={form.trigger_delay}
													onChange={(e) => setField('trigger_delay', parseInt(e.target.value, 10) || 5)}
												/>
												<span className="dctc-ai-input-unit-label">{__('seconds', 'dragwyb-click-to-chat')}</span>
											</div>
											<p className="dctc-ai-bot-hint">
												{__('Seconds to wait after page load before opening chat modal.', 'dragwyb-click-to-chat')}
											</p>
										</div>
									)}

									<div className="dctc-ai-bot-field">
										<label htmlFor="time_delay">
											{__('Floating Button Appearance Delay', 'dragwyb-click-to-chat')}
										</label>
										<div className="dctc-ai-inline-input-row">
											<input
												type="number"
												id="time_delay"
												className="dctc-ai-bot-input dctc-ai-input--narrow"
												min="0"
												max="60"
												step="1"
												value={form.time_delay}
												onChange={(e) =>
													setField(
														'time_delay',
														e.target.value === '' ? '' : Number(e.target.value)
													)
												}
											/>
											<span className="dctc-ai-input-unit-label">{__('seconds', 'dragwyb-click-to-chat')}</span>
										</div>
										<p className="dctc-ai-bot-hint">
											{__('Set 0 for immediate display. 2–4s gives a smoother page load feel.', 'dragwyb-click-to-chat')}
										</p>
									</div>
								</div>
							</div>
						</section>

						{ /* 2. Security & Rate Limiting */}
						<section className="dctc-ai-card">
							<header className="dctc-ai-card__header">
								<div className="dctc-ai-card__header-left">
									<div className="dctc-ai-card-icon">
										<span className="dashicons dashicons-shield" />
									</div>
									<div>
										<h2 className="dctc-ai-card__title">
											{__('Security & Rate Limiting', 'dragwyb-click-to-chat')}
										</h2>
										<p className="dctc-ai-card__desc">
											{__('Throttle requests per visitor IP to protect your AI API billing.', 'dragwyb-click-to-chat')}
										</p>
									</div>
								</div>
							</header>

							<div className="dctc-ai-card__body">
								<div className="dctc-ai-bot-field">
									<label htmlFor="rate_limit_per_minute">
										{__('Rate Limit (Requests per minute per visitor)', 'dragwyb-click-to-chat')}
									</label>
									<div className="dctc-ai-inline-input-row">
										<input
											type="number"
											id="rate_limit_per_minute"
											className="dctc-ai-bot-input dctc-ai-input--narrow"
											min="1"
											max="300"
											value={form.rate_limit_per_minute}
											onChange={(e) =>
												setField('rate_limit_per_minute', parseInt(e.target.value, 10) || 1)
											}
										/>
										<span className="dctc-ai-input-unit-label">
											{__('requests / minute', 'dragwyb-click-to-chat')}
										</span>
									</div>
									<p className="dctc-ai-bot-hint">
										{__('Recommended: 15–30 requests/min. Blocks aggressive automated scripts.', 'dragwyb-click-to-chat')}
									</p>
								</div>
							</div>
						</section>

						{ /* 3. AI Usage & Cost Controls (Conditional Switcher Card) */}
						<SwitcherCard
							id="enable_usage_limits"
							title={__('AI Usage & Cost Controls', 'dragwyb-click-to-chat')}
							desc={__('Set visitor daily message limits and monthly budget ceilings to prevent unexpected AI API costs.', 'dragwyb-click-to-chat')}
							icon="dashicons-chart-pie"
							badge={__('Cost Protection', 'dragwyb-click-to-chat')}
							checked={form.enable_usage_limits}
							onChange={(v) => setField('enable_usage_limits', v)}
							disabledNotice={__('Enable usage controls to enforce daily per-visitor limits and monthly budget ceilings.', 'dragwyb-click-to-chat')}
						>
							{usageStats && (
								<div className="dctc-ai-usage-meter-card" style={{ marginBottom: '1.25rem', padding: '1rem', background: '#f8fafc', borderRadius: '8px', border: '1px solid #e2e8f0' }}>
									<div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '0.5rem' }}>
										<span style={{ fontWeight: 600, fontSize: '0.9rem', color: '#1e293b' }}>
											{__('Monthly Request Usage', 'dragwyb-click-to-chat')}
										</span>
										<span style={{ fontSize: '0.85rem', color: '#64748b' }}>
											{sprintf(
												/* translators: 1: Current requests, 2: Budget */
												__('%1$s / %2$s requests (%3$s%%)', 'dragwyb-click-to-chat'),
												Number(usageStats.month_requests || 0).toLocaleString(),
												Number(form.monthly_request_budget || 0).toLocaleString(),
												usageStats.budget_percent || 0
											)}
										</span>
									</div>
									<div style={{ width: '100%', height: '8px', background: '#e2e8f0', borderRadius: '4px', overflow: 'hidden' }}>
										<div
											style={{
												width: `${Math.min(100, usageStats.budget_percent || 0)}%`,
												height: '100%',
												background: (usageStats.budget_percent || 0) > 85 ? '#ef4444' : (usageStats.budget_percent || 0) > 60 ? '#f59e0b' : '#10b981',
												transition: 'width 0.3s ease',
											}}
										/>
									</div>
									<div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '0.78rem', color: '#64748b', marginTop: '0.5rem' }}>
										<span>{sprintf(__('Today: %d requests', 'dragwyb-click-to-chat'), usageStats.today_requests || 0)}</span>
										<span>{sprintf(__('Est. Tokens this month: %s', 'dragwyb-click-to-chat'), Number(usageStats.month_tokens || 0).toLocaleString())}</span>
									</div>
								</div>
							)}

							<div className="dctc-ai-grid-2col">
								<div className="dctc-ai-bot-field">
									<label htmlFor="visitor_daily_message_limit">
										{__('Daily Message Limit per Visitor', 'dragwyb-click-to-chat')}
									</label>
									<div className="dctc-ai-inline-input-row">
										<input
											type="number"
											id="visitor_daily_message_limit"
											className="dctc-ai-bot-input dctc-ai-input--narrow"
											min="1"
											max="500"
											value={form.visitor_daily_message_limit}
											onChange={(e) =>
												setField('visitor_daily_message_limit', parseInt(e.target.value, 10) || 50)
											}
										/>
										<span className="dctc-ai-input-unit-label">
											{__('messages / day', 'dragwyb-click-to-chat')}
										</span>
									</div>
									<p className="dctc-ai-bot-hint">
										{__('Prevents a single user from running excessive queries in 24 hours. Recommended: 30–50.', 'dragwyb-click-to-chat')}
									</p>
								</div>

								<div className="dctc-ai-bot-field">
									<label htmlFor="monthly_request_budget">
										{__('Monthly Request Budget Ceiling', 'dragwyb-click-to-chat')}
									</label>
									<div className="dctc-ai-inline-input-row">
										<input
											type="number"
											id="monthly_request_budget"
											className="dctc-ai-bot-input dctc-ai-input--narrow"
											min="0"
											max="1000000"
											step="1"
											value={form.monthly_request_budget}
											onChange={(e) =>
												setField('monthly_request_budget', e.target.value === '' ? '' : Math.max(0, parseInt(e.target.value, 10) || 0))
											}
										/>
										<span className="dctc-ai-input-unit-label">
											{__('requests / month', 'dragwyb-click-to-chat')}
										</span>
									</div>
									<p className="dctc-ai-bot-hint">
										{__('Total AI requests permitted for the whole website per month. 0 = unlimited.', 'dragwyb-click-to-chat')}
									</p>
								</div>
							</div>

							<div className="dctc-ai-features-grid" style={{ marginTop: '1.25rem', paddingTop: '1.25rem', borderTop: '1px solid #f1f5f9' }}>
								<SettingCard
									id="enable_budget_email_alerts"
									title={__('Send Budget Threshold Email Alerts', 'dragwyb-click-to-chat')}
									desc={__('Receive an automated email alert when your monthly AI budget hits 80% and 100% capacity.', 'dragwyb-click-to-chat')}
									icon="dashicons-email"
									badge={__('Proactive', 'dragwyb-click-to-chat')}
									checked={!!form.enable_budget_email_alerts}
									onChange={(v) => setField('enable_budget_email_alerts', v)}
								/>

								{form.enable_budget_email_alerts && (
									<div className="dctc-ai-bot-field" style={{ marginTop: '0.75rem' }}>
										<label htmlFor="alert_email">
											{__('Alert Notification Email (Optional)', 'dragwyb-click-to-chat')}
										</label>
										<input
											type="email"
											id="alert_email"
											className="dctc-ai-bot-input"
											value={form.alert_email}
											onChange={(e) => setField('alert_email', e.target.value)}
											placeholder={__('Leave blank to use WordPress admin email', 'dragwyb-click-to-chat')}
										/>
									</div>
								)}
							</div>
						</SwitcherCard>

						{ /* 4. AI Lead Capture & CRM Webhook (Conditional Switcher Card) */}
						<SwitcherCard
							id="enable_lead_capture"
							title={__('AI Lead Capture & CRM Integration', 'dragwyb-click-to-chat')}
							desc={__('Capture high-intent prospective visitor contacts directly in chat, send instant email alerts, and sync with CRM webhooks.', 'dragwyb-click-to-chat')}
							icon="dashicons-id"
							badge={__('Lead Generation', 'dragwyb-click-to-chat')}
							checked={form.enable_lead_capture}
							onChange={(v) => setField('enable_lead_capture', v)}
							disabledNotice={__('Enable lead capture to collect contact inquiries, notify sales via email, and sync with webhooks.', 'dragwyb-click-to-chat')}
						>
							<div className="dctc-ai-grid-2col">
								<div className="dctc-ai-bot-field">
									<label htmlFor="lead_trigger_type">
										{__('Lead Capture Trigger Mode', 'dragwyb-click-to-chat')}
									</label>
									<select
										id="lead_trigger_type"
										className="dctc-ai-bot-select"
										value={form.lead_trigger_type}
										onChange={(e) => setField('lead_trigger_type', e.target.value)}
									>
										<option value="manual">{__('Manual Button in Chat Header / Menu', 'dragwyb-click-to-chat')}</option>
										<option value="time_delay">{__('Auto-Prompt After Time Delay', 'dragwyb-click-to-chat')}</option>
										<option value="message_count">{__('Auto-Prompt After Message Count', 'dragwyb-click-to-chat')}</option>
										<option value="intent">{__('Auto-Prompt on AI Purchase / Contact Intent', 'dragwyb-click-to-chat')}</option>
									</select>
									<p className="dctc-ai-bot-hint">
										{__('Decide when the lead capture card should be presented to visitors.', 'dragwyb-click-to-chat')}
									</p>
								</div>

								{form.lead_trigger_type === 'time_delay' && (
									<div className="dctc-ai-bot-field">
										<label htmlFor="lead_trigger_delay">
											{__('Trigger Time Delay (Seconds)', 'dragwyb-click-to-chat')}
										</label>
										<div className="dctc-ai-inline-input-row">
											<input
												type="number"
												id="lead_trigger_delay"
												className="dctc-ai-bot-input dctc-ai-input--narrow"
												min="5"
												max="300"
												value={form.lead_trigger_delay}
												onChange={(e) =>
													setField('lead_trigger_delay', parseInt(e.target.value, 10) || 30)
												}
											/>
											<span className="dctc-ai-input-unit-label">{__('seconds', 'dragwyb-click-to-chat')}</span>
										</div>
									</div>
								)}

								{form.lead_trigger_type === 'message_count' && (
									<div className="dctc-ai-bot-field">
										<label htmlFor="lead_trigger_message_count">
											{__('Trigger After Visitor Messages', 'dragwyb-click-to-chat')}
										</label>
										<div className="dctc-ai-inline-input-row">
											<input
												type="number"
												id="lead_trigger_message_count"
												className="dctc-ai-bot-input dctc-ai-input--narrow"
												min="1"
												max="20"
												value={form.lead_trigger_message_count}
												onChange={(e) =>
													setField('lead_trigger_message_count', parseInt(e.target.value, 10) || 3)
												}
											/>
											<span className="dctc-ai-input-unit-label">{__('messages', 'dragwyb-click-to-chat')}</span>
										</div>
									</div>
								)}
							</div>


							<div style={{ marginTop: '1.25rem', padding: '1rem', background: '#f8fafc', borderRadius: '8px', border: '1px solid #e2e8f0' }}>
								<strong style={{ display: 'block', marginBottom: '0.5rem', color: '#1e293b', fontSize: '0.9rem' }}>
									{__('Lead Capture & Qualification Fields to Show in Chat', 'dragwyb-click-to-chat')}
								</strong>
								<div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(180px, 1fr))', gap: '0.85rem' }}>
									{[
										{ key: 'name', label: __('Full Name', 'dragwyb-click-to-chat') },
										{ key: 'email', label: __('Email Address', 'dragwyb-click-to-chat') },
										{ key: 'phone', label: __('Phone / WhatsApp', 'dragwyb-click-to-chat') },
										{ key: 'company', label: __('Company / Org', 'dragwyb-click-to-chat') },
										{ key: 'company_size', label: __('Company Size', 'dragwyb-click-to-chat') },
										{ key: 'budget', label: __('Budget Range', 'dragwyb-click-to-chat') },
										{ key: 'timeline', label: __('Purchase Timeline', 'dragwyb-click-to-chat') },
										{ key: 'interest', label: __('Product / Service Interest', 'dragwyb-click-to-chat') },
										{ key: 'requirement', label: __('Requirement / Notes', 'dragwyb-click-to-chat') },
									].map((f) => (
										<label key={f.key} style={{ display: 'inline-flex', alignItems: 'center', gap: '0.4rem', fontSize: '0.85rem', cursor: 'pointer', color: '#334155' }}>
											<input
												type="checkbox"
												checked={form.lead_fields?.[f.key] !== false}
												onChange={(e) =>
													setField('lead_fields', {
														...form.lead_fields,
														[f.key]: e.target.checked,
													})
												}
											/>
											{f.label}
										</label>
									))}
								</div>
							</div>

							<div className="dctc-ai-grid-2col" style={{ marginTop: '1.25rem' }}>
								<div className="dctc-ai-bot-field">
									<label htmlFor="lead_qualification_threshold">
										{__('Auto-Qualification Score Threshold (0 - 100)', 'dragwyb-click-to-chat')}
									</label>
									<div className="dctc-ai-inline-input-row">
										<input
											type="number"
											id="lead_qualification_threshold"
											className="dctc-ai-bot-input dctc-ai-input--narrow"
											min="0"
											max="100"
											value={form.lead_qualification_threshold}
											onChange={(e) =>
												setField('lead_qualification_threshold', parseInt(e.target.value, 10) || 70)
											}
										/>
										<span className="dctc-ai-input-unit-label">{__('points (marks lead as Qualified)', 'dragwyb-click-to-chat')}</span>
									</div>
									<p className="dctc-ai-bot-hint">
										{__('Leads scoring at or above this score are automatically classified as Qualified.', 'dragwyb-click-to-chat')}
									</p>
								</div>

								<div className="dctc-ai-bot-field">
									<div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '0.4rem' }}>
										<label htmlFor="enable_ai_intent_scoring" style={{ margin: 0, fontWeight: 600 }}>
											{__('AI Intent & Urgency Analysis', 'dragwyb-click-to-chat')}
										</label>
										<Toggle
											id="enable_ai_intent_scoring"
											checked={form.enable_ai_intent_scoring}
											onChange={(v) => setField('enable_ai_intent_scoring', v)}
										/>
									</div>
									<p className="dctc-ai-bot-hint" style={{ marginTop: '0.5rem' }}>
										{__('Analyzes visitor requirement text and conversation signals for buying intent and urgency tags.', 'dragwyb-click-to-chat')}
									</p>
								</div>
							</div>

							<div className="dctc-ai-grid-2col" style={{ marginTop: '1.25rem' }}>
								<div className="dctc-ai-bot-field">
									<div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '0.4rem' }}>
										<label htmlFor="enable_lead_email_alerts" style={{ margin: 0, fontWeight: 600 }}>
											{__('Instant Admin Email Alerts', 'dragwyb-click-to-chat')}
										</label>
										<Toggle
											id="enable_lead_email_alerts"
											checked={form.enable_lead_email_alerts}
											onChange={(v) => setField('enable_lead_email_alerts', v)}
										/>
									</div>
									{form.enable_lead_email_alerts && (
										<input
											type="email"
											id="lead_notification_email"
											className="dctc-ai-bot-input"
											value={form.lead_notification_email}
											onChange={(e) => setField('lead_notification_email', e.target.value)}
											placeholder={__('Leave blank to use admin email', 'dragwyb-click-to-chat')}
										/>
									)}
									<p className="dctc-ai-bot-hint">
										{__('Receive an instant email with lead details and score whenever a visitor submits their contact info.', 'dragwyb-click-to-chat')}
									</p>
								</div>

								<div className="dctc-ai-bot-field">
									<label htmlFor="lead_webhook_url">
										{__('Outbound Webhook URL (Zapier / Make / CRM)', 'dragwyb-click-to-chat')}
									</label>
									<input
										type="url"
										id="lead_webhook_url"
										className="dctc-ai-bot-input"
										value={form.lead_webhook_url}
										onChange={(e) => setField('lead_webhook_url', e.target.value)}
										placeholder="https://hooks.zapier.com/hooks/catch/..."
									/>
									<p className="dctc-ai-bot-hint">
										{__('Real-time asynchronous JSON POST sent to your CRM or automation workflow on every lead capture.', 'dragwyb-click-to-chat')}
									</p>
								</div>
							</div>
						</SwitcherCard>

						{ /* 5. Connect Chatbot with Support Tickets */}
						{ (window.dctc_ai_data?.is_support_enabled || window.dctc_support_data) ? (
							<SwitcherCard
								id="enable_support_escalation"
								title={__('Connect Chatbot with Support Tickets', 'dragwyb-click-to-chat')}
								desc={__('Seamlessly connect live AI chatbot conversations with Support Center tickets for automatic inquiry escalation and agent takeover.', 'dragwyb-click-to-chat')}
								icon="dashicons-tickets-alt"
								badge={__('Support Center', 'dragwyb-click-to-chat')}
								checked={form.enable_support_escalation}
								onChange={(v) => setField('enable_support_escalation', v)}
								disabledNotice={__('Turn on to automatically escalate unresolved customer queries and support issues from chatbot conversations into tracked support tickets.', 'dragwyb-click-to-chat')}
							>
								<div style={{ display: 'flex', flexDirection: 'column', gap: '0.85rem' }}>
									<div style={{ padding: '1rem', background: '#f8fafc', borderRadius: '8px', border: '1px solid #e2e8f0', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
										<div style={{ paddingRight: '1rem' }}>
											<strong style={{ display: 'block', color: '#1e293b', fontSize: '0.9rem', marginBottom: '0.25rem' }}>
												{__('Smart AI Ticket Routing & Auto-Assignment', 'dragwyb-click-to-chat')}
											</strong>
											<span style={{ fontSize: '0.825rem', color: '#64748b' }}>
												{__('Automatically classify customer query intent (technical support, billing, sales) and assign newly generated tickets to the most qualified agent.', 'dragwyb-click-to-chat')}
											</span>
										</div>
										<Toggle
											id="auto_assign_support_tickets"
											checked={form.auto_assign_support_tickets}
											onChange={(v) => setField('auto_assign_support_tickets', v)}
										/>
									</div>

									<div style={{ padding: '1rem', background: '#f8fafc', borderRadius: '8px', border: '1px solid #e2e8f0', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
										<div style={{ paddingRight: '1rem' }}>
											<strong style={{ display: 'block', color: '#1e293b', fontSize: '0.9rem', marginBottom: '0.25rem' }}>
												{__('Pause AI when Human Agent Takes Control', 'dragwyb-click-to-chat')}
											</strong>
											<span style={{ fontSize: '0.825rem', color: '#64748b' }}>
												{__('When an agent claims the ticket or sends a live reply from the Support Center, automatically silence bot responses so the human conversation stays smooth.', 'dragwyb-click-to-chat')}
											</span>
										</div>
										<Toggle
											id="auto_pause_ai_on_ticket"
											checked={form.auto_pause_ai_on_ticket}
											onChange={(v) => setField('auto_pause_ai_on_ticket', v)}
										/>
									</div>
								</div>
							</SwitcherCard>
						) : (
							<section className="dctc-ai-card dctc-ai-conditional-card is-disabled" style={{ borderLeft: '4px solid #f59e0b' }}>
								<header className="dctc-ai-card__header dctc-ai-conditional-card__header">
									<div className="dctc-ai-card__header-left">
										<div className="dctc-ai-card-icon" style={{ background: '#fef3c7', color: '#d97706' }} aria-hidden="true">
											<span className="dashicons dashicons-tickets-alt" />
										</div>
										<div>
											<div className="dctc-ai-header-with-badge">
												<h2 className="dctc-ai-card__title">
													{__('Connect Chatbot with Support Tickets', 'dragwyb-click-to-chat')}
												</h2>
												<span className="dctc-ai-mini-badge" style={{ background: '#fef3c7', color: '#92400e', borderColor: '#fde68a' }}>
													{__('Requires Support Center', 'dragwyb-click-to-chat')}
												</span>
											</div>
											<p className="dctc-ai-card__desc">
												{__('Connect your AI chatbot directly to Support Center tickets, enable intelligent agent routing, and allow live human takeover.', 'dragwyb-click-to-chat')}
											</p>
										</div>
									</div>
								</header>
								<div className="dctc-ai-card__body dctc-ai-conditional-card__body" style={{ background: '#fffbeb', borderTop: '1px solid #fef3c7', padding: '1.25rem 1.5rem', display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap', gap: '1rem' }}>
									<div style={{ maxWidth: '650px' }}>
										<strong style={{ display: 'block', color: '#92400e', fontSize: '0.925rem', marginBottom: '0.35rem' }}>
											{__('Support Center is currently disabled or not configured.', 'dragwyb-click-to-chat')}
										</strong>
										<p style={{ margin: 0, color: '#78350f', fontSize: '0.85rem', lineHeight: 1.5 }}>
											{__('To route chatbot conversations into support tickets and assign them to staff agents, enable the Support Center module first.', 'dragwyb-click-to-chat')}
										</p>
									</div>
									<a
										href="#support-center"
										onClick={(e) => {
											e.preventDefault();
											const supportTabBtn = document.querySelector('[data-tab="support-center"], button[id*="support-center"]');
											if (supportTabBtn) {
												supportTabBtn.click();
											} else {
												window.location.hash = 'support-center';
												window.location.reload();
											}
										}}
										className="dctc-ai-btn dctc-ai-btn-primary"
										style={{ textDecoration: 'none', display: 'inline-flex', alignItems: 'center', gap: '0.5rem', whiteSpace: 'nowrap' }}
									>
										<span className="dashicons dashicons-admin-generic" style={{ fontSize: '16px', width: '16px', height: '16px', lineHeight: '16px' }} />
										{__('Enable Support Center', 'dragwyb-click-to-chat')}
									</a>
								</div>
							</section>
						)}

						{ /* 6. AI Business Tools & Workflow Automation (Conditional Switcher Card) */}
						<SwitcherCard
							id="enable_ai_tools"
							title={__('AI Business Tools & Workflow Automation', 'dragwyb-click-to-chat')}
							desc={__('Allow AI models to safely execute real-world business actions (products, order checks, ticket logging, appointment scheduling, and webhooks).', 'dragwyb-click-to-chat')}
							icon="dashicons-admin-generic"
							badge={__('Agent Actions', 'dragwyb-click-to-chat')}
							checked={form.enable_ai_tools}
							onChange={(v) => setField('enable_ai_tools', v)}
							disabledNotice={__('Turn on to equip your AI assistant with real business actions and workflow automation.', 'dragwyb-click-to-chat')}
						>
							<div style={{ padding: '1rem', background: '#f8fafc', borderRadius: '8px', border: '1px solid #e2e8f0' }}>
								<strong style={{ display: 'block', marginBottom: '0.6rem', color: '#1e293b', fontSize: '0.9rem' }}>
									{__('Active AI Business Tools & Capabilities', 'dragwyb-click-to-chat')}
								</strong>
								<div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(260px, 1fr))', gap: '0.85rem' }}>
									{[
										...(isWcActive ? [
											{ id: 'search_products', label: __('🛍️ WooCommerce Product Search', 'dragwyb-click-to-chat'), desc: __('Find matching store items with live prices and stock', 'dragwyb-click-to-chat') },
											{ id: 'get_order_status', label: __('📦 Order Status Verification', 'dragwyb-click-to-chat'), desc: __('Check order delivery status with billing email protection', 'dragwyb-click-to-chat') },
										] : []),
										{ id: 'create_support_ticket', label: __('🎫 Support Ticket Dispatcher', 'dragwyb-click-to-chat'), desc: __('File customer tickets with priority and notify team', 'dragwyb-click-to-chat') },
										{ id: 'book_appointment', label: __('📅 Appointment & Demo Booking', 'dragwyb-click-to-chat'), desc: __('Schedule consultations and callbacks with clients', 'dragwyb-click-to-chat') },
										{ id: 'search_website_content', label: __('🔍 Website Content Search', 'dragwyb-click-to-chat'), desc: __('Find published articles, guides, and pages', 'dragwyb-click-to-chat') },
									].map((tool) => {
										const isToolActive = Array.isArray(form.enabled_tools) && form.enabled_tools.includes(tool.id);
										return (
											<div key={tool.id} style={{ display: 'flex', alignItems: 'flex-start', gap: '0.5rem', background: '#ffffff', padding: '0.75rem', borderRadius: '6px', border: '1px solid #e2e8f0' }}>
												<input
													type="checkbox"
													id={`tool_${tool.id}`}
													checked={isToolActive}
													onChange={(e) => {
														const current = Array.isArray(form.enabled_tools) ? [...form.enabled_tools] : [];
														const next = e.target.checked
															? [...current, tool.id]
															: current.filter((x) => x !== tool.id);
														setField('enabled_tools', next);
													}}
													style={{ marginTop: '3px' }}
												/>
												<label htmlFor={`tool_${tool.id}`} style={{ cursor: 'pointer', margin: 0 }}>
													<strong style={{ display: 'block', fontSize: '0.85rem', color: '#1e293b' }}>{tool.label}</strong>
													<span style={{ fontSize: '0.75rem', color: '#64748b' }}>{tool.desc}</span>
												</label>
											</div>
										);
									})}
								</div>
							</div>

							<div className="dctc-ai-grid-2col" style={{ marginTop: '1.25rem' }}>
								<div className="dctc-ai-bot-field">
									<label htmlFor="ticket_notification_email">
										{__('Support Ticket Alert Email', 'dragwyb-click-to-chat')}
									</label>
									<input
										type="email"
										id="ticket_notification_email"
										className="dctc-ai-bot-input"
										value={form.ticket_notification_email}
										onChange={(e) => setField('ticket_notification_email', e.target.value)}
										placeholder={__('Leave blank to use admin email', 'dragwyb-click-to-chat')}
									/>
									<p className="dctc-ai-bot-hint">
										{__('Email address to receive immediate alerts when tickets are created.', 'dragwyb-click-to-chat')}
									</p>
								</div>

								<div className="dctc-ai-bot-field">
									<label htmlFor="appointment_notification_email">
										{__('Appointment Booking Alert Email', 'dragwyb-click-to-chat')}
									</label>
									<input
										type="email"
										id="appointment_notification_email"
										className="dctc-ai-bot-input"
										value={form.appointment_notification_email}
										onChange={(e) => setField('appointment_notification_email', e.target.value)}
										placeholder={__('Leave blank to use admin email', 'dragwyb-click-to-chat')}
									/>
									<p className="dctc-ai-bot-hint">
										{__('Email address to receive consultation and demo booking requests.', 'dragwyb-click-to-chat')}
									</p>
								</div>
							</div>

							<div className="dctc-ai-bot-field" style={{ marginTop: '1.25rem' }}>
								<label htmlFor="workflow_webhook_url">
									{__('Automation Action Webhook URL (Zapier / Make / Slack / CRM)', 'dragwyb-click-to-chat')}
								</label>
								<input
									type="url"
									id="workflow_webhook_url"
									className="dctc-ai-bot-input"
									value={form.workflow_webhook_url}
									onChange={(e) => setField('workflow_webhook_url', e.target.value)}
									placeholder="https://hooks.zapier.com/hooks/catch/..."
								/>
								<p className="dctc-ai-bot-hint">
									{__('Dispatches real-time JSON payload whenever AI tools or actions are executed.', 'dragwyb-click-to-chat')}
								</p>
							</div>
						</SwitcherCard>

						{ /* Page-Aware AI & Smart Behavioral Triggers */}
						<SwitcherCard
							id="enable_smart_triggers"
							title={__('Page-Aware AI & Smart Behavioral Triggers', 'dragwyb-click-to-chat')}
							desc={__('Dynamically inject visited page, post & WooCommerce product context into AI prompts, and trigger proactive greetings on user interaction.', 'dragwyb-click-to-chat')}
							icon="dashicons-visibility"
							badge={__('Proactive AI', 'dragwyb-click-to-chat')}
							checked={form.enable_smart_triggers}
							onChange={(v) => setField('enable_smart_triggers', v)}
							disabledNotice={__('Enable to automatically inject page context and activate smart behavioral triggers (scroll depth, exit intent, inactivity, proactive teaser bubbles).', 'dragwyb-click-to-chat')}
						>
							<div className="dctc-ai-features-grid" style={{ marginBottom: '1.25rem' }}>
								<SettingCard
									id="enable_page_context"
									title={__('Page Context Injection', 'dragwyb-click-to-chat')}
									desc={__('Pass current visited page title, URL, post type & WooCommerce product details into the AI prompt for page-tailored answers.', 'dragwyb-click-to-chat')}
									icon="dashicons-admin-page"
									badge={__('Context Aware', 'dragwyb-click-to-chat')}
									checked={form.enable_page_context}
									onChange={(v) => setField('enable_page_context', v)}
								/>
								<SettingCard
									id="trigger_exit_intent"
									title={__('Exit Intent Trigger (Desktop)', 'dragwyb-click-to-chat')}
									desc={__('Detect when user cursor moves toward browser top bar or tab bar to show proactive greeting before leaving.', 'dragwyb-click-to-chat')}
									icon="dashicons-external"
									badge={__('Conversion', 'dragwyb-click-to-chat')}
									checked={form.trigger_exit_intent}
									onChange={(v) => setField('trigger_exit_intent', v)}
								/>
							</div>

							<div className="dctc-ai-grid-2col">
								<div className="dctc-ai-bot-field">
									<label htmlFor="trigger_action">
										{__('Trigger Response Action', 'dragwyb-click-to-chat')}
									</label>
									<select
										id="trigger_action"
										className="dctc-ai-bot-select"
										value={form.trigger_action}
										onChange={(e) => setField('trigger_action', e.target.value)}
									>
										<option value="show_bubble">{__('Show Proactive Teaser Bubble (Subtle & Non-Intrusive)', 'dragwyb-click-to-chat')}</option>
										<option value="open_chat">{__('Auto-Open Full Chat Window', 'dragwyb-click-to-chat')}</option>
									</select>
									<p className="dctc-ai-bot-hint">
										{__('Choose how the widget responds when a behavioral trigger condition is met.', 'dragwyb-click-to-chat')}
									</p>
								</div>

								<div className="dctc-ai-bot-field">
									<label htmlFor="trigger_scroll_depth">
										{__('Scroll Depth Trigger Percentage', 'dragwyb-click-to-chat')}
									</label>
									<div className="dctc-ai-inline-input-row">
										<input
											type="number"
											id="trigger_scroll_depth"
											className="dctc-ai-bot-input dctc-ai-input--narrow"
											min="10"
											max="100"
											step="5"
											value={form.trigger_scroll_depth}
											onChange={(e) =>
												setField('trigger_scroll_depth', parseInt(e.target.value, 10) || 50)
											}
										/>
										<span className="dctc-ai-input-unit-label">% of page</span>
									</div>
									<p className="dctc-ai-bot-hint">
										{__('Fires when user scrolls past this percentage of page height.', 'dragwyb-click-to-chat')}
									</p>
								</div>
							</div>

							<div className="dctc-ai-grid-2col" style={{ marginTop: '1rem' }}>
								<div className="dctc-ai-bot-field">
									<label htmlFor="trigger_inactivity">
										{__('User Inactivity Timeout (Seconds)', 'dragwyb-click-to-chat')}
									</label>
									<div className="dctc-ai-inline-input-row">
										<input
											type="number"
											id="trigger_inactivity"
											className="dctc-ai-bot-input dctc-ai-input--narrow"
											min="5"
											max="300"
											value={form.trigger_inactivity}
											onChange={(e) =>
												setField('trigger_inactivity', parseInt(e.target.value, 10) || 30)
											}
										/>
										<span className="dctc-ai-input-unit-label">seconds</span>
									</div>
									<p className="dctc-ai-bot-hint">
										{__('Triggers if visitor is idle on the page without scrolling or clicking.', 'dragwyb-click-to-chat')}
									</p>
								</div>

								<div className="dctc-ai-bot-field">
									<label htmlFor="target_devices">
										{__('Device Targeting Filter', 'dragwyb-click-to-chat')}
									</label>
									<select
										id="target_devices"
										className="dctc-ai-bot-select"
										value={form.target_devices}
										onChange={(e) => setField('target_devices', e.target.value)}
									>
										<option value="all">{__('All Devices (Desktop & Mobile)', 'dragwyb-click-to-chat')}</option>
										<option value="desktop_only">{__('Desktop Only', 'dragwyb-click-to-chat')}</option>
										<option value="mobile_only">{__('Mobile Only', 'dragwyb-click-to-chat')}</option>
									</select>
								</div>
							</div>

							<div className="dctc-ai-bot-field" style={{ marginTop: '1rem' }}>
								<label htmlFor="target_users">
									{__('Visitor Audience Targeting', 'dragwyb-click-to-chat')}
								</label>
								<select
									id="target_users"
									className="dctc-ai-bot-select"
									value={form.target_users}
									onChange={(e) => setField('target_users', e.target.value)}
								>
									<option value="all">{__('All Visitors (Logged-in & Guests)', 'dragwyb-click-to-chat')}</option>
									<option value="guests">{__('Guest Visitors Only', 'dragwyb-click-to-chat')}</option>
									<option value="logged_in">{__('Logged-in Users Only', 'dragwyb-click-to-chat')}</option>
								</select>
							</div>

							<div className="dctc-ai-bot-field" style={{ marginTop: '1rem' }}>
								<label htmlFor="url_rules">
									{__('Target Specific URL Paths (Optional)', 'dragwyb-click-to-chat')}
								</label>
								<textarea
									id="url_rules"
									className="dctc-ai-bot-textarea"
									rows="2"
									value={form.url_rules}
									onChange={(e) => setField('url_rules', e.target.value)}
									placeholder="/pricing&#10;/shop/*&#10;/contact"
								/>
								<p className="dctc-ai-bot-hint">
									{__('Leave empty to apply sitewide. Enter one path or wildcard pattern per line to restrict triggers to specific pages.', 'dragwyb-click-to-chat')}
								</p>
							</div>
						</SwitcherCard>

						{ /* 7. Save Chat History & Retention Policy (Conditional Switcher Card) */}
						<SwitcherCard
							id="save_chat"
							title={__('Conversation History & Retention Policy', 'dragwyb-click-to-chat')}
							desc={__('Persist conversation logs for visitors, power AI contextual memory, and enforce automatic retention cleanup.', 'dragwyb-click-to-chat')}
							icon="dashicons-backup"
							badge={__('Privacy & Memory', 'dragwyb-click-to-chat')}
							checked={form.save_chat}
							onChange={(v) => setField('save_chat', v)}
							disabledNotice={__('Enable chat history to maintain conversation sessions, context memory, and automated retention.', 'dragwyb-click-to-chat')}
						>
							<div className="dctc-ai-grid-2col">
								<div className="dctc-ai-bot-field">
									<label htmlFor="chat_retention_days">
										{__('Conversation Retention Policy (WP-Cron)', 'dragwyb-click-to-chat')}
									</label>
									<select
										id="chat_retention_days"
										className="dctc-ai-bot-select"
										value={form.chat_retention_days}
										onChange={(e) =>
											setField('chat_retention_days', parseInt(e.target.value, 10) || 0)
										}
									>
										<option value="0">{__('Keep Forever (No Auto-Delete)', 'dragwyb-click-to-chat')}</option>
										<option value="7">{__('Older than 7 days', 'dragwyb-click-to-chat')}</option>
										<option value="14">{__('Older than 14 days', 'dragwyb-click-to-chat')}</option>
										<option value="30">{__('Older than 30 days (Recommended)', 'dragwyb-click-to-chat')}</option>
										<option value="60">{__('Older than 60 days', 'dragwyb-click-to-chat')}</option>
										<option value="90">{__('Older than 90 days', 'dragwyb-click-to-chat')}</option>
										<option value="180">{__('Older than 180 days (6 months)', 'dragwyb-click-to-chat')}</option>
										<option value="365">{__('Older than 365 days (1 year)', 'dragwyb-click-to-chat')}</option>
									</select>
									<p className="dctc-ai-bot-hint">
										{__('Automatically purges old chat sessions on daily schedule to comply with privacy policies and keep database clean.', 'dragwyb-click-to-chat')}
									</p>
								</div>

								<div className="dctc-ai-bot-field">
									<label htmlFor="memory_window_size">
										{__('Context Memory Window (Messages)', 'dragwyb-click-to-chat')}
									</label>
									<div className="dctc-ai-inline-input-row">
										<input
											type="number"
											id="memory_window_size"
											className="dctc-ai-bot-input dctc-ai-input--narrow"
											min="2"
											max="50"
											value={form.memory_window_size}
											onChange={(e) =>
												setField('memory_window_size', parseInt(e.target.value, 10) || 10)
											}
										/>
										<span className="dctc-ai-input-unit-label">
											{__('recent messages', 'dragwyb-click-to-chat')}
										</span>
									</div>
									<p className="dctc-ai-bot-hint">
										{__('Number of preceding conversation turns sent to LLM for follow-up and pronoun resolution.', 'dragwyb-click-to-chat')}
									</p>
								</div>
							</div>

							<div className="dctc-ai-features-grid" style={{ marginTop: '1.25rem', paddingTop: '1.25rem', borderTop: '1px solid #f1f5f9' }}>
								<SettingCard
									id="ask_email"
									title={__('Ask Visitor Email for Continuity', 'dragwyb-click-to-chat')}
									desc={__('Optionally request email to restore chat sessions across different devices.', 'dragwyb-click-to-chat')}
									icon="dashicons-email-alt"
									badge={__('Lead Capture', 'dragwyb-click-to-chat')}
									checked={!!form.ask_email}
									onChange={(v) => setField('ask_email', v)}
								/>
							</div>
						</SwitcherCard>

						{ /* Multilingual AI & Auto-Detection */}
						<SwitcherCard
							id="enable_multilingual"
							title={__('Multilingual AI & Language Auto-Detection', 'dragwyb-click-to-chat')}
							desc={__('Detect visitor language automatically and respond fluently in over 50+ languages.', 'dragwyb-click-to-chat')}
							icon="dashicons-translation"
							badge={__('Global AI', 'dragwyb-click-to-chat')}
							checked={form.enable_multilingual}
							onChange={(v) => setField('enable_multilingual', v)}
							disabledNotice={__('Enable to automatically reply in the visitor’s language or enforce a specific language.', 'dragwyb-click-to-chat')}
						>
							<div className="dctc-ai-grid-2col">
								<div className="dctc-ai-bot-field">
									<label htmlFor="preferred_language">
										{__('Primary Bot Language', 'dragwyb-click-to-chat')}
									</label>
									<select
										id="preferred_language"
										className="dctc-ai-bot-select"
										value={form.preferred_language}
										onChange={(e) => setField('preferred_language', e.target.value)}
									>
										<option value="auto">{__('🌐 Auto-Detect Visitor Language (Recommended)', 'dragwyb-click-to-chat')}</option>
										<option value="en">English (US/UK)</option>
										<option value="es">Español (Spanish)</option>
										<option value="fr">Français (French)</option>
										<option value="de">Deutsch (German)</option>
										<option value="it">Italiano (Italian)</option>
										<option value="pt">Português (Portuguese)</option>
										<option value="hi">हिन्दी (Hindi)</option>
										<option value="ar">العربية (Arabic)</option>
										<option value="zh">中文 (Chinese)</option>
										<option value="ja">日本語 (Japanese)</option>
										<option value="nl">Nederlands (Dutch)</option>
										<option value="ru">Русский (Russian)</option>
										<option value="tr">Türkçe (Turkish)</option>
										<option value="id">Bahasa Indonesia</option>
									</select>
									<p className="dctc-ai-bot-hint">
										{__('When Auto-Detect is chosen, the assistant speaks in whatever language the visitor uses in chat.', 'dragwyb-click-to-chat')}
									</p>
								</div>

								<div className="dctc-ai-bot-field">
									<SettingCard
										id="visitor_language_override"
										title={__('Auto-Adapt to Visitor Locale', 'dragwyb-click-to-chat')}
										desc={__('Use browser language detection as secondary fallback for multilingual visitors.', 'dragwyb-click-to-chat')}
										icon="dashicons-admin-site"
										checked={form.visitor_language_override}
										onChange={(v) => setField('visitor_language_override', v)}
									/>
								</div>
							</div>
						</SwitcherCard>

						{ /* Voice AI & Audio Experience */}
						<SwitcherCard
							id="enable_voice_input"
							title={__('Voice AI & Speech Recognition', 'dragwyb-click-to-chat')}
							desc={__('Enable microphone button for voice input and audio response read-aloud capabilities.', 'dragwyb-click-to-chat')}
							icon="dashicons-microphone"
							badge={__('Voice AI', 'dragwyb-click-to-chat')}
							checked={form.enable_voice_input}
							onChange={(v) => setField('enable_voice_input', v)}
							disabledNotice={__('Enable voice AI to let visitors speak their questions hands-free via Web Speech API.', 'dragwyb-click-to-chat')}
						>
							<div className="dctc-ai-features-grid">
								<SettingCard
									id="enable_voice_output"
									title={__('Text-to-Speech Read Aloud (TTS)', 'dragwyb-click-to-chat')}
									desc={__('Displays speaker icon on assistant replies so visitors can listen to answers.', 'dragwyb-click-to-chat')}
									icon="dashicons-controls-volumeon"
									checked={form.enable_voice_output}
									onChange={(v) => setField('enable_voice_output', v)}
								/>
							</div>

							<div className="dctc-ai-bot-field" style={{ marginTop: '1rem' }}>
								<label htmlFor="voice_language">
									{__('Speech Recognition & Accent Language', 'dragwyb-click-to-chat')}
								</label>
								<select
									id="voice_language"
									className="dctc-ai-bot-select"
									value={form.voice_language}
									onChange={(e) => setField('voice_language', e.target.value)}
								>
									<option value="auto">{__('Auto (Browser Locale Default)', 'dragwyb-click-to-chat')}</option>
									<option value="en-US">English (US)</option>
									<option value="en-GB">English (UK)</option>
									<option value="es-ES">Spanish (Spain)</option>
									<option value="es-MX">Spanish (Latin America)</option>
									<option value="fr-FR">French</option>
									<option value="de-DE">German</option>
									<option value="it-IT">Italian</option>
									<option value="pt-BR">Portuguese (Brazil)</option>
									<option value="hi-IN">Hindi (India)</option>
									<option value="ar-SA">Arabic (Saudi Arabia)</option>
									<option value="zh-CN">Chinese (Mandarin)</option>
									<option value="ja-JP">Japanese</option>
								</select>
							</div>
						</SwitcherCard>

						{ /* 4. Visitor File & Image Uploads (Conditional Switcher Card) */}
						<SwitcherCard
							id="enable_uploads"
							title={__('Visitor File & Image Uploads', 'dragwyb-click-to-chat')}
							desc={__('Allow visitors to attach photos, screenshots, and documents directly in chat composer.', 'dragwyb-click-to-chat')}
							icon="dashicons-paperclip"
							badge={__('Interactive', 'dragwyb-click-to-chat')}
							checked={form.enable_uploads}
							onChange={(v) => setField('enable_uploads', v)}
							disabledNotice={__('Turn on to let visitors upload images, PDFs, and documents during chat.', 'dragwyb-click-to-chat')}
						>
							<div className="dctc-ai-features-grid" style={{ marginBottom: '1.25rem' }}>
								<SettingCard
									id="enable_vision_understanding"
									title={__('Multimodal Vision AI Processing', 'dragwyb-click-to-chat')}
									desc={__('Sends uploaded image attachments to vision-capable models (GPT-4o, Claude 3.5, Gemini) for direct visual analysis.', 'dragwyb-click-to-chat')}
									icon="dashicons-visibility"
									badge={__('Vision AI', 'dragwyb-click-to-chat')}
									checked={form.enable_vision_understanding}
									onChange={(v) => setField('enable_vision_understanding', v)}
								/>
							</div>
							<div className="dctc-ai-grid-2col">
								<div className="dctc-ai-bot-field">
									<label htmlFor="allowed_file_types">
										{__('Allowed Extensions (Whitelist)', 'dragwyb-click-to-chat')}
									</label>
									<input
										type="text"
										id="allowed_file_types"
										className="dctc-ai-bot-input"
										value={form.allowed_file_types}
										onChange={(e) => setField('allowed_file_types', e.target.value)}
										placeholder="jpg, jpeg, png, webp, gif, pdf, txt, doc, docx"
									/>
									<p className="dctc-ai-bot-hint">
										{__('Comma-separated file extensions permitted for upload.', 'dragwyb-click-to-chat')}
									</p>
								</div>

								<div className="dctc-ai-bot-field">
									<label htmlFor="excluded_file_types">
										{__('Blocked Extensions (Strict Blacklist)', 'dragwyb-click-to-chat')}
									</label>
									<input
										type="text"
										id="excluded_file_types"
										className="dctc-ai-bot-input"
										value={form.excluded_file_types}
										onChange={(e) => setField('excluded_file_types', e.target.value)}
										placeholder="php, js, html, exe, svg"
									/>
									<p className="dctc-ai-bot-hint">
										{__('Extensions that are strictly forbidden. Takes priority over whitelist.', 'dragwyb-click-to-chat')}
									</p>
								</div>
							</div>

							<div className="dctc-ai-grid-3col" style={{ marginTop: '1.25rem' }}>
								<div className="dctc-ai-bot-field">
									<label htmlFor="max_upload_size">
										{__('Max Size Per File', 'dragwyb-click-to-chat')}
									</label>
									<div className="dctc-ai-inline-input-row">
										<input
											type="number"
											id="max_upload_size"
											className="dctc-ai-bot-input"
											min="1"
											max="50"
											value={form.max_upload_size}
											onChange={(e) =>
												setField('max_upload_size', parseInt(e.target.value, 10) || 5)
											}
										/>
										<span className="dctc-ai-input-unit-label">MB</span>
									</div>
								</div>

								<div className="dctc-ai-bot-field">
									<label htmlFor="max_files_per_message">
										{__('Max Files Per Message', 'dragwyb-click-to-chat')}
									</label>
									<div className="dctc-ai-inline-input-row">
										<input
											type="number"
											id="max_files_per_message"
											className="dctc-ai-bot-input"
											min="1"
											max="10"
											value={form.max_files_per_message}
											onChange={(e) =>
												setField('max_files_per_message', parseInt(e.target.value, 10) || 3)
											}
										/>
										<span className="dctc-ai-input-unit-label">
											{__('files', 'dragwyb-click-to-chat')}
										</span>
									</div>
								</div>

								<div className="dctc-ai-bot-field">
									<label htmlFor="store_chat_attachments">
										{__('Attachment Storage Mode', 'dragwyb-click-to-chat')}
									</label>
									<select
										id="store_chat_attachments"
										className="dctc-ai-bot-select"
										value={form.store_chat_attachments || 'temp'}
										onChange={(e) => setField('store_chat_attachments', e.target.value)}
									>
										<option value="temp">{__('Temporary Storage (Recommended)', 'dragwyb-click-to-chat')}</option>
										<option value="save_with_history">{__('Save with History (Media Library)', 'dragwyb-click-to-chat')}</option>
										<option value="do_not_store">{__('Do Not Store After Processing', 'dragwyb-click-to-chat')}</option>
									</select>
								</div>
							</div>
						</SwitcherCard>

						{ /* 8. Error Logging & Retention (Conditional Switcher Card) */}
						<SwitcherCard
							id="enable_error_log"
							title={__('Error Logging & Retention', 'dragwyb-click-to-chat')}
							desc={__('Capture AI API failures, provider errors, and system exceptions for troubleshooting.', 'dragwyb-click-to-chat')}
							icon="dashicons-warning"
							checked={form.enable_error_log}
							onChange={(v) => setField('enable_error_log', v)}
							disabledNotice={__('Enable error logging to capture API timeouts, provider errors, and system exceptions.', 'dragwyb-click-to-chat')}
						>
							<div className="dctc-ai-bot-field">
								<label htmlFor="error_log_retention_days">
									{__('Auto-delete Logs (Daily WordPress Cron)', 'dragwyb-click-to-chat')}
								</label>
								<select
									id="error_log_retention_days"
									className="dctc-ai-bot-select dctc-ai-input--medium"
									value={form.error_log_retention_days}
									onChange={(e) =>
										setField('error_log_retention_days', parseInt(e.target.value, 10) || 0)
									}
								>
									<option value="0">{__('Never / Permanent (Default)', 'dragwyb-click-to-chat')}</option>
									<option value="1">{__('Older than 1 day', 'dragwyb-click-to-chat')}</option>
									<option value="7">{__('Older than 7 days', 'dragwyb-click-to-chat')}</option>
									<option value="14">{__('Older than 14 days', 'dragwyb-click-to-chat')}</option>
									<option value="30">{__('Older than 30 days', 'dragwyb-click-to-chat')}</option>
									<option value="60">{__('Older than 60 days', 'dragwyb-click-to-chat')}</option>
									<option value="90">{__('Older than 90 days', 'dragwyb-click-to-chat')}</option>
								</select>
								<p className="dctc-ai-bot-hint">
									{__('Keep the database lean by automatically pruning older logs.', 'dragwyb-click-to-chat')}
								</p>
							</div>
						</SwitcherCard>
					</div>
				)}

				{ /* =========================================================================
				     STYLE SUBTAB: Unified Customizer & Positioning
				   ========================================================================= */ }
				{subtab === 'style' && (
					<div className="dctc-ai-tab-panel-section">
						{ /* 1. UNIFIED LIVE CUSTOMIZER: Floating Launcher, Avatars, Branding Colors & Real-Time Simulation */}
						<section className="dctc-ai-card">
							<header className="dctc-ai-card__header">
								<div className="dctc-ai-card__header-left">
									<div className="dctc-ai-card-icon">
										<span className="dashicons dashicons-admin-customizer" />
									</div>
									<div>
										<h2 className="dctc-ai-card__title">
											{__('Chatbot Visual Styling & Live Preview', 'dragwyb-click-to-chat')}
										</h2>
										<p className="dctc-ai-card__desc">
											{__('Customize launcher button, bot & visitor avatars, brand colors, and message bubble styles with instant live preview.', 'dragwyb-click-to-chat')}
										</p>
									</div>
								</div>
							</header>

							<div className="dctc-ai-card__body">
								<div className="dctc-ai-live-customizer-layout">
									{ /* Left Column: UI & Styling Controls */}
									<div className="dctc-ai-customizer-controls">
										{ /* Group A: Floating Launcher Button */}
										<div className="dctc-ai-customizer-group">
											<div className="dctc-ai-customizer-group__header">
												<span className="dashicons dashicons-art" />
												<h3 className="dctc-ai-customizer-group__title">
													{__('Floating Launcher Button', 'dragwyb-click-to-chat')}
												</h3>
											</div>

											<div className="dctc-ai-grid-2col">
												<div className="dctc-ai-bot-field">
													<label htmlFor="launcher_icon_preset">
														{__('Preset Icon', 'dragwyb-click-to-chat')}
													</label>
													<select
														id="launcher_icon_preset"
														className="dctc-ai-bot-select"
														value={form.launcher_icon_preset}
														onChange={(e) => setField('launcher_icon_preset', e.target.value)}
													>
														{LAUNCHER_PRESETS.map((preset) => (
															<option key={preset.id} value={preset.id}>
																{preset.label}
															</option>
														))}
													</select>
												</div>

												<div className="dctc-ai-bot-field">
													<label htmlFor="widget_size">
														{__('Button Size', 'dragwyb-click-to-chat')}
													</label>
													<div className="dctc-ai-widget-size-row">
														<input
															type="number"
															id="widget_size"
															className="dctc-ai-bot-input dctc-ai-widget-size-input"
															min="24"
															max="120"
															step="1"
															value={form.widget_size}
															onChange={(e) =>
																setField(
																	'widget_size',
																	e.target.value === '' ? '' : Number(e.target.value)
																)
															}
														/>
														<select
															id="widget_size_unit"
															className="dctc-ai-bot-select dctc-ai-widget-size-unit"
															value={form.widget_size_unit}
															onChange={(e) => setField('widget_size_unit', e.target.value)}
														>
															<option value="px">px</option>
															<option value="rem">rem</option>
															<option value="em">em</option>
														</select>
													</div>
												</div>
											</div>

											<div className="dctc-ai-bot-field">
												<label>
													{__('Custom Uploaded Launcher Icon (Optional)', 'dragwyb-click-to-chat')}
												</label>
												<div className="dctc-ai-assistant-icon-row">
													<div className="dctc-ai-avatar-btn-group">
														<button
															type="button"
															className="dctc-ai-btn dctc-ai-btn-secondary dctc-ai-btn-sm"
															onClick={openAssistantIconMedia}
														>
															<span className="dashicons dashicons-upload" />
															{form.assistant_icon
																? __('Change Custom Icon', 'dragwyb-click-to-chat')
																: __('Upload Custom Icon', 'dragwyb-click-to-chat')}
														</button>
														{!!form.assistant_icon && (
															<button
																type="button"
																className="dctc-ai-btn dctc-ai-btn-secondary dctc-ai-btn-sm"
																onClick={() => setField('assistant_icon', '')}
															>
																{__('Reset to Preset', 'dragwyb-click-to-chat')}
															</button>
														)}
													</div>
													{form.assistant_icon ? (
														<span className="dctc-ai-filename-chip">
															{form.assistant_icon.split('/').pop()}
														</span>
													) : (
														<span className="dctc-ai-filename-chip is-muted">
															{__('Using preset icon', 'dragwyb-click-to-chat')}
														</span>
													)}
												</div>
											</div>
										</div>

										{ /* Group B: Branding Colors & Message Bubbles */}
										<div className="dctc-ai-customizer-group">
											<div className="dctc-ai-customizer-group__header">
												<span className="dashicons dashicons-color-picker" />
												<h3 className="dctc-ai-customizer-group__title">
													{__('Branding Colors & Message Bubbles', 'dragwyb-click-to-chat')}
												</h3>
											</div>

											<div className="dctc-ai-grid-2col">
												<ColorField
													id="primary_color"
													label={__('Brand Primary Color', 'dragwyb-click-to-chat')}
													value={form.primary_color}
													onChange={(v) => setField('primary_color', v)}
												/>

												<div className="dctc-ai-bot-field">
													<label htmlFor="bubble_style">
														{__('Message Bubble Corners', 'dragwyb-click-to-chat')}
													</label>
													<select
														id="bubble_style"
														className="dctc-ai-bot-select"
														value={form.bubble_style}
														onChange={(e) => setField('bubble_style', e.target.value)}
													>
														<option value="rounded">{__('Rounded Corners', 'dragwyb-click-to-chat')}</option>
														<option value="square">{__('Square / Sharp', 'dragwyb-click-to-chat')}</option>
														<option value="pill">{__('Pill / Smooth', 'dragwyb-click-to-chat')}</option>
													</select>
												</div>
											</div>

											<div className="dctc-ai-features-grid">
												<SettingCard
													id="show_bot_avatar_in_chat"
													title={__('Show Bot Avatar in Messages', 'dragwyb-click-to-chat')}
													desc={__('Display assistant avatar badge beside bot answers.', 'dragwyb-click-to-chat')}
													checked={form.show_bot_avatar_in_chat}
													onChange={(v) => setField('show_bot_avatar_in_chat', v)}
												/>
												<SettingCard
													id="show_user_avatar_in_chat"
													title={__('Show User Avatar in Messages', 'dragwyb-click-to-chat')}
													desc={__('Display visitor avatar badge beside visitor messages.', 'dragwyb-click-to-chat')}
													checked={form.show_user_avatar_in_chat}
													onChange={(v) => setField('show_user_avatar_in_chat', v)}
												/>
												<SettingCard
													id="show_sources"
													title={__('Show Source Links in Answers', 'dragwyb-click-to-chat')}
													desc={__('Display clickable source citation pills under AI answers when Knowledge Base content is cited.', 'dragwyb-click-to-chat')}
													checked={form.show_sources}
													onChange={(v) => setField('show_sources', v)}
												/>
											</div>
										</div>

										{ /* Group C: Bot Avatar & Identity */}
										<div className="dctc-ai-customizer-group">
											<div className="dctc-ai-customizer-group__header">
												<span className="dashicons dashicons-superhero-alt" />
												<h3 className="dctc-ai-customizer-group__title">
													{__('Bot Avatar & Icon', 'dragwyb-click-to-chat')}
												</h3>
											</div>

											<div className="dctc-ai-avatar-editor">
												<div className="dctc-ai-avatar-preview-badge" style={{ backgroundColor: `${form.primary_color}15`, color: form.primary_color }}>
													{renderBotAvatarIcon(32)}
												</div>

												<div className="dctc-ai-avatar-controls">
													<div className="dctc-ai-bot-field">
														<label htmlFor="bot_icon_preset">
															{__('Preset Icon Style', 'dragwyb-click-to-chat')}
														</label>
														<select
															id="bot_icon_preset"
															className="dctc-ai-bot-select"
															value={form.bot_icon_preset || 'bot'}
															onChange={(e) => setField('bot_icon_preset', e.target.value)}
														>
															<option value="bot">{__('🤖 Robot Assistant', 'dragwyb-click-to-chat')}</option>
															<option value="sparkle">{__('✨ Sparkle AI', 'dragwyb-click-to-chat')}</option>
															<option value="support">{__('🎧 Support Agent', 'dragwyb-click-to-chat')}</option>
															<option value="chat">{__('💬 Chat Bubble', 'dragwyb-click-to-chat')}</option>
															<option value="initial">{__('🔤 Bot Name Initial', 'dragwyb-click-to-chat')}</option>
														</select>
													</div>

													<div className="dctc-ai-avatar-btn-group">
														<button
															type="button"
															className="dctc-ai-btn dctc-ai-btn-secondary dctc-ai-btn-sm"
															onClick={openBotMedia}
														>
															<span className="dashicons dashicons-upload" />
															{form.bot_avatar
																? __('Change Custom Image', 'dragwyb-click-to-chat')
																: __('Upload Custom Image', 'dragwyb-click-to-chat')}
														</button>
														{!!form.bot_avatar && (
															<button
																type="button"
																className="dctc-ai-btn dctc-ai-btn-secondary dctc-ai-btn-sm"
																onClick={() => setField('bot_avatar', '')}
															>
																{__('Reset to Preset', 'dragwyb-click-to-chat')}
															</button>
														)}
													</div>

													{form.bot_avatar ? (
														<span className="dctc-ai-filename-chip">
															{form.bot_avatar.split('/').pop()}
														</span>
													) : (
														<span className="dctc-ai-filename-chip is-muted">
															{__('Using preset vector icon', 'dragwyb-click-to-chat')}
														</span>
													)}
												</div>
											</div>
										</div>

										{ /* Group D: Visitor Avatar (conditionally shown only if show_user_avatar_in_chat is true) */}
										{form.show_user_avatar_in_chat && (
											<div className="dctc-ai-customizer-group">
												<div className="dctc-ai-customizer-group__header">
													<span className="dashicons dashicons-admin-users" />
													<h3 className="dctc-ai-customizer-group__title">
														{__('Visitor Avatar & Icon', 'dragwyb-click-to-chat')}
													</h3>
												</div>

												<div className="dctc-ai-avatar-editor">
													<div className="dctc-ai-avatar-preview-badge dctc-ai-avatar-preview-badge--user">
														{renderUserAvatarIcon(32)}
													</div>

													<div className="dctc-ai-avatar-controls">
														<div className="dctc-ai-bot-field">
															<label htmlFor="user_icon_preset">
																{__('Preset Icon Style', 'dragwyb-click-to-chat')}
															</label>
															<select
																id="user_icon_preset"
																className="dctc-ai-bot-select"
																value={form.user_icon_preset || 'user'}
																onChange={(e) => setField('user_icon_preset', e.target.value)}
															>
																<option value="user">{__('👤 Default User', 'dragwyb-click-to-chat')}</option>
																<option value="user-circle">{__('🔘 User Circle', 'dragwyb-click-to-chat')}</option>
																<option value="business">{__('👔 Business Client', 'dragwyb-click-to-chat')}</option>
																<option value="smile">{__('😊 Friendly Smile', 'dragwyb-click-to-chat')}</option>
																<option value="star">{__('⭐ Star Member', 'dragwyb-click-to-chat')}</option>
															</select>
														</div>

														<div className="dctc-ai-avatar-btn-group">
															<button
																type="button"
																className="dctc-ai-btn dctc-ai-btn-secondary dctc-ai-btn-sm"
																onClick={openUserMedia}
															>
																<span className="dashicons dashicons-upload" />
																{form.user_avatar
																	? __('Change Custom Image', 'dragwyb-click-to-chat')
																	: __('Upload Custom Image', 'dragwyb-click-to-chat')}
															</button>
															{!!form.user_avatar && (
																<button
																	type="button"
																	className="dctc-ai-btn dctc-ai-btn-secondary dctc-ai-btn-sm"
																	onClick={() => setField('user_avatar', '')}
																>
																	{__('Reset to Preset', 'dragwyb-click-to-chat')}
																</button>
															)}
														</div>

														{form.user_avatar ? (
															<span className="dctc-ai-filename-chip">
																{form.user_avatar.split('/').pop()}
															</span>
														) : (
															<span className="dctc-ai-filename-chip is-muted">
																{__('Using preset vector icon', 'dragwyb-click-to-chat')}
															</span>
														)}
													</div>
												</div>
											</div>
										)}
									</div>

									{ /* Right Column: Unified Real-Time Live Preview */}
									<div className="dctc-ai-live-preview-panel">
										<div className="dctc-ai-live-preview-panel__header">
											<h4 className="dctc-ai-live-preview-panel__title">
												<span className="dashicons dashicons-visibility" />
												{__('Interactive Live Preview', 'dragwyb-click-to-chat')}
											</h4>
											<span className="dctc-ai-status-pill is-active">
												{__('Real-Time', 'dragwyb-click-to-chat')}
											</span>
										</div>

										<div className="dctc-ai-preview-stage">
											{ /* Chat Window Simulation */}
											<div className="dctc-ai-chat-live-mockup">
												<div className="dctc-ai-chat-live-mockup__header" style={{ backgroundColor: form.primary_color }}>
													<div className="dctc-ai-mockup-avatar">
														{renderBotAvatarIcon(18)}
													</div>
													<span className="dctc-ai-mockup-title">{form.bot_name || 'AI Assistant'}</span>
												</div>

												<div className="dctc-ai-chat-live-mockup__body">
													{ /* Bot Message */}
													<div className="dctc-ai-mockup-msg-row is-bot">
														{form.show_bot_avatar_in_chat && (
															<div className="dctc-ai-mockup-msg-avatar" style={{ backgroundColor: `${form.primary_color}20`, color: form.primary_color }}>
																{renderBotAvatarIcon(14)}
															</div>
														)}
														<div
															className="dctc-ai-mockup-msg-bubble is-bot"
															style={{ borderRadius: getBubbleRadiusStyle() }}
														>
															{form.greeting_msg || __('Hello! How can I help you today?', 'dragwyb-click-to-chat')}
															{form.show_sources && (
																<div className="dctc-ai-sources" style={{ marginTop: '8px', paddingTop: '6px' }}>
																	<span className="dctc-ai-sources__label">
																		{__('Sources:', 'dragwyb-click-to-chat')}
																	</span>
																	<div className="dctc-ai-sources__list">
																		<span className="dctc-ai-source-chip">
																			<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="dctc-ai-source-chip__icon" aria-hidden="true">
																				<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6" />
																				<polyline points="15 3 21 3 21 9" />
																				<line x1="10" y1="14" x2="21" y2="3" />
																			</svg>
																			<span className="dctc-ai-source-chip__title">{__('Knowledge Base', 'dragwyb-click-to-chat')}</span>
																		</span>
																	</div>
																</div>
															)}
														</div>
													</div>

													{ /* User Message */}
													<div className="dctc-ai-mockup-msg-row is-user">
														<div
															className="dctc-ai-mockup-msg-bubble is-user"
															style={{
																backgroundColor: form.primary_color,
																borderRadius: getBubbleRadiusStyle(),
															}}
														>
															{__('Can you tell me more about your services?', 'dragwyb-click-to-chat')}
														</div>
														{form.show_user_avatar_in_chat && (
															<div className="dctc-ai-mockup-msg-avatar is-user">
																{renderUserAvatarIcon(14)}
															</div>
														)}
													</div>
												</div>
											</div>

											{ /* Floating Launcher Button Simulation */}
											<div className="dctc-ai-preview-launcher-row">
												{form.launcher_text && (
													<div className="dctc-ai-launcher-sim-callout">
														{form.launcher_text}
													</div>
												)}
												<div
													className="dctc-ai-launcher-sim-btn"
													style={{
														width: `${Math.min(Math.max(Number(form.widget_size) || 64, 36), 68)}px`,
														height: `${Math.min(Math.max(Number(form.widget_size) || 64, 36), 68)}px`,
														backgroundColor: form.primary_color || '#6366f1',
													}}
												>
													{renderLauncherIcon()}
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</section>

						{ /* 2. Screen Positioning & Custom Coordinates */}
						<section className="dctc-ai-card">
							<header className="dctc-ai-card__header">
								<div className="dctc-ai-card__header-left">
									<div className="dctc-ai-card-icon">
										<span className="dashicons dashicons-location-alt" />
									</div>
									<div>
										<h2 className="dctc-ai-card__title">
											{__('Screen Positioning & Coordinates', 'dragwyb-click-to-chat')}
										</h2>
										<p className="dctc-ai-card__desc">
											{__('Choose the corner anchor or configure custom pixel/percentage offsets.', 'dragwyb-click-to-chat')}
										</p>
									</div>
								</div>
							</header>

							<div className="dctc-ai-card__body">
								<div className="dctc-ai-position-selector" role="radiogroup">
									{[
										{
											value: 'bottom-left',
											label: __('Bottom Left', 'dragwyb-click-to-chat'),
											kind: 'corner',
											cx: 10,
										},
										{
											value: 'bottom-right',
											label: __('Bottom Right (Standard)', 'dragwyb-click-to-chat'),
											kind: 'corner',
											cx: 50,
										},
										{
											value: 'custom',
											label: __('Custom Coordinates', 'dragwyb-click-to-chat'),
											kind: 'custom',
										},
									].map((opt) => (
										<label
											key={opt.value}
											className={`dctc-ai-position-option ${form.position === opt.value ? 'is-active' : ''}`}
										>
											<input
												type="radio"
												name="dctc_ai_widget_position"
												value={opt.value}
												checked={form.position === opt.value}
												onChange={() => setField('position', opt.value)}
											/>
											<span className="dctc-ai-position-card">
												<svg width="60" height="40" viewBox="0 0 60 40" fill="none" aria-hidden="true">
													<rect width="60" height="40" rx="4" fill="#F3F4F6" />
													{opt.kind === 'corner' ? (
														<circle cx={opt.cx} cy="30" r="5" fill="currentColor" />
													) : (
														<>
															<path d="M25 15 L35 15 L30 10 Z" fill="currentColor" />
															<path d="M25 25 L35 25 L30 30 Z" fill="currentColor" />
															<path d="M15 20 L20 25 L20 15 Z" fill="currentColor" />
															<path d="M40 20 L35 25 L35 15 Z" fill="currentColor" />
														</>
													)}
												</svg>
												<span>{opt.label}</span>
											</span>
										</label>
									))}
								</div>

								{form.position === 'custom' && (
									<div className="dctc-ai-custom-position-box">
										<h4 className="dctc-ai-custom-position-box__title">
											{__('Custom Position Coordinates', 'dragwyb-click-to-chat')}
										</h4>
										<div className="dctc-ai-grid-2col">
											<div className="dctc-ai-bot-field">
												<label htmlFor="custom_vertical_align">
													{__('Vertical Anchor', 'dragwyb-click-to-chat')}
												</label>
												<select
													id="custom_vertical_align"
													className="dctc-ai-bot-select"
													value={form.custom_vertical_align}
													onChange={(e) => setField('custom_vertical_align', e.target.value)}
												>
													<option value="bottom">{__('Bottom', 'dragwyb-click-to-chat')}</option>
													<option value="top">{__('Top', 'dragwyb-click-to-chat')}</option>
												</select>

												<label htmlFor="custom_vertical" style={{ marginTop: '0.75rem' }}>
													{__('Vertical Distance Offset', 'dragwyb-click-to-chat')}
												</label>
												<div className="dctc-ai-widget-size-row">
													<input
														type="number"
														id="custom_vertical"
														className="dctc-ai-bot-input dctc-ai-widget-size-input"
														min="0"
														step="1"
														value={form.custom_vertical}
														onChange={(e) =>
															setField(
																'custom_vertical',
																e.target.value === '' ? '' : Number(e.target.value)
															)
														}
													/>
													<select
														className="dctc-ai-bot-select dctc-ai-widget-size-unit"
														value={form.custom_vertical_unit}
														onChange={(e) => setField('custom_vertical_unit', e.target.value)}
													>
														<option value="px">px</option>
														<option value="rem">rem</option>
														<option value="em">em</option>
														<option value="%">%</option>
													</select>
												</div>
											</div>

											<div className="dctc-ai-bot-field">
												<label htmlFor="custom_side">
													{__('Horizontal Anchor', 'dragwyb-click-to-chat')}
												</label>
												<select
													id="custom_side"
													className="dctc-ai-bot-select"
													value={form.custom_side}
													onChange={(e) => setField('custom_side', e.target.value)}
												>
													<option value="right">{__('Right', 'dragwyb-click-to-chat')}</option>
													<option value="left">{__('Left', 'dragwyb-click-to-chat')}</option>
												</select>

												<label htmlFor="custom_horizontal" style={{ marginTop: '0.75rem' }}>
													{__('Horizontal Distance Offset', 'dragwyb-click-to-chat')}
												</label>
												<div className="dctc-ai-widget-size-row">
													<input
														type="number"
														id="custom_horizontal"
														className="dctc-ai-bot-input dctc-ai-widget-size-input"
														min="0"
														step="1"
														value={form.custom_horizontal}
														onChange={(e) =>
															setField(
																'custom_horizontal',
																e.target.value === '' ? '' : Number(e.target.value)
															)
														}
													/>
													<select
														className="dctc-ai-bot-select dctc-ai-widget-size-unit"
														value={form.custom_horizontal_unit}
														onChange={(e) => setField('custom_horizontal_unit', e.target.value)}
													>
														<option value="px">px</option>
														<option value="rem">rem</option>
														<option value="em">em</option>
														<option value="%">%</option>
													</select>
												</div>
											</div>
										</div>
									</div>
								)}
							</div>
						</section>
					</div>
				)}

				{ /* =========================================================================
				     MESSAGES SUBTAB: Greetings, Starter Questions, Action Buttons, Notices & Fallbacks
				   ========================================================================= */ }
				{subtab === 'messages' && (
					<div className="dctc-ai-tab-panel-section">
						{ /* 1. Welcome Greeting & Proactive Teaser */}
						<section className="dctc-ai-card">
							<header className="dctc-ai-card__header">
								<div className="dctc-ai-card__header-left">
									<div className="dctc-ai-card-icon">
										<span className="dashicons dashicons-format-chat" />
									</div>
									<div>
										<h2 className="dctc-ai-card__title">
											{__('Conversational Greetings & Proactive Prompts', 'dragwyb-click-to-chat')}
										</h2>
										<p className="dctc-ai-card__desc">
											{__('Customize the welcome message and floating proactive bubble teaser.', 'dragwyb-click-to-chat')}
										</p>
									</div>
								</div>
							</header>

							<div className="dctc-ai-card__body">
								<div className="dctc-ai-form-stack">
									<div className="dctc-ai-bot-field">
										<label htmlFor="greeting_msg">
											{__('Initial Welcome Greeting Message', 'dragwyb-click-to-chat')}
										</label>
										<textarea
											id="greeting_msg"
											className="dctc-ai-bot-input dctc-ai-bot-textarea"
											rows="3"
											value={form.greeting_msg}
											onChange={(e) => setField('greeting_msg', e.target.value)}
											placeholder={__('Hello! I am your AI assistant. How can I help you today?', 'dragwyb-click-to-chat')}
										/>
										<p className="dctc-ai-bot-hint">
											{__('Sent automatically to greet visitors when they open the chat widget.', 'dragwyb-click-to-chat')}
										</p>
									</div>

									<div className="dctc-ai-bot-field">
										<label htmlFor="proactive_bubble_message">
											{__('Proactive Teaser Bubble Message', 'dragwyb-click-to-chat')}
										</label>
										<input
											type="text"
											id="proactive_bubble_message"
											className="dctc-ai-bot-input"
											value={form.proactive_bubble_message}
											onChange={(e) => setField('proactive_bubble_message', e.target.value)}
											placeholder="👋 Hi there! Have a question about this page? Let me know if I can help!"
										/>
										<p className="dctc-ai-bot-hint">
											{__('Displayed inside the floating popup preview bubble above the launcher button.', 'dragwyb-click-to-chat')}
										</p>
									</div>
								</div>
							</div>
						</section>

						{ /* 2. Suggested Starter Questions */}
						<SwitcherCard
							id="enable_pre_questions"
							title={__('Suggested Starter Questions', 'dragwyb-click-to-chat')}
							desc={__('Display clickable conversation starter pills when visitors first open the chat.', 'dragwyb-click-to-chat')}
							icon="dashicons-format-quote"
							checked={form.enable_pre_questions}
							onChange={(v) => setField('enable_pre_questions', v)}
							disabledNotice={__('Turn on to provide clickable starter questions when visitors open the chat.', 'dragwyb-click-to-chat')}
						>
							<div className="dctc-ai-grid-2col">
								<div className="dctc-ai-bot-field">
									<label htmlFor="pre_question_1">
										<span className="dctc-ai-num-pill">1</span>
										{__('Question 1', 'dragwyb-click-to-chat')}
									</label>
									<input
										type="text"
										id="pre_question_1"
										className="dctc-ai-bot-input"
										value={form.pre_question_1}
										onChange={(e) => setField('pre_question_1', e.target.value)}
										placeholder={__('e.g. What services do you offer?', 'dragwyb-click-to-chat')}
									/>
								</div>

								<div className="dctc-ai-bot-field">
									<label htmlFor="pre_question_2">
										<span className="dctc-ai-num-pill">2</span>
										{__('Question 2', 'dragwyb-click-to-chat')}
									</label>
									<input
										type="text"
										id="pre_question_2"
										className="dctc-ai-bot-input"
										value={form.pre_question_2}
										onChange={(e) => setField('pre_question_2', e.target.value)}
										placeholder={__('e.g. How can I contact support?', 'dragwyb-click-to-chat')}
									/>
								</div>

								<div className="dctc-ai-bot-field">
									<label htmlFor="pre_question_3">
										<span className="dctc-ai-num-pill">3</span>
										{__('Question 3', 'dragwyb-click-to-chat')}
									</label>
									<input
										type="text"
										id="pre_question_3"
										className="dctc-ai-bot-input"
										value={form.pre_question_3}
										onChange={(e) => setField('pre_question_3', e.target.value)}
										placeholder={__('e.g. What are your pricing plans?', 'dragwyb-click-to-chat')}
									/>
								</div>

								<div className="dctc-ai-bot-field">
									<label htmlFor="pre_question_4">
										<span className="dctc-ai-num-pill">4</span>
										{__('Question 4', 'dragwyb-click-to-chat')}
									</label>
									<input
										type="text"
										id="pre_question_4"
										className="dctc-ai-bot-input"
										value={form.pre_question_4}
										onChange={(e) => setField('pre_question_4', e.target.value)}
										placeholder={__('e.g. Tell me about your company.', 'dragwyb-click-to-chat')}
									/>
								</div>
							</div>

							<div className="dctc-ai-grid-2col" style={{ marginTop: '1.5rem', paddingTop: '1.25rem', borderTop: '1px solid #f1f5f9' }}>
								<div className="dctc-ai-form-stack">
									<ColorField
										id="pre_questions_bg_color"
										label={__('Background Color', 'dragwyb-click-to-chat')}
										value={form.pre_questions_bg_color}
										onChange={(v) => setField('pre_questions_bg_color', v)}
									/>
									<ColorField
										id="pre_questions_text_color"
										label={__('Text Color', 'dragwyb-click-to-chat')}
										value={form.pre_questions_text_color}
										onChange={(v) => setField('pre_questions_text_color', v)}
									/>
									<ColorField
										id="pre_questions_border_color"
										label={__('Border Color', 'dragwyb-click-to-chat')}
										value={form.pre_questions_border_color}
										onChange={(v) => setField('pre_questions_border_color', v)}
									/>
									<div className="dctc-ai-bot-field">
										<label htmlFor="pre_questions_border_radius">
											{__('Border Radius Style', 'dragwyb-click-to-chat')}
										</label>
										<select
											id="pre_questions_border_radius"
											className="dctc-ai-bot-select"
											value={form.pre_questions_border_radius}
											onChange={(e) => setField('pre_questions_border_radius', e.target.value)}
										>
											<option value="rounded">{__('Rounded (Default)', 'dragwyb-click-to-chat')}</option>
											<option value="square">{__('Square', 'dragwyb-click-to-chat')}</option>
											<option value="pill">{__('Pill', 'dragwyb-click-to-chat')}</option>
										</select>
									</div>
								</div>

								<div className="dctc-ai-preview-box-col">
									<div className="dctc-ai-preview-box-title">
										{__('Live Questions Preview', 'dragwyb-click-to-chat')}
									</div>
									<div className="dctc-ai-chips-preview-container">
										{[form.pre_question_1 || __('What services do you offer?', 'dragwyb-click-to-chat'),
										form.pre_question_2 || __('How can I contact support?', 'dragwyb-click-to-chat')].map((q, idx) => (
											<button
												key={idx}
												type="button"
												className="dctc-ai-preview-chip"
												style={{
													backgroundColor: form.pre_questions_bg_color,
													color: form.pre_questions_text_color,
													borderColor: form.pre_questions_border_color,
													borderRadius: getQuestionRadiusStyle(),
												}}
											>
												{q}
											</button>
										))}
									</div>
								</div>
							</div>
						</SwitcherCard>

						{ /* 3. Quick Action Buttons in Chat Header */}
						<section className="dctc-ai-card">
							<header className="dctc-ai-card__header">
								<div className="dctc-ai-card__header-left">
									<div className="dctc-ai-card-icon">
										<span className="dashicons dashicons-button" />
									</div>
									<div>
										<h2 className="dctc-ai-card__title">
											{__('Quick Action Buttons in Chat Header', 'dragwyb-click-to-chat')}
										</h2>
										<p className="dctc-ai-card__desc">
											{__('Add custom shortcut links (e.g., Contact Us, Pricing, WhatsApp) directly inside the chat header.', 'dragwyb-click-to-chat')}
										</p>
									</div>
								</div>
								<button
									type="button"
									className="dctc-ai-btn dctc-ai-btn-secondary dctc-ai-btn-sm"
									onClick={addActionButton}
								>
									<span className="dashicons dashicons-plus-alt2" />
									{__('Add Action Button', 'dragwyb-click-to-chat')}
								</button>
							</header>

							<div className="dctc-ai-card__body">
								{form.action_buttons.length === 0 ? (
									<div className="dctc-ai-empty-state">
										<span className="dashicons dashicons-editor-break dctc-ai-empty-state__icon" />
										<p className="dctc-ai-empty-state__title">
											{__('No quick action buttons added yet', 'dragwyb-click-to-chat')}
										</p>
										<p className="dctc-ai-empty-state__desc">
											{__('Add shortcuts for frequently visited pages, phone links, or WhatsApp redirect.', 'dragwyb-click-to-chat')}
										</p>
										<button
											type="button"
											className="dctc-ai-btn dctc-ai-btn-secondary"
											onClick={addActionButton}
										>
											<span className="dashicons dashicons-plus-alt2" />
											{__('Add Your First Button', 'dragwyb-click-to-chat')}
										</button>
									</div>
								) : (
									<div className="dctc-ai-action-buttons-list">
										<div className="dctc-ai-action-buttons-header">
											<span className="dctc-ai-col-label flex-1">{__('Button Label / Text', 'dragwyb-click-to-chat')}</span>
											<span className="dctc-ai-col-label flex-2">{__('Destination URL', 'dragwyb-click-to-chat')}</span>
											<span className="dctc-ai-col-label w-120">{__('Target', 'dragwyb-click-to-chat')}</span>
											<span className="dctc-ai-col-label w-40">{__('Remove', 'dragwyb-click-to-chat')}</span>
										</div>

										{form.action_buttons.map((btn, index) => (
											<div key={btn.id || index} className="dctc-ai-action-button-row">
												<div className="flex-1">
													<input
														type="text"
														className="dctc-ai-bot-input"
														placeholder={__('e.g. 📞 Contact Us', 'dragwyb-click-to-chat')}
														value={btn.label}
														onChange={(e) => updateActionButton(index, 'label', e.target.value)}
													/>
												</div>
												<div className="flex-2">
													<input
														type="text"
														className="dctc-ai-bot-input"
														placeholder={__('https://... or /contact', 'dragwyb-click-to-chat')}
														value={btn.url}
														onChange={(e) => updateActionButton(index, 'url', e.target.value)}
													/>
												</div>
												<div className="w-120">
													<select
														className="dctc-ai-bot-select"
														value={btn.target || '_blank'}
														onChange={(e) => updateActionButton(index, 'target', e.target.value)}
													>
														<option value="_blank">{__('New Tab', 'dragwyb-click-to-chat')}</option>
														<option value="_self">{__('Same Tab', 'dragwyb-click-to-chat')}</option>
													</select>
												</div>
												<div className="w-40">
													<button
														type="button"
														className="dctc-ai-btn-icon-danger"
														onClick={() => removeActionButton(index)}
														title={__('Remove button', 'dragwyb-click-to-chat')}
													>
														<span className="dashicons dashicons-trash" />
													</button>
												</div>
											</div>
										))}
									</div>
								)}
							</div>
						</section>

						{ /* 4. WooCommerce Order Tracking Messages (Only shown when WooCommerce is active) */}
						{isWcActive && (
						<section className="dctc-ai-card">
							<header className="dctc-ai-card__header">
								<div className="dctc-ai-card__header-left">
									<div className="dctc-ai-card-icon dctc-ai-card-icon--active">
										<span className="dashicons dashicons-cart" />
									</div>
									<div>
										<h2 className="dctc-ai-card__title">
											{__('WooCommerce Order Tracking Notices', 'dragwyb-click-to-chat')}
										</h2>
										<p className="dctc-ai-card__desc">
											{__('Set prompts, guest login requirement notices, and email verification mismatch messages.', 'dragwyb-click-to-chat')}
										</p>
									</div>
								</div>
							</header>

							<div className="dctc-ai-card__body">
								<div className="dctc-ai-form-stack">
									<div className="dctc-ai-bot-field">
										<label htmlFor="order_tracking_prompt_msg">
											{__('Order Tracking Form Header Prompt (Logged-in Users)', 'dragwyb-click-to-chat')}
										</label>
										<textarea
											id="order_tracking_prompt_msg"
											className="dctc-ai-bot-input dctc-ai-bot-textarea"
											rows="2"
											value={form.order_tracking_prompt_msg}
											onChange={(e) => setField('order_tracking_prompt_msg', e.target.value)}
											placeholder={__('Please enter your Order ID and billing email below to view your real-time order and shipment tracking details.', 'dragwyb-click-to-chat')}
										/>
										<p className="dctc-ai-bot-hint">
											{__('Displayed in chat when a logged-in user asks about tracking their order status.', 'dragwyb-click-to-chat')}
										</p>
									</div>

									<div className="dctc-ai-bot-field">
										<label htmlFor="order_tracking_login_msg">
											{__('Guest Login Required Notice', 'dragwyb-click-to-chat')}
											<span className="dctc-ai-var-badge">{'{login_url}'}</span>
										</label>
										<textarea
											id="order_tracking_login_msg"
											className="dctc-ai-bot-input dctc-ai-bot-textarea"
											rows="2"
											value={form.order_tracking_login_msg}
											onChange={(e) => setField('order_tracking_login_msg', e.target.value)}
											placeholder={__('To securely track your order status, please [log in to your account]({login_url}) first.', 'dragwyb-click-to-chat')}
										/>
										<p className="dctc-ai-bot-hint">
											{__('Returned when a guest/non-logged-in visitor attempts to track an order. {login_url} is replaced with your login/my-account link.', 'dragwyb-click-to-chat')}
										</p>
									</div>

									<div className="dctc-ai-bot-field">
										<label htmlFor="order_mismatch_msg">
											{__('Order Email Mismatch Privacy Notice', 'dragwyb-click-to-chat')}
										</label>
										<textarea
											id="order_mismatch_msg"
											className="dctc-ai-bot-input dctc-ai-bot-textarea"
											rows="2"
											value={form.order_mismatch_msg}
											onChange={(e) => setField('order_mismatch_msg', e.target.value)}
											placeholder={__('This order was purchased with a different email address. For privacy and security reasons, order details cannot be displayed.', 'dragwyb-click-to-chat')}
										/>
										<p className="dctc-ai-bot-hint">
											{__('Displayed when the tracked order belongs to a different email address or purchaser account.', 'dragwyb-click-to-chat')}
										</p>
									</div>
								</div>
							</div>
						</section>
						)}

						{ /* 5. Support Escalation & Ticket Logging */}
						<section className="dctc-ai-card">
							<header className="dctc-ai-card__header">
								<div className="dctc-ai-card__header-left">
									<div className="dctc-ai-card-icon dctc-ai-card-icon--active">
										<span className="dashicons dashicons-sos" />
									</div>
									<div>
										<h2 className="dctc-ai-card__title">
											{__('Support Ticket Escalation Notice', 'dragwyb-click-to-chat')}
										</h2>
										<p className="dctc-ai-card__desc">
											{__('Message shown when the AI escalates complex queries directly into a Support Ticket.', 'dragwyb-click-to-chat')}
										</p>
									</div>
								</div>
							</header>

							<div className="dctc-ai-card__body">
								<div className="dctc-ai-form-stack">
									<div className="dctc-ai-bot-field">
										<label htmlFor="support_ticket_msg">
											{__('Automatic Ticket Creation Notice', 'dragwyb-click-to-chat')}
										</label>
										<textarea
											id="support_ticket_msg"
											className="dctc-ai-bot-input dctc-ai-bot-textarea"
											rows="3"
											value={form.support_ticket_msg}
											onChange={(e) => setField('support_ticket_msg', e.target.value)}
											placeholder={__('I have logged your inquiry with our support team and created a support ticket for this session. A support specialist will review your message and assist you shortly.', 'dragwyb-click-to-chat')}
										/>
										<p className="dctc-ai-bot-hint">
											{__('Sent to the visitor in chat when a support ticket is created for their active session.', 'dragwyb-click-to-chat')}
										</p>
									</div>
								</div>
							</div>
						</section>

						{ /* 6. Fallbacks, Server Errors & Daily Limits */}
						<section className="dctc-ai-card">
							<header className="dctc-ai-card__header">
								<div className="dctc-ai-card__header-left">
									<div className="dctc-ai-card-icon dctc-ai-card-icon--warning">
										<span className="dashicons dashicons-warning" />
									</div>
									<div>
										<h2 className="dctc-ai-card__title">
											{__('Fallbacks, Server Errors & Daily Limits', 'dragwyb-click-to-chat')}
										</h2>
										<p className="dctc-ai-card__desc">
											{__('Configure fallback responses for server issues, empty knowledge results, and quota caps.', 'dragwyb-click-to-chat')}
										</p>
									</div>
								</div>
							</header>

							<div className="dctc-ai-card__body">
								<div className="dctc-ai-form-stack">
									<div className="dctc-ai-bot-field">
										<label htmlFor="api_error_msg">
											{__('API / Server Error Fallback Message', 'dragwyb-click-to-chat')}
											<span className="dctc-ai-var-badge">{'{support_url}'}</span>
										</label>
										<textarea
											id="api_error_msg"
											className="dctc-ai-bot-input dctc-ai-bot-textarea"
											rows="3"
											value={form.api_error_msg}
											onChange={(e) => setField('api_error_msg', e.target.value)}
											placeholder={__('There is some error on server, please contact our [support agent]({support_url}).', 'dragwyb-click-to-chat')}
										/>
										<p className="dctc-ai-bot-hint">
											{__('Markdown supported. Use [support agent]({support_url}) to link to your Support Helpdesk URL.', 'dragwyb-click-to-chat')}
										</p>
									</div>

									<div className="dctc-ai-bot-field">
										<label htmlFor="no_data_message">
											{__('Knowledge Base / No Relevant Data Fallback Message', 'dragwyb-click-to-chat')}
										</label>
										<textarea
											id="no_data_message"
											className="dctc-ai-bot-input dctc-ai-bot-textarea"
											rows="3"
											value={form.no_data_message}
											onChange={(e) => setField('no_data_message', e.target.value)}
											placeholder={__("I don't have information about your question in my knowledge base. Please rephrase or ask about topics I have knowledge of.", 'dragwyb-click-to-chat')}
										/>
										<p className="dctc-ai-bot-hint">
											{__('Returned when the query cannot be answered by your indexed site content or trained knowledge base.', 'dragwyb-click-to-chat')}
										</p>
									</div>

									<div className="dctc-ai-bot-field">
										<label htmlFor="budget_limit_message">
											{__('Daily Chat Limit Reached Notice', 'dragwyb-click-to-chat')}
										</label>
										<textarea
											id="budget_limit_message"
											className="dctc-ai-bot-input dctc-ai-bot-textarea"
											rows="2"
											value={form.budget_limit_message}
											onChange={(e) => setField('budget_limit_message', e.target.value)}
											placeholder={__('You have reached the daily chat limit. Please connect with our team directly via WhatsApp or Support.', 'dragwyb-click-to-chat')}
										/>
										<p className="dctc-ai-bot-hint">
											{__('Displayed when a visitor exceeds the maximum permitted messages per day.', 'dragwyb-click-to-chat')}
										</p>
									</div>
								</div>
							</div>
						</section>

						{ /* 7. Lead Generation Form Texts */}
						<section className="dctc-ai-card">
							<header className="dctc-ai-card__header">
								<div className="dctc-ai-card__header-left">
									<div className="dctc-ai-card-icon">
										<span className="dashicons dashicons-businessman" />
									</div>
									<div>
										<h2 className="dctc-ai-card__title">
											{__('Lead Capture Form Headings', 'dragwyb-click-to-chat')}
										</h2>
										<p className="dctc-ai-card__desc">
											{__('Configure headings displayed on interactive lead capture cards in chat.', 'dragwyb-click-to-chat')}
										</p>
									</div>
								</div>
							</header>

							<div className="dctc-ai-card__body">
								<div className="dctc-ai-form-stack">
									<div className="dctc-ai-grid-2col">
										<div className="dctc-ai-bot-field">
											<label htmlFor="lead_form_title">
												{__('Lead Card Heading', 'dragwyb-click-to-chat')}
											</label>
											<input
												type="text"
												id="lead_form_title"
												className="dctc-ai-bot-input"
												value={form.lead_form_title}
												onChange={(e) => setField('lead_form_title', e.target.value)}
												placeholder={__('Contact Our Team', 'dragwyb-click-to-chat')}
											/>
										</div>

										<div className="dctc-ai-bot-field">
											<label htmlFor="lead_form_subtitle">
												{__('Lead Card Subtitle / Description', 'dragwyb-click-to-chat')}
											</label>
											<input
												type="text"
												id="lead_form_subtitle"
												className="dctc-ai-bot-input"
												value={form.lead_form_subtitle}
												onChange={(e) => setField('lead_form_subtitle', e.target.value)}
												placeholder={__('Leave your details and our team will get back to you shortly.', 'dragwyb-click-to-chat')}
											/>
										</div>
									</div>
								</div>
							</div>
						</section>
					</div>
				)}

				{ /* Save Footer */}
				<footer className="dctc-ai-form-footer">
					<div className="dctc-ai-form-footer__status">
						{dirty ? (
							<span className="dctc-ai-unsaved-badge">
								<span className="dctc-ai-dot is-warning" />
								{__('Unsaved changes', 'dragwyb-click-to-chat')}
							</span>
						) : (
							<span className="dctc-ai-saved-badge">
								<span className="dctc-ai-dot is-success" />
								{__('All settings saved', 'dragwyb-click-to-chat')}
							</span>
						)}
					</div>
					<button
						type="submit"
						className="dctc-ai-btn dctc-ai-btn-primary"
						disabled={saving || !dirty}
					>
						{saving ? (
							<>
								<span className="dctc-ai-spinner" aria-hidden="true" />{' '}
								{__('Saving Changes…', 'dragwyb-click-to-chat')}
							</>
						) : (
							<>
								{__('Save', 'dragwyb-click-to-chat')}
							</>
						)}
					</button>
				</footer>
			</form>
		</div>
	);
}
