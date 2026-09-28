/**
 * Height helpers (port of assets/_dev/js/functions/equalHeights.js).
 */
const $ = window.jQuery;

export function equalHeights( equalise ) {
	let highestBox = 0;
	$( equalise ).each( function () {
		if ( $( this ).outerHeight() > highestBox ) {
			highestBox = $( this ).outerHeight();
		}
	} );
	$( equalise ).outerHeight( highestBox );
}

export function equalHeightsWithReset( equalise ) {
	let highestBox = 0;
	$( equalise ).css( 'height', 'auto' );
	$( equalise ).each( function () {
		if ( $( this ).outerHeight() > highestBox ) {
			highestBox = $( this ).outerHeight();
		}
	} );
	$( equalise ).outerHeight( highestBox );
}

export function widthEqualHeight( equalise ) {
	$( equalise ).each( function () {
		$( this ).width( $( this ).outerHeight() );
	} );
}
