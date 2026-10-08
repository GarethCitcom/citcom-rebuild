/**
 * Blog sidebar category pill (port of assets/_dev/js/functions/sidebar.js).
 */
import { contentHeight, position, hover } from './fx';

const $$ = ( selector ) => [ ...document.querySelectorAll( selector ) ];

function animateCategoryPill( activeNavPill ) {
	const pill = document.querySelector( '.category-pill' );
	if ( ! pill || ! activeNavPill ) {
		return;
	}
	$$( '.cat-item a' ).forEach( ( a ) => a.classList.remove( 'active' ) );
	pill.style.height = contentHeight( activeNavPill ) + 'px';
	pill.style.top = position( activeNavPill ).top + 'px';
	pill.style.opacity = '1';
	activeNavPill
		.querySelectorAll( 'a' )
		.forEach( ( a ) => a.classList.add( 'active' ) );
}

export function sidebar() {
	const list = document.querySelector( '.wp-block-categories-list' );
	if ( ! list ) {
		return;
	}
	let activeCatNav = false;
	$$( '.wp-block-categories-list .cat-item' ).forEach( ( item ) => {
		if ( item.classList.contains( 'current-cat' ) ) {
			item.querySelectorAll( 'a' ).forEach( ( a ) =>
				a.classList.add( 'active' )
			);
			activeCatNav = true;
		}
	} );

	const current = () =>
		document.querySelector(
			'.wp-block-categories-list .cat-item.current-cat'
		);
	if ( activeCatNav ) {
		const activeNavPill = current();
		animateCategoryPill( activeNavPill );
		list.addEventListener( 'mouseleave', () =>
			animateCategoryPill( activeNavPill )
		);
	} else {
		list.addEventListener( 'mouseleave', () => {
			const pill = document.querySelector( '.category-pill' );
			if ( pill ) {
				pill.style.height = '0px';
				pill.style.top = '-50px';
				pill.style.opacity = '0';
			}
			$$( '.cat-item a' ).forEach( ( a ) =>
				a.classList.remove( 'active' )
			);
		} );
	}
	$$( '.wp-block-categories-list .cat-item' ).forEach( ( item ) =>
		hover( item, () => animateCategoryPill( item ) )
	);
	setTimeout( () => {
		$$( '.category-pill' ).forEach( ( el ) =>
			el.classList.add( 'animate' )
		);
		$$( '.wp-block-categories-list .cat-item.current-cat' ).forEach(
			( el ) => el.classList.remove( 'temp-active' )
		);
	}, 100 );

	window.addEventListener( 'resize', () => {
		if ( activeCatNav ) {
			animateCategoryPill( current() );
		}
	} );
}
