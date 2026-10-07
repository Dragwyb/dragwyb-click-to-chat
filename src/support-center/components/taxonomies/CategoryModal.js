/**
 * CategoryModal: Add / Edit Support Category with Dynamic Sub-Taxonomies Drag & Drop
 */
import { __ } from '@wordpress/i18n';

export default function CategoryModal({
	open,
	editingCat,
	catForm,
	setCatForm,
	onSave,
	onClose,
	saving,
	generateSlug,
	COLOR_PRESETS,
	PRESET_SKILLS,
	taxonomies,
	draggedSubTaxSlug,
	setDraggedSubTaxSlug,
	dropTargetSubTaxSlug,
	setDropTargetSubTaxSlug,
	handleSubTaxDragStart,
	handleSubTaxDragOver,
	handleSubTaxDrop,
	handleToggleSubTaxonomy,
	handleMoveSubTaxonomy,
}) {
	if (!open) return null;

	return (
		<div className="dctc-sc-modal-backdrop" onClick={onClose}>
			<div className="dctc-sc-modal-card is-wide" onClick={(e) => e.stopPropagation()}>
				<div className="dctc-sc-modal-top-header">
					<div className="dctc-sc-modal-icon-badge" style={{ background: `linear-gradient(135deg, ${catForm.color || '#4f46e5'} 0%, #6366f1 100%)` }}>
						<span className="dashicons dashicons-category"></span>
					</div>
					<div className="dctc-sc-modal-title-wrap">
						<h3 className="dctc-sc-modal-title">
							{editingCat ? __('Edit Support Category', 'dragwyb-click-to-chat') : __('Add New Category', 'dragwyb-click-to-chat')}
						</h3>
						<p className="dctc-sc-modal-desc">
							{__('Primary routing category: dynamically triggers support ticket fields and agent specialization.', 'dragwyb-click-to-chat')}
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
						{/* Category Name & Slug */}
						<div className="dctc-sc-form-grid-2">
							<div className="dctc-sc-form-group">
								<label className="dctc-sc-field-label">
									<span className="dashicons dashicons-category"></span>
									{__('Category Name', 'dragwyb-click-to-chat')}
									<span className="dctc-sc-required-star">*</span>
								</label>
								<input
									type="text"
									required
									className="dctc-sc-custom-input"
									placeholder="e.g. WooCommerce & Orders"
									value={catForm.name}
									onChange={(e) => {
										const val = e.target.value;
										setCatForm({
											...catForm,
											name: val,
											slug: !editingCat && (!catForm.slug || catForm.slug === generateSlug(catForm.name)) ? generateSlug(val) : catForm.slug,
										});
									}}
								/>
							</div>

							<div className="dctc-sc-form-group">
								<label className="dctc-sc-field-label">
									<span className="dashicons dashicons-admin-links"></span>
									{__('Category Slug', 'dragwyb-click-to-chat')}
								</label>
								<input
									type="text"
									className="dctc-sc-custom-input"
									placeholder="e.g. woocommerce-orders"
									value={catForm.slug}
									onChange={(e) => setCatForm({ ...catForm, slug: generateSlug(e.target.value) })}
								/>
							</div>
						</div>

						{/* Color & Default Priority */}
						<div className="dctc-sc-form-grid-2">
							<div className="dctc-sc-form-group">
								<label className="dctc-sc-field-label">
									<span className="dashicons dashicons-art"></span>
									{__('Category Color', 'dragwyb-click-to-chat')}
								</label>
								<div className="dctc-sc-color-picker-wrap">
									<div className="dctc-sc-color-swatches-row">
										{COLOR_PRESETS.map((c) => (
											<button
												key={c}
												type="button"
												className={`dctc-sc-color-swatch ${catForm.color === c ? 'is-selected' : ''}`}
												style={{ backgroundColor: c }}
												onClick={() => setCatForm({ ...catForm, color: c })}
												title={c}
											/>
										))}
									</div>
									<div className="dctc-sc-native-color-wrap">
										<input
											type="color"
											className="dctc-sc-native-color-btn"
											value={catForm.color || '#4F46E5'}
											onChange={(e) => setCatForm({ ...catForm, color: e.target.value })}
										/>
										<input
											type="text"
											className="dctc-sc-custom-input"
											style={{ width: '110px' }}
											value={catForm.color}
											onChange={(e) => setCatForm({ ...catForm, color: e.target.value })}
										/>
									</div>
								</div>
							</div>

							<div className="dctc-sc-form-group">
								<label className="dctc-sc-field-label">
									<span className="dashicons dashicons-flag"></span>
									{__('Default Ticket Priority', 'dragwyb-click-to-chat')}
								</label>
								<div className="dctc-sc-select-wrapper">
									<select
										className="dctc-sc-custom-select"
										value={catForm.default_priority}
										onChange={(e) => setCatForm({ ...catForm, default_priority: e.target.value })}
									>
										<option value="low">{__('Low Priority', 'dragwyb-click-to-chat')}</option>
										<option value="normal">{__('Normal Priority', 'dragwyb-click-to-chat')}</option>
										<option value="high">{__('High Priority', 'dragwyb-click-to-chat')}</option>
										<option value="urgent">{__('Urgent Priority', 'dragwyb-click-to-chat')}</option>
									</select>
								</div>
								<span className="dctc-sc-field-hint">{__('Assigned automatically when a ticket is filed under this category.', 'dragwyb-click-to-chat')}</span>
							</div>
						</div>

						{/* Support Form Dynamic Sub-Field Taxonomies Panel */}
						<div className="dctc-sc-condition-card">
							<div className="dctc-sc-condition-card-header">
								<h4>
									<span className="dashicons dashicons-randomize"></span>
									{__('Support Form Dynamic Sub-Field Taxonomies', 'dragwyb-click-to-chat')}
								</h4>
								<p>{__('Select and drag or use arrows to reorder the classification sub-fields that appear in the ticket form when this category is selected.', 'dragwyb-click-to-chat')}</p>
							</div>

							<div className="dctc-sc-condition-group">
								{(() => {
									const defaultSubs = [
										{ slug: 'product', name: __('Products', 'dragwyb-click-to-chat'), icon_dashicon: 'dashicons-products', color: '#059669', is_system: true, description: __('WooCommerce / custom product catalog dropdown.', 'dragwyb-click-to-chat') },
										{ slug: 'tag', name: __('Tags', 'dragwyb-click-to-chat'), icon_dashicon: 'dashicons-tag', color: '#D97706', is_system: true, description: __('Tags classification dropdown and badge selector.', 'dragwyb-click-to-chat') },
									];
									const currentList = Array.isArray(taxonomies) ? taxonomies.filter((t) => t.slug !== 'category') : [];
									const combined = [...currentList];
									defaultSubs.forEach((d) => {
										if (!combined.some((t) => t.slug === d.slug)) {
											combined.push(d);
										}
									});

									const availableSubTaxonomies = combined.sort((a, b) => {
										const aIdx = (catForm.sub_taxonomies || []).indexOf(a.slug);
										const bIdx = (catForm.sub_taxonomies || []).indexOf(b.slug);
										if (aIdx !== -1 && bIdx !== -1) return aIdx - bIdx;
										if (aIdx !== -1) return -1;
										if (bIdx !== -1) return 1;
										return 0;
									});

									return availableSubTaxonomies.map((tax) => {
										const isEnabled = (catForm.sub_taxonomies || []).includes(tax.slug);
										const activeIndex = (catForm.sub_taxonomies || []).indexOf(tax.slug);
										const canMoveUp = isEnabled && activeIndex > 0;
										const canMoveDown = isEnabled && activeIndex < (catForm.sub_taxonomies || []).length - 1;

										return (
											<div
												key={tax.slug}
												draggable={isEnabled}
												onDragStart={(e) => isEnabled && handleSubTaxDragStart(e, tax.slug)}
												onDragOver={(e) => handleSubTaxDragOver(e, tax.slug)}
												onDragEnd={() => {
													setDraggedSubTaxSlug(null);
													setDropTargetSubTaxSlug(null);
												}}
												onDrop={(e) => handleSubTaxDrop(e, tax.slug)}
												className={`dctc-sc-condition-row ${isEnabled ? 'is-enabled' : ''} ${draggedSubTaxSlug === tax.slug ? 'is-dragging' : ''} ${dropTargetSubTaxSlug === tax.slug ? 'is-drop-target' : ''}`}
												onClick={() => handleToggleSubTaxonomy(tax.slug)}
											>
												<div style={{ display: 'flex', alignItems: 'center', gap: '5px' }} onClick={(e) => e.stopPropagation()}>
													<span
														className="dctc-sc-drag-handle"
														title={isEnabled ? __('Drag to reorder position in form', 'dragwyb-click-to-chat') : ''}
													>
														<span className="dashicons dashicons-menu"></span>
													</span>
													<div className="dctc-sc-reorder-actions">
														<button
															type="button"
															className="dctc-sc-reorder-btn"
															disabled={!canMoveUp}
															onClick={() => handleMoveSubTaxonomy(tax.slug, 'up')}
															title={__('Move Up', 'dragwyb-click-to-chat')}
														>
															<span className="dashicons dashicons-arrow-up-alt2"></span>
														</button>
														<button
															type="button"
															className="dctc-sc-reorder-btn"
															disabled={!canMoveDown}
															onClick={() => handleMoveSubTaxonomy(tax.slug, 'down')}
															title={__('Move Down', 'dragwyb-click-to-chat')}
														>
															<span className="dashicons dashicons-arrow-down-alt2"></span>
														</button>
													</div>
													<span className={`dctc-sc-order-badge ${isEnabled ? 'is-active' : ''}`}>
														{isEnabled ? `#${activeIndex + 1}` : '—'}
													</span>
												</div>

												<div className="dctc-sc-condition-info">
													<div
														className="dctc-sc-condition-icon-wrap"
														style={{
															background: `${tax.color || '#4f46e5'}15`,
															color: tax.color || '#4f46e5',
														}}
													>
														{tax.image_url ? (
															<img src={tax.image_url} alt="" style={{ width: '18px', height: '18px', objectFit: 'cover', borderRadius: '3px' }} />
														) : (
															<span className={`dashicons ${tax.icon_dashicon || 'dashicons-tag'}`}></span>
														)}
													</div>
													<div className="dctc-sc-condition-text-wrap">
														<span className="dctc-sc-condition-main-title">
															{tax.name}
															{tax.slug === 'product' && ` (${__('Product Catalog Dropdown', 'dragwyb-click-to-chat')})`}
															{tax.slug === 'tag' && ` (${__('Tags Classification', 'dragwyb-click-to-chat')})`}
														</span>
														<span className="dctc-sc-condition-sub-hint">
															{tax.description || __('Dynamic sub-field in support ticket form.', 'dragwyb-click-to-chat')}
														</span>
													</div>
												</div>

												<label className="dctc-sc-switch-control" onClick={(e) => e.stopPropagation()}>
													<input
														type="checkbox"
														checked={isEnabled}
														onChange={() => handleToggleSubTaxonomy(tax.slug)}
													/>
													<span className="dctc-sc-switch-slider"></span>
												</label>
											</div>
										);
									});
								})()}
							</div>
						</div>

						{/* Required Skills Pillbox */}
						<div className="dctc-sc-form-group">
							<label className="dctc-sc-field-label">
								<span className="dashicons dashicons-awards"></span>
								{__('Required Agent Skills (Auto-routing matching)', 'dragwyb-click-to-chat')}
							</label>
							<input
								type="text"
								className="dctc-sc-custom-input"
								placeholder="e.g. technical, billing, returns"
								value={catForm.required_skills}
								onChange={(e) => setCatForm({ ...catForm, required_skills: e.target.value })}
							/>
							<div className="dctc-sc-preset-skills-wrapper" style={{ marginTop: '4px' }}>
								<span className="dctc-sc-preset-label">{__('Quick Add Skills:', 'dragwyb-click-to-chat')}</span>
								<div className="dctc-sc-preset-chips">
									{PRESET_SKILLS.map((skill) => {
										const activeSkills = (catForm.required_skills || '')
											.split(',')
											.map((s) => s.trim())
											.filter(Boolean);
										const isActive = activeSkills.includes(skill);
										return (
											<button
												key={skill}
												type="button"
												className={`dctc-sc-skill-chip ${isActive ? 'is-selected' : ''}`}
												onClick={() => {
													let nextSkills;
													if (isActive) {
														nextSkills = activeSkills.filter((s) => s !== skill);
													} else {
														nextSkills = [...activeSkills, skill];
													}
													setCatForm({ ...catForm, required_skills: nextSkills.join(', ') });
												}}
											>
												<span className="dctc-sc-chip-icon">{isActive ? '✓' : '+'}</span>
												{skill}
											</button>
										);
									})}
								</div>
							</div>
						</div>

						{/* Category Description */}
						<div className="dctc-sc-form-group">
							<label className="dctc-sc-field-label">
								<span className="dashicons dashicons-editor-paragraph"></span>
								{__('Category Description (Optional)', 'dragwyb-click-to-chat')}
							</label>
							<textarea
								rows="2"
								className="dctc-sc-custom-textarea"
								placeholder={__('Describe what inquiries belong here...', 'dragwyb-click-to-chat')}
								value={catForm.description}
								onChange={(e) => setCatForm({ ...catForm, description: e.target.value })}
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
									{editingCat ? __('Update Category', 'dragwyb-click-to-chat') : __('Create Category', 'dragwyb-click-to-chat')}
								</>
							)}
						</button>
					</div>
				</form>
			</div>
		</div>
	);
}
