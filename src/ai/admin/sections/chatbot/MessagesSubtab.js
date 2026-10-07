/**
 * Messages Subtab: Greetings, Starter Questions, Action Buttons, Order Tracking & Fallback Notices
 */
import { __ } from '@wordpress/i18n';
import { SwitcherCard, ColorField } from '../../components';

export default function MessagesSubtab({
	form,
	setField,
	addActionButton,
	updateActionButton,
	removeActionButton,
	getQuestionRadiusStyle,
	isWcActive,
}) {
	return (
		<div className="dctc-ai-tab-panel-section">
			{/* 1. Welcome Greeting & Proactive Teaser */}
			<section className="dctc-ai-card">
				<header className="dctc-ai-card__header">
					<div className="dctc-ai-card__header-left">
						<div className="dctc-ai-card-icon">
							<span className="dashicons dashicons-format-chat" />
						</div>
						<div>
							<h2 className="dctc-ai-card__title">
								{__('Conversational Greetings & Proactive Prompts', 'dragwyb-click-to-chat')}
							</h2>
							<p className="dctc-ai-card__desc">
								{__('Customize the welcome message and floating proactive bubble teaser.', 'dragwyb-click-to-chat')}
							</p>
						</div>
					</div>
				</header>

				<div className="dctc-ai-card__body">
					<div className="dctc-ai-form-stack">
						<div className="dctc-ai-bot-field">
							<label htmlFor="greeting_msg">
								{__('Initial Welcome Greeting Message', 'dragwyb-click-to-chat')}
							</label>
							<textarea
								id="greeting_msg"
								className="dctc-ai-bot-input dctc-ai-bot-textarea"
								rows="3"
								value={form.greeting_msg}
								onChange={(e) => setField('greeting_msg', e.target.value)}
								placeholder={__('Hello! I am your AI assistant. How can I help you today?', 'dragwyb-click-to-chat')}
							/>
							<p className="dctc-ai-bot-hint">
								{__('Sent automatically to greet visitors when they open the chat widget.', 'dragwyb-click-to-chat')}
							</p>
						</div>

						<div className="dctc-ai-bot-field">
							<label htmlFor="proactive_bubble_message">
								{__('Proactive Teaser Bubble Message', 'dragwyb-click-to-chat')}
							</label>
							<input
								type="text"
								id="proactive_bubble_message"
								className="dctc-ai-bot-input"
								value={form.proactive_bubble_message}
								onChange={(e) => setField('proactive_bubble_message', e.target.value)}
								placeholder="👋 Hi there! Have a question about this page? Let me know if I can help!"
							/>
							<p className="dctc-ai-bot-hint">
								{__('Displayed inside the floating popup preview bubble above the launcher button.', 'dragwyb-click-to-chat')}
							</p>
						</div>
					</div>
				</div>
			</section>

			{/* 2. Suggested Starter Questions */}
			<SwitcherCard
				id="enable_pre_questions"
				title={__('Suggested Starter Questions', 'dragwyb-click-to-chat')}
				desc={__('Display clickable conversation starter pills when visitors first open the chat.', 'dragwyb-click-to-chat')}
				icon="dashicons-format-quote"
				checked={form.enable_pre_questions}
				onChange={(v) => setField('enable_pre_questions', v)}
				disabledNotice={__('Turn on to provide clickable starter questions when visitors open the chat.', 'dragwyb-click-to-chat')}
			>
				<div className="dctc-ai-grid-2col">
					<div className="dctc-ai-bot-field">
						<label htmlFor="pre_question_1">
							<span className="dctc-ai-num-pill">1</span>
							{__('Question 1', 'dragwyb-click-to-chat')}
						</label>
						<input
							type="text"
							id="pre_question_1"
							className="dctc-ai-bot-input"
							value={form.pre_question_1}
							onChange={(e) => setField('pre_question_1', e.target.value)}
							placeholder={__('e.g. What services do you offer?', 'dragwyb-click-to-chat')}
						/>
					</div>

					<div className="dctc-ai-bot-field">
						<label htmlFor="pre_question_2">
							<span className="dctc-ai-num-pill">2</span>
							{__('Question 2', 'dragwyb-click-to-chat')}
						</label>
						<input
							type="text"
							id="pre_question_2"
							className="dctc-ai-bot-input"
							value={form.pre_question_2}
							onChange={(e) => setField('pre_question_2', e.target.value)}
							placeholder={__('e.g. How can I contact support?', 'dragwyb-click-to-chat')}
						/>
					</div>

					<div className="dctc-ai-bot-field">
						<label htmlFor="pre_question_3">
							<span className="dctc-ai-num-pill">3</span>
							{__('Question 3', 'dragwyb-click-to-chat')}
						</label>
						<input
							type="text"
							id="pre_question_3"
							className="dctc-ai-bot-input"
							value={form.pre_question_3}
							onChange={(e) => setField('pre_question_3', e.target.value)}
							placeholder={__('e.g. What are your pricing plans?', 'dragwyb-click-to-chat')}
						/>
					</div>

					<div className="dctc-ai-bot-field">
						<label htmlFor="pre_question_4">
							<span className="dctc-ai-num-pill">4</span>
							{__('Question 4', 'dragwyb-click-to-chat')}
						</label>
						<input
							type="text"
							id="pre_question_4"
							className="dctc-ai-bot-input"
							value={form.pre_question_4}
							onChange={(e) => setField('pre_question_4', e.target.value)}
							placeholder={__('e.g. Tell me about your company.', 'dragwyb-click-to-chat')}
						/>
					</div>
				</div>

				<div className="dctc-ai-grid-2col" style={{ marginTop: '1.5rem', paddingTop: '1.25rem', borderTop: '1px solid #f1f5f9' }}>
					<div className="dctc-ai-form-stack">
						<ColorField
							id="pre_questions_bg_color"
							label={__('Background Color', 'dragwyb-click-to-chat')}
							value={form.pre_questions_bg_color}
							onChange={(v) => setField('pre_questions_bg_color', v)}
						/>
						<ColorField
							id="pre_questions_text_color"
							label={__('Text Color', 'dragwyb-click-to-chat')}
							value={form.pre_questions_text_color}
							onChange={(v) => setField('pre_questions_text_color', v)}
						/>
						<ColorField
							id="pre_questions_border_color"
							label={__('Border Color', 'dragwyb-click-to-chat')}
							value={form.pre_questions_border_color}
							onChange={(v) => setField('pre_questions_border_color', v)}
						/>
						<div className="dctc-ai-bot-field">
							<label htmlFor="pre_questions_border_radius">
								{__('Border Radius Style', 'dragwyb-click-to-chat')}
							</label>
							<select
								id="pre_questions_border_radius"
								className="dctc-ai-bot-select"
								value={form.pre_questions_border_radius}
								onChange={(e) => setField('pre_questions_border_radius', e.target.value)}
							>
								<option value="rounded">{__('Rounded (Default)', 'dragwyb-click-to-chat')}</option>
								<option value="square">{__('Square', 'dragwyb-click-to-chat')}</option>
								<option value="pill">{__('Pill', 'dragwyb-click-to-chat')}</option>
							</select>
						</div>
					</div>

					<div className="dctc-ai-preview-box-col">
						<div className="dctc-ai-preview-box-title">
							{__('Live Questions Preview', 'dragwyb-click-to-chat')}
						</div>
						<div className="dctc-ai-chips-preview-container">
							{[form.pre_question_1 || __('What services do you offer?', 'dragwyb-click-to-chat'),
							form.pre_question_2 || __('How can I contact support?', 'dragwyb-click-to-chat')].map((q, idx) => (
								<button
									key={idx}
									type="button"
									className="dctc-ai-preview-chip"
									style={{
										backgroundColor: form.pre_questions_bg_color,
										color: form.pre_questions_text_color,
										borderColor: form.pre_questions_border_color,
										borderRadius: getQuestionRadiusStyle(),
									}}
								>
									{q}
								</button>
							))}
						</div>
					</div>
				</div>
			</SwitcherCard>

			{/* 3. Quick Action Buttons in Chat Header */}
			<section className="dctc-ai-card">
				<header className="dctc-ai-card__header">
					<div className="dctc-ai-card__header-left">
						<div className="dctc-ai-card-icon">
							<span className="dashicons dashicons-button" />
						</div>
						<div>
							<h2 className="dctc-ai-card__title">
								{__('Quick Action Buttons in Chat Header', 'dragwyb-click-to-chat')}
							</h2>
							<p className="dctc-ai-card__desc">
								{__('Add custom shortcut links (e.g., Contact Us, Pricing, WhatsApp) directly inside the chat header.', 'dragwyb-click-to-chat')}
							</p>
						</div>
					</div>
					<button
						type="button"
						className="dctc-ai-btn dctc-ai-btn-secondary dctc-ai-btn-sm"
						onClick={addActionButton}
					>
						<span className="dashicons dashicons-plus-alt2" />
						{__('Add Action Button', 'dragwyb-click-to-chat')}
					</button>
				</header>

				<div className="dctc-ai-card__body">
					{form.action_buttons.length === 0 ? (
						<div className="dctc-ai-empty-state">
							<span className="dashicons dashicons-editor-break dctc-ai-empty-state__icon" />
							<p className="dctc-ai-empty-state__title">
								{__('No quick action buttons added yet', 'dragwyb-click-to-chat')}
							</p>
							<p className="dctc-ai-empty-state__desc">
								{__('Add shortcuts for frequently visited pages, phone links, or WhatsApp redirect.', 'dragwyb-click-to-chat')}
							</p>
							<button
								type="button"
								className="dctc-ai-btn dctc-ai-btn-secondary"
								onClick={addActionButton}
							>
								<span className="dashicons dashicons-plus-alt2" />
								{__('Add Your First Button', 'dragwyb-click-to-chat')}
							</button>
						</div>
					) : (
						<div className="dctc-ai-action-buttons-list">
							<div className="dctc-ai-action-buttons-header">
								<span className="dctc-ai-col-label flex-1">{__('Button Label / Text', 'dragwyb-click-to-chat')}</span>
								<span className="dctc-ai-col-label flex-2">{__('Destination URL', 'dragwyb-click-to-chat')}</span>
								<span className="dctc-ai-col-label w-120">{__('Target', 'dragwyb-click-to-chat')}</span>
								<span className="dctc-ai-col-label w-40">{__('Remove', 'dragwyb-click-to-chat')}</span>
							</div>

							{form.action_buttons.map((btn, index) => (
								<div key={btn.id || index} className="dctc-ai-action-button-row">
									<div className="flex-1">
										<input
											type="text"
											className="dctc-ai-bot-input"
											placeholder={__('e.g. 📞 Contact Us', 'dragwyb-click-to-chat')}
											value={btn.label}
											onChange={(e) => updateActionButton(index, 'label', e.target.value)}
										/>
									</div>
									<div className="flex-2">
										<input
											type="text"
											className="dctc-ai-bot-input"
											placeholder={__('https://... or /contact', 'dragwyb-click-to-chat')}
											value={btn.url}
											onChange={(e) => updateActionButton(index, 'url', e.target.value)}
										/>
									</div>
									<div className="w-120">
										<select
											className="dctc-ai-bot-select"
											value={btn.target || '_blank'}
											onChange={(e) => updateActionButton(index, 'target', e.target.value)}
										>
											<option value="_blank">{__('New Tab', 'dragwyb-click-to-chat')}</option>
											<option value="_self">{__('Same Tab', 'dragwyb-click-to-chat')}</option>
										</select>
									</div>
									<div className="w-40">
										<button
											type="button"
											className="dctc-ai-btn-icon-danger"
											onClick={() => removeActionButton(index)}
											title={__('Remove button', 'dragwyb-click-to-chat')}
										>
											<span className="dashicons dashicons-trash" />
										</button>
									</div>
								</div>
							))}
						</div>
					)}
				</div>
			</section>

			{/* 4. WooCommerce Order Tracking Messages (Only shown when WooCommerce is active) */}
			{isWcActive && (
				<section className="dctc-ai-card">
					<header className="dctc-ai-card__header">
						<div className="dctc-ai-card__header-left">
							<div className="dctc-ai-card-icon dctc-ai-card-icon--active">
								<span className="dashicons dashicons-cart" />
							</div>
							<div>
								<h2 className="dctc-ai-card__title">
									{__('WooCommerce Order Tracking Notices', 'dragwyb-click-to-chat')}
								</h2>
								<p className="dctc-ai-card__desc">
									{__('Set prompts, guest login requirement notices, and email verification mismatch messages.', 'dragwyb-click-to-chat')}
								</p>
							</div>
						</div>
					</header>

					<div className="dctc-ai-card__body">
						<div className="dctc-ai-form-stack">
							<div className="dctc-ai-bot-field">
								<label htmlFor="order_tracking_prompt_msg">
									{__('Order Tracking Form Header Prompt (Logged-in Users)', 'dragwyb-click-to-chat')}
								</label>
								<textarea
									id="order_tracking_prompt_msg"
									className="dctc-ai-bot-input dctc-ai-bot-textarea"
									rows="2"
									value={form.order_tracking_prompt_msg}
									onChange={(e) => setField('order_tracking_prompt_msg', e.target.value)}
									placeholder={__('Please enter your Order ID and billing email below to view your real-time order and shipment tracking details.', 'dragwyb-click-to-chat')}
								/>
								<p className="dctc-ai-bot-hint">
									{__('Displayed in chat when a logged-in user asks about tracking their order status.', 'dragwyb-click-to-chat')}
								</p>
							</div>

							<div className="dctc-ai-bot-field">
								<label htmlFor="order_tracking_login_msg">
									{__('Guest Login Required Notice', 'dragwyb-click-to-chat')}
									<span className="dctc-ai-var-badge">{'{login_url}'}</span>
								</label>
								<textarea
									id="order_tracking_login_msg"
									className="dctc-ai-bot-input dctc-ai-bot-textarea"
									rows="2"
									value={form.order_tracking_login_msg}
									onChange={(e) => setField('order_tracking_login_msg', e.target.value)}
									placeholder={__('To securely track your order status, please [log in to your account]({login_url}) first.', 'dragwyb-click-to-chat')}
								/>
								<p className="dctc-ai-bot-hint">
									{__('Returned when a guest/non-logged-in visitor attempts to track an order. {login_url} is replaced with your login/my-account link.', 'dragwyb-click-to-chat')}
								</p>
							</div>

							<div className="dctc-ai-bot-field">
								<label htmlFor="order_mismatch_msg">
									{__('Order Email Mismatch Privacy Notice', 'dragwyb-click-to-chat')}
								</label>
								<textarea
									id="order_mismatch_msg"
									className="dctc-ai-bot-input dctc-ai-bot-textarea"
									rows="2"
									value={form.order_mismatch_msg}
									onChange={(e) => setField('order_mismatch_msg', e.target.value)}
									placeholder={__('This order was purchased with a different email address. For privacy and security reasons, order details cannot be displayed.', 'dragwyb-click-to-chat')}
								/>
								<p className="dctc-ai-bot-hint">
									{__('Displayed when the tracked order belongs to a different email address or purchaser account.', 'dragwyb-click-to-chat')}
								</p>
							</div>
						</div>
					</div>
				</section>
			)}

			{/* 5. Support Escalation & Ticket Logging */}
			<section className="dctc-ai-card">
				<header className="dctc-ai-card__header">
					<div className="dctc-ai-card__header-left">
						<div className="dctc-ai-card-icon dctc-ai-card-icon--active">
							<span className="dashicons dashicons-sos" />
						</div>
						<div>
							<h2 className="dctc-ai-card__title">
								{__('Support Ticket Escalation Notice', 'dragwyb-click-to-chat')}
							</h2>
							<p className="dctc-ai-card__desc">
								{__('Message shown when the AI escalates complex queries directly into a Support Ticket.', 'dragwyb-click-to-chat')}
							</p>
						</div>
					</div>
				</header>

				<div className="dctc-ai-card__body">
					<div className="dctc-ai-form-stack">
						<div className="dctc-ai-bot-field">
							<label htmlFor="support_ticket_msg">
								{__('Automatic Ticket Creation Notice', 'dragwyb-click-to-chat')}
							</label>
							<textarea
								id="support_ticket_msg"
								className="dctc-ai-bot-input dctc-ai-bot-textarea"
								rows="3"
								value={form.support_ticket_msg}
								onChange={(e) => setField('support_ticket_msg', e.target.value)}
								placeholder={__('I have logged your inquiry with our support team and created a support ticket for this session. A support specialist will review your message and assist you shortly.', 'dragwyb-click-to-chat')}
							/>
							<p className="dctc-ai-bot-hint">
								{__('Sent to the visitor in chat when a support ticket is created for their active session.', 'dragwyb-click-to-chat')}
							</p>
						</div>

						<div className="dctc-ai-bot-field">
							<label htmlFor="human_agent_waiting_message">
								{__('Human Agent Waiting Status Message (Every 30s Update)', 'dragwyb-click-to-chat')}
							</label>
							<textarea
								id="human_agent_waiting_message"
								className="dctc-ai-bot-input dctc-ai-bot-textarea"
								rows="2"
								value={form.human_agent_waiting_message}
								onChange={(e) => setField('human_agent_waiting_message', e.target.value)}
								placeholder={__('Sorry to keep you waiting...', 'dragwyb-click-to-chat')}
							/>
							<p className="dctc-ai-bot-hint">
								{__('Displayed with the typing animation every 30 seconds when the visitor is waiting for a live human agent reply.', 'dragwyb-click-to-chat')}
							</p>
						</div>
					</div>
				</div>
			</section>

			{/* 6. Fallbacks, Server Errors & Daily Limits */}
			<section className="dctc-ai-card">
				<header className="dctc-ai-card__header">
					<div className="dctc-ai-card__header-left">
						<div className="dctc-ai-card-icon dctc-ai-card-icon--warning">
							<span className="dashicons dashicons-warning" />
						</div>
						<div>
							<h2 className="dctc-ai-card__title">
								{__('Fallbacks, Server Errors & Daily Limits', 'dragwyb-click-to-chat')}
							</h2>
							<p className="dctc-ai-card__desc">
								{__('Configure fallback responses for server issues, empty knowledge results, and quota caps.', 'dragwyb-click-to-chat')}
							</p>
						</div>
					</div>
				</header>

				<div className="dctc-ai-card__body">
					<div className="dctc-ai-form-stack">
						<div className="dctc-ai-bot-field">
							<label htmlFor="api_error_msg">
								{__('API / Server Error Fallback Message', 'dragwyb-click-to-chat')}
								<span className="dctc-ai-var-badge">{'{support_url}'}</span>
							</label>
							<textarea
								id="api_error_msg"
								className="dctc-ai-bot-input dctc-ai-bot-textarea"
								rows="3"
								value={form.api_error_msg}
								onChange={(e) => setField('api_error_msg', e.target.value)}
								placeholder={__('There is some error on server, please contact our [support agent]({support_url}).', 'dragwyb-click-to-chat')}
							/>
							<p className="dctc-ai-bot-hint">
								{__('Markdown supported. Use [support agent]({support_url}) to link to your Support Helpdesk URL.', 'dragwyb-click-to-chat')}
							</p>
						</div>

						<div className="dctc-ai-bot-field">
							<label htmlFor="no_data_message">
								{__('Knowledge Base / No Relevant Data Fallback Message', 'dragwyb-click-to-chat')}
							</label>
							<textarea
								id="no_data_message"
								className="dctc-ai-bot-input dctc-ai-bot-textarea"
								rows="3"
								value={form.no_data_message}
								onChange={(e) => setField('no_data_message', e.target.value)}
								placeholder={__("I don't have information about your question in my knowledge base. Please rephrase or ask about topics I have knowledge of.", 'dragwyb-click-to-chat')}
							/>
							<p className="dctc-ai-bot-hint">
								{__('Returned when the query cannot be answered by your indexed site content or trained knowledge base.', 'dragwyb-click-to-chat')}
							</p>
						</div>

						<div className="dctc-ai-bot-field">
							<label htmlFor="budget_limit_message">
								{__('Daily Chat Limit Reached Notice', 'dragwyb-click-to-chat')}
							</label>
							<textarea
								id="budget_limit_message"
								className="dctc-ai-bot-input dctc-ai-bot-textarea"
								rows="2"
								value={form.budget_limit_message}
								onChange={(e) => setField('budget_limit_message', e.target.value)}
								placeholder={__('You have reached the daily chat limit. Please connect with our team directly via WhatsApp or Support.', 'dragwyb-click-to-chat')}
							/>
							<p className="dctc-ai-bot-hint">
								{__('Displayed when a visitor exceeds the maximum permitted messages per day.', 'dragwyb-click-to-chat')}
							</p>
						</div>
					</div>
				</div>
			</section>

			{/* 7. Lead Generation Form Texts */}
			<section className="dctc-ai-card">
				<header className="dctc-ai-card__header">
					<div className="dctc-ai-card__header-left">
						<div className="dctc-ai-card-icon">
							<span className="dashicons dashicons-businessman" />
						</div>
						<div>
							<h2 className="dctc-ai-card__title">
								{__('Lead Capture Form Headings', 'dragwyb-click-to-chat')}
							</h2>
							<p className="dctc-ai-card__desc">
								{__('Configure headings displayed on interactive lead capture cards in chat.', 'dragwyb-click-to-chat')}
							</p>
						</div>
					</div>
				</header>

				<div className="dctc-ai-card__body">
					<div className="dctc-ai-form-stack">
						<div className="dctc-ai-grid-2col">
							<div className="dctc-ai-bot-field">
								<label htmlFor="lead_form_title">
									{__('Lead Card Heading', 'dragwyb-click-to-chat')}
								</label>
								<input
									type="text"
									id="lead_form_title"
									className="dctc-ai-bot-input"
									value={form.lead_form_title}
									onChange={(e) => setField('lead_form_title', e.target.value)}
									placeholder={__('Contact Our Team', 'dragwyb-click-to-chat')}
								/>
							</div>

							<div className="dctc-ai-bot-field">
								<label htmlFor="lead_form_subtitle">
									{__('Lead Card Subtitle / Description', 'dragwyb-click-to-chat')}
								</label>
								<input
									type="text"
									id="lead_form_subtitle"
									className="dctc-ai-bot-input"
									value={form.lead_form_subtitle}
									onChange={(e) => setField('lead_form_subtitle', e.target.value)}
									placeholder={__('Leave your details and our team will get back to you shortly.', 'dragwyb-click-to-chat')}
								/>
							</div>
						</div>
					</div>
				</div>
			</section>
		</div>
	);
}
