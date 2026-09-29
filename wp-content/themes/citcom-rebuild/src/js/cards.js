/**
 * Card hover state (port of assets/_dev/js/functions/cards.js).
 */
const $ = window.jQuery;

export function cards() {
	$( '.card-body' ).hover(
		function () {
			$( this ).addClass( 'card-hover' );
		},
		function () {
			$( this ).removeClass( 'card-hover' );
		}
	);
}
