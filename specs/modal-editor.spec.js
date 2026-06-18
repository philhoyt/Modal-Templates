/**
 * Editor smoke test: the block-editor extension in src/modal-button/index.js.
 *
 * The unit/render specs never load the editor bundle, so this confirms the
 * addFilter that injects the "Modal" inspector panel actually registers and
 * renders for a supported trigger block (core/group). It catches the class of
 * failure where the editor script errors on load and the panel never appears.
 */

const { test, expect } = require( '@wordpress/e2e-test-utils-playwright' );

test.describe( 'Modal editor extension', () => {
	test.beforeAll( async ( { requestUtils } ) => {
		await requestUtils.activatePlugin( 'modal-templates' );
	} );

	test.beforeEach( async ( { admin } ) => {
		await admin.createNewPost();
	} );

	test( 'a supported block shows the "Modal" inspector panel', async ( {
		editor,
		page,
	} ) => {
		await editor.insertBlock( { name: 'core/group' } );
		await editor.openDocumentSettingsSidebar();

		const settings = page.getByRole( 'region', {
			name: 'Editor settings',
		} );

		// The PanelBody added by withModalInspector renders as a toggle button.
		await expect(
			settings.getByRole( 'button', { name: 'Modal', exact: true } )
		).toBeVisible();
	} );

	test( 'an unsupported block does not show the "Modal" panel', async ( {
		editor,
		page,
	} ) => {
		await editor.insertBlock( { name: 'core/paragraph' } );
		await editor.openDocumentSettingsSidebar();

		const settings = page.getByRole( 'region', {
			name: 'Editor settings',
		} );

		await expect(
			settings.getByRole( 'button', { name: 'Modal', exact: true } )
		).toHaveCount( 0 );
	} );
} );
