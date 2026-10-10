import apiFetch from '@wordpress/api-fetch';
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
	onRefreshTicketDetails,
	onShowNotice,
}) {
	const [workspaceTab, setWorkspaceTab] = useState('conversation');
	const [composerMode, setComposerMode] = useState('reply'); // 'reply' | 'note'
	const [isComposerOpen, setIsComposerOpen] = useState(false);
	const replyTextareaRef = useRef(null);

	// Message Action Dropdown and Inline Edit States
	const [activeMessageMenuKey, setActiveMessageMenuKey] = useState(null);
	const [editingMessageKey, setEditingMessageKey] = useState(null);
	const [editMessageContent, setEditMessageContent] = useState('');
	const [editEditorMode, setEditEditorMode] = useState('visual');
	const [editSubmitting, setEditSubmitting] = useState(false);
	const editMessageTextareaRef = useRef(null);

	// Close message menu when clicking outside
	useEffect(() => {
		const handleClickOutside = (e) => {
			if (!e.target.closest('.dctc-sc-msg-menu-container')) {
				setActiveMessageMenuKey(null);
			}
		};
		document.addEventListener('click', handleClickOutside);
		return () => document.removeEventListener('click', handleClickOutside);
	}, []);

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

	const applyEditFormatting = (tagType) => {
		const textarea = editMessageTextareaRef.current;
		if (!textarea) return;
		const start = textarea.selectionStart || 0;
		const end = textarea.selectionEnd || 0;
		const text = editMessageContent || '';
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
		setEditMessageContent(updated);
		setTimeout(() => {
			if (textarea) {
				textarea.focus();
				textarea.setSelectionRange(start + replacement.length, start + replacement.length);
			}
		}, 50);
	};

	const handleStartEditMessage = (msg, msgIdentifier) => {
		setEditingMessageKey(msgIdentifier);
		setEditMessageContent(msg.content || '');
		setEditEditorMode('visual');
		setActiveMessageMenuKey(null);
		setTimeout(() => {
			if (editMessageTextareaRef.current) {
				editMessageTextareaRef.current.focus();
			}
		}, 60);
	};

	const handleSaveEditMessage = async (msgIdentifier) => {
		if (!selectedTicket?.id || !msgIdentifier || !editMessageContent.trim()) return;
		setEditSubmitting(true);
		try {
			const data = await apiFetch({
				path: `/dctc-ai/v1/support/tickets/${selectedTicket.id}/message-edit`,
				method: 'POST',
				data: {
					message_id: msgIdentifier,
					content: editMessageContent,
				},
			});
			if (data?.success) {
				setEditingMessageKey(null);
				setEditMessageContent('');
				if (onRefreshTicketDetails) {
					onRefreshTicketDetails(selectedTicket.id, true);
				}
				if (onShowNotice) {
					onShowNotice(__('Message updated successfully.', 'dragwyb-click-to-chat'), 'success');
				}
			}
		} catch (err) {
			console.error('Error updating message:', err);
			if (onShowNotice) {
				onShowNotice(__('Failed to update message.', 'dragwyb-click-to-chat'), 'error');
			}
		} finally {
			setEditSubmitting(false);
		}
	};

	const handleDeleteMessage = async (msgIdentifier) => {
		if (!selectedTicket?.id || !msgIdentifier) return;
		if (!window.confirm(__('Are you sure you want to delete this message?', 'dragwyb-click-to-chat'))) {
			return;
		}
		try {
			const data = await apiFetch({
				path: `/dctc-ai/v1/support/tickets/${selectedTicket.id}/message-delete`,
				method: 'POST',
				data: {
					message_id: msgIdentifier,
				},
			});
			if (data?.success) {
				setActiveMessageMenuKey(null);
				if (editingMessageKey === msgIdentifier) {
					setEditingMessageKey(null);
				}
				if (onRefreshTicketDetails) {
					onRefreshTicketDetails(selectedTicket.id, true);
				}
				if (onShowNotice) {
					onShowNotice(__('Message deleted successfully.', 'dragwyb-click-to-chat'), 'success');
				}
			}
		} catch (err) {
			console.error('Error deleting message:', err);
			if (onShowNotice) {
				onShowNotice(__('Failed to delete message.', 'dragwyb-click-to-chat'), 'error');
			}
		}
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
			{/* MAIN WORKSPACE */}
			<main className="dctc-sc-col-main">
				{!selectedTicket ? (
					<div className="dctc-sc-no-selection">
						<span className="dashicons dashicons-format-chat"></span>
						<h3>{__('Select a ticket to begin support', 'dragwyb-click-to-chat')}</h3>
					</div>
				) : (
					<div className="dctc-sc-workspace-inner">
						{/* TOP HEADER BAR WITH LONG TITLE WRAPPING */}
						<div className="dctc-sc-ws-header">
							<div className="dctc-sc-ws-header-title-wrap">
								<h2 className="dctc-sc-ws-subject">
									<span className="dctc-sc-ws-subject-text">{selectedTicket?.subject || __('Untitled Ticket', 'dragwyb-click-to-chat')}</span>
									<span className="dctc-sc-ws-ticket-id">#{selectedTicket?.ticket_number || selectedTicket?.id}</span>
								</h2>
							</div>

							<div className="dctc-sc-ws-header-actions">
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

						{/* WORKSPACE SUBTABS & TOP ACTIONS BAR */}
						<div className="dctc-sc-workspace-tabs-bar">
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
							</div>

							<div className="dctc-sc-ws-tab-actions">
								<button
									type="button"
									className={`dctc-sc-header-action-btn reply-btn ${isComposerOpen && composerMode === 'reply' ? 'active' : ''}`}
									onClick={() => handleOpenComposer('reply')}
								>
									<span className="dashicons dashicons-undo"></span>
									<span>{__('Add Reply', 'dragwyb-click-to-chat')}</span>
								</button>
								<button
									type="button"
									className={`dctc-sc-header-action-btn note-btn ${isComposerOpen && composerMode === 'note' ? 'active' : ''}`}
									onClick={() => handleOpenComposer('note')}
								>
									<span className="dashicons dashicons-lock"></span>
									<span>{__('Add Note', 'dragwyb-click-to-chat')}</span>
								</button>
							</div>
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

								{/* INLINE WYSIWYG / NOTE COMPOSER */}
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
																className="dctc-pro dctc-sc-composer-ai-btn"
																onClick={handleAiSuggestClick}
																disabled={aiSuggestLoading}
																title={__('Suggest AI Reply (Dragwyb Pro Feature)', 'dragwyb-click-to-chat')}
																data-pro-feature="ai-reply-suggest"
															>
																<span className="dashicons dashicons-superhero"></span>
																<span>{aiSuggestLoading ? __('Thinking...', 'dragwyb-click-to-chat') : __('AI Suggest', 'dragwyb-click-to-chat')}</span>
																<span className="dctc-pro-badge" style={{ fontSize: '9px', padding: '1px 4px', marginLeft: '4px', background: 'rgba(255,255,255,0.25)' }}>PRO</span>
															</button>

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
											: (isAI ? 'AI' : getInitials(msg.sender_name || userPermissions.agent_name || 'AD'));

										const senderName = isCustomer
											? (selectedTicket?.customer_name || 'Guest Visitor')
											: (isAI ? __('AI Assistant', 'dragwyb-click-to-chat') : (msg.sender_name || userPermissions.agent_name || 'Staff Member'));

										const msgIdentifier = msg.id || msg.uuid || (msg.created_at ? msg.created_at : String(rawMessages.length - 1 - idx));
										const msgKey = `msg-${msgIdentifier}`;
										const isEditingThisMsg = editingMessageKey === msgIdentifier;

										const isCustomerSeen = Boolean(
											msg.is_read ||
											msg.read ||
											msg.seen ||
											(selectedTicket?.customer_last_seen_at && msg.created_at && (new Date(selectedTicket.customer_last_seen_at).getTime() >= new Date(msg.created_at).getTime()))
										);

										return (
											<div
												key={msgKey}
												data-index={idx}
												data-msg-uuid={selectedTicket?.uuid || ''}
												data-msg-id={msgIdentifier}
												className={`dctc-sc-message-bubble-row ${isCustomer ? 'customer-row' : (isAI ? 'agent-row ai-row' : 'agent-row')} ${isEditingThisMsg ? 'is-editing-mode' : ''}`}
											>
												<div className={`dctc-sc-msg-avatar ${isCustomer ? 'cust-avatar' : (isAI ? 'ai-avatar' : 'agent-avatar')}`}>
													{avatarInitials}
												</div>

												<div className="dctc-sc-msg-body-wrap">
													<div className="dctc-sc-msg-header-info">
														<span className="dctc-sc-msg-sender-name">
															{senderName}
														</span>
														<span className="dctc-sc-msg-timestamp">
															{msg.created_at || ''}
															{msg.is_edited && <span className="dctc-sc-msg-edited-badge">({__('edited', 'dragwyb-click-to-chat')})</span>}
														</span>

														{msg.sender_type !== 'customer' &&
															<div className="dctc-sc-msg-menu-container">
																<button
																	type="button"
																	className={`dctc-sc-msg-menu-btn ${activeMessageMenuKey === msgKey ? 'active' : ''}`}
																	onClick={(e) => {
																		e.stopPropagation();
																		setActiveMessageMenuKey((prev) => (prev === msgKey ? null : msgKey));
																	}}
																	title={__('Message options', 'dragwyb-click-to-chat')}
																>
																	<span className="dashicons dashicons-ellipsis"></span>
																</button>

																{activeMessageMenuKey === msgKey && (
																	<div className="dctc-sc-msg-dropdown-menu">
																		<button
																			type="button"
																			className="dctc-sc-msg-dropdown-item"
																			onClick={() => handleStartEditMessage(msg, msgIdentifier)}
																		>
																			<span className="dashicons dashicons-edit"></span>
																			<span>{__('Edit Message', 'dragwyb-click-to-chat')}</span>
																		</button>
																		<button
																			type="button"
																			className="dctc-sc-msg-dropdown-item is-delete"
																			onClick={() => handleDeleteMessage(msgIdentifier)}
																		>
																			<span className="dashicons dashicons-trash"></span>
																			<span>{__('Delete Message', 'dragwyb-click-to-chat')}</span>
																		</button>
																	</div>
																)}
															</div>
														}
													</div>

													{isEditingThisMsg ? (
														<div className="dctc-sc-msg-inline-editor">
															<div className="dctc-sc-wysiwyg-wrapper">
																<div className="dctc-sc-wysiwyg-header-tabs">
																	<div className="dctc-sc-wysiwyg-mode-switch">
																		<button
																			type="button"
																			className={`dctc-sc-editor-mode-btn ${editEditorMode === 'visual' ? 'active' : ''}`}
																			onClick={() => setEditEditorMode('visual')}
																		>
																			{__('Visual', 'dragwyb-click-to-chat')}
																		</button>
																		<button
																			type="button"
																			className={`dctc-sc-editor-mode-btn ${editEditorMode === 'text' ? 'active' : ''}`}
																			onClick={() => setEditEditorMode('text')}
																		>
																			{__('Text', 'dragwyb-click-to-chat')}
																		</button>
																	</div>
																</div>

																{editEditorMode === 'visual' && (
																	<div className="dctc-sc-wysiwyg-toolbar">
																		<button type="button" className="dctc-sc-wysiwyg-btn" onClick={() => applyEditFormatting('bold')} title={__('Bold', 'dragwyb-click-to-chat')}>
																			<strong>B</strong>
																		</button>
																		<button type="button" className="dctc-sc-wysiwyg-btn" onClick={() => applyEditFormatting('italic')} title={__('Italic', 'dragwyb-click-to-chat')}>
																			<em>I</em>
																		</button>
																		<button type="button" className="dctc-sc-wysiwyg-btn" onClick={() => applyEditFormatting('underline')} title={__('Underline', 'dragwyb-click-to-chat')}>
																			<u>U</u>
																		</button>
																		<span className="dctc-sc-wysiwyg-divider"></span>
																		<button type="button" className="dctc-sc-wysiwyg-btn" onClick={() => applyEditFormatting('link')} title={__('Insert Link', 'dragwyb-click-to-chat')}>
																			<span className="dashicons dashicons-admin-links"></span>
																		</button>
																		<button type="button" className="dctc-sc-wysiwyg-btn" onClick={() => applyEditFormatting('ul')} title={__('Bullet List', 'dragwyb-click-to-chat')}>
																			<span className="dashicons dashicons-editor-ul"></span>
																		</button>
																		<button type="button" className="dctc-sc-wysiwyg-btn" onClick={() => applyEditFormatting('ol')} title={__('Numbered List', 'dragwyb-click-to-chat')}>
																			<span className="dashicons dashicons-editor-ol"></span>
																		</button>
																		<button type="button" className="dctc-sc-wysiwyg-btn" onClick={() => applyEditFormatting('quote')} title={__('Blockquote', 'dragwyb-click-to-chat')}>
																			<span className="dashicons dashicons-editor-quote"></span>
																		</button>
																		<button type="button" className="dctc-sc-wysiwyg-btn" onClick={() => applyEditFormatting('code')} title={__('Code Block', 'dragwyb-click-to-chat')}>
																			<span className="dashicons dashicons-editor-code"></span>
																		</button>
																	</div>
																)}

																<textarea
																	ref={editMessageTextareaRef}
																	rows="4"
																	value={editMessageContent}
																	onChange={(e) => setEditMessageContent(e.target.value)}
																	className={`dctc-sc-wysiwyg-textarea ${editEditorMode === 'text' ? 'text-mode-font' : ''}`}
																/>

																<div className="dctc-sc-inline-edit-footer">
																	<button
																		type="button"
																		className="dctc-sc-edit-cancel-btn"
																		onClick={() => { setEditingMessageKey(null); setEditMessageContent(''); }}
																		disabled={editSubmitting}
																	>
																		{__('Discard', 'dragwyb-click-to-chat')}
																	</button>
																	<button
																		type="button"
																		className="dctc-sc-edit-save-btn"
																		onClick={() => handleSaveEditMessage(msgIdentifier)}
																		disabled={editSubmitting || !editMessageContent.trim()}
																	>
																		{editSubmitting ? __('Updating...', 'dragwyb-click-to-chat') : __('Update', 'dragwyb-click-to-chat')}
																	</button>
																</div>
															</div>
														</div>
													) : (
														<div
															className="dctc-sc-msg-bubble-content"
															dangerouslySetInnerHTML={{ __html: msg.content || '' }}
														/>
													)}

													{!isCustomer && (
														<div className="dctc-sc-msg-status-receipt" title={isCustomerSeen ? __('Read by customer', 'dragwyb-click-to-chat') : __('Sent', 'dragwyb-click-to-chat')}>
															{isCustomerSeen ? (
																<span className="dctc-sc-double-check is-seen">✓✓</span>
															) : (
																<span className="dctc-sc-single-check">✓</span>
															)}
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
					</div>
				)}
			</main>
		</>
	);
}
