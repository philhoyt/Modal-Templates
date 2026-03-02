const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

/**
 * @wordpress/scripts uses an async function for entry (to auto-discover
 * block.json editorScript fields). Spread won't call it, so we wrap it
 * and merge in the extra modal-button entry point.
 */
module.exports = {
	...defaultConfig,
	entry: async () => {
		const blockEntries =
			typeof defaultConfig.entry === 'function'
				? await defaultConfig.entry()
				: defaultConfig.entry;

		return {
			...blockEntries,
			'modal-button/index': './src/modal-button/index.js',
		};
	},
};
