/**
 * Make jQuery touch handlers passive (was assets/_dev/js/plugins/passiveEventListeners.js).
 */
const $ = window.jQuery;

if ( $ && $.event ) {
	[ 'touchstart', 'touchend', 'touchmove' ].forEach( ( type ) => {
		$.event.special[ type ] = {
			setup( _, ns, handle ) {
				this.addEventListener( type, handle, {
					passive: ! ns.includes( 'noPreventDefault' ),
				} );
			},
		};
	} );
}
