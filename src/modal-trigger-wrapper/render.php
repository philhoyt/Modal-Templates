<?php
/**
 * Render callback for the Modal Trigger Wrapper (card) block.
 *
 * Exposed variables:
 *   $attributes (array)  — block attributes
 *   $content    (string) — rendered inner blocks HTML
 *   $block      (WP_Block) — block instance
 *
 * The entire wrapper div becomes a clickable trigger. Inner blocks
 * (image, heading, paragraph, etc.) are rendered as normal — they are
 * the visible card content. A hidden <template> element alongside the
 * wrapper holds the pre-rendered modal content.
 *
 * When used inside a Query Loop the render runs once per post, so each
 * card gets its own pre-rendered modal content from the same shared
 * template part, filled with that post's data.
 */

defined( 'ABSPATH' ) || exit;

$modal_slug  = sanitize_title( $attributes['modalSlug'] ?? '' );
$modal_width = in_array( $attributes['width'] ?? 'medium', [ 'small', 'medium', 'large', 'full' ], true )
	? $attributes['width']
	: 'medium';

// No modal assigned — render inner content without trigger behaviour.
if ( ! $modal_slug ) {
	echo '<div class="wp-block-modal-templates-modal-trigger-wrapper">';
	echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — pre-processed block output.
	echo '</div>';
	return;
}

$content_id = 'mt-tpl-' . wp_unique_id();

$wrapper_attributes = get_block_wrapper_attributes(
	[
		'class'                 => 'wp-block-modal-templates-modal-trigger-wrapper modal-trigger-wrapper--active',
		'data-modal-content-id' => $content_id,
		'data-modal-width'      => $modal_width,
		'role'                  => 'button',
		'tabindex'              => '0',
		'aria-haspopup'         => 'dialog',
	]
);
?>

<div <?php echo wp_kses_post( $wrapper_attributes ); ?>>
	<?php
	echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — pre-processed block output.
	?>
</div>

<template id="<?php echo esc_attr( $content_id ); ?>" class="mt-modal-prerendered">
	<?php block_template_part( $modal_slug ); ?>
</template>
