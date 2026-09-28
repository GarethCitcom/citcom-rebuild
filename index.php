<?php
/**
 * Fallback template. The blog index renders the "Posts page" blocks, as the old
 * home.php did; anything else gets a plain loop.
 *
 * @package citcom
 */

get_header();

?>

<main>
	<?php
	if ( is_home() && get_option( 'page_for_posts' ) ) {
		citcom_render_template_post( get_option( 'page_for_posts' ) );
	} elseif ( have_posts() ) {
		while ( have_posts() ) {
			the_post();
			?>
			<section class="section-padding">
				<div class="container-xl">
					<?php the_title( '<h1>', '</h1>' ); ?>
					<?php the_content(); ?>
				</div>
			</section>
			<?php
		}
	}
	?>
</main>

<?php

get_footer();
