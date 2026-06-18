<?php
/**
 * Tests for the settings sanitizer and CSS helpers in includes/settings.php.
 *
 * @package modal-templates
 */

/**
 * @covers ::modal_templates_sanitize_styles
 * @covers ::modal_templates_hex_to_rgba
 */
class Test_Modal_Templates_Settings extends WP_UnitTestCase {

	public function test_non_array_input_returns_defaults(): void {
		$defaults = modal_templates_style_defaults();
		$this->assertSame( $defaults, modal_templates_sanitize_styles( 'not-an-array' ) );
		$this->assertSame( $defaults, modal_templates_sanitize_styles( null ) );
	}

	public function test_opacity_is_clamped_to_0_100(): void {
		$over  = modal_templates_sanitize_styles( array( 'backdrop_opacity' => 500 ) );
		$under = modal_templates_sanitize_styles( array( 'backdrop_opacity' => -20 ) );
		$this->assertSame( 100, $over['backdrop_opacity'] );
		$this->assertSame( 0, $under['backdrop_opacity'] );
	}

	public function test_custom_width_is_clamped_to_range(): void {
		$over  = modal_templates_sanitize_styles( array( 'dialog_custom_width' => 99999 ) );
		$under = modal_templates_sanitize_styles( array( 'dialog_custom_width' => 1 ) );
		$this->assertSame( 3000, $over['dialog_custom_width'] );
		$this->assertSame( 200, $under['dialog_custom_width'] );
	}

	public function test_invalid_hex_falls_back_to_default(): void {
		$defaults = modal_templates_style_defaults();
		$output   = modal_templates_sanitize_styles( array( 'backdrop_color' => 'javascript:alert(1)' ) );
		$this->assertSame( $defaults['backdrop_color'], $output['backdrop_color'] );
	}

	public function test_valid_hex_is_preserved(): void {
		$output = modal_templates_sanitize_styles( array( 'dialog_bg' => '#abcdef' ) );
		$this->assertSame( '#abcdef', $output['dialog_bg'] );
	}

	public function test_invalid_padding_unit_falls_back_to_rem(): void {
		$output = modal_templates_sanitize_styles( array( 'dialog_padding_unit' => 'parsecs' ) );
		$this->assertSame( 'rem', $output['dialog_padding_unit'] );

		$valid = modal_templates_sanitize_styles( array( 'dialog_padding_unit' => 'px' ) );
		$this->assertSame( 'px', $valid['dialog_padding_unit'] );
	}

	public function test_hex_to_rgba_conversion(): void {
		$this->assertSame( 'rgba(0, 0, 0, 0.60)', modal_templates_hex_to_rgba( '#000000', 60 ) );
		$this->assertSame( 'rgba(255, 255, 255, 1.00)', modal_templates_hex_to_rgba( '#ffffff', 100 ) );
		$this->assertSame( 'rgba(17, 34, 51, 0.00)', modal_templates_hex_to_rgba( '#112233', 0 ) );
	}
}
