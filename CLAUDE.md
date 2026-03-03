# Modal Templates

Block-based modal plugin for WordPress. Modals are designed as template parts in the Site Editor and attached to core/button or core/group triggers. Works inside Query Loops via inline pre-rendering.

- **Plugin**: `modal-templates` | v1.0.0 | Requires WP 6.4+, PHP 8.1+
- **Text domain**: `modal-templates`
- **GitHub**: https://github.com/philhoyt/Modal-Templates

## Architecture

- **No custom block** — extends `core/button` and `core/group` via `addFilter` with `modalSlug` / `modalWidth` attributes and a "Modal" inspector panel
- **Template parts** — modal content is authored in the Site Editor; the "modal" area is registered via `default_wp_template_part_areas`
- **Inline pre-rendering** — `render_block` PHP filter injects `<template id="mt-tpl-{slug}">` next to the trigger so no AJAX is needed at open time
- **Frontend JS** — clones `<template>` content into a modal shell on click; handles focus trap, ARIA, ESC/backdrop close, iOS scroll lock
- **Loop support** — pre-rendering runs inside Query Loop post context, so dynamic blocks resolve correctly per post
- **Settings page** — Settings > Modal Templates lets editors customize backdrop, dialog colors/radius/padding via CSS custom properties

## Key Files

| File | Purpose |
|------|---------|
| `modal-templates.php` | Plugin entry: constants, requires |
| `includes/register-blocks.php` | Enqueues assets; `render_block` filter injects data attrs + `<template>` |
| `includes/template-parts.php` | Registers "Modal" template part area |
| `includes/settings.php` | Admin settings page + CSS custom property output |
| `includes/rest-api.php` | REST endpoint `/wp-json/modal-templates/v1/render` (dynamic re-render) |
| `src/modal-button/index.js` | Editor extensions for core/button and core/group |
| `src/shared/TemplateSelector.js` | ComboboxControl for choosing/creating modal template parts |
| `assets/js/modal-frontend.js` | Open/close, focus trap, public API (`window.ModalTemplates`) |
| `assets/css/modal-frontend.css` | Modal shell styles, width variants, animations, BEM classes |
| `webpack.config.js` | Extends `@wordpress/scripts` default; adds `modal-button/index` entry |

## Build

```bash
npm install
npm run build      # production build
npm start          # development watch
npm run lint       # JS + CSS + PHP linters
npm run plugin-zip # create distributable .zip
```

Build output goes to `build/modal-button/` (editor JS bundle + `index.asset.php`).

## Coding Standards

Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/) throughout.

### PHP
- [WordPress PHP Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/)
- Tabs for indentation
- Yoda conditions
- Space before opening parenthesis on control structures (`if ( $foo )`, not `if($foo)`)
- Snake_case for functions and variables, prefixed with `modal_templates_`
- Run `npm run lint:php` to check; config in `phpcs.xml.dist`

### JavaScript
- [WordPress JavaScript Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/javascript/)
- Single quotes
- Tabs for indentation
- Use `@wordpress/*` packages — no third-party equivalents (e.g. use `@wordpress/element` not `react` directly)

### CSS
- [WordPress CSS Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/css/)
- BEM naming for plugin-specific classes: `.mt-modal__backdrop`, `.mt-modal__dialog`, `.mt-modal__content`
- CSS custom properties for design tokens (set in settings.php, consumed in modal-frontend.css)
