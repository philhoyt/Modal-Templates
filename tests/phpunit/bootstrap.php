<?php
/**
 * PHPUnit bootstrap file.
 *
 * Loads the WordPress test suite environment when running integration tests.
 * When the test library is not available (e.g. a bare `vendor/bin/phpunit`
 * run outside wp-env), only the isolated unit suite in tests/phpunit/unit/
 * can run, so the WordPress bootstrap is skipped.
 *
 * @package modal-templates
 */

declare(strict_types=1);

$wp_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $wp_tests_dir ) {
	$wp_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $wp_tests_dir . '/includes/functions.php' ) ) {
	// No WordPress test library — nothing to bootstrap.
	return;
}

// Required by the WP Core test bootstrap — points to the installed polyfills library.
define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', dirname( __DIR__, 2 ) . '/vendor/yoast/phpunit-polyfills' );

require_once $wp_tests_dir . '/includes/functions.php';

/**
 * Load the plugin's main file before the test suite runs.
 */
function _manually_load_plugin(): void {
	require dirname( __DIR__, 2 ) . '/modal-templates.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require $wp_tests_dir . '/includes/bootstrap.php';
