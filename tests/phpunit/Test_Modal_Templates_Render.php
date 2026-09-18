<?php
/**
 * Tests for the render_block modal trigger filter in includes/register-blocks.php.
 *
 * @package modal-templates
 */

/**
 * Render filter tests.
 *
 * @covers ::modal_templates_filter_modal_block
 */
class Test_Modal_Templates_Render extends WP_UnitTestCase {

	private const BUTTON_HTML = '<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#">Open</a></div>';

	public function test_non_trigger_block_is_untouched(): void {
		$content = '<p>Hello</p>';
		$block   = array(
			'blockName' => 'core/paragraph',
			'attrs'     => array( 'modalSlug' => 'something' ),
		);
		$this->assertSame( $content, modal_templates_filter_modal_block( $content, $block ) );
	}

	public function test_button_without_modal_slug_is_untouched(): void {
		$block = array(
			'blockName' => 'core/button',
			'attrs'     => array(),
		);
		$this->assertSame(
			self::BUTTON_HTML,
			modal_templates_filter_modal_block( self::BUTTON_HTML, $block )
		);
	}

	public function test_button_with_modal_slug_injects_trigger_attributes(): void {
		$block = array(
			'blockName' => 'core/button',
			'attrs'     => array( 'modalSlug' => 'my-modal' ),
		);

		$output = modal_templates_filter_modal_block( self::BUTTON_HTML, $block );

		$this->assertStringContainsString( 'data-modal-content-id="mt-tpl-', $output );
		$this->assertStringContainsString( 'aria-haspopup="dialog"', $output );
		$this->assertStringContainsString( 'role="button"', $output );
		$this->assertStringContainsString( '<template id="mt-tpl-', $output );
		$this->assertStringContainsString( 'class="mt-modal-prerendered"', $output );
	}

	public function test_invalid_width_defaults_to_medium(): void {
		$block = array(
			'blockName' => 'core/button',
			'attrs'     => array(
				'modalSlug'  => 'my-modal',
				'modalWidth' => 'enormous',
			),
		);

		$output = modal_templates_filter_modal_block( self::BUTTON_HTML, $block );
		$this->assertStringContainsString( 'data-modal-width="medium"', $output );
	}

	public function test_valid_width_is_preserved(): void {
		$block = array(
			'blockName' => 'core/button',
			'attrs'     => array(
				'modalSlug'  => 'my-modal',
				'modalWidth' => 'large',
			),
		);

		$output = modal_templates_filter_modal_block( self::BUTTON_HTML, $block );
		$this->assertStringContainsString( 'data-modal-width="large"', $output );
	}

	public function test_group_trigger_swaps_nested_anchors_to_spans(): void {
		$group_html = '<div class="wp-block-group"><a class="inner" href="/x">Link</a></div>';
		$block      = array(
			'blockName' => 'core/group',
			'attrs'     => array( 'modalSlug' => 'my-modal' ),
		);

		$output = modal_templates_filter_modal_block( $group_html, $block );

		// The outer wrapper becomes the trigger; nested <a> is rewritten to <span>.
		$this->assertStringContainsString( 'role="button"', $output );
		$this->assertStringContainsString( '<span class="inner"', $output );
		$this->assertStringNotContainsString( '<a class="inner"', $output );
	}

	public function test_self_referencing_modal_does_not_recurse(): void {
		$slug = 'mt-self-ref';

		// A modal template part whose content contains a trigger for itself.
		$part_id = self::factory()->post->create(
			array(
				'post_type'    => 'wp_template_part',
				'post_name'    => $slug,
				'post_status'  => 'publish',
				'post_content' => '<!-- wp:button {"modalSlug":"' . $slug . '"} -->'
					. '<div class="wp-block-button"><a class="wp-block-button__link wp-element-button">Again</a></div>'
					. '<!-- /wp:button -->',
			)
		);
		wp_set_post_terms( $part_id, get_stylesheet(), 'wp_theme' );
		wp_set_post_terms( $part_id, 'modal', 'wp_template_part_area' );

		$block  = array(
			'blockName' => 'core/button',
			'attrs'     => array( 'modalSlug' => $slug ),
		);
		$output = modal_templates_filter_modal_block( self::BUTTON_HTML, $block );

		// Outer trigger is pre-rendered once; the nested trigger is left plain.
		$this->assertSame( 1, substr_count( $output, '<template id="mt-tpl-' ) );
		$this->assertStringContainsString( '>Again</a>', $output );
		$this->assertSame( 1, substr_count( $output, 'data-modal-content-id=' ) );
	}

	public function test_frontend_assets_enqueue_only_when_a_trigger_renders(): void {
		wp_dequeue_style( 'modal-templates-frontend' );
		wp_dequeue_script( 'modal-templates-frontend' );

		modal_templates_filter_modal_block(
			self::BUTTON_HTML,
			array(
				'blockName' => 'core/button',
				'attrs'     => array(),
			)
		);
		$this->assertFalse( wp_style_is( 'modal-templates-frontend', 'enqueued' ) );
		$this->assertFalse( wp_script_is( 'modal-templates-frontend', 'enqueued' ) );

		modal_templates_filter_modal_block(
			self::BUTTON_HTML,
			array(
				'blockName' => 'core/button',
				'attrs'     => array( 'modalSlug' => 'my-modal' ),
			)
		);
		$this->assertTrue( wp_style_is( 'modal-templates-frontend', 'enqueued' ) );
		$this->assertTrue( wp_script_is( 'modal-templates-frontend', 'enqueued' ) );
	}
}
