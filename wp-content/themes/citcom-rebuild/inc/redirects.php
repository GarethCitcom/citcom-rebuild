<?php
/**
 * Redirects from old addresses, entered in Site Settings > Redirects.
 *
 * SmartCrawl kept the site's redirects; SEOPress only does in its paid
 * plugin (docs/04-seopress.md). There are about ten, so they are a repeater
 * on the options page and one comparison per request.
 *
 * @package citcom
 */

defined( 'ABSPATH' ) || exit;

/**
 * An address reduced to what a redirect is matched on: its path, decoded,
 * in lower case, without the trailing slash. A full URL gives its path.
 *
 * @param string $address Path or URL.
 * @return string '/' for the home page.
 */
function citcom_redirect_path( string $address ): string {
	$path = (string) wp_parse_url( trim( $address ), PHP_URL_PATH );
	$path = '/' . trim( strtolower( rawurldecode( $path ) ), '/' );
	return $path;
}

/**
 * The redirect for a requested address, if there is one.
 *
 * @param string $request_uri The request, path and query string.
 * @return array{0:string,1:int}|null Target URL and status code.
 */
function citcom_redirect_for( string $request_uri ): ?array {
	if ( ! function_exists( 'get_field' ) ) {
		return null;
	}
	$rows = get_field( 'redirects', 'option' );
	if ( ! is_array( $rows ) || ! $rows ) {
		return null;
	}

	$path      = citcom_redirect_path( $request_uri );
	$home_host = wp_parse_url( home_url(), PHP_URL_HOST );
	foreach ( $rows as $row ) {
		$from = (string) ( $row['from'] ?? '' );
		$to   = trim( (string) ( $row['to'] ?? '' ) );
		if ( '' === $from || '' === $to || citcom_redirect_path( $from ) !== $path ) {
			continue;
		}
		// A path is a page on this site; anything else is taken as a full URL.
		$target = '/' === $to[0] ? home_url( $to ) : $to;
		if ( ! wp_http_validate_url( $target ) && 0 !== strpos( $target, home_url() ) ) {
			continue;
		}
		// A row pointing at itself would loop.
		$target_path = citcom_redirect_path( $target );
		$target_host = wp_parse_url( $target, PHP_URL_HOST );
		if ( $target_path === $path && $target_host === $home_host ) {
			continue;
		}
		$status = (int) ( $row['type'] ?? 301 );
		return array( $target, 302 === $status ? 302 : 301 );
	}

	return null;
}

add_action(
	'template_redirect',
	function () {
		if ( is_admin() || empty( $_SERVER['REQUEST_URI'] ) ) {
			return;
		}
		$redirect = citcom_redirect_for( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
		if ( $redirect ) {
			// phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- Targets are entered by the site's editors and may be another site.
			wp_redirect( $redirect[0], $redirect[1], 'Citcom' );
			exit;
		}
	},
	1
);
