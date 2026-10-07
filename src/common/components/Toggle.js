/**
 * Global Toggle switch component shared across the entire plugin.
 *
 * @param {Object}   props
 * @param {string}   props.id
 * @param {boolean}  props.checked
 * @param {Function} props.onChange
 * @param {boolean}  [props.disabled]
 * @param {string}   [props.className]
 */
export default function Toggle({
	id,
	checked,
	onChange,
	disabled = false,
	className = '',
}) {
	return (
		<label className={`dctc-ai-toggle ${className}`} htmlFor={id}>
			<input
				id={id}
				type="checkbox"
				checked={!!checked}
				disabled={disabled}
				onChange={(e) => onChange(e.target.checked)}
			/>
			<span className="dctc-ai-toggle-slider" aria-hidden="true" />
		</label>
	);
}
