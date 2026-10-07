/**
 * Synced Range Slider + Numeric input field.
 */
export default function RangeField({
	id,
	label,
	value,
	onChange,
	min = 0,
	max = 100,
	step = 1,
	unit = '',
	desc = null,
	className = '',
}) {
	const numericValue = typeof value === 'number' ? value : Number(value) || min;

	return (
		<div className={`dctc-ai-bot-field dctc-ai-range-field-wrap ${className}`}>
			<div className="dctc-ai-range-field-header">
				<label htmlFor={id}>{label}</label>
				<span className="dctc-ai-range-value">
					{numericValue}
					{unit && <span className="dctc-ai-range-unit">{unit}</span>}
				</span>
			</div>
			{desc && <p className="dctc-ai-bot-hint">{desc}</p>}
			<div className="dctc-ai-range-input-row">
				<input
					type="range"
					id={id}
					min={min}
					max={max}
					step={step}
					value={numericValue}
					onChange={(e) => onChange(Number(e.target.value))}
					className="dctc-ai-range-slider"
				/>
				<input
					type="number"
					min={min}
					max={max}
					step={step}
					value={numericValue}
					onChange={(e) => onChange(Number(e.target.value))}
					className="dctc-ai-range-number-input"
				/>
			</div>
		</div>
	);
}
