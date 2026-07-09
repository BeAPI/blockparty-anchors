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
import { useBlockProps, RichText } from '@wordpress/block-editor';

/**
 * WordPress data layer.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-data/
 */
import { useSelect, useDispatch } from '@wordpress/data';

/**
 * WordPress element hook.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-element/
 */
import { useCallback, useMemo } from '@wordpress/element';

/**
 * Utility to build slugs from titles.
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

const ANCHOR_BLOCK_NAME = 'blockparty/anchor';

/**
 * Recursively find blocks by name in the block tree.
 *
 * @param {Array}  blocks    Block list.
 * @param {string} blockName Block name to match.
 *
 * @return {Array} Matching blocks.
 */
const findBlocksByName = ( blocks, blockName ) =>
	blocks.reduce( ( collected, block ) => {
		if ( block.name === blockName ) {
			collected.push( block );
		}

		if ( block.innerBlocks?.length ) {
			collected.push(
				...findBlocksByName( block.innerBlocks, blockName )
			);
		}

		return collected;
	}, [] );

/**
 * Build an anchor slug from its title.
 *
 * @param {string} title Anchor title.
 *
 * @return {string} Anchor slug.
 */
const buildSlugFromTitle = ( title ) => ( title ? cleanForSlug( title ) : '' );

/**
 * Resolve the slug used by an anchor block in the editor.
 *
 * @param {Object} attributes Anchor block attributes.
 *
 * @return {string} Anchor slug.
 */
const getAnchorSlug = ( attributes ) =>
	attributes.slug || buildSlugFromTitle( attributes.title || '' );

/**
 * Ensure a slug is unique among anchors already assigned on the page.
 *
 * Mirrors server-side Anchors::make_unique_slug() so editor hrefs match frontend output.
 *
 * @param {string}               slug          Candidate slug.
 * @param {Record<string, true>} assignedSlugs Slugs already assigned.
 *
 * @return {string} Unique slug.
 */
const makeUniqueSlug = ( slug, assignedSlugs ) => {
	if ( ! slug ) {
		return '';
	}

	let uniqueSlug = slug;
	let suffix = 2;

	while ( assignedSlugs[ uniqueSlug ] ) {
		uniqueSlug = `${ slug }-${ suffix }`;
		suffix += 1;
	}

	assignedSlugs[ uniqueSlug ] = true;

	return uniqueSlug;
};

/**
 * Resolve the unique slug for an anchor block in document order.
 *
 * @param {Object}               attributes    Anchor block attributes.
 * @param {Record<string, true>} assignedSlugs Slugs already assigned.
 *
 * @return {string} Unique anchor slug.
 */
const getUniqueAnchorSlug = ( attributes, assignedSlugs ) => {
	const slug = getAnchorSlug( attributes );

	if ( ! slug ) {
		return '';
	}

	return makeUniqueSlug( slug, assignedSlugs );
};

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {Element} Element to render.
 */
export default function Edit() {
	const anchorBlocks = useSelect( ( select ) => {
		const { getBlocks } = select( 'core/block-editor' );

		return findBlocksByName( getBlocks(), ANCHOR_BLOCK_NAME );
	}, [] );

	const { updateBlockAttributes } = useDispatch( 'core/block-editor' );

	const anchorsWithSlugs = useMemo( () => {
		const assignedSlugs = {};

		return anchorBlocks.map( ( block ) => ( {
			block,
			slug: getUniqueAnchorSlug( block.attributes, assignedSlugs ),
		} ) );
	}, [ anchorBlocks ] );

	const onChangeAnchor = useCallback(
		( clientId, title ) => {
			updateBlockAttributes( clientId, {
				title,
				slug: buildSlugFromTitle( title ),
			} );
		},
		[ updateBlockAttributes ]
	);

	return (
		<div { ...useBlockProps() }>
			<div className="wp-block-blockparty-anchors-list__content">
				<div
					role="heading"
					aria-level="2"
					className="wp-block-blockparty-anchors-list__title"
				>
					{ __( 'Quick access', 'blockparty-anchors' ) }
				</div>
				<div className="wp-block-blockparty-anchors-list__scroll">
					{ anchorsWithSlugs.length ? (
						<ul className="wp-block-blockparty-anchors-list__items no-list-style">
							{ anchorsWithSlugs.map( ( { block, slug } ) => (
								<li
									key={ block.clientId }
									className="wp-block-blockparty-anchors-list__item"
								>
									<a
										className="wp-block-blockparty-anchors-list__link"
										href={ slug ? `#${ slug }` : '#' }
										onClick={ ( event ) =>
											event.preventDefault()
										}
									>
										<RichText
											tagName="span"
											value={
												block.attributes.title || ''
											}
											onChange={ ( title ) =>
												onChangeAnchor(
													block.clientId,
													title
												)
											}
											placeholder={ __(
												'Enter anchor title',
												'blockparty-anchors'
											) }
											allowedFormats={ [] }
											aria-label={ __(
												'Anchor title',
												'blockparty-anchors'
											) }
										/>
									</a>
								</li>
							) ) }
						</ul>
					) : (
						<p className="wp-block-blockparty-anchors-list__empty">
							{ __(
								'No anchors found on this page. Add an Anchor block to populate this list.',
								'blockparty-anchors'
							) }
						</p>
					) }
				</div>
			</div>
		</div>
	);
}
