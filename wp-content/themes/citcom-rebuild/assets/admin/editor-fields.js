/**
 * Block editor fixes for third-party ACF field types.
 *
 * acf-swatch colours its toggles on the ACF 5 "ready/append" actions, which do
 * not fire for fields rendered in the block sidebar or the expanded editor.
 * Colour them here on the ACF 6 field lifecycle instead.
 */
( function ( $ ) {
	if ( typeof acf === 'undefined' || ! acf.addAction ) {
		return;
	}

	function colourSwatches( field ) {
		field.$( 'ul.acf-swatch-list input' ).each( function () {
			var value = $( this ).val();
			var colour = value === 'none' || value === '' ? 'transparent' : value;
			$( this ).siblings( '.swatch-toggle' ).children( '.swatch-color' ).css( 'background-color', colour );
		} );
	}

	acf.addAction( 'new_field/type=swatch', colourSwatches );
	acf.addAction( 'show_field/type=swatch', colourSwatches );
} )( jQuery );
