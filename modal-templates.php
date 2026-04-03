<?php
/**
 * Plugin Name: Modal Templates
 * Plugin URI:  https://github.com/philhoyt/Modal-Templates
 * Description: Block-based modals driven by template parts. Design modal contents in the Site Editor and attach them to button or card triggers—works inside Query Loops.
 * Version:     1.0.2
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

define( 'MODAL_TEMPLATES_VERSION', '1.0.2' );
define( 'MODAL_TEMPLATES_DIR', plugin_dir_path( __FILE__ ) );
define( 'MODAL_TEMPLATES_URL', plugin_dir_url( __FILE__ ) );

require_once MODAL_TEMPLATES_DIR . 'includes/template-parts.php';
require_once MODAL_TEMPLATES_DIR . 'includes/register-blocks.php';
require_once MODAL_TEMPLATES_DIR . 'includes/settings.php';

if ( file_exists( MODAL_TEMPLATES_DIR . 'lib/plugin-update-checker/plugin-update-checker.php' ) ) {
	require_once MODAL_TEMPLATES_DIR . 'lib/plugin-update-checker/plugin-update-checker.php';

	$modal_templates_update_checker = YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
		'https://github.com/philhoyt/Modal-Templates/',
		__FILE__,
		'modal-templates'
	);
	$modal_templates_update_checker->getVcsApi()->enableReleaseAssets();
}
