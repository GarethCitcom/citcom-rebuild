<?php
/**
 * Document title and the hand-over to the SEO plugin (SEOPress, docs/04-seopress.md).
 *
 * WordPress prints <title> (title-tag support) and the SEO plugin answers
 * through core's document title filter. The original header.php asked
 * SmartCrawl for the title itself; what it added on top is kept here: the
 * fallback when no SEO plugin is active, and the archives that take their
 * title from a template post.
 *
 * @package citcom
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'after_setup_theme',
	function () {
		add_theme_support( 'title-tag' );
	}
);

/*
 * Without an SEO plugin: "CitCom." on the front page, "Page name | CitCom."
 * everywhere else, as header.php printed with wp_title().
 */
add_filter(
	'document_title_separator',
	function () {
		return '|';
	}
);

add_filter(
	'document_title_parts',
	function ( $parts ) {
		if ( is_front_page() ) {
			return array( 'title' => 'CitCom.' );
		}
		unset( $parts['tagline'] );
		$parts['site'] = 'CitCom.';
		return $parts;
	}
);

/**
 * The template post that stands in for the archive being viewed.
 *
 * The case study and service archives, the case study tag filter and the blog
 * category and tag archives are laid out by a post of the `template` type
 * chosen in Site Settings. Its SEO title and description, when it has them,
 * are the archive's. The order is header.php's: a later match wins.
 *
 * @return int Post ID, 0 when the request is not one of those archives.
 */
function citcom_seo_archive_template_id(): int {
	$options = citcom_get_cached_options();
	$id      = 0;

	if ( is_post_type_archive( 'case-study' ) ) {
		$id = $options['case_study_archive'] ?? 0;
	}
	if ( is_post_type_archive( 'service' ) ) {
		$id = $options['services_archive'] ?? 0;
	}
	if ( is_tax( 'cs-tag' ) && 'case-study' === get_query_var( 'post_type' ) ) {
		$id = $options['case_studies_tag_archive'] ?? 0;
	}
	if ( is_category() ) {
		$id = $options['category_archive'] ?? 0;
	}
	if ( is_tag() ) {
		$id = $options['tag_archive'] ?? 0;
	}

	return is_object( $id ) ? (int) ( $id->ID ?? 0 ) : (int) $id;
}

/**
 * The template post's own SEOPress value for the archive being viewed.
 *
 * @param string $meta_key SEOPress post meta key.
 * @return string Escaped as SEOPress escapes its own values, '' when there is none.
 */
function citcom_seo_archive_template_value( string $meta_key ): string {
	$template_id = citcom_seo_archive_template_id();
	$value       = $template_id ? (string) get_post_meta( $template_id, $meta_key, true ) : '';

	if ( '' === $value ) {
		return '';
	}
	if ( false !== strpos( $value, '%%' ) && function_exists( 'seopress_get_service' ) ) {
		$value = (string) seopress_get_service( 'TagsToString' )->replace( $value, seopress_get_service( 'ContextPage' )->getContext() );
	}

	return esc_attr( $value );
}

add_filter(
	'seopress_titles_title',
	function ( $title ) {
		$custom = citcom_seo_archive_template_value( '_seopress_titles_title' );
		return '' !== $custom ? $custom : $title;
	}
);

add_filter(
	'seopress_titles_desc',
	function ( $description ) {
		$custom = citcom_seo_archive_template_value( '_seopress_titles_desc' );
		return '' !== $custom ? $custom : $description;
	}
);

/*
 * The case study filter (/case-studies/?cs-tag=print) is the tag's archive at
 * another address, and its canonical is the tag archive, as it was under
 * SmartCrawl. SEOPress drops the query string and would print /case-studies/.
 * A canonical set on the term itself is SEOPress's to print.
 */
