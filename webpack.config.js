const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

module.exports = {
	...defaultConfig,
	entry: {
		'modal-button/index': './src/modal-button/index.js',
	},
};
