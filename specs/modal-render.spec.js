/**
 * Golden-path E2E: the real WordPress flow.
 *
 * Unlike modal.spec.js (which drives the frontend controller over a synthetic DOM),
 * this test goes through WordPress end to end:
 *   1. Create a real template part in the "modal" area via REST.
 *   2. Publish a real page containing a core/button with a modalSlug attribute.
 *   3. Visit the rendered frontend page.
 *   4. Confirm the render_block filter injected a populated <template> and the
 *      trigger opens a modal showing the actual template-part content.
 *
 * This is the layer the unit tests can't reach: block_template_part() resolving a
 * real part, and the render_block pipeline firing on a published page.
 */

const { test, expect } = require( '@wordpress/e2e-test-utils-playwright' );

const MODAL_SLUG = 'modal-e2e';
const MODAL_HEADING = 'Hello from the template part';

test.describe( 'Modal render (real WordPress flow)', () => {
	let pageUrl;

	test.beforeAll( async ( { requestUtils } ) => {
		// The wp-env tests instance (where wp-scripts points E2E) has no active
		// theme and the plugin inactive by default. Set both up so the frontend
		// renders and the render_block filter runs. Idempotent + reproducible
		// across a fresh `wp-env start`.
		await requestUtils.activateTheme( 'twentytwentyfive' );
		await requestUtils.activatePlugin( 'modal-templates' );

		await requestUtils.deleteAllTemplates( 'wp_template_part' );
		await requestUtils.deleteAllPages();

		// 1. A real modal-area template part with a heading we can assert on.
		await requestUtils.createTemplate( 'wp_template_part', {
			slug: MODAL_SLUG,
			title: 'E2E Modal',
			area: 'modal',
			content: `<!-- wp:heading --><h2>${ MODAL_HEADING }</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Rendered through WordPress.</p><!-- /wp:paragraph -->`,
		} );

		// 2. A published page whose button carries the modalSlug attribute.
		const page = await requestUtils.createPage( {
			title: 'Modal E2E Page',
			status: 'publish',
			content: `<!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button {"modalSlug":"${ MODAL_SLUG }","modalWidth":"medium"} --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button">Open the modal</a></div><!-- /wp:button --></div><!-- /wp:buttons -->`,
		} );

		pageUrl = page.link;
	} );

	test.afterAll( async ( { requestUtils } ) => {
		await requestUtils.deleteAllPages();
		await requestUtils.deleteAllTemplates( 'wp_template_part' );
	} );

	test( 'the published page injects a populated <template> next to the trigger', async ( {
		page,
	} ) => {
		await page.goto( pageUrl );

		// The PHP render_block filter turned the button link into a trigger...
		const trigger = page.locator( '[data-modal-content-id]' );
		await expect( trigger ).toBeVisible();
		await expect( trigger ).toHaveAttribute( 'aria-haspopup', 'dialog' );

		// ...and pre-rendered the template-part content into a hidden <template>.
		const templateHtml = await page
			.locator( 'template.mt-modal-prerendered' )
			.evaluate( ( el ) => el.innerHTML );
		expect( templateHtml ).toContain( MODAL_HEADING );
	} );

	test( 'clicking the real trigger opens a modal with the template-part content', async ( {
		page,
	} ) => {
		await page.goto( pageUrl );

		await page.locator( '[data-modal-content-id]' ).click();

		const shell = page.locator( '#mt-modal-shell' );
		await expect( shell ).toHaveClass( /mt-modal--open/ );
		await expect( shell ).toContainText( MODAL_HEADING );
		await expect(
			page.locator( '[data-modal-content-id]' )
		).toHaveAttribute( 'aria-expanded', 'true' );

		// Close path still works on the real page.
		await page.keyboard.press( 'Escape' );
		await expect( shell ).not.toHaveClass( /mt-modal--open/ );
	} );
} );
