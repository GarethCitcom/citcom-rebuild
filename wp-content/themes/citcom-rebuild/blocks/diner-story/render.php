<?php
/**
 * citcom/diner-story
 *
 * Mirrors flexDinerStory() (templates/flexFunctions/diner_story.php): the dark
 * copy panel and photo split, chequer bands top and bottom, with the starburst
 * stamp overhanging the bottom-right corner. "fluid" opts the section out of
 * the theme-wide section side padding so it runs edge to edge.
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

$heading = (string) ( $fields['heading'] ?? '' );
$body    = (string) ( $fields['content'] ?? '' );
$button  = is_array( $fields['button'] ?? null ) ? $fields['button'] : null;
$image   = is_array( $fields['image'] ?? null ) ? $fields['image'] : null;
$stamp   = is_array( $fields['stamp'] ?? null ) ? $fields['stamp'] : array();

// The photo ships with the theme so the section renders before anything is uploaded.
$image_fallback = CITCOM_THEME_URI . '/assets/img/diner/diner-story-photo.jpg';

?>

<section <?php echo $attrs['anchor_attr']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="fluid flex-diner_story bg-primary <?php echo esc_attr( $attrs['classes'] ); ?>" data-index="<?php echo (int) $index; ?>">

	<div class="diner-story-grid">

		<?php
		// Absolutely positioned, so the chequer band overlays both columns.
		?>
		<span class="diner-story-edge diner-story-edge-top" aria-hidden="true"></span>

		<div class="diner-story-copy" data-aos="fade-up">
			<?php if ( $heading ) : ?>
				<h2 class="diner-story-heading"><?php echo esc_html( $heading ); ?></h2>
			<?php endif; ?>

			<?php if ( $body ) : ?>
				<div class="diner-story-content">
					<?php echo wp_kses_post( wpautop( $body ) ); ?>
				</div>
			<?php endif; ?>

			<?php if ( $button ) : ?>
				<div class="diner-story-cta">
					<?php echo diner_button( $button, 'diner-btn-gradient diner-btn-bright', false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			<?php endif; ?>
		</div>

		<div class="diner-story-media">
			<?php if ( $image ) : ?>
				<?php echo the_image( $image['id'] ?? $image['ID'] ?? 0, 'diner-story-photo', 'sizes="(min-width: 768px) 640px, 100vw"' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php else : ?>
				<img src="<?php echo esc_url( $image_fallback ); ?>" class="diner-story-photo" alt="">
			<?php endif; ?>
		</div>

		<span class="diner-story-edge diner-story-edge-bottom" aria-hidden="true"></span>

	</div>

	<?php echo diner_stamp( $stamp, 'diner-story-stamp' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

</section>
