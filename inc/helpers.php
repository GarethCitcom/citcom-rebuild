<?php
/**
 * Template helpers ported from the original theme (functions/helpers.php,
 * functions/acf-options-cache.php, functions/lib/*, functions/theme_content/*).
 *
 * Only what the templates and blocks actually call is here.
 *
 * @package citcom
 */

defined( 'ABSPATH' ) || exit;

/**
 * All Site Settings options in one request-cached array.
 *
 * Replaces the original citcom_get_cached_options(); the ACFAllObj cache is gone.
 *
 * @return array<string,mixed>
 */
function citcom_get_cached_options(): array {
	static $options = null;

	if ( null !== $options ) {
		return $options;
	}

	$keys = array(
		'case_study_archive',
		'services_archive',
		'case_studies_tag_archive',
		'category_archive',
		'tag_archive',
		'blog_post',
		'logo_dark',
		'logo_light',
		'logo_dark_default',
		'logo_light_default',
		'social_icon_links',
		'footer_logos',
		'service_shapes',
	);

	$options = array();
	foreach ( $keys as $key ) {
		$options[ $key ] = function_exists( 'get_field' ) ? get_field( $key, 'option' ) : null;
	}

	return $options;
}

/**
 * One Site Settings option.
 *
 * @param string $key Field name.
 * @return mixed|null
 */
function citcom_get_option( string $key ) {
	$options = citcom_get_cached_options();
	return $options[ $key ] ?? null;
}

/**
 * Current archive post type name, or 'post' when not on an archive.
 *
 * @return string
 */
function get_archive_post_type(): string {
	if ( is_archive() ) {
		$object = get_queried_object();
		if ( $object && isset( $object->name ) ) {
			return (string) $object->name;
		}
	}
	return 'post';
}

/**
 * Trim content to a word count after running the_content filters.
 *
 * @param string $content Raw content.
 * @param int    $length  Word count.
 * @param string $suffix  Appended when trimmed.
 * @return string
 */
function custom_excerpt( $content, $length, $suffix = '' ) {
	if ( empty( $content ) ) {
		return '';
	}

	$words = is_int( $length ) ? $length : 20;

	$text = strip_shortcodes( $content );
	$text = apply_filters( 'the_content', $text );
	$text = str_replace( ']]>', ']]&gt;', $text );

	return wp_trim_words( $text, $words, $suffix );
}

/**
 * "x min read" estimate at 200 words per minute.
 *
 * @param WP_Post $post Post.
 * @return string
 */
function reading_time( $post ): string {
	$the_content = get_post_field( 'post_content', $post->ID );
	$words       = str_word_count( wp_strip_all_tags( $the_content ) );
	$minute      = (int) floor( $words / 200 );
	$second      = (int) floor( $words % 200 / ( 200 / 60 ) );
	$estimate    = 0 === $minute ? $second . ' sec' : $minute . ' min' . ( 1 === $minute ? '' : 's' );

	return $estimate . ' read';
}

/**
 * Post-process editor content the way the flexible layouts did:
 * Font Awesome list bullets, vlite video wrapper, gradient plain quotes.
 *
 * @param string $content HTML.
 * @return string
 */
function citdotLists( $content ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
	$content = str_replace( '<ul class="wp-block-list">', '<ul class="fa-ul" style="--fa-li-width: 3rem;">', $content );
	$content = str_replace( '<li>', '<li><span class="fa-li"><i class="fa-kit fa-citdot"></i></span>', $content );
	$content = str_replace( 'wp-block-video', 'wp-block-video v-vlite-container', $content );
	$content = str_replace( 'wp-block-quote is-style-plain', 'wp-block-quote is-style-plain grad-persian text-light', $content );
	return $content;
}

/**
 * Responsive <img> for an attachment with extra classes and attributes.
 *
 * Signature kept from the original theme so ported markup calls it unchanged.
 *
 * @param int    $attachment_id Attachment ID.
 * @param string $classes       Class attribute value.
 * @param string $attr          Extra attributes as a string, e.g. 'style="..." loading="lazy"'.
 * @param string $parent_class  Unused, kept for signature compatibility.
 * @return string
 */
function the_image( $attachment_id, $classes = '', $attr = '', $parent_class = '' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
	$attributes = array();

	if ( ! empty( $classes ) ) {
		$attributes['class'] = $classes;
	}

	if ( ! empty( $attr ) ) {
		preg_match_all( '/([\w-]+)=["\']([^"\']*)["\']/', $attr, $matches, PREG_SET_ORDER );
		foreach ( $matches as $match ) {
			$attributes[ $match[1] ] = $match[2];
		}
	}

	return wp_get_attachment_image( (int) $attachment_id, 'full', false, $attributes );
}

