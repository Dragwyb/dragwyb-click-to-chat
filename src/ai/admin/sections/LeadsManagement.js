/**
 * AI Leads Management Section
 *
 * Provides a responsive dashboard for managing, filtering, scoring,
 * viewing transcripts, and exporting leads captured by the AI Chatbot.
 *
 * @package Dragwyb_Click_To_Chat
 */
import { useState, useEffect, useCallback, useMemo } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import ConfirmModal from '../components/ConfirmModal';

const STATUS_OPTIONS = [
	{ id: 'all', label: __('All Leads', 'dragwyb-click-to-chat'), color: '#64748b' },
	{ id: 'new', label: __('New', 'dragwyb-click-to-chat'), color: '#3b82f6' },
	{ id: 'contacted', label: __('Contacted', 'dragwyb-click-to-chat'), color: '#8b5cf6' },
	{ id: 'qualified', label: __('Qualified', 'dragwyb-click-to-chat'), color: '#10b981' },
	{ id: 'converted', label: __('Converted', 'dragwyb-click-to-chat'), color: '#059669' },
	{ id: 'discarded', label: __('Discarded', 'dragwyb-click-to-chat'), color: '#94a3b8' },
];

function formatDate(value) {
	if (!value) return '';
	const iso = String(value).includes('T') ? value : String(value).replace(' ', 'T');
	const date = new Date(iso);
	if (Number.isNaN(date.getTime())) return value;
	const now = new Date();
	const opts = {
		month: 'short',
		day: 'numeric',
		...(date.getFullYear() !== now.getFullYear() ? { year: 'numeric' } : {}),
	};
	return `${date.toLocaleDateString(undefined, opts)} · ${date.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' })}`;
}

function getScoreBadge(score) {
	const num = Number(score) || 0;
	if (num >= 70) {
		return { label: `${num}/100 High Quality`, bg: '#ecfdf5', color: '#065f46', border: '#a7f3d0' };
	}
	if (num >= 40) {
		return { label: `${num}/100 Medium`, bg: '#fffbeb', color: '#92400e', border: '#fde68a' };
	}
	return { label: `${num}/100 Basic`, bg: '#f1f5f9', color: '#475569', border: '#e2e8f0' };
}

function getIntentBadge(intent) {
	switch (intent) {
		case 'urgent':
			return { label: __('🔥 Urgent Intent', 'dragwyb-click-to-chat'), bg: '#fef2f2', color: '#991b1b', border: '#fecaca' };
		case 'high':
			return { label: __('⚡ High Intent', 'dragwyb-click-to-chat'), bg: '#fffbeb', color: '#92400e', border: '#fde68a' };
		case 'low':
			return { label: __('💬 General', 'dragwyb-click-to-chat'), bg: '#f8fafc', color: '#64748b', border: '#e2e8f0' };
		case 'medium':
		default:
			return { label: __('🎯 Qualified', 'dragwyb-click-to-chat'), bg: '#eff6ff', color: '#1e40af', border: '#bfdbfe' };
	}
}

function parseScoreBreakdown(raw) {
	if (!raw) return [];
	if (Array.isArray(raw)) return raw;
	try {
		const parsed = JSON.parse(raw);
		return Array.isArray(parsed) ? parsed : [];
	} catch {
		return [];
	}
}

