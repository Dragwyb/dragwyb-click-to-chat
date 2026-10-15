import { __ } from '@wordpress/i18n';

export default function Toast( { message, type = 'success', onClose } ) {
	if ( ! message ) {
		return null;
	}

	return (
		<div className={ `dctc-ai-toast dctc-ai-toast-${ type }` } role="alert">
			<span
				className={
					'dashicons ' +
					( type === 'error'
						? 'dashicons-warning'
						: type === 'info'
						? 'dashicons-info'
						: 'dashicons-yes-alt' )
				}
			/>
			<span className="dctc-ai-toast-message">{ message }</span>
			{ onClose && (
				<button
					type="button"
					className="dctc-ai-toast-close"
					onClick={ onClose }
					aria-label={ __( 'Close notice', 'dragwyb-click-to-chat' ) }
				>
					<span className="dashicons dashicons-no-alt" />
				</button>
			) }
		</div>
	);
}
