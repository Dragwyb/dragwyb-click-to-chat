/**
 * Confirm / danger modal shared by API key reset, Pinecone reset, and session delete.
 *
 * @param {Object}   props
 * @param {boolean}  props.open
 * @param {string}   props.title
 * @param {string}   props.message
 * @param {string}   [props.confirmLabel]
 * @param {boolean}  [props.danger=true]
 * @param {boolean}  [props.busy=false]
 * @param {Function} props.onConfirm
 * @param {Function} props.onCancel
 */
import { useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

export default function ConfirmModal( {
	open,
	title,
	message,
	confirmLabel,
	danger = true,
	busy = false,
	onConfirm,
	onCancel,
} ) {
	useEffect( () => {
		if ( ! open ) {
			return;
		}
		document.body.classList.add( 'dctc-ai-modal-open' );
		const onKey = ( e ) => {
			if ( e.key === 'Escape' ) {
				onCancel();
			}
		};
		document.addEventListener( 'keydown', onKey );
		return () => {
			document.body.classList.remove( 'dctc-ai-modal-open' );
			document.removeEventListener( 'keydown', onKey );
		};
	}, [ open, onCancel ] );

	if ( ! open ) {
		return null;
	}

	return (
		<div
			className="dctc-ai-modal dctc-ai-confirm-modal is-visible"
			role="dialog"
			aria-modal="true"
			aria-labelledby="dctc-ai-confirm-title"
		>
			<div className="dctc-ai-modal-overlay" onClick={ onCancel } />
			<div className="dctc-ai-modal-content dctc-ai-confirm-modal__content">
				<div className="dctc-ai-modal-header">
					<div className="dctc-ai-modal-title">
						<span className="dctc-ai-modal-title-icon" aria-hidden="true">
							<span className="dashicons dashicons-warning" />
						</span>
						<h3 id="dctc-ai-confirm-title">{ title }</h3>
					</div>
					<button
						type="button"
						className="dctc-ai-modal-close"
						onClick={ onCancel }
						aria-label={ __( 'Close', 'dragwyb-click-to-chat' ) }
					>
						<span className="dashicons dashicons-no-alt" aria-hidden="true" />
					</button>
				</div>
				<div className="dctc-ai-modal-body dctc-ai-confirm-modal__body">
					<p>{ message }</p>
				</div>
				<div className="dctc-ai-modal-footer dctc-ai-confirm-modal__footer">
					<button
						type="button"
						className="dctc-ai-btn dctc-ai-btn-secondary"
						onClick={ onCancel }
						disabled={ busy }
					>
						{ __( 'Cancel', 'dragwyb-click-to-chat' ) }
					</button>
					<button
						type="button"
						className={
							'dctc-ai-btn ' +
							( danger ? 'dctc-ai-btn-danger' : 'dctc-ai-btn-primary' )
						}
						onClick={ onConfirm }
						disabled={ busy }
					>
						{ busy ? (
							<span className="dctc-ai-spinner" aria-hidden="true" />
						) : (
							confirmLabel || __( 'Confirm', 'dragwyb-click-to-chat' )
						) }
					</button>
				</div>
			</div>
		</div>
	);
}
