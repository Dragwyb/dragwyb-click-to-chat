/**
 * Admin AI Copilot Component — Pro Marketing Preview
 *
 * Showcases AI-powered business analytics, gap discovery, ticket summaries,
 * and content advice in Dragwyb Pro.
 */

import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import ProBadge from '../../../common/components/ProBadge';
import { getProUrl } from '../utils/providers';

const SAMPLE_CAPABILITIES = [
	{
		icon: 'dashicons-chart-line',
		title: __( 'Visitor Question Discovery', 'dragwyb-click-to-chat' ),
		desc: __( 'Instantly see what topics and questions visitors ask most across your entire store.', 'dragwyb-click-to-chat' ),
	},
	{
		icon: 'dashicons-warning',
		title: __( 'Unanswered Gaps & Fallback Alerts', 'dragwyb-click-to-chat' ),
		desc: __( 'Identifies specific queries where the AI had low confidence so you can update FAQs immediately.', 'dragwyb-click-to-chat' ),
	},
	{
		icon: 'dashicons-id',
		title: __( 'Lead Conversion Intelligence', 'dragwyb-click-to-chat' ),
		desc: __( 'Pinpoint high-intent visitor patterns and page drivers that generate your highest-value leads.', 'dragwyb-click-to-chat' ),
	},
	{
		icon: 'dashicons-edit',
		title: __( '1-Click Content & FAQ Generator', 'dragwyb-click-to-chat' ),
		desc: __( 'Generate polished Knowledge Base articles with outline bullet points based on real questions.', 'dragwyb-click-to-chat' ),
	},
];

const PREVIEW_PROMPTS = [
	{
		title: '🔍 ' + __( 'Top Visitor Questions This Month', 'dragwyb-click-to-chat' ),
		previewAnswer: __( '📊 Analysis of 420 visitor chats:\n• 42% Shipping & delivery times to EU/US\n• 28% Return policy & warranty on electronic items\n• 18% Bulk pricing discount inquiry\n• 12% General support questions', 'dragwyb-click-to-chat' ),
	},
	{
		title: '❓ ' + __( 'Knowledge Base Gaps & Low-Confidence Queries', 'dragwyb-click-to-chat' ),
		previewAnswer: __( '💡 3 Missing Topics Detected:\n1. "Do you offer cash on delivery in Canada?" (Asked 14 times)\n2. "How do I upgrade an existing subscription license?" (Asked 9 times)\n3. "Is there a student discount code?" (Asked 6 times)', 'dragwyb-click-to-chat' ),
	},
	{
		title: '📈 ' + __( 'Lead Conversion & Sales Insights', 'dragwyb-click-to-chat' ),
		previewAnswer: __( '🚀 74% of high-intent leads interacted with the chatbot on /pricing before submitting contact details. Recommended: Add a direct discount voucher trigger on the pricing page.', 'dragwyb-click-to-chat' ),
	},
];

