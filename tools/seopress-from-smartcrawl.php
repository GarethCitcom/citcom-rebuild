<?php
/**
 * Copies what SmartCrawl holds into SEOPress: the title and description
 * templates, the social and sitemap settings, and every page's and term's own
 * title, description, canonical, robots and social values.
 *
 * SEOPress has an importer of its own (SEO > Tools > Plugins). This is used
 * instead because that importer, as of SEOPress 10.3:
 * - skips a term's noindex (it expects the word "noindex", SmartCrawl stores 1),
 *   which would open six case study tag archives to indexing;
 * - compares settings with `1 === $value`, so a flag stored as true or "1" is
 *   lost (the noindex on the `template` post type);
 * - leaves SEOPress's own defaults in place where SmartCrawl had nothing, and
 *   those defaults noindex the blog tag archives;
 * - does not carry the primary category, the default share images or the
 *   address.
 *
 * SmartCrawl's data is only read, never changed, so going back is a matter of
 * reactivating SmartCrawl. Run it with both the theme and SEOPress loaded:
 *
 *   wp db export ~/pre-seopress.sql
 *   wp eval-file tools/seopress-from-smartcrawl.php https://example.com dry-run
 *   wp eval-file tools/seopress-from-smartcrawl.php https://example.com
 *
 * Add "force" to run it again over values edited in SEOPress since.
 * Redirects are copied only when SEOPress PRO is active (its redirections
 * live in a post type the free plugin does not have); otherwise they are
 * listed. See docs/04-seopress.md.
 *
 * @package citcom
 */

if ( ! defined( 'WP_CLI' ) ) {
	exit;
}

global $wpdb;

if ( empty( $args[0] ) || untrailingslashit( (string) $args[0] ) !== untrailingslashit( (string) get_option( 'siteurl' ) ) ) {
	WP_CLI::error( 'Pass the URL of this site as the first argument. This site is ' . get_option( 'siteurl' ) . '.' );
}
if ( ! function_exists( 'seopress_get_service' ) ) {
	WP_CLI::error( 'SEOPress is not active.' );
}
if ( ! post_type_exists( 'service' ) ) {
	WP_CLI::error( 'The theme is not loaded (run without --skip-themes): its post types decide which settings are copied.' );
}
$dry = in_array( 'dry-run', $args, true );
if ( ! $dry && get_option( 'citcom_seopress_migrated' ) && ! in_array( 'force', $args, true ) ) {
	WP_CLI::error( 'Already run on ' . get_option( 'citcom_seopress_migrated' ) . '. Add "force" to run it again.' );
}

$log   = array();
$truth = function ( $value ): bool {
	return true === $value || 1 === $value || '1' === $value || 'on' === $value || 'noindex' === $value || 'nofollow' === $value;
};

// SmartCrawl's macros in SEOPress's names (the list in SEOPress's importer, plus the ones it leaves out).
$translate = function ( $value ): string {
	return sanitize_text_field(
		strtr(
			(string) $value,
			array(
				'%%title%%'                => '%%post_title%%',
				'%%sitename%%'             => '%%sitetitle%%',
				'%%sitedesc%%'             => '%%tagline%%',
				'%%excerpt%%'              => '%%post_excerpt%%',
				'%%searchphrase%%'         => '%%search_keywords%%',
				'%%category%%'             => '%%_category_title%%',
				'%%category_description%%' => '%%_category_description%%',
				'%%tag%%'                  => '%%tag_title%%',
				'%%name%%'                 => '%%post_author%%',
				'%%user_description%%'     => '%%author_bio%%',
				'%%pt_plural%%'            => '%%cpt_plural%%',
				'%%date%%'                 => '%%archive_date_month_name%% %%archive_date_year%%', // %%archive_date%% prints "0 - 2026" on a year archive.
			)
		)
	);
};
$image_url = function ( $image ): string {
	if ( is_numeric( $image ) && (int) $image > 0 ) {
		return (string) wp_get_attachment_url( (int) $image );
	}
	return is_string( $image ) && preg_match( '#^https?://#', $image ) ? $image : '';
};

