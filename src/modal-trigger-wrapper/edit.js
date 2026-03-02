import { __, sprintf } from '@wordpress/i18n';
import {
	useBlockProps,
	InspectorControls,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';
import TemplateSelector from '../shared/TemplateSelector';

const TEMPLATE = [
	[ 'core/image', { sizeSlug: 'large' } ],
	[
		'core/heading',
		{ level: 3, placeholder: __( 'Card Heading…', 'modal-templates' ) },
	],
	[
		'core/paragraph',
		{
			placeholder: __(
				'Card description. The whole card opens a modal on click.',
				'modal-templates'
			),
		},
	],
];

const widthOptions = [
	{ label: __( 'Small (480px)', 'modal-templates' ), value: 'small' },
	{ label: __( 'Medium (640px)', 'modal-templates' ), value: 'medium' },
	{ label: __( 'Large (960px)', 'modal-templates' ), value: 'large' },
	{ label: __( 'Full (100%)', 'modal-templates' ), value: 'full' },
];

export default function Edit( { attributes, setAttributes } ) {
	const { modalSlug, modalWidth } = attributes;

	const blockProps = useBlockProps( {
		className: [
			'modal-trigger-wrapper',
			modalSlug
				? 'modal-trigger-wrapper--active'
				: 'modal-trigger-wrapper--unassigned',
		].join( ' ' ),
		style: {
			cursor: modalSlug ? 'pointer' : 'default',
			outline: ! modalSlug ? '2px dashed #ccc' : undefined,
		},
	} );

	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		template: TEMPLATE,
		templateLock: false,
	} );

	return (
		<>
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

			<div style={ { position: 'relative' } }>
				{ modalSlug && (
					<div
						style={ {
							position: 'absolute',
							top: 0,
							right: 0,
							background: '#1e1e1e',
							color: '#fff',
							fontSize: '11px',
							padding: '2px 8px',
							borderRadius: '0 0 0 4px',
							zIndex: 10,
							pointerEvents: 'none',
						} }
					>
						{ sprintf(
							/* translators: %s: template part slug */
							__( '⟶ modal: %s', 'modal-templates' ),
							modalSlug
						) }
					</div>
				) }
				<div { ...innerBlocksProps } />
			</div>

			{ ! modalSlug && (
				<p
					style={ {
						color: '#757575',
						fontSize: '12px',
						marginTop: '4px',
					} }
				>
					{ __(
						'⚠ No modal template part selected. Use the sidebar to assign one.',
						'modal-templates'
					) }
				</p>
			) }
		</>
	);
}
