/**
 * Blog sidebar category pill (port of assets/_dev/js/functions/sidebar.js).
 */
const $ = window.jQuery;

function animateCategoryPill( activeNavPill ) {
	$( '.cat-item a' ).removeClass( 'active' );
	$( '.category-pill' ).height( activeNavPill.height() );
	$( '.category-pill' ).css( 'top', activeNavPill.position().top );
	$( '.category-pill' ).css( 'opacity', '1' );
	activeNavPill.find( 'a' ).addClass( 'active' );
}

export function sidebar() {
	let activeCatNav = false;
	$( '.wp-block-categories-list .cat-item' ).each( function () {
		if ( $( this ).hasClass( 'current-cat' ) ) {
			$( this ).find( 'a' ).addClass( 'active' );
			activeCatNav = true;
		}
	} );

	if ( activeCatNav ) {
		const activeNavPill = $( '.wp-block-categories-list .cat-item.current-cat' );
		animateCategoryPill( activeNavPill );
		$( '.wp-block-categories-list' ).on( 'mouseleave', function () {
			animateCategoryPill( activeNavPill );
		} );
	} else {
		$( '.wp-block-categories-list' ).on( 'mouseleave', function () {
			$( '.category-pill' ).height( '0px' );
			$( '.category-pill' ).css( 'top', '-50px' );
			$( '.category-pill' ).css( 'opacity', '0' );
			$( '.cat-item a' ).removeClass( 'active' );
		} );
	}
	$( '.wp-block-categories-list .cat-item' ).hover( function () {
		animateCategoryPill( $( this ) );
	} );
	setTimeout( function () {
		$( '.category-pill' ).addClass( 'animate' );
		$( '.wp-block-categories-list .cat-item.current-cat' ).removeClass( 'temp-active' );
	}, 100 );

	$( window ).resize( function () {
		if ( activeCatNav ) {
			animateCategoryPill( $( '.wp-block-categories-list .cat-item.current-cat' ) );
		}
	} );
}