$same_path = function ( string $a, string $b ): bool {
	return untrailingslashit( (string) wp_parse_url( $a, PHP_URL_PATH ) ) === untrailingslashit( (string) wp_parse_url( $b, PHP_URL_PATH ) );
};

$onpage   = (array) get_option( 'wds_onpage_options', array() );
$social   = (array) get_option( 'wds_social_options', array() );
$schema   = (array) get_option( 'wds_schema_options', array() );
$sitemap  = (array) get_option( 'wds_sitemap_options', array() );
$settings = (array) get_option( 'wds_settings_options', array() );
if ( ! $onpage ) {
	WP_CLI::error( 'No SmartCrawl settings found (wds_onpage_options).' );
}

$sp_titles   = (array) get_option( 'seopress_titles_option_name', array() );
$sp_social   = (array) get_option( 'seopress_social_option_name', array() );
$sp_sitemap  = (array) get_option( 'seopress_xml_sitemap_option_name', array() );
$sp_advanced = (array) get_option( 'seopress_advanced_option_name', array() );
$sp_toggle   = (array) get_option( 'seopress_toggle', array() );
$sp_indexing = (array) get_option( 'seopress_instant_indexing_option_name', array() );

$post_types = array_values( array_diff( get_post_types( array( 'public' => true ) ), array( 'attachment' ) ) );
$taxonomies = array_values( array_diff( get_taxonomies( array( 'public' => true ) ), array( 'post_format' ) ) );

/*
 * 1. Titles and descriptions: the templates.
 */
$separators = array(
	'pipe'       => '|',
	'dash'       => '-',
	'mdash'      => '—',
	'ndash'      => '–',
	'bullet'     => '•',
	'middle-dot' => '·',
	'colon'      => ':',
	'tilde'      => '~',
	'greater'    => '>',
	'arrow'      => '→',
);
if ( ! empty( $onpage['separator'] ) ) {
	$sp_titles['seopress_titles_sep'] = sanitize_text_field( $onpage['separator'] );
} elseif ( isset( $separators[ $onpage['preset-separator'] ?? '' ] ) ) {
	$sp_titles['seopress_titles_sep'] = $separators[ $onpage['preset-separator'] ];
}

// A flag SEOPress stores as "1" or not at all.
$flag = function ( array &$target, string $key, bool $on ): void {
	if ( $on ) {
		$target[ $key ] = '1';
	} else {
		unset( $target[ $key ] );
	}
};

// The old theme printed "CitCom." as the front page title whatever the SEO plugin said (header.php).
$sp_titles['seopress_titles_home_site_title'] = 'CitCom.';
$sp_titles['seopress_titles_home_site_desc']  = $translate( $onpage['metadesc-home'] ?? '' );

