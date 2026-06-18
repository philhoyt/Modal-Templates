/**
 * ESLint flat config (ESLint v9 / @wordpress/scripts v32+).
 *
 * Extends the default flat config shipped with @wordpress/scripts
 * (which pulls in @wordpress/eslint-plugin's recommended ruleset) and
 * adds browser globals for the frontend controller in assets/js/.
 */
const defaultConfig = require( '@wordpress/scripts/config/eslint.config.cjs' );
const globals = require( 'globals' );

module.exports = [
	...defaultConfig,
	{
		ignores: [ 'bin/**', 'build/**', 'lib/**', 'vendor/**' ],
	},
	{
		languageOptions: {
			globals: {
				...globals.browser,
			},
		},
		rules: {
			// @wordpress/* packages are provided by WordPress at runtime and
			// externalized at build time by the Dependency Extraction plugin —
			// they are not node dependencies, so the import resolver must skip
			// them rather than report them as unresolved/extraneous.
			'import/no-unresolved': [ 'error', { ignore: [ '^@wordpress/' ] } ],
			// Every non-relative import in this project is a WordPress-provided
			// package, declared neither here nor bundled — disable the rule
			// rather than fight the externalized-dependency pattern.
			'import/no-extraneous-dependencies': 'off',
		},
	},
];
