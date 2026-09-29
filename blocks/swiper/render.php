<?php
/**
 * citcom/swiper
 *
 * Mirrors flexSwiper() (templates/flexFunctions/swiper.php). Only the "images"
 * type renders, as in the original: "content" and "cards" were never built.
 * The data-* attributes are read by src/js/swiper.js. Two quirks are kept
 * because the baseline has them: the centre-align check is always true (the
 * flag is a non-empty string), so the mobile column is 6 whenever it was 12;
 * and speed is delay-style maths on the percentage.
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

$swiper_type = ! empty( $fields['swiper_type'] ) ? (string) $fields['swiper_type'] : 'images';

$slides_per_view     = is_array( $fields['slides_per_view'] ?? null ) ? $fields['slides_per_view'] : array();
$slides_per_view     = array_merge(
	array(
		'number_of_slides_xl' => 2,
		'number_of_slides_lg' => 3,
		'number_of_slides_md' => 6,
		'number_of_slides_sm' => 12,
	),
	array_filter( $slides_per_view )
);
$center_align_slides = ! empty( $fields['center_align_slides'] ) ? 'true' : 'false';
$loop_slides         = ! empty( $fields['loop_slides'] ) ? 'true' : 'false';
$auto_play           = ! empty( $fields['auto_play'] ) ? 'true' : 'false';
$delay               = 'true' === $auto_play ? ( (float) ( $fields['delay'] ?? 0 ) * 1000 ) : 0;
$speed               = 'true' === $auto_play ? ( (float) ( $fields['speed'] ?? 0 ) / 2 * 1000 ) : 0;
$navigation_arrows   = ! empty( $fields['navigation_arrows'] ) ? '' : 'hidden';
$pagination_dots     = ! empty( $fields['pagination_dots'] ) ? '' : 'hidden';
$scrollbar           = ! empty( $fields['scrollbar'] ) ? '' : 'hidden';
$reverse_direction   = ! empty( $fields['reverse_direction'] ) ? 'true' : 'false';

if ( $center_align_slides ) {
	$slides_per_view['number_of_slides_sm'] = 12 === (int) $slides_per_view['number_of_slides_sm'] ? 6 : $slides_per_view['number_of_slides_sm'];
}

$image_slides = array_filter( array_map( fn( $img ) => is_array( $img ) ? (int) ( $img['ID'] ?? $img['id'] ?? 0 ) : (int) $img, (array) ( $fields['image_slides'] ?? array() ) ) );

$swiper_id = uniqid();

?>

<section <?php echo $attrs['anchor_attr']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="section-padding fluid flex-swiper <?php echo esc_attr( $attrs['classes'] ); ?>" data-index="<?php echo (int) $index; ?>">

	<div id="swiper-<?php echo esc_attr( $swiper_id ); ?>" class="swiper swiper-swiper" data-center="<?php echo esc_attr( $center_align_slides ); ?>" data-loop="<?php echo esc_attr( $loop_slides ); ?>" data-autoplay="<?php echo esc_attr( $auto_play ); ?>" data-delay="<?php echo esc_attr( (string) $delay ); ?>" data-speed="<?php echo esc_attr( (string) $speed ); ?>" data-reversedirection="<?php echo esc_attr( $reverse_direction ); ?>">
		<div class="swiper-wrapper row flex-nowrap">
			<?php if ( 'images' === $swiper_type ) : ?>
				<?php foreach ( $image_slides as $img ) : ?>
					<div class="swiper-slide col-<?php echo esc_attr( (string) $slides_per_view['number_of_slides_sm'] ); ?> col-md-<?php echo esc_attr( (string) $slides_per_view['number_of_slides_md'] ); ?> col-lg-<?php echo esc_attr( (string) $slides_per_view['number_of_slides_lg'] ); ?> col-xl-<?php echo esc_attr( (string) $slides_per_view['number_of_slides_xl'] ); ?>" data-aos="blur-sm">
						<?php echo wp_get_attachment_image( $img, 'medium', false, array( 'class' => 'img-fluid mx-auto', 'loading' => 'lazy' ) ); ?>
						<div class="swiper-lazy-preloader swiper-lazy-preloader-white"></div>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
			<?php if ( ! $image_slides && $is_preview ) : ?>
				<p class="px-4"><em><?php esc_html_e( 'Add image slides in the block settings.', 'citcom' ); ?></em></p>
			<?php endif; ?>
		</div>
		<div class="swiper-pagination <?php echo esc_attr( $pagination_dots ); ?>"></div>
		<div class="swiper-navigation <?php echo esc_attr( $navigation_arrows ); ?>">
			<div class="swiper-button-prev"></div>
			<div class="swiper-button-next"></div>
		</div>
		<div class="swiper-scrollbar <?php echo esc_attr( $scrollbar ); ?>"></div>
	</div>
</section>
