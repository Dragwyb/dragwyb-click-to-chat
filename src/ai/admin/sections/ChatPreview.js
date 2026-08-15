/**
 * Read-only live preview of the chat widget appearance.
 */
import { __ } from '@wordpress/i18n';

const FEATURES = [
	__( 'Header & User Message colors', 'dragwyb-click-to-chat' ),
	__( 'Assistant Name & Greeting', 'dragwyb-click-to-chat' ),
	__( 'Bot Avatar updates', 'dragwyb-click-to-chat' ),
	__( 'Assistant Icon updates', 'dragwyb-click-to-chat' ),
	__( 'Message bubble styling', 'dragwyb-click-to-chat' ),
	__( 'Chat Launcher visibility', 'dragwyb-click-to-chat' ),
];

const SAMPLE_USER = __(
	'How do I customize this chat window?',
	'dragwyb-click-to-chat'
);
const SAMPLE_BOT = __(
	"It's easy! Just use the settings in the dashboard to change the name, colors, and more.",
	'dragwyb-click-to-chat'
);

export default function ChatPreview( { settings } ) {
	const bot = settings?.chatbot || {};
	const display = settings?.display || {};
	const primary = bot.primary_color || '#6366f1';
	const name = bot.bot_name || __( 'AI Assistant', 'dragwyb-click-to-chat' );
	const greeting =
		bot.greeting_msg ||
		__(
			'Hello! I am your AI assistant. How can I help you today?',
			'dragwyb-click-to-chat'
		);
	const styleClass = `dctc-ai-preview-widget--${ bot.bubble_style || 'rounded' }`;
	const avatar = bot.bot_avatar || '';
	const assistantIcon = display.assistant_icon || '';
	const initial = name.trim().charAt( 0 ).toUpperCase() || 'B';

	const Avatar = ( { className } ) => (
		<div className={ className } aria-hidden="true">
			{ avatar ? (
				<img src={ avatar } alt="" />
			) : className.includes( 'bot-avatar' ) ? (
				<span className="dashicons dashicons-admin-users" aria-hidden="true" />
			) : (
				<span>{ initial }</span>
			) }
		</div>
	);

	return (
		<div className="dctc-ai-preview">
			<div className="dctc-ai-preview__stage">
				<div
					className={ `dctc-ai-preview-widget ${ styleClass }` }
					style={ { '--dctc-ai-preview-primary': primary } }
				>
					<header className="dctc-ai-preview-widget__header">
						<div className="dctc-ai-preview-widget__header-main">
							<Avatar className="dctc-ai-preview-widget__avatar" />
							<div className="dctc-ai-preview-widget__info">
								<strong>{ name }</strong>
								<span className="dctc-ai-preview-widget__status">
									<span
										className="dctc-ai-preview-widget__status-dot"
										aria-hidden="true"
									/>
									{ __( 'Online', 'dragwyb-click-to-chat' ) }
								</span>
							</div>
						</div>
						<button
							type="button"
							className="dctc-ai-preview-widget__close"
							aria-label={ __( 'Close chat', 'dragwyb-click-to-chat' ) }
							tabIndex={ -1 }
						>
							<span className="dashicons dashicons-no-alt" aria-hidden="true" />
						</button>
					</header>

					<div className="dctc-ai-preview-widget__messages">
						<div className="dctc-ai-preview-widget__row dctc-ai-preview-widget__row--bot">
							<Avatar className="dctc-ai-preview-widget__bot-avatar" />
							<div className="dctc-ai-preview-widget__bubble dctc-ai-preview-widget__bubble--bot">
								<p>{ greeting }</p>
							</div>
						</div>
						<div className="dctc-ai-preview-widget__row dctc-ai-preview-widget__row--user">
							<div className="dctc-ai-preview-widget__bubble dctc-ai-preview-widget__bubble--user">
								<p>{ SAMPLE_USER }</p>
							</div>
						</div>
						<div className="dctc-ai-preview-widget__row dctc-ai-preview-widget__row--bot">
							<Avatar className="dctc-ai-preview-widget__bot-avatar" />
							<div className="dctc-ai-preview-widget__bubble dctc-ai-preview-widget__bubble--bot">
								<p>{ SAMPLE_BOT }</p>
							</div>
						</div>
					</div>

					<footer className="dctc-ai-preview-widget__footer">
						<input
							type="text"
							className="dctc-ai-preview-widget__input"
							placeholder={ __( 'Type a message…', 'dragwyb-click-to-chat' ) }
							disabled
							aria-label={ __( 'Message input', 'dragwyb-click-to-chat' ) }
						/>
						<button
							type="button"
							className="dctc-ai-preview-widget__send"
							disabled
							aria-label={ __( 'Send', 'dragwyb-click-to-chat' ) }
							tabIndex={ -1 }
						>
							<svg
								width="18"
								height="18"
								viewBox="0 0 24 24"
								fill="none"
								aria-hidden="true"
							>
								<path
									d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"
									stroke="currentColor"
									strokeWidth="2"
									strokeLinecap="round"
									strokeLinejoin="round"
								/>
							</svg>
						</button>
					</footer>
				</div>

				<div
					className={
						'dctc-ai-preview__launcher' +
						( assistantIcon ? ' dctc-ai-preview__launcher--custom' : '' )
					}
					style={ assistantIcon ? undefined : { background: primary } }
					aria-hidden="true"
				>
					{ assistantIcon ? (
						<img
							className="dctc-ai-preview__launcher-icon"
							src={ assistantIcon }
							alt=""
						/>
					) : (
						<span className="dashicons dashicons-format-chat" />
					) }
				</div>
			</div>

			<aside className="dctc-ai-preview__panel">
				<div className="dctc-ai-preview__panel-icon" aria-hidden="true">
					<span className="dashicons dashicons-visibility" />
				</div>
				<h3 className="dctc-ai-preview__panel-title">
					{ __( 'Live Preview', 'dragwyb-click-to-chat' ) }
				</h3>
				<p className="dctc-ai-preview__panel-desc">
					{ __(
						'Any changes you make in the Chatbot Settings will immediately reflect here. Test your user experience before going live.',
						'dragwyb-click-to-chat'
					) }
				</p>
				<ul className="dctc-ai-preview__features">
					{ FEATURES.map( ( feature ) => (
						<li key={ feature }>
							<span className="dctc-ai-preview__check" aria-hidden="true">
								<span className="dashicons dashicons-yes" />
							</span>
							{ feature }
						</li>
					) ) }
				</ul>
			</aside>
		</div>
	);
}
