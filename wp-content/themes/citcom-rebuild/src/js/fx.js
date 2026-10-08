/**
 * The few jQuery effects the original scripts used, on plain elements.
 * Durations and the default "swing" easing match jQuery's.
 */
const SWING = 'cubic-bezier(0.42, 0, 0.58, 1)';

function shown( el ) {
	return 'none' !== window.getComputedStyle( el ).display;
}

function display( el ) {
	el.style.removeProperty( 'display' );
	if ( 'none' === window.getComputedStyle( el ).display ) {
		el.style.display = 'block';
	}
}

/**
 * jQuery fadeIn(): show, then opacity 0 to 1.
 * @param {Element} el       Element.
 * @param {number}  duration Milliseconds.
 */
export function fadeIn( el, duration = 400 ) {
	if ( shown( el ) && ! el.dataset.fxOut ) {
		return;
	}
	delete el.dataset.fxOut;
	display( el );
	el.style.opacity = '0';
	const animation = el.animate( [ { opacity: 0 }, { opacity: 1 } ], {
		duration,
		easing: SWING,
	} );
	animation.onfinish = () => {
		el.style.removeProperty( 'opacity' );
	};
}

/**
 * jQuery fadeOut(): opacity to 0, then display none.
 * @param {Element} el       Element.
 * @param {number}  duration Milliseconds.
 */
export function fadeOut( el, duration = 400 ) {
	if ( ! shown( el ) ) {
		return;
	}
	el.dataset.fxOut = '1';
	const animation = el.animate(
		[ { opacity: window.getComputedStyle( el ).opacity }, { opacity: 0 } ],
		{ duration, easing: SWING }
	);
	animation.onfinish = () => {
		if ( el.dataset.fxOut ) {
			el.style.display = 'none';
			el.style.removeProperty( 'opacity' );
			delete el.dataset.fxOut;
		}
	};
}

/**
 * jQuery slideDown(): show and grow from zero height.
 * @param {Element} el       Element.
 * @param {number}  duration Milliseconds.
 */
export function slideDown( el, duration = 400 ) {
	if (
		shown( el ) &&
		'down' !== el.dataset.fxSlide &&
		! el.dataset.fxSlide
	) {
		return;
	}
	el.dataset.fxSlide = 'down';
	display( el );
	const height = el.offsetHeight;
	el.style.overflow = 'hidden';
	const animation = el.animate(
		[
			{ height: '0px', paddingTop: '0px', paddingBottom: '0px' },
			{ height: height + 'px' },
		],
		{ duration, easing: SWING }
	);
	animation.onfinish = () => {
		if ( 'down' === el.dataset.fxSlide ) {
			el.style.removeProperty( 'overflow' );
			delete el.dataset.fxSlide;
		}
	};
}

/**
 * jQuery slideUp(): shrink to zero height, then display none.
 * @param {Element} el       Element.
 * @param {number}  duration Milliseconds.
 */
export function slideUp( el, duration = 400 ) {
	if ( ! shown( el ) ) {
		return;
	}
	el.dataset.fxSlide = 'up';
	el.style.overflow = 'hidden';
	const animation = el.animate(
		[
			{ height: el.offsetHeight + 'px' },
			{ height: '0px', paddingTop: '0px', paddingBottom: '0px' },
		],
		{ duration, easing: SWING }
	);
	animation.onfinish = () => {
		if ( 'up' === el.dataset.fxSlide ) {
			el.style.display = 'none';
			el.style.removeProperty( 'overflow' );
			delete el.dataset.fxSlide;
		}
	};
}

/**
 * jQuery .width(): content width, without padding and border.
 * @param {Element} el Element.
 */
export function contentWidth( el ) {
	const cs = window.getComputedStyle( el );
	return (
		el.getBoundingClientRect().width -
		parseFloat( cs.paddingLeft ) -
		parseFloat( cs.paddingRight ) -
		parseFloat( cs.borderLeftWidth ) -
		parseFloat( cs.borderRightWidth )
	);
}

/**
 * jQuery .height(): content height.
 * @param {Element} el Element.
 */
export function contentHeight( el ) {
	const cs = window.getComputedStyle( el );
	return (
		el.getBoundingClientRect().height -
		parseFloat( cs.paddingTop ) -
		parseFloat( cs.paddingBottom ) -
		parseFloat( cs.borderTopWidth ) -
		parseFloat( cs.borderBottomWidth )
	);
}

/**
 * jQuery .position(): offset from the offset parent, without the element's margins.
 * @param {Element} el Element.
 */
export function position( el ) {
	const cs = window.getComputedStyle( el );
	return {
		left: el.offsetLeft - parseFloat( cs.marginLeft ),
		top: el.offsetTop - parseFloat( cs.marginTop ),
	};
}

/**
 * jQuery .hover( in, out ): mouseenter and mouseleave. One handler serves both.
 * @param {Element}  el    Element.
 * @param {Function} enter Handler.
 * @param {Function} leave Handler.
 */
export function hover( el, enter, leave = enter ) {
	el.addEventListener( 'mouseenter', enter );
	el.addEventListener( 'mouseleave', leave );
}

/**
 * Runs when the document is ready.
 *
 * @param {Function} fn Callback.
 */
export function ready( fn ) {
	if ( 'loading' !== document.readyState ) {
		fn();
	} else {
		document.addEventListener( 'DOMContentLoaded', fn );
	}
}

/**
 * Runs once the theme stylesheet applies. It loads without blocking (inlined
 * critical CSS first, inc/assets.php), so anything that measures layout at
 * DOMContentLoaded would measure an unstyled page.
 *
 * @param {Function} fn Callback.
 */
export function stylesReady( fn ) {
	const link = document.getElementById( 'theme-style-css' );
	if ( ! link || ( 'stylesheet' === link.rel && link.sheet ) ) {
		fn();
		return;
	}
	let done = false;
	const run = () => {
		if ( ! done ) {
			done = true;
			// The inline onload swaps rel to stylesheet first; one tick lets the sheet apply.
			setTimeout( fn, 0 );
		}
	};
	link.addEventListener( 'load', run, { once: true } );
	link.addEventListener( 'error', run, { once: true } );
	setTimeout( run, 4000 );
}
