<?php
/**
 * Tests for the render_block modal trigger filter in includes/register-blocks.php.
 *
 * @package modal-templates
 */

/**
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
}
