<?php
/**
 * PHPStan bootstrap.
 *
 * Declares the plugin constants that are define()d at runtime in
 * modal-templates.php. The included files (includes/*.php) are analysed as
 * standalone units, so PHPStan does not see the constants from the require
 * chain. Declaring them here lets static analysis resolve them with the right
 * types. This file is for analysis only — it is not loaded by WordPress and is
 * excluded from the distributable zip.
 *
 * @package modal-templates
 */

// Dynamic (non-literal) string values so PHPStan infers the `string` type
// without resolving constant-folded paths — e.g. require( MODAL_TEMPLATES_DIR
// . '...' ) would otherwise be checked against a fake literal path.
define( 'MODAL_TEMPLATES_VERSION', (string) getenv( 'MODAL_TEMPLATES_VERSION' ) );
define( 'MODAL_TEMPLATES_DIR', (string) getenv( 'MODAL_TEMPLATES_DIR' ) );
define( 'MODAL_TEMPLATES_URL', (string) getenv( 'MODAL_TEMPLATES_URL' ) );
