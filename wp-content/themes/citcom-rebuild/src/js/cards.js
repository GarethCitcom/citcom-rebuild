/**
 * Card hover state (port of assets/_dev/js/functions/cards.js).
 * Safe to call again after cards are added: a card is only bound once.
 */
import { hover } from './fx';

export function cards() {
	document
		.querySelectorAll( '.card-body:not([data-cards])' )
		.forEach( ( body ) => {
			body.dataset.cards = '1';
			hover(
				body,
				() => body.classList.add( 'card-hover' ),
				() => body.classList.remove( 'card-hover' )
			);
		} );
}
