<?php
/**
 * Pressidium Cookie Consent settings for the Citcom site (docs/analytics-consent.md).
 *
 * Writes the plugin's settings option in full, so it can be applied the same way
 * on staging and live. Run from the WordPress root with the plugin active:
 *
 *   wp --skip-themes eval-file tools/pressidium-settings.php
 *
 * Applied on staging on 2026-10-27. Not deployed (tools/ is excluded), so copy it
 * over or pipe it in: ssh <site> 'cd ~/htdocs && wp --skip-themes eval-file -' < tools/pressidium-settings.php
 *
 * @package citcom
 */

use Pressidium\WP\CookieConsent\Settings;
use Pressidium\WP\CookieConsent\Options\WP_Options;

$store = new Settings( new WP_Options() );
$s     = $store->get();
$host  = wp_parse_url( home_url(), PHP_URL_HOST );

// The banner erases cookies on its own cookie domain. GA sets _ga on the registrable
// domain, so on staging (a subdomain) that has to be the parent; on live the host is it.
$cookie_domain = 'citcomstaging.mystagingwebsite.com' === $host ? 'mystagingwebsite.com' : $host;

$s['page_scripts']      = true;  // Makes the theme's text/plain GTM loader live on consent.
$s['autoclear_cookies'] = true;  // Erase the listed cookies when a category is withdrawn.
$s['force_consent']     = false;
$s['hide_from_bots']    = true;
$s['reconsent']         = true;
$s['cookie_domain']     = $cookie_domain;

$s['languages']['en']['consent_modal']['title']                 = 'Cookies on this site';
$s['languages']['en']['consent_modal']['description']           = 'We use essential cookies to make this site work and, with your permission, analytics cookies to see how it is used. <button type="button" data-cc="c-settings" class="cc-link">Cookie settings</button>';
$s['languages']['en']['consent_modal']['primary_btn']['text']   = 'Accept all';
$s['languages']['en']['consent_modal']['secondary_btn']['text'] = 'Essential only';

$blocks = &$s['languages']['en']['settings_modal']['blocks'];
foreach ( $blocks as &$block ) {
	$toggle = $block['toggle']['value'] ?? '';
	if ( '' === $toggle && isset( $block['title'] ) && 0 === strpos( $block['title'], 'Cookie usage' ) ) {
		$block['title']       = 'How we use cookies';
		$block['description'] = 'Essential cookies keep the site working. Analytics cookies are only set if you allow them, and you can change your choice at any time from the Cookie Preferences link in the footer.';
	}
	if ( 'necessary' === $toggle ) {
		$block['title']       = 'Essential cookies';
		$block['description'] = 'Needed for the site to work, including remembering your cookie choices. They cannot be switched off here.';
	}
	if ( 'analytics' === $toggle ) {
		$block['title']       = 'Analytics cookies';
		$block['description'] = 'Google Analytics, loaded through Google Tag Manager, so we can see which pages are read and which forms are sent. Nothing is collected until you allow it, and allowing it loads Google Tag Manager on this page.';
	}
	if ( '' === $toggle && isset( $block['title'] ) && 'More information' === $block['title'] ) {
		$block['description'] = 'For any questions about how we use cookies, please contact us.';
	}
}
unset( $block, $blocks );

$s['pressidium_options']['cookie_table']['necessary']   = array(
	array(
		'name'        => 'pressidium_cookie_consent',
		'domain'      => $cookie_domain,
		'expiration'  => '6 months',
		'path'        => '/',
		'description' => 'Remembers your cookie choices.',
		'is_regex'    => false,
	),
);
$s['pressidium_options']['cookie_table']['analytics']   = array(
	array(
		'name'        => '_ga',
		'domain'      => $cookie_domain,
		'expiration'  => '2 years',
		'path'        => '/',
		'description' => 'Google Analytics: tells returning visitors apart.',
		'is_regex'    => false,
	),
	array(
		'name'        => '^_ga_',
		'domain'      => $cookie_domain,
		'expiration'  => '2 years',
		'path'        => '/',
		'description' => 'Google Analytics: keeps the visit together for this property.',
		'is_regex'    => true,
	),
);
$s['pressidium_options']['cookie_table']['targeting']   = array();
$s['pressidium_options']['cookie_table']['preferences'] = array();
$s['pressidium_options']['blocked_scripts']             = array(); // The theme ships GTM inert itself.
$s['pressidium_options']['hide_empty_categories']       = true;
$s['pressidium_options']['floating_button']['enabled']  = false;   // The footer's Cookie Preferences link opens the modal instead (src/js/cookie-preferences.js).
$s['pressidium_options']['gcm']['enabled']              = false;   // Consent Mode off for now.

$ok = $store->set( $s );
$r  = $store->get();
echo wp_json_encode(
	array(
		'saved'             => $ok,
		'host'              => $host,
		'cookie_domain'     => $r['cookie_domain'],
		'page_scripts'      => $r['page_scripts'],
		'autoclear_cookies' => $r['autoclear_cookies'],
		'floating_button'   => $r['pressidium_options']['floating_button']['enabled'],
		'gcm'               => $r['pressidium_options']['gcm']['enabled'],
		'blocked_scripts'   => count( $r['pressidium_options']['blocked_scripts'] ),
		'analytics_cookies' => array_column( $r['pressidium_options']['cookie_table']['analytics'], 'name' ),
	),
	JSON_PRETTY_PRINT
), "\n";
