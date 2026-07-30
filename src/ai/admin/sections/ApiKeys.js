/**
 * API Keys + default provider + model selection.
 */
import { useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import ConfirmModal from '../components/ConfirmModal';
import Toggle from '../components/Toggle';
import { PROVIDERS } from '../utils/providers';

export default function ApiKeys( { settings, onSave, showNotice } ) {
	const [ saving, setSaving ] = useState( false );
	const [ resetting, setResetting ] = useState( {} );
	const [ confirmProvider, setConfirmProvider ] = useState( null );
	const [ modelsList, setModelsList ] = useState(
		() => window.dctc_ai_data?.models_list || {}
	);

	const buildForm = () => ( {
		openai_key: '',
		google_key: '',
		models: settings?.models || {},
		default_provider: settings?.chatbot?.default_provider || 'openai',
	} );

	const [ form, setForm ] = useState( buildForm );
	const [ saved, setSaved ] = useState( buildForm );
	const dirty = JSON.stringify( form ) !== JSON.stringify( saved );

	const hasKey = ( id ) => !! settings.api_keys?.[ id ];
	const maskedKey = ( id ) => settings.api_keys?.[ id ] || '';

	const onSubmit = async ( e ) => {
		e.preventDefault();
		setSaving( true );
		try {
			const res = await apiFetch( {
				path: '/dctc-ai/v1/save-settings',
				method: 'POST',
				data: form,
			} );
			showNotice(
				res.message ||
					__( 'Settings saved successfully!', 'dragwyb-click-to-chat' ),
				'success'
			);
			onSave( {
				api_keys: res.api_keys || {},
				chatbot: res.chatbot || {},
				models: res.models || {},
			} );
			if ( res.models_list ) {
				setModelsList( res.models_list );
			}
			setForm( ( prev ) => {
				const next = { ...prev };
				Object.keys( PROVIDERS ).forEach( ( id ) => {
					next[ `${ id }_key` ] = '';
				} );
				if ( res.chatbot?.default_provider ) {
					next.default_provider = res.chatbot.default_provider;
				}
				setSaved( next );
				return next;
			} );
		} catch ( err ) {
			let msg = err.message;
			if ( err.errors && typeof err.errors === 'object' ) {
				msg = Object.values( err.errors ).join( ' | ' );
			}
			showNotice(
				msg || __( 'Failed to save settings', 'dragwyb-click-to-chat' ),
				'error'
			);
		} finally {
			setSaving( false );
		}
	};

	const resetKey = async ( provider ) => {
		setResetting( ( prev ) => ( { ...prev, [ provider ]: true } ) );
		try {
			const res = await apiFetch( {
				path: '/dctc-ai/v1/reset-key',
				method: 'POST',
				data: { provider },
			} );
			showNotice(
				__( 'Key reset successfully!', 'dragwyb-click-to-chat' ),
				'success'
			);
			const apiKeys = { ...settings?.api_keys };
			delete apiKeys[ provider ];
			const models = { ...settings?.models };
			delete models[ provider ];
			onSave( {
				api_keys: apiKeys,
				models,
				chatbot: res.chatbot || settings?.chatbot,
			} );
			setForm( ( prev ) => {
				const next = {
					...prev,
					[ `${ provider }_key` ]: '',
					default_provider: res.chatbot?.default_provider || '',
					models: { ...prev.models, [ provider ]: '' },
				};
				setSaved( next );
				return next;
			} );
		} catch ( err ) {
			showNotice(
				err.message || __( 'Failed to reset key', 'dragwyb-click-to-chat' ),
				'error'
			);
		} finally {
			setResetting( ( prev ) => ( { ...prev, [ provider ]: false } ) );
		}
	};

	return (
		<div className="dctc-ai-api-keys">
			<form onSubmit={ onSubmit }>
				{ Object.entries( PROVIDERS ).map( ( [ id, meta ] ) => (
					<article key={ id } className="dctc-ai-api-provider-card">
						<header
							className="dctc-ai-api-provider-card__header"
							style={ {
								display: 'flex',
								justifyContent: 'space-between',
								alignItems: 'center',
							} }
						>
							<h3>{ meta.name }</h3>
							{ hasKey( id ) && (
								<div
									className="dctc-ai-api-provider-toggle"
									style={ {
										display: 'flex',
										alignItems: 'center',
										gap: '8px',
									} }
								>
									<span
										style={ {
											fontSize: '12px',
											fontWeight: '500',
											color:
												form.default_provider === id
													? 'var(--dctc-ai-success, #10b981)'
													: '#6b7280',
										} }
									>
										{ form.default_provider === id
											? __( 'Active', 'dragwyb-click-to-chat' )
											: __( 'Inactive', 'dragwyb-click-to-chat' ) }
									</span>
									<Toggle
										id={ `toggle_${ id }` }
										checked={ form.default_provider === id }
										onChange={ ( checked ) => {
											if ( checked ) {
												setForm( ( prev ) => ( {
													...prev,
													default_provider: id,
												} ) );
											} else {
												const other = Object.keys( PROVIDERS ).find(
													( p ) => p !== id && hasKey( p )
												);
												setForm( ( prev ) => ( {
													...prev,
													default_provider: other || '',
												} ) );
											}
										} }
									/>
								</div>
							) }
						</header>

						<div className="dctc-ai-api-provider-card__body">
							<div className="dctc-ai-api-field">
								<div className="dctc-ai-api-field__label-row">
									<label htmlFor={ `${ id }_key` }>
										{ __( 'API Key', 'dragwyb-click-to-chat' ) }
									</label>
									{ hasKey( id ) && (
										<button
											type="button"
											className="dctc-ai-api-btn-reset"
											onClick={ () => setConfirmProvider( id ) }
											disabled={ resetting[ id ] }
										>
											{ resetting[ id ] ? (
												<span
													className="dctc-ai-spinner"
													aria-hidden="true"
												/>
											) : (
												__( 'Reset Key', 'dragwyb-click-to-chat' )
											) }
										</button>
									) }
								</div>
								<input
									type="text"
									id={ `${ id }_key` }
									className={
										'dctc-ai-api-input' +
										( hasKey( id ) ? ' dctc-ai-api-input--masked' : '' )
									}
									value={
										hasKey( id ) ? maskedKey( id ) : form[ `${ id }_key` ]
									}
									onChange={ ( e ) =>
										setForm( ( prev ) => ( {
											...prev,
											[ `${ id }_key` ]: e.target.value,
										} ) )
									}
									placeholder={ sprintf(
										/* translators: %s: provider name */
										__( 'Enter your %s API key', 'dragwyb-click-to-chat' ),
										meta.name
									) }
									disabled={ hasKey( id ) }
								/>
								<a
									href={ meta.link }
									className="dctc-ai-api-help-link"
									target="_blank"
									rel="noopener noreferrer"
								>
									{ sprintf(
										/* translators: %s: provider name */
										__(
											'Generate your %s API key here',
											'dragwyb-click-to-chat'
										),
										meta.name
									) }
									<span
										className="dashicons dashicons-external"
										aria-hidden="true"
									/>
								</a>
							</div>

							{ hasKey( id ) &&
								modelsList[ id ] &&
								Object.keys( modelsList[ id ] ).length > 0 && (
									<div className="dctc-ai-api-field">
										<label htmlFor={ `models_${ id }` }>
											{ __( 'Select Model', 'dragwyb-click-to-chat' ) }
										</label>
										<select
											id={ `models_${ id }` }
											className="dctc-ai-api-select"
											value={ form.models[ id ] || '' }
											onChange={ ( e ) =>
												setForm( ( prev ) => ( {
													...prev,
													models: {
														...prev.models,
														[ id ]: e.target.value,
													},
												} ) )
											}
										>
											{ Object.entries( modelsList[ id ] ).map(
												( [ modelId, label ] ) => (
													<option key={ modelId } value={ modelId }>
														{ label }
													</option>
												)
											) }
										</select>
									</div>
								) }
						</div>
					</article>
				) ) }

				<footer className="dctc-ai-api-keys-footer">
					<button
						type="submit"
						className="dctc-ai-btn dctc-ai-btn-primary"
						disabled={ saving || ! dirty }
					>
						{ saving ? (
							<>
								<span className="dctc-ai-spinner" aria-hidden="true" />{ ' ' }
								{ __( 'Saving…', 'dragwyb-click-to-chat' ) }
							</>
						) : (
							__( 'Save', 'dragwyb-click-to-chat' )
						) }
					</button>
				</footer>
			</form>

			<ConfirmModal
				open={ !! confirmProvider }
				title={ __( 'Reset API key', 'dragwyb-click-to-chat' ) }
				message={ sprintf(
					/* translators: %s: provider name */
					__(
						'Are you sure you want to reset the %s API key? This cannot be undone.',
						'dragwyb-click-to-chat'
					),
					PROVIDERS[ confirmProvider ]?.name || ''
				) }
				confirmLabel={ __( 'Reset Key', 'dragwyb-click-to-chat' ) }
				busy={ resetting[ confirmProvider ] }
				onCancel={ () => setConfirmProvider( null ) }
				onConfirm={ () => {
					const provider = confirmProvider;
					setConfirmProvider( null );
					resetKey( provider );
				} }
			/>
		</div>
	);
}
