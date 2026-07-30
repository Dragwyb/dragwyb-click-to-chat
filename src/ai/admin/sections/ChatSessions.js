/**
 * Chat session list — search, filter, export, transcript modal, delete.
 */
import {
	useState,
	useEffect,
	useMemo,
	useCallback,
	useRef,
} from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import ConfirmModal from '../components/ConfirmModal';
import { formatProviderLabel } from '../utils/providers';

function parseMessages( content ) {
	if ( ! content ) {
		return [];
	}
	try {
		const parsed = typeof content === 'string' ? JSON.parse( content ) : content;
		return Array.isArray( parsed ) ? parsed : [];
	} catch {
		return [];
	}
}

function sessionEmail( session ) {
	return session.email
		? session.email
		: __( 'Guest User', 'dragwyb-click-to-chat' );
}

function sessionMeta( session ) {
	return {
		provider: session?.provider
			? formatProviderLabel( session.provider )
			: __( 'Unknown', 'dragwyb-click-to-chat' ),
		model: session?.model || __( 'Unknown', 'dragwyb-click-to-chat' ),
	};
}

function escapeHtml( value ) {
	return String( value ?? '' )
		.replace( /&/g, '&amp;' )
		.replace( /</g, '&lt;' )
		.replace( />/g, '&gt;' );
}

function lastMessagePreview( session ) {
	const msgs = parseMessages( session.content );
	if ( ! msgs.length ) {
		return '';
	}
	const last = msgs[ msgs.length - 1 ];
	const text = ( last?.content || '' ).replace( /\s+/g, ' ' ).trim();
	if ( ! text ) {
		return '';
	}
	return `"${ text.length > 72 ? `${ text.slice( 0, 72 ) }…` : text }"`;
}

function formatDate( value ) {
	if ( ! value ) {
		return '';
	}
	const iso = String( value ).includes( 'T' )
		? value
		: String( value ).replace( ' ', 'T' );
	const date = new Date( iso );
	if ( Number.isNaN( date.getTime() ) ) {
		return value;
	}
	const now = new Date();
	const opts = {
		month: 'short',
		day: 'numeric',
		...( date.getFullYear() !== now.getFullYear() ? { year: 'numeric' } : {} ),
	};
	return `${ date.toLocaleDateString( undefined, opts ) } · ${ date.toLocaleTimeString(
		undefined,
		{ hour: 'numeric', minute: '2-digit' }
	) }`;
}

function pageNumbers( current, total ) {
	if ( total <= 7 ) {
		return Array.from( { length: total }, ( _, i ) => i + 1 );
	}
	const set = new Set( [ 1, total, current ] );
	if ( current > 2 ) {
		set.add( current - 1 );
	}
	if ( current < total - 1 ) {
		set.add( current + 1 );
	}
	const sorted = [ ...set ].sort( ( a, b ) => a - b );
	const out = [];
	for ( let i = 0; i < sorted.length; i++ ) {
		if ( i > 0 && sorted[ i ] - sorted[ i - 1 ] > 1 ) {
			out.push( '…' );
		}
		out.push( sorted[ i ] );
	}
	return out;
}

