<?php
/**
 * Structured data: one JSON-LD graph on every front-end page.
 *
 * The theme owns the whole graph. SEOPress (free) would print a WebSite and,
 * on the home page, an Organization of its own, under the same @ids; both are
 * switched off below and their settings (SEO > Social Networks > Knowledge
 * Graph) are read here instead. See docs/04-seopress.md.
 *
 * Every page: Organization (#organization), WebSite (#website), one page node
 * (#webpage: WebPage, AboutPage, ContactPage or CollectionPage) and, except
 * on the home page, a BreadcrumbList (#breadcrumb) from the same trail as the
 * visible breadcrumb. Blog posts add an Article; the home page's Organization
 * is also the LocalBusiness entered in SmartCrawl's schema builder
 * (`citcom_local_business` option, written by tools/seopress-from-smartcrawl.php).
 * Nothing on 404 and search pages.
 *
 * @package citcom
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'seopress_schemas_website_html', '__return_empty_string' );

// SEOPress prints its Organization from a class method hooked at priority 2.
add_action(
	'wp_head',
	function () {
		global $wp_filter;
		foreach ( $wp_filter['wp_head']->callbacks[2] ?? array() as $hook ) {
			if ( is_array( $hook['function'] ) && is_object( $hook['function'][0] ) && 'SEOPress\Actions\Front\Schemas\PrintHeadJsonSchema' === get_class( $hook['function'][0] ) ) {
				remove_action( 'wp_head', $hook['function'], 2 );
			}
		}
	},
	1
);

/**
 * SEOPress's knowledge graph settings, by their short names.
 *
 * @return array<string,string>
 */
function citcom_schema_settings(): array {
	$settings = array();
	foreach ( (array) get_option( 'seopress_social_option_name', array() ) as $key => $value ) {
		if ( is_scalar( $value ) && 0 === strpos( (string) $key, 'seopress_social_' ) ) {
			$settings[ substr( (string) $key, 16 ) ] = trim( (string) $value );
		}
	}
	return $settings;
}

/**
 * A string as it should appear in JSON: no tags, no HTML entities.
 *
 * @param string $text Text, possibly escaped for HTML.
 * @return string
 */
function citcom_schema_text( string $text ): string {
	return trim( wp_specialchars_decode( wp_strip_all_tags( $text ), ENT_QUOTES ) );
}

/**
 * The address of the page being viewed, as its canonical form.
 *
 * @return string
 */
function citcom_schema_url(): string {
	global $wp;
	$url = '';
	if ( is_front_page() ) {
		$url = home_url( '/' );
	} elseif ( is_home() ) {
		$url = (string) get_permalink( (int) get_option( 'page_for_posts' ) );
	} elseif ( is_singular() ) {
		$url = (string) get_permalink();
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$link = get_term_link( get_queried_object() );
		$url  = is_wp_error( $link ) ? '' : $link;
	} elseif ( is_post_type_archive() ) {
		$url = (string) get_post_type_archive_link( (string) get_query_var( 'post_type' ) );
	}
	if ( '' === $url ) {
		$url = user_trailingslashit( home_url( $wp->request ) );
	}
	if ( is_paged() && ! is_singular() ) {
		$url = user_trailingslashit( trailingslashit( $url ) . 'page/' . (int) get_query_var( 'paged' ) );
	}
	return $url;
}

/**
 * The organisation, which on the home page is also the local business.
 *
 * @return array<string,mixed>
 */
