=== Modal Templates ===
Contributors: philhoyt
Tags: modal, popup, block editor, gutenberg, template parts
Requires at least: 6.4
Tested up to: 6.7
Stable tag: 1.0.0
Requires PHP: 8.1
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Design modal contents as template parts in the Site Editor and attach them to any button or group block as a trigger.

== Description ==

A WordPress block plugin that lets you design modal contents as template parts in the Site Editor, then attach them to any button or group block as a trigger.

**How it works**

1. Design modal content as a template part in the Site Editor
2. WordPress pre-renders that template part into a hidden `<template>` element inline on the page
3. When a trigger is clicked, the frontend JS clones that element into the modal shell

Because rendering happens inside the `render_block` filter, it runs within the active post context. This means a single template part can render dynamic, per-post content when used inside a Query Loop — each post gets its own pre-rendered modal with that post's data.

**Features**

* **Block editor native** — extends core Button and Group blocks via `addFilter`
* **Site Editor integration** — create and edit modal template parts within the editor; "Edit in Site Editor" link opens directly to the selected part
* **Query Loop support** — one template part, dynamic content per post
* **Full accessibility** — `role="dialog"`, `aria-modal`, `aria-labelledby` (dynamic from heading), `aria-expanded`, `aria-controls`, `aria-haspopup`, `inert` on background content, focus trap, focus return on close, ESC to close, iOS scroll lock
* **Close animation** — smooth fade + slide out with mobile bottom-sheet variant; respects `prefers-reduced-motion`; pauses video/audio on close
* **Width options** — Small (480px), Medium (640px), Large (960px), Full, or Custom (set in Settings)
* **Settings page** — Settings > Modal Templates lets non-developers adjust backdrop colour/opacity, dialog background, border radius, content padding, custom width, and close button colour
* **CSS custom properties** — every design token is a variable; theme CSS always wins over settings-page values

== Installation ==

1. Upload the `modal-templates` folder to `/wp-content/plugins/`
2. Activate via **Plugins > Installed Plugins**
3. Optionally adjust styles at **Settings > Modal Templates**

**Requirements**

* WordPress 6.4+
* PHP 8.1+
* A block theme (required for template parts and the Site Editor)

== Usage ==

**Create a modal template part**

In the block editor, select a Button or Group block and open the **Modal** panel in the inspector sidebar. Click **+ New modal template**, give it a name, and it opens in the Site Editor ready to edit.

**Attach a modal to a trigger**

1. Select a Button or Group block
2. Open the **Modal** panel in the inspector
3. Choose a template part from the dropdown
4. Set a width (defaults to Medium)

On the frontend, clicking the button or group opens the modal with that template part's rendered content.

**Programmatic control**

`
// Open the modal for any element that has data-modal-content-id set
window.ModalTemplates.open( triggerElement );

// Close the currently open modal
window.ModalTemplates.close();
`

== Frequently Asked Questions ==

= Does this work with any theme? =

It requires a block theme (one that uses the Site Editor). Classic themes are not compatible.

= Does it work inside Query Loops? =

Yes. Each post in a Query Loop gets its own pre-rendered modal containing that post's data — no AJAX required.

= Can I style the modal to match my theme? =

Yes, in two ways. Non-developers can use **Settings > Modal Templates** to adjust colours, border radius, padding, and width. Developers can override any CSS custom property in their theme stylesheet:

`
:root {
    --mt-dialog-bg: #1a1a1a;
    --mt-dialog-radius: 0;
    --mt-dialog-padding: 3rem;
}
`

Theme CSS always takes precedence over the settings-page values.

= Can I open or close a modal programmatically? =

Yes. The plugin exposes a small JavaScript API on `window.ModalTemplates` — see the Usage section above.

= Will it conflict with my theme's z-index? =

Override the z-index custom property in your theme CSS:

`
:root {
    --mt-z-index: 9999;
}
`

== Changelog ==

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.0 =
Initial release.

== Other Notes ==

= CSS Custom Properties =

Override any of these in your theme stylesheet. Site admins can adjust the most common ones via **Settings > Modal Templates**.

* `--mt-backdrop-color` — overlay colour and opacity
* `--mt-dialog-bg` — dialog background colour
* `--mt-dialog-radius` — dialog corner radius
* `--mt-dialog-shadow` — dialog box shadow
* `--mt-dialog-padding` — content area padding
* `--mt-dialog-width-custom` — width used when the Custom option is selected
* `--mt-close-color` — close button icon colour
* `--mt-close-bg-hover` — close button hover background
* `--mt-z-index` — stacking order (default: 99999)