foreach ( $post_types as $type ) {
	foreach ( array( 'title', 'description' ) as $part ) {
		$key = ( 'title' === $part ? 'title-' : 'metadesc-' ) . $type;
		if ( isset( $onpage[ $key ] ) && '' !== $onpage[ $key ] ) {
			$sp_titles['seopress_titles_single_titles'][ $type ][ $part ] = $translate( $onpage[ $key ] );
		}
		$key = ( 'title' === $part ? 'title-pt-archive-' : 'metadesc-pt-archive-' ) . $type;
		if ( isset( $onpage[ $key ] ) && '' !== $onpage[ $key ] ) {
			$sp_titles['seopress_titles_archive_titles'][ $type ][ $part ] = $translate( $onpage[ $key ] );
		}
	}
	// article:published_time, which SmartCrawl printed on every single page.
	$sp_titles['seopress_titles_single_titles'][ $type ]['date'] = '1';
	foreach ( array( 'noindex', 'nofollow' ) as $robot ) {
		$sp_titles['seopress_titles_single_titles'][ $type ] = $sp_titles['seopress_titles_single_titles'][ $type ] ?? array();
		$flag( $sp_titles['seopress_titles_single_titles'][ $type ], $robot, $truth( $onpage[ 'meta_robots-' . $robot . '-' . $type ] ?? false ) );
		$sp_titles['seopress_titles_archive_titles'][ $type ] = $sp_titles['seopress_titles_archive_titles'][ $type ] ?? array();
		$flag( $sp_titles['seopress_titles_archive_titles'][ $type ], $robot, $truth( $onpage[ 'meta_robots-' . $robot . '-pt-archive-' . $type ] ?? false ) );
	}
}
foreach ( $taxonomies as $taxonomy ) {
	foreach ( array( 'title', 'description' ) as $part ) {
		$key = ( 'title' === $part ? 'title-' : 'metadesc-' ) . $taxonomy;
		if ( isset( $onpage[ $key ] ) && '' !== $onpage[ $key ] ) {
			$sp_titles['seopress_titles_tax_titles'][ $taxonomy ][ $part ] = $translate( $onpage[ $key ] );
		}
	}
	// SEOPress noindexes tag archives out of the box; SmartCrawl's state wins.
	foreach ( array( 'noindex', 'nofollow' ) as $robot ) {
		$sp_titles['seopress_titles_tax_titles'][ $taxonomy ] = $sp_titles['seopress_titles_tax_titles'][ $taxonomy ] ?? array();
		$flag( $sp_titles['seopress_titles_tax_titles'][ $taxonomy ], $robot, $truth( $onpage[ 'meta_robots-' . $robot . '-' . $taxonomy ] ?? false ) );
	}
}
$simple = array(
	'title-author'    => 'seopress_titles_archives_author_title',
	'metadesc-author' => 'seopress_titles_archives_author_desc',
	'title-date'      => 'seopress_titles_archives_date_title',
	'metadesc-date'   => 'seopress_titles_archives_date_desc',
	'title-search'    => 'seopress_titles_archives_search_title',
	'metadesc-search' => 'seopress_titles_archives_search_desc',
	'title-404'       => 'seopress_titles_archives_404_title',
	'metadesc-404'    => 'seopress_titles_archives_404_desc',
);
foreach ( $simple as $from => $to ) {
	if ( isset( $onpage[ $from ] ) && '' !== $onpage[ $from ] ) {
		$sp_titles[ $to ] = $translate( $onpage[ $from ] );
	}
}
// Author archives: SmartCrawl switched them off unless told otherwise, and so does this.
$flag( $sp_titles, 'seopress_titles_archives_author_disable', ! $truth( $onpage['enable-author-archive'] ?? false ) );
// Date archives and search results stay noindex, which is what both plugins and WordPress itself do.
$sp_titles['seopress_titles_archives_date_noindex']         = '1';
$sp_titles['seopress_titles_archives_search_title_noindex'] = '1';

/*
 * 2. Social: Open Graph, Twitter cards, the organisation.
 */
$flag( $sp_social, 'seopress_social_facebook_og', $truth( $social['og-enable'] ?? false ) );
$flag( $sp_social, 'seopress_social_twitter_card', $truth( $social['twitter-card-enable'] ?? ( $social['twitter-enable'] ?? false ) ) );
$sp_social['seopress_social_twitter_card_og']       = '1';     // Twitter falls back to the Open Graph values, as SmartCrawl did.
$sp_social['seopress_social_twitter_card_img_size'] = 'large'; // summary_large_image, SmartCrawl's card.

