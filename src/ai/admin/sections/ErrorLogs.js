/**
 * Error log table for plugin and AI REST errors.
 */
import { useCallback, useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

function formatDate( value ) {
	if ( ! value ) {
		return __( 'Unknown', 'dragwyb-click-to-chat' );
	}

	const iso = String( value ).includes( 'T' )
		? value
		: String( value ).replace( ' ', 'T' );
	const date = new Date( iso );

	if ( Number.isNaN( date.getTime() ) ) {
		return value;
	}

	return `${ date.toLocaleDateString( undefined, {
		month: 'short',
		day: 'numeric',
		year: 'numeric',
	} ) } ${ date.toLocaleTimeString( undefined, {
		hour: 'numeric',
		minute: '2-digit',
	} ) }`;
}

function logLocation( log ) {
	if ( ! log?.file ) {
		return '';
	}

	return log.line ? `${ log.file }:${ log.line }` : log.file;
}

function logContext( log ) {
	const parts = [];
	if ( log?.code ) {
		parts.push(
			`${ __( 'Code', 'dragwyb-click-to-chat' ) }: ${ log.code }`
		);
	}
	if ( log?.context ) {
		parts.push( log.context );
	}
	return parts.join( ' | ' );
}

export default function ErrorLogs( { showNotice } ) {
	const [ logs, setLogs ] = useState( [] );
	const [ loading, setLoading ] = useState( true );
	const [ clearing, setClearing ] = useState( false );

	const loadLogs = useCallback( async () => {
		setLoading( true );
		try {
			const res = await apiFetch( {
				path: '/dctc-ai/v1/error-logs?limit=50',
			} );
			setLogs( Array.isArray( res?.logs ) ? res.logs : [] );
		} catch {
			setLogs( [] );
			showNotice?.(
				__(
					'Could not load error logs. Please refresh the page.',
					'dragwyb-click-to-chat'
				),
				'error'
			);
		} finally {
			setLoading( false );
		}
	}, [ showNotice ] );

	useEffect( () => {
		loadLogs();
	}, [ loadLogs ] );

	const clearLogs = async () => {
		if (
			! window.confirm(
				__( 'Clear all error logs?', 'dragwyb-click-to-chat' )
			)
		) {
			return;
		}

		setClearing( true );
		try {
			await apiFetch( {
				path: '/dctc-ai/v1/error-logs',
				method: 'DELETE',
			} );
			setLogs( [] );
			showNotice?.(
				__( 'Error logs cleared.', 'dragwyb-click-to-chat' ),
				'success'
			);
		} catch {
			showNotice?.(
				__( 'Could not clear error logs.', 'dragwyb-click-to-chat' ),
				'error'
			);
		} finally {
			setClearing( false );
		}
	};

	return (
		<div className="dctc-ai-error-logs">
			<div className="dctc-ai-error-logs-toolbar">
				<div className="dctc-ai-error-logs-toolbar__left">
					<span className="dashicons dashicons-warning" aria-hidden="true" />
					<span>
						{ logs.length
							? `${ logs.length } ${ __(
									'recent errors',
									'dragwyb-click-to-chat'
							  ) }`
							: __( 'No errors logged', 'dragwyb-click-to-chat' ) }
					</span>
				</div>
				<div className="dctc-ai-error-logs-toolbar__right">
					<button
						type="button"
						className="dctc-ai-btn dctc-ai-btn-secondary dctc-ai-btn-sm"
						onClick={ loadLogs }
						disabled={ loading || clearing }
					>
						<span className="dashicons dashicons-update" aria-hidden="true" />
						{ __( 'Refresh', 'dragwyb-click-to-chat' ) }
					</button>
					<button
						type="button"
						className="dctc-ai-btn dctc-ai-btn-danger dctc-ai-btn-sm"
						onClick={ clearLogs }
						disabled={ loading || clearing || logs.length === 0 }
					>
						<span className="dashicons dashicons-trash" aria-hidden="true" />
						{ clearing
							? __( 'Clearing...', 'dragwyb-click-to-chat' )
							: __( 'Clear Logs', 'dragwyb-click-to-chat' ) }
					</button>
				</div>
			</div>

			{ loading ? (
				<div className="dctc-ai-error-logs-empty">
					<span
						className="dctc-ai-spinner dctc-ai-spinner--muted"
						aria-hidden="true"
					/>
					<h3>{ __( 'Loading error logs...', 'dragwyb-click-to-chat' ) }</h3>
				</div>
			) : logs.length === 0 ? (
				<div className="dctc-ai-error-logs-empty">
					<span className="dashicons dashicons-yes-alt" aria-hidden="true" />
					<h3>{ __( 'No errors logged yet', 'dragwyb-click-to-chat' ) }</h3>
				</div>
			) : (
				<div className="dctc-ai-error-logs-table-wrap">
					<table className="dctc-ai-error-logs-table">
						<thead>
							<tr>
								<th>{ __( 'Time', 'dragwyb-click-to-chat' ) }</th>
								<th>{ __( 'Type', 'dragwyb-click-to-chat' ) }</th>
								<th>{ __( 'Message', 'dragwyb-click-to-chat' ) }</th>
								<th>{ __( 'Location', 'dragwyb-click-to-chat' ) }</th>
								<th>{ __( 'Context', 'dragwyb-click-to-chat' ) }</th>
							</tr>
						</thead>
						<tbody>
							{ logs.map( ( log, index ) => (
								<tr key={ log.id || index }>
									<td className="dctc-ai-error-logs-date">
										{ formatDate( log.created_at ) }
									</td>
									<td>
										<span className="dctc-ai-error-logs-badge">
											{ log.type || __( 'Error', 'dragwyb-click-to-chat' ) }
										</span>
									</td>
									<td className="dctc-ai-error-logs-message">
										{ log.message ||
											__( 'No message recorded.', 'dragwyb-click-to-chat' ) }
									</td>
									<td className="dctc-ai-error-logs-location">
										{ logLocation( log ) || '-' }
									</td>
									<td className="dctc-ai-error-logs-context">
										{ logContext( log ) || '-' }
									</td>
								</tr>
							) ) }
						</tbody>
					</table>
				</div>
			) }
		</div>
	);
}
