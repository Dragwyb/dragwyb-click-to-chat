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
		widget_size: display.widget_size ?? 64,
		widget_size_unit: display.widget_size_unit || 'px',
		custom_vertical_align: display.custom_vertical_align || 'bottom',
		custom_vertical: display.custom_vertical ?? 24,
		custom_vertical_unit: display.custom_vertical_unit || 'px',
		custom_side: display.custom_side || 'right',
		custom_horizontal: display.custom_horizontal ?? 24,
		custom_horizontal_unit: display.custom_horizontal_unit || 'px',
		launcher_text: display.launcher_text || '',
		assistant_icon: display.assistant_icon || '',
		trigger_type: display.trigger_type || 'click',
		trigger_delay: display.trigger_delay ?? 5,
		time_delay: display.time_delay ?? 0,
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

	const openAssistantIconMedia = () => {
		if ( ! window.wp?.media ) {
			showNotice(
				__( 'WordPress media modal is not available.', 'dragwyb-click-to-chat' ),
				'error'
			);
			return;
		}
		const frame = window.wp.media( {
			title: __( 'Select Assistant Icon', 'dragwyb-click-to-chat' ),
			button: { text: __( 'Use as Icon', 'dragwyb-click-to-chat' ) },
			library: { type: 'image' },
			multiple: false,
		} );
		frame.on( 'select', () => {
			const attachment = frame.state().get( 'selection' ).first().toJSON();
			setField( 'assistant_icon', attachment.url );
		} );
		frame.open();
	};

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

				<section className="dctc-ai-display-panel">
					<header className="dctc-ai-display-panel__header">
						<h3 className="dctc-ai-display-panel__title">
							{ __( 'Visibility & Trigger', 'dragwyb-click-to-chat' ) }
						</h3>
						<p className="dctc-ai-display-panel__desc">
							{ __(
								'Control the button label, pages to skip, and when the chat opens.',
								'dragwyb-click-to-chat'
							) }
						</p>
					</header>
					<div className="dctc-ai-display-panel__body">
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
									onChange={ ( e ) =>
										setField( 'launcher_text', e.target.value )
									}
									placeholder={ __(
										'How can we help?',
										'dragwyb-click-to-chat'
									) }
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

					<div className="dctc-ai-bot-field dctc-ai-display-trigger-field">
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
								onChange={ ( e ) =>
									setField( 'trigger_delay', e.target.value )
								}
							/>
						</div>
					) }

					<div className="dctc-ai-bot-field dctc-ai-display-time-delay-field">
						<label htmlFor="time_delay">
							{ __( 'Time Delay', 'dragwyb-click-to-chat' ) }
						</label>
						<p className="dctc-ai-bot-hint">
							{ __(
								'Delay widget appearance after page load (in seconds)',
								'dragwyb-click-to-chat'
							) }
						</p>
						<div className="dctc-ai-widget-size-row dctc-ai-time-delay-row">
							<input
								type="number"
								id="time_delay"
								className="dctc-ai-bot-input dctc-ai-widget-size-input"
								min="0"
								max="60"
								step="1"
								value={ form.time_delay }
								onChange={ ( e ) =>
									setField(
										'time_delay',
										e.target.value === ''
											? ''
											: Number( e.target.value )
									)
								}
							/>
							<span className="dctc-ai-time-delay-unit">
								{ __( 'seconds', 'dragwyb-click-to-chat' ) }
							</span>
						</div>
						<p className="dctc-ai-bot-hint">
							{ __(
								'Set to 0 for immediate display. Recommended: 2–5 seconds for better user experience.',
								'dragwyb-click-to-chat'
							) }
						</p>
					</div>
						</div>
					</div>
				</section>

				<section className="dctc-ai-display-panel">
					<header className="dctc-ai-display-panel__header">
						<h3 className="dctc-ai-display-panel__title">
							{ __( 'Widget Appearance', 'dragwyb-click-to-chat' ) }
						</h3>
						<p className="dctc-ai-display-panel__desc">
							{ __(
								'Choose where the AI chat button sits and how large it appears.',
								'dragwyb-click-to-chat'
							) }
						</p>
					</header>
					<div className="dctc-ai-display-panel__body">
					<div className="dctc-ai-bot-field dctc-ai-display-position-field">
						<label>
							{ __( 'Widget Position on Screen', 'dragwyb-click-to-chat' ) }
						</label>
						<p className="dctc-ai-bot-hint">
							{ __(
								'Choose where the AI chat widget appears on your website',
								'dragwyb-click-to-chat'
							) }
						</p>
						<div className="dctc-ai-position-selector" role="radiogroup">
							{ [
								{
									value: 'bottom-left',
									label: __( 'Bottom Left', 'dragwyb-click-to-chat' ),
									kind: 'corner',
									cx: 10,
								},
								{
									value: 'bottom-right',
									label: __( 'Bottom Right', 'dragwyb-click-to-chat' ),
									kind: 'corner',
									cx: 50,
								},
								{
									value: 'custom',
									label: __( 'Custom', 'dragwyb-click-to-chat' ),
									kind: 'custom',
								},
							].map( ( opt ) => (
								<label
									key={ opt.value }
									className={
										'dctc-ai-position-option' +
										( form.position === opt.value ? ' is-active' : '' )
									}
								>
									<input
										type="radio"
										name="dctc_ai_widget_position"
										value={ opt.value }
										checked={ form.position === opt.value }
										onChange={ () => setField( 'position', opt.value ) }
									/>
									<span className="dctc-ai-position-card">
										<svg
											width="60"
											height="40"
											viewBox="0 0 60 40"
											fill="none"
											aria-hidden="true"
										>
											<rect
												width="60"
												height="40"
												rx="4"
												fill="#F3F4F6"
											/>
											{ opt.kind === 'corner' ? (
												<circle
													cx={ opt.cx }
													cy="30"
													r="5"
													fill="currentColor"
												/>
											) : (
												<>
													<path
														d="M25 15 L35 15 L30 10 Z"
														fill="currentColor"
													/>
													<path
														d="M25 25 L35 25 L30 30 Z"
														fill="currentColor"
													/>
													<path
														d="M15 20 L20 25 L20 15 Z"
														fill="currentColor"
													/>
													<path
														d="M40 20 L35 25 L35 15 Z"
														fill="currentColor"
													/>
												</>
											) }
										</svg>
										<span>{ opt.label }</span>
									</span>
								</label>
							) ) }
						</div>

						{ form.position === 'custom' && (
							<div className="dctc-ai-custom-position">
								<h4 className="dctc-ai-custom-position__title">
									{ __(
										'Custom Position Settings',
										'dragwyb-click-to-chat'
									) }
								</h4>
								<div className="dctc-ai-custom-position__grid">
									<div className="dctc-ai-custom-position__col">
										<label htmlFor="custom_vertical_align">
											{ __(
												'Vertical Alignment',
												'dragwyb-click-to-chat'
											) }
										</label>
										<select
											id="custom_vertical_align"
											className="dctc-ai-bot-select"
											value={ form.custom_vertical_align }
											onChange={ ( e ) =>
												setField(
													'custom_vertical_align',
													e.target.value
												)
											}
										>
											<option value="bottom">
												{ __( 'Bottom', 'dragwyb-click-to-chat' ) }
											</option>
											<option value="top">
												{ __( 'Top', 'dragwyb-click-to-chat' ) }
											</option>
										</select>
										<label
											htmlFor="custom_vertical"
											className="dctc-ai-custom-position__sublabel"
										>
											{ __(
												'Vertical Distance',
												'dragwyb-click-to-chat'
											) }
										</label>
										<div className="dctc-ai-widget-size-row">
											<input
												type="number"
												id="custom_vertical"
												className="dctc-ai-bot-input dctc-ai-widget-size-input"
												min="0"
												step="1"
												value={ form.custom_vertical }
												onChange={ ( e ) =>
													setField(
														'custom_vertical',
														e.target.value === ''
															? ''
															: Number( e.target.value )
													)
												}
											/>
											<select
												className="dctc-ai-bot-select dctc-ai-widget-size-unit"
												value={ form.custom_vertical_unit }
												onChange={ ( e ) =>
													setField(
														'custom_vertical_unit',
														e.target.value
													)
												}
												aria-label={ __(
													'Vertical distance unit',
													'dragwyb-click-to-chat'
												) }
											>
												<option value="px">px</option>
												<option value="rem">rem</option>
												<option value="em">em</option>
												<option value="%">%</option>
											</select>
										</div>
									</div>
									<div className="dctc-ai-custom-position__col">
										<label htmlFor="custom_side">
											{ __(
												'Horizontal Alignment',
												'dragwyb-click-to-chat'
											) }
										</label>
										<select
											id="custom_side"
											className="dctc-ai-bot-select"
											value={ form.custom_side }
											onChange={ ( e ) =>
												setField( 'custom_side', e.target.value )
											}
										>
											<option value="right">
												{ __( 'Right', 'dragwyb-click-to-chat' ) }
											</option>
											<option value="left">
												{ __( 'Left', 'dragwyb-click-to-chat' ) }
											</option>
										</select>
										<label
											htmlFor="custom_horizontal"
											className="dctc-ai-custom-position__sublabel"
										>
											{ __(
												'Horizontal Distance',
												'dragwyb-click-to-chat'
											) }
										</label>
										<div className="dctc-ai-widget-size-row">
											<input
												type="number"
												id="custom_horizontal"
												className="dctc-ai-bot-input dctc-ai-widget-size-input"
												min="0"
												step="1"
												value={ form.custom_horizontal }
												onChange={ ( e ) =>
													setField(
														'custom_horizontal',
														e.target.value === ''
															? ''
															: Number( e.target.value )
													)
												}
											/>
											<select
												className="dctc-ai-bot-select dctc-ai-widget-size-unit"
												value={ form.custom_horizontal_unit }
												onChange={ ( e ) =>
													setField(
														'custom_horizontal_unit',
														e.target.value
													)
												}
												aria-label={ __(
													'Horizontal distance unit',
													'dragwyb-click-to-chat'
												) }
											>
												<option value="px">px</option>
												<option value="rem">rem</option>
												<option value="em">em</option>
												<option value="%">%</option>
											</select>
										</div>
									</div>
								</div>
								<p className="dctc-ai-bot-hint">
									{ __(
										'Tip: Use custom positioning to place the widget exactly where you want it on your page.',
										'dragwyb-click-to-chat'
									) }
								</p>
							</div>
						) }
					</div>

					<div className="dctc-ai-bot-field dctc-ai-display-size-field">
						<label htmlFor="widget_size">
							{ __( 'Widget Size', 'dragwyb-click-to-chat' ) }
						</label>
						<p className="dctc-ai-bot-hint">
							{ __(
								'Adjust the size of the AI chat button',
								'dragwyb-click-to-chat'
							) }
						</p>
						<div className="dctc-ai-widget-size-row">
							<input
								type="number"
								id="widget_size"
								className="dctc-ai-bot-input dctc-ai-widget-size-input"
								min="24"
								max="120"
								step="1"
								value={ form.widget_size }
								onChange={ ( e ) =>
									setField(
										'widget_size',
										e.target.value === ''
											? ''
											: Number( e.target.value )
									)
								}
							/>
							<select
								id="widget_size_unit"
								className="dctc-ai-bot-select dctc-ai-widget-size-unit"
								value={ form.widget_size_unit }
								onChange={ ( e ) =>
									setField( 'widget_size_unit', e.target.value )
								}
								aria-label={ __(
									'Widget size unit',
									'dragwyb-click-to-chat'
								) }
							>
								<option value="px">px</option>
								<option value="rem">rem</option>
								<option value="em">em</option>
							</select>
						</div>
					</div>

					<div className="dctc-ai-bot-field dctc-ai-assistant-icon-field">
						<label>
							{ __( 'Assistant Icon', 'dragwyb-click-to-chat' ) }
						</label>
						<p className="dctc-ai-bot-hint">
							{ __(
								'Choose an icon for the floating AI chat button. Upload SVG, PNG, JPG, or WebP.',
								'dragwyb-click-to-chat'
							) }
						</p>
						<div className="dctc-ai-assistant-icon-row">
							<div
								className={
									'dctc-ai-assistant-icon-preview' +
									( form.assistant_icon
										? ' dctc-ai-assistant-icon-preview--custom'
										: '' )
								}
								aria-hidden="true"
							>
								{ form.assistant_icon ? (
									<img src={ form.assistant_icon } alt="" />
								) : (
									<span className="dashicons dashicons-format-chat" />
								) }
							</div>
							<div className="dctc-ai-assistant-icon-actions">
								<button
									type="button"
									className="dctc-ai-btn dctc-ai-btn-secondary"
									onClick={ openAssistantIconMedia }
								>
									<span
										className="dashicons dashicons-upload"
										aria-hidden="true"
									/>
									{ form.assistant_icon
										? __( 'Change Icon', 'dragwyb-click-to-chat' )
										: __( 'Upload Icon', 'dragwyb-click-to-chat' ) }
								</button>
								{ !! form.assistant_icon && (
									<button
										type="button"
										className="dctc-ai-btn dctc-ai-btn-secondary"
										onClick={ () => setField( 'assistant_icon', '' ) }
									>
										{ __( 'Reset to Default', 'dragwyb-click-to-chat' ) }
									</button>
								) }
								{ form.assistant_icon ? (
									<span className="dctc-ai-assistant-icon-filename">
										{ form.assistant_icon.split( '/' ).pop() }
									</span>
								) : (
									<span className="dctc-ai-assistant-icon-filename">
										{ __( 'Default chat icon', 'dragwyb-click-to-chat' ) }
									</span>
								) }
							</div>
						</div>
					</div>
					</div>
				</section>

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
