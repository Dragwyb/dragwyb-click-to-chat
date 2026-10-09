/**
 * Support Center — Standalone Tickets Workspace Entry Point
 */
import apiFetch from '@wordpress/api-fetch';
import { useState, useEffect, useRef, useCallback, createRoot, render } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import SupportHeader from './components/SupportHeader';
import TicketsView from './views/TicketsView';
import './style.css';

if (!window.wpApiSettings?.nonce && window.dctc_support_data?.nonce) {
	apiFetch.use(apiFetch.createNonceMiddleware(window.dctc_support_data.nonce));
}

function TicketsApp() {
	const userPermissions = window.dctc_support_data?.permissions || {
		view_tickets: true,
		manage_agents: true,
		manage_categories: true,
		manage_tags: true,
		manage_settings: true,
		is_admin: true,
	};

	const urlParameters = new URLSearchParams(window.location.search);
	const ticketIdFromUrl = urlParameters.get('ticket_id');

	const [notice, setNotice] = useState(null);

	// Tickets State
	const [tickets, setTickets] = useState([]);
	const [totalTickets, setTotalTickets] = useState(0);
	const [currentPage, setCurrentPage] = useState(1);
	const [perPage, setPerPage] = useState(10);
	const [totalPages, setTotalPages] = useState(1);
	const [loading, setLoading] = useState(false);
	const [selectedTicketId, setSelectedTicketId] = useState(ticketIdFromUrl);
	const [selectedTicket, setSelectedTicket] = useState(null);
	const [ticketLoading, setTicketLoading] = useState(false);

	// Saved Filters from bootstrap / options table
	const savedFilters = window.dctc_support_data?.saved_filters && typeof window.dctc_support_data.saved_filters === 'object'
		? window.dctc_support_data.saved_filters
		: {};

	// Filters from URL or Saved preferences
	const searchParams = new URLSearchParams(window.location.search);
	const urlStatus = searchParams.get('status');
	const initialStatus = urlStatus || savedFilters.status || 'all';

	const [statusFilter, setStatusFilter] = useState(initialStatus);
	const [priorityFilter, setPriorityFilter] = useState(savedFilters.priority || 'all');
	const [categoryFilter, setCategoryFilter] = useState(savedFilters.category_id || savedFilters.category || 'all');
	const [ticketTypeFilter, setTicketTypeFilter] = useState(savedFilters.ticket_type || 'all');
	const [productFilter, setProductFilter] = useState(savedFilters.product_id || savedFilters.product || 'all');
	const [tagFilter, setTagFilter] = useState(savedFilters.tag_id || savedFilters.tag || 'all');
	const [customerTypeFilter, setCustomerTypeFilter] = useState(savedFilters.customer_type || 'all');
	const [dateRangeFilter, setDateRangeFilter] = useState(savedFilters.date_range || 'all');
	const [assignedToFilter, setAssignedToFilter] = useState(savedFilters.assigned_agent_id || savedFilters.assigned_to || 'all');
	const [taxFilters, setTaxFilters] = useState(savedFilters.tax_filters || {});
	const [sortBy, setSortBy] = useState(savedFilters.sort_by || 'newest');
	const [searchQuery, setSearchQuery] = useState(savedFilters.search || '');

	// Debounce and Auto-save state tracker
	const isInitialMount = useRef(true);
	const saveTimeoutRef = useRef(null);
	const isTicketFetching = useRef(false);

	// Metadata
	const [categories, setCategories] = useState([]);
	const [tags, setTags] = useState([]);
	const [products, setProducts] = useState([]);
	const [agents, setAgents] = useState([]);
	const [taxonomies, setTaxonomies] = useState([]);
	const [supportSettings, setSupportSettings] = useState({});

	// WooCommerce context
	const [wcData, setWcData] = useState(null);
	const [wcLoading, setWcLoading] = useState(false);

	const showNotice = (message, type = 'success') => {
		setNotice({ message, type });
		setTimeout(() => setNotice(null), 6000);
	};

	const updateTickeIdInUrl = (ticketId) => {
		const newUrl = new URL(window.location);
		if (ticketId) {
			newUrl.searchParams.set('ticket_id', ticketId);
		} else {
			newUrl.searchParams.delete('ticket_id');
		}
		window.history.pushState({}, '', newUrl);
	};

	// Auto-save user filters to database (option / user meta table)
	useEffect(() => {
		if (isInitialMount.current) {
			isInitialMount.current = false;
			return;
		}

		if (saveTimeoutRef.current) {
			clearTimeout(saveTimeoutRef.current);
		}

		saveTimeoutRef.current = setTimeout(async () => {
			try {
				const currentFilters = {
					status: statusFilter,
					priority: priorityFilter,
					category_id: categoryFilter,
					ticket_type: ticketTypeFilter,
					product_id: productFilter,
					tag_id: tagFilter,
					customer_type: customerTypeFilter,
					date_range: dateRangeFilter,
					assigned_agent_id: assignedToFilter,
					tax_filters: taxFilters,
					sort_by: sortBy,
					search: searchQuery,
				};

				const isDefault =
					statusFilter === 'all' &&
					priorityFilter === 'all' &&
					categoryFilter === 'all' &&
					ticketTypeFilter === 'all' &&
					productFilter === 'all' &&
					tagFilter === 'all' &&
					customerTypeFilter === 'all' &&
					dateRangeFilter === 'all' &&
					assignedToFilter === 'all' &&
					sortBy === 'newest' &&
					!searchQuery &&
					(!taxFilters || Object.keys(taxFilters).length === 0);

				await apiFetch({
					path: '/dctc-ai/v1/support/user-filters',
					method: 'POST',
					data: isDefault ? { reset: true } : { filters: currentFilters },
				});
			} catch (err) {
				console.warn('Failed to auto-save user filter options:', err);
			}
		}, 500);

		return () => {
			if (saveTimeoutRef.current) {
				clearTimeout(saveTimeoutRef.current);
			}
		};
	}, [
		statusFilter,
		priorityFilter,
		categoryFilter,
		ticketTypeFilter,
		productFilter,
		tagFilter,
		customerTypeFilter,
		dateRangeFilter,
		assignedToFilter,
		taxFilters,
		sortBy,
		searchQuery,
	]);

	// Reset All Filters and Delete from Database
	const handleResetFilters = useCallback(async () => {
		setStatusFilter('all');
		setPriorityFilter('all');
		setCategoryFilter('all');
		setTicketTypeFilter('all');
		setProductFilter('all');
		setTagFilter('all');
		setCustomerTypeFilter('all');
		setDateRangeFilter('all');
		setAssignedToFilter('all');
		setTaxFilters({});
		setSortBy('newest');
		setSearchQuery('');
		setCurrentPage(1);

		if (typeof window !== 'undefined' && window.history?.replaceState) {
			const url = new URL(window.location.href);
			url.searchParams.delete('status');
			url.searchParams.delete('priority');
			url.searchParams.delete('category_id');
			url.searchParams.delete('search');
			window.history.replaceState({}, document.title, url.pathname + url.search + url.hash);
		}

		try {
			await apiFetch({
				path: '/dctc-ai/v1/support/user-filters',
				method: 'POST',
				data: { reset: true },
			});
		} catch (err) {
			console.warn('Failed to delete saved filters option:', err);
		}
	}, []);

	// Fetch Metadata
	const fetchMetaData = useCallback(async () => {
		try {
			const promises = [
				apiFetch({ path: '/dctc-ai/v1/support/categories' }),
				apiFetch({ path: '/dctc-ai/v1/support/tags' }),
				apiFetch({ path: '/dctc-ai/v1/support/products' }),
				apiFetch({ path: '/dctc-ai/v1/support/agents' }),
				apiFetch({ path: '/dctc-ai/v1/support/settings' }),
				apiFetch({ path: '/dctc-ai/v1/support/taxonomies' }),
			];
			const [catRes, tagRes, prodRes, agentRes, setRes, taxRes] = await Promise.allSettled(promises);

			if (catRes.status === 'fulfilled' && catRes.value?.success) setCategories(catRes.value.categories || []);
			if (tagRes.status === 'fulfilled' && tagRes.value?.success) setTags(tagRes.value.tags || []);
			if (prodRes.status === 'fulfilled' && prodRes.value?.success) setProducts(prodRes.value.products || []);
			if (agentRes.status === 'fulfilled' && agentRes.value?.success) setAgents(agentRes.value.agents || []);
			if (setRes.status === 'fulfilled' && setRes.value?.success) setSupportSettings(setRes.value.settings || {});
			if (taxRes.status === 'fulfilled' && taxRes.value?.success) setTaxonomies(taxRes.value.taxonomies || []);
		} catch (err) {
			console.error('Error fetching support metadata:', err);
		}
	}, []);

	// Fetch Tickets List with all active filters
	const fetchTickets = useCallback(async () => {
		setLoading(true);
		try {
			const queryParams = new URLSearchParams({
				page: currentPage,
				per_page: perPage,
				status: statusFilter,
				priority: priorityFilter,
				category_id: categoryFilter !== 'all' ? categoryFilter : '',
				ticket_type: ticketTypeFilter !== 'all' ? ticketTypeFilter : '',
				product_id: productFilter !== 'all' ? productFilter : '',
				tag_id: tagFilter !== 'all' ? tagFilter : '',
				customer_type: customerTypeFilter !== 'all' ? customerTypeFilter : '',
				date_range: dateRangeFilter !== 'all' ? dateRangeFilter : '',
				assigned_agent_id: assignedToFilter !== 'all' ? assignedToFilter : '',
				search: searchQuery,
			});

			if (sortBy === 'oldest') {
				queryParams.set('orderby', 'created_at');
				queryParams.set('order', 'ASC');
			} else if (sortBy === 'priority') {
				queryParams.set('orderby', 'priority');
				queryParams.set('order', 'DESC');
			} else {
				queryParams.set('orderby', 'created_at');
				queryParams.set('order', 'DESC');
			}

			if (taxFilters && Object.keys(taxFilters).length > 0) {
				queryParams.set('tax_terms', JSON.stringify(taxFilters));
			}

			const data = await apiFetch({
				path: `/dctc-ai/v1/support/tickets?${queryParams.toString()}`,
			});
			if (data?.success) {
				setTickets(data.tickets || []);
				setTotalTickets(data.total || 0);
				setTotalPages(data.total_pages || 1);
			}
		} catch (err) {
			console.error('Error fetching tickets:', err);
		} finally {
			setLoading(false);
		}
	}, [
		currentPage,
		perPage,
		statusFilter,
		priorityFilter,
		categoryFilter,
		ticketTypeFilter,
		productFilter,
		tagFilter,
		customerTypeFilter,
		dateRangeFilter,
		assignedToFilter,
		taxFilters,
		sortBy,
		searchQuery,
	]);

	// Fetch Single Ticket Details with silent polling and change detection
	const fetchTicketDetails = useCallback(async (ticketId, isSilent = false) => {
		if (!ticketId) return;
		if (!isSilent) {
			setTicketLoading(true);
		}

		isTicketFetching.current = true;

		try {
			const data = await apiFetch({
				path: `/dctc-ai/v1/support/tickets/${ticketId}`,
			});
			if (data?.success && data.ticket) {
				setSelectedTicket((prevTicket) => {
					if (!prevTicket || prevTicket.id !== data.ticket.id) {
						return data.ticket;
					}

					const prevMessages = Array.isArray(prevTicket.messages) ? prevTicket.messages : [];
					const incomingMessages = Array.isArray(data.ticket.messages) ? data.ticket.messages : [];

					let messagesChanged = prevMessages.length !== incomingMessages.length;
					if (!messagesChanged) {
						for (let i = 0; i < incomingMessages.length; i++) {
							const prevM = prevMessages[i];
							const newM = incomingMessages[i];
							if (
								prevM.id !== newM.id ||
								prevM.content !== newM.content ||
								prevM.role !== newM.role ||
								prevM.sender_type !== newM.sender_type ||
								prevM.sender_name !== newM.sender_name ||
								prevM.created_at !== newM.created_at
							) {
								messagesChanged = true;
								break;
							}
						}
					}

					const metadataChanged =
						prevTicket.status !== data.ticket.status ||
						prevTicket.priority !== data.ticket.priority ||
						prevTicket.assigned_agent_id !== data.ticket.assigned_agent_id ||
						prevTicket.control_mode !== data.ticket.control_mode ||
						prevTicket.subject !== data.ticket.subject ||
						JSON.stringify(prevTicket.tags || []) !== JSON.stringify(data.ticket.tags || []) ||
						JSON.stringify(prevTicket.notes || []) !== JSON.stringify(data.ticket.notes || []);

					if (messagesChanged || metadataChanged) {
						return {
							...prevTicket,
							...data.ticket,
							messages: incomingMessages,
						};
					}

					return prevTicket;
				});
			}
		} catch (err) {
			console.error('Error fetching ticket detail:', err);
		} finally {
			if (!isSilent) {
				setTicketLoading(false);
			}
		}

		isTicketFetching.current = false;
	}, []);

	// Fetch WooCommerce Context
	const fetchWooCommerceContext = useCallback(async (ticketId) => {
		if (!ticketId) return;
		setWcLoading(true);
		try {
			const data = await apiFetch({
				path: `/dctc-ai/v1/support/tickets/${ticketId}/woocommerce`,
			});
			if (data?.success) {
				setWcData(data.woocommerce || null);
			}
		} catch (err) {
			console.error('Error fetching WooCommerce context:', err);
		} finally {
			setWcLoading(false);
		}
	}, []);

	// Initial load
	useEffect(() => {
		fetchMetaData();
	}, [fetchMetaData]);

	useEffect(() => {
		fetchTickets();
	}, [fetchTickets]);

	const bodyAddTicketViewCls = (status) => {
		if (status) {
			document.documentElement.scrollTop = 0;
		}
		document.body.classList.toggle('dctc-view-ticket', status);
	}

	// Ticket polling: 5-second polling when inside a specific ticket conversation
	useEffect(() => {
		if (!selectedTicketId) {
			bodyAddTicketViewCls(false)
			return
		};

		bodyAddTicketViewCls(true);

		let isMounted = true;
		const interval = setInterval(() => {
			if (isMounted && selectedTicketId && !isTicketFetching.current) {
				fetchTicketDetails(selectedTicketId, true);
			}
		}, 5000);

		return () => {
			isMounted = false;
			clearInterval(interval);
		};
	}, [selectedTicketId, fetchTicketDetails]);

	// 1-minute active polling to check for new tickets when viewing all tickets
	useEffect(() => {
		if (selectedTicketId) return;

		const interval = setInterval(() => {
			fetchTickets();
		}, 60000);

		return () => {
			clearInterval(interval);
		};
	}, [selectedTicketId, fetchTickets]);

	// Select ticket effect
	useEffect(() => {
		if (selectedTicketId) {
			fetchTicketDetails(selectedTicketId);
			fetchWooCommerceContext(selectedTicketId);
			updateTickeIdInUrl(selectedTicketId);
		} else {
			setSelectedTicket(null);
			setWcData(null);
			updateTickeIdInUrl(null);
		}
	}, [selectedTicketId, fetchTicketDetails, fetchWooCommerceContext]);

	// Ticket Actions
	const handleTakeControl = async (ticketId) => {
		try {
			const data = await apiFetch({
				path: `/dctc-ai/v1/support/tickets/${ticketId}/take-control`,
				method: 'POST',
			});
			if (data?.success) {
				setSelectedTicket((prev) => (prev ? { ...prev, control_mode: 'human' } : null));
				showNotice(__('Human control active. AI responses paused for this session.', 'dragwyb-click-to-chat'), 'info');
				fetchTicketDetails(ticketId);
			}
		} catch (err) {
			console.error('Error taking control:', err);
			showNotice(__('Failed to take control.', 'dragwyb-click-to-chat'), 'error');
		}
	};

	const handleReleaseControl = async (ticketId) => {
		try {
			const data = await apiFetch({
				path: `/dctc-ai/v1/support/tickets/${ticketId}/release-control`,
				method: 'POST',
			});
			if (data?.success) {
				setSelectedTicket((prev) => (prev ? { ...prev, control_mode: 'ai' } : null));
				showNotice(__('Control returned to AI Assistant.', 'dragwyb-click-to-chat'), 'success');
				fetchTicketDetails(ticketId);
			}
		} catch (err) {
			console.error('Error releasing control:', err);
			showNotice(__('Failed to release control.', 'dragwyb-click-to-chat'), 'error');
		}
	};

	const handleChangeStatus = async (ticketId, newStatus) => {
		try {
			const data = await apiFetch({
				path: `/dctc-ai/v1/support/tickets/${ticketId}/status`,
				method: 'POST',
				data: { status: newStatus },
			});
			if (data?.success) {
				setSelectedTicket((prev) => (prev ? { ...prev, status: newStatus } : null));
				fetchTickets();
				showNotice(__('Status updated successfully.', 'dragwyb-click-to-chat'), 'success');
			}
		} catch (err) {
			console.error('Error changing status:', err);
			showNotice(__('Failed to change status.', 'dragwyb-click-to-chat'), 'error');
		}
	};

	const handleChangePriority = async (ticketId, newPriority) => {
		try {
			const data = await apiFetch({
				path: `/dctc-ai/v1/support/tickets/${ticketId}/priority`,
				method: 'POST',
				data: { priority: newPriority },
			});
			if (data?.success) {
				setSelectedTicket((prev) => (prev ? { ...prev, priority: newPriority } : null));
				fetchTickets();
				showNotice(__('Priority updated.', 'dragwyb-click-to-chat'), 'success');
			}
		} catch (err) {
			console.error('Error changing priority:', err);
		}
	};

	const handleAssignAgent = async (ticketId, agentId) => {
		try {
			const data = await apiFetch({
				path: `/dctc-ai/v1/support/tickets/${ticketId}/assign`,
				method: 'POST',
				data: { agent_id: agentId },
			});
			if (data?.success) {
				setSelectedTicket((prev) => (prev ? { ...prev, assigned_agent_id: agentId } : null));
				fetchTickets();
				showNotice(__('Ticket reassigned.', 'dragwyb-click-to-chat'), 'success');
			}
		} catch (err) {
			console.error('Error assigning agent:', err);
		}
	};

	const handleUpdateTicketProperty = async (ticketId, propertyKey, value) => {
		try {
			const data = await apiFetch({
				path: `/dctc-ai/v1/support/tickets/${ticketId}`,
				method: 'POST',
				data: { [propertyKey]: value },
			});
			if (data?.success && data.ticket) {
				setSelectedTicket(data.ticket);
				fetchTickets();
				showNotice(__('Property updated successfully.', 'dragwyb-click-to-chat'), 'success');
			}
		} catch (err) {
			console.error('Error updating ticket property:', err);
			showNotice(__('Failed to update property.', 'dragwyb-click-to-chat'), 'error');
		}
	};

	const handleReplySubmit = async (ticketId, replyContent, attachments = []) => {
		try {
			const data = await apiFetch({
				path: `/dctc-ai/v1/support/tickets/${ticketId}/replies`,
				method: 'POST',
				data: {
					content: replyContent,
					attachments: attachments,
				},
			});
			if (data?.success) {
				fetchTicketDetails(ticketId);
				showNotice(__('Reply sent successfully.', 'dragwyb-click-to-chat'), 'success');
			}
		} catch (err) {
			console.error('Error posting reply:', err);
			showNotice(__('Failed to send reply.', 'dragwyb-click-to-chat'), 'error');
		}
	};

	const handleAddInternalNote = async (ticketId, noteContent) => {
		try {
			const data = await apiFetch({
				path: `/dctc-ai/v1/support/tickets/${ticketId}/notes`,
				method: 'POST',
				data: { content: noteContent },
			});
			if (data?.success) {
				fetchTicketDetails(ticketId);
				showNotice(__('Internal note added.', 'dragwyb-click-to-chat'), 'success');
			}
		} catch (err) {
			console.error('Error adding internal note:', err);
			showNotice(__('Failed to add note.', 'dragwyb-click-to-chat'), 'error');
		}
	};

	const handleDeleteTicket = async (ticketId) => {
		if (!window.confirm(__('Are you sure you want to delete this ticket?', 'dragwyb-click-to-chat'))) {
			return;
		}
		try {
			const data = await apiFetch({
				path: `/dctc-ai/v1/support/tickets/${ticketId}`,
				method: 'DELETE',
			});
			if (data?.success) {
				setSelectedTicketId(null);
				setSelectedTicket(null);
				fetchTickets();
				showNotice(__('Ticket deleted successfully.', 'dragwyb-click-to-chat'), 'success');
			}
		} catch (err) {
			console.error('Error deleting ticket:', err);
			showNotice(__('Failed to delete ticket.', 'dragwyb-click-to-chat'), 'error');
		}
	};

	return (
		<div className="dctc-sc-app-wrap">
			<SupportHeader
				activeTab="tickets"
				userPermissions={userPermissions}
				notice={notice}
			/>
			<main className="dctc-sc-main-content">
				<TicketsView
					tickets={tickets}
					setTickets={setTickets}
					totalTickets={totalTickets}
					currentPage={currentPage}
					setCurrentPage={setCurrentPage}
					perPage={perPage}
					setPerPage={setPerPage}
					totalPages={totalPages}
					loading={loading}
					onRefreshTickets={fetchTickets}
					statusFilter={statusFilter}
					setStatusFilter={setStatusFilter}
					priorityFilter={priorityFilter}
					setPriorityFilter={setPriorityFilter}
					categoryFilter={categoryFilter}
					setCategoryFilter={setCategoryFilter}
					searchQuery={searchQuery}
					setSearchQuery={setSearchQuery}
					selectedTicketId={selectedTicketId}
					setSelectedTicketId={setSelectedTicketId}
					selectedTicket={selectedTicket}
					setSelectedTicket={setSelectedTicket}
					ticketLoading={ticketLoading}
					categories={categories}
					tags={tags}
					products={products}
					agents={agents}
					taxonomies={taxonomies}
					supportSettings={supportSettings}
					wcData={wcData}
					ticketTypeFilter={ticketTypeFilter}
					setTicketTypeFilter={setTicketTypeFilter}
					productFilter={productFilter}
					setProductFilter={setProductFilter}
					tagFilter={tagFilter}
					setTagFilter={setTagFilter}
					customerTypeFilter={customerTypeFilter}
					setCustomerTypeFilter={setCustomerTypeFilter}
					dateRangeFilter={dateRangeFilter}
					setDateRangeFilter={setDateRangeFilter}
					assignedToFilter={assignedToFilter}
					setAssignedToFilter={setAssignedToFilter}
					taxFilters={taxFilters}
					setTaxFilters={setTaxFilters}
					sortBy={sortBy}
					setSortBy={setSortBy}
					onResetFilters={handleResetFilters}
					onUpdateTicketProperty={handleUpdateTicketProperty}
					onTakeControl={handleTakeControl}
					onReleaseControl={handleReleaseControl}
					onChangeStatus={handleChangeStatus}
					onChangePriority={handleChangePriority}
					onAssignAgent={handleAssignAgent}
					onReplySubmit={handleReplySubmit}
					onAddInternalNote={handleAddInternalNote}
					onDeleteTicket={handleDeleteTicket}
					onRefreshTicketDetails={fetchTicketDetails}
					onShowNotice={showNotice}
					userPermissions={userPermissions}
				/>
			</main>
		</div>
	);
}

document.addEventListener('DOMContentLoaded', () => {
	const container = document.getElementById('dctc-support-admin-root');
	if (!container) return;

	if (createRoot) {
		createRoot(container).render(<TicketsApp />);
	} else if (render) {
		render(<TicketsApp />, container);
	}
});
