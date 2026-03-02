<?php
/**
 * Register Modal Templates blocks, enqueue assets, and extend core/button.
 *
 * @package modal-templates
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', 'modal_templates_register_blocks' );
add_action( 'wp_enqueue_scripts', 'modal_templates_enqueue_frontend_assets' );
add_action( 'enqueue_block_editor_assets', 'modal_templates_enqueue_editor_assets' );
add_filter( 'render_block', 'modal_templates_filter_core_button', 10, 2 );

/**
 * Register the Modal Trigger Wrapper block.
 * The core/button extension is handled entirely in JS (addFilter) + the
 * render_block filter below — no block.json registration needed for it.
 */
function modal_templates_register_blocks(): void {
	register_block_type( MODAL_TEMPLATES_DIR . 'build/modal-trigger-wrapper' );
}

/**
 * Enqueue the frontend modal stylesheet and script.
 */
function modal_templates_enqueue_frontend_assets(): void {
	wp_enqueue_style(
		'modal-templates-frontend',
		MODAL_TEMPLATES_URL . 'assets/css/modal-frontend.css',
		array(),
		MODAL_TEMPLATES_VERSION
	);

	wp_enqueue_script(
		'modal-templates-frontend',
		MODAL_TEMPLATES_URL . 'assets/js/modal-frontend.js',
		array(),
		MODAL_TEMPLATES_VERSION,
		true
	);
}

/**
 * Enqueue the block-editor extension for core/button.
 * This registers the JS filters that add modalSlug/modalWidth attributes
 * and inject the "Modal" inspector panel into core/button.
 */
function modal_templates_enqueue_editor_assets(): void {
	$asset_file = require MODAL_TEMPLATES_DIR . 'build/modal-button/index.asset.php';

	wp_enqueue_script(
		'modal-templates-editor',
		MODAL_TEMPLATES_URL . 'build/modal-button/index.js',
		$asset_file['dependencies'],
		$asset_file['version'],
		true
	);

	// Provide admin URL (mirrors Ollie's menuDesignerData pattern) so the
	// TemplateSelector can build Site Editor links without guessing.
	wp_localize_script(
		'modal-templates-editor',
		'modalTemplatesData',
		array(
			'adminUrl' => esc_url_raw( admin_url() ),
		)
	);
}

/**
 * Intercept core/button rendering when a modalSlug is set.
 *
 * Adds data attributes to the inner <a>/<button> element and appends a
 * hidden <template> element containing the pre-rendered modal content.
 * The frontend JS clones that template into the modal shell on click.
 *
 * Uses WP_HTML_Tag_Processor for safe, selector-based attribute injection.
 *
 * @param string $block_content Rendered HTML.
 * @param array  $block         Block data array (name, attrs, innerBlocks).
 * @return string
 */
function modal_templates_filter_core_button( string $block_content, array $block ): string {
	if ( 'core/button' !== $block['blockName'] ) {
		return $block_content;
	}

	$modal_slug = sanitize_title( $block['attrs']['modalSlug'] ?? '' );
	if ( ! $modal_slug ) {
		return $block_content;
	}

	$modal_width = in_array( $block['attrs']['modalWidth'] ?? 'medium', array( 'small', 'medium', 'large', 'full' ), true )
		? $block['attrs']['modalWidth']
		: 'medium';

	$content_id = 'mt-tpl-' . wp_unique_id();

	// ------------------------------------------------------------------ //
	// Add data attributes to the inner link/button                        //
	// ------------------------------------------------------------------ //

	$processor = new WP_HTML_Tag_Processor( $block_content );

	while ( $processor->next_tag() ) {
		$class = $processor->get_attribute( 'class' ) ?? '';
		if ( str_contains( $class, 'wp-block-button__link' ) ) {
			$processor->set_attribute( 'data-modal-content-id', $content_id );
			$processor->set_attribute( 'data-modal-width', $modal_width );
			$processor->set_attribute( 'aria-haspopup', 'dialog' );
			// Suppress default link navigation — JS handles the click.
			$processor->set_attribute( 'role', 'button' );
			break;
		}
	}

	$modified = $processor->get_updated_html();

	// ------------------------------------------------------------------ //
	// Pre-render modal content into a <template> element                  //
	// ------------------------------------------------------------------ //

	ob_start();
	block_template_part( $modal_slug );
	$template_html = ob_get_clean();

	$modified .= sprintf(
		'<template id="%s" class="mt-modal-prerendered">%s</template>',
		esc_attr( $content_id ),
		$template_html // Already processed by do_blocks inside block_template_part.
	);

	return $modified;
}
