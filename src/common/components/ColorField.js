/**
 * Global ColorField: Reusable Color Picker field with label, description, and hex value display.
 */
export default function ColorField({
	id,
	label,
	value,
	onChange,
	desc = null,
	className = '',
}) {
	return (
		<div className={`dctc-ai-bot-field dctc-ai-color-field-wrap ${className}`}>
			<label htmlFor={id}>{label}</label>
			{desc && <p className="dctc-ai-bot-hint">{desc}</p>}
			<div className="dctc-ai-bot-color-field">
				<input
					type="color"
					id={id}
					className="dctc-ai-bot-color-picker"
					value={value || '#000000'}
					onChange={(e) => onChange(e.target.value)}
				/>
				<span className="dctc-ai-bot-color-value">{value || '#000000'}</span>
			</div>
		</div>
	);
}
