/**
 * Support Center Standalone Application Shell
 *
 * Provides a dedicated, full-width support operations workspace.
 */
import { useState, useEffect, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

import DashboardView from './views/DashboardView';
import TicketsView from './views/TicketsView';
import AgentsView from './views/AgentsView';
import TaxonomiesView from './views/TaxonomiesView';
import SettingsView from './views/SettingsView';

export default function App() {
	// User Permissions State
	const [ userPermissions, setUserPermissions ] = useState( () => {
		return window.dctc_support_data?.permissions || {
			view_tickets: true,
			manage_agents: true,
			manage_categories: true,
			manage_tags: true,
			manage_settings: true,
			is_admin: true,
		};
	} );

	// Subtab router
	const [ activeTab, setActiveTab ] = useState( () => {
		const searchParams = new URLSearchParams( window.location.search );
		const page = searchParams.get( 'page' ) || '';
		const subtab = searchParams.get( 'subtab' ) || '';
		if ( subtab === 'categories' || subtab === 'tags' || subtab === 'taxonomies' ) return 'taxonomies';
		if ( subtab ) return subtab;
		if ( page === 'dragwyb-support-tickets' ) return 'tickets';
		if ( page === 'dragwyb-support-agents' ) return 'agents';
		if ( page === 'dragwyb-support-categories' || page === 'dragwyb-support-tags' || page === 'dragwyb-support-taxonomies' ) return 'taxonomies';
		if ( page === 'dragwyb-support-settings' ) return 'settings';
		return 'dashboard';
	} );

	// Global Toast / Notice
	const [ notice, setNotice ] = useState( null );

	// Dashboard Stats
	const [ dashboardStats, setDashboardStats ] = useState( null );
	const [ statsLoading, setStatsLoading ] = useState( false );
	const [ statusUpdating, setStatusUpdating ] = useState( false );

	// Tickets State
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
	const [ permissionsMatrix, setPermissionsMatrix ] = useState( null );

	// WooCommerce context
	const [ wcData, setWcData ] = useState( null );
	const [ wcLoading, setWcLoading ] = useState( false );

	const canManageAgents = !! ( userPermissions.is_admin || userPermissions.manage_agents );
	const canManageTaxonomies = !! ( userPermissions.is_admin || userPermissions.manage_categories || userPermissions.manage_tags );
	const canManageSettings = !! ( userPermissions.is_admin || userPermissions.manage_settings );

	// Auto guard tabs against direct URL access if not authorized
	useEffect( () => {
		if ( activeTab === 'agents' && ! canManageAgents ) {
			setActiveTab( 'dashboard' );
		} else if ( activeTab === 'taxonomies' && ! canManageTaxonomies ) {
			setActiveTab( 'dashboard' );
		} else if ( activeTab === 'settings' && ! canManageSettings ) {
			setActiveTab( 'dashboard' );
		}
	}, [ activeTab, canManageAgents, canManageTaxonomies, canManageSettings ] );

	const showNotice = ( message, type = 'success' ) => {
		setNotice( { message, type } );
		setTimeout( () => setNotice( null ), 6000 );
	};

	// Fetch Metadata & Permissions
	const fetchMetaData = useCallback( async () => {
		try {
			const promises = [
				apiFetch( { path: '/dctc-ai/v1/support/categories' } ),
				apiFetch( { path: '/dctc-ai/v1/support/tags' } ),
				apiFetch( { path: '/dctc-ai/v1/support/agents' } ),
				apiFetch( { path: '/dctc-ai/v1/support/settings' } ),
				apiFetch( { path: '/dctc-ai/v1/support/permissions' } ),
			];
			const [ catRes, tagRes, agentRes, setRes, permRes ] = await Promise.allSettled( promises );

			if ( catRes.status === 'fulfilled' && catRes.value?.success ) setCategories( catRes.value.categories || [] );
			if ( tagRes.status === 'fulfilled' && tagRes.value?.success ) setTags( tagRes.value.tags || [] );
			if ( agentRes.status === 'fulfilled' && agentRes.value?.success ) setAgents( agentRes.value.agents || [] );
			if ( setRes.status === 'fulfilled' && setRes.value?.success ) setSupportSettings( setRes.value.settings || {} );
			if ( permRes.status === 'fulfilled' && permRes.value?.success ) {
				if ( permRes.value.permissions_matrix ) setPermissionsMatrix( permRes.value.permissions_matrix );
				if ( permRes.value.user_permissions ) setUserPermissions( permRes.value.user_permissions );
			}
		} catch ( err ) {
			console.error( 'Error fetching support metadata:', err );
		}
	}, [] );

	// Fetch Dashboard Stats
	const fetchDashboardStats = useCallback( async () => {
		setStatsLoading( true );
		try {
			const data = await apiFetch( { path: '/dctc-ai/v1/support/dashboard' } );
			if ( data?.success ) {
				setDashboardStats( data.stats || null );
			}
		} catch ( err ) {
			console.error( 'Error fetching dashboard stats:', err );
		} finally {
			setStatsLoading( false );
		}
	}, [] );

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

			const data = await apiFetch( {
				path: `/dctc-ai/v1/support/tickets?${ queryParams.toString() }`,
			} );
			if ( data?.success ) {
				setTickets( data.tickets || [] );
				setTotalTickets( data.total || 0 );
				setTotalPages( data.total_pages || 1 );

				if ( ! selectedTicketId && data.tickets?.length > 0 ) {
					setSelectedTicketId( data.tickets[ 0 ].id );
				}
			}
		} catch ( err ) {
			console.error( 'Error fetching tickets:', err );
		} finally {
			setLoading( false );
		}
	}, [ currentPage, statusFilter, priorityFilter, categoryFilter, searchQuery, selectedTicketId ] );

	// Fetch Single Ticket Details with silent polling and change detection
	const fetchTicketDetails = useCallback( async ( ticketId, isSilent = false ) => {
		if ( ! ticketId ) return;
		if ( ! isSilent ) {
			setTicketLoading( true );
		}
		try {
			const data = await apiFetch( {
				path: `/dctc-ai/v1/support/tickets/${ ticketId }`,
			} );
			if ( data?.success && data.ticket ) {
				setSelectedTicket( ( prevTicket ) => {
					if ( ! prevTicket || prevTicket.id !== data.ticket.id ) {
						return data.ticket;
					}

					const prevMessages = Array.isArray( prevTicket.messages ) ? prevTicket.messages : [];
					const incomingMessages = Array.isArray( data.ticket.messages ) ? data.ticket.messages : [];

					// Check if message count or message contents changed
					let messagesChanged = prevMessages.length !== incomingMessages.length;
					if ( ! messagesChanged ) {
						for ( let i = 0; i < incomingMessages.length; i++ ) {
							const prevM = prevMessages[ i ];
							const newM = incomingMessages[ i ];
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
						JSON.stringify( prevTicket.tags || [] ) !== JSON.stringify( data.ticket.tags || [] ) ||
						JSON.stringify( prevTicket.notes || [] ) !== JSON.stringify( data.ticket.notes || [] ) ||
						JSON.stringify( prevTicket.events || [] ) !== JSON.stringify( data.ticket.events || [] );

					if ( messagesChanged || metadataChanged ) {
						return {
							...prevTicket,
							...data.ticket,
							messages: incomingMessages,
						};
					}

					// Return identical reference to prevent re-render / blink
					return prevTicket;
				} );
			}
		} catch ( err ) {
			console.error( 'Error fetching ticket detail:', err );
		} finally {
			if ( ! isSilent ) {
				setTicketLoading( false );
			}
		}
	}, [] );

	// Fetch WooCommerce Context
	const fetchWooCommerceContext = useCallback( async ( ticketId ) => {
		if ( ! ticketId ) return;
		setWcLoading( true );
		try {
			const data = await apiFetch( {
				path: `/dctc-ai/v1/support/tickets/${ ticketId }/woocommerce`,
			} );
			if ( data?.success ) {
				setWcData( data.woocommerce || null );
			}
		} catch ( err ) {
			console.error( 'Error fetching WooCommerce context:', err );
			setWcData( null );
		} finally {
			setWcLoading( false );
		}
	}, [] );

	// Update Availability
	const handleUpdateStatus = async ( newStatus ) => {
		setStatusUpdating( true );
		try {
			const data = await apiFetch( {
				path: '/dctc-ai/v1/support/agents/me/status',
				method: 'POST',
				data: { status: newStatus },
			} );
			if ( data?.success ) {
				setDashboardStats( ( prev ) => {
					if ( ! prev ) return prev;
					return {
						...prev,
						agent: {
							...prev.agent,
							availability_status: newStatus,
						},
					};
				} );
				showNotice( __( 'Availability status updated.', 'dragwyb-click-to-chat' ), 'success' );
			}
		} catch ( err ) {
			console.error( 'Error updating agent status:', err );
		} finally {
			setStatusUpdating( false );
		}
	};

	// Jump to tickets workspace with filter
	const handleJumpToTickets = ( filter = 'all', ticketId = null ) => {
		setStatusFilter( filter );
		setCurrentPage( 1 );
		if ( ticketId ) {
			setSelectedTicketId( ticketId );
		}
		setActiveTab( 'tickets' );
	};

	useEffect( () => {
		fetchMetaData();
		fetchDashboardStats();
	}, [ fetchMetaData, fetchDashboardStats ] );

	useEffect( () => {
		fetchTickets();
	}, [ fetchTickets ] );

	useEffect( () => {
		if ( selectedTicketId ) {
			fetchTicketDetails( selectedTicketId );
			fetchWooCommerceContext( selectedTicketId );
		}
	}, [ selectedTicketId, fetchTicketDetails, fetchWooCommerceContext ] );

	return (
		<div className="dctc-sc-app-wrapper">
			{ /* Standalone Support Center Top Bar */ }
			<header className="dctc-sc-header-bar">
				<div className="dctc-sc-brand">
					<div className="dctc-sc-brand-icon">
						<span className="dashicons dashicons-tickets-alt"></span>
					</div>
					<div>
						<h1 className="dctc-sc-app-title">{ __( 'Support Center', 'dragwyb-click-to-chat' ) }</h1>
						<span className="dctc-sc-app-tagline">
							{ __( 'Hybrid AI & Agent Helpdesk', 'dragwyb-click-to-chat' ) }
							{ userPermissions.support_role && (
								<span style={ { marginLeft: '8px', fontSize: '11px', fontWeight: 700, padding: '2px 8px', background: '#e0e7ff', color: '#4338ca', borderRadius: '10px', display: 'inline-flex', alignItems: 'center', gap: '4px' } }>
									<span className={ `dashicons ${ userPermissions.is_admin ? 'dashicons-shield' : 'dashicons-businesswoman' }` } style={ { fontSize: '12px', width: '12px', height: '12px' } }></span>
									{ userPermissions.is_admin ? __( 'Admin', 'dragwyb-click-to-chat' ) : userPermissions.support_role.toUpperCase() }
								</span>
							) }
						</span>
					</div>
				</div>

				<nav className="dctc-sc-top-nav">
					<button
						type="button"
						className={ `dctc-sc-nav-link ${ activeTab === 'dashboard' ? 'active' : '' }` }
						onClick={ () => setActiveTab( 'dashboard' ) }
					>
						<span className="dashicons dashicons-dashboard"></span>
						{ __( 'Dashboard', 'dragwyb-click-to-chat' ) }
					</button>

					<button
						type="button"
						className={ `dctc-sc-nav-link ${ activeTab === 'tickets' ? 'active' : '' }` }
						onClick={ () => setActiveTab( 'tickets' ) }
					>
						<span className="dashicons dashicons-format-chat"></span>
						{ __( 'Tickets', 'dragwyb-click-to-chat' ) }
						{ totalTickets > 0 && <span className="dctc-sc-nav-badge">{ totalTickets }</span> }
					</button>

					{ canManageAgents && (
						<button
							type="button"
							className={ `dctc-sc-nav-link ${ activeTab === 'agents' ? 'active' : '' }` }
							onClick={ () => setActiveTab( 'agents' ) }
						>
							<span className="dashicons dashicons-groups"></span>
							{ __( 'Agents & Staff', 'dragwyb-click-to-chat' ) }
						</button>
					) }

					{ canManageTaxonomies && (
						<button
							type="button"
							className={ `dctc-sc-nav-link ${ activeTab === 'taxonomies' ? 'active' : '' }` }
							onClick={ () => setActiveTab( 'taxonomies' ) }
						>
							<span className="dashicons dashicons-category"></span>
							{ __( 'Taxonomies', 'dragwyb-click-to-chat' ) }
						</button>
					) }

					{ canManageSettings && (
						<button
							type="button"
							className={ `dctc-sc-nav-link ${ activeTab === 'settings' ? 'active' : '' }` }
							onClick={ () => setActiveTab( 'settings' ) }
						>
							<span className="dashicons dashicons-admin-generic"></span>
							{ __( 'Settings & Permissions', 'dragwyb-click-to-chat' ) }
						</button>
					) }
				</nav>
			</header>

			{ /* Global Toast Notice */ }
			{ notice && (
				<div className={ `dctc-sc-global-toast ${ notice.type }` }>
					<span className={ `dashicons ${ notice.type === 'success' ? 'dashicons-yes-alt' : notice.type === 'error' ? 'dashicons-warning' : 'dashicons-info' }` }></span>
					<span>{ notice.message }</span>
				</div>
			) }

			{ /* Main Body Content View */ }
			<main className="dctc-sc-main-content">
				{ activeTab === 'dashboard' && (
					<DashboardView
						dashboardStats={ dashboardStats }
						statsLoading={ statsLoading }
						statusUpdating={ statusUpdating }
						onUpdateStatus={ handleUpdateStatus }
						onRefresh={ () => { fetchDashboardStats(); fetchTickets(); } }
						onJumpToTickets={ handleJumpToTickets }
						onSwitchTab={ setActiveTab }
						userPermissions={ userPermissions }
					/>
				) }

				{ activeTab === 'tickets' && (
					<TicketsView
						tickets={ tickets }
						totalTickets={ totalTickets }
						loading={ loading }
						currentPage={ currentPage }
						totalPages={ totalPages }
						setCurrentPage={ setCurrentPage }
						statusFilter={ statusFilter }
						setStatusFilter={ setStatusFilter }
						priorityFilter={ priorityFilter }
						setPriorityFilter={ setPriorityFilter }
						categoryFilter={ categoryFilter }
						setCategoryFilter={ setCategoryFilter }
						searchQuery={ searchQuery }
						setSearchQuery={ setSearchQuery }
						selectedTicketId={ selectedTicketId }
						setSelectedTicketId={ setSelectedTicketId }
						selectedTicket={ selectedTicket }
						ticketLoading={ ticketLoading }
						categories={ categories }
						agents={ agents }
						tags={ tags }
						wcData={ wcData }
						wcLoading={ wcLoading }
						onRefreshTickets={ fetchTickets }
						onRefreshTicketDetails={ fetchTicketDetails }
						onShowNotice={ showNotice }
						userPermissions={ userPermissions }
					/>
				) }

				{ activeTab === 'agents' && canManageAgents && (
					<AgentsView
						agents={ agents }
						onRefresh={ fetchMetaData }
						userPermissions={ userPermissions }
					/>
				) }

				{ activeTab === 'taxonomies' && canManageTaxonomies && (
					<TaxonomiesView
						categories={ categories }
						tags={ tags }
						onRefresh={ fetchMetaData }
						onShowNotice={ showNotice }
						userPermissions={ userPermissions }
					/>
				) }

				{ activeTab === 'settings' && canManageSettings && (
					<SettingsView
						supportSettings={ supportSettings }
						setSupportSettings={ setSupportSettings }
						permissionsMatrix={ permissionsMatrix }
						setPermissionsMatrix={ setPermissionsMatrix }
						onShowNotice={ showNotice }
						userPermissions={ userPermissions }
					/>
				) }
			</main>
		</div>
	);
}
