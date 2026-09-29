/**
 * Display Posts block: load more and live search over admin-ajax.
 * Port of assets/_dev/js/functions/displayPosts.js. The block prints a
 * window.displayPostsQuery object describing the current query.
 */
import AOS from 'aos';
import { cards } from './cards';

const $ = window.jQuery;

function refreshLazy() {
	if ( window.citcomLozad ) {
		window.citcomLozad.observe();
	}
}

function searchPosts( val ) {
	const windowWidth = $( window ).width();
	if ( val.length > 2 && windowWidth > 767 ) {
		$( '#search-posts' ).trigger( 'submit' );
	}
}

export function displayPosts() {
	const ajax = window.citcomAjax || {};

	// Load more posts.
	$( document ).on( 'click', '.citcom_loadmore', function () {
		if ( typeof window.displayPostsQuery === 'undefined' ) {
			return;
		}
		let search = '';
		if ( $( '#search-posts-input' ).length ) {
			search = $( '#search-posts-input' ).val();
		}

		const button = $( this );
		const data = {
			action: 'loadmore',
			displayPostsQuery: window.displayPostsQuery,
			search,
		};

		$.ajax( {
			url: ajax.ajaxurl,
			data,
			type: 'POST',
			beforeSend() {
				button.text( 'Loading...' );
			},
			success( response ) {
				if ( response.data.return ) {
					window.displayPostsQuery = response.data.displayPostsQuery;
					button.text( 'More posts' ).before( response.data.return );
					window.displayPostsQuery.cur_page++;

					if ( window.displayPostsQuery.cur_page === window.displayPostsQuery.max_page ) {
						button.remove();
					}
					refreshLazy();
					cards();
				} else {
					button.remove();
				}
				AOS.refresh();
			},
		} );
	} );

	// Search posts.
	$( '#search-posts' ).submit( function ( e ) {
		e.preventDefault();
		if ( typeof window.displayPostsQuery === 'undefined' ) {
			return;
		}
		window.displayPostsQuery.cur_page = 1;
		window.displayPostsQuery.max_page = 1;
		window.displayPostsQuery.posts.paged = 1;
		const search = $( '#search-posts-input' ).val();
		const data = {
			action: 'postsearch',
			displayPostsQuery: window.displayPostsQuery,
			search,
		};
		$.ajax( {
			url: ajax.ajaxurl,
			data,
			type: 'POST',
			beforeSend() {
				$( '#search-posts-btn' ).addClass( 'load' );
			},
			success( response ) {
				if ( response.data.return ) {
					window.displayPostsQuery = response.data.displayPostsQuery;
					window.displayPostsQuery.max_page = response.data.maxPages;
					$( '#display-posts .row' ).html( response.data.return );
					if ( window.displayPostsQuery.cur_page < window.displayPostsQuery.max_page ) {
						$( '#display-posts .row' ).append( '<div class="citcom_loadmore btn btn-outline-secondary rounded-pill px-4 mx-auto mt-5">Load more</div>' );
					}
					refreshLazy();
					cards();
					$( '.close-blog-filters' ).trigger( 'click' );
				} else {
					$( '#display-posts .row' ).html( '<p>No results found</p>' );
					$( '.citcom_loadmore' ).remove();
					$( '.close-blog-filters' ).trigger( 'click' );
				}
				$( '#search-posts-btn' ).removeClass( 'load' );
				AOS.refresh();
			},
		} );
	} );

	const searchText = document.getElementById( 'search-posts-input' );
	if ( searchText ) {
		let timeout = null;
		searchText.addEventListener( 'input', () => {
			if ( timeout ) {
				window.clearTimeout( timeout );
			}
			const val = searchText.value;
			timeout = setTimeout( function () {
				searchPosts( val );
			}, 500 );
		} );
	}
}
