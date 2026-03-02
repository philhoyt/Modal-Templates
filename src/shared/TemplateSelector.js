/**
 * TemplateSelector
 *
 * A ComboboxControl that lists all template parts in the "modal" area
 * and provides a "Create new" button that:
 *   1. Saves a new wp_template_part record with area="modal"
 *   2. Opens the Site Editor to edit it
 *   3. Updates the block's modalSlug attribute
 *
 * Pattern mirrors Ollie Menu Designer's TemplateSelector / useTemplateCreation.
 */

import { __, sprintf } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import {
	ComboboxControl,
	Button,
	Spinner,
	BaseControl,
	ExternalLink,
} from '@wordpress/components';
import { useEntityRecords } from '@wordpress/core-data';
import { useDispatch, useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import { decodeEntities } from '@wordpress/html-entities';

const MODAL_AREA = 'modal';

export default function TemplateSelector( { value, onChange } ) {
	const [ isCreating, setIsCreating ] = useState( false );

	// Fetch all template parts; filter client-side for the modal area
	// (same approach as Ollie — avoids relying on REST ?area= support).
	const { hasResolved, records } = useEntityRecords(
		'postType',
		'wp_template_part',
		{ per_page: -1 }
	);

	const { saveEntityRecord } = useDispatch( coreStore );

	// Theme stylesheet slug — needed when constructing the template part ID
	// and when opening the Site Editor.
	const currentTheme = useSelect(
		( select ) => select( coreStore ).getCurrentTheme()?.stylesheet,
		[]
	);

	// Admin URL injected by PHP (same pattern as Ollie's menuDesignerData).
	const adminUrl =
		window.modalTemplatesData?.adminUrl ||
		`${ window.location.origin }/wp-admin/`;

	const modalParts =
		hasResolved && records
			? records.filter( ( part ) => part.area === MODAL_AREA )
			: [];

	const options = modalParts.map( ( part ) => ( {
		label: decodeEntities( part.title?.rendered || part.slug ),
		value: part.slug,
	} ) );

	// ------------------------------------------------------------------ //
	// Create a new modal template part and open it in the Site Editor     //
	// ------------------------------------------------------------------ //
	const handleCreate = async () => {
		if ( isCreating ) return;
		setIsCreating( true );

		try {
			const timestamp = Date.now();
			const slug  = `modal-${ timestamp }`;
			const title = sprintf(
				/* translators: %s: auto-generated number */
				__( 'Modal %s', 'modal-templates' ),
				new Date().toLocaleDateString( undefined, {
					month: 'short',
					day: 'numeric',
				} )
			);

			const newPart = await saveEntityRecord(
				'postType',
				'wp_template_part',
				{
					slug,
					theme: currentTheme || '',
					type: 'wp_template_part',
					area: MODAL_AREA,
					title: { raw: title, rendered: title },
					content:
						'<!-- wp:paragraph --><p>' +
						__( 'Modal content goes here.', 'modal-templates' ) +
						'</p><!-- /wp:paragraph -->',
					status: 'publish',
				}
			);

			if ( newPart?.id ) {
				onChange( newPart.slug );

				// Give WordPress a moment to finish indexing the new record.
				setTimeout( () => {
					const editUrl =
						`${ adminUrl }site-editor.php` +
						`?postId=${ encodeURIComponent( newPart.id ) }` +
						`&postType=wp_template_part&canvas=edit`;
					window.open( editUrl, '_blank' );
				}, 500 );
			}
		} catch ( err ) {
			// eslint-disable-next-line no-console
			console.error( 'Modal Templates: failed to create template part', err );
		} finally {
			setIsCreating( false );
		}
	};

	// Site Editor link for the currently selected template part.
	const editUrl =
		value && currentTheme
			? `${ adminUrl }site-editor.php` +
			  `?postType=wp_template_part` +
			  `&categoryId=${ MODAL_AREA }` +
			  `&canvas=edit`
			: null;

	// ------------------------------------------------------------------ //
	// Render                                                               //
	// ------------------------------------------------------------------ //

	if ( ! hasResolved ) {
		return <Spinner />;
	}

	return (
		<BaseControl __nextHasNoMarginBottom>
			{ options.length > 0 ? (
				<ComboboxControl
					label={ __( 'Modal Template Part', 'modal-templates' ) }
					value={ value || '' }
					options={ options }
					onChange={ ( v ) => onChange( v || '' ) }
					help={ __(
						'The selected template part is rendered inside the modal when this trigger is clicked.',
						'modal-templates'
					) }
					__nextHasNoMarginBottom
				/>
			) : (
				<p
					style={ {
						color: '#757575',
						fontSize: '12px',
						margin: '0 0 8px',
					} }
				>
					{ __(
						'No modal template parts found. Create one below.',
						'modal-templates'
					) }
				</p>
			) }

			<div style={ { display: 'flex', gap: '8px', marginTop: '8px', flexWrap: 'wrap' } }>
				<Button
					variant="secondary"
					size="small"
					isBusy={ isCreating }
					disabled={ isCreating }
					onClick={ handleCreate }
				>
					{ __( '+ New modal template', 'modal-templates' ) }
				</Button>

				{ value && editUrl && (
					<ExternalLink href={ editUrl }>
						{ __( 'Edit in Site Editor', 'modal-templates' ) }
					</ExternalLink>
				) }
			</div>
		</BaseControl>
	);
}
