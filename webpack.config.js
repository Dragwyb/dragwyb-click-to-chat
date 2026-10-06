const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');

module.exports = {
	...defaultConfig,
	entry: {
		'admin/dctc-ai-dashboard': path.resolve(
			__dirname,
			'src/ai/admin/index.js'
		),
		'frontend/dctc-ai-frontend': path.resolve(
			__dirname,
			'src/ai/frontend/index.js'
		),
		'support/dctc-support-dashboard': path.resolve(
			__dirname,
			'src/support-center/dashboard.js'
		),
		'support/dctc-support-tickets': path.resolve(
			__dirname,
			'src/support-center/tickets.js'
		),
		'support/dctc-support-agents': path.resolve(
			__dirname,
			'src/support-center/agents.js'
		),
		'support/dctc-support-taxonomies': path.resolve(
			__dirname,
			'src/support-center/taxonomies.js'
		),
		'support/dctc-support-settings': path.resolve(
			__dirname,
			'src/support-center/settings.js'
		),
	},
	output: {
		...defaultConfig.output,
		path: path.resolve(__dirname, 'build/ai'),
	},
};
