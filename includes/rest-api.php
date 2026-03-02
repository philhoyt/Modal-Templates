<?php
/**
 * REST API endpoint: render a modal template part in a given post context.
 *
 * GET /wp-json/modal-templates/v1/render?slug={slug}&post_id={id}
 *
 * @package modal-templates
 */

defined( 'ABSPATH' ) || exit;

add_action( 'rest_api_init', 'modal_templates_register_rest_routes' );

/**
 * Register the REST route.
 */
function modal_templates_register_rest_routes(): void {
	register_rest_route(
		'modal-templates/v1',
		'/render',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'modal_templates_rest_render',
			'permission_callback' => '__return_true', // Public endpoint – content is already public.
			'args'                => array(
				'slug'    => array(
					'required'          => true,
					'sanitize_callback' => 'sanitize_title',
				),
				'post_id' => array(
					'default'           => 0,
					'sanitize_callback' => 'absint',
				),
			),
		)
	);
}

/**
 * Render a template part (optionally within a post context) and return its HTML.
 *
 * @param WP_REST_Request $request Incoming REST request.
 * @return WP_REST_Response
 */
function modal_templates_rest_render( WP_REST_Request $request ): WP_REST_Response {
	$slug    = $request->get_param( 'slug' );
	$post_id = (int) $request->get_param( 'post_id' );

	// Optionally set up post context so dynamic blocks (Post Title, etc.) resolve correctly.
	if ( $post_id > 0 ) {
		$post = get_post( $post_id );
		if ( ! $post || ! is_post_publicly_viewable( $post ) ) {
			return new WP_REST_Response( array( 'html' => '' ), 403 );
		}
		setup_postdata( $post );
		$GLOBALS['post'] = $post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}

	ob_start();
	block_template_part( $slug );
	$html = ob_get_clean();

	if ( $post_id > 0 ) {
		wp_reset_postdata();
	}

	return new WP_REST_Response( array( 'html' => $html ), 200 );
}
