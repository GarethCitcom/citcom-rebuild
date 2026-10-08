/**
 * Swiper carousels (port of assets/_dev/js/functions/swiper.js), loaded only on
 * pages that have one. Only the modules the two carousel types use are bundled.
 *
 * Slides past the second are printed with their sources held back
 * (citcom_defer_image() in inc/helpers.php): Chrome fetches lazy images inside
 * an overflow:hidden carousel anyway. They are fetched here as the carousel
 * nears them.
 */
import Swiper from 'swiper';
import {
	Autoplay,
	FreeMode,
	Navigation,
	Pagination,
	Scrollbar,
} from 'swiper/modules';

function loadDeferred( slide ) {
	slide.querySelectorAll( 'img.citcom-deferred' ).forEach( ( img ) => {
		if ( img.dataset.srcset ) {
			img.srcset = img.dataset.srcset;
		}
		if ( img.dataset.src ) {
			img.src = img.dataset.src;
		}
		img.classList.remove( 'citcom-deferred' );
	} );
}

function loadNearby( swiper, ahead = 2 ) {
	const { slides, activeIndex } = swiper;
	for (
		let i = Math.max( 0, activeIndex - 1 );
		i <= Math.min( slides.length - 1, activeIndex + ahead );
		i++
	) {
		loadDeferred( slides[ i ] );
	}
}

const data = ( el, key ) => {
	const value = el.dataset[ key ];
	if ( undefined === value || '' === value ) {
		return undefined;
	}
	if ( 'true' === value || 'false' === value ) {
		return 'true' === value;
	}
	return isNaN( Number( value ) ) ? value : Number( value );
};

export function swiperSetup() {
	document.querySelectorAll( '.swiper-gallery' ).forEach( ( el ) => {
		new Swiper( el, {
			modules: [ Pagination ],
			pagination: {
				el: el.querySelector( '.swiper-pagination' ),
				clickable: true,
			},
			on: {
				init: ( swiper ) => loadNearby( swiper ),
				slideChange: ( swiper ) => loadNearby( swiper ),
				touchMove: ( swiper ) => loadNearby( swiper ),
			},
		} );
	} );

	document.querySelectorAll( '.swiper-swiper' ).forEach( ( el ) => {
		const swiperLoop = data( el, 'loop' );
		const swiperAutoplay = data( el, 'autoplay' );
		const swiperDelay = data( el, 'delay' );
		const swiperSpeed = data( el, 'speed' );
		const swiperCenter = data( el, 'center' );
		const swiperReverseDirection = data( el, 'reversedirection' );
		const swiperFreeMode = ! ( swiperDelay > 0 );

		const swiper = new Swiper( el, {
			modules: [ Autoplay, FreeMode, Navigation, Pagination, Scrollbar ],
			slidesPerView: 'auto',
			slidesPerGroup: 2,
			spaceBetween: 0,
			loop: swiperLoop,
			autoplay: {
				delay: -1,
				pauseOnMouseEnter: true,
				reverseDirection: swiperReverseDirection,
			},
			freeMode: swiperFreeMode,
			speed: swiperSpeed,
			centerInsufficientSlides: swiperCenter,
			centeredSlides: swiperCenter,
			grabCursor: true,
			pagination: {
				el: el.querySelector( '.swiper-pagination' ),
			},
			navigation: {
				nextEl: el.querySelector( '.swiper-button-next' ),
				prevEl: el.querySelector( '.swiper-button-prev' ),
			},
			scrollbar: {
				el: el.querySelector( '.swiper-scrollbar' ),
			},
			on: {
				init: ( s ) => loadNearby( s, 6 ),
				slideChange: ( s ) => loadNearby( s, 6 ),
				progress: ( s ) => loadNearby( s, 6 ),
			},
		} );

		if ( ! swiperAutoplay ) {
			swiper.autoplay.stop();
		}
	} );
}
