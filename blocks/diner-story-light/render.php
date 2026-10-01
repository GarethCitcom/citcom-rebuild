<?php
/**
 * citcom/diner-story-light
 *
 * Mirrors flexDinerStoryLight() (templates/flexFunctions/diner_story_light.php):
 * the same copy panel and photo split as diner-story, on the dotted light
 * background with a single chequer strip below the grid. The stamp field is
 * kept but not rendered, as decided on the old site (2026-08-24).
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

// Same bundled fallback photo as diner-story.
$image_fallback = CITCOM_THEME_URI . '/assets/img/diner/diner-story-photo.jpg';

?>

<section <?php echo $attrs['anchor_attr']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="flex-diner_story_light diner-dots <?php echo esc_attr( $attrs['classes'] ); ?>" data-index="<?php echo (int) $index; ?>">

	<?php if ( $heading ) : ?>
		<h2 class="visually-hidden"><?php echo esc_html( $heading ); ?></h2>
	<?php endif; ?>

	<div class="diner-story-light-grid">

		<div class="diner-story-light-copy" data-aos="fade-up">
			<?php if ( $body ) : ?>
				<div class="diner-story-light-content">
					<?php echo wp_kses_post( wpautop( $body ) ); ?>
				</div>
			<?php endif; ?>

			<?php if ( $button ) : ?>
				<div class="diner-story-light-cta">
					<?php echo diner_button( $button, 'diner-btn-gradient diner-btn-bright', false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			<?php endif; ?>
		</div>

		<div class="diner-story-light-media">
			<?php if ( $image ) : ?>
				<?php echo the_image( $image['id'] ?? $image['ID'] ?? 0, 'diner-story-light-photo' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php else : ?>
				<img src="<?php echo esc_url( $image_fallback ); ?>" class="diner-story-light-photo" alt="">
			<?php endif; ?>
		</div>

	</div>

	<?php
	// Single chequer strip below the grid, reserved in the section's own padding-bottom.
	?>
	<span class="diner-story-light-edge" aria-hidden="true"></span>

</section>
