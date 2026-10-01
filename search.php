<?php
/**
 * Search results, ported 1:1 from the original search.php.
 *
 * @package citcom
 */

get_header();

?>

<main>

	<section class="section-padding flex-page_header d-flex align-items-center position-relative pattern pattern-persian bg-default_lighter">

		<div class="page-header w-100">
			<div class="container-xl">
				<h1 class="page-header-title position-relative z-2 rounded-1 bg-default_lighter"><?php printf( 'Search Results for: %s', '<span class="">"' . esc_html( get_search_query() ) . '"</span>' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h1>
			</div>
		</div>

	</section>

	<section class="section-padding">

		<div class="container-xl">
			<div class="row">
				<div class="col">
					<div id="blog-sidebar" class="pe-0 pe-md-5 pe-lg-0 pb-5 pb-md-0" data-aos="fade-up">
						<div class="d-grid">
							<button class="btn btn-outline-primary rounded-pill px-4 d-md-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasResponsive" aria-controls="offcanvasResponsive"><i class="fa-regular fa-sidebar pe-2"></i> Sidebar</button>
						</div>
						<div class=" offcanvas-md offcanvas-top" tabindex="-1" id="offcanvasResponsive" aria-labelledby="offcanvasResponsiveLabel">
							<div class="offcanvas-header">
								<h5 class="offcanvas-title" id="offcanvasResponsiveLabel"><i class="fa-regular fa-sidebar pe-2"></i> Sidebar</h5>
								<button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#offcanvasResponsive" aria-label="Close"></button>
							</div>
							<div class="offcanvas-body flex-column">
								<?php dynamic_sidebar( 'search_sidebar' ); ?>
							</div>
						</div>

					</div>
				</div>
				<div class="col-12 col-md-7 col-lg-9">
					<div class="ps-md-5">
						<?php if ( have_posts() ) : ?>

							<?php
							while ( have_posts() ) :
								the_post();

								switch ( get_post_type() ) {
									case 'page':
										$citcom_icon        = 'fa-regular fa-globe';
										$citcom_result_type = 'Web page';
										break;
									case 'post':
										$citcom_icon        = 'fa-regular fa-blog';
										$citcom_result_type = 'Blog';
										break;
									case 'case-study':
										$citcom_icon        = 'fa-regular fa-briefcase';
										$citcom_result_type = 'Case Study';
										break;
									case 'service':
										$citcom_icon        = 'fa-regular fa-hand-holding-heart';
										$citcom_result_type = 'Services';
										break;
									default:
										$citcom_icon        = 'fa-regular fa-citdot';
										$citcom_result_type = 'Result';
										break;
								}//end switch

								$citcom_excerpt = custom_excerpt( get_the_content(), 30, ' ...' );
								?>

								<div class="s-result position-relative d-flex align-items-start gap-3 px-2" data-bs-toggle="tooltip" data-bs-title="<?php echo esc_attr( $citcom_result_type ); ?>" data-bs-placement="left">

									<div class="icon citdot bg-primary text-secondary p-2">
										<i class="<?php echo esc_attr( $citcom_icon ); ?> fa-lg"></i>
									</div>

									<div class="text flex-shrink-1">
										<h5 class="mb-2"><a href="<?php the_permalink(); ?>" class="stretched-link link-underline  link-offset-2 link-underline-opacity-0 link-underline-opacity-75-hover"><?php the_title(); ?></a></h5>
										<p class="opacity-50 lh-1 text-slate"><small><?php the_permalink(); ?></small></p>
										<p class="mb-0"><?php echo wp_kses_post( $citcom_excerpt ); ?></p>
									</div>

									<?php if ( has_post_thumbnail() ) : ?>
										<div class="f-img ms-auto d-none d-md-block flex-grow-1" style="max-width:5rem;">
											<?php echo get_the_post_thumbnail( get_the_ID(), 'thumbnail', array( 'class' => 'img-thumbnail img-fluid' ) ); ?>
										</div>
									<?php endif; ?>

								</div>

								<hr class="border border-default_lighter">

							<?php endwhile; ?>

						<?php else : ?>

							<p class="lead">No results found</p>

						<?php endif; ?>

						<div id="pag-container" class="mt-5">
							<?php
							global $wp_query;
							theme_numeric_posts_nav( $wp_query );
							?>
						</div>
					</div>
				</div>
			</div>
		</div>

	</section>

</main>

<?php

get_footer();
