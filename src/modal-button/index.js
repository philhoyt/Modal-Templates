/**
 * Modal Templates — block editor extensions
 *
 * Extends core/button and core/group with modal trigger behaviour:
 *   1. Adds modalSlug + modalWidth attributes to both blocks
 *   2. Injects a "Modal" inspector panel into both blocks
 *   3. Registers block variations for quick insertion
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

const MODAL_BLOCKS = [ 'core/button', 'core/group' ];

const widthOptions = [
	{ label: __( 'Small (480px)', 'modal-templates' ), value: 'small' },
	{ label: __( 'Medium (640px)', 'modal-templates' ), value: 'medium' },
	{ label: __( 'Large (960px)', 'modal-templates' ), value: 'large' },
	{ label: __( 'Full (100%)', 'modal-templates' ), value: 'full' },
	{
		label: __( 'Custom (set in Settings)', 'modal-templates' ),
		value: 'custom',
	},
];

/* ------------------------------------------------------------------ */
/* 1. Add modalSlug + modalWidth attributes to core/button + core/group */
/* ------------------------------------------------------------------ */

addFilter(
	'blocks.registerBlockType',
	'modal-templates/modal-attributes',
	( settings, name ) => {
		if ( ! MODAL_BLOCKS.includes( name ) ) {
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
					enum: [ 'small', 'medium', 'large', 'full', 'custom' ],
				},
			},
		};
	}
);

/* ------------------------------------------------------------------ */
/* 2. Inject "Modal" inspector panel into core/button + core/group     */
/* ------------------------------------------------------------------ */

const withModalInspector = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {
		if ( ! MODAL_BLOCKS.includes( props.name ) ) {
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
	'modal-templates/modal-inspector',
	withModalInspector
);

/* ------------------------------------------------------------------ */
/* 3. Block variations                                                  */
/* ------------------------------------------------------------------ */

registerBlockVariation( 'core/button', {
	name: 'modal-templates/modal-trigger-button',
	title: __( 'Modal Trigger Button', 'modal-templates' ),
	description: __(
		'A button that opens a modal containing a template part.',
		'modal-templates'
	),
	icon: 'admin-page',
	isActive: ( blockAttributes ) => !! blockAttributes.modalSlug,
} );

registerBlockVariation( 'core/group', {
	name: 'modal-templates/modal-trigger-group',
	title: __( 'Modal Trigger Group', 'modal-templates' ),
	description: __(
		'A group of blocks that opens a modal when clicked.',
		'modal-templates'
	),
	icon: 'admin-page',
	isActive: ( blockAttributes ) => !! blockAttributes.modalSlug,
} );
