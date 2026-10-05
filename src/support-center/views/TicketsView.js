/**
 * Support Center - Modern, Compact & Clean Tickets Workspace View
 * Expandable/Collapsible Sidebar, Full-Width All-Tickets View, and Clean Ticket Detail Workspace
 */
import { useState, useRef, useEffect, useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

export default function TicketsView({
	tickets = [],
	totalTickets = 0,
	loading = false,
	currentPage = 1,
	totalPages = 1,
	setCurrentPage,
	statusFilter = 'all',
	setStatusFilter,
	priorityFilter = 'all',
	setPriorityFilter,
	categoryFilter = 'all',
	setCategoryFilter,
	searchQuery = '',
	setSearchQuery,
	selectedTicketId,
	setSelectedTicketId,
	selectedTicket,
	ticketLoading = false,
	categories = [],
	agents = [],
	tags = [],
	wcData = null,
	wcLoading = false,
	onRefreshTickets,
	onRefreshTicketDetails,
	onShowNotice,
	userPermissions = {},
}) {
	const canAssign = !(userPermissions.is_admin === false && !userPermissions.assign_ticket && !userPermissions.reassign_ticket);
	const canChangePriority = !(userPermissions.is_admin === false && !userPermissions.change_priority);
	const canChangeStatus = !(userPermissions.is_admin === false && userPermissions.change_status === false);
	const canTakeControl = !(userPermissions.is_admin === false && !userPermissions.take_ai_control && !userPermissions.release_ai_control);
	const canInternalNote = !(userPermissions.is_admin === false && userPermissions.internal_note === false);

	// Left Folders & Views sidebar: Collapsed by default
	const [isFoldersExpanded, setIsFoldersExpanded] = useState(false);

	// Active View / Folder filter
	const [activeFolder, setActiveFolder] = useState('all');
	const [activeView, setActiveView] = useState(null);

	// Expandable settings states (hidden by default for clean, uncluttered layout)
	const [showMoreFilters, setShowMoreFilters] = useState(false);
	const [showCustomerSession, setShowCustomerSession] = useState(false);
	const [showAdvancedProps, setShowAdvancedProps] = useState(false);

	// Workspace Subtabs: 'conversation' | 'notes' | 'activity'
	const [workspaceTab, setWorkspaceTab] = useState('conversation');

	// Filter toolbar secondary states
	const [assignedToFilter, setAssignedToFilter] = useState('all');
	const [productFilter, setProductFilter] = useState('all');
	const [tagFilter, setTagFilter] = useState('all');
	const [dateRangeFilter, setDateRangeFilter] = useState('all');
	const [customerTypeFilter, setCustomerTypeFilter] = useState('all');
	const [sortBy, setSortBy] = useState('newest');

	// Composer state
	const [composerMode, setComposerMode] = useState('reply'); // 'reply' | 'note'
	const [replyText, setReplyText] = useState('');
	const [noteText, setNoteText] = useState('');
	const [isPinnedNote, setIsPinnedNote] = useState(false);
	const [markAsResolved, setMarkAsResolved] = useState(false);
	const [submitting, setSubmitting] = useState(false);
	const [aiSuggestLoading, setAiSuggestLoading] = useState(false);

	// New Ticket Modal state
	const [isNewTicketModalOpen, setIsNewTicketModalOpen] = useState(false);
	const [newTicketData, setNewTicketData] = useState({
		subject: '',
		customer_name: '',
		customer_email: '',
		category_id: '',
		priority: 'normal',
		message: '',
		attachments: [],
	});
	const [creatingTicket, setCreatingTicket] = useState(false);
	const modalMessageInputRef = useRef(null);

	// Multi-select tickets state
	const [selectedTicketIds, setSelectedTicketIds] = useState([]);

	// Starred & Flagged local states
	const [starredTickets, setStarredTickets] = useState({});
	const [flaggedTickets, setFlaggedTickets] = useState({});

	const timelineEndRef = useRef(null);

	useEffect(() => {
		if (timelineEndRef.current) {
			timelineEndRef.current.scrollIntoView({ behavior: 'smooth' });
		}
	}, [selectedTicket?.messages, selectedTicket?.events]);

	// Auto background polling for live updates
	useEffect(() => {
		if (!selectedTicketId) return;
		let isCancelled = false;

		const interval = setInterval(() => {
			if (typeof document !== 'undefined' && document.hidden) return;
			if (!isCancelled && selectedTicketId) {
				onRefreshTicketDetails(selectedTicketId, true);
			}
		}, 5000);

		return () => {
			isCancelled = true;
			clearInterval(interval);
		};
	}, [selectedTicketId, onRefreshTicketDetails]);

	// Counts calculation for folders and views
	const folderCounts = useMemo(() => {
		const counts = {
			all: totalTickets || tickets.length,
			open: 0,
			pending: 0,
			my: 0,
			unassigned: 0,
			resolved: 0,
			closed: 0,
			spam: 0,
			trash: 0,
			high_priority: 0,
			waiting_reply: 0,
			today: 0,
			this_week: 0,
			ai_suggested: 0,
			overdue: 0,
		};

		tickets.forEach((t) => {
			if (t.status === 'open') counts.open++;
			if (t.status === 'pending' || t.status === 'waiting_customer') counts.pending++;
			if (t.status === 'resolved') counts.resolved++;
			if (t.status === 'closed') counts.closed++;
			if (t.status === 'trash') counts.trash++;
			if (!t.assigned_agent_id || t.assigned_agent_id === 0) counts.unassigned++;
			if (t.priority === 'high' || t.priority === 'urgent') counts.high_priority++;
			if (t.status === 'pending') counts.waiting_reply++;
		});

		return counts;
	}, [tickets, totalTickets]);

	// Count active secondary filters
	const activeSecondaryFilterCount = useMemo(() => {
		let count = 0;
		if (priorityFilter !== 'all') count++;
		if (categoryFilter !== 'all') count++;
		if (productFilter !== 'all') count++;
		if (assignedToFilter !== 'all') count++;
		if (tagFilter !== 'all') count++;
		if (dateRangeFilter !== 'all') count++;
		if (customerTypeFilter !== 'all') count++;
		return count;
	}, [priorityFilter, categoryFilter, productFilter, assignedToFilter, tagFilter, dateRangeFilter, customerTypeFilter]);

	// Priority badge helper
	const getPriorityBadgeClass = (priority) => {
		switch (priority?.toLowerCase()) {
			case 'urgent': return 'dctc-sc-badge-urgent';
			case 'high': return 'dctc-sc-badge-high';
			case 'normal': return 'dctc-sc-badge-normal';
			default: return 'dctc-sc-badge-low';
		}
	};

	// Status badge helper
	const getStatusBadgeClass = (status) => {
		switch (status?.toLowerCase()) {
			case 'open': return 'dctc-sc-badge-open';
			case 'pending': return 'dctc-sc-badge-pending';
			case 'waiting_customer': return 'dctc-sc-badge-waiting';
			case 'resolved': return 'dctc-sc-badge-resolved';
			case 'closed': return 'dctc-sc-badge-closed';
			case 'trash': return 'dctc-sc-badge-trash';
			default: return 'dctc-sc-badge-secondary';
		}
	};

	// Customer initials helper
	const getInitials = (name, email) => {
		const target = name || email || 'Guest Visitor';
		const parts = target.trim().split(/[\s_@.-]+/);
		if (parts.length >= 2) {
			return (parts[0][0] + parts[1][0]).toUpperCase();
		}
		return target.substring(0, 2).toUpperCase();
	};

	// Relative time helper
	const formatRelativeTime = (dateStr) => {
		if (!dateStr) return '';
		try {
			const date = new Date(dateStr);
			if (isNaN(date.getTime())) return dateStr;
			const diffSecs = Math.floor((new Date() - date) / 1000);
			if (diffSecs < 60) return 'Just now';
			if (diffSecs < 3600) return `${Math.floor(diffSecs / 60)}m ago`;
			if (diffSecs < 86400) return `${Math.floor(diffSecs / 3600)}h ago`;
			if (diffSecs < 604800) return `${Math.floor(diffSecs / 86400)}d ago`;
			return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
		} catch (e) {
			return dateStr;
		}
	};

	// Handle Folder click
	const handleSelectFolder = (folderKey) => {
		setActiveFolder(folderKey);
		setActiveView(null);
		setCurrentPage(1);
		if (folderKey === 'all') {
			setStatusFilter('all');
		} else if (['open', 'pending', 'resolved', 'closed', 'trash'].includes(folderKey)) {
			setStatusFilter(folderKey);
		} else if (folderKey === 'unassigned') {
			setStatusFilter('all');
		}
	};

	// Handle View click
	const handleSelectView = (viewKey) => {
		setActiveView(viewKey);
		setActiveFolder(null);
		setCurrentPage(1);
		if (viewKey === 'high_priority') {
			setPriorityFilter('high');
			setStatusFilter('all');
		} else if (viewKey === 'waiting_reply') {
			setStatusFilter('pending');
		}
	};

	// Action: Open Ticket (automatically collapses left sidebar)
	const handleOpenTicket = (ticketId) => {
		setSelectedTicketId(ticketId);
		setIsFoldersExpanded(false);
	};

	// Action: Close Ticket / Go Back to All Tickets View
	const handleCloseTicket = () => {
		setSelectedTicketId(null);
	};

	// Action: Take Control / Release Control
	const handleToggleControl = async () => {
		if (!selectedTicketId) return;
		const nextMode = selectedTicket?.control_mode === 'human' ? 'ai' : 'human';
		try {
			const data = await apiFetch({
				path: `/dctc-ai/v1/support/tickets/${selectedTicketId}/control`,
				method: 'POST',
				data: { mode: nextMode },
			});
			if (data?.success) {
				onRefreshTicketDetails(selectedTicketId);
				onShowNotice(
					nextMode === 'human'
						? __('You took control. AI is now paused.', 'dragwyb-click-to-chat')
						: __('Conversation handed back to AI Assistant.', 'dragwyb-click-to-chat'),
					'success'
				);
			}
		} catch (err) {
			console.error('Error toggling control:', err);
			onShowNotice(__('Failed to toggle control mode.', 'dragwyb-click-to-chat'), 'error');
		}
	};

	// Action: Change Status
	const handleStatusChange = async (newStatus) => {
		if (!selectedTicketId) return;
		try {
			const data = await apiFetch({
				path: `/dctc-ai/v1/support/tickets/${selectedTicketId}/status`,
				method: 'POST',
				data: { status: newStatus },
			});
			if (data?.success) {
				onRefreshTicketDetails(selectedTicketId);
				onRefreshTickets();
				onShowNotice(__('Ticket status updated.', 'dragwyb-click-to-chat'), 'success');
			}
		} catch (err) {
			console.error('Error updating status:', err);
			onShowNotice(__('Failed to update status.', 'dragwyb-click-to-chat'), 'error');
		}
	};

	// Action: Change Priority
	const handlePriorityChange = async (newPriority) => {
		if (!selectedTicketId) return;
		try {
			const data = await apiFetch({
				path: `/dctc-ai/v1/support/tickets/${selectedTicketId}/priority`,
				method: 'POST',
				data: { priority: newPriority },
			});
			if (data?.success) {
				onRefreshTicketDetails(selectedTicketId);
				onRefreshTickets();
				onShowNotice(__('Ticket priority updated.', 'dragwyb-click-to-chat'), 'success');
			}
		} catch (err) {
			console.error('Error updating priority:', err);
			onShowNotice(__('Failed to update priority.', 'dragwyb-click-to-chat'), 'error');
		}
	};

	// Action: Assign Agent
	const handleAssignAgent = async (agentId) => {
		if (!selectedTicketId) return;
		try {
			const data = await apiFetch({
				path: `/dctc-ai/v1/support/tickets/${selectedTicketId}/assign`,
				method: 'POST',
				data: { agent_id: Number(agentId) },
			});
			if (data?.success) {
				onRefreshTicketDetails(selectedTicketId);
				onRefreshTickets();
				onShowNotice(__('Agent assignment updated.', 'dragwyb-click-to-chat'), 'success');
			}
		} catch (err) {
			console.error('Error assigning agent:', err);
			onShowNotice(__('Failed to assign agent.', 'dragwyb-click-to-chat'), 'error');
		}
	};

	// Action: Send Human Agent Reply
	const handleSendReply = async (e) => {
		if (e) e.preventDefault();
		if (!selectedTicketId || !replyText.trim()) return;
		setSubmitting(true);
		try {
			const data = await apiFetch({
				path: `/dctc-ai/v1/support/tickets/${selectedTicketId}/reply`,
				method: 'POST',
				data: { message: replyText.trim() },
			});
			if (data?.success) {
				setReplyText('');
				if (markAsResolved) {
					await handleStatusChange('resolved');
					setMarkAsResolved(false);
				}
				onRefreshTicketDetails(selectedTicketId);
				onRefreshTickets();
				onShowNotice(__('Reply sent to customer.', 'dragwyb-click-to-chat'), 'success');
			}
		} catch (err) {
			console.error('Error sending reply:', err);
			onShowNotice(__('Failed to send reply.', 'dragwyb-click-to-chat'), 'error');
		} finally {
			setSubmitting(false);
		}
	};

	// Action: Add Internal Staff Note
	const handleAddNote = async (e) => {
		if (e) e.preventDefault();
		if (!selectedTicketId || !noteText.trim()) return;
		setSubmitting(true);
		try {
			const data = await apiFetch({
				path: `/dctc-ai/v1/support/tickets/${selectedTicketId}/notes`,
				method: 'POST',
				data: { note: noteText.trim(), is_pinned: isPinnedNote },
			});
			if (data?.success) {
				setNoteText('');
				setIsPinnedNote(false);
				onRefreshTicketDetails(selectedTicketId);
				onShowNotice(__('Internal note recorded.', 'dragwyb-click-to-chat'), 'success');
			}
		} catch (err) {
			console.error('Error saving note:', err);
			onShowNotice(__('Failed to save note.', 'dragwyb-click-to-chat'), 'error');
		} finally {
			setSubmitting(false);
		}
	};

	// Action: Suggest AI Reply
	const handleSuggestAiReply = async () => {
		if (!selectedTicketId) return;
		setAiSuggestLoading(true);
		try {
			const data = await apiFetch({
				path: `/dctc-ai/v1/support/tickets/${selectedTicketId}/ai-suggest-reply`,
				method: 'POST',
			});
			if (data?.success && data.suggestion) {
				setComposerMode('reply');
				setReplyText(data.suggestion);
				onShowNotice(__('AI drafted a response based on context!', 'dragwyb-click-to-chat'), 'info');
			}
		} catch (err) {
			console.error('Error generating AI reply:', err);
			onShowNotice(__('Could not generate AI reply.', 'dragwyb-click-to-chat'), 'error');
		} finally {
			setAiSuggestLoading(false);
		}
	};

	// WYSIWYG formatting helper for New Ticket modal
	const applyFormatting = (tagType) => {
		const textarea = modalMessageInputRef.current;
		if (!textarea) return;
		const start = textarea.selectionStart || 0;
		const end = textarea.selectionEnd || 0;
		const text = newTicketData.message || '';
		const selected = text.substring(start, end) || 'sample text';
		let replacement = '';
		if (tagType === 'bold') replacement = `<b>${selected}</b>`;
		else if (tagType === 'italic') replacement = `<i>${selected}</i>`;
		else if (tagType === 'underline') replacement = `<u>${selected}</u>`;
		else if (tagType === 'link') replacement = `<a href="https://example.com">${selected}</a>`;
		else if (tagType === 'ul') replacement = `\n<ul>\n  <li>${selected}</li>\n</ul>\n`;
		else if (tagType === 'ol') replacement = `\n<ol>\n  <li>${selected}</li>\n</ol>\n`;
		else if (tagType === 'quote') replacement = `\n<blockquote>${selected}</blockquote>\n`;
		else if (tagType === 'code') replacement = `<code>${selected}</code>`;

		const updated = text.substring(0, start) + replacement + text.substring(end);
		setNewTicketData((prev) => ({ ...prev, message: updated }));
		setTimeout(() => {
			if (textarea) {
				textarea.focus();
				textarea.setSelectionRange(start + replacement.length, start + replacement.length);
			}
		}, 50);
	};

	// File attachments helper for New Ticket modal
	const handleAttachFiles = () => {
		if (window.wp && window.wp.media) {
			const frame = window.wp.media({
				title: __('Select or Upload Support Files', 'dragwyb-click-to-chat'),
				button: { text: __('Attach Files', 'dragwyb-click-to-chat') },
				multiple: true,
			});
			frame.on('select', () => {
				const selection = frame.state().get('selection').toJSON();
				const newAttachments = selection.map((file) => ({
					id: file.id,
					url: file.url,
					name: file.filename || file.title || 'Attachment',
					type: file.mime || file.type || '',
				}));
				setNewTicketData((prev) => ({
					...prev,
					attachments: [...(prev.attachments || []), ...newAttachments],
				}));
			});
			frame.open();
		} else {
			const input = document.createElement('input');
			input.type = 'file';
			input.multiple = true;
			input.onchange = (e) => {
				const files = Array.from(e.target.files);
				const fileNames = files.map((f) => ({ name: f.name, url: '', type: f.type }));
				setNewTicketData((prev) => ({
					...prev,
					attachments: [...(prev.attachments || []), ...fileNames],
				}));
			};
			input.click();
		}
	};

	// Remove single attachment chip
	const handleRemoveAttachment = (indexToRemove) => {
		setNewTicketData((prev) => ({
			...prev,
			attachments: (prev.attachments || []).filter((_, idx) => idx !== indexToRemove),
		}));
	};

	// Action: Create New Ticket Submit
	const handleCreateTicketSubmit = async (e) => {
		if (e) e.preventDefault();
		if (!newTicketData.subject.trim() || !newTicketData.message.trim()) {
			onShowNotice(__('Subject and message are required.', 'dragwyb-click-to-chat'), 'error');
			return;
		}
		setCreatingTicket(true);
		try {
			const payload = {
				...newTicketData,
				initial_message: newTicketData.message,
			};
			const res = await apiFetch({
				path: '/dctc-ai/v1/support/tickets',
				method: 'POST',
				data: payload,
			});
			if (res?.success) {
				setIsNewTicketModalOpen(false);
				setNewTicketData({
					subject: '',
					customer_name: '',
					customer_email: '',
					category_id: '',
					priority: 'normal',
					message: '',
					attachments: [],
				});
				onShowNotice(__('Ticket created successfully!', 'dragwyb-click-to-chat'), 'success');
				onRefreshTickets();
				if (res.ticket?.id) {
					handleOpenTicket(res.ticket.id);
				}
			}
		} catch (err) {
			console.error('Error creating ticket:', err);
			onShowNotice(__('Failed to create ticket.', 'dragwyb-click-to-chat'), 'error');
		} finally {
			setCreatingTicket(false);
		}
	};

	// Prev / Next ticket navigation
	const handleNavigateTicket = (direction) => {
		if (!tickets.length || !selectedTicketId) return;
		const currentIndex = tickets.findIndex((t) => t.id === selectedTicketId);
		if (currentIndex === -1) return;
		if (direction === 'prev' && currentIndex > 0) {
			handleOpenTicket(tickets[currentIndex - 1].id);
		} else if (direction === 'next' && currentIndex < tickets.length - 1) {
			handleOpenTicket(tickets[currentIndex + 1].id);
		}
	};

	// Reset all filters
	const handleResetFilters = () => {
		setStatusFilter('all');
		setPriorityFilter('all');
		setCategoryFilter('all');
		setAssignedToFilter('all');
		setProductFilter('all');
		setTagFilter('all');
		setDateRangeFilter('all');
		setCustomerTypeFilter('all');
		setSearchQuery('');
		setActiveFolder('all');
		setActiveView(null);
		setCurrentPage(1);
	};

	// Copy session ID
	const handleCopySession = (text) => {
		if (!text) return;
		navigator.clipboard.writeText(text);
		onShowNotice(__('Session ID copied to clipboard!', 'dragwyb-click-to-chat'), 'success');
	};

	// Quick action: Delete Ticket
	const handleDeleteTicket = async () => {
		if (!selectedTicketId) return;
		if (!window.confirm(__('Are you sure you want to delete this ticket?', 'dragwyb-click-to-chat'))) return;
		try {
			await apiFetch({
				path: `/dctc-ai/v1/support/tickets/${selectedTicketId}`,
				method: 'DELETE',
			});
			onShowNotice(__('Ticket deleted.', 'dragwyb-click-to-chat'), 'success');
			setSelectedTicketId(null);
			onRefreshTickets();
		} catch (err) {
			console.error('Error deleting ticket:', err);
			onShowNotice(__('Failed to delete ticket.', 'dragwyb-click-to-chat'), 'error');
		}
	};

	// Toggle star
	const toggleStar = (id) => {
		setStarredTickets((prev) => ({ ...prev, [id]: !prev[id] }));
	};

	// Toggle flag
	const toggleFlag = (id) => {
		setFlaggedTickets((prev) => ({ ...prev, [id]: !prev[id] }));
	};

	// Toggle select checkbox for a ticket
	const toggleTicketSelect = (id, e) => {
		e.stopPropagation();
		setSelectedTicketIds((prev) =>
			prev.includes(id) ? prev.filter((item) => item !== id) : [...prev, id]
		);
	};

	// Filter tickets client-side for additional filters
	const filteredTickets = useMemo(() => {
		let list = [...tickets];
		if (activeFolder === 'unassigned') {
			list = list.filter((t) => !t.assigned_agent_id || t.assigned_agent_id === 0);
		} else if (activeFolder === 'trash') {
			list = list.filter((t) => t.status === 'trash');
		}
		if (activeView === 'high_priority') {
			list = list.filter((t) => t.priority === 'high' || t.priority === 'urgent');
		} else if (activeView === 'waiting_reply') {
			list = list.filter((t) => t.status === 'pending' || t.status === 'waiting_customer');
		}
		if (assignedToFilter !== 'all') {
			list = list.filter((t) => String(t.assigned_agent_id) === String(assignedToFilter));
		}
		if (sortBy === 'oldest') {
			list.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
		} else if (sortBy === 'priority') {
			const pOrder = { urgent: 4, high: 3, normal: 2, low: 1 };
			list.sort((a, b) => (pOrder[b.priority] || 0) - (pOrder[a.priority] || 0));
		}
		return list;
	}, [tickets, activeFolder, activeView, assignedToFilter, sortBy]);

	// Determine if database has 0 tickets or if filters caused 0 matches
	const hasActiveSearchOrFilters = Boolean(
		searchQuery ||
		statusFilter !== 'all' ||
		priorityFilter !== 'all' ||
		categoryFilter !== 'all' ||
		activeSecondaryFilterCount > 0 ||
		(activeFolder && activeFolder !== 'all') ||
		activeView
	);

	const isDatabaseEmpty = !loading && (Number(totalTickets) === 0 && tickets.length === 0) && !hasActiveSearchOrFilters;

	return (
		<div className="dctc-sc-enterprise-container">
			{ /* TOP TOOLBAR */}
			<div className={`dctc-sc-filter-toolbar-wrap ${isDatabaseEmpty ? 'dctc-sc-filter-toolbar-empty' : ''}`}>
				{isDatabaseEmpty ? (
					<div className="dctc-sc-filter-toolbar-main" style={{ justifyContent: 'space-between', width: '100%' }}>
						<div className="dctc-sc-toolbar-brand-badge">
							<span className="dashicons dashicons-tickets-alt" style={{ color: '#6366f1', fontSize: '20px', width: '20px', height: '20px' }}></span>
							<strong style={{ fontSize: '14.5px', color: '#0f172a' }}>{__('Tickets Workspace', 'dragwyb-click-to-chat')}</strong>
							<span style={{ fontSize: '12px', color: '#64748b', background: '#f1f5f9', padding: '3px 10px', borderRadius: '12px', fontWeight: 700 }}>
								{__('0 Tickets', 'dragwyb-click-to-chat')}
							</span>
						</div>
						<div className="dctc-sc-toolbar-actions">
							<button
								type="button"
								className="dctc-sc-new-ticket-btn"
								onClick={() => setIsNewTicketModalOpen(true)}
							>
								<span className="dashicons dashicons-plus"></span>
								{__('New Ticket', 'dragwyb-click-to-chat')}
							</button>
						</div>
					</div>
				) : (
					<div className="dctc-sc-filter-toolbar-main">
						{ /* Left Folders Toggle Button */}
						<button
							type="button"
							className={`dctc-sc-toggle-folders-btn ${isFoldersExpanded ? 'active' : ''}`}
							onClick={() => setIsFoldersExpanded((prev) => !prev)}
							title={isFoldersExpanded ? __('Collapse Folders Sidebar', 'dragwyb-click-to-chat') : __('Expand Folders Sidebar', 'dragwyb-click-to-chat')}
						>
							<span className="dashicons dashicons-category"></span>
							<span>{__('Folders', 'dragwyb-click-to-chat')}</span>
							<span className={`dashicons ${isFoldersExpanded ? 'dashicons-arrow-left-alt2' : 'dashicons-arrow-right-alt2'}`} style={{ fontSize: '11px', width: '11px', height: '11px' }}></span>
						</button>

						{ /* Search Bar */}
						<div className="dctc-sc-toolbar-search">
							<span className="dashicons dashicons-search"></span>
							<input
								type="text"
								placeholder={__('Search tickets by subject, customer, email, ID...', 'dragwyb-click-to-chat')}
								value={searchQuery}
								onChange={(e) => setSearchQuery(e.target.value)}
							/>
							{searchQuery && (
								<button
									type="button"
									className="dctc-sc-search-clear"
									onClick={() => setSearchQuery('')}
								>
									&times;
								</button>
							)}
						</div>

						{ /* Quick Status Pills */}
						<div className="dctc-sc-quick-status-pills">
							{[
								{ id: 'all', label: __('All', 'dragwyb-click-to-chat') },
								{ id: 'open', label: __('Open', 'dragwyb-click-to-chat') },
								{ id: 'pending', label: __('Pending', 'dragwyb-click-to-chat') },
								{ id: 'resolved', label: __('Resolved', 'dragwyb-click-to-chat') },
								{ id: 'closed', label: __('Closed', 'dragwyb-click-to-chat') },
							].map((tab) => (
								<button
									key={tab.id}
									type="button"
									className={`dctc-sc-status-pill-btn ${statusFilter === tab.id ? 'active' : ''}`}
									onClick={() => { setStatusFilter(tab.id); setCurrentPage(1); }}
								>
									{tab.label}
								</button>
							))}
						</div>

						{ /* Expandable Filter Toggle */}
						<button
							type="button"
							className={`dctc-sc-more-filters-btn ${showMoreFilters || activeSecondaryFilterCount > 0 ? 'active' : ''}`}
							onClick={() => setShowMoreFilters((prev) => !prev)}
							title={__('Toggle advanced filters', 'dragwyb-click-to-chat')}
						>
							<span className="dashicons dashicons-filter"></span>
							<span>{__('Filter Options', 'dragwyb-click-to-chat')}</span>
							{activeSecondaryFilterCount > 0 && (
								<span className="dctc-sc-filter-active-count">{activeSecondaryFilterCount}</span>
							)}
							<span className={`dashicons ${showMoreFilters ? 'dashicons-arrow-up-alt2' : 'dashicons-arrow-down-alt2'}`} style={{ fontSize: '12px', width: '12px', height: '12px' }}></span>
						</button>

						{ /* New Ticket Action */}
						<div className="dctc-sc-toolbar-actions">
							<button
								type="button"
								className="dctc-sc-new-ticket-btn"
								onClick={() => setIsNewTicketModalOpen(true)}
							>
								<span className="dashicons dashicons-plus"></span>
								{__('New Ticket', 'dragwyb-click-to-chat')}
							</button>
						</div>
					</div>
				)}

				{ /* SECONDARY FILTER DRAWER (Collapsible on demand) */}
				{showMoreFilters && (
					<div className="dctc-sc-filter-drawer">
						<div className="dctc-sc-drawer-dropdowns">
							{ /* Priority */}
							<div className="dctc-sc-drawer-field">
								<label>{__('Priority', 'dragwyb-click-to-chat')}</label>
								<select
									value={priorityFilter}
									onChange={(e) => { setPriorityFilter(e.target.value); setCurrentPage(1); }}
								>
									<option value="all">{__('All Priorities', 'dragwyb-click-to-chat')}</option>
									<option value="urgent">{__('Urgent', 'dragwyb-click-to-chat')}</option>
									<option value="high">{__('High', 'dragwyb-click-to-chat')}</option>
									<option value="normal">{__('Normal', 'dragwyb-click-to-chat')}</option>
									<option value="low">{__('Low', 'dragwyb-click-to-chat')}</option>
								</select>
							</div>

							{ /* Category */}
							<div className="dctc-sc-drawer-field">
								<label>{__('Category', 'dragwyb-click-to-chat')}</label>
								<select
									value={categoryFilter}
									onChange={(e) => { setCategoryFilter(e.target.value); setCurrentPage(1); }}
								>
									<option value="all">{__('All Categories', 'dragwyb-click-to-chat')}</option>
									{categories.map((cat) => (
										<option key={cat.id} value={cat.id}>{cat.name}</option>
									))}
								</select>
							</div>

							{ /* Assigned Agent */}
							<div className="dctc-sc-drawer-field">
								<label>{__('Assigned Agent', 'dragwyb-click-to-chat')}</label>
								<select
									value={assignedToFilter}
									onChange={(e) => setAssignedToFilter(e.target.value)}
								>
									<option value="all">{__('All Agents', 'dragwyb-click-to-chat')}</option>
									<option value="0">{__('Unassigned', 'dragwyb-click-to-chat')}</option>
									{agents.map((ag) => (
										<option key={ag.id} value={ag.id}>{ag.display_name}</option>
									))}
								</select>
							</div>

							{ /* Product */}
							<div className="dctc-sc-drawer-field">
								<label>{__('Product', 'dragwyb-click-to-chat')}</label>
								<select
									value={productFilter}
									onChange={(e) => setProductFilter(e.target.value)}
								>
									<option value="all">{__('All Products', 'dragwyb-click-to-chat')}</option>
									<option value="chatbot">{__('Chatbot Widget', 'dragwyb-click-to-chat')}</option>
									<option value="portal">{__('Support Portal', 'dragwyb-click-to-chat')}</option>
								</select>
							</div>

							{ /* Tags */}
							<div className="dctc-sc-drawer-field">
								<label>{__('Tag', 'dragwyb-click-to-chat')}</label>
								<select
									value={tagFilter}
									onChange={(e) => setTagFilter(e.target.value)}
								>
									<option value="all">{__('All Tags', 'dragwyb-click-to-chat')}</option>
									{tags.map((tg) => (
										<option key={tg.id} value={tg.name}>{tg.name}</option>
									))}
								</select>
							</div>

							{ /* Date Range */}
							<div className="dctc-sc-drawer-field">
								<label>{__('Date Range', 'dragwyb-click-to-chat')}</label>
								<select
									value={dateRangeFilter}
									onChange={(e) => setDateRangeFilter(e.target.value)}
								>
									<option value="all">{__('All Time', 'dragwyb-click-to-chat')}</option>
									<option value="today">{__('Today', 'dragwyb-click-to-chat')}</option>
									<option value="7days">{__('Last 7 Days', 'dragwyb-click-to-chat')}</option>
									<option value="30days">{__('Last 30 Days', 'dragwyb-click-to-chat')}</option>
								</select>
							</div>

							{ /* Customer Type */}
							<div className="dctc-sc-drawer-field">
								<label>{__('Customer Type', 'dragwyb-click-to-chat')}</label>
								<select
									value={customerTypeFilter}
									onChange={(e) => setCustomerTypeFilter(e.target.value)}
								>
									<option value="all">{__('All Types', 'dragwyb-click-to-chat')}</option>
									<option value="registered">{__('Registered User', 'dragwyb-click-to-chat')}</option>
									<option value="guest">{__('Guest Visitor', 'dragwyb-click-to-chat')}</option>
								</select>
							</div>
						</div>

						<div className="dctc-sc-drawer-footer">
							<button
								type="button"
								className="dctc-sc-reset-filters-btn"
								onClick={handleResetFilters}
							>
								<span className="dashicons dashicons-image-rotate"></span>
								{__('Reset All Filters', 'dragwyb-click-to-chat')}
							</button>
						</div>
					</div>
				)}
			</div>

			{ /* WORKSPACE CONTAINER */}
			<div className={`dctc-sc-workspace-layout ${isFoldersExpanded ? 'folders-open' : 'folders-closed'} ${selectedTicketId ? 'ticket-active' : 'no-ticket'}`}>
				{ /* COLUMN 1: FOLDERS & VIEWS SIDEBAR (Optional on expand) */}
				{isFoldersExpanded && (
					<aside className="dctc-sc-col-folders">
						<div className="dctc-sc-folder-group">
							{[
								{ key: 'all', label: __('All Tickets', 'dragwyb-click-to-chat'), icon: 'dashicons-laptop', count: folderCounts.all },
								{ key: 'open', label: __('Open', 'dragwyb-click-to-chat'), icon: 'dashicons-inbox', count: folderCounts.open },
								{ key: 'pending', label: __('Pending', 'dragwyb-click-to-chat'), icon: 'dashicons-clock', count: folderCounts.pending },
								{ key: 'my', label: __('My Tickets', 'dragwyb-click-to-chat'), icon: 'dashicons-admin-users', count: folderCounts.my },
								{ key: 'unassigned', label: __('Unassigned', 'dragwyb-click-to-chat'), icon: 'dashicons-groups', count: folderCounts.unassigned },
								{ key: 'resolved', label: __('Resolved', 'dragwyb-click-to-chat'), icon: 'dashicons-yes-alt', count: folderCounts.resolved },
								{ key: 'closed', label: __('Closed', 'dragwyb-click-to-chat'), icon: 'dashicons-dismiss', count: folderCounts.closed },
								{ key: 'spam', label: __('Spam', 'dragwyb-click-to-chat'), icon: 'dashicons-warning', count: folderCounts.spam },
								{ key: 'trash', label: __('Trash', 'dragwyb-click-to-chat'), icon: 'dashicons-trash', count: folderCounts.trash },
							].map((item) => (
								<button
									key={item.key}
									type="button"
									className={`dctc-sc-folder-item ${activeFolder === item.key ? 'active' : ''}`}
									onClick={() => handleSelectFolder(item.key)}
								>
									<span className={`dashicons ${item.icon}`}></span>
									<span className="dctc-sc-folder-name">{item.label}</span>
									<span className="dctc-sc-folder-count">{item.count}</span>
								</button>
							))}
						</div>

						<div className="dctc-sc-views-section">
							<div className="dctc-sc-views-header">
								<span>{__('VIEWS', 'dragwyb-click-to-chat')}</span>
								<span className="dashicons dashicons-admin-generic"></span>
							</div>
							<div className="dctc-sc-views-group">
								{[
									{ key: 'high_priority', label: __('High Priority', 'dragwyb-click-to-chat'), icon: 'dashicons-flag', iconColor: '#ef4444', count: folderCounts.high_priority },
									{ key: 'waiting_reply', label: __('Waiting for Reply', 'dragwyb-click-to-chat'), icon: 'dashicons-clock', iconColor: '#f59e0b', count: folderCounts.waiting_reply },
									{ key: 'today', label: __('Today', 'dragwyb-click-to-chat'), icon: 'dashicons-calendar-alt', iconColor: '#6366f1', count: folderCounts.today },
									{ key: 'this_week', label: __('This Week', 'dragwyb-click-to-chat'), icon: 'dashicons-calendar', iconColor: '#6366f1', count: folderCounts.this_week },
									{ key: 'ai_suggested', label: __('AI Suggested', 'dragwyb-click-to-chat'), icon: 'dashicons-superhero', iconColor: '#8b5cf6', count: folderCounts.ai_suggested },
									{ key: 'overdue', label: __('Overdue', 'dragwyb-click-to-chat'), icon: 'dashicons-backup', iconColor: '#ef4444', count: folderCounts.overdue },
								].map((v) => (
									<button
										key={v.key}
										type="button"
										className={`dctc-sc-folder-item ${activeView === v.key ? 'active' : ''}`}
										onClick={() => handleSelectView(v.key)}
									>
										<span className={`dashicons ${v.icon}`} style={{ color: v.iconColor }}></span>
										<span className="dctc-sc-folder-name">{v.label}</span>
										<span className="dctc-sc-folder-count">{v.count}</span>
									</button>
								))}

								<button
									type="button"
									className="dctc-sc-create-view-btn"
									onClick={() => onShowNotice(__('Custom view creator coming soon.', 'dragwyb-click-to-chat'), 'info')}
								>
									<span className="dashicons dashicons-plus"></span>
									{__('Create View', 'dragwyb-click-to-chat')}
								</button>
							</div>
						</div>
					</aside>
				)}

				{ /* IF NO TICKET IS SELECTED: FULL WIDTH ALL TICKETS LIST */}
				{!selectedTicketId && (
					<section className="dctc-sc-col-list fullwidth">
						{isDatabaseEmpty ? (
							/* BRAND NEW WORKSPACE EMPTY STATE (0 tickets created yet) */
							<div className="dctc-sc-empty-inbox-container">
								<div className="dctc-sc-empty-inbox-icon-wrap">
									<svg viewBox="0 0 64 64" width="64" height="64" fill="none" style={{ width: '64px', height: '64px', display: 'block' }}>
										<rect width="64" height="64" rx="20" fill="url(#empty-inbox-grad)" />
										<path d="M20 24C20 21.79 21.79 20 24 20H40C42.21 20 44 21.79 44 24V40C44 42.21 42.21 44 40 44H24C21.79 44 20 42.21 20 40V24Z" stroke="#FFFFFF" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" />
										<path d="M20 26L32 34L44 26" stroke="#FFFFFF" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" />
										<circle cx="45" cy="19" r="4" fill="#10B981" stroke="#FFFFFF" strokeWidth="2" />
										<defs>
											<linearGradient id="empty-inbox-grad" x1="0" y1="0" x2="64" y2="64" gradientUnits="userSpaceOnUse">
												<stop stopColor="#6366F1" />
												<stop offset="1" stopColor="#4338CA" />
											</linearGradient>
										</defs>
									</svg>
								</div>

								<h2 className="dctc-sc-empty-inbox-title">
									{__('No Support Tickets Created Yet', 'dragwyb-click-to-chat')}
								</h2>
								<p className="dctc-sc-empty-inbox-desc">
									{__('Your support ticketing workspace is active and ready. Tickets will appear here automatically when visitors request agent support from your chat widget, submit inquiries via your frontend portal, or when staff logs a ticket.', 'dragwyb-click-to-chat')}
								</p>

								<div className="dctc-sc-empty-inbox-features">
									<div className="dctc-sc-empty-feature-card">
										<div className="dctc-sc-empty-feature-icon" style={{ background: '#ec4899' }}>💬</div>
										<h4>{__('Automated Ticket Generation', 'dragwyb-click-to-chat')}</h4>
										<p>{__('Tickets are generated automatically from your AI Assistant and chat channels whenever visitors report issues, submit product or lead inquiries, or request human support.', 'dragwyb-click-to-chat')}</p>
									</div>
									<div className="dctc-sc-empty-feature-card">
										<div className="dctc-sc-empty-feature-icon" style={{ background: '#6366f1' }}>🌐</div>
										<h4>{__('Customer Helpdesk Portal', 'dragwyb-click-to-chat')}</h4>
										<p>{__('Customers can submit tickets and view replies directly through your website frontend portal.', 'dragwyb-click-to-chat')}</p>
									</div>
									<div className="dctc-sc-empty-feature-card">
										<div className="dctc-sc-empty-feature-icon" style={{ background: '#10b981' }}>⚡</div>
										<h4>{__('Manual Ticket Creation', 'dragwyb-click-to-chat')}</h4>
										<p>{__('Agents can create tickets instantly for phone inquiries, emails, or offline requests.', 'dragwyb-click-to-chat')}</p>
									</div>
								</div>

								<div className="dctc-sc-empty-inbox-actions">
									<button
										type="button"
										className="dctc-sc-new-ticket-btn"
										onClick={() => setIsNewTicketModalOpen(true)}
										style={{ padding: '12px 28px', fontSize: '15px' }}
									>
										<span className="dashicons dashicons-plus"></span>
										{__('Create Your First Ticket', 'dragwyb-click-to-chat')}
									</button>
									<a
										href="admin.php?page=dragwyb-click-to-chat-guide&tab=support"
										className="dctc-sc-reset-filters-btn"
										style={{ padding: '12px 24px', fontSize: '14px', textDecoration: 'none', background: '#f8fafc', display: 'inline-flex', alignItems: 'center', gap: '6px' }}
									>
										<span>📖</span>
										<span>{__('Read Support Center Guide', 'dragwyb-click-to-chat')}</span>
									</a>
								</div>
							</div>
						) : (
							<>
								<div className="dctc-sc-list-header">
									<div className="dctc-sc-list-header-left">
										<h3 className="dctc-sc-list-title">
											{activeFolder ? activeFolder.charAt(0).toUpperCase() + activeFolder.slice(1) + ' Tickets' : 'Filtered Views'}
										</h3>
										<span className="dctc-sc-list-subtitle">
											{totalTickets || filteredTickets.length} {__('tickets found', 'dragwyb-click-to-chat')}
										</span>
									</div>
									<div className="dctc-sc-list-sort">
										<select
											value={sortBy}
											onChange={(e) => setSortBy(e.target.value)}
										>
											<option value="newest">{__('Newest First', 'dragwyb-click-to-chat')}</option>
											<option value="oldest">{__('Oldest First', 'dragwyb-click-to-chat')}</option>
											<option value="priority">{__('Priority High-Low', 'dragwyb-click-to-chat')}</option>
										</select>
									</div>
								</div>

								<div className="dctc-sc-ticket-grid-fullwidth">
									{loading ? (
										<div className="dctc-sc-loading-state">
											<span className="spinner is-active"></span>
											{__('Loading tickets...', 'dragwyb-click-to-chat')}
										</div>
									) : filteredTickets.length === 0 ? (
										<div className="dctc-sc-filter-empty-state">
											<div className="dctc-sc-filter-empty-icon">
												<span className="dashicons dashicons-filter"></span>
											</div>
											<h3>{__('No Support Tickets Match Your Filters', 'dragwyb-click-to-chat')}</h3>
											<p>{__('Try adjusting your search keywords, clearing status filters, or resetting advanced options.', 'dragwyb-click-to-chat')}</p>
											<button
												type="button"
												className="dctc-sc-reset-filters-btn"
												onClick={handleResetFilters}
											>
												<span className="dashicons dashicons-image-rotate"></span>
												{__('Reset All Filters', 'dragwyb-click-to-chat')}
											</button>
										</div>
									) : (
										filteredTickets.map((item) => {
											const initials = getInitials(item.customer_name, item.customer_email || item.session_id);
											const isStarred = starredTickets[item.id];
											const isFlagged = flaggedTickets[item.id];
											const isChecked = selectedTicketIds.includes(item.id);

											return (
												<div
													key={item.id}
													className="dctc-sc-card-fullwidth"
													onClick={() => handleOpenTicket(item.id)}
												>
													<div className="dctc-sc-card-main-info">
														<div className="dctc-sc-card-top">
															<div className="dctc-sc-card-top-left">
																<input
																	type="checkbox"
																	checked={isChecked}
																	onChange={(e) => toggleTicketSelect(item.id, e)}
																	className="dctc-sc-card-checkbox"
																/>
																<span className="dctc-sc-card-id">#{item.ticket_number || item.id}</span>
																<span className={`dctc-sc-badge ${getStatusBadgeClass(item.status)}`}>
																	{(item.status || 'open').toUpperCase()}
																</span>
																<span className={`dctc-sc-badge ${getPriorityBadgeClass(item.priority)}`}>
																	<span className="dashicons dashicons-flag" style={{ fontSize: '10px', width: '10px', height: '10px', marginRight: '2px' }}></span>
																	{item.priority ? item.priority.charAt(0).toUpperCase() + item.priority.slice(1) : 'Normal'}
																</span>
															</div>
															<div className="dctc-sc-card-top-right">
																{isStarred && <span className="dashicons dashicons-star-filled" style={{ color: '#f59e0b', fontSize: '14px' }}></span>}
																{isFlagged && <span className="dashicons dashicons-flag" style={{ color: '#ef4444', fontSize: '14px' }}></span>}
															</div>
														</div>

														<div className="dctc-sc-card-subject">
															{item.subject}
														</div>

														<div className="dctc-sc-card-excerpt">
															{(item.excerpt || item.last_message || item.subject || '').replace(/<[^>]*>?/gm, '')}
														</div>
													</div>

													<div className="dctc-sc-card-meta-side">
														<div className="dctc-sc-card-customer-row">
															<div className="dctc-sc-card-customer-info">
																<div className="dctc-sc-card-avatar">
																	{initials}
																</div>
																<span className="dctc-sc-card-customer-name">
																	{item.customer_name || (item.session_id ? `Guest (${item.session_id.substring(0, 8)})` : 'Guest Visitor')}
																</span>
															</div>
															<span className="dctc-sc-card-time">
																{formatRelativeTime(item.created_at)}
															</span>
														</div>

														<div className="dctc-sc-card-badges-row">
															<span className="dctc-sc-pill-badge category">
																<span className="dashicons dashicons-category"></span>
																{item.category_name || __('Technical & Bugs', 'dragwyb-click-to-chat')}
															</span>
															<span className="dctc-sc-pill-badge agent">
																<span className="dashicons dashicons-admin-users"></span>
																{item.agent_name || __('Aniket Dogra', 'dragwyb-click-to-chat')}
															</span>
															<span className="dctc-sc-pill-badge chats">
																<span className="dashicons dashicons-format-chat"></span>
																{item.chat_count !== undefined ? item.chat_count : (item.message_count || 2)} {__('chats', 'dragwyb-click-to-chat')}
															</span>
														</div>
													</div>
												</div>
											);
										})
									)}
								</div>

								{ /* Pagination */}
								{totalPages > 1 && (
									<div className="dctc-sc-pagination-bar">
										<button
											type="button"
											disabled={currentPage <= 1}
											onClick={() => setCurrentPage((p) => Math.max(1, p - 1))}
											className="dctc-sc-page-nav"
										>
											&laquo; {__('Prev', 'dragwyb-click-to-chat')}
										</button>
										<span>{currentPage} / {totalPages}</span>
										<button
											type="button"
											disabled={currentPage >= totalPages}
											onClick={() => setCurrentPage((p) => Math.min(totalPages, p + 1))}
											className="dctc-sc-page-nav"
										>
											{__('Next', 'dragwyb-click-to-chat')} &raquo;
										</button>
									</div>
								)}
							</>
						)}
					</section>
				)}

				{ /* IF A TICKET IS SELECTED: SHOW CENTER WORKSPACE & RIGHT DETAILS SIDEBAR */}
				{ /* IF A TICKET IS SELECTED: SHOW CENTER WORKSPACE & RIGHT DETAILS SIDEBAR */}
				{selectedTicketId && (
					<>
						{ /* COLUMN 2/3: TICKET WORKSPACE & CONVERSATION */}
						<main className="dctc-sc-col-main">
							{ticketLoading && !selectedTicket ? (
								<div className="dctc-sc-main-loading">
									<span className="spinner is-active"></span>
									<p>{__('Loading conversation stream...', 'dragwyb-click-to-chat')}</p>
								</div>
							) : !selectedTicket ? (
								<div className="dctc-sc-no-selection">
									<span className="dashicons dashicons-format-chat"></span>
									<h3>{__('Select a ticket to begin support', 'dragwyb-click-to-chat')}</h3>
								</div>
							) : (
								<div className="dctc-sc-workspace-inner">
									{ /* TOP HEADER BAR OF WORKSPACE */}
									<div className="dctc-sc-ws-header">
										<div className="dctc-sc-ws-header-left">
											{ /* Go Back to All Tickets Button */}
											<button
												type="button"
												className="dctc-sc-back-to-list-btn"
												onClick={handleCloseTicket}
												title={__('Close ticket and return to full list', 'dragwyb-click-to-chat')}
											>
												<span className="dashicons dashicons-arrow-left-alt"></span>
												<span>{__('All Tickets', 'dragwyb-click-to-chat')}</span>
											</button>

											<span className="dctc-sc-ws-ticket-id">#{selectedTicket?.ticket_number || selectedTicket?.id}</span>

											{ /* Status dropdown badge */}
											<div className="dctc-sc-ws-badge-dropdown">
												<select
													value={selectedTicket?.status || 'open'}
													onChange={(e) => handleStatusChange(e.target.value)}
													className={`dctc-sc-ws-select-badge ${getStatusBadgeClass(selectedTicket?.status)}`}
												>
													<option value="open">OPEN</option>
													<option value="pending">PENDING</option>
													<option value="waiting_customer">WAITING</option>
													<option value="resolved">RESOLVED</option>
													<option value="closed">CLOSED</option>
												</select>
											</div>

											{ /* Priority dropdown badge */}
											<div className="dctc-sc-ws-badge-dropdown">
												<select
													value={selectedTicket?.priority || 'normal'}
													onChange={(e) => handlePriorityChange(e.target.value)}
													className={`dctc-sc-ws-select-badge ${getPriorityBadgeClass(selectedTicket?.priority)}`}
												>
													<option value="low">Low</option>
													<option value="normal">Normal</option>
													<option value="high">High</option>
													<option value="urgent">Urgent</option>
												</select>
											</div>

											{ /* Action icon buttons */}
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
													onClick={() => handleNavigateTicket('prev')}
													title={__('Previous Ticket', 'dragwyb-click-to-chat')}
												>
													&lt;
												</button>
												<button
													type="button"
													className="dctc-sc-arrow-btn"
													onClick={() => handleNavigateTicket('next')}
													title={__('Next Ticket', 'dragwyb-click-to-chat')}
												>
													&gt;
												</button>
											</div>

											{ /* Give / Take Control Button (Only show if ticket is from AI Assistant Chatbot AND session is active) */}
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

											{ /* Close button */}
											<button
												type="button"
												className="dctc-sc-close-view-btn"
												onClick={handleCloseTicket}
												title={__('Close View', 'dragwyb-click-to-chat')}
											>
												&times;
											</button>
										</div>
									</div>

									{ /* TICKET TITLE & META BAR */}
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

									{ /* CLEAN WORKSPACE SUBTABS */}
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

									{ /* CONVERSATION TAB STREAM */}
									{workspaceTab === 'conversation' && (
										<div className="dctc-sc-conversation-scroll">
											{ /* Pinned Notes */}
											{(selectedTicket?.notes || []).filter((n) => n.is_pinned).map((pin) => (
												<div key={`pin-${pin.id}`} className="dctc-sc-pinned-note-banner">
													<span className="dashicons dashicons-admin-post"></span>
													<div>
														<strong>{__('Pinned Staff Note', 'dragwyb-click-to-chat')} ({pin.author_name}):</strong> {pin.note}
													</div>
												</div>
											))}

											{ /* Conversation Messages */}
											{(selectedTicket?.messages || []).length === 0 ? (
												<div className="dctc-sc-empty-stream">
													<p>{__('No messages in this ticket yet.', 'dragwyb-click-to-chat')}</p>
												</div>
											) : (
												selectedTicket.messages.map((msg, idx) => {
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

											{ /* Audit Trail Events */}
											<div className="dctc-sc-audit-timeline">
												{(selectedTicket?.events && selectedTicket.events.length > 0) ? (
													selectedTicket.events.map((evt, idx) => (
														<div key={`evt-${idx}`} className="dctc-sc-audit-node">
															<div className="dctc-sc-audit-dot"></div>
															<div className="dctc-sc-audit-text">
																<span><strong>{evt.actor_name || 'System'}</strong>: {(evt.event_type || '').replace('_', ' ')} {evt.new_value ? `→ ${evt.new_value}` : ''}</span>
																<span className="dctc-sc-audit-time">{evt.created_at}</span>
															</div>
														</div>
													))
												) : null}
											</div>
											<div ref={timelineEndRef} />
										</div>
									)}

									{ /* INTERNAL NOTES TAB */}
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

									{ /* ACTIVITY TAB */}
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

									{ /* COMPOSER AREA */}
									<div className="dctc-sc-rich-composer">
										<div className="dctc-sc-composer-top-bar">
											<div className="dctc-sc-composer-mode-tabs">
												<button
													type="button"
													className={`dctc-sc-composer-tab ${composerMode === 'reply' ? 'active' : ''}`}
													onClick={() => setComposerMode('reply')}
												>
													<span className="dashicons dashicons-admin-comments"></span>
													{__('Reply to Customer', 'dragwyb-click-to-chat')}
												</button>
												<button
													type="button"
													className={`dctc-sc-composer-tab ${composerMode === 'note' ? 'active' : ''}`}
													onClick={() => setComposerMode('note')}
												>
													<span className="dashicons dashicons-lock"></span>
													{__('Internal Note', 'dragwyb-click-to-chat')}
												</button>
											</div>

											<button
												type="button"
												className="dctc-sc-suggest-ai-btn"
												onClick={handleSuggestAiReply}
												disabled={aiSuggestLoading}
											>
												<span className="dashicons dashicons-superhero"></span>
												{aiSuggestLoading ? __('Thinking...', 'dragwyb-click-to-chat') : __('Suggest AI Reply', 'dragwyb-click-to-chat')}
											</button>
										</div>

										{composerMode === 'reply' ? (
											<form onSubmit={handleSendReply} className="dctc-sc-composer-main-form">
												<textarea
													rows="3"
													placeholder={__('Write a response to the customer...', 'dragwyb-click-to-chat')}
													value={replyText}
													onChange={(e) => setReplyText(e.target.value)}
													className="dctc-sc-composer-input"
												/>

												{ /* Rich formatting toolbar */}
												<div className="dctc-sc-format-toolbar">
													<div className="dctc-sc-format-buttons">
														<button type="button" title="Bold"><strong>B</strong></button>
														<button type="button" title="Italic"><em>I</em></button>
														<button type="button" title="Underline"><u>U</u></button>
														<button type="button" title="Link"><span className="dashicons dashicons-admin-links"></span></button>
														<button type="button" title="Bullet List"><span className="dashicons dashicons-editor-ul"></span></button>
														<button type="button" title="Numbered List"><span className="dashicons dashicons-editor-ol"></span></button>
														<button type="button" title="Code">&lt;/&gt;</button>
														<button type="button" title="Image"><span className="dashicons dashicons-format-image"></span></button>
														<button type="button" title="Emoji"><span className="dashicons dashicons-smiley"></span></button>
														<button type="button" title="Attachment"><span className="dashicons dashicons-paperclip"></span></button>
													</div>
													<button type="button" className="dctc-sc-fullscreen-icon" title="Expand">
														<span className="dashicons dashicons-editor-expand"></span>
													</button>
												</div>

												{ /* Bottom submission bar */}
												<div className="dctc-sc-composer-bottom-bar">
													<div className="dctc-sc-bottom-left">
														<button type="button" className="dctc-sc-composer-pill-btn">
															<span className="dashicons dashicons-layout"></span>
															{__('Templates', 'dragwyb-click-to-chat')}
														</button>
														<button type="button" className="dctc-sc-composer-pill-btn">
															<span className="dashicons dashicons-paperclip"></span>
															{__('Attach Files', 'dragwyb-click-to-chat')}
														</button>
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
											<form onSubmit={handleAddNote} className="dctc-sc-composer-main-form note-mode">
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
								</div>
							)}
						</main>

						{ /* COLUMN 4: RIGHT SIDEBAR - CUSTOMER & TICKET DETAILS (COMPACT & EXPANDABLE) */}
						{selectedTicket && (
							<aside className="dctc-sc-col-details">
								{ /* CUSTOMER CARD */}
								<div className="dctc-sc-details-card">
									<div className="dctc-sc-card-head">
										<div className="dctc-sc-card-head-title">
											<span className="dashicons dashicons-admin-users"></span>
											<h4>{__('Customer Details', 'dragwyb-click-to-chat')}</h4>
										</div>
									</div>

									<div className="dctc-sc-customer-summary">
										<div className="dctc-sc-cust-avatar-large">
											{getInitials(selectedTicket?.customer_name, selectedTicket?.customer_email || selectedTicket?.session_id)}
										</div>
										<div className="dctc-sc-cust-identity">
											<span className="dctc-sc-cust-fullname">
												{selectedTicket?.customer_name || (selectedTicket?.session_id ? `Guest (${selectedTicket.session_id.substring(0, 8)})` : 'Guest Visitor')}
											</span>
											<span className="dctc-sc-guest-tag">{__('Guest', 'dragwyb-click-to-chat')}</span>
										</div>
									</div>

									<div className="dctc-sc-customer-info-rows">
										<div className="dctc-sc-info-row">
											<span className="dashicons dashicons-email"></span>
											<span className="dctc-sc-info-text">
												{selectedTicket?.customer_email || __('Not provided (Live Chat)', 'dragwyb-click-to-chat')}
											</span>
										</div>
										{selectedTicket?.customer_phone && (
											<div className="dctc-sc-info-row">
												<span className="dashicons dashicons-phone"></span>
												<span className="dctc-sc-info-text">{selectedTicket.customer_phone}</span>
											</div>
										)}
									</div>

									{ /* Expand Session & Technical Details Toggle */}
									<button
										type="button"
										className="dctc-sc-expand-toggle-btn"
										onClick={() => setShowCustomerSession((prev) => !prev)}
									>
										<span className="dashicons dashicons-admin-generic"></span>
										<span>{__('Session & Technical Info', 'dragwyb-click-to-chat')}</span>
										<span className={`dashicons ${showCustomerSession ? 'dashicons-arrow-up-alt2' : 'dashicons-arrow-down-alt2'}`} style={{ fontSize: '11px', width: '11px', height: '11px' }}></span>
									</button>

									{showCustomerSession && (
										<div className="dctc-sc-expanded-section">
											{selectedTicket?.session_id && (
												<div className="dctc-sc-info-row">
													<span className="dashicons dashicons-key"></span>
													<span className="dctc-sc-info-text monospace" title={selectedTicket.session_id}>
														{selectedTicket.session_id}
													</span>
													<button
														type="button"
														className="dctc-sc-copy-btn"
														onClick={() => handleCopySession(selectedTicket.session_id)}
														title={__('Copy Session ID', 'dragwyb-click-to-chat')}
													>
														<span className="dashicons dashicons-admin-page"></span>
													</button>
												</div>
											)}
											<div className="dctc-sc-stat-item">
												<span className="stat-name">{__('First Seen', 'dragwyb-click-to-chat')}</span>
												<span className="stat-data">{selectedTicket?.created_at || 'Oct 04, 2026'}</span>
											</div>
											<div className="dctc-sc-stat-item">
												<span className="stat-name">{__('Total Chats', 'dragwyb-click-to-chat')}</span>
												<span className="stat-data">{selectedTicket?.chat_count !== undefined ? selectedTicket.chat_count : (selectedTicket?.messages?.length || 0)}</span>
											</div>
											<div className="dctc-sc-stat-item">
												<span className="stat-name">{__('Total Tickets', 'dragwyb-click-to-chat')}</span>
												<span className="stat-data">1</span>
											</div>
										</div>
									)}
								</div>

								{ /* TICKET PROPERTIES CARD */}
								<div className="dctc-sc-details-card">
									<div className="dctc-sc-card-head">
										<div className="dctc-sc-card-head-title">
											<span className="dashicons dashicons-clipboard"></span>
											<h4>{__('Ticket Properties', 'dragwyb-click-to-chat')}</h4>
										</div>
									</div>

									<div className="dctc-sc-ticket-fields">
										{ /* Daily Vital 1: Status */}
										<div className="dctc-sc-field-row">
											<label>{__('Status', 'dragwyb-click-to-chat')}</label>
											<select
												value={selectedTicket?.status || 'open'}
												onChange={(e) => handleStatusChange(e.target.value)}
												className="dctc-sc-detail-select"
											>
												<option value="open">Open</option>
												<option value="pending">Pending</option>
												<option value="waiting_customer">Waiting Customer</option>
												<option value="resolved">Resolved</option>
												<option value="closed">Closed</option>
											</select>
										</div>

										{ /* Daily Vital 2: Priority */}
										<div className="dctc-sc-field-row">
											<label>{__('Priority', 'dragwyb-click-to-chat')}</label>
											<select
												value={selectedTicket?.priority || 'normal'}
												onChange={(e) => handlePriorityChange(e.target.value)}
												className="dctc-sc-detail-select"
											>
												<option value="low">Low</option>
												<option value="normal">Normal</option>
												<option value="high">High</option>
												<option value="urgent">Urgent</option>
											</select>
										</div>

										{ /* Daily Vital 3: Assigned To */}
										<div className="dctc-sc-field-row">
											<label>{__('Assigned To', 'dragwyb-click-to-chat')}</label>
											<select
												value={selectedTicket?.assigned_agent_id || 0}
												onChange={(e) => handleAssignAgent(Number(e.target.value))}
												className="dctc-sc-detail-select"
											>
												<option value="0">{__('Unassigned', 'dragwyb-click-to-chat')}</option>
												{agents.map((ag) => (
													<option key={ag.id} value={ag.id}>
														{ag.display_name}
													</option>
												))}
											</select>
										</div>

										{ /* Expand Secondary Properties Toggle */}
										<button
											type="button"
											className="dctc-sc-expand-toggle-btn"
											onClick={() => setShowAdvancedProps((prev) => !prev)}
										>
											<span className="dashicons dashicons-category"></span>
											<span>{__('More Properties', 'dragwyb-click-to-chat')}</span>
											<span className={`dashicons ${showAdvancedProps ? 'dashicons-arrow-up-alt2' : 'dashicons-arrow-down-alt2'}`} style={{ fontSize: '11px', width: '11px', height: '11px' }}></span>
										</button>

										{showAdvancedProps && (
											<div className="dctc-sc-expanded-section">
												{ /* Category */}
												<div className="dctc-sc-field-row">
													<label>{__('Category', 'dragwyb-click-to-chat')}</label>
													<select
														value={selectedTicket?.category_id || ''}
														onChange={async (e) => {
															const val = e.target.value;
															if (!selectedTicket?.id) return;
															try {
																await apiFetch({
																	path: `/dctc-ai/v1/support/tickets/${selectedTicket.id}`,
																	method: 'PUT',
																	data: { category_id: val ? Number(val) : 0 },
																});
																onRefreshTicketDetails(selectedTicket.id);
																onRefreshTickets();
																onShowNotice(__('Category updated.', 'dragwyb-click-to-chat'), 'success');
															} catch (err) {
																console.error(err);
															}
														}}
														className="dctc-sc-detail-select"
													>
														<option value="">{__('Technical & Bugs', 'dragwyb-click-to-chat')}</option>
														{categories.map((cat) => (
															<option key={cat.id} value={cat.id}>{cat.name}</option>
														))}
													</select>
												</div>

												{ /* Product */}
												<div className="dctc-sc-field-row">
													<label>{__('Product', 'dragwyb-click-to-chat')}</label>
													<select className="dctc-sc-detail-select">
														<option>{__('Chatbot Widget', 'dragwyb-click-to-chat')}</option>
														<option>{__('Support Portal', 'dragwyb-click-to-chat')}</option>
													</select>
												</div>

												{ /* Tags */}
												<div className="dctc-sc-field-row">
													<label>{__('Tags', 'dragwyb-click-to-chat')}</label>
													<div className="dctc-sc-tags-inline">
														<span className="dctc-sc-tag-empty">{__('No tags', 'dragwyb-click-to-chat')}</span>
														<button type="button" className="dctc-sc-tag-add-btn">+</button>
													</div>
												</div>

												{ /* Source */}
												<div className="dctc-sc-field-row">
													<label>{__('Source', 'dragwyb-click-to-chat')}</label>
													<span className="dctc-sc-static-val">
														{selectedTicket?.reply_surface === 'chatbot_widget' ? 'Chatbot Widget' : 'Support Portal'}
													</span>
												</div>
											</div>
										)}
									</div>
								</div>

								{ /* WOOCOMMERCE ASSOCIATED ORDERS & PRODUCTS CARD (Shown only if customer has WC orders/products, otherwise skipped) */}
								{wcData && wcData.is_active && Array.isArray(wcData.recent_orders) && wcData.recent_orders.length > 0 && (
									<div className="dctc-sc-details-card">
										<div className="dctc-sc-card-head">
											<div className="dctc-sc-card-head-title">
												<span className="dashicons dashicons-cart" style={{ color: '#7c3aed' }}></span>
												<h4>{__('WooCommerce Orders', 'dragwyb-click-to-chat')}</h4>
											</div>
											{wcData.total_spent && (
												<span className="dctc-sc-guest-tag" style={{ background: '#f5f3ff', color: '#6d28d9', borderColor: '#ddd6fe' }}>
													{wcData.total_spent}
												</span>
											)}
										</div>

										<div className="dctc-sc-wc-orders-list" style={{ display: 'flex', flexDirection: 'column', gap: '10px', marginTop: '10px' }}>
											{wcData.recent_orders.map((order) => (
												<div key={order.id} style={{ background: '#f8fafc', border: '1px solid #e2e8f0', borderRadius: '8px', padding: '10px 12px' }}>
													<div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '6px' }}>
														<a href={order.view_url} target="_blank" rel="noreferrer" style={{ fontWeight: 700, color: '#4f46e5', textDecoration: 'none', fontSize: '12.5px' }}>
															#{order.number}
														</a>
														<span className={`dctc-sc-badge ${order.status === 'completed' ? 'dctc-sc-badge-resolved' : 'dctc-sc-badge-pending'}`} style={{ fontSize: '10px', padding: '2px 6px' }}>
															{order.status_name || order.status}
														</span>
														<span style={{ fontSize: '12px', fontWeight: 600, color: '#0f172a' }}>{order.total}</span>
													</div>
													{order.items_summary && (
														<div style={{ fontSize: '12px', color: '#475569', lineHeight: '1.4', marginBottom: '4px' }}>
															<span className="dashicons dashicons-products" style={{ fontSize: '12px', width: '12px', height: '12px', verticalAlign: 'middle', marginRight: '3px', color: '#64748b' }}></span>
															{order.items_summary}
														</div>
													)}
													<div style={{ fontSize: '11px', color: '#94a3b8' }}>{order.date}</div>
												</div>
											))}
										</div>
									</div>
								)}

								{ /* QUICK ACTIONS CARD */}
								<div className="dctc-sc-details-card">
									<div className="dctc-sc-card-head">
										<div className="dctc-sc-card-head-title">
											<span className="dashicons dashicons-admin-generic"></span>
											<h4>{__('Quick Actions', 'dragwyb-click-to-chat')}</h4>
										</div>
									</div>

									<div className="dctc-sc-quick-actions-grid">
										<button
											type="button"
											className="dctc-sc-quick-action-btn"
											onClick={() => onShowNotice(__('Ticket merge dialog coming soon.', 'dragwyb-click-to-chat'), 'info')}
										>
											<span className="dashicons dashicons-randomize"></span>
											{__('Merge', 'dragwyb-click-to-chat')}
										</button>

										<button
											type="button"
											className="dctc-sc-quick-action-btn"
											onClick={() => onShowNotice(__('Converted to email thread.', 'dragwyb-click-to-chat'), 'info')}
										>
											<span className="dashicons dashicons-email-alt"></span>
											{__('Email', 'dragwyb-click-to-chat')}
										</button>

										<button
											type="button"
											className="dctc-sc-quick-action-btn"
											onClick={() => window.print()}
										>
											<span className="dashicons dashicons-printer"></span>
											{__('Print', 'dragwyb-click-to-chat')}
										</button>

										<button
											type="button"
											className="dctc-sc-quick-action-btn delete"
											onClick={handleDeleteTicket}
										>
											<span className="dashicons dashicons-trash"></span>
											{__('Delete', 'dragwyb-click-to-chat')}
										</button>
									</div>
								</div>
							</aside>
						)}
					</>
				)}
			</div>

			{ /* CREATE NEW TICKET MODAL */}
			{isNewTicketModalOpen && (
				<div className="dctc-sc-modal-backdrop" onClick={() => setIsNewTicketModalOpen(false)}>
					<div className="dctc-sc-modal-dialog" onClick={(e) => e.stopPropagation()}>
						<div className="dctc-sc-modal-header">
							<h3>
								<span className="dashicons dashicons-plus"></span>
								{__('Create New Support Ticket', 'dragwyb-click-to-chat')}
							</h3>
							<button
								type="button"
								className="dctc-sc-modal-close-btn"
								onClick={() => setIsNewTicketModalOpen(false)}
							>
								&times;
							</button>
						</div>

						<form onSubmit={handleCreateTicketSubmit} className="dctc-sc-modal-form">
							<div className="dctc-sc-modal-body">
								<div className="dctc-sc-modal-form-group">
									<label>{__('Subject *', 'dragwyb-click-to-chat')}</label>
									<input
										type="text"
										required
										placeholder={__('Enter a support issue title...', 'dragwyb-click-to-chat')}
										value={newTicketData.subject}
										onChange={(e) => setNewTicketData({ ...newTicketData, subject: e.target.value })}
									/>
								</div>

								<div className="dctc-sc-modal-form-row">
									<div className="dctc-sc-modal-form-group">
										<label>{__('Customer Name', 'dragwyb-click-to-chat')}</label>
										<input
											type="text"
											placeholder={__('e.g. John Doe', 'dragwyb-click-to-chat')}
											value={newTicketData.customer_name}
											onChange={(e) => setNewTicketData({ ...newTicketData, customer_name: e.target.value })}
										/>
									</div>
									<div className="dctc-sc-modal-form-group">
										<label>{__('Customer Email', 'dragwyb-click-to-chat')}</label>
										<input
											type="email"
											placeholder={__('customer@example.com', 'dragwyb-click-to-chat')}
											value={newTicketData.customer_email}
											onChange={(e) => setNewTicketData({ ...newTicketData, customer_email: e.target.value })}
										/>
									</div>
								</div>

								<div className="dctc-sc-modal-form-row">
									<div className="dctc-sc-modal-form-group">
										<label>{__('Category', 'dragwyb-click-to-chat')}</label>
										<select
											value={newTicketData.category_id}
											onChange={(e) => setNewTicketData({ ...newTicketData, category_id: e.target.value })}
										>
											<option value="">{__('General / Technical', 'dragwyb-click-to-chat')}</option>
											{categories.map((cat) => (
												<option key={cat.id} value={cat.id}>{cat.name}</option>
											))}
										</select>
									</div>
									<div className="dctc-sc-modal-form-group">
										<label>{__('Priority', 'dragwyb-click-to-chat')}</label>
										<select
											value={newTicketData.priority}
											onChange={(e) => setNewTicketData({ ...newTicketData, priority: e.target.value })}
										>
											<option value="low">{__('Low', 'dragwyb-click-to-chat')}</option>
											<option value="normal">{__('Normal', 'dragwyb-click-to-chat')}</option>
											<option value="high">{__('High', 'dragwyb-click-to-chat')}</option>
											<option value="urgent">{__('Urgent', 'dragwyb-click-to-chat')}</option>
										</select>
									</div>
								</div>

								<div className="dctc-sc-modal-form-group">
									<label>{__('Message *', 'dragwyb-click-to-chat')}</label>
									<div className="dctc-sc-wysiwyg-container">
										<div className="dctc-sc-wysiwyg-toolbar">
											<button type="button" className="dctc-sc-wysiwyg-btn" onClick={() => applyFormatting('bold')} title={__('Bold', 'dragwyb-click-to-chat')}>
												<strong>B</strong>
											</button>
											<button type="button" className="dctc-sc-wysiwyg-btn" onClick={() => applyFormatting('italic')} title={__('Italic', 'dragwyb-click-to-chat')}>
												<em>I</em>
											</button>
											<button type="button" className="dctc-sc-wysiwyg-btn" onClick={() => applyFormatting('underline')} title={__('Underline', 'dragwyb-click-to-chat')}>
												<u>U</u>
											</button>
											<span className="dctc-sc-wysiwyg-divider"></span>
											<button type="button" className="dctc-sc-wysiwyg-btn" onClick={() => applyFormatting('link')} title={__('Insert Link', 'dragwyb-click-to-chat')}>
												<span className="dashicons dashicons-admin-links"></span>
											</button>
											<button type="button" className="dctc-sc-wysiwyg-btn" onClick={() => applyFormatting('ul')} title={__('Bullet List', 'dragwyb-click-to-chat')}>
												<span className="dashicons dashicons-editor-ul"></span>
											</button>
											<button type="button" className="dctc-sc-wysiwyg-btn" onClick={() => applyFormatting('ol')} title={__('Numbered List', 'dragwyb-click-to-chat')}>
												<span className="dashicons dashicons-editor-ol"></span>
											</button>
											<button type="button" className="dctc-sc-wysiwyg-btn" onClick={() => applyFormatting('quote')} title={__('Blockquote', 'dragwyb-click-to-chat')}>
												<span className="dashicons dashicons-editor-quote"></span>
											</button>
											<button type="button" className="dctc-sc-wysiwyg-btn" onClick={() => applyFormatting('code')} title={__('Code Block', 'dragwyb-click-to-chat')}>
												<span className="dashicons dashicons-editor-code"></span>
											</button>
											<span className="dctc-sc-wysiwyg-divider"></span>
											<button type="button" className="dctc-sc-wysiwyg-btn attach-btn" onClick={handleAttachFiles} title={__('Attach Files / Media', 'dragwyb-click-to-chat')}>
												<span className="dashicons dashicons-paperclip"></span>
												<span>{__('Attach Files', 'dragwyb-click-to-chat')}</span>
											</button>
										</div>

										<textarea
											ref={modalMessageInputRef}
											rows="5"
											required
											placeholder={__('Briefly describe the problem details...', 'dragwyb-click-to-chat')}
											value={newTicketData.message}
											onChange={(e) => setNewTicketData({ ...newTicketData, message: e.target.value })}
											className="dctc-sc-wysiwyg-textarea"
										/>

										{newTicketData.attachments && newTicketData.attachments.length > 0 && (
											<div className="dctc-sc-attachment-chips-wrap">
												{newTicketData.attachments.map((file, fIdx) => (
													<div key={fIdx} className="dctc-sc-attachment-chip">
														<span className="dashicons dashicons-media-default"></span>
														<span className="chip-name" title={file.name}>{file.name}</span>
														<button
															type="button"
															className="chip-remove"
															onClick={() => handleRemoveAttachment(fIdx)}
															title={__('Remove file', 'dragwyb-click-to-chat')}
														>
															&times;
														</button>
													</div>
												))}
											</div>
										)}
									</div>
								</div>
							</div>

							<div className="dctc-sc-modal-footer-bar">
								<button
									type="button"
									className="dctc-sc-btn-cancel"
									onClick={() => setIsNewTicketModalOpen(false)}
								>
									{__('Cancel', 'dragwyb-click-to-chat')}
								</button>
								<button
									type="submit"
									disabled={creatingTicket}
									className="dctc-sc-btn-submit"
								>
									{creatingTicket ? __('Creating...', 'dragwyb-click-to-chat') : __('Create Ticket', 'dragwyb-click-to-chat')}
								</button>
							</div>
						</form>
					</div>
				</div>
			)}
		</div>
	);
}
