<?php
/**
 * citcom/diner-reviews
 *
 * Mirrors flexDinerReviews() (templates/flexFunctions/diner_reviews.php): the
 * "Five Star Reputation" panel on the dark aubergine background. With a
 * Trustindex code it shows Trustindex's live widget (through
 * citcom_trustindex_embed() in inc/shortcodes.php); without one it shows the
 * placeholder: a rating summary badge and a row of review cards.
 *
 * @var array  $block      Block settings and attributes.
 * @var string $content    Inner HTML (unused).
 * @var bool   $is_preview True in the editor.
 * @var int    $post_id    Post the block is saved to.
 *
 * @package citcom
 */

defined( 'ABSPATH' ) || exit;

$fields = get_fields() ?: array();
$attrs  = citcom_section_attrs( $block, $fields );
$index  = citcom_block_index();

$heading    = (string) ( $fields['heading'] ?? '' );
$trustindex = trim( (string) ( $fields['trustindex_code'] ?? '' ) );
$summary    = is_array( $fields['summary'] ?? null ) ? $fields['summary'] : array();
$reviews    = is_array( $fields['reviews'] ?? null ) ? $fields['reviews'] : array();
$has_more   = false;

$summary_label  = (string) ( $summary['label'] ?? '' );
$summary_rating = $summary['rating'] ?? 5;
$summary_count  = (string) ( $summary['review_count'] ?? '' );
$summary_link   = is_array( $summary['link'] ?? null ) ? $summary['link'] : null;

$google_badge = CITCOM_THEME_URI . '/assets/img/Google__G__logo.svg';

/*
 * The scattered stars are the bundled sparkle crops used as a CSS mask over a
 * solid fill, so they take the light tint this dark section needs. They are
 * inlined as data: URIs: a mask has to read the image's pixels, and ShortPixel
 * rewrites any url() in the page to its CDN, which the browser then blocks as
 * cross-origin. The -crisp variants have a hard alpha edge (the shared
 * sparkle-N.png files carry a soft halo the mask would pick up).
 */
