<?php
/**
 * citcom/diner-guestcheck
 *
 * Mirrors flexDinerGuestcheck() (templates/flexFunctions/diner_guestcheck.php):
 * the "GUEST CHECK" receipt card on the left with a theme form (forms/) on the
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
$form         = is_scalar( $fields['form'] ?? null ) ? (string) $fields['form'] : ''; // Theme form slug, or a legacy Forminator id.
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
					// "plain": no Bootstrap classes, this block styles the bare form itself.
					echo citcom_render_form( $form, array( 'variant' => 'plain' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in citcom_render_form()
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
 * Printed once. Reveals the card and form wrappers by adding AOS's own
 * "aos-animate" class once the page has loaded: the card image loading shifts
 * the row after AOS has measured it, so these two could stay at opacity 0.
 * (The old script also relabelled the Forminator submit button; the theme form
 * has its own label.)
 */
if ( ! $is_preview && citcom_once( 'diner-guestcheck-script' ) ) :
	?>
	<script>
	window.addEventListener('load', function () {
		document.querySelectorAll('.diner-guestcheck-card-aos, .diner-guestcheck-form-wrap').forEach(function (el) {
			el.classList.add('aos-animate');
		});
	});
	</script>
	<?php
endif;
