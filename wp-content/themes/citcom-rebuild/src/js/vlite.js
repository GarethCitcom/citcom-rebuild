/**
 * vlite video players (port of assets/_dev/js/functions/vlite.js).
 */
import Vlitejs from 'vlitejs';

const $ = window.jQuery;

export function videoLite() {
	$( '.vlite' ).each( function () {
		const vidID = $( this ).attr( 'id' );
		const containerId = vidID.replace( 'player', 'video' );
		const playerOptions = $( this ).data( 'options' );
		const youTube = $( this ).data( 'youtube-id' );
		const providerPlayer = youTube ? 'youtube' : 'html5';
		new Vlitejs( '#' + vidID, {
			options: playerOptions,
			provider: providerPlayer,
			onReady( player ) {
				$( '#' + containerId )
					.delay( 600 )
					.queue( 'fx', function () {
						$( this ).addClass( 'v-vlite-lazy-loaded' );
					} );
				$( '#' + vidID ).fadeIn( 300 );
				setTimeout( function () {
					$( '#' + containerId ).removeClass( 'v-vlite-lazy' );
				}, 900 );
				$( '#' + vidID + '-volume' ).addClass( 'active' );
				$( document ).on( 'click', '#' + vidID + '-volume', function () {
					const volume = $( '#' + vidID + '-volume' );
					if ( volume.hasClass( 'unmute' ) ) {
						player.unMute();
						volume.removeClass( 'unmute' ).addClass( 'mute' );
					} else if ( volume.hasClass( 'mute' ) ) {
						player.mute();
						volume.removeClass( 'mute' ).addClass( 'unmute' );
					}
				} );
			},
		} );
	} );

	let ranVidID = 1;
	$( '.wp-block-video' ).each( function () {
		const theVideo = $( this ).find( 'video' );
		const vidID = 'vlite-' + ranVidID;
		theVideo.attr( 'id', vidID );
		new Vlitejs( '#' + vidID, {
			options: {},
			provider: 'html5',
			onReady() {
				$( '#' + vidID ).fadeIn( 300 );
			},
		} );
		ranVidID++;
	} );
}
