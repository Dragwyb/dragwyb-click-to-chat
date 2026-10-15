/**
 * TagModal: Add / Edit Support Tag
 */
import { __ } from '@wordpress/i18n';

export default function TagModal({
	open,
	editingTag,
	tagForm,
	setTagForm,
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
					<div className="dctc-sc-modal-icon-badge" style={{ background: `linear-gradient(135deg, ${tagForm.color || '#D97706'} 0%, #f59e0b 100%)` }}>
						<span className="dashicons dashicons-tag"></span>
					</div>
					<div className="dctc-sc-modal-title-wrap">
						<h3 className="dctc-sc-modal-title">{editingTag ? __('Edit Support Tag', 'dragwyb-click-to-chat') : __('Add New Tag', 'dragwyb-click-to-chat')}</h3>
						<p className="dctc-sc-modal-desc">{__('Tags allow customers and agents to pinpoint precise sub-topics and issue badges.', 'dragwyb-click-to-chat')}</p>
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
									{__('Tag Name', 'dragwyb-click-to-chat')}
									<span className="dctc-sc-required-star">*</span>
								</label>
								<input
									type="text"
									required
									className="dctc-sc-custom-input"
									placeholder="e.g. Refund Request"
									value={tagForm.name}
									onChange={(e) => {
										const val = e.target.value;
										setTagForm({
											...tagForm,
											name: val,
											slug: !editingTag && (!tagForm.slug || tagForm.slug === generateSlug(tagForm.name)) ? generateSlug(val) : tagForm.slug,
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
									placeholder="e.g. refund-request"
									value={tagForm.slug}
									onChange={(e) => setTagForm({ ...tagForm, slug: generateSlug(e.target.value) })}
								/>
							</div>
						</div>

						{/* Color Swatches */}
						<div className="dctc-sc-form-group">
							<label className="dctc-sc-field-label">
								<span className="dashicons dashicons-art"></span>
								{__('Badge Color', 'dragwyb-click-to-chat')}
							</label>
							<div className="dctc-sc-color-picker-wrap">
								<div className="dctc-sc-color-swatches-row">
									{COLOR_PRESETS.map((c) => (
										<button
											key={c}
											type="button"
											className={`dctc-sc-color-swatch ${tagForm.color === c ? 'is-selected' : ''}`}
											style={{ backgroundColor: c }}
											onClick={() => setTagForm({ ...tagForm, color: c })}
											title={c}
										/>
									))}
								</div>
								<div className="dctc-sc-native-color-wrap">
									<input
										type="color"
										className="dctc-sc-native-color-btn"
										value={tagForm.color || '#4F46E5'}
										onChange={(e) => setTagForm({ ...tagForm, color: e.target.value })}
									/>
									<input
										type="text"
										className="dctc-sc-custom-input"
										style={{ width: '110px' }}
										value={tagForm.color}
										onChange={(e) => setTagForm({ ...tagForm, color: e.target.value })}
									/>
								</div>
							</div>
						</div>
					</div>

					<div className="dctc-sc-modal-footer-bar">
						<button type="button" className="dctc-sc-btn-cancel" onClick={onClose}>
							{__('Cancel', 'dragwyb-click-to-chat')}
						</button>
						<button type="submit" className="dctc-sc-btn-submit" disabled={saving}>
							{saving ? __('Saving...', 'dragwyb-click-to-chat') : (editingTag ? __('Update Tag', 'dragwyb-click-to-chat') : __('Create Tag', 'dragwyb-click-to-chat'))}
						</button>
					</div>
				</form>
			</div>
		</div>
	);
}
