/**
 * Unified Inbox Component
 *
 * Multi-channel conversation hub bridging AI chatbot, WhatsApp handoffs,
 * customer emails, agent assignments, internal staff notes & live human interventions.
 */

import { useState, useEffect, useRef, useCallback } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

const CHANNELS = [
	{ id: 'all', label: __( 'All Channels', 'dragwyb-click-to-chat' ), icon: 'dashicons-networking' },
	{ id: 'chatbot', label: __( 'AI Chatbot', 'dragwyb-click-to-chat' ), icon: 'dashicons-format-chat' },
	{ id: 'whatsapp', label: __( 'WhatsApp', 'dragwyb-click-to-chat' ), icon: 'dashicons-phone' },
	{ id: 'email', label: __( 'Email', 'dragwyb-click-to-chat' ), icon: 'dashicons-email' },
];

const STATUSES = [
	{ id: 'all', label: __( 'All Statuses', 'dragwyb-click-to-chat' ) },
	{ id: 'active', label: __( 'Active', 'dragwyb-click-to-chat' ), color: '#10b981' },
	{ id: 'waiting_human', label: __( 'Needs Human ⚠️', 'dragwyb-click-to-chat' ), color: '#f59e0b' },
	{ id: 'assigned', label: __( 'Assigned 👤', 'dragwyb-click-to-chat' ), color: '#3b82f6' },
	{ id: 'resolved', label: __( 'Resolved ✅', 'dragwyb-click-to-chat' ), color: '#64748b' },
];

function getChannelIcon( channel ) {
	switch ( channel ) {
		case 'whatsapp':
			return '📱';
		case 'email':
			return '✉️';
		case 'telegram':
			return '✈️';
		case 'phone':
			return '📞';
		case 'chatbot':
		default:
			return '🤖';
	}
}

function getSentimentBadge( sentiment ) {
	switch ( sentiment ) {
		case 'positive':
			return { label: __( '😊 Positive', 'dragwyb-click-to-chat' ), bg: '#ecfdf5', color: '#065f46' };
		case 'frustrated':
		case 'negative':
			return { label: __( '😤 Frustrated', 'dragwyb-click-to-chat' ), bg: '#fef2f2', color: '#991b1b' };
		case 'neutral':
		default:
			return { label: __( '😐 Neutral', 'dragwyb-click-to-chat' ), bg: '#f8fafc', color: '#64748b' };
	}
}

