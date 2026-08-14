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

function providerLabel( provider ) {
	if ( ! provider ) {
		return '';
	}
	const map = {
		openai: 'OpenAI',
		google: 'Google Gemini',
		anthropic: 'Anthropic',
		openrouter: 'OpenRouter',
		deepseek: 'DeepSeek',
		mistral: 'Mistral',
		groq: 'Groq',
	};
	return map[ provider.toLowerCase() ] || provider;
}

export default function ErrorLogs( { showNotice } ) {
	const [ logs, setLogs ] = useState( [] );
	const [ totalCount, setTotalCount ] = useState( 0 );
	const [ isLoggingEnabled, setIsLoggingEnabled ] = useState( true );
	const [ loading, setLoading ] = useState( true );
	const [ clearing, setClearing ] = useState( false );
	const [ selectedLog, setSelectedLog ] = useState( null );

	const loadLogs = useCallback( async () => {
		setLoading( true );
		try {
			const res = await apiFetch( {
				path: '/dctc-ai/v1/error-logs?limit=100',
			} );
			setLogs( Array.isArray( res?.logs ) ? res.logs : [] );
			setTotalCount(
				typeof res?.total === 'number'
					? res.total
					: res?.logs?.length || 0
			);
			if ( typeof res?.enabled === 'boolean' ) {
				setIsLoggingEnabled( res.enabled );
			}
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

	const clearAllLogs = async () => {
		if (
			! window.confirm(
				__( 'Clear all error logs from the database?', 'dragwyb-click-to-chat' )
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
			setTotalCount( 0 );
			showNotice?.(
				__( 'All error logs have been cleared from database.', 'dragwyb-click-to-chat' ),
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

	const deleteSingleLog = async ( id, e ) => {
		e?.stopPropagation();
		try {
			await apiFetch( {
				path: `/dctc-ai/v1/error-logs?id=${ id }`,
				method: 'DELETE',
			} );
			setLogs( ( prev ) => prev.filter( ( item ) => item.id !== id ) );
			setTotalCount( ( prev ) => Math.max( 0, prev - 1 ) );
			if ( selectedLog?.id === id ) {
				setSelectedLog( null );
			}
			showNotice?.(
				__( 'Log entry deleted.', 'dragwyb-click-to-chat' ),
				'success'
			);
		} catch {
			showNotice?.(
				__( 'Failed to delete log entry.', 'dragwyb-click-to-chat' ),
				'error'
			);
		}
	};

	return (
		<div className="dctc-ai-error-logs">
			{ ! isLoggingEnabled && (
				<div
					className="dctc-ai-notice dctc-ai-notice-info"
					style={ {
						display: 'flex',
						alignItems: 'center',
						gap: '0.625rem',
						padding: '0.75rem 1rem',
						borderRadius: '8px',
						background: '#f8fafc',
						border: '1px solid #e2e8f0',
						color: '#64748b',
						fontSize: '0.875rem',
						marginBottom: '1rem',
					} }
				>
					<span
						className="dashicons dashicons-info"
						style={ { color: '#6366f1' } }
						aria-hidden="true"
					/>
					<span>
						{ __(
							'Error logging is currently disabled. You can enable it in Chatbot > Advance settings.',
							'dragwyb-click-to-chat'
						) }
					</span>
				</div>
			) }

			<div className="dctc-ai-error-logs-toolbar">
				<div className="dctc-ai-error-logs-toolbar__left">
					<span className="dashicons dashicons-warning" aria-hidden="true" />
					<span>
						{ logs.length
							? `${ totalCount || logs.length } ${ __(
									'logged errors',
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
						onClick={ clearAllLogs }
						disabled={ loading || clearing || logs.length === 0 }
					>
						<span className="dashicons dashicons-trash" aria-hidden="true" />
						{ clearing
							? __( 'Clearing...', 'dragwyb-click-to-chat' )
							: __( 'Clear All Logs', 'dragwyb-click-to-chat' ) }
					</button>
				</div>
			</div>

			{ loading ? (
				<div className="dctc-ai-error-logs-empty">
					<span
						className="dctc-ai-spinner dctc-ai-spinner--muted"
						aria-hidden="true"
					/>
					<h3>{ __( 'Loading error logs from database...', 'dragwyb-click-to-chat' ) }</h3>
				</div>
			) : logs.length === 0 ? (
				<div className="dctc-ai-error-logs-empty">
					<span className="dashicons dashicons-yes-alt" aria-hidden="true" />
					<h3>{ __( 'No errors logged yet', 'dragwyb-click-to-chat' ) }</h3>
					<p>{ __( 'Any AI provider, model, or plugin errors will be captured and recorded here.', 'dragwyb-click-to-chat' ) }</p>
				</div>
			) : (
				<div className="dctc-ai-error-logs-table-wrap">
					<table className="dctc-ai-error-logs-table">
						<thead>
							<tr>
								<th>{ __( 'Time', 'dragwyb-click-to-chat' ) }</th>
								<th>{ __( 'Provider', 'dragwyb-click-to-chat' ) }</th>
								<th>{ __( 'Model', 'dragwyb-click-to-chat' ) }</th>
								<th>{ __( 'User Message', 'dragwyb-click-to-chat' ) }</th>
								<th>{ __( 'Model Error', 'dragwyb-click-to-chat' ) }</th>
								<th>{ __( 'Type / Location', 'dragwyb-click-to-chat' ) }</th>
								<th className="dctc-ai-error-logs-actions-col">{ __( 'Actions', 'dragwyb-click-to-chat' ) }</th>
							</tr>
						</thead>
						<tbody>
							{ logs.map( ( log, index ) => {
								const location = logLocation( log );
								const context = logContext( log );
								return (
									<tr
										key={ log.id || index }
										onClick={ () => setSelectedLog( log ) }
										className="dctc-ai-error-logs-row"
									>
										<td className="dctc-ai-error-logs-date">
											{ formatDate( log.created_at ) }
										</td>
										<td className="dctc-ai-error-logs-provider">
											{ log.provider ? (
												<span className="dctc-ai-provider-badge">
													{ providerLabel( log.provider ) }
												</span>
											) : (
												<span className="dctc-ai-error-logs-muted">—</span>
											) }
										</td>
										<td className="dctc-ai-error-logs-model">
											{ log.model ? (
												<code>{ log.model }</code>
											) : (
												<span className="dctc-ai-error-logs-muted">—</span>
											) }
										</td>
										<td className="dctc-ai-error-logs-user-msg">
											{ log.user_message ? (
												<span
													className="dctc-ai-error-logs-prompt"
													title={ log.user_message }
												>
													{ log.user_message }
												</span>
											) : (
												<span className="dctc-ai-error-logs-muted">—</span>
											) }
										</td>
										<td className="dctc-ai-error-logs-message">
											<div className="dctc-ai-error-logs-error-text">
												{ log.model_error || log.message || __( 'No message recorded.', 'dragwyb-click-to-chat' ) }
											</div>
										</td>
										<td className="dctc-ai-error-logs-details">
											<span className="dctc-ai-error-logs-badge">
												{ log.error_type || log.type || __( 'Error', 'dragwyb-click-to-chat' ) }
											</span>
											{ location && (
												<div className="dctc-ai-error-logs-location">
													{ location }
												</div>
											) }
											{ context && (
												<div className="dctc-ai-error-logs-context">
													{ context }
												</div>
											) }
										</td>
										<td className="dctc-ai-error-logs-actions-col">
											<button
												type="button"
												className="dctc-ai-btn-icon dctc-ai-btn-icon-danger"
												title={ __( 'Delete log', 'dragwyb-click-to-chat' ) }
												aria-label={ __( 'Delete this log entry', 'dragwyb-click-to-chat' ) }
												onClick={ ( e ) => deleteSingleLog( log.id, e ) }
											>
												<span className="dashicons dashicons-trash" />
											</button>
										</td>
									</tr>
								);
							} ) }
						</tbody>
					</table>
				</div>
			) }

			{ selectedLog && (
				<div
					className="dctc-ai-modal-overlay"
					role="dialog"
					aria-modal="true"
					onClick={ () => setSelectedLog( null ) }
				>
					<div
						className="dctc-ai-modal-content dctc-ai-error-log-modal"
						onClick={ ( e ) => e.stopPropagation() }
					>
						<div className="dctc-ai-modal-header">
							<div className="dctc-ai-modal-title">
								<span className="dashicons dashicons-warning" aria-hidden="true" />
								<h3>{ __( 'Error Log Details', 'dragwyb-click-to-chat' ) }</h3>
							</div>
							<button
								type="button"
								className="dctc-ai-modal-close"
								onClick={ () => setSelectedLog( null ) }
								aria-label={ __( 'Close', 'dragwyb-click-to-chat' ) }
							>
								✕
							</button>
						</div>

						<div className="dctc-ai-modal-body">
							<div className="dctc-ai-error-detail-grid">
								<div className="dctc-ai-error-detail-item">
									<label>{ __( 'Timestamp', 'dragwyb-click-to-chat' ) }</label>
									<div>{ formatDate( selectedLog.created_at ) } ({ selectedLog.created_at })</div>
								</div>
								<div className="dctc-ai-error-detail-item">
									<label>{ __( 'Error Type', 'dragwyb-click-to-chat' ) }</label>
									<div>
										<span className="dctc-ai-error-logs-badge">
											{ selectedLog.error_type || selectedLog.type }
										</span>
									</div>
								</div>
								{ selectedLog.provider && (
									<div className="dctc-ai-error-detail-item">
										<label>{ __( 'Provider', 'dragwyb-click-to-chat' ) }</label>
										<div>{ providerLabel( selectedLog.provider ) }</div>
									</div>
								) }
								{ selectedLog.model && (
									<div className="dctc-ai-error-detail-item">
										<label>{ __( 'Model', 'dragwyb-click-to-chat' ) }</label>
										<div><code>{ selectedLog.model }</code></div>
									</div>
								) }
								{ selectedLog.user_message && (
									<div className="dctc-ai-error-detail-item dctc-ai-error-detail-full">
										<label>{ __( 'User Message to Bot', 'dragwyb-click-to-chat' ) }</label>
										<div className="dctc-ai-error-detail-code">
											{ selectedLog.user_message }
										</div>
									</div>
								) }
								<div className="dctc-ai-error-detail-item dctc-ai-error-detail-full">
									<label>{ __( 'Model / Error Message', 'dragwyb-click-to-chat' ) }</label>
									<div className="dctc-ai-error-detail-error">
										{ selectedLog.model_error || selectedLog.message }
									</div>
								</div>
								{ logLocation( selectedLog ) && (
									<div className="dctc-ai-error-detail-item dctc-ai-error-detail-full">
										<label>{ __( 'File Location', 'dragwyb-click-to-chat' ) }</label>
										<div><code>{ logLocation( selectedLog ) }</code></div>
									</div>
								) }
								{ logContext( selectedLog ) && (
									<div className="dctc-ai-error-detail-item dctc-ai-error-detail-full">
										<label>{ __( 'Context / Code', 'dragwyb-click-to-chat' ) }</label>
										<div>{ logContext( selectedLog ) }</div>
									</div>
								) }
							</div>
						</div>

						<div className="dctc-ai-modal-footer">
							<button
								type="button"
								className="dctc-ai-btn dctc-ai-btn-danger dctc-ai-btn-sm"
								onClick={ () => deleteSingleLog( selectedLog.id ) }
							>
								<span className="dashicons dashicons-trash" />
								{ __( 'Delete Entry', 'dragwyb-click-to-chat' ) }
							</button>
							<button
								type="button"
								className="dctc-ai-btn dctc-ai-btn-secondary dctc-ai-btn-sm"
								onClick={ () => setSelectedLog( null ) }
							>
								{ __( 'Close', 'dragwyb-click-to-chat' ) }
							</button>
						</div>
					</div>
				</div>
			) }
		</div>
	);
}
