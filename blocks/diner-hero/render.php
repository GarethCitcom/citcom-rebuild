<?php
/**
 * citcom/diner-hero
 *
 * Mirrors flexDinerHero() (templates/flexFunctions/diner_hero.php): the
 * "Welcome to the CitCom Creative Diner" sign. Layer order follows the design:
 * aubergine base with the brand pattern, the sign artwork, the grain pass, and
 * the pill CTA over the top.
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
$sign         = is_array( $fields['sign_image'] ?? null ) ? $fields['sign_image'] : null;
$button       = is_array( $fields['button'] ?? null ) ? $fields['button'] : null;
$show_texture = array_key_exists( 'show_texture', $fields ) ? $fields['show_texture'] : true;

// The sign ships with the theme so the section renders before anything is uploaded.
// Below lg the bundled fallback uses a version with the fade baked into the pixels;
// a custom upload gets the CSS overlay instead.
$sign_fallback        = CITCOM_THEME_URI . '/assets/img/diner/diner-sign.jpg';
$sign_fallback_mobile = CITCOM_THEME_URI . '/assets/img/diner/diner-sign-mobile.jpg';

?>

<section <?php echo $attrs['anchor_attr']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="fluid flex-diner_hero bg-primary <?php echo esc_attr( $attrs['classes'] ); ?>" data-index="<?php echo (int) $index; ?>">

	<div class="diner-hero-stage">

		<span class="diner-hero-edge diner-hero-edge-start diner-check" aria-hidden="true"></span>
		<span class="diner-hero-edge diner-hero-edge-end diner-check" aria-hidden="true"></span>

		<?php if ( $heading ) : ?>
			<h1 class="visually-hidden"><?php echo esc_html( $heading ); ?></h1>
		<?php endif; ?>

		<div class="diner-hero-sign-wrap">
			<?php if ( $sign && 0 === strpos( (string) ( $sign['mime_type'] ?? '' ), 'video/' ) ) : ?>
				<video class="diner-hero-sign" src="<?php echo esc_url( $sign['url'] ?? '' ); ?>" autoplay muted loop playsinline></video>
				<span class="diner-hero-sign-fade" aria-hidden="true"></span>
			<?php elseif ( $sign ) : ?>
				<?php echo the_image( $sign['id'] ?? $sign['ID'] ?? 0, 'diner-hero-sign' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span class="diner-hero-sign-fade" aria-hidden="true"></span>
			<?php else : ?>
				<picture>
					<source media="(max-width: 1039.98px)" srcset="<?php echo esc_url( $sign_fallback_mobile ); ?>">
					<img src="<?php echo esc_url( $sign_fallback ); ?>" class="diner-hero-sign" alt="Welcome to the CitCom Creative Diner">
				</picture>
			<?php endif; ?>

			<?php
			// Feathers the artwork's own edge into the aubergine where it meets the chequer strips (desktop only).
			?>
			<span class="diner-hero-sign-edge diner-hero-sign-edge-start" aria-hidden="true"></span>
			<span class="diner-hero-sign-edge diner-hero-sign-edge-end" aria-hidden="true"></span>
		</div>

		<?php
		// Reinforces the fade's bottom edge on mobile; in flow after the wrap, pulled up over the seam.
		?>
		<span class="diner-hero-sign-veil" aria-hidden="true"></span>

		<?php if ( $show_texture ) : ?>
			<span class="diner-grain" aria-hidden="true"></span>
		<?php endif; ?>

		<?php
		if ( $button ) :
			// The button only ever jumps to the menu block's #feeling-peckish heading,
			// so the label and target stay editable but the href does not.
			$button['url'] = '#feeling-peckish';
			?>
			<div class="diner-hero-cta" data-aos="fade-up">
				<?php echo diner_button( $button ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		<?php endif; ?>

	</div>

</section>
