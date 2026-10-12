import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

const PRESET_COLORS = [
	{ name: 'Indigo', value: '#4f46e5' },
	{ name: 'Sky Blue', value: '#0284c7' },
	{ name: 'Emerald', value: '#059669' },
	{ name: 'Teal', value: '#0d9488' },
	{ name: 'Orange', value: '#ea580c' },
	{ name: 'Rose Red', value: '#e11d48' },
	{ name: 'Amber', value: '#d97706' },
	{ name: 'Purple', value: '#7c3aed' },
	{ name: 'Pink', value: '#db2777' },
	{ name: 'Slate', value: '#475569' },
];

export default function AgentModal({
	isOpen,
	onClose,
	agent = null,
	agents = [],
	onSaved,
	onShowNotice,
}) {
	const [wpUsers, setWpUsers] = useState([]);
	const [loadingUsers, setLoadingUsers] = useState(false);
	const [saving, setSaving] = useState(false);

	const [selectedUserId, setSelectedUserId] = useState('');
	const [supportRole, setSupportRole] = useState('support');
	const [seniority, setSeniority] = useState('support');
	const [color, setColor] = useState('#4f46e5');
	const [maxTickets, setMaxTickets] = useState(10);
	const [availability, setAvailability] = useState('available');

	// Category and Sub-Taxonomies state
	const [categories, setCategories] = useState([]);
	const [taxonomies, setTaxonomies] = useState([]);
	const [allowedCategories, setAllowedCategories] = useState([]);
	const [allowedSubtaxonomies, setAllowedSubtaxonomies] = useState({});
	const [loadingTaxonomies, setLoadingTaxonomies] = useState(false);

	useEffect(() => {
		if (isOpen) {
			if (agent) {
				setSelectedUserId(agent.wp_user_id);
				setSupportRole(agent.support_role || 'support');
				setSeniority(agent.seniority || 'support');
				setColor(agent.color || '#4f46e5');
				setMaxTickets(agent.max_active_tickets || 10);
				setAvailability(agent.availability_status || 'available');
				setAllowedCategories(Array.isArray(agent.allowed_categories) ? agent.allowed_categories.map(Number) : []);
				setAllowedSubtaxonomies(agent.allowed_subtaxonomies && typeof agent.allowed_subtaxonomies === 'object' ? agent.allowed_subtaxonomies : {});
			} else {
				setSelectedUserId('');
				setSupportRole('support');
				setSeniority('support');
				setColor('#4f46e5');
				setMaxTickets(10);
				setAvailability('available');
				setAllowedCategories([]);
				setAllowedSubtaxonomies({});
			}

			setLoadingUsers(true);
			apiFetch({ path: '/dctc-ai/v1/support/wp-users' })
				.then((res) => {
					if (res?.users) {
						setWpUsers(res.users);
						if (!agent) {
							const existingUserIds = (agents || []).map((a) => String(a.wp_user_id));
							const unassignedUser = res.users.find((u) => !existingUserIds.includes(String(u.id)));
							const defaultUser = unassignedUser || res.users[0];
							if (defaultUser && !selectedUserId) {
								handleSelectUser(defaultUser.id, res.users);
							}
						}
					}
				})
				.catch((err) => {
					console.error('Error fetching WP users:', err);
				})
				.finally(() => {
					setLoadingUsers(false);
				});

			// Fetch categories and taxonomies with their terms
			setLoadingTaxonomies(true);
			Promise.all([
				apiFetch({ path: '/dctc-ai/v1/support/categories' }),
				apiFetch({ path: '/dctc-ai/v1/support/taxonomies' }),
			])
				.then(([catsRes, taxRes]) => {
					if (catsRes?.categories) {
						setCategories(catsRes.categories);
					}
					if (taxRes?.taxonomies) {
						setTaxonomies(taxRes.taxonomies);
					}
				})
				.catch((err) => {
					console.error('Error fetching categories & taxonomies:', err);
				})
				.finally(() => {
					setLoadingTaxonomies(false);
				});
		}
	}, [isOpen, agent]);

	if (!isOpen) return null;

	const handleSelectUser = (userId, usersList = wpUsers) => {
		setSelectedUserId(userId);
		const existingAgent = (agents || []).find((a) => String(a.wp_user_id) === String(userId));
		if (existingAgent) {
			setSupportRole(existingAgent.support_role || 'support');
			setSeniority(existingAgent.seniority || 'support');
			if (existingAgent.color) {
				setColor(existingAgent.color);
			}
			setMaxTickets(existingAgent.max_active_tickets || 10);
			setAvailability(existingAgent.availability_status || 'available');
			if (Array.isArray(existingAgent.allowed_categories)) {
				setAllowedCategories(existingAgent.allowed_categories.map(Number));
			}
			if (existingAgent.allowed_subtaxonomies && typeof existingAgent.allowed_subtaxonomies === 'object') {
				setAllowedSubtaxonomies(existingAgent.allowed_subtaxonomies);
			}
		}
	};

	const toggleCategory = (catId) => {
		const idNum = Number(catId);
		let nextCats;
		if (allowedCategories.includes(idNum)) {
			nextCats = allowedCategories.filter((id) => id !== idNum);
		} else {
			nextCats = [...allowedCategories, idNum];
		}
		setAllowedCategories(nextCats);
	};

	const selectAllCategories = () => {
		if (allowedCategories.length === categories.length) {
			setAllowedCategories([]);
		} else {
			setAllowedCategories(categories.map((c) => Number(c.id)));
		}
	};

	// Determine active categories and supported sub-taxonomies
	const activeCategoryObjects = categories.filter((c) => allowedCategories.includes(Number(c.id)));
	const supportedSubTaxSlugs = Array.from(
		new Set(
			activeCategoryObjects.flatMap((c) => {
				if (Array.isArray(c.sub_taxonomies) && c.sub_taxonomies.length > 0) {
					return c.sub_taxonomies;
				}
				const fallback = [];
				if (Number(c.show_product ?? 1)) fallback.push('product');
				if (Number(c.show_tags ?? 1)) fallback.push('tag');
				return fallback;
			})
		)
	);

	const toggleSubtaxTerm = (taxSlug, termVal) => {
		const currentTerms = Array.isArray(allowedSubtaxonomies[taxSlug]) ? [...allowedSubtaxonomies[taxSlug]] : [];
		const isIncluded = currentTerms.some((t) => String(t) === String(termVal));
		let nextTerms;
		if (isIncluded) {
			nextTerms = currentTerms.filter((t) => String(t) !== String(termVal));
		} else {
			nextTerms = [...currentTerms, termVal];
		}
		setAllowedSubtaxonomies({
			...allowedSubtaxonomies,
			[taxSlug]: nextTerms,
		});
	};

	const toggleAllTermsForTax = (taxSlug, allTerms) => {
		const currentTerms = Array.isArray(allowedSubtaxonomies[taxSlug]) ? allowedSubtaxonomies[taxSlug] : [];
		if (currentTerms.length === allTerms.length) {
			const updated = { ...allowedSubtaxonomies };
			delete updated[taxSlug];
			setAllowedSubtaxonomies(updated);
		} else {
			setAllowedSubtaxonomies({
				...allowedSubtaxonomies,
				[taxSlug]: allTerms.map((t) => t.id || t.slug),
			});
		}
	};

	const handleAddAgent = async (e) => {
		e.preventDefault();
		if (!selectedUserId) return;

		setSaving(true);
		try {
			const res = await apiFetch({
				path: '/dctc-ai/v1/support/agents',
				method: 'POST',
				data: {
					id: agent?.id || 0,
					wp_user_id: parseInt(selectedUserId, 10),
					support_role: supportRole,
					seniority: seniority,
					color: color || '#4f46e5',
					max_active_tickets: parseInt(maxTickets, 10) || 10,
					availability_status: availability,
					allowed_categories: allowedCategories,
					allowed_subtaxonomies: allowedSubtaxonomies,
					active: 1,
					assignment_enabled: 1,
				},
			});

			if (res?.success) {
				onClose();
				if (onSaved) onSaved();
				if (onShowNotice) {
					onShowNotice(__('Staff agent saved successfully!', 'dragwyb-click-to-chat'), 'success');
				}
			}
		} catch (err) {
			console.error('Error saving agent:', err);
			if (onShowNotice) {
				onShowNotice(err?.message || __('Failed to save staff agent.', 'dragwyb-click-to-chat'), 'error');
			}
		} finally {
			setSaving(false);
		}
	};

	const selectedUserObj = (wpUsers || []).find((u) => String(u.id) === String(selectedUserId));
	const currentDisplayName = selectedUserObj?.display_name || agent?.display_name || 'Support Agent';
	const userInitials = (function (name) {
		if (!name) return 'AG';
		const parts = name.trim().split(/\s+/);
		if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase();
		return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
	})(currentDisplayName);

	return (
		<div className="dctc-sc-modal-backdrop" onClick={onClose}>
			<div
				className="dctc-sc-agent-modal-card"
				style={{ maxWidth: '640px', width: '92%' }}
				onClick={(e) => e.stopPropagation()}
			>
				{/* Modal Top Header */}
				<div className="dctc-sc-modal-top-header">
					<div className="dctc-sc-modal-icon-badge">
						<span className="dashicons dashicons-businesswoman"></span>
					</div>
					<div className="dctc-sc-modal-title-wrap">
						<h3 className="dctc-sc-modal-title">
							{agent ? __('Edit Staff Support Agent', 'dragwyb-click-to-chat') : __('Add Staff Support Agent', 'dragwyb-click-to-chat')}
						</h3>
						<p className="dctc-sc-modal-desc">
							{__('Assign a WordPress user to the live support roster with custom routing permissions & ticket limits.', 'dragwyb-click-to-chat')}
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

				{/* Modal Content / Form */}
				{loadingUsers ? (
					<div style={{ textAlign: 'center', padding: '60px 20px' }}>
						<span className="spinner is-active" style={{ float: 'none', margin: '0 auto 12px' }}></span>
						<p style={{ color: '#64748b', fontSize: '13.5px', margin: 0 }}>
							{__('Loading WordPress users list...', 'dragwyb-click-to-chat')}
						</p>
					</div>
				) : (
					<form className="dctc-sc-modal-form" onSubmit={handleAddAgent}>
						<div className="dctc-sc-modal-body-scroll">
							{/* Section 1: User Selection */}
							<div className="dctc-sc-form-group">
								<label className="dctc-sc-field-label">
									<span className="dashicons dashicons-admin-users"></span>
									{__('Select WordPress User', 'dragwyb-click-to-chat')}
									<span className="dctc-sc-required-star">*</span>
								</label>
								<div className="dctc-sc-select-wrapper">
									<select
										className="dctc-sc-custom-select"
										value={selectedUserId}
										onChange={(e) => handleSelectUser(e.target.value)}
										disabled={!!agent}
										required
									>
										<option value="">{__('-- Choose WordPress User Account --', 'dragwyb-click-to-chat')}</option>
										{wpUsers.length === 0 && (
											<option disabled>{__('No eligible WordPress users found', 'dragwyb-click-to-chat')}</option>
										)}
										{wpUsers.map((u) => {
											const isOnRoster = (agents || []).some((a) => String(a.wp_user_id) === String(u.id));
											return (
												<option key={u.id} value={u.id}>
													{u.display_name} ({u.user_email}) — Role: {(u.roles || []).join(', ') || 'user'}{isOnRoster ? ` • [In Roster - Update]` : ''}
												</option>
											);
										})}
									</select>
								</div>
								<span className="dctc-sc-field-hint">
									{__('Choose which registered WordPress account will handle customer tickets.', 'dragwyb-click-to-chat')}
								</span>
							</div>

							{/* Section 2: Support Role & Seniority */}
							<div className="dctc-sc-form-grid-2">
								<div className="dctc-sc-form-group">
									<label className="dctc-sc-field-label">
										<span className="dashicons dashicons-shield"></span>
										{__('Support Role / Permissions', 'dragwyb-click-to-chat')}
									</label>
									<div className="dctc-sc-select-wrapper">
										<select
											className="dctc-sc-custom-select"
											value={supportRole}
											onChange={(e) => setSupportRole(e.target.value)}
										>
											<option value="support">{__('Support Agent (Standard)', 'dragwyb-click-to-chat')}</option>
											<option value="senior">{__('Senior Specialist', 'dragwyb-click-to-chat')}</option>
											<option value="manager">{__('Support Manager', 'dragwyb-click-to-chat')}</option>
											<option value="admin">{__('Support Administrator', 'dragwyb-click-to-chat')}</option>
											<option value="fresher">{__('Junior / Tier 1', 'dragwyb-click-to-chat')}</option>
										</select>
									</div>
								</div>

								<div className="dctc-sc-form-group">
									<label className="dctc-sc-field-label">
										<span className="dashicons dashicons-awards"></span>
										{__('Seniority Level', 'dragwyb-click-to-chat')}
									</label>
									<div className="dctc-sc-select-wrapper">
										<select
											className="dctc-sc-custom-select"
											value={seniority}
											onChange={(e) => setSeniority(e.target.value)}
										>
											<option value="support">{__('Tier 1 (Frontline Specialist)', 'dragwyb-click-to-chat')}</option>
											<option value="senior">{__('Tier 2 (Senior Specialist)', 'dragwyb-click-to-chat')}</option>
											<option value="manager">{__('Tier 3 (Team Lead / Escalation)', 'dragwyb-click-to-chat')}</option>
										</select>
									</div>
								</div>
							</div>

							{/* Section 3: Max Active Tickets & Availability */}
							<div className="dctc-sc-form-grid-2">
								<div className="dctc-sc-form-group">
									<label className="dctc-sc-field-label">
										<span className="dashicons dashicons-chart-bar"></span>
										{__('Max Ticket Capacity', 'dragwyb-click-to-chat')}
									</label>
									<input
										type="number"
										min="1"
										max="200"
										className="dctc-sc-custom-input"
										value={maxTickets}
										onChange={(e) => setMaxTickets(e.target.value)}
										placeholder="10"
									/>
									<span className="dctc-sc-field-hint">
										{__('Maximum concurrent open tickets allowed before routing elsewhere.', 'dragwyb-click-to-chat')}
									</span>
								</div>

								<div className="dctc-sc-form-group">
									<label className="dctc-sc-field-label">
										<span className="dashicons dashicons-clock"></span>
										{__('Initial Availability', 'dragwyb-click-to-chat')}
									</label>
									<div className="dctc-sc-select-wrapper">
										<select
											className="dctc-sc-custom-select"
											value={availability}
											onChange={(e) => setAvailability(e.target.value)}
										>
											<option value="available">🟢 {__('Available (Accepting Tickets)', 'dragwyb-click-to-chat')}</option>
											<option value="away">🟡 {__('Away (Temporarily Busy)', 'dragwyb-click-to-chat')}</option>
											<option value="offline">⚪ {__('Offline (Not Accepting)', 'dragwyb-click-to-chat')}</option>
										</select>
									</div>
									<span className="dctc-sc-field-hint">
										{__('Can be changed anytime by agent from the Support topbar.', 'dragwyb-click-to-chat')}
									</span>
								</div>
							</div>

							{/* Section 4: Brand Accent Color & Avatar Preview */}
							<div className="dctc-sc-form-group">
								<label className="dctc-sc-field-label">
									<span className="dashicons dashicons-art"></span>
									{__('Agent Avatar & Identification Color', 'dragwyb-click-to-chat')}
								</label>
								<div className="dctc-sc-agent-color-picker-box">
									<div className="dctc-sc-agent-color-preview-wrap">
										<div
											className="dctc-sc-sidebar-viewer-avatar dctc-sc-agent-color-preview-avatar"
											style={{ backgroundColor: color || '#4f46e5' }}
											title={`${currentDisplayName} (${userInitials})`}
										>
											<span>{userInitials}</span>
											<span className="dctc-sc-viewer-online-dot"></span>
										</div>
										<div className="dctc-sc-agent-color-preview-info">
											<strong>{currentDisplayName}</strong>
											<span>{__('Ticket viewers stack initials preview', 'dragwyb-click-to-chat')}</span>
										</div>
									</div>

									<div className="dctc-sc-color-palette-wrap">
										<div className="dctc-sc-preset-colors-row">
											{PRESET_COLORS.map((c) => {
												const isSelected = (color || '').toLowerCase() === c.value.toLowerCase();
												return (
													<button
														key={c.value}
														type="button"
														className={`dctc-sc-color-swatch-btn ${isSelected ? 'is-selected' : ''}`}
														style={{ backgroundColor: c.value }}
														onClick={() => setColor(c.value)}
														title={c.name}
													>
														{isSelected && <span className="dashicons dashicons-yes"></span>}
													</button>
												);
											})}
										</div>

										<div className="dctc-sc-custom-color-input-row" style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
											<input
												type="color"
												className="dctc-sc-color-input-native"
												value={color || '#4f46e5'}
												onChange={(e) => setColor(e.target.value)}
												title={__('Custom color picker', 'dragwyb-click-to-chat')}
											/>
											<input
												type="text"
												className="dctc-sc-custom-input dctc-sc-hex-input"
												value={color || '#4f46e5'}
												onChange={(e) => setColor(e.target.value)}
												placeholder="#4f46e5"
												maxLength={7}
												style={{ width: '90px' }}
											/>
										</div>
									</div>
								</div>
								<span className="dctc-sc-field-hint">
									{__('This color is used for the active viewer avatar initials span in the ticket sidebar and agent presence indicator.', 'dragwyb-click-to-chat')}
								</span>
							</div>

							{/* Section 5: Assigned Categories & Supported Sub-Taxonomies */}
							<div className="dctc-sc-form-group" style={{ marginTop: '12px', borderTop: '1px solid #f1f5f9', paddingTop: '14px' }}>
								<label className="dctc-sc-field-label">
									<span className="dashicons dashicons-category"></span>
									{__('Categories & Sub-Taxonomies Specialization', 'dragwyb-click-to-chat')}
								</label>
								<span className="dctc-sc-field-hint" style={{ marginBottom: '10px' }}>
									{__('Assign ticket categories to this agent. Sub-taxonomies (products, tags) supported by selected categories are displayed below for precise skill & item matching.', 'dragwyb-click-to-chat')}
								</span>

								{/* Category Selection */}
								<div style={{ marginBottom: '14px' }}>
									<div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '8px' }}>
										<span style={{ fontSize: '11px', fontWeight: 600, textTransform: 'uppercase', color: '#64748b', letterSpacing: '0.04em' }}>
											{__('1. Select Authorized Categories:', 'dragwyb-click-to-chat')}
										</span>
										{categories.length > 0 && (
											<button
												type="button"
												className="button button-small"
												onClick={selectAllCategories}
												style={{ fontSize: '11px', height: '24px', lineHeight: '22px' }}
											>
												{allowedCategories.length === categories.length
													? __('Deselect All', 'dragwyb-click-to-chat')
													: __('Select All Categories', 'dragwyb-click-to-chat')}
											</button>
										)}
									</div>

									{loadingTaxonomies ? (
										<div style={{ padding: '8px 0', color: '#94a3b8', fontSize: '12px' }}>
											<span className="spinner is-active" style={{ float: 'none', margin: '0 6px 0 0' }}></span>
											{__('Loading categories & taxonomies...', 'dragwyb-click-to-chat')}
										</div>
									) : categories.length === 0 ? (
										<p style={{ color: '#94a3b8', fontSize: '12px', fontStyle: 'italic', margin: 0 }}>
											{__('No categories created yet. Create categories under Support > Taxonomies.', 'dragwyb-click-to-chat')}
										</p>
									) : (
										<div className="dctc-sc-preset-chips">
											{categories.map((cat) => {
												const isSelected = allowedCategories.includes(Number(cat.id));
												return (
													<button
														key={cat.id}
														type="button"
														className={`dctc-sc-skill-chip ${isSelected ? 'is-selected' : ''}`}
														onClick={() => toggleCategory(cat.id)}
														style={isSelected ? {
															backgroundColor: cat.color || '#4f46e5',
															borderColor: cat.color || '#4338ca',
															color: '#ffffff',
														} : {}}
													>
														<span
															style={{
																width: '7px',
																height: '7px',
																borderRadius: '50%',
																backgroundColor: isSelected ? '#ffffff' : (cat.color || '#4f46e5'),
																display: 'inline-block',
															}}
														></span>
														<span>{cat.name}</span>
														<span className="dctc-sc-chip-icon">
															{isSelected ? '✓' : '+'}
														</span>
													</button>
												);
											})}
										</div>
									)}
								</div>

								{/* Supported Sub-Taxonomies */}
								<div>
									<div style={{ marginBottom: '8px' }}>
										<span style={{ fontSize: '11px', fontWeight: 600, textTransform: 'uppercase', color: '#64748b', letterSpacing: '0.04em' }}>
											{__('2. Supported Sub-Taxonomies & Items (Based on Selected Categories):', 'dragwyb-click-to-chat')}
										</span>
									</div>

									{allowedCategories.length === 0 ? (
										<div
											style={{
												background: '#f8fafc',
												border: '1px dashed #cbd5e1',
												borderRadius: '8px',
												padding: '12px 14px',
												textAlign: 'center',
												color: '#64748b',
												fontSize: '12px',
											}}
										>
											<span className="dashicons dashicons-info" style={{ verticalAlign: 'middle', marginRight: '4px' }}></span>
											{__('Select one or more categories above to display and configure supported sub-taxonomies and items.', 'dragwyb-click-to-chat')}
										</div>
									) : supportedSubTaxSlugs.length === 0 ? (
										<div
											style={{
												background: '#f8fafc',
												border: '1px solid #e2e8f0',
												borderRadius: '8px',
												padding: '12px',
												color: '#64748b',
												fontSize: '12px',
											}}
										>
											{__('The selected categories do not have sub-taxonomies assigned. Standard ticket fields will be used.', 'dragwyb-click-to-chat')}
										</div>
									) : (
										<div style={{ display: 'flex', flexDirection: 'column', gap: '10px' }}>
											{supportedSubTaxSlugs.map((slug) => {
												const taxDef = taxonomies.find((t) => t.slug === slug);
												const taxTitle = taxDef ? taxDef.name : (slug.charAt(0).toUpperCase() + slug.slice(1));
												const terms = Array.isArray(taxDef?.terms) ? taxDef.terms : [];
												const selectedForTax = Array.isArray(allowedSubtaxonomies[slug]) ? allowedSubtaxonomies[slug] : [];

												return (
													<div
														key={slug}
														style={{
															background: '#f8fafc',
															border: '1px solid #e2e8f0',
															borderRadius: '8px',
															padding: '12px',
														}}
													>
														<div
															style={{
																display: 'flex',
																justifyContent: 'space-between',
																alignItems: 'center',
																marginBottom: '8px',
															}}
														>
															<div style={{ display: 'flex', alignItems: 'center', gap: '6px' }}>
																<span className="dashicons dashicons-tag" style={{ color: taxDef?.color || '#4f46e5', fontSize: '15px', lineHeight: 1 }}></span>
																<strong style={{ fontSize: '12px', color: '#1e293b' }}>{taxTitle}</strong>
																<span style={{ fontSize: '11px', color: '#64748b' }}>
																	({selectedForTax.length > 0 ? `${selectedForTax.length}/${terms.length} ${__('specialized items', 'dragwyb-click-to-chat')}` : __('Handles all items', 'dragwyb-click-to-chat')})
																</span>
															</div>

															{terms.length > 0 && (
																<button
																	type="button"
																	className="button button-small"
																	onClick={() => toggleAllTermsForTax(slug, terms)}
																	style={{ fontSize: '10px', height: '22px', lineHeight: '20px', padding: '0 8px' }}
																>
																	{selectedForTax.length === terms.length
																		? __('Clear Selection', 'dragwyb-click-to-chat')
																		: __('Select All Items', 'dragwyb-click-to-chat')}
																</button>
															)}
														</div>

														{terms.length === 0 ? (
															<p style={{ margin: '4px 0 0 0', fontSize: '11.5px', color: '#94a3b8', fontStyle: 'italic' }}>
																{__('No items configured for this taxonomy yet.', 'dragwyb-click-to-chat')}
															</p>
														) : (
															<div className="dctc-sc-preset-chips" style={{ marginTop: '6px' }}>
																{terms.map((term) => {
																	const termKey = term.id || term.slug;
																	const isTermSelected = selectedForTax.some((val) => String(val) === String(term.id) || String(val) === String(term.slug));
																	return (
																		<button
																			key={term.id || term.slug}
																			type="button"
																			className={`dctc-sc-skill-chip ${isTermSelected ? 'is-selected' : ''}`}
																			onClick={() => toggleSubtaxTerm(slug, termKey)}
																			style={isTermSelected ? {
																				backgroundColor: '#4f46e5',
																				borderColor: '#4338ca',
																				color: '#ffffff',
																			} : {}}
																		>
																			<span className="dctc-sc-chip-icon">{isTermSelected ? '✓' : '+'}</span>
																			{term.name || term.slug}
																		</button>
																	);
																})}
															</div>
														)}
													</div>
												);
											})}
										</div>
									)}
								</div>
							</div>
						</div>

						{/* Pinned Modal Footer Bar */}
						<div className="dctc-sc-modal-footer-bar">
							<button
								type="button"
								className="dctc-sc-btn-cancel"
								onClick={onClose}
								disabled={saving}
							>
								{__('Cancel', 'dragwyb-click-to-chat')}
							</button>
							<button
								type="submit"
								className="dctc-sc-btn-submit"
								disabled={saving}
							>
								{saving ? (
									<>
										<span className="spinner is-active" style={{ margin: 0, float: 'none' }}></span>
										{__('Saving Agent...', 'dragwyb-click-to-chat')}
									</>
								) : (
									<>
										<span className="dashicons dashicons-saved" style={{ fontSize: '17px', lineHeight: '1' }}></span>
										{agent ? __('Update Staff Agent', 'dragwyb-click-to-chat') : __('Add to Staff Roster', 'dragwyb-click-to-chat')}
									</>
								)}
							</button>
						</div>
					</form>
				)}
			</div>
		</div>
	);
}
