<?php
/**
 * Plugin Name: Modal Templates
 * Plugin URI:  https://github.com/philhoyt/Modal-Templates
 * Description: Block-based modals driven by template parts. Design modal contents in the Site Editor and attach them to button or card triggers—works inside Query Loops.
 * Version:     1.0.0
 * Author:      philhoyt
 * Author URI:  https://philhoyt.com
 * License:     GPL-2.0-or-later
 * Text Domain: modal-templates
 * Requires at least: 6.4
 * Requires PHP: 8.1
 *
 * @package modal-templates
 */

defined( 'ABSPATH' ) || exit;

define( 'MODAL_TEMPLATES_VERSION', '1.0.0' );
define( 'MODAL_TEMPLATES_DIR', plugin_dir_path( __FILE__ ) );
define( 'MODAL_TEMPLATES_URL', plugin_dir_url( __FILE__ ) );

add_action( 'init', 'modal_templates_load_textdomain' );

/**
 * Load the plugin text domain for translations.
 */
function modal_templates_load_textdomain(): void {
	load_plugin_textdomain(
		'modal-templates',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages'
	);
}

require_once MODAL_TEMPLATES_DIR . 'includes/template-parts.php';
require_once MODAL_TEMPLATES_DIR . 'includes/register-blocks.php';
require_once MODAL_TEMPLATES_DIR . 'includes/settings.php';
