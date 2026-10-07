/**
 * CustomTermModal: Add / Edit Custom Taxonomy Item/Term
 */
import { __ } from '@wordpress/i18n';

export default function CustomTermModal({
	open,
	editingTerm,
	activeTax,
	termForm,
	setTermForm,
	onSave,
	onClose,
	saving,
	generateSlug,
	COLOR_PRESETS,
}) {
	if (!open) return null;

	return (
		<div className="dctc-sc-modal-backdrop" onClick={onClose}>
			<div className="dctc-sc-modal-card" onClick={(e) => e.stopPropagation()}>
				<div className="dctc-sc-modal-top-header">
					<div className="dctc-sc-modal-icon-badge" style={{ background: `linear-gradient(135deg, ${termForm.color || activeTax?.color || '#4f46e5'} 0%, #6366f1 100%)` }}>
						{activeTax?.image_url ? (
							<img src={activeTax.image_url} alt="" style={{ width: '22px', height: '22px', borderRadius: '4px', objectFit: 'cover' }} />
						) : (
							<span className={`dashicons ${activeTax?.icon_dashicon || activeTax?.icon || 'dashicons-category'}`}></span>
						)}
					</div>
					<div className="dctc-sc-modal-title-wrap">
						<h3 className="dctc-sc-modal-title">
							{editingTerm
								? __(`Edit ${activeTax?.name || 'Taxonomy'} Item`, 'dragwyb-click-to-chat')
								: __(`Add New ${activeTax?.name || 'Taxonomy'} Item`, 'dragwyb-click-to-chat')}
						</h3>
						<p className="dctc-sc-modal-desc">
							{__(`Create a classified term under the ${activeTax?.name} taxonomy.`, 'dragwyb-click-to-chat')}
						</p>
					</div>
					<button type="button" className="dctc-sc-modal-close-btn" onClick={onClose} title={__('Close', 'dragwyb-click-to-chat')}>
						<span className="dashicons dashicons-no-alt"></span>
					</button>
				</div>

				<form onSubmit={onSave} className="dctc-sc-modal-form">
					<div className="dctc-sc-modal-body-scroll">
						<div className="dctc-sc-form-grid-2">
							<div className="dctc-sc-form-group">
								<label className="dctc-sc-field-label">
									<span className="dashicons dashicons-tag"></span>
									{__('Item Name', 'dragwyb-click-to-chat')}
									<span className="dctc-sc-required-star">*</span>
								</label>
								<input
									type="text"
									required
									className="dctc-sc-custom-input"
									placeholder={`e.g. ${activeTax?.name || 'Term'} Name`}
									value={termForm.name}
									onChange={(e) => {
										const val = e.target.value;
										setTermForm({
											...termForm,
											name: val,
											slug: !editingTerm && (!termForm.slug || termForm.slug === generateSlug(termForm.name)) ? generateSlug(val) : termForm.slug,
										});
									}}
								/>
							</div>

							<div className="dctc-sc-form-group">
								<label className="dctc-sc-field-label">
									<span className="dashicons dashicons-admin-links"></span>
									{__('Slug', 'dragwyb-click-to-chat')}
								</label>
								<input
									type="text"
									className="dctc-sc-custom-input"
									placeholder="e.g. item-slug"
									value={termForm.slug}
									onChange={(e) => setTermForm({ ...termForm, slug: generateSlug(e.target.value) })}
								/>
							</div>
						</div>

						{/* Color Picker */}
						<div className="dctc-sc-form-group">
							<label className="dctc-sc-field-label">
								<span className="dashicons dashicons-art"></span>
								{__('Color Theme', 'dragwyb-click-to-chat')}
							</label>
							<div className="dctc-sc-color-picker-wrap">
								<div className="dctc-sc-color-swatches-row">
									{COLOR_PRESETS.map((c) => (
										<button
											key={c}
											type="button"
											className={`dctc-sc-color-swatch ${termForm.color === c ? 'is-selected' : ''}`}
											style={{ backgroundColor: c }}
											onClick={() => setTermForm({ ...termForm, color: c })}
											title={c}
										/>
									))}
								</div>
								<div className="dctc-sc-native-color-wrap">
									<input
										type="color"
										className="dctc-sc-native-color-btn"
										value={termForm.color || '#4F46E5'}
										onChange={(e) => setTermForm({ ...termForm, color: e.target.value })}
									/>
									<input
										type="text"
										className="dctc-sc-custom-input"
										style={{ width: '110px' }}
										value={termForm.color}
										onChange={(e) => setTermForm({ ...termForm, color: e.target.value })}
									/>
								</div>
							</div>
						</div>

						{/* Description */}
						<div className="dctc-sc-form-group">
							<label className="dctc-sc-field-label">
								<span className="dashicons dashicons-editor-paragraph"></span>
								{__('Description (Optional)', 'dragwyb-click-to-chat')}
							</label>
							<textarea
								rows="2"
								className="dctc-sc-custom-textarea"
								placeholder={__('Optional description for this item...', 'dragwyb-click-to-chat')}
								value={termForm.description}
								onChange={(e) => setTermForm({ ...termForm, description: e.target.value })}
							/>
						</div>
					</div>

					<div className="dctc-sc-modal-footer-bar">
						<button type="button" className="dctc-sc-btn-cancel" onClick={onClose}>
							{__('Cancel', 'dragwyb-click-to-chat')}
						</button>
						<button type="submit" className="dctc-sc-btn-submit" disabled={saving}>
							{saving ? __('Saving...', 'dragwyb-click-to-chat') : (editingTerm ? __('Update Item', 'dragwyb-click-to-chat') : __('Save Item', 'dragwyb-click-to-chat'))}
						</button>
					</div>
				</form>
			</div>
		</div>
	);
}
