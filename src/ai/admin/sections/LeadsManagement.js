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
import { Modal, EmptyState, Pagination } from '../components';

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
				setTotal((t) => Math.max(0, t - 1));
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
			const queryParams = new URLSearchParams({
				limit: '1000',
				page: '1',
				status: statusFilter,
				search: searchQuery,
			});
			const res = await apiFetch({ path: `/dctc-ai/v1/leads?${queryParams.toString()}` });
			if (res?.success && res.leads?.length) {
				const headers = ['ID', 'Name', 'Email', 'Phone', 'Company', 'Company Size', 'Budget', 'Timeline', 'Interest', 'Score', 'Intent', 'Status', 'Date', 'Requirement'];
				const rows = res.leads.map((l) => [
					l.id,
					`"${(l.name || '').replace(/"/g, '""')}"`,
					`"${(l.email || '').replace(/"/g, '""')}"`,
					`"${(l.phone || '').replace(/"/g, '""')}"`,
					`"${(l.company || '').replace(/"/g, '""')}"`,
					`"${(l.company_size || '').replace(/"/g, '""')}"`,
					`"${(l.budget || '').replace(/"/g, '""')}"`,
					`"${(l.timeline || '').replace(/"/g, '""')}"`,
					`"${(l.interest || '').replace(/"/g, '""')}"`,
					l.score || 0,
					l.intent_level || 'general',
					l.status || 'new',
					`"${l.created_at || ''}"`,
					`"${(l.requirement || '').replace(/"/g, '""')}"`,
				]);

				const csvContent = [headers.join(','), ...rows.map((e) => e.join(','))].join('\n');
				const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
				const url = URL.createObjectURL(blob);
				const link = document.createElement('a');
				link.setAttribute('href', url);
				link.setAttribute('download', `leads-export-${new Date().toISOString().slice(0, 10)}.csv`);
				document.body.appendChild(link);
				link.click();
				document.body.removeChild(link);
				if (showNotice) {
					showNotice(sprintf(__('Exported %d leads successfully.', 'dragwyb-click-to-chat'), res.leads.length), 'success');
				}
			} else {
				if (showNotice) {
					showNotice(__('No leads available to export.', 'dragwyb-click-to-chat'), 'info');
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

	const metrics = useMemo(() => {
		const highQuality = leads.filter((l) => Number(l.score) >= 70).length;
		const urgent = leads.filter((l) => l.intent_level === 'urgent' || l.intent_level === 'high').length;
		const qualified = leads.filter((l) => l.status === 'qualified' || l.status === 'converted').length;
		return { highQuality, urgent, qualified };
	}, [leads]);

	return (
		<div className="dctc-ai-leads-management" style={{ maxWidth: '1200px', margin: '0 auto', paddingBottom: '3rem' }}>
			{/* Header */}
			<div style={{ marginBottom: '1.5rem', display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', flexWrap: 'wrap', gap: '1rem' }}>
				<div>
					<h1 style={{ fontSize: '1.5rem', fontWeight: 700, margin: '0 0 0.4rem', color: '#0f172a' }}>
						{__('Lead Capture & Scoring', 'dragwyb-click-to-chat')}
					</h1>
					<p style={{ margin: 0, color: '#64748b', fontSize: '0.92rem' }}>
						{__('Monitor, qualify, and action high-intent leads captured autonomously by your AI Assistant.', 'dragwyb-click-to-chat')}
					</p>
				</div>
			</div>

			{/* Metric Cards */}
			<div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(220px, 1fr))', gap: '1rem', marginBottom: '1.5rem' }}>
				<div style={{ background: '#ffffff', border: '1px solid #e2e8f0', borderRadius: '10px', padding: '1.25rem', boxShadow: '0 1px 3px rgba(0,0,0,0.05)' }}>
					<span style={{ fontSize: '0.8rem', fontWeight: 600, color: '#64748b', textTransform: 'uppercase' }}>
						{__('Total Leads Captured', 'dragwyb-click-to-chat')}
					</span>
					<div style={{ fontSize: '1.75rem', fontWeight: 700, color: '#0f172a', marginTop: '0.25rem' }}>
						{total}
					</div>
				</div>

				<div style={{ background: '#ffffff', border: '1px solid #e2e8f0', borderRadius: '10px', padding: '1.25rem', boxShadow: '0 1px 3px rgba(0,0,0,0.05)' }}>
					<span style={{ fontSize: '0.8rem', fontWeight: 600, color: '#059669', textTransform: 'uppercase' }}>
						{__('High-Quality Score (70+)', 'dragwyb-click-to-chat')}
					</span>
					<div style={{ fontSize: '1.75rem', fontWeight: 700, color: '#059669', marginTop: '0.25rem' }}>
						{metrics.highQuality}
					</div>
				</div>

				<div style={{ background: '#ffffff', border: '1px solid #e2e8f0', borderRadius: '10px', padding: '1.25rem', boxShadow: '0 1px 3px rgba(0,0,0,0.05)' }}>
					<span style={{ fontSize: '0.8rem', fontWeight: 600, color: '#dc2626', textTransform: 'uppercase' }}>
						{__('High / Urgent Intent', 'dragwyb-click-to-chat')}
					</span>
					<div style={{ fontSize: '1.75rem', fontWeight: 700, color: '#dc2626', marginTop: '0.25rem' }}>
						{metrics.urgent}
					</div>
				</div>

				<div style={{ background: '#ffffff', border: '1px solid #e2e8f0', borderRadius: '10px', padding: '1.25rem', boxShadow: '0 1px 3px rgba(0,0,0,0.05)' }}>
					<span style={{ fontSize: '0.8rem', fontWeight: 600, color: '#2563eb', textTransform: 'uppercase' }}>
						{__('Qualified / Converted', 'dragwyb-click-to-chat')}
					</span>
					<div style={{ fontSize: '1.75rem', fontWeight: 700, color: '#2563eb', marginTop: '0.25rem' }}>
						{metrics.qualified}
					</div>
				</div>
			</div>

			{/* Filters and Controls */}
			<div style={{ background: '#ffffff', border: '1px solid #e2e8f0', borderRadius: '10px', padding: '1rem', marginBottom: '1.25rem', display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: '0.75rem' }}>
				<div style={{ display: 'flex', alignItems: 'center', gap: '0.75rem', flexWrap: 'wrap' }}>
					{/* Search */}
					<div style={{ position: 'relative' }}>
						<input
							type="text"
							placeholder={__('Search leads by name, email, company...', 'dragwyb-click-to-chat')}
							value={searchQuery}
							onChange={(e) => {
								setSearchQuery(e.target.value);
								setPage(1);
							}}
							style={{ width: '280px', borderRadius: '6px', paddingLeft: '28px' }}
						/>
						<span className="dashicons dashicons-search" style={{ position: 'absolute', left: '6px', top: '50%', transform: 'translateY(-50%)', color: '#94a3b8', fontSize: '18px' }} />
					</div>

					{/* Status Filters */}
					<div style={{ display: 'flex', gap: '0.35rem', flexWrap: 'wrap' }}>
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
					<EmptyState
						icon="dashicons-id"
						title={__('No Leads Found', 'dragwyb-click-to-chat')}
						description={
							searchQuery || statusFilter !== 'all'
								? __('No leads matched your search or status filter criteria.', 'dragwyb-click-to-chat')
								: __('When visitors share their contact details through the AI Chatbot lead form, they will appear here.', 'dragwyb-click-to-chat')
						}
					/>
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

				{/* Reusable Pagination */}
				<Pagination
					currentPage={page}
					totalPages={pages}
					totalItems={total}
					onPageChange={setPage}
				/>
			</div>

			{/* View Details Modal via Reusable Modal Component */}
			<Modal
				isOpen={!!activeLead}
				onClose={() => setActiveLead(null)}
				maxWidth="720px"
				title={activeLead?.name || __('Chat Visitor', 'dragwyb-click-to-chat')}
				subtitle={activeLead ? `${__('Captured on', 'dragwyb-click-to-chat')} ${formatDate(activeLead.created_at)}` : ''}
				footer={
					activeLead && (
						<div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', width: '100%' }}>
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
					)
				}
			>
				{activeLead && (() => {
					const conversation = Array.isArray(activeLead.conversation) ? activeLead.conversation : [];

					return (
						<div>
							{/* Contact Information & AI Intent Details Grid */}
							<div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(130px, 1fr))', gap: '0.75rem', marginBottom: '1.25rem', background: '#f8fafc', padding: '1rem', borderRadius: '8px', border: '1px solid #e2e8f0' }}>
								<div>
									<strong style={{ display: 'block', fontSize: '0.75rem', color: '#64748b' }}>{__('Email', 'dragwyb-click-to-chat')}</strong>
									{activeLead.email ? (
										<a href={`mailto:${activeLead.email}`} style={{ fontSize: '0.85rem', color: '#2563eb', wordBreak: 'break-all' }}>{activeLead.email}</a>
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

							{/* Full Conversation History Stream between User, Human Agent, and AI Response */}
							<div style={{ marginBottom: '1.25rem' }}>
								<div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '0.6rem', flexWrap: 'wrap', gap: '0.5rem' }}>
									<div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
										<strong style={{ fontSize: '0.92rem', color: '#0f172a', display: 'flex', alignItems: 'center', gap: '0.4rem' }}>
											<span className="dashicons dashicons-format-chat" style={{ color: '#2563eb', fontSize: '18px', lineHeight: '18px' }} />
											{__('Full Conversation History', 'dragwyb-click-to-chat')}
										</strong>
										{conversation.length > 0 && (
											<span style={{ fontSize: '0.75rem', fontWeight: 600, padding: '0.15rem 0.5rem', background: '#e2e8f0', color: '#475569', borderRadius: '999px' }}>
												{sprintf(__('%d messages', 'dragwyb-click-to-chat'), conversation.length)}
											</span>
										)}
									</div>
									<div style={{ display: 'flex', gap: '0.6rem', fontSize: '0.75rem', flexWrap: 'wrap' }}>
										<span style={{ display: 'inline-flex', alignItems: 'center', gap: '0.25rem', color: '#1e40af' }}>
											<span style={{ width: '8px', height: '8px', borderRadius: '50%', background: '#3b82f6' }} />
											{__('User / Visitor', 'dragwyb-click-to-chat')}
										</span>
										<span style={{ display: 'inline-flex', alignItems: 'center', gap: '0.25rem', color: '#6b21a8' }}>
											<span style={{ width: '8px', height: '8px', borderRadius: '50%', background: '#a855f7' }} />
											{__('AI Response', 'dragwyb-click-to-chat')}
										</span>
										<span style={{ display: 'inline-flex', alignItems: 'center', gap: '0.25rem', color: '#15803d' }}>
											<span style={{ width: '8px', height: '8px', borderRadius: '50%', background: '#22c55e' }} />
											{__('Human Agent', 'dragwyb-click-to-chat')}
										</span>
									</div>
								</div>

								<div
									style={{
										background: '#f8fafc',
										border: '1px solid #e2e8f0',
										borderRadius: '10px',
										padding: '0.85rem',
										maxHeight: '380px',
										overflowY: 'auto',
										display: 'flex',
										flexDirection: 'column',
										gap: '0.75rem',
									}}
								>
									{conversation.length === 0 ? (
										<div style={{ textAlign: 'center', padding: '2rem 1rem', color: '#64748b' }}>
											<span className="dashicons dashicons-format-chat" style={{ fontSize: '32px', width: '32px', height: '32px', color: '#cbd5e1', marginBottom: '0.5rem' }} />
											<div style={{ fontSize: '0.9rem', fontWeight: 600, color: '#334155' }}>
												{__('No chat messages recorded', 'dragwyb-click-to-chat')}
											</div>
											<div style={{ fontSize: '0.8rem', color: '#94a3b8', marginTop: '0.25rem' }}>
												{__('The visitor submitted this inquiry directly.', 'dragwyb-click-to-chat')}
											</div>
										</div>
									) : (
										conversation.map((msg, idx) => {
											const isUser = msg.sender_type === 'customer' || msg.role === 'user';
											const isHuman = msg.sender_type === 'human_agent' || msg.sender_type === 'agent' || msg.sender_type === 'support';
											const isAI = !isUser && !isHuman;

											let bubbleBg = '#ffffff';
											let bubbleBorder = '#e2e8f0';
											let tagBg = '#f1f5f9';
											let tagColor = '#475569';
											let tagLabel = __('System', 'dragwyb-click-to-chat');
											let icon = '👤';
											let defaultName = __('Visitor', 'dragwyb-click-to-chat');

											if (isUser) {
												bubbleBg = '#eff6ff';
												bubbleBorder = '#bfdbfe';
												tagBg = '#dbeafe';
												tagColor = '#1e40af';
												tagLabel = __('User / Visitor', 'dragwyb-click-to-chat');
												icon = '👤';
												defaultName = activeLead.name || __('Visitor', 'dragwyb-click-to-chat');
											} else if (isHuman) {
												bubbleBg = '#f0fdf4';
												bubbleBorder = '#bbf7d0';
												tagBg = '#dcfce7';
												tagColor = '#15803d';
												tagLabel = __('Support Agent (Human)', 'dragwyb-click-to-chat');
												icon = '👨‍💼';
												defaultName = __('Support Agent', 'dragwyb-click-to-chat');
											} else if (isAI) {
												bubbleBg = '#ffffff';
												bubbleBorder = '#e2e8f0';
												tagBg = '#ede9fe';
												tagColor = '#6b21a8';
												tagLabel = __('AI Response', 'dragwyb-click-to-chat');
												icon = '🤖';
												defaultName = __('AI Assistant', 'dragwyb-click-to-chat');
											}

											return (
												<div
													key={idx}
													style={{
														background: bubbleBg,
														border: `1px solid ${bubbleBorder}`,
														borderRadius: '8px',
														padding: '0.75rem 0.85rem',
														boxShadow: '0 1px 2px rgba(0,0,0,0.03)',
													}}
												>
													<div
														style={{
															display: 'flex',
															justifyContent: 'space-between',
															alignItems: 'center',
															marginBottom: '0.4rem',
															flexWrap: 'wrap',
															gap: '0.35rem',
														}}
													>
														<div style={{ display: 'flex', alignItems: 'center', gap: '0.4rem' }}>
															<span style={{ fontSize: '1rem' }}>{icon}</span>
															<strong style={{ fontSize: '0.85rem', color: '#0f172a' }}>
																{msg.sender_name || defaultName}
															</strong>
															<span
																style={{
																	fontSize: '0.7rem',
																	fontWeight: 600,
																	padding: '0.1rem 0.45rem',
																	borderRadius: '999px',
																	background: tagBg,
																	color: tagColor,
																}}
															>
																{tagLabel}
															</span>
														</div>
														{msg.created_at && (
															<span style={{ fontSize: '0.75rem', color: '#94a3b8' }}>
																{formatDate(msg.created_at)}
															</span>
														)}
													</div>
													<div
														style={{
															fontSize: '0.88rem',
															color: '#1e293b',
															lineHeight: 1.55,
															whiteSpace: 'pre-wrap',
															wordBreak: 'break-word',
														}}
													>
														{msg.content}
													</div>
												</div>
											);
										})
									)}
								</div>
							</div>

							{activeLead.requirement && conversation.length > 1 && (
								<div style={{ marginBottom: '1.25rem' }}>
									<strong style={{ display: 'block', fontSize: '0.85rem', color: '#475569', marginBottom: '0.35rem' }}>
										{__('Submitted Requirement Summary', 'dragwyb-click-to-chat')}
									</strong>
									<div style={{ background: '#ffffff', border: '1px solid #e2e8f0', borderRadius: '8px', padding: '0.85rem', fontSize: '0.88rem', color: '#1e293b', whiteSpace: 'pre-wrap', lineHeight: 1.5 }}>
										{activeLead.requirement}
									</div>
								</div>
							)}

							{activeLead.source_url && (
								<div style={{ marginBottom: '1.25rem', fontSize: '0.85rem' }}>
									<strong style={{ color: '#64748b' }}>{__('Source URL: ', 'dragwyb-click-to-chat')}</strong>
									<a href={activeLead.source_url} target="_blank" rel="noopener noreferrer" style={{ color: '#2563eb' }}>
										{activeLead.source_url}
									</a>
								</div>
							)}
						</div>
					);
				})()}
			</Modal>

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
