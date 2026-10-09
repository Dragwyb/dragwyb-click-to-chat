/**
 * Support Center - Support Portal Customizer & Theme Settings View
 */
import { useState, useEffect, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import {
	SettingCard,
	FormField,
	ColorField,
	RangeField,
	NoticeBanner,
} from '../components';

const THEME_PRESETS = [
	{
		id: 'indigo',
		name: __('Modern Indigo (Default)', 'dragwyb-click-to-chat'),
		primary: '#4F46E5',
		hover: '#4338CA',
		text: '#FFFFFF',
		headerBg: '#F8FAFC',
		headerTitle: '#111827',
		headerSubtitle: '#6B7280',
		containerBg: '#FFFFFF',
		cardBg: '#F9FAFB',
		cardBorder: '#E5E7EB',
		cardHoverBg: '#F3F4F6',
		inputBg: '#F8FAFC',
		inputBorder: '#CBD5E1',
		secondaryBg: '#FFFFFF',
		secondaryText: '#374151',
		secondaryBorder: '#D1D5DB',
		secondaryHoverBg: '#F3F4F6',
		border: '#E5E7EB',
		radius: 12,
		borderWidth: 1,
		borderStyle: 'solid',
	},
	{
		id: 'blue',
		name: __('Ocean Blue', 'dragwyb-click-to-chat'),
		primary: '#0284C7',
		hover: '#0369A1',
		text: '#FFFFFF',
		headerBg: '#F0F9FF',
		headerTitle: '#0C4A6E',
		headerSubtitle: '#0369A1',
		containerBg: '#FFFFFF',
		cardBg: '#F8FAFC',
		cardBorder: '#E0F2FE',
		cardHoverBg: '#F0F9FF',
		inputBg: '#F0F9FF',
		inputBorder: '#BAE6FD',
		secondaryBg: '#FFFFFF',
		secondaryText: '#0369A1',
		secondaryBorder: '#BAE6FD',
		secondaryHoverBg: '#E0F2FE',
		border: '#E0F2FE',
		radius: 10,
		borderWidth: 1,
		borderStyle: 'solid',
	},
	{
		id: 'emerald',
		name: __('Emerald Forest', 'dragwyb-click-to-chat'),
		primary: '#059669',
		hover: '#047857',
		text: '#FFFFFF',
		headerBg: '#ECFDF5',
		headerTitle: '#064E3B',
		headerSubtitle: '#047857',
		containerBg: '#FFFFFF',
		cardBg: '#F9FAFB',
		cardBorder: '#D1FAE5',
		cardHoverBg: '#ECFDF5',
		inputBg: '#F0FDF4',
		inputBorder: '#A7F3D0',
		secondaryBg: '#FFFFFF',
		secondaryText: '#065F46',
		secondaryBorder: '#A7F3D0',
		secondaryHoverBg: '#ECFDF5',
		border: '#D1FAE5',
		radius: 14,
		borderWidth: 1,
		borderStyle: 'solid',
	},
	{
		id: 'dark',
		name: __('Dark Slate', 'dragwyb-click-to-chat'),
		primary: '#6366F1',
		hover: '#4F46E5',
		text: '#FFFFFF',
		headerBg: '#1E293B',
		headerTitle: '#F8FAFC',
		headerSubtitle: '#94A3B8',
		containerBg: '#0F172A',
		cardBg: '#1E293B',
		cardBorder: '#334155',
		cardHoverBg: '#273549',
		inputBg: '#1E293B',
		inputBorder: '#475569',
		secondaryBg: '#1E293B',
		secondaryText: '#F1F5F9',
		secondaryBorder: '#475569',
		secondaryHoverBg: '#334155',
		border: '#334155',
		radius: 12,
		borderWidth: 1,
		borderStyle: 'solid',
	},
	{
		id: 'purple',
		name: __('Royal Purple', 'dragwyb-click-to-chat'),
		primary: '#7C3AED',
		hover: '#6D28D9',
		text: '#FFFFFF',
		headerBg: '#F5F3FF',
		headerTitle: '#4C1D95',
		headerSubtitle: '#6D28D9',
		containerBg: '#FFFFFF',
		cardBg: '#FAFAF9',
		cardBorder: '#EDE9FE',
		cardHoverBg: '#F5F3FF',
		inputBg: '#FAF5FF',
		inputBorder: '#DDD6FE',
		secondaryBg: '#FFFFFF',
		secondaryText: '#5B21B6',
		secondaryBorder: '#DDD6FE',
		secondaryHoverBg: '#F5F3FF',
		border: '#EDE9FE',
		radius: 16,
		borderWidth: 1,
		borderStyle: 'solid',
	},
	{
		id: 'amber',
		name: __('Sunset Amber', 'dragwyb-click-to-chat'),
		primary: '#D97706',
		hover: '#B45309',
		text: '#FFFFFF',
		headerBg: '#FFFBEB',
		headerTitle: '#78350F',
		headerSubtitle: '#92400E',
		containerBg: '#FFFFFF',
		cardBg: '#FFFDF5',
		cardBorder: '#FEF3C7',
		cardHoverBg: '#FEF3C7',
		inputBg: '#FFFBEB',
		inputBorder: '#FDE68A',
		secondaryBg: '#FFFFFF',
		secondaryText: '#92400E',
		secondaryBorder: '#FDE68A',
		secondaryHoverBg: '#FEF3C7',
		border: '#FEF3C7',
		radius: 8,
		borderWidth: 1,
		borderStyle: 'solid',
	},
	{
		id: 'rose',
		name: __('Crimson Rose', 'dragwyb-click-to-chat'),
		primary: '#E11D48',
		hover: '#BE123C',
		text: '#FFFFFF',
		headerBg: '#FFF1F2',
		headerTitle: '#881337',
		headerSubtitle: '#9F1239',
		containerBg: '#FFFFFF',
		cardBg: '#FFF5F5',
		cardBorder: '#FFE4E6',
		cardHoverBg: '#FFE4E6',
		inputBg: '#FFF1F2',
		inputBorder: '#FECDD3',
		secondaryBg: '#FFFFFF',
		secondaryText: '#9F1239',
		secondaryBorder: '#FECDD3',
		secondaryHoverBg: '#FFE4E6',
		border: '#FFE4E6',
		radius: 12,
		borderWidth: 1,
		borderStyle: 'solid',
	},
	{
		id: 'monochrome',
		name: __('Clean Monochrome', 'dragwyb-click-to-chat'),
		primary: '#18181B',
		hover: '#09090B',
		text: '#FFFFFF',
		headerBg: '#F4F4F5',
		headerTitle: '#09090B',
		headerSubtitle: '#52525B',
		containerBg: '#FFFFFF',
		cardBg: '#FAFAFA',
		cardBorder: '#E4E4E7',
		cardHoverBg: '#F4F4F5',
		inputBg: '#FAFAFA',
		inputBorder: '#D4D4D8',
		secondaryBg: '#FFFFFF',
		secondaryText: '#18181B',
		secondaryBorder: '#D4D4D8',
		secondaryHoverBg: '#F4F4F5',
		border: '#E4E4E7',
		radius: 6,
		borderWidth: 1,
		borderStyle: 'solid',
	},
];

const DEFAULT_PORTAL_SETTINGS = {
	preset: 'indigo',
	primary_color: '#4F46E5',
	primary_hover_color: '#4338CA',
	primary_text_color: '#FFFFFF',
	secondary_btn_bg: '#FFFFFF',
	secondary_btn_text: '#374151',
	secondary_btn_border: '#D1D5DB',
	secondary_btn_hover_bg: '#F3F4F6',
	border_color: '#E5E7EB',
	border_width: 1,
	border_style: 'solid',
	border_radius: 12,
	container_bg_color: '#FFFFFF',
	header_bg_color: '#F9FAFB',
	header_border_color: '#E5E7EB',
	header_title_color: '#111827',
	header_subtitle_color: '#6B7280',
	card_bg_color: '#F9FAFB',
	card_hover_bg_color: '#F3F4F6',
	card_border_color: '#E5E7EB',
	input_bg_color: '#F8FAFC',
	input_border_color: '#CBD5E1',
	font_family: 'inherit',
	portal_title: __('Help & Support Center', 'dragwyb-click-to-chat'),
	portal_subtitle: __('View your recent requests, check status updates, or start a new support conversation.', 'dragwyb-click-to-chat'),
	btn_new_ticket_text: __('New Support Request', 'dragwyb-click-to-chat'),
	btn_back_tickets_text: __('Back to My Tickets', 'dragwyb-click-to-chat'),
	search_placeholder: __('Search your tickets by subject or number...', 'dragwyb-click-to-chat'),
	loading_text: __('Loading support tickets...', 'dragwyb-click-to-chat'),
	empty_tickets_title: __('No support requests found', 'dragwyb-click-to-chat'),
	empty_tickets_desc: __('You have not submitted any support tickets yet. Click "New Support Request" to start one.', 'dragwyb-click-to-chat'),
	modal_title: __('Create a New Support Request', 'dragwyb-click-to-chat'),
	modal_subtitle: __('Submit your inquiry and our support team will assist you shortly.', 'dragwyb-click-to-chat'),
	category_label: __('Category', 'dragwyb-click-to-chat'),
	taxonomy_labels: {},
	subject_label: __('Subject', 'dragwyb-click-to-chat'),
	subject_placeholder: __('Enter a support issue title...', 'dragwyb-click-to-chat'),
	message_label: __('Message', 'dragwyb-click-to-chat'),
	btn_submit_ticket_text: __('Submit Support Request', 'dragwyb-click-to-chat'),
	btn_cancel_text: __('Cancel', 'dragwyb-click-to-chat'),
	btn_close_ticket_text: __('Close Ticket', 'dragwyb-click-to-chat'),
	btn_send_reply_text: __('Send Reply', 'dragwyb-click-to-chat'),
	reply_placeholder: __('Type your reply message...', 'dragwyb-click-to-chat'),
	enable_guest_ticket_form: true,
	show_login_button: true,
	show_register_button: true,
	guest_auth_box_title: __('Customer Support Portal', 'dragwyb-click-to-chat'),
	guest_auth_box_desc: __('Please log in to your account or submit a support request directly below as a guest.', 'dragwyb-click-to-chat'),
	btn_login_text: __('Log In to Submit Ticket', 'dragwyb-click-to-chat'),
	btn_register_text: __('Register Account', 'dragwyb-click-to-chat'),
	btn_guest_create_ticket_text: __('Submit Ticket as Guest', 'dragwyb-click-to-chat'),
	guest_name_label: __('Your Name', 'dragwyb-click-to-chat'),
	guest_name_placeholder: __('John Doe', 'dragwyb-click-to-chat'),
	guest_email_label: __('Your Email Address', 'dragwyb-click-to-chat'),
	guest_email_placeholder: __('you@example.com', 'dragwyb-click-to-chat'),
};

export default function PortalView({
	portalSettings: initialSettings,
	setPortalSettings: setParentSettings,
	onShowNotice,
}) {
	const [settings, setSettings] = useState(initialSettings || DEFAULT_PORTAL_SETTINGS);
	const [activeTab, setActiveTab] = useState('styling'); // styling, text, guest
	const [previewAuthMode, setPreviewAuthMode] = useState('logged_in'); // logged_in, logged_out
	const [previewViewMode, setPreviewViewMode] = useState('list'); // list, modal, detail
	const [saving, setSaving] = useState(false);
	const [localNotice, setLocalNotice] = useState(null);
	const [copiedShortcode, setCopiedShortcode] = useState(false);

	// Dynamic Taxonomies & Items State
	const [rawTaxonomies, setRawTaxonomies] = useState([]);
	const [categoriesList, setCategoriesList] = useState([]);
	const [productsList, setProductsList] = useState([]);
	const [tagsList, setTagsList] = useState([]);

	useEffect(() => {
		if (initialSettings && Object.keys(initialSettings).length > 0) {
			setSettings((prev) => ({ ...prev, ...initialSettings }));
		}
	}, [initialSettings]);

	// Fetch all registered taxonomies, products, tags, and categories
	const fetchTaxonomiesData = useCallback(async () => {
		try {
			const [taxRes, catRes, prodRes, tagRes] = await Promise.allSettled([
				apiFetch({ path: '/dctc-ai/v1/support/taxonomies' }),
				apiFetch({ path: '/dctc-ai/v1/support/categories' }),
				apiFetch({ path: '/dctc-ai/v1/support/products' }),
				apiFetch({ path: '/dctc-ai/v1/support/tags' }),
			]);

			if (taxRes.status === 'fulfilled' && taxRes.value?.success && Array.isArray(taxRes.value.taxonomies)) {
				setRawTaxonomies(taxRes.value.taxonomies);
			}
			if (catRes.status === 'fulfilled' && catRes.value?.success && Array.isArray(catRes.value.categories)) {
				setCategoriesList(catRes.value.categories);
			}
			if (prodRes.status === 'fulfilled' && prodRes.value?.success && Array.isArray(prodRes.value.products)) {
				setProductsList(prodRes.value.products);
			}
			if (tagRes.status === 'fulfilled' && tagRes.value?.success && Array.isArray(tagRes.value.tags)) {
				setTagsList(tagRes.value.tags);
			}
		} catch (err) {
			console.error('Error fetching taxonomies data:', err);
		}
	}, []);

	useEffect(() => {
		fetchTaxonomiesData();
	}, [fetchTaxonomiesData]);

	// Compute taxonomies that actually have registered items (excluding primary category)
	const taxonomiesWithItems = rawTaxonomies
		.filter((tax) => tax.slug !== 'category')
		.map((tax) => {
			let count = 0;
			if (tax.slug === 'product') {
				count = productsList.length;
			} else if (tax.slug === 'tag') {
				count = tagsList.length;
			} else {
				count = Array.isArray(tax.terms) ? tax.terms.length : 0;
			}
			return {
				...tax,
				itemsCount: count,
			};
		})
		.filter((tax) => tax.itemsCount > 0);

	const updateSetting = (key, value) => {
		setSettings((prev) => {
			const next = { ...prev, [key]: value };
			if (setParentSettings) setParentSettings(next);
			return next;
		});
	};

	const updateTaxonomyLabel = (slug, label) => {
		setSettings((prev) => {
			const nextLabels = { ...(prev.taxonomy_labels || {}), [slug]: label };
			const next = { ...prev, taxonomy_labels: nextLabels };
			if (setParentSettings) setParentSettings(next);
			return next;
		});
	};

	const applyPreset = (presetId) => {
		const preset = THEME_PRESETS.find((p) => p.id === presetId);
		if (!preset) return;

		setSettings((prev) => {
			const updated = {
				...prev,
				preset: presetId,
				primary_color: preset.primary,
				primary_hover_color: preset.hover,
				primary_text_color: preset.text,
				header_bg_color: preset.headerBg,
				header_title_color: preset.headerTitle,
				header_subtitle_color: preset.headerSubtitle,
				container_bg_color: preset.containerBg,
				card_bg_color: preset.cardBg,
				card_border_color: preset.cardBorder,
				card_hover_bg_color: preset.cardHoverBg,
				input_bg_color: preset.inputBg,
				input_border_color: preset.inputBorder,
				secondary_btn_bg: preset.secondaryBg,
				secondary_btn_text: preset.secondaryText,
				secondary_btn_border: preset.secondaryBorder,
				secondary_btn_hover_bg: preset.secondaryHoverBg,
				border_color: preset.border,
				border_radius: preset.radius,
				border_width: preset.borderWidth,
				border_style: preset.borderStyle,
			};
			if (setParentSettings) setParentSettings(updated);
			return updated;
		});
	};

	const handleSave = async (e) => {
		if (e) e.preventDefault();
		setSaving(true);
		setLocalNotice(null);

		try {
			const res = await apiFetch({
				path: '/dctc-ai/v1/support/portal-settings',
				method: 'POST',
				data: settings,
			});

			if (res?.success) {
				setSettings(res.settings || settings);
				if (setParentSettings) setParentSettings(res.settings || settings);
				const msg = __('Support Portal styling & configuration saved successfully!', 'dragwyb-click-to-chat');
				setLocalNotice({ type: 'success', message: msg });
				if (onShowNotice) onShowNotice(msg, 'success');
			} else {
				throw new Error(res?.message || __('Could not save settings.', 'dragwyb-click-to-chat'));
			}
		} catch (err) {
			console.error('Error saving portal settings:', err);
			const errMsg = err?.message || __('Error saving portal settings.', 'dragwyb-click-to-chat');
			setLocalNotice({ type: 'error', message: errMsg });
			if (onShowNotice) onShowNotice(errMsg, 'error');
		} finally {
			setSaving(false);
			setTimeout(() => setLocalNotice(null), 5000);
		}
	};

	const copyShortcode = () => {
		navigator.clipboard.writeText('[dctc_support_portal]');
		setCopiedShortcode(true);
		setTimeout(() => setCopiedShortcode(false), 2500);
	};

	function isDarkColor(hex) {
		if (!hex) return false;
		let c = String(hex).replace(/^#/, '').trim();
		if (c.length === 3) {
			c = c[0] + c[0] + c[1] + c[1] + c[2] + c[2];
		}
		if (c.length !== 6) return false;
		const r = parseInt(c.substring(0, 2), 16) || 0;
		const g = parseInt(c.substring(2, 4), 16) || 0;
		const b = parseInt(c.substring(4, 6), 16) || 0;
		const luma = (0.299 * r) + (0.587 * g) + (0.114 * b);
		return luma < 145;
	}

	// Preview calculated styles
	const previewPrimary = settings.primary_color || '#4F46E5';
	const previewPrimaryHover = settings.primary_hover_color || '#4338CA';
	const previewPrimaryText = settings.primary_text_color || '#FFFFFF';
	const previewHeaderBg = settings.header_bg_color || '#F9FAFB';
	const previewHeaderTitle = settings.header_title_color || '#111827';
	const previewHeaderSub = settings.header_subtitle_color || '#6B7280';
	const previewContainerBg = settings.container_bg_color || '#FFFFFF';
	const previewCardBg = settings.card_bg_color || '#F9FAFB';
	const previewCardBorder = settings.card_border_color || '#E5E7EB';
	const previewInputBg = settings.input_bg_color || '#F8FAFC';
	const previewInputBorder = settings.input_border_color || '#CBD5E1';
	const previewBorderColor = settings.border_color || '#E5E7EB';
	const previewBorderWidth = `${settings.border_width ?? 1}px`;
	const previewBorderStyle = settings.border_style || 'solid';
	const previewRadius = `${settings.border_radius ?? 12}px`;
	const previewBtnRadius = `${Math.max(4, Math.round((settings.border_radius ?? 12) * 0.65))}px`;
	const previewSecondaryBg = settings.secondary_btn_bg || '#FFFFFF';
	const previewSecondaryText = settings.secondary_btn_text || '#374151';
	const previewSecondaryBorder = settings.secondary_btn_border || '#D1D5DB';

	const isDarkContainer = isDarkColor(previewContainerBg);
	const isDarkCard = isDarkColor(previewCardBg);

	const previewCardTitle = isDarkCard ? '#F8FAFC' : '#0F172A';
	const previewCardDate = isDarkCard ? '#94A3B8' : '#64748B';
	const previewCardDesc = isDarkCard ? '#CBD5E1' : '#475569';

	const previewInputText = isDarkColor(previewInputBg) ? '#F8FAFC' : '#0F172A';
	const previewInputPlaceholder = isDarkColor(previewInputBg) ? '#94A3B8' : '#9CA3AF';

	const previewCustomerMsgBg = isDarkContainer ? 'rgba(99, 102, 241, 0.22)' : '#EEF2FF';
	const previewCustomerMsgText = isDarkContainer ? '#F8FAFC' : '#1E1B4B';
	const previewCustomerMsgSender = isDarkContainer ? '#A5B4FC' : '#4338CA';
	const previewCustomerMsgBorder = isDarkContainer ? 'rgba(99, 102, 241, 0.4)' : '#C7D2FE';

	const previewAgentMsgBg = isDarkContainer ? '#1E293B' : '#F9FAFB';
	const previewAgentMsgText = isDarkContainer ? '#F1F5F9' : '#111827';
	const previewAgentMsgSender = isDarkContainer ? '#34D399' : '#047857';
	const previewAgentMsgBorder = isDarkContainer ? '#334155' : '#E5E7EB';

	const previewModalBg = isDarkContainer ? '#0F172A' : '#FFFFFF';
	const previewModalBorder = isDarkContainer ? '#334155' : '#E2E8F0';
	const previewFormLabel = isDarkContainer ? '#E2E8F0' : '#334155';

	const previewBadgeCatBg = isDarkCard ? 'rgba(99, 102, 241, 0.22)' : '#EEF2FF';
	const previewBadgeCatText = isDarkCard ? '#C7D2FE' : '#4338CA';
	const previewBadgeAgentBg = isDarkCard ? 'rgba(16, 185, 129, 0.2)' : '#ECFDF5';
	const previewBadgeAgentText = isDarkCard ? '#6EE7B7' : '#047857';
	const previewBadgeChatsBg = isDarkCard ? 'rgba(245, 158, 11, 0.2)' : '#FEF3C7';
	const previewBadgeChatsText = isDarkCard ? '#FCD34D' : '#B45309';
	const previewBadgeTagBg = isDarkCard ? 'rgba(148, 163, 184, 0.2)' : '#F1F5F9';
	const previewBadgeTagText = isDarkCard ? '#E2E8F0' : '#475569';

	return (
		<div className="dctc-sc-settings-wrap" style={{ maxWidth: '1440px', margin: '0 auto' }}>
			{localNotice && (
				<NoticeBanner
					type={localNotice.type}
					message={localNotice.message}
					onDismiss={() => setLocalNotice(null)}
					className="dctc-sc-settings-banner"
				/>
			)}

			{ /* Top Header & Shortcode Embed Hero */}
			<div className="dctc-sc-hero-banner-modern" style={{ marginBottom: '22px' }}>
				<div className="dctc-sc-hero-left">
					<div className="dctc-sc-hero-avatar-circle" style={{ background: 'linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%)' }}>
						<span className="dashicons dashicons-desktop" style={{ fontSize: '24px', color: '#fff' }}></span>
					</div>
					<div>
						<div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
							<h2 style={{ margin: 0, fontSize: '20px', fontWeight: 800, color: '#0f172a' }}>
								{__('Support Portal Customizer', 'dragwyb-click-to-chat')}
							</h2>
							<span className="dctc-sc-status-pill enabled">
								<span className="dashicons dashicons-yes"></span>
								{__('Ready to Embed', 'dragwyb-click-to-chat')}
							</span>
						</div>
						<p style={{ margin: '4px 0 0', fontSize: '13.5px', color: '#64748b' }}>
							{__('Design the frontend customer support portal, customize colors, border radius, headings, button texts, dynamic registered taxonomies, and guest ticket submissions.', 'dragwyb-click-to-chat')}
						</p>
					</div>
				</div>

				<div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
					<div style={{ display: 'flex', alignItems: 'center', background: '#f1f5f9', border: '1px solid #cbd5e1', borderRadius: '8px', padding: '4px 10px', gap: '8px' }}>
						<code style={{ fontWeight: 700, color: '#4f46e5', fontSize: '13px', background: 'transparent', padding: 0 }}>
							[dctc_support_portal]
						</code>
						<button
							type="button"
							onClick={copyShortcode}
							className="button button-small"
							style={{ display: 'inline-flex', alignItems: 'center', gap: '4px', fontWeight: 600 }}
						>
							<span className={`dashicons ${copiedShortcode ? 'dashicons-yes' : 'dashicons-clipboard'}`} style={{ fontSize: '15px' }}></span>
							{copiedShortcode ? __('Copied!', 'dragwyb-click-to-chat') : __('Copy Shortcode', 'dragwyb-click-to-chat')}
						</button>
					</div>

					<button
						type="button"
						onClick={handleSave}
						disabled={saving}
						className="button button-primary"
						style={{ display: 'inline-flex', alignItems: 'center', gap: '6px', height: '36px', padding: '0 18px', fontWeight: 700 }}
					>
						{saving ? (
							<>
								<span className="spinner is-active" style={{ float: 'none', margin: 0 }}></span>
								{__('Saving...', 'dragwyb-click-to-chat')}
							</>
						) : (
							<>
								<span className="dashicons dashicons-saved" style={{ fontSize: '16px' }}></span>
								{__('Save Changes', 'dragwyb-click-to-chat')}
							</>
						)}
					</button>
				</div>
			</div>

			{ /* Main Split Layout: Left Controls / Right Live Preview */}
			<div style={{ display: 'grid', gridTemplateColumns: 'minmax(420px, 1.15fr) minmax(460px, 1.35fr)', gap: '24px', alignItems: 'start' }}>

				{ /* Left Column: Settings Configuration Tabs */}
				<div style={{ display: 'flex', flexDirection: 'column', gap: '18px' }}>

					{ /* Nav Subtabs */}
					<div style={{ display: 'flex', background: '#e2e8f0', borderRadius: '10px', padding: '4px', gap: '4px' }}>
						<button
							type="button"
							onClick={() => setActiveTab('styling')}
							style={{
								flex: 1,
								padding: '8px 12px',
								border: 'none',
								borderRadius: '8px',
								fontWeight: 700,
								fontSize: '12.5px',
								cursor: 'pointer',
								background: activeTab === 'styling' ? '#ffffff' : 'transparent',
								color: activeTab === 'styling' ? '#4f46e5' : '#64748b',
								boxShadow: activeTab === 'styling' ? '0 1px 3px rgba(0,0,0,0.1)' : 'none',
								transition: 'all 0.15s',
							}}
						>
							<span className="dashicons dashicons-art" style={{ fontSize: '15px', marginRight: '4px', verticalAlign: 'middle' }}></span>
							{__('Theme & Styling', 'dragwyb-click-to-chat')}
						</button>
						<button
							type="button"
							onClick={() => setActiveTab('text')}
							style={{
								flex: 1,
								padding: '8px 12px',
								border: 'none',
								borderRadius: '8px',
								fontWeight: 700,
								fontSize: '12.5px',
								cursor: 'pointer',
								background: activeTab === 'text' ? '#ffffff' : 'transparent',
								color: activeTab === 'text' ? '#4f46e5' : '#64748b',
								boxShadow: activeTab === 'text' ? '0 1px 3px rgba(0,0,0,0.1)' : 'none',
								transition: 'all 0.15s',
							}}
						>
							<span className="dashicons dashicons-editor-textcolor" style={{ fontSize: '15px', marginRight: '4px', verticalAlign: 'middle' }}></span>
							{__('Titles & Texts', 'dragwyb-click-to-chat')}
						</button>
						<button
							type="button"
							onClick={() => setActiveTab('guest')}
							style={{
								flex: 1,
								padding: '8px 12px',
								border: 'none',
								borderRadius: '8px',
								fontWeight: 700,
								fontSize: '12.5px',
								cursor: 'pointer',
								background: activeTab === 'guest' ? '#ffffff' : 'transparent',
								color: activeTab === 'guest' ? '#4f46e5' : '#64748b',
								boxShadow: activeTab === 'guest' ? '0 1px 3px rgba(0,0,0,0.1)' : 'none',
								transition: 'all 0.15s',
							}}
						>
							<span className="dashicons dashicons-admin-users" style={{ fontSize: '15px', marginRight: '4px', verticalAlign: 'middle' }}></span>
							{__('Guest & Logged-Out', 'dragwyb-click-to-chat')}
						</button>
					</div>

					{ /* Tab 1: Styling & Themes */}
					{activeTab === 'styling' && (
						<div className="dctc-sc-panel-box">
							<div className="dctc-sc-panel-header">
								<div className="dctc-sc-panel-icon-wrap icon-indigo">
									<span className="dashicons dashicons-art"></span>
								</div>
								<div>
									<h3>{__('Theme Presets & Palette', 'dragwyb-click-to-chat')}</h3>
									<p className="dctc-sc-panel-sub">{__('Choose a curated color preset or customize individual styles.', 'dragwyb-click-to-chat')}</p>
								</div>
							</div>

							<div className="dctc-sc-settings-form-body">
								{ /* Presets Grid */}
								<div style={{ marginBottom: '20px' }}>
									<label style={{ fontWeight: 700, fontSize: '13px', color: '#1e293b', display: 'block', marginBottom: '8px' }}>
										{__('Preset Theme', 'dragwyb-click-to-chat')}
									</label>
									<div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(130px, 1fr))', gap: '8px' }}>
										{THEME_PRESETS.map((p) => {
											const isSelected = settings.preset === p.id;
											return (
												<button
													key={p.id}
													type="button"
													onClick={() => applyPreset(p.id)}
													style={{
														display: 'flex',
														flexDirection: 'column',
														alignItems: 'center',
														gap: '6px',
														padding: '10px 8px',
														borderRadius: '8px',
														border: isSelected ? '2px solid #4f46e5' : '1px solid #e2e8f0',
														background: isSelected ? '#eef2ff' : '#ffffff',
														cursor: 'pointer',
														transition: 'all 0.15s',
														textAlign: 'center',
													}}
												>
													<div style={{ display: 'flex', gap: '3px' }}>
														<span style={{ width: '14px', height: '14px', borderRadius: '50%', background: p.primary, boxShadow: '0 1px 2px rgba(0,0,0,0.2)' }}></span>
														<span style={{ width: '14px', height: '14px', borderRadius: '50%', background: p.headerBg, border: '1px solid #cbd5e1' }}></span>
													</div>
													<span style={{ fontSize: '11.5px', fontWeight: isSelected ? 700 : 500, color: isSelected ? '#4338ca' : '#334155' }}>
														{p.name.split(' ')[0]} {p.name.split(' ')[1] || ''}
													</span>
												</button>
											);
										})}
									</div>
								</div>

								{ /* Border & Radius Controls */}
								<h4 style={{ margin: '16px 0 12px', fontSize: '13px', fontWeight: 800, color: '#0f172a', textTransform: 'uppercase', letterSpacing: '0.5px' }}>
									{__('Borders & Corner Radius', 'dragwyb-click-to-chat')}
								</h4>

								<div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '14px' }}>
									<RangeField
										id="dctc-portal-radius"
										label={__('Border Radius (px)', 'dragwyb-click-to-chat')}
										value={settings.border_radius ?? 12}
										min={0}
										max={32}
										step={1}
										unit="px"
										onChange={(val) => updateSetting('border_radius', val)}
									/>
									<RangeField
										id="dctc-portal-border-width"
										label={__('Border Width (px)', 'dragwyb-click-to-chat')}
										value={settings.border_width ?? 1}
										min={0}
										max={6}
										step={1}
										unit="px"
										onChange={(val) => updateSetting('border_width', val)}
									/>
								</div>

								<div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '14px', marginTop: '10px' }}>
									<ColorField
										id="dctc-portal-border-color"
										label={__('Portal Border Color', 'dragwyb-click-to-chat')}
										value={settings.border_color || '#E5E7EB'}
										onChange={(val) => updateSetting('border_color', val)}
									/>
									<FormField id="dctc-portal-border-style" label={__('Border Style', 'dragwyb-click-to-chat')}>
										<select
											id="dctc-portal-border-style"
											value={settings.border_style || 'solid'}
											onChange={(e) => updateSetting('border_style', e.target.value)}
										>
											<option value="solid">{__('Solid', 'dragwyb-click-to-chat')}</option>
											<option value="dashed">{__('Dashed', 'dragwyb-click-to-chat')}</option>
											<option value="none">{__('None', 'dragwyb-click-to-chat')}</option>
										</select>
									</FormField>
								</div>

								{ /* Heading & Background Colors */}
								<h4 style={{ margin: '20px 0 12px', fontSize: '13px', fontWeight: 800, color: '#0f172a', textTransform: 'uppercase', letterSpacing: '0.5px' }}>
									{__('Header & Background Colors', 'dragwyb-click-to-chat')}
								</h4>

								<div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '14px' }}>
									<ColorField
										id="dctc-portal-header-bg"
										label={__('Header Background', 'dragwyb-click-to-chat')}
										value={settings.header_bg_color || '#F9FAFB'}
										onChange={(val) => updateSetting('header_bg_color', val)}
									/>
									<ColorField
										id="dctc-portal-header-title-color"
										label={__('Header Title Color', 'dragwyb-click-to-chat')}
										value={settings.header_title_color || '#111827'}
										onChange={(val) => updateSetting('header_title_color', val)}
									/>
									<ColorField
										id="dctc-portal-header-subtitle-color"
										label={__('Subtitle Color', 'dragwyb-click-to-chat')}
										value={settings.header_subtitle_color || '#6B7280'}
										onChange={(val) => updateSetting('header_subtitle_color', val)}
									/>
									<ColorField
										id="dctc-portal-container-bg"
										label={__('Container Background', 'dragwyb-click-to-chat')}
										value={settings.container_bg_color || '#FFFFFF'}
										onChange={(val) => updateSetting('container_bg_color', val)}
									/>
								</div>

								{ /* Button Colors */}
								<h4 style={{ margin: '20px 0 12px', fontSize: '13px', fontWeight: 800, color: '#0f172a', textTransform: 'uppercase', letterSpacing: '0.5px' }}>
									{__('Primary & Secondary Buttons', 'dragwyb-click-to-chat')}
								</h4>

								<div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '14px' }}>
									<ColorField
										id="dctc-portal-primary-color"
										label={__('Primary Button BG', 'dragwyb-click-to-chat')}
										value={settings.primary_color || '#4F46E5'}
										onChange={(val) => updateSetting('primary_color', val)}
									/>
									<ColorField
										id="dctc-portal-primary-hover"
										label={__('Primary Button Hover BG', 'dragwyb-click-to-chat')}
										value={settings.primary_hover_color || '#4338CA'}
										onChange={(val) => updateSetting('primary_hover_color', val)}
									/>
									<ColorField
										id="dctc-portal-primary-text"
										label={__('Primary Button Text', 'dragwyb-click-to-chat')}
										value={settings.primary_text_color || '#FFFFFF'}
										onChange={(val) => updateSetting('primary_text_color', val)}
									/>
									<ColorField
										id="dctc-portal-secondary-btn-bg"
										label={__('Secondary Button BG', 'dragwyb-click-to-chat')}
										value={settings.secondary_btn_bg || '#FFFFFF'}
										onChange={(val) => updateSetting('secondary_btn_bg', val)}
									/>
								</div>

								{ /* Card & Field Styling */}
								<h4 style={{ margin: '20px 0 12px', fontSize: '13px', fontWeight: 800, color: '#0f172a', textTransform: 'uppercase', letterSpacing: '0.5px' }}>
									{__('Ticket Cards & Form Fields', 'dragwyb-click-to-chat')}
								</h4>

								<div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '14px' }}>
									<ColorField
										id="dctc-portal-card-bg"
										label={__('Ticket Card BG', 'dragwyb-click-to-chat')}
										value={settings.card_bg_color || '#F9FAFB'}
										onChange={(val) => updateSetting('card_bg_color', val)}
									/>
									<ColorField
										id="dctc-portal-card-border"
										label={__('Ticket Card Border', 'dragwyb-click-to-chat')}
										value={settings.card_border_color || '#E5E7EB'}
										onChange={(val) => updateSetting('card_border_color', val)}
									/>
									<ColorField
										id="dctc-portal-input-bg"
										label={__('Input Fields BG', 'dragwyb-click-to-chat')}
										value={settings.input_bg_color || '#F8FAFC'}
										onChange={(val) => updateSetting('input_bg_color', val)}
									/>
									<ColorField
										id="dctc-portal-input-border"
										label={__('Input Fields Border', 'dragwyb-click-to-chat')}
										value={settings.input_border_color || '#CBD5E1'}
										onChange={(val) => updateSetting('input_border_color', val)}
									/>
								</div>
							</div>
						</div>
					)}

					{ /* Tab 2: Titles & Texts */}
					{activeTab === 'text' && (
						<div className="dctc-sc-panel-box">
							<div className="dctc-sc-panel-header">
								<div className="dctc-sc-panel-icon-wrap icon-purple">
									<span className="dashicons dashicons-editor-textcolor"></span>
								</div>
								<div>
									<h3>{__('Titles, Headings & Copy', 'dragwyb-click-to-chat')}</h3>
									<p className="dctc-sc-panel-sub">{__('Customize all customer-facing text, placeholders, dynamic taxonomies, and action buttons in the portal.', 'dragwyb-click-to-chat')}</p>
								</div>
							</div>

							<div className="dctc-sc-settings-form-body">
								{ /* Header section copy */}
								<FormField id="dctc-portal-title" label={__('Portal Main Heading / Title', 'dragwyb-click-to-chat')}>
									<input
										type="text"
										id="dctc-portal-title"
										value={settings.portal_title || ''}
										onChange={(e) => updateSetting('portal_title', e.target.value)}
										placeholder="Help & Support Center"
										className="regular-text"
									/>
								</FormField>

								<FormField id="dctc-portal-subtitle" label={__('Portal Subtitle / Description', 'dragwyb-click-to-chat')}>
									<input
										type="text"
										id="dctc-portal-subtitle"
										value={settings.portal_subtitle || ''}
										onChange={(e) => updateSetting('portal_subtitle', e.target.value)}
										placeholder="View your recent requests, check status updates..."
										className="regular-text"
									/>
								</FormField>

								<div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '14px' }}>
									<FormField id="dctc-btn-new-ticket" label={__('New Request Button Text', 'dragwyb-click-to-chat')}>
										<input
											type="text"
											id="dctc-btn-new-ticket"
											value={settings.btn_new_ticket_text || ''}
											onChange={(e) => updateSetting('btn_new_ticket_text', e.target.value)}
											placeholder="New Support Request"
										/>
									</FormField>

									<FormField id="dctc-btn-back-tickets" label={__('Back to Tickets Button Text', 'dragwyb-click-to-chat')}>
										<input
											type="text"
											id="dctc-btn-back-tickets"
											value={settings.btn_back_tickets_text || ''}
											onChange={(e) => updateSetting('btn_back_tickets_text', e.target.value)}
											placeholder="Back to My Tickets"
										/>
									</FormField>
								</div>

								{ /* Ticket list copy */}
								<h4 style={{ margin: '20px 0 12px', fontSize: '13px', fontWeight: 800, color: '#0f172a', textTransform: 'uppercase', letterSpacing: '0.5px' }}>
									{__('Search & List States', 'dragwyb-click-to-chat')}
								</h4>

								<FormField id="dctc-search-placeholder" label={__('Search Input Placeholder', 'dragwyb-click-to-chat')}>
									<input
										type="text"
										id="dctc-search-placeholder"
										value={settings.search_placeholder || ''}
										onChange={(e) => updateSetting('search_placeholder', e.target.value)}
										placeholder="Search your tickets by subject or number..."
									/>
								</FormField>

								<div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '14px' }}>
									<FormField id="dctc-empty-title" label={__('Empty State Title', 'dragwyb-click-to-chat')}>
										<input
											type="text"
											id="dctc-empty-title"
											value={settings.empty_tickets_title || ''}
											onChange={(e) => updateSetting('empty_tickets_title', e.target.value)}
											placeholder="No support requests found"
										/>
									</FormField>

									<FormField id="dctc-loading-text" label={__('Loading Text', 'dragwyb-click-to-chat')}>
										<input
											type="text"
											id="dctc-loading-text"
											value={settings.loading_text || ''}
											onChange={(e) => updateSetting('loading_text', e.target.value)}
											placeholder="Loading support tickets..."
										/>
									</FormField>
								</div>

								{ /* Create Modal copy & Dynamic Registered Taxonomies */}
								<h4 style={{ margin: '20px 0 12px', fontSize: '13px', fontWeight: 800, color: '#0f172a', textTransform: 'uppercase', letterSpacing: '0.5px' }}>
									{__('Create Ticket Form & Dynamic Taxonomies', 'dragwyb-click-to-chat')}
								</h4>

								<div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '14px' }}>
									<FormField id="dctc-modal-title" label={__('Modal Window Title', 'dragwyb-click-to-chat')}>
										<input
											type="text"
											id="dctc-modal-title"
											value={settings.modal_title || ''}
											onChange={(e) => updateSetting('modal_title', e.target.value)}
											placeholder="Create a New Support Request"
										/>
									</FormField>

									<FormField id="dctc-modal-subtitle" label={__('Modal Subtitle', 'dragwyb-click-to-chat')}>
										<input
											type="text"
											id="dctc-modal-subtitle"
											value={settings.modal_subtitle || ''}
											onChange={(e) => updateSetting('modal_subtitle', e.target.value)}
											placeholder="Submit your inquiry..."
										/>
									</FormField>
								</div>

								{ /* Primary Category Label (Full Width) */}
								<div style={{ background: '#f8fafc', border: '1px solid #e2e8f0', borderRadius: '10px', padding: '14px 16px', margin: '14px 0' }}>
									<div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '8px' }}>
										<label htmlFor="dctc-cat-label" style={{ fontWeight: 700, fontSize: '13px', color: '#0f172a', margin: 0 }}>
											{__('Primary Category Label (100% Full Width)', 'dragwyb-click-to-chat')}
										</label>
										<span style={{ background: '#EEF2FF', color: '#4338CA', padding: '2px 8px', borderRadius: '6px', fontSize: '11px', fontWeight: 600 }}>
											{categoriesList.length} {__('categories registered', 'dragwyb-click-to-chat')}
										</span>
									</div>
									<input
										type="text"
										id="dctc-cat-label"
										value={settings.category_label || ''}
										onChange={(e) => updateSetting('category_label', e.target.value)}
										placeholder="Category"
										className="regular-text"
									/>
								</div>

								{ /* Other Registered Taxonomies with items (50% Width) */}
								{taxonomiesWithItems.length > 0 && (
									<div style={{ margin: '14px 0' }}>
										<label style={{ fontWeight: 700, fontSize: '12.5px', color: '#334155', display: 'block', marginBottom: '8px' }}>
											{__('Additional Active Taxonomies (50% Width in Form):', 'dragwyb-click-to-chat')}
										</label>
										<div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '12px' }}>
											{taxonomiesWithItems.map((tax) => {
												const currentLabel = (settings.taxonomy_labels && settings.taxonomy_labels[tax.slug]) || tax.name;
												return (
													<div key={tax.slug} style={{ background: '#f8fafc', border: '1px solid #e2e8f0', borderRadius: '8px', padding: '10px 12px' }}>
														<div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '4px' }}>
															<label htmlFor={`dctc-tax-label-${tax.slug}`} style={{ fontSize: '11.5px', fontWeight: 700, color: '#1e293b', margin: 0 }}>
																{tax.name} {__('Label', 'dragwyb-click-to-chat')}
															</label>
															<span style={{ background: '#ECFDF5', color: '#047857', padding: '1px 6px', borderRadius: '4px', fontSize: '10px', fontWeight: 600 }}>
																{tax.itemsCount} {__('items', 'dragwyb-click-to-chat')}
															</span>
														</div>
														<input
															type="text"
															id={`dctc-tax-label-${tax.slug}`}
															value={currentLabel}
															onChange={(e) => updateTaxonomyLabel(tax.slug, e.target.value)}
															placeholder={tax.name}
															style={{ width: '100%', fontSize: '12.5px', padding: '6px 10px' }}
														/>
													</div>
												);
											})}
										</div>
									</div>
								)}

								<div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '14px' }}>
									<FormField id="dctc-subject-label" label={__('Subject Field Label', 'dragwyb-click-to-chat')}>
										<input
											type="text"
											id="dctc-subject-label"
											value={settings.subject_label || ''}
											onChange={(e) => updateSetting('subject_label', e.target.value)}
											placeholder="Subject"
										/>
									</FormField>

									<FormField id="dctc-message-label" label={__('Message Field Label', 'dragwyb-click-to-chat')}>
										<input
											type="text"
											id="dctc-message-label"
											value={settings.message_label || ''}
											onChange={(e) => updateSetting('message_label', e.target.value)}
											placeholder="Message"
										/>
									</FormField>
								</div>

								<div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '14px' }}>
									<FormField id="dctc-btn-submit-ticket" label={__('Submit Ticket Button Text', 'dragwyb-click-to-chat')}>
										<input
											type="text"
											id="dctc-btn-submit-ticket"
											value={settings.btn_submit_ticket_text || ''}
											onChange={(e) => updateSetting('btn_submit_ticket_text', e.target.value)}
											placeholder="Submit Support Request"
										/>
									</FormField>

									<FormField id="dctc-btn-cancel" label={__('Cancel Button Text', 'dragwyb-click-to-chat')}>
										<input
											type="text"
											id="dctc-btn-cancel"
											value={settings.btn_cancel_text || ''}
											onChange={(e) => updateSetting('btn_cancel_text', e.target.value)}
											placeholder="Cancel"
										/>
									</FormField>
								</div>

								{ /* Single Conversation copy */}
								<h4 style={{ margin: '20px 0 12px', fontSize: '13px', fontWeight: 800, color: '#0f172a', textTransform: 'uppercase', letterSpacing: '0.5px' }}>
									{__('Ticket Conversation & Reply', 'dragwyb-click-to-chat')}
								</h4>

								<div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '14px' }}>
									<FormField id="dctc-btn-send-reply" label={__('Send Reply Button Text', 'dragwyb-click-to-chat')}>
										<input
											type="text"
											id="dctc-btn-send-reply"
											value={settings.btn_send_reply_text || ''}
											onChange={(e) => updateSetting('btn_send_reply_text', e.target.value)}
											placeholder="Send Reply"
										/>
									</FormField>

									<FormField id="dctc-btn-close-ticket" label={__('Close Ticket Button Text', 'dragwyb-click-to-chat')}>
										<input
											type="text"
											id="dctc-btn-close-ticket"
											value={settings.btn_close_ticket_text || ''}
											onChange={(e) => updateSetting('btn_close_ticket_text', e.target.value)}
											placeholder="Close Ticket"
										/>
									</FormField>
								</div>
							</div>
						</div>
					)}

					{ /* Tab 3: Guest & Logged-Out Settings */}
					{activeTab === 'guest' && (
						<div className="dctc-sc-panel-box">
							<div className="dctc-sc-panel-header">
								<div className="dctc-sc-panel-icon-wrap icon-amber">
									<span className="dashicons dashicons-admin-users"></span>
								</div>
								<div>
									<h3>{__('Logged-Out Visitors & Guest Submissions', 'dragwyb-click-to-chat')}</h3>
									<p className="dctc-sc-panel-sub">{__('Configure how non-logged-in visitors interact with the support portal and submit tickets.', 'dragwyb-click-to-chat')}</p>
								</div>
							</div>

							<div className="dctc-sc-settings-form-body">
								{ /* Toggle: Allow Logged-out Guests to Create Tickets */}
								<SettingCard
									id="dctc-portal-enable-guest"
									title={__('Allow Logged-Out Visitors to Create Tickets', 'dragwyb-click-to-chat')}
									desc={__('Display direct "Submit Ticket as Guest" buttons and name/email fields so visitors do not have to log in first.', 'dragwyb-click-to-chat')}
									checked={!!settings.enable_guest_ticket_form}
									onChange={(val) => updateSetting('enable_guest_ticket_form', val)}
								/>

								{ /* Conditional Guest Form Options based on enable_guest_ticket_form */}
								{settings.enable_guest_ticket_form ? (
									<div style={{ background: '#f8fafc', border: '1px solid #e2e8f0', borderRadius: '10px', padding: '16px', marginTop: '14px' }}>
										<h4 style={{ margin: '0 0 12px', fontSize: '13px', fontWeight: 800, color: '#0f172a', textTransform: 'uppercase', letterSpacing: '0.5px' }}>
											{__('Guest Ticket Form & Button Options', 'dragwyb-click-to-chat')}
										</h4>

										<FormField id="dctc-portal-guest-btn-text" label={__('Guest Create Ticket Button Text', 'dragwyb-click-to-chat')}>
											<input
												type="text"
												id="dctc-portal-guest-btn-text"
												value={settings.btn_guest_create_ticket_text || ''}
												onChange={(e) => updateSetting('btn_guest_create_ticket_text', e.target.value)}
												placeholder="Submit Ticket as Guest"
											/>
										</FormField>

										<div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '14px', marginTop: '10px' }}>
											<FormField id="dctc-portal-guest-name-label" label={__('Guest Name Field Label', 'dragwyb-click-to-chat')}>
												<input
													type="text"
													id="dctc-portal-guest-name-label"
													value={settings.guest_name_label || ''}
													onChange={(e) => updateSetting('guest_name_label', e.target.value)}
													placeholder="Your Name"
												/>
											</FormField>

											<FormField id="dctc-portal-guest-email-label" label={__('Guest Email Field Label', 'dragwyb-click-to-chat')}>
												<input
													type="text"
													id="dctc-portal-guest-email-label"
													value={settings.guest_email_label || ''}
													onChange={(e) => updateSetting('guest_email_label', e.target.value)}
													placeholder="Your Email Address"
												/>
											</FormField>
										</div>

										<div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '14px', marginTop: '10px' }}>
											<FormField id="dctc-portal-guest-name-ph" label={__('Guest Name Placeholder', 'dragwyb-click-to-chat')}>
												<input
													type="text"
													id="dctc-portal-guest-name-ph"
													value={settings.guest_name_placeholder || ''}
													onChange={(e) => updateSetting('guest_name_placeholder', e.target.value)}
													placeholder="John Doe"
												/>
											</FormField>

											<FormField id="dctc-portal-guest-email-ph" label={__('Guest Email Placeholder', 'dragwyb-click-to-chat')}>
												<input
													type="text"
													id="dctc-portal-guest-email-ph"
													value={settings.guest_email_placeholder || ''}
													onChange={(e) => updateSetting('guest_email_placeholder', e.target.value)}
													placeholder="you@example.com"
												/>
											</FormField>
										</div>
									</div>
								) : (
									<div style={{ background: '#FFFBEB', border: '1px solid #FDE68A', borderRadius: '8px', padding: '12px 14px', marginTop: '12px', color: '#92400E', fontSize: '12.5px', display: 'flex', alignItems: 'center', gap: '8px' }}>
										<span className="dashicons dashicons-info" style={{ color: '#D97706' }}></span>
										<span>{__('Guest ticket submissions are disabled. Non-logged-in visitors will be prompted to log in or register before submitting tickets.', 'dragwyb-click-to-chat')}</span>
									</div>
								)}

								<h4 style={{ margin: '20px 0 12px', fontSize: '13px', fontWeight: 800, color: '#0f172a', textTransform: 'uppercase', letterSpacing: '0.5px' }}>
									{__('Auth Box & Account Redirects', 'dragwyb-click-to-chat')}
								</h4>

								<FormField id="dctc-portal-guest-auth-title" label={__('Auth Box Title', 'dragwyb-click-to-chat')}>
									<input
										type="text"
										id="dctc-portal-guest-auth-title"
										value={settings.guest_auth_box_title || ''}
										onChange={(e) => updateSetting('guest_auth_box_title', e.target.value)}
										placeholder="Customer Support Portal"
									/>
								</FormField>

								<FormField id="dctc-portal-guest-auth-desc" label={__('Auth Box Description', 'dragwyb-click-to-chat')}>
									<input
										type="text"
										id="dctc-portal-guest-auth-desc"
										value={settings.guest_auth_box_desc || ''}
										onChange={(e) => updateSetting('guest_auth_box_desc', e.target.value)}
										placeholder="Please log in to your account or submit a request directly..."
									/>
								</FormField>

								<div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '14px' }}>
									<SettingCard
										id="dctc-portal-show-login-btn"
										title={__('Show "Log In" Button', 'dragwyb-click-to-chat')}
										desc={__('Display WordPress login redirect button.', 'dragwyb-click-to-chat')}
										checked={!!settings.show_login_button}
										onChange={(val) => updateSetting('show_login_button', val)}
									/>
									<SettingCard
										id="dctc-portal-show-reg-btn"
										title={__('Show "Register" Button', 'dragwyb-click-to-chat')}
										desc={__('Display user registration link if enabled in WordPress.', 'dragwyb-click-to-chat')}
										checked={!!settings.show_register_button}
										onChange={(val) => updateSetting('show_register_button', val)}
									/>
								</div>
							</div>
						</div>
					)}
				</div>

				{ /* Right Column: Real-Time Live Interactive Preview */}
				<div style={{ position: 'sticky', top: '40px' }}>
					<div className="dctc-sc-panel-box" style={{ overflow: 'hidden' }}>
						<div className="dctc-sc-panel-header" style={{ background: '#f8fafc', borderBottom: '1px solid #e2e8f0', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
							<div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
								<span className="dashicons dashicons-visibility" style={{ color: '#4f46e5', fontSize: '18px' }}></span>
								<h3 style={{ margin: 0, fontSize: '14px', fontWeight: 800, color: '#0f172a' }}>
									{__('Live Interactive Preview', 'dragwyb-click-to-chat')}
								</h3>
							</div>

							{ /* Preview Mode Selectors */}
							<div style={{ display: 'flex', gap: '6px' }}>
								<select
									value={previewAuthMode}
									onChange={(e) => setPreviewAuthMode(e.target.value)}
									style={{ fontSize: '12px', height: '28px', padding: '0 8px', borderRadius: '6px', fontWeight: 600 }}
								>
									<option value="logged_in">{__('Preview Logged-In User', 'dragwyb-click-to-chat')}</option>
									<option value="logged_out">{__('Preview Logged-Out Visitor', 'dragwyb-click-to-chat')}</option>
								</select>

								<select
									value={previewViewMode}
									onChange={(e) => setPreviewViewMode(e.target.value)}
									style={{ fontSize: '12px', height: '28px', padding: '0 8px', borderRadius: '6px', fontWeight: 600 }}
								>
									<option value="list">{__('Ticket List View', 'dragwyb-click-to-chat')}</option>
									<option value="modal">{__('Create Modal Dialog', 'dragwyb-click-to-chat')}</option>
									<option value="detail">{__('Ticket Detail View', 'dragwyb-click-to-chat')}</option>
								</select>
							</div>
						</div>

						{ /* Rendered Live Portal Card */}
						<div style={{ padding: '20px', background: '#f1f5f9' }}>
							<div
								style={{
									background: previewContainerBg,
									borderWidth: previewBorderWidth,
									borderStyle: previewBorderStyle,
									borderColor: previewBorderColor,
									borderRadius: previewRadius,
									boxShadow: '0 4px 16px rgba(0,0,0,0.06)',
									overflow: 'hidden',
									transition: 'all 0.2s',
								}}
							>
								{ /* Header */}
								<div
									style={{
										background: previewHeaderBg,
										borderBottom: `1px solid ${previewBorderColor}`,
										padding: '16px 20px',
										display: 'flex',
										justifyContent: 'space-between',
										alignItems: 'center',
										gap: '12px',
										flexWrap: 'wrap',
									}}
								>
									<div>
										<h4 style={{ margin: '0 0 3px', fontSize: '17px', fontWeight: 800, color: previewHeaderTitle }}>
											{settings.portal_title || 'Help & Support Center'}
										</h4>
										<p style={{ margin: 0, fontSize: '12px', color: previewHeaderSub }}>
											{settings.portal_subtitle || 'View your recent requests or start a new conversation.'}
										</p>
									</div>

									<div style={{ display: 'flex', gap: '8px', alignItems: 'center' }}>
										{previewViewMode !== 'list' && (
											<button
												type="button"
												onClick={() => setPreviewViewMode('list')}
												style={{
													background: previewSecondaryBg,
													color: previewSecondaryText,
													border: `1px solid ${previewSecondaryBorder}`,
													borderRadius: previewBtnRadius,
													padding: '7px 12px',
													fontSize: '12px',
													fontWeight: 600,
													cursor: 'pointer',
													display: 'inline-flex',
													alignItems: 'center',
													gap: '5px',
												}}
											>
												<span className="dashicons dashicons-arrow-left-alt" style={{ fontSize: '14px', width: '14px', height: '14px' }}></span>
												{settings.btn_back_tickets_text || 'Back to My Tickets'}
											</button>
										)}

										{previewViewMode === 'list' && (
											<>
												{previewAuthMode === 'logged_in' ? (
													<button
														type="button"
														onClick={() => setPreviewViewMode('modal')}
														style={{
															background: previewPrimary,
															color: previewPrimaryText,
															border: `1px solid ${previewPrimary}`,
															borderRadius: previewBtnRadius,
															padding: '7px 14px',
															fontSize: '12px',
															fontWeight: 700,
															cursor: 'pointer',
															display: 'inline-flex',
															alignItems: 'center',
															gap: '5px',
														}}
													>
														<span className="dashicons dashicons-plus-alt2" style={{ fontSize: '14px', width: '14px', height: '14px' }}></span>
														{settings.btn_new_ticket_text || 'New Support Request'}
													</button>
												) : settings.enable_guest_ticket_form ? (
													<button
														type="button"
														onClick={() => setPreviewViewMode('modal')}
														style={{
															background: previewPrimary,
															color: previewPrimaryText,
															border: `1px solid ${previewPrimary}`,
															borderRadius: previewBtnRadius,
															padding: '7px 14px',
															fontSize: '12px',
															fontWeight: 700,
															cursor: 'pointer',
															display: 'inline-flex',
															alignItems: 'center',
															gap: '5px',
														}}
													>
														<span className="dashicons dashicons-plus-alt2" style={{ fontSize: '14px', width: '14px', height: '14px' }}></span>
														{settings.btn_guest_create_ticket_text || 'Submit Ticket as Guest'}
													</button>
												) : null}
											</>
										)}
									</div>
								</div>

								{ /* Body Views */}
								<div style={{ padding: '18px' }}>
									{ /* View: List for Logged-In */}
									{previewViewMode === 'list' && previewAuthMode === 'logged_in' && (
										<div>
											<div style={{ marginBottom: '14px' }}>
												<input
													type="text"
													disabled
													placeholder={settings.search_placeholder || 'Search your tickets by subject or number...'}
													style={{
														width: '100%',
														background: previewInputBg,
														border: `1px solid ${previewInputBorder}`,
														borderRadius: previewBtnRadius,
														padding: '8px 12px',
														fontSize: '12.5px',
													}}
												/>
											</div>

											{ /* Sample Mock Ticket 1 (Clickable to switch to detail) */}
											<div
												onClick={() => setPreviewViewMode('detail')}
												style={{
													background: previewCardBg,
													border: `1px solid ${previewCardBorder}`,
													borderRadius: `${Math.max(4, (settings.border_radius ?? 12) - 4)}px`,
													padding: '12px 14px',
													marginBottom: '10px',
													display: 'flex',
													justifyContent: 'space-between',
													alignItems: 'center',
													cursor: 'pointer',
													transition: 'all 0.15s',
												}}
												title={__('Click to preview conversation detail', 'dragwyb-click-to-chat')}
											>
												<div style={{ flex: 1, minWidth: 0, marginRight: '12px' }}>
													<div style={{ display: 'flex', alignItems: 'center', gap: '6px', marginBottom: '6px', flexWrap: 'wrap' }}>
														<span style={{ color: previewPrimary, fontWeight: 800, fontSize: '11.5px' }}>#10017</span>
														<span style={{ background: '#ECFDF5', color: '#047857', padding: '2px 7px', borderRadius: '4px', fontSize: '10.5px', fontWeight: 700 }}>RESOLVED</span>
														<span style={{ background: previewBadgeCatBg, color: previewBadgeCatText, padding: '2px 7px', borderRadius: '4px', fontSize: '10.5px', fontWeight: 600 }}>Product Support</span>
														<span style={{ background: previewBadgeAgentBg, color: previewBadgeAgentText, padding: '2px 7px', borderRadius: '4px', fontSize: '10.5px', fontWeight: 600 }}>admin</span>
														<span style={{ background: previewBadgeChatsBg, color: previewBadgeChatsText, padding: '2px 7px', borderRadius: '4px', fontSize: '10.5px', fontWeight: 700 }}>53 chats</span>
													</div>
													<strong style={{ fontSize: '13.5px', color: previewCardTitle, display: 'block', marginBottom: '2px' }}>[Lead] Nuvyrex</strong>
													<div style={{ display: 'flex', gap: '4px', marginTop: '4px' }}>
														<span style={{ background: previewBadgeTagBg, color: previewBadgeTagText, padding: '1px 6px', borderRadius: '4px', fontSize: '10px', fontWeight: 600 }}>WooCommerce</span>
													</div>
												</div>
												<span style={{ fontSize: '11px', color: previewCardDate, whiteSpace: 'nowrap' }}>2026-10-08</span>
											</div>

											{ /* Sample Mock Ticket 2 (Clickable to switch to detail) */}
											<div
												onClick={() => setPreviewViewMode('detail')}
												style={{
													background: previewCardBg,
													border: `1px solid ${previewCardBorder}`,
													borderRadius: `${Math.max(4, (settings.border_radius ?? 12) - 4)}px`,
													padding: '12px 14px',
													display: 'flex',
													justifyContent: 'space-between',
													alignItems: 'center',
													cursor: 'pointer',
													transition: 'all 0.15s',
												}}
												title={__('Click to preview conversation detail', 'dragwyb-click-to-chat')}
											>
												<div style={{ flex: 1, minWidth: 0, marginRight: '12px' }}>
													<div style={{ display: 'flex', alignItems: 'center', gap: '6px', marginBottom: '6px', flexWrap: 'wrap' }}>
														<span style={{ color: previewPrimary, fontWeight: 800, fontSize: '11.5px' }}>#10016</span>
														<span style={{ background: '#EFF6FF', color: '#1D4ED8', padding: '2px 7px', borderRadius: '4px', fontSize: '10.5px', fontWeight: 700 }}>OPEN</span>
														<span style={{ background: previewBadgeCatBg, color: previewBadgeCatText, padding: '2px 7px', borderRadius: '4px', fontSize: '10.5px', fontWeight: 600 }}>WooCommerce & Orders</span>
														<span style={{ background: previewBadgeAgentBg, color: previewBadgeAgentText, padding: '2px 7px', borderRadius: '4px', fontSize: '10.5px', fontWeight: 600 }}>admin</span>
														<span style={{ background: previewBadgeChatsBg, color: previewBadgeChatsText, padding: '2px 7px', borderRadius: '4px', fontSize: '10.5px', fontWeight: 700 }}>14 chats</span>
													</div>
													<strong style={{ fontSize: '13.5px', color: previewCardTitle, display: 'block' }}>Issue with monthly invoice renewal</strong>
												</div>
												<span style={{ fontSize: '11px', color: previewCardDate, whiteSpace: 'nowrap' }}>2026-10-08</span>
											</div>
										</div>
									)}

									{ /* View: Auth Prompt for Logged-Out Visitor */}
									{previewViewMode === 'list' && previewAuthMode === 'logged_out' && (
										<div style={{ textAlign: 'center', padding: '24px 16px' }}>
											<div style={{ width: '48px', height: '48px', borderRadius: '50%', background: 'rgba(79, 70, 229, 0.1)', color: previewPrimary, display: 'inline-flex', alignItems: 'center', justifyContent: 'center', marginBottom: '12px' }}>
												<span className="dashicons dashicons-lock" style={{ fontSize: '24px', width: '24px', height: '24px' }}></span>
											</div>
											<h4 style={{ margin: '0 0 6px', fontSize: '16px', fontWeight: 800, color: previewHeaderTitle }}>
												{settings.guest_auth_box_title || 'Customer Support Portal'}
											</h4>
											<p style={{ margin: '0 0 16px', fontSize: '12.5px', color: previewHeaderSub, maxWidth: '340px', marginLeft: 'auto', marginRight: 'auto' }}>
												{settings.guest_auth_box_desc || 'Please log in to your account or submit a support request directly below as a guest.'}
											</p>
											<div style={{ display: 'flex', gap: '8px', justifyContent: 'center', flexWrap: 'wrap' }}>
												{settings.enable_guest_ticket_form && (
													<button
														type="button"
														onClick={() => setPreviewViewMode('modal')}
														style={{
															background: previewPrimary,
															color: previewPrimaryText,
															border: `1px solid ${previewPrimary}`,
															borderRadius: previewBtnRadius,
															padding: '8px 16px',
															fontSize: '12.5px',
															fontWeight: 700,
															cursor: 'pointer',
															display: 'inline-flex',
															alignItems: 'center',
															gap: '5px',
														}}
													>
														<span className="dashicons dashicons-edit" style={{ fontSize: '14px', width: '14px', height: '14px' }}></span>
														{settings.btn_guest_create_ticket_text || 'Submit Ticket as Guest'}
													</button>
												)}
												{settings.show_login_button && (
													<button
														type="button"
														style={{
															background: previewSecondaryBg,
															color: previewSecondaryText,
															border: `1px solid ${previewSecondaryBorder}`,
															borderRadius: previewBtnRadius,
															padding: '8px 16px',
															fontSize: '12.5px',
															fontWeight: 700,
															cursor: 'pointer',
														}}
													>
														{settings.btn_login_text || 'Log In to Submit Ticket'}
													</button>
												)}
											</div>
										</div>
									)}

									{ /* View: Create Ticket Modal with Primary Category (100% full width) and Other Taxonomies (50% width) */}
									{previewViewMode === 'modal' && (
										<div
											style={{
												background: previewModalBg,
												border: `1px solid ${previewModalBorder}`,
												borderRadius: previewRadius,
												boxShadow: '0 10px 25px rgba(0,0,0,0.2)',
												overflow: 'hidden',
											}}
										>
											<div style={{ background: previewHeaderBg, borderBottom: `1px solid ${previewBorderColor}`, padding: '14px 16px', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
												<div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
													<div style={{ width: '28px', height: '28px', borderRadius: '6px', background: previewPrimary, color: '#fff', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
														<span className="dashicons dashicons-format-chat" style={{ fontSize: '15px', width: '15px', height: '15px' }}></span>
													</div>
													<div>
														<h5 style={{ margin: 0, fontSize: '14px', fontWeight: 800, color: previewHeaderTitle }}>
															{settings.modal_title || 'Create a New Support Request'}
														</h5>
													</div>
												</div>
												<button
													type="button"
													onClick={() => setPreviewViewMode('list')}
													style={{ background: 'transparent', border: 'none', cursor: 'pointer', padding: 0 }}
													title={__('Close', 'dragwyb-click-to-chat')}
												>
													<span className="dashicons dashicons-no-alt" style={{ color: previewHeaderSub }}></span>
												</button>
											</div>

											<div style={{ padding: '16px', display: 'flex', flexDirection: 'column', gap: '12px' }}>
												{ /* Guest Name & Email Preview when in Logged-Out Preview Mode AND enable_guest_ticket_form is ON */}
												{previewAuthMode === 'logged_out' && settings.enable_guest_ticket_form && (
													<div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '10px' }}>
														<div>
															<label style={{ fontSize: '11px', fontWeight: 700, color: previewFormLabel }}>
																{settings.guest_name_label || 'Your Name'} *
															</label>
															<input
																type="text"
																disabled
																placeholder={settings.guest_name_placeholder || 'John Doe'}
																style={{ width: '100%', marginTop: '3px', background: previewInputBg, color: previewInputText, border: `1px solid ${previewInputBorder}`, borderRadius: previewBtnRadius, padding: '6px 10px', fontSize: '12px' }}
															/>
														</div>
														<div>
															<label style={{ fontSize: '11px', fontWeight: 700, color: previewFormLabel }}>
																{settings.guest_email_label || 'Your Email'} *
															</label>
															<input
																type="email"
																disabled
																placeholder={settings.guest_email_placeholder || 'you@example.com'}
																style={{ width: '100%', marginTop: '3px', background: previewInputBg, color: previewInputText, border: `1px solid ${previewInputBorder}`, borderRadius: previewBtnRadius, padding: '6px 10px', fontSize: '12px' }}
															/>
														</div>
													</div>
												)}

												{ /* Primary Category Field (100% Full Width) */}
												<div style={{ width: '100%' }}>
													<label style={{ fontSize: '11px', fontWeight: 700, color: previewFormLabel, display: 'block', marginBottom: '3px' }}>
														{settings.category_label || 'Category'} *
													</label>
													<select disabled style={{ width: '100%', background: previewInputBg, color: previewInputText, border: `1px solid ${previewInputBorder}`, borderRadius: previewBtnRadius, padding: '6px 10px', fontSize: '12px' }}>
														<option>General Inquiry</option>
													</select>
												</div>

												{ /* Other Registered Taxonomies having items (50% Width / 2-Column Grid) */}
												{taxonomiesWithItems.length > 0 && (
													<div
														style={{
															display: 'grid',
															gridTemplateColumns: taxonomiesWithItems.length === 1 ? '1fr' : '1fr 1fr',
															gap: '10px',
														}}
													>
														{taxonomiesWithItems.map((tax) => {
															const taxLabel = (settings.taxonomy_labels && settings.taxonomy_labels[tax.slug]) || tax.name;
															return (
																<div key={tax.slug}>
																	<label style={{ fontSize: '11px', fontWeight: 700, color: previewFormLabel, display: 'block', marginBottom: '3px' }}>
																		{taxLabel} (Optional)
																	</label>
																	<select
																		disabled
																		style={{
																			width: '100%',
																			background: previewInputBg,
																			color: previewInputText,
																			border: `1px solid ${previewInputBorder}`,
																			borderRadius: previewBtnRadius,
																			padding: '6px 10px',
																			fontSize: '12px',
																		}}
																	>
																		<option>-- Select {taxLabel} (Optional) --</option>
																	</select>
																</div>
															);
														})}
													</div>
												)}

												<div>
													<label style={{ fontSize: '11px', fontWeight: 700, color: previewFormLabel }}>
														{settings.subject_label || 'Subject'} *
													</label>
													<input
														type="text"
														disabled
														placeholder={settings.subject_placeholder || 'Enter a support issue title...'}
														style={{ width: '100%', marginTop: '3px', background: previewInputBg, color: previewInputText, border: `1px solid ${previewInputBorder}`, borderRadius: previewBtnRadius, padding: '6px 10px', fontSize: '12px' }}
													/>
												</div>

												<div>
													<label style={{ fontSize: '11px', fontWeight: 700, color: previewFormLabel }}>
														{settings.message_label || 'Message'} *
													</label>
													<div style={{ background: previewInputBg, border: `1px solid ${previewInputBorder}`, borderRadius: previewBtnRadius, padding: '16px', color: previewInputPlaceholder, fontSize: '12px' }}>
														Rich Text WYSIWYG Editor...
													</div>
												</div>
											</div>

											<div style={{ background: previewHeaderBg, borderTop: `1px solid ${previewBorderColor}`, padding: '12px 16px', display: 'flex', justifyContent: 'flex-end', gap: '8px' }}>
												<button
													type="button"
													onClick={() => setPreviewViewMode('list')}
													style={{ background: previewSecondaryBg, color: previewSecondaryText, border: `1px solid ${previewSecondaryBorder}`, borderRadius: previewBtnRadius, padding: '6px 12px', fontSize: '12px', fontWeight: 600, cursor: 'pointer' }}
												>
													{settings.btn_cancel_text || 'Cancel'}
												</button>
												<button
													type="button"
													onClick={() => setPreviewViewMode('detail')}
													style={{ background: previewPrimary, color: previewPrimaryText, border: `1px solid ${previewPrimary}`, borderRadius: previewBtnRadius, padding: '6px 14px', fontSize: '12px', fontWeight: 700, cursor: 'pointer' }}
												>
													{settings.btn_submit_ticket_text || 'Submit Support Request'}
												</button>
											</div>
										</div>
									)}

									{ /* View: Ticket Detail & Reply */}
									{previewViewMode === 'detail' && (
										<div>
											<div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', paddingBottom: '12px', borderBottom: `1px solid ${previewBorderColor}`, marginBottom: '14px' }}>
												<div>
													<div style={{ display: 'flex', gap: '6px', alignItems: 'center', marginBottom: '4px', flexWrap: 'wrap' }}>
														<span style={{ color: previewPrimary, fontWeight: 800, fontSize: '13px' }}>#10017</span>
														<span style={{ background: '#ECFDF5', color: '#047857', padding: '2px 7px', borderRadius: '4px', fontSize: '10.5px', fontWeight: 700 }}>RESOLVED</span>
														<span style={{ background: previewBadgeCatBg, color: previewBadgeCatText, padding: '2px 7px', borderRadius: '4px', fontSize: '10.5px', fontWeight: 600 }}>Product Support</span>
														<span style={{ background: previewBadgeAgentBg, color: previewBadgeAgentText, padding: '2px 7px', borderRadius: '4px', fontSize: '10.5px', fontWeight: 600 }}>admin</span>
													</div>
													<strong style={{ fontSize: '15px', color: previewCardTitle }}>[Lead] Nuvyrex</strong>
												</div>
												<button
													type="button"
													onClick={() => setPreviewViewMode('list')}
													style={{ background: previewSecondaryBg, color: previewSecondaryText, border: `1px solid ${previewSecondaryBorder}`, borderRadius: previewBtnRadius, padding: '5px 10px', fontSize: '11.5px', fontWeight: 600, cursor: 'pointer' }}
												>
													{settings.btn_close_ticket_text || 'Close Ticket'}
												</button>
											</div>

											{ /* Sample Message Thread */}
											<div style={{ display: 'flex', flexDirection: 'column', gap: '10px', marginBottom: '14px' }}>
												<div style={{ alignSelf: 'flex-end', background: previewCustomerMsgBg, border: `1px solid ${previewCustomerMsgBorder}`, borderRadius: '8px', padding: '10px 12px', maxWidth: '85%' }}>
													<div style={{ fontSize: '11px', fontWeight: 700, color: previewCustomerMsgSender, marginBottom: '3px', display: 'flex', justifyContent: 'space-between', gap: '10px' }}>
														<span>You</span>
														<span style={{ fontSize: '10px', opacity: 0.75 }}>2026-10-08 19:11:44</span>
													</div>
													<p style={{ margin: 0, fontSize: '12.5px', color: previewCustomerMsgText, lineHeight: '1.5' }}>can you help me</p>
												</div>
												<div style={{ alignSelf: 'flex-start', background: previewAgentMsgBg, border: `1px solid ${previewAgentMsgBorder}`, borderRadius: '8px', padding: '10px 12px', maxWidth: '85%' }}>
													<div style={{ fontSize: '11px', fontWeight: 700, color: previewAgentMsgSender, marginBottom: '3px', display: 'flex', justifyContent: 'space-between', gap: '10px' }}>
														<span>AI Assistant</span>
														<span style={{ fontSize: '10px', opacity: 0.75 }}>2026-10-08 19:12:14</span>
													</div>
													<p style={{ margin: 0, fontSize: '12.5px', color: previewAgentMsgText, lineHeight: '1.5' }}>Please fill out the form below so our team will connect with you.</p>
												</div>
												<div style={{ alignSelf: 'flex-end', background: previewCustomerMsgBg, border: `1px solid ${previewCustomerMsgBorder}`, borderRadius: '8px', padding: '10px 12px', maxWidth: '85%' }}>
													<div style={{ fontSize: '11px', fontWeight: 700, color: previewCustomerMsgSender, marginBottom: '3px', display: 'flex', justifyContent: 'space-between', gap: '10px' }}>
														<span>You</span>
														<span style={{ fontSize: '10px', opacity: 0.75 }}>2026-10-08 19:14:51</span>
													</div>
													<p style={{ margin: 0, fontSize: '12.5px', color: previewCustomerMsgText, lineHeight: '1.5' }}>Hii I want to buy your product</p>
												</div>
											</div>

											{ /* Reply Bar */}
											<div style={{ display: 'flex', justifyContent: 'flex-end' }}>
												<button
													type="button"
													style={{ background: previewPrimary, color: previewPrimaryText, border: `1px solid ${previewPrimary}`, borderRadius: previewBtnRadius, padding: '6px 14px', fontSize: '12px', fontWeight: 700, display: 'inline-flex', alignItems: 'center', gap: '4px', cursor: 'pointer' }}
												>
													<span className="dashicons dashicons-send" style={{ fontSize: '13px', width: '13px', height: '13px' }}></span>
													{settings.btn_send_reply_text || 'Send Reply'}
												</button>
											</div>
										</div>
									)}
								</div>
							</div>
						</div>
					</div>
				</div>

			</div>
		</div>
	);
}