$sparkle_variants = 3;
$sparkle_data     = array();
for ( $v = 1; $v <= $sparkle_variants; $v++ ) {
	$sparkle_path = CITCOM_THEME_DIR . '/assets/img/diner/sparkle-' . $v . '-crisp.png';
	if ( file_exists( $sparkle_path ) ) {
		$sparkle_data[ $v ] = 'data:image/png;base64,' . base64_encode( (string) file_get_contents( $sparkle_path ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode, WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	}
}

// Initials for a review with no avatar: first letter of up to the first two words of the name.
$initials = static function ( $name ) {
	$words = preg_split( '/\s+/', trim( (string) $name ) );
	$out   = '';
	foreach ( array_slice( (array) $words, 0, 2 ) as $word ) {
		$out .= mb_strtoupper( mb_substr( $word, 0, 1 ) );
	}
	return '' !== $out ? $out : '?';
};

?>

<section <?php echo $attrs['anchor_attr']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="flex-diner_reviews bg-primary <?php echo esc_attr( $attrs['classes'] ); ?>" data-index="<?php echo (int) $index; ?>">

	<?php
	foreach ( array( 1, 2, 3, 4, 5, 6 ) as $i ) :
		$variant  = ( ( $i - 1 ) % $sparkle_variants ) + 1;
		$data_uri = $sparkle_data[ $variant ] ?? '';
		if ( '' === $data_uri ) {
			continue;
		}
		?>
		<span class="diner-reviews-dust diner-reviews-dust-<?php echo (int) $i; ?>" style="--diner-reviews-dust-img: url('<?php echo esc_attr( $data_uri ); ?>');" aria-hidden="true"></span>
	<?php endforeach; ?>

	<div class="diner-reviews-inner">

		<div class="diner-reviews-panel" data-aos="fade-up">

			<?php if ( $heading ) : ?>
				<h2 class="diner-reviews-heading"><?php echo esc_html( $heading ); ?></h2>
			<?php endif; ?>

			<?php if ( $trustindex ) : ?>

				<div class="diner-reviews-trustindex">
					<?php if ( $is_preview ) : ?>
						<?php
						// Trustindex's loader script does not run in the editor canvas.
						?>
						<p class="text-center text-light mb-0"><strong><?php esc_html_e( 'Trustindex widget', 'citcom' ); ?></strong> <code><?php echo esc_html( $trustindex ); ?></code><?php echo '' === citcom_trustindex_embed( $trustindex ) ? ' ' . esc_html__( '(nothing will show: not Trustindex code, or the Trustindex plugin is not active)', 'citcom' ) : ''; ?></p>
					<?php else : ?>
						<?php echo citcom_trustindex_embed( $trustindex ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trustindex shortcode output or a rebuilt loader script ?>
					<?php endif; ?>
				</div>

			<?php else : ?>

			<div class="diner-reviews-body">

				<?php
				if ( $summary_label || $summary_count ) :
					$summary_tag   = ! empty( $summary_link['url'] ) ? 'a' : 'div';
					$summary_attrs = 'a' === $summary_tag
						? ' href="' . esc_url( $summary_link['url'] ) . '"' . ( ! empty( $summary_link['target'] ) ? ' target="' . esc_attr( $summary_link['target'] ) . '"' : '' )
						: '';
					?>
					<<?php echo $summary_tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="diner-reviews-summary"<?php echo $summary_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
						<?php if ( $summary_label ) : ?>
							<p class="diner-reviews-summary-label"><?php echo esc_html( $summary_label ); ?></p>
						<?php endif; ?>

						<?php echo diner_rating_stars( $summary_rating, 'diner-reviews-summary-stars' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

						<?php if ( $summary_count ) : ?>
							<p class="diner-reviews-summary-count">Based on <?php echo esc_html( $summary_count ); ?> reviews</p>
						<?php endif; ?>

						<p class="diner-reviews-summary-google">
							<span class="g-blue">G</span><span class="g-red">o</span><span class="g-yellow">o</span><span class="g-blue">g</span><span class="g-green">l</span><span class="g-red">e</span>
						</p>
					</<?php echo $summary_tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
				<?php endif; ?>

				<?php
				if ( $reviews ) :
					// The arrow shows whenever there is more than one card: the cards have a
					// minimum width, so at narrower viewports fewer than three fit and the
					// rest must stay reachable (the native scrollbar is hidden).
					$has_more = count( $reviews ) > 1;
					?>
					<div class="diner-reviews-cards-viewport">
						<div class="diner-reviews-cards" data-diner-reviews-track>
							<?php
							foreach ( $reviews as $review ) :
								$name     = (string) ( $review['name'] ?? '' );
								$rating   = $review['rating'] ?? 5;
								$verified = ! empty( $review['verified'] );
								$text     = (string) ( $review['review_text'] ?? '' );
								$more     = is_array( $review['link'] ?? null ) ? $review['link'] : null;
								?>
								<article class="diner-reviews-card">
									<img src="<?php echo esc_url( $google_badge ); ?>" class="diner-reviews-card-badge" alt="Google" aria-hidden="true">

									<div class="diner-reviews-card-head">
										<?php if ( ! empty( $review['avatar']['id'] ) ) : ?>
											<?php echo the_image( $review['avatar']['id'], 'diner-reviews-card-avatar', ' alt=""' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
										<?php else : ?>
											<span class="diner-reviews-card-avatar diner-reviews-card-avatar-initials" aria-hidden="true"><?php echo esc_html( $initials( $name ) ); ?></span>
										<?php endif; ?>

										<span class="diner-reviews-card-who">
											<?php if ( $name ) : ?>
												<span class="diner-reviews-card-name"><?php echo esc_html( $name ); ?></span>
											<?php endif; ?>
											<?php if ( ! empty( $review['time_ago'] ) ) : ?>
												<span class="diner-reviews-card-time"><?php echo esc_html( $review['time_ago'] ); ?></span>
											<?php endif; ?>
										</span>
									</div>

									<span class="diner-reviews-card-rating">
										<?php echo diner_rating_stars( $rating ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
										<?php if ( $verified ) : ?>
											<svg class="diner-reviews-verified" viewBox="0 0 16 16" aria-label="Verified" role="img">
												<circle cx="8" cy="8" r="8" fill="#4285F4" />
												<path d="M4.5 8.2 L6.8 10.5 L11.5 5.5" fill="none" stroke="#fff" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
											</svg>
										<?php endif; ?>
									</span>

									<?php if ( $text ) : ?>
										<p class="diner-reviews-card-text"><?php echo esc_html( $text ); ?></p>
									<?php endif; ?>

									<?php if ( ! empty( $more['url'] ) ) : ?>
										<a href="<?php echo esc_url( $more['url'] ); ?>"<?php echo ! empty( $more['target'] ) ? ' target="' . esc_attr( $more['target'] ) . '"' : ''; ?> class="diner-reviews-card-more">Read more</a>
									<?php endif; ?>
								</article>
							<?php endforeach; ?>
						</div>

						<?php if ( $has_more ) : ?>
							<button type="button" class="diner-reviews-arrow" data-diner-reviews-next aria-label="Show more reviews">
								<svg viewBox="0 0 8 14" aria-hidden="true" focusable="false">
									<path d="M1 1 L7 7 L1 13" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
								</svg>
							</button>
						<?php endif; ?>
					</div>
				<?php endif; ?>

			</div>

			<?php endif; ?>

		</div>

	</div>

</section>

<?php
// Printed once however many of these blocks are on the page: it is delegated on
// document. Native scroll-snap does the visual work; this drives it from the arrow.
if ( $has_more && ! $is_preview && citcom_once( 'diner-reviews-script' ) ) :
	?>
	<script>
	document.addEventListener('click', function (e) {
		var btn = e.target.closest('[data-diner-reviews-next]');
		if (!btn) return;
		var viewport = btn.closest('.diner-reviews-cards-viewport');
		var track = viewport && viewport.querySelector('[data-diner-reviews-track]');
		if (!track) return;
		var card = track.querySelector('.diner-reviews-card');
		var gap = parseFloat(getComputedStyle(track).columnGap || getComputedStyle(track).gap) || 0;
		var step = card ? card.getBoundingClientRect().width + gap : track.clientWidth;
		var atEnd = track.scrollLeft + track.clientWidth >= track.scrollWidth - 4;
		track.scrollTo({
			left: atEnd ? 0 : track.scrollLeft + step,
			behavior: 'smooth'
		});
	});
	</script>
	<?php
endif;
