/**
 * Support Center - Tickets Workspace View (3-Column Layout)
 */
import { useState, useRef, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

export default function TicketsView( {
	tickets,
	totalTickets,
	loading,
	currentPage,
	totalPages,
	setCurrentPage,
	statusFilter,
	setStatusFilter,
	priorityFilter,
	setPriorityFilter,
	categoryFilter,
	setCategoryFilter,
	searchQuery,
	setSearchQuery,
	selectedTicketId,
	setSelectedTicketId,
	selectedTicket,
	ticketLoading,
	categories,
	agents,
	tags,
	wcData,
	wcLoading,
	onRefreshTickets,
	onRefreshTicketDetails,
	onShowNotice,
	userPermissions = {},
} ) {
	const canAssign = !! ( userPermissions.is_admin || userPermissions.assign_ticket || userPermissions.reassign_ticket );
	const canChangePriority = !! ( userPermissions.is_admin || userPermissions.change_priority );
	const canChangeStatus = !! ( userPermissions.is_admin || userPermissions.change_status !== false );
	const canTakeControl = !! ( userPermissions.is_admin || userPermissions.take_ai_control || userPermissions.release_ai_control );
	const canInternalNote = !! ( userPermissions.is_admin || userPermissions.internal_note !== false );

	const [ composerMode, setComposerMode ] = useState( 'reply' );
	const [ replyText, setReplyText ] = useState( '' );
	const [ noteText, setNoteText ] = useState( '' );
	const [ isPinnedNote, setIsPinnedNote ] = useState( false );
	const [ submitting, setSubmitting ] = useState( false );
	const [ aiSuggestLoading, setAiSuggestLoading ] = useState( false );

	const timelineEndRef = useRef( null );

	useEffect( () => {
		if ( timelineEndRef.current ) {
			timelineEndRef.current.scrollIntoView( { behavior: 'smooth' } );
		}
	}, [ selectedTicket?.messages, selectedTicket?.events ] );

	// Priority badge helper
	const getPriorityBadgeClass = ( priority ) => {
		switch ( priority ) {
			case 'urgent': return 'dctc-sc-badge-urgent';
			case 'high': return 'dctc-sc-badge-high';
			case 'normal': return 'dctc-sc-badge-normal';
			default: return 'dctc-sc-badge-low';
		}
	};

	// Status badge helper
	const getStatusBadgeClass = ( status ) => {
		switch ( status ) {
			case 'open': return 'dctc-sc-badge-open';
			case 'pending': return 'dctc-sc-badge-pending';
			case 'waiting_customer': return 'dctc-sc-badge-waiting';
			case 'resolved': return 'dctc-sc-badge-resolved';
			case 'closed': return 'dctc-sc-badge-closed';
			default: return 'dctc-sc-badge-secondary';
		}
	};

	// Action: Take Control / Release Control
	const handleToggleControl = async () => {
		if ( ! selectedTicketId ) return;
		const nextMode = selectedTicket?.control_mode === 'human' ? 'ai' : 'human';
		try {
			const data = await apiFetch( {
				path: `/dctc-ai/v1/support/tickets/${ selectedTicketId }/control`,
				method: 'POST',
				data: { mode: nextMode },
			} );
			if ( data?.success ) {
				onRefreshTicketDetails( selectedTicketId );
				onShowNotice(
					nextMode === 'human'
						? __( 'You have taken control of this conversation. AI is paused.', 'dragwyb-click-to-chat' )
						: __( 'Conversation handed back to AI Assistant.', 'dragwyb-click-to-chat' ),
					'success'
				);
			}
		} catch ( err ) {
			console.error( 'Error toggling control:', err );
			onShowNotice( __( 'Failed to toggle control mode.', 'dragwyb-click-to-chat' ), 'error' );
		}
	};

	// Action: Change Status
	const handleStatusChange = async ( newStatus ) => {
		if ( ! selectedTicketId ) return;
		try {
			const data = await apiFetch( {
				path: `/dctc-ai/v1/support/tickets/${ selectedTicketId }/status`,
				method: 'POST',
				data: { status: newStatus },
			} );
			if ( data?.success ) {
				onRefreshTicketDetails( selectedTicketId );
				onRefreshTickets();
				onShowNotice( __( 'Ticket status updated.', 'dragwyb-click-to-chat' ), 'success' );
			}
		} catch ( err ) {
			console.error( 'Error updating status:', err );
		}
	};

	// Action: Change Priority
	const handlePriorityChange = async ( newPriority ) => {
		if ( ! selectedTicketId ) return;
		try {
			const data = await apiFetch( {
				path: `/dctc-ai/v1/support/tickets/${ selectedTicketId }/priority`,
				method: 'POST',
				data: { priority: newPriority },
			} );
			if ( data?.success ) {
				onRefreshTicketDetails( selectedTicketId );
				onRefreshTickets();
				onShowNotice( __( 'Ticket priority updated.', 'dragwyb-click-to-chat' ), 'success' );
			}
		} catch ( err ) {
			console.error( 'Error updating priority:', err );
		}
	};

	// Action: Assign Agent
	const handleAssignAgent = async ( agentId ) => {
		if ( ! selectedTicketId ) return;
		try {
			const data = await apiFetch( {
				path: `/dctc-ai/v1/support/tickets/${ selectedTicketId }/assign`,
				method: 'POST',
				data: { agent_id: Number( agentId ) },
			} );
			if ( data?.success ) {
				onRefreshTicketDetails( selectedTicketId );
				onRefreshTickets();
				onShowNotice( __( 'Agent assignment updated.', 'dragwyb-click-to-chat' ), 'success' );
			}
		} catch ( err ) {
			console.error( 'Error assigning agent:', err );
		}
	};

	// Action: Send Human Agent Reply
	const handleSendReply = async ( e ) => {
		if ( e ) e.preventDefault();
		if ( ! selectedTicketId || ! replyText.trim() ) return;
		setSubmitting( true );
		try {
			const data = await apiFetch( {
				path: `/dctc-ai/v1/support/tickets/${ selectedTicketId }/reply`,
				method: 'POST',
				data: { message: replyText.trim() },
			} );
			if ( data?.success ) {
				setReplyText( '' );
				onRefreshTicketDetails( selectedTicketId );
				onShowNotice( __( 'Reply sent to customer.', 'dragwyb-click-to-chat' ), 'success' );
			}
		} catch ( err ) {
			console.error( 'Error sending reply:', err );
			onShowNotice( __( 'Failed to send reply.', 'dragwyb-click-to-chat' ), 'error' );
		} finally {
			setSubmitting( false );
		}
	};

	// Action: Add Internal Staff Note
	const handleAddNote = async ( e ) => {
		if ( e ) e.preventDefault();
		if ( ! selectedTicketId || ! noteText.trim() ) return;
		setSubmitting( true );
		try {
			const data = await apiFetch( {
				path: `/dctc-ai/v1/support/tickets/${ selectedTicketId }/notes`,
				method: 'POST',
				data: { note: noteText.trim(), is_pinned: isPinnedNote },
			} );
			if ( data?.success ) {
				setNoteText( '' );
				setIsPinnedNote( false );
				onRefreshTicketDetails( selectedTicketId );
				onShowNotice( __( 'Internal note recorded.', 'dragwyb-click-to-chat' ), 'success' );
			}
		} catch ( err ) {
			console.error( 'Error saving note:', err );
		} finally {
			setSubmitting( false );
		}
	};

	// Action: Suggest AI Reply
	const handleSuggestAiReply = async () => {
		if ( ! selectedTicketId ) return;
		setAiSuggestLoading( true );
		try {
			const data = await apiFetch( {
				path: `/dctc-ai/v1/support/tickets/${ selectedTicketId }/ai-suggest-reply`,
				method: 'POST',
			} );
			if ( data?.success && data.suggestion ) {
				setComposerMode( 'reply' );
				setReplyText( data.suggestion );
				onShowNotice( __( 'AI drafted a reply based on conversation history!', 'dragwyb-click-to-chat' ), 'info' );
			}
		} catch ( err ) {
			console.error( 'Error generating AI reply:', err );
			onShowNotice( __( 'Could not draft AI reply.', 'dragwyb-click-to-chat' ), 'error' );
		} finally {
			setAiSuggestLoading( false );
		}
	};

	return (
		<div className="dctc-sc-workspace-grid">
			{ /* Column 1: Ticket Filters & Ticket List */ }
			<div className="dctc-sc-col-list">
				{ /* Status Filter Pills */ }
				<div className="dctc-sc-status-pills">
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
							className={ `dctc-sc-pill ${ statusFilter === tab.id ? 'active' : '' }` }
							onClick={ () => { setStatusFilter( tab.id ); setCurrentPage( 1 ); } }
						>
							{ tab.label }
						</button>
					) ) }
				</div>

				{ /* Search Bar */ }
				<div className="dctc-sc-search-bar">
					<input
						type="text"
						placeholder={ __( 'Search tickets, email, subject...', 'dragwyb-click-to-chat' ) }
						value={ searchQuery }
						onChange={ ( e ) => setSearchQuery( e.target.value ) }
						className="dctc-sc-search-input"
					/>
				</div>

				{ /* Ticket List Cards */ }
				<div className="dctc-sc-ticket-scroll-list">
					{ loading ? (
						<div className="dctc-sc-loading-state">
							<span className="spinner is-active"></span>
							{ __( 'Loading tickets...', 'dragwyb-click-to-chat' ) }
						</div>
					) : tickets.length === 0 ? (
						<div className="dctc-sc-empty-state">
							<span className="dashicons dashicons-clipboard"></span>
							<p>{ __( 'No support tickets found.', 'dragwyb-click-to-chat' ) }</p>
						</div>
					) : (
						tickets.map( ( item ) => (
							<div
								key={ item.id }
								className={ `dctc-sc-ticket-card ${ selectedTicketId === item.id ? 'selected' : '' }` }
								onClick={ () => setSelectedTicketId( item.id ) }
							>
								<div className="dctc-sc-ticket-card-header">
									<span className="dctc-sc-ticket-number">#{ item.ticket_number }</span>
									<span className={ `dctc-sc-badge ${ getPriorityBadgeClass( item.priority ) }` }>
										{ item.priority }
									</span>
									<span className={ `dctc-sc-badge ${ getStatusBadgeClass( item.status ) }` }>
										{ item.status }
									</span>
								</div>

								<div className="dctc-sc-ticket-card-subject">{ item.subject }</div>

								<div className="dctc-sc-ticket-card-customer">
									<span>
										<span className="dashicons dashicons-admin-users" style={ { fontSize: '13px', width: '13px', height: '13px', verticalAlign: 'middle', marginRight: '4px' } }></span>
										{ item.customer_name || item.customer_email || ( item.session_id ? `Guest (${ item.session_id.substring( 0, 10 ) }...)` : __( 'Anonymous Visitor', 'dragwyb-click-to-chat' ) ) }
									</span>
								</div>

								{ /* Category, Agent, and Chat Count Badges */ }
								<div className="dctc-sc-ticket-badges-bar">
									<span className="dctc-sc-badge dctc-sc-badge-category" title={ __( 'Category', 'dragwyb-click-to-chat' ) }>
										<span className="dashicons dashicons-category" style={ { fontSize: '12px', width: '12px', height: '12px', verticalAlign: 'middle', marginRight: '3px' } }></span>
										{ item.category_name || __( 'General', 'dragwyb-click-to-chat' ) }
									</span>
									<span className="dctc-sc-badge dctc-sc-badge-agent" title={ __( 'Assigned Agent', 'dragwyb-click-to-chat' ) }>
										<span className="dashicons dashicons-businesswoman" style={ { fontSize: '12px', width: '12px', height: '12px', verticalAlign: 'middle', marginRight: '3px' } }></span>
										{ item.agent_name || __( 'Unassigned', 'dragwyb-click-to-chat' ) }
									</span>
									<span className="dctc-sc-badge dctc-sc-badge-chats" title={ __( 'Number of Messages / Chats', 'dragwyb-click-to-chat' ) }>
										<span className="dashicons dashicons-format-chat" style={ { fontSize: '12px', width: '12px', height: '12px', verticalAlign: 'middle', marginRight: '3px' } }></span>
										{ item.chat_count !== undefined ? item.chat_count : ( item.message_count || 1 ) } { ( item.chat_count === 1 || item.message_count === 1 ) ? __( 'msg', 'dragwyb-click-to-chat' ) : __( 'chats', 'dragwyb-click-to-chat' ) }
									</span>
								</div>

								{ /* Tags & Product Badges */ }
								{ Array.isArray( item.tags ) && item.tags.length > 0 && (
									<div className="dctc-sc-card-tags-wrap">
										{ item.tags.map( ( tag, idx ) => (
											<span key={ idx } className="dctc-sc-badge dctc-sc-badge-tag" title={ __( 'Tag / Product', 'dragwyb-click-to-chat' ) }>
												<span className="dashicons dashicons-tag" style={ { fontSize: '11px', width: '11px', height: '11px', verticalAlign: 'middle', marginRight: '3px' } }></span>
												{ tag }
											</span>
										) ) }
									</div>
								) }

								<div className="dctc-sc-ticket-card-footer">
									<span className="dctc-sc-control-indicator">
										<span className={ `dashicons ${ item.control_mode === 'human' ? 'dashicons-admin-users' : 'dashicons-superhero' }` } style={ { fontSize: '12px', width: '12px', height: '12px', verticalAlign: 'middle', marginRight: '4px' } }></span>
										{ item.control_mode === 'human' ? __( 'Staff Assigned', 'dragwyb-click-to-chat' ) : __( 'AI Active', 'dragwyb-click-to-chat' ) }
									</span>
									<span className="dctc-sc-card-time">{ item.created_at }</span>
								</div>
							</div>
						) )
					) }
				</div>

				{ /* Pagination */ }
				{ totalPages > 1 && (
					<div className="dctc-sc-pagination">
						<button
							type="button"
							disabled={ currentPage <= 1 }
							onClick={ () => setCurrentPage( ( p ) => Math.max( 1, p - 1 ) ) }
							className="button button-small"
						>
							&laquo; { __( 'Prev', 'dragwyb-click-to-chat' ) }
						</button>
						<span>{ currentPage } / { totalPages }</span>
						<button
							type="button"
							disabled={ currentPage >= totalPages }
							onClick={ () => setCurrentPage( ( p ) => Math.min( totalPages, p + 1 ) ) }
							className="button button-small"
						>
							{ __( 'Next', 'dragwyb-click-to-chat' ) } &raquo;
						</button>
					</div>
				) }
			</div>

			{ /* Column 2: Ticket Workspace & Conversation Timeline */ }
			<div className="dctc-sc-col-main">
				{ ticketLoading ? (
					<div className="dctc-sc-main-loading">
						<span className="spinner is-active"></span>
						<p>{ __( 'Loading ticket details...', 'dragwyb-click-to-chat' ) }</p>
					</div>
				) : ! selectedTicket ? (
					<div className="dctc-sc-no-selection">
						<span className="dashicons dashicons-format-chat"></span>
						<h3>{ __( 'Select a ticket to begin support', 'dragwyb-click-to-chat' ) }</h3>
					</div>
				) : (
					<div className="dctc-sc-ticket-workspace">
						{ /* Ticket Header Bar */ }
						<div className="dctc-sc-ticket-main-header">
							<div className="dctc-sc-header-left">
								<div className="dctc-sc-header-badges">
									<span className="dctc-sc-ticket-large-num">#{ selectedTicket.ticket_number }</span>
									<span className={ `dctc-sc-badge ${ getStatusBadgeClass( selectedTicket.status ) }` }>
										{ selectedTicket.status }
									</span>
									<span className={ `dctc-sc-badge ${ getPriorityBadgeClass( selectedTicket.priority ) }` }>
										{ selectedTicket.priority }
									</span>
									<span className="dctc-sc-surface-tag">
										<span className={ `dashicons ${ selectedTicket.reply_surface === 'chatbot_widget' ? 'dashicons-format-chat' : 'dashicons-admin-site' }` } style={ { fontSize: '12px', width: '12px', height: '12px', verticalAlign: 'middle', marginRight: '4px' } }></span>
										{ selectedTicket.reply_surface === 'chatbot_widget' ? __( 'Chatbot Widget', 'dragwyb-click-to-chat' ) : __( 'Support Portal', 'dragwyb-click-to-chat' ) }
									</span>
									<span className="dctc-sc-badge dctc-sc-badge-category">
										<span className="dashicons dashicons-category" style={ { fontSize: '12px', width: '12px', height: '12px', verticalAlign: 'middle', marginRight: '3px' } }></span>
										{ selectedTicket.category_name || __( 'General', 'dragwyb-click-to-chat' ) }
									</span>
									<span className="dctc-sc-badge dctc-sc-badge-agent">
										<span className="dashicons dashicons-businesswoman" style={ { fontSize: '12px', width: '12px', height: '12px', verticalAlign: 'middle', marginRight: '3px' } }></span>
										{ selectedTicket.agent_name || __( 'Unassigned', 'dragwyb-click-to-chat' ) }
									</span>
									<span className="dctc-sc-badge dctc-sc-badge-chats">
										<span className="dashicons dashicons-format-chat" style={ { fontSize: '12px', width: '12px', height: '12px', verticalAlign: 'middle', marginRight: '3px' } }></span>
										{ selectedTicket.chat_count !== undefined ? selectedTicket.chat_count : ( selectedTicket.messages ? selectedTicket.messages.length : 0 ) } { ( selectedTicket.chat_count === 1 ) ? __( 'msg', 'dragwyb-click-to-chat' ) : __( 'chats', 'dragwyb-click-to-chat' ) }
									</span>
								</div>
								<h2 className="dctc-sc-ticket-main-title">{ selectedTicket.subject }</h2>
								{ Array.isArray( selectedTicket.tags ) && selectedTicket.tags.length > 0 && (
									<div className="dctc-sc-header-tags-row">
										{ selectedTicket.tags.map( ( tag, idx ) => (
											<span key={ idx } className="dctc-sc-badge dctc-sc-badge-tag">
												<span className="dashicons dashicons-tag" style={ { fontSize: '11px', width: '11px', height: '11px', verticalAlign: 'middle', marginRight: '3px' } }></span>
												{ tag }
											</span>
										) ) }
									</div>
								) }
							</div>

							<div className="dctc-sc-header-actions">
								<button
									type="button"
									className={ `dctc-sc-control-action-btn ${ selectedTicket.control_mode === 'human' ? 'btn-release-ai' : 'btn-take-control' }` }
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
						<div className="dctc-sc-quick-status-bar">
							<div className="dctc-sc-quick-control">
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

							<div className="dctc-sc-quick-control">
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

							<div className="dctc-sc-quick-control">
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
						<div className="dctc-sc-timeline-container">
							{ /* Pinned Notes */ }
							{ ( selectedTicket.notes || [] ).filter( ( n ) => n.is_pinned ).map( ( pin ) => (
								<div key={ `pin-${ pin.id }` } className="dctc-sc-pinned-note-banner">
									<span className="dashicons dashicons-admin-post"></span>
									<div>
										<strong>{ __( 'Pinned Internal Note', 'dragwyb-click-to-chat' ) } ({ pin.author_name }):</strong> { pin.note }
									</div>
								</div>
							) ) }

							{ /* Messages Stream */ }
							{ ( selectedTicket.messages || [] ).map( ( msg ) => {
								const isCustomer = msg.sender_type === 'customer';
								const isAI = msg.sender_type === 'ai_agent' || msg.sender_type === 'bot';
								const isHumanAgent = msg.sender_type === 'human_agent' || msg.sender_type === 'agent';

								let bubbleClass = 'dctc-sc-msg-customer';
								if ( isAI ) bubbleClass = 'dctc-sc-msg-ai';
								if ( isHumanAgent ) bubbleClass = 'dctc-sc-msg-agent';

								return (
									<div key={ msg.id } className={ `dctc-sc-msg-row ${ bubbleClass }` }>
										<div className="dctc-sc-msg-meta-line">
											<span className="dctc-sc-msg-author">
												{ isCustomer && (
													<>
														<span className="dashicons dashicons-admin-users" style={ { fontSize: '13px', width: '13px', height: '13px', verticalAlign: 'middle', marginRight: '4px' } }></span>
														{ __( 'Customer', 'dragwyb-click-to-chat' ) }
													</>
												) }
												{ isAI && (
													<>
														<span className="dashicons dashicons-superhero" style={ { fontSize: '13px', width: '13px', height: '13px', verticalAlign: 'middle', marginRight: '4px' } }></span>
														{ __( 'AI Assistant', 'dragwyb-click-to-chat' ) }
													</>
												) }
												{ isHumanAgent && (
													<>
														<span className="dashicons dashicons-businesswoman" style={ { fontSize: '13px', width: '13px', height: '13px', verticalAlign: 'middle', marginRight: '4px' } }></span>
														{ msg.sender_name || __( 'Staff Agent', 'dragwyb-click-to-chat' ) }
													</>
												) }
											</span>
											<span className="dctc-sc-msg-time">{ msg.created_at }</span>
										</div>
										<div className="dctc-sc-msg-bubble-content">
											{ msg.content }
										</div>
									</div>
								);
							} ) }

							{ /* Timeline Audit Events */ }
							{ ( selectedTicket.events || [] ).map( ( evt, idx ) => (
								<div key={ `evt-${ idx }` } className="dctc-sc-timeline-event-row">
									<span className="dctc-sc-event-icon dashicons dashicons-marker"></span>
									<span className="dctc-sc-event-text">
										<strong>{ evt.actor_name }</strong>: { evt.event_type.replace( '_', ' ' ) }
										{ evt.new_value ? ` → ${ evt.new_value }` : '' }
									</span>
									<span className="dctc-sc-event-time">{ evt.created_at }</span>
								</div>
							) ) }
							<div ref={ timelineEndRef } />
						</div>

						{ /* Composer: Reply vs Note */ }
						<div className="dctc-sc-composer-box">
							<div className="dctc-sc-composer-tabs">
								<button
									type="button"
									className={ `dctc-sc-comp-tab ${ composerMode === 'reply' ? 'active' : '' }` }
									onClick={ () => setComposerMode( 'reply' ) }
								>
									<span className="dashicons dashicons-admin-comments"></span>
									{ __( 'Reply to Customer', 'dragwyb-click-to-chat' ) }
								</button>
								<button
									type="button"
									className={ `dctc-sc-comp-tab dctc-sc-note-tab ${ composerMode === 'note' ? 'active' : '' }` }
									onClick={ () => setComposerMode( 'note' ) }
								>
									<span className="dashicons dashicons-lock"></span>
									{ __( 'Internal Staff Note', 'dragwyb-click-to-chat' ) }
								</button>
								<button
									type="button"
									className="dctc-sc-ai-assist-btn"
									onClick={ handleSuggestAiReply }
									disabled={ aiSuggestLoading }
									title={ __( 'Draft a reply using AI based on the conversation context', 'dragwyb-click-to-chat' ) }
									style={ { marginLeft: 'auto' } }
								>
									<span className="dashicons dashicons-superhero"></span>
									{ aiSuggestLoading ? __( 'Thinking...', 'dragwyb-click-to-chat' ) : __( 'Suggest AI Reply', 'dragwyb-click-to-chat' ) }
								</button>
							</div>

							{ composerMode === 'reply' ? (
								<form onSubmit={ handleSendReply } className="dctc-sc-composer-form">
									<textarea
										rows="3"
										placeholder={ __( 'Write a response to the customer...', 'dragwyb-click-to-chat' ) }
										value={ replyText }
										onChange={ ( e ) => setReplyText( e.target.value ) }
										className="dctc-sc-composer-textarea"
									/>
									<div className="dctc-sc-composer-actions">
										<span className="dctc-sc-composer-hint">
											{ selectedTicket.control_mode === 'ai'
												? __( 'AI is currently active. Replying will pause AI automatically.', 'dragwyb-click-to-chat' )
												: __( 'Message will be delivered directly to the customer session.', 'dragwyb-click-to-chat' ) }
										</span>
										<button
											type="submit"
											disabled={ submitting || ! replyText.trim() }
											className="button button-primary dctc-sc-send-btn"
										>
											{ submitting ? __( 'Sending...', 'dragwyb-click-to-chat' ) : __( 'Send Reply', 'dragwyb-click-to-chat' ) }
										</button>
									</div>
								</form>
							) : (
								<form onSubmit={ handleAddNote } className="dctc-sc-composer-form dctc-sc-note-form">
									<textarea
										rows="3"
										placeholder={ __( 'Add a private note visible only to support staff...', 'dragwyb-click-to-chat' ) }
										value={ noteText }
										onChange={ ( e ) => setNoteText( e.target.value ) }
										className="dctc-sc-composer-textarea dctc-sc-note-textarea"
									/>
									<div className="dctc-sc-composer-actions">
										<label className="dctc-sc-pinned-checkbox">
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
											className="button dctc-sc-note-btn"
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

			{ /* Column 3: Customer Context & WooCommerce */ }
			<div className="dctc-sc-col-meta">
				{ selectedTicket && (
					<>
						{ /* Customer Info Card */ }
						<div className="dctc-sc-meta-card">
							<h4 className="dctc-sc-meta-card-title">
								<span className="dashicons dashicons-admin-users"></span>
								{ __( 'Customer Details', 'dragwyb-click-to-chat' ) }
							</h4>
							<div className="dctc-sc-meta-row">
								<span className="meta-label">{ __( 'Name:', 'dragwyb-click-to-chat' ) }</span>
								<span className="meta-val">{ selectedTicket.customer_name || __( 'Guest Visitor', 'dragwyb-click-to-chat' ) }</span>
							</div>
							<div className="dctc-sc-meta-row">
								<span className="meta-label">{ __( 'Email:', 'dragwyb-click-to-chat' ) }</span>
								<span className="meta-val">
									{ selectedTicket.customer_email ? (
										<a href={ `mailto:${ selectedTicket.customer_email }` }>{ selectedTicket.customer_email }</a>
									) : (
										<span style={ { color: '#9CA3AF' } }>{ __( 'Not provided (Live Chat)', 'dragwyb-click-to-chat' ) }</span>
									) }
								</span>
							</div>
							{ selectedTicket.session_id && (
								<div className="dctc-sc-meta-row">
									<span className="meta-label">{ __( 'Session ID:', 'dragwyb-click-to-chat' ) }</span>
									<span className="meta-val" style={ { fontFamily: 'monospace', fontSize: '11px', background: '#F3F4F6', padding: '2px 6px', borderRadius: '4px', wordBreak: 'break-all' } } title={ selectedTicket.session_id }>
										{ selectedTicket.session_id }
									</span>
								</div>
							) }
							<div className="dctc-sc-meta-row">
								<span className="meta-label">{ __( 'Surface:', 'dragwyb-click-to-chat' ) }</span>
								<span className="meta-val">{ selectedTicket.reply_surface || 'web' }</span>
							</div>
							<div className="dctc-sc-meta-row">
								<span className="meta-label">{ __( 'Created:', 'dragwyb-click-to-chat' ) }</span>
								<span className="meta-val">{ selectedTicket.created_at }</span>
							</div>
						</div>

						{ /* Ticket Classification & Badges Card */ }
						<div className="dctc-sc-meta-card">
							<h4 className="dctc-sc-meta-card-title">
								<span className="dashicons dashicons-tag"></span>
								{ __( 'Classification & Badges', 'dragwyb-click-to-chat' ) }
							</h4>
							<div className="dctc-sc-meta-row">
								<span className="meta-label">{ __( 'Category:', 'dragwyb-click-to-chat' ) }</span>
								<span className="dctc-sc-badge dctc-sc-badge-category">
									<span className="dashicons dashicons-category" style={ { fontSize: '12px', width: '12px', height: '12px', verticalAlign: 'middle', marginRight: '3px' } }></span>
									{ selectedTicket.category_name || __( 'General Inquiry', 'dragwyb-click-to-chat' ) }
								</span>
							</div>
							<div className="dctc-sc-meta-row">
								<span className="meta-label">{ __( 'Assigned Agent:', 'dragwyb-click-to-chat' ) }</span>
								<span className="dctc-sc-badge dctc-sc-badge-agent">
									<span className="dashicons dashicons-businesswoman" style={ { fontSize: '12px', width: '12px', height: '12px', verticalAlign: 'middle', marginRight: '3px' } }></span>
									{ selectedTicket.agent_name || __( 'Unassigned', 'dragwyb-click-to-chat' ) }
								</span>
							</div>
							<div className="dctc-sc-meta-row">
								<span className="meta-label">{ __( 'Total Chats:', 'dragwyb-click-to-chat' ) }</span>
								<span className="dctc-sc-badge dctc-sc-badge-chats">
									<span className="dashicons dashicons-format-chat" style={ { fontSize: '12px', width: '12px', height: '12px', verticalAlign: 'middle', marginRight: '3px' } }></span>
									{ selectedTicket.chat_count !== undefined ? selectedTicket.chat_count : ( selectedTicket.messages ? selectedTicket.messages.length : 0 ) } { __( 'messages', 'dragwyb-click-to-chat' ) }
								</span>
							</div>
							<div className="dctc-sc-meta-tags-block">
								<span className="meta-label" style={ { display: 'block', marginBottom: '6px', fontWeight: 600, fontSize: '11px', color: '#6B7280' } }>
									{ __( 'Tags & Products:', 'dragwyb-click-to-chat' ) }
								</span>
								<div className="dctc-sc-card-tags-wrap">
									{ Array.isArray( selectedTicket.tags ) && selectedTicket.tags.length > 0 ? (
										selectedTicket.tags.map( ( tag, idx ) => (
											<span key={ idx } className="dctc-sc-badge dctc-sc-badge-tag">
												<span className="dashicons dashicons-tag" style={ { fontSize: '11px', width: '11px', height: '11px', verticalAlign: 'middle', marginRight: '3px' } }></span>
												{ tag }
											</span>
										) )
									) : (
										<span style={ { fontSize: '12px', color: '#9CA3AF' } }>{ __( 'No tags assigned', 'dragwyb-click-to-chat' ) }</span>
									) }
								</div>
							</div>
						</div>

						{ /* WooCommerce Card */ }
						{ wcLoading ? (
							<div className="dctc-sc-meta-card">
								<span className="spinner is-active"></span> { __( 'Loading WooCommerce info...', 'dragwyb-click-to-chat' ) }
							</div>
						) : wcData ? (
							<div className="dctc-sc-meta-card dctc-sc-wc-card">
								<h4 className="dctc-sc-meta-card-title">
									<span className="dashicons dashicons-cart"></span>
									{ __( 'WooCommerce Profile', 'dragwyb-click-to-chat' ) }
								</h4>
								<div className="dctc-sc-wc-header-stats">
									<div className="dctc-sc-wc-stat-badge">
										<span className="stat-label">{ __( 'Total Spend', 'dragwyb-click-to-chat' ) }</span>
										<span className="stat-value">{ wcData.currency_symbol }{ ( Number( wcData.total_spend ) || 0 ).toFixed( 2 ) }</span>
									</div>
									<div className="dctc-sc-wc-stat-badge">
										<span className="stat-label">{ __( 'Orders', 'dragwyb-click-to-chat' ) }</span>
										<span className="stat-value">{ wcData.order_count || 0 }</span>
									</div>
								</div>

								{ ( wcData.recent_orders || [] ).length > 0 && (
									<div className="dctc-sc-wc-orders-list">
										<span className="dctc-sc-meta-subtitle">{ __( 'Recent Orders', 'dragwyb-click-to-chat' ) }</span>
										{ wcData.recent_orders.map( ( ord ) => (
											<div key={ ord.id } className="dctc-sc-wc-order-item">
												<div className="dctc-sc-wc-order-top">
													<a href={ ord.admin_url } target="_blank" rel="noreferrer" className="dctc-sc-wc-order-num">
														#{ ord.number }
													</a>
													<span className="dctc-sc-wc-order-status">{ ord.status }</span>
												</div>
												<div className="dctc-sc-wc-order-meta">
													<span>{ ord.currency_symbol }{ ord.total }</span>
													<span>{ ord.date_created }</span>
												</div>
											</div>
										) ) }
									</div>
								) }
							</div>
						) : null }

						{ /* Internal Notes Summary Card */ }
						<div className="dctc-sc-meta-card">
							<h4 className="dctc-sc-meta-card-title">
								<span className="dashicons dashicons-lock"></span>
								{ __( 'Internal Notes', 'dragwyb-click-to-chat' ) } ({ ( selectedTicket.notes || [] ).length })
							</h4>
							{ ( selectedTicket.notes || [] ).length === 0 ? (
								<p className="dctc-sc-empty-notes">{ __( 'No internal notes added yet.', 'dragwyb-click-to-chat' ) }</p>
							) : (
								<div className="dctc-sc-notes-list">
									{ selectedTicket.notes.map( ( n ) => (
										<div key={ n.id } className={ `dctc-sc-note-item ${ n.is_pinned ? 'is-pinned' : '' }` }>
											<div className="dctc-sc-note-header">
												<strong>{ n.author_name }</strong>
												<span>{ n.created_at }</span>
											</div>
											<div className="dctc-sc-note-body">{ n.note }</div>
										</div>
									) ) }
								</div>
							) }
						</div>
					</>
				) }
			</div>
		</div>
	);
}