function citcom_schema_organization(): array {
	$settings = citcom_schema_settings();
	$node     = array(
		'@type' => 'Organization',
		'@id'   => home_url( '/#organization' ),
		'name'  => '' !== ( $settings['knowledge_name'] ?? '' ) ? $settings['knowledge_name'] : get_bloginfo( 'name' ),
		'url'   => home_url( '/' ),
	);
	if ( ! empty( $settings['knowledge_img'] ) ) {
		$logo = array(
			'@type' => 'ImageObject',
			'@id'   => home_url( '/#logo' ),
			'url'   => $settings['knowledge_img'],
		);
		$id   = attachment_url_to_postid( $settings['knowledge_img'] );
		$size = $id ? wp_get_attachment_image_src( $id, 'full' ) : false;
		if ( $size && $size[1] && $size[2] ) {
			$logo['width']  = (int) $size[1];
			$logo['height'] = (int) $size[2];
		}
		$node['logo']  = $logo;
		$node['image'] = array( '@id' => $logo['@id'] );
	}
	if ( ! empty( $settings['knowledge_desc'] ) ) {
		$node['description'] = citcom_schema_text( $settings['knowledge_desc'] );
	}
	if ( ! empty( $settings['knowledge_phone'] ) ) {
		$node['contactPoint'] = array_filter(
			array(
				'@type'       => 'ContactPoint',
				'telephone'   => $settings['knowledge_phone'],
				'contactType' => $settings['knowledge_contact_type'] ?? '',
			)
		);
	}
	$same_as = array();
	foreach ( array( 'facebook', 'instagram', 'linkedin', 'youtube', 'pinterest' ) as $account ) {
		if ( ! empty( $settings[ 'accounts_' . $account ] ) ) {
			$same_as[] = $settings[ 'accounts_' . $account ];
		}
	}
	if ( ! empty( $settings['accounts_twitter'] ) ) {
		$same_as[] = 0 === strpos( $settings['accounts_twitter'], 'http' ) ? $settings['accounts_twitter'] : 'https://x.com/' . ltrim( $settings['accounts_twitter'], '@' );
	}
	if ( $same_as ) {
		$node['sameAs'] = $same_as;
	}

	// The local business, on the home page only: the same organisation, described once.
	$business = is_front_page() ? get_option( 'citcom_local_business' ) : null;
	if ( is_array( $business ) && ! empty( $business['name'] ) ) {
		$node['@type'] = array( 'Organization', 'LocalBusiness' );
		if ( $business['name'] !== $node['name'] ) {
			$node['alternateName'] = $business['name'];
		}
		if ( ! empty( $business['image'] ) ) {
			$node['image'] = is_numeric( $business['image'] ) ? (string) wp_get_attachment_url( (int) $business['image'] ) : $business['image'];
		}
		foreach ( array( 'telephone', 'hasMap', 'address', 'geo', 'priceRange', 'openingHours' ) as $key ) {
			if ( ! empty( $business[ $key ] ) ) {
				$node[ $key ] = $business[ $key ];
			}
		}
	}

	return $node;
}

/**
 * The site.
 *
 * @return array<string,mixed>
 */
function citcom_schema_website(): array {
	return array(
		'@type'      => 'WebSite',
		'@id'        => home_url( '/#website' ),
		'name'       => CITCOM_SEO_SITE_NAME,
		'url'        => home_url( '/' ),
		'publisher'  => array( '@id' => home_url( '/#organization' ) ),
		'inLanguage' => 'en-GB',
	);
}

/**
 * The page being viewed.
 *
 * @param string $url        Its address.
 * @param bool   $breadcrumb Whether a BreadcrumbList is in the graph to point at.
 * @return array<string,mixed>
 */
function citcom_schema_page( string $url, bool $breadcrumb ): array {
	$type = 'WebPage';
	if ( is_archive() || is_home() ) {
		$type = 'CollectionPage';
	} elseif ( is_page( 'about-us' ) ) {
		$type = 'AboutPage';
	} elseif ( is_page( 'contact-us' ) ) {
		$type = 'ContactPage';
	}

	$node = array(
		'@type'      => $type,
		'@id'        => $url . '#webpage',
		'url'        => $url,
		'name'       => citcom_schema_text( wp_get_document_title() ),
		'isPartOf'   => array( '@id' => home_url( '/#website' ) ),
		'inLanguage' => 'en-GB',
	);

	$description = function_exists( 'seopress_titles_the_description_content' ) ? citcom_schema_text( (string) seopress_titles_the_description_content() ) : '';
	if ( '' !== $description ) {
		$node['description'] = $description;
	}

	if ( is_singular() ) {
		$post                  = get_queried_object();
		$node['datePublished'] = get_post_time( 'c', false, $post );
		$node['dateModified']  = get_post_modified_time( 'c', false, $post );
		$image                 = citcom_schema_image( get_post_thumbnail_id( $post ), $url . '#primaryimage' );
		if ( $image ) {
			$node['primaryImageOfPage'] = $image;
		}
	}

	if ( 'CollectionPage' === $type ) {
		global $wp_query;
		$elements = array();
		foreach ( (array) $wp_query->posts as $position => $item ) {
			$elements[] = array(
				'@type'    => 'ListItem',
				'position' => $position + 1,
				'url'      => (string) get_permalink( $item ),
			);
		}
		if ( $elements ) {
			$node['mainEntity'] = array(
				'@type'           => 'ItemList',
				'itemListElement' => $elements,
			);
		}
	}

	if ( $breadcrumb ) {
		$node['breadcrumb'] = array( '@id' => $url . '#breadcrumb' );
	}

	return $node;
}