export default function AdminCopilot() {
	const [ activePromptIdx, setActivePromptIdx ] = useState( 0 );

	return (
		<div className="dctc-pro dctc-ai-copilot-wrap" data-pro-feature="admin-copilot">
			{/* Top Pro Feature Banner */}
			<div style={{
				background: 'linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4338ca 100%)',
				color: '#fff',
				borderRadius: '12px',
				padding: '24px 28px',
				marginBottom: '24px',
				boxShadow: '0 4px 20px rgba(49, 46, 129, 0.15)',
				display: 'flex',
				justifyContent: 'space-between',
				alignItems: 'center',
				flexWrap: 'wrap',
				gap: '16px',
			}}>
				<div style={{ maxWidth: '650px' }}>
					<div style={{ display: 'flex', alignItems: 'center', gap: '8px', marginBottom: '8px' }}>
						<span style={{ fontSize: '24px' }}>🚀</span>
						<h2 style={{ margin: 0, color: '#fff', fontSize: '20px', fontWeight: '700' }}>
							{ __( 'Admin AI Copilot & Business Intelligence', 'dragwyb-click-to-chat' ) }
						</h2>
						<ProBadge />
					</div>
					<p style={{ margin: 0, color: '#c7d2fe', fontSize: '14px', lineHeight: 1.5 }}>
						{ __( 'Ask your conversational AI Copilot anything about visitor questions, Knowledge Base gaps, lead analytics, and content optimization recommendations.', 'dragwyb-click-to-chat' ) }
					</p>
				</div>
				<div>
					<a
						href={getProUrl('admin_copilot')}
						target="_blank"
						rel="noopener noreferrer"
						className="dctc-pro-upgrade-btn"
						style={{
							display: 'inline-flex',
							alignItems: 'center',
							gap: '8px',
							background: '#fbbf24',
							color: '#1e1b4b',
							padding: '10px 20px',
							borderRadius: '8px',
							fontSize: '14px',
							fontWeight: '700',
							textDecoration: 'none',
							boxShadow: '0 4px 12px rgba(251, 191, 36, 0.3)',
							transition: 'all 0.2s ease',
						}}
					>
						<span className="dashicons dashicons-star-filled" style={{ fontSize: '16px', width: '16px', height: '16px' }} />
						{ __( 'Upgrade to Dragwyb Pro', 'dragwyb-click-to-chat' ) }
					</a>
				</div>
			</div>

			{/* 4 Feature Highlights Grid */}
			<div style={{
				display: 'grid',
				gridTemplateColumns: 'repeat(auto-fit, minmax(240px, 1fr))',
				gap: '16px',
				marginBottom: '24px',
			}}>
				{ SAMPLE_CAPABILITIES.map( ( cap, idx ) => (
					<div
						key={ idx }
						style={{
							background: '#fff',
							border: '1px solid #e5e7eb',
							borderRadius: '10px',
							padding: '16px',
							display: 'flex',
							flexDirection: 'column',
							gap: '8px',
						}}
					>
						<div style={{
							width: '36px',
							height: '36px',
							borderRadius: '8px',
							background: '#f5f3ff',
							color: '#7c3aed',
							display: 'flex',
							alignItems: 'center',
							justifyContent: 'center',
						}}>
							<span className={ `dashicons ${ cap.icon }` } style={{ fontSize: '18px' }} />
						</div>
						<h4 style={{ margin: 0, fontSize: '14px', fontWeight: '600', color: '#111827' }}>
							{ cap.title }
						</h4>
						<p style={{ margin: 0, fontSize: '12px', color: '#6b7280', lineHeight: 1.4 }}>
							{ cap.desc }
						</p>
					</div>
				) ) }
			</div>

			{/* Interactive Copilot Preview Box */}
			<div style={{
				background: '#fff',
				border: '1px solid #e5e7eb',
				borderRadius: '12px',
				padding: '24px',
				boxShadow: '0 1px 3px rgba(0,0,0,0.05)',
			}}>
				<div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '16px' }}>
					<h3 style={{ margin: 0, fontSize: '16px', fontWeight: '600', color: '#1f2937' }}>
						{ __( 'Interactive Copilot Demo Preview', 'dragwyb-click-to-chat' ) }
					</h3>
					<span style={{ fontSize: '12px', color: '#9ca3af' }}>
						{ __( 'Click a sample question to see Copilot output', 'dragwyb-click-to-chat' ) }
					</span>
				</div>

				<div style={{ display: 'flex', gap: '8px', flexWrap: 'wrap', marginBottom: '16px' }}>
					{ PREVIEW_PROMPTS.map( ( p, idx ) => (
						<button
							key={ idx }
							type="button"
							onClick={ () => setActivePromptIdx( idx ) }
							style={{
								padding: '8px 14px',
								borderRadius: '8px',
								border: activePromptIdx === idx ? '2px solid #7c3aed' : '1px solid #e5e7eb',
								background: activePromptIdx === idx ? '#f5f3ff' : '#fff',
								color: activePromptIdx === idx ? '#6d28d9' : '#374151',
								fontWeight: activePromptIdx === idx ? '600' : '500',
								fontSize: '13px',
								cursor: 'pointer',
							}}
						>
							{ p.title }
						</button>
					) ) }
				</div>

				<div style={{
					background: '#f9fafb',
					border: '1px solid #e5e7eb',
					borderRadius: '8px',
					padding: '16px 20px',
					whiteSpace: 'pre-line',
					fontSize: '13px',
					lineHeight: 1.6,
					color: '#1f2937',
					fontFamily: 'monospace, sans-serif',
					position: 'relative',
				}}>
					{ PREVIEW_PROMPTS[ activePromptIdx ].previewAnswer }
					<div style={{ marginTop: '12px', paddingTop: '10px', borderTop: '1px dashed #e5e7eb', display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
						<span style={{ fontSize: '11px', color: '#9ca3af' }}>
							{ __( 'Simulated output from Dragwyb Pro Copilot Intelligence Engine', 'dragwyb-click-to-chat' ) }
						</span>
						<a
							href={getProUrl('admin_copilot_demo')}
							target="_blank"
							rel="noopener noreferrer"
							style={{ fontSize: '12px', color: '#7c3aed', fontWeight: '600', textDecoration: 'none' }}
						>
							{ __( 'Unlock Full Copilot in Pro →', 'dragwyb-click-to-chat' ) }
						</a>
					</div>
				</div>

				{/* Disabled Input Bar */}
				<div style={{ marginTop: '16px', display: 'flex', gap: '8px' }}>
					<input
						type="text"
						className="dctc-ai-bot-input dctc-pro-control"
						placeholder={ __( 'Ask Copilot anything about your site, leads, questions, or tickets (Pro feature)...', 'dragwyb-click-to-chat' ) }
						disabled={ true }
						readOnly={ true }
						style={{ flex: 1, background: '#f9fafb', cursor: 'not-allowed' }}
					/>
					<button
						type="button"
						className="dctc-ai-btn"
						disabled={ true }
						style={{ opacity: 0.6, cursor: 'not-allowed' }}
					>
						<span className="dashicons dashicons-arrow-right-alt2" />
					</button>
				</div>
			</div>
		</div>
	);
}
