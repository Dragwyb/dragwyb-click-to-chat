/**
 * Global Top Header Component
 *
 * Unified sticky top navigation bar used across:
 * - Support Center
 * - AI Assistant
 * - Channels & Settings
 */
import { __ } from '@wordpress/i18n';

export default function GlobalHeader({
	icon = 'dashicons-format-chat',
	title,
	subheading,
	tagline,
	menuItems = [],
	activeTab,
	onTabChange,
	rightActions,
	rightSide,
	className = '',
}) {
	const sub = subheading || tagline;

	return (
		<header className={`dctc-sc-header-bar ${className}`.trim()}>
			<div className="dctc-sc-brand">
				<div className="dctc-sc-brand-icon">
					{typeof icon === 'string' ? (
						<span className={`dashicons ${icon}`} aria-hidden="true" />
					) : (
						icon
					)}
				</div>
				<div>
					{title && <h1 className="dctc-sc-app-title">{title}</h1>}
					{sub && <span className="dctc-sc-app-tagline">{sub}</span>}
				</div>
			</div>

			{Array.isArray(menuItems) && menuItems.length > 0 && (
				<nav className="dctc-sc-top-nav" role="tablist">
					{menuItems.map((item) => {
						const isActive =
							item.active !== undefined
								? item.active
								: activeTab !== undefined
								? activeTab === item.id
								: false;

						const handleClick = (e) => {
							if (item.onClick) {
								item.onClick(e, item);
							} else if (onTabChange) {
								e.preventDefault();
								onTabChange(item.id);
							}
						};

						if (item.href) {
							return (
								<a
									key={item.id || item.label}
									href={item.href}
									className={`dctc-sc-nav-link ${isActive ? 'active' : ''} ${item.className || ''}`.trim()}
									onClick={item.onClick}
								>
									{item.icon && (
										typeof item.icon === 'string' ? (
											<span className={`dashicons ${item.icon}`} aria-hidden="true" />
										) : (
											item.icon
										)
									)}
									{item.label}
									{item.badge !== undefined && (
										<span className="dctc-sc-nav-badge">{item.badge}</span>
									)}
								</a>
							);
						}

						return (
							<button
								key={item.id || item.label}
								type="button"
								role="tab"
								aria-selected={isActive}
								className={`dctc-sc-nav-link ${isActive ? 'active' : ''} ${item.className || ''}`.trim()}
								onClick={handleClick}
							>
								{item.icon && (
									typeof item.icon === 'string' ? (
										<span className={`dashicons ${item.icon}`} aria-hidden="true" />
									) : (
										item.icon
									)
								)}
								{item.label}
								{item.badge !== undefined && (
									<span className="dctc-sc-nav-badge">{item.badge}</span>
								)}
							</button>
						);
					})}
				</nav>
			)}

			<div className="dctc-sc-header-right">
				{rightActions || rightSide}
			</div>
		</header>
	);
}