export default function LeadsManagement({ showNotice }) {
	const [leads, setLeads] = useState([]);
	const [total, setTotal] = useState(0);
	const [page, setPage] = useState(1);
	const [pages, setPages] = useState(1);
	const [limit] = useState(25);
	const [statusFilter, setStatusFilter] = useState('all');
	const [searchQuery, setSearchQuery] = useState('');
	const [loading, setLoading] = useState(true);
	const [exporting, setExporting] = useState(false);
	const [activeLead, setActiveLead] = useState(null);
	const [leadToDelete, setLeadToDelete] = useState(null);
	const [updatingStatusId, setUpdatingStatusId] = useState(null);

	const fetchLeads = useCallback(async () => {
		setLoading(true);
		try {
			const queryParams = new URLSearchParams({
				limit: String(limit),
				page: String(page),
				status: statusFilter,
				search: searchQuery,
			});
			const res = await apiFetch({ path: `/dctc-ai/v1/leads?${queryParams.toString()}` });
			if (res?.success) {
				setLeads(res.leads || []);
				setTotal(res.total || 0);
				setPages(res.pages || 1);
			}
		} catch (err) {
			if (showNotice) {
				showNotice(err.message || __('Failed to load leads.', 'dragwyb-click-to-chat'), 'error');
			}
		} finally {
			setLoading(false);
		}
	}, [limit, page, statusFilter, searchQuery, showNotice]);

	useEffect(() => {
		fetchLeads();
	}, [fetchLeads]);

	const handleStatusChange = async (leadId, newStatus) => {
		setUpdatingStatusId(leadId);
		try {
			const res = await apiFetch({
				path: `/dctc-ai/v1/leads/${leadId}/status`,
				method: 'POST',
				data: { status: newStatus },
			});
			if (res?.success) {
				setLeads((prev) =>
					prev.map((l) => (l.id === leadId ? { ...l, status: newStatus } : l))
				);
				if (activeLead && activeLead.id === leadId) {
					setActiveLead((prev) => ({ ...prev, status: newStatus }));
				}
				if (showNotice) {
					showNotice(__('Lead status updated successfully.', 'dragwyb-click-to-chat'), 'success');
				}
			}
		} catch (err) {
			if (showNotice) {
				showNotice(err.message || __('Failed to update status.', 'dragwyb-click-to-chat'), 'error');
			}
		} finally {
			setUpdatingStatusId(null);
		}
	};

	const handleDeleteLead = async () => {
		if (!leadToDelete) return;
		try {
			const res = await apiFetch({
				path: `/dctc-ai/v1/leads/${leadToDelete.id}`,
				method: 'DELETE',
			});
			if (res?.success) {
				setLeads((prev) => prev.filter((l) => l.id !== leadToDelete.id));
				setTotal((prev) => Math.max(0, prev - 1));
				if (activeLead && activeLead.id === leadToDelete.id) {
					setActiveLead(null);
				}
				if (showNotice) {
					showNotice(__('Lead deleted successfully.', 'dragwyb-click-to-chat'), 'success');
				}
			}
		} catch (err) {
			if (showNotice) {
				showNotice(err.message || __('Failed to delete lead.', 'dragwyb-click-to-chat'), 'error');
			}
		} finally {
			setLeadToDelete(null);
		}
	};

	const handleExportCSV = async () => {
		setExporting(true);
		try {
			const res = await apiFetch({ path: '/dctc-ai/v1/leads/export' });
			if (res?.success && res.csv) {
				const blob = new Blob([res.csv], { type: 'text/csv;charset=utf-8;' });
				const url = URL.createObjectURL(blob);
				const link = document.createElement('a');
				link.setAttribute('href', url);
				link.setAttribute('download', res.filename || 'leads-export.csv');
				document.body.appendChild(link);
				link.click();
				document.body.removeChild(link);
				URL.revokeObjectURL(url);
				if (showNotice) {
					showNotice(__('Leads exported to CSV successfully.', 'dragwyb-click-to-chat'), 'success');
				}
			}
		} catch (err) {
			if (showNotice) {
				showNotice(err.message || __('Failed to export leads.', 'dragwyb-click-to-chat'), 'error');
			}
		} finally {
			setExporting(false);
		}
	};

	// Metrics summary
	const stats = useMemo(() => {
		const highQuality = leads.filter((l) => (Number(l.score) || 0) >= 70).length;
		const converted = leads.filter((l) => l.status === 'converted').length;
		const newLeads = leads.filter((l) => l.status === 'new').length;
		return { highQuality, converted, newLeads };
	}, [leads]);

	return (
		<div className="dctc-ai-leads-management">
			{/* Top Metric Cards */}
			<div className="dctc-ai-stats-grid" style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', gap: '1rem', marginBottom: '1.5rem' }}>
				<div className="dctc-ai-stat-card" style={{ background: '#ffffff', padding: '1.25rem', borderRadius: '10px', border: '1px solid #e2e8f0', boxShadow: '0 1px 3px rgba(0,0,0,0.05)' }}>
					<span style={{ fontSize: '0.8rem', fontWeight: 600, color: '#64748b', textTransform: 'uppercase' }}>
						{__('Total Captured Leads', 'dragwyb-click-to-chat')}
					</span>
					<div style={{ fontSize: '1.8rem', fontWeight: 700, color: '#0f172a', marginTop: '0.25rem' }}>
						{total}
					</div>
				</div>

				<div className="dctc-ai-stat-card" style={{ background: '#ffffff', padding: '1.25rem', borderRadius: '10px', border: '1px solid #e2e8f0', boxShadow: '0 1px 3px rgba(0,0,0,0.05)' }}>
					<span style={{ fontSize: '0.8rem', fontWeight: 600, color: '#10b981', textTransform: 'uppercase' }}>
						{__('High-Quality Leads', 'dragwyb-click-to-chat')}
					</span>
					<div style={{ fontSize: '1.8rem', fontWeight: 700, color: '#065f46', marginTop: '0.25rem' }}>
						{stats.highQuality}
					</div>
				</div>

				<div className="dctc-ai-stat-card" style={{ background: '#ffffff', padding: '1.25rem', borderRadius: '10px', border: '1px solid #e2e8f0', boxShadow: '0 1px 3px rgba(0,0,0,0.05)' }}>
					<span style={{ fontSize: '0.8rem', fontWeight: 600, color: '#3b82f6', textTransform: 'uppercase' }}>
						{__('New / Uncontacted', 'dragwyb-click-to-chat')}
					</span>
					<div style={{ fontSize: '1.8rem', fontWeight: 700, color: '#1e40af', marginTop: '0.25rem' }}>
						{stats.newLeads}
					</div>
				</div>

				<div className="dctc-ai-stat-card" style={{ background: '#ffffff', padding: '1.25rem', borderRadius: '10px', border: '1px solid #e2e8f0', boxShadow: '0 1px 3px rgba(0,0,0,0.05)' }}>
					<span style={{ fontSize: '0.8rem', fontWeight: 600, color: '#8b5cf6', textTransform: 'uppercase' }}>
						{__('Converted', 'dragwyb-click-to-chat')}
					</span>
					<div style={{ fontSize: '1.8rem', fontWeight: 700, color: '#5b21b6', marginTop: '0.25rem' }}>
						{stats.converted}
					</div>
				</div>
			</div>

			{/* Filter Toolbar */}
			<div className="dctc-ai-leads-toolbar" style={{ display: 'flex', flexWrap: 'wrap', gap: '0.75rem', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1.25rem' }}>
				<div style={{ display: 'flex', flexWrap: 'wrap', gap: '0.5rem', alignItems: 'center' }}>
					<input
						type="search"
						placeholder={__('Search by name, email, phone, company...', 'dragwyb-click-to-chat')}
						value={searchQuery}
						onChange={(e) => {
							setSearchQuery(e.target.value);
							setPage(1);
						}}
						style={{ minWidth: '280px', padding: '0.5rem 0.85rem', borderRadius: '6px', border: '1px solid #cbd5e1', fontSize: '0.9rem' }}
					/>

					<div style={{ display: 'flex', gap: '0.25rem' }}>
						{STATUS_OPTIONS.map((opt) => (
							<button
								key={opt.id}
								type="button"
								onClick={() => {
									setStatusFilter(opt.id);
									setPage(1);
								}}
								className={`button ${statusFilter === opt.id ? 'button-primary' : 'button-secondary'}`}
								style={{ borderRadius: '6px', fontSize: '0.85rem' }}
							>
								{opt.label}
							</button>
						))}
					</div>
				</div>

				<div>
					<button
						type="button"
						className="button button-secondary"
						onClick={handleExportCSV}
						disabled={exporting || total === 0}
						style={{ display: 'inline-flex', alignItems: 'center', gap: '0.4rem', borderRadius: '6px' }}
					>
						<span className="dashicons dashicons-download" style={{ fontSize: '16px', lineHeight: '20px' }} />
						{exporting ? __('Exporting...', 'dragwyb-click-to-chat') : __('Export CSV', 'dragwyb-click-to-chat')}
					</button>
				</div>
			</div>

			{/* Leads Table */}
			<div className="dctc-ai-table-container" style={{ background: '#ffffff', borderRadius: '10px', border: '1px solid #e2e8f0', overflow: 'hidden', boxShadow: '0 1px 3px rgba(0,0,0,0.05)' }}>
				{loading ? (
					<div style={{ padding: '3rem', textAlign: 'center', color: '#64748b' }}>
						<span className="spinner is-active" style={{ float: 'none', margin: '0 auto 0.5rem' }} />
						<div>{__('Loading leads...', 'dragwyb-click-to-chat')}</div>
					</div>
				) : leads.length === 0 ? (
					<div style={{ padding: '3.5rem 2rem', textAlign: 'center', color: '#64748b' }}>
						<span className="dashicons dashicons-id" style={{ fontSize: '48px', height: '48px', width: '48px', color: '#cbd5e1', marginBottom: '0.75rem' }} />
						<h3 style={{ margin: '0 0 0.5rem', color: '#1e293b' }}>
							{__('No Leads Found', 'dragwyb-click-to-chat')}
						</h3>
						<p style={{ margin: 0, maxWidth: '450px', marginLeft: 'auto', marginRight: 'auto', fontSize: '0.9rem' }}>
							{searchQuery || statusFilter !== 'all'
								? __('No leads matched your search or status filter criteria.', 'dragwyb-click-to-chat')
								: __('When visitors share their contact details through the AI Chatbot lead form, they will appear here.', 'dragwyb-click-to-chat')}
						</p>
					</div>
				) : (
					<table className="wp-list-table widefat fixed striped" style={{ border: 'none' }}>
						<thead>
							<tr>
								<th style={{ width: '22%', fontWeight: 600 }}>{__('Lead / Contact', 'dragwyb-click-to-chat')}</th>
								<th style={{ width: '22%', fontWeight: 600 }}>{__('Email / Phone', 'dragwyb-click-to-chat')}</th>
								<th style={{ width: '22%', fontWeight: 600 }}>{__('Requirement / Note', 'dragwyb-click-to-chat')}</th>
								<th style={{ width: '12%', fontWeight: 600 }}>{__('Lead Score', 'dragwyb-click-to-chat')}</th>
								<th style={{ width: '12%', fontWeight: 600 }}>{__('Status', 'dragwyb-click-to-chat')}</th>
								<th style={{ width: '10%', textAlign: 'right', fontWeight: 600 }}>{__('Actions', 'dragwyb-click-to-chat')}</th>
							</tr>
						</thead>
						<tbody>
							{leads.map((lead) => {
								const badge = getScoreBadge(lead.score);
								const initial = (lead.name || lead.email || 'L').charAt(0).toUpperCase();

								return (
									<tr key={lead.id} style={{ verticalAlign: 'middle' }}>
										<td>
											<div style={{ display: 'flex', alignItems: 'center', gap: '0.75rem' }}>
												<div
													style={{
														width: '36px',
														height: '36px',
														borderRadius: '50%',
														background: '#e0e7ff',
														color: '#4338ca',
														display: 'flex',
														alignItems: 'center',
														justifyContent: 'center',
														fontWeight: 700,
														fontSize: '0.9rem',
														flexShrink: 0,
													}}
												>
													{initial}
												</div>
												<div>
													<strong style={{ display: 'block', color: '#0f172a' }}>
														{lead.name || __('Anonymous Visitor', 'dragwyb-click-to-chat')}
													</strong>
													{lead.company && (
														<span style={{ fontSize: '0.8rem', color: '#64748b' }}>
															{lead.company}
														</span>
													)}
												</div>
											</div>
										</td>
										<td>
											{lead.email && (
												<div style={{ fontSize: '0.85rem' }}>
													<a href={`mailto:${lead.email}`} style={{ textDecoration: 'none', color: '#2563eb' }}>
														{lead.email}
													</a>
												</div>
											)}
											{lead.phone && (
												<div style={{ fontSize: '0.8rem', color: '#64748b', marginTop: '0.2rem' }}>
													<a href={`tel:${lead.phone}`} style={{ textDecoration: 'none', color: '#475569' }}>
														📞 {lead.phone}
													</a>
												</div>
											)}
										</td>
										<td>
											<div style={{ fontSize: '0.85rem', color: '#334155', maxWidth: '240px', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }} title={lead.requirement}>
												{lead.requirement || '—'}
											</div>
											<div style={{ fontSize: '0.75rem', color: '#94a3b8', marginTop: '0.2rem' }}>
												{formatDate(lead.created_at)}
											</div>
										</td>
										<td>
											<div style={{ display: 'flex', flexDirection: 'column', gap: '0.35rem', alignItems: 'flex-start' }}>
												<span
													style={{
														display: 'inline-block',
														padding: '0.2rem 0.6rem',
														borderRadius: '999px',
														fontSize: '0.75rem',
														fontWeight: 600,
														background: badge.bg,
														color: badge.color,
														border: `1px solid ${badge.border}`,
													}}
												>
													{badge.label}
												</span>
												{lead.intent_level && (
													<span
														style={{
															display: 'inline-block',
															padding: '0.15rem 0.5rem',
															borderRadius: '999px',
															fontSize: '0.7rem',
															fontWeight: 600,
															background: getIntentBadge(lead.intent_level).bg,
															color: getIntentBadge(lead.intent_level).color,
															border: `1px solid ${getIntentBadge(lead.intent_level).border}`,
														}}
													>
														{getIntentBadge(lead.intent_level).label}
													</span>
												)}
											</div>
										</td>
										<td>
											<select
												value={lead.status || 'new'}
												disabled={updatingStatusId === lead.id}
												onChange={(e) => handleStatusChange(lead.id, e.target.value)}
												style={{
													fontSize: '0.85rem',
													padding: '0.25rem 0.5rem',
													borderRadius: '6px',
													borderColor: '#cbd5e1',
												}}
											>
												<option value="new">{__('New', 'dragwyb-click-to-chat')}</option>
												<option value="contacted">{__('Contacted', 'dragwyb-click-to-chat')}</option>
												<option value="qualified">{__('Qualified', 'dragwyb-click-to-chat')}</option>
												<option value="converted">{__('Converted', 'dragwyb-click-to-chat')}</option>
												<option value="discarded">{__('Discarded', 'dragwyb-click-to-chat')}</option>
											</select>
										</td>
										<td style={{ textAlign: 'right' }}>
											<div style={{ display: 'inline-flex', gap: '0.35rem' }}>
												<button
													type="button"
													className="button button-small"
													onClick={() => setActiveLead(lead)}
													title={__('View details', 'dragwyb-click-to-chat')}
												>
													<span className="dashicons dashicons-visibility" style={{ fontSize: '16px', lineHeight: '24px' }} />
												</button>
												<button
													type="button"
													className="button button-small"
													style={{ color: '#ef4444' }}
													onClick={() => setLeadToDelete(lead)}
													title={__('Delete lead', 'dragwyb-click-to-chat')}
												>
													<span className="dashicons dashicons-trash" style={{ fontSize: '16px', lineHeight: '24px' }} />
												</button>
											</div>
										</td>
									</tr>
								);
							})}
						</tbody>
					</table>
				)}

				{/* Pagination */}
				{pages > 1 && (
					<div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', padding: '0.75rem 1.25rem', borderTop: '1px solid #e2e8f0', background: '#f8fafc' }}>
						<span style={{ fontSize: '0.85rem', color: '#64748b' }}>
							{sprintf(
								/* translators: 1: Current page, 2: Total pages, 3: Total items */
								__('Page %1$d of %2$d (%3$d total leads)', 'dragwyb-click-to-chat'),
								page,
								pages,
								total
							)}
						</span>
						<div style={{ display: 'flex', gap: '0.5rem' }}>
							<button
								type="button"
								className="button"
								disabled={page <= 1}
								onClick={() => setPage((p) => Math.max(1, p - 1))}
							>
								{__('Previous', 'dragwyb-click-to-chat')}
							</button>
							<button
								type="button"
								className="button"
								disabled={page >= pages}
								onClick={() => setPage((p) => Math.min(pages, p + 1))}
							>
								{__('Next', 'dragwyb-click-to-chat')}
							</button>
						</div>
					</div>
				)}
			</div>

			{/* View Details Modal */}
			{activeLead && (
				<div className="dctc-ai-modal-overlay" style={{ position: 'fixed', inset: 0, background: 'rgba(15, 23, 42, 0.65)', backdropFilter: 'blur(4px)', display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 100000 }}>
					<div className="dctc-ai-modal-box" style={{ background: '#ffffff', width: '90%', maxWidth: '580px', borderRadius: '12px', padding: '1.75rem', boxShadow: '0 20px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1)', maxElementSibling: '90vh', overflowY: 'auto' }}>
						<div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: '1.25rem' }}>
							<div>
								<h2 style={{ margin: 0, fontSize: '1.25rem', color: '#0f172a' }}>
									{activeLead.name || __('Lead Details', 'dragwyb-click-to-chat')}
								</h2>
								<span style={{ fontSize: '0.85rem', color: '#64748b' }}>
									{__('Captured on', 'dragwyb-click-to-chat')} {formatDate(activeLead.created_at)}
								</span>
							</div>
							<button
								type="button"
								onClick={() => setActiveLead(null)}
								className="button button-small"
								style={{ borderRadius: '50%', width: '28px', height: '28px', padding: 0, display: 'flex', alignItems: 'center', justifyContent: 'center' }}
							>
								✕
							</button>
						</div>

						<div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(130px, 1fr))', gap: '0.75rem', marginBottom: '1.25rem', background: '#f8fafc', padding: '1rem', borderRadius: '8px', border: '1px solid #e2e8f0' }}>
							<div>
								<strong style={{ display: 'block', fontSize: '0.75rem', color: '#64748b' }}>{__('Email', 'dragwyb-click-to-chat')}</strong>
								{activeLead.email ? (
									<a href={`mailto:${activeLead.email}`} style={{ fontSize: '0.85rem', color: '#2563eb' }}>{activeLead.email}</a>
								) : '—'}
							</div>

							<div>
								<strong style={{ display: 'block', fontSize: '0.75rem', color: '#64748b' }}>{__('Phone', 'dragwyb-click-to-chat')}</strong>
								{activeLead.phone ? (
									<a href={`tel:${activeLead.phone}`} style={{ fontSize: '0.85rem', color: '#2563eb' }}>{activeLead.phone}</a>
								) : '—'}
							</div>

							<div>
								<strong style={{ display: 'block', fontSize: '0.75rem', color: '#64748b' }}>{__('Company / Org', 'dragwyb-click-to-chat')}</strong>
								<span style={{ fontSize: '0.85rem', color: '#1e293b' }}>{activeLead.company || '—'}</span>
							</div>

							<div>
								<strong style={{ display: 'block', fontSize: '0.75rem', color: '#64748b' }}>{__('Company Size', 'dragwyb-click-to-chat')}</strong>
								<span style={{ fontSize: '0.85rem', color: '#1e293b' }}>{activeLead.company_size || '—'}</span>
							</div>

							<div>
								<strong style={{ display: 'block', fontSize: '0.75rem', color: '#64748b' }}>{__('Budget Range', 'dragwyb-click-to-chat')}</strong>
								<span style={{ fontSize: '0.85rem', color: '#0f766e', fontWeight: 600 }}>{activeLead.budget || '—'}</span>
							</div>

							<div>
								<strong style={{ display: 'block', fontSize: '0.75rem', color: '#64748b' }}>{__('Timeline', 'dragwyb-click-to-chat')}</strong>
								<span style={{ fontSize: '0.85rem', color: '#1e293b' }}>{activeLead.timeline || '—'}</span>
							</div>

							<div>
								<strong style={{ display: 'block', fontSize: '0.75rem', color: '#64748b' }}>{__('Interest', 'dragwyb-click-to-chat')}</strong>
								<span style={{ fontSize: '0.85rem', color: '#1e293b' }}>{activeLead.interest || '—'}</span>
							</div>

							<div>
								<strong style={{ display: 'block', fontSize: '0.75rem', color: '#64748b' }}>{__('AI Intent', 'dragwyb-click-to-chat')}</strong>
								<span style={{ fontSize: '0.85rem', fontWeight: 600, color: getIntentBadge(activeLead.intent_level).color }}>
									{getIntentBadge(activeLead.intent_level).label}
								</span>
							</div>
						</div>

						{/* Explainable Score Breakdown */}
						{parseScoreBreakdown(activeLead.score_breakdown).length > 0 && (
							<div style={{ marginBottom: '1.25rem', background: '#ffffff', border: '1px solid #e2e8f0', borderRadius: '8px', padding: '1rem' }}>
								<div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '0.6rem' }}>
									<strong style={{ fontSize: '0.9rem', color: '#0f172a' }}>
										{__('Explainable Qualification Score Breakdown', 'dragwyb-click-to-chat')}
									</strong>
									<span style={{ fontSize: '0.9rem', fontWeight: 700, color: '#065f46' }}>
										{activeLead.score || 0} / 100
									</span>
								</div>
								<div style={{ display: 'flex', flexDirection: 'column', gap: '0.45rem' }}>
									{parseScoreBreakdown(activeLead.score_breakdown).map((f, fIdx) => (
										<div key={fIdx} style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', fontSize: '0.8rem', padding: '0.35rem 0.5rem', background: '#f8fafc', borderRadius: '6px' }}>
											<div>
												<span style={{ fontWeight: 600, color: '#334155' }}>{f.factor}</span>
												{f.detail && <span style={{ color: '#64748b', marginLeft: '0.4rem' }}>({f.detail})</span>}
											</div>
											<span style={{ fontWeight: 700, color: f.points > 0 ? '#10b981' : '#94a3b8' }}>
												+{f.points} / {f.max}
											</span>
										</div>
									))}
								</div>
							</div>
						)}

						<div style={{ marginBottom: '1.25rem' }}>
							<strong style={{ display: 'block', fontSize: '0.85rem', color: '#475569', marginBottom: '0.35rem' }}>
								{__('Requirement / Message', 'dragwyb-click-to-chat')}
							</strong>
							<div style={{ background: '#ffffff', border: '1px solid #e2e8f0', borderRadius: '8px', padding: '0.85rem', fontSize: '0.9rem', color: '#1e293b', whiteSpace: 'pre-wrap', lineHeight: 1.5 }}>
								{activeLead.requirement || __('No message provided.', 'dragwyb-click-to-chat')}
							</div>
						</div>

						{activeLead.source_url && (
							<div style={{ marginBottom: '1.25rem', fontSize: '0.85rem' }}>
								<strong style={{ color: '#64748b' }}>{__('Source URL: ', 'dragwyb-click-to-chat')}</strong>
								<a href={activeLead.source_url} target="_blank" rel="noopener noreferrer" style={{ color: '#2563eb' }}>
									{activeLead.source_url}
								</a>
							</div>
						)}

						<div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', paddingTop: '1rem', borderTop: '1px solid #e2e8f0' }}>
							<div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
								<label style={{ fontSize: '0.85rem', fontWeight: 600, color: '#475569' }}>
									{__('Status:', 'dragwyb-click-to-chat')}
								</label>
								<select
									value={activeLead.status || 'new'}
									onChange={(e) => handleStatusChange(activeLead.id, e.target.value)}
									style={{ fontSize: '0.85rem', borderRadius: '6px' }}
								>
									<option value="new">{__('New', 'dragwyb-click-to-chat')}</option>
									<option value="contacted">{__('Contacted', 'dragwyb-click-to-chat')}</option>
									<option value="qualified">{__('Qualified', 'dragwyb-click-to-chat')}</option>
									<option value="converted">{__('Converted', 'dragwyb-click-to-chat')}</option>
									<option value="discarded">{__('Discarded', 'dragwyb-click-to-chat')}</option>
								</select>
							</div>

							<button
								type="button"
								className="button button-primary"
								onClick={() => setActiveLead(null)}
							>
								{__('Close', 'dragwyb-click-to-chat')}
							</button>
						</div>
					</div>
				</div>
			)}

			{/* Delete Confirmation Modal */}
			{leadToDelete && (
				<ConfirmModal
					title={__('Delete Lead', 'dragwyb-click-to-chat')}
					message={sprintf(
						/* translators: %s: Lead name/email */
						__('Are you sure you want to permanently delete lead "%s"? This action cannot be undone.', 'dragwyb-click-to-chat'),
						leadToDelete.name || leadToDelete.email || `#${leadToDelete.id}`
					)}
					confirmText={__('Delete Lead', 'dragwyb-click-to-chat')}
					cancelText={__('Cancel', 'dragwyb-click-to-chat')}
					isDestructive={true}
					onConfirm={handleDeleteLead}
					onCancel={() => setLeadToDelete(null)}
				/>
			)}
		</div>
	);
}
