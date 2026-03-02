/**
 * Modal Templates — core/button extension
 *
 * Rather than shipping a parallel button block, we extend core/button with:
 *   1. Two extra attributes: modalSlug, modalWidth
 *   2. A "Modal Settings" inspector panel (rendered for all core/button blocks
 *      so the user can opt-in to modal behaviour on any button)
 *   3. A block variation "Modal Trigger Button" for quick insertion
 *
 * On the PHP side a render_block filter reads modalSlug and injects the
 * data attributes + pre-rendered <template> element that the frontend JS
 * needs (see includes/register-blocks.php).
 */

import { __ } from '@wordpress/i18n';
import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';
import { registerBlockVariation } from '@wordpress/blocks';
import TemplateSelector from '../shared/TemplateSelector';

/* ------------------------------------------------------------------ */
/* 1. Add modalSlug + modalWidth attributes to core/button             */
/* ------------------------------------------------------------------ */

addFilter(
	'blocks.registerBlockType',
	'modal-templates/button-attributes',
	( settings, name ) => {
		if ( name !== 'core/button' ) {
			return settings;
		}

		return {
			...settings,
			attributes: {
				...settings.attributes,
				modalSlug: {
					type: 'string',
					default: '',
				},
				modalWidth: {
					type: 'string',
					default: 'medium',
					enum: [ 'small', 'medium', 'large', 'full' ],
				},
			},
		};
	}
);

/* ------------------------------------------------------------------ */
/* 2. Inject "Modal Settings" inspector panel into core/button         */
/* ------------------------------------------------------------------ */

const widthOptions = [
	{ label: __( 'Small (480px)', 'modal-templates' ), value: 'small' },
	{ label: __( 'Medium (640px)', 'modal-templates' ), value: 'medium' },
	{ label: __( 'Large (960px)', 'modal-templates' ), value: 'large' },
	{ label: __( 'Full (100%)', 'modal-templates' ), value: 'full' },
];

const withModalInspector = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {
		if ( props.name !== 'core/button' ) {
			return <BlockEdit { ...props } />;
		}

		const { attributes, setAttributes } = props;
		const { modalSlug, modalWidth } = attributes;

		return (
			<>
				<BlockEdit { ...props } />

				<InspectorControls>
					<PanelBody
						title={ __( 'Modal', 'modal-templates' ) }
						initialOpen={ !! modalSlug }
					>
						<TemplateSelector
							value={ modalSlug }
							onChange={ ( value ) =>
								setAttributes( { modalSlug: value } )
							}
						/>

						{ modalSlug && (
							<SelectControl
								label={ __( 'Modal width', 'modal-templates' ) }
								value={ modalWidth }
								options={ widthOptions }
								onChange={ ( value ) =>
									setAttributes( { modalWidth: value } )
								}
								__nextHasNoMarginBottom
							/>
						) }
					</PanelBody>
				</InspectorControls>
			</>
		);
	};
}, 'withModalInspector' );

addFilter(
	'editor.BlockEdit',
	'modal-templates/button-inspector',
	withModalInspector
);

/* ------------------------------------------------------------------ */
/* 3. Block variation: "Modal Trigger Button"                          */
/* ------------------------------------------------------------------ */

registerBlockVariation( 'core/button', {
	name: 'modal-templates/modal-trigger-button',
	title: __( 'Modal Trigger Button', 'modal-templates' ),
	description: __(
		'A button that opens a modal containing a template part.',
		'modal-templates'
	),
	icon: 'admin-page',
	// isActive is checked when loading existing blocks to decide which
	// variation label / icon to show in the toolbar.
	isActive: ( blockAttributes ) => !! blockAttributes.modalSlug,
} );