// Default share images: SmartCrawl kept one per post type, the same one each time; SEOPress keeps one sitewide.
$og_default = '';
$tw_default = '';
$og_id      = 0;
foreach ( $onpage as $key => $value ) {
	if ( 0 === strpos( $key, 'og-images-' ) && is_array( $value ) && ! empty( $value[0] ) && '' === $og_default ) {
		$og_default = $image_url( $value[0] );
		$og_id      = (int) $value[0];
	}
	if ( 0 === strpos( $key, 'twitter-images-' ) && is_array( $value ) && ! empty( $value[0] ) && '' === $tw_default ) {
		$tw_default = $image_url( $value[0] );
	}
}
if ( '' !== $og_default ) {
	$sp_social['seopress_social_facebook_img']               = esc_url_raw( $og_default );
	$sp_social['seopress_social_facebook_img_attachment_id'] = $og_id;
}
if ( '' !== $tw_default ) {
	$sp_social['seopress_social_twitter_card_img'] = esc_url_raw( $tw_default );
}

$accounts = array(
	'facebook_url'  => 'seopress_social_accounts_facebook',
	'instagram_url' => 'seopress_social_accounts_instagram',
	'linkedin_url'  => 'seopress_social_accounts_linkedin',
	'youtube_url'   => 'seopress_social_accounts_youtube',
	'pinterest_url' => 'seopress_social_accounts_pinterest',
);
foreach ( $accounts as $from => $to ) {
	if ( ! empty( $social[ $from ] ) ) {
		$sp_social[ $to ] = esc_url_raw( $social[ $from ] );
	}
}
if ( ! empty( $social['twitter_username'] ) ) {
	$sp_social['seopress_social_accounts_twitter'] = sanitize_text_field( $social['twitter_username'] );
}
if ( ! empty( $social['fb-app-id'] ) ) {
	$sp_social['seopress_social_facebook_app_id'] = sanitize_text_field( $social['fb-app-id'] );
}
$sp_social['seopress_social_knowledge_type'] = sanitize_text_field( $social['schema_type'] ?? 'Organization' );
if ( ! empty( $social['organization_name'] ) ) {
	$sp_social['seopress_social_knowledge_name'] = sanitize_text_field( $social['organization_name'] );
}
if ( ! empty( $social['organization_logo'] ) ) {
	$sp_social['seopress_social_knowledge_img'] = esc_url_raw( $image_url( $social['organization_logo'] ) );
}
if ( ! empty( $schema['organization_description'] ) ) {
	$sp_social['seopress_social_knowledge_desc'] = sanitize_text_field( $schema['organization_description'] );
}
if ( ! empty( $schema['organization_phone_number'] ) ) {
	$sp_social['seopress_social_knowledge_phone'] = sanitize_text_field( $schema['organization_phone_number'] );
}
if ( ! empty( $schema['organization_contact_type'] ) ) {
	$sp_social['seopress_social_knowledge_contact_type'] = sanitize_text_field( $schema['organization_contact_type'] );
}
// SEOPress prints an empty fb:pages, fb:app_id or twitter:site tag for a key that is missing; an empty value prints nothing.
foreach ( array( 'seopress_social_facebook_link_ownership_id', 'seopress_social_facebook_app_id', 'seopress_social_accounts_twitter' ) as $key ) {
	$sp_social[ $key ] = $sp_social[ $key ] ?? '';
}
// The address, from the custom PostalAddress type built in SmartCrawl's schema builder.
$custom_types = array();
foreach ( (array) get_option( 'wds-schema-types', array() ) as $type_id ) {
	$custom = get_option( 'wds-schema-type-' . $type_id );
	if ( ! is_array( $custom ) || empty( $custom['properties'] ) ) {
		continue;
	}
	$values = array();
	foreach ( $custom['properties'] as $name => $property ) {
		$values[ $name ] = $property['value'] ?? '';
	}
	$custom_types[] = $values;
	if ( 'PostalAddress' === ( $values['@type'] ?? '' ) ) {
		$address = array(
			'streetAddress'   => 'seopress_social_knowledge_street',
			'addressLocality' => 'seopress_social_knowledge_locality',
			'addressRegion'   => 'seopress_social_knowledge_region',
			'postalCode'      => 'seopress_social_knowledge_postal_code',
			'addressCountry'  => 'seopress_social_knowledge_country',
		);
		foreach ( $address as $from => $to ) {
			if ( ! empty( $values[ $from ] ) ) {
				$sp_social[ $to ] = sanitize_text_field( $values[ $from ] );
			}
		}
	}
}

