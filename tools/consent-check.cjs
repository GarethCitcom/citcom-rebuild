/**
 * The consent checklist of docs/analytics-consent.md, run in the installed Chrome
 * against staging:
 *
 *   HEADFUL=1 node tools/consent-check.cjs
 *
 * Headful, because Pressable serves a JavaScript challenge to a browser whose
 * user agent does not match its fingerprint, and Pressidium hides the banner
 * from any browser that reports navigator.webdriver (masked below). It sends
 * one real contact form entry, named so it can be found and deleted. Tag
 * Assistant and GA4 Realtime are still checked by eye.
 */
const { chromium } = require( 'playwright' );

const BASE = 'https://citcomstaging.mystagingwebsite.com';
const PAGES = {
	post: '/blog/events/exhibition-stand-guide',
	service: '/services/creative/',
	contact: '/contact-us/',
};
const UA = process.env.HEADFUL ? undefined : 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36';
const results = [];
const ok = ( name, pass, detail = '' ) => {
	results.push( { name, pass, detail } );
	console.log( ( pass ? 'PASS ' : 'FAIL ' ) + name + ( detail ? '  [' + detail + ']' : '' ) );
};
// Pressable answers a new browser with a JavaScript proof-of-work page (HASHCASH_CONFIG) that then reloads.
async function go( page, url ) {
	let resp = await page.goto( url, { waitUntil: "domcontentloaded" } );
	for ( let i = 0; i < 3; i++ ) {
		const challenged = await page.evaluate( () => !! window.HASHCASH_CONFIG ).catch( () => false );
		if ( ! challenged ) { break; }
		console.log( "INFO challenge page, waiting for it to clear" );
		resp = await page.waitForNavigation( { waitUntil: "domcontentloaded", timeout: 60000 } ).catch( () => resp );
	}
	await page.waitForLoadState( "networkidle" ).catch( () => null );
	return resp;
}
const sleep = ( ms ) => new Promise( ( r ) => setTimeout( r, ms ) );

function watch( page ) {
	const log = { gtm: 0, pageViews: 0, events: [] };
	page.on( 'request', ( req ) => {
		const url = req.url();
		if ( url.includes( 'googletagmanager.com/gtm.js' ) ) {
			log.gtm++;
		}
		if ( url.includes( '/g/collect' ) ) {
			const body = req.postData() || '';
			const chunks = [ url.split( '?' )[ 1 ] || '', ...body.split( '\n' ) ];
			for ( const c of chunks ) {
				const m = c.match( /(?:^|&)en=([^&]+)/ );
				if ( m ) {
					log.events.push( m[ 1 ] );
					if ( m[ 1 ] === 'page_view' ) {
						log.pageViews++;
					}
				}
			}
		}
	} );
	return log;
}
const gaCookies = async ( ctx ) => ( await ctx.cookies() ).filter( ( c ) => /^_ga/.test( c.name ) ).map( ( c ) => c.name );
const bannerVisible = ( page ) => page.locator( '#cm' ).isVisible().catch( () => false );
const dl = ( page, ev ) => page.evaluate( ( e ) => ( window.dataLayer || [] ).filter( ( x ) => x && x.event === e ), ev );

