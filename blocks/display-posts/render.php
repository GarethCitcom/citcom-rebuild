<?php
/**
 * citcom/display-posts
 *
 * Mirrors flexPosts() (templates/flexFunctions/display_posts.php). Three modes:
 * "archive" loops the main query (archive pages and the Posts page, with the
 * blog sidebar for blog listings and a Load more button), "latest" queries the
 * newest posts of a type, "choice" shows selected posts in the chosen order.
 * Cards come from template-parts/card-post.php. After the section a small
 * inline script hands the query to src/js/display-posts.js for load more and
 * search (see inc/ajax.php).
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

$type          = ! empty( $fields['type_of_display'] ) ? (string) $fields['type_of_display'] : 'archive';
$is_archive    = false;
$archive_blog  = false;
$posts_class   = '';
$posts_per_row = 'row-cols-1 row-cols-md-3 g-3 g-md-4 justify-content-center';
$post_type     = '';
$the_query     = null;

if ( 'latest' === $type ) {
	$no_of_posts = (int) ( $fields['number_of_posts_to_show'] ?? 3 );
	$post_type   = (string) ( $fields['post_type'] ?? 'post' );
} elseif ( 'choice' === $type ) {
	$post_type = (string) ( $fields['post_type'] ?? 'post' );
	$chosen    = 'post' === $post_type ? ( $fields['posts'] ?? array() ) : ( $fields['posts_cs'] ?? array() );
	$chosen    = array_filter( array_map( 'intval', (array) $chosen ) );
} else {
	$is_archive = true;
	$post_type  = get_archive_post_type();
	global $wp_query;
	$the_query     = $wp_query;
	$posts_per_row = 'row-cols-1 row-cols-md-3 g-3 g-md-4';
	if ( 'post' === get_archive_post_type() || is_category() || is_tag() ) {
		$archive_blog  = true;
		$post_type     = 'post';
		$posts_class   = 'col-md-7 col-lg-9';
		$posts_per_row = 'row-cols-1 row-cols-lg-2 g-3 g-md-4';
	}
}

if ( ! $is_archive ) {
	$query_args = array(
		'post_type'           => $post_type,
		'ignore_sticky_posts' => 1,
	);
	if ( 'latest' === $type ) {
		$query_args['posts_per_page'] = $no_of_posts;
	}
	if ( 'choice' === $type ) {
		$query_args['post__in'] = $chosen ? $chosen : array( 0 );
		$query_args['order']    = 'ASC';
		$query_args['orderby']  = 'post__in';
	}
	$the_query = new WP_Query( $query_args );
}

// The editor has no archive query; show the latest posts of the type instead.
if ( $is_preview && $is_archive && ( ! $the_query || ! $the_query->have_posts() ) ) {
	$the_query = new WP_Query(
		array(
			'post_type'      => in_array( $post_type, array( 'post', 'case-study' ), true ) ? $post_type : 'post',
			'posts_per_page' => 3,
		)
	);
}

$post_type = $post_type ? $post_type : get_archive_post_type();

$sidebar         = ( $is_archive && $archive_blog ) || is_category() || is_tag();
$sidebar_padding = $sidebar ? 'ps-0 ps-lg-5' : '';

// What the card template and the ajax handlers need to know about this block.
$card_data = array(
	'section_classes' => $attrs['classes'],
	'post_type'       => $post_type,
	'type_of_display' => $type,
);

?>

<section <?php echo $attrs['anchor_attr']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="section-padding flex-display_posts <?php echo esc_attr( $attrs['classes'] ); ?>" data-index="<?php echo (int) $index; ?>">

	<div class="container-xl">
		<div class="row">
			<?php if ( $sidebar ) : ?>
				<div class="col">
					<div id="blog-sidebar" class="pe-0 pe-md-5 pe-lg-0 pb-5 pb-md-0" data-aos="fade-up">
						<div class="d-grid">
							<button class="btn btn-outline-primary rounded-pill px-4 d-md-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasResponsive" aria-controls="offcanvasResponsive"><i class="fa-regular fa-filter-list pe-2"></i> Blog Filters</button>
						</div>
						<div class=" offcanvas-md offcanvas-top" tabindex="-1" id="offcanvasResponsive" aria-labelledby="offcanvasResponsiveLabel">
							<div class="offcanvas-header">
								<h5 class="offcanvas-title" id="offcanvasResponsiveLabel"><i class="fa-regular fa-filter-list pe-2"></i> Blog Filters</h5>
								<button type="button" class="btn-close close-blog-filters" data-bs-dismiss="offcanvas" data-bs-target="#offcanvasResponsive" aria-label="Close"></button>
							</div>
							<div class="offcanvas-body flex-column">
								<?php dynamic_sidebar( 'blog_sidebar' ); ?>
							</div>
						</div>

					</div>
				</div>
			<?php endif; ?>
			<div class="col-12 <?php echo esc_attr( $posts_class ); ?>">

				<div id="display-posts" class="<?php echo esc_attr( $sidebar_padding ); ?>">

					<?php if ( $the_query && $the_query->have_posts() ) : ?>

						<?php
						// Prime term caches so the cards do not query terms one by one.
						$post_ids = wp_list_pluck( $the_query->posts, 'ID' );
						if ( 'post' === $post_type ) {
							update_object_term_cache( $post_ids, 'post' );
						} elseif ( 'case-study' === $post_type ) {
							update_object_term_cache( $post_ids, 'case-study' );
						}
						?>

						<div class="row <?php echo esc_attr( $posts_per_row ); ?>">


							<?php
							while ( $the_query->have_posts() ) :
								$the_query->the_post();
								get_template_part( 'template-parts/card-post', null, array( 'data' => $card_data ) );
							endwhile;
							?>

							<?php if ( $is_archive && ! $is_preview && 1 < $the_query->max_num_pages ) : ?>
								<div class="citcom_loadmore btn btn-outline-secondary rounded-pill px-4 mx-auto mt-5">Load more</div>
							<?php endif; ?>

						</div>

					<?php elseif ( $is_preview ) : ?>
						<p><em><?php esc_html_e( 'No posts to show yet.', 'citcom' ); ?></em></p>
					<?php endif; ?>

				</div>
			</div>
		</div>
	</div>

</section>

<?php
if ( ! $is_preview && $the_query ) {
	$page = get_query_var( 'paged' ) ? ( 0 === (int) get_query_var( 'paged' ) ? 1 : (int) get_query_var( 'paged' ) ) : 1;

	$load_more_query = array(
		'posts'    => $the_query->query,
		'cur_page' => $page,
		'max_page' => $the_query->max_num_pages,
		'data'     => $card_data,
	);
	?>

	<script type="text/javascript">
		var displayPostsQuery = <?php echo wp_json_encode( $load_more_query ); ?>
	</script>

	<?php
}
wp_reset_postdata();
