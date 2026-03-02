<?php
/**
 * Modal Templates — Settings Page
 *
 * Registers a Settings > Modal Templates admin page that lets site editors
 * customise the modal appearance without writing code. Values are stored as
 * a single wp_options entry and output as CSS custom-property overrides on
 * the frontend, after the plugin stylesheet, so theme CSS can still win.
 *
 * Developers can also override any custom property directly in theme CSS:
 *
 *   :root {
 *     --mt-dialog-radius: 0;
 *     --mt-dialog-padding: 1.5rem;
 *   }
 *
 * @package modal-templates
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', 'modal_templates_add_settings_page' );
add_action( 'admin_init', 'modal_templates_register_settings' );
add_action( 'admin_enqueue_scripts', 'modal_templates_enqueue_settings_assets' );
add_action( 'wp_enqueue_scripts', 'modal_templates_output_custom_styles', 20 );
add_filter( 'plugin_action_links_' . plugin_basename( MODAL_TEMPLATES_DIR . 'modal-templates.php' ), 'modal_templates_plugin_action_links' );

// ------------------------------------------------------------------ //
// Defaults                                                           //
// ------------------------------------------------------------------ //

/**
 * Return the default style values.
 *
 * @return array<string,int|string>
 */
function modal_templates_style_defaults(): array {
	return array(
		'backdrop_color'       => '#000000',
		'backdrop_opacity'     => 60,
		'dialog_bg'            => '#ffffff',
		'dialog_radius'        => 8,
		'dialog_padding_value' => 2,
		'dialog_padding_unit'  => 'rem',
		'dialog_custom_width'  => 800,
		'close_color'          => '#555555',
	);
}

// ------------------------------------------------------------------ //
// Admin menu                                                         //
// ------------------------------------------------------------------ //

/**
 * Add a Settings link to the plugin's entry on the Plugins screen.
 *
 * @param array<int|string,string> $links Existing action links.
 * @return array<int|string,string>
 */
function modal_templates_plugin_action_links( array $links ): array {
	$settings_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=modal-templates' ) ) . '">'
		. esc_html__( 'Settings', 'modal-templates' )
		. '</a>';
	array_unshift( $links, $settings_link );
	return $links;
}

/**
 * Register the settings page under Settings > Modal Templates.
 */
function modal_templates_add_settings_page(): void {
	add_options_page(
		__( 'Modal Templates', 'modal-templates' ),
		__( 'Modal Templates', 'modal-templates' ),
		'manage_options',
		'modal-templates',
		'modal_templates_render_settings_page'
	);
}

// ------------------------------------------------------------------ //
// Settings registration + sanitization                               //
// ------------------------------------------------------------------ //

/**
 * Register the single option that holds all style settings.
 */
function modal_templates_register_settings(): void {
	register_setting(
		'modal_templates_styles_group',
		'modal_templates_styles',
		array(
			'sanitize_callback' => 'modal_templates_sanitize_styles',
			'default'           => modal_templates_style_defaults(),
		)
	);
}

/**
 * Sanitize and validate style settings on save.
 *
 * @param mixed $input Raw POST data.
 * @return array<string,int|string> Sanitized values merged over defaults.
 */
function modal_templates_sanitize_styles( $input ): array {
	$defaults = modal_templates_style_defaults();

	if ( ! is_array( $input ) ) {
		return $defaults;
	}

	$output = array();

	// Hex colour fields.
	foreach ( array( 'backdrop_color', 'dialog_bg', 'close_color' ) as $field ) {
		$val              = sanitize_hex_color( $input[ $field ] ?? '' );
		$output[ $field ] = $val ?? $defaults[ $field ];
	}

	// Integer range fields.
	$output['backdrop_opacity']    = max( 0, min( 100, (int) ( $input['backdrop_opacity'] ?? $defaults['backdrop_opacity'] ) ) );
	$output['dialog_radius']       = max( 0, min( 100, (int) ( $input['dialog_radius'] ?? $defaults['dialog_radius'] ) ) );
	$output['dialog_custom_width'] = max( 200, min( 3000, (int) ( $input['dialog_custom_width'] ?? $defaults['dialog_custom_width'] ) ) );

	// Padding value (float) + unit (rem or px).
	$output['dialog_padding_value'] = max( 0.0, min( 200.0, (float) ( $input['dialog_padding_value'] ?? $defaults['dialog_padding_value'] ) ) );
	$output['dialog_padding_unit']  = in_array( $input['dialog_padding_unit'] ?? '', array( 'rem', 'px' ), true )
		? $input['dialog_padding_unit']
		: $defaults['dialog_padding_unit'];

	return $output;
}

// ------------------------------------------------------------------ //
// Frontend inline CSS output                                         //
// ------------------------------------------------------------------ //

/**
 * Convert a hex colour and integer opacity percentage to an rgba() string.
 *
 * @param string $hex     Six-digit hex colour (with leading #).
 * @param int    $opacity 0–100 integer percentage.
 * @return string e.g. "rgba(0, 0, 0, 0.60)".
 */
