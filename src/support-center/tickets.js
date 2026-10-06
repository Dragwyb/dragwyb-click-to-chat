/**
 * Support Center — Standalone Tickets Workspace Entry Point
 */
import apiFetch from '@wordpress/api-fetch';
import { useState, useEffect, useCallback, createRoot, render } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import SupportHeader from './components/SupportHeader';
import TicketsView from './views/TicketsView';
import './style.css';

if ( ! window.wpApiSettings?.nonce && window.dctc_support_data?.nonce ) {
	apiFetch.use( apiFetch.createNonceMiddleware( window.dctc_support_data.nonce ) );
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

	const [notice, setNotice] = useState(null);

	// Tickets State
	const [tickets, setTickets] = useState([]);
	const [totalTickets, setTotalTickets] = useState(0);
	const [currentPage, setCurrentPage] = useState(1);
	const [totalPages, setTotalPages] = useState(1);
	const [loading, setLoading] = useState(false);
	const [selectedTicketId, setSelectedTicketId] = useState(null);
	const [selectedTicket, setSelectedTicket] = useState(null);
	const [ticketLoading, setTicketLoading] = useState(false);

	// Filters from URL
	const searchParams = new URLSearchParams(window.location.search);
	const initialStatus = searchParams.get('status') || 'all';
	const [statusFilter, setStatusFilter] = useState(initialStatus);
	const [priorityFilter, setPriorityFilter] = useState('all');
	const [categoryFilter, setCategoryFilter] = useState('all');
	const [searchQuery, setSearchQuery] = useState('');

	// Metadata
	const [categories, setCategories] = useState([]);
	const [tags, setTags] = useState([]);
	const [products, setProducts] = useState([]);
	const [agents, setAgents] = useState([]);
	const [supportSettings, setSupportSettings] = useState({});

	// WooCommerce context
	const [wcData, setWcData] = useState(null);
	const [wcLoading, setWcLoading] = useState(false);

	const showNotice = (message, type = 'success') => {
		setNotice({ message, type });
		setTimeout(() => setNotice(null), 6000);
	};

	// Fetch Metadata
	const fetchMetaData = useCallback(async () => {
		try {
			const promises = [
				apiFetch({ path: '/dctc-ai/v1/support/categories' }),
				apiFetch({ path: '/dctc-ai/v1/support/tags' }),
				apiFetch({ path: '/dctc-ai/v1/support/products' }),
				apiFetch({ path: '/dctc-ai/v1/support/agents' }),
				apiFetch({ path: '/dctc-ai/v1/support/settings' }),
			];
			const [catRes, tagRes, prodRes, agentRes, setRes] = await Promise.allSettled(promises);

			if (catRes.status === 'fulfilled' && catRes.value?.success) setCategories(catRes.value.categories || []);
			if (tagRes.status === 'fulfilled' && tagRes.value?.success) setTags(tagRes.value.tags || []);
			if (prodRes.status === 'fulfilled' && prodRes.value?.success) setProducts(prodRes.value.products || []);
			if (agentRes.status === 'fulfilled' && agentRes.value?.success) setAgents(agentRes.value.agents || []);
			if (setRes.status === 'fulfilled' && setRes.value?.success) setSupportSettings(setRes.value.settings || {});
		} catch (err) {
			console.error('Error fetching support metadata:', err);
		}
	}, []);

	// Fetch Tickets List
	const fetchTickets = useCallback(async () => {
		setLoading(true);
		try {
			const queryParams = new URLSearchParams({
				page: currentPage,
				per_page: 20,
				status: statusFilter,
				priority: priorityFilter,
				category_id: categoryFilter !== 'all' ? categoryFilter : '',
				search: searchQuery,
			});

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
	}, [currentPage, statusFilter, priorityFilter, categoryFilter, searchQuery]);

	// Fetch Single Ticket Details with silent polling and change detection
	const fetchTicketDetails = useCallback(async (ticketId, isSilent = false) => {
		if (!ticketId) return;
		if (!isSilent) {
			setTicketLoading(true);
		}
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
						JSON.stringify(prevTicket.notes || []) !== JSON.stringify(data.ticket.notes || []) ||
						JSON.stringify(prevTicket.events || []) !== JSON.stringify(data.ticket.events || []);

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

	// Ticket polling: 5-second polling when inside a specific ticket conversation
	useEffect(() => {
		if (!selectedTicketId) return;

		let isMounted = true;
		const interval = setInterval(() => {
			if (isMounted && selectedTicketId) {
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
		} else {
			setSelectedTicket(null);
			setWcData(null);
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
					totalPages={totalPages}
					loading={loading}
					onRefreshTickets={fetchTickets}
					selectedTicketId={selectedTicketId}
					setSelectedTicketId={setSelectedTicketId}
					selectedTicket={selectedTicket}
					setSelectedTicket={setSelectedTicket}
					ticketLoading={ticketLoading}
					categories={categories}
					tags={tags}
					products={products}
					agents={agents}
					supportSettings={supportSettings}
					wcData={wcData}
					wcLoading={wcLoading}
					onUpdateTicketProperty={handleUpdateTicketProperty}
					onTakeControl={handleTakeControl}
					onReleaseControl={handleReleaseControl}
					onChangeStatus={handleChangeStatus}
					onChangePriority={handleChangePriority}
					onAssignAgent={handleAssignAgent}
					onReplySubmit={handleReplySubmit}
					onAddInternalNote={handleAddInternalNote}
					onDeleteTicket={handleDeleteTicket}
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
