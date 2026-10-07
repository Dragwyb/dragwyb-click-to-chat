import { __ } from '@wordpress/i18n';
import TrainingFilesUpload from '../../components/TrainingFilesUpload';

const TEXT_HINT = __(
	'Add facts, FAQs, or company info you want the chatbot to rely on when answering.',
	'dragwyb-click-to-chat'
);
const URL_HINT = __(
	'We will read these pages from time to time and use what we find in replies.',
	'dragwyb-click-to-chat'
);

export default function SourcesSubtab({
	knowledgeText,
	setKnowledgeText,
	urls,
	setUrls,
	trainingFiles,
	setTrainingFiles,
	showNotice,
}) {
	return (
		<div className="dctc-ai-tab-panel-section">
			<section className="dctc-ai-card">
				<header className="dctc-ai-card__header">
					<div className="dctc-ai-card__header-left">
						<div className="dctc-ai-card-icon">
							<span className="dashicons dashicons-media-text" />
						</div>
						<div>
							<h2 className="dctc-ai-card__title">
								{__('Direct Text Knowledge', 'dragwyb-click-to-chat')}
							</h2>
							<p className="dctc-ai-card__desc">{TEXT_HINT}</p>
						</div>
					</div>
				</header>
				<div className="dctc-ai-card__body">
					<textarea
						className="dctc-ai-bot-textarea"
						rows="8"
						value={knowledgeText}
						onChange={(e) => setKnowledgeText(e.target.value)}
						placeholder={__(
							'Enter factual information directly…',
							'dragwyb-click-to-chat'
						)}
					/>
				</div>
			</section>

			<section className="dctc-ai-card">
				<header className="dctc-ai-card__header">
					<div className="dctc-ai-card__header-left">
						<div className="dctc-ai-card-icon">
							<span className="dashicons dashicons-admin-site-alt3" />
						</div>
						<div>
							<h2 className="dctc-ai-card__title">
								{__('Web Pages (URLs)', 'dragwyb-click-to-chat')}
							</h2>
							<p className="dctc-ai-card__desc">{URL_HINT}</p>
						</div>
					</div>
				</header>
				<div className="dctc-ai-card__body">
					<div className="dctc-ai-kb-url-list">
						{urls.map((url, idx) => (
							<div key={idx} className="dctc-ai-kb-url-item">
								<span
									className="dctc-ai-kb-url-item__icon dashicons dashicons-admin-links"
									aria-hidden="true"
								/>
								<input
									type="url"
									className="dctc-ai-bot-input"
									value={url}
									onChange={(e) => {
										const value = e.target.value;
										setUrls((prev) => {
											const next = [...prev];
											next[idx] = value;
											return next;
										});
									}}
									placeholder="https://example.com/page"
								/>
								<button
									type="button"
									className="dctc-ai-kb-url-remove"
									onClick={() =>
										setUrls((prev) =>
											prev.filter((_, i) => i !== idx)
										)
									}
									aria-label={__(
										'Remove URL',
										'dragwyb-click-to-chat'
									)}
								>
									<span
										className="dashicons dashicons-trash"
										aria-hidden="true"
									/>
								</button>
							</div>
						))}
					</div>
					<button
						type="button"
						className="dctc-ai-btn-add-url"
						onClick={() => setUrls((prev) => [...prev, ''])}
					>
						<span
							className="dashicons dashicons-plus-alt2"
							aria-hidden="true"
						/>
						{__('Add Another Page URL', 'dragwyb-click-to-chat')}
					</button>
				</div>
			</section>

			<section className="dctc-ai-card">
				<header className="dctc-ai-card__header">
					<div className="dctc-ai-card__header-left">
						<div className="dctc-ai-card-icon">
							<span className="dashicons dashicons-media-document" />
						</div>
						<div>
							<h2 className="dctc-ai-card__title">
								{__('Training Documents (Files)', 'dragwyb-click-to-chat')}
							</h2>
							<p className="dctc-ai-card__desc">
								{__(
									'Upload PDFs, Word docs, CSVs, or text files to train the assistant.',
									'dragwyb-click-to-chat'
								)}
							</p>
						</div>
					</div>
				</header>
				<div className="dctc-ai-card__body">
					<TrainingFilesUpload
						files={trainingFiles}
						onChange={setTrainingFiles}
						showNotice={showNotice}
					/>
				</div>
			</section>
		</div>
	);
}
