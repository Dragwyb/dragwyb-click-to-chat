/**
 * AI Assistant admin shell: sidebar nav, hash routing, toast, and dedicated onboarding page gate.
 */
import { useState, useEffect, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import Toast from './components/Toast';
import GlobalHeader from '../../common/components/GlobalHeader';
import OnboardingWizard from './onboarding/OnboardingWizard';
import ChatbotSettings from './sections/ChatbotSettings';
import AiEngineSettings from './sections/AiEngineSettings';
import KnowledgeBase from './sections/KnowledgeBase';
import LeadsManagement from './sections/LeadsManagement';
import ChatSessions from './sections/ChatSessions';
import AdminCopilot from './sections/AdminCopilot';
import ChatPreview from './sections/ChatPreview';
import ErrorLogs from './sections/ErrorLogs';

const TABS = [
	{
		id: 'chatbot-settings',
		label: __('Chatbot Settings', 'dragwyb-click-to-chat'),
		icon: 'dashicons-admin-settings',
		desc: __(
			'Configure bot identity, site-wide visibility, triggers, file uploads, avatars & styling.',
			'dragwyb-click-to-chat'
		),
		component: ChatbotSettings,
	},
	{
		id: 'ai-engine',
		label: __('AI Engine & Prompt', 'dragwyb-click-to-chat'),
		icon: 'dashicons-rest-api',
		desc: __(
			'Configure your AI providers, API keys, models, system instructions, and response behavior.',
			'dragwyb-click-to-chat'
		),
		component: AiEngineSettings,
	},
	{
		id: 'knowledge-base',
		label: __('Knowledge Base', 'dragwyb-click-to-chat'),
		icon: 'dashicons-database',
		desc: __(
			'Provide custom text, links, documents, and index your website content for the chatbot to learn from.',
			'dragwyb-click-to-chat'
		),
		component: KnowledgeBase,
	},
	{
		id: 'ai-copilot',
		label: __('AI Copilot', 'dragwyb-click-to-chat'),
		icon: 'dashicons-superhero',
		desc: __(
			'Ask your AI Copilot anything about visitor questions, unanswered gaps, lead analytics, and content advice.',
			'dragwyb-click-to-chat'
		),
		component: AdminCopilot,
	},
	{
		id: 'leads',
		label: __('AI Leads', 'dragwyb-click-to-chat'),
		icon: 'dashicons-id',
		desc: __(
			'Review, qualify, search, filter, update statuses, and export leads collected by your AI assistant.',
			'dragwyb-click-to-chat'
		),
		component: LeadsManagement,
	},
	{
		id: 'chat-sessions',
		label: __('Chat Sessions', 'dragwyb-click-to-chat'),
		icon: 'dashicons-format-chat',
		desc: __(
			'View and manage recent conversations with your AI assistant.',
			'dragwyb-click-to-chat'
		),
		component: ChatSessions,
	},
	{
		id: 'chat-preview',
		label: __('Chat Preview', 'dragwyb-click-to-chat'),
		icon: 'dashicons-welcome-view-site',
		desc: __(
			'See how your chatbot appears to your website visitors.',
			'dragwyb-click-to-chat'
		),
		component: ChatPreview,
	},
	{
		id: 'error-logs',
		label: __('Error Logs', 'dragwyb-click-to-chat'),
		icon: 'dashicons-warning',
		desc: __(
			'Review recent plugin errors, warnings, and AI API failures.',
			'dragwyb-click-to-chat'
		),
		component: ErrorLogs,
	},
];

const NAV_GROUPS = [
	{
		label: __('SETUP', 'dragwyb-click-to-chat'),
		items: ['chatbot-settings', 'ai-engine'],
	},
	{
		label: __('KNOWLEDGE', 'dragwyb-click-to-chat'),
		items: ['knowledge-base'],
	},
	{
		label: __('CRM & SESSIONS', 'dragwyb-click-to-chat'),
		items: ['ai-copilot', 'leads', 'chat-sessions'],
	},
	{
		label: __('MONITOR', 'dragwyb-click-to-chat'),
		items: ['chat-preview', 'error-logs'],
	},
];

export default function App({ settings: initialSettings }) {
	const [settings, setSettings] = useState(
		() => initialSettings || window.dctc_ai_data?.settings || {}
	);
	const [notice, setNotice] = useState(null);
	const [isSaving, setIsSaving] = useState(false);

	const currentPage =
		window.dctc_ai_data?.current_page ||
		new URLSearchParams(window.location.search).get('page') ||
		'';
	const isGuidePage = currentPage === 'dragwyb-click-to-chat-guide';
	const searchParams = new URLSearchParams(window.location.search);
	const isTabSetup = searchParams.get('tab') === 'setup';
	const isOpenOne = searchParams.get('open') === '1';
	const isExplicitOpen = searchParams.get('dctc_open_onboarding') === 'true';

	// Check tab === setup and open === 1 before auto-opening modal
	const [showWizard, setShowWizard] = useState(
		() => (isGuidePage && isTabSetup && isOpenOne) || isExplicitOpen || !!window.dctc_ai_data?.show_onboarding
	);
	const [wizardKey, setWizardKey] = useState(0);

	// Listen for global modal open events from Guide page button clicks
	useEffect(() => {
		const handleOpenWizard = () => {
			setShowWizard(true);
		};
		window.addEventListener('dctc_open_onboarding_wizard', handleOpenWizard);
		return () => {
			window.removeEventListener('dctc_open_onboarding_wizard', handleOpenWizard);
		};
	}, []);

	// Listen for saving start and end events
	useEffect(() => {
		const onSavingStart = () => setIsSaving(true);
		const onSavingEnd = () => setIsSaving(false);
		window.addEventListener('dctc_ai_saving_start', onSavingStart);
		window.addEventListener('dctc_ai_saving_end', onSavingEnd);
		return () => {
			window.removeEventListener('dctc_ai_saving_start', onSavingStart);
			window.removeEventListener('dctc_ai_saving_end', onSavingEnd);
		};
	}, []);

	const saveChat = !!settings?.chatbot?.save_chat;
	const visibleTabs = TABS.filter(
		(tab) => tab.id !== 'chat-sessions' || saveChat
	);

	const [activeTab, setActiveTab] = useState(() => {
		const hash = window.location.hash.replace('#', '');
		if (hash === 'display-settings') {
			return 'chatbot-settings';
		}
		if (
			hash === 'api-keys' ||
			hash === 'instructions' ||
			hash === 'providers' ||
			hash === 'prompt'
		) {
			return 'ai-engine';
		}
		if (hash && visibleTabs.some((t) => t.id === hash)) {
			return hash;
		}
		return visibleTabs[0]?.id || 'chatbot-settings';
	});

	const showNotice = useCallback((message, type = 'success') => {
		setNotice({ message, type });
		setTimeout(() => setNotice(null), 10000);
	}, []);

	const onSave = useCallback((partial) => {
		setSettings((prev) => ({ ...prev, ...partial }));
	}, []);

	const current = visibleTabs.find((t) => t.id === activeTab) || visibleTabs[0];
	const Panel = current?.component;

	useEffect(() => {
		if (!visibleTabs.some((t) => t.id === activeTab)) {
			const id = visibleTabs[0].id;
			setActiveTab(id);
			window.location.hash = id;
		}
	}, [activeTab, visibleTabs]);

	useEffect(() => {
		const el = document.querySelector('.dctc-ai-tabs .dctc-ai-tab.active');
		if (el && typeof el.scrollIntoView === 'function') {
			el.scrollIntoView({ block: 'nearest', inline: 'nearest' });
		}
	}, [activeTab]);

	useEffect(() => {
		const onHash = () => {
			const hash = window.location.hash.replace('#', '');
			if (
				hash === 'api-keys' ||
				hash === 'instructions' ||
				hash === 'providers' ||
				hash === 'prompt'
			) {
				setActiveTab('ai-engine');
			} else if (hash && visibleTabs.some((t) => t.id === hash)) {
				setActiveTab(hash);
			}
		};
		window.addEventListener('hashchange', onHash);
		return () => window.removeEventListener('hashchange', onHash);
	}, [visibleTabs]);

	const goToTab = (id) => () => {
		setActiveTab(id);
		window.location.hash = id;
	};

	// When on the Guide page: render the Onboarding Wizard Modal and toasts
	if (isGuidePage) {
		return (
			<div className="dctc-guide-react-root">
				{notice && <Toast message={notice.message} type={notice.type} onClose={() => setNotice(null)} />}
				{showWizard && (
					<OnboardingWizard
						key={wizardKey}
						open={true}
						onClose={() => {
							if (typeof window !== 'undefined' && window.history && window.history.replaceState) {
								const url = new URL(window.location.href);
								if (url.searchParams.has('open') || url.searchParams.has('dctc_open_onboarding')) {
									url.searchParams.delete('open');
									url.searchParams.delete('dctc_open_onboarding');
									window.history.replaceState({}, document.title, url.pathname + url.search + url.hash);
								}
							}
							setShowWizard(false);
							setWizardKey((k) => k + 1);
						}}
						showNotice={showNotice}
					/>
				)}
			</div>
		);
	}

	// Normal Main Menu Page (dragwyb-click-to-chat):
	return (
		<div className="dctc-ai-app-wrapper">
			{notice && <Toast message={notice.message} type={notice.type} onClose={() => setNotice(null)} />}

			{/* Full-Size Sticky Top Header Bar using shared GlobalHeader */}
			<GlobalHeader
				className="dctc-ai-top-header"
				icon="dashicons-format-chat"
				title={__('AI Assistant', 'dragwyb-click-to-chat')}
				subheading={__('Autonomous AI Agent & Knowledge Base', 'dragwyb-click-to-chat')}
				rightActions={
					<>
						<a
							href="admin.php?page=dragwyb-click-to-chat-guide"
							className="dctc-ai-guide-btn"
							title={__('View AI Assistant documentation', 'dragwyb-click-to-chat')}
						>
							<span className="dashicons dashicons-book" />
							{__('User Guide', 'dragwyb-click-to-chat')}
						</a>

						<button
							type="button"
							id="dctc-global-save-btn"
							className="dctc-ai-btn dctc-ai-btn-primary"
							disabled={isSaving}
							onClick={() => {
								window.dispatchEvent(new CustomEvent('dctc_ai_trigger_save'));
							}}
						>
							<span
								className={`dashicons ${isSaving ? 'dashicons-update spin' : 'dashicons-saved'}`}
								style={{
									fontSize: '16px',
									width: '16px',
									height: '16px',
									marginRight: '4px',
									animation: isSaving ? 'dctcSpin 1s linear infinite' : 'none',
								}}
							/>
							{isSaving ? __('Saving...', 'dragwyb-click-to-chat') : __('Save Settings', 'dragwyb-click-to-chat')}
						</button>
					</>
				}
			/>

			<div className="dctc-ai-dashboard-wrapper">
				<aside className="dctc-ai-dashboard-header">
					<nav
						className="dctc-ai-tabs"
						role="tablist"
						aria-label={__('Settings sections', 'dragwyb-click-to-chat')}
					>
						{NAV_GROUPS.map((group) => (
							<div className="dctc-ai-nav-group" key={group.label}>
								<span className="dctc-ai-nav-group__label">{group.label}</span>
								{group.items.map((itemId) => {
									const tab = visibleTabs.find((t) => t.id === itemId);
									if (!tab) {
										return null;
									}
									return (
										<button
											key={tab.id}
											type="button"
											role="tab"
											id={`tab-${tab.id}`}
											aria-controls={`panel-${tab.id}`}
											aria-selected={activeTab === tab.id}
											className={`dctc-ai-tab ${activeTab === tab.id ? 'active' : ''}`}
											onClick={goToTab(tab.id)}
										>
											<span className={`dashicons ${tab.icon}`} aria-hidden="true" />
											<span className="dctc-ai-tab__label">{tab.label}</span>
										</button>
									);
								})}
							</div>
						))}
					</nav>
				</aside>

				<main
					className="dctc-ai-dashboard-body"
					id={`panel-${current?.id}`}
					role="tabpanel"
					aria-labelledby={`tab-${current?.id}`}
				>
					{Panel && (
						<Panel
							settings={settings}
							onSave={onSave}
							showNotice={showNotice}
						/>
					)}
				</main>
			</div>
		</div>
	);
}
