const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );

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
		'support/dctc-support-center': path.resolve(
			__dirname,
			'src/support-center/index.js'
		),
	},
	output: {
		...defaultConfig.output,
		path: path.resolve( __dirname, 'build/ai' ),
	},
};
