/**
 * Support Center - Agents & Staff View
 */
import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { AgentModal } from '../components/agents';

export default function AgentsView({ agents = [], onRefresh, userPermissions = {} }) {
	const [showModal, setShowModal] = useState(false);
	const [editingAgent, setEditingAgent] = useState(null);
	const [notice, setNotice] = useState(null);
	const [categories, setCategories] = useState([]);

	useEffect(() => {
		apiFetch({ path: '/dctc-ai/v1/support/categories' })
			.then((res) => {
				if (res?.categories) {
					setCategories(res.categories);
				}
			})
			.catch((err) => console.error('Error fetching categories in AgentsView:', err));
	}, []);

	const canManageAgents = !(userPermissions.is_admin === false && !userPermissions.manage_agents);

	const showTemporaryNotice = (text, type = 'success') => {
		setNotice({ type, text });
		setTimeout(() => setNotice(null), 4000);
	};

	const handleOpenAddModal = () => {
		setEditingAgent(null);
		setShowModal(true);
	};

	const handleOpenEditModal = (agentObj) => {
		setEditingAgent(agentObj);
		setShowModal(true);
	};

	const handleDeleteAgent = async (agentId, agentName) => {
		if (!window.confirm(`Are you sure you want to remove ${agentName} from the active support roster?`)) {
			return;
		}

		try {
			const res = await apiFetch({
				path: `/dctc-ai/v1/support/agents/${agentId}`,
				method: 'DELETE',
			});
			if (res?.success) {
				onRefresh();
				showTemporaryNotice(__('Agent removed from roster.', 'dragwyb-click-to-chat'), 'success');
			}
		} catch (err) {
			console.error('Error deleting agent:', err);
			showTemporaryNotice(__('Could not remove agent.', 'dragwyb-click-to-chat'), 'error');
		}
	};

	const getAgentInitials = (name) => {
		if (!name) return 'AG';
		const parts = name.trim().split(/\s+/);
		if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase();
		return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
	};

	return (
		<div className="dctc-sc-panel-box">
			{notice && (
				<div className={`dctc-sc-settings-banner ${notice.type}`} style={{ marginBottom: '16px' }}>
					<span className={`dashicons ${notice.type === 'success' ? 'dashicons-yes-alt' : 'dashicons-warning'}`}></span>
					<span>{notice.text}</span>
				</div>
			)}

			<div className="dctc-sc-panel-header">
				<div className="dctc-sc-panel-icon-wrap icon-indigo">
					<span className="dashicons dashicons-groups"></span>
				</div>
				<div>
					<h3>{__('Support Staff & Agent Roster', 'dragwyb-click-to-chat')}</h3>
					<p className="dctc-sc-panel-sub">{__('Manage support specialist availability, category routing, and active ticket capacities.', 'dragwyb-click-to-chat')}</p>
				</div>
				<div style={{ marginLeft: 'auto', display: 'flex', gap: '8px' }}>
					{canManageAgents && (
						<button
							type="button"
							className="button button-primary"
							onClick={handleOpenAddModal}
							style={{ display: 'flex', alignItems: 'center', gap: '6px', padding: '6px 14px', borderRadius: '7px', fontWeight: 600 }}
						>
							<span className="dashicons dashicons-plus-alt2"></span>
							{__('Add Staff Agent', 'dragwyb-click-to-chat')}
						</button>
					)}
					<button
						type="button"
						className="dctc-sc-panel-header-btn"
						onClick={onRefresh}
					>
						<span className="dashicons dashicons-update"></span>
						{__('Refresh Roster', 'dragwyb-click-to-chat')}
					</button>
				</div>
			</div>

			<table className="wp-list-table widefat fixed striped dctc-sc-table">
				<thead>
					<tr>
						<th>{__('Agent Name', 'dragwyb-click-to-chat')}</th>
						<th>{__('Email', 'dragwyb-click-to-chat')}</th>
						<th>{__('Support Role', 'dragwyb-click-to-chat')}</th>
						<th>{__('Seniority', 'dragwyb-click-to-chat')}</th>
						<th>{__('Availability', 'dragwyb-click-to-chat')}</th>
						<th>{__('Active Load', 'dragwyb-click-to-chat')}</th>
						<th>{__('Categories & Specialization', 'dragwyb-click-to-chat')}</th>
						{canManageAgents && <th style={{ width: '130px', textAlign: 'center' }}>{__('Action', 'dragwyb-click-to-chat')}</th>}
					</tr>
				</thead>
				<tbody>
					{agents.length === 0 ? (
						<tr>
							<td colSpan={canManageAgents ? 8 : 7} style={{ textAlign: 'center', padding: '30px' }}>
								{__('No active staff agents found.', 'dragwyb-click-to-chat')}
							</td>
						</tr>
					) : (
						agents.map((ag) => (
							<tr key={ag.id}>
								<td>
									<div className="dctc-sc-agent-cell" style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
										<div
											className="dctc-sc-sidebar-viewer-avatar"
											style={{
												backgroundColor: ag.color || '#4f46e5',
												width: '30px',
												height: '30px',
												minWidth: '30px',
												fontSize: '11px',
												cursor: 'default'
											}}
											title={ag.display_name}
										>
											<span>{getAgentInitials(ag.display_name)}</span>
										</div>
										<span className={`dctc-sc-status-indicator ${ag.availability_status}`}></span>
										<strong>{ag.display_name}</strong>
									</div>
								</td>
								<td>{ag.user_email}</td>
								<td><span className="dctc-sc-role-pill">{ag.support_role}</span></td>
								<td><code>{ag.seniority}</code></td>
								<td>
									<span className={`dctc-sc-avail-badge ${ag.availability_status}`}>
										<span className="dctc-sc-avail-dot"></span>
										{ag.availability_status === 'available' && __('Available', 'dragwyb-click-to-chat')}
										{ag.availability_status === 'away' && __('Away', 'dragwyb-click-to-chat')}
										{ag.availability_status === 'offline' && __('Offline', 'dragwyb-click-to-chat')}
									</span>
								</td>
								<td>
									<strong>{ag.current_active_tickets || 0}</strong> / {ag.max_active_tickets || 10}
								</td>
								<td>
									<div className="dctc-sc-skills-wrap">
										{(() => {
											const allowedCats = Array.isArray(ag.allowed_categories) ? ag.allowed_categories.map(Number) : [];
											const subtaxMap = ag.allowed_subtaxonomies && typeof ag.allowed_subtaxonomies === 'object' ? ag.allowed_subtaxonomies : {};
											const subtaxKeys = Object.keys(subtaxMap).filter((k) => Array.isArray(subtaxMap[k]) && subtaxMap[k].length > 0);

											if (allowedCats.length === 0 && subtaxKeys.length === 0) {
												return (
													<span className="dctc-sc-skill-tag" style={{ background: '#f1f5f9', color: '#64748b' }}>
														{__('All Categories', 'dragwyb-click-to-chat')}
													</span>
												);
											}

											const catBadges = allowedCats.map((cid) => {
												const cObj = categories.find((c) => Number(c.id) === cid);
												return (
													<span
														key={`cat-${cid}`}
														className="dctc-sc-skill-tag"
														style={{
															background: cObj ? `${cObj.color || '#4f46e5'}18` : '#ede9fe',
															color: cObj ? (cObj.color || '#4f46e5') : '#5b21b6',
															border: cObj ? `1px solid ${cObj.color || '#4f46e5'}33` : 'none',
														}}
													>
														{cObj ? cObj.name : `Cat #${cid}`}
													</span>
												);
											});

											const subtaxBadges = subtaxKeys.map((taxSlug) => (
												<span
													key={`tax-${taxSlug}`}
													className="dctc-sc-skill-tag"
													style={{ background: '#fef3c7', color: '#92400e', border: '1px solid #fde68a' }}
												>
													{`${taxSlug}: ${subtaxMap[taxSlug].length}`}
												</span>
											));

											return (
												<div style={{ display: 'flex', flexWrap: 'wrap', gap: '4px' }}>
													{catBadges}
													{subtaxBadges}
												</div>
											);
										})()}
									</div>
								</td>
								{canManageAgents && (
									<td style={{ textAlign: 'center' }}>
										<div style={{ display: 'flex', gap: '4px', justifyContent: 'center' }}>
											<button
												type="button"
												className="button button-small"
												title={__('Edit agent permissions', 'dragwyb-click-to-chat')}
												onClick={() => handleOpenEditModal(ag)}
											>
												{__('Edit', 'dragwyb-click-to-chat')}
											</button>
											<button
												type="button"
												className="button button-small button-link-delete"
												title={__('Remove agent from roster', 'dragwyb-click-to-chat')}
												onClick={() => handleDeleteAgent(ag.id, ag.display_name)}
											>
												{__('Remove', 'dragwyb-click-to-chat')}
											</button>
										</div>
									</td>
								)}
							</tr>
						))
					)}
				</tbody>
			</table>

			{/* Add/Edit Agent Modal */}
			<AgentModal
				isOpen={showModal}
				onClose={() => setShowModal(false)}
				agent={editingAgent}
				agents={agents}
				onSaved={onRefresh}
				onShowNotice={showTemporaryNotice}
			/>
		</div>
	);
}
