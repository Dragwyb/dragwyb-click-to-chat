import { useState, useRef, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { StatusBadge, PriorityBadge } from '../SupportBadges';

export default function TicketDetailWorkspace({
	selectedTicket,
	selectedTicketId,
	totalTickets = 0,
	filteredTickets = [],
	userPermissions = {},
	isLeftTicketsPanelExpanded,
	setIsLeftTicketsPanelExpanded,
	activeViewers = [],
	starredTickets = {},
	flaggedTickets = {},
	toggleStar,
	toggleFlag,
	onOpenTicket,
	onCloseTicket,
	onNavigateTicket,
	handleStatusChange,
	handlePriorityChange,
	handleToggleControl,
	handleSendReply,
	handleAddNote,
	handleSuggestAiReply,
	replyText,
	setReplyText,
	replyAttachments,
	setReplyAttachments,
	noteText,
	setNoteText,
	isPinnedNote,
	setIsPinnedNote,
	markAsResolved,
	setMarkAsResolved,
	submitting,
	aiSuggestLoading,
	replyEditorMode,
	setReplyEditorMode,
	getInitials,
}) {
	const [workspaceTab, setWorkspaceTab] = useState('conversation');
	const [composerMode, setComposerMode] = useState('reply'); // 'reply' | 'note'
	const [isComposerOpen, setIsComposerOpen] = useState(false);
	const replyTextareaRef = useRef(null);

	const handleOpenComposer = (mode) => {
		setComposerMode(mode);
		setIsComposerOpen(true);
		setTimeout(() => {
			if (mode === 'reply' && replyTextareaRef.current) {
				replyTextareaRef.current.focus();
			}
		}, 60);
	};

	const handleAiSuggestClick = () => {
		setComposerMode('reply');
		setIsComposerOpen(true);
		handleSuggestAiReply();
	};

	const onReplySubmit = async (e) => {
		await handleSendReply(e);
		setIsComposerOpen(false);
	};

	const onNoteSubmit = async (e) => {
		await handleAddNote(e);
		setIsComposerOpen(false);
	};

	const applyReplyFormatting = (tagType) => {
		const textarea = replyTextareaRef.current;
		if (!textarea) return;
		const start = textarea.selectionStart || 0;
		const end = textarea.selectionEnd || 0;
		const text = replyText || '';
		const selected = text.substring(start, end) || 'text';
		let replacement = '';
		if (tagType === 'bold') replacement = `<strong>${selected}</strong>`;
		else if (tagType === 'italic') replacement = `<em>${selected}</em>`;
		else if (tagType === 'underline') replacement = `<u>${selected}</u>`;
		else if (tagType === 'strike') replacement = `<s>${selected}</s>`;
		else if (tagType === 'link') replacement = `<a href="https://example.com">${selected}</a>`;
		else if (tagType === 'ul') replacement = `\n<ul>\n  <li>${selected}</li>\n</ul>\n`;
		else if (tagType === 'ol') replacement = `\n<ol>\n  <li>${selected}</li>\n</ol>\n`;
		else if (tagType === 'quote') replacement = `\n<blockquote>${selected}</blockquote>\n`;
		else if (tagType === 'code') replacement = `<code>${selected}</code>`;

		const updated = text.substring(0, start) + replacement + text.substring(end);
		setReplyText(updated);
		setTimeout(() => {
			if (textarea) {
				textarea.focus();
				textarea.setSelectionRange(start + replacement.length, start + replacement.length);
			}
		}, 50);
	};

	const handleAttachReplyFiles = () => {
		if (window.wp && window.wp.media) {
			const frame = window.wp.media({
				title: __('Attach Media / Files to Reply', 'dragwyb-click-to-chat'),
				button: { text: __('Insert into Reply', 'dragwyb-click-to-chat') },
				multiple: true,
			});
			frame.on('select', () => {
				const selection = frame.state().get('selection').toJSON();
				setReplyAttachments((prev) => [...prev, ...selection]);
				const fileLinks = selection.map((f) => {
					if (f.type === 'image' || (f.mime && f.mime.startsWith('image/'))) {
						return `<p><img src="${f.url}" alt="${f.alt || f.title}" style="max-width:100%;height:auto;border-radius:6px;" /></p>`;
					}
					return `<p><a href="${f.url}" target="_blank" rel="noopener noreferrer">📎 ${f.filename || f.title}</a></p>`;
				}).join('\n');
				setReplyText((prev) => (prev ? `${prev}\n${fileLinks}` : fileLinks));
			});
			frame.open();
		} else {
			const input = document.createElement('input');
			input.type = 'file';
			input.multiple = true;
			input.onchange = (e) => {
				const files = Array.from(e.target.files);
				setReplyAttachments((prev) => [...prev, ...files]);
				const names = files.map((f) => `📎 ${f.name}`).join(', ');
				setReplyText((prev) => (prev ? `${prev} [${names}]` : `[${names}]`));
			};
			input.click();
		}
	};

	const rawMessages = selectedTicket?.messages || [];
	const messagesNewToOld = [...rawMessages].reverse();

	return (
		<>
			{/* COLLAPSIBLE LEFT "ALL TICKETS" PANEL */}
			<div className={`dctc-sc-left-tickets-panel-wrapper ${isLeftTicketsPanelExpanded ? 'is-expanded' : 'is-collapsed'}`}>
				{isLeftTicketsPanelExpanded ? (
					<aside className="dctc-sc-left-tickets-panel">
						<div className="dctc-sc-left-panel-header">
							<div className="dctc-sc-left-panel-title">
								<span className="dashicons dashicons-tickets-alt"></span>
								<strong>{__('All Tickets', 'dragwyb-click-to-chat')}</strong>
								<span className="dctc-sc-left-panel-count">
									{totalTickets || filteredTickets.length}
								</span>
							</div>
							<button
								type="button"
								className="dctc-sc-left-panel-collapse-btn"
								onClick={() => setIsLeftTicketsPanelExpanded(false)}
								title={__('Collapse Tickets Sidebar', 'dragwyb-click-to-chat')}
							>
								<span className="dashicons dashicons-arrow-left-alt2"></span>
							</button>
						</div>

						<div className="dctc-sc-left-panel-list">
							{filteredTickets.length === 0 ? (
								<div className="dctc-sc-left-panel-empty">
									{__('No tickets found', 'dragwyb-click-to-chat')}
								</div>
							) : (
								filteredTickets.map((t) => {
									const isCurrent = t.id === selectedTicketId;
									const subjectTrimmed = t.subject || __('Untitled Ticket', 'dragwyb-click-to-chat');
									const excerptTrimmed = (t.excerpt || t.last_message || t.subject || '').replace(/<[^>]*>?/gm, '').trim();
									const displayProduct = t.product || t.product_name;
									const displayAgent = t.agent_name || (t.assigned_agent_id ? `Agent #${t.assigned_agent_id}` : __('Unassigned', 'dragwyb-click-to-chat'));

									return (
										<div
											key={t.id}
											className={`dctc-sc-left-panel-item ${isCurrent ? 'active' : ''}`}
											onClick={() => onOpenTicket(t.id)}
										>
											<div className="dctc-sc-left-panel-item-header">
												<span className="dctc-sc-left-panel-id">#{t.ticket_number || t.id}</span>
												<span className="dctc-sc-left-panel-subject" title={subjectTrimmed}>
													{subjectTrimmed}
												</span>
											</div>

											{excerptTrimmed && (
												<div className="dctc-sc-left-panel-excerpt" title={excerptTrimmed}>
													{excerptTrimmed}
												</div>
											)}

											<div className="dctc-sc-left-panel-badges">
												{displayProduct && (
													<span className="dctc-sc-panel-badge-product">
														<span className="dashicons dashicons-products"></span>
														{displayProduct}
													</span>
												)}
												<span className={`dctc-sc-panel-badge-agent ${!t.assigned_agent_id ? 'unassigned' : ''}`}>
													<span className="dashicons dashicons-admin-users"></span>
													{displayAgent}
												</span>
											</div>
										</div>
									);
								})
							)}
						</div>
					</aside>
				) : (
					<div className="dctc-sc-left-panel-collapsed-bar">
						<button
							type="button"
							className="dctc-sc-left-panel-expand-btn"
							onClick={() => setIsLeftTicketsPanelExpanded(true)}
							title={__('Expand All Tickets list', 'dragwyb-click-to-chat')}
						>
							<span className="dashicons dashicons-arrow-right-alt2"></span>
							<span className="dctc-sc-expand-label">{__('All Tickets', 'dragwyb-click-to-chat')}</span>
							<span className="dctc-sc-expand-badge">{totalTickets || filteredTickets.length}</span>
						</button>
					</div>
				)}
			</div>

			{/* MAIN WORKSPACE */}
			<main className="dctc-sc-col-main">
				{!selectedTicket ? (
					<div className="dctc-sc-no-selection">
						<span className="dashicons dashicons-format-chat"></span>
						<h3>{__('Select a ticket to begin support', 'dragwyb-click-to-chat')}</h3>
					</div>
				) : (
					<div className="dctc-sc-workspace-inner">
						{/* TOP HEADER BAR */}
						<div className="dctc-sc-ws-header">
							<div className="dctc-sc-ws-header-left">
								<button
									type="button"
									className="dctc-sc-back-to-list-btn"
									onClick={onCloseTicket}
									title={__('Close ticket and return to full list', 'dragwyb-click-to-chat')}
								>
									<span className="dashicons dashicons-arrow-left-alt"></span>
									<span>{__('All Tickets', 'dragwyb-click-to-chat')}</span>
								</button>

								<span className="dctc-sc-ws-ticket-id">#{selectedTicket?.ticket_number || selectedTicket?.id}</span>

								<div className="dctc-sc-ws-badge-dropdown">
									<select
										value={selectedTicket?.status || 'open'}
										onChange={(e) => handleStatusChange(e.target.value)}
										className="dctc-sc-ws-select-badge"
									>
										<option value="open">OPEN</option>
										<option value="pending">PENDING</option>
										<option value="waiting_customer">WAITING</option>
										<option value="resolved">RESOLVED</option>
										<option value="closed">CLOSED</option>
									</select>
								</div>

								<div className="dctc-sc-ws-badge-dropdown">
									<select
										value={selectedTicket?.priority || 'normal'}
										onChange={(e) => handlePriorityChange(e.target.value)}
										className="dctc-sc-ws-select-badge"
									>
										<option value="low">Low</option>
										<option value="normal">Normal</option>
										<option value="high">High</option>
										<option value="urgent">Urgent</option>
									</select>
								</div>

								{selectedTicket?.id && (
									<>
										<button
											type="button"
											className={`dctc-sc-ws-icon-btn ${flaggedTickets[selectedTicket.id] ? 'active-flag' : ''}`}
											onClick={() => toggleFlag(selectedTicket.id)}
											title={__('Flag Ticket', 'dragwyb-click-to-chat')}
										>
											<span className="dashicons dashicons-flag"></span>
										</button>

										<button
											type="button"
											className={`dctc-sc-ws-icon-btn ${starredTickets[selectedTicket.id] ? 'active-star' : ''}`}
											onClick={() => toggleStar(selectedTicket.id)}
											title={__('Star Ticket', 'dragwyb-click-to-chat')}
										>
											<span className={`dashicons ${starredTickets[selectedTicket.id] ? 'dashicons-star-filled' : 'dashicons-star-empty'}`}></span>
										</button>
									</>
								)}
							</div>

							<div className="dctc-sc-ws-header-right">
								<div className="dctc-sc-nav-arrows">
									<button
										type="button"
										className="dctc-sc-arrow-btn"
										onClick={() => onNavigateTicket('prev')}
										title={__('Previous Ticket', 'dragwyb-click-to-chat')}
									>
										&lt;
									</button>
									<button
										type="button"
										className="dctc-sc-arrow-btn"
										onClick={() => onNavigateTicket('next')}
										title={__('Next Ticket', 'dragwyb-click-to-chat')}
									>
										&gt;
									</button>
								</div>

								{(selectedTicket?.reply_surface === 'chatbot_widget' || selectedTicket?.origin_type === 'ai_chatbot') && (selectedTicket?.is_session_active || selectedTicket?.session_active) && (
									<button
										type="button"
										className="dctc-sc-control-ai-btn"
										onClick={handleToggleControl}
									>
										<span className={`dashicons ${selectedTicket?.control_mode === 'human' ? 'dashicons-controls-play' : 'dashicons-controls-pause'}`}></span>
										{selectedTicket?.control_mode === 'human'
											? __('Give Control to AI', 'dragwyb-click-to-chat')
											: __('Take Control (Pause AI)', 'dragwyb-click-to-chat')}
									</button>
								)}

								<button
									type="button"
									className="dctc-sc-close-view-btn"
									onClick={onCloseTicket}
									title={__('Close View', 'dragwyb-click-to-chat')}
								>
									&times;
								</button>
							</div>
						</div>

						{/* TITLE & META BAR */}
						<div className="dctc-sc-ws-title-section">
							<h2 className="dctc-sc-ws-subject">{selectedTicket?.subject || __('Untitled Ticket', 'dragwyb-click-to-chat')}</h2>
							<div className="dctc-sc-ws-meta-bar">
								<span className="dctc-sc-meta-item">
									<span className="dashicons dashicons-admin-users"></span>
									{selectedTicket?.customer_name || (selectedTicket?.session_id ? `Guest (${selectedTicket.session_id.substring(0, 8)})` : 'Guest Visitor')}
								</span>
								<span className="dctc-sc-meta-item">
									<span className="dashicons dashicons-email-alt"></span>
									{selectedTicket?.customer_email || __('Not provided', 'dragwyb-click-to-chat')}
								</span>
								<span className="dctc-sc-meta-item">
									<span className="dashicons dashicons-format-chat"></span>
									{selectedTicket?.chat_count !== undefined ? selectedTicket.chat_count : (selectedTicket?.messages?.length || 0)} {__('chats', 'dragwyb-click-to-chat')}
								</span>
								<span className="dctc-sc-meta-item">
									<span className="dashicons dashicons-calendar-alt"></span>
									{selectedTicket?.created_at || ''}
								</span>
								<span className="dctc-sc-meta-item">
									<span className="dashicons dashicons-smartphone"></span>
									{selectedTicket?.reply_surface === 'chatbot_widget' ? __('Via Chatbot Widget', 'dragwyb-click-to-chat') : __('Via Support Portal', 'dragwyb-click-to-chat')}
								</span>
							</div>
						</div>

						{/* WORKSPACE SUBTABS */}
						<div className="dctc-sc-workspace-tabs">
							<button
								type="button"
								className={`dctc-sc-ws-tab-btn ${workspaceTab === 'conversation' ? 'active' : ''}`}
								onClick={() => setWorkspaceTab('conversation')}
							>
								<span className="dashicons dashicons-format-chat"></span>
								{__('Conversation', 'dragwyb-click-to-chat')}
							</button>
							<button
								type="button"
								className={`dctc-sc-ws-tab-btn ${workspaceTab === 'notes' ? 'active' : ''}`}
								onClick={() => setWorkspaceTab('notes')}
							>
								<span className="dashicons dashicons-lock"></span>
								{__('Internal Notes', 'dragwyb-click-to-chat')}
								{(selectedTicket?.notes || []).length > 0 && (
									<span className="dctc-sc-tab-badge">{(selectedTicket?.notes || []).length}</span>
								)}
							</button>
							<button
								type="button"
								className={`dctc-sc-ws-tab-btn ${workspaceTab === 'activity' ? 'active' : ''}`}
								onClick={() => setWorkspaceTab('activity')}
							>
								<span className="dashicons dashicons-backup"></span>
								{__('Activity Logs', 'dragwyb-click-to-chat')}
							</button>
						</div>

						{/* CONVERSATION TAB */}
						{workspaceTab === 'conversation' && (
							<div className="dctc-sc-conversation-scroll">
								{/* PINNED STAFF NOTES */}
								{(selectedTicket?.notes || []).filter((n) => n.is_pinned).map((pin) => (
									<div key={`pin-${pin.id}`} className="dctc-sc-pinned-note-banner">
										<span className="dashicons dashicons-admin-post"></span>
										<div>
											<strong>{__('Pinned Staff Note', 'dragwyb-click-to-chat')} ({pin.author_name}):</strong> {pin.note}
										</div>
									</div>
								))}

								{/* TOP ACTION BAR: Reply to Customer / Internal Note / Suggest AI Reply */}
								<div className="dctc-sc-top-conversation-actions">
									<div className="dctc-sc-action-buttons-group">
										<button
											type="button"
											className={`dctc-sc-action-btn ${isComposerOpen && composerMode === 'reply' ? 'active' : ''}`}
											onClick={() => handleOpenComposer('reply')}
										>
											<span className="dashicons dashicons-undo"></span>
											<strong>{__('Reply to Customer', 'dragwyb-click-to-chat')}</strong>
										</button>
										<button
											type="button"
											className={`dctc-sc-action-btn note-btn ${isComposerOpen && composerMode === 'note' ? 'active' : ''}`}
											onClick={() => handleOpenComposer('note')}
										>
											<span className="dashicons dashicons-lock"></span>
											<strong>{__('Internal Note', 'dragwyb-click-to-chat')}</strong>
										</button>
										<button
											type="button"
											className="dctc-sc-suggest-ai-btn"
											onClick={handleAiSuggestClick}
											disabled={aiSuggestLoading}
										>
											<span className="dashicons dashicons-superhero"></span>
											{aiSuggestLoading ? __('Thinking...', 'dragwyb-click-to-chat') : __('Suggest AI Reply', 'dragwyb-click-to-chat')}
										</button>
									</div>
								</div>

								{/* INLINE WYSIWYG / NOTE COMPOSER (Opens in place where new reply will appear) */}
								{isComposerOpen && (
									<div className="dctc-sc-inline-top-composer">
										{composerMode === 'reply' ? (
											<form onSubmit={onReplySubmit} className="dctc-sc-composer-main-form">
												<div className="dctc-sc-wysiwyg-wrapper">
													<div className="dctc-sc-wysiwyg-header-tabs">
														<div className="dctc-sc-wysiwyg-mode-switch">
															<button
																type="button"
																className={`dctc-sc-editor-mode-btn ${replyEditorMode === 'visual' ? 'active' : ''}`}
																onClick={() => setReplyEditorMode('visual')}
															>
																{__('Visual', 'dragwyb-click-to-chat')}
															</button>
															<button
																type="button"
																className={`dctc-sc-editor-mode-btn ${replyEditorMode === 'text' ? 'active' : ''}`}
																onClick={() => setReplyEditorMode('text')}
															>
																{__('Text', 'dragwyb-click-to-chat')}
															</button>
														</div>
														<div className="dctc-sc-wysiwyg-media-action">
															<button
																type="button"
																className="dctc-sc-add-media-btn"
																onClick={handleAttachReplyFiles}
																title={__('Add Media / Files', 'dragwyb-click-to-chat')}
															>
																<span className="dashicons dashicons-admin-media"></span>
																<span>{__('Add Media', 'dragwyb-click-to-chat')}</span>
															</button>
														</div>
													</div>

													{replyEditorMode === 'visual' && (
														<div className="dctc-sc-wysiwyg-toolbar">
															<button type="button" className="dctc-sc-wysiwyg-btn" onClick={() => applyReplyFormatting('bold')} title={__('Bold', 'dragwyb-click-to-chat')}>
																<strong>B</strong>
															</button>
															<button type="button" className="dctc-sc-wysiwyg-btn" onClick={() => applyReplyFormatting('italic')} title={__('Italic', 'dragwyb-click-to-chat')}>
																<em>I</em>
															</button>
															<button type="button" className="dctc-sc-wysiwyg-btn" onClick={() => applyReplyFormatting('underline')} title={__('Underline', 'dragwyb-click-to-chat')}>
																<u>U</u>
															</button>
															<button type="button" className="dctc-sc-wysiwyg-btn" onClick={() => applyReplyFormatting('strike')} title={__('Strikethrough', 'dragwyb-click-to-chat')}>
																<s>S</s>
															</button>
															<span className="dctc-sc-wysiwyg-divider"></span>
															<button type="button" className="dctc-sc-wysiwyg-btn" onClick={() => applyReplyFormatting('link')} title={__('Insert Link', 'dragwyb-click-to-chat')}>
																<span className="dashicons dashicons-admin-links"></span>
															</button>
															<button type="button" className="dctc-sc-wysiwyg-btn" onClick={() => applyReplyFormatting('ul')} title={__('Bullet List', 'dragwyb-click-to-chat')}>
																<span className="dashicons dashicons-editor-ul"></span>
															</button>
															<button type="button" className="dctc-sc-wysiwyg-btn" onClick={() => applyReplyFormatting('ol')} title={__('Numbered List', 'dragwyb-click-to-chat')}>
																<span className="dashicons dashicons-editor-ol"></span>
															</button>
															<button type="button" className="dctc-sc-wysiwyg-btn" onClick={() => applyReplyFormatting('quote')} title={__('Blockquote', 'dragwyb-click-to-chat')}>
																<span className="dashicons dashicons-editor-quote"></span>
															</button>
															<button type="button" className="dctc-sc-wysiwyg-btn" onClick={() => applyReplyFormatting('code')} title={__('Code Block', 'dragwyb-click-to-chat')}>
																<span className="dashicons dashicons-editor-code"></span>
															</button>
														</div>
													)}

													<textarea
														ref={replyTextareaRef}
														rows="4"
														placeholder={__('Write a standard formatted response to the customer...', 'dragwyb-click-to-chat')}
														value={replyText}
														onChange={(e) => setReplyText(e.target.value)}
														className={`dctc-sc-composer-input ${replyEditorMode === 'text' ? 'text-mode-font' : ''}`}
													/>
												</div>

												<div className="dctc-sc-composer-bottom-bar">
													<div className="dctc-sc-bottom-left">
														{replyAttachments.length > 0 && (
															<span className="dctc-sc-attached-count">
																{replyAttachments.length} {__('file(s) attached', 'dragwyb-click-to-chat')}
															</span>
														)}
														{activeViewers && activeViewers.length > 0 && (
															<div className="dctc-sc-active-viewers-dock" title={__('Active agents viewing this ticket', 'dragwyb-click-to-chat')}>
																<div className="dctc-sc-viewers-avatar-stack">
																	{activeViewers.map((viewer) => (
																		<div
																			key={viewer.user_id}
																			className="dctc-sc-viewer-avatar-circle"
																			title={`${viewer.name} (Watching now)`}
																		>
																			{viewer.avatar ? (
																				<img src={viewer.avatar} alt={viewer.name} />
																			) : (
																				<span>{viewer.initials || 'AG'}</span>
																			)}
																			<span className="dctc-sc-viewer-pulse-dot"></span>
																		</div>
																	))}
																</div>
																<span className="dctc-sc-viewers-text">
																	{activeViewers.map((v) => v.name).join(', ')}
																</span>
															</div>
														)}
													</div>

													<div className="dctc-sc-bottom-right">
														<label className="dctc-sc-checkbox-label">
															<input
																type="checkbox"
																checked={markAsResolved}
																onChange={(e) => setMarkAsResolved(e.target.checked)}
															/>
															{__('Mark as resolved', 'dragwyb-click-to-chat')}
														</label>

														<button
															type="button"
															onClick={() => setIsComposerOpen(false)}
															className="dctc-sc-secondary-btn"
															style={{ marginRight: '6px' }}
														>
															{__('Cancel', 'dragwyb-click-to-chat')}
														</button>

														<button
															type="submit"
															disabled={submitting || !replyText.trim()}
															className="dctc-sc-primary-send-btn"
														>
															<span className="dashicons dashicons-send"></span>
															{submitting ? __('Sending...', 'dragwyb-click-to-chat') : __('Send Reply', 'dragwyb-click-to-chat')}
														</button>
													</div>
												</div>
											</form>
										) : (
											<form onSubmit={onNoteSubmit} className="dctc-sc-composer-main-form note-mode">
												<textarea
													rows="3"
													placeholder={__('Add a private note visible only to support staff...', 'dragwyb-click-to-chat')}
													value={noteText}
													onChange={(e) => setNoteText(e.target.value)}
													className="dctc-sc-composer-input note-input"
												/>

												<div className="dctc-sc-composer-bottom-bar">
													<div className="dctc-sc-bottom-left">
														<label className="dctc-sc-checkbox-label">
															<input
																type="checkbox"
																checked={isPinnedNote}
																onChange={(e) => setIsPinnedNote(e.target.checked)}
															/>
															{__('Pin note to top', 'dragwyb-click-to-chat')}
														</label>
													</div>

													<div className="dctc-sc-bottom-right">
														<button
															type="button"
															onClick={() => setIsComposerOpen(false)}
															className="dctc-sc-secondary-btn"
															style={{ marginRight: '6px' }}
														>
															{__('Cancel', 'dragwyb-click-to-chat')}
														</button>

														<button
															type="submit"
															disabled={submitting || !noteText.trim()}
															className="dctc-sc-primary-send-btn note-save-btn"
														>
															<span className="dashicons dashicons-saved"></span>
															{submitting ? __('Saving...', 'dragwyb-click-to-chat') : __('Save Note', 'dragwyb-click-to-chat')}
														</button>
													</div>
												</div>
											</form>
										)}
									</div>
								)}

								{/* AI TYPING INDICATOR (Newest ongoing event) */}
								{Boolean(selectedTicket?.ai_response_waiting || selectedTicket?.ai_response) && (
									<div className="dctc-sc-message-bubble-row agent-row dctc-sc-ai-typing-row">
										<div className="dctc-sc-msg-avatar">
											AI
										</div>
										<div className="dctc-sc-msg-body-wrap">
											<div className="dctc-sc-msg-header-info">
												<span className="dctc-sc-msg-sender-name">
													{__('AI Assistant', 'dragwyb-click-to-chat')}
												</span>
												<span className="dctc-sc-msg-timestamp">
													{__('Typing...', 'dragwyb-click-to-chat')}
												</span>
											</div>
											<div className="dctc-sc-msg-bubble-content dctc-sc-ai-typing-bubble" style={{ display: 'inline-flex', alignItems: 'center', gap: '8px', padding: '9px 14px', background: '#f0fdf4', border: '1px solid #bbf7d0', borderRadius: '12px' }}>
												<span className="dctc-sc-typing-text" style={{ fontStyle: 'italic', color: '#166534', fontSize: '13px' }}>
													{__('AI Assistant is generating a reply...', 'dragwyb-click-to-chat')}
												</span>
												<span className="dctc-chat-typing-dots" style={{ display: 'inline-flex', gap: '3px' }}>
													<span style={{ width: '5px', height: '5px', backgroundColor: '#16a34a', borderRadius: '50%', display: 'inline-block' }}></span>
													<span style={{ width: '5px', height: '5px', backgroundColor: '#16a34a', borderRadius: '50%', display: 'inline-block' }}></span>
													<span style={{ width: '5px', height: '5px', backgroundColor: '#16a34a', borderRadius: '50%', display: 'inline-block' }}></span>
												</span>
											</div>
										</div>
									</div>
								)}

								{/* MESSAGES LIST (New to Old: newest reply shown on top) */}
								{messagesNewToOld.length === 0 ? (
									<div className="dctc-sc-empty-stream">
										<p>{__('No messages in this ticket yet.', 'dragwyb-click-to-chat')}</p>
									</div>
								) : (
									messagesNewToOld.map((msg, idx) => {
										const isCustomer = msg.sender_type === 'customer' || msg.role === 'user';
										const isHumanAgent = msg.sender_type === 'human_agent' || msg.sender_type === 'agent';
										const isAI = !isHumanAgent && (msg.sender_type === 'ai_agent' || msg.sender_type === 'bot' || msg.role === 'assistant');

										const avatarInitials = isCustomer
											? getInitials(selectedTicket?.customer_name, selectedTicket?.customer_email || selectedTicket?.session_id)
											: (isAI ? 'AI' : ((userPermissions.agent_name || 'Admin').substring(0, 2).toUpperCase()));

										return (
											<div
												key={msg.id ? `msg-${msg.id}` : (msg.uuid ? `msg-${msg.uuid}` : `msg-idx-${idx}`)}
												data-index={idx}
												data-msg-uuid={selectedTicket?.uuid || ''}
												data-msg-id={msg.id || ''}
												className={`dctc-sc-message-bubble-row ${isCustomer ? 'customer-row' : 'agent-row'}`}
											>
												<div className="dctc-sc-msg-avatar">
													{avatarInitials}
												</div>

												<div className="dctc-sc-msg-body-wrap">
													<div className="dctc-sc-msg-header-info">
														<span className="dctc-sc-msg-sender-name">
															{isCustomer
																? (selectedTicket?.customer_name || 'Guest Visitor')
																: (isAI ? __('AI Assistant', 'dragwyb-click-to-chat') : (msg.sender_name || 'admin'))}
														</span>
														<span className="dctc-sc-msg-timestamp">
															{msg.created_at || ''}
														</span>
													</div>

													<div
														className="dctc-sc-msg-bubble-content"
														dangerouslySetInnerHTML={{ __html: msg.content || '' }}
													/>

													{!isCustomer && (
														<div className="dctc-sc-msg-status-receipt">
															<span className="dctc-sc-double-check">✓✓</span>
														</div>
													)}
												</div>
											</div>
										);
									})
								)}
							</div>
						)}

						{/* INTERNAL NOTES TAB */}
						{workspaceTab === 'notes' && (
							<div className="dctc-sc-notes-tab-content">
								<div className="dctc-sc-notes-list">
									{(selectedTicket?.notes || []).length === 0 ? (
										<p className="dctc-sc-empty-notes">{__('No internal notes added yet.', 'dragwyb-click-to-chat')}</p>
									) : (
										selectedTicket.notes.map((n) => (
											<div key={n.id} className={`dctc-sc-note-card ${n.is_pinned ? 'is-pinned' : ''}`}>
												<div className="dctc-sc-note-card-header">
													<strong>{n.author_name}</strong>
													<span>{n.created_at}</span>
												</div>
												<div className="dctc-sc-note-card-body">{n.note}</div>
											</div>
										))
									)}
								</div>
							</div>
						)}

						{/* ACTIVITY TAB */}
						{workspaceTab === 'activity' && (
							<div className="dctc-sc-tab-pane">
								<h4 style={{ margin: '0 0 12px', fontSize: '13.5px', color: '#0f172a' }}>{__('Ticket Audit Trail & System Events', 'dragwyb-click-to-chat')}</h4>
								{(selectedTicket?.events || []).map((evt, idx) => (
									<div key={idx} className="dctc-sc-audit-log-row">
										<span className="dashicons dashicons-marker"></span>
										<span style={{ flex: 1 }}><strong>{evt.actor_name}</strong> ({evt.event_type}) {evt.new_value || ''}</span>
										<span className="dctc-sc-audit-time">{evt.created_at}</span>
									</div>
								))}
							</div>
						)}
					</div>
				)}
			</main>
		</>
	);
}
