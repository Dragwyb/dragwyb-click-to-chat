/**
 * Admin AI Copilot Component
 *
 * Provides site administrators with AI-powered operational insights,
 * conversation trend analytics, unanswered question discoveries, and
 * intelligent Knowledge Base gap recommendations.
 */

import { useState, useEffect, useRef, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

const QUICK_PROMPTS = [
	{
		id: 'frequent_questions',
		icon: '🔍',
		title: __( 'Top Visitor Questions', 'dragwyb-click-to-chat' ),
		desc: __( 'Discover common topics and frequently asked questions.', 'dragwyb-click-to-chat' ),
		prompt: 'What are our visitors asking about most frequently? Group their questions into clear categories with percentages or frequencies if available.',
	},
	{
		id: 'unanswered_questions',
		icon: '❓',
		title: __( 'Unanswered Questions & Gaps', 'dragwyb-click-to-chat' ),
		desc: __( 'Find questions where the bot had low confidence or fell back.', 'dragwyb-click-to-chat' ),
		prompt: 'What visitor questions were unanswered, received fallback answers, or left users frustrated? List the exact customer inquiries.',
	},
	{
		id: 'lead_performance',
		icon: '📈',
		title: __( 'Lead Generation Breakdown', 'dragwyb-click-to-chat' ),
		desc: __( 'Which pages and topics drive the highest value leads?', 'dragwyb-click-to-chat' ),
		prompt: 'Analyze our lead capture performance. Which pages generate the most leads, and what are high-scoring leads asking before converting?',
	},
	{
		id: 'kb_recommendations',
		icon: '💡',
		title: __( 'Knowledge Base Gap Analysis', 'dragwyb-click-to-chat' ),
		desc: __( 'Get concrete FAQ and documentation outlines to add.', 'dragwyb-click-to-chat' ),
		prompt: 'Based on visitor questions and gaps in our Knowledge Base, suggest 3 to 5 new FAQ articles or Knowledge Base entries I should create today, with outline bullet points for each.',
	},
	{
		id: 'executive_summary',
		icon: '📊',
		title: __( 'Executive Health Summary', 'dragwyb-click-to-chat' ),
		desc: __( 'Comprehensive overview of bot performance & user sentiment.', 'dragwyb-click-to-chat' ),
		prompt: 'Provide an executive summary of our AI chatbot performance, customer sentiment distribution, top conversion drivers, and 3 high-priority recommendations for this week.',
	},
];

export default function AdminCopilot( { showNotice } ) {
	const [ stats, setStats ] = useState( null );
	const [ statsLoading, setStatsLoading ] = useState( true );
	const [ messages, setMessages ] = useState( [
		{
			role: 'assistant',
			content: __(
				'Hello Admin! I am your AI Copilot. I analyze your live chatbot conversations, visitor sentiment, lead conversions, and Knowledge Base coverage to help you optimize your website.\n\nChoose a quick prompt below or ask me anything about your visitors and store performance!',
				'dragwyb-click-to-chat'
			),
		},
	] );
	const [ input, setInput ] = useState( '' );
	const [ isLoading, setIsLoading ] = useState( false );
	const [ copiedIdx, setCopiedIdx ] = useState( null );
	const chatEndRef = useRef( null );

	const fetchStats = useCallback( async () => {
		setStatsLoading( true );
		try {
			const res = await apiFetch( { path: '/dctc-ai/v1/copilot/stats' } );
			if ( res && res.stats ) {
				setStats( res.stats );
			}
		} catch ( err ) {
			// Fail silently for stats
		} finally {
			setStatsLoading( false );
		}
	}, [] );

	useEffect( () => {
		fetchStats();
	}, [ fetchStats ] );

	useEffect( () => {
		chatEndRef.current?.scrollIntoView( { behavior: 'smooth' } );
	}, [ messages, isLoading ] );

	const handleSend = async ( promptToSend ) => {
		const text = typeof promptToSend === 'string' ? promptToSend : input;
		if ( ! text.trim() || isLoading ) {
			return;
		}

		const userMsg = { role: 'user', content: text.trim() };
		setMessages( ( prev ) => [ ...prev, userMsg ] );
		setInput( '' );
		setIsLoading( true );

		try {
			const res = await apiFetch( {
				path: '/dctc-ai/v1/copilot',
				method: 'POST',
				data: { prompt: text.trim() },
			} );

			if ( res.success ) {
				const botMsg = {
					role: 'assistant',
					content: res.message,
					provider: res.provider,
					model: res.model,
				};
				setMessages( ( prev ) => [ ...prev, botMsg ] );
				if ( res.stats ) {
					setStats( res.stats );
				}
			} else {
				setMessages( ( prev ) => [
					...prev,
					{
						role: 'assistant',
						content:
							res.message ||
							__(
								'An error occurred while communicating with the AI Copilot.',
								'dragwyb-click-to-chat'
							),
						isError: true,
					},
				] );
			}
		} catch ( err ) {
			setMessages( ( prev ) => [
				...prev,
				{
					role: 'assistant',
					content:
						err.message ||
						__(
							'Failed to connect to the AI Copilot endpoint.',
							'dragwyb-click-to-chat'
						),
					isError: true,
				},
			] );
		} finally {
			setIsLoading( false );
		}
	};

	const handleCopy = ( text, idx ) => {
		navigator.clipboard.writeText( text );
		setCopiedIdx( idx );
		if ( showNotice ) {
			showNotice( __( 'Copied to clipboard!', 'dragwyb-click-to-chat' ) );
		}
		setTimeout( () => setCopiedIdx( null ), 2000 );
	};

	const handleClearChat = () => {
		setMessages( [
			{
				role: 'assistant',
				content: __(
					'Chat cleared. How can I assist you with your site intelligence today?',
					'dragwyb-click-to-chat'
				),
			},
		] );
	};

	return (
		<div className="dctc-ai-copilot-container">
			{ /* Header Metric Cards */ }
			<div className="dctc-ai-copilot-metrics">
				<div className="dctc-ai-copilot-metric-card">
					<div className="dctc-ai-copilot-metric-icon">💬</div>
					<div className="dctc-ai-copilot-metric-data">
						<span className="dctc-ai-copilot-metric-val">
							{ statsLoading ? '...' : stats?.total_sessions ?? 0 }
						</span>
						<span className="dctc-ai-copilot-metric-lbl">
							{ __( 'Conversations Analyzed', 'dragwyb-click-to-chat' ) }
						</span>
					</div>
				</div>

				<div className="dctc-ai-copilot-metric-card">
					<div className="dctc-ai-copilot-metric-icon">🎯</div>
					<div className="dctc-ai-copilot-metric-data">
						<span className="dctc-ai-copilot-metric-val">
							{ statsLoading ? '...' : stats?.total_leads ?? 0 }
						</span>
						<span className="dctc-ai-copilot-metric-lbl">
							{ __( 'AI Leads Captured', 'dragwyb-click-to-chat' ) }
						</span>
					</div>
				</div>

				<div className="dctc-ai-copilot-metric-card">
					<div className="dctc-ai-copilot-metric-icon">❓</div>
					<div className="dctc-ai-copilot-metric-data">
						<span className="dctc-ai-copilot-metric-val">
							{ statsLoading ? '...' : stats?.unanswered_questions?.length ?? 0 }
						</span>
						<span className="dctc-ai-copilot-metric-lbl">
							{ __( 'Flagged Content Gaps', 'dragwyb-click-to-chat' ) }
						</span>
					</div>
				</div>

				<div className="dctc-ai-copilot-metric-card">
					<div className="dctc-ai-copilot-metric-icon">📚</div>
					<div className="dctc-ai-copilot-metric-data">
						<span className="dctc-ai-copilot-metric-val">
							{ statsLoading ? '...' : stats?.total_kb_docs ?? 0 }
						</span>
						<span className="dctc-ai-copilot-metric-lbl">
							{ __( 'Indexed KB Documents', 'dragwyb-click-to-chat' ) }
						</span>
					</div>
				</div>
			</div>

			{ /* Quick Prompt Selection Chips */ }
			<div className="dctc-ai-copilot-quick-prompts">
				<div className="dctc-ai-copilot-quick-title">
					<span>⚡ { __( 'Quick Strategic Questions:', 'dragwyb-click-to-chat' ) }</span>
				</div>
				<div className="dctc-ai-copilot-quick-grid">
					{ QUICK_PROMPTS.map( ( qp ) => (
						<button
							key={ qp.id }
							type="button"
							className="dctc-ai-copilot-chip"
							onClick={ () => handleSend( qp.prompt ) }
							disabled={ isLoading }
						>
							<span className="dctc-ai-copilot-chip-icon">{ qp.icon }</span>
							<div className="dctc-ai-copilot-chip-text">
								<strong>{ qp.title }</strong>
								<small>{ qp.desc }</small>
							</div>
						</button>
					) ) }
				</div>
			</div>

			{ /* Copilot Interactive Conversation Stream */ }
			<div className="dctc-ai-copilot-chat-card">
				<div className="dctc-ai-copilot-chat-header">
					<div className="dctc-ai-copilot-chat-title">
						<span className="dctc-ai-copilot-badge">🤖 AI Copilot</span>
						<span className="dctc-ai-copilot-status-dot"></span>
						<span className="dctc-ai-copilot-status-text">
							{ __( 'Connected to Site Database & AI Engine', 'dragwyb-click-to-chat' ) }
						</span>
					</div>
					<button
						type="button"
						className="dctc-ai-btn-secondary dctc-ai-btn--sm"
						onClick={ handleClearChat }
						disabled={ isLoading }
					>
						{ __( 'Clear Chat', 'dragwyb-click-to-chat' ) }
					</button>
				</div>

				<div className="dctc-ai-copilot-chat-body">
					{ messages.map( ( msg, idx ) => {
						const isAssistant = msg.role === 'assistant';
						return (
							<div
								key={ idx }
								className={ `dctc-ai-copilot-msg ${
									isAssistant
										? 'dctc-ai-copilot-msg--assistant'
										: 'dctc-ai-copilot-msg--user'
								} ${ msg.isError ? 'is-error' : '' }` }
							>
								<div className="dctc-ai-copilot-msg-avatar">
									{ isAssistant ? '🤖' : '👤' }
								</div>
								<div className="dctc-ai-copilot-msg-content">
									<div className="dctc-ai-copilot-msg-text">
										{ msg.content.split( '\n' ).map( ( line, lIdx ) => (
											<p key={ lIdx } style={ { margin: '0 0 6px 0' } }>
												{ line }
											</p>
										) ) }
									</div>
									{ isAssistant && (
										<div className="dctc-ai-copilot-msg-actions">
											<button
												type="button"
												className="dctc-ai-copilot-copy-btn"
												onClick={ () => handleCopy( msg.content, idx ) }
												title={ __( 'Copy response', 'dragwyb-click-to-chat' ) }
											>
												{ copiedIdx === idx ? '✓ Copied' : '📋 Copy' }
											</button>
											{ msg.model && (
												<span className="dctc-ai-copilot-model-tag">
													{ msg.provider } / { msg.model }
												</span>
											) }
										</div>
									) }
								</div>
							</div>
						);
					} ) }

					{ isLoading && (
						<div className="dctc-ai-copilot-msg dctc-ai-copilot-msg--assistant">
							<div className="dctc-ai-copilot-msg-avatar">🤖</div>
							<div className="dctc-ai-copilot-msg-content">
								<div className="dctc-ai-copilot-typing">
									<span></span>
									<span></span>
									<span></span>
								</div>
							</div>
						</div>
					) }
					<div ref={ chatEndRef } />
				</div>

				{ /* Input Bar */ }
				<div className="dctc-ai-copilot-chat-footer">
					<div className="dctc-ai-copilot-input-box">
						<textarea
							value={ input }
							onChange={ ( e ) => setInput( e.target.value ) }
							onKeyDown={ ( e ) => {
								if ( e.key === 'Enter' && ! e.shiftKey ) {
									e.preventDefault();
									handleSend();
								}
							} }
							placeholder={ __(
								'Ask Copilot about visitor trends, missed queries, high-converting pages, or KB advice...',
								'dragwyb-click-to-chat'
							) }
							rows={ 2 }
							disabled={ isLoading }
						/>
						<button
							type="button"
							className="dctc-ai-copilot-send-btn"
							onClick={ () => handleSend() }
							disabled={ ! input.trim() || isLoading }
						>
							{ isLoading
								? __( 'Analyzing...', 'dragwyb-click-to-chat' )
								: __( 'Ask Copilot ➔', 'dragwyb-click-to-chat' ) }
						</button>
					</div>
				</div>
			</div>
		</div>
	);
}
