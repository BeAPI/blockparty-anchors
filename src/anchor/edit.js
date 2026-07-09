/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';

/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
import {
	InspectorControls,
	useBlockProps,
	RichText,
} from '@wordpress/block-editor';

/**
 * UI components for the inspector panel.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-components/
 */
import { PanelBody, PanelRow, TextControl } from '@wordpress/components';

/**
 * Utility to build HTML-friendly IDs from labels.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-url/
 */
import { cleanForSlug } from '@wordpress/url';

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * Those files can contain any CSS code that gets applied to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './editor.scss';

/**
 * Allow slug typing while keeping only HTML id-friendly characters.
 *
 * @param {string} slug Raw slug input.
 *
 * @return {string} Sanitized slug input.
 */
const sanitizeSlugInput = ( slug ) =>
	slug
		.toLowerCase()
		.replace( /\s+/g, '-' )
		.replace( /[^a-z0-9-]/g, '' )
		.replace( /-{2,}/g, '-' );

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @param {Object}   props               Component props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Set block attributes callback.
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {Element} Element to render.
 */
export default function Edit( { attributes, setAttributes } ) {
	const onChangeTitle = ( title ) => {
		setAttributes( {
			title,
			slug: title ? cleanForSlug( title ) : '',
		} );
	};

	const onChangeSlug = ( slug ) => {
		setAttributes( {
			slug: sanitizeSlugInput( slug ),
		} );
	};

	const onBlurSlug = () => {
		if ( ! attributes.slug ) {
			return;
		}

		setAttributes( {
			slug: cleanForSlug( attributes.slug ),
		} );
	};

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Settings', 'blockparty-anchors' ) }
					initialOpen={ true }
				>
					<PanelRow>
						<TextControl
							type="text"
							value={ attributes.slug || '' }
							onChange={ onChangeSlug }
							onBlur={ onBlurSlug }
							label={ __( 'Anchor ID', 'blockparty-anchors' ) }
							help={ __(
								'This identifier is generated automatically when the anchor is edited the first time. It should be edited with cautions afterwards.',
								'blockparty-anchors'
							) }
						/>
					</PanelRow>
				</PanelBody>
			</InspectorControls>
			<div { ...useBlockProps() }>
				<RichText
					tagName="span"
					value={ attributes.title }
					onChange={ onChangeTitle }
					placeholder={ __( 'Anchor label', 'blockparty-anchors' ) }
				/>
			</div>
		</>
	);
}
