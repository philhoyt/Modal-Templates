# Modal Templates

[![CI](https://github.com/philhoyt/Modal-Templates/actions/workflows/ci.yml/badge.svg)](https://github.com/philhoyt/Modal-Templates/actions/workflows/ci.yml)

Build modal content as a template part in the Site Editor and open it from a core Button or Group block.

The plugin registers a "Modal" template part area and adds a Modal panel to the core Button and Group blocks. No custom blocks are registered. The chosen template part is rendered on the server inside the `render_block` filter and injected next to the trigger as a `<template>` element, so it runs in the current post context and works per post inside a Query Loop. The front-end script clones that template into a dialog shell on click; nothing is fetched when the modal opens.

The dialog uses `role="dialog"`, `aria-modal`, and `aria-labelledby` (from the first heading in the content). The trigger gets `aria-expanded`, `aria-controls`, and `aria-haspopup`. While open, background content is `inert`, Tab is trapped inside the dialog, and body scroll is locked (including on iOS). Escape, the backdrop, and the close button all close it; focus returns to the trigger, and any video or audio inside the modal is paused. The close animation is skipped under `prefers-reduced-motion`.

## Requirements

- WordPress 6.4 or later
- PHP 8.1 or later
- A block theme. Template parts and the Site Editor are not available in classic themes.

## Installation

1. Download `modal-templates.zip` from the [latest release](https://github.com/philhoyt/Modal-Templates/releases/latest).
2. In the WordPress admin, go to Plugins > Add New Plugin > Upload Plugin.
3. Choose the zip, click Install Now, then Activate Plugin.

The plugin checks GitHub releases for updates with Plugin Update Checker, so later versions show up on the Plugins screen.

## Usage

1. Select a Button or Group block.
2. Open the Modal panel in the block sidebar.
3. Pick a modal template part from the list, or click "+ New modal template", enter a name, and edit the new part in the Site Editor. The create button and the "Edit in Site Editor" link appear only for users who can create template parts.
4. Set a width: Small (480px), Medium (640px, the default), Large (960px), Full, or Custom.

On the front end, clicking the block opens the modal. A Button trigger with a link URL opens the modal instead of following the link. Inside a Group trigger, any `<a>` is replaced with `<span>` so the group does not contain nested links.

The front-end script and stylesheet are enqueued only on pages where a trigger renders. If you print triggers from your own code, load them on every page:

```php
add_filter( 'modal_templates_force_enqueue_assets', '__return_true' );
```

### JavaScript API

```js
// Open the modal for any element that has data-modal-content-id set.
window.ModalTemplates.open( triggerElement );

// Close the open modal.
window.ModalTemplates.close();

// Refresh the static ARIA attributes on triggers inside a container.
// Clicks are delegated, so markup added after page load already works.
window.ModalTemplates.bind( container );
```

## Configuration

Settings > Modal Templates sets the backdrop colour and opacity, dialog background, border radius, content padding (rem or px), custom width (200-3000px), and close button colour. The page writes these values as CSS custom properties on `:root`, and a rule in your theme stylesheet overrides any of them.

| Property | Default | Set from Settings |
|---|---|---|
| `--mt-backdrop-color` | `rgba(0, 0, 0, 0.6)` | Yes (colour and opacity) |
| `--mt-dialog-bg` | `#fff` | Yes |
| `--mt-dialog-radius` | `8px` | Yes |
| `--mt-dialog-shadow` | `0 20px 60px rgba(0, 0, 0, 0.3)` | No |
| `--mt-dialog-padding` | `2rem` | Yes |
| `--mt-dialog-width-custom` | `800px` | Yes, used when width is Custom |
| `--mt-close-color` | `#555` | Yes |
| `--mt-close-bg-hover` | `#f0f0f0` | No |
| `--mt-z-index` | `99999` | No |

Deleting the plugin removes the `modal_templates_styles` option. Modal template parts are ordinary template parts and are left in place.

## Development

```bash
npm install
composer install        # also copies Plugin Update Checker into lib/

npm start               # webpack watch
npm run build           # production build to build/modal-button/
npm run lint            # lint:js, lint:css, and phpcs
npm run format          # wp-scripts format

composer run lint       # phpcs
composer run analyse    # phpstan
npm run test:unit       # Vitest (wp-scripts test-unit-js)

npm run env:start       # wp-env
npm run test:php        # PHPUnit inside wp-env
npm run test:e2e        # Playwright specs in specs/
npm run i18n            # regenerate languages/modal-templates.pot, .mo, and .json
npm run plugin-zip      # build modal-templates.zip
```

CI runs phpcs and phpstan on PHP 8.1 through 8.4, lints and builds the JS, and runs PHPUnit and Playwright against wp-env.

## Releases

Bump the version in `modal-templates.php` (header and `MODAL_TEMPLATES_VERSION`), `readme.txt` (Stable tag and changelog), `package.json`, and the changelog in this file. Then push a lowercase `v` tag:

```bash
git tag v1.2.0 && git push origin v1.2.0
```

The release workflow builds the assets, runs `wp-scripts plugin-zip`, pulls the matching `= X.Y.Z =` section out of `readme.txt` for the release notes, and attaches `modal-templates.zip` and `readme.txt` to the GitHub release. The tag must start with a lowercase `v`: Plugin Update Checker strips the prefix case-sensitively, so an uppercase `V` tag is never offered as an update.

## Limitations

- Classic themes are not supported.
- A template part that contains a trigger for itself is rendered as a plain block rather than a modal, to avoid infinite recursion.
- One modal shell is used per page. Opening a trigger inside an open modal swaps the content rather than stacking a second dialog.

## Changelog

### 1.2.0
- Added a permission check so the create button and the Site Editor link appear only for users who can create template parts.
- Added an error notice when creating a template part fails.
- Fixed triggers added after page load, including triggers inside modal content, so they open their modal.
- Fixed Button triggers with a link URL to open the modal instead of following the link.
- Fixed focus handling: focus moves into the modal on open and returns to the trigger on close.
- Fixed closing the modal so it no longer removes an `inert` attribute set by another script.
- Fixed a template part that contains a trigger for itself so it no longer recurses until the page fails.
- Fixed front-end assets to load only on pages that contain a modal trigger. Added the `modal_templates_force_enqueue_assets` filter to load them everywhere.
- Fixed three-digit hex colours such as `#fff` in the style settings.
- Fixed the missing accessible label on the padding unit select.
- Tested up to WordPress 7.1.

### 1.1.1
- Tested up to WordPress 7.0.
- Updated the bundled Plugin Update Checker to 5.7.
- Removed unused internal code and added PHPUnit, Playwright, and CI. No change to plugin behaviour.

### 1.1.0
- Changed Group triggers to replace nested links with spans so the group markup is valid and the whole group is clickable.
- Added update checks against GitHub releases.

### 1.0.2
- Fixed a layout shift on Windows when the scrollbar disappears as a modal opens.

### 1.0.1
- Fixed the modal on mobile so it centres on screen instead of sitting at the bottom.

### 1.0.0
- Initial release.

## License

GPL-2.0-or-later. See [gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html).
