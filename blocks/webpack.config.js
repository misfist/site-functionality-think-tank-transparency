const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const Dotenv = require( 'dotenv-webpack' );
/**
 * Webpack config (Development mode)
 *
 * @see https://developer.wordpress.org/block-editor/packages/packages-scripts/#provide-your-own-webpack-config
 */
module.exports = {
	...defaultConfig,
	entry: {
		...defaultConfig.entry,
	},
	plugins: [
		...defaultConfig.plugins,

		new Dotenv( {
			allowEmptyValues: true, // allow empty variables (e.g. `FOO=`) (treat it as empty string, rather than missing)
			silent: true, // hide any errors
			defaults: true, // load '.env.defaults' as the default values if empty.
		} ),
	],
};