export default function ChatSessions( { showNotice } ) {
	const [ sessions, setSessions ] = useState( [] );
	const [ loading, setLoading ] = useState( true );
	const [ search, setSearch ] = useState( '' );
	const [ providerFilter, setProviderFilter ] = useState( 'all' );
	const [ page, setPage ] = useState( 1 );
	const [ perPage, setPerPage ] = useState( 10 );
	const [ viewing, setViewing ] = useState( null );
	const [ modalVisible, setModalVisible ] = useState( false );
	const [ deletingId, setDeletingId ] = useState( null );
	const [ confirmDelete, setConfirmDelete ] = useState( null );
	const [ filterOpen, setFilterOpen ] = useState( false );
	const filterRef = useRef( null );
	const [ loadLimit, setLoadLimit ] = useState(
		window.dctc_ai_data?.load_limit || '100'
	);
	const [ loadLimitDraft, setLoadLimitDraft ] = useState( loadLimit );
	const [ sortOrder, setSortOrder ] = useState(
		window.dctc_ai_data?.sort_order || 'desc'
	);
	const [ perPageDraft, setPerPageDraft ] = useState( String( perPage ) );

	useEffect( () => {
		setLoadLimitDraft( loadLimit );
	}, [ loadLimit ] );

	useEffect( () => {
		setPerPageDraft( String( perPage ) );
	}, [ perPage ] );

	const commitLimit = ( raw ) => {
		const trimmed = raw.trim();
		if ( trimmed === '' ) {
			setLoadLimit( 'all' );
		} else {
			const n = parseInt( trimmed, 10 );
			if ( ! isNaN( n ) && n > 0 ) {
				setLoadLimit( String( n ) );
			} else {
				setLoadLimitDraft( loadLimit );
			}
		}
	};

	const commitPerPage = ( raw ) => {
		const n = parseInt( raw.trim(), 10 );
		if ( ! isNaN( n ) && n > 0 ) {
			setPerPage( n );
		} else {
			setPerPageDraft( String( perPage ) );
		}
	};

	const providers = useMemo( () => {
		const set = new Set();
		sessions.forEach( ( s ) => {
			if ( s.provider ) {
				set.add( s.provider );
			}
		} );
		return [ ...set ].sort();
	}, [ sessions ] );

	const filtered = useMemo( () => {
		const q = search.trim().toLowerCase();
		return sessions.filter( ( s ) => {
			if ( providerFilter !== 'all' && ( s.provider || '' ) !== providerFilter ) {
				return false;
			}
			if ( ! q ) {
				return true;
			}
			const email = sessionEmail( s );
			const preview = lastMessagePreview( s ).toLowerCase();
			return (
				s.session_id?.toLowerCase().includes( q ) ||
				email.toLowerCase().includes( q ) ||
				( s.provider || '' ).toLowerCase().includes( q ) ||
				( s.model || '' ).toLowerCase().includes( q ) ||
				preview.includes( q )
			);
		} );
	}, [ sessions, search, providerFilter ] );

	const totalPages = useMemo(
		() => Math.max( 1, Math.ceil( filtered.length / perPage ) ),
		[ filtered.length, perPage ]
	);

	const pageRows = useMemo( () => {
		const start = ( Math.min( page, totalPages ) - 1 ) * perPage;
		return filtered.slice( start, start + perPage );
	}, [ filtered, page, totalPages, perPage ] );

	useEffect( () => {
		let cancelled = false;
		( async () => {
			setLoading( true );
			try {
				const res = await apiFetch( {
					path: `/dctc-ai/v1/sessions?limit=${ loadLimit }&order=${ sortOrder }`,
				} );
				if ( ! cancelled ) {
					setSessions( Array.isArray( res?.sessions ) ? res.sessions : [] );
					if ( res?.load_limit ) {
						setLoadLimit( res.load_limit );
					}
					if ( res?.sort_order ) {
						setSortOrder( res.sort_order );
					}
				}
			} catch {
				if ( ! cancelled ) {
					setSessions( [] );
					showNotice?.(
						__(
							'Could not load chat sessions. Please refresh the page.',
							'dragwyb-click-to-chat'
						),
						'error'
					);
				}
			} finally {
				if ( ! cancelled ) {
					setLoading( false );
				}
			}
		} )();
		return () => {
			cancelled = true;
		};
	}, [ showNotice, loadLimit, sortOrder ] );

	useEffect( () => {
		setPage( 1 );
	}, [ search, providerFilter, perPage, loadLimit, sortOrder ] );

	useEffect( () => {
		if ( ! filterOpen ) {
			return;
		}
		const onDown = ( e ) => {
			if ( filterRef.current && ! filterRef.current.contains( e.target ) ) {
				setFilterOpen( false );
			}
		};
		const onKey = ( e ) => {
			if ( e.key === 'Escape' ) {
				setFilterOpen( false );
			}
		};
		document.addEventListener( 'mousedown', onDown );
		document.addEventListener( 'keydown', onKey );
		return () => {
			document.removeEventListener( 'mousedown', onDown );
			document.removeEventListener( 'keydown', onKey );
		};
	}, [ filterOpen ] );

	useEffect( () => {
		if ( page > totalPages ) {
			setPage( totalPages );
		}
	}, [ page, totalPages ] );

	useEffect( () => {
		if ( viewing ) {
			setModalVisible( true );
			document.body.classList.add( 'dctc-ai-modal-open' );
		} else {
			setModalVisible( false );
			document.body.classList.remove( 'dctc-ai-modal-open' );
		}
		return () => document.body.classList.remove( 'dctc-ai-modal-open' );
	}, [ viewing ] );

	const providerOptions = useMemo(
		() => [
			{
				value: 'all',
				label: __( 'All providers', 'dragwyb-click-to-chat' ),
			},
			...providers.map( ( p ) => ( {
				value: p,
				label: formatProviderLabel( p ),
			} ) ),
		],
		[ providers ]
	);

	const filterLabel =
		providerOptions.find( ( o ) => o.value === providerFilter )?.label ||
		__( 'All providers', 'dragwyb-click-to-chat' );

	const closeView = useCallback( () => setViewing( null ), [] );

	const deleteSession = useCallback(
		async ( session ) => {
			setDeletingId( session.session_id );
			try {
				await apiFetch( {
					path: '/dctc-ai/v1/delete-session',
					method: 'POST',
					data: { session_id: session.session_id },
				} );
				setSessions( ( prev ) =>
					prev.filter( ( s ) => s.session_id !== session.session_id )
				);
				if ( viewing?.session_id === session.session_id ) {
					closeView();
				}
				showNotice?.(
					__( 'Session deleted.', 'dragwyb-click-to-chat' ),
					'success'
				);
			} catch {
				showNotice?.(
					__(
						'Could not delete this session. Please try again.',
						'dragwyb-click-to-chat'
					),
					'error'
				);
			} finally {
				setDeletingId( null );
			}
		},
		[ viewing, closeView, showNotice ]
	);

	const exportCsv = useCallback( () => {
		if ( ! filtered.length ) {
			showNotice?.(
				__( 'No sessions to export.', 'dragwyb-click-to-chat' ),
				'error'
			);
			return;
		}
		const cell = ( v ) => `"${ String( v ?? '' ).replace( /"/g, '""' ) }"`;
		const rows = filtered.map( ( s ) =>
			[
				sessionEmail( s ),
				s.provider || '',
				s.model || '',
				lastMessagePreview( s ).replace( /^"|"$/g, '' ),
				s.created_at || '',
				s.updated_at || '',
			]
				.map( cell )
				.join( ',' )
		);
		const csv = [
			[ 'Email', 'Provider', 'Model', 'Last Message', 'Created', 'Updated' ]
				.map( cell )
				.join( ',' ),
			...rows,
		].join( '\n' );
		const blob = new Blob( [ csv ], { type: 'text/csv;charset=utf-8;' } );
		const url = URL.createObjectURL( blob );
		const a = document.createElement( 'a' );
		a.href = url;
		a.download = `dctc-ai-chat-sessions-${ new Date()
			.toISOString()
			.slice( 0, 10 ) }.csv`;
		a.click();
		URL.revokeObjectURL( url );
	}, [ filtered, showNotice ] );

	const printTranscript = useCallback( () => {
		if ( ! viewing ) {
			return;
		}
		const email = sessionEmail( viewing );
		const meta = sessionMeta( viewing );
		const msgs = parseMessages( viewing.content );
		const win = window.open( '', '_blank', 'width=720,height=900' );
		if ( ! win ) {
			showNotice?.(
				__(
					'Allow pop-ups to download or print the transcript.',
					'dragwyb-click-to-chat'
				),
				'error'
			);
			return;
		}
		win.document.write( `<!DOCTYPE html><html><head><title>${ __(
			'Session Transcript',
			'dragwyb-click-to-chat'
		) }</title>
			<style>
				body { font-family: system-ui, sans-serif; padding: 2rem; color: #111827; line-height: 1.5; }
				h1 { font-size: 1.25rem; margin-bottom: 0.25rem; }
				.meta { color: #6b7280; font-size: 0.875rem; margin-bottom: 2rem; }
				.msg { margin-bottom: 1.25rem; }
				.role { font-weight: 600; font-size: 0.8125rem; text-transform: uppercase; letter-spacing: 0.04em; color: #6366f1; }
				.time { color: #9ca3af; font-size: 0.75rem; font-weight: 400; margin-left: 0.5rem; }
				.body { margin-top: 0.35rem; white-space: pre-wrap; }
			</style></head><body>
			<h1>${ __( 'Session Transcript', 'dragwyb-click-to-chat' ) }</h1>
			<p class="meta">${ escapeHtml( email ) }</p>
			<p class="meta"><strong>${ __( 'Provider', 'dragwyb-click-to-chat' ) }:</strong> ${ escapeHtml(
			meta.provider
		) } · <strong>${ __( 'Model', 'dragwyb-click-to-chat' ) }:</strong> ${ escapeHtml(
			meta.model
		) }</p>
			${ msgs
				.map(
					( m ) =>
						`<div class="msg"><div class="role">${ escapeHtml(
							m.role === 'user'
								? email
								: __( 'Assistant', 'dragwyb-click-to-chat' )
						) }<span class="time">${ formatDate(
							m.created_at
						) }</span></div><div class="body">${ escapeHtml(
							m.content
						) }</div></div>`
				)
				.join( '' ) }
			</body></html>` );
		win.document.close();
		win.focus();
		win.print();
	}, [ viewing, showNotice ] );

	const pages = pageNumbers( Math.min( page, totalPages ), totalPages );
	const rangeStart = filtered.length
		? ( Math.min( page, totalPages ) - 1 ) * perPage + 1
		: 0;
	const rangeEnd = Math.min(
		Math.min( page, totalPages ) * perPage,
		filtered.length
	);

	return (
		<div className="dctc-ai-sessions">
			<div className="dctc-ai-sessions-toolbar">
				<div className="dctc-ai-sessions-toolbar__left">
					<div className="dctc-ai-sessions-search">
						<span className="dashicons dashicons-search" aria-hidden="true" />
						<input
							type="search"
							className="dctc-ai-sessions-search__input"
							placeholder={ __(
								'Search sessions…',
								'dragwyb-click-to-chat'
							) }
							value={ search }
							onChange={ ( e ) => setSearch( e.target.value ) }
							aria-label={ __( 'Search sessions', 'dragwyb-click-to-chat' ) }
						/>
					</div>

					<div
						ref={ filterRef }
						className={
							'dctc-ai-sessions-filter ' + ( filterOpen ? 'is-open' : '' )
						}
					>
						<button
							type="button"
							className="dctc-ai-sessions-filter__trigger"
							onClick={ () => setFilterOpen( ( v ) => ! v ) }
							aria-expanded={ filterOpen }
							aria-haspopup="listbox"
							aria-label={ __(
								'Filter by provider',
								'dragwyb-click-to-chat'
							) }
						>
							<span
								className="dashicons dashicons-filter"
								aria-hidden="true"
							/>
							<span className="dctc-ai-sessions-filter__label">
								{ filterLabel }
							</span>
							<span
								className={
									'dashicons dashicons-arrow-down-alt2 dctc-ai-sessions-filter__chevron ' +
									( filterOpen ? 'is-open' : '' )
								}
								aria-hidden="true"
							/>
						</button>
						{ filterOpen && (
							<ul
								className="dctc-ai-sessions-filter__menu"
								role="listbox"
								aria-label={ __( 'Providers', 'dragwyb-click-to-chat' ) }
							>
								{ providerOptions.map( ( opt ) => (
									<li key={ opt.value } role="presentation">
										<button
											type="button"
											role="option"
											aria-selected={ providerFilter === opt.value }
											className={
												'dctc-ai-sessions-filter__option ' +
												( providerFilter === opt.value
													? 'is-selected'
													: '' )
											}
											onClick={ () => {
												setProviderFilter( opt.value );
												setFilterOpen( false );
											} }
										>
											{ opt.label }
										</button>
									</li>
								) ) }
							</ul>
						) }
					</div>

					<div className="dctc-ai-sessions-limit">
						<span className="dashicons dashicons-database" aria-hidden="true" />
						<span className="dctc-ai-sessions-limit__label">
							{ __( 'Limit:', 'dragwyb-click-to-chat' ) }
						</span>
						<input
							type="text"
							pattern="[0-9]*"
							inputMode="numeric"
							className="dctc-ai-sessions-limit__input"
							placeholder={ __( 'All', 'dragwyb-click-to-chat' ) }
							value={ loadLimitDraft === 'all' ? '' : loadLimitDraft }
							onChange={ ( e ) => setLoadLimitDraft( e.target.value ) }
							onBlur={ () => commitLimit( loadLimitDraft ) }
							onKeyDown={ ( e ) => {
								if ( e.key === 'Enter' ) {
									e.preventDefault();
									commitLimit( loadLimitDraft );
									e.target.blur();
								}
							} }
							title={ __(
								'Sessions load limit (empty for all)',
								'dragwyb-click-to-chat'
							) }
							aria-label={ __(
								'Sessions load limit',
								'dragwyb-click-to-chat'
							) }
						/>
					</div>

					<div className="dctc-ai-sessions-switcher">
						<button
							type="button"
							className={
								'dctc-ai-sessions-switcher__btn ' +
								( sortOrder === 'desc' ? 'is-active' : '' )
							}
							onClick={ () => setSortOrder( 'desc' ) }
							aria-label={ __(
								'Sort by newest first',
								'dragwyb-click-to-chat'
							) }
						>
							{ __( 'Newest', 'dragwyb-click-to-chat' ) }
						</button>
						<button
							type="button"
							className={
								'dctc-ai-sessions-switcher__btn ' +
								( sortOrder === 'asc' ? 'is-active' : '' )
							}
							onClick={ () => setSortOrder( 'asc' ) }
							aria-label={ __(
								'Sort by oldest first',
								'dragwyb-click-to-chat'
							) }
						>
							{ __( 'Oldest', 'dragwyb-click-to-chat' ) }
						</button>
					</div>
				</div>

				<div className="dctc-ai-sessions-toolbar__right">
					<button
						type="button"
						className="dctc-ai-sessions-export"
						onClick={ exportCsv }
					>
						{ __( 'Export CSV', 'dragwyb-click-to-chat' ) }
					</button>
				</div>
			</div>

			{ loading ? (
				<div className="dctc-ai-sessions-empty">
					<span
						className="dctc-ai-spinner dctc-ai-spinner--muted"
						aria-hidden="true"
					/>
					<h3>{ __( 'Loading sessions…', 'dragwyb-click-to-chat' ) }</h3>
				</div>
			) : sessions.length === 0 ? (
				<div className="dctc-ai-sessions-empty">
					<span
						className="dashicons dashicons-format-chat"
						aria-hidden="true"
					/>
					<h3>{ __( 'No sessions yet', 'dragwyb-click-to-chat' ) }</h3>
					<p>
						{ __(
							'Conversations will show up here once visitors start chatting with your bot.',
							'dragwyb-click-to-chat'
						) }
					</p>
				</div>
			) : filtered.length === 0 ? (
				<div className="dctc-ai-sessions-empty dctc-ai-sessions-empty--compact">
					<span className="dashicons dashicons-search" aria-hidden="true" />
					<h3>{ __( 'No matching sessions', 'dragwyb-click-to-chat' ) }</h3>
					<p>
						{ __(
							'Try a different search term or filter.',
							'dragwyb-click-to-chat'
						) }
					</p>
				</div>
			) : (
				<>
					<div className="dctc-ai-sessions-table-wrap">
						<table className="dctc-ai-sessions-table">
							<thead>
								<tr>
									<th>{ __( 'Email', 'dragwyb-click-to-chat' ) }</th>
									<th>{ __( 'Provider', 'dragwyb-click-to-chat' ) }</th>
									<th>{ __( 'Created', 'dragwyb-click-to-chat' ) }</th>
									<th>{ __( 'Last message', 'dragwyb-click-to-chat' ) }</th>
									<th className="dctc-ai-sessions-table__actions-col">
										{ __( 'Actions', 'dragwyb-click-to-chat' ) }
									</th>
								</tr>
							</thead>
							<tbody>
								{ pageRows.map( ( session ) => {
									const email = sessionEmail( session );
									const preview = lastMessagePreview( session );
									const busy = deletingId === session.session_id;
									return (
										<tr key={ session.session_id }>
											<td>
												<span className="dctc-ai-sessions-user__name">
													{ email }
												</span>
											</td>
											<td className="dctc-ai-sessions-provider">
												{ session.provider ? (
													<span className="dctc-ai-sessions-provider__badge">
														{ formatProviderLabel( session.provider ) }
													</span>
												) : (
													<span className="dctc-ai-sessions-last-msg--empty">
														{ __( 'Unknown', 'dragwyb-click-to-chat' ) }
													</span>
												) }
											</td>
											<td className="dctc-ai-sessions-date">
												{ session.created_at ? (
													<span>{ formatDate( session.created_at ) }</span>
												) : (
													<span className="dctc-ai-sessions-last-msg--empty">
														{ __( 'Unknown', 'dragwyb-click-to-chat' ) }
													</span>
												) }
											</td>
											<td className="dctc-ai-sessions-last-msg">
												{ preview || (
													<span className="dctc-ai-sessions-last-msg--empty">
														{ __(
															'No messages',
															'dragwyb-click-to-chat'
														) }
													</span>
												) }
											</td>
											<td className="dctc-ai-sessions-table__actions">
												<button
													type="button"
													className="dctc-ai-sessions-icon-btn"
													onClick={ () => setViewing( session ) }
													title={ __(
														'View session',
														'dragwyb-click-to-chat'
													) }
													aria-label={ __(
														'View session',
														'dragwyb-click-to-chat'
													) }
												>
													<span
														className="dashicons dashicons-visibility"
														aria-hidden="true"
													/>
												</button>
												<button
													type="button"
													className="dctc-ai-sessions-icon-btn dctc-ai-sessions-icon-btn--danger"
													onClick={ ( e ) => {
														e.stopPropagation();
														setConfirmDelete( session );
													} }
													disabled={ busy }
													title={ __(
														'Delete session',
														'dragwyb-click-to-chat'
													) }
													aria-label={ __(
														'Delete session',
														'dragwyb-click-to-chat'
													) }
												>
													<span
														className="dashicons dashicons-trash"
														aria-hidden="true"
													/>
												</button>
											</td>
										</tr>
									);
								} ) }
							</tbody>
						</table>
					</div>

					<footer className="dctc-ai-sessions-footer">
						<p className="dctc-ai-sessions-footer__count">
							{ filtered.length
								? sprintf(
										/* translators: 1: start index, 2: end index, 3: total count */
										__(
											'Showing %1$d–%2$d of %3$d sessions',
											'dragwyb-click-to-chat'
										),
										rangeStart,
										rangeEnd,
										filtered.length
								  )
								: __( 'No sessions', 'dragwyb-click-to-chat' ) }
						</p>
						<div className="dctc-ai-sessions-footer__right">
							<div className="dctc-ai-sessions-per-page-input-wrap">
								<span className="dctc-ai-sessions-per-page-input__label">
									{ __( 'Rows per page:', 'dragwyb-click-to-chat' ) }
								</span>
								<input
									type="text"
									pattern="[0-9]*"
									inputMode="numeric"
									className="dctc-ai-sessions-per-page-input__field"
									value={ perPageDraft }
									onChange={ ( e ) => setPerPageDraft( e.target.value ) }
									onBlur={ () => commitPerPage( perPageDraft ) }
									onKeyDown={ ( e ) => {
										if ( e.key === 'Enter' ) {
											e.preventDefault();
											commitPerPage( perPageDraft );
											e.target.blur();
										}
									} }
									aria-label={ __(
										'Sessions per page',
										'dragwyb-click-to-chat'
									) }
								/>
							</div>
							{ totalPages > 1 && (
								<nav
									className="dctc-ai-sessions-pagination"
									aria-label={ __(
										'Sessions pagination',
										'dragwyb-click-to-chat'
									) }
								>
									<button
										type="button"
										className="dctc-ai-sessions-page-btn"
										disabled={ page <= 1 }
										onClick={ () =>
											setPage( ( p ) => Math.max( 1, p - 1 ) )
										}
										aria-label={ __(
											'Previous page',
											'dragwyb-click-to-chat'
										) }
									>
										<span
											className="dashicons dashicons-arrow-left-alt2"
											aria-hidden="true"
										/>
									</button>
									{ pages.map( ( n, i ) =>
										n === '…' ? (
											<span
												key={ `ellipsis-${ i }` }
												className="dctc-ai-sessions-page-ellipsis"
											>
												…
											</span>
										) : (
											<button
												key={ n }
												type="button"
												className={
													'dctc-ai-sessions-page-num ' +
													( page === n ? 'is-active' : '' )
												}
												onClick={ () => setPage( n ) }
												aria-current={ page === n ? 'page' : undefined }
											>
												{ n }
											</button>
										)
									) }
									<button
										type="button"
										className="dctc-ai-sessions-page-btn"
										disabled={ page >= totalPages }
										onClick={ () =>
											setPage( ( p ) => Math.min( totalPages, p + 1 ) )
										}
										aria-label={ __(
											'Next page',
											'dragwyb-click-to-chat'
										) }
									>
										<span
											className="dashicons dashicons-arrow-right-alt2"
											aria-hidden="true"
										/>
									</button>
								</nav>
							) }
						</div>
					</footer>
				</>
			) }

			{ viewing && (
				<div
					className={
						'dctc-ai-modal dctc-ai-sessions-modal ' +
						( modalVisible ? 'is-visible' : '' )
					}
					role="dialog"
					aria-modal="true"
					aria-labelledby="dctc-ai-session-modal-title"
				>
					<div className="dctc-ai-modal-overlay" onClick={ closeView } />
					<div className="dctc-ai-modal-content">
						<div className="dctc-ai-modal-header">
							<div className="dctc-ai-modal-title">
								<span
									className="dctc-ai-modal-title-icon"
									aria-hidden="true"
								>
									<span className="dashicons dashicons-format-chat" />
								</span>
								<h3 id="dctc-ai-session-modal-title">
									{ __( 'Session Transcript', 'dragwyb-click-to-chat' ) }
								</h3>
							</div>
							<button
								type="button"
								className="dctc-ai-modal-close"
								onClick={ closeView }
								aria-label={ __( 'Close', 'dragwyb-click-to-chat' ) }
							>
								<span
									className="dashicons dashicons-no-alt"
									aria-hidden="true"
								/>
							</button>
						</div>
						{ ( () => {
							const meta = sessionMeta( viewing );
							return (
								<div className="dctc-ai-sessions-modal-meta">
									<div className="dctc-ai-sessions-modal-meta__item">
										<span className="dctc-ai-sessions-modal-meta__label">
											{ __( 'Provider', 'dragwyb-click-to-chat' ) }
										</span>
										<span className="dctc-ai-sessions-modal-meta__value">
											{ meta.provider }
										</span>
									</div>
									<div className="dctc-ai-sessions-modal-meta__item">
										<span className="dctc-ai-sessions-modal-meta__label">
											{ __( 'Model', 'dragwyb-click-to-chat' ) }
										</span>
										<span className="dctc-ai-sessions-modal-meta__value">
											{ meta.model }
										</span>
									</div>
								</div>
							);
						} )() }
						<div className="dctc-ai-modal-body">
							{ ( () => {
								const msgs = parseMessages( viewing.content );
								return msgs.length ? (
									msgs.map( ( msg, i ) => (
										<div
											key={ i }
											className={
												'dctc-ai-modal-msg ' +
												( msg.role === 'user'
													? 'dctc-ai-modal-msg-user'
													: 'dctc-ai-modal-msg-assistant' )
											}
										>
											<div className="dctc-ai-modal-msg-content">
												{ msg.content }
											</div>
											{ msg.created_at && (
												<div className="dctc-ai-modal-msg-time">
													{ formatDate( msg.created_at ) }
												</div>
											) }
										</div>
									) )
								) : (
									<p className="dctc-ai-text-muted">
										{ __(
											'No messages in this session.',
											'dragwyb-click-to-chat'
										) }
									</p>
								);
							} )() }
						</div>
						<div className="dctc-ai-modal-footer">
							<button
								type="button"
								className="dctc-ai-modal-footer-link"
								onClick={ printTranscript }
							>
								{ __( 'Print Chat Transcript', 'dragwyb-click-to-chat' ) }
							</button>
							<button
								type="button"
								className="dctc-ai-btn dctc-ai-btn-primary"
								onClick={ closeView }
							>
								{ __( 'Return to Sessions', 'dragwyb-click-to-chat' ) }
							</button>
						</div>
					</div>
				</div>
			) }

			<ConfirmModal
				open={ !! confirmDelete }
				title={ __( 'Delete chat session', 'dragwyb-click-to-chat' ) }
				message={ __(
					'Delete this chat session permanently? This cannot be undone.',
					'dragwyb-click-to-chat'
				) }
				confirmLabel={ __( 'Delete Session', 'dragwyb-click-to-chat' ) }
				busy={
					!! deletingId && confirmDelete?.session_id === deletingId
				}
				onCancel={ () => setConfirmDelete( null ) }
				onConfirm={ () => {
					const session = confirmDelete;
					setConfirmDelete( null );
					deleteSession( session );
				} }
			/>
		</div>
	);
}
