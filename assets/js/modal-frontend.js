/**
 * Modal Templates — Frontend Controller
 *
 * Opens / closes the modal shell and clones pre-rendered template-part
 * content (stored in inert <template> elements on the page) into it.
 *
 * No AJAX required — content is server-rendered alongside each trigger,
 * which means it works correctly inside Query Loops: each card's <template>
 * already contains that specific post's data.
 */

( function () {
	'use strict';

	/* ------------------------------------------------------------------ */
	/* Constants                                                            */
	/* ------------------------------------------------------------------ */

	const FOCUSABLE = [
		'a[href]',
		'button:not([disabled])',
		'input:not([disabled])',
		'select:not([disabled])',
		'textarea:not([disabled])',
		'[tabindex]:not([tabindex="-1"])',
		'details > summary',
	].join( ', ' );

	/* ------------------------------------------------------------------ */
	/* State                                                                */
	/* ------------------------------------------------------------------ */

	let shell       = null; // Single shared modal shell appended to <body>.
	let lastTrigger = null; // Element that opened the modal (for focus return).

	/* ------------------------------------------------------------------ */
	/* Shell bootstrap                                                      */
	/* ------------------------------------------------------------------ */

	function getShell() {
		if ( shell ) return shell;

		shell = document.createElement( 'div' );
		shell.id = 'mt-modal-shell';
		shell.setAttribute( 'role', 'dialog' );
		shell.setAttribute( 'aria-modal', 'true' );
		shell.setAttribute( 'aria-hidden', 'true' );
		shell.setAttribute( 'tabindex', '-1' );
		shell.innerHTML = `
			<div class="mt-modal__backdrop" data-mt-backdrop></div>
			<div class="mt-modal__positioner">
				<div class="mt-modal__dialog" data-mt-dialog>
					<button
						class="mt-modal__close"
						data-mt-close
						aria-label="Close modal"
						type="button"
					>
						<span aria-hidden="true">&#x2715;</span>
					</button>
					<div class="mt-modal__content" data-mt-content></div>
				</div>
			</div>
		`;

		document.body.appendChild( shell );
		shell.querySelector( '[data-mt-close]' ).addEventListener( 'click', closeModal );
		shell.querySelector( '[data-mt-backdrop]' ).addEventListener( 'click', closeModal );

		return shell;
	}

	/* ------------------------------------------------------------------ */
	/* Open / close                                                         */
	/* ------------------------------------------------------------------ */

	function openModal( triggerEl ) {
		const contentId = triggerEl.dataset.modalContentId;
		const width     = triggerEl.dataset.modalWidth || 'medium';

		if ( ! contentId ) return;

		// Find the pre-rendered <template> element.
		const tpl = document.getElementById( contentId );
		if ( ! tpl ) return;

		lastTrigger = triggerEl;
		const s = getShell();

		// Set width.
		s.querySelector( '[data-mt-dialog]' ).dataset.mtWidth = width;

		// Clone template content into the modal's content area.
		const contentEl = s.querySelector( '[data-mt-content]' );
		contentEl.innerHTML = '';
		contentEl.appendChild( tpl.content.cloneNode( true ) );

		// Open.
		s.setAttribute( 'aria-hidden', 'false' );
		s.classList.add( 'mt-modal--open' );
		document.body.classList.add( 'mt-modal-open' );

		// Move focus to first focusable element inside the dialog.
		requestAnimationFrame( () => {
			const first = s.querySelector( FOCUSABLE );
			( first || s ).focus();
		} );
	}

	function closeModal() {
		if ( ! shell ) return;

		shell.setAttribute( 'aria-hidden', 'true' );
		shell.classList.remove( 'mt-modal--open' );
		document.body.classList.remove( 'mt-modal-open' );

		if ( lastTrigger ) {
			lastTrigger.focus();
			lastTrigger = null;
		}
	}

	/* ------------------------------------------------------------------ */
	/* Keyboard handling                                                    */
	/* ------------------------------------------------------------------ */

	document.addEventListener( 'keydown', function ( e ) {
		if ( ! shell || ! shell.classList.contains( 'mt-modal--open' ) ) return;

		if ( e.key === 'Escape' ) {
			closeModal();
			return;
		}

		// Focus trap.
		if ( e.key === 'Tab' ) {
			const dialog   = shell.querySelector( '[data-mt-dialog]' );
			const focusable = Array.from( dialog.querySelectorAll( FOCUSABLE ) );
			if ( focusable.length === 0 ) { e.preventDefault(); return; }

			const first = focusable[ 0 ];
			const last  = focusable[ focusable.length - 1 ];

			if ( e.shiftKey && document.activeElement === first ) {
				e.preventDefault();
				last.focus();
			} else if ( ! e.shiftKey && document.activeElement === last ) {
				e.preventDefault();
				first.focus();
			}
		}
	} );

	/* ------------------------------------------------------------------ */
	/* Trigger wiring                                                       */
	/* ------------------------------------------------------------------ */

	function wireTriggers() {
		document.querySelectorAll( '[data-modal-content-id]' ).forEach( ( el ) => {
			if ( el.dataset.mtBound ) return;
			el.dataset.mtBound = '1';

			el.addEventListener( 'click', () => openModal( el ) );
			el.addEventListener( 'keydown', ( e ) => {
				if ( e.key === 'Enter' || e.key === ' ' ) {
					e.preventDefault();
					openModal( el );
				}
			} );
		} );
	}

	/* ------------------------------------------------------------------ */
	/* Init                                                                 */
	/* ------------------------------------------------------------------ */

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', wireTriggers );
	} else {
		wireTriggers();
	}

	// Public API for advanced integrations.
	window.ModalTemplates = { open: openModal, close: closeModal };

} )();
