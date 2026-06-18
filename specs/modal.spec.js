/**
 * E2E behaviour spec for the shipped frontend controller (assets/js/modal-frontend.js).
 *
 * The PHP `render_block` injection (data attributes + <template>) is covered by the
 * PHPUnit suite. This spec exercises the accessibility contract of the real frontend
 * script — open, focus management, focus trap, and the close paths (ESC + backdrop) —
 * against a minimal DOM harness that mirrors what the PHP filter emits on the page.
 */

const path = require( 'path' );
const { test, expect } = require( '@playwright/test' );

const FRONTEND_SCRIPT = path.resolve(
	__dirname,
	'../assets/js/modal-frontend.js'
);
const FRONTEND_STYLES = path.resolve(
	__dirname,
	'../assets/css/modal-frontend.css'
);

const HARNESS = `
	<main id="bg-content">
		<h1>Page</h1>
		<button id="trigger" data-modal-content-id="mt-tpl-1" data-modal-width="medium">
			Open modal
		</button>
	</main>
	<template id="mt-tpl-1" class="mt-modal-prerendered">
		<h2>Modal heading</h2>
		<p>Body copy.</p>
		<a id="inner-link" href="#">A link</a>
		<button id="inner-button" type="button">A button</button>
	</template>
`;

test.beforeEach( async ( { page } ) => {
	// about:blank gives us a clean document with no dependency on a running site;
	// we inject the real shipped controller and the markup the PHP filter produces.
	await page.goto( 'about:blank' );
	await page.setContent( `<!DOCTYPE html><html><body>${ HARNESS }</body></html>` );
	// Load the real stylesheet so the backdrop/positioner layering (the dialog
	// has pointer-events:all, the positioner none) behaves as it does in production.
	await page.addStyleTag( { path: FRONTEND_STYLES } );
	await page.addScriptTag( { path: FRONTEND_SCRIPT } );
} );

test( 'clicking a trigger opens the modal and clones the template content', async ( {
	page,
} ) => {
	await page.locator( '#trigger' ).click();

	const shell = page.locator( '#mt-modal-shell' );
	await expect( shell ).toHaveClass( /mt-modal--open/ );
	await expect( shell ).toHaveAttribute( 'aria-hidden', 'false' );
	await expect( shell ).toContainText( 'Modal heading' );
	await expect( page.locator( '#trigger' ) ).toHaveAttribute(
		'aria-expanded',
		'true'
	);

	// The dialog is labelled by the first heading in the cloned content.
	const labelledBy = await shell.getAttribute( 'aria-labelledby' );
	expect( labelledBy ).toBeTruthy();
} );

test( 'opening moves focus into the dialog and makes the background inert', async ( {
	page,
} ) => {
	await page.locator( '#trigger' ).click();

	// Focus lands on the first focusable element inside the dialog (the close button).
	await expect( page.locator( '#mt-modal-shell [data-mt-close]' ) ).toBeFocused();

	// Background content outside the shell is marked inert.
	await expect( page.locator( '#bg-content' ) ).toHaveAttribute( 'inert', '' );
} );

test( 'the focus trap wraps Tab and Shift+Tab inside the dialog', async ( {
	page,
} ) => {
	await page.locator( '#trigger' ).click();
	await expect( page.locator( '[data-mt-close]' ) ).toBeFocused();

	// Shift+Tab from the first focusable wraps to the last (inner button).
	await page.keyboard.press( 'Shift+Tab' );
	await expect( page.locator( '#inner-button' ) ).toBeFocused();

	// Tab from the last focusable wraps back to the first (close button).
	await page.keyboard.press( 'Tab' );
	await expect( page.locator( '[data-mt-close]' ) ).toBeFocused();
} );

test( 'Escape closes the modal and returns focus to the trigger', async ( {
	page,
} ) => {
	const trigger = page.locator( '#trigger' );
	await trigger.click();
	await expect( page.locator( '#mt-modal-shell' ) ).toHaveClass(
		/mt-modal--open/
	);

	await page.keyboard.press( 'Escape' );

	const shell = page.locator( '#mt-modal-shell' );
	await expect( shell ).not.toHaveClass( /mt-modal--open/ );
	await expect( shell ).toHaveAttribute( 'aria-hidden', 'true' );
	await expect( trigger ).toHaveAttribute( 'aria-expanded', 'false' );
	await expect( trigger ).toBeFocused();

	// Background inert is lifted on close.
	await expect( page.locator( '#bg-content' ) ).not.toHaveAttribute(
		'inert',
		''
	);
} );

test( 'clicking the backdrop closes the modal', async ( { page } ) => {
	await page.locator( '#trigger' ).click();
	await expect( page.locator( '#mt-modal-shell' ) ).toHaveClass(
		/mt-modal--open/
	);

	// Click a corner of the viewport: the positioner is pointer-events:none, so the
	// click falls through to the backdrop (the centered dialog is pointer-events:all).
	await page.mouse.click( 10, 10 );
	await expect( page.locator( '#mt-modal-shell' ) ).not.toHaveClass(
		/mt-modal--open/
	);
} );
