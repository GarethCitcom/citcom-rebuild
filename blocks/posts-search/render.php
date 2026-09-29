<?php
/**
 * citcom/posts-search (was the acf/posts-search block, blocks/posts-search.php).
 *
 * Sits in the blog sidebar widget area; src/js/display-posts.js submits it to
 * the postsearch ajax handler and replaces the listing.
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

?>

<div class="posts-search<?php echo esc_attr( $classes ); ?>">

	<?php if ( $is_preview ) : ?>

		<div style="background: #1C0221; color: #9AD14D; border-radius: 50rem; padding: 0.25rem 1.5rem;">
			<h4 style="display:flex;font-family: forma-djr-micro, sans-serif;">Posts Search <span class="dashicons dashicons-search" style="margin-left:auto;"></span></h4>
		</div>

	<?php else : ?>

		<form id="search-posts">
			<div class="input-group mb-5">
				<label for="search-posts-input" class="form-label visually-hidden">Search Posts</label>
				<input type="search" id="search-posts-input" name="search-posts-input" class="form-control form-control-lg border-0 focus-ring focus-ring-primary bg-default_lighter rounded-start-pill ps-4" placeholder="Search ..." aria-label="Search Posts" aria-describedby="search-posts-btn">
				<button type="submit" class="btn btn-default_lighter focus-ring focus-ring-primary border-0 rounded-end-pill ps-3 pe-4" id="search-posts-btn">
					<i class="fa-light fa-magnifying-glass fa-lg"></i>
					<i class="fa-solid fa-spinner fa-lg fa-spin-pulse"></i>
				</button>
			</div>
		</form>

	<?php endif; ?>

</div>
