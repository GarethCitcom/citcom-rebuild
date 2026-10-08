<?php
/**
 * Shortcodes the site content relies on (functions/theme_content/shortcode.php)
 * and the Trustindex embed helper shared by the blocks that show reviews.
 *
 * The old theme printed an Elfsight reviews widget from the google_reviews
 * layout and from two shortcodes. Elfsight is no longer used (decided
 * 2026-09-30): reviews come from Trustindex everywhere.
 *
 * @package citcom
 */

defined( 'ABSPATH' ) || exit;

/**
 * The widget shown when no code is given: the Google reviews widget set up in
 * the Trustindex plugin.
 *
 * @return string
 */
function citcom_trustindex_default(): string {
	return (string) apply_filters( 'citcom_trustindex_default', '[trustindex no-registration=google]' );
}

/**
 * What kind of Trustindex code a string is.
 *
 * Editors paste either a Trustindex plugin shortcode or the embed snippet from
 * trustindex.io (a loader script). Nothing else is accepted, so the field
 * cannot be used to print arbitrary markup or scripts.
 *
 * @param string $code Field value.
 * @return string 'shortcode', 'script' or '' when it is neither.
 */
function citcom_trustindex_kind( string $code ): string {
	$code = trim( $code );
	if ( preg_match( '/^\[trustindex[\w-]*(\s[^\[\]]*)?\]$/', $code ) ) {
		return 'shortcode';
	}
	if ( false !== stripos( $code, '<script' ) && preg_match( '#https://cdn\.trustindex\.io/loader[a-z-]*\.js\?[a-z0-9]+#i', $code ) ) {
		return 'script';
	}
	return '';
}

/**
 * Markup for a Trustindex widget.
 *
 * @param string $code Shortcode or embed snippet; empty for the default widget.
 * @return string Empty when the code is not Trustindex code, or when it is a
 *                shortcode and the Trustindex plugin is not active.
 */
function citcom_trustindex_embed( string $code = '' ): string {
	$code = trim( $code );
	if ( '' === $code ) {
		$code = citcom_trustindex_default();
	}

	switch ( citcom_trustindex_kind( $code ) ) {
		case 'shortcode':
			preg_match( '/^\[(trustindex[\w-]*)/', $code, $tag );
			return shortcode_exists( $tag[1] ) ? do_shortcode( $code ) : '';

		case 'script':
			// Rebuilt from the loader URL alone; the widget is inserted where the script sits.
			preg_match_all( '#https://cdn\.trustindex\.io/loader[a-z-]*\.js\?[a-z0-9]+#i', $code, $urls );
			$html = '';
			foreach ( array_unique( $urls[0] ) as $url ) {
				$html .= "<script defer async src='" . esc_url( $url ) . "'></script>"; // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- the loader renders in place
			}
			return $html;
	}

	return '';
}

/**
 * Refuse anything but Trustindex code in the "Trustindex widget" fields.
 */
add_filter(
	'acf/validate_value/name=trustindex_code',
	function ( $valid, $value ) {
		if ( true !== $valid || '' === trim( (string) $value ) ) {
			return $valid;
		}
		return citcom_trustindex_kind( (string) $value )
			? $valid
			: __( 'Paste a Trustindex shortcode, such as [trustindex no-registration=google], or the embed snippet from trustindex.io.', 'citcom' );
	},
	10,
	2
);

add_shortcode(
	'google_reviews',
	function () {
		return citcom_trustindex_embed();
	}
);

/*
 * The old [youtube_gallery] printed the same Elfsight reviews widget as
 * [google_reviews] (a copy of it, never a gallery). It stays registered so the
 * tag does not show as text anywhere it is still in content, and prints nothing.
 */
add_shortcode( 'youtube_gallery', '__return_empty_string' );

add_shortcode(
	'chatcom',
	function () {
		return '<iframe src="https://aiserve247.com/aiserve247/200736ba40bc48b08e6fed58cd7e5d49" width="100%" height="496" style="background: #ffffff;overflow: hidden;border-radius: 20px;max-width: 56rem;margin:0 auto;"></iframe>';
	}
);
