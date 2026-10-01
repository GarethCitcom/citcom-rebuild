<?php
/**
 * Service row for citcom/sub-services, ported from templates/service-card.php.
 * Expects the global $post to be the service.
 *
 * @package citcom
 */

// The post id is passed explicitly: inside a block render, get_field() without one
// resolves to the block's own data (ACF local meta), not the service.
global $post;

$citcom_gallery         = get_field( 'gallery', $post->ID );
$citcom_excerpt         = get_field( 'service_excerpt', $post->ID );
$citcom_case_study_tags = get_field( 'linked_case_studies', $post->ID );
$citcom_cs_archive_url  = get_post_type_archive_link( 'case-study' );

$citcom_cs_btn      = false;
$citcom_cs_tag_link = '';
if ( $citcom_case_study_tags ) {
	$citcom_cs_btn      = true;
	$citcom_cs_tag_link = $citcom_cs_archive_url . '?cs-tag=';
	$citcom_i           = 0;
	foreach ( (array) $citcom_case_study_tags as $citcom_tag ) {
		$citcom_slug         = is_object( $citcom_tag ) ? $citcom_tag->slug : (string) $citcom_tag;
		$citcom_cs_tag_link .= $citcom_i > 0 ? ',' : '';
		$citcom_cs_tag_link .= $citcom_slug;
		++$citcom_i;
	}
}

$citcom_swiper_id = uniqid();
$citcom_slug      = $post->post_name;

?>

<div id="<?php echo esc_attr( $citcom_slug ); ?>" class="service-row row g-0 mb-5 overflow-hidden">
	<div class="col-12 col-md-6 bg-light rounded-start-3">
		<div class="service-details p-5">
			<?php the_title( '<h2 class="lh-1">', '</h2>' ); ?>
			<?php echo esc_html( (string) $citcom_excerpt ); ?>
			<div class="ctas d-flex flex-wrap gap-3 mt-4">
				<a href="<?php the_permalink(); ?>" class="btn btn-primary px-4 rounded-pill">Find out more</a>
				<?php if ( $citcom_cs_btn ) : ?>
					<a href="<?php echo esc_url( $citcom_cs_tag_link ); ?>" class="btn btn-outline-primary px-4 rounded-pill">Case Studies</a>
				<?php endif; ?>
			</div>
		</div>
	</div>
	<div class="col-12 col-md-6 bg-default rounded-end-3">
		<!-- Swiper -->
		<div id="swiper-<?php echo esc_attr( $citcom_swiper_id ); ?>" class="swiper swiper-gallery h-100">
			<div class="swiper-wrapper">
				<?php foreach ( (array) $citcom_gallery as $citcom_gallery_id ) : ?>
					<div class="swiper-slide">
						<div class="gallery-img h-100 w-100 position-relative">
							<?php echo the_image( is_array( $citcom_gallery_id ) ? ( $citcom_gallery_id['ID'] ?? $citcom_gallery_id['id'] ?? 0 ) : $citcom_gallery_id, 'img-bg z-n1 object-fit-cover position-absolute top-0 start-0 w-100 h-100', 'style="object-position: 50% 50%;"' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
			<div class="swiper-pagination"></div>
		</div>
	</div>
</div>
