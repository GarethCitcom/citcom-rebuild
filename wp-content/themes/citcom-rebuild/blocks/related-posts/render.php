<?php
/**
 * citcom/related-posts (was the acf/related-posts block, blocks/related-posts.php).
 *
 * Posts related to the one being read, from get_related_posts(). On the old
 * site the global that fed this block was always empty, so it printed only its
 * wrapper; it follows the same switch as single.php (the
 * citcom_show_related_posts filter, off by default) and so does the same.
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

$related = array();
if ( ! $is_preview && apply_filters( 'citcom_show_related_posts', false ) ) {
	foreach ( (array) get_related_posts( get_the_ID(), 3 ) as $candidate ) {
		if ( $candidate instanceof WP_Post && get_the_ID() !== $candidate->ID ) {
			$related[] = $candidate;
		}
	}
}

global $post;

?>

<div class="related-posts<?php echo esc_attr( $classes ); ?>">

	<?php if ( $is_preview ) : ?>

		<div style="background: #1C0221; color: #9AD14D; border-radius: 50rem; padding: 0.25rem 1.5rem;">
			<h4 style="display:flex;font-family: forma-djr-micro, sans-serif;">Related Posts <span class="dashicons dashicons-randomize" style="margin-left:auto;"></span></h4>
		</div>

	<?php elseif ( ! empty( $related ) ) : ?>

		<div id="related-posts" class="rounded-3 p-4 mb-3 bg-default_lighter">
			<h4>Related Reads</h4>
			<div class="list-group list-group-flush">
				<?php
				foreach ( $related as $post ) : // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
					setup_postdata( $post );
					$categories = get_the_category();
					?>
					<a href="<?php the_permalink(); ?>" class="list-group-item bg-transparent ps-0 pe-0 d-flex flex-wrap align-items-baseline justify-content-between"><span class="title w-100 fw-normal"><?php the_title(); ?></span><span class="cat"><?php echo esc_html( $categories ? $categories[0]->name : '' ); ?></span><span class="data ml-auto"><small><?php echo esc_html( get_the_date( 'j M y' ) ); ?></small></span></a>
					<?php
				endforeach;
				wp_reset_postdata();
				?>
			</div>
		</div>

	<?php endif; ?>

</div>
