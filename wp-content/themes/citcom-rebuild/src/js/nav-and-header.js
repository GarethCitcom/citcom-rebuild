/**
 * Header behaviour: shrink on scroll, animated nav pill, dropdowns, mobile slide
 * menu and the search input. Port of assets/_dev/js/functions/navAndHeader.js.
 */
import { contentWidth, position, hover, fadeIn, fadeOut } from './fx';

let navUnFocus = true;
let windowWidth;
let headerHeight;

const $$ = ( selector, root = document ) => [
	...root.querySelectorAll( selector ),
];
const $1 = ( selector, root = document ) => root.querySelector( selector );

export function animateCitcomNavPill( activeNavPill ) {
	const pill = $1( '.citcom-nav-pill' );
	if ( ! pill || ! activeNavPill ) {
		return;
	}
	pill.style.width = contentWidth( activeNavPill ) + 'px';
	pill.style.left = position( activeNavPill ).left + 'px';
	pill.style.opacity = '1';
}

function hideDropdown() {
	if ( true === navUnFocus ) {
		$$( '.citcom-dropdown-menu-container' ).forEach( ( el ) =>
			el.classList.remove( 'show' )
		);
	}
}

function activePill() {
	return (
		$1( '.citcom-nav .nav-item.active' ) ||
		$1( '.citcom-nav .nav-item.current_page_parent' )
	);
}

function setHeaderHeight() {
	const header = $1( 'header' );
	headerHeight = header ? header.offsetHeight : 0;
	document.documentElement.style.cssText =
		'--header-height: ' + headerHeight + 'px';
}

function resizeNavbar( activeNav, headerResize = true ) {
	if ( activeNav ) {
		animateCitcomNavPill( activePill() );
	}
	if ( headerResize ) {
		setHeaderHeight();
	}
	windowWidth = document.documentElement.clientWidth;
}

