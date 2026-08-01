/**
 * Chatbot settings — General / Advance / Style subtabs.
 */
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import Toggle from '../components/Toggle';

const SUBTABS = [
	{ id: 'general', label: __( 'General', 'dragwyb-click-to-chat' ) },
	{ id: 'advanced', label: __( 'Advance', 'dragwyb-click-to-chat' ) },
	{ id: 'style', label: __( 'Style', 'dragwyb-click-to-chat' ) },
];

function SettingCard( { id, title, desc, checked, onChange } ) {
	return (
		<div className="dctc-ai-bot-setting-card">
			<div className="dctc-ai-bot-setting-card__text">
				<strong>{ title }</strong>
				<span>{ desc }</span>
			</div>
			<Toggle id={ id } checked={ checked } onChange={ onChange } />
		</div>
	);
}

function ColorField( { id, label, value, onChange } ) {
	return (
		<div className="dctc-ai-bot-field">
			<label htmlFor={ id }>{ label }</label>
			<div className="dctc-ai-bot-color-field">
				<input
					type="color"
					id={ id }
					className="dctc-ai-bot-color-picker"
					value={ value }
					onChange={ ( e ) => onChange( e.target.value ) }
				/>
				<span className="dctc-ai-bot-color-value">{ value }</span>
			</div>
		</div>
	);
}

