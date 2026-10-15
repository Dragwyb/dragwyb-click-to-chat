/**
 * ProductModal: Add / Edit Product
 */
import { __ } from '@wordpress/i18n';

export default function ProductModal({
	open,
	editingProd,
	prodForm,
	setProdForm,
	onSave,
	onClose,
	saving,
	generateSlug,
	categories = [],
}) {
	if (!open) return null;

	return (
		<div className="dctc-sc-modal-backdrop" onClick={onClose}>
			<div className="dctc-sc-modal-card" onClick={(e) => e.stopPropagation()}>
				<div className="dctc-sc-modal-top-header">
					<div className="dctc-sc-modal-icon-badge" style={{ background: 'linear-gradient(135deg, #059669 0%, #10b981 100%)' }}>
						<span className="dashicons dashicons-cart"></span>
					</div>
					<div className="dctc-sc-modal-title-wrap">
						<h3 className="dctc-sc-modal-title">{editingProd ? __('Edit Product', 'dragwyb-click-to-chat') : __('Add Product to Support Catalog', 'dragwyb-click-to-chat')}</h3>
						<p className="dctc-sc-modal-desc">{__('Products can be selected by customers during ticket creation for issue context.', 'dragwyb-click-to-chat')}</p>
					</div>
					<button type="button" className="dctc-sc-modal-close-btn" onClick={onClose} title={__('Close', 'dragwyb-click-to-chat')}>
						<span className="dashicons dashicons-no-alt"></span>
					</button>
				</div>

				<form onSubmit={onSave} className="dctc-sc-modal-form">
					<div className="dctc-sc-modal-body-scroll">
						<div className="dctc-sc-form-group">
							<label className="dctc-sc-field-label">
								<span className="dashicons dashicons-products"></span>
								{__('Product Name', 'dragwyb-click-to-chat')}
								<span className="dctc-sc-required-star">*</span>
							</label>
							<input
								type="text"
								required
								className="dctc-sc-custom-input"
								placeholder="e.g. Pro Membership Plan / Wireless Keyboard"
								value={prodForm.name}
								onChange={(e) => {
									const val = e.target.value;
									setProdForm({
										...prodForm,
										name: val,
										slug: !editingProd && (!prodForm.slug || prodForm.slug === generateSlug(prodForm.name)) ? generateSlug(val) : prodForm.slug,
									});
								}}
							/>
						</div>

						<div className="dctc-sc-form-group">
							<label className="dctc-sc-field-label">
								<span className="dashicons dashicons-barcode"></span>
								{__('SKU / Model Code', 'dragwyb-click-to-chat')}
							</label>
							<input
								type="text"
								className="dctc-sc-custom-input"
								placeholder="e.g. PRO-01"
								value={prodForm.sku}
								onChange={(e) => setProdForm({ ...prodForm, sku: e.target.value })}
							/>
						</div>

						<div className="dctc-sc-form-group">
							<label className="dctc-sc-field-label">
								<span className="dashicons dashicons-category"></span>
								{__('Primary Category Association', 'dragwyb-click-to-chat')}
							</label>
							<div className="dctc-sc-select-wrapper">
								<select
									className="dctc-sc-custom-select"
									value={prodForm.category_id || 0}
									onChange={(e) => setProdForm({ ...prodForm, category_id: parseInt(e.target.value, 10) || 0 })}
								>
									<option value="0">{__('— All / General Products —', 'dragwyb-click-to-chat')}</option>
									{categories.map((c) => (
										<option key={c.id} value={c.id}>
											{c.name}
										</option>
									))}
								</select>
							</div>
							<span className="dctc-sc-field-hint">{__('Optional: link this product to a specific support category.', 'dragwyb-click-to-chat')}</span>
						</div>
					</div>

					<div className="dctc-sc-modal-footer-bar">
						<button type="button" className="dctc-sc-btn-cancel" onClick={onClose}>
							{__('Cancel', 'dragwyb-click-to-chat')}
						</button>
						<button type="submit" className="dctc-sc-btn-submit" disabled={saving}>
							{saving ? __('Saving...', 'dragwyb-click-to-chat') : (editingProd ? __('Update Product', 'dragwyb-click-to-chat') : __('Save Product', 'dragwyb-click-to-chat'))}
						</button>
					</div>
				</form>
			</div>
		</div>
	);
}