export default function UnifiedInbox( { showNotice } ) {
	const [ conversations, setConversations ] = useState( [] );
	const [ selectedId, setSelectedId ] = useState( null );
	const [ activeConv, setActiveConv ] = useState( null );
	const [ agents, setAgents ] = useState( [] );
	const [ isLoadingList, setIsLoadingList ] = useState( true );
	const [ isLoadingConv, setIsLoadingConv ] = useState( false );

	// Filters
	const [ channelFilter, setChannelFilter ] = useState( 'all' );
	const [ statusFilter, setStatusFilter ] = useState( 'all' );
	const [ searchQuery, setSearchQuery ] = useState( '' );
	const [ page, setPage ] = useState( 1 );
	const [ totalPages, setTotalPages ] = useState( 1 );

	// Reply & Notes composer state
	const [ replyText, setReplyText ] = useState( '' );
	const [ composerMode, setComposerMode ] = useState( 'reply' ); // 'reply' or 'note'
	const [ isSending, setIsSending ] = useState( false );

	const threadEndRef = useRef( null );

	// Fetch Agents List
	const fetchAgents = useCallback( async () => {
		try {
			const res = await apiFetch( { path: '/dctc-ai/v1/inbox/agents' } );
			if ( res?.agents ) {
				setAgents( res.agents );
			}
		} catch ( e ) {}
	}, [] );

	// Fetch Conversations List
	const fetchConversations = useCallback( async () => {
		setIsLoadingList( true );
		try {
			const params = new URLSearchParams( {
				channel: channelFilter,
				status: statusFilter,
				search: searchQuery,
				page: page.toString(),
				per_page: '20',
			} );
			const res = await apiFetch( { path: `/dctc-ai/v1/inbox/conversations?${ params.toString() }` } );
			if ( res?.success ) {
				setConversations( res.conversations || [] );
				setTotalPages( res.total_pages || 1 );

				// Auto-select first conversation if none selected
				if ( ! selectedId && res.conversations?.length > 0 ) {
					setSelectedId( res.conversations[ 0 ].id );
				}
			}
		} catch ( err ) {
			if ( showNotice ) {
				showNotice( __( 'Failed to load inbox conversations.', 'dragwyb-click-to-chat' ), 'error' );
			}
		} finally {
			setIsLoadingList( false );
		}
	}, [ channelFilter, statusFilter, searchQuery, page, selectedId, showNotice ] );

	// Fetch Single Conversation Thread
	const fetchActiveConversation = useCallback( async ( id ) => {
		if ( ! id ) return;
		setIsLoadingConv( true );
		try {
			const res = await apiFetch( { path: `/dctc-ai/v1/inbox/conversations/${ id }` } );
			if ( res?.success ) {
				setActiveConv( res.conversation );
			}
		} catch ( err ) {
			if ( showNotice ) {
				showNotice( __( 'Failed to load conversation details.', 'dragwyb-click-to-chat' ), 'error' );
			}
		} finally {
			setIsLoadingConv( false );
		}
	}, [ showNotice ] );

	useEffect( () => {
		fetchAgents();
	}, [ fetchAgents ] );

	useEffect( () => {
		fetchConversations();
	}, [ fetchConversations ] );

	useEffect( () => {
		if ( selectedId ) {
			fetchActiveConversation( selectedId );
		}
	}, [ selectedId, fetchActiveConversation ] );

	useEffect( () => {
		threadEndRef.current?.scrollIntoView( { behavior: 'smooth' } );
	}, [ activeConv?.messages, activeConv?.internal_notes ] );

	// Send Agent Reply or Internal Note
	const handleSend = async () => {
		if ( ! replyText.trim() || ! selectedId || isSending ) return;

		setIsSending( true );
		try {
			if ( composerMode === 'reply' ) {
				const res = await apiFetch( {
					path: `/dctc-ai/v1/inbox/conversations/${ selectedId }/reply`,
					method: 'POST',
					data: { message: replyText.trim() },
				} );
				if ( res?.success ) {
					setReplyText( '' );
					fetchActiveConversation( selectedId );
					fetchConversations();
					if ( showNotice ) {
						showNotice( __( 'Reply sent to visitor.', 'dragwyb-click-to-chat' ) );
					}
				}
			} else {
				const res = await apiFetch( {
					path: `/dctc-ai/v1/inbox/conversations/${ selectedId }/notes`,
					method: 'POST',
					data: { note: replyText.trim() },
				} );
				if ( res?.success ) {
					setReplyText( '' );
					fetchActiveConversation( selectedId );
					if ( showNotice ) {
						showNotice( __( 'Internal note added.', 'dragwyb-click-to-chat' ) );
					}
				}
			}
		} catch ( err ) {
			if ( showNotice ) {
				showNotice( err.message || __( 'Operation failed.', 'dragwyb-click-to-chat' ), 'error' );
			}
		} finally {
			setIsSending( false );
		}
	};

	// Change Status
	const handleStatusChange = async ( newStatus ) => {
		if ( ! selectedId ) return;
		try {
			const res = await apiFetch( {
				path: `/dctc-ai/v1/inbox/conversations/${ selectedId }/status`,
				method: 'POST',
				data: { status: newStatus },
			} );
			if ( res?.success ) {
				setActiveConv( ( prev ) => prev ? { ...prev, status: newStatus } : null );
				fetchConversations();
				if ( showNotice ) {
					showNotice( sprintf( __( 'Status updated to %s.', 'dragwyb-click-to-chat' ), newStatus ) );
				}
			}
		} catch ( err ) {
			if ( showNotice ) {
				showNotice( __( 'Failed to update status.', 'dragwyb-click-to-chat' ), 'error' );
			}
		}
	};

	// Change Assignment
	const handleAssignChange = async ( agentId ) => {
		if ( ! selectedId ) return;
		try {
			const res = await apiFetch( {
				path: `/dctc-ai/v1/inbox/conversations/${ selectedId }/assign`,
				method: 'POST',
				data: { assigned_to: parseInt( agentId, 10 ) || 0 },
			} );
			if ( res?.success ) {
				setActiveConv( ( prev ) => prev ? { ...prev, assigned_to: res.assigned_to } : null );
				fetchConversations();
				if ( showNotice ) {
					showNotice( __( 'Agent assignment updated.', 'dragwyb-click-to-chat' ) );
				}
			}
		} catch ( err ) {
			if ( showNotice ) {
				showNotice( __( 'Failed to update agent assignment.', 'dragwyb-click-to-chat' ), 'error' );
			}
		}
	};

	const sentimentInfo = getSentimentBadge( activeConv?.sentiment );

	return (
		<div className="dctc-ai-inbox-container">
			{ /* Left Pane: Channel Tabs, Filters & Conversation List */ }
			<aside className="dctc-ai-inbox-sidebar">
				{ /* Top Header */ }
				<div className="dctc-ai-inbox-sidebar-top">
					<div className="dctc-ai-inbox-sidebar-header">
						<div className="dctc-ai-inbox-sidebar-title">
							<h3>{ __( 'Conversations', 'dragwyb-click-to-chat' ) }</h3>
							<span className="dctc-ai-inbox-count-badge">
								{ conversations.length }
							</span>
						</div>
						<button
							type="button"
							className="dctc-ai-inbox-refresh-btn"
							onClick={ fetchConversations }
							title={ __( 'Refresh conversations', 'dragwyb-click-to-chat' ) }
						>
							<span className="dashicons dashicons-update"></span>
						</button>
					</div>

					{ /* Channel Filter Pills (Wrap neatly without ugly horizontal scrollbars) */ }
					<div className="dctc-ai-inbox-channel-tabs">
						{ CHANNELS.map( ( ch ) => (
							<button
								key={ ch.id }
								type="button"
								className={ `dctc-ai-inbox-channel-btn ${ channelFilter === ch.id ? 'is-active' : '' }` }
								onClick={ () => { setChannelFilter( ch.id ); setPage( 1 ); } }
								title={ ch.label }
							>
								<span>{ ch.label }</span>
							</button>
						) ) }
					</div>

					{ /* Search Row */ }
					<div className="dctc-ai-inbox-search-row">
						<div className="dctc-ai-inbox-search-wrap">
							<span className="dashicons dashicons-search dctc-ai-inbox-search-icon"></span>
							<input
								type="search"
								className="dctc-ai-inbox-search-input"
								placeholder={ __( 'Search by email, session, or text…', 'dragwyb-click-to-chat' ) }
								value={ searchQuery }
								onChange={ ( e ) => { setSearchQuery( e.target.value ); setPage( 1 ); } }
							/>
							{ searchQuery && (
								<button
									type="button"
									className="dctc-ai-inbox-search-clear"
									onClick={ () => setSearchQuery( '' ) }
								>
									×
								</button>
							) }
						</div>
					</div>

					{ /* Status Filters Bar */ }
					<div className="dctc-ai-inbox-status-bar">
						{ STATUSES.map( ( st ) => (
							<button
								key={ st.id }
								type="button"
								className={ `dctc-ai-inbox-status-pill ${ statusFilter === st.id ? 'is-active' : '' }` }
								onClick={ () => { setStatusFilter( st.id ); setPage( 1 ); } }
							>
								{ st.label }
							</button>
						) ) }
					</div>
				</div>

				{ /* Conversations List */ }
				<div className="dctc-ai-inbox-list">
					{ isLoadingList ? (
						<div className="dctc-ai-inbox-loading">
							<span className="dctc-ai-spinner"></span>
							<p>{ __( 'Loading conversations…', 'dragwyb-click-to-chat' ) }</p>
						</div>
					) : conversations.length === 0 ? (
						<div className="dctc-ai-inbox-empty">
							<div className="dctc-ai-inbox-empty-icon">💬</div>
							<h4>{ __( 'No conversations found', 'dragwyb-click-to-chat' ) }</h4>
							<p>{ __( 'Try changing your search query or channel filter to view more sessions.', 'dragwyb-click-to-chat' ) }</p>
							{ ( channelFilter !== 'all' || statusFilter !== 'all' || searchQuery ) && (
								<button
									type="button"
									className="dctc-ai-btn-secondary dctc-ai-btn--sm"
									onClick={ () => {
										setChannelFilter( 'all' );
										setStatusFilter( 'all' );
										setSearchQuery( '' );
									} }
								>
									{ __( 'Reset Filters', 'dragwyb-click-to-chat' ) }
								</button>
							) }
						</div>
					) : (
						conversations.map( ( item ) => {
							const isSelected = item.id === selectedId;
							return (
								<div
									key={ item.id }
									className={ `dctc-ai-inbox-item ${ isSelected ? 'is-selected' : '' } ${ item.unread_count > 0 ? 'is-unread' : '' }` }
									onClick={ () => setSelectedId( item.id ) }
								>
									<div className="dctc-ai-inbox-item-header">
										<div className="dctc-ai-inbox-item-user">
											<span className="dctc-ai-inbox-item-channel">{ getChannelIcon( item.channel ) }</span>
											<strong>{ item.email || item.session_id }</strong>
										</div>
										<span className="dctc-ai-inbox-item-time">
											{ item.created_at ? item.created_at.split( ' ' )[ 0 ] : '' }
										</span>
									</div>

									<p className="dctc-ai-inbox-item-preview">
										{ item.last_message || __( 'No messages yet.', 'dragwyb-click-to-chat' ) }
									</p>

									<div className="dctc-ai-inbox-item-footer">
										<span className={ `dctc-ai-inbox-status-tag status--${ item.status }` }>
											{ item.status }
										</span>
										{ item.unread_count > 0 && (
											<span className="dctc-ai-inbox-unread-badge">{ item.unread_count }</span>
										) }
										{ item.assigned_to && (
											<span className="dctc-ai-inbox-assigned-badge">
												👤 { item.assigned_to.name }
											</span>
										) }
									</div>
								</div>
							);
						} )
					) }
				</div>

				{ /* Pagination */ }
				{ totalPages > 1 && (
					<div className="dctc-ai-inbox-pagination">
						<button
							type="button"
							disabled={ page <= 1 }
							onClick={ () => setPage( ( p ) => Math.max( 1, p - 1 ) ) }
							className="dctc-ai-btn-secondary dctc-ai-btn--sm"
						>
							« { __( 'Prev', 'dragwyb-click-to-chat' ) }
						</button>
						<span>{ sprintf( __( 'Page %d of %d', 'dragwyb-click-to-chat' ), page, totalPages ) }</span>
						<button
							type="button"
							disabled={ page >= totalPages }
							onClick={ () => setPage( ( p ) => Math.min( totalPages, p + 1 ) ) }
							className="dctc-ai-btn-secondary dctc-ai-btn--sm"
						>
							{ __( 'Next', 'dragwyb-click-to-chat' ) } »
						</button>
					</div>
				) }
			</aside>

			{ /* Middle Pane: Active Conversation Stream & Agent Live Reply */ }
			<main className="dctc-ai-inbox-main">
				{ isLoadingConv ? (
					<div className="dctc-ai-inbox-main-loading">
						<span className="dctc-ai-spinner"></span>
						<p>{ __( 'Loading conversation thread…', 'dragwyb-click-to-chat' ) }</p>
					</div>
				) : ! activeConv ? (
					<div className="dctc-ai-inbox-main-empty">
						<div className="dctc-ai-inbox-main-empty-illustration">
							<span className="dctc-ai-inbox-empty-bubble">💬</span>
						</div>
						<h3>{ __( 'Select a conversation to begin', 'dragwyb-click-to-chat' ) }</h3>
						<p>{ __( 'Choose an active visitor session from the conversation list to review customer messages, intervene live as a human agent, and log private staff notes.', 'dragwyb-click-to-chat' ) }</p>
					</div>
				) : (
					<>
						{ /* Thread Header */ }
						<header className="dctc-ai-inbox-thread-header">
							<div className="dctc-ai-inbox-thread-title">
								<span className="dctc-ai-inbox-thread-channel-icon">
									{ getChannelIcon( activeConv.channel ) }
								</span>
								<div>
									<h4>{ activeConv.email || activeConv.session_id }</h4>
									<small>{ activeConv.channel.toUpperCase() } • Session: { activeConv.session_id }</small>
								</div>
							</div>

							<div className="dctc-ai-inbox-thread-actions">
								{ /* Assignee selector */ }
								<select
									className="dctc-ai-inbox-select"
									value={ activeConv.assigned_to?.id || '0' }
									onChange={ ( e ) => handleAssignChange( e.target.value ) }
								>
									<option value="0">{ __( 'Unassigned', 'dragwyb-click-to-chat' ) }</option>
									{ agents.map( ( ag ) => (
										<option key={ ag.id } value={ ag.id }>
											{ ag.name }
										</option>
									) ) }
								</select>

								{ /* Status selector */ }
								<select
									className="dctc-ai-inbox-select"
									value={ activeConv.status }
									onChange={ ( e ) => handleStatusChange( e.target.value ) }
								>
									<option value="active">{ __( '🟢 Active', 'dragwyb-click-to-chat' ) }</option>
									<option value="waiting_human">{ __( '⚠️ Needs Human', 'dragwyb-click-to-chat' ) }</option>
									<option value="assigned">{ __( '👤 Assigned', 'dragwyb-click-to-chat' ) }</option>
									<option value="resolved">{ __( '✅ Resolved', 'dragwyb-click-to-chat' ) }</option>
									<option value="closed">{ __( '🔒 Closed', 'dragwyb-click-to-chat' ) }</option>
								</select>
							</div>
						</header>

						{ /* Messages Stream */ }
						<div className="dctc-ai-inbox-thread-stream">
							{ ( activeConv.messages || [] ).map( ( msg, mIdx ) => {
								const isUser = msg.role === 'user';
								const isAgent = msg.role === 'agent';
								const isBot = msg.role === 'assistant' || msg.role === 'bot';

								return (
									<div
										key={ mIdx }
										className={ `dctc-ai-inbox-msg ${
											isUser
												? 'is-user'
												: isAgent
												? 'is-agent'
												: 'is-bot'
										}` }
									>
										<div className="dctc-ai-inbox-msg-avatar">
											{ isUser ? '👤' : isAgent ? '👨‍💼' : '🤖' }
										</div>
										<div className="dctc-ai-inbox-msg-content">
											<div className="dctc-ai-inbox-msg-meta">
												<strong>{ isUser ? ( activeConv.email || 'Visitor' ) : isAgent ? ( msg.author_name || 'Staff Agent' ) : 'AI Assistant' }</strong>
												{ msg.timestamp && <small>{ msg.timestamp }</small> }
											</div>
											<div className="dctc-ai-inbox-msg-text">
												<p>{ msg.content }</p>
											</div>
										</div>
									</div>
								);
							} ) }

							{ /* Internal Staff Notes inserted into thread */ }
							{ ( activeConv.internal_notes || [] ).map( ( note, nIdx ) => (
								<div key={ nIdx } className="dctc-ai-inbox-internal-note-card">
									<div className="dctc-ai-inbox-internal-note-header">
										<span>🔒 <strong>{ note.user_name }</strong> (Internal Team Note)</span>
										<small>{ note.created_at }</small>
									</div>
									<p>{ note.note }</p>
								</div>
							) ) }

							<div ref={ threadEndRef } />
						</div>

						{ /* Reply & Notes Composer */ }
						<footer className="dctc-ai-inbox-composer">
							<div className="dctc-ai-inbox-composer-modes">
								<button
									type="button"
									className={ `dctc-ai-inbox-mode-tab ${ composerMode === 'reply' ? 'is-active' : '' }` }
									onClick={ () => setComposerMode( 'reply' ) }
								>
									💬 { __( 'Reply as Human Agent', 'dragwyb-click-to-chat' ) }
								</button>
								<button
									type="button"
									className={ `dctc-ai-inbox-mode-tab ${ composerMode === 'note' ? 'is-active' : '' }` }
									onClick={ () => setComposerMode( 'note' ) }
								>
									🔒 { __( 'Add Internal Staff Note', 'dragwyb-click-to-chat' ) }
								</button>
							</div>

							<div className="dctc-ai-inbox-composer-box">
								<textarea
									rows={ 2 }
									value={ replyText }
									onChange={ ( e ) => setReplyText( e.target.value ) }
									onKeyDown={ ( e ) => {
										if ( e.key === 'Enter' && ! e.shiftKey ) {
											e.preventDefault();
											handleSend();
										}
									} }
									placeholder={
										composerMode === 'reply'
											? __( 'Type your human response to this visitor…', 'dragwyb-click-to-chat' )
											: __( 'Write a private internal note for team members (never visible to visitor)…', 'dragwyb-click-to-chat' )
									}
									disabled={ isSending }
								/>
								<button
									type="button"
									className="dctc-ai-btn dctc-ai-btn-primary"
									onClick={ handleSend }
									disabled={ ! replyText.trim() || isSending }
								>
									{ isSending
										? __( 'Sending…', 'dragwyb-click-to-chat' )
										: composerMode === 'reply'
										? __( 'Send Reply ➔', 'dragwyb-click-to-chat' )
										: __( 'Save Note ➔', 'dragwyb-click-to-chat' ) }
								</button>
							</div>
						</footer>
					</>
				)}
			</main>

			{ /* Right Pane: Visitor Profile & Context Sidebar */ }
			{ activeConv && (
				<aside className="dctc-ai-inbox-profile-sidebar">
					<div className="dctc-ai-inbox-profile-card">
						<div className="dctc-ai-inbox-profile-avatar">
							{ getChannelIcon( activeConv.channel ) }
						</div>
						<h4>{ activeConv.email || __( 'Guest Visitor', 'dragwyb-click-to-chat' ) }</h4>
						<span className="dctc-ai-inbox-profile-sub">
							{ activeConv.channel.toUpperCase() } • { activeConv.status }
						</span>
					</div>

					{ /* AI Sentiment & Intent Tags */ }
					<div className="dctc-ai-inbox-context-section">
						<h5>{ __( 'AI Intelligence & Sentiment', 'dragwyb-click-to-chat' ) }</h5>
						<div className="dctc-ai-inbox-tags-wrap">
							<span
								className="dctc-ai-inbox-tag"
								style={ { background: sentimentInfo.bg, color: sentimentInfo.color } }
							>
								{ sentimentInfo.label }
							</span>
							{ activeConv.intent_tag && (
								<span className="dctc-ai-inbox-tag" style={ { background: '#eff6ff', color: '#1e40af' } }>
									🏷️ { activeConv.intent_tag }
								</span>
							) }
						</div>
					</div>

					{ /* AI Conversation Summary */ }
					{ activeConv.summary && (
						<div className="dctc-ai-inbox-context-section">
							<h5>{ __( 'Conversation Summary', 'dragwyb-click-to-chat' ) }</h5>
							<div className="dctc-ai-inbox-summary-box">
								<p>{ activeConv.summary }</p>
							</div>
						</div>
					) }

					{ /* Linked Lead Information */ }
					{ activeConv.lead && (
						<div className="dctc-ai-inbox-context-section">
							<h5>{ __( 'Captured Sales Lead', 'dragwyb-click-to-chat' ) }</h5>
							<div className="dctc-ai-inbox-lead-details">
								<div className="dctc-ai-inbox-lead-row">
									<span>{ __( 'Score:', 'dragwyb-click-to-chat' ) }</span>
									<strong>⭐ { activeConv.lead.score } / 100 ({ activeConv.lead.intent_level })</strong>
								</div>
								{ activeConv.lead.phone && (
									<div className="dctc-ai-inbox-lead-row">
										<span>{ __( 'Phone:', 'dragwyb-click-to-chat' ) }</span>
										<a href={ `https://wa.me/${ activeConv.lead.phone.replace( /[^0-9]/g, '' ) }` } target="_blank" rel="noopener noreferrer">
											📱 { activeConv.lead.phone }
										</a>
									</div>
								) }
								{ activeConv.lead.budget && (
									<div className="dctc-ai-inbox-lead-row">
										<span>{ __( 'Budget:', 'dragwyb-click-to-chat' ) }</span>
										<span>{ activeConv.lead.budget }</span>
									</div>
								) }
								{ activeConv.lead.company && (
									<div className="dctc-ai-inbox-lead-row">
										<span>{ __( 'Company:', 'dragwyb-click-to-chat' ) }</span>
										<span>{ activeConv.lead.company }</span>
									</div>
								) }
							</div>
						</div>
					) }

					{ /* Quick Handoff Contact Actions */ }
					<div className="dctc-ai-inbox-context-section">
						<h5>{ __( 'Direct Channel Actions', 'dragwyb-click-to-chat' ) }</h5>
						<div className="dctc-ai-inbox-actions-list">
							{ activeConv.email && (
								<a
									href={ `mailto:${ activeConv.email }?subject=Follow-up regarding your inquiry` }
									className="dctc-ai-btn-secondary dctc-ai-btn--block"
								>
									✉️ { __( 'Send Direct Email', 'dragwyb-click-to-chat' ) }
								</a>
							) }
							{ activeConv.lead?.phone && (
								<a
									href={ `https://wa.me/${ activeConv.lead.phone.replace( /[^0-9]/g, '' ) }` }
									target="_blank"
									rel="noopener noreferrer"
									className="dctc-ai-btn-secondary dctc-ai-btn--block"
									style={ { color: '#059669' } }
								>
									📱 { __( 'Open in WhatsApp', 'dragwyb-click-to-chat' ) }
								</a>
							) }
							<button
								type="button"
								className="dctc-ai-btn-secondary dctc-ai-btn--block"
								onClick={ () => handleStatusChange( activeConv.status === 'resolved' ? 'active' : 'resolved' ) }
							>
								{ activeConv.status === 'resolved'
									? __( 'Reopen Conversation', 'dragwyb-click-to-chat' )
									: __( '✅ Mark as Resolved', 'dragwyb-click-to-chat' ) }
							</button>
						</div>
					</div>
				</aside>
			) }
		</div>
	);
}