/**
 * Inline SVG markup for an attachment, read from the uploads directory.
 *
 * Fixes the original get-logos.php, which built the path from DOCUMENT_ROOT.
 *
 * @param int|string|null $attachment_id Attachment ID.
 * @return string Empty when the file is missing or not an SVG.
 */
function citcom_inline_svg( $attachment_id ): string {
	static $cache = array();

	$attachment_id = (int) $attachment_id;
	if ( ! $attachment_id ) {
		return '';
	}
	if ( isset( $cache[ $attachment_id ] ) ) {
		return $cache[ $attachment_id ];
	}

	$svg  = '';
	$file = get_attached_file( $attachment_id );
	if ( $file && 'image/svg+xml' === get_post_mime_type( $attachment_id ) && is_readable( $file ) ) {
		$svg = (string) file_get_contents( $file ); // phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown
	}

	$cache[ $attachment_id ] = $svg;
	return $svg;
}

/**
 * One of the four Site Settings logos as inline SVG.
 *
 * @param string $style dark|light|dark_default|light_default.
 * @return string
 */
function citcom_logo( string $style ): string {
	$map = array(
		'dark'          => 'logo_dark',
		'light'         => 'logo_light',
		'dark_default'  => 'logo_dark_default',
		'light_default' => 'logo_light_default',
	);
	if ( ! isset( $map[ $style ] ) ) {
		return '';
	}
	return citcom_inline_svg( citcom_get_option( $map[ $style ] ) );
}

/**
 * Inline SVG for a service shape from Site Settings.
 *
 * @param string $service creative|development|marketing|events|print|video.
 * @return string
 */
function serviceShapeSVG( $service ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
	$shapes = citcom_get_option( 'service_shapes' );
	if ( empty( $shapes[ $service ] ) ) {
		return '';
	}
	$id = attachment_url_to_postid( $shapes[ $service ] );
	return $id ? citcom_inline_svg( $id ) : '';
}

/**
 * Bootstrap breadcrumb. Markup lives in template-parts/breadcrumb.php.
 *
 * @param bool $home Show the home icon crumb.
 */
function get_breadcrumb( $home = true ) {
	get_template_part( 'template-parts/breadcrumb', null, array( 'home' => $home ) );
}

/**
 * Run a callback with another post set up as the global post.
 *
 * @param int|WP_Post $alternative_post Post to switch to.
 * @param callable    $callback         Receives the post.
 * @return mixed
 */
