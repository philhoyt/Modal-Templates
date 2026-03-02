<?php
/**
 * Register Modal Templates blocks, enqueue assets, and extend core/button + core/group.
 *
 * @package modal-templates
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', 'modal_templates_register_blocks' );
add_action( 'wp_enqueue_scripts', 'modal_templates_enqueue_frontend_assets' );
add_action( 'enqueue_block_editor_assets', 'modal_templates_enqueue_editor_assets' );
add_filter( 'render_block', 'modal_templates_filter_modal_block', 10, 2 );

/**
 * No custom blocks to register — both triggers (button and group) are
 * handled entirely via JS addFilter + the render_block PHP filter below.
 */
function modal_templates_register_blocks(): void {}

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
 * Enqueue the block-editor extensions for core/button and core/group.
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

	wp_localize_script(
		'modal-templates-editor',
		'modalTemplatesData',
		array(
			'adminUrl' => esc_url_raw( admin_url() ),
		)
	);
}

/**
 * Intercept core/button and core/group rendering when a modalSlug is set.
 *
 * Adds data attributes to the trigger element and appends a hidden <template>
 * element containing the pre-rendered modal content. The frontend JS clones
 * that template into the modal shell on click.
 *
 * @param string $block_content Rendered HTML.
 * @param array  $block         Block data array (name, attrs, innerBlocks).
 * @return string
 */
function modal_templates_filter_modal_block( string $block_content, array $block ): string {
	if ( ! in_array( $block['blockName'], array( 'core/button', 'core/group' ), true ) ) {
		return $block_content;
	}

	$modal_slug = sanitize_title( $block['attrs']['modalSlug'] ?? '' );
	if ( ! $modal_slug ) {
		return $block_content;
	}

	$modal_width_raw = $block['attrs']['modalWidth'] ?? 'medium';
	$modal_width     = in_array( $modal_width_raw, array( 'small', 'medium', 'large', 'full' ), true )
		? $modal_width_raw
		: 'medium';

	$content_id = 'mt-tpl-' . wp_unique_id();

	// ------------------------------------------------------------------ //
	// Inject data attributes onto the trigger element                     //
	// ------------------------------------------------------------------ //

	$processor = new WP_HTML_Tag_Processor( $block_content );

	if ( 'core/button' === $block['blockName'] ) {
		// Target the inner link/button element inside the wrapper div.
		while ( $processor->next_tag() ) {
			if ( str_contains( $processor->get_attribute( 'class' ) ?? '', 'wp-block-button__link' ) ) {
				modal_templates_set_trigger_attributes( $processor, $content_id, $modal_width );
				break;
			}
		}
	} else {
		// core/group — target the first (outer wrapper) element.
		$processor->next_tag();
		modal_templates_set_trigger_attributes( $processor, $content_id, $modal_width );
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
		$template_html
	);

	return $modified;
}

/**
 * Set the modal trigger data attributes on a WP_HTML_Tag_Processor tag.
 *
 * @param WP_HTML_Tag_Processor $processor  Tag processor instance, positioned on the target tag.
 * @param string                $content_id Unique ID linking this trigger to its <template>.
 * @param string                $modal_width Modal dialog width variant.
 */
function modal_templates_set_trigger_attributes(
	WP_HTML_Tag_Processor $processor,
	string $content_id,
	string $modal_width
): void {
	$processor->set_attribute( 'data-modal-content-id', $content_id );
	$processor->set_attribute( 'data-modal-width', $modal_width );
	$processor->set_attribute( 'aria-haspopup', 'dialog' );
	$processor->set_attribute( 'aria-expanded', 'false' );
	$processor->set_attribute( 'aria-controls', 'mt-modal-shell' );
	$processor->set_attribute( 'role', 'button' );
	$processor->set_attribute( 'tabindex', '0' );
}
