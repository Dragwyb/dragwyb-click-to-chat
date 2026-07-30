/**
 * System prompt + model parameters (temperature / max tokens).
 */
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

const PROMPT_HINT = __(
	'Explain how your chatbot should sound and behave—its tone, what it helps with, and any rules it should follow.',
	'dragwyb-click-to-chat'
);

export default function Instructions( { settings, onSave, showNotice } ) {
	const [ saving, setSaving ] = useState( false );
	const chatbot = settings?.chatbot || {};

	const buildForm = () => ( {
		system_prompt: chatbot.system_prompt || '',
		temperature: chatbot.temperature ?? 0.7,
		max_tokens: chatbot.max_tokens ?? 500,
	} );

	const [ form, setForm ] = useState( buildForm );
	const [ saved, setSaved ] = useState( buildForm );
	const dirty = JSON.stringify( form ) !== JSON.stringify( saved );

	const setField = ( key, value ) => {
		setForm( ( prev ) => ( { ...prev, [ key ]: value } ) );
	};

	const temp = Number( form.temperature );

	const onSubmit = async ( e ) => {
		e.preventDefault();
		setSaving( true );
		try {
			await apiFetch( {
				path: '/dctc-ai/v1/save-bot-settings',
				method: 'POST',
				data: form,
			} );
			onSave( { chatbot: { ...chatbot, ...form } } );
			setSaved( form );
			showNotice(
				__( 'Instructions saved successfully!', 'dragwyb-click-to-chat' )
			);
		} catch ( err ) {
			showNotice(
				err.message ||
					__( 'Failed to save instructions', 'dragwyb-click-to-chat' ),
				'error'
			);
		} finally {
			setSaving( false );
		}
	};

	return (
		<div className="dctc-ai-instructions-settings">
			<form onSubmit={ onSubmit }>
				<article className="dctc-ai-instructions-card">
					<header className="dctc-ai-instructions-card__header">
						<span className="dctc-ai-instructions-card__icon" aria-hidden="true">
							<span className="dashicons dashicons-edit" />
						</span>
						<h3 className="dctc-ai-instructions-card__title">
							{ __( 'System Instructions', 'dragwyb-click-to-chat' ) }
						</h3>
					</header>
					<div className="dctc-ai-instructions-card__body">
						<label htmlFor="system_prompt" className="dctc-ai-instructions-label">
							{ __( 'Custom ChatBot Prompt', 'dragwyb-click-to-chat' ) }
						</label>
						<textarea
							id="system_prompt"
							className="dctc-ai-instructions-textarea"
							rows="6"
							value={ form.system_prompt }
							onChange={ ( e ) => setField( 'system_prompt', e.target.value ) }
							placeholder={ __(
								"For example: You're a friendly assistant on our site. Answer clearly, stay helpful, and keep replies short.",
								'dragwyb-click-to-chat'
							) }
						/>
						<p className="dctc-ai-bot-hint">{ PROMPT_HINT }</p>
					</div>
				</article>

				<article className="dctc-ai-instructions-card">
					<header className="dctc-ai-instructions-card__header">
						<span className="dctc-ai-instructions-card__icon" aria-hidden="true">
							<span className="dashicons dashicons-admin-settings" />
						</span>
						<h3 className="dctc-ai-instructions-card__title">
							{ __( 'Model Parameters', 'dragwyb-click-to-chat' ) }
						</h3>
					</header>
					<div className="dctc-ai-instructions-card__body">
						<div className="dctc-ai-instructions-params-grid">
							<div className="dctc-ai-instructions-param">
								<div className="dctc-ai-instructions-param__head">
									<label htmlFor="temperature">
										{ __( 'Chat Accuracy', 'dragwyb-click-to-chat' ) }
									</label>
									<span className="dctc-ai-instructions-value-badge">
										{ temp.toFixed( 1 ) }
									</span>
								</div>
								<input
									type="range"
									id="temperature"
									className="dctc-ai-instructions-range"
									min="0"
									max="2"
									step="0.1"
									value={ form.temperature }
									onChange={ ( e ) =>
										setField( 'temperature', e.target.value )
									}
								/>
								<div className="dctc-ai-instructions-range-labels">
									<span>
										{ __( 'Focused/Deterministic', 'dragwyb-click-to-chat' ) }
									</span>
									<span>{ __( 'Creative', 'dragwyb-click-to-chat' ) }</span>
								</div>
								<p className="dctc-ai-bot-hint">
									{ __(
										'Turn it up for more creative replies; turn it down for shorter, more predictable answers. Values above 1.0 make replies noticeably more random and are rarely needed.',
										'dragwyb-click-to-chat'
									) }
								</p>
							</div>

							<div className="dctc-ai-instructions-param">
								<label
									htmlFor="max_tokens"
									className="dctc-ai-instructions-label"
								>
									{ __( 'Max Tokens', 'dragwyb-click-to-chat' ) }
								</label>
								<input
									type="number"
									id="max_tokens"
									className="dctc-ai-bot-input"
									value={ form.max_tokens }
									onChange={ ( e ) => setField( 'max_tokens', e.target.value ) }
									min="100"
									max="8192"
								/>
								<p className="dctc-ai-bot-hint">
									{ __(
										'Sets the longest reply the chatbot can send in one message.',
										'dragwyb-click-to-chat'
									) }
								</p>
							</div>
						</div>
					</div>
				</article>

				<footer className="dctc-ai-instructions-footer">
					<button
						type="submit"
						className="dctc-ai-btn dctc-ai-btn-primary"
						disabled={ saving || ! dirty }
					>
						{ saving ? (
							<>
								<span className="dctc-ai-spinner" aria-hidden="true" />{ ' ' }
								{ __( 'Saving…', 'dragwyb-click-to-chat' ) }
							</>
						) : (
							__( 'Save', 'dragwyb-click-to-chat' )
						) }
					</button>
				</footer>
			</form>
		</div>
	);
}
