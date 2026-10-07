/**
 * Global FormField: Common form group wrapper for inputs, selects, textareas with labels, hints, and error states.
 */
export default function FormField({
	id,
	label,
	desc = null,
	required = false,
	error = null,
	badge = null,
	children,
	className = '',
}) {
	return (
		<div className={`dctc-ai-bot-field ${error ? 'has-error' : ''} ${className}`}>
			{label && (
				<div className="dctc-ai-bot-field__label-row">
					<label htmlFor={id}>
						{label}
						{required && <span className="dctc-ai-required-star" aria-hidden="true">*</span>}
					</label>
					{badge && <span className="dctc-ai-mini-badge">{badge}</span>}
				</div>
			)}
			{desc && <p className="dctc-ai-bot-hint">{desc}</p>}
			{children}
			{error && <p className="dctc-ai-bot-error">{error}</p>}
		</div>
	);
}
