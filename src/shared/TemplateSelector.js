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

import { __, sprintf } from '@wordpress/i18n';
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
import { store as noticesStore } from '@wordpress/notices';
import { speak } from '@wordpress/a11y';

const MODAL_AREA = 'modal';

/**
 * Convert a human-readable name into a URL-safe slug prefixed with "modal-".
 * e.g. "Modal Content" → "modal-content"
 *
 * @param {string} name Raw user input.
 * @return {string} Slug string.
 */
function nameToSlug( name ) {
	const base = name
		.toLowerCase()
		.trim()
		.replace( /[^a-z0-9]+/g, '-' )
		.replace( /^-+|-+$/g, '' );

	// Names with no ASCII alphanumerics (e.g. "モーダル") would all collapse to
	// "modal-"; fall back to a unique suffix instead of colliding.
	return 'modal-' + ( base || Date.now().toString( 36 ) );
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
	const { createErrorNotice } = useDispatch( noticesStore );

	// Theme stylesheet slug — needed when opening the Site Editor.
	const currentTheme = useSelect(
		( select ) => select( coreStore ).getCurrentTheme()?.stylesheet,
		[]
	);

	// Listing template parts only needs edit_posts, but creating/editing them
	// needs edit_theme_options — hide the create/edit UI from users who lack it.
	const canCreate = useSelect(
		( select ) =>
			select( coreStore ).canUser( 'create', {
				kind: 'postType',
				name: 'wp_template_part',
			} ),
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
			const message = err?.message
				? sprintf(
						/* translators: %s: error message from the REST API */
						__(
							'Could not create the modal template part: %s',
							'modal-templates'
						),
						err.message
					)
				: __(
						'Could not create the modal template part.',
						'modal-templates'
					);
			createErrorNotice( message, { type: 'snackbar' } );
			speak( message, 'assertive' );
		} finally {
			setIsCreating( false );
		}
	};

	const handleCancel = () => {
		setShowNameInput( false );
		setNewName( '' );
	};

	// Site Editor link for the currently selected template part.
	// Use the record's `id` field (e.g. "twentytwentyfour//modal-content")
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
					__next40pxDefaultSize
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
					{ canCreate
						? __(
								'No modal template parts found. Create one below.',
								'modal-templates'
							)
						: __(
								'No modal template parts found. Ask an administrator to create one in the Site Editor.',
								'modal-templates'
							) }
				</p>
			) }

			{ canCreate && showNameInput && (
				<div style={ { marginTop: '8px' } }>
					<TextControl
						ref={ nameInputRef }
						label={ __( 'Template name', 'modal-templates' ) }
						value={ newName }
						placeholder={ __(
							'e.g. Modal Content',
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
						__next40pxDefaultSize
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
			) }

			{ canCreate && ! showNameInput && (
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