/*
 * 3. Sitemap: the same post types and taxonomies in, the same ones out.
 */
$flag( $sp_sitemap, 'seopress_xml_sitemap_general_enable', $truth( $sitemap['override-native'] ?? false ) || $truth( $sitemap['active'] ?? false ) || $truth( $settings['sitemap'] ?? false ) );
$flag( $sp_sitemap, 'seopress_xml_sitemap_img_enable', $truth( $sitemap['sitemap-images'] ?? false ) );
$sp_sitemap['seopress_xml_sitemap_post_types_list'] = array();
foreach ( $post_types as $type ) {
	if ( ! $truth( $sitemap[ 'post_types-' . $type . '-not_in_sitemap' ] ?? false ) ) {
		$sp_sitemap['seopress_xml_sitemap_post_types_list'][ $type ] = array( 'include' => '1' );
	}
}
$sp_sitemap['seopress_xml_sitemap_taxonomies_list'] = array();
foreach ( $taxonomies as $taxonomy ) {
	if ( ! $truth( $sitemap[ 'taxonomies-' . $taxonomy . '-not_in_sitemap' ] ?? false ) ) {
		$sp_sitemap['seopress_xml_sitemap_taxonomies_list'][ $taxonomy ] = array( 'include' => '1' );
	}
}

/*
 * 4. Advanced, and the switches.
 */
$flag( $sp_advanced, 'seopress_advanced_advanced_wp_generator', $truth( $settings['general-suppress-generator'] ?? false ) );
foreach ( array(
	'verification-google-meta' => 'seopress_advanced_advanced_google',
	'verification-bing-meta'   => 'seopress_advanced_advanced_bing',
) as $from => $to ) {
	foreach ( array( $settings, $sitemap ) as $source ) {
		if ( ! empty( $source[ $from ] ) ) {
			$sp_advanced[ $to ] = sanitize_text_field( $source[ $from ] );
		}
	}
}
if ( ! empty( $social['pinterest-verify'] ) ) {
	$sp_advanced['seopress_advanced_advanced_pinterest'] = sanitize_text_field( $social['pinterest-verify'] );
}
/*
 * Two settings SEOPress switches on when it is first activated must be off on
 * this site: "redirect attachment pages" and "noindex attachment pages". The
 * theme's blog addresses (/blog/{category}/{slug}, inc/setup.php) reach
 * WordPress as an attachment query, so the first sends every blog post to the
 * home page and the second marks every blog post noindex. Real attachment
 * pages are not served at all (wp_attachment_pages_enabled is 0).
 */
unset( $sp_advanced['seopress_advanced_advanced_attachments'], $sp_advanced['seopress_advanced_advanced_attachments_file'], $sp_titles['seopress_titles_attachments_noindex'] );
// IndexNow: off until someone turns it on for the live site. A copy of the site must not submit its URLs.
$sp_toggle['toggle-instant-indexing'] = '0';
unset( $sp_indexing['seopress_instant_indexing_automate_submission'] );

if ( ! $dry ) {
	update_option( 'seopress_titles_option_name', $sp_titles );
	update_option( 'seopress_social_option_name', $sp_social );
	update_option( 'seopress_xml_sitemap_option_name', $sp_sitemap );
	update_option( 'seopress_advanced_option_name', $sp_advanced );
	update_option( 'seopress_toggle', $sp_toggle );
	update_option( 'seopress_instant_indexing_option_name', $sp_indexing );
}
$log[] = 'settings: titles for ' . count( $post_types ) . ' post types (' . implode( ', ', $post_types ) . ') and ' . count( $taxonomies ) . ' taxonomies (' . implode( ', ', $taxonomies ) . ')';
$log[] = 'sitemap: ' . implode( ', ', array_keys( $sp_sitemap['seopress_xml_sitemap_post_types_list'] ) ) . ' + ' . implode( ', ', array_keys( $sp_sitemap['seopress_xml_sitemap_taxonomies_list'] ) );
$log[] = 'share images: ' . ( $og_default ? wp_basename( $og_default ) : 'none' ) . ' / ' . ( $tw_default ? wp_basename( $tw_default ) : 'none' );

