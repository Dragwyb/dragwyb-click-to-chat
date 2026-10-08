/**
 * Support Center - Modern, Compact & Clean Tickets Workspace View
 * Expandable/Collapsible Sidebar, Full-Width All-Tickets View, and Clean Ticket Detail Workspace
 */
import { useState, useRef, useEffect, useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

import { TicketWorkspaceSkeleton, TicketDetailsSidebarSkeleton } from '../components/SupportSkeletons';
import {
	NewTicketModal,
	TicketFilterBar,
	TicketFoldersSidebar,
	TicketListTable,
	TicketDetailWorkspace,
	TicketSidebar,
} from '../components/tickets';

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
	products = [],
	agents = [],
	tags = [],
	taxonomies = [],
	wcData = null,
	wcLoading = false,
	ticketTypeFilter = 'all',
	setTicketTypeFilter,
	productFilter = 'all',
	setProductFilter,
	tagFilter = 'all',
	setTagFilter,
	customerTypeFilter = 'all',
	setCustomerTypeFilter,
	dateRangeFilter = 'all',
	setDateRangeFilter,
	assignedToFilter = 'all',
	setAssignedToFilter,
	taxFilters = {},
	setTaxFilters,
	sortBy = 'newest',
	setSortBy,
	onResetFilters,
	onRefreshTickets,
	onManualRefresh,
	onRefreshTicketDetails,
	onShowNotice,
	userPermissions = {},
}) {
	// Left Folders & Views sidebar: Collapsed by default
	const [isFoldersExpanded, setIsFoldersExpanded] = useState(false);

	// Collapsible Left "All Tickets" Panel in Ticket Detail View: Collapsed by default
	const [isLeftTicketsPanelExpanded, setIsLeftTicketsPanelExpanded] = useState(false);

	// Manual refresh spinning indicator state
	const [isRefreshing, setIsRefreshing] = useState(false);

	// Active View / Folder filter
	const [activeFolder, setActiveFolder] = useState('all');
	const [activeView, setActiveView] = useState(null);

	// Expandable filter toggle state
	const [showMoreFilters, setShowMoreFilters] = useState(false);

	// Composer state
	const [replyText, setReplyText] = useState('');
	const [replyAttachments, setReplyAttachments] = useState([]);
	const [noteText, setNoteText] = useState('');
	const [isPinnedNote, setIsPinnedNote] = useState(false);
	const [markAsResolved, setMarkAsResolved] = useState(false);
	const [submitting, setSubmitting] = useState(false);
	const [aiSuggestLoading, setAiSuggestLoading] = useState(false);
	const [replyEditorMode, setReplyEditorMode] = useState('visual');

	// New Ticket Modal state
	const [isNewTicketModalOpen, setIsNewTicketModalOpen] = useState(false);

	// Multi-select tickets state
	const [selectedTicketIds, setSelectedTicketIds] = useState([]);

	// Starred & Flagged local states
	const [starredTickets, setStarredTickets] = useState({});
	const [flaggedTickets, setFlaggedTickets] = useState({});

	// Active Ticket Viewers (Multi-agent presence)
	const [activeViewers, setActiveViewers] = useState([]);

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

	// Agent Viewing Presence Tracker for active ticket
	useEffect(() => {
		if (!selectedTicketId) {
			setActiveViewers([]);
			return;
		}
		const ticketId = selectedTicketId;

		const sendPresence = async (isViewing) => {
			try {
				const res = await apiFetch({
					path: `/dctc-ai/v1/support/tickets/${ticketId}/presence`,
					method: 'POST',
					data: { viewing: isViewing ? 1 : 0 },
				});
				if (res && Array.isArray(res.viewing_users)) {
					setActiveViewers(res.viewing_users);
				}
			} catch (e) { }
		};

		if (typeof document === 'undefined' || !document.hidden) {
			sendPresence(1);
		}

		const presenceInterval = setInterval(() => {
			if (typeof document !== 'undefined' && document.hidden) {
				sendPresence(0);
				return;
			}
			sendPresence(1);
		}, 10000);

		const handleVisibilityChange = () => {
			if (document.hidden) {
				sendPresence(0);
			} else {
				sendPresence(1);
			}
		};

		const handleBeforeUnload = () => {
			sendPresence(0);
		};

		if (typeof document !== 'undefined') {
			document.addEventListener('visibilitychange', handleVisibilityChange);
			window.addEventListener('beforeunload', handleBeforeUnload);
		}

		return () => {
			clearInterval(presenceInterval);
			if (typeof document !== 'undefined') {
				document.removeEventListener('visibilitychange', handleVisibilityChange);
				window.removeEventListener('beforeunload', handleBeforeUnload);
			}
			sendPresence(0);
		};
	}, [selectedTicketId]);

	// Sync active viewers from selectedTicket if refreshed
	useEffect(() => {
		if (selectedTicket?.viewing_users && Array.isArray(selectedTicket.viewing_users)) {
			setActiveViewers(selectedTicket.viewing_users);
		}
	}, [selectedTicket?.viewing_users]);

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
			if (t.status !== 'resolved' && t.status !== 'closed' && t.status !== 'trash') counts.open++;
			if (t.status === 'pending' || t.status === 'waiting_customer' || t.status === 'waiting_agent' || t.status === 'hold') counts.pending++;
			if (t.status === 'resolved') counts.resolved++;
			if (t.status === 'closed') counts.closed++;
			if (t.status === 'trash') counts.trash++;
			if (!t.assigned_agent_id || t.assigned_agent_id === 0) counts.unassigned++;
			if (t.priority === 'high' || t.priority === 'urgent') counts.high_priority++;
			if (t.status === 'pending') counts.waiting_reply++;
			if (t.control_mode === 'ai' || t.origin_type === 'chatbot' || t.origin_type === 'ai' || (t.session_id && t.session_id !== '')) {
				counts.ai_suggested++;
			}
		});

		return counts;
	}, [tickets, totalTickets]);

	// Count active secondary filters
	const activeSecondaryFilterCount = useMemo(() => {
		let count = 0;
		if (priorityFilter !== 'all') count++;
		if (ticketTypeFilter !== 'all') count++;
		if (categoryFilter !== 'all') count++;
		if (productFilter !== 'all') count++;
		if (assignedToFilter !== 'all') count++;
		if (tagFilter !== 'all') count++;
		if (dateRangeFilter !== 'all') count++;
		if (customerTypeFilter !== 'all') count++;
		Object.values(taxFilters || {}).forEach((val) => {
			if (val && val !== 'all') count++;
		});
		return count;
	}, [priorityFilter, ticketTypeFilter, categoryFilter, productFilter, assignedToFilter, tagFilter, dateRangeFilter, customerTypeFilter, taxFilters]);

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
		if (setCurrentPage) setCurrentPage(1);
		if (folderKey === 'all') {
			if (setStatusFilter) setStatusFilter('all');
			if (setAssignedToFilter) setAssignedToFilter('all');
		} else if (['open', 'pending', 'resolved', 'closed', 'trash', 'ai_bot'].includes(folderKey)) {
			if (setStatusFilter) setStatusFilter(folderKey);
			if (setAssignedToFilter) setAssignedToFilter('all');
		} else if (folderKey === 'unassigned') {
			if (setStatusFilter) setStatusFilter('all');
			if (setAssignedToFilter) setAssignedToFilter('0');
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
		} else if (viewKey === 'ai_suggested') {
			setStatusFilter('ai_bot');
		}
	};

	// Open / Close ticket
	const handleOpenTicket = (ticketId) => {
		setSelectedTicketId(ticketId);
		setIsFoldersExpanded(false);
	};

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
				setReplyAttachments([]);
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
			if (data?.success && data.suggested_reply) {
				setReplyText(data.suggested_reply);
				onShowNotice(__('AI drafted a response based on context!', 'dragwyb-click-to-chat'), 'info');
			}
		} catch (err) {
			console.error('Error generating AI reply:', err);
			onShowNotice(__('Could not generate AI reply.', 'dragwyb-click-to-chat'), 'error');
		} finally {
			setAiSuggestLoading(false);
		}
	};

	// Manual Refresh action
	const handleManualRefreshTickets = async () => {
		setIsRefreshing(true);
		try {
			if (onManualRefresh) {
				await onManualRefresh();
			} else if (onRefreshTickets) {
				await onRefreshTickets();
			}
			onShowNotice(__('Support tickets refreshed.', 'dragwyb-click-to-chat'), 'success');
		} catch (err) {
			console.error('Refresh error:', err);
		} finally {
			setTimeout(() => setIsRefreshing(false), 500);
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
		if (onResetFilters) {
			onResetFilters();
		} else {
			if (setStatusFilter) setStatusFilter('all');
			if (setPriorityFilter) setPriorityFilter('all');
			if (setCategoryFilter) setCategoryFilter('all');
			if (setTicketTypeFilter) setTicketTypeFilter('all');
			if (setTaxFilters) setTaxFilters({});
			if (setAssignedToFilter) setAssignedToFilter('all');
			if (setProductFilter) setProductFilter('all');
			if (setTagFilter) setTagFilter('all');
			if (setDateRangeFilter) setDateRangeFilter('all');
			if (setCustomerTypeFilter) setCustomerTypeFilter('all');
			if (setSearchQuery) setSearchQuery('');
		}
		setActiveFolder('all');
		setActiveView(null);
		if (setCurrentPage) setCurrentPage(1);

		if (typeof window !== 'undefined' && window.history?.replaceState) {
			const url = new URL(window.location.href);
			url.searchParams.delete('status');
			url.searchParams.delete('priority');
			url.searchParams.delete('category_id');
			url.searchParams.delete('search');
			window.history.replaceState({}, document.title, url.pathname + url.search + url.hash);
		}

		if (onRefreshTickets) {
			onRefreshTickets();
		}
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

	const toggleStar = (id) => {
		setStarredTickets((prev) => ({ ...prev, [id]: !prev[id] }));
	};

	const toggleFlag = (id) => {
		setFlaggedTickets((prev) => ({ ...prev, [id]: !prev[id] }));
	};

	const toggleTicketSelect = (id, e) => {
		e.stopPropagation();
		setSelectedTicketIds((prev) =>
			prev.includes(id) ? prev.filter((item) => item !== id) : [...prev, id]
		);
	};

	// Filtered tickets from server results
	const filteredTickets = tickets || [];

	const hasActiveSearchOrFilters = Boolean(
		searchQuery ||
		statusFilter !== 'all' ||
		priorityFilter !== 'all' ||
		categoryFilter !== 'all' ||
		ticketTypeFilter !== 'all' ||
		activeSecondaryFilterCount > 0 ||
		(activeFolder && activeFolder !== 'all') ||
		activeView
	);

	const isDatabaseEmpty = !loading && (Number(totalTickets) === 0 && tickets.length === 0) && !hasActiveSearchOrFilters;

	return (
		<div className="dctc-sc-enterprise-container">
			{/* TOP TOOLBAR - Only show when viewing all tickets */}
			{!selectedTicketId && (
				<TicketFilterBar
					isDatabaseEmpty={isDatabaseEmpty}
					isRefreshing={isRefreshing}
					onManualRefresh={handleManualRefreshTickets}
					isFoldersExpanded={isFoldersExpanded}
					setIsFoldersExpanded={setIsFoldersExpanded}
					searchQuery={searchQuery}
					setSearchQuery={setSearchQuery}
					statusFilter={statusFilter}
					setStatusFilter={setStatusFilter}
					setCurrentPage={setCurrentPage}
					showMoreFilters={showMoreFilters}
					setShowMoreFilters={setShowMoreFilters}
					activeSecondaryFilterCount={activeSecondaryFilterCount}
					priorityFilter={priorityFilter}
					setPriorityFilter={setPriorityFilter}
					ticketTypeFilter={ticketTypeFilter}
					setTicketTypeFilter={setTicketTypeFilter}
					taxFilters={taxFilters}
					setTaxFilters={setTaxFilters}
					taxonomies={taxonomies}
					categoryFilter={categoryFilter}
					setCategoryFilter={setCategoryFilter}
					assignedToFilter={assignedToFilter}
					setAssignedToFilter={setAssignedToFilter}
					productFilter={productFilter}
					setProductFilter={setProductFilter}
					tagFilter={tagFilter}
					setTagFilter={setTagFilter}
					dateRangeFilter={dateRangeFilter}
					setDateRangeFilter={setDateRangeFilter}
					customerTypeFilter={customerTypeFilter}
					setCustomerTypeFilter={setCustomerTypeFilter}
					categories={categories}
					agents={agents}
					tags={tags}
					products={products}
					onResetFilters={handleResetFilters}
				/>
			)}

			{/* WORKSPACE CONTAINER */}
			<div className={`dctc-sc-workspace-layout ${isFoldersExpanded ? 'folders-open' : 'folders-closed'} ${selectedTicketId ? 'ticket-active' : 'no-ticket'}`}>
				{/* COLUMN 1: FOLDERS & VIEWS SIDEBAR */}
				{isFoldersExpanded && (
					<TicketFoldersSidebar
						activeFolder={activeFolder}
						activeView={activeView}
						folderCounts={folderCounts}
						onSelectFolder={handleSelectFolder}
						onSelectView={handleSelectView}
						onShowNotice={onShowNotice}
					/>
				)}

				{/* FULL WIDTH ALL TICKETS LIST */}
				{!selectedTicketId && (
					<TicketListTable
						isDatabaseEmpty={isDatabaseEmpty}
						loading={loading}
						filteredTickets={filteredTickets}
						totalTickets={totalTickets}
						activeFolder={activeFolder}
						sortBy={sortBy}
						setSortBy={setSortBy}
						starredTickets={starredTickets}
						flaggedTickets={flaggedTickets}
						selectedTicketIds={selectedTicketIds}
						onOpenTicket={handleOpenTicket}
						onToggleTicketSelect={toggleTicketSelect}
						onResetFilters={handleResetFilters}
						currentPage={currentPage}
						totalPages={totalPages}
						setCurrentPage={setCurrentPage}
						getInitials={getInitials}
						formatRelativeTime={formatRelativeTime}
						onManualRefresh={handleManualRefreshTickets}
						isRefreshing={isRefreshing}
					/>
				)}

				{selectedTicketId && (
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
						{ticketLoading && (!selectedTicket || String(selectedTicket?.id) !== String(selectedTicketId)) ? (
							<div className="dctc-sc-workspace-inner dctc-sc-workspace-skeleton-wrap" style={{ flex: 1, display: 'flex' }}>
								<TicketWorkspaceSkeleton />
							</div>
						) : (
							<TicketDetailWorkspace
								selectedTicket={selectedTicket}
								selectedTicketId={selectedTicketId}
								totalTickets={totalTickets}
								filteredTickets={filteredTickets}
								userPermissions={userPermissions}
								isLeftTicketsPanelExpanded={isLeftTicketsPanelExpanded}
								setIsLeftTicketsPanelExpanded={setIsLeftTicketsPanelExpanded}
								activeViewers={activeViewers}
								starredTickets={starredTickets}
								flaggedTickets={flaggedTickets}
								toggleStar={toggleStar}
								toggleFlag={toggleFlag}
								onOpenTicket={handleOpenTicket}
								onCloseTicket={handleCloseTicket}
								onNavigateTicket={handleNavigateTicket}
								handleStatusChange={handleStatusChange}
								handlePriorityChange={handlePriorityChange}
								handleToggleControl={handleToggleControl}
								handleSendReply={handleSendReply}
								handleAddNote={handleAddNote}
								handleSuggestAiReply={handleSuggestAiReply}
								replyText={replyText}
								setReplyText={setReplyText}
								replyAttachments={replyAttachments}
								setReplyAttachments={setReplyAttachments}
								noteText={noteText}
								setNoteText={setNoteText}
								isPinnedNote={isPinnedNote}
								setIsPinnedNote={setIsPinnedNote}
								markAsResolved={markAsResolved}
								setMarkAsResolved={setMarkAsResolved}
								submitting={submitting}
								aiSuggestLoading={aiSuggestLoading}
								replyEditorMode={replyEditorMode}
								setReplyEditorMode={setReplyEditorMode}
								getInitials={getInitials}
							/>
						)}

						{/* COLUMN 4: RIGHT SIDEBAR */}
						{ticketLoading && (!selectedTicket || String(selectedTicket?.id) !== String(selectedTicketId)) ? (
							<TicketDetailsSidebarSkeleton />
						) : selectedTicket ? (
							<TicketSidebar
								selectedTicket={selectedTicket}
								categories={categories}
								products={products}
								agents={agents}
								tags={tags}
								wcData={wcData}
								onRefreshTicketDetails={onRefreshTicketDetails}
								onRefreshTickets={onRefreshTickets}
								onShowNotice={onShowNotice}
								handleStatusChange={handleStatusChange}
								handlePriorityChange={handlePriorityChange}
								handleAssignAgent={handleAssignAgent}
								handleDeleteTicket={handleDeleteTicket}
								getInitials={getInitials}
							/>
						) : null}
					</>
				)}
			</div>

			{/* CREATE NEW TICKET MODAL */}
			<NewTicketModal
				isOpen={isNewTicketModalOpen}
				onClose={() => setIsNewTicketModalOpen(false)}
				categories={categories}
				onTicketCreated={(newId) => {
					onRefreshTickets();
					if (newId) handleOpenTicket(newId);
				}}
				onShowNotice={onShowNotice}
			/>
		</div>
	);
}
