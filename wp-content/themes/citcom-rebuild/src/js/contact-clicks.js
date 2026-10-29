/**
 * Phone and email link clicks, reported to GTM as contact_click with the
 * method (phone or email). Separate from the form events in forms.js: a
 * tap on a tel: or mailto: link is a lead signal of its own, with no
 * server confirmation to wait for.
 *
 * One delegated listener, so links added later (a modal, display-posts
 * results) count too. The push is made whether or not GTM has loaded: GTM
 * reads the queue when consent lets it in.
 */
export function contactClicks() {
	document.addEventListener( 'click', ( e ) => {
		const link = e.target.closest
			? e.target.closest( 'a[href^="tel:"], a[href^="mailto:"]' )
			: null;
		if ( ! link ) {
			return;
		}
		window.dataLayer = window.dataLayer || [];
		window.dataLayer.push( {
			event: 'contact_click',
			method: link.getAttribute( 'href' ).startsWith( 'tel:' )
				? 'phone'
				: 'email',
		} );
	} );
}
