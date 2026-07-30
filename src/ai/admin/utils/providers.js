/**
 * Shared AI provider metadata for API Keys + Setup Wizard.
 */
import { __ } from '@wordpress/i18n';

export const PROVIDERS = {
	openai: {
		name: __( 'OpenAI', 'dragwyb-click-to-chat' ),
		link: 'https://platform.openai.com/settings/organization/api-keys',
	},
	google: {
		name: __( 'Google Gemini', 'dragwyb-click-to-chat' ),
		link: 'https://aistudio.google.com/api-keys',
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
	if ( settings.chatbot?.default_provider ) {
		return settings.chatbot.default_provider;
	}
	if ( keys.google && ! keys.openai ) {
		return 'google';
	}
	return 'openai';
}

export function formatProviderLabel( provider ) {
	if ( ! provider ) {
		return __( 'Unknown', 'dragwyb-click-to-chat' );
	}
	const map = { openai: 'OpenAI', google: 'Google' };
	const key = provider.toLowerCase();
	return map[ key ] || provider.charAt( 0 ).toUpperCase() + provider.slice( 1 );
}
