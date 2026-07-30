/**
 * Knowledge Base — Sources + Database tabs, indexing with 2s status poll.
 */
import { useState, useEffect } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import ConfirmModal from '../components/ConfirmModal';
import {
	VECTOR_DB_OPTIONS,
	resolveEmbeddingProvider,
} from '../utils/providers';

const SUBTABS = [
	{ id: 'sources', label: __( 'Sources', 'dragwyb-click-to-chat' ) },
	{ id: 'vector-db', label: __( 'Database', 'dragwyb-click-to-chat' ) },
];

const TEXT_HINT = __(
	'Add facts, FAQs, or company info you want the chatbot to rely on when answering.',
	'dragwyb-click-to-chat'
);
const URL_HINT = __(
	'We will read these pages from time to time and use what we find in replies.',
	'dragwyb-click-to-chat'
);
const DEFAULT_NO_DATA =
	"I don't have information about your question in my knowledge base. Please rephrase or ask about topics I have knowledge of.";

export default function KnowledgeBase( { settings, onSave, showNotice } ) {
	const [ saving, setSaving ] = useState( false );
	const [ indexing, setIndexing ] = useState( false );
	const [ indexStatus, setIndexStatus ] = useState( null );
	const [ stats, setStats ] = useState( null );
	const [ availableTypes, setAvailableTypes ] = useState( [] );
	const [ subtab, setSubtab ] = useState( 'sources' );
	const [ confirmReset, setConfirmReset ] = useState( false );

	const chatbot = settings?.chatbot || {};
	const rag = settings?.rag || {};
	const hasPineconeSaved = !!(
		rag.vector_db?.api_key ||
		rag.vector_db?.host ||
		rag.vector_db?.index_name
	);

	const [ knowledgeText, setKnowledgeText ] = useState(
		chatbot.knowledge_text || ''
	);
	const [ urls, setUrls ] = useState(
		Array.isArray( chatbot.knowledge_urls ) && chatbot.knowledge_urls.length
			? chatbot.knowledge_urls
			: []
	);
	const [ trainingFiles, setTrainingFiles ] = useState(
		Array.isArray( chatbot.training_files ) ? chatbot.training_files : []
	);
	const [ selectedPostTypes, setSelectedPostTypes ] = useState(
		rag.post_types || [ 'post', 'page' ]
	);
	const [ chunkSize, setChunkSize ] = useState( rag.chunk_size || 1000 );
	const [ maxResults, setMaxResults ] = useState( rag.max_results || 5 );
	const [ vectorDb, setVectorDb ] = useState(
		rag.vector_db?.provider || 'sqlite'
	);
	const [ requireIndexed, setRequireIndexed ] = useState(
		rag.require_indexed_data || false
	);
	const [ noDataMessage, setNoDataMessage ] = useState(
		rag.no_data_message || DEFAULT_NO_DATA
	);
	const [ pineconeKey, setPineconeKey ] = useState(
		rag.vector_db?.api_key || ''
	);
	const [ pineconeHost, setPineconeHost ] = useState(
		rag.vector_db?.host || ''
	);
	const [ pineconeIndex, setPineconeIndex ] = useState(
		rag.vector_db?.index_name || ''
	);

	const [ embeddingProvider, setEmbeddingProvider ] = useState( () =>
		resolveEmbeddingProvider( settings )
	);

	const [ savedSnapshot, setSavedSnapshot ] = useState( () => ( {
		knowledgeText: chatbot.knowledge_text || '',
		urls:
			Array.isArray( chatbot.knowledge_urls ) && chatbot.knowledge_urls.length
				? chatbot.knowledge_urls
				: [],
		trainingFiles: Array.isArray( chatbot.training_files )
			? chatbot.training_files
			: [],
		selectedPostTypes: rag.post_types || [ 'post', 'page' ],
		maxChunkSize: rag.chunk_size || 1000,
		maxResults: rag.max_results || 5,
		vectorDb: rag.vector_db?.provider || 'sqlite',
		requireIndexedData: rag.require_indexed_data || false,
		noDataMessage: rag.no_data_message || DEFAULT_NO_DATA,
		pineconeApiKey: rag.vector_db?.api_key || '',
		pineconeHost: rag.vector_db?.host || '',
		pineconeIndexName: rag.vector_db?.index_name || '',
		embeddingProviderValue: resolveEmbeddingProvider( settings ),
	} ) );

	useEffect( () => {
		if ( ! rag.embeddings?.provider ) {
			const p = resolveEmbeddingProvider( settings );
			setEmbeddingProvider( p );
			setSavedSnapshot( ( prev ) => ( {
				...prev,
				embeddingProviderValue: p,
			} ) );
		}
	}, [
		settings?.api_keys?.openai,
		settings?.api_keys?.google,
		settings?.chatbot?.default_provider,
		rag.embeddings?.provider,
	] );

	const currentSnapshot = {
		knowledgeText,
		urls,
		trainingFiles: trainingFiles.map( ( f ) =>
			typeof f === 'object' ? f.id : f
		),
		selectedPostTypes,
		maxChunkSize: chunkSize,
		maxResults,
		vectorDb,
		requireIndexedData: requireIndexed,
		noDataMessage,
		pineconeApiKey: pineconeKey,
		pineconeHost,
		pineconeIndexName: pineconeIndex,
		embeddingProviderValue: embeddingProvider,
	};
	const dirty =
		JSON.stringify( currentSnapshot ) !== JSON.stringify( savedSnapshot );

	const embedInfo =
		embeddingProvider === 'google'
			? {
					provider: 'Google Gemini',
					model: 'gemini-embedding-001',
					key: settings?.api_keys?.google ? 'google' : null,
					dimensions: 768,
			  }
			: {
					provider: 'OpenAI',
					model: 'text-embedding-3-small',
					key: settings?.api_keys?.openai ? 'openai' : null,
					dimensions: 1536,
			  };

	const indexedTypes = stats?.indexed_post_types || [];
	const postTypesChanged =
		!! stats &&
		( selectedPostTypes.length !== indexedTypes.length ||
			selectedPostTypes.some( ( t ) => ! new Set( indexedTypes ).has( t ) ) );

	useEffect( () => {
		fetchPostTypes();
		fetchStats();
	}, [] );

	const fetchPostTypes = async () => {
		try {
			const res = await apiFetch( {
				path: '/dctc-ai/v1/rag/post-types',
				method: 'GET',
			} );
			setAvailableTypes( res.types || [] );
		} catch ( e ) {
			console.error( 'Failed to fetch post types:', e );
		}
	};

	const fetchStats = async () => {
		try {
			const res = await apiFetch( {
				path: '/dctc-ai/v1/rag/stats',
				method: 'GET',
			} );
			setStats( res );
		} catch ( e ) {
			console.error( 'Failed to fetch RAG stats:', e );
		}
	};

	const progressPct = indexStatus
		? indexStatus.status === 'indexing' && indexStatus.docs_total > 0
			? Math.round(
					( indexStatus.docs_processed / indexStatus.docs_total ) * 100
			  )
			: indexStatus.status === 'embedding' && indexStatus.embed_total > 0
			? Math.round(
					( indexStatus.embed_processed / indexStatus.embed_total ) * 100
			  )
			: indexStatus.status === 'completed'
			? 100
			: 0
		: 0;

	const progressLabel = indexStatus
		? indexStatus.status === 'indexing'
			? sprintf(
					/* translators: 1: processed, 2: total */
					__( 'Indexing documents… %1$d/%2$d', 'dragwyb-click-to-chat' ),
					indexStatus.docs_processed,
					indexStatus.docs_total
			  )
			: indexStatus.status === 'embedding'
			? indexStatus.embed_total > 0
				? sprintf(
						/* translators: 1: processed, 2: total */
						__(
							'Generating embeddings… %1$d/%2$d',
							'dragwyb-click-to-chat'
						),
						indexStatus.embed_processed,
						indexStatus.embed_total
				  )
				: __( 'Generating embeddings…', 'dragwyb-click-to-chat' )
			: ''
		: '';

	const pollIndex = () => {
		const tick = async () => {
			let status;
			try {
				status = await apiFetch( {
					path: '/dctc-ai/v1/rag/index/status',
					method: 'GET',
				} );
				setIndexStatus( status );
			} catch ( e ) {
				console.error( 'Failed to fetch indexing status:', e );
				setIndexing( false );
				return;
			}
			if ( status.status !== 'indexing' && status.status !== 'embedding' ) {
				setIndexing( false );
				fetchStats();
			} else {
				setTimeout( tick, 2000 );
			}
		};
		tick();
	};

	const onSubmit = async ( e ) => {
		e.preventDefault();
		setSaving( true );
		const fileIds = trainingFiles.map( ( f ) =>
			typeof f === 'object' ? f.id : parseInt( f, 10 )
		);
		const cleanUrls = urls.filter( ( u ) => !! u.trim() );
		const botPayload = {
			knowledge_text: knowledgeText,
			knowledge_urls: cleanUrls,
			training_files: fileIds,
		};
		const ragPayload = {
			post_types: selectedPostTypes,
			chunk_size: parseInt( chunkSize, 10 ),
			max_results: parseInt( maxResults, 10 ),
			require_indexed_data: requireIndexed,
			no_data_message: noDataMessage,
			vector_db: {
				provider: vectorDb,
				api_key: vectorDb === 'pinecone' ? pineconeKey : '',
				host: vectorDb === 'pinecone' ? pineconeHost : '',
				index_name: vectorDb === 'pinecone' ? pineconeIndex : '',
			},
			embeddings: { provider: embeddingProvider },
		};
		try {
			await apiFetch( {
				path: '/dctc-ai/v1/save-bot-settings',
				method: 'POST',
				data: botPayload,
			} );
			await apiFetch( {
				path: '/dctc-ai/v1/rag/settings',
				method: 'POST',
				data: ragPayload,
			} );
			onSave( {
				chatbot: { ...chatbot, ...botPayload },
				rag: { ...rag, ...ragPayload },
			} );
			setSavedSnapshot( currentSnapshot );
			showNotice(
				postTypesChanged
					? __(
							'Settings saved! Please re-index your content to apply the changes.',
							'dragwyb-click-to-chat'
					  )
					: __(
							'Knowledge base and RAG settings saved successfully!',
							'dragwyb-click-to-chat'
					  )
			);
		} catch ( err ) {
			console.error( 'Save error:', err );
			showNotice(
				err.message || __( 'Failed to save settings', 'dragwyb-click-to-chat' ),
				'error'
			);
		} finally {
			setSaving( false );
		}
	};

	const startIndex = async () => {
		if ( selectedPostTypes.length === 0 ) {
			showNotice(
				__(
					'Please select at least one content type to index or enable website indexing',
					'dragwyb-click-to-chat'
				),
				'error'
			);
			return;
		}
		setIndexing( true );
		setIndexStatus( null );
		try {
			const res = await apiFetch( {
				path: '/dctc-ai/v1/rag/index',
				method: 'POST',
				data: {
					post_types: [ ...selectedPostTypes ],
					index_sources: {
						knowledge_text: true,
						urls: true,
						files: true,
						website: true,
					},
				},
			} );
			if ( ! res.success ) {
				showNotice(
					res.message ||
						__( 'Failed to start indexing', 'dragwyb-click-to-chat' ),
					'error'
				);
				setIndexing( false );
				return;
			}
			showNotice(
				res.message ||
					__( 'Content indexing started!', 'dragwyb-click-to-chat' )
			);
			if ( res.status === 'queued' ) {
				pollIndex();
			} else {
				setIndexing( false );
				fetchStats();
			}
		} catch ( err ) {
			console.error( 'Indexing error:', err );
			showNotice(
				err.message || __( 'Failed to index content', 'dragwyb-click-to-chat' ),
				'error'
			);
			setIndexing( false );
		}
	};

	const resetPinecone = async () => {
		setConfirmReset( false );
		setSaving( true );
		try {
			const payload = {
				...rag,
				vector_db: {
					provider: vectorDb,
					api_key: '',
					host: '',
					index_name: '',
				},
			};
			await apiFetch( {
				path: '/dctc-ai/v1/rag/settings',
				method: 'POST',
				data: payload,
			} );
			setPineconeKey( '' );
			setPineconeHost( '' );
			setPineconeIndex( '' );
			setSavedSnapshot( ( prev ) => ( {
				...prev,
				pineconeApiKey: '',
				pineconeHost: '',
				pineconeIndexName: '',
			} ) );
			onSave( {
				rag: {
					...rag,
					vector_db: {
						...rag.vector_db,
						api_key: '',
						host: '',
						index_name: '',
					},
				},
			} );
			showNotice(
				__( 'Pinecone settings reset successfully!', 'dragwyb-click-to-chat' )
			);
		} catch ( err ) {
			showNotice(
				err.message ||
					__( 'Failed to reset Pinecone settings', 'dragwyb-click-to-chat' ),
				'error'
			);
		} finally {
			setSaving( false );
		}
	};

	const togglePostType = ( value ) => {
		setSelectedPostTypes( ( prev ) =>
			prev.includes( value )
				? prev.filter( ( v ) => v !== value )
				: [ ...prev, value ]
		);
	};

	return (
		<div className="dctc-ai-kb-settings">
			<nav
				className="dctc-ai-kb-subnav"
				role="tablist"
				aria-label={ __( 'Knowledge base sections', 'dragwyb-click-to-chat' ) }
			>
				{ SUBTABS.map( ( tab ) => (
					<button
						key={ tab.id }
						type="button"
						role="tab"
						aria-selected={ subtab === tab.id }
						className={
							'dctc-ai-kb-subtab ' + ( subtab === tab.id ? 'active' : '' )
						}
						onClick={ () => setSubtab( tab.id ) }
					>
						{ tab.label }
					</button>
				) ) }
			</nav>

			<form onSubmit={ onSubmit }>
				{ /* Sources */ }
				<div className={ subtab === 'sources' ? '' : 'dctc-ai-kb-panel--hidden' }>
					<article className="dctc-ai-kb-card">
						<header className="dctc-ai-kb-card__header">
							<span className="dctc-ai-kb-card__icon" aria-hidden="true">
								<span className="dashicons dashicons-media-text" />
							</span>
							<div className="dctc-ai-kb-card__heading">
								<h3 className="dctc-ai-kb-card__title">
									{ __( 'Direct Text Knowledge', 'dragwyb-click-to-chat' ) }
								</h3>
								<p className="dctc-ai-kb-card__desc">{ TEXT_HINT }</p>
							</div>
						</header>
						<div className="dctc-ai-kb-card__body">
							<textarea
								className="dctc-ai-kb-textarea"
								rows="8"
								value={ knowledgeText }
								onChange={ ( e ) => setKnowledgeText( e.target.value ) }
								placeholder={ __(
									'Enter factual information directly…',
									'dragwyb-click-to-chat'
								) }
							/>
						</div>
					</article>

					<article className="dctc-ai-kb-card">
						<header className="dctc-ai-kb-card__header">
							<span className="dctc-ai-kb-card__icon" aria-hidden="true">
								<span className="dashicons dashicons-admin-site-alt3" />
							</span>
							<div className="dctc-ai-kb-card__heading">
								<h3 className="dctc-ai-kb-card__title">
									{ __( 'Web Pages (URLs)', 'dragwyb-click-to-chat' ) }
								</h3>
								<p className="dctc-ai-kb-card__desc">{ URL_HINT }</p>
							</div>
						</header>
						<div className="dctc-ai-kb-card__body">
							<div className="dctc-ai-kb-url-list">
								{ urls.map( ( url, idx ) => (
									<div key={ idx } className="dctc-ai-kb-url-item">
										<span
											className="dctc-ai-kb-url-item__icon dashicons dashicons-admin-links"
											aria-hidden="true"
										/>
										<input
											type="url"
											className="dctc-ai-kb-url-input"
											value={ url }
											onChange={ ( e ) => {
												const value = e.target.value;
												setUrls( ( prev ) => {
													const next = [ ...prev ];
													next[ idx ] = value;
													return next;
												} );
											} }
											placeholder="https://example.com/page"
										/>
										<button
											type="button"
											className="dctc-ai-kb-url-remove"
											onClick={ () =>
												setUrls( ( prev ) =>
													prev.filter( ( _, i ) => i !== idx )
												)
											}
											aria-label={ __(
												'Remove URL',
												'dragwyb-click-to-chat'
											) }
										>
											<span
												className="dashicons dashicons-trash"
												aria-hidden="true"
											/>
										</button>
									</div>
								) ) }
							</div>
							<button
								type="button"
								className="dctc-ai-kb-add-url"
								onClick={ () => setUrls( [ ...urls, '' ] ) }
							>
								{ __( '+ Add URL', 'dragwyb-click-to-chat' ) }
							</button>
						</div>
					</article>
				</div>

				{ /* Database */ }
				<div
					className={ subtab === 'vector-db' ? '' : 'dctc-ai-kb-panel--hidden' }
				>
					<article className="dctc-ai-kb-card">
						<header className="dctc-ai-kb-card__header">
							<span className="dctc-ai-kb-card__icon" aria-hidden="true">
								<span className="dashicons dashicons-media-text" />
							</span>
							<div className="dctc-ai-kb-card__heading">
								<h3 className="dctc-ai-kb-card__title">
									{ __(
										'Website Content to Index',
										'dragwyb-click-to-chat'
									) }
								</h3>
								<p className="dctc-ai-kb-card__desc">
									{ __(
										'Select which post types to include. Leave all unchecked to skip indexing your website content.',
										'dragwyb-click-to-chat'
									) }
								</p>
							</div>
						</header>
						<div className="dctc-ai-kb-card__body">
							<div className="dctc-ai-kb-post-types">
								{ availableTypes.length > 0 ? (
									availableTypes.map( ( type ) => (
										<label key={ type.value } className="dctc-ai-checkbox">
											<input
												type="checkbox"
												checked={ selectedPostTypes.includes( type.value ) }
												onChange={ () => togglePostType( type.value ) }
											/>
											<span className="dctc-ai-checkbox__label">
												{ type.label } ({ type.count })
											</span>
										</label>
									) )
								) : (
									<p className="dctc-ai-hint">
										{ __(
											'No post types available.',
											'dragwyb-click-to-chat'
										) }
									</p>
								) }
							</div>
							{ indexing && indexStatus && (
								<div className="dctc-ai-kb-index-progress" role="status">
									<div className="dctc-ai-kb-index-progress__bar">
										<div
											className="dctc-ai-kb-index-progress__fill"
											style={ { width: `${ progressPct }%` } }
										/>
									</div>
									<p className="dctc-ai-kb-index-progress__label">
										{ progressLabel }
									</p>
								</div>
							) }
						</div>
					</article>

					<article className="dctc-ai-kb-card">
						<header className="dctc-ai-kb-card__header">
							<span className="dctc-ai-kb-card__icon" aria-hidden="true">
								<span className="dashicons dashicons-database" />
							</span>
							<div className="dctc-ai-kb-card__heading">
								<h3 className="dctc-ai-kb-card__title">
									{ __( 'Database', 'dragwyb-click-to-chat' ) }
								</h3>
								<p className="dctc-ai-kb-card__desc">
									{ __(
										'Choose where to store your document embeddings for semantic search.',
										'dragwyb-click-to-chat'
									) }
								</p>
							</div>
						</header>
						<div className="dctc-ai-kb-card__body">
							<div className="dctc-ai-kb-db-options">
								{ VECTOR_DB_OPTIONS.map( ( opt ) => (
									<label key={ opt.value } className="dctc-ai-radio-card">
										<input
											type="radio"
											name="vector_db"
											value={ opt.value }
											checked={ vectorDb === opt.value }
											onChange={ ( e ) => setVectorDb( e.target.value ) }
										/>
										<span className="dctc-ai-radio-card__label">
											<strong>{ opt.label }</strong>
											<br />
											<small>{ opt.desc }</small>
										</span>
									</label>
								) ) }
							</div>
						</div>
					</article>

					{ vectorDb === 'pinecone' && (
						<article className="dctc-ai-kb-card">
							<header
								className="dctc-ai-kb-card__header"
								style={ {
									display: 'flex',
									justifyContent: 'space-between',
									alignItems: 'center',
									flexWrap: 'wrap',
									gap: '1rem',
								} }
							>
								<div
									style={ {
										display: 'flex',
										gap: '0.75rem',
										alignItems: 'center',
										minWidth: 0,
										flex: 1,
									} }
								>
									<span
										className="dctc-ai-kb-card__icon"
										aria-hidden="true"
										style={ { flexShrink: 0 } }
									>
										<span className="dashicons dashicons-cloud" />
									</span>
									<div className="dctc-ai-kb-card__heading" style={ { minWidth: 0 } }>
										<h3
											className="dctc-ai-kb-card__title"
											style={ { display: 'flex', alignItems: 'center' } }
										>
											{ __(
												'Pinecone Configuration',
												'dragwyb-click-to-chat'
											) }
											{ embedInfo.dimensions && (
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
															__(
																'Because you selected %1$s, your Pinecone index must be created with exactly %2$d dimensions.',
																'dragwyb-click-to-chat'
															),
															embedInfo.provider,
															embedInfo.dimensions
														) }
													</div>
												</div>
											) }
										</h3>
										<p className="dctc-ai-kb-card__desc" style={ { margin: 0 } }>
											{ __(
												'Connect your Pinecone cloud vector database.',
												'dragwyb-click-to-chat'
											) }{ ' ' }
											<a
												href="https://app.pinecone.io/"
												target="_blank"
												rel="noopener noreferrer"
												style={ { fontWeight: '500' } }
											>
												{ __(
													'Open Pinecone Console',
													'dragwyb-click-to-chat'
												) }{ ' ' }
												→
											</a>
										</p>
									</div>
								</div>
								{ hasPineconeSaved && (
									<button
										type="button"
										className="dctc-ai-btn dctc-ai-btn-secondary dctc-ai-btn-sm"
										onClick={ () => setConfirmReset( true ) }
										style={ {
											fontSize: '0.8125rem',
											padding: '0.5rem 0.875rem',
											flexShrink: 0,
										} }
									>
										{ __( 'Reset Settings', 'dragwyb-click-to-chat' ) }
									</button>
								) }
							</header>
							<div className="dctc-ai-kb-card__body">
								<div className="dctc-ai-kb-form-group">
									<label className="dctc-ai-label">
										{ __( 'Pinecone API Key', 'dragwyb-click-to-chat' ) }
										<input
											type="password"
											className="dctc-ai-input"
											value={ pineconeKey }
											onChange={ ( e ) => setPineconeKey( e.target.value ) }
											placeholder="pcsk_..."
										/>
									</label>
									<p className="dctc-ai-hint">
										{ __(
											'You can generate an API key from the "API Keys" section in your Pinecone dashboard.',
											'dragwyb-click-to-chat'
										) }
									</p>
								</div>
								<div className="dctc-ai-kb-form-group">
									<label className="dctc-ai-label">
										{ __( 'Pinecone Host', 'dragwyb-click-to-chat' ) }
										<input
											type="text"
											className="dctc-ai-input"
											value={ pineconeHost }
											onChange={ ( e ) => setPineconeHost( e.target.value ) }
											placeholder="https://index-xxxxx.svc.aped-4627-b74a.pinecone.io"
										/>
									</label>
									<p className="dctc-ai-hint">
										{ __(
											'The host URL for your index. Find this by clicking on your index in the Pinecone dashboard.',
											'dragwyb-click-to-chat'
										) }
									</p>
								</div>
								<div className="dctc-ai-kb-form-group">
									<label className="dctc-ai-label">
										{ __( 'Index Name', 'dragwyb-click-to-chat' ) }
										<input
											type="text"
											className="dctc-ai-input"
											value={ pineconeIndex }
											onChange={ ( e ) => setPineconeIndex( e.target.value ) }
											placeholder="e.g. dctc-ai-index"
										/>
									</label>
									<p className="dctc-ai-hint">
										{ __(
											'The exact name of the index you created.',
											'dragwyb-click-to-chat'
										) }
									</p>
								</div>
								{ hasPineconeSaved && (
									<div className="dctc-ai-kb-pinecone-reset">
										<button
											type="button"
											className="dctc-ai-btn dctc-ai-btn-secondary"
											onClick={ () => setConfirmReset( true ) }
										>
											{ __(
												'Reset Pinecone Settings',
												'dragwyb-click-to-chat'
											) }
										</button>
										<p className="dctc-ai-hint">
											{ __(
												'Click this to clear your Pinecone API Key, Host, and Index Name.',
												'dragwyb-click-to-chat'
											) }
										</p>
									</div>
								) }
							</div>
						</article>
					) }

					<article className="dctc-ai-kb-card">
						<header className="dctc-ai-kb-card__header">
							<span className="dctc-ai-kb-card__icon" aria-hidden="true">
								<span className="dashicons dashicons-lightbulb" />
							</span>
							<div className="dctc-ai-kb-card__heading">
								<h3 className="dctc-ai-kb-card__title">
									{ __( 'Embedding Configuration', 'dragwyb-click-to-chat' ) }
								</h3>
								<p className="dctc-ai-kb-card__desc">
									{ __(
										'Select the AI provider to generate vector embeddings.',
										'dragwyb-click-to-chat'
									) }
								</p>
							</div>
						</header>
						<div className="dctc-ai-kb-card__body">
							<div className="dctc-ai-kb-form-group">
								<div
									style={ {
										display: 'flex',
										alignItems: 'center',
										marginBottom: '0.375rem',
									} }
								>
									<label
										className="dctc-ai-label"
										style={ { marginBottom: 0 } }
									>
										{ __( 'Embedding Provider', 'dragwyb-click-to-chat' ) }
									</label>
									{ vectorDb === 'pinecone' && (
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
									className="dctc-ai-select dctc-ai-kb-embedding-select"
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
								<p className="dctc-ai-hint">
									{ __(
										'Select the provider to use for processing your knowledge base into vectors.',
										'dragwyb-click-to-chat'
									) }
								</p>
							</div>
							<div className="dctc-ai-kb-info-block">
								<p>
									<strong>
										{ __( 'Status:', 'dragwyb-click-to-chat' ) }
									</strong>
									{ embedInfo.key ? (
										<span className="dctc-ai-kb-status dctc-ai-kb-status--ok">
											✓{ ' ' }
											{ __(
												'API Key Configured',
												'dragwyb-click-to-chat'
											) }
										</span>
									) : (
										<span className="dctc-ai-kb-status dctc-ai-kb-status--missing">
											✗{ ' ' }
											{ __( 'API Key Missing', 'dragwyb-click-to-chat' ) }
										</span>
									) }
								</p>
								<p>
									<strong>
										{ __(
											'Required Index Dimensions:',
											'dragwyb-click-to-chat'
										) }
									</strong>{ ' ' }
									{ embedInfo.dimensions }
								</p>
								{ ! embedInfo.key && (
									<p className="dctc-ai-kb-warning">
										{ __(
											'⚠️ No AI API key found for the selected provider. Please add an API key in the API Keys tab to enable RAG indexing.',
											'dragwyb-click-to-chat'
										) }
									</p>
								) }
							</div>
						</div>
					</article>
				</div>

				<footer className="dctc-ai-kb-footer">
					<button
						type="submit"
						className="dctc-ai-btn dctc-ai-btn-primary"
						disabled={ saving || ! dirty }
					>
						{ saving
							? __( 'Saving…', 'dragwyb-click-to-chat' )
							: __( 'Save', 'dragwyb-click-to-chat' ) }
					</button>
					{ subtab === 'vector-db' && selectedPostTypes.length > 0 && (
						<button
							type="button"
							className="dctc-ai-btn dctc-ai-btn-secondary"
							onClick={ startIndex }
							disabled={ indexing }
						>
							<span className="dashicons dashicons-update" aria-hidden="true" />
							{ indexing
								? __( 'Indexing…', 'dragwyb-click-to-chat' )
								: __( 'Index Content Now', 'dragwyb-click-to-chat' ) }
						</button>
					) }
				</footer>
			</form>

			<ConfirmModal
				open={ confirmReset }
				title={ __( 'Reset Pinecone settings', 'dragwyb-click-to-chat' ) }
				message={ __(
					'Are you sure you want to clear your Pinecone API Key, Host, and Index Name?',
					'dragwyb-click-to-chat'
				) }
				confirmLabel={ __(
					'Reset Pinecone Settings',
					'dragwyb-click-to-chat'
				) }
				onCancel={ () => setConfirmReset( false ) }
				onConfirm={ resetPinecone }
			/>
		</div>
	);
}
