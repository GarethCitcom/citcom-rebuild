/**
 * Header behaviour: shrink on scroll, animated nav pill, dropdowns, mobile slide
 * menu and the search input. Port of assets/_dev/js/functions/navAndHeader.js.
 */
const $ = window.jQuery;

let navUnFocus = true;
let windowWidth;
let headerHeight;

export function animateCitcomNavPill( activeNavPill ) {
	$( '.citcom-nav-pill' ).width( activeNavPill.width() );
	$( '.citcom-nav-pill' ).css( 'left', activeNavPill.position().left );
	$( '.citcom-nav-pill' ).css( 'opacity', '1' );
}

function hideDropdown() {
	if ( navUnFocus === true ) {
		$( '.citcom-dropdown-menu-container' ).removeClass( 'show' );
	}
}

function resizeNavbar( activeNav, headerResize = true ) {
	if ( activeNav ) {
		let activeNavPill = $( '.citcom-nav .nav-item.active' );
		if ( ! activeNavPill.length ) {
			activeNavPill = $( '.citcom-nav .nav-item.current_page_parent' );
		}
		animateCitcomNavPill( activeNavPill );
	}
	if ( headerResize ) {
		headerHeight = $( 'header' ).outerHeight();
		document.documentElement.style.cssText = '--header-height: ' + headerHeight + 'px';
	}
	windowWidth = $( window ).width();
}

export function navAndHeader() {
	windowWidth = $( window ).width();

	$( '.citcom-btn' ).click( function () {
		$( this ).addClass( 'active' );
		setTimeout( function () {
			$( '.citcom-btn' ).removeClass( 'active' );
		}, 1000 );
	} );

	let activeNav = false;
	$( '.citcom-nav .nav-item' ).each( function () {
		if ( $( this ).hasClass( 'active' ) || $( this ).hasClass( 'current_page_parent' ) ) {
			activeNav = true;
		}
	} );

	if ( activeNav ) {
		let activeNavPill = $( '.citcom-nav .nav-item.active' );
		if ( ! activeNavPill.length ) {
			activeNavPill = $( '.citcom-nav .nav-item.current_page_parent' );
		}
		animateCitcomNavPill( activeNavPill );
		$( '.citcom-nav' ).on( 'mouseleave', function () {
			animateCitcomNavPill( activeNavPill );
		} );
	} else {
		$( '.citcom-nav' ).on( 'mouseleave', function () {
			$( '.citcom-nav-pill' ).width( '0px' );
			$( '.citcom-nav-pill' ).css( 'left', '-50px' );
			$( '.citcom-nav-pill' ).css( 'opacity', '0' );
		} );
	}
	$( '.citcom-nav .nav-item' ).hover( function () {
		animateCitcomNavPill( $( this ) );
	} );
	setTimeout( function () {
		$( '.citcom-nav-pill' ).addClass( 'animate' );
		$( '.citcom-nav .nav-link' ).removeClass( 'temp-active' );
	}, 100 );
	$( '.citcom-nav .nav-link' ).click( function () {
		$( '.citcom-nav .nav-item' ).removeClass( 'active' );
		$( this ).parent().addClass( 'active' );
	} );

	$( '.search-citcom' ).hover( function () {
		$( this ).addClass( 'hover' );
		if ( windowWidth < 1600 && windowWidth > 1039 ) {
			$( 'header .right' ).addClass( 'blur' );
		}
	} );

	$( '.search-input' ).focus( function () {
		$( '.search-citcom' ).addClass( 'focus' );
		if ( windowWidth < 1600 && windowWidth > 1039 ) {
			$( 'header .right' ).addClass( 'blur' );
		}
	} );
	$( '.search-input' ).blur( function () {
		$( '.search-citcom' ).removeClass( 'focus' );
		$( '.search-citcom' ).removeClass( 'hover' );
		$( 'header .right' ).removeClass( 'blur' );
	} );

	$( '.search-citcom' ).on( 'mouseleave', function () {
		if ( ! $( this ).hasClass( 'focus' ) ) {
			$( 'header .right' ).removeClass( 'blur' );
		}
	} );

	// Dropdowns.
	$( '.citcom-dropdown' ).hover(
		function () {
			$( this ).find( '.citcom-dropdown-menu-container' ).addClass( 'show' );
		},
		function () {
			$( this ).find( '.citcom-dropdown-menu-container' ).removeClass( 'show' );
		}
	);
	$( '.citcom-dropdown .toggle-citcom-dropdown' ).focus( function () {
		navUnFocus = true;
		$( this ).parent().find( '.citcom-dropdown-menu-container' ).addClass( 'show' );
	} );
	$( '.citcom-dropdown .toggle-citcom-dropdown' ).blur( function () {
		setTimeout( hideDropdown, 600 );
		navUnFocus = true;
	} );
	$( '.citcom-dropdown-item' ).focus( function () {
		navUnFocus = false;
		$( this ).parent().parent().addClass( 'show' );
	} );
	$( '.citcom-dropdown-item' ).blur( function () {
		setTimeout( hideDropdown, 600 );
		navUnFocus = true;
	} );

	// Mobile offcanvas: slide sub-menus in and out.
	$( '.mob-slide' ).click( function () {
		$( '#mobile-nav' ).addClass( 'slide-open' );
		$( this ).next( '.citcom-dropdown-menu-container' ).fadeIn();
		$( '.mob-slide-return' ).fadeIn();
	} );
	$( '.mob-slide-return' ).click( function () {
		$( '#mobile-nav' ).removeClass( 'slide-open' );
		$( '#mobile-nav .citcom-dropdown-menu-container' ).fadeOut();
		$( '.mob-slide-return' ).fadeOut();
	} );

	headerHeight = $( 'header' ).outerHeight();
	document.documentElement.style.cssText = '--header-height: ' + headerHeight + 'px';

	$( window ).resize( function () {
		resizeNavbar( activeNav );
	} );

	let scroll = $( window ).scrollTop();
	if ( scroll > 200 ) {
		$( 'header' ).addClass( 'shrink' );
	}

	const docHeight = $( document ).height() - 200;
	const windowHeight = $( window ).height();
	$( window ).scroll( function () {
		scroll = $( window ).scrollTop();
		if ( scroll > 200 ) {
			$( 'header' ).addClass( 'shrink' );
			$( 'header' ).addClass( 'shadow-lg' );
		} else {
			$( 'header' ).removeClass( 'shrink' );
			$( 'header' ).removeClass( 'shadow-lg' );
		}
		if ( scroll >= docHeight - windowHeight ) {
			$( '#reading-progress' ).addClass( 'end' );
		} else {
			$( '#reading-progress' ).removeClass( 'end' );
		}
	} );

	const categoryListing = $( '.wp-block-categories-list' );
	if ( categoryListing.length ) {
		categoryListing.append( '<div class="category-pill"></div>' );
	}
}