/*
 * 5. Every post's own values.
 */
$rows    = $wpdb->get_results(
	"SELECT pm.post_id, pm.meta_key, pm.meta_value FROM {$wpdb->postmeta} pm JOIN {$wpdb->posts} p ON p.ID = pm.post_id
	WHERE p.post_type <> 'revision' AND pm.meta_value <> '' AND pm.meta_key IN ('_wds_title','_wds_metadesc','_wds_canonical','_wds_opengraph','_wds_twitter','_wds_meta-robots-noindex','_wds_meta-robots-nofollow','_wds_meta-robots-adv','_wds_redirect','_wds_focus-keywords','wds_primary_category')
	ORDER BY pm.post_id, pm.meta_id"
);
$counts  = array();
$notes   = array();
$written = array();
$put     = function ( int $post_id, string $key, $value ) use ( &$counts, &$written, $dry ): void {
	if ( '' === $value || null === $value ) {
		return;
	}
	if ( isset( $written[ $post_id . ' ' . $key ] ) ) {
		return; // SmartCrawl sometimes holds a key twice; the first row is the one WordPress reads.
	}
	$written[ $post_id . ' ' . $key ] = true;
	$counts[ $key ]                   = ( $counts[ $key ] ?? 0 ) + 1;
	if ( ! $dry ) {
		update_post_meta( $post_id, $key, wp_slash( $value ) );
	}
};
foreach ( $rows as $row ) {
	$id    = (int) $row->post_id;
	$value = maybe_unserialize( $row->meta_value );
	switch ( $row->meta_key ) {
		case '_wds_title':
			$put( $id, '_seopress_titles_title', $translate( $value ) );
			break;
		case '_wds_metadesc':
			$put( $id, '_seopress_titles_desc', $translate( $value ) );
			break;
		case '_wds_canonical':
			// A canonical that only repeats the post's own address is left out: SEOPress prints that one
			// anyway, and drops a post from the sitemap when its stored canonical differs in any way.
			if ( $same_path( (string) $value, (string) get_permalink( $id ) ) ) {
				$notes[] = "post $id: canonical is its own address, not copied";
				break;
			}
			$put( $id, '_seopress_robots_canonical', esc_url_raw( $value ) );
			break;
		case '_wds_meta-robots-noindex':
			$put( $id, '_seopress_robots_index', $truth( $value ) ? 'yes' : '' );
			break;
		case '_wds_meta-robots-nofollow':
			$put( $id, '_seopress_robots_follow', $truth( $value ) ? 'yes' : '' );
			break;
		case '_wds_meta-robots-adv':
			foreach ( array_filter( array_map( 'trim', explode( ',', (string) $value ) ) ) as $token ) {
				if ( 'nosnippet' === $token ) {
					$put( $id, '_seopress_robots_snippet', 'yes' );
				} elseif ( 'noimageindex' === $token ) {
					$put( $id, '_seopress_robots_imageindex', 'yes' );
				} else {
					$notes[] = "post $id: robots \"$token\" has no SEOPress setting, not copied";
				}
			}
			break;
		case '_wds_opengraph':
		case '_wds_twitter':
			if ( ! is_array( $value ) ) {
				break;
			}
			$prefix = '_wds_opengraph' === $row->meta_key ? '_seopress_social_fb_' : '_seopress_social_twitter_';
			$put( $id, $prefix . 'title', $translate( $value['title'] ?? '' ) );
			$put( $id, $prefix . 'desc', $translate( $value['description'] ?? '' ) );
			$image = $image_url( $value['images'][0] ?? '' );
			if ( '' !== $image ) {
				$put( $id, $prefix . 'img', esc_url_raw( $image ) );
				if ( is_numeric( $value['images'][0] ) ) {
					$put( $id, $prefix . 'img_attachment_id', (int) $value['images'][0] );
				}
			}
			if ( ! empty( $value['disabled'] ) ) {
				$notes[] = "post $id (" . get_post_status( $id ) . '): ' . ( '_wds_opengraph' === $row->meta_key ? 'Open Graph' : 'Twitter card' ) . ' was switched off for this post; SEOPress has no per-post switch, so its tags will be printed';
			}
			break;
		case '_wds_redirect':
			$put( $id, '_seopress_redirections_value', esc_url_raw( $value ) );
			$put( $id, '_seopress_redirections_type', '301' );
			$put( $id, '_seopress_redirections_enabled', 'yes' );
			break;
		case '_wds_focus-keywords':
			$put( $id, '_seopress_analysis_target_kw', sanitize_text_field( $value ) );
			break;
		case 'wds_primary_category':
			// Only a category the post is still in; SmartCrawl kept the default one on posts that were moved since.
			if ( (int) $value > 0 && has_term( (int) $value, 'category', $id ) ) {
				$put( $id, '_seopress_robots_primary_cat', (string) (int) $value );
			}
			break;
	}
}
ksort( $counts );
foreach ( $counts as $key => $n ) {
	$log[] = sprintf( 'post meta %-40s %d', $key, $n );
}

