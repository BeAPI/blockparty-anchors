/**
 * Frontend behavior for the Anchors List block.
 *
 * - Tracks which page section is in view (IntersectionObserver).
 * - Toggles an `is-active` class on the matching list item and link.
 * - Smooth-scrolls to the target on click and updates the URL hash.
 * - Syncs the active item from the current hash on load.
 * - Scrolls the active link into view inside the horizontal scroller.
 */

const BLOCK_SELECTOR = '.wp-block-blockparty-anchors-list';
const ITEM_SELECTOR = '.wp-block-blockparty-anchors-list__item';
const LINK_SELECTOR = '.wp-block-blockparty-anchors-list__link[href^="#"]';
const ACTIVE_CLASS = 'is-active';

/**
 * Resolve hash targets for every in-page link in a list.
 *
 * @param {NodeList|HTMLAnchorElement[]} links Anchor links.
 * @return {Array<{ id: string, element: Element, link: HTMLAnchorElement }>} Matched targets.
 */
const getAnchorTargets = ( links ) => {
	const targets = [];

	links.forEach( ( link ) => {
		const href = link.getAttribute( 'href' ) || '';
		const id = href.slice( 1 );

		if ( ! id ) {
			return;
		}

		const element = document.getElementById( id );

		if ( element ) {
			targets.push( { id, element, link } );
		}
	} );

	return targets;
};

/**
 * Scroll an active link into view horizontally without moving the page.
 *
 * @param {HTMLElement} link Active list link.
 */
const scrollLinkIntoListView = ( link ) => {
	const scroller = link.closest(
		'.wp-block-blockparty-anchors-list__scroll'
	);

	if ( ! scroller ) {
		return;
	}

	const linkRect = link.getBoundingClientRect();
	const scrollerRect = scroller.getBoundingClientRect();

	if (
		linkRect.left >= scrollerRect.left &&
		linkRect.right <= scrollerRect.right
	) {
		return;
	}

	const nextScrollLeft =
		scroller.scrollLeft +
		( linkRect.left - scrollerRect.left ) -
		( scrollerRect.width - linkRect.width ) / 2;

	scroller.scrollTo( {
		left: Math.max( 0, nextScrollLeft ),
		behavior: 'smooth',
	} );
};

/**
 * Toggle active state on list items and links.
 *
 * @param {HTMLElement}                  list     List root.
 * @param {NodeList|HTMLAnchorElement[]} links    All list links.
 * @param {string}                       activeId Active target id.
 */
const updateActiveLink = ( list, links, activeId ) => {
	if ( list.dataset.activeAnchor === activeId ) {
		return;
	}

	links.forEach( ( link ) => {
		const linkTarget = ( link.getAttribute( 'href' ) || '' ).slice( 1 );
		const parentItem = link.closest( ITEM_SELECTOR );
		const isActive = linkTarget === activeId;

		link.classList.toggle( ACTIVE_CLASS, isActive );

		if ( isActive ) {
			link.setAttribute( 'aria-current', 'location' );
			scrollLinkIntoListView( link );
		} else {
			link.removeAttribute( 'aria-current' );
		}

		if ( parentItem ) {
			parentItem.classList.toggle( ACTIVE_CLASS, isActive );
		}
	} );

	list.dataset.activeAnchor = activeId;
};

/**
 * Pick the active target among currently intersecting sections.
 * Prefers the last intersecting target in document order (closest to the spy band).
 *
 * @param {Map}   visibleSections Visible targets keyed by id.
 * @param {Array} orderedTargets  Targets in document order.
 * @return {string|null} Active target id, or null when none are visible.
 */
const getMostRelevantActiveId = ( visibleSections, orderedTargets ) => {
	if ( ! visibleSections.size ) {
		return null;
	}

	let activeId = null;

	orderedTargets.forEach( ( { id } ) => {
		if ( visibleSections.has( id ) ) {
			activeId = id;
		}
	} );

	return activeId;
};

/**
 * Initialize scroll-spy and click handling for one Anchors List block.
 *
 * @param {HTMLElement} list Block root element.
 */
const initAnchorsList = ( list ) => {
	const links = list.querySelectorAll( LINK_SELECTOR );

	if ( ! links.length ) {
		return;
	}

	const targets = getAnchorTargets( links );

	if ( ! targets.length ) {
		return;
	}

	const visibleSections = new Map();

	// eslint-disable-next-line no-undef -- browser API
	const observer = new IntersectionObserver(
		( entries ) => {
			entries.forEach( ( entry ) => {
				const targetId = entry.target.id;

				if ( entry.isIntersecting ) {
					visibleSections.set( targetId, entry.target );
				} else {
					visibleSections.delete( targetId );
				}
			} );

			const activeId = getMostRelevantActiveId(
				visibleSections,
				targets
			);

			if ( activeId ) {
				updateActiveLink( list, links, activeId );
			}
		},
		{
			root: null,
			// Narrow horizontal band near the top of the viewport.
			rootMargin: '-20% 0px -70% 0px',
			threshold: 0,
		}
	);

	targets.forEach( ( { element } ) => {
		observer.observe( element );
	} );

	links.forEach( ( link ) => {
		link.addEventListener( 'click', ( event ) => {
			const targetId = ( link.getAttribute( 'href' ) || '' ).slice( 1 );
			const targetElement = document.getElementById( targetId );

			if ( ! targetElement ) {
				return;
			}

			event.preventDefault();

			targetElement.scrollIntoView( {
				behavior: 'smooth',
				block: 'start',
			} );

			if ( window.history?.pushState ) {
				window.history.pushState( null, '', `#${ targetId }` );
			}

			updateActiveLink( list, links, targetId );
		} );
	} );

	const hashId = window.location.hash.slice( 1 );

	if ( hashId && targets.some( ( { id } ) => id === hashId ) ) {
		updateActiveLink( list, links, hashId );
	}
};

const init = () => {
	document.querySelectorAll( BLOCK_SELECTOR ).forEach( initAnchorsList );
};

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', init );
} else {
	init();
}