export default function ChatbotSettings( { settings, onSave, showNotice } ) {
	const chatbot = settings?.chatbot || {};
	const [ subtab, setSubtab ] = useState( 'general' );
	const [ saving, setSaving ] = useState( false );

	const buildForm = () => ( {
		bot_name: chatbot.bot_name || '',
		primary_color: chatbot.primary_color || '#6366f1',
		greeting_msg: chatbot.greeting_msg || '',
		api_error_msg: chatbot.api_error_msg || '',
		support_url: chatbot.support_url || '',
		pre_question_1: chatbot.pre_question_1 || '',
		pre_question_2: chatbot.pre_question_2 || '',
		pre_question_3: chatbot.pre_question_3 || '',
		pre_question_4: chatbot.pre_question_4 || '',
		pre_questions_bg_color: chatbot.pre_questions_bg_color || '#ffffff',
		pre_questions_text_color: chatbot.pre_questions_text_color || '#475569',
		pre_questions_border_color: chatbot.pre_questions_border_color || '#e2e8f0',
		pre_questions_border_radius: chatbot.pre_questions_border_radius || 'rounded',
		bot_avatar: chatbot.bot_avatar || '',
		bubble_style: chatbot.bubble_style || 'rounded',
		save_chat: !! chatbot.save_chat,
		ask_email: !! chatbot.ask_email,
		enable_pre_questions: !! chatbot.enable_pre_questions,
		rate_limit_per_minute: chatbot.rate_limit_per_minute ?? 20,
	} );

	const [ form, setForm ] = useState( buildForm );
	const [ saved, setSaved ] = useState( buildForm );
	const dirty = JSON.stringify( form ) !== JSON.stringify( saved );

	const setField = ( key, value ) => {
		setForm( ( prev ) => ( { ...prev, [ key ]: value } ) );
	};

	const initial = ( form.bot_name || 'B' ).charAt( 0 ).toUpperCase();

	const openMedia = () => {
		if ( ! window.wp?.media ) {
			showNotice(
				__( 'WordPress media modal is not available.', 'dragwyb-click-to-chat' ),
				'error'
			);
			return;
		}
		const frame = window.wp.media( {
			title: __( 'Select Bot Avatar', 'dragwyb-click-to-chat' ),
			button: { text: __( 'Use as Avatar', 'dragwyb-click-to-chat' ) },
			multiple: false,
		} );
		frame.on( 'select', () => {
			const attachment = frame.state().get( 'selection' ).first().toJSON();
			setField( 'bot_avatar', attachment.url );
		} );
		frame.open();
	};

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
				__( 'Bot settings saved successfully!', 'dragwyb-click-to-chat' )
			);
		} catch ( err ) {
			showNotice(
				err.message || __( 'Failed to save settings', 'dragwyb-click-to-chat' ),
				'error'
			);
		} finally {
			setSaving( false );
		}
	};

	const panelClass = ( id ) =>
		'dctc-ai-bot-panel ' + ( subtab === id ? '' : 'dctc-ai-kb-panel--hidden' );

	return (
		<div className="dctc-ai-bot-settings">
			<nav
				className="dctc-ai-kb-subnav"
				role="tablist"
				aria-label={ __( 'Chatbot settings sections', 'dragwyb-click-to-chat' ) }
			>
				{ SUBTABS.map( ( tab ) => (
					<button
						key={ tab.id }
						type="button"
						role="tab"
						aria-selected={ subtab === tab.id }
						className={
							'dctc-ai-kb-subtab ' + ( subtab === tab.id ? 'active' : '' )
						}
						onClick={ () => setSubtab( tab.id ) }
					>
						{ tab.label }
					</button>
				) ) }
			</nav>

			<form onSubmit={ onSubmit }>
				{ /* General */ }
				<div className={ panelClass( 'general' ) }>
					<section className="dctc-ai-bot-section">
						<h2 className="dctc-ai-bot-section__title">
							{ __( 'Bot Identity', 'dragwyb-click-to-chat' ) }
						</h2>
						<div className="dctc-ai-bot-col--stack">
							<div className="dctc-ai-bot-field">
								<label htmlFor="bot_name">
									{ __( 'AI Assistant Name', 'dragwyb-click-to-chat' ) }
								</label>
								<input
									type="text"
									id="bot_name"
									className="dctc-ai-bot-input"
									value={ form.bot_name }
									onChange={ ( e ) => setField( 'bot_name', e.target.value ) }
									placeholder={ __( 'AI Assistant', 'dragwyb-click-to-chat' ) }
								/>
								<p className="dctc-ai-bot-hint">
									{ __(
										'This name will appear in the chat window header for your users.',
										'dragwyb-click-to-chat'
									) }
								</p>
							</div>
							<div className="dctc-ai-bot-field">
								<label htmlFor="greeting_msg">
									{ __( 'Greeting Message', 'dragwyb-click-to-chat' ) }
								</label>
								<textarea
									id="greeting_msg"
									className="dctc-ai-bot-input dctc-ai-bot-textarea"
									rows="4"
									value={ form.greeting_msg }
									onChange={ ( e ) =>
										setField( 'greeting_msg', e.target.value )
									}
									placeholder={ __(
										'Hello! I am your AI assistant. How can I help you today?',
										'dragwyb-click-to-chat'
									) }
								/>
								<p className="dctc-ai-bot-hint">
									{ __(
										'The first message your chatbot sends to initiate a conversation.',
										'dragwyb-click-to-chat'
									) }
								</p>
							</div>
							<div className="dctc-ai-bot-field">
								<label htmlFor="support_url">
									{ __( 'Support System URL', 'dragwyb-click-to-chat' ) }
								</label>
								<input
									type="text"
									id="support_url"
									className="dctc-ai-bot-input"
									value={ form.support_url }
									onChange={ ( e ) =>
										setField( 'support_url', e.target.value )
									}
									placeholder={ __(
										'https://example.com/support',
										'dragwyb-click-to-chat'
									) }
								/>
								<p className="dctc-ai-bot-hint">
									{ __(
										'The link users will be redirected to when they click "support agent" in case of errors.',
										'dragwyb-click-to-chat'
									) }
								</p>
							</div>
							<div className="dctc-ai-bot-field">
								<label htmlFor="api_error_msg">
									{ __( 'API/Server Error Message', 'dragwyb-click-to-chat' ) }
								</label>
								<textarea
									id="api_error_msg"
									className="dctc-ai-bot-input dctc-ai-bot-textarea"
									rows="3"
									value={ form.api_error_msg }
									onChange={ ( e ) =>
										setField( 'api_error_msg', e.target.value )
									}
									placeholder={ __(
										'There is some error on server, please contact our [support agent]({support_url}).',
										'dragwyb-click-to-chat'
									) }
								/>
								<p className="dctc-ai-bot-hint">
									{ __(
										'The message shown when a connection or API error occurs. You can use the {support_url} placeholder to insert the Support URL link.',
										'dragwyb-click-to-chat'
									) }
								</p>
							</div>
						</div>
					</section>
				</div>

				{ /* Advance */ }
				<div className={ panelClass( 'advanced' ) }>
					<section className="dctc-ai-bot-section">
						<h2 className="dctc-ai-bot-section__title">
							{ __( 'Security', 'dragwyb-click-to-chat' ) }
						</h2>
						<div className="dctc-ai-bot-field">
							<label htmlFor="rate_limit_per_minute">
								{ __(
									'Chat Rate Limit (requests per minute per visitor)',
									'dragwyb-click-to-chat'
								) }
							</label>
							<input
								type="number"
								id="rate_limit_per_minute"
								className="dctc-ai-bot-input"
								min="1"
								max="300"
								value={ form.rate_limit_per_minute }
								onChange={ ( e ) =>
									setField(
										'rate_limit_per_minute',
										parseInt( e.target.value, 10 ) || 1
									)
								}
							/>
							<p className="dctc-ai-bot-hint">
								{ __(
									"The public chat endpoint is throttled per visitor IP to stop it being used to run up your AI provider bill. Lower this if you're seeing abuse, or raise it if legitimate users are being blocked.",
									'dragwyb-click-to-chat'
								) }
							</p>
						</div>
					</section>

					<section className="dctc-ai-bot-section">
						<h2 className="dctc-ai-bot-section__title">
							{ __( 'Conversation Features', 'dragwyb-click-to-chat' ) }
						</h2>
						<div className="dctc-ai-bot-features">
							<SettingCard
								id="save_chat"
								title={ __( 'Save Chat History', 'dragwyb-click-to-chat' ) }
								desc={ __(
									'Enable user session continuity',
									'dragwyb-click-to-chat'
								) }
								checked={ form.save_chat }
								onChange={ ( v ) => setField( 'save_chat', v ) }
							/>
							{ form.save_chat && (
								<SettingCard
									id="ask_email"
									title={ __( 'Ask User Email', 'dragwyb-click-to-chat' ) }
									desc={ __(
										'Prompt user to enter their email to save chat continuity',
										'dragwyb-click-to-chat'
									) }
									checked={ !! form.ask_email }
									onChange={ ( v ) => setField( 'ask_email', v ) }
								/>
							) }
							<SettingCard
								id="enable_pre_questions"
								title={ __(
									'Enable Suggested Questions',
									'dragwyb-click-to-chat'
								) }
								desc={ __(
									'Provide quick suggestion questions to start a conversation',
									'dragwyb-click-to-chat'
								) }
								checked={ form.enable_pre_questions }
								onChange={ ( v ) => setField( 'enable_pre_questions', v ) }
							/>
						</div>
					</section>

					{ form.enable_pre_questions && (
						<>
							<section className="dctc-ai-bot-section">
								<h2 className="dctc-ai-bot-section__title">
									{ __( 'Suggested Questions', 'dragwyb-click-to-chat' ) }
								</h2>
								<p
									className="dctc-ai-bot-hint"
									style={ { marginBottom: '1.25rem' } }
								>
									{ __(
										'Provide up to 4 suggested questions that users can click to instantly start a conversation.',
										'dragwyb-click-to-chat'
									) }
								</p>
								<div className="dctc-ai-bot-grid">
									<div className="dctc-ai-bot-col">
										<div className="dctc-ai-bot-field">
											<label htmlFor="pre_question_1">
												{ __(
													'Suggested Question 1',
													'dragwyb-click-to-chat'
												) }
											</label>
											<input
												type="text"
												id="pre_question_1"
												className="dctc-ai-bot-input"
												value={ form.pre_question_1 }
												onChange={ ( e ) =>
													setField( 'pre_question_1', e.target.value )
												}
												placeholder={ __(
													'e.g. What services do you offer?',
													'dragwyb-click-to-chat'
												) }
											/>
										</div>
										<div className="dctc-ai-bot-field">
											<label htmlFor="pre_question_2">
												{ __(
													'Suggested Question 2',
													'dragwyb-click-to-chat'
												) }
											</label>
											<input
												type="text"
												id="pre_question_2"
												className="dctc-ai-bot-input"
												value={ form.pre_question_2 }
												onChange={ ( e ) =>
													setField( 'pre_question_2', e.target.value )
												}
												placeholder={ __(
													'e.g. How can I contact support?',
													'dragwyb-click-to-chat'
												) }
											/>
										</div>
									</div>
									<div className="dctc-ai-bot-col">
										<div className="dctc-ai-bot-field">
											<label htmlFor="pre_question_3">
												{ __(
													'Suggested Question 3',
													'dragwyb-click-to-chat'
												) }
											</label>
											<input
												type="text"
												id="pre_question_3"
												className="dctc-ai-bot-input"
												value={ form.pre_question_3 }
												onChange={ ( e ) =>
													setField( 'pre_question_3', e.target.value )
												}
												placeholder={ __(
													'e.g. What are your pricing plans?',
													'dragwyb-click-to-chat'
												) }
											/>
										</div>
										<div className="dctc-ai-bot-field">
											<label htmlFor="pre_question_4">
												{ __(
													'Suggested Question 4',
													'dragwyb-click-to-chat'
												) }
											</label>
											<input
												type="text"
												id="pre_question_4"
												className="dctc-ai-bot-input"
												value={ form.pre_question_4 }
												onChange={ ( e ) =>
													setField( 'pre_question_4', e.target.value )
												}
												placeholder={ __(
													'e.g. Tell me about your company.',
													'dragwyb-click-to-chat'
												) }
											/>
										</div>
									</div>
								</div>
							</section>

							<section className="dctc-ai-bot-section">
								<h2 className="dctc-ai-bot-section__title">
									{ __(
										'Suggested Questions Styling',
										'dragwyb-click-to-chat'
									) }
								</h2>
								<div className="dctc-ai-bot-grid">
									<div className="dctc-ai-bot-col">
										<ColorField
											id="pre_questions_bg_color"
											label={ __(
												'Background Color',
												'dragwyb-click-to-chat'
											) }
											value={ form.pre_questions_bg_color }
											onChange={ ( v ) =>
												setField( 'pre_questions_bg_color', v )
											}
										/>
										<ColorField
											id="pre_questions_text_color"
											label={ __( 'Text Color', 'dragwyb-click-to-chat' ) }
											value={ form.pre_questions_text_color }
											onChange={ ( v ) =>
												setField( 'pre_questions_text_color', v )
											}
										/>
									</div>
									<div className="dctc-ai-bot-col">
										<ColorField
											id="pre_questions_border_color"
											label={ __(
												'Border Color',
												'dragwyb-click-to-chat'
											) }
											value={ form.pre_questions_border_color }
											onChange={ ( v ) =>
												setField( 'pre_questions_border_color', v )
											}
										/>
										<div className="dctc-ai-bot-field">
											<label htmlFor="pre_questions_border_radius">
												{ __(
													'Border Radius Style',
													'dragwyb-click-to-chat'
												) }
											</label>
											<select
												id="pre_questions_border_radius"
												className="dctc-ai-bot-select"
												value={ form.pre_questions_border_radius }
												onChange={ ( e ) =>
													setField(
														'pre_questions_border_radius',
														e.target.value
													)
												}
											>
												<option value="rounded">
													{ __(
														'Rounded (Default)',
														'dragwyb-click-to-chat'
													) }
												</option>
												<option value="square">
													{ __( 'Square', 'dragwyb-click-to-chat' ) }
												</option>
												<option value="pill">
													{ __( 'Pill', 'dragwyb-click-to-chat' ) }
												</option>
											</select>
										</div>
									</div>
								</div>
							</section>
						</>
					) }
				</div>

				{ /* Style */ }
				<div className={ panelClass( 'style' ) }>
					<div className="dctc-ai-bot-grid">
						<section className="dctc-ai-bot-section">
							<h2 className="dctc-ai-bot-section__title">
								{ __( 'Bot Avatar', 'dragwyb-click-to-chat' ) }
							</h2>
							<div
								className="dctc-ai-bot-avatar-card"
								style={ { margin: '0 auto' } }
							>
								<div className="dctc-ai-bot-avatar-preview">
									{ form.bot_avatar ? (
										<img src={ form.bot_avatar } alt="" />
									) : (
										<span>{ initial }</span>
									) }
								</div>
								<button
									type="button"
									className="dctc-ai-btn dctc-ai-btn-primary dctc-ai-bot-upload-btn"
									onClick={ openMedia }
								>
									<span
										className="dashicons dashicons-upload"
										aria-hidden="true"
									/>
									{ __( 'Upload New', 'dragwyb-click-to-chat' ) }
								</button>
								{ !! form.bot_avatar && (
									<button
										type="button"
										className="dctc-ai-bot-remove-avatar"
										onClick={ () => setField( 'bot_avatar', '' ) }
									>
										{ __( 'Remove', 'dragwyb-click-to-chat' ) }
									</button>
								) }
								<p className="dctc-ai-bot-hint dctc-ai-bot-hint--center">
									{ __(
										'JPG, PNG or SVG. Max size 2MB.',
										'dragwyb-click-to-chat'
									) }
								</p>
							</div>
						</section>

						<section className="dctc-ai-bot-section">
							<h2 className="dctc-ai-bot-section__title">
								{ __( 'Branding & Styles', 'dragwyb-click-to-chat' ) }
							</h2>
							<div className="dctc-ai-bot-col--stack">
								<div className="dctc-ai-bot-field">
									<label htmlFor="primary_color">
										{ __( 'Primary Color', 'dragwyb-click-to-chat' ) }
									</label>
									<div className="dctc-ai-bot-color-field">
										<input
											type="color"
											id="primary_color"
											className="dctc-ai-bot-color-picker"
											value={ form.primary_color }
											onChange={ ( e ) =>
												setField( 'primary_color', e.target.value )
											}
											aria-label={ __(
												'Primary color',
												'dragwyb-click-to-chat'
											) }
										/>
										<span className="dctc-ai-bot-color-value">
											{ form.primary_color }
										</span>
									</div>
									<p className="dctc-ai-bot-hint">
										{ __(
											'Used for the chat header, launcher button, and your messages.',
											'dragwyb-click-to-chat'
										) }
									</p>
								</div>
								<div className="dctc-ai-bot-field">
									<label htmlFor="bubble_style">
										{ __( 'Chat Bubble Style', 'dragwyb-click-to-chat' ) }
									</label>
									<select
										id="bubble_style"
										className="dctc-ai-bot-select"
										value={ form.bubble_style }
										onChange={ ( e ) =>
											setField( 'bubble_style', e.target.value )
										}
									>
										<option value="rounded">
											{ __( 'Rounded (Default)', 'dragwyb-click-to-chat' ) }
										</option>
										<option value="square">
											{ __( 'Square', 'dragwyb-click-to-chat' ) }
										</option>
										<option value="pill">
											{ __( 'Pill', 'dragwyb-click-to-chat' ) }
										</option>
									</select>
									<p className="dctc-ai-bot-hint">
										{ __(
											'Controls the corner rounding of chat message bubbles.',
											'dragwyb-click-to-chat'
										) }
									</p>
								</div>
							</div>
						</section>
					</div>
				</div>

				<footer className="dctc-ai-bot-footer">
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
