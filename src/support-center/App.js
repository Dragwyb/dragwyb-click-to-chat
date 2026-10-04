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
import CategoriesView from './views/CategoriesView';
import TagsView from './views/TagsView';
import SettingsView from './views/SettingsView';

export default function App() {
	// Subtab router
	const [ activeTab, setActiveTab ] = useState( () => {
		const searchParams = new URLSearchParams( window.location.search );
		const page = searchParams.get( 'page' ) || '';
		const subtab = searchParams.get( 'subtab' ) || '';
		if ( subtab ) return subtab;
		if ( page === 'dragwyb-support-tickets' ) return 'tickets';
		if ( page === 'dragwyb-support-agents' ) return 'agents';
		if ( page === 'dragwyb-support-categories' ) return 'categories';
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

	// WooCommerce context
	const [ wcData, setWcData ] = useState( null );
	const [ wcLoading, setWcLoading ] = useState( false );

	const showNotice = ( message, type = 'success' ) => {
		setNotice( { message, type } );
		setTimeout( () => setNotice( null ), 6000 );
	};

	// Fetch Metadata
	const fetchMetaData = useCallback( async () => {
		try {
			const [ catData, tagData, agentData, setData ] = await Promise.all( [
				apiFetch( { path: '/dctc-ai/v1/support/categories' } ),
				apiFetch( { path: '/dctc-ai/v1/support/tags' } ),
				apiFetch( { path: '/dctc-ai/v1/support/agents' } ),
				apiFetch( { path: '/dctc-ai/v1/support/settings' } ),
			] );

			if ( catData?.success ) setCategories( catData.categories || [] );
			if ( tagData?.success ) setTags( tagData.tags || [] );
			if ( agentData?.success ) setAgents( agentData.agents || [] );
			if ( setData?.success ) setSupportSettings( setData.settings || {} );
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

	// Fetch Single Ticket Details
	const fetchTicketDetails = useCallback( async ( ticketId ) => {
		if ( ! ticketId ) return;
		setTicketLoading( true );
		try {
			const data = await apiFetch( {
				path: `/dctc-ai/v1/support/tickets/${ ticketId }`,
			} );
			if ( data?.success && data.ticket ) {
				setSelectedTicket( data.ticket );
			}
		} catch ( err ) {
			console.error( 'Error fetching ticket detail:', err );
		} finally {
			setTicketLoading( false );
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
						<span className="dctc-sc-app-tagline">{ __( 'Hybrid AI & Agent Helpdesk', 'dragwyb-click-to-chat' ) }</span>
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

					<button
						type="button"
						className={ `dctc-sc-nav-link ${ activeTab === 'agents' ? 'active' : '' }` }
						onClick={ () => setActiveTab( 'agents' ) }
					>
						<span className="dashicons dashicons-groups"></span>
						{ __( 'Agents & Staff', 'dragwyb-click-to-chat' ) }
					</button>

					<button
						type="button"
						className={ `dctc-sc-nav-link ${ activeTab === 'categories' ? 'active' : '' }` }
						onClick={ () => setActiveTab( 'categories' ) }
					>
						<span className="dashicons dashicons-category"></span>
						{ __( 'Categories', 'dragwyb-click-to-chat' ) }
					</button>

					<button
						type="button"
						className={ `dctc-sc-nav-link ${ activeTab === 'tags' ? 'active' : '' }` }
						onClick={ () => setActiveTab( 'tags' ) }
					>
						<span className="dashicons dashicons-tag"></span>
						{ __( 'Tags', 'dragwyb-click-to-chat' ) }
					</button>

					<button
						type="button"
						className={ `dctc-sc-nav-link ${ activeTab === 'settings' ? 'active' : '' }` }
						onClick={ () => setActiveTab( 'settings' ) }
					>
						<span className="dashicons dashicons-admin-generic"></span>
						{ __( 'Settings', 'dragwyb-click-to-chat' ) }
					</button>
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
					/>
				) }

				{ activeTab === 'agents' && (
					<AgentsView
						agents={ agents }
						onRefresh={ fetchMetaData }
					/>
				) }

				{ activeTab === 'categories' && (
					<CategoriesView
						categories={ categories }
					/>
				) }

				{ activeTab === 'tags' && (
					<TagsView
						tags={ tags }
					/>
				) }

				{ activeTab === 'settings' && (
					<SettingsView
						supportSettings={ supportSettings }
						setSupportSettings={ setSupportSettings }
						onShowNotice={ showNotice }
					/>
				) }
			</main>
		</div>
	);
}
