/**
 * Display Posts block: load more and live search over admin-ajax.
 * Port of assets/_dev/js/functions/displayPosts.js. The block prints a
 * window.displayPostsQuery object describing the current query.
 */
import AOS from 'aos';
import { cards } from './cards';

function refreshLazy() {
	if ( window.citcomLozad ) {
		window.citcomLozad.observe();
	}
}

/**
 * The nested object as admin-ajax expects it: the way jQuery serialised it, with bracketed keys.
 * @param {Object}          data   Values.
 * @param {URLSearchParams} params Target.
 * @param {string}          prefix Key prefix.
 */
function encode( data, params = new URLSearchParams(), prefix = '' ) {
	Object.entries( data ).forEach( ( [ key, value ] ) => {
		const name = prefix ? `${ prefix }[${ key }]` : key;
		if ( value && 'object' === typeof value ) {
			encode( value, params, name );
		} else if ( undefined !== value && null !== value ) {
			params.append( name, String( value ) );
		}
	} );
	return params;
}

async function post( url, data ) {
	const response = await fetch( url, {
		method: 'POST',
		headers: {
			'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
		},
		body: encode( data ).toString(),
	} );
	return response.json();
}

function searchPosts( val ) {
	const form = document.getElementById( 'search-posts' );
	if (
		form &&
		val.length > 2 &&
		document.documentElement.clientWidth > 767
	) {
		if ( form.requestSubmit ) {
			form.requestSubmit();
		} else {
			form.dispatchEvent( new Event( 'submit', { cancelable: true } ) );
		}
	}
}

function closeFilters() {
	document
		.querySelectorAll( '.close-blog-filters' )
		.forEach( ( el ) => el.click() );
}

export function displayPosts() {
	const ajax = window.citcomAjax || {};

	// Load more posts.
	document.addEventListener( 'click', async ( event ) => {
		const button = event.target.closest( '.citcom_loadmore' );
		if ( ! button || 'undefined' === typeof window.displayPostsQuery ) {
			return;
		}
		const input = document.getElementById( 'search-posts-input' );
		const data = {
			action: 'loadmore',
			displayPostsQuery: window.displayPostsQuery,
			search: input ? input.value : '',
		};
		button.textContent = 'Loading...';
		try {
			const response = await post( ajax.ajaxurl, data );
			if ( response.data && response.data.return ) {
				window.displayPostsQuery = response.data.displayPostsQuery;
				button.textContent = 'More posts';
				button.insertAdjacentHTML(
					'beforebegin',
					response.data.return
				);
				window.displayPostsQuery.cur_page++;
				if (
					window.displayPostsQuery.cur_page ===
					window.displayPostsQuery.max_page
				) {
					button.remove();
				}
				refreshLazy();
				cards();
			} else {
				button.remove();
			}
		} catch {
			button.textContent = 'More posts';
		}
		AOS.refresh();
	} );

	// Search posts.
	const form = document.getElementById( 'search-posts' );
	if ( form ) {
		form.addEventListener( 'submit', async ( e ) => {
			e.preventDefault();
			if ( 'undefined' === typeof window.displayPostsQuery ) {
				return;
			}
			window.displayPostsQuery.cur_page = 1;
			window.displayPostsQuery.max_page = 1;
			window.displayPostsQuery.posts.paged = 1;
			const input = document.getElementById( 'search-posts-input' );
			const btn = document.getElementById( 'search-posts-btn' );
			const row = document.querySelector( '#display-posts .row' );
			const data = {
				action: 'postsearch',
				displayPostsQuery: window.displayPostsQuery,
				search: input ? input.value : '',
			};
			if ( btn ) {
				btn.classList.add( 'load' );
			}
			try {
				const response = await post( ajax.ajaxurl, data );
				if ( response.data && response.data.return ) {
					window.displayPostsQuery = response.data.displayPostsQuery;
					window.displayPostsQuery.max_page = response.data.maxPages;
					if ( row ) {
						row.innerHTML = response.data.return;
						if (
							window.displayPostsQuery.cur_page <
							window.displayPostsQuery.max_page
						) {
							row.insertAdjacentHTML(
								'beforeend',
								'<div class="citcom_loadmore btn btn-outline-secondary rounded-pill px-4 mx-auto mt-5">Load more</div>'
							);
						}
					}
					refreshLazy();
					cards();
					closeFilters();
				} else {
					if ( row ) {
						row.innerHTML = '<p>No results found</p>';
					}
					document
						.querySelectorAll( '.citcom_loadmore' )
						.forEach( ( el ) => el.remove() );
					closeFilters();
				}
			} catch {
				// Leave the list as it was.
			}
			if ( btn ) {
				btn.classList.remove( 'load' );
			}
			AOS.refresh();
		} );
	}

	const searchText = document.getElementById( 'search-posts-input' );
	if ( searchText ) {
		let timeout = null;
		searchText.addEventListener( 'input', () => {
			if ( timeout ) {
				window.clearTimeout( timeout );
			}
			const val = searchText.value;
			timeout = setTimeout( () => searchPosts( val ), 500 );
		} );
	}
}
