/**
 * Playwright config for Modal Templates.
 *
 * Extends the default config shipped with @wordpress/scripts (login global-setup,
 * chromium project, and the WP_BASE_URL that wp-scripts derives from .wp-env.json's
 * tests port) and only points testDir at this project's specs/ folder — otherwise
 * Playwright resolves the bundled config's relative testDir inside node_modules and
 * reports "No tests found".
 *
 * E2E runs against the wp-env *tests* instance (testsPort 8991). That instance has
 * no active theme or plugin out of the box, so the real-flow spec (modal-render.spec.js)
 * activates a block theme and the plugin itself in beforeAll via requestUtils.
 */
const path = require( 'path' );
const baseConfig = require( '@wordpress/scripts/config/playwright.config' );

module.exports = {
	...baseConfig,
	testDir: path.join( __dirname, 'specs' ),
};
