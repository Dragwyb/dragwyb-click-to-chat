import { __ } from '@wordpress/i18n';
import { VECTOR_DB_OPTIONS } from '../../utils/providers';

const DEFAULT_NO_DATA =
	"I don't have information about your question in my knowledge base. Please rephrase or ask about topics I have knowledge of.";

export default function VectorDbSubtab({
	availableTypes = [],
	selectedPostTypes = [],
	togglePostType,
	autoUpdate,
	setAutoUpdate,
	indexing,
	startIndex,
	indexStatus,
	progressPct,
	progressLabel,
	vectorDb,
	setVectorDb,
	pineconeKey,
	setPineconeKey,
	pineconeHost,
	setPineconeHost,
	pineconeIndex,
	setPineconeIndex,
	hasPineconeSaved,
	setConfirmReset,
	embeddingProvider,
	setEmbeddingProvider,
	embedInfo,
	minConfidence,
	setMinConfidence,
	requireIndexed,
	setRequireIndexed,
	noDataMessage,
	setNoDataMessage,
}) {
	return (
		<div className="dctc-ai-tab-panel-section">
			{/* Website Content to Index */}
			<section className="dctc-ai-card">
				<header className="dctc-ai-card__header">
					<div className="dctc-ai-card__header-left">
						<div className="dctc-ai-card-icon">
							<span className="dashicons dashicons-admin-post" />
						</div>
						<div>
							<h2 className="dctc-ai-card__title">
								{__('Website Content to Index', 'dragwyb-click-to-chat')}
							</h2>
							<p className="dctc-ai-card__desc">
								{__(
									'Select which post types to include. Leave all unchecked to skip indexing your website content.',
									'dragwyb-click-to-chat'
								)}
							</p>
						</div>
					</div>
				</header>
				<div className="dctc-ai-card__body">
					<div className="dctc-ai-kb-post-types">
						{availableTypes.length > 0 ? (
							availableTypes.map((type) => (
								<label key={type.value} className="dctc-ai-checkbox">
									<input
										type="checkbox"
										checked={selectedPostTypes.includes(type.value)}
										onChange={() => togglePostType(type.value)}
									/>
									<span className="dctc-ai-checkbox__label">
										{type.label} ({type.count})
									</span>
								</label>
							))
						) : (
							<p className="dctc-ai-hint">
								{__('No post types available.', 'dragwyb-click-to-chat')}
							</p>
						)}
					</div>
					<div className="dctc-ai-kb-auto-sync-opt" style={{ marginTop: '16px', paddingTop: '16px', borderTop: '1px solid #f1f5f9' }}>
						<label className="dctc-ai-checkbox" style={{ display: 'flex', alignItems: 'flex-start', gap: '8px' }}>
							<input
								type="checkbox"
								checked={autoUpdate}
								onChange={(e) => setAutoUpdate(e.target.checked)}
								style={{ marginTop: '2px' }}
							/>
							<span className="dctc-ai-checkbox__label">
								<strong>{__('Auto-Sync Content Updates', 'dragwyb-click-to-chat')}</strong>
								<br />
								<small style={{ color: '#64748b' }}>
									{__(
										'Automatically index or update posts and WooCommerce products in the background when published or edited, and purge them when moved to trash or unpublished.',
										'dragwyb-click-to-chat'
									)}
								</small>
							</span>
						</label>
					</div>
					<div className="dctc-ai-kb-sync-actions" style={{ marginTop: '16px', display: 'flex', alignItems: 'center', gap: '12px' }}>
						{selectedPostTypes.length > 0 && startIndex && (
							<button
								type="button"
								className="dctc-ai-btn dctc-ai-btn-secondary"
								onClick={startIndex}
								disabled={indexing}
							>
								<span
									className={`dashicons dashicons-update ${indexing ? 'dctc-ai-spin' : ''}`}
									aria-hidden="true"
								/>{' '}
								{indexing
									? __('Indexing Content…', 'dragwyb-click-to-chat')
									: __('Index Content Now', 'dragwyb-click-to-chat')}
							</button>
						)}
					</div>

					{indexing && indexStatus && (
						<div className="dctc-ai-kb-index-progress" role="status" style={{ marginTop: '16px' }}>
							<div className="dctc-ai-kb-index-progress__bar">
								<div
									className="dctc-ai-kb-index-progress__fill"
									style={{ width: `${progressPct}%` }}
								/>
							</div>
							<p className="dctc-ai-kb-index-progress__label">
								{progressLabel}
							</p>
						</div>
					)}
				</div>
			</section>

			{/* Database Storage Engine */}
			<section className="dctc-ai-card">
				<header className="dctc-ai-card__header">
					<div className="dctc-ai-card__header-left">
						<div className="dctc-ai-card-icon">
							<span className="dashicons dashicons-database" />
						</div>
						<div>
							<h2 className="dctc-ai-card__title">
								{__('Database Storage Engine', 'dragwyb-click-to-chat')}
							</h2>
							<p className="dctc-ai-card__desc">
								{__(
									'Choose where to store your document embeddings for semantic search.',
									'dragwyb-click-to-chat'
								)}
							</p>
						</div>
					</div>
				</header>
				<div className="dctc-ai-card__body">
					<div className="dctc-ai-kb-db-options">
						{VECTOR_DB_OPTIONS.map((opt) => (
							<label key={opt.value} className="dctc-ai-radio-card">
								<input
									type="radio"
									name="vector_db"
									value={opt.value}
									checked={vectorDb === opt.value}
									onChange={(e) => setVectorDb(e.target.value)}
								/>
								<span className="dctc-ai-radio-card__label">
									<strong>{opt.label}</strong>
									<br />
									<small>{opt.desc}</small>
								</span>
							</label>
						))}
					</div>
				</div>
			</section>

			{/* Pinecone Configuration */}
			{vectorDb === 'pinecone' && (
				<section className="dctc-ai-card dctc-ai-kb-pinecone-card">
					<header className="dctc-ai-card__header dctc-ai-kb-pinecone-header">
						<div className="dctc-ai-card__header-left">
							<div className="dctc-ai-card-icon">
								<span className="dashicons dashicons-cloud" />
							</div>
							<div>
								<h2 className="dctc-ai-card__title dctc-ai-kb-pinecone-title">
									{__('Pinecone Configuration', 'dragwyb-click-to-chat')}
								</h2>
								<p className="dctc-ai-card__desc">
									{__(
										'Connect your Pinecone cloud vector database.',
										'dragwyb-click-to-chat'
									)}{' '}
									<a
										className="dctc-ai-kb-pinecone-console-link"
										href="https://app.pinecone.io/"
										target="_blank"
										rel="noopener noreferrer"
									>
										{__('Open Pinecone Console', 'dragwyb-click-to-chat')}{' '}
										→
									</a>
								</p>
							</div>
						</div>
						{hasPineconeSaved && (
							<button
								type="button"
								className="dctc-ai-btn dctc-ai-btn-secondary dctc-ai-btn-sm dctc-ai-kb-pinecone-reset-btn"
								onClick={() => setConfirmReset(true)}
							>
								{__('Reset Settings', 'dragwyb-click-to-chat')}
							</button>
						)}
					</header>
					<div className="dctc-ai-card__body">
						<div className="dctc-ai-bot-field">
							<label className="dctc-ai-label">
								{__('Pinecone API Key', 'dragwyb-click-to-chat')}
							</label>
							<input
								type="password"
								className="dctc-ai-bot-input"
								value={pineconeKey}
								onChange={(e) => setPineconeKey(e.target.value)}
								placeholder="pcsk_..."
							/>
							<p className="dctc-ai-bot-hint">
								{__(
									'You can generate an API key from the "API Keys" section in your Pinecone dashboard.',
									'dragwyb-click-to-chat'
								)}
							</p>
						</div>
						<div className="dctc-ai-bot-field">
							<label className="dctc-ai-label">
								{__('Pinecone Host', 'dragwyb-click-to-chat')}
							</label>
							<input
								type="text"
								className="dctc-ai-bot-input"
								value={pineconeHost}
								onChange={(e) => setPineconeHost(e.target.value)}
								placeholder="https://index-xxxxx.svc.aped-4627-b74a.pinecone.io"
							/>
							<p className="dctc-ai-bot-hint">
								{__(
									'The host URL for your index. Find this by clicking on your index in the Pinecone dashboard.',
									'dragwyb-click-to-chat'
								)}
							</p>
						</div>
						<div className="dctc-ai-bot-field">
							<label className="dctc-ai-label">
								{__('Index Name', 'dragwyb-click-to-chat')}
							</label>
							<input
								type="text"
								className="dctc-ai-bot-input"
								value={pineconeIndex}
								onChange={(e) => setPineconeIndex(e.target.value)}
								placeholder="e.g. dctc-ai-index"
							/>
							<p className="dctc-ai-bot-hint">
								{__(
									'The exact name of the index you created.',
									'dragwyb-click-to-chat'
								)}
							</p>
						</div>
					</div>
				</section>
			)}

			{/* Embedding Configuration */}
			<section className="dctc-ai-card">
				<header className="dctc-ai-card__header">
					<div className="dctc-ai-card__header-left">
						<div className="dctc-ai-card-icon">
							<span className="dashicons dashicons-lightbulb" />
						</div>
						<div>
							<h2 className="dctc-ai-card__title">
								{__('Embedding Configuration', 'dragwyb-click-to-chat')}
							</h2>
							<p className="dctc-ai-card__desc">
								{__(
									'Select the AI provider to generate vector embeddings.',
									'dragwyb-click-to-chat'
								)}
							</p>
						</div>
					</div>
				</header>
				<div className="dctc-ai-card__body">
					<div className="dctc-ai-bot-field">
						<label className="dctc-ai-label">
							{__('Embedding Provider', 'dragwyb-click-to-chat')}
						</label>
						<select
							className="dctc-ai-bot-select"
							value={embeddingProvider}
							onChange={(e) => setEmbeddingProvider(e.target.value)}
						>
							<option value="openai">
								{__('OpenAI (text-embedding-3-small)', 'dragwyb-click-to-chat')}
							</option>
							<option value="google">
								{__('Google Gemini (gemini-embedding-001)', 'dragwyb-click-to-chat')}
							</option>
						</select>
						<p className="dctc-ai-bot-hint">
							{__(
								'Select the provider to use for processing your knowledge base into vectors.',
								'dragwyb-click-to-chat'
							)}
						</p>
					</div>
					<div className="dctc-ai-kb-info-block" style={{ marginTop: '1rem', padding: '1rem', background: '#f8fafc', borderRadius: '8px', border: '1px solid #e2e8f0' }}>
						<p style={{ margin: '0 0 0.5rem 0' }}>
							<strong>{__('Status:', 'dragwyb-click-to-chat')}</strong>{' '}
							{embedInfo.key ? (
								<span style={{ color: '#059669', fontWeight: 600 }}>
									✓ {__('API Key Configured', 'dragwyb-click-to-chat')}
								</span>
							) : (
								<span style={{ color: '#dc2626', fontWeight: 600 }}>
									✗ {__('API Key Missing', 'dragwyb-click-to-chat')}
								</span>
							)}
						</p>
						<p style={{ margin: 0 }}>
							<strong>{__('Required Index Dimensions:', 'dragwyb-click-to-chat')}</strong>{' '}
							{embedInfo.dimensions}
						</p>
					</div>
				</div>
			</section>

			{/* Hallucination Protection & Fallback */}
			<section className="dctc-ai-card">
				<header className="dctc-ai-card__header">
					<div className="dctc-ai-card__header-left">
						<div className="dctc-ai-card-icon">
							<span className="dashicons dashicons-shield" />
						</div>
						<div>
							<h2 className="dctc-ai-card__title">
								{__('Hallucination Protection & Fallback', 'dragwyb-click-to-chat')}
							</h2>
							<p className="dctc-ai-card__desc">
								{__(
									'Control confidence thresholds and prevent the AI from inventing facts when knowledge base evidence is insufficient.',
									'dragwyb-click-to-chat'
								)}
							</p>
						</div>
					</div>
				</header>
				<div className="dctc-ai-card__body">
					<div className="dctc-ai-bot-field">
						<label className="dctc-ai-label">
							{__('Minimum Evidence Confidence Threshold', 'dragwyb-click-to-chat')}
						</label>
						<select
							className="dctc-ai-bot-select"
							value={minConfidence}
							onChange={(e) => setMinConfidence(parseFloat(e.target.value))}
						>
							<option value={0.75}>{__('Strict (0.75) — Highest grounding, minimal hallucination', 'dragwyb-click-to-chat')}</option>
							<option value={0.65}>{__('Balanced (0.65) — Recommended for most websites', 'dragwyb-click-to-chat')}</option>
							<option value={0.50}>{__('Permissive (0.50) — Tolerates looser matches', 'dragwyb-click-to-chat')}</option>
						</select>
						<p className="dctc-ai-bot-hint">
							{__(
								'Retrieved chunks with a similarity score below this threshold are filtered out to prevent answering from weak or irrelevant context.',
								'dragwyb-click-to-chat'
							)}
						</p>
					</div>

					<div style={{ marginTop: '16px', paddingTop: '16px', borderTop: '1px solid #f1f5f9' }}>
						<label className="dctc-ai-checkbox" style={{ display: 'flex', alignItems: 'flex-start', gap: '8px' }}>
							<input
								type="checkbox"
								checked={requireIndexed}
								onChange={(e) => setRequireIndexed(e.target.checked)}
								style={{ marginTop: '2px' }}
							/>
							<span className="dctc-ai-checkbox__label">
								<strong>{__('Strict Knowledge Base Mode', 'dragwyb-click-to-chat')}</strong>
								<br />
								<small style={{ color: '#64748b' }}>
									{__(
										'Only answer if sufficient verified evidence is found in your knowledge base. When insufficient, trigger fallback with human support handoff.',
										'dragwyb-click-to-chat'
									)}
								</small>
							</span>
						</label>
					</div>

					{requireIndexed && (
						<div className="dctc-ai-bot-field" style={{ marginTop: '16px', paddingTop: '16px', borderTop: '1px solid #f1f5f9' }}>
							<label className="dctc-ai-label">
								{__('Fallback / Unknown-Answer Message', 'dragwyb-click-to-chat')}
							</label>
							<textarea
								className="dctc-ai-bot-textarea"
								rows={3}
								value={noDataMessage}
								onChange={(e) => setNoDataMessage(e.target.value)}
								placeholder={DEFAULT_NO_DATA}
							/>
							<p className="dctc-ai-bot-hint">
								{__(
									'Message shown to visitors when no verified evidence is found. Support handoff buttons (WhatsApp / Contact) will be offered alongside this message.',
									'dragwyb-click-to-chat'
								)}
							</p>
						</div>
					)}
				</div>
			</section>
		</div>
	);
}
