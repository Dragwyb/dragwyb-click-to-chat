/**
 * Support Center Admin Workspace
 *
 * Provides a 3-column ticket management workspace, conversation timeline,
 * Take Control / Handback engine, internal notes, categories, tags, and agent controls.
 */
import { useState, useEffect, useCallback, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

export default function SupportCenter( { onSaveSuccess } ) {
	const [ activeSubTab, setActiveSubTab ] = useState( 'tickets' );
	const [ tickets, setTickets ] = useState( [] );
	const [ totalTickets, setTotalTickets ] = useState( 0 );
	const [ currentPage, setCurrentPage ] = useState( 1 );
	const [ totalPages, setTotalPages ] = useState( 1 );
	const [ loading, setLoading ] = useState( false );
	const [ selectedTicketId, setSelectedTicketId ] = useState( null );
	const [ selectedTicket, setSelectedTicket ] = useState( null );
	const [ ticketLoading, setTicketLoading ] = useState( false );

	// Filters
	const [ statusFilter, setStatusFilter ] = useState( 'all' );
	const [ priorityFilter, setPriorityFilter ] = useState( 'all' );
	const [ categoryFilter, setCategoryFilter ] = useState( 'all' );
	const [ searchQuery, setSearchQuery ] = useState( '' );

	// Metadata
	const [ categories, setCategories ] = useState( [] );
	const [ tags, setTags ] = useState( [] );
	const [ agents, setAgents ] = useState( [] );
	const [ supportSettings, setSupportSettings ] = useState( {} );

	// Composer state
	const [ composerMode, setComposerMode ] = useState( 'reply' ); // 'reply' or 'note'
	const [ replyText, setReplyText ] = useState( '' );
	const [ noteText, setNoteText ] = useState( '' );
	const [ isPinnedNote, setIsPinnedNote ] = useState( false );
	const [ submitting, setSubmitting ] = useState( false );

	// WooCommerce & AI Assist state
	const [ wcData, setWcData ] = useState( null );
	const [ wcLoading, setWcLoading ] = useState( false );
	const [ aiSummaryLoading, setAiSummaryLoading ] = useState( false );
	const [ aiSuggestLoading, setAiSuggestLoading ] = useState( false );

	const timelineEndRef = useRef( null );

	const restNonce = window.dctc_ai_data?.nonce || '';
	const restBase = ( window.dctc_ai_data?.rest_url || '/wp-json/' ) + 'dctc-ai/v1/support';

	// Fetch Categories, Tags, Agents, Settings
	const fetchMetaData = useCallback( async () => {
		try {
			const [ catRes, tagRes, agentRes, setRes ] = await Promise.all( [
				fetch( `${ restBase }/categories`, { headers: { 'X-WP-Nonce': restNonce } } ),
				fetch( `${ restBase }/tags`, { headers: { 'X-WP-Nonce': restNonce } } ),
				fetch( `${ restBase }/agents`, { headers: { 'X-WP-Nonce': restNonce } } ),
				fetch( `${ restBase }/settings`, { headers: { 'X-WP-Nonce': restNonce } } ),
			] );

			const catData = await catRes.json();
			const tagData = await tagRes.json();
			const agentData = await agentRes.json();
			const setData = await setRes.json();

			if ( catData.success ) setCategories( catData.categories || [] );
			if ( tagData.success ) setTags( tagData.tags || [] );
			if ( agentData.success ) setAgents( agentData.agents || [] );
			if ( setData.success ) setSupportSettings( setData.settings || {} );
		} catch ( err ) {
			console.error( 'Error fetching support metadata:', err );
		}
	}, [ restBase, restNonce ] );

	// Fetch Tickets List
	const fetchTickets = useCallback( async () => {
		setLoading( true );
		try {
			const queryParams = new URLSearchParams( {
				page: currentPage,
				per_page: 20,
				status: statusFilter,
				priority: priorityFilter,
				category_id: categoryFilter !== 'all' ? categoryFilter : '',
				search: searchQuery,
			} );

			const res = await fetch( `${ restBase }/tickets?${ queryParams.toString() }`, {
				headers: { 'X-WP-Nonce': restNonce },
			} );
			const data = await res.json();
			if ( data.success ) {
				setTickets( data.tickets || [] );
				setTotalTickets( data.total || 0 );
				setTotalPages( data.total_pages || 1 );

				// Auto-select first ticket if none selected
				if ( ! selectedTicketId && data.tickets?.length > 0 ) {
					setSelectedTicketId( data.tickets[ 0 ].id );
				}
			}
		} catch ( err ) {
			console.error( 'Error fetching tickets:', err );
		} finally {
			setLoading( false );
		}
	}, [ currentPage, statusFilter, priorityFilter, categoryFilter, searchQuery, restBase, restNonce, selectedTicketId ] );

	// Fetch WooCommerce Context
	const fetchWooCommerceContext = useCallback( async ( ticketId ) => {
		if ( ! ticketId ) return;
		setWcLoading( true );
		try {
			const res = await fetch( `${ restBase }/tickets/${ ticketId }/woocommerce`, {
				headers: { 'X-WP-Nonce': restNonce },
			} );
			const data = await res.json();
			if ( data.success ) {
				setWcData( data.woocommerce || null );
			}
		} catch ( err ) {
			console.error( 'Error fetching WooCommerce context:', err );
			setWcData( null );
		} finally {
			setWcLoading( false );
		}
	}, [ restBase, restNonce ] );

	// Fetch Single Ticket Details
	const fetchTicketDetails = useCallback( async ( ticketId ) => {
		if ( ! ticketId ) return;
		setTicketLoading( true );
		try {
			const res = await fetch( `${ restBase }/tickets/${ ticketId }`, {
				headers: { 'X-WP-Nonce': restNonce },
			} );
			const data = await res.json();
			if ( data.success && data.ticket ) {
				setSelectedTicket( data.ticket );
			}
		} catch ( err ) {
			console.error( 'Error fetching ticket detail:', err );
		} finally {
			setTicketLoading( false );
		}
	}, [ restBase, restNonce ] );

	useEffect( () => {
		fetchMetaData();
	}, [ fetchMetaData ] );

	useEffect( () => {
		fetchTickets();
	}, [ fetchTickets ] );

	useEffect( () => {
		if ( selectedTicketId ) {
			fetchTicketDetails( selectedTicketId );
			fetchWooCommerceContext( selectedTicketId );
		}
	}, [ selectedTicketId, fetchTicketDetails, fetchWooCommerceContext ] );

	// Scroll timeline to bottom when messages update
	useEffect( () => {
		if ( timelineEndRef.current ) {
			timelineEndRef.current.scrollIntoView( { behavior: 'smooth' } );
		}
	}, [ selectedTicket?.messages, selectedTicket?.events ] );

	// Action: Take Control / Release Control
	const handleToggleControl = async () => {
		if ( ! selectedTicket ) return;
		const isHuman = selectedTicket.control_mode === 'human';
		const endpoint = isHuman ? 'release-control' : 'take-control';

		try {
			const res = await fetch( `${ restBase }/tickets/${ selectedTicket.id }/${ endpoint }`, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': restNonce,
				},
			} );
			const data = await res.json();
			if ( data.success ) {
				setSelectedTicket( ( prev ) => ( {
					...prev,
					control_mode: data.control_mode,
				} ) );
				fetchTickets();
				fetchTicketDetails( selectedTicket.id );
			}
		} catch ( err ) {
			console.error( 'Error toggling control:', err );
		}
	};

	// Action: Update Status
	const handleStatusChange = async ( newStatus ) => {
		if ( ! selectedTicket ) return;
		try {
			const res = await fetch( `${ restBase }/tickets/${ selectedTicket.id }/status`, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': restNonce,
				},
				body: JSON.stringify( { status: newStatus } ),
			} );
			const data = await res.json();
			if ( data.success ) {
				fetchTicketDetails( selectedTicket.id );
				fetchTickets();
			}
		} catch ( err ) {
			console.error( 'Error changing status:', err );
		}
	};

	// Action: Update Priority
	const handlePriorityChange = async ( newPriority ) => {
		if ( ! selectedTicket ) return;
		try {
			const res = await fetch( `${ restBase }/tickets/${ selectedTicket.id }/priority`, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': restNonce,
				},
				body: JSON.stringify( { priority: newPriority } ),
			} );
			const data = await res.json();
			if ( data.success ) {
				fetchTicketDetails( selectedTicket.id );
				fetchTickets();
			}
		} catch ( err ) {
			console.error( 'Error changing priority:', err );
		}
	};

	// Action: Assign Agent
	const handleAssignAgent = async ( agentId ) => {
		if ( ! selectedTicket ) return;
		try {
			const res = await fetch( `${ restBase }/tickets/${ selectedTicket.id }/assign`, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': restNonce,
				},
				body: JSON.stringify( { agent_id: agentId } ),
			} );
			const data = await res.json();
			if ( data.success ) {
				fetchTicketDetails( selectedTicket.id );
				fetchTickets();
			}
		} catch ( err ) {
			console.error( 'Error assigning agent:', err );
		}
	};

	// Action: Send Reply
	const handleSendReply = async ( e ) => {
		e.preventDefault();
		if ( ! selectedTicket || ! replyText.trim() ) return;
		setSubmitting( true );
		try {
			const res = await fetch( `${ restBase }/tickets/${ selectedTicket.id }/reply`, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': restNonce,
				},
				body: JSON.stringify( { message: replyText } ),
			} );
			const data = await res.json();
			if ( data.success ) {
				setReplyText( '' );
				fetchTicketDetails( selectedTicket.id );
				fetchTickets();
			}
		} catch ( err ) {
			console.error( 'Error sending reply:', err );
		} finally {
			setSubmitting( false );
		}
	};

	// Action: Add Internal Note
	const handleAddNote = async ( e ) => {
		e.preventDefault();
		if ( ! selectedTicket || ! noteText.trim() ) return;
		setSubmitting( true );
		try {
			const res = await fetch( `${ restBase }/tickets/${ selectedTicket.id }/note`, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': restNonce,
				},
				body: JSON.stringify( { content: noteText, is_pinned: isPinnedNote } ),
			} );
			const data = await res.json();
			if ( data.success ) {
				setNoteText( '' );
				setIsPinnedNote( false );
				fetchTicketDetails( selectedTicket.id );
			}
		} catch ( err ) {
			console.error( 'Error adding note:', err );
		} finally {
			setSubmitting( false );
		}
	};

	// Action: Generate AI Summary
	const handleGenerateAiSummary = async () => {
		if ( ! selectedTicket ) return;
		setAiSummaryLoading( true );
		try {
			const res = await fetch( `${ restBase }/tickets/${ selectedTicket.id }/ai-summary`, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': restNonce,
				},
			} );
			const data = await res.json();
			if ( data.success && data.ai_summary ) {
				setSelectedTicket( ( prev ) => ( {
					...prev,
					ai_summary: data.ai_summary,
				} ) );
				fetchTicketDetails( selectedTicket.id );
			}
		} catch ( err ) {
			console.error( 'Error generating AI summary:', err );
		} finally {
			setAiSummaryLoading( false );
		}
	};

	// Action: AI Suggest Reply
	const handleSuggestAiReply = async () => {
		if ( ! selectedTicket ) return;
		setAiSuggestLoading( true );
		try {
			const res = await fetch( `${ restBase }/tickets/${ selectedTicket.id }/ai-suggest-reply`, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': restNonce,
				},
			} );
			const data = await res.json();
			if ( data.success && data.suggested_reply ) {
				setReplyText( data.suggested_reply );
				setComposerMode( 'reply' );
			}
		} catch ( err ) {
			console.error( 'Error generating reply suggestion:', err );
		} finally {
			setAiSuggestLoading( false );
		}
	};

	// Helper: Priority color
	const getPriorityBadgeClass = ( priority ) => {
		switch ( priority ) {
			case 'urgent': return 'dctc-badge-urgent';
			case 'high': return 'dctc-badge-high';
			case 'normal': return 'dctc-badge-normal';
			default: return 'dctc-badge-low';
		}
	};

	// Helper: Status color
	const getStatusBadgeClass = ( status ) => {
		switch ( status ) {
			case 'open': return 'dctc-badge-open';
			case 'pending': return 'dctc-badge-pending';
			case 'waiting_customer': return 'dctc-badge-waiting';
			case 'resolved': return 'dctc-badge-resolved';
			case 'closed': return 'dctc-badge-closed';
			default: return 'dctc-badge-secondary';
		}
	};

	return (
		<div className="dctc-support-center-wrapper">
			{ /* Sub-navigation Bar */ }
			<div className="dctc-support-subnav">
				<div className="dctc-support-subnav-links">
					<button
						type="button"
						className={ `dctc-subnav-btn ${ activeSubTab === 'tickets' ? 'active' : '' }` }
						onClick={ () => setActiveSubTab( 'tickets' ) }
					>
						<span className="dashicons dashicons-tickets-alt"></span>
						{ __( 'Tickets Workspace', 'dragwyb-click-to-chat' ) }
						{ totalTickets > 0 && <span className="dctc-subnav-count">{ totalTickets }</span> }
					</button>
					<button
						type="button"
						className={ `dctc-subnav-btn ${ activeSubTab === 'agents' ? 'active' : '' }` }
						onClick={ () => setActiveSubTab( 'agents' ) }
					>
						<span className="dashicons dashicons-groups"></span>
						{ __( 'Agents & Staff', 'dragwyb-click-to-chat' ) }
					</button>
					<button
						type="button"
						className={ `dctc-subnav-btn ${ activeSubTab === 'categories' ? 'active' : '' }` }
						onClick={ () => setActiveSubTab( 'categories' ) }
					>
						<span className="dashicons dashicons-category"></span>
						{ __( 'Categories & Skills', 'dragwyb-click-to-chat' ) }
					</button>
					<button
						type="button"
						className={ `dctc-subnav-btn ${ activeSubTab === 'tags' ? 'active' : '' }` }
						onClick={ () => setActiveSubTab( 'tags' ) }
					>
						<span className="dashicons dashicons-tag"></span>
						{ __( 'Tags', 'dragwyb-click-to-chat' ) }
					</button>
					<button
						type="button"
						className={ `dctc-subnav-btn ${ activeSubTab === 'settings' ? 'active' : '' }` }
						onClick={ () => setActiveSubTab( 'settings' ) }
					>
						<span className="dashicons dashicons-admin-generic"></span>
						{ __( 'Support Settings', 'dragwyb-click-to-chat' ) }
					</button>
				</div>
			</div>

			{ /* View: Tickets Workspace (3-Column Layout) */ }
			{ activeSubTab === 'tickets' && (
				<div className="dctc-support-workspace-grid">
					{ /* Column 1: Ticket Filters & Ticket List */ }
					<div className="dctc-support-col-list">
						{ /* Status Filter Tabs */ }
						<div className="dctc-ticket-status-pills">
							{ [
								{ id: 'all', label: __( 'All', 'dragwyb-click-to-chat' ) },
								{ id: 'open', label: __( 'Open', 'dragwyb-click-to-chat' ) },
								{ id: 'pending', label: __( 'Pending', 'dragwyb-click-to-chat' ) },
								{ id: 'resolved', label: __( 'Resolved', 'dragwyb-click-to-chat' ) },
								{ id: 'closed', label: __( 'Closed', 'dragwyb-click-to-chat' ) },
							].map( ( tab ) => (
								<button
									key={ tab.id }
									type="button"
									className={ `dctc-pill ${ statusFilter === tab.id ? 'active' : '' }` }
									onClick={ () => { setStatusFilter( tab.id ); setCurrentPage( 1 ); } }
								>
									{ tab.label }
								</button>
							) ) }
						</div>

						{ /* Search & Priority Dropdown */ }
						<div className="dctc-ticket-search-bar">
							<input
								type="text"
								placeholder={ __( 'Search tickets, email, subject...', 'dragwyb-click-to-chat' ) }
								value={ searchQuery }
								onChange={ ( e ) => setSearchQuery( e.target.value ) }
								className="dctc-search-input"
							/>
						</div>

						{ /* Ticket List Cards */ }
						<div className="dctc-ticket-scroll-list">
							{ loading ? (
								<div className="dctc-loading-state">
									<span className="spinner is-active"></span>
									{ __( 'Loading tickets...', 'dragwyb-click-to-chat' ) }
								</div>
							) : tickets.length === 0 ? (
								<div className="dctc-empty-state">
									<span className="dashicons dashicons-clipboard"></span>
									<p>{ __( 'No support tickets found.', 'dragwyb-click-to-chat' ) }</p>
								</div>
							) : (
								tickets.map( ( item ) => (
									<div
										key={ item.id }
										className={ `dctc-ticket-card ${ selectedTicketId === item.id ? 'selected' : '' }` }
										onClick={ () => setSelectedTicketId( item.id ) }
									>
										<div className="dctc-ticket-card-header">
											<span className="dctc-ticket-number">#{ item.ticket_number }</span>
											<span className={ `dctc-badge ${ getPriorityBadgeClass( item.priority ) }` }>
												{ item.priority }
											</span>
											<span className={ `dctc-badge ${ getStatusBadgeClass( item.status ) }` }>
												{ item.status }
											</span>
										</div>
										<h4 className="dctc-ticket-card-title">{ item.subject }</h4>
										<div className="dctc-ticket-card-footer">
											<span className="dctc-ticket-customer">
												<span className="dashicons dashicons-admin-users"></span>
												{ item.customer_name || item.customer_email || 'Visitor' }
											</span>
											<span className={ `dctc-control-badge ${ item.control_mode === 'human' ? 'human' : 'ai' }` }>
												{ item.control_mode === 'human' ? '👤 Human' : '🤖 AI' }
											</span>
										</div>
									</div>
								) )
							) }
						</div>

						{ /* Pagination */ }
						{ totalPages > 1 && (
							<div className="dctc-pagination-bar">
								<button
									disabled={ currentPage <= 1 }
									onClick={ () => setCurrentPage( ( p ) => Math.max( 1, p - 1 ) ) }
									className="button button-small"
								>
									«
								</button>
								<span>{ currentPage } / { totalPages }</span>
								<button
									disabled={ currentPage >= totalPages }
									onClick={ () => setCurrentPage( ( p ) => Math.min( totalPages, p + 1 ) ) }
									className="button button-small"
								>
									»
								</button>
							</div>
						) }
					</div>

					{ /* Column 2: Ticket Workspace & Conversation Timeline */ }
					<div className="dctc-support-col-main">
						{ ticketLoading ? (
							<div className="dctc-main-loading">
								<span className="spinner is-active"></span>
								<p>{ __( 'Loading ticket details...', 'dragwyb-click-to-chat' ) }</p>
							</div>
						) : ! selectedTicket ? (
							<div className="dctc-no-selection">
								<span className="dashicons dashicons-format-chat"></span>
								<h3>{ __( 'Select a ticket to begin support', 'dragwyb-click-to-chat' ) }</h3>
							</div>
						) : (
							<div className="dctc-ticket-workspace">
								{ /* Ticket Header Bar */ }
								<div className="dctc-ticket-main-header">
									<div className="dctc-header-left">
										<div className="dctc-header-badges">
											<span className="dctc-ticket-large-num">#{ selectedTicket.ticket_number }</span>
											<span className={ `dctc-badge ${ getStatusBadgeClass( selectedTicket.status ) }` }>
												{ selectedTicket.status }
											</span>
											<span className={ `dctc-badge ${ getPriorityBadgeClass( selectedTicket.priority ) }` }>
												{ selectedTicket.priority }
											</span>
											<span className="dctc-surface-tag">
												{ selectedTicket.reply_surface === 'chatbot_widget' ? '💬 Chatbot Widget' : '🌐 Support Portal' }
											</span>
										</div>
										<h2 className="dctc-ticket-main-title">{ selectedTicket.subject }</h2>
									</div>

									<div className="dctc-header-actions">
										{ /* Take Control / Release to AI Button */ }
										<button
											type="button"
											className={ `dctc-control-action-btn ${ selectedTicket.control_mode === 'human' ? 'btn-release-ai' : 'btn-take-control' }` }
											onClick={ handleToggleControl }
										>
											{ selectedTicket.control_mode === 'human' ? (
												<>
													<span className="dashicons dashicons-controls-play"></span>
													{ __( 'Give Control to AI', 'dragwyb-click-to-chat' ) }
												</>
											) : (
												<>
													<span className="dashicons dashicons-controls-pause"></span>
													{ __( 'Take Control (Pause AI)', 'dragwyb-click-to-chat' ) }
												</>
											) }
										</button>
									</div>
								</div>

								{ /* Status Bar Quick Actions */ }
								<div className="dctc-quick-status-bar">
									<div className="dctc-quick-control">
										<label>{ __( 'Status:', 'dragwyb-click-to-chat' ) }</label>
										<select
											value={ selectedTicket.status }
											onChange={ ( e ) => handleStatusChange( e.target.value ) }
										>
											<option value="open">{ __( 'Open', 'dragwyb-click-to-chat' ) }</option>
											<option value="pending">{ __( 'Pending', 'dragwyb-click-to-chat' ) }</option>
											<option value="waiting_customer">{ __( 'Waiting Customer', 'dragwyb-click-to-chat' ) }</option>
											<option value="resolved">{ __( 'Resolved', 'dragwyb-click-to-chat' ) }</option>
											<option value="closed">{ __( 'Closed', 'dragwyb-click-to-chat' ) }</option>
											<option value="trash">{ __( 'Move to Trash', 'dragwyb-click-to-chat' ) }</option>
										</select>
									</div>

									<div className="dctc-quick-control">
										<label>{ __( 'Priority:', 'dragwyb-click-to-chat' ) }</label>
										<select
											value={ selectedTicket.priority }
											onChange={ ( e ) => handlePriorityChange( e.target.value ) }
										>
											<option value="low">{ __( 'Low', 'dragwyb-click-to-chat' ) }</option>
											<option value="normal">{ __( 'Normal', 'dragwyb-click-to-chat' ) }</option>
											<option value="high">{ __( 'High', 'dragwyb-click-to-chat' ) }</option>
											<option value="urgent">{ __( 'Urgent', 'dragwyb-click-to-chat' ) }</option>
										</select>
									</div>

									<div className="dctc-quick-control">
										<label>{ __( 'Assign Agent:', 'dragwyb-click-to-chat' ) }</label>
										<select
											value={ selectedTicket.assigned_agent_id || 0 }
											onChange={ ( e ) => handleAssignAgent( Number( e.target.value ) ) }
										>
											<option value="0">{ __( 'Unassigned', 'dragwyb-click-to-chat' ) }</option>
											{ agents.map( ( ag ) => (
												<option key={ ag.id } value={ ag.id }>
													{ ag.display_name } ({ ag.support_role })
												</option>
											) ) }
										</select>
									</div>
								</div>

								{ /* Conversation Timeline */ }
								<div className="dctc-timeline-container">
									{ ( selectedTicket.messages || [] ).map( ( msg, idx ) => {
										const isUser = msg.role === 'user' || msg.sender_type === 'customer';
										const isAi = ( msg.role === 'assistant' || msg.sender_type === 'ai' ) && ! msg.sender_name?.includes( 'Agent' );
										const isAgent = msg.sender_type === 'agent' || ( msg.role === 'assistant' && msg.sender_name?.includes( 'Agent' ) );

										return (
											<div
												key={ idx }
												className={ `dctc-timeline-msg ${ isUser ? 'msg-customer' : isAgent ? 'msg-agent' : 'msg-ai' }` }
											>
												<div className="dctc-msg-bubble-header">
													<span className="dctc-msg-sender">
														{ isUser ? '👤 ' + ( msg.sender_name || 'Customer' ) : isAgent ? '🧑‍💼 ' + ( msg.sender_name || 'Support Agent' ) : '🤖 AI Assistant' }
													</span>
													<span className="dctc-msg-time">{ msg.created_at || '' }</span>
												</div>
												<div className="dctc-msg-bubble-content">
													{ msg.content }
												</div>
											</div>
										);
									} ) }

									{ /* Timeline Audit Events */ }
									{ ( selectedTicket.events || [] ).map( ( evt, idx ) => (
										<div key={ `evt-${ idx }` } className="dctc-timeline-event-row">
											<span className="dctc-event-icon dashicons dashicons-marker"></span>
											<span className="dctc-event-text">
												<strong>{ evt.actor_name }</strong>: { evt.event_type.replace( '_', ' ' ) }
												{ evt.new_value ? ` → ${ evt.new_value }` : '' }
											</span>
											<span className="dctc-event-time">{ evt.created_at }</span>
										</div>
									) ) }
									<div ref={ timelineEndRef } />
								</div>

								{ /* Composer Modes: Reply vs Internal Note */ }
								<div className="dctc-composer-box">
									<div className="dctc-composer-tabs">
										<button
											type="button"
											className={ `dctc-comp-tab ${ composerMode === 'reply' ? 'active' : '' }` }
											onClick={ () => setComposerMode( 'reply' ) }
										>
											<span className="dashicons dashicons-admin-comments"></span>
											{ __( 'Reply to Customer', 'dragwyb-click-to-chat' ) }
										</button>
										<button
											type="button"
											className={ `dctc-comp-tab dctc-note-tab ${ composerMode === 'note' ? 'active' : '' }` }
											onClick={ () => setComposerMode( 'note' ) }
										>
											<span className="dashicons dashicons-lock"></span>
											{ __( 'Internal Staff Note', 'dragwyb-click-to-chat' ) }
										</button>
										<button
											type="button"
											className="dctc-ai-assist-btn"
											onClick={ handleSuggestAiReply }
											disabled={ aiSuggestLoading }
											title={ __( 'Draft a reply using AI based on the conversation context', 'dragwyb-click-to-chat' ) }
											style={ { marginLeft: 'auto' } }
										>
											<span className="dashicons dashicons-superhero"></span>
											{ aiSuggestLoading ? __( 'Thinking...', 'dragwyb-click-to-chat' ) : __( '✨ Suggest AI Reply', 'dragwyb-click-to-chat' ) }
										</button>
									</div>

									{ composerMode === 'reply' ? (
										<form onSubmit={ handleSendReply } className="dctc-composer-form">
											<textarea
												rows="3"
												placeholder={ __( 'Write a response to the customer...', 'dragwyb-click-to-chat' ) }
												value={ replyText }
												onChange={ ( e ) => setReplyText( e.target.value ) }
												className="dctc-composer-textarea"
											/>
											<div className="dctc-composer-actions">
												<span className="dctc-composer-hint">
													{ selectedTicket.control_mode === 'ai'
														? __( '⚠️ AI is currently active. Replying will pause AI automatically.', 'dragwyb-click-to-chat' )
														: __( '✓ Message will be delivered directly to the customer session.', 'dragwyb-click-to-chat' ) }
												</span>
												<button
													type="submit"
													disabled={ submitting || ! replyText.trim() }
													className="button button-primary dctc-send-btn"
												>
													{ submitting ? __( 'Sending...', 'dragwyb-click-to-chat' ) : __( 'Send Reply', 'dragwyb-click-to-chat' ) }
												</button>
											</div>
										</form>
									) : (
										<form onSubmit={ handleAddNote } className="dctc-composer-form dctc-note-form">
											<textarea
												rows="3"
												placeholder={ __( 'Add a private note visible only to support staff...', 'dragwyb-click-to-chat' ) }
												value={ noteText }
												onChange={ ( e ) => setNoteText( e.target.value ) }
												className="dctc-composer-textarea dctc-note-textarea"
											/>
											<div className="dctc-composer-actions">
												<label className="dctc-pinned-checkbox">
													<input
														type="checkbox"
														checked={ isPinnedNote }
														onChange={ ( e ) => setIsPinnedNote( e.target.checked ) }
													/>
													{ __( 'Pin note to top', 'dragwyb-click-to-chat' ) }
												</label>
												<button
													type="submit"
													disabled={ submitting || ! noteText.trim() }
													className="button dctc-note-btn"
												>
													{ submitting ? __( 'Saving...', 'dragwyb-click-to-chat' ) : __( 'Save Internal Note', 'dragwyb-click-to-chat' ) }
												</button>
											</div>
										</form>
									) }
								</div>
							</div>
						) }
					</div>

					{ /* Column 3: Customer Context & Ticket Sidebar */ }
					<div className="dctc-support-col-meta">
						{ selectedTicket && (
							<div className="dctc-meta-panels">
								{ /* Customer Card */ }
								<div className="dctc-meta-card">
									<h4 className="dctc-meta-card-title">
										<span className="dashicons dashicons-admin-users"></span>
										{ __( 'Customer Profile', 'dragwyb-click-to-chat' ) }
									</h4>
									<div className="dctc-meta-field">
										<label>{ __( 'Name:', 'dragwyb-click-to-chat' ) }</label>
										<span>{ selectedTicket.customer_name || 'Visitor' }</span>
									</div>
									<div className="dctc-meta-field">
										<label>{ __( 'Email:', 'dragwyb-click-to-chat' ) }</label>
										<span>{ selectedTicket.customer_email || '—' }</span>
									</div>
									<div className="dctc-meta-field">
										<label>{ __( 'Origin:', 'dragwyb-click-to-chat' ) }</label>
										<span>{ selectedTicket.origin_type }</span>
									</div>
								</div>

								{ /* AI Summary & Assistance */ }
								<div className="dctc-meta-card dctc-ai-summary-card">
									<div className="dctc-meta-card-title" style={ { display: 'flex', justifyContent: 'space-between', alignItems: 'center' } }>
										<span style={ { display: 'flex', alignItems: 'center', gap: '6px' } }>
											<span className="dashicons dashicons-superhero"></span>
											{ __( 'AI Summary', 'dragwyb-click-to-chat' ) }
										</span>
										<button
											type="button"
											className="dctc-ai-assist-btn"
											onClick={ handleGenerateAiSummary }
											disabled={ aiSummaryLoading }
											style={ { fontSize: '10px', padding: '2px 8px' } }
										>
											{ aiSummaryLoading ? __( 'Generating...', 'dragwyb-click-to-chat' ) : selectedTicket.ai_summary ? __( '🔄 Refresh', 'dragwyb-click-to-chat' ) : __( '✨ Summarize', 'dragwyb-click-to-chat' ) }
										</button>
									</div>
									{ selectedTicket.ai_summary ? (
										<p className="dctc-ai-summary-text">{ selectedTicket.ai_summary }</p>
									) : (
										<p className="dctc-empty-hint" style={ { margin: 0, fontSize: '11px', color: '#6d28d9' } }>
											{ __( 'Click Summarize to generate an instant AI ticket overview.', 'dragwyb-click-to-chat' ) }
										</p>
									) }
								</div>

								{ /* WooCommerce Order History */ }
								{ wcData && wcData.is_active && (
									<div className="dctc-meta-card dctc-wc-card">
										<h4 className="dctc-meta-card-title">
											<span className="dashicons dashicons-cart"></span>
											{ __( 'WooCommerce Orders', 'dragwyb-click-to-chat' ) }
										</h4>
										<div className="dctc-wc-header-stats">
											<div className="dctc-wc-stat-badge">
												<span className="stat-label">{ __( 'Total Spent', 'dragwyb-click-to-chat' ) }</span>
												<span className="stat-value" dangerouslySetInnerHTML={ { __html: wcData.total_spent || '$0' } } />
											</div>
											<div className="dctc-wc-stat-badge">
												<span className="stat-label">{ __( 'Lifetime Orders', 'dragwyb-click-to-chat' ) }</span>
												<span className="stat-value">{ wcData.order_count || 0 }</span>
											</div>
										</div>
										<div className="dctc-wc-orders-list">
											{ ( wcData.recent_orders || [] ).length === 0 ? (
												<p className="dctc-empty-hint">{ __( 'No past orders found.', 'dragwyb-click-to-chat' ) }</p>
											) : (
												wcData.recent_orders.map( ( order ) => (
													<div key={ order.id } className="dctc-wc-order-item">
														<div className="dctc-wc-order-top">
															<a href={ order.view_url } target="_blank" rel="noreferrer" className="dctc-wc-order-num">
																#{ order.number } ↗
															</a>
															<span className="dctc-wc-order-status">{ order.status_name }</span>
														</div>
														<div className="dctc-wc-order-meta">
															<span>{ order.date }</span>
															<strong dangerouslySetInnerHTML={ { __html: order.total } } />
														</div>
														{ order.items_summary && (
															<div className="dctc-wc-order-items" title={ order.items_summary }>
																{ order.items_summary }
															</div>
														) }
													</div>
												) )
											) }
										</div>
									</div>
								) }

								{ /* Internal Notes List */ }
								<div className="dctc-meta-card">
									<h4 className="dctc-meta-card-title">
										<span className="dashicons dashicons-lock"></span>
										{ __( 'Internal Notes', 'dragwyb-click-to-chat' ) }
										<span className="dctc-badge dctc-badge-secondary">
											{ selectedTicket.notes?.length || 0 }
										</span>
									</h4>
									<div className="dctc-notes-list">
										{ ( selectedTicket.notes || [] ).length === 0 ? (
											<p className="dctc-empty-hint">{ __( 'No internal notes yet.', 'dragwyb-click-to-chat' ) }</p>
										) : (
											selectedTicket.notes.map( ( note ) => (
												<div
													key={ note.id }
													className={ `dctc-note-item ${ note.is_pinned ? 'pinned' : '' }` }
												>
													<div className="dctc-note-header">
														<strong>{ note.agent_name }</strong>
														<span>{ note.created_at }</span>
													</div>
													<p>{ note.content }</p>
												</div>
											) )
										) }
									</div>
								</div>
							</div>
						) }
					</div>
				</div>
			) }

			{ /* View: Agents Management */ }
			{ activeSubTab === 'agents' && (
				<div className="dctc-support-panel-box">
					<div className="dctc-panel-header">
						<h3>{ __( 'Support Agents & Workload', 'dragwyb-click-to-chat' ) }</h3>
					</div>
					<table className="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th>{ __( 'Agent Name', 'dragwyb-click-to-chat' ) }</th>
								<th>{ __( 'Role', 'dragwyb-click-to-chat' ) }</th>
								<th>{ __( 'Seniority', 'dragwyb-click-to-chat' ) }</th>
								<th>{ __( 'Availability', 'dragwyb-click-to-chat' ) }</th>
								<th>{ __( 'Active Workload', 'dragwyb-click-to-chat' ) }</th>
								<th>{ __( 'Skills', 'dragwyb-click-to-chat' ) }</th>
							</tr>
						</thead>
						<tbody>
							{ agents.map( ( agent ) => (
								<tr key={ agent.id }>
									<td>
										<strong>{ agent.display_name }</strong>
										<br />
										<small>{ agent.user_email }</small>
									</td>
									<td><span className="dctc-badge">{ agent.support_role }</span></td>
									<td>{ agent.seniority }</td>
									<td>
										<span className={ `dctc-status-indicator ${ agent.availability_status }` }></span>
										{ agent.availability_status }
									</td>
									<td>{ agent.current_active_tickets } / { agent.max_active_tickets }</td>
									<td>
										{ ( agent.skills || [] ).map( ( s ) => (
											<span key={ s } className="dctc-skill-tag">{ s }</span>
										) ) }
									</td>
								</tr>
							) ) }
						</tbody>
					</table>
				</div>
			) }

			{ /* View: Categories */ }
			{ activeSubTab === 'categories' && (
				<div className="dctc-support-panel-box">
					<div className="dctc-panel-header">
						<h3>{ __( 'Support Categories & Skill Requirements', 'dragwyb-click-to-chat' ) }</h3>
					</div>
					<table className="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th>{ __( 'Category Name', 'dragwyb-click-to-chat' ) }</th>
								<th>{ __( 'Slug', 'dragwyb-click-to-chat' ) }</th>
								<th>{ __( 'Default Priority', 'dragwyb-click-to-chat' ) }</th>
								<th>{ __( 'Required Skills', 'dragwyb-click-to-chat' ) }</th>
								<th>{ __( 'AI Allowed', 'dragwyb-click-to-chat' ) }</th>
							</tr>
						</thead>
						<tbody>
							{ categories.map( ( cat ) => (
								<tr key={ cat.id }>
									<td><strong>{ cat.name }</strong></td>
									<td><code>{ cat.slug }</code></td>
									<td><span className={ `dctc-badge ${ getPriorityBadgeClass( cat.default_priority ) }` }>{ cat.default_priority }</span></td>
									<td>
										{ ( cat.required_skills || [] ).map( ( s ) => (
											<span key={ s } className="dctc-skill-tag">{ s }</span>
										) ) }
									</td>
									<td>{ cat.ai_allowed ? '✅ Yes' : '❌ Human Only' }</td>
								</tr>
							) ) }
						</tbody>
					</table>
				</div>
			) }

			{ /* View: Tags */ }
			{ activeSubTab === 'tags' && (
				<div className="dctc-support-panel-box">
					<div className="dctc-panel-header">
						<h3>{ __( 'Support Tags Taxonomy', 'dragwyb-click-to-chat' ) }</h3>
					</div>
					<div className="dctc-tags-grid">
						{ tags.map( ( tag ) => (
							<div key={ tag.id } className="dctc-tag-pill" style={ { borderColor: tag.color } }>
								<span className="dctc-tag-dot" style={ { backgroundColor: tag.color } }></span>
								<strong>{ tag.name }</strong>
								<code>{ tag.slug }</code>
							</div>
						) ) }
					</div>
				</div>
			) }

			{ /* View: Support Settings */ }
			{ activeSubTab === 'settings' && (
				<div className="dctc-support-panel-box">
					<div className="dctc-panel-header">
						<h3>{ __( 'Support Center Configuration', 'dragwyb-click-to-chat' ) }</h3>
					</div>
					<div className="dctc-settings-grid">
						<div className="dctc-setting-row">
							<label>{ __( 'Support System Enabled', 'dragwyb-click-to-chat' ) }</label>
							<span>{ supportSettings.enabled ? '✅ Active' : '❌ Disabled' }</span>
						</div>
						<div className="dctc-setting-row">
							<label>{ __( 'Automatic Assignment Algorithm', 'dragwyb-click-to-chat' ) }</label>
							<span><code>{ supportSettings.assignment_algorithm || 'least_loaded' }</code></span>
						</div>
						<div className="dctc-setting-row">
							<label>{ __( 'Auto-Pause AI on Ticket Creation', 'dragwyb-click-to-chat' ) }</label>
							<span>{ supportSettings.auto_pause_ai ? '✅ Enabled (Human Takeover)' : '❌ Disabled' }</span>
						</div>
					</div>
				</div>
			) }
		</div>
	);
}
