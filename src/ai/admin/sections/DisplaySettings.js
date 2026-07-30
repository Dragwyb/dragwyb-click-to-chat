/**
 * Display / visibility settings for the frontend widget.
 */
import { useState, useEffect, useRef } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import Toggle from '../components/Toggle';

function SettingCard( { id, title, desc, checked, onChange, icon = null } ) {
	return (
		<div className="dctc-ai-bot-setting-card">
			<div className="dctc-ai-bot-setting-card__main">
				{ icon }
				<div className="dctc-ai-bot-setting-card__text">
					<strong>{ title }</strong>
					<span>{ desc }</span>
				</div>
			</div>
			<Toggle id={ id } checked={ checked } onChange={ onChange } />
		</div>
	);
}

export default function DisplaySettings( { settings, onSave, showNotice } ) {
	const [ saving, setSaving ] = useState( false );
	const [ content, setContent ] = useState( [] );
	const [ loadingContent, setLoadingContent ] = useState( false );
	const [ search, setSearch ] = useState( '' );
	const [ open, setOpen ] = useState( false );
	const comboRef = useRef( null );
	const display = settings?.display || {};

	const buildForm = () => ( {
		entire_site: !! display.entire_site,
		show_on_mobile: display.show_on_mobile !== false,
		exclude_pages: display.exclude_pages || '',
		position: display.position || 'bottom-right',
		launcher_text: display.launcher_text || '',
		trigger_type: display.trigger_type || 'click',
		trigger_delay: display.trigger_delay ?? 5,
	} );

	const [ form, setForm ] = useState( buildForm );
	const [ saved, setSaved ] = useState( buildForm );
	const dirty = JSON.stringify( form ) !== JSON.stringify( saved );

	useEffect( () => {
		let alive = true;
		setLoadingContent( true );
		apiFetch( { path: '/dctc-ai/v1/all-content' } )
			.then( ( items ) => {
				if ( alive && Array.isArray( items ) ) {
					setContent( items );
				}
			} )
			.finally( () => {
				if ( alive ) {
					setLoadingContent( false );
				}
			} );
		return () => {
			alive = false;
		};
	}, [] );

	useEffect( () => {
		const onDown = ( e ) => {
			if ( comboRef.current && ! comboRef.current.contains( e.target ) ) {
				setOpen( false );
			}
		};
		document.addEventListener( 'mousedown', onDown );
		return () => document.removeEventListener( 'mousedown', onDown );
	}, [] );

	const setField = ( key, value ) => {
		setForm( ( prev ) => ( { ...prev, [ key ]: value } ) );
	};

	const selectedIds = () =>
		form.exclude_pages
			? form.exclude_pages
					.split( ',' )
					.map( ( s ) => s.trim() )
					.filter( Boolean )
			: [];

	const addExclude = ( raw ) => {
		const id = ( raw || search ).trim();
		if ( ! id ) {
			return;
		}
		const ids = selectedIds();
		if ( ! ids.includes( id ) ) {
			setField( 'exclude_pages', [ ...ids, id ].join( ', ' ) );
		}
		setSearch( '' );
		setOpen( false );
	};

	const toggleExclude = ( id ) => {
		const ids = selectedIds();
		const next = ids.includes( id )
			? ids.filter( ( x ) => x !== id )
			: [ ...ids, id ];
		setField( 'exclude_pages', next.join( ', ' ) );
	};

	const ids = selectedIds();
	const filtered = content.filter( ( item ) => {
		if ( ! search.trim() ) {
			return true;
		}
		const q = search.toLowerCase();
		return (
			item.title.toLowerCase().includes( q ) ||
			String( item.id ).includes( q ) ||
			item.type.toLowerCase().includes( q )
		);
	} );

	const onSubmit = async ( e ) => {
		e.preventDefault();
		setSaving( true );
		try {
			await apiFetch( {
				path: '/dctc-ai/v1/save-display-settings',
				method: 'POST',
				data: form,
			} );
			onSave( { display: { ...display, ...form } } );
			setSaved( form );
			showNotice(
				__( 'Display settings saved successfully!', 'dragwyb-click-to-chat' )
			);
		} catch ( err ) {
			showNotice(
				err.message || __( 'Failed to save settings', 'dragwyb-click-to-chat' ),
				'error'
			);
		} finally {
			setSaving( false );
		}
	};

	return (
		<div className="dctc-ai-display-settings">
			<form onSubmit={ onSubmit }>
				<SettingCard
					id="entire_site"
					title={ __( 'Enable Site-wide Chatbot', 'dragwyb-click-to-chat' ) }
					desc={ __(
						'Activate the chatbot across all pages of your website',
						'dragwyb-click-to-chat'
					) }
					checked={ form.entire_site }
					onChange={ ( v ) => setField( 'entire_site', v ) }
				/>

				<div className="dctc-ai-display-grid">
					<div className="dctc-ai-bot-field">
						<label htmlFor="launcher_text">
							{ __( 'Chat Button Text', 'dragwyb-click-to-chat' ) }
						</label>
						<input
							type="text"
							id="launcher_text"
							className="dctc-ai-bot-input"
							value={ form.launcher_text }
							onChange={ ( e ) => setField( 'launcher_text', e.target.value ) }
							placeholder={ __( 'How can we help?', 'dragwyb-click-to-chat' ) }
						/>
					</div>

					<div className="dctc-ai-bot-field">
						<label htmlFor="exclude_search_input">
							{ __(
								'Exclude Content (Pages, Posts, Custom Types)',
								'dragwyb-click-to-chat'
							) }
						</label>
						<div
							ref={ comboRef }
							className="dctc-ai-combobox-wrapper"
							style={ { position: 'relative', width: '100%' } }
						>
							<div
								className="dctc-ai-combobox-input-wrap"
								style={ { position: 'relative' } }
							>
								<input
									type="text"
									id="exclude_search_input"
									className="dctc-ai-bot-input"
									value={ search }
									onChange={ ( e ) => {
										setSearch( e.target.value );
										setOpen( true );
									} }
									onFocus={ () => setOpen( true ) }
									onKeyDown={ ( e ) => {
										if ( e.key === 'Enter' ) {
											e.preventDefault();
											addExclude();
										}
									} }
									placeholder={
										loadingContent
											? __( 'Loading content…', 'dragwyb-click-to-chat' )
											: __(
													'Type title or ID to search…',
													'dragwyb-click-to-chat'
											  )
									}
									style={ { paddingRight: '36px' } }
								/>
								<span
									style={ {
										position: 'absolute',
										right: '12px',
										top: '50%',
										transform: 'translateY(-50%)',
										pointerEvents: 'none',
										color: '#94a3b8',
										fontSize: '12px',
									} }
								>
									▼
								</span>
							</div>

							{ open && (
								<div
									className="dctc-ai-combobox-dropdown"
									style={ {
										position: 'absolute',
										top: 'calc(100% + 4px)',
										left: 0,
										right: 0,
										maxHeight: '220px',
										overflowY: 'auto',
										background: '#ffffff',
										border: '1px solid #cbd5e1',
										borderRadius: '8px',
										boxShadow:
											'0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.05)',
										zIndex: 999,
										padding: '4px 0',
									} }
								>
									{ filtered.length === 0 ? (
										<div
											style={ {
												padding: '10px 14px',
												fontSize: '13px',
												color: '#64748b',
												textAlign: 'center',
											} }
										>
											{ search.trim() ? (
												<div>
													<div>
														{ __(
															'No matching page/post found.',
															'dragwyb-click-to-chat'
														) }
													</div>
													<button
														type="button"
														onClick={ () => addExclude() }
														style={ {
															marginTop: '6px',
															background: '#4f46e5',
															color: '#fff',
															border: 'none',
															borderRadius: '6px',
															padding: '4px 10px',
															fontSize: '12px',
															cursor: 'pointer',
														} }
													>
														{ sprintf(
															/* translators: %s: custom ID */
															__(
																'Add "%s" as custom ID',
																'dragwyb-click-to-chat'
															),
															search.trim()
														) }
													</button>
												</div>
											) : (
												__(
													'No pages or posts available.',
													'dragwyb-click-to-chat'
												)
											) }
										</div>
									) : (
										filtered.map( ( item ) => {
											const idStr = String( item.id );
											const selected = ids.includes( idStr );
											return (
												<div
													key={ item.id }
													onClick={ () => toggleExclude( idStr ) }
													style={ {
														display: 'flex',
														alignItems: 'center',
														justifyContent: 'space-between',
														padding: '8px 14px',
														fontSize: '13px',
														cursor: 'pointer',
														background: selected
															? '#f0f9ff'
															: 'transparent',
														borderBottom: '1px solid #f1f5f9',
													} }
												>
													<div
														style={ {
															display: 'flex',
															alignItems: 'center',
															gap: '8px',
															flex: 1,
														} }
													>
														<span
															style={ {
																fontSize: '11px',
																fontWeight: '600',
																textTransform: 'uppercase',
																padding: '2px 6px',
																borderRadius: '4px',
																background:
																	item.type === 'Page'
																		? '#e0f2fe'
																		: '#fef3c7',
																color:
																	item.type === 'Page'
																		? '#0369a1'
																		: '#b45309',
															} }
														>
															{ item.type }
														</span>
														<span
															style={ {
																fontWeight: 500,
																color: '#1e293b',
															} }
														>
															{ item.title }
														</span>
														<span
															style={ {
																fontSize: '11px',
																color: '#94a3b8',
															} }
														>
															(ID: { item.id })
														</span>
													</div>
													{ selected && (
														<span
															style={ {
																color: '#0284c7',
																fontWeight: 'bold',
																fontSize: '14px',
															} }
														>
															✓
														</span>
													) }
												</div>
											);
										} )
									) }
								</div>
							) }

							<div
								className="dctc-ai-selected-tokens"
								style={ {
									display: 'flex',
									flexWrap: 'wrap',
									gap: '6px',
									marginTop: '8px',
									minHeight: '24px',
								} }
							>
								{ ids.length === 0 ? (
									<span
										style={ {
											fontSize: '12px',
											color: '#94a3b8',
											fontStyle: 'italic',
										} }
									>
										{ __(
											'No content currently excluded.',
											'dragwyb-click-to-chat'
										) }
									</span>
								) : (
									ids.map( ( id ) => {
										const item = content.find(
											( c ) => String( c.id ) === id
										);
										const label = item
											? `[${ item.type }] ${ item.title }`
											: sprintf(
													/* translators: %s: post ID */
													__( 'ID %s', 'dragwyb-click-to-chat' ),
													id
											  );
										return (
											<span
												key={ id }
												className="dctc-ai-badge"
												style={ {
													display: 'inline-flex',
													alignItems: 'center',
													gap: '6px',
													padding: '4px 10px',
													borderRadius: '6px',
													background: '#eff6ff',
													border: '1px solid #bfdbfe',
													color: '#1d4ed8',
													fontSize: '12px',
													fontWeight: '500',
												} }
											>
												{ label }
												<button
													type="button"
													onClick={ () => {
														const next = selectedIds().filter(
															( x ) => x !== id
														);
														setField(
															'exclude_pages',
															next.join( ', ' )
														);
													} }
													style={ {
														border: 'none',
														background: 'transparent',
														cursor: 'pointer',
														padding: 0,
														lineHeight: 1,
														color: '#3b82f6',
														fontWeight: 'bold',
														fontSize: '12px',
													} }
													aria-label={ __(
														'Remove',
														'dragwyb-click-to-chat'
													) }
												>
													✕
												</button>
											</span>
										);
									} )
								) }
							</div>
						</div>
					</div>

					<div className="dctc-ai-bot-field">
						<label htmlFor="trigger_type">
							{ __( 'Auto-Open Trigger', 'dragwyb-click-to-chat' ) }
						</label>
						<select
							id="trigger_type"
							className="dctc-ai-bot-select"
							value={ form.trigger_type }
							onChange={ ( e ) => setField( 'trigger_type', e.target.value ) }
						>
							<option value="delay">
								{ __( 'On Page Load', 'dragwyb-click-to-chat' ) }
							</option>
							<option value="click">
								{ __( 'Wait for Click', 'dragwyb-click-to-chat' ) }
							</option>
						</select>
					</div>

					<div className="dctc-ai-bot-field">
						<label htmlFor="position">
							{ __( 'Widget Position on Screen', 'dragwyb-click-to-chat' ) }
						</label>
						<select
							id="position"
							className="dctc-ai-bot-select"
							value={ form.position }
							onChange={ ( e ) => setField( 'position', e.target.value ) }
						>
							<option value="bottom-right">
								{ __( 'Bottom Right', 'dragwyb-click-to-chat' ) }
							</option>
							<option value="bottom-left">
								{ __( 'Bottom Left', 'dragwyb-click-to-chat' ) }
							</option>
						</select>
					</div>
				</div>

				{ form.trigger_type === 'delay' && (
					<div className="dctc-ai-bot-field dctc-ai-display-delay-field">
						<label htmlFor="trigger_delay">
							{ __( 'Delay (seconds)', 'dragwyb-click-to-chat' ) }
						</label>
						<input
							type="number"
							id="trigger_delay"
							className="dctc-ai-bot-input"
							min="1"
							max="60"
							value={ form.trigger_delay }
							onChange={ ( e ) => setField( 'trigger_delay', e.target.value ) }
						/>
					</div>
				) }

				<SettingCard
					id="show_on_mobile"
					title={ __( 'Show on Mobile', 'dragwyb-click-to-chat' ) }
					desc={ __(
						'Optimize interface for mobile devices',
						'dragwyb-click-to-chat'
					) }
					checked={ form.show_on_mobile }
					onChange={ ( v ) => setField( 'show_on_mobile', v ) }
					icon={
						<span className="dctc-ai-display-mobile-icon" aria-hidden="true">
							<span className="dashicons dashicons-smartphone" />
						</span>
					}
				/>

				<footer className="dctc-ai-display-footer">
					<button
						type="submit"
						className="dctc-ai-btn dctc-ai-btn-primary"
						disabled={ saving || ! dirty }
					>
						{ saving
							? __( 'Saving…', 'dragwyb-click-to-chat' )
							: __( 'Save', 'dragwyb-click-to-chat' ) }
					</button>
				</footer>
			</form>
		</div>
	);
}
