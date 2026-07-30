/**
 * Fixed toast notice for the AI admin dashboard.
 *
 * @param {Object}   props
 * @param {string}   props.message
 * @param {string}   [props.type='success']
 * @param {Function} props.onClose
 */
import { __ } from '@wordpress/i18n';

export default function Toast( { message, type = 'success', onClose } ) {
	if ( ! message ) {
		return null;
	}

	return (
		<div className={ `dctc-ai-toast dctc-ai-toast-${ type }` }>
			<span
				className={
					'dashicons ' +
					( type === 'success' ? 'dashicons-yes-alt' : 'dashicons-warning' )
				}
			/>
			<span className="dctc-ai-toast-message">{ message }</span>
			<button
				type="button"
				className="dctc-ai-toast-close"
				onClick={ onClose }
				aria-label={ __( 'Close notice', 'dragwyb-click-to-chat' ) }
			>
				<span className="dashicons dashicons-no-alt" />
			</button>
		</div>
	);
}
