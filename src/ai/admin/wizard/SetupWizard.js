/**
 * First-run multi-step setup wizard.
 * Steps: provider → vector DB → [pinecone] → content types → knowledge → done
 */
import { useState, useEffect } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { PROVIDERS, VECTOR_DB_OPTIONS, resolveEmbeddingProvider } from '../utils/providers';

async function setWizardStatus( status ) {
	try {
		await apiFetch( {
			path: '/dctc-ai/v1/setup-wizard',
			method: 'POST',
			data: { status },
		} );
	} catch ( e ) {
		// Non-fatal — wizard UI still closes.
	}
}

export default function SetupWizard( {
	open,
	settings,
	onSave,
	onClose,
	showNotice,
} ) {
	const [ step, setStep ] = useState( 1 );
	const [ busy, setBusy ] = useState( false );

	const defaultProvider = () =>
		settings?.chatbot?.default_provider
			? settings.chatbot.default_provider
			: settings?.api_keys?.openai
			? 'openai'
			: settings?.api_keys?.google
			? 'google'
			: 'openai';

	const [ provider, setProvider ] = useState( defaultProvider );
	const keyAlreadySet = !! settings?.api_keys?.[ provider ];
	const [ apiKey, setApiKey ] = useState(
		() => settings?.api_keys?.[ defaultProvider() ] || ''
	);
	const [ vectorDb, setVectorDb ] = useState(
		() => settings?.rag?.vector_db?.provider || 'sqlite'
	);
	const [ embeddingProvider, setEmbeddingProvider ] = useState( () =>
		resolveEmbeddingProvider( settings )
	);
	const [ pineconeKey, setPineconeKey ] = useState(
		() => settings?.rag?.vector_db?.api_key || ''
	);
	const [ pineconeHost, setPineconeHost ] = useState(
		() => settings?.rag?.vector_db?.host || ''
	);
	const [ pineconeIndex, setPineconeIndex ] = useState(
		() => settings?.rag?.vector_db?.index_name || ''
	);
	const [ knowledgeText, setKnowledgeText ] = useState( '' );
	const [ postTypes, setPostTypes ] = useState( [] );
	const [ selectedTypes, setSelectedTypes ] = useState(
		() => settings?.rag?.post_types || [ 'post', 'page' ]
	);

	useEffect( () => {
		if ( ! open ) {
			return;
		}
		document.body.classList.add( 'dctc-ai-modal-open' );
		const p = defaultProvider();
		setProvider( p );
		setApiKey( settings?.api_keys?.[ p ] || '' );
		setVectorDb( settings?.rag?.vector_db?.provider || 'sqlite' );
		setEmbeddingProvider( resolveEmbeddingProvider( settings ) );
		setPineconeKey( settings?.rag?.vector_db?.api_key || '' );
		setPineconeHost( settings?.rag?.vector_db?.host || '' );
		setPineconeIndex( settings?.rag?.vector_db?.index_name || '' );
		setSelectedTypes( settings?.rag?.post_types || [ 'post', 'page' ] );
		apiFetch( { path: '/dctc-ai/v1/rag/post-types' } )
			.then( ( res ) => setPostTypes( res.types || [] ) )
			.catch( () => {} );
		return () => document.body.classList.remove( 'dctc-ai-modal-open' );
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ open ] );

	if ( ! open ) {
		return null;
	}

	const isPinecone = vectorDb === 'pinecone';
	const totalSteps = isPinecone ? 5 : 4;
	const stepLabels = isPinecone
		? [
				__( 'Connect an AI provider', 'dragwyb-click-to-chat' ),
				__( 'Choose vector database', 'dragwyb-click-to-chat' ),
				__( 'Pinecone settings', 'dragwyb-click-to-chat' ),
				__( 'Content for Your Chatbot', 'dragwyb-click-to-chat' ),
				__( 'Add chatbot knowledge', 'dragwyb-click-to-chat' ),
		  ]
		: [
				__( 'Connect an AI provider', 'dragwyb-click-to-chat' ),
				__( 'Choose vector database', 'dragwyb-click-to-chat' ),
				__( 'Content for Your Chatbot', 'dragwyb-click-to-chat' ),
				__( 'Add chatbot knowledge', 'dragwyb-click-to-chat' ),
		  ];
	const isFinishStep = isPinecone ? step === 5 : step === 4;
	const hasEmbedKey = !! settings?.api_keys?.[ embeddingProvider ];

	const skipOrClose = async ( status ) => {
		setBusy( true );
		await setWizardStatus( status );
		setBusy( false );
		onClose();
	};

	const saveVectorDb = async () => {
		setBusy( true );
		try {
			const vector_db =
				vectorDb === 'pinecone'
					? {
							provider: 'pinecone',
							api_key: pineconeKey,
							host: pineconeHost,
							index_name: pineconeIndex,
					  }
					: { provider: 'sqlite' };
			const embeddings = { provider: embeddingProvider };
			await apiFetch( {
				path: '/dctc-ai/v1/rag/settings',
				method: 'POST',
				data: { vector_db, embeddings },
			} );
			onSave( {
				rag: {
					...settings?.rag,
					vector_db,
					embeddings: { ...settings?.rag?.embeddings, ...embeddings },
				},
			} );
			setStep( isPinecone ? 4 : 3 );
		} catch ( err ) {
			showNotice(
				err?.message ||
					__(
						'Could not connect to this vector database. Please check your details.',
						'dragwyb-click-to-chat'
					),
				'error'
			);
		} finally {
			setBusy( false );
		}
	};

	const saveContentTypes = async () => {
		setBusy( true );
		try {
			await apiFetch( {
				path: '/dctc-ai/v1/rag/settings',
				method: 'POST',
				data: { post_types: selectedTypes },
			} );
			onSave( { rag: { ...settings?.rag, post_types: selectedTypes } } );
			const res = await apiFetch( {
				path: '/dctc-ai/v1/rag/index',
				method: 'POST',
				data: {
					post_types: selectedTypes,
					index_sources: {
						knowledge_text: false,
						urls: false,
						files: false,
						website: selectedTypes.length > 0,
					},
				},
			} );
			if ( res && res.success === false ) {
				throw new Error(
					res.message ||
						__( 'Embedding generation failed.', 'dragwyb-click-to-chat' )
				);
			}
			setStep( isPinecone ? 5 : 4 );
		} catch ( err ) {
			showNotice(
				err.message ||
					__( 'Could not save post types.', 'dragwyb-click-to-chat' ),
				'error'
			);
		} finally {
			setBusy( false );
		}
	};

	const finishKnowledge = async () => {
		setBusy( true );
		try {
			await apiFetch( {
				path: '/dctc-ai/v1/save-bot-settings',
				method: 'POST',
				data: { knowledge_text: knowledgeText },
			} );
			onSave( {
				chatbot: { ...settings?.chatbot, knowledge_text: knowledgeText },
			} );
			if ( knowledgeText.trim() ) {
				const res = await apiFetch( {
					path: '/dctc-ai/v1/rag/index',
					method: 'POST',
					data: {
						post_types: selectedTypes,
						index_sources: {
							knowledge_text: true,
							urls: false,
							files: false,
							website: false,
						},
					},
				} );
				if ( res && res.success === false ) {
					throw new Error(
						res.message ||
							__( 'Embedding generation failed.', 'dragwyb-click-to-chat' )
					);
				}
			}
			showNotice(
				__(
					'Embedding generation and setup completed successfully!',
					'dragwyb-click-to-chat'
				)
			);
			await setWizardStatus( 'completed' );
			setStep( 'done' );
		} catch ( err ) {
			showNotice(
				err?.message ||
					__(
						'Could not complete the setup process. Please try again.',
						'dragwyb-click-to-chat'
					),
				'error'
			);
		} finally {
			setBusy( false );
		}
	};

	const saveProvider = async () => {
		if ( ! apiKey.trim() ) {
			showNotice(
				__( 'An API key is required to continue.', 'dragwyb-click-to-chat' ),
				'error'
			);
			return;
		}
		setEmbeddingProvider( provider );
		if (
			settings?.api_keys?.[ provider ] &&
			apiKey === settings.api_keys[ provider ] &&
			settings?.chatbot?.default_provider === provider
		) {
			setStep( 2 );
			return;
		}
		setBusy( true );
		try {
			const data = { default_provider: provider };
			if ( apiKey !== settings?.api_keys?.[ provider ] ) {
				data[ `${ provider }_key` ] = apiKey;
			}
			const res = await apiFetch( {
				path: '/dctc-ai/v1/save-settings',
				method: 'POST',
				data,
			} );
			onSave( {
				api_keys: res.api_keys || {},
				chatbot: res.chatbot || {},
			} );
			setStep( 2 );
		} catch ( err ) {
			const msg =
				err?.errors?.[ provider ] ||
				err?.message ||
				__(
					'Could not validate this API key. Please try again.',
					'dragwyb-click-to-chat'
				);
			showNotice( msg, 'error' );
		} finally {
			setBusy( false );
		}
	};

	const onNext = () => {
		if ( step === 1 ) {
			saveProvider();
		} else if ( step === 2 ) {
			if ( isPinecone ) {
				setStep( 3 );
			} else {
				saveVectorDb();
			}
		} else if ( step === 3 ) {
			if ( isPinecone ) {
				saveVectorDb();
			} else {
				saveContentTypes();
			}
		} else if ( step === 4 ) {
			if ( isPinecone ) {
				saveContentTypes();
			} else {
				finishKnowledge();
			}
		} else if ( step === 5 ) {
			finishKnowledge();
		}
	};

	const contentStep = (
		<>
			<h2 className="dctc-ai-wizard-step-title">
				{ __( 'Content for Your Chatbot', 'dragwyb-click-to-chat' ) }
			</h2>
			<p className="dctc-ai-wizard-step-desc">
				{ __(
					'Choose which WordPress content the chatbot can use to answer questions.',
					'dragwyb-click-to-chat'
				) }
			</p>
			<div
				className="dctc-ai-kb-post-types"
				style={ {
					marginTop: '1.5rem',
					display: 'flex',
					flexDirection: 'column',
					gap: '0.75rem',
				} }
			>
				{ postTypes.length > 0 ? (
					postTypes.map( ( type ) => (
						<label
							key={ type.value }
							className="dctc-ai-checkbox"
							style={ {
								display: 'flex',
								alignItems: 'center',
								gap: '0.5rem',
								fontSize: '0.875rem',
								color: '#374151',
								cursor: 'pointer',
							} }
						>
							<input
								type="checkbox"
								checked={ selectedTypes.includes( type.value ) }
								onChange={ () => {
									setSelectedTypes( ( prev ) =>
										prev.includes( type.value )
											? prev.filter( ( v ) => v !== type.value )
											: [ ...prev, type.value ]
									);
								} }
							/>
							<span className="dctc-ai-checkbox__label">
								{ type.label } ({ type.count })
							</span>
						</label>
					) )
				) : (
					<p className="dctc-ai-hint" style={ { fontSize: '0.8125rem', color: '#6b7280' } }>
						{ __( 'Fetching available content types...', 'dragwyb-click-to-chat' ) }
					</p>
				) }
			</div>
		</>
	);

	const knowledgeStep = (
		<>
			<h2 className="dctc-ai-wizard-step-title">
				{ __( 'Add what your bot should know', 'dragwyb-click-to-chat' ) }
			</h2>
			<p className="dctc-ai-wizard-step-desc">
				{ __(
					'Add facts, FAQs, or company information for your chatbot. You can add more content later in the Knowledge Base.',
					'dragwyb-click-to-chat'
				) }
			</p>
			<div className="dctc-ai-bot-field">
				<label htmlFor="wizard_knowledge_text">
					{ __( 'Knowledge Base Text', 'dragwyb-click-to-chat' ) }
				</label>
				<textarea
					id="wizard_knowledge_text"
					className="dctc-ai-bot-input dctc-ai-bot-textarea"
					rows="6"
					value={ knowledgeText }
					onChange={ ( e ) => setKnowledgeText( e.target.value ) }
					placeholder={ __(
						'e.g. We are open Monday-Friday, 9am-5pm. Our return policy is...',
						'dragwyb-click-to-chat'
					) }
				/>
			</div>
		</>
	);

	const pineconeStep = () => {
		const dims = embeddingProvider === 'google' ? 768 : 1536;
		const label =
			embeddingProvider === 'google'
				? __( 'Google Gemini', 'dragwyb-click-to-chat' )
				: __( 'OpenAI', 'dragwyb-click-to-chat' );
		return (
			<>
				<h2 className="dctc-ai-wizard-step-title">
					{ __( 'Pinecone Settings', 'dragwyb-click-to-chat' ) }
				</h2>
				<p className="dctc-ai-wizard-step-desc">
					{ __(
						'Configure your Pinecone connection details below.',
						'dragwyb-click-to-chat'
					) }
				</p>
				<div className="dctc-ai-kb-info-block" style={ { marginBottom: '1.25rem' } }>
					<p style={ { margin: 0 } }>
						<strong>
							{ __(
								'Important Pinecone Index Requirement:',
								'dragwyb-click-to-chat'
							) }
						</strong>{ ' ' }
						{ sprintf(
							/* translators: 1: provider name, 2: dimension count */
							__(
								'Because you are using %1$s, you must create your Pinecone index with exactly %2$d dimensions.',
								'dragwyb-click-to-chat'
							),
							label,
							dims
						) }
					</p>
				</div>
				<div className="dctc-ai-bot-field">
					<label htmlFor="wizard_pinecone_key">
						{ __( 'Pinecone API Key', 'dragwyb-click-to-chat' ) }
					</label>
					<input
						type="password"
						id="wizard_pinecone_key"
						className="dctc-ai-bot-input"
						value={ pineconeKey }
						onChange={ ( e ) => setPineconeKey( e.target.value ) }
						placeholder="pcsk_..."
					/>
				</div>
				<div className="dctc-ai-bot-field">
					<label htmlFor="wizard_pinecone_host">
						{ __( 'Pinecone Host', 'dragwyb-click-to-chat' ) }
					</label>
					<input
						type="text"
						id="wizard_pinecone_host"
						className="dctc-ai-bot-input"
						value={ pineconeHost }
						onChange={ ( e ) => setPineconeHost( e.target.value ) }
						placeholder="https://index-xxxxx.svc.aped-4627-b74a.pinecone.io"
					/>
					<a
						href="https://app.pinecone.io/"
						className="dctc-ai-api-help-link"
						target="_blank"
						rel="noopener noreferrer"
					>
						{ __(
							'Find your Pinecone API details and host URL here',
							'dragwyb-click-to-chat'
						) }
						<span className="dashicons dashicons-external" aria-hidden="true" />
					</a>
				</div>
				<div className="dctc-ai-bot-field">
					<label htmlFor="wizard_pinecone_index">
						{ __( 'Index Name', 'dragwyb-click-to-chat' ) }
					</label>
					<input
						type="text"
						id="wizard_pinecone_index"
						className="dctc-ai-bot-input"
						value={ pineconeIndex }
						onChange={ ( e ) => setPineconeIndex( e.target.value ) }
						placeholder={ __( 'e.g. dctc-ai-index', 'dragwyb-click-to-chat' ) }
					/>
				</div>
			</>
		);
	};

	const nextDisabled =
		busy ||
		( step === 1 && ! apiKey.trim() ) ||
		( step === 2 && ! hasEmbedKey ) ||
		( isPinecone &&
			step === 3 &&
			( ! pineconeKey.trim() || ! pineconeHost.trim() || ! pineconeIndex.trim() ) );

	return (
		<div
			className="dctc-ai-modal dctc-ai-wizard-modal is-visible"
			role="dialog"
			aria-modal="true"
			aria-labelledby="dctc-ai-wizard-title"
		>
			<div className="dctc-ai-modal-overlay" />
			<div className="dctc-ai-modal-content dctc-ai-wizard-modal__content">
				<div className="dctc-ai-modal-header">
					<div className="dctc-ai-modal-title">
						<span className="dctc-ai-modal-title-icon" aria-hidden="true">
							<span className="dashicons dashicons-format-chat" />
						</span>
						<h3 id="dctc-ai-wizard-title">
							{ __(
								'Welcome to AI Assistant — quick setup',
								'dragwyb-click-to-chat'
							) }
						</h3>
					</div>
					<button
						type="button"
						className="dctc-ai-modal-close"
						onClick={ () => skipOrClose( 'skipped' ) }
						aria-label={ __( 'Close', 'dragwyb-click-to-chat' ) }
						disabled={ busy }
					>
						<span className="dashicons dashicons-no-alt" aria-hidden="true" />
					</button>
				</div>

				{ step !== 'done' && (
					<div className="dctc-ai-wizard-progress">
						{ Array.from( { length: totalSteps }, ( _, i ) => i + 1 ).map(
							( n ) => (
								<button
									key={ n }
									type="button"
									className={
										`dctc-ai-wizard-progress__dot ${
											n === step ? 'is-active' : ''
										} ${ n < step ? 'is-done' : '' }`
									}
									onClick={ () => n < step && setStep( n ) }
									disabled={ n >= step || busy }
									aria-label={
										__( 'Go back to step', 'dragwyb-click-to-chat' ) +
										': ' +
										stepLabels[ n - 1 ]
									}
									aria-current={ n === step ? 'step' : undefined }
								/>
							)
						) }
					</div>
				) }

				<div className="dctc-ai-modal-body dctc-ai-wizard-modal__body">
					{ step === 1 && (
						<>
							<h2 className="dctc-ai-wizard-step-title">
								{ __( 'Connect an AI provider', 'dragwyb-click-to-chat' ) }
							</h2>
							<p className="dctc-ai-wizard-step-desc">
								{ __(
									'Choose an AI provider and enter your API key to continue.',
									'dragwyb-click-to-chat'
								) }
							</p>
							<div className="dctc-ai-bot-field">
								<label htmlFor="wizard_provider">
									{ __( 'AI Provider', 'dragwyb-click-to-chat' ) }
								</label>
								<select
									id="wizard_provider"
									className="dctc-ai-bot-select"
									value={ provider }
									onChange={ ( e ) => {
										const v = e.target.value;
										setProvider( v );
										setApiKey( settings?.api_keys?.[ v ] || '' );
										setEmbeddingProvider( v );
									} }
								>
									{ Object.entries( PROVIDERS ).map( ( [ id, meta ] ) => (
										<option key={ id } value={ id }>
											{ meta.name }
										</option>
									) ) }
								</select>
							</div>
							<div className="dctc-ai-bot-field">
								<label htmlFor="wizard_api_key">
									{ __( 'API Key', 'dragwyb-click-to-chat' ) }
								</label>
								<input
									type="text"
									id="wizard_api_key"
									className="dctc-ai-bot-input"
									value={ apiKey }
									onChange={ ( e ) => setApiKey( e.target.value ) }
									placeholder={ __(
										'Paste your API key here',
										'dragwyb-click-to-chat'
									) }
									disabled={ keyAlreadySet }
								/>
								<a
									href={ PROVIDERS[ provider ].link }
									className="dctc-ai-api-help-link"
									target="_blank"
									rel="noopener noreferrer"
								>
									{ __(
										'Generate your API key here',
										'dragwyb-click-to-chat'
									) }
									<span
										className="dashicons dashicons-external"
										aria-hidden="true"
									/>
								</a>
							</div>
						</>
					) }

					{ step === 2 && (
						<>
							<h2 className="dctc-ai-wizard-step-title">
								{ __( 'Choose your database', 'dragwyb-click-to-chat' ) }
							</h2>
							<p className="dctc-ai-wizard-step-desc">
								{ __(
									'Your knowledge base is stored here so the AI can quickly search and use it when answering questions.',
									'dragwyb-click-to-chat'
								) }
							</p>
							<div className="dctc-ai-kb-db-options">
								{ VECTOR_DB_OPTIONS.map( ( opt ) => (
									<label
										key={ opt.value }
										className="dctc-ai-radio-card"
										style={ { margin: 0 } }
									>
										<input
											type="radio"
											name="wizard_vector_db"
											value={ opt.value }
											checked={ vectorDb === opt.value }
											onChange={ () => setVectorDb( opt.value ) }
										/>
										<span className="dctc-ai-radio-card__label">
											<strong>{ opt.label }</strong>
											<br />
											<small>{ opt.desc }</small>
										</span>
									</label>
								) ) }
							</div>
							<div className="dctc-ai-bot-field" style={ { marginTop: '1.5rem' } }>
								<div
									style={ {
										display: 'flex',
										alignItems: 'center',
										marginBottom: '0.375rem',
									} }
								>
									<label
										htmlFor="wizard_embedding_provider"
										style={ { marginBottom: 0 } }
									>
										{ __( 'Embedding Provider', 'dragwyb-click-to-chat' ) }
									</label>
									{ isPinecone && (
										<div className="dctc-ai-info-tooltip-wrapper">
											<button
												type="button"
												className="dctc-ai-info-btn"
												aria-label={ __(
													'Dimensions info',
													'dragwyb-click-to-chat'
												) }
											>
												i
											</button>
											<div className="dctc-ai-info-tooltip">
												{ sprintf(
													/* translators: 1: model name, 2: dimensions */
													__(
														'Because you selected %1$s, your Pinecone index must be created with exactly %2$d dimensions.',
														'dragwyb-click-to-chat'
													),
													embeddingProvider === 'google'
														? __(
																'Google Gemini (gemini-embedding-001)',
																'dragwyb-click-to-chat'
														  )
														: __(
																'OpenAI (text-embedding-3-small)',
																'dragwyb-click-to-chat'
														  ),
													embeddingProvider === 'google' ? 768 : 1536
												) }
											</div>
										</div>
									) }
								</div>
								<select
									id="wizard_embedding_provider"
									className="dctc-ai-bot-select"
									value={ embeddingProvider }
									onChange={ ( e ) => setEmbeddingProvider( e.target.value ) }
								>
									<option value="openai">
										{ __(
											'OpenAI (text-embedding-3-small)',
											'dragwyb-click-to-chat'
										) }
									</option>
									<option value="google">
										{ __(
											'Google Gemini (gemini-embedding-001)',
											'dragwyb-click-to-chat'
										) }
									</option>
								</select>
								<p
									className="dctc-ai-bot-hint"
									style={ { marginTop: '0.375rem' } }
								>
									{ __(
										'Select the AI provider to generate vector embeddings.',
										'dragwyb-click-to-chat'
									) }
								</p>
								{ ! hasEmbedKey && (
									<div
										className="dctc-ai-bot-warning"
										style={ {
											marginTop: '0.75rem',
											padding: '0.75rem 1rem',
											background: '#fef2f2',
											border: '1px solid #fee2e2',
											borderRadius: '8px',
											fontSize: '0.8125rem',
											color: '#b91c1c',
											display: 'flex',
											alignItems: 'center',
											gap: '0.5rem',
											flexWrap: 'wrap',
										} }
									>
										<span>
											{ __(
												'You need to add an API key to generate embeddings.',
												'dragwyb-click-to-chat'
											) }
										</span>
										<button
											type="button"
											className="dctc-ai-bot-link"
											style={ {
												display: 'inline-flex',
												padding: 0,
												border: 'none',
												background: 'none',
												color: '#2563eb',
												textDecoration: 'underline',
												cursor: 'pointer',
												fontSize: 'inherit',
												fontWeight: '600',
											} }
											onClick={ () => setStep( 1 ) }
										>
											{ __(
												'Go back to Step 1 to add the API key',
												'dragwyb-click-to-chat'
											) }
										</button>
									</div>
								) }
							</div>
						</>
					) }

					{ isPinecone ? (
						<>
							{ step === 3 && pineconeStep() }
							{ step === 4 && contentStep }
							{ step === 5 && knowledgeStep }
						</>
					) : (
						<>
							{ step === 3 && contentStep }
							{ step === 4 && knowledgeStep }
						</>
					) }

					{ step === 'done' && (
						<div className="dctc-ai-wizard-done">
							<span
								className="dctc-ai-wizard-done__icon dashicons dashicons-yes-alt"
								aria-hidden="true"
							/>
							<h2 className="dctc-ai-wizard-step-title">
								{ __( "You're all set!", 'dragwyb-click-to-chat' ) }
							</h2>
							<p className="dctc-ai-wizard-step-desc">
								{ __(
									'Your chatbot is ready to go. You can fine-tune everything else from the dashboard at any time.',
									'dragwyb-click-to-chat'
								) }
							</p>
						</div>
					) }
				</div>

				<div className="dctc-ai-modal-footer dctc-ai-wizard-modal__footer">
					{ step === 'done' ? (
						<>
							<span />
							<button
								type="button"
								className="dctc-ai-btn dctc-ai-btn-primary"
								onClick={ () => {
									if (
										window.location.href.includes(
											'page=dragwyb-click-to-chat-ai'
										)
									) {
										onClose();
									} else {
										window.location.href =
											'admin.php?page=dragwyb-click-to-chat-ai';
									}
								} }
							>
								{ __( 'Go to Settings', 'dragwyb-click-to-chat' ) }
							</button>
						</>
					) : (
						<>
							<button
								type="button"
								className="dctc-ai-wizard-skip"
								onClick={ () => {
									if ( step === ( isPinecone ? 5 : 4 ) ) {
										skipOrClose( 'skipped' );
									} else {
										setStep( step + 1 );
									}
								} }
								disabled={ busy }
							>
								{ __( 'Skip for now', 'dragwyb-click-to-chat' ) }
							</button>
							<button
								type="button"
								className="dctc-ai-btn dctc-ai-btn-primary"
								onClick={ onNext }
								disabled={ nextDisabled }
							>
								{ busy ? (
									<span className="dctc-ai-spinner" aria-hidden="true" />
								) : isFinishStep ? (
									__( 'Finish', 'dragwyb-click-to-chat' )
								) : (
									__( 'Next', 'dragwyb-click-to-chat' )
								) }
							</button>
						</>
					) }
				</div>
			</div>
		</div>
	);
}
