/**
 * vlite video players (port of assets/_dev/js/functions/vlite.js), loaded only
 * on pages with a video.
 */
import Vlitejs from 'vlitejs';
import VlitejsYoutube from 'vlitejs/providers/youtube.js';
import 'vlitejs/vlite.css';
import { fadeIn } from './fx';

Vlitejs.registerProvider( 'youtube', VlitejsYoutube );

function options( el ) {
	try {
		return el.dataset.options ? JSON.parse( el.dataset.options ) : {};
	} catch {
		return {};
	}
}

export function videoLite() {
	document.querySelectorAll( '.vlite' ).forEach( ( el ) => {
		const vidID = el.id;
		const containerId = vidID.replace( 'player', 'video' );
		const youTube = el.dataset.youtubeId;
		new Vlitejs( '#' + vidID, {
			options: options( el ),
			provider: youTube ? 'youtube' : 'html5',
			onReady( player ) {
				const container = document.getElementById( containerId );
				const target = document.getElementById( vidID );
				const volume = document.getElementById( vidID + '-volume' );
				setTimeout(
					() =>
						container &&
						container.classList.add( 'v-vlite-lazy-loaded' ),
					600
				);
				if ( target ) {
					fadeIn( target, 300 );
				}
				setTimeout(
					() =>
						container &&
						container.classList.remove( 'v-vlite-lazy' ),
					900
				);
				if ( volume ) {
					volume.classList.add( 'active' );
					volume.addEventListener( 'click', () => {
						if ( volume.classList.contains( 'unmute' ) ) {
							player.unMute();
							volume.classList.replace( 'unmute', 'mute' );
						} else if ( volume.classList.contains( 'mute' ) ) {
							player.mute();
							volume.classList.replace( 'mute', 'unmute' );
						}
					} );
				}
			},
		} );
	} );

	let ranVidID = 1;
	document.querySelectorAll( '.wp-block-video' ).forEach( ( block ) => {
		const theVideo = block.querySelector( 'video' );
		if ( ! theVideo ) {
			return;
		}
		const vidID = 'vlite-' + ranVidID;
		theVideo.id = vidID;
		new Vlitejs( '#' + vidID, {
			options: {},
			provider: 'html5',
			onReady() {
				const target = document.getElementById( vidID );
				if ( target ) {
					fadeIn( target, 300 );
				}
			},
		} );
		ranVidID++;
	} );
}
