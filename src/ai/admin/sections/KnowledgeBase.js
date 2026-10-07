/**
 * Knowledge Base — Sources + Database tabs, indexing with 2s status poll.
 */
import { useState, useEffect } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import ConfirmModal from '../components/ConfirmModal';
import { resolveEmbeddingProvider } from '../utils/providers';
import { SourcesSubtab, VectorDbSubtab } from './knowledge-base';

const SUBTABS = [
	{
		id: 'sources',
		label: __('Knowledge Sources', 'dragwyb-click-to-chat'),
		icon: 'dashicons-media-text',
		desc: __('Direct text, web URLs & training docs', 'dragwyb-click-to-chat'),
	},
	{
		id: 'vector-db',
		label: __('Database & Indexing', 'dragwyb-click-to-chat'),
		icon: 'dashicons-database',
		desc: __('Vector store, embedding & sync triggers', 'dragwyb-click-to-chat'),
	},
];

const DEFAULT_NO_DATA =
	"I don't have information about your question in my knowledge base. Please rephrase or ask about topics I have knowledge of.";

export default function KnowledgeBase({ settings, onSave, showNotice }) {
	const [saving, setSaving] = useState(false);
	const [indexing, setIndexing] = useState(false);
	const [indexStatus, setIndexStatus] = useState(null);
	const [stats, setStats] = useState(null);
	const [availableTypes, setAvailableTypes] = useState([]);
	const [subtab, setSubtab] = useState('sources');
	const [confirmReset, setConfirmReset] = useState(false);

	const chatbot = settings?.chatbot || {};
	const rag = settings?.rag || {};
	const hasPineconeSaved = !!(
		rag.vector_db?.api_key ||
		rag.vector_db?.host ||
		rag.vector_db?.index_name
	);

	const [knowledgeText, setKnowledgeText] = useState(
		chatbot.knowledge_text || ''
	);
	const [urls, setUrls] = useState(
		Array.isArray(chatbot.knowledge_urls) && chatbot.knowledge_urls.length
			? chatbot.knowledge_urls
			: []
	);
	const [trainingFiles, setTrainingFiles] = useState(
		Array.isArray(chatbot.training_files) ? chatbot.training_files : []
	);
	const [selectedPostTypes, setSelectedPostTypes] = useState(
		rag.post_types || ['post', 'page']
	);
	const [chunkSize, setChunkSize] = useState(rag.chunk_size || 1000);
	const [maxResults, setMaxResults] = useState(rag.max_results || 5);
	const [vectorDb, setVectorDb] = useState(
		rag.vector_db?.provider || 'sqlite'
	);
	const [requireIndexed, setRequireIndexed] = useState(
		rag.require_indexed_data || false
	);
	const [minConfidence, setMinConfidence] = useState(
		rag.min_confidence !== undefined ? parseFloat(rag.min_confidence) : 0.65
	);
	const [noDataMessage, setNoDataMessage] = useState(
		rag.no_data_message || DEFAULT_NO_DATA
	);
	const [pineconeKey, setPineconeKey] = useState(
		rag.vector_db?.api_key || ''
	);
	const [pineconeHost, setPineconeHost] = useState(
		rag.vector_db?.host || ''
	);
	const [pineconeIndex, setPineconeIndex] = useState(
		rag.vector_db?.index_name || ''
	);

	const [autoUpdate, setAutoUpdate] = useState(
		rag.indexing?.auto_update !== undefined
			? !!rag.indexing.auto_update
			: (rag.auto_update !== undefined ? !!rag.auto_update : true)
	);

	const [embeddingProvider, setEmbeddingProvider] = useState(() =>
		resolveEmbeddingProvider(settings)
	);

	const [savedSnapshot, setSavedSnapshot] = useState(() => ({
		knowledgeText: chatbot.knowledge_text || '',
		urls:
			Array.isArray(chatbot.knowledge_urls) && chatbot.knowledge_urls.length
				? chatbot.knowledge_urls
				: [],
		trainingFiles: Array.isArray(chatbot.training_files)
			? chatbot.training_files
			: [],
		selectedPostTypes: rag.post_types || ['post', 'page'],
		maxChunkSize: rag.chunk_size || 1000,
		maxResults: rag.max_results || 5,
		vectorDb: rag.vector_db?.provider || 'sqlite',
		requireIndexedData: rag.require_indexed_data || false,
		minConfidence:
			rag.min_confidence !== undefined ? parseFloat(rag.min_confidence) : 0.65,
		noDataMessage: rag.no_data_message || DEFAULT_NO_DATA,
		pineconeApiKey: rag.vector_db?.api_key || '',
		pineconeHost: rag.vector_db?.host || '',
		pineconeIndexName: rag.vector_db?.index_name || '',
		embeddingProviderValue: resolveEmbeddingProvider(settings),
		autoUpdate:
			rag.indexing?.auto_update !== undefined
				? !!rag.indexing.auto_update
				: (rag.auto_update !== undefined ? !!rag.auto_update : true),
	}));

	useEffect(() => {
		if (!rag.embeddings?.provider) {
			const p = resolveEmbeddingProvider(settings);
			setEmbeddingProvider(p);
			setSavedSnapshot((prev) => ({
				...prev,
				embeddingProviderValue: p,
			}));
		}
	}, [
		settings?.api_keys?.openai,
		settings?.api_keys?.google,
		settings?.chatbot?.default_provider,
		rag.embeddings?.provider,
	]);

	const currentSnapshot = {
		knowledgeText,
		urls,
		trainingFiles: trainingFiles.map((f) =>
			typeof f === 'object' ? f.id : f
		),
		selectedPostTypes,
		maxChunkSize: chunkSize,
		maxResults,
		vectorDb,
		requireIndexedData: requireIndexed,
		minConfidence,
		noDataMessage,
		pineconeApiKey: pineconeKey,
		pineconeHost,
		pineconeIndexName: pineconeIndex,
		embeddingProviderValue: embeddingProvider,
		autoUpdate,
	};
	const dirty =
		JSON.stringify(currentSnapshot) !== JSON.stringify(savedSnapshot);

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
		!!stats &&
		(selectedPostTypes.length !== indexedTypes.length ||
			selectedPostTypes.some((t) => !new Set(indexedTypes).has(t)));

	useEffect(() => {
		const handleTriggerSave = () => {
			onSubmit();
		};
		window.addEventListener('dctc_ai_trigger_save', handleTriggerSave);
		return () => {
			window.removeEventListener('dctc_ai_trigger_save', handleTriggerSave);
		};
	}, [currentSnapshot, settings]);

	useEffect(() => {
		fetchPostTypes();
		fetchStats();
	}, []);

	const fetchPostTypes = async () => {
		try {
			const res = await apiFetch({
				path: '/dctc-ai/v1/rag/post-types',
				method: 'GET',
			});
			setAvailableTypes(res.types || []);
		} catch (e) {
			console.error('Failed to fetch post types:', e);
		}
	};

	const fetchStats = async () => {
		try {
			const res = await apiFetch({
				path: '/dctc-ai/v1/rag/stats',
				method: 'GET',
			});
			setStats(res);
		} catch (e) {
			console.error('Failed to fetch RAG stats:', e);
		}
	};

	const progressPct = indexStatus
		? indexStatus.status === 'indexing' && indexStatus.docs_total > 0
			? Math.round(
				(indexStatus.docs_processed / indexStatus.docs_total) * 100
			)
			: indexStatus.status === 'embedding' && indexStatus.embed_total > 0
				? Math.round(
					(indexStatus.embed_processed / indexStatus.embed_total) * 100
				)
				: indexStatus.status === 'completed'
					? 100
					: 0
		: 0;

	const progressLabel = indexStatus
		? indexStatus.status === 'indexing'
			? sprintf(
				/* translators: 1: processed, 2: total */
				__('Indexing documents… %1$d/%2$d', 'dragwyb-click-to-chat'),
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
					: __('Generating embeddings…', 'dragwyb-click-to-chat')
				: ''
		: '';

	const pollIndex = () => {
		const tick = async () => {
			let status;
			try {
				status = await apiFetch({
					path: '/dctc-ai/v1/rag/index/status',
					method: 'GET',
				});
				setIndexStatus(status);
			} catch (e) {
				console.error('Failed to fetch indexing status:', e);
				setIndexing(false);
				return;
			}
			if (status.status !== 'indexing' && status.status !== 'embedding') {
				setIndexing(false);
				fetchStats();
			} else {
				setTimeout(tick, 2000);
			}
		};
		tick();
	};

	const onSubmit = async (e) => {
		if (e) e.preventDefault();
		setSaving(true);
		const fileIds = trainingFiles.map((f) =>
			typeof f === 'object' ? f.id : parseInt(f, 10)
		);
		const cleanUrls = urls.filter((u) => !!u.trim());
		const botPayload = {
			knowledge_text: knowledgeText,
			knowledge_urls: cleanUrls,
			training_files: fileIds,
		};
		const ragPayload = {
			post_types: selectedPostTypes,
			chunk_size: parseInt(chunkSize, 10),
			max_results: parseInt(maxResults, 10),
			require_indexed_data: requireIndexed,
			min_confidence: parseFloat(minConfidence),
			no_data_message: noDataMessage,
			vector_db: {
				provider: vectorDb,
				api_key: vectorDb === 'pinecone' ? pineconeKey : '',
				host: vectorDb === 'pinecone' ? pineconeHost : '',
				index_name: vectorDb === 'pinecone' ? pineconeIndex : '',
			},
			embeddings: { provider: embeddingProvider },
			auto_update: autoUpdate,
		};
		try {
			await apiFetch({
				path: '/dctc-ai/v1/save-bot-settings',
				method: 'POST',
				data: botPayload,
			});
			await apiFetch({
				path: '/dctc-ai/v1/rag/settings',
				method: 'POST',
				data: ragPayload,
			});
			onSave({
				chatbot: { ...chatbot, ...botPayload },
				rag: { ...rag, ...ragPayload },
			});
			setSavedSnapshot(currentSnapshot);
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
		} catch (err) {
			console.error('Save error:', err);
			showNotice(
				err.message || __('Failed to save settings', 'dragwyb-click-to-chat'),
				'error'
			);
		} finally {
			setSaving(false);
		}
	};

	const startIndex = async () => {
		if (selectedPostTypes.length === 0) {
			showNotice(
				__(
					'Please select at least one content type to index or enable website indexing',
					'dragwyb-click-to-chat'
				),
				'error'
			);
			return;
		}
		setIndexing(true);
		setIndexStatus(null);
		try {
			const res = await apiFetch({
				path: '/dctc-ai/v1/rag/index',
				method: 'POST',
				data: {
					post_types: [...selectedPostTypes],
					index_sources: {
						knowledge_text: true,
						urls: true,
						files: true,
						website: true,
					},
				},
			});
			if (!res.success) {
				showNotice(
					res.message ||
					__('Failed to start indexing', 'dragwyb-click-to-chat'),
					'error'
				);
				setIndexing(false);
				return;
			}
			showNotice(
				res.message ||
				__('Content indexing started!', 'dragwyb-click-to-chat')
			);
			if (res.status === 'queued') {
				pollIndex();
			} else {
				setIndexing(false);
				fetchStats();
			}
		} catch (err) {
			console.error('Indexing error:', err);
			showNotice(
				err.message || __('Failed to index content', 'dragwyb-click-to-chat'),
				'error'
			);
			setIndexing(false);
		}
	};

	const resetPinecone = async () => {
		setConfirmReset(false);
		setSaving(true);
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
			await apiFetch({
				path: '/dctc-ai/v1/rag/settings',
				method: 'POST',
				data: payload,
			});
			setPineconeKey('');
			setPineconeHost('');
			setPineconeIndex('');
			setSavedSnapshot((prev) => ({
				...prev,
				pineconeApiKey: '',
				pineconeHost: '',
				pineconeIndexName: '',
			}));
			onSave({
				rag: {
					...rag,
					vector_db: {
						...rag.vector_db,
						api_key: '',
						host: '',
						index_name: '',
					},
				},
			});
			showNotice(
				__('Pinecone settings reset successfully!', 'dragwyb-click-to-chat')
			);
		} catch (err) {
			showNotice(
				err.message ||
				__('Failed to reset Pinecone settings', 'dragwyb-click-to-chat'),
				'error'
			);
		} finally {
			setSaving(false);
		}
	};

	const togglePostType = (value) => {
		setSelectedPostTypes((prev) =>
			prev.includes(value)
				? prev.filter((v) => v !== value)
				: [...prev, value]
		);
	};

	return (
		<div className="dctc-ai-kb-settings">
			<header className="dctc-ai-section-header">
				<div className="dctc-ai-section-header__left">
					<div className="dctc-ai-section-header__icon-box">
						<span className="dashicons dashicons-database" />
					</div>
					<div>
						<div className="dctc-ai-section-header__title-row">
							<h1 className="dctc-ai-section-header__title">
								{__('Knowledge Base & Content Indexer', 'dragwyb-click-to-chat')}
							</h1>
							<span className="dctc-ai-status-pill is-active">
								{stats?.total_indexed ? sprintf(__('%d Documents Indexed', 'dragwyb-click-to-chat'), stats.total_indexed) : __('Vector Store Active', 'dragwyb-click-to-chat')}
							</span>
						</div>
						<p className="dctc-ai-section-header__desc">
							{subtab === 'sources'
								? __('Provide direct factual text, crawlable web URLs, and training documents for your chatbot to learn from.', 'dragwyb-click-to-chat')
								: __('Configure vector database storage (SQLite / Pinecone), chunk sizing, embedding models, and auto-sync.', 'dragwyb-click-to-chat')}
						</p>
					</div>
				</div>

				<div className="dctc-ai-section-header__right">
					<button
						type="button"
						className="dctc-ai-btn dctc-ai-btn-primary dctc-ai-btn-header-save"
						onClick={onSubmit}
						disabled={saving || !dirty}
					>
						<span className={`dashicons ${saving ? 'dashicons-update spin-anim' : 'dashicons-saved'}`} />
						{saving ? __('Saving…', 'dragwyb-click-to-chat') : dirty ? __('Save Changes', 'dragwyb-click-to-chat') : __('Saved', 'dragwyb-click-to-chat')}
					</button>
				</div>
			</header>

			<nav
				className="dctc-ai-subtab-nav"
				role="tablist"
				aria-label={__('Knowledge base sections', 'dragwyb-click-to-chat')}
				style={{ gridTemplateColumns: 'repeat(2, 1fr)' }}
			>
				{SUBTABS.map((tab) => (
					<button
						key={tab.id}
						type="button"
						role="tab"
						aria-selected={subtab === tab.id}
						className={`dctc-ai-subtab-btn ${subtab === tab.id ? 'active' : ''}`}
						onClick={() => setSubtab(tab.id)}
					>
						<span className={`dashicons ${tab.icon}`} aria-hidden="true" />
						<div className="dctc-ai-subtab-btn__content">
							<span className="dctc-ai-subtab-btn__label">{tab.label}</span>
							<span className="dctc-ai-subtab-btn__hint">{tab.desc}</span>
						</div>
					</button>
				))}
			</nav>

			<form onSubmit={onSubmit}>
				{subtab === 'sources' && (
					<SourcesSubtab
						knowledgeText={knowledgeText}
						setKnowledgeText={setKnowledgeText}
						urls={urls}
						setUrls={setUrls}
						trainingFiles={trainingFiles}
						setTrainingFiles={setTrainingFiles}
						showNotice={showNotice}
					/>
				)}

				{subtab === 'vector-db' && (
					<VectorDbSubtab
						availableTypes={availableTypes}
						selectedPostTypes={selectedPostTypes}
						togglePostType={togglePostType}
						autoUpdate={autoUpdate}
						setAutoUpdate={setAutoUpdate}
						indexing={indexing}
						indexStatus={indexStatus}
						progressPct={progressPct}
						progressLabel={progressLabel}
						vectorDb={vectorDb}
						setVectorDb={setVectorDb}
						pineconeKey={pineconeKey}
						setPineconeKey={setPineconeKey}
						pineconeHost={pineconeHost}
						setPineconeHost={setPineconeHost}
						pineconeIndex={pineconeIndex}
						setPineconeIndex={setPineconeIndex}
						hasPineconeSaved={hasPineconeSaved}
						setConfirmReset={setConfirmReset}
						embeddingProvider={embeddingProvider}
						setEmbeddingProvider={setEmbeddingProvider}
						embedInfo={embedInfo}
						startIndex={startIndex}
						minConfidence={minConfidence}
						setMinConfidence={setMinConfidence}
						requireIndexed={requireIndexed}
						setRequireIndexed={setRequireIndexed}
						noDataMessage={noDataMessage}
						setNoDataMessage={setNoDataMessage}
					/>
				)}
			</form>

			<ConfirmModal
				open={confirmReset}
				title={__('Reset Pinecone settings', 'dragwyb-click-to-chat')}
				message={__(
					'Are you sure you want to clear your Pinecone API Key, Host, and Index Name?',
					'dragwyb-click-to-chat'
				)}
				confirmLabel={__(
					'Reset Pinecone Settings',
					'dragwyb-click-to-chat'
				)}
				onCancel={() => setConfirmReset(false)}
				onConfirm={resetPinecone}
			/>
		</div>
	);
}
