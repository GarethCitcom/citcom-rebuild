<?php
/**
 * citcom/diner-menu
 *
 * Mirrors flexDinerMenu() (templates/flexFunctions/diner_menu.php): the
 * "Feeling peckish?" services grid. Card colour alternates in CSS, so
 * reordering keeps the chequer.
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

$heading     = (string) ( $fields['heading'] ?? '' );
$cards       = is_array( $fields['cards'] ?? null ) ? $fields['cards'] : array();
$button      = is_array( $fields['button'] ?? null ) ? $fields['button'] : null;
$bleed_start = is_array( $fields['bleed_start'] ?? null ) ? $fields['bleed_start'] : null;
$bleed_end   = is_array( $fields['bleed_end'] ?? null ) ? $fields['bleed_end'] : null;

?>

<section <?php echo $attrs['anchor_attr']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="flex-diner_menu diner-dots <?php echo esc_attr( $attrs['classes'] ); ?>" data-index="<?php echo (int) $index; ?>">

	<?php if ( $bleed_start ) : ?>
		<?php echo the_image( $bleed_start['id'] ?? $bleed_start['ID'] ?? 0, 'diner-menu-bleed diner-menu-bleed-start', 'aria-hidden="true"' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<?php endif; ?>
	<?php if ( $bleed_end ) : ?>
		<?php echo the_image( $bleed_end['id'] ?? $bleed_end['ID'] ?? 0, 'diner-menu-bleed diner-menu-bleed-end', 'aria-hidden="true"' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<?php endif; ?>

	<?php if ( $heading ) : ?>
		<?php
		// Jump target for the hero's CTA: the id is on the heading, so it cannot collide with this section's own anchor.
		?>
		<h2 id="feeling-peckish" class="diner-menu-heading" data-aos="fade-up"><?php echo esc_html( $heading ); ?></h2>
	<?php endif; ?>

	<?php if ( $cards ) : ?>
		<div class="diner-menu-grid">
			<?php
			foreach ( $cards as $card ) :
				// The design sizes and tilts each shot individually.
				$style = 'style="'
					. '--diner-shot: ' . esc_attr( (string) ( $card['image_scale'] ?? '' ) ) . '%;'
					. '--diner-shot-tilt: ' . esc_attr( (string) ( $card['image_tilt'] ?? '' ) ) . 'deg;'
					. '"';

				// The card is an <a> only when a link is set; grid items lay out the same either way.
				$card_link  = is_array( $card['link'] ?? null ) && ! empty( $card['link']['url'] ) ? $card['link'] : null;
				$card_tag   = $card_link ? 'a' : 'div';
				$card_attrs = 'class="diner-menu-card" data-aos="fade-up"';
				if ( $card_link ) {
					$card_attrs .= ' href="' . esc_url( $card_link['url'] ) . '"';
					if ( ! empty( $card_link['target'] ) ) {
						$card_attrs .= ' target="' . esc_attr( $card_link['target'] ) . '"';
					}
				}
				$card_image = is_array( $card['image'] ?? null ) ? $card['image'] : null;
				?>
				<<?php echo $card_tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo $card_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
					<?php if ( $card_image ) : ?>
						<?php echo the_image( $card_image['id'] ?? $card_image['ID'] ?? 0, 'diner-menu-shot', $style ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php endif; ?>
					<?php if ( ! empty( $card['label'] ) ) : ?>
						<p class="diner-menu-label fw-same"><?php echo esc_html( $card['label'] ); ?></p>
					<?php endif; ?>
				</<?php echo $card_tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<?php if ( $button ) : ?>
		<div class="diner-menu-cta" data-aos="fade-up">
			<?php
			// No stars on this one: the design's pill is label plus insets only.
			?>
			<?php echo diner_button( $button, 'diner-btn-gradient', false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
	<?php endif; ?>

</section>