function modal_templates_hex_to_rgba( string $hex, int $opacity ): string {
	$hex = ltrim( $hex, '#' );
	$r   = hexdec( substr( $hex, 0, 2 ) );
	$g   = hexdec( substr( $hex, 2, 2 ) );
	$b   = hexdec( substr( $hex, 4, 2 ) );

	return sprintf( 'rgba(%d, %d, %d, %.2f)', $r, $g, $b, $opacity / 100 );
}

/**
 * Output saved style values as CSS custom-property overrides.
 *
 * Appended as inline CSS after the plugin stylesheet so theme CSS
 * (loaded later in the page) can still override individual properties.
 */
function modal_templates_output_custom_styles(): void {
	$options = get_option( 'modal_templates_styles', array() );
	$raw     = wp_parse_args( $options, modal_templates_style_defaults() );

	// Re-validate before writing into CSS.
	$backdrop_color       = sanitize_hex_color( (string) $raw['backdrop_color'] ) ?? '#000000';
	$backdrop_opacity     = max( 0, min( 100, (int) $raw['backdrop_opacity'] ) );
	$dialog_bg            = sanitize_hex_color( (string) $raw['dialog_bg'] ) ?? '#ffffff';
	$dialog_radius        = max( 0, min( 100, (int) $raw['dialog_radius'] ) );
	$dialog_padding_value = max( 0.0, min( 200.0, (float) $raw['dialog_padding_value'] ) );
	$dialog_padding_unit  = in_array( (string) $raw['dialog_padding_unit'], array( 'rem', 'px' ), true )
		? (string) $raw['dialog_padding_unit']
		: 'rem';
	$dialog_custom_width  = max( 200, min( 3000, (int) $raw['dialog_custom_width'] ) );
	$close_color          = sanitize_hex_color( (string) $raw['close_color'] ) ?? '#555555';

	$backdrop_rgba     = modal_templates_hex_to_rgba( $backdrop_color, $backdrop_opacity );
	$padding_formatted = rtrim( rtrim( sprintf( '%.2f', $dialog_padding_value ), '0' ), '.' );

	$css  = ":root {\n";
	$css .= "\t--mt-backdrop-color: {$backdrop_rgba};\n";
	$css .= "\t--mt-dialog-bg: {$dialog_bg};\n";
	$css .= "\t--mt-dialog-radius: {$dialog_radius}px;\n";
	$css .= "\t--mt-dialog-padding: {$padding_formatted}{$dialog_padding_unit};\n";
	$css .= "\t--mt-dialog-width-custom: {$dialog_custom_width}px;\n";
	$css .= "\t--mt-close-color: {$close_color};\n";
	$css .= '}';

	wp_add_inline_style( 'modal-templates-frontend', $css );
}

// ------------------------------------------------------------------ //
// Admin assets                                                       //
// ------------------------------------------------------------------ //

/**
 * Enqueue the colour-picker and range-display script on the settings page only.
 *
 * @param string $hook Current admin page hook suffix.
 */
function modal_templates_enqueue_settings_assets( string $hook ): void {
	if ( 'settings_page_modal-templates' !== $hook ) {
		return;
	}

	wp_enqueue_style( 'wp-color-picker' );
	wp_enqueue_script( 'wp-color-picker' );

	wp_add_inline_script(
		'wp-color-picker',
		'jQuery( function( $ ) {
			$( ".mt-color-field" ).wpColorPicker();
			var $range   = $( "#mt-backdrop-opacity" );
			var $display = $( "#mt-backdrop-opacity-val" );
			$range.on( "input", function() {
				$display.text( this.value + "%" );
			} );
		} );'
	);
}

// ------------------------------------------------------------------ //
// Settings page render                                               //
// ------------------------------------------------------------------ //

/**
 * Render the Settings > Modal Templates admin page.
 */
