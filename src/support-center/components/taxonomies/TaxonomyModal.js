/**
 * TaxonomyModal: Add / Edit Custom Support Taxonomy
 */
import { __ } from '@wordpress/i18n';

export default function TaxonomyModal({
	open,
	editingTax,
	taxForm,
	setTaxForm,
	onSave,
	onClose,
	saving,
	generateSlug,
	DASHICON_PRESETS,
	COLOR_PRESETS,
	onOpenMedia,
}) {
	if (!open) return null;

	return (
		<div className="dctc-sc-modal-backdrop" onClick={onClose}>
			<div className="dctc-sc-modal-card" onClick={(e) => e.stopPropagation()}>
				<div className="dctc-sc-modal-top-header">
					<div className="dctc-sc-modal-icon-badge" style={{ background: `linear-gradient(135deg, ${taxForm.color || '#4f46e5'} 0%, #6366f1 100%)` }}>
						{taxForm.image_url ? (
							<img src={taxForm.image_url} alt="" style={{ width: '22px', height: '22px', borderRadius: '4px', objectFit: 'cover' }} />
						) : (
							<span className={`dashicons ${taxForm.icon_dashicon || 'dashicons-category'}`}></span>
						)}
					</div>
					<div className="dctc-sc-modal-title-wrap">
						<h3 className="dctc-sc-modal-title">
							{editingTax ? __('Edit Custom Taxonomy', 'dragwyb-click-to-chat') : __('Add New Support Taxonomy', 'dragwyb-click-to-chat')}
						</h3>
						<p className="dctc-sc-modal-desc">
							{__('Define custom classification dimensions and route support queries with dynamic terms.', 'dragwyb-click-to-chat')}
						</p>
					</div>
					<button
						type="button"
						className="dctc-sc-modal-close-btn"
						onClick={onClose}
						title={__('Close', 'dragwyb-click-to-chat')}
					>
						<span className="dashicons dashicons-no-alt"></span>
					</button>
				</div>

				<form onSubmit={onSave} className="dctc-sc-modal-form">
					<div className="dctc-sc-modal-body-scroll">
						{/* Taxonomy Name & Slug */}
						<div className="dctc-sc-form-grid-2">
							<div className="dctc-sc-form-group">
								<label className="dctc-sc-field-label">
									<span className="dashicons dashicons-tag"></span>
									{__('Taxonomy Name', 'dragwyb-click-to-chat')}
									<span className="dctc-sc-required-star">*</span>
								</label>
								<input
									type="text"
									required
									className="dctc-sc-custom-input"
									placeholder="e.g. Departments, Hardware, Platforms"
									value={taxForm.name}
									onChange={(e) => {
										const val = e.target.value;
										setTaxForm({
											...taxForm,
											name: val,
											slug: !editingTax && (!taxForm.slug || taxForm.slug === generateSlug(taxForm.name)) ? generateSlug(val) : taxForm.slug,
										});
									}}
								/>
							</div>

							<div className="dctc-sc-form-group">
								<label className="dctc-sc-field-label">
									<span className="dashicons dashicons-admin-links"></span>
									{__('Slug Identifier', 'dragwyb-click-to-chat')}
								</label>
								<input
									type="text"
									className="dctc-sc-custom-input"
									placeholder="e.g. department"
									value={taxForm.slug}
									onChange={(e) => setTaxForm({ ...taxForm, slug: generateSlug(e.target.value) })}
								/>
							</div>
						</div>

						{/* Icon Type Tabs */}
						<div className="dctc-sc-form-group">
							<label className="dctc-sc-field-label">
								<span className="dashicons dashicons-art"></span>
								{__('Taxonomy Icon Type', 'dragwyb-click-to-chat')}
							</label>
							<div className="dctc-sc-segmented-tabs">
								<button
									type="button"
									className={`dctc-sc-segment-tab ${(taxForm.icon_type || (taxForm.image_url ? 'custom' : 'preset')) === 'preset' ? 'is-active' : ''}`}
									onClick={() => setTaxForm({ ...taxForm, icon_type: 'preset', icon_dashicon: taxForm.icon_dashicon || 'dashicons-category' })}
								>
									<span className="dashicons dashicons-marker"></span>
									{__('Preset Icons', 'dragwyb-click-to-chat')}
								</button>
								<button
									type="button"
									className={`dctc-sc-segment-tab ${(taxForm.icon_type || (taxForm.image_url ? 'custom' : 'preset')) === 'custom' ? 'is-active' : ''}`}
									onClick={() => setTaxForm({ ...taxForm, icon_type: 'custom' })}
								>
									<span className="dashicons dashicons-upload"></span>
									{__('Custom Icon / Upload', 'dragwyb-click-to-chat')}
								</button>
								<button
									type="button"
									className={`dctc-sc-segment-tab ${taxForm.icon_type === 'none' ? 'is-active' : ''}`}
									onClick={() => setTaxForm({ ...taxForm, icon_type: 'none', image_url: '', icon_dashicon: '' })}
								>
									<span className="dashicons dashicons-dismiss"></span>
									{__('No Icon', 'dragwyb-click-to-chat')}
								</button>
							</div>
						</div>

						{/* Condition 1: Preset Dashicons */}
						{(taxForm.icon_type || (taxForm.image_url ? 'custom' : 'preset')) === 'preset' && (
							<div className="dctc-sc-form-group">
								<label className="dctc-sc-field-label">
									<span className="dashicons dashicons-admin-appearance"></span>
									{__('Select Preset Dashicon', 'dragwyb-click-to-chat')}
								</label>
								<div className="dctc-sc-icon-grid">
									{DASHICON_PRESETS.map((iconClass) => (
										<button
											key={iconClass}
											type="button"
											className={`dctc-sc-icon-tile ${(!taxForm.image_url && taxForm.icon_dashicon === iconClass) ? 'is-active' : ''}`}
											onClick={() => setTaxForm({ ...taxForm, icon_dashicon: iconClass, image_url: '', icon_type: 'preset' })}
											title={iconClass}
										>
											<span className={`dashicons ${iconClass}`}></span>
										</button>
									))}
								</div>
							</div>
						)}

						{/* Condition 2: Custom Image Upload */}
						{(taxForm.icon_type || (taxForm.image_url ? 'custom' : 'preset')) === 'custom' && (
							<div className="dctc-sc-form-group">
								<label className="dctc-sc-field-label">
									<span className="dashicons dashicons-upload"></span>
									{__('Upload Custom Icon or Image', 'dragwyb-click-to-chat')}
								</label>
								<div className="dctc-sc-media-upload-box">
									<div className="dctc-sc-media-preview-wrap">
										{taxForm.image_url ? (
											<img src={taxForm.image_url} alt="Preview" className="dctc-sc-media-preview-img" />
										) : (
											<span className="dashicons dashicons-format-image" style={{ color: '#94a3b8', fontSize: '22px' }}></span>
										)}
									</div>
									<div className="dctc-sc-media-upload-actions">
										<button
											type="button"
											className="dctc-sc-upload-trigger-btn"
											onClick={onOpenMedia}
										>
											<span className="dashicons dashicons-admin-media"></span>
											{taxForm.image_url ? __('Change Image / Icon', 'dragwyb-click-to-chat') : __('Choose from Media Library', 'dragwyb-click-to-chat')}
										</button>
										{taxForm.image_url && (
											<button
												type="button"
												className="dctc-sc-remove-media-link"
												onClick={() => setTaxForm({ ...taxForm, image_url: '' })}
											>
												{__('Remove custom image', 'dragwyb-click-to-chat')}
											</button>
										)}
									</div>
								</div>
							</div>
						)}

						{/* Color Theme */}
						<div className="dctc-sc-form-group">
							<label className="dctc-sc-field-label">
								<span className="dashicons dashicons-art"></span>
								{__('Accent Color Theme', 'dragwyb-click-to-chat')}
							</label>
							<div className="dctc-sc-color-picker-wrap">
								<div className="dctc-sc-color-swatches-row">
									{COLOR_PRESETS.map((c) => (
										<button
											key={c}
											type="button"
											className={`dctc-sc-color-swatch ${taxForm.color === c ? 'is-selected' : ''}`}
											style={{ backgroundColor: c }}
											onClick={() => setTaxForm({ ...taxForm, color: c })}
											title={c}
										/>
									))}
								</div>
								<div className="dctc-sc-native-color-wrap">
									<input
										type="color"
										className="dctc-sc-native-color-btn"
										value={taxForm.color || '#4F46E5'}
										onChange={(e) => setTaxForm({ ...taxForm, color: e.target.value })}
									/>
									<input
										type="text"
										className="dctc-sc-custom-input"
										style={{ width: '120px' }}
										value={taxForm.color}
										onChange={(e) => setTaxForm({ ...taxForm, color: e.target.value })}
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
								placeholder={__('What is this taxonomy used for?', 'dragwyb-click-to-chat')}
								value={taxForm.description}
								onChange={(e) => setTaxForm({ ...taxForm, description: e.target.value })}
							/>
						</div>
					</div>

					<div className="dctc-sc-modal-footer-bar">
						<button type="button" className="dctc-sc-btn-cancel" onClick={onClose}>
							{__('Cancel', 'dragwyb-click-to-chat')}
						</button>
						<button type="submit" className="dctc-sc-btn-submit" disabled={saving}>
							{saving ? (
								<>
									<span className="spinner is-active" style={{ margin: 0, float: 'none' }}></span>
									{__('Saving...', 'dragwyb-click-to-chat')}
								</>
							) : (
								<>
									<span className="dashicons dashicons-saved" style={{ fontSize: '17px', lineHeight: '1' }}></span>
									{editingTax ? __('Update Taxonomy', 'dragwyb-click-to-chat') : __('Create Taxonomy', 'dragwyb-click-to-chat')}
								</>
							)}
						</button>
					</div>
				</form>
			</div>
		</div>
	);
}
