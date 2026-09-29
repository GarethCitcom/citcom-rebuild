<?php
/**
 * Blog post, ported 1:1 from the original single.php.
 *
 * The related posts section is kept but off by default: on the current site the
 * global that fed it is always empty, so no post shows it. Turn it on with
 * add_filter( 'citcom_show_related_posts', '__return_true' ) once that is wanted.
 *
 * @package citcom
 */

get_header();

global $post;

$citcom_single_template = citcom_get_option( 'blog_post' );
$citcom_post_image      = get_field( 'page_header_image' );
$citcom_section_class   = '';
$citcom_bg_image_id     = 0;
$citcom_bg_image_left   = '50';
$citcom_bg_image_top    = '50';

if ( $citcom_post_image ) {
	$citcom_bg_image_id   = (int) ( $citcom_post_image['id'] ?? 0 );
	$citcom_bg_image_left = $citcom_post_image['left'] ?? '50';
	$citcom_bg_image_top  = $citcom_post_image['top'] ?? '50';
} else {
	$citcom_section_class = 'pattern pattern-persian bg-default_lighter';
}

$citcom_related_posts = apply_filters( 'citcom_show_related_posts', false ) ? get_related_posts( get_the_ID(), 3 ) : array();

?>

<main>

	<section class="section-padding flex-page_header <?php echo esc_attr( $citcom_section_class ); ?> d-flex align-items-center position-relative">

		<div class="page-header w-100">
			<?php if ( $citcom_post_image ) : ?>
				<div class="header-bg position-absolute z-0 start-0 top-0 end-0 bottom-0">
					<?php echo the_image( $citcom_bg_image_id, 'img-bg z-n1 object-fit-cover position-absolute top-0 start-0 w-100 h-100', 'style="object-position: ' . esc_attr( $citcom_bg_image_left ) . '% ' . esc_attr( $citcom_bg_image_top ) . '%;"' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
				<div class="pattern position-absolute z-1 start-0 top-0 end-0 bottom-0" data-aos="zoom-out"></div>
			<?php endif; ?>
		</div>

	</section>

	<section id="single-post-bar" class="single-bar pt-3 pb-2">
		<div class="container-xl">
			<div class="d-flex justify-content-between align-items-center">
				<div data-aos="fade-down">
					<?php get_breadcrumb(); ?>
				</div>
				<div class="d-none d-md-block" data-aos="fade-down">
					<div class="publish-by text-end d-flex gap-2 align-items-center">
						<p class="mb-0">Published <strong><?php echo esc_html( get_the_date( 'jS M Y' ) ); ?></strong> | By <strong><?php echo esc_html( get_the_author_meta( 'display_name', $post->post_author ) ); ?></strong><br><span>Est. reading time: <?php echo esc_html( reading_time( $post ) ); ?></span></p>
						<?php echo get_avatar( $post->post_author, 42, 'wavatar', get_the_author_meta( 'display_name', $post->post_author ), array( 'class' => 'rounded-circle border border-primary bg-default' ) ); ?>
					</div>
				</div>
			</div>
		</div>
	</section>

	<section id="reading-progress"></section>

	<section class="single section-padding">

		<div class="container-xl">
			<div class="row">
				<div class="col">
					<div id="blog-sidebar" class="pe-0 pe-md-5 pe-lg-0 pb-5 pb-md-0" data-aos="fade-up">
						<div class="d-grid">
							<button class="btn btn-outline-primary rounded-pill px-4 d-md-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasResponsive" aria-controls="offcanvasResponsive"><i class="fa-regular fa-bars pe-2"></i> Post menu</button>
						</div>
						<div class=" offcanvas-md offcanvas-top" tabindex="-1" id="offcanvasResponsive" aria-labelledby="offcanvasResponsiveLabel">
							<div class="offcanvas-header">
								<h5 class="offcanvas-title" id="offcanvasResponsiveLabel"><i class="fa-regular fa-bars pe-2"></i> Post menu</h5>
								<button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#offcanvasResponsive" aria-label="Close"></button>
							</div>
							<div class="offcanvas-body flex-column">
								<?php dynamic_sidebar( 'post_sidebar' ); ?>
							</div>
						</div>

					</div>
				</div>
				<div class="col-12 col-md-7 col-lg-9">

					<div id="post-content" class="ps-0 ps-lg-5">

						<?php the_title( '<h1 data-aos="fade-up">', '</h1>' ); ?>

						<div class="d-block d-md-none" data-aos="fade-up">
							<div class="publish-by text-start d-flex gap-2 align-items-center justify-content-between">
								<p class="mb-0">Published <strong><?php echo esc_html( get_the_date( 'jS M Y' ) ); ?></strong> | By <strong><?php echo esc_html( get_the_author_meta( 'display_name', $post->post_author ) ); ?></strong><br><span>Est. reading time: <?php echo esc_html( reading_time( $post ) ); ?></span></p>
								<?php echo get_avatar( $post->post_author, 42, 'wavatar', get_the_author_meta( 'display_name', $post->post_author ), array( 'class' => 'rounded-circle border border-primary bg-default' ) ); ?>
							</div>
						</div>

						<hr class="d-block d-md-none" data-aos="slide-right">

						<div data-aos="fade-up">
							<?php the_content(); ?>
						</div>

					</div>

				</div>
			</div>
		</div>
	</section>

	<?php if ( ! empty( $citcom_related_posts ) ) : ?>

		<section class="section-padding bg-default_lighter">

			<div class="container-xl">

				<h3 class="fs-2 mb-5" data-aos="fade-up">Related Posts</h3>

				<div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3 g-md-4">

					<?php
					foreach ( $citcom_related_posts as $post ) : // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
						setup_postdata( $post );
						$citcom_featured_image = get_post_thumbnail_id() ? get_post_thumbnail_id() : false;
						if ( ! $citcom_featured_image ) {
							$citcom_related_img    = get_field( 'page_header_image' );
							$citcom_featured_image = $citcom_related_img['id'] ?? 0;
						}
						$citcom_card_content  = get_the_excerpt() ? wp_trim_words( get_the_excerpt(), 30, '...' ) : wp_trim_words( get_the_content(), 30, '...' );
						$citcom_post_cats     = get_the_terms( $post->ID, 'category' ) ?: array();
						$citcom_post_tags     = get_the_terms( $post->ID, 'post_tag' );
						$citcom_post_cat_tags = ! empty( $citcom_post_tags ) && ! is_wp_error( $citcom_post_tags ) ? array_merge( $citcom_post_cats, $citcom_post_tags ) : $citcom_post_cats;
						?>

						<div class="col d-flex align-items-stretch">

							<div class="card blog-portrait-card" data-aos="zoom-in">
								<div class="card-body card-light d-flex flex-column justify-content-start">
									<div class="corner-link">
										<a href="<?php the_permalink(); ?>" class="d-block rounded-2 focus-ring text-nowrap">
											<div class="citdot btn btn-secondary btn-icon">
												<i class="fa-solid fa-arrow-up-right fa-lg fa-fw"></i>
											</div>
										</a>
									</div>
									<div class="card-img ratio ratio-4x3">
										<?php echo the_image( $citcom_featured_image, 'img-bg z-1 object-fit-cover position-absolute top-0 start-0 w-100 h-100', 'style="object-position: 50% 50%;"' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									</div>
									<div class="card-text w-100">
										<div class="d-flex align-items-start justify-content-between column-gap-3 row-gap-2">
											<div class="d-flex column-gap-2 row-gap-1 flex-wrap">
												<?php foreach ( $citcom_post_cat_tags as $citcom_term ) : ?>
													<a href="<?php echo esc_url( get_term_link( $citcom_term ) ); ?>" class="btn btn-tag"><?php echo esc_html( $citcom_term->name ); ?></a>
												<?php endforeach; ?>
											</div>
											<p class="mb-0 text-nowrap"><?php echo esc_html( reading_time( $post ) ); ?></p>
										</div>
										<h4 class="card-title pt-1"><a href="<?php the_permalink(); ?>" class="stretched-link focus-ring rounded-2"><?php the_title(); ?></a></h4>
										<p class="card-excerpt"><?php echo wp_kses_post( $citcom_card_content ); ?></p>
									</div>
								</div>
							</div>

						</div>

					<?php endforeach; ?>

				</div>

			</div>

		</section>

		<?php
		wp_reset_postdata();
	endif;

	// The "Blog post" template (Site Settings > Templates) supplies the sections after the article.
	citcom_render_template_post( $citcom_single_template );

	?>
</main>

<?php

get_footer();
