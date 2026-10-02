<?php
/**
 * Post or case study card, ported from templates/card-post.php.
 *
 * Used inside the loop by citcom/display-posts and by the load more / search
 * ajax handlers in inc/ajax.php.
 *
 * @var array $args { data: { section_classes: string, post_type: string } }
 *
 * @package citcom
 */

global $post;

$citcom_data            = $args['data'] ?? array();
$citcom_section_classes = (string) ( $citcom_data['section_classes'] ?? '' );

// Cards on a coloured section use the light style.
$citcom_card_style = 'default';
foreach ( array( 'bg-primary', 'bg-secondary', 'bg-default_darker', 'bg-default', 'bg-default_lighter', 'bg-slate', 'bg-persian', 'bg-carrot' ) as $citcom_bg ) {
	if ( str_contains( $citcom_section_classes, $citcom_bg ) ) {
		$citcom_card_style = 'light';
	}
}

$citcom_post_type      = $post->post_type;
$citcom_card_type      = 'post' === $citcom_post_type ? 'blog-portrait-card' : 'case-study-card';
$citcom_card_img_ratio = 'post' === $citcom_post_type ? '4x3' : '16x9';
$citcom_card_flex      = 'post' === $citcom_post_type ? 'd-flex flex-column justify-content-start' : '';

$citcom_featured_image = get_post_thumbnail_id() ? get_post_thumbnail_id() : false;
if ( ! $citcom_featured_image ) {
	// The post id is explicit: inside a block (display-posts) a bare get_field() reads the block's own data.
	$citcom_post_img       = get_field( 'page_header_image', get_the_ID() );
	$citcom_featured_image = ! empty( $citcom_post_img['id'] ) ? (int) $citcom_post_img['id'] : (int) apply_filters( 'citcom_card_fallback_image', 439 );
	// Media library fallback, as before.
}

if ( 'post' === $citcom_post_type ) {
	$citcom_card_content   = get_the_excerpt() ? wp_trim_words( get_the_excerpt(), 30, '...' ) : wp_trim_words( get_the_content(), 30, '...' );
	$citcom_post_cats      = get_the_terms( $post->ID, 'category' );
	$citcom_post_tags      = get_the_terms( $post->ID, 'post_tag' );
	$citcom_post_cats      = $citcom_post_cats && ! is_wp_error( $citcom_post_cats ) ? $citcom_post_cats : array();
	$citcom_post_cats_tags = ! empty( $citcom_post_tags ) && ! is_wp_error( $citcom_post_tags ) ? array_merge( $citcom_post_cats, $citcom_post_tags ) : $citcom_post_cats;
} else {
	$citcom_post_cats_tags = get_the_terms( $post, 'cs-tag' );
	$citcom_card_style    .= ' align-items-start justify-content-start flex-column';
}

$citcom_post_cats_tags = ! $citcom_post_cats_tags || is_wp_error( $citcom_post_cats_tags ) ? array() : $citcom_post_cats_tags;

?>

<div class="col d-flex align-items-stretch">

	<div class="card <?php echo esc_attr( $citcom_card_type ); ?>" data-aos="zoom-in">
		<div class="card-body card-<?php echo esc_attr( $citcom_card_style ); ?> <?php echo esc_attr( $citcom_card_flex ); ?>">
			<?php if ( 'post' === $citcom_post_type ) : ?>
				<div class="corner-link">
					<a href="<?php the_permalink(); ?>" class="d-block rounded-2 focus-ring text-nowrap" aria-label="<?php echo esc_attr( get_the_title() ); ?>">
						<div class="citdot btn btn-secondary btn-icon">
							<i class="fa-solid fa-arrow-up-right fa-lg fa-fw"></i>
						</div>
					</a>
				</div>
			<?php endif; ?>
			<div class="card-img ratio ratio-<?php echo esc_attr( $citcom_card_img_ratio ); ?>">
				<?php echo the_image( $citcom_featured_image, 'img-bg z-1 object-fit-cover position-absolute top-0 start-0 w-100 h-100', 'style="object-position: 50% 50%;"' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
			<?php if ( 'post' === $citcom_post_type ) : ?>
				<div class="card-text w-100">
					<div class="d-flex align-items-start justify-content-between column-gap-3 row-gap-2">
						<div class="d-flex column-gap-2 row-gap-1 flex-wrap">
							<?php foreach ( $citcom_post_cats_tags as $citcom_term ) : ?>
								<a href="<?php echo esc_url( get_term_link( $citcom_term ) ); ?>" class="btn btn-tag"><?php echo esc_html( $citcom_term->name ); ?></a>
							<?php endforeach; ?>
						</div>
						<p class="mb-0 text-nowrap"><?php echo esc_html( reading_time( $post ) ); ?></p>
					</div>
					<h4 class="card-title pt-1"><a href="<?php the_permalink(); ?>" class="stretched-link focus-ring rounded-2"><?php the_title(); ?></a></h4>
					<p class="card-excerpt"><?php echo wp_kses_post( $citcom_card_content ); ?></p>
				</div>
			<?php else : ?>

				<div class="d-flex justify-content-between align-items-start gap-2 w-100">
					<div class="card-text">
						<h4 class="card-title"><?php the_title(); ?></h4>
						<div class="d-flex  column-gap-2 row-gap-1 flex-wrap">
							<?php foreach ( $citcom_post_cats_tags as $citcom_term ) : ?>
								<a href="<?php echo esc_url( get_term_link( $citcom_term ) ); ?>" class="btn btn-tag"><?php echo esc_html( $citcom_term->name ); ?></a>
							<?php endforeach; ?>
						</div>
					</div>
					<a href="<?php the_permalink(); ?>" class="d-block rounded-2 focus-ring stretched-link" aria-label="<?php echo esc_attr( get_the_title() ); ?>">
						<div class="citdot default-link btn btn-primary btn-icon">
							<i class="fa-solid fa-arrow-up-right fa-lg fa-fw"></i>
						</div>
					</a>
				</div>

			<?php endif; ?>

		</div>
	</div>

</div>
