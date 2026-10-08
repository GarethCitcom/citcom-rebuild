/**
 * Height helpers (port of assets/_dev/js/functions/equalHeights.js).
 *
 * jQuery's .width( v ) sets the content width and .outerHeight( v ) the
 * border-box height; on a border-box element (everything here) the first adds
 * the padding and border back on. Same arithmetic, so the boxes stay the size
 * they were.
 * @param el
 */
/**
 * Padding and border on each axis, and the box model.
 *
 * @param {Element} el Element.
 * @return {{borderBox: boolean, x: number, y: number}} Edges.
 */
function edges( el ) {
	const cs = window.getComputedStyle( el );
	const sum = ( ...props ) =>
		props.reduce(
			( total, prop ) => total + ( parseFloat( cs[ prop ] ) || 0 ),
			0
		);
	return {
		borderBox: 'border-box' === cs.boxSizing,
		x: sum(
			'paddingLeft',
			'paddingRight',
			'borderLeftWidth',
			'borderRightWidth'
		),
		y: sum(
			'paddingTop',
			'paddingBottom',
			'borderTopWidth',
			'borderBottomWidth'
		),
	};
}

/**
 * jQuery .outerHeight( v ).
 *
 * @param {Element} el     Element.
 * @param {number}  height Border-box height.
 */
function setOuterHeight( el, height ) {
	const e = edges( el );
	el.style.height = ( e.borderBox ? height : height - e.y ) + 'px';
}

/**
 * Same height for every match.
 *
 * @param {string} selector Elements.
 */
export function equalHeights( selector ) {
	const elements = [ ...document.querySelectorAll( selector ) ];
	const highest = Math.max( 0, ...elements.map( ( el ) => el.offsetHeight ) );
	elements.forEach( ( el ) => setOuterHeight( el, highest ) );
}

/**
 * Same height for every match, measured afresh.
 *
 * @param {string} selector Elements.
 */
export function equalHeightsWithReset( selector ) {
	const elements = [ ...document.querySelectorAll( selector ) ];
	elements.forEach( ( el ) => {
		el.style.height = 'auto';
	} );
	const highest = Math.max( 0, ...elements.map( ( el ) => el.offsetHeight ) );
	elements.forEach( ( el ) => setOuterHeight( el, highest ) );
}

/**
 * Content width equal to the outer height, as jQuery's .width( outerHeight() ) did.
 *
 * @param {string} selector Elements.
 */
export function widthEqualHeight( selector ) {
	document.querySelectorAll( selector ).forEach( ( el ) => {
		const e = edges( el );
		el.style.width =
			( e.borderBox ? el.offsetHeight + e.x : el.offsetHeight ) + 'px';
	} );
}
