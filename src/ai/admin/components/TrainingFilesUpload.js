import { __ } from '@wordpress/i18n';

export default function TrainingFilesUpload({
	files = [],
	onChange,
	showNotice,
}) {
	const handleUploadFiles = () => {
		if (window.wp && window.wp.media) {
			const frame = window.wp.media({
				title: __('Select Training Documents', 'dragwyb-click-to-chat'),
				button: { text: __('Attach Training Documents', 'dragwyb-click-to-chat') },
				multiple: true,
			});
			frame.on('select', () => {
				const selection = frame.state().get('selection').toJSON();
				const formatted = selection.map((f) => ({
					id: f.id,
					name: f.filename || f.title || `file-${f.id}`,
					url: f.url,
					mime: f.mime || f.type || '',
				}));
				onChange([...(files || []), ...formatted]);
				if (showNotice) {
					showNotice(__('Training documents attached.', 'dragwyb-click-to-chat'), 'info');
				}
			});
			frame.open();
		} else {
			const input = document.createElement('input');
			input.type = 'file';
			input.multiple = true;
			input.accept = '.pdf,.doc,.docx,.txt,.csv,.json,.md';
			input.onchange = (e) => {
				const selected = Array.from(e.target.files).map((f, idx) => ({
					id: Date.now() + idx,
					name: f.name,
					url: '',
					mime: f.type,
				}));
				onChange([...(files || []), ...selected]);
			};
			input.click();
		}
	};

	const handleRemoveFile = (indexToRemove) => {
		onChange((files || []).filter((_, idx) => idx !== indexToRemove));
	};

	return (
		<div className="dctc-ai-training-files-manager">
			{files && files.length > 0 ? (
				<div className="dctc-ai-file-chips-grid" style={{ display: 'flex', flexWrap: 'wrap', gap: '8px', marginBottom: '12px' }}>
					{files.map((file, idx) => {
						const fileName = typeof file === 'object' ? file.name : `File #${file}`;
						return (
							<div
								key={idx}
								className="dctc-ai-file-chip"
								style={{
									display: 'inline-flex',
									alignItems: 'center',
									gap: '6px',
									padding: '6px 12px',
									background: '#f1f5f9',
									border: '1px solid #cbd5e1',
									borderRadius: '6px',
									fontSize: '13px',
									color: '#1e293b',
								}}
							>
								<span className="dashicons dashicons-media-document" style={{ fontSize: '16px', color: '#6366f1' }} />
								<span style={{ maxWidth: '200px', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }} title={fileName}>
									{fileName}
								</span>
								<button
									type="button"
									onClick={() => handleRemoveFile(idx)}
									style={{
										background: 'transparent',
										border: 'none',
										color: '#94a3b8',
										cursor: 'pointer',
										padding: '0 2px',
										fontSize: '14px',
										lineHeight: 1,
									}}
									title={__('Remove file', 'dragwyb-click-to-chat')}
								>
									&times;
								</button>
							</div>
						);
					})}
				</div>
			) : (
				<p style={{ margin: '0 0 12px 0', fontSize: '13px', color: '#64748b' }}>
					{__('No training files attached yet.', 'dragwyb-click-to-chat')}
				</p>
			)}

			<button
				type="button"
				className="dctc-ai-btn dctc-ai-btn-secondary"
				onClick={handleUploadFiles}
				style={{ display: 'inline-flex', alignItems: 'center', gap: '6px' }}
			>
				<span className="dashicons dashicons-upload" />
				{__('Upload / Select Training Files', 'dragwyb-click-to-chat')}
			</button>
		</div>
	);
}
