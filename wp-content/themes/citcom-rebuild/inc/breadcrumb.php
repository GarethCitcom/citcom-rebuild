<?php
/**
 * The breadcrumb trail as data, for the visible breadcrumb
 * (template-parts/breadcrumb.php) and the BreadcrumbList (inc/schema.php).
 *
 * @package citcom
 */

defined( 'ABSPATH' ) || exit;

/**
 * The trail for the current request, in the order the original theme showed it.
 *
 * Each item has `name` and `url`; the home item also has `icon` (shown as a
 * house, named "Home" in data), the last item `current`. The search item
 * carries its own `html`.
 *
 * @param bool $home Start with the home link.
 * @return array<int,array<string,mixed>>
 */
function citcom_breadcrumb_items( bool $home = true ): array {
	$post    = get_post();
	$options = citcom_get_cached_options();
	$items   = array();

	$blog_page = (int) get_option( 'page_for_posts' );
	$blog      = array(
		'name' => get_the_title( $blog_page ),
		'url'  => (string) get_permalink( $blog_page ),
	);
	$cases     = array(
		'name' => get_the_title( $options['case_study_archive'] ),
		'url'  => (string) get_post_type_archive_link( 'case-study' ),
	);
	$services  = array(
		'name' => get_the_title( $options['services_archive'] ),
		'url'  => (string) get_post_type_archive_link( 'service' ),
	);

	if ( $home ) {
		$items[] = array(
			'name' => 'Home',
			'url'  => home_url(),
			'icon' => true,
		);
	}
	if ( is_singular( 'post' ) || is_category() || is_tag() ) {
		$items[] = $blog;
	}
	if ( is_singular( 'case-study' ) || is_tax( 'cs-tag' ) ) {
		$items[] = $cases;
	}
	if ( is_singular( 'service' ) ) {
		$items[] = $services;
	}
	if ( is_category() || is_tag() || is_tax( 'cs-tag' ) ) {
		$items[] = array(
			'name'    => ( is_category() ? 'Category: ' : 'Tag: ' ) . get_queried_object()->name,
			'url'     => '',
			'current' => true,
		);
	}
	if ( is_singular( 'post' ) && $post && has_category( '', $post->ID ) ) {
		$category = get_the_category( $post->ID )[0];
		$items[]  = array(
			'name' => $category->name,
			'url'  => (string) get_term_link( $category ),
		);
	}
	if ( $post && $post->post_parent ) {
		$parents   = array();
		$parent_id = $post->post_parent;
		while ( $parent_id ) {
			$page      = get_post( $parent_id );
			$parents[] = array(
				'name' => get_the_title( $page->ID ),
				'url'  => (string) get_permalink( $page->ID ),
			);
			$parent_id = $page->post_parent;
		}
		$items = array_merge( $items, array_reverse( $parents ) );
	}
	if ( is_singular( array( 'post', 'page', 'case-study', 'service' ) ) ) {
		$items[] = array(
			'name'    => get_the_title(),
			'url'     => '',
			'current' => true,
		);
	}
	if ( is_home() ) {
		$items[] = array_merge( $blog, array( 'current' => true ) );
	}
	if ( is_archive() && 'service' === get_archive_post_type() ) {
		$items[] = array_merge( $services, array( 'current' => true ) );
	}
	if ( is_archive() && 'case-study' === get_archive_post_type() ) {
		$items[] = array_merge( $cases, array( 'current' => true ) );
	}
	if ( is_search() ) {
		$items[] = array(
			'name'    => 'Search Results for... ' . get_search_query(),
			'html'    => 'Search Results for... <em>' . esc_html( get_search_query() ) . '</em>',
			'url'     => '',
			'current' => true,
		);
	}

	return $items;
}
