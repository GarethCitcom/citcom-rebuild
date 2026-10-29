/**
 * The footer's "Cookie Preferences" link opens the Pressidium Cookie Consent
 * preferences modal, in place of the plugin's floating button (switched off in
 * tools/pressidium-settings.php).
 *
 * The link is a menu item whose "Menu ID" field is open_preferences_center (the
 * id the old TermsFeed banner used), with href="#". Delegated, so it works for
 * any such link wherever a menu puts it. If the plugin is not on the page, the
 * link is left alone.
 */
export function cookiePreferences() {
	document.addEventListener( 'click', ( e ) => {
		const link = e.target.closest
			? e.target.closest( '#open_preferences_center' )
			: null;
		if ( ! link ) {
			return;
		}
		const cc = window.pressidiumCookieConsent;
		if ( ! cc || typeof cc.showSettings !== 'function' ) {
			return;
		}
		e.preventDefault();
		cc.showSettings( 0 );
	} );
}
