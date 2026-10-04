/**
 * Shared AI provider metadata for API Keys + Setup Wizard.
 */
import { __ } from '@wordpress/i18n';

export const PROVIDERS = {
	openai: {
		name: __( 'OpenAI', 'dragwyb-click-to-chat' ),
		link: 'https://platform.openai.com/settings/organization/api-keys',
		icon: 'superhero-alt',
		badge: __( 'Industry Standard', 'dragwyb-click-to-chat' ),
		desc: __( 'GPT-4o, GPT-4o Mini, o3-mini & embeddings.', 'dragwyb-click-to-chat' ),
	},
	google: {
		name: __( 'Google Gemini', 'dragwyb-click-to-chat' ),
		link: 'https://aistudio.google.com/api-keys',
		icon: 'star-filled',
		badge: __( 'High Speed & Context', 'dragwyb-click-to-chat' ),
		desc: __( 'Gemini 3.5 Flash Lite, Gemini 2.0 Flash, Gemini 2.5 Flash & embeddings.', 'dragwyb-click-to-chat' ),
	},
	anthropic: {
		name: __( 'Anthropic Claude', 'dragwyb-click-to-chat' ),
		link: 'https://console.anthropic.com/settings/keys',
		icon: 'awards',
		badge: __( 'Top Intelligence', 'dragwyb-click-to-chat' ),
		desc: __( 'Claude 3.5 Sonnet, Claude 3.5 Haiku, Claude 3 Opus.', 'dragwyb-click-to-chat' ),
	},
	openrouter: {
		name: __( 'OpenRouter', 'dragwyb-click-to-chat' ),
		link: 'https://openrouter.ai/keys',
		icon: 'networking',
		badge: __( 'Multi-Model Router', 'dragwyb-click-to-chat' ),
		desc: __( 'Access hundreds of models via a single unified API key.', 'dragwyb-click-to-chat' ),
	},
	groq: {
		name: __( 'Groq LPUs', 'dragwyb-click-to-chat' ),
		link: 'https://console.groq.com/keys',
		icon: 'performance',
		badge: __( 'Ultra-Low Latency', 'dragwyb-click-to-chat' ),
		desc: __( 'Llama 3.3 70B & Mixtral running at lightning speed.', 'dragwyb-click-to-chat' ),
	},
	deepseek: {
		name: __( 'DeepSeek', 'dragwyb-click-to-chat' ),
		link: 'https://platform.deepseek.com/api_keys',
		icon: 'lightbulb',
		badge: __( 'Advanced Reasoning', 'dragwyb-click-to-chat' ),
		desc: __( 'DeepSeek V3 chat & DeepSeek R1 reasoning models.', 'dragwyb-click-to-chat' ),
	},
};

export const VECTOR_DB_OPTIONS = [
	{
		value: 'sqlite',
		label: __( 'SQLite (Local)', 'dragwyb-click-to-chat' ),
		desc: __( 'Local vector storage - no setup needed', 'dragwyb-click-to-chat' ),
	},
	{
		value: 'pinecone',
		label: __( 'Pinecone (Cloud)', 'dragwyb-click-to-chat' ),
		desc: __( 'Managed cloud service - requires API key', 'dragwyb-click-to-chat' ),
	},
];

export function resolveEmbeddingProvider( settings = {} ) {
	const keys = settings.api_keys || {};
	const fromRag = settings.rag?.embeddings?.provider;
	if ( fromRag ) {
		return fromRag;
	}
	if ( settings.chatbot?.default_provider && ( settings.chatbot.default_provider === 'openai' || settings.chatbot.default_provider === 'google' ) ) {
		return settings.chatbot.default_provider;
	}
	if ( keys.google && ! keys.openai ) {
		return 'google';
	}
	return 'openai';
}

export function formatProviderLabel( provider ) {
	if ( ! provider ) {
		return __( 'None / Unknown', 'dragwyb-click-to-chat' );
	}
	const map = {
		openai: 'OpenAI',
		google: 'Google Gemini',
		anthropic: 'Anthropic Claude',
		openrouter: 'OpenRouter',
		groq: 'Groq',
		deepseek: 'DeepSeek',
	};
	const key = provider.toLowerCase();
	return map[ key ] || provider.charAt( 0 ).toUpperCase() + provider.slice( 1 );
}