/*
 * 6. Terms. SmartCrawl keeps them in one option, by taxonomy and term id.
 */
$term_counts = array();
foreach ( (array) get_option( 'wds_taxonomy_meta', array() ) as $taxonomy => $terms ) {
	$template_title = $onpage[ 'title-' . $taxonomy ] ?? '';
	$template_desc  = $onpage[ 'metadesc-' . $taxonomy ] ?? '';
	foreach ( (array) $terms as $term_id => $data ) {
		$term_id = (int) $term_id;
		if ( ! term_exists( $term_id, $taxonomy ) ) {
			$notes[] = "term $term_id ($taxonomy) no longer exists, skipped";
			continue;
		}
		$values = array(
			// A value that only repeats the taxonomy's template is the template, not an override.
			'_seopress_titles_title'         => ( $data['wds_title'] ?? '' ) === $template_title ? '' : $translate( $data['wds_title'] ?? '' ),
			'_seopress_titles_desc'          => ( $data['wds_desc'] ?? '' ) === $template_desc ? '' : $translate( $data['wds_desc'] ?? '' ),
			'_seopress_robots_index'         => $truth( $data['wds_noindex'] ?? false ) ? 'yes' : '',
			'_seopress_robots_follow'        => $truth( $data['wds_nofollow'] ?? false ) ? 'yes' : '',
			'_seopress_robots_canonical'     => esc_url_raw( $data['wds_canonical'] ?? '' ),
			'_seopress_social_fb_title'      => $translate( $data['opengraph']['title'] ?? '' ),
			'_seopress_social_fb_desc'       => $translate( $data['opengraph']['description'] ?? '' ),
			'_seopress_social_fb_img'        => esc_url_raw( $image_url( $data['opengraph']['images'][0] ?? '' ) ),
			'_seopress_social_twitter_title' => $translate( $data['twitter']['title'] ?? '' ),
			'_seopress_social_twitter_desc'  => $translate( $data['twitter']['description'] ?? '' ),
			'_seopress_social_twitter_img'   => esc_url_raw( $image_url( $data['twitter']['images'][0] ?? '' ) ),
		);
		foreach ( $values as $key => $value ) {
			if ( '' === $value ) {
				continue;
			}
			$term_counts[ $key ] = ( $term_counts[ $key ] ?? 0 ) + 1;
			if ( ! $dry ) {
				update_term_meta( $term_id, $key, wp_slash( $value ) );
			}
		}
	}
}
foreach ( $term_counts as $key => $n ) {
	$log[] = sprintf( 'term meta %-40s %d', $key, $n );
}

