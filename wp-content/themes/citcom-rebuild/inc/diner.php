<?php
/**
 * Shared markup for the diner blocks (functions/lib/diner-components.php).
 *
 * The starred pill CTA and the starburst stamp appear in most of the diner
 * sections, so they live here rather than in each block. Function names are
 * kept from the old theme.
 *
 * @package citcom
 */

defined( 'ABSPATH' ) || exit;

/**
 * Outlined five point star that flanks the diner CTA label.
 *
 * Sized in em so it tracks the label; currentColor so it inherits the button's
 * text colour.
 *
 * @return string
 */
function diner_star_svg() {
	return '<svg class="diner-btn-star" viewBox="0 0 24 23" fill="none" aria-hidden="true" focusable="false">'
		. '<path d="M12 0.7 L14.7 8.48 L22.94 8.65 L16.37 13.62 L18.76 21.5 L12 16.8 '
		. 'L5.24 21.5 L7.63 13.62 L1.06 8.65 L9.3 8.48 Z" '
		. 'stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>'
		. '</svg>';
}

/**
 * Solid five point star, same geometry as diner_star_svg() but filled; used for
 * review ratings.
 *
 * @return string
 */
function diner_rating_star_svg() {
	return '<svg viewBox="0 0 24 23" fill="currentColor" aria-hidden="true" focusable="false">'
		. '<path d="M12 0.7 L14.7 8.48 L22.94 8.65 L16.37 13.62 L18.76 21.5 L12 16.8 '
		. 'L5.24 21.5 L7.63 13.62 L1.06 8.65 L9.3 8.48 Z"/>'
		. '</svg>';
}

/**
 * A row of 5 stars, filled up to $rating (rounded to the nearest whole star).
 *
 * @param float|string $rating  0 to 5.
 * @param string       $classes Extra classes on the wrapping span.
 * @return string
 */
function diner_rating_stars( $rating, $classes = '' ) {
	$rating = max( 0, min( 5, (float) $rating ) );
	$filled = (int) round( $rating );
	$star   = diner_rating_star_svg();

	$out = '<span class="diner-stars ' . esc_attr( $classes ) . '" role="img" aria-label="' . esc_attr( $rating . ' out of 5 stars' ) . '">';
	for ( $i = 1; $i <= 5; $i++ ) {
		$out .= '<span class="diner-star' . ( $i <= $filled ? ' diner-star-filled' : '' ) . '">' . $star . '</span>';
	}
	$out .= '</span>';

	return $out;
}

/**
 * The diner pill CTA.
 *
 * Not every pill carries the stars: the services CTA is label only.
 *
 * @param array|null|false $link    ACF link field value (url / title / target).
 * @param string           $classes Extra classes for the anchor.
 * @param bool             $stars   Whether to flank the label with the outlined stars.
 * @return string
 */
function diner_button( $link, $classes = '', $stars = true ) {
	if ( empty( $link ) || empty( $link['url'] ) ) {
		return '';
	}

	$target = ! empty( $link['target'] ) ? ' target="' . esc_attr( $link['target'] ) . '"' : '';
	$flank  = $stars ? diner_star_svg() : '';

	return '<a href="' . esc_url( $link['url'] ) . '"' . $target
		. ' class="diner-btn ' . esc_attr( $classes ) . '">'
		. $flank
		. '<span class="diner-btn-label">' . esc_html( $link['title'] ?? '' ) . '</span>'
		. $flank
		. '</a>';
}

/**
 * The diner starburst stamp (a 12 point credentials badge, such as "Est. 1987").
 * Its look is in the shared .diner-stamp CSS; size, position and tilt are left
 * to the calling block. The intro block keeps its own older, section-scoped
 * copy of this markup, as the old theme did.
 *
 * @param array|null|false $stamp   ACF group value: enabled / line_1 / line_2 / line_3.
 * @param string           $classes Extra classes on the outer element.
 * @return string
 */
function diner_stamp( $stamp, $classes = '' ) {
	if ( empty( $stamp ) || empty( $stamp['enabled'] ) ) {
		return '';
	}

	ob_start();
	?>
	<div class="diner-stamp <?php echo esc_attr( $classes ); ?>">
		<div class="diner-stamp-inner">
			<?php if ( ! empty( $stamp['line_1'] ) ) : ?>
				<span class="diner-stamp-over"><?php echo esc_html( $stamp['line_1'] ); ?></span>
			<?php endif; ?>
			<?php if ( ! empty( $stamp['line_2'] ) ) : ?>
				<span class="diner-stamp-figure"><?php echo esc_html( $stamp['line_2'] ); ?></span>
			<?php endif; ?>
			<?php if ( ! empty( $stamp['line_3'] ) ) : ?>
				<span class="diner-stamp-caption"><?php echo esc_html( $stamp['line_3'] ); ?></span>
			<?php endif; ?>
		</div>
	</div>
	<?php
	return (string) ob_get_clean();
}
