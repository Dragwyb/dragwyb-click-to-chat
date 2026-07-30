/**
 * Toggle switch used across AI admin settings cards.
 *
 * @param {Object}   props
 * @param {string}   props.id
 * @param {boolean}  props.checked
 * @param {Function} props.onChange
 * @param {boolean}  [props.disabled]
 */
export default function Toggle( { id, checked, onChange, disabled = false } ) {
	return (
		<label className="dctc-ai-toggle" htmlFor={ id }>
			<input
				id={ id }
				type="checkbox"
				checked={ !! checked }
				disabled={ disabled }
				onChange={ ( e ) => onChange( e.target.checked ) }
			/>
			<span className="dctc-ai-toggle-slider" aria-hidden="true" />
		</label>
	);
}
