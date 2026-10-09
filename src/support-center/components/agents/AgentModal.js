import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

const PRESET_SKILLS = [
	'technical',
	'woocommerce',
	'billing',
	'product',
	'returns',
	'general',
	'orders',
];

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
	const [skills, setSkills] = useState(['technical', 'woocommerce', 'billing']);
	const [customSkillInput, setCustomSkillInput] = useState('');

	useEffect(() => {
		if (isOpen) {
			if (agent) {
				setSelectedUserId(agent.wp_user_id);
				setSupportRole(agent.support_role || 'support');
				setSeniority(agent.seniority || 'support');
				setColor(agent.color || '#4f46e5');
				setMaxTickets(agent.max_active_tickets || 10);
				setAvailability(agent.availability_status || 'available');
				setSkills(Array.isArray(agent.skills) ? agent.skills : []);
			} else {
				setSelectedUserId('');
				setSupportRole('support');
				setSeniority('support');
				setColor('#4f46e5');
				setMaxTickets(10);
				setAvailability('available');
				setSkills(['technical', 'woocommerce', 'billing']);
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
			if (Array.isArray(existingAgent.skills) && existingAgent.skills.length > 0) {
				setSkills(existingAgent.skills);
			}
		}
	};

	const toggleSkillPreset = (skill) => {
		if (skills.includes(skill)) {
			setSkills(skills.filter((s) => s !== skill));
		} else {
			setSkills([...skills, skill]);
		}
	};

	const removeSkill = (skillToRemove) => {
		setSkills(skills.filter((s) => s !== skillToRemove));
	};

	const handleAddCustomSkill = (e) => {
		if (e.key === 'Enter' || e.key === ',') {
			e.preventDefault();
			const trimmed = customSkillInput.trim().toLowerCase().replace(/,/g, '');
			if (trimmed && !skills.includes(trimmed)) {
				setSkills([...skills, trimmed]);
			}
			setCustomSkillInput('');
		}
	};

	const handleAddCustomSkillBlur = () => {
		const trimmed = customSkillInput.trim().toLowerCase().replace(/,/g, '');
		if (trimmed && !skills.includes(trimmed)) {
			setSkills([...skills, trimmed]);
		}
		setCustomSkillInput('');
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
					wp_user_id: parseInt(selectedUserId, 10),
					support_role: supportRole,
					seniority: seniority,
					color: color || '#4f46e5',
					max_active_tickets: parseInt(maxTickets, 10) || 10,
					availability_status: availability,
					skills: skills,
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
	const userInitials = (function(name) {
		if (!name) return 'AG';
		const parts = name.trim().split(/\s+/);
		if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase();
		return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
	})(currentDisplayName);

	return (
		<div className="dctc-sc-modal-backdrop" onClick={onClose}>
			<div className="dctc-sc-agent-modal-card" onClick={(e) => e.stopPropagation()}>
				{/* Modal Header */}
				<div className="dctc-sc-modal-top-header">
					<div className="dctc-sc-modal-icon-badge">
						<span className="dashicons dashicons-businesswoman"></span>
					</div>
					<div className="dctc-sc-modal-title-wrap">
						<h3 className="dctc-sc-modal-title">
							{agent ? __('Edit Staff Support Agent', 'dragwyb-click-to-chat') : __('Add Staff Support Agent', 'dragwyb-click-to-chat')}
						</h3>
						<p className="dctc-sc-modal-desc">{__('Assign a WordPress user to the live support roster with custom routing permissions & ticket limits.', 'dragwyb-click-to-chat')}</p>
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
						<p style={{ color: '#64748b', fontSize: '13.5px', margin: 0 }}>{__('Loading WordPress users list...', 'dragwyb-click-to-chat')}</p>
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
										required
									>
										{wpUsers.length === 0 && (
											<option value="">{__('No WordPress users found', 'dragwyb-click-to-chat')}</option>
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
								<span className="dctc-sc-field-hint">{__('Choose which registered WordPress account will handle customer tickets.', 'dragwyb-click-to-chat')}</span>
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
										max="100"
										className="dctc-sc-custom-input"
										value={maxTickets}
										onChange={(e) => setMaxTickets(e.target.value)}
										placeholder="10"
									/>
									<span className="dctc-sc-field-hint">{__('Concurrent active ticket limit', 'dragwyb-click-to-chat')}</span>
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
											<option value="available">{__('Available (Accepting Tickets)', 'dragwyb-click-to-chat')}</option>
											<option value="away">{__('Away (Temporarily Busy)', 'dragwyb-click-to-chat')}</option>
											<option value="offline">{__('Offline (Not Accepting)', 'dragwyb-click-to-chat')}</option>
										</select>
									</div>
									<span className="dctc-sc-field-hint">{__('Can be changed anytime by agent', 'dragwyb-click-to-chat')}</span>
								</div>
							</div>

							{/* Section 4: Agent Avatar & Identifier Color */}
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

										<div className="dctc-sc-custom-color-input-row">
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
											/>
										</div>
									</div>
								</div>
								<span className="dctc-sc-field-hint">
									{__('This color is used for the active viewer avatar initials span in the ticket sidebar and agent presence indicator.', 'dragwyb-click-to-chat')}
								</span>
							</div>

							{/* Section 5: Routing Skills with Interactive Tag Pillbox */}
							<div className="dctc-sc-form-group">
								<label className="dctc-sc-field-label">
									<span className="dashicons dashicons-tag"></span>
									{__('Routing Skills & Specialties', 'dragwyb-click-to-chat')}
								</label>

								<div className="dctc-sc-tag-input-container">
									<div className="dctc-sc-active-tags-list">
										{skills.map((skill) => (
											<span key={skill} className="dctc-sc-tag-badge">
												<span className="dctc-sc-tag-text">{skill}</span>
												<button
													type="button"
													className="dctc-sc-tag-remove"
													onClick={() => removeSkill(skill)}
													title={__('Remove skill', 'dragwyb-click-to-chat')}
												>
													✕
												</button>
											</span>
										))}
										<input
											type="text"
											className="dctc-sc-tag-inline-input"
											value={customSkillInput}
											onChange={(e) => setCustomSkillInput(e.target.value)}
											onKeyDown={handleAddCustomSkill}
											onBlur={handleAddCustomSkillBlur}
											placeholder={skills.length === 0 ? __('Type skill & press Enter (e.g. billing, shipping)', 'dragwyb-click-to-chat') : __('+ Add skill...', 'dragwyb-click-to-chat')}
										/>
									</div>
								</div>

								{/* Quick Add Preset Buttons */}
								<div className="dctc-sc-preset-skills-wrapper">
									<span className="dctc-sc-preset-label">{__('Quick add:', 'dragwyb-click-to-chat')}</span>
									<div className="dctc-sc-preset-chips">
										{PRESET_SKILLS.map((skill) => {
											const isActive = skills.includes(skill);
											return (
												<button
													key={skill}
													type="button"
													className={`dctc-sc-skill-chip ${isActive ? 'is-selected' : ''}`}
													onClick={() => toggleSkillPreset(skill)}
												>
													<span className="dctc-sc-chip-icon">{isActive ? '✓' : '+'}</span>
													{skill}
												</button>
											);
										})}
									</div>
								</div>
								<span className="dctc-sc-field-hint">
									{__('Incoming tickets with matching issue categories will be auto-routed to this agent.', 'dragwyb-click-to-chat')}
								</span>
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
