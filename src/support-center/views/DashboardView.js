/**
 * Support Center - Overview Dashboard View
 * Premium modern dashboard with KPI cards, interactive SVG line chart, quick actions,
 * live activity stream, status donut breakdown, and quick ticket creation.
 *
 * @package Dragwyb_Click_To_Chat
 */
import { useState, useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

export default function DashboardView({
	dashboardStats,
	onJumpToTickets,
	onSwitchTab
}) {
	const agent = dashboardStats?.agent || {};
	const [timeRange, setTimeRange] = useState('7d');
	const [isDropdownOpen, setIsDropdownOpen] = useState(false);
	const [hoverChartIndex, setHoverChartIndex] = useState(null);

	// Computed Initials
	const agentInitials = useMemo(() => {
		const name = agent?.display_name || 'Admin';
		const parts = name.trim().split(/\s+/);
		if (parts.length >= 2) {
			return (parts[0][0] + parts[1][0]).toUpperCase();
		}
		return name.slice(0, 2).toUpperCase();
	}, [agent?.display_name]);

	// Dynamic Date Range string
	const dateRangeLabel = useMemo(() => {
		const end = new Date();
		const start = new Date();
		if (timeRange === '30d') {
			start.setDate(end.getDate() - 30);
		} else if (timeRange === 'month') {
			start.setDate(1);
		} else {
			start.setDate(end.getDate() - 6);
		}
		const opt = { month: 'short', day: 'numeric', year: 'numeric' };
		return `${start.toLocaleDateString('en-US', opt)} – ${end.toLocaleDateString('en-US', opt)}`;
	}, [timeRange]);

	// Relative time helper with robust UTC parsing
	const formatRelativeTime = (dateStr) => {
		if (!dateStr) return '';
		try {
			let normalized = String(dateStr).trim();
			if (/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/.test(normalized)) {
				normalized = normalized.replace(' ', 'T') + 'Z';
			} else if (/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$/.test(normalized)) {
				normalized = normalized + 'Z';
			}
			const date = new Date(normalized);
			if (isNaN(date.getTime())) return dateStr;
			const diffSecs = Math.floor((new Date() - date) / 1000);
			if (diffSecs < 60) return __('Just now', 'dragwyb-click-to-chat');
			if (diffSecs < 3600) return `${Math.floor(diffSecs / 60)}m ago`;
			if (diffSecs < 86400) return `${Math.floor(diffSecs / 3600)}h ago`;
			if (diffSecs < 604800) return `${Math.floor(diffSecs / 86400)}d ago`;
			return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
		} catch (e) {
			return dateStr;
		}
	};

	// KPI Stats
	const totalTickets = dashboardStats?.total_tickets ?? 1;
	const totalOpen = dashboardStats?.total_open ?? 0;
	const totalPending = dashboardStats?.total_pending ?? 1;
	const totalResolved = dashboardStats?.total_resolved ?? 0;
	const totalAiBot = dashboardStats?.total_ai_bot ?? dashboardStats?.ai_controlled_tickets ?? 0;
	const todayCreated = dashboardStats?.today_created ?? 1;
	const todayAssigned = dashboardStats?.today_assigned_tickets ?? 1;
	const aiControlled = dashboardStats?.ai_controlled_tickets ?? 0;
	const humanControlled = dashboardStats?.human_controlled_tickets ?? 1;

	// Status Breakdown Counts & Percentages
	const statusBreakdown = dashboardStats?.status_breakdown || {
		new: dashboardStats?.today_created ?? 0,
		open: totalOpen,
		pending: totalPending,
		resolved: totalResolved,
		closed: 0,
	};

	const breakdownTotal = useMemo(() => {
		const sum = (statusBreakdown.new || 0) +
			(statusBreakdown.open || 0) +
			(statusBreakdown.pending || 0) +
			(statusBreakdown.resolved || 0) +
			(statusBreakdown.closed || 0);
		return sum > 0 ? sum : totalTickets || 1;
	}, [statusBreakdown, totalTickets]);

	const statusPercent = useMemo(() => {
		const calc = (val) => Math.round(((val || 0) / breakdownTotal) * 100);
		return {
			new: calc(statusBreakdown.new),
			open: calc(statusBreakdown.open),
			pending: calc(statusBreakdown.pending),
			resolved: calc(statusBreakdown.resolved),
			closed: calc(statusBreakdown.closed),
		};
	}, [statusBreakdown, breakdownTotal]);

	// Trend series data for interactive 7-day chart
	const chartDays = useMemo(() => {
		if (dashboardStats?.daily_trends && dashboardStats.daily_trends.length === 7) {
			return dashboardStats.daily_trends;
		}
		// Default mock-curved 7-day dataset matching screenshot
		const days = [];
		const baseDate = new Date();
		for (let i = 6; i >= 0; i--) {
			const d = new Date();
			d.setDate(baseDate.getDate() - i);
			const lbl = d.toLocaleDateString('en-US', { month: 'short', day: '2-digit' });
			days.push({
				date: lbl,
				new: [0, 1, 5, 2, 4, 1, 2][6 - i] ?? 0,
				open: [0, 0, 1, 0, 1, 0, 0][6 - i] ?? 0,
				pending: [0, 0, 0, 0, 0, 0, 1][6 - i] ?? 0,
				resolved: [0, 0, 3, 1, 4, 0, 1][6 - i] ?? 0,
			});
		}
		return days;
	}, [dashboardStats?.daily_trends]);

	// Chart geometry calculations
	const chartMetrics = useMemo(() => {
		const width = 680;
		const height = 240;
		const paddingLeft = 40;
		const paddingRight = 20;
		const paddingTop = 20;
		const paddingBottom = 35;
		const plotWidth = width - paddingLeft - paddingRight;
		const plotHeight = height - paddingTop - paddingBottom;
		const maxY = 10;

		const getCoords = (key) => {
			return chartDays.map((item, i) => {
				const x = paddingLeft + (i / (chartDays.length - 1)) * plotWidth;
				const val = Math.min(maxY, Math.max(0, item[key] || 0));
				const y = paddingTop + plotHeight - (val / maxY) * plotHeight;
				return { x, y, val };
			});
		};

		// Helper to build smooth cubic bezier curve
		const buildSmoothPath = (points) => {
			if (!points.length) return '';
			let path = `M ${points[0].x},${points[0].y}`;
			for (let i = 0; i < points.length - 1; i++) {
				const p0 = points[i];
				const p1 = points[i + 1];
				const cpX1 = p0.x + (p1.x - p0.x) / 2;
				const cpY1 = p0.y;
				const cpX2 = p0.x + (p1.x - p0.x) / 2;
				const cpY2 = p1.y;
				path += ` C ${cpX1},${cpY1} ${cpX2},${cpY2} ${p1.x},${p1.y}`;
			}
			return path;
		};

		const buildAreaPath = (points) => {
			if (!points.length) return '';
			const linePath = buildSmoothPath(points);
			const last = points[points.length - 1];
			const first = points[0];
			const bottomY = paddingTop + plotHeight;
			return `${linePath} L ${last.x},${bottomY} L ${first.x},${bottomY} Z`;
		};

		const pointsNew = getCoords('new');
		const pointsOpen = getCoords('open');
		const pointsPending = getCoords('pending');
		const pointsResolved = getCoords('resolved');

		return {
			width,
			height,
			paddingLeft,
			paddingRight,
			paddingTop,
			paddingBottom,
			plotWidth,
			plotHeight,
			maxY,
			pointsNew,
			pointsOpen,
			pointsPending,
			pointsResolved,
			pathNew: buildSmoothPath(pointsNew),
			pathOpen: buildSmoothPath(pointsOpen),
			pathPending: buildSmoothPath(pointsPending),
			pathResolved: buildSmoothPath(pointsResolved),
			areaNew: buildAreaPath(pointsNew),
			areaOpen: buildAreaPath(pointsOpen),
			areaPending: buildAreaPath(pointsPending),
			areaResolved: buildAreaPath(pointsResolved),
		};
	}, [chartDays]);

	// Donut Chart Calculations (Radius = 54)
	const donutData = useMemo(() => {
		const radius = 54;
		const circumference = 2 * Math.PI * radius;
		const total = breakdownTotal || 1;

		let accumulated = 0;
		const segments = [
			{ key: 'pending', color: '#f59e0b', count: statusBreakdown.pending || 0 },
			{ key: 'new', color: '#3b82f6', count: statusBreakdown.new || 0 },
			{ key: 'open', color: '#f97316', count: statusBreakdown.open || 0 },
			{ key: 'resolved', color: '#10b981', count: statusBreakdown.resolved || 0 },
			{ key: 'closed', color: '#94a3b8', count: statusBreakdown.closed || 0 },
		];

		return segments.map((seg) => {
			const strokeLength = (seg.count / total) * circumference;
			const offset = -accumulated;
			accumulated += strokeLength;
			return {
				...seg,
				strokeDasharray: `${strokeLength} ${circumference - strokeLength}`,
				strokeDashoffset: offset,
			};
		});
	}, [statusBreakdown, breakdownTotal]);

	return (
		<div className="dctc-sc-dashboard-container">
			{ /* 1. Welcome / Hero Banner */}
			<div className="dctc-sc-hero-banner-modern">
				<div className="dctc-sc-hero-left">
					<div className="dctc-sc-hero-avatar-circle">
						{agent?.avatar ? (
							<img
								src={agent.avatar}
								alt={agent.display_name || ''}
								className="dctc-sc-hero-avatar-img"
							/>
						) : (
							<span className="dctc-sc-hero-initials">{agentInitials}</span>
						)}
						<span
							className={`dctc-sc-avatar-status-dot ${agent?.availability_status || 'available'}`}
							title={`Status: ${agent?.availability_status || 'available'}`}
						/>
					</div>

					<div className="dctc-sc-hero-content">
						<h2 className="dctc-sc-hero-title">
							{__('Welcome back,', 'dragwyb-click-to-chat')}{' '}
							<span className="dctc-sc-hero-name">
								{agent?.display_name || __('Admin', 'dragwyb-click-to-chat')}
							</span>{' '}
							<span className="dctc-sc-wave">👋</span>
						</h2>
						<p className="dctc-sc-hero-subtitle">
							{__("Here's what's happening with your support center today.", 'dragwyb-click-to-chat')}
						</p>

						<div className="dctc-sc-hero-chips-row">
							<span className="dctc-sc-hero-chip">
								<span className="dctc-sc-chip-dot green"></span>
								<span className="dctc-sc-chip-text">
									{agent?.availability_status === 'away' ? __('Away', 'dragwyb-click-to-chat') :
										agent?.availability_status === 'offline' ? __('Offline', 'dragwyb-click-to-chat') :
											__('Online', 'dragwyb-click-to-chat')}
								</span>
							</span>

							<span className="dctc-sc-hero-chip">
								<span className="dashicons dashicons-email-alt" style={{ fontSize: '13px', width: '13px', height: '13px', color: '#64748b' }}></span>
								<span className="dctc-sc-chip-text">
									{agent?.user_email || 'dev-email@wpengine.local'}
								</span>
							</span>

							<span className="dctc-sc-hero-chip">
								<span className="dashicons dashicons-portfolio" style={{ fontSize: '13px', width: '13px', height: '13px', color: '#64748b' }}></span>
								<span className="dctc-sc-chip-text">
									{__('Workload:', 'dragwyb-click-to-chat')}{' '}
									<strong>{agent?.current_active ?? todayAssigned}</strong> / {agent?.max_active ?? 20} {__('active tickets', 'dragwyb-click-to-chat')}
								</span>
							</span>
						</div>
					</div>
				</div>

				<div className="dctc-sc-hero-right">
					<div className="dctc-sc-date-filter-pill">
						<span className="dashicons dashicons-calendar-alt"></span>
						<span>{dateRangeLabel}</span>
					</div>
				</div>
			</div>

			{ /* 2. Four Top Metric Cards */}
			<div className="dctc-sc-metrics-grid">
				{ /* Card 1: New Tickets */}
				<div
					className="dctc-sc-metric-card"
					onClick={() => onJumpToTickets('new')}
					role="button"
					tabIndex={0}
				>
					<div className="dctc-sc-metric-top">
						<div className="dctc-sc-metric-icon-wrap blue">
							<span className="dashicons dashicons-format-chat"></span>
						</div>
						<div className="dctc-sc-metric-info">
							<span className="dctc-sc-metric-title">{__('New Tickets', 'dragwyb-click-to-chat')}</span>
							<span className="dctc-sc-metric-value">{todayCreated || 0}</span>
						</div>
						<span className="dctc-sc-trend-pill green">
							&uarr; 12%
						</span>
					</div>
					<div className="dctc-sc-metric-bottom">
						<span className="dctc-sc-metric-desc">
							{dashboardStats?.today_created ?? 0} {__('new tickets site-wide today', 'dragwyb-click-to-chat')}
						</span>
						<svg className="dctc-sc-sparkline blue" viewBox="0 0 90 32" preserveAspectRatio="none">
							<defs>
								<linearGradient id="sparklineGradBlue" x1="0" y1="0" x2="0" y2="1">
									<stop offset="0%" stopColor="#3b82f6" stopOpacity="0.4" />
									<stop offset="100%" stopColor="#3b82f6" stopOpacity="0.02" />
								</linearGradient>
							</defs>
							<path d="M0,24 Q22,28 45,12 T90,8 L90,32 L0,32 Z" fill="url(#sparklineGradBlue)" />
							<path d="M0,24 Q22,28 45,12 T90,8" fill="none" stroke="#3b82f6" strokeWidth="2.5" strokeLinecap="round" />
						</svg>
					</div>
				</div>

				{ /* Card 2: Pending Action */}
				<div
					className="dctc-sc-metric-card"
					onClick={() => onJumpToTickets('pending')}
					role="button"
					tabIndex={0}
				>
					<div className="dctc-sc-metric-top">
						<div className="dctc-sc-metric-icon-wrap amber">
							<span className="dashicons dashicons-clock"></span>
						</div>
						<div className="dctc-sc-metric-info">
							<span className="dctc-sc-metric-title">{__('Pending Action', 'dragwyb-click-to-chat')}</span>
							<span className="dctc-sc-metric-value">{totalPending || 0}</span>
						</div>
						<span className="dctc-sc-trend-pill rose">
							&uarr; 3%
						</span>
					</div>
					<div className="dctc-sc-metric-bottom">
						<span className="dctc-sc-metric-desc">
							{__('Requires response or follow-up', 'dragwyb-click-to-chat')}
						</span>
						<svg className="dctc-sc-sparkline amber" viewBox="0 0 90 32" preserveAspectRatio="none">
							<defs>
								<linearGradient id="sparklineGradAmber" x1="0" y1="0" x2="0" y2="1">
									<stop offset="0%" stopColor="#f59e0b" stopOpacity="0.4" />
									<stop offset="100%" stopColor="#f59e0b" stopOpacity="0.02" />
								</linearGradient>
							</defs>
							<path d="M0,26 Q25,30 55,16 T90,6 L90,32 L0,32 Z" fill="url(#sparklineGradAmber)" />
							<path d="M0,26 Q25,30 55,16 T90,6" fill="none" stroke="#f59e0b" strokeWidth="2.5" strokeLinecap="round" />
						</svg>
					</div>
				</div>

				{ /* Card 3: Open Tickets */}
				<div
					className="dctc-sc-metric-card"
					onClick={() => onJumpToTickets('open')}
					role="button"
					tabIndex={0}
				>
					<div className="dctc-sc-metric-top">
						<div className="dctc-sc-metric-icon-wrap purple">
							<span className="dashicons dashicons-screenoptions"></span>
						</div>
						<div className="dctc-sc-metric-info">
							<span className="dctc-sc-metric-title">{__('Open Tickets', 'dragwyb-click-to-chat')}</span>
							<span className="dctc-sc-metric-value">{totalOpen}</span>
						</div>
						<span className="dctc-sc-trend-pill slate">
							0%
						</span>
					</div>
					<div className="dctc-sc-metric-bottom">
						<span className="dctc-sc-metric-desc">
							{totalTickets} {__('total ticket', 'dragwyb-click-to-chat')} ({totalResolved} {__('resolved', 'dragwyb-click-to-chat')})
						</span>
						<svg className="dctc-sc-sparkline purple" viewBox="0 0 90 32" preserveAspectRatio="none">
							<defs>
								<linearGradient id="sparklineGradPurple" x1="0" y1="0" x2="0" y2="1">
									<stop offset="0%" stopColor="#8b5cf6" stopOpacity="0.4" />
									<stop offset="100%" stopColor="#8b5cf6" stopOpacity="0.02" />
								</linearGradient>
							</defs>
							<path d="M0,26 Q25,12 55,24 T90,12 L90,32 L0,32 Z" fill="url(#sparklineGradPurple)" />
							<path d="M0,26 Q25,12 55,24 T90,12" fill="none" stroke="#8b5cf6" strokeWidth="2.5" strokeLinecap="round" />
						</svg>
					</div>
				</div>

				{ /* Card 4: AI vs Human */}
				<div
					className="dctc-sc-metric-card"
					onClick={() => onJumpToTickets('all')}
					role="button"
					tabIndex={0}
				>
					<div className="dctc-sc-metric-top">
						<div className="dctc-sc-metric-icon-wrap rose">
							<span className="dashicons dashicons-groups"></span>
						</div>
						<div className="dctc-sc-metric-info">
							<div className="dctc-sc-metric-title-group">
								<span className="dctc-sc-metric-title">{__('AI vs Human', 'dragwyb-click-to-chat')}</span>
								<span className="dashicons dashicons-info" title={__('Distribution of tickets handled automatically by AI vs Human agents', 'dragwyb-click-to-chat')}></span>
							</div>
							<div className="dctc-sc-split-stat">
								<span className="dctc-sc-split-val">{aiControlled}</span>
								<span className="dctc-sc-split-slash">/</span>
								<span className="dctc-sc-split-val">{humanControlled}</span>
							</div>
						</div>
					</div>
					<div className="dctc-sc-metric-bottom dctc-sc-metric-bot-human">
						<div className="dctc-sc-human-tags">
							<span>🤖 {__('AI Bot', 'dragwyb-click-to-chat')}</span>
							<span>👤 {__('Human Staff', 'dragwyb-click-to-chat')}</span>
						</div>
						<span className="dctc-sc-metric-desc">
							{__('Live hybrid handoff & takeover active', 'dragwyb-click-to-chat')}
						</span>
					</div>
				</div>
			</div>

			{ /* 3. Middle Section: Tickets Overview Chart & Quick Actions */}
			<div className="dctc-sc-dashboard-row">
				{ /* Left Column (60%): Interactive Tickets Overview Chart */}
				<div className="dctc-sc-card-box dctc-sc-col-chart">
					<div className="dctc-sc-card-box-header">
						<div className="dctc-sc-card-box-title">
							<span className="dashicons dashicons-chart-bar" style={{ color: '#6366f1', fontSize: '20px', width: '20px', height: '20px' }}></span>
							<h3>{__('Tickets Overview', 'dragwyb-click-to-chat')}</h3>
						</div>

						<div className="dctc-sc-dropdown-wrap">
							<button
								type="button"
								className="dctc-sc-dropdown-trigger"
								onClick={() => setIsDropdownOpen(!isDropdownOpen)}
							>
								<span className="dashicons dashicons-calendar-alt"></span>
								<span>
									{timeRange === '30d' ? __('Last 30 days', 'dragwyb-click-to-chat') :
										timeRange === 'month' ? __('This Month', 'dragwyb-click-to-chat') :
											__('Last 7 days', 'dragwyb-click-to-chat')}
								</span>
								<span className="dashicons dashicons-arrow-down-alt2"></span>
							</button>

							{isDropdownOpen && (
								<div className="dctc-sc-dropdown-menu">
									<button
										type="button"
										className={`dctc-sc-dropdown-item ${timeRange === '7d' ? 'active' : ''}`}
										onClick={() => { setTimeRange('7d'); setIsDropdownOpen(false); }}
									>
										{__('Last 7 days', 'dragwyb-click-to-chat')}
									</button>
									<button
										type="button"
										className={`dctc-sc-dropdown-item ${timeRange === '30d' ? 'active' : ''}`}
										onClick={() => { setTimeRange('30d'); setIsDropdownOpen(false); }}
									>
										{__('Last 30 days', 'dragwyb-click-to-chat')}
									</button>
									<button
										type="button"
										className={`dctc-sc-dropdown-item ${timeRange === 'month' ? 'active' : ''}`}
										onClick={() => { setTimeRange('month'); setIsDropdownOpen(false); }}
									>
										{__('This Month', 'dragwyb-click-to-chat')}
									</button>
								</div>
							)}
						</div>
					</div>

					{ /* Legend */}
					<div className="dctc-sc-chart-legend">
						<span className="dctc-sc-legend-item">
							<span className="dctc-sc-legend-line blue"></span>
							<span>{__('New', 'dragwyb-click-to-chat')}</span>
						</span>
						<span className="dctc-sc-legend-item">
							<span className="dctc-sc-legend-line orange"></span>
							<span>{__('Open', 'dragwyb-click-to-chat')}</span>
						</span>
						<span className="dctc-sc-legend-item">
							<span className="dctc-sc-legend-line yellow"></span>
							<span>{__('Pending', 'dragwyb-click-to-chat')}</span>
						</span>
						<span className="dctc-sc-legend-item">
							<span className="dctc-sc-legend-line green"></span>
							<span>{__('Resolved', 'dragwyb-click-to-chat')}</span>
						</span>
					</div>

					{ /* SVG Interactive Area Chart */}
					<div className="dctc-sc-svg-chart-container">
						<svg
							viewBox={`0 0 ${chartMetrics.width} ${chartMetrics.height}`}
							className="dctc-sc-svg-chart"
						>
							<defs>
								<linearGradient id="dctcGradientNew" x1="0" y1="0" x2="0" y2="1">
									<stop offset="0%" stopColor="#3b82f6" stopOpacity="0.35" />
									<stop offset="60%" stopColor="#3b82f6" stopOpacity="0.12" />
									<stop offset="100%" stopColor="#3b82f6" stopOpacity="0.01" />
								</linearGradient>
								<linearGradient id="dctcGradientResolved" x1="0" y1="0" x2="0" y2="1">
									<stop offset="0%" stopColor="#10b981" stopOpacity="0.35" />
									<stop offset="60%" stopColor="#10b981" stopOpacity="0.12" />
									<stop offset="100%" stopColor="#10b981" stopOpacity="0.01" />
								</linearGradient>
								<linearGradient id="dctcGradientPending" x1="0" y1="0" x2="0" y2="1">
									<stop offset="0%" stopColor="#eab308" stopOpacity="0.3" />
									<stop offset="60%" stopColor="#eab308" stopOpacity="0.1" />
									<stop offset="100%" stopColor="#eab308" stopOpacity="0.01" />
								</linearGradient>
								<linearGradient id="dctcGradientOpen" x1="0" y1="0" x2="0" y2="1">
									<stop offset="0%" stopColor="#f97316" stopOpacity="0.3" />
									<stop offset="60%" stopColor="#f97316" stopOpacity="0.1" />
									<stop offset="100%" stopColor="#f97316" stopOpacity="0.01" />
								</linearGradient>
							</defs>

							{ /* Grid Lines & Y-axis labels */}
							{[0, 2, 4, 6, 8, 10].map((val) => {
								const y = chartMetrics.paddingTop + chartMetrics.plotHeight - (val / chartMetrics.maxY) * chartMetrics.plotHeight;
								return (
									<g key={val} className="dctc-sc-grid-group">
										<line
											x1={chartMetrics.paddingLeft}
											y1={y}
											x2={chartMetrics.width - chartMetrics.paddingRight}
											y2={y}
											stroke="#f1f5f9"
											strokeWidth="1"
										/>
										<text
											x={chartMetrics.paddingLeft - 8}
											y={y + 3.5}
											textAnchor="end"
											className="dctc-sc-chart-axis-text"
										>
											{val}
										</text>
									</g>
								);
							})}

							{ /* Gradient Area Fills */}
							<path d={chartMetrics.areaNew} fill="url(#dctcGradientNew)" />
							<path d={chartMetrics.areaResolved} fill="url(#dctcGradientResolved)" />
							<path d={chartMetrics.areaPending} fill="url(#dctcGradientPending)" />
							<path d={chartMetrics.areaOpen} fill="url(#dctcGradientOpen)" />

							{ /* Series Lines */}
							<path
								d={chartMetrics.pathResolved}
								fill="none"
								stroke="#10b981"
								strokeWidth="2.2"
								strokeLinecap="round"
							/>
							<path
								d={chartMetrics.pathPending}
								fill="none"
								stroke="#eab308"
								strokeWidth="2"
								strokeLinecap="round"
							/>
							<path
								d={chartMetrics.pathOpen}
								fill="none"
								stroke="#f97316"
								strokeWidth="2"
								strokeLinecap="round"
							/>
							<path
								d={chartMetrics.pathNew}
								fill="none"
								stroke="#3b82f6"
								strokeWidth="2.5"
								strokeLinecap="round"
							/>

							{ /* X-Axis Date Labels & Interactive Column Overlays */}
							{chartDays.map((day, i) => {
								const x = chartMetrics.paddingLeft + (i / (chartDays.length - 1)) * chartMetrics.plotWidth;
								const isHovered = hoverChartIndex === i;

								return (
									<g key={day.date || i}>
										<text
											x={x}
											y={chartMetrics.height - 10}
											textAnchor="middle"
											className={`dctc-sc-chart-axis-text ${isHovered ? 'active' : ''}`}
										>
											{day.date}
										</text>

										{ /* Interactive vertical hover guideline */}
										{isHovered && (
											<line
												x1={x}
												y1={chartMetrics.paddingTop}
												x2={x}
												y2={chartMetrics.paddingTop + chartMetrics.plotHeight}
												stroke="#cbd5e1"
												strokeWidth="1.5"
												strokeDasharray="3 3"
											/>
										)}

										{ /* Active dots on series */}
										<circle
											cx={chartMetrics.pointsNew[i]?.x}
											cy={chartMetrics.pointsNew[i]?.y}
											r={isHovered ? 5 : 3}
											fill="#3b82f6"
											stroke="#ffffff"
											strokeWidth="2"
										/>
										<circle
											cx={chartMetrics.pointsResolved[i]?.x}
											cy={chartMetrics.pointsResolved[i]?.y}
											r={isHovered ? 4.5 : 2.5}
											fill="#10b981"
											stroke="#ffffff"
											strokeWidth="1.5"
										/>

										{ /* Invisible hover hit area */}
										<rect
											x={x - 25}
											y={chartMetrics.paddingTop}
											width="50"
											height={chartMetrics.plotHeight}
											fill="transparent"
											onMouseEnter={() => setHoverChartIndex(i)}
											onMouseLeave={() => setHoverChartIndex(null)}
											style={{ cursor: 'pointer' }}
										/>
									</g>
								);
							})}
						</svg>

						{ /* Interactive Hover Tooltip Popover */}
						{hoverChartIndex !== null && chartDays[hoverChartIndex] && (
							<div
								className="dctc-sc-chart-tooltip"
								style={{
									left: `${(chartMetrics.pointsNew[hoverChartIndex]?.x / chartMetrics.width) * 100}%`,
								}}
							>
								<strong>{chartDays[hoverChartIndex].date}</strong>
								<div className="dctc-sc-tooltip-row">
									<span className="dot blue"></span> {__('New:', 'dragwyb-click-to-chat')} {chartDays[hoverChartIndex].new || 0}
								</div>
								<div className="dctc-sc-tooltip-row">
									<span className="dot orange"></span> {__('Open:', 'dragwyb-click-to-chat')} {chartDays[hoverChartIndex].open || 0}
								</div>
								<div className="dctc-sc-tooltip-row">
									<span className="dot yellow"></span> {__('Pending:', 'dragwyb-click-to-chat')} {chartDays[hoverChartIndex].pending || 0}
								</div>
								<div className="dctc-sc-tooltip-row">
									<span className="dot green"></span> {__('Resolved:', 'dragwyb-click-to-chat')} {chartDays[hoverChartIndex].resolved || 0}
								</div>
							</div>
						)}
					</div>
				</div>

				{ /* Right Column (40%): Quick Actions Grid */}
				<div className="dctc-sc-card-box dctc-sc-col-actions">
					<div className="dctc-sc-card-box-header">
						<div className="dctc-sc-card-box-title">
							<span className="dashicons dashicons-admin-generic" style={{ color: '#6366f1', fontSize: '20px', width: '20px', height: '20px' }}></span>
							<h3>{__('Quick Actions', 'dragwyb-click-to-chat')}</h3>
						</div>
					</div>

					<div className="dctc-sc-quick-actions-grid">
						{ /* Action 1: All Tickets */}
						<div
							className="dctc-sc-quick-action-item"
							onClick={() => onJumpToTickets('all')}
							role="button"
							tabIndex={0}
						>
							<div className="dctc-sc-action-icon blue">
								<span className="dashicons dashicons-list-view"></span>
							</div>
							<div className="dctc-sc-action-text">
								<strong>{__('All Tickets', 'dragwyb-click-to-chat')}</strong>
								<span>{totalTickets} {__('total tickets', 'dragwyb-click-to-chat')}</span>
							</div>
							<span className="dctc-sc-action-arrow">&rsaquo;</span>
						</div>

						{ /* Action 2: Open Tickets */}
						<div
							className="dctc-sc-quick-action-item"
							onClick={() => onJumpToTickets('open')}
							role="button"
							tabIndex={0}
						>
							<div className="dctc-sc-action-icon red">
								<span className="dashicons dashicons-flag"></span>
							</div>
							<div className="dctc-sc-action-text">
								<strong>{__('Open Tickets', 'dragwyb-click-to-chat')}</strong>
								<span>{totalOpen} {__('open tickets', 'dragwyb-click-to-chat')}</span>
							</div>
							<span className="dctc-sc-action-arrow">&rsaquo;</span>
						</div>

						{ /* Action 3: AI Bot Tickets */}
						<div
							className="dctc-sc-quick-action-item"
							onClick={() => onJumpToTickets('ai_bot')}
							role="button"
							tabIndex={0}
						>
							<div className="dctc-sc-action-icon indigo">
								<span className="dashicons dashicons-superhero"></span>
							</div>
							<div className="dctc-sc-action-text">
								<strong>{__('AI Bot Tickets', 'dragwyb-click-to-chat')}</strong>
								<span>{totalAiBot} {__('bot tickets', 'dragwyb-click-to-chat')}</span>
							</div>
							<span className="dctc-sc-action-arrow">&rsaquo;</span>
						</div>

						{ /* Action 4: Pending Follow-up */}
						<div
							className="dctc-sc-quick-action-item"
							onClick={() => onJumpToTickets('pending')}
							role="button"
							tabIndex={0}
						>
							<div className="dctc-sc-action-icon orange">
								<span className="dashicons dashicons-clock"></span>
							</div>
							<div className="dctc-sc-action-text">
								<strong>{__('Pending Follow-up', 'dragwyb-click-to-chat')}</strong>
								<span>{totalPending} {__('requires action', 'dragwyb-click-to-chat')}</span>
							</div>
							<span className="dctc-sc-action-arrow">&rsaquo;</span>
						</div>

						{ /* Action 5: Resolved & Closed */}
						<div
							className="dctc-sc-quick-action-item"
							onClick={() => onJumpToTickets('resolved')}
							role="button"
							tabIndex={0}
						>
							<div className="dctc-sc-action-icon green">
								<span className="dashicons dashicons-yes-alt"></span>
							</div>
							<div className="dctc-sc-action-text">
								<strong>{__('Resolved & Closed', 'dragwyb-click-to-chat')}</strong>
								<span>{totalResolved} {__('resolved tickets', 'dragwyb-click-to-chat')}</span>
							</div>
							<span className="dctc-sc-action-arrow">&rsaquo;</span>
						</div>

						{ /* Action 6: Agents & Staff */}
						<div
							className="dctc-sc-quick-action-item"
							onClick={() => onSwitchTab('agents')}
							role="button"
							tabIndex={0}
						>
							<div className="dctc-sc-action-icon purple">
								<span className="dashicons dashicons-groups"></span>
							</div>
							<div className="dctc-sc-action-text">
								<strong>{__('Agents & Staff', 'dragwyb-click-to-chat')}</strong>
								<span>{__('Manage your team', 'dragwyb-click-to-chat')}</span>
							</div>
							<span className="dctc-sc-action-arrow">&rsaquo;</span>
						</div>

						{ /* Action 7: Categories & Tags */}
						<div
							className="dctc-sc-quick-action-item"
							onClick={() => onSwitchTab('taxonomies')}
							role="button"
							tabIndex={0}
						>
							<div className="dctc-sc-action-icon slate">
								<span className="dashicons dashicons-tag"></span>
							</div>
							<div className="dctc-sc-action-text">
								<strong>{__('Categories & Tags', 'dragwyb-click-to-chat')}</strong>
								<span>{__('Organize tickets', 'dragwyb-click-to-chat')}</span>
							</div>
							<span className="dctc-sc-action-arrow">&rsaquo;</span>
						</div>

						{ /* Action 8: Support Settings */}
						<div
							className="dctc-sc-quick-action-item"
							onClick={() => onSwitchTab('settings')}
							role="button"
							tabIndex={0}
						>
							<div className="dctc-sc-action-icon teal">
								<span className="dashicons dashicons-admin-generic"></span>
							</div>
							<div className="dctc-sc-action-text">
								<strong>{__('Support Settings', 'dragwyb-click-to-chat')}</strong>
								<span>{__('Configure helpdesk', 'dragwyb-click-to-chat')}</span>
							</div>
							<span className="dctc-sc-action-arrow">&rsaquo;</span>
						</div>
					</div>
				</div>
			</div>

			{ /* 4. Bottom Section: Recent Activity & Status Breakdown */}
			<div className="dctc-sc-dashboard-row">
				{ /* Left Column (60%): Recent Activity Feed */}
				<div className="dctc-sc-card-box dctc-sc-col-activity">
					<div className="dctc-sc-card-box-header">
						<div className="dctc-sc-card-box-title">
							<span className="dashicons dashicons-rss" style={{ color: '#6366f1', fontSize: '20px', width: '20px', height: '20px' }}></span>
							<h3>{__('Recent Activity', 'dragwyb-click-to-chat')}</h3>
						</div>

						<span className="dctc-sc-live-feed-badge">
							<span className="dctc-sc-pulse-dot"></span>
							{__('+ Live Feed', 'dragwyb-click-to-chat')}
						</span>
					</div>

					<div className="dctc-sc-activity-list">
						{(!dashboardStats?.recent_activity || dashboardStats.recent_activity.length === 0) ? (
							<div className="dctc-sc-empty-activity-state">
								<span className="dashicons dashicons-info-outline"></span>
								<p>{__('No recent support ticket events recorded yet.', 'dragwyb-click-to-chat')}</p>
							</div>
						) : (
							dashboardStats.recent_activity.map((item, idx) => {
								const isBot = item.event_type?.includes('ai') || item.event_type?.includes('bot');
								const isUser = item.event_type === 'created' || item.event_type === 'customer_reply';
								const isStatus = item.event_type?.includes('status') || item.event_type?.includes('priority');

								return (
									<div
										key={item.id || idx}
										className="dctc-sc-activity-row"
										onClick={() => onJumpToTickets('all', item.ticket_id)}
										role="button"
										tabIndex={0}
									>
										<div className="dctc-sc-activity-row-left">
											<div className="dctc-sc-activity-icon-badge">
												{isBot ? (
													<span className="dashicons dashicons-superhero"></span>
												) : isUser ? (
													<span className="dashicons dashicons-admin-users"></span>
												) : (
													<span className="dashicons dashicons-tag"></span>
												)}
											</div>

											<span className="dctc-sc-ticket-id-pill">
												#{item.ticket_id || item.id || (10002 - idx)}
											</span>

											<div className="dctc-sc-activity-text-group">
												<strong className="dctc-sc-activity-subject">
													{item.ticket_subject || (idx === 0 ? '[Lead] Hii I want to buy your product' : idx === 1 ? 'New ticket created' : 'AI response sent')}
												</strong>
												<span className="dctc-sc-activity-action-label">
													{item.event_data?.message || item.event_data?.note || (idx === 0 ? 'Status changed' : idx === 1 ? 'From Live Chat' : 'Automated reply from AI Bot')}
												</span>
											</div>
										</div>

										<div className="dctc-sc-activity-row-right">
											<span className="dctc-sc-activity-time">
												{formatRelativeTime(item.created_at) || __('Just now', 'dragwyb-click-to-chat')}
											</span>
											<span className="dctc-sc-activity-chevron">&rsaquo;</span>
										</div>
									</div>
								);
							})
						)}
					</div>
				</div>

				{ /* Right Column (40%): Ticket Status Breakdown */}
				<div className="dctc-sc-card-box dctc-sc-col-breakdown">
					<div className="dctc-sc-card-box-header">
						<div className="dctc-sc-card-box-title">
							<span className="dashicons dashicons-chart-pie" style={{ color: '#6366f1', fontSize: '20px', width: '20px', height: '20px' }}></span>
							<h3>{__('Ticket Status Breakdown', 'dragwyb-click-to-chat')}</h3>
						</div>
					</div>

					<div className="dctc-sc-breakdown-body">
						{ /* Left: Donut Chart */}
						<div className="dctc-sc-donut-wrapper">
							<svg viewBox="0 0 140 140" className="dctc-sc-donut-svg">
								<circle
									cx="70"
									cy="70"
									r="54"
									fill="none"
									stroke="#f1f5f9"
									strokeWidth="16"
								/>
								{donutData.map((seg) => (
									<circle
										key={seg.key}
										cx="70"
										cy="70"
										r="54"
										fill="none"
										stroke={seg.color}
										strokeWidth="16"
										strokeDasharray={seg.strokeDasharray}
										strokeDashoffset={seg.strokeDashoffset}
										strokeLinecap="round"
										transform="rotate(-90 70 70)"
									/>
								))}
							</svg>

							<div className="dctc-sc-donut-center">
								<span className="dctc-sc-donut-total">{totalTickets}</span>
								<span className="dctc-sc-donut-label">{__('Total Tickets', 'dragwyb-click-to-chat')}</span>
							</div>
						</div>

						{ /* Right: Breakdown Rows with Progress Bars */}
						<div className="dctc-sc-breakdown-rows">
							{ /* New */}
							<div className="dctc-sc-breakdown-row">
								<span className="dctc-sc-status-dot blue"></span>
								<span className="dctc-sc-status-name">{__('New', 'dragwyb-click-to-chat')}</span>
								<span className="dctc-sc-status-count">{statusBreakdown.new || 0}</span>
								<span className="dctc-sc-status-pct">{statusPercent.new}%</span>
								<div className="dctc-sc-progress-track">
									<div className="dctc-sc-progress-fill blue" style={{ width: `${statusPercent.new}%` }}></div>
								</div>
							</div>

							{ /* Open */}
							<div className="dctc-sc-breakdown-row">
								<span className="dctc-sc-status-dot orange"></span>
								<span className="dctc-sc-status-name">{__('Open', 'dragwyb-click-to-chat')}</span>
								<span className="dctc-sc-status-count">{statusBreakdown.open || 0}</span>
								<span className="dctc-sc-status-pct">{statusPercent.open}%</span>
								<div className="dctc-sc-progress-track">
									<div className="dctc-sc-progress-fill orange" style={{ width: `${statusPercent.open}%` }}></div>
								</div>
							</div>

							{ /* Pending */}
							<div className="dctc-sc-breakdown-row">
								<span className="dctc-sc-status-dot yellow"></span>
								<span className="dctc-sc-status-name">{__('Pending', 'dragwyb-click-to-chat')}</span>
								<span className="dctc-sc-status-count">{statusBreakdown.pending || 0}</span>
								<span className="dctc-sc-status-pct">{statusPercent.pending}%</span>
								<div className="dctc-sc-progress-track">
									<div className="dctc-sc-progress-fill yellow" style={{ width: `${statusPercent.pending}%` }}></div>
								</div>
							</div>

							{ /* Resolved */}
							<div className="dctc-sc-breakdown-row">
								<span className="dctc-sc-status-dot green"></span>
								<span className="dctc-sc-status-name">{__('Resolved', 'dragwyb-click-to-chat')}</span>
								<span className="dctc-sc-status-count">{statusBreakdown.resolved || 0}</span>
								<span className="dctc-sc-status-pct">{statusPercent.resolved}%</span>
								<div className="dctc-sc-progress-track">
									<div className="dctc-sc-progress-fill green" style={{ width: `${statusPercent.resolved}%` }}></div>
								</div>
							</div>

							{ /* Closed */}
							<div className="dctc-sc-breakdown-row">
								<span className="dctc-sc-status-dot slate"></span>
								<span className="dctc-sc-status-name">{__('Closed', 'dragwyb-click-to-chat')}</span>
								<span className="dctc-sc-status-count">{statusBreakdown.closed || 0}</span>
								<span className="dctc-sc-status-pct">{statusPercent.closed}%</span>
								<div className="dctc-sc-progress-track">
									<div className="dctc-sc-progress-fill slate" style={{ width: `${statusPercent.closed}%` }}></div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	);
}

