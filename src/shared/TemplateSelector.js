/**
 * TemplateSelector
 *
 * A ComboboxControl that lists all template parts in the "modal" area
 * and provides a "Create new" flow that:
 *   1. Prompts the user for a name
 *   2. Saves a new wp_template_part record with area="modal"
 *   3. Opens the Site Editor to edit it
 *   4. Updates the block's modalSlug attribute
 *
 * Pattern mirrors Ollie Menu Designer's TemplateSelector / useTemplateCreation.
 */

import { __ } from '@wordpress/i18n';
import { useState, useRef, useEffect } from '@wordpress/element';
import {
	ComboboxControl,
	Button,
	Spinner,
	BaseControl,
	TextControl,
	ExternalLink,
} from '@wordpress/components';
import { useEntityRecords, store as coreStore } from '@wordpress/core-data';
import { useDispatch, useSelect } from '@wordpress/data';
import { decodeEntities } from '@wordpress/html-entities';

const MODAL_AREA = 'modal';

/**
 * Convert a human-readable name into a URL-safe slug prefixed with "modal-".
 * e.g. "Person Bio" → "modal-person-bio"
 *
 * @param {string} name Raw user input.
 * @return {string} Slug string.
 */
function nameToSlug( name ) {
	return (
		'modal-' +
		name
			.toLowerCase()
			.trim()
			.replace( /[^a-z0-9]+/g, '-' )
			.replace( /^-+|-+$/g, '' )
	);
}

export default function TemplateSelector( { value, onChange } ) {
	const [ isCreating, setIsCreating ] = useState( false );
	const [ showNameInput, setShowNameInput ] = useState( false );
	const [ newName, setNewName ] = useState( '' );
	const nameInputRef = useRef( null );

	// Focus the name input as soon as it appears.
	useEffect( () => {
		if ( showNameInput && nameInputRef.current ) {
			nameInputRef.current.focus();
		}
	}, [ showNameInput ] );

	// Fetch all template parts; filter client-side for the modal area
	// (same approach as Ollie — avoids relying on REST ?area= support).
	const { hasResolved, records } = useEntityRecords(
		'postType',
		'wp_template_part',
		{ per_page: -1 }
	);

	const { saveEntityRecord } = useDispatch( coreStore );

	// Theme stylesheet slug — needed when opening the Site Editor.
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
		if ( isCreating || ! newName.trim() ) {
			return;
		}

		const title = newName.trim();
		const slug = nameToSlug( title );

		setIsCreating( true );

		try {
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
				setShowNameInput( false );
				setNewName( '' );

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
			console.error(
				'Modal Templates: failed to create template part',
				err
			);
		} finally {
			setIsCreating( false );
		}
	};

	const handleCancel = () => {
		setShowNameInput( false );
		setNewName( '' );
	};

	// Site Editor link for the currently selected template part.
	// Use the record's `id` field (e.g. "twentytwentyfour//modal-person-bio")
	// so the Site Editor opens directly to that template part for editing.
	const selectedPart = modalParts.find( ( part ) => part.slug === value );
	const editUrl = selectedPart
		? `${ adminUrl }site-editor.php` +
		  `?postType=wp_template_part` +
		  `&postId=${ encodeURIComponent( selectedPart.id ) }` +
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

			{ showNameInput ? (
				<div style={ { marginTop: '8px' } }>
					<TextControl
						ref={ nameInputRef }
						label={ __( 'Template name', 'modal-templates' ) }
						value={ newName }
						placeholder={ __(
							'e.g. Person Bio',
							'modal-templates'
						) }
						onChange={ setNewName }
						onKeyDown={ ( e ) => {
							if ( e.key === 'Enter' ) {
								handleCreate();
							} else if ( e.key === 'Escape' ) {
								handleCancel();
							}
						} }
						help={
							newName.trim()
								? `slug: ${ nameToSlug( newName ) }`
								: undefined
						}
						__nextHasNoMarginBottom
					/>
					<div
						style={ {
							display: 'flex',
							gap: '8px',
							marginTop: '8px',
						} }
					>
						<Button
							variant="primary"
							size="small"
							isBusy={ isCreating }
							disabled={ isCreating || ! newName.trim() }
							onClick={ handleCreate }
						>
							{ __( 'Create', 'modal-templates' ) }
						</Button>
						<Button
							variant="tertiary"
							size="small"
							disabled={ isCreating }
							onClick={ handleCancel }
						>
							{ __( 'Cancel', 'modal-templates' ) }
						</Button>
					</div>
				</div>
			) : (
				<div
					style={ {
						display: 'flex',
						gap: '8px',
						marginTop: '8px',
						flexWrap: 'wrap',
					} }
				>
					<Button
						variant="secondary"
						size="small"
						onClick={ () => setShowNameInput( true ) }
					>
						{ __( '+ New modal template', 'modal-templates' ) }
					</Button>

					{ value && editUrl && (
						<ExternalLink href={ editUrl }>
							{ __( 'Edit in Site Editor', 'modal-templates' ) }
						</ExternalLink>
					) }
				</div>
			) }
		</BaseControl>
	);
}
