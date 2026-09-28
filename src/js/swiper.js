/**
 * Swiper carousels (port of assets/_dev/js/functions/swiper.js).
 */
import Swiper from 'swiper/bundle';

const $ = window.jQuery;

export function swiperSetup() {
	$( '.swiper-gallery' ).each( function () {
		const swiperID = $( this ).attr( 'id' );
		new Swiper( '#' + swiperID, {
			pagination: {
				el: '.swiper-pagination',
				clickable: true,
			},
		} );
	} );

	$( '.swiper-swiper' ).each( function () {
		const swiperID = $( this ).attr( 'id' );
		const swiperLoop = $( this ).data( 'loop' );
		const swiperAutoplay = $( this ).data( 'autoplay' );
		const swiperDelay = $( this ).data( 'delay' );
		const swiperSpeed = $( this ).data( 'speed' );
		const swiperCenter = $( this ).data( 'center' );
		const swiperReverseDirection = $( this ).data( 'reversedirection' );
		const swiperFreeMode = ! ( swiperDelay > 0 );

		const swiper = new Swiper( '#' + swiperID, {
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
			lazy: false,
			pagination: {
				el: '.swiper-pagination',
			},
			navigation: {
				nextEl: '.swiper-button-next',
				prevEl: '.swiper-button-prev',
			},
			scrollbar: {
				el: '.swiper-scrollbar',
			},
		} );

		if ( ! swiperAutoplay ) {
			swiper.autoplay.stop();
		}
	} );
}
