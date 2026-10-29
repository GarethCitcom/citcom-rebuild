<?php
/**
 * Load more and search for citcom/display-posts (was functions/ajax.php).
 *
 * The block prints a `displayPostsQuery` object (the WP_Query vars, current
 * and max page, card data); src/js/display-posts.js posts it back here. The
 * query vars are filtered to a known list before they reach WP_Query.
 *
 * @package citcom
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_ajax_loadmore', 'citcom_loadmore_ajax_handler' );
add_action( 'wp_ajax_nopriv_loadmore', 'citcom_loadmore_ajax_handler' );
add_action( 'wp_ajax_postsearch', 'citcom_postsearch_ajax_handler' );
add_action( 'wp_ajax_nopriv_postsearch', 'citcom_postsearch_ajax_handler' );

/**
 * Only these query vars are accepted from the browser.
 *
 * @param array $raw Posted query vars.
 * @return array
 */
function citcom_display_posts_query_args( array $raw ): array {
	$allowed = array(
		'post_type',
		'posts_per_page',
		'paged',
		's',
		'post__in',
		'orderby',
		'order',
		'category_name',
		'cat',
		'tag',
		'tag_id',
		'cs-tag',
		'pagename',
		'page',
		'name',
		'year',
		'monthnum',
		'day',
		'author',
		'author_name',
		'ignore_sticky_posts',
	);

	$args = array_intersect_key( $raw, array_flip( $allowed ) );

	$types = array( 'post', 'case-study', 'service' );
	if ( isset( $args['post_type'] ) ) {
		$requested         = array_map( 'sanitize_key', (array) $args['post_type'] );
		$args['post_type'] = array_values( array_intersect( $requested, $types ) ) ?: 'post';
		if ( is_array( $args['post_type'] ) && 1 === count( $args['post_type'] ) ) {
			$args['post_type'] = $args['post_type'][0];
		}
	}
	if ( isset( $args['post__in'] ) ) {
		$args['post__in'] = array_map( 'intval', (array) $args['post__in'] );
	}
	foreach ( array( 'posts_per_page', 'paged', 'cat', 'tag_id', 'year', 'monthnum', 'day', 'author', 'page' ) as $int ) {
		if ( isset( $args[ $int ] ) && '' !== $args[ $int ] ) {
			$args[ $int ] = (int) $args[ $int ];
		}
	}
	foreach ( array( 's', 'orderby', 'order', 'category_name', 'tag', 'cs-tag', 'pagename', 'name', 'author_name' ) as $text ) {
		if ( isset( $args[ $text ] ) && is_string( $args[ $text ] ) ) {
			$args[ $text ] = sanitize_text_field( wp_unslash( $args[ $text ] ) );
		}
	}
	if ( isset( $args['posts_per_page'] ) ) {
		$args['posts_per_page'] = max( 1, min( 24, (int) $args['posts_per_page'] ) );
	}

	$args['post_status'] = 'publish';

	return $args;
}

/**
 * The posted displayPostsQuery, or an empty array.
 *
 * @return array
 */
function citcom_display_posts_request(): array {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Public, read-only listing.
	$query = isset( $_POST['displayPostsQuery'] ) && is_array( $_POST['displayPostsQuery'] ) ? wp_unslash( $_POST['displayPostsQuery'] ) : array();
	return $query;
}

/**
 * Render the cards for a query.
 *
 * @param WP_Query $query Query.
 * @param array    $data  Card data (section_classes, post_type).
 * @return string
 */
function citcom_display_posts_cards( WP_Query $query, array $data ): string {
	if ( ! $query->have_posts() ) {
		return '';
	}
	update_object_term_cache( wp_list_pluck( $query->posts, 'ID' ), 'post' );
	ob_start();
	while ( $query->have_posts() ) {
		$query->the_post();
		get_template_part( 'template-parts/card-post', null, array( 'data' => $data ) );
	}
	wp_reset_postdata();
	return (string) ob_get_clean();
}

/**
 * Next page of the listing.
 */
function citcom_loadmore_ajax_handler() {
	$request = citcom_display_posts_request();
	$args    = citcom_display_posts_query_args( (array) ( $request['posts'] ?? array() ) );
	$data    = (array) ( $request['data'] ?? array() );

	$args['paged'] = (int) ( $request['cur_page'] ?? 1 ) + 1;
	if ( ! isset( $args['s'] ) ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$args['s'] = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) );
	}

	$query            = new WP_Query( $args );
	$request['posts'] = $args;

	wp_send_json_success(
		array(
			'displayPostsQuery' => $request,
			'return'            => citcom_display_posts_cards( $query, $data ),
		),
		200
	);
}

/**
 * Search within the listing.
 */
function citcom_postsearch_ajax_handler() {
	$request = citcom_display_posts_request();
	$args    = citcom_display_posts_query_args( (array) ( $request['posts'] ?? array() ) );
	$data    = (array) ( $request['data'] ?? array() );

	// phpcs:ignore WordPress.Security.NonceVerification.Missing
	$args['s'] = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) );

	$query            = new WP_Query( $args );
	$request['posts'] = $args;
	$response         = array(
		'displayPostsQuery' => $request,
		'return'            => citcom_display_posts_cards( $query, $data ),
	);
	if ( $query->have_posts() ) {
		$response['maxPages'] = $query->max_num_pages;
	}

	wp_send_json_success( $response, 200 );
}
