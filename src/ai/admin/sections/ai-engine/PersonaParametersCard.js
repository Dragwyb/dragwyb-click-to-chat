import { __ } from '@wordpress/i18n';

const PROMPT_HINT = __(
	'Explain how your chatbot should sound and behave—its tone, what it helps with, and any rules it should follow.',
	'dragwyb-click-to-chat'
);

export default function PersonaParametersCard({
	systemPrompt,
	onSystemPromptChange,
	temperature,
	onTemperatureChange,
	maxTokens,
	onMaxTokensChange,
}) {
	const temp = Number(temperature);

	return (
		<>
			{/* System Persona & Instructions Card */}
			<section className="dctc-ai-card">
				<header className="dctc-ai-card__header">
					<div className="dctc-ai-card__header-left">
						<div className="dctc-ai-card-icon">
							<span className="dashicons dashicons-edit" />
						</div>
						<div>
							<h2 className="dctc-ai-card__title">
								{__('System Persona & Instructions', 'dragwyb-click-to-chat')}
							</h2>
							<p className="dctc-ai-card__desc">
								{__('Define how your AI assistant speaks, behaves, answers questions, and represents your brand.', 'dragwyb-click-to-chat')}
							</p>
						</div>
					</div>
				</header>

				<div className="dctc-ai-card__body">
					<div className="dctc-ai-bot-field">
						<label htmlFor="system_prompt">
							{__('Custom ChatBot System Prompt', 'dragwyb-click-to-chat')}
						</label>
						<textarea
							id="system_prompt"
							className="dctc-ai-bot-input dctc-ai-bot-textarea"
							style={{ minHeight: '120px' }}
							rows="5"
							value={systemPrompt}
							onChange={(e) => onSystemPromptChange(e.target.value)}
							placeholder={__(
								"For example: You are a friendly customer assistant for our website. Answer visitor questions clearly, recommend relevant products, and keep responses concise and helpful.",
								'dragwyb-click-to-chat'
							)}
						/>
						<p className="dctc-ai-bot-hint">{PROMPT_HINT}</p>
					</div>
				</div>
			</section>

			{/* Model Parameters & Chat Accuracy Card */}
			<section className="dctc-ai-card">
				<header className="dctc-ai-card__header">
					<div className="dctc-ai-card__header-left">
						<div className="dctc-ai-card-icon">
							<span className="dashicons dashicons-admin-settings" />
						</div>
						<div>
							<h2 className="dctc-ai-card__title">
								{__('Model Parameters & Accuracy', 'dragwyb-click-to-chat')}
							</h2>
							<p className="dctc-ai-card__desc">
								{__('Fine-tune AI response creativity, precision, and maximum output length.', 'dragwyb-click-to-chat')}
							</p>
						</div>
					</div>
				</header>

				<div className="dctc-ai-card__body">
					<div className="dctc-ai-grid-2col">
						<div className="dctc-ai-bot-field">
							<div className="dctc-ai-instructions-param__head">
								<label htmlFor="temperature">
									{__('Chat Accuracy / Temperature', 'dragwyb-click-to-chat')}
								</label>
								<span className="dctc-ai-instructions-value-badge">
									{temp.toFixed(1)}
								</span>
							</div>
							<input
								type="range"
								id="temperature"
								className="dctc-ai-instructions-range"
								min="0"
								max="2"
								step="0.1"
								value={temperature}
								onChange={(e) => onTemperatureChange(e.target.value)}
							/>
							<div className="dctc-ai-instructions-range-labels">
								<span>{__('Focused / Precise (0.0)', 'dragwyb-click-to-chat')}</span>
								<span>{__('Balanced (0.7)', 'dragwyb-click-to-chat')}</span>
								<span>{__('Creative (2.0)', 'dragwyb-click-to-chat')}</span>
							</div>
							<p className="dctc-ai-bot-hint">
								{__('Lower values produce precise, predictable responses; higher values encourage creative answers.', 'dragwyb-click-to-chat')}
							</p>
						</div>

						<div className="dctc-ai-bot-field">
							<label htmlFor="max_tokens">
								{__('Max Tokens (Response Length)', 'dragwyb-click-to-chat')}
							</label>
							<input
								type="number"
								id="max_tokens"
								className="dctc-ai-bot-input"
								value={maxTokens}
								onChange={(e) => onMaxTokensChange(e.target.value)}
								min="100"
								max="8192"
								step="50"
							/>
							<p className="dctc-ai-bot-hint">
								{__('Sets the maximum length limit for chatbot responses in a single message.', 'dragwyb-click-to-chat')}
							</p>
						</div>
					</div>
				</div>
			</section>
		</>
	);
}
