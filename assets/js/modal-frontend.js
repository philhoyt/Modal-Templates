/**
 * Modal Templates — Frontend Controller
 *
 * Opens / closes the modal shell and clones pre-rendered template-part
 * content (stored in inert <template> elements on the page) into it.
 *
 * Accessibility features:
 *  - role="dialog" + aria-modal="true" + aria-labelledby (dynamic, from heading)
 *  - inert on all background content when modal is open (NVDA/FF compat)
 *  - Focus trap (Tab wrapping) + focus return to trigger on close
 *  - ESC to close, backdrop click to close
 *  - aria-expanded state on triggers
 *  - iOS scroll lock (fixed-position body trick)
 *  - Scroll position reset on each open
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

	let shell = null; // Single shared modal shell appended to <body>.
	let lastTrigger = null; // Element that opened the modal (for focus return).
	let scrollY = 0; // Saved scroll position for iOS scroll lock.

	/* ------------------------------------------------------------------ */
	/* Shell bootstrap                                                      */
	/* ------------------------------------------------------------------ */

	function getShell() {
		if ( shell ) {
			return shell;
		}

		shell = document.createElement( 'div' );
		shell.id = 'mt-modal-shell';
		shell.setAttribute( 'role', 'dialog' );
		shell.setAttribute( 'aria-modal', 'true' );
		shell.setAttribute( 'aria-hidden', 'true' );
		shell.setAttribute( 'aria-label', 'Modal' );
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
		shell
			.querySelector( '[data-mt-close]' )
			.addEventListener( 'click', closeModal );
		shell
			.querySelector( '[data-mt-backdrop]' )
			.addEventListener( 'click', closeModal );

		return shell;
	}

	/* ------------------------------------------------------------------ */
	/* Scroll lock (works on iOS Safari)                                   */
	/* ------------------------------------------------------------------ */

	function lockScroll() {
		scrollY = window.pageYOffset;
		document.body.style.position = 'fixed';
		document.body.style.top = `-${ scrollY }px`;
		document.body.style.width = '100%';
	}

	function unlockScroll() {
		document.body.style.removeProperty( 'position' );
		document.body.style.removeProperty( 'top' );
		document.body.style.removeProperty( 'width' );
		window.scrollTo( 0, scrollY );
	}

	/* ------------------------------------------------------------------ */
	/* Background inert                                                     */
	/* ------------------------------------------------------------------ */

	/**
	 * Apply or remove the `inert` attribute on every direct child of <body>
	 * except the modal shell. This prevents keyboard and screen-reader users
	 * from reaching background content while the modal is open — more
	 * reliable than aria-modal alone (e.g. NVDA + Firefox).
	 *
	 * @param {boolean} active
	 */
	function setBackgroundInert( active ) {
		Array.from( document.body.children ).forEach( ( el ) => {
			if ( el === shell ) {
				return;
			}
			if ( active ) {
				el.setAttribute( 'inert', '' );
			} else {
				el.removeAttribute( 'inert' );
			}
		} );
	}

	/* ------------------------------------------------------------------ */
	/* Open / close                                                         */
	/* ------------------------------------------------------------------ */

	function openModal( triggerEl ) {
		const contentId = triggerEl.dataset.modalContentId;
		const width = triggerEl.dataset.modalWidth || 'medium';

		if ( ! contentId ) {
			return;
		}

		const tpl = document.getElementById( contentId );
		if ( ! tpl ) {
			return;
		}

		lastTrigger = triggerEl;
		const s = getShell();

		// Cancel any in-progress close animation.
		s.classList.remove( 'mt-modal--closing' );

		// Width variant.
		s.querySelector( '[data-mt-dialog]' ).dataset.mtWidth = width;

		// Clone template content and reset scroll position.
		const contentEl = s.querySelector( '[data-mt-content]' );
		contentEl.innerHTML = '';
		contentEl.appendChild( tpl.content.cloneNode( true ) );
		contentEl.scrollTop = 0;

		// aria-labelledby: point dialog at the first heading in the content.
		const heading = contentEl.querySelector( 'h1, h2, h3, h4, h5, h6' );
		if ( heading ) {
			if ( ! heading.id ) {
				heading.id = 'mt-modal-heading';
			}
			s.setAttribute( 'aria-labelledby', heading.id );
			s.removeAttribute( 'aria-label' );
		} else {
			s.removeAttribute( 'aria-labelledby' );
			s.setAttribute( 'aria-label', 'Modal' );
		}

		// Reflect open state on the trigger.
		triggerEl.setAttribute( 'aria-expanded', 'true' );

		// Open shell.
		s.setAttribute( 'aria-hidden', 'false' );
		s.classList.add( 'mt-modal--open' );

		// Lock scroll + inert background.
		lockScroll();
		setBackgroundInert( true );

		// Move focus to first focusable element inside the dialog.
		window.requestAnimationFrame( () => {
			const first = s.querySelector( FOCUSABLE );
			( first || s ).focus();
		} );
	}

	function closeModal() {
		if ( ! shell || ! shell.classList.contains( 'mt-modal--open' ) ) {
			return;
		}

		// Pause any playing media before the exit animation begins.
		shell.querySelectorAll( 'video, audio' ).forEach( function ( el ) {
			el.pause();
		} );

		// Update ARIA + restore page state immediately — don't wait for animation.
		shell.setAttribute( 'aria-hidden', 'true' );
		unlockScroll();
		setBackgroundInert( false );

		if ( lastTrigger ) {
			lastTrigger.setAttribute( 'aria-expanded', 'false' );
			lastTrigger.focus();
			lastTrigger = null;
		}

		// Play exit animation, then fully hide the shell.
		shell.classList.remove( 'mt-modal--open' );
		shell.classList.add( 'mt-modal--closing' );

		const dialog = shell.querySelector( '[data-mt-dialog]' );

		// Timeout fallback covers reduced-motion (animationend never fires).
		const timer = setTimeout( function () {
			shell.classList.remove( 'mt-modal--closing' );
		}, 300 );

		dialog.addEventListener( 'animationend', function handler() {
			clearTimeout( timer );
			shell.classList.remove( 'mt-modal--closing' );
			dialog.removeEventListener( 'animationend', handler );
		} );
	}

	/* ------------------------------------------------------------------ */
	/* Keyboard handling                                                    */
	/* ------------------------------------------------------------------ */

	document.addEventListener( 'keydown', function ( e ) {
		if ( ! shell || ! shell.classList.contains( 'mt-modal--open' ) ) {
			return;
		}

		if ( e.key === 'Escape' ) {
			closeModal();
			return;
		}

		// Focus trap.
		if ( e.key === 'Tab' ) {
			const dialog = shell.querySelector( '[data-mt-dialog]' );
			const focusable = Array.from(
				dialog.querySelectorAll( FOCUSABLE )
			);

			if ( focusable.length === 0 ) {
				e.preventDefault();
				return;
			}

			const first = focusable[ 0 ];
			const last = focusable[ focusable.length - 1 ];

			if ( e.shiftKey && shell.ownerDocument.activeElement === first ) {
				e.preventDefault();
				last.focus();
			} else if (
				! e.shiftKey &&
				shell.ownerDocument.activeElement === last
			) {
				e.preventDefault();
				first.focus();
			}
		}
	} );

	/* ------------------------------------------------------------------ */
	/* Trigger wiring                                                       */
	/* ------------------------------------------------------------------ */

	function wireTriggers() {
		document
			.querySelectorAll( '[data-modal-content-id]' )
			.forEach( ( el ) => {
				if ( el.dataset.mtBound ) {
					return;
				}
				el.dataset.mtBound = '1';

				// Ensure static aria attributes are present.
				el.setAttribute( 'aria-controls', 'mt-modal-shell' );
				if ( ! el.hasAttribute( 'aria-expanded' ) ) {
					el.setAttribute( 'aria-expanded', 'false' );
				}

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
