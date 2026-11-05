<?php
/**
 * Register Modal Templates blocks, enqueue assets, and extend core/button + core/group.
 *
 * @package modal-templates
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', 'modal_templates_register_blocks' );
add_action( 'init', 'modal_templates_register_frontend_assets' );
add_action( 'wp_enqueue_scripts', 'modal_templates_maybe_force_enqueue_frontend_assets' );
add_action( 'enqueue_block_editor_assets', 'modal_templates_enqueue_editor_assets' );
add_filter( 'render_block', 'modal_templates_filter_modal_block', 10, 2 );

/**
 * No custom blocks to register — both triggers (button and group) are
 * handled entirely via JS addFilter + the render_block PHP filter below.
 */
function modal_templates_register_blocks(): void {}

/**
 * Register the frontend modal stylesheet and script.
 *
 * Registered early (init) so the handles exist for wp_add_inline_style() in
 * settings.php; they are only enqueued when a trigger actually renders
 * (see modal_templates_enqueue_frontend_assets()).
 */
function modal_templates_register_frontend_assets(): void {
	wp_register_style(
		'modal-templates-frontend',
		MODAL_TEMPLATES_URL . 'assets/css/modal-frontend.css',
		array(),
		MODAL_TEMPLATES_VERSION
	);

	wp_register_script(
		'modal-templates-frontend',
		MODAL_TEMPLATES_URL . 'assets/js/modal-frontend.js',
		array(),
		MODAL_TEMPLATES_VERSION,
		true
	);

	wp_add_inline_script(
		'modal-templates-frontend',
		'window.modalTemplatesL10n = ' . wp_json_encode(
			array(
				'dialogLabel' => __( 'Modal', 'modal-templates' ),
				'closeLabel'  => __( 'Close modal', 'modal-templates' ),
			)
		) . ';',
		'before'
	);
}

/**
 * Enqueue the frontend modal stylesheet and script.
 *
 * Called from the render_block filter the first time a trigger renders, so
 * pages without a modal trigger load nothing. In block themes the template is
 * rendered before wp_head(), so the stylesheet still lands in <head>.
 */
function modal_templates_enqueue_frontend_assets(): void {
	wp_enqueue_style( 'modal-templates-frontend' );
	wp_enqueue_script( 'modal-templates-frontend' );
}

/**
 * Optionally load the frontend assets on every page.
 *
 * Useful when triggers are output by code that bypasses render_block, or
 * when window.ModalTemplates is driven programmatically on pages with no
 * trigger block.
 */
function modal_templates_maybe_force_enqueue_frontend_assets(): void {
	/**
	 * Filter whether the modal assets load on every frontend page.
	 *
	 * @param bool $force Default false — assets load only when a trigger renders.
	 */
	if ( apply_filters( 'modal_templates_force_enqueue_assets', false ) ) {
		modal_templates_enqueue_frontend_assets();
	}
}

/**
 * Enqueue the block-editor extensions for core/button and core/group.
 */
function modal_templates_enqueue_editor_assets(): void {
	$asset_path = MODAL_TEMPLATES_DIR . 'build/modal-button/index.asset.php';
	if ( ! file_exists( $asset_path ) ) {
		// Built assets are missing (fresh clone before `npm run build`) — skip
		// rather than fatal in every editor screen.
		return;
	}
	$asset_file = require $asset_path;

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

	wp_set_script_translations(
		'modal-templates-editor',
		'modal-templates',
		MODAL_TEMPLATES_DIR . 'languages'
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
	// Slugs currently being pre-rendered further up the call stack.
	static $rendering = array();

	if ( ! in_array( $block['blockName'], array( 'core/button', 'core/group' ), true ) ) {
		return $block_content;
	}

	$modal_slug = sanitize_title( $block['attrs']['modalSlug'] ?? '' );
	if ( ! $modal_slug ) {
		return $block_content;
	}

	// A trigger inside a modal that points back at that same modal (directly
	// or via a cycle) would recurse forever — leave it as a plain block.
	if ( isset( $rendering[ $modal_slug ] ) ) {
		return $block_content;
	}

	modal_templates_enqueue_frontend_assets();

	$modal_width_raw = $block['attrs']['modalWidth'] ?? 'medium';
	$modal_width     = in_array( $modal_width_raw, array( 'small', 'medium', 'large', 'full', 'custom' ), true )
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

	if ( 'core/group' === $block['blockName'] ) {
		$modified = modal_templates_strip_nested_anchors( $modified );
	}

	// ------------------------------------------------------------------ //
	// Pre-render modal content into a <template> element                  //
	// ------------------------------------------------------------------ //

	$rendering[ $modal_slug ] = true;
	ob_start();
	try {
		block_template_part( $modal_slug );
	} finally {
		$template_html = ob_get_clean();
		unset( $rendering[ $modal_slug ] );
	}

	$modified .= sprintf(
		'<template id="%s" class="mt-modal-prerendered">%s</template>',
		esc_attr( $content_id ),
		$template_html
	);

	return $modified;
}

/**
 * Replace all <a> elements inside a group trigger with <span> elements.
 *
 * When a core/group block acts as a modal trigger it receives role="button",
 * making any nested <a> tags invalid HTML. Swapping them to <span> preserves
 * visual styling (classes/styles carry over) while producing valid markup.
 *
 * @param string $html Rendered group block HTML.
 * @return string
 */
function modal_templates_strip_nested_anchors( string $html ): string {
	$html = preg_replace( '/<a\b([^>]*)>/i', '<span$1>', $html );
	$html = preg_replace( '/<\/a>/i', '</span>', $html );
	return $html;
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
