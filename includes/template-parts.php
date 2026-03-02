<?php
/**
 * Register the "modal" template part area so users can create
 * Modal Contents template parts in the Site Editor.
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'default_wp_template_part_areas', 'modal_templates_register_area' );

/**
 * Add a "Modal" area to the list of template part areas.
 *
 * @param array $areas Existing template part areas.
 * @return array
 */
function modal_templates_register_area( array $areas ): array {
	$areas[] = [
		'area'        => 'modal',
		'area_tag'    => 'div',
		'label'       => __( 'Modal', 'modal-templates' ),
		'description' => __( 'Template parts used as modal content, selectable from Modal Trigger blocks.', 'modal-templates' ),
		'icon'        => 'layout',
	];

	return $areas;
}
