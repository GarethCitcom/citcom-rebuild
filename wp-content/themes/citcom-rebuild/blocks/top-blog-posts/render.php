<?php
/**
 * citcom/top-blog-posts (was the acf/top-blog-posts block, blocks/top-blog-posts.php).
 *
 * Up to three hand-picked posts. Lives in the blog listing sidebar widget area.
 *
 * @var array  $block      Block settings and attributes.
 * @var string $content    Inner HTML (unused).
 * @var bool   $is_preview True in the editor.
 * @var int    $post_id    Post the block is saved to.
 *
 * @package citcom
 */

defined( 'ABSPATH' ) || exit;

$classes = '';
if ( ! empty( $block['className'] ) ) {
	$classes .= ' ' . $block['className'];
}
if ( $is_preview ) {
	$classes .= ' preview-block';
}

$title     = (string) get_field( 'title' );
$top_posts = array_filter( (array) get_field( 'posts' ), static fn( $p ) => $p instanceof WP_Post );

global $post;

?>

<div class="top-blog-posts<?php echo esc_attr( $classes ); ?>">

	<?php if ( $is_preview ) : ?>

		<div style="background: #1C0221; color: #9AD14D; border-radius: 50rem; padding: 0.25rem 1.5rem;">
			<h4 style="display:flex;font-family: forma-djr-micro, sans-serif;">Top Blog Posts <span class="dashicons dashicons-editor-ol" style="margin-left:auto;"></span></h4>
		</div>

	<?php else : ?>

		<div id="top-blog-posts" class="rounded-3 p-4 mb-3 bg-default_lighter">
			<h4><?php echo esc_html( $title ); ?></h4>
			<div class="list-group list-group-flush list-group-numbered">
				<?php
				foreach ( $top_posts as $post ) : // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
					setup_postdata( $post );
					$categories = get_the_category();
					?>
					<a href="<?php the_permalink(); ?>" class="list-group-item bg-transparent ps-4 pe-0 d-flex flex-wrap align-items-baseline justify-content-between"><span class="title w-100 fw-normal"><?php the_title(); ?></span><span class="cat"><?php echo esc_html( $categories ? $categories[0]->name : '' ); ?></span><span class="data ml-auto"><small><?php echo esc_html( get_the_date( 'j M y' ) ); ?></small></span></a>
					<?php
				endforeach;
				wp_reset_postdata();
				?>
			</div>
		</div>

	<?php endif; ?>

</div>
