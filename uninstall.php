<?php
/**
 * Modal Templates — Uninstall
 *
 * Runs automatically when the plugin is deleted via the WordPress admin.
 * Removes all options created by the plugin.
 *
 * @package modal-templates
 */

// Bail if not called by WordPress during plugin deletion.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'modal_templates_styles' );
