/**
 * Theme front-end script: port of assets/_dev/js/main.js and its function modules.
 *
 * No jQuery (removed in Phase 4, see docs/jquery-usage.md). Bootstrap is cut to
 * the three components the templates use. The carousel and video players are
 * separate chunks, fetched only on pages that have one.
 */
import AOS from 'aos';
import lozad from 'lozad';
import Modal from 'bootstrap/js/dist/modal';
import Offcanvas from 'bootstrap/js/dist/offcanvas';
import Tooltip from 'bootstrap/js/dist/tooltip';

import './scss/theme.scss';

import { ready, hover, slideDown, slideUp, contentWidth } from './js/fx';
import { widthEqualHeight, equalHeightsWithReset } from './js/equal-heights';
import { navAndHeader } from './js/nav-and-header';
import { cards } from './js/cards';
import { sidebar } from './js/sidebar';
import { displayPosts } from './js/display-posts';
import { forms } from './js/forms';

// Bootstrap is exposed for the inline tooltip/modal calls the templates make.
window.bootstrap = { Modal, Offcanvas, Tooltip };

// Lazy loading for elements with the .lozad class; shared with display-posts.
const observer = lozad();
observer.observe();
window.citcomLozad = observer;

AOS.init( {
	disable: 'phone',
	once: true,
	duration: 1200,
	offset: 140,
	easing: 'cubic-bezier(0.25, 1, 0.5, 1)',
} );

window.mobileCheck = function () {
	let check = false;
	( function ( a ) {
		if (
			/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i.test(
				a
			) ||
			/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(
				a.substr( 0, 4 )
			)
		) {
			check = true;
		}
	} )( navigator.userAgent || navigator.vendor || window.opera );
	return check;
};

const $$ = ( selector ) => [ ...document.querySelectorAll( selector ) ];

ready( () => {
	// Services archive: highlight the sub-service link for the row in view.
	const rows = $$( '.service-row' ).map( ( row ) => {
		const cs = window.getComputedStyle( row );
		const rect = row.getBoundingClientRect();
		const topDist = Math.floor( rect.top + window.scrollY ) - 20;
		const outer = Math.floor(
			rect.height +
				parseFloat( cs.marginTop ) +
				parseFloat( cs.marginBottom )
		);
		return { id: row.id, topDist, bottomDist: topDist + outer };
	} );
	const highlightRow = () => {
		const docTop = window.scrollY;
		rows.forEach( ( row ) => {
			if ( docTop > row.topDist && docTop < row.bottomDist ) {
				$$( '.sub-link' ).forEach( ( link ) =>
					link.classList.remove( 'active' )
				);
				const link = document.getElementById( 'menu-' + row.id );
				if ( link ) {
					link.classList.add( 'active' );
				}
			}
		} );
	};
	if ( rows.length ) {
		highlightRow();
		window.addEventListener( 'scroll', highlightRow, { passive: true } );
	}

	if ( window.mobileCheck() ) {
		document.documentElement.classList.add( 'no-animation' );
	}

	const sizeButtons = () => {
		widthEqualHeight( '.citcom-btn-bg' );
		widthEqualHeight( '.icon-btn-bg' );
		equalHeightsWithReset( '.citdot-card' );
	};
	sizeButtons();
	window.addEventListener( 'resize', sizeButtons );

	$$( '.stretched-link' ).forEach( ( link ) => {
		hover(
			link,
			() => link.parentElement.classList.add( 'stretch-hover' ),
			() => link.parentElement.classList.remove( 'stretch-hover' )
		);
	} );

	$$( '.wp-block-quote' ).forEach( ( quote ) => {
		const container = document.createElement( 'div' );
		container.className = 'quote-container';
		quote.parentNode.insertBefore( container, quote );
		container.appendChild( quote );
		quote.insertAdjacentHTML( 'afterend', '<div class="speech"></div>' );
	} );

	navAndHeader();
	cards();
	displayPosts();
	forms();

	if ( document.querySelector( '.vlite, .wp-block-video' ) ) {
		import( './js/vlite' ).then( ( m ) => m.videoLite() );
	}
	if ( document.querySelector( '.swiper' ) ) {
		import( './js/swiper' ).then( ( m ) => m.swiperSetup() );
	}
	if ( document.querySelector( '.wp-block-categories-list' ) ) {
		sidebar();
	}

	AOS.refresh();

	// Services showcase (home page): open the hovered service panel.
	const showcaseText = $$( '.showcase-service-text' );
	if ( showcaseText.length ) {
		const openText = () =>
			document.querySelector(
				'.showcase .service.open .showcase-service-text'
			);
		const setWidth = () => {
			const open = openText();
			const width = open ? contentWidth( open ) : 0;
			showcaseText.forEach( ( el ) => {
				el.style.width = width + 'px';
			} );
		};
		setWidth();
		window.addEventListener( 'resize', () => {
			showcaseText.forEach( ( el ) => {
				el.style.width = 'auto';
			} );
			setWidth();
		} );
		let timeOut;
		$$( '.showcase .service' ).forEach( ( el ) => {
			// jQuery .hover() with one handler ran it on leave as well.
			hover( el, () => {
				if ( ! el.classList.contains( 'active' ) ) {
					clearTimeout( timeOut );
					$$( '.service' ).forEach( ( s ) =>
						s.classList.remove( 'open', 'active' )
					);
					el.classList.add( 'open' );
					timeOut = setTimeout(
						() => el.classList.add( 'active' ),
						300
					);
				}
			} );
		} );
	}

	// Staff cards (about page): reveal the description on hover.
	$$( '.citdot-card.staff' ).forEach( ( card ) => {
		const descriptions = [
			...card.querySelectorAll( '.profile-info .description' ),
		];
		card.addEventListener( 'mouseenter', () =>
			descriptions.forEach( ( d ) => slideDown( d ) )
		);
		card.addEventListener( 'mouseleave', () =>
			descriptions.forEach( ( d ) => slideUp( d ) )
		);
	} );
} );

$$( '[data-bs-toggle="tooltip"]' ).forEach( ( el ) => new Tooltip( el ) );
