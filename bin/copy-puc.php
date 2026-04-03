<?php
/**
 * Copies Plugin Update Checker from vendor/ into lib/ so it can be
 * included in the distributable plugin zip.
 *
 * Run automatically via Composer post-install-cmd / post-update-cmd,
 * or manually: php bin/copy-puc.php
 */

$src = dirname( __DIR__ ) . '/vendor/yahnis-elsts/plugin-update-checker';
$dst = dirname( __DIR__ ) . '/lib/plugin-update-checker';

if ( ! is_dir( $src ) ) {
	echo "Error: PUC not found at $src — run `composer install` first.\n";
	exit( 1 );
}

if ( is_dir( $dst ) ) {
	shell_exec( 'rm -rf ' . escapeshellarg( $dst ) );
}

shell_exec( 'cp -r ' . escapeshellarg( $src ) . ' ' . escapeshellarg( $dst ) );

echo "Plugin Update Checker copied to lib/plugin-update-checker\n";
