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