/**
 * An attachment as an ImageObject.
 *
 * @param int    $attachment_id Attachment, 0 for none.
 * @param string $id            The node's @id.
 * @return array<string,mixed> Empty when there is no image.
 */
function citcom_schema_image( int $attachment_id, string $id ): array {
	$image = $attachment_id ? wp_get_attachment_image_src( $attachment_id, 'full' ) : false;
	if ( ! $image ) {
		return array();
	}
	return array(
		'@type'  => 'ImageObject',
		'@id'    => $id,
		'url'    => $image[0],
		'width'  => (int) $image[1],
		'height' => (int) $image[2],
	);
}

/**
 * The breadcrumb trail, from the same data as the visible one.
 *
 * A trail of one item (the home link alone, as on a landing page or a date
 * archive) is no trail, and neither the list nor the page's reference to it
 * is printed.
 *
 * @param string $url The page's address.
 * @return array<string,mixed> Empty when there is no trail to show.
 */
function citcom_schema_breadcrumb( string $url ): array {
	$elements = array();
	foreach ( citcom_breadcrumb_items() as $item ) {
		$element = array(
			'@type'    => 'ListItem',
			'position' => count( $elements ) + 1,
			'name'     => citcom_schema_text( (string) $item['name'] ),
		);
		if ( ! empty( $item['url'] ) ) {
			$element['item'] = $item['url'];
		} elseif ( empty( $item['current'] ) ) {
			continue;
		}
		$elements[] = $element;
	}
	if ( count( $elements ) < 2 ) {
		return array();
	}
	return array(
		'@type'           => 'BreadcrumbList',
		'@id'             => $url . '#breadcrumb',
		'itemListElement' => $elements,
	);
}

/**
 * A blog post as an Article.
 *
 * @param WP_Post $post The post.
 * @param string  $url  Its address.
 * @return array<string,mixed>
 */
function citcom_schema_article( WP_Post $post, string $url ): array {
	$author      = (string) get_the_author_meta( 'display_name', (int) $post->post_author );
	$article     = array(
		'@type'            => 'Article',
		'@id'              => $url . '#article',
		'mainEntityOfPage' => array( '@id' => $url . '#webpage' ),
		'headline'         => citcom_schema_text( get_the_title( $post ) ),
		'datePublished'    => get_post_time( 'c', false, $post ),
		'dateModified'     => get_post_modified_time( 'c', false, $post ),
		'publisher'        => array( '@id' => home_url( '/#organization' ) ),
	);
	$description = function_exists( 'seopress_titles_the_description_content' ) ? citcom_schema_text( (string) seopress_titles_the_description_content() ) : '';
	if ( '' === $description ) {
		$description = citcom_schema_text( get_the_excerpt( $post ) );
	}
	if ( '' !== $description ) {
		$article['description'] = $description;
	}
	// A display name that is an email address is an account nobody named; the organisation stands in.
	$article['author'] = '' === $author || is_email( $author )
		? array( '@id' => home_url( '/#organization' ) )
		: array(
			'@type' => 'Person',
			'name'  => $author,
		);
	$image             = citcom_schema_image( get_post_thumbnail_id( $post ), $url . '#primaryimage' );
	if ( $image ) {
		$article['image'] = array( '@id' => $image['@id'] );
	}
	return $article;
}

/**
 * The whole graph for the current request.
 *
 * @return array<int,array<string,mixed>> Empty when the page gets none.
 */
function citcom_schema_graph(): array {
	if ( is_404() || is_search() || is_feed() || is_embed() || is_robots() ) {
		return array();
	}
	$url        = citcom_schema_url();
	$breadcrumb = is_front_page() ? array() : citcom_schema_breadcrumb( $url );
	$graph      = array(
		citcom_schema_organization(),
		citcom_schema_website(),
		citcom_schema_page( $url, (bool) $breadcrumb ),
		$breadcrumb,
	);
	if ( is_singular( 'post' ) && get_queried_object() instanceof WP_Post ) {
		$graph[] = citcom_schema_article( get_queried_object(), $url );
	}
	return array_values( array_filter( (array) apply_filters( 'citcom_schema_graph', array_filter( $graph ), $url ) ) );
}

add_action(
	'wp_head',
	function () {
		$graph = citcom_schema_graph();
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