export function navAndHeader() {
	windowWidth = document.documentElement.clientWidth;

	$$( '.citcom-btn' ).forEach( ( btn ) => {
		btn.addEventListener( 'click', () => {
			btn.classList.add( 'active' );
			setTimeout( () => {
				$$( '.citcom-btn' ).forEach( ( b ) =>
					b.classList.remove( 'active' )
				);
			}, 1000 );
		} );
	} );

	const activeNav = $$( '.citcom-nav .nav-item' ).some(
		( item ) =>
			item.classList.contains( 'active' ) ||
			item.classList.contains( 'current_page_parent' )
	);
	const nav = $1( '.citcom-nav' );

	if ( nav ) {
		if ( activeNav ) {
			const activeNavPill = activePill();
			animateCitcomNavPill( activeNavPill );
			nav.addEventListener( 'mouseleave', () =>
				animateCitcomNavPill( activeNavPill )
			);
		} else {
			nav.addEventListener( 'mouseleave', () => {
				const pill = $1( '.citcom-nav-pill' );
				if ( pill ) {
					pill.style.width = '0px';
					pill.style.left = '-50px';
					pill.style.opacity = '0';
				}
			} );
		}
	}
	// jQuery .hover() with one handler ran it on leave as well.
	$$( '.citcom-nav .nav-item' ).forEach( ( item ) =>
		hover( item, () => animateCitcomNavPill( item ) )
	);
	setTimeout( () => {
		$$( '.citcom-nav-pill' ).forEach( ( el ) =>
			el.classList.add( 'animate' )
		);
		$$( '.citcom-nav .nav-link' ).forEach( ( el ) =>
			el.classList.remove( 'temp-active' )
		);
	}, 100 );
	$$( '.citcom-nav .nav-link' ).forEach( ( link ) => {
		link.addEventListener( 'click', () => {
			$$( '.citcom-nav .nav-item' ).forEach( ( el ) =>
				el.classList.remove( 'active' )
			);
			link.parentElement.classList.add( 'active' );
		} );
	} );

	const right = $1( 'header .right' );
	const blur = () => {
		if ( right && windowWidth < 1600 && windowWidth > 1039 ) {
			right.classList.add( 'blur' );
		}
	};
	$$( '.search-citcom' ).forEach( ( search ) => {
		hover( search, () => {
			search.classList.add( 'hover' );
			blur();
		} );
		search.addEventListener( 'mouseleave', () => {
			if ( ! search.classList.contains( 'focus' ) && right ) {
				right.classList.remove( 'blur' );
			}
		} );
	} );
	$$( '.search-input' ).forEach( ( input ) => {
		input.addEventListener( 'focus', () => {
			$$( '.search-citcom' ).forEach( ( el ) =>
				el.classList.add( 'focus' )
			);
			blur();
		} );
		input.addEventListener( 'blur', () => {
			$$( '.search-citcom' ).forEach( ( el ) =>
				el.classList.remove( 'focus', 'hover' )
			);
			if ( right ) {
				right.classList.remove( 'blur' );
			}
		} );
	} );

	// Dropdowns.
	$$( '.citcom-dropdown' ).forEach( ( dropdown ) => {
		hover(
			dropdown,
			() =>
				$$( '.citcom-dropdown-menu-container', dropdown ).forEach(
					( el ) => el.classList.add( 'show' )
				),
			() =>
				$$( '.citcom-dropdown-menu-container', dropdown ).forEach(
					( el ) => el.classList.remove( 'show' )
				)
		);
	} );
	$$( '.citcom-dropdown .toggle-citcom-dropdown' ).forEach( ( toggle ) => {
		toggle.addEventListener( 'focus', () => {
			navUnFocus = true;
			$$(
				'.citcom-dropdown-menu-container',
				toggle.parentElement
			).forEach( ( el ) => el.classList.add( 'show' ) );
		} );
		toggle.addEventListener( 'blur', () => {
			setTimeout( hideDropdown, 600 );
			navUnFocus = true;
		} );
	} );
	$$( '.citcom-dropdown-item' ).forEach( ( item ) => {
		item.addEventListener( 'focus', () => {
			navUnFocus = false;
			item.parentElement.parentElement.classList.add( 'show' );
		} );
		item.addEventListener( 'blur', () => {
			setTimeout( hideDropdown, 600 );
			navUnFocus = true;
		} );
	} );

	// Mobile offcanvas: slide sub-menus in and out.
	const mobileNav = $1( '#mobile-nav' );
	$$( '.mob-slide' ).forEach( ( btn ) => {
		btn.addEventListener( 'click', () => {
			if ( mobileNav ) {
				mobileNav.classList.add( 'slide-open' );
			}
			const menu = btn.nextElementSibling;
			if (
				menu &&
				menu.classList.contains( 'citcom-dropdown-menu-container' )
			) {
				fadeIn( menu );
			}
			$$( '.mob-slide-return' ).forEach( ( el ) => fadeIn( el ) );
		} );
	} );
	$$( '.mob-slide-return' ).forEach( ( btn ) => {
		btn.addEventListener( 'click', () => {
			if ( mobileNav ) {
				mobileNav.classList.remove( 'slide-open' );
				$$( '.citcom-dropdown-menu-container', mobileNav ).forEach(
					( el ) => fadeOut( el )
				);
			}
			$$( '.mob-slide-return' ).forEach( ( el ) => fadeOut( el ) );
		} );
	} );

	setHeaderHeight();

	window.addEventListener( 'resize', () => resizeNavbar( activeNav ) );

	const header = $1( 'header' );
	const progress = $1( '#reading-progress' );
	let scroll = window.scrollY;
	if ( header && scroll > 200 ) {
		header.classList.add( 'shrink' );
	}

	const docHeight =
		Math.max(
			document.documentElement.scrollHeight,
			document.body.scrollHeight
		) - 200;
	const windowHeight = document.documentElement.clientHeight;
	window.addEventListener(
		'scroll',
		() => {
			scroll = window.scrollY;
			if ( header ) {
				header.classList.toggle( 'shrink', scroll > 200 );
				header.classList.toggle( 'shadow-lg', scroll > 200 );
			}
			if ( progress ) {
				progress.classList.toggle(
					'end',
					scroll >= docHeight - windowHeight
				);
			}
		},
		{ passive: true }
	);

	const categoryListing = $1( '.wp-block-categories-list' );
	if ( categoryListing ) {
		categoryListing.insertAdjacentHTML(
			'beforeend',
			'<div class="category-pill"></div>'
		);
	}
}
