<?php
/**
 * Plugin Name: Modal Templates
 * Plugin URI:  https://github.com/philhoyt/Modal-Templates
 * Description: Block-based modals driven by template parts. Design modal contents in the Site Editor and attach them to button or card triggers—works inside Query Loops.
 * Version:     1.2.1
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

define( 'MODAL_TEMPLATES_VERSION', '1.2.1' );
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
	$modal_templates_vcs_api        = $modal_templates_update_checker->getVcsApi();
	if ( method_exists( $modal_templates_vcs_api, 'enableReleaseAssets' ) ) {
		// Only a .zip asset counts, and a release without one is skipped. The
		// default PREFER_RELEASE_ASSETS falls back to GitHub's source archive,
		// which installs as <Repo>-<tag>/ and breaks the plugin directory.
		// Without the filter the first asset wins, GitHub lists assets by name,
		// so an asset named ahead of modal-templates.zip would be installed (PCLZIP_ERR_BAD_FORMAT).
		$modal_templates_vcs_api->enableReleaseAssets( '/\.zip$/', $modal_templates_vcs_api::REQUIRE_RELEASE_ASSETS );
	}
}