/*
 * 7. Redirects. SEOPress keeps them as posts of a type only its PRO plugin registers.
 */
$table     = $wpdb->prefix . 'smartcrawl_redirects';
$redirects = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table ? $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i ORDER BY id', $table ) ) : array();
$copied    = 0;
foreach ( $redirects as $redirect ) {
	$source  = '' !== (string) $redirect->path ? $redirect->path : $redirect->source;
	$decoded = json_decode( (string) $redirect->destination, true );
	if ( is_array( $decoded ) && isset( $decoded['id'] ) ) {
		$target = 'term' === ( $decoded['type'] ?? '' ) ? get_term_link( (int) $decoded['id'] ) : get_permalink( (int) $decoded['id'] );
		$target = is_string( $target ) ? $target : '';
	} else {
		$target = is_string( $decoded ) ? $decoded : (string) $redirect->destination;
	}
	$type = in_array( (string) $redirect->type, array( '301', '302', '307', '308', '410', '451' ), true ) ? (string) $redirect->type : '301';
	if ( '' === $source || '' === $target ) {
		$notes[] = 'redirect ' . $redirect->id . ' has no source or target, skipped';
		continue;
	}
	// SEOPress matches on the path without its leading slash.
	$title = ltrim( (string) wp_parse_url( $source, PHP_URL_PATH ), '/' );
	if ( ! post_type_exists( 'seopress_404' ) ) {
		$notes[] = "redirect not copied (needs SEOPress PRO or another home): /$title -> $target ($type)";
		continue;
	}
	$existing = get_posts(
		array(
			'post_type'      => 'seopress_404',
			'post_status'    => 'any',
			'title'          => $title,
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);
	++$copied;
	if ( $dry || $existing ) {
		continue;
	}
	$redirect_id = wp_insert_post(
		array(
			'post_title'  => $title,
			'post_type'   => 'seopress_404',
			'post_status' => 'publish',
		)
	);
	if ( $redirect_id && ! is_wp_error( $redirect_id ) ) {
		update_post_meta( $redirect_id, '_seopress_redirections_value', esc_url_raw( $target ) );
		update_post_meta( $redirect_id, '_seopress_redirections_type', $type );
		update_post_meta( $redirect_id, '_seopress_redirections_enabled', 'yes' );
	}
}
$log[] = 'redirects: ' . count( $redirects ) . ' in SmartCrawl, ' . $copied . ' copied';

/*
 * 8. What has no counterpart, for the person running this to read.
 */
foreach ( $custom_types as $values ) {
	if ( 'PostalAddress' !== ( $values['@type'] ?? '' ) ) {
		$notes[] = 'custom schema type ' . ( $values['@type'] ?? '?' ) . ' is not copied (the address is, into the organisation): ' . wp_json_encode( array_diff_key( $values, array( '@type' => 1 ) ), JSON_UNESCAPED_SLASHES );
	}
}
$extras = (array) get_option( 'wds-sitemap-extras', array() );
if ( $extras ) {
	$notes[] = count( $extras ) . ' extra sitemap URLs were added by hand in SmartCrawl; SEOPress lists none (docs/04-seopress.md)';
}

if ( ! $dry ) {
	update_option( 'citcom_seopress_migrated', gmdate( 'Y-m-d H:i' ), false );
	flush_rewrite_rules( false );
	wp_cache_flush();
}
WP_CLI::log( implode( "\n", $log ) );
if ( $notes ) {
	WP_CLI::log( "\nNotes:\n- " . implode( "\n- ", $notes ) );
}
WP_CLI::success( $dry ? 'Dry run: nothing written.' : 'SmartCrawl values copied into SEOPress.' );
