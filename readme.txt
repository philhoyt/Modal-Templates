=== Modal Templates ===
Contributors: philhoyt
Tags: modal, popup, block editor, gutenberg, template parts
Requires at least: 6.4
Tested up to: 7.1
Stable tag: 1.2.1
Requires PHP: 8.1
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Build modal content as a template part in the Site Editor and open it from a Button or Group block

== Description ==

Modal Templates adds a "Modal" template part area to the Site Editor. You design the modal content there as a normal template part, then pick it from a Modal panel on any core Button or Group block. On the front end, clicking that block opens the template part in a dialog.

The template part is rendered on the server inside the `render_block` filter, so it runs in the current post context. Put a trigger inside a Query Loop and each post gets its own copy of the modal with that post's data. No AJAX request is made when the modal opens.

Requires a block theme. Template parts and the Site Editor are not available in classic themes.

What the plugin does:

* Adds a Modal panel to the core Button and Group blocks. No custom blocks are registered.
* Creates a new modal template part from the Modal panel. The button and the "Edit in Site Editor" link are shown only to users who can create template parts.
* Renders the modal with `role="dialog"`, `aria-modal`, and `aria-labelledby` pointing at the first heading in the content.
* Sets `aria-expanded`, `aria-controls`, and `aria-haspopup` on the trigger.
* Marks background content `inert` while the modal is open, traps Tab inside the dialog, and returns focus to the trigger on close.
* Closes on Escape, on a backdrop click, or on the close button.
* Locks body scroll on iOS and compensates for the scrollbar width so the page does not shift.
* Fades and slides out on close, skips the animation under `prefers-reduced-motion`, and pauses any video or audio inside the modal.
* Offers five widths: Small (480px), Medium (640px), Large (960px), Full, or Custom.
* Loads its script and stylesheet only on pages where a trigger block renders.
* Replaces any `<a>` inside a Group trigger with `<span>` so the group does not contain nested links.
* Checks GitHub releases for updates with Plugin Update Checker.

= Usage =

1. Select a Button or Group block in the editor.
2. Open the Modal panel in the block sidebar.
3. Pick an existing modal template part, or click "+ New modal template", enter a name, and edit it in the Site Editor.
4. Set a width. Medium is the default.

A Button trigger with a link URL opens the modal instead of following the link.

= Styling =

Settings > Modal Templates sets the backdrop colour and opacity, dialog background, border radius, content padding, custom width, and close button colour. The page writes these as CSS custom properties, so a rule in your theme stylesheet overrides any of them:

* `--mt-backdrop-color` (default `rgba(0, 0, 0, 0.6)`)
* `--mt-dialog-bg` (`#fff`)
* `--mt-dialog-radius` (`8px`)
* `--mt-dialog-shadow` (`0 20px 60px rgba(0, 0, 0, 0.3)`)
* `--mt-dialog-padding` (`2rem`)
* `--mt-dialog-width-custom` (`800px`, used when the width is Custom)
* `--mt-close-color` (`#555`)
* `--mt-close-bg-hover` (`#f0f0f0`)
* `--mt-z-index` (`99999`)

= JavaScript API =

`
// Open the modal for any element that has data-modal-content-id set.
window.ModalTemplates.open( triggerElement );

// Close the open modal.
window.ModalTemplates.close();

// Refresh the static ARIA attributes on triggers inside a container.
// Clicks are delegated, so markup added after page load already works.
window.ModalTemplates.bind( container );
`

If you print triggers from your own code and the plugin's assets are not loading, force them on every page:

`
add_filter( 'modal_templates_force_enqueue_assets', '__return_true' );
`

== Installation ==

1. Download `modal-templates.zip` from the latest release at https://github.com/philhoyt/Modal-Templates/releases/latest.
2. In the WordPress admin, go to Plugins > Add New Plugin > Upload Plugin.
3. Choose the zip and click Install Now, then Activate Plugin.
4. Optional: adjust the modal styles at Settings > Modal Templates.

== Frequently Asked Questions ==

= Does it work with a classic theme? =

No. The plugin stores modal content as template parts, which need a block theme and the Site Editor.

= Does it work inside a Query Loop? =

Yes. Each post in the loop gets its own rendered copy of the modal with that post's data.

= The modal appears behind my header or another overlay. =

Raise the stacking order in your theme CSS:

`
:root {
    --mt-z-index: 9999;
}
`

= Does a template part that triggers itself cause a loop? =

No. A trigger for the template part that is already being rendered is left as a plain block.

= What is removed on uninstall? =

Deleting the plugin removes the `modal_templates_styles` option. Modal template parts are regular template parts and are not deleted.

== Screenshots ==

1. The Modal panel in the block sidebar with a template part and width selected.
2. A modal open on the front end, showing a team member bio triggered from a Query Loop.

== Changelog ==

= 1.2.1 =
* Fixed the update checker so it always installs the release zip, not whichever file GitHub lists first.
* Fixed the plugin zip so it ships the languages folder.
* Rewrote the readme files in plain English.

= 1.2.0 =
* Added a permission check so the create button and the Site Editor link appear only for users who can create template parts.
* Added an error notice when creating a template part fails.
* Fixed triggers added after page load, including triggers inside modal content, so they open their modal.
* Fixed Button triggers with a link URL to open the modal instead of following the link.
* Fixed focus handling: focus moves into the modal on open and returns to the trigger on close.
* Fixed closing the modal so it no longer removes an `inert` attribute set by another script.
* Fixed a template part that contains a trigger for itself so it no longer recurses until the page fails.
* Fixed front-end assets to load only on pages that contain a modal trigger. Added the `modal_templates_force_enqueue_assets` filter to load them everywhere.
* Fixed three-digit hex colours such as `#fff` in the style settings.
* Fixed the missing accessible label on the padding unit select.
* Tested up to WordPress 7.1.

= 1.1.1 =
* Tested up to WordPress 7.0.
* Updated the bundled Plugin Update Checker to 5.7.
* Removed unused internal code and added PHPUnit, Playwright, and CI. No change to plugin behaviour.

= 1.1.0 =
* Changed Group triggers to replace nested links with spans so the group markup is valid and the whole group is clickable.
* Added update checks against GitHub releases.

= 1.0.2 =
* Fixed a layout shift on Windows when the scrollbar disappears as a modal opens.

= 1.0.1 =
* Fixed the modal on mobile so it centres on screen instead of sitting at the bottom.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.1.0 =
Group triggers now replace nested links with spans. Update checks now come from GitHub releases.

= 1.0.2 =
Fixes a layout shift on Windows when the scrollbar disappears as a modal opens.

= 1.0.1 =
Fixes modal positioning on mobile. The modal now centres on screen instead of sitting at the bottom.

= 1.0.0 =
Initial release.
