import { defineConfig } from 'vitest/config';

// Unit tests run under wp-scripts test-unit-js (Vitest since scripts 36).
// Limit discovery to the plugin's own source: Vitest's default glob would
// otherwise pick up the Playwright specs in specs/ and the vendored lib/.
export default defineConfig( {
	test: {
		include: [ 'src/**/*.test.{js,jsx}', 'assets/**/*.test.{js,jsx}' ],
		globals: false,
		restoreMocks: true,
	},
} );