function override_post_context( $alternative_post, $callback ) {
	global $post;

	$previous = $post;
	$post     = get_post( $alternative_post ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

	if ( ! $post ) {
		$post = $previous; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		return null;
	}

	setup_postdata( $post );
	$return = call_user_func( $callback, $post );
	wp_reset_postdata();

	$post = $previous; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	if ( $post ) {
		setup_postdata( $post );
	}

	return $return;
}

/**
 * Render another post's block content in place.
 *
 * The original theme did this by looping the "template" post's flexible content
 * (archive.php, single.php, header/footer title lookups). Blocks now live in
 * post_content, so we run the_content on that post.
 *
 * @param int|string|null $post_id Template post ID (Site Settings > Templates).
 */
function citcom_render_template_post( $post_id ) {
	$post_id = (int) $post_id;
	if ( ! $post_id || ! get_post( $post_id ) ) {
		return;
	}
	override_post_context(
		$post_id,
		function () {
			the_content();
		}
	);
}

/**
 * Related posts by shared category or tag, cached for 12 hours.
 *
 * @param int|null $post_id Post ID.
 * @param int      $count   Number to return.
 * @return WP_Post[]
 */
function get_related_posts( $post_id = null, $count = 3 ) {
	if ( ! $post_id ) {
		$post_id = get_the_ID();
	}

	$cache_key = 'related_posts_' . $post_id . '_' . $count;
	$related   = get_transient( $cache_key );
	if ( false !== $related ) {
		return $related;
	}

	$cats    = get_the_terms( $post_id, 'category' );
	$cat_ids = $cats && ! is_wp_error( $cats ) ? wp_list_pluck( $cats, 'term_id' ) : array();
	$tags    = get_the_terms( $post_id, 'post_tag' );
	$tag_ids = $tags && ! is_wp_error( $tags ) ? wp_list_pluck( $tags, 'term_id' ) : array();

	$tax_query = array( 'relation' => 'OR' );
	if ( ! empty( $cat_ids ) ) {
		$tax_query[] = array(
			'taxonomy' => 'category',
			'field'    => 'term_id',
			'terms'    => $cat_ids,
		);
	}
	if ( ! empty( $tag_ids ) ) {
		$tax_query[] = array(
			'taxonomy' => 'post_tag',
			'field'    => 'term_id',
			'terms'    => $tag_ids,
		);
	}

	$args = array(
		'post__not_in'        => array( $post_id ),
		'posts_per_page'      => $count,
		'post_status'         => 'publish',
		'ignore_sticky_posts' => 1,
	);
	if ( count( $tax_query ) > 1 ) {
		$args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
	}

	$related = get_posts( $args );
	set_transient( $cache_key, $related, 12 * HOUR_IN_SECONDS );

	return $related;
}

add_action(
	'save_post',
	function ( $post_id ) {
		for ( $i = 1; $i <= 10; $i++ ) {
			delete_transient( 'related_posts_' . $post_id . '_' . $i );
		}
	}
);

/**
 * Numeric pagination (search results and display-posts).
 *
 * @param WP_Query    $wp_query Query.
 * @param string|bool $anchor   Optional anchor appended to page links.
 */
function theme_numeric_posts_nav( $wp_query, $anchor = false ) {
	$anchor_tag = $anchor ? $anchor : '';

	if ( $wp_query->max_num_pages <= 1 ) {
		return;
	}

	$paged = get_query_var( 'paged' ) ? absint( get_query_var( 'paged' ) ) : 1;
	$max   = (int) $wp_query->max_num_pages;
	$links = array();

	if ( $paged >= 1 ) {
		$links[] = $paged;
	}
	if ( $paged >= 3 ) {
		$links[] = $paged - 1;
	}
	if ( ( $paged + 2 ) <= $max ) {
		$links[] = $paged + 1;
	}

	echo '<nav class="pag-nav"><ul class="pagination justify-content-center gap-3 align-items-center">' . "\n";

	if ( get_previous_posts_link() ) {
		printf( '<li class="page-item">%s</li>' . "\n", get_previous_posts_link( '<span class="visually-hidden px-4 rounded-pill">Previous</span><i class="fa-regular fa-chevron-left"></i>' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	} else {
		echo '<li class="page-item disabled"><span class="page-link px-4 rounded-pill"><i class="fa-regular fa-chevron-left"></i></span></li>';
	}

	if ( ! in_array( 1, $links, true ) ) {
		$class = 1 === $paged ? ' class="page-item active"' : ' class="page-item"';
		printf( '<li%s><a href="%s" class="page-link num-link px-4 rounded-pill">%s</a></li>' . "\n", $class, esc_url( get_pagenum_link( 1 ) ) . $anchor_tag, '1' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		if ( ! in_array( 2, $links, true ) ) {
			echo '<li>…</li>';
		}
	}

	sort( $links );
	foreach ( (array) $links as $link ) {
		$class = $paged === $link ? ' class="page-item active"' : ' class="page-item"';
		printf( '<li%s><a href="%s" class="page-link num-link px-4 rounded-pill">%s</a></li>' . "\n", $class, esc_url( get_pagenum_link( $link ) . $anchor_tag ), (int) $link ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	if ( ! in_array( $max, $links, true ) ) {
		if ( ! in_array( $max - 1, $links, true ) ) {
			echo '<li>…</li>' . "\n";
		}
		$class = $paged === $max ? ' class="page-item active"' : ' class="page-item"';
		printf( '<li%s><a href="%s" class="page-link num-link px-4 rounded-pill">%s</a></li>' . "\n", $class, esc_url( get_pagenum_link( $max ) . $anchor_tag ), (int) $max ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	if ( (int) $wp_query->max_num_pages === $paged ) {
		echo '<li class="page-item disabled"><span class="page-link px-4 rounded-pill"><i class="fa-regular fa-chevron-right"></i></span></li>';
	} else {
		printf( '<li class="page-item">%s</li>' . "\n", get_next_posts_link( '<span class="visually-hidden px-4 rounded-pill">Next</span><i class="fa-regular fa-chevron-right"></i>', $wp_query->max_num_pages ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	echo '</ul></nav>' . "\n";
}

add_filter( 'next_posts_link_attributes', 'citcom_posts_link_attributes' );
add_filter( 'previous_posts_link_attributes', 'citcom_posts_link_attributes' );

/**
 * Class for the previous/next pagination anchors.
 *
 * @return string
 */
function citcom_posts_link_attributes() {
	return 'class="page-link"';
}

/**
 * Format 1000+ as 1k, 1.2m etc. (stats block).
 *
 * @param int|float $num Number.
 * @return string|int|float
 */
function thousandsCurrencyFormat( $num ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
	if ( $num > 1000 ) {
		$x               = round( $num );
		$x_number_format = number_format( $x );
		$x_array         = explode( ',', $x_number_format );
		$x_parts         = array( 'k', 'm', 'b', 't' );
		$x_count_parts   = count( $x_array ) - 1;
		$x_display       = $x_array[0] . ( (int) $x_array[1][0] !== 0 ? '.' . $x_array[1][0] : '' );
		$x_display      .= $x_parts[ $x_count_parts - 1 ];
		return $x_display;
	}
	return $num;
}
