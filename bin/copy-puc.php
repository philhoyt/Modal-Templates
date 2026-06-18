<?php
/**
 * Copies Plugin Update Checker from vendor/ into lib/ so it can be
 * included in the distributable plugin zip.
 *
 * Run automatically via Composer post-install-cmd / post-update-cmd,
 * or manually: php bin/copy-puc.php
 *
 * Uses native PHP filesystem calls (no shell_exec) so it runs identically
 * on Windows, macOS, and Linux.
 */

$src = dirname( __DIR__ ) . '/vendor/yahnis-elsts/plugin-update-checker';
$dst = dirname( __DIR__ ) . '/lib/plugin-update-checker';

if ( ! is_dir( $src ) ) {
	echo "Error: PUC not found at $src — run `composer install` first.\n";
	exit( 1 );
}

/**
 * Recursively delete a directory and its contents.
 *
 * @param string $dir Absolute path to remove.
 */
function modal_templates_rrmdir( $dir ) {
	if ( ! is_dir( $dir ) ) {
		return;
	}

	$items = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ),
		RecursiveIteratorIterator::CHILD_FIRST
	);

	foreach ( $items as $item ) {
		if ( $item->isDir() ) {
			rmdir( $item->getPathname() );
		} else {
			unlink( $item->getPathname() );
		}
	}

	rmdir( $dir );
}

/**
 * Recursively copy a directory tree.
 *
 * @param string $src Source directory.
 * @param string $dst Destination directory.
 */
function modal_templates_rcopy( $src, $dst ) {
	mkdir( $dst, 0755, true );

	$items = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $src, FilesystemIterator::SKIP_DOTS ),
		RecursiveIteratorIterator::SELF_FIRST
	);

	foreach ( $items as $item ) {
		$target = $dst . DIRECTORY_SEPARATOR . $items->getSubPathname();
		if ( $item->isDir() ) {
			mkdir( $target, 0755, true );
		} else {
			copy( $item->getPathname(), $target );
		}
	}
}

modal_templates_rrmdir( $dst );
modal_templates_rcopy( $src, $dst );

echo "Plugin Update Checker copied to lib/plugin-update-checker\n";
