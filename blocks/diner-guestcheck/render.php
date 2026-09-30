<?php
/**
 * citcom/diner-guestcheck
 *
 * Mirrors flexDinerGuestcheck() (templates/flexFunctions/diner_guestcheck.php):
 * the "GUEST CHECK" receipt card on the left with a live Forminator form on the
 * right, chequer bands top and bottom, and the shared starburst stamp. Only the
 * receipt itself is baked into the artwork; the card heading and body copy are
 * real text laid over it ("Thank You!" at the bottom is part of the artwork).
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

$heading      = (string) ( $fields['heading'] ?? '' );
$card_heading = (string) ( $fields['card_heading'] ?? '' );
$card_content = (string) ( $fields['card_content'] ?? '' );
$card_image   = is_array( $fields['card_image'] ?? null ) ? $fields['card_image'] : null;
$form         = (int) ( $fields['form'] ?? 0 );
$stamp        = is_array( $fields['stamp'] ?? null ) ? $fields['stamp'] : array();

// Ships with the theme so the section renders before anything is uploaded.
$card_fallback = CITCOM_THEME_URI . '/assets/img/diner/guest-check.webp';

?>

<section <?php echo $attrs['anchor_attr']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="flex-diner_guestcheck diner-dots <?php echo esc_attr( $attrs['classes'] ); ?>" data-index="<?php echo (int) $index; ?>">

	<span class="diner-guestcheck-edge diner-guestcheck-edge-top" aria-hidden="true"></span>

	<?php if ( $heading ) : ?>
		<h2 class="visually-hidden"><?php echo esc_html( $heading ); ?></h2>
	<?php endif; ?>

	<div class="diner-guestcheck-inner">

		<div class="diner-guestcheck-card-aos" data-aos="fade-up">
			<div class="diner-guestcheck-card">
				<?php if ( $card_image ) : ?>
					<?php echo the_image( $card_image['id'] ?? $card_image['ID'] ?? 0, 'diner-guestcheck-card-img' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php else : ?>
					<img src="<?php echo esc_url( $card_fallback ); ?>" class="diner-guestcheck-card-img" alt="">
				<?php endif; ?>

				<div class="diner-guestcheck-card-overlay">
					<?php if ( $card_heading ) : ?>
						<p class="diner-guestcheck-card-heading"><?php echo esc_html( $card_heading ); ?></p>
					<?php endif; ?>
					<?php if ( $card_content ) : ?>
						<p class="diner-guestcheck-card-content">
							<?php echo esc_html( $card_content ); ?>
							<img class="diner-guestcheck-card-arrow" src="<?php echo esc_url( CITCOM_THEME_URI . '/assets/img/diner/arrow.svg' ); ?>" alt="" aria-hidden="true">
						</p>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<div class="diner-guestcheck-form-wrap" data-aos="fade-up">
			<?php if ( $form ) : ?>
				<div class="diner-guestcheck-form">
					<?php
					if ( shortcode_exists( 'forminator_form' ) ) {
						echo do_shortcode( '[forminator_form id="' . $form . '"]' );
					} elseif ( $is_preview ) {
						echo '<p><em>' . esc_html__( 'Forminator is not active on this site, so the form will not show.', 'citcom' ) . '</em></p>';
					}
					?>
				</div>
			<?php endif; ?>
		</div>

		<?php echo diner_stamp( $stamp, 'diner-guestcheck-stamp' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

	</div>

	<span class="diner-guestcheck-edge diner-guestcheck-edge-bottom" aria-hidden="true"></span>

</section>

<?php
/*
 * Printed once. Two jobs, both scoped to .diner-guestcheck-form:
 *
 * 1. Retitles the submit button to "SEND TO THE KITCHEN" without touching the
 *    Forminator form's stored button text (the form may be shared with other
 *    pages). Forminator can inject the fields after load (ajax-load) and
 *    re-render the button after a validation error, so a MutationObserver
 *    stays attached.
 * 2. Reveals the card and form wrappers by adding AOS's own "aos-animate"
 *    class. AOS does not re-measure when content is injected inside an element
 *    that already has data-aos, and the card image loading shifts the row, so
 *    these two could stay at opacity 0.
 */
if ( ! $is_preview && citcom_once( 'diner-guestcheck-script' ) ) :
	?>
	<script>
	(function () {
		var LABEL = 'SEND TO THE KITCHEN';
		function relabel(btn) {
			var span = btn.querySelector('span:not([aria-hidden])');
			var target = span || btn;
			if (target.textContent.trim() !== LABEL) {
				target.textContent = LABEL;
			}
		}
		function revealAOS() {
			document.querySelectorAll('.diner-guestcheck-card-aos, .diner-guestcheck-form-wrap').forEach(function (el) {
				el.classList.add('aos-animate');
			});
		}
		document.querySelectorAll('.diner-guestcheck-form').forEach(function (wrap) {
			var existing = wrap.querySelector('.forminator-button-submit');
			if (existing) {
				relabel(existing);
				revealAOS();
			}
			new MutationObserver(function () {
				var btn = wrap.querySelector('.forminator-button-submit');
				if (btn) relabel(btn);
				revealAOS();
			}).observe(wrap, { childList: true, subtree: true });
		});
		window.addEventListener('load', revealAOS);
	})();
	</script>
	<?php
endif;