add_filter(
	'seopress_titles_canonical',
	function ( $link ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reads which address was requested, nothing more.
		if ( ! is_tax( 'cs-tag' ) || ! isset( $_GET['cs-tag'] ) ) {
			return $link;
		}
		$term = get_queried_object();
		if ( ! $term instanceof WP_Term || get_term_meta( $term->term_id, '_seopress_robots_canonical', true ) ) {
			return $link;
		}
		$url = get_term_link( $term );
		if ( is_wp_error( $url ) ) {
			return $link;
		}
		if ( is_paged() ) {
			$url = user_trailingslashit( trailingslashit( $url ) . 'page/' . (int) get_query_var( 'paged' ) );
		}
		return '<link rel="canonical" href="' . esc_url( $url ) . '">';
	}
);

/*
 * Structured data the free SEOPress plugin does not print. SmartCrawl gave
 * blog posts an Article and the home page a LocalBusiness; SEOPress keeps
 * both in its paid plugin and prints only the organisation, on the home page.
 * With SEOPress PRO active this steps aside.
 */

/**
 * Article for a blog post.
 *
 * @param WP_Post $post The post.
 * @return array<string,mixed>
 */
function citcom_seo_article( WP_Post $post ): array {
	$url     = (string) get_permalink( $post );
	$author  = (string) get_the_author_meta( 'display_name', (int) $post->post_author );
	$article = array(
		'@type'            => 'Article',
		'@id'              => $url . '#article',
		'mainEntityOfPage' => $url,
		'headline'         => wp_strip_all_tags( get_the_title( $post ) ),
		'datePublished'    => get_post_time( 'c', false, $post ),
		'dateModified'     => get_post_modified_time( 'c', false, $post ),
		'publisher'        => array(
			'@type' => 'Organization',
			'name'  => get_bloginfo( 'name' ),
			'url'   => home_url( '/' ),
		),
	);

	// A display name that is an email address is an account nobody named; the site stands in.
	$article['author'] = '' === $author || is_email( $author )
		? $article['publisher']
		: array(
			'@type' => 'Person',
			'name'  => $author,
		);

	$image = get_post_thumbnail_id( $post ) ? wp_get_attachment_image_src( get_post_thumbnail_id( $post ), 'full' ) : false;
	if ( $image ) {
		$article['image'] = array(
			'@type'  => 'ImageObject',
			'url'    => $image[0],
			'width'  => (int) $image[1],
			'height' => (int) $image[2],
		);
	}

	return $article;
}

/**
 * LocalBusiness for the home page, from the `citcom_local_business` option.
 *
 * The option holds what was entered in SmartCrawl's schema builder (name,
 * telephone, image, map link, address, coordinates) and is written by
 * tools/seopress-from-smartcrawl.php. There it was three unconnected items;
 * here the address and coordinates sit inside the business, which is what
 * search engines read.
 *
 * @return array<string,mixed> Empty when the option is not set.
 */
function citcom_seo_local_business(): array {
	$business = get_option( 'citcom_local_business' );
	if ( ! is_array( $business ) || empty( $business['name'] ) ) {
		return array();
	}
	if ( ! empty( $business['image'] ) && is_numeric( $business['image'] ) ) {
		$business['image'] = (string) wp_get_attachment_url( (int) $business['image'] );
	}

	return array_filter(
		array_merge(
			array(
				'@type' => 'LocalBusiness',
				'@id'   => home_url( '/#local-business' ),
				'url'   => home_url( '/' ),
			),
			$business
		)
	);
}

add_action(
	'wp_head',
	function () {
		if ( defined( 'SEOPRESS_PRO_VERSION' ) ) {
			return;
		}
		$graph = array();
		if ( is_singular( 'post' ) && get_queried_object() instanceof WP_Post ) {
			$graph[] = citcom_seo_article( get_queried_object() );
		}
		if ( is_front_page() && ! is_paged() ) {
			$graph[] = citcom_seo_local_business();
		}
		$graph = array_values( array_filter( $graph ) );
		if ( ! $graph ) {
			return;
		}
		$data = array(
			'@context' => 'https://schema.org',
			'@graph'   => $graph,
		);
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON, with "<" and ">" encoded by JSON_HEX_TAG.
		echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG ) . "</script>\n";
	},
	20
);