function modal_templates_render_settings_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$options = get_option( 'modal_templates_styles', array() );
	$styles  = wp_parse_args( $options, modal_templates_style_defaults() );

	$backdrop_color       = sanitize_hex_color( (string) $styles['backdrop_color'] ) ?? '#000000';
	$backdrop_opacity     = max( 0, min( 100, (int) $styles['backdrop_opacity'] ) );
	$dialog_bg            = sanitize_hex_color( (string) $styles['dialog_bg'] ) ?? '#ffffff';
	$dialog_radius        = max( 0, min( 100, (int) $styles['dialog_radius'] ) );
	$dialog_padding_value = max( 0.0, min( 200.0, (float) $styles['dialog_padding_value'] ) );
	$dialog_padding_unit  = in_array( (string) $styles['dialog_padding_unit'], array( 'rem', 'px' ), true )
		? (string) $styles['dialog_padding_unit']
		: 'rem';
	$dialog_custom_width  = max( 200, min( 3000, (int) $styles['dialog_custom_width'] ) );
	$close_color          = sanitize_hex_color( (string) $styles['close_color'] ) ?? '#555555';
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Modal Templates', 'modal-templates' ); ?></h1>
		<p>
			<?php esc_html_e( 'Customise the modal appearance. Developers can also override any value directly in theme CSS using the custom property name shown beneath each field.', 'modal-templates' ); ?>
		</p>

		<form method="post" action="options.php">
			<?php settings_fields( 'modal_templates_styles_group' ); ?>

			<h2><?php esc_html_e( 'Backdrop', 'modal-templates' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<label for="mt-backdrop-color">
							<?php esc_html_e( 'Colour', 'modal-templates' ); ?>
						</label>
					</th>
					<td>
						<input
							type="text"
							id="mt-backdrop-color"
							name="modal_templates_styles[backdrop_color]"
							value="<?php echo esc_attr( $backdrop_color ); ?>"
							class="mt-color-field"
							data-default-color="#000000"
						>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="mt-backdrop-opacity">
							<?php esc_html_e( 'Opacity', 'modal-templates' ); ?>
						</label>
					</th>
					<td>
						<input
							type="range"
							id="mt-backdrop-opacity"
							name="modal_templates_styles[backdrop_opacity]"
							value="<?php echo esc_attr( (string) $backdrop_opacity ); ?>"
							min="0"
							max="100"
							step="1"
							style="width:200px;vertical-align:middle;"
						>
						<span id="mt-backdrop-opacity-val">
							<?php echo esc_html( $backdrop_opacity . '%' ); ?>
						</span>
						<p class="description">
							<?php esc_html_e( 'Colour + opacity combine to form', 'modal-templates' ); ?>
							<code>--mt-backdrop-color</code>
						</p>
					</td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'Dialog', 'modal-templates' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<label for="mt-dialog-bg">
							<?php esc_html_e( 'Background colour', 'modal-templates' ); ?>
						</label>
					</th>
					<td>
						<input
							type="text"
							id="mt-dialog-bg"
							name="modal_templates_styles[dialog_bg]"
							value="<?php echo esc_attr( $dialog_bg ); ?>"
							class="mt-color-field"
							data-default-color="#ffffff"
						>
						<p class="description"><code>--mt-dialog-bg</code></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="mt-dialog-radius">
							<?php esc_html_e( 'Border radius', 'modal-templates' ); ?>
						</label>
					</th>
					<td>
						<input
							type="number"
							id="mt-dialog-radius"
							name="modal_templates_styles[dialog_radius]"
							value="<?php echo esc_attr( (string) $dialog_radius ); ?>"
							min="0"
							max="100"
							step="1"
							class="small-text"
						> px
						<p class="description"><code>--mt-dialog-radius</code></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="mt-dialog-padding-value">
							<?php esc_html_e( 'Content padding', 'modal-templates' ); ?>
						</label>
					</th>
					<td>
						<input
							type="number"
							id="mt-dialog-padding-value"
							name="modal_templates_styles[dialog_padding_value]"
							value="<?php echo esc_attr( (string) $dialog_padding_value ); ?>"
							min="0"
							max="200"
							step="0.5"
							class="small-text"
						>
						<select name="modal_templates_styles[dialog_padding_unit]">
							<option value="rem" <?php selected( $dialog_padding_unit, 'rem' ); ?>>rem</option>
							<option value="px" <?php selected( $dialog_padding_unit, 'px' ); ?>>px</option>
						</select>
						<p class="description"><code>--mt-dialog-padding</code></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="mt-dialog-custom-width">
							<?php esc_html_e( 'Custom width', 'modal-templates' ); ?>
						</label>
					</th>
					<td>
						<input
							type="number"
							id="mt-dialog-custom-width"
							name="modal_templates_styles[dialog_custom_width]"
							value="<?php echo esc_attr( (string) $dialog_custom_width ); ?>"
							min="200"
							max="3000"
							step="1"
							class="small-text"
						> px
						<p class="description">
							<?php esc_html_e( 'Used when "Custom (set in Settings)" is selected as the modal width in the block editor.', 'modal-templates' ); ?>
							<code>--mt-dialog-width-custom</code>
						</p>
					</td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'Close button', 'modal-templates' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<label for="mt-close-color">
							<?php esc_html_e( 'Icon colour', 'modal-templates' ); ?>
						</label>
					</th>
					<td>
						<input
							type="text"
							id="mt-close-color"
							name="modal_templates_styles[close_color]"
							value="<?php echo esc_attr( $close_color ); ?>"
							class="mt-color-field"
							data-default-color="#555555"
						>
						<p class="description"><code>--mt-close-color</code></p>
					</td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'Developer-only properties', 'modal-templates' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'These values can only be set via theme CSS. Add them to your theme\'s stylesheet or the Additional CSS panel in the Customiser.', 'modal-templates' ); ?>
			</p>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Box shadow', 'modal-templates' ); ?></th>
					<td><code>--mt-dialog-shadow</code></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Close button hover background', 'modal-templates' ); ?></th>
					<td><code>--mt-close-bg-hover</code> <?php esc_html_e( '(default: #f0f0f0)', 'modal-templates' ); ?></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Z-index', 'modal-templates' ); ?></th>
					<td><code>--mt-z-index</code> <?php esc_html_e( '(default: 99999)', 'modal-templates' ); ?></td>
				</tr>
			</table>

			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
