/**
 * SettingCard: Compact setting row/card with icon, title, description, badge, toggle.
 */
import Toggle from './Toggle';

export default function SettingCard({
	id,
	title,
	desc = null,
	checked,
	onChange,
	icon = null,
	badge = null,
	disabled = false,
	className = '',
}) {
	return (
		<div className={`dctc-ai-bot-setting-card ${checked ? 'is-active' : ''} ${className}`}>
			<div className="dctc-ai-bot-setting-card__main">
				{icon && (
					<span className={`dashicons ${icon} dctc-ai-setting-row-icon`} aria-hidden="true" />
				)}
				<div className="dctc-ai-bot-setting-card__text">
					<div className="dctc-ai-bot-setting-card__title-row">
						<strong>{title}</strong>
						{badge && <span className="dctc-ai-mini-badge">{badge}</span>}
					</div>
					{desc && <span className="dctc-ai-bot-setting-card__desc">{desc}</span>}
				</div>
			</div>
			<Toggle id={id} checked={checked} onChange={onChange} disabled={disabled} />
		</div>
	);
}