( async () => {
	const browser = await chromium.launch( { channel: process.env.CH || "chrome", headless: process.env.HEADFUL ? false : true } );
	const _nc = browser.newContext.bind( browser );
	browser.newContext = async ( o ) => { const c = await _nc( o ); await c.addInitScript( () => Object.defineProperty( navigator, "webdriver", { get: () => false } ) ); return c; };

	// 1. Fresh visits, desktop: banner, no gtm.js, no _ga, no noscript iframe.
	for ( const [ label, path ] of Object.entries( PAGES ) ) {
		const ctx = await browser.newContext( { userAgent: UA, viewport: { width: 1366, height: 800 } } );
		const page = await ctx.newPage();
		const log = watch( page );
		const resp = await go( page, BASE + path );
		await sleep( 3000 );
		const html = await page.content();
		ok( `fresh desktop ${ label }: page loads`, resp.status() === 200, 'status ' + resp.status() );
		ok( `fresh desktop ${ label }: banner shown`, await bannerVisible( page ) );
		ok( `fresh desktop ${ label }: no gtm.js`, log.gtm === 0, 'gtm.js requests ' + log.gtm );
		ok( `fresh desktop ${ label }: no _ga cookies`, ( await gaCookies( ctx ) ).length === 0 );
		ok( `fresh desktop ${ label }: no noscript iframe`, ! html.includes( 'ns.html?id=GTM' ) );
		ok( `fresh desktop ${ label }: loader inert`, html.includes( 'data-cookiecategory="analytics"' ) );
		await ctx.close();
		await sleep( 2000 );
	}

	// 2. Fresh visit, mobile.
	{
		// Phone viewport with the real user agent: a spoofed mobile UA never clears Pressable's challenge.
		const ctx = await browser.newContext( { viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true } );
		const page = await ctx.newPage();
		const log = watch( page );
		await go( page, BASE + PAGES.post );
		await sleep( 3000 );
		ok( 'fresh mobile post: banner shown', await page.waitForSelector( '#cm', { state: 'visible', timeout: 15000 } ).then( () => true ).catch( () => false ) );
		ok( 'fresh mobile post: no gtm.js', log.gtm === 0, 'gtm.js requests ' + log.gtm );
		ok( 'fresh mobile post: no _ga cookies', ( await gaCookies( ctx ) ).length === 0 );
		await ctx.close();
		await sleep( 2000 );
	}

	// 3. Accept on the contact page, then a returning visit, then withdraw.
	{
		const ctx = await browser.newContext( { userAgent: UA, viewport: { width: 1366, height: 800 } } );
		const page = await ctx.newPage();
		const log = watch( page );
		await go( page, BASE + PAGES.contact );
		await sleep( 2000 );
		await page.click( '#c-p-bn' );
		await page.waitForRequest( ( r ) => r.url().includes( 'gtm.js' ), { timeout: 15000 } ).catch( () => null );
		await sleep( 8000 );
		ok( 'accept: gtm.js loads on the same page', log.gtm === 1, 'gtm.js requests ' + log.gtm );
		ok( 'accept: banner hidden', ! ( await bannerVisible( page ) ) );
		ok( 'accept: loader became live', await page.evaluate( () => !! document.querySelector( 'script[src*="gtm.js?id=GTM-KQQPQS8"]' ) ) );
		ok( 'accept: GA4 sends one page_view', log.pageViews === 1, 'page_view ' + log.pageViews + ', events ' + log.events.join( ',' ) );
		ok( 'accept: _ga cookies set', ( await gaCookies( ctx ) ).length >= 1, ( await gaCookies( ctx ) ).join( ',' ) );

		// Returning visit with saved consent.
		const page2 = await ctx.newPage();
		const log2 = watch( page2 );
		await go( page2, BASE + PAGES.service );
		await sleep( 8000 );
		ok( 'returning: no banner', ! ( await bannerVisible( page2 ) ) );
		ok( 'returning: gtm.js loads straight away', log2.gtm === 1, 'gtm.js requests ' + log2.gtm );
		ok( 'returning: one page_view', log2.pageViews === 1, 'page_view ' + log2.pageViews );

		// Withdraw: settings modal, reject all.
		await page2.evaluate( () => window.pressidiumCookieConsent.showSettings( 0 ) );
		await sleep( 1000 );
		await page2.click( '#s-rall-bn' );
		console.log( "INFO cookies right after reject: " + ( await page2.evaluate( () => document.cookie ) ).replace( /=[^;]*/g, "" ) + " | analytics allowed: " + ( await page2.evaluate( () => window.pressidiumCookieConsent.allowedCategory( "analytics" ) ) ) );
		await sleep( 3000 );
		console.log( "INFO cookies 3s after reject: " + ( await page2.evaluate( () => document.cookie ) ).replace( /=[^;]*/g, "" ) );
		const after = await gaCookies( ctx );
		console.log( "INFO cookies after withdraw: " + ( await ctx.cookies() ).map( ( c ) => c.name + "@" + c.domain ).join( ", " ) );
		ok( 'withdraw: _ga cookies erased', after.length === 0, 'left: ' + after.join( ',' ) );
		const page3 = await ctx.newPage();
		const log3 = watch( page3 );
		await go( page3, BASE + PAGES.post );
		await sleep( 5000 );
		ok( 'withdraw: no gtm.js on later pages', log3.gtm === 0, 'gtm.js requests ' + log3.gtm );
		ok( 'withdraw: no banner nag', ! ( await bannerVisible( page3 ) ) );
		ok( 'withdraw: still no _ga', ( await gaCookies( ctx ) ).length === 0 );
		await ctx.close();
		await sleep( 2000 );
	}

	// 4. Contact form: validation error, then a real submit, with consent given. Then a phone link.
	{
		const ctx = await browser.newContext( { userAgent: UA, viewport: { width: 1366, height: 900 } } );
		const page = await ctx.newPage();
		const log = watch( page );
		await go( page, BASE + PAGES.contact );
		await sleep( 2000 );
		await page.click( '#c-p-bn' );
		await page.waitForRequest( ( r ) => r.url().includes( 'gtm.js' ), { timeout: 15000 } ).catch( () => null );
		await sleep( 4000 );
		const form = page.locator( 'form[data-citcom-form="contact"]' );
		page.on( "response", async ( r ) => { if ( r.url().includes( "/wp-json/citcom/v1/forms/" ) ) { console.log( "INFO form POST " + r.status() + " " + ( await r.text().catch( () => "" ) ).slice( 0, 200 ) ); } } );
		await form.locator( 'button[type="submit"]' ).click();
		await sleep( 4000 );
		ok( 'form: validation error shows no success', ( await dl( page, 'formsuccess' ) ).length === 0 );
		ok( 'form: validation error pushes formfailed', ( await dl( page, 'formfailed' ) ).length === 1 );
		ok( 'form: no generate_lead on error', ! log.events.includes( 'generate_lead' ), 'events ' + log.events.join( ',' ) );

		await form.locator( 'input[name="name"]' ).fill( 'Analytics test (automated)' );
		await form.locator( 'input[name="email"]' ).fill( 'analytics-test@example.com' );
		await form.locator( 'textarea[name="message"]' ).fill( 'Automated test of the consent and analytics setup. Please ignore.' );
		await sleep( 4000 );
		const eventsBefore = log.events.length;
		await form.locator( 'button[type="submit"]' ).click();
		await page.waitForSelector( 'form[data-citcom-form="contact"] .citcom-form-message, form[data-citcom-form="contact"] [role="status"], form[data-citcom-form="contact"] [role="alert"]', { timeout: 20000 } ).catch( () => null );
		await sleep( 6000 );
		const msg = await form.evaluate( ( f ) => ( f.querySelector( '.citcom-form-message, [role="status"], [role="alert"]' ) || {} ).textContent || '' );
		const successes = await dl( page, 'formsuccess' );
		ok( 'form: success message shown', /thank|received|sent|touch/i.test( msg ), msg.trim().slice( 0, 80 ) );
		ok( 'form: formsuccess once with form=contact', successes.length === 1 && successes[ 0 ].form === 'contact', JSON.stringify( successes ) );
		ok( 'form: formfailed not pushed again', ( await dl( page, 'formfailed' ) ).length === 1 );
		const leadHits = log.events.slice( eventsBefore ).filter( ( e ) => e === 'generate_lead' ).length;
		console.log( 'INFO generate_lead hits after success: ' + leadHits + ' (needs the GTM trigger; events seen: ' + log.events.slice( eventsBefore ).join( ',' ) + ')' );

		// Phone / email link.
		await page.evaluate( () => document.addEventListener( 'click', ( e ) => { if ( e.target.closest( 'a[href^="tel:"], a[href^="mailto:"]' ) ) { e.preventDefault(); } }, true ) );
		const tel = page.locator( 'a[href^="tel:"]' ).first();
		const hasTel = ( await tel.count() ) > 0;
		if ( hasTel ) {
			await tel.scrollIntoViewIfNeeded();
			await tel.click( { force: true } );
			await sleep( 1000 );
			const clicks = await dl( page, 'contact_click' );
			ok( 'contact_click: phone link pushes once', clicks.length === 1 && clicks[ 0 ].method === 'phone', JSON.stringify( clicks ) );
		} else {
			ok( 'contact_click: a tel: link exists on /contact-us/', false, 'none found' );
		}
		await ctx.close();
	}

	await browser.close();
	const failed = results.filter( ( r ) => ! r.pass ).length;
	console.log( `\n${ results.length - failed }/${ results.length } checks passed` );
	process.exit( failed ? 1 : 0 );
} )().catch( ( e ) => { console.error( 'ERROR', e ); process.exit( 2 ); } );
