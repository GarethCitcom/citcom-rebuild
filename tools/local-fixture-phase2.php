<?php
/**
 * Local fixture for the Phase 2 blocks sub-services, trustindex, stats,
 * video, swiper, contact-map, services-showcase and citdot-cards. Run after
 * tools/local-fixture.php, from the WordPress root:
 *
 *   wp eval-file wp-content/themes/citcom-rebuild/tools/local-fixture-phase2.php
 *
 * Content comes from tools/fixtures/phase2-sections.json, extracted from the
 * staging HTML, so each section can be compared with its staging original:
 *
 * - /about-us/          + citdot_cards (staging index 8) and two swipers (10, 12)
 * - /contact-us/        + contact_map (staging /contact-us/ index 2)
 * - /home-classic/      video, services_showcase and swiper from staging /home/
 * - /results/           stats (staging "Delivering 400% ROI" case study) and trustindex
 *                       (the old google_reviews section, now a Trustindex widget)
 * - /services/creative/ sub_services with five child services, as staging
 *
 * Re-running reuses anything that already exists.
 *
 * @package citcom
 */

if ( ! defined( 'WP_CLI' ) ) {
	exit;
}

define( 'CITCOM_FIXTURE_HELPERS_ONLY', true );
require_once __DIR__ . '/local-fixture.php';

$citcom_file = __DIR__ . '/fixtures/phase2-sections.json';
if ( ! file_exists( $citcom_file ) ) {
	WP_CLI::error( 'tools/fixtures/phase2-sections.json is missing.' );
}
$citcom_p2 = json_decode( (string) file_get_contents( $citcom_file ), true ) ?: array();

/**
 * Sideload an image once, titled by its alt text, without letting two different
 * files that share an alt (staging has "Bt" twice) collapse into one attachment.
 */
function citcom_fixture_image( string $url, string $alt ): int {
	$file     = basename( (string) wp_parse_url( $url, PHP_URL_PATH ) );
	$title    = $alt ?: $file;
	$existing = citcom_fixture_attachment( $title );
	if ( $existing && basename( (string) get_attached_file( $existing ) ) !== $file ) {
		$title .= ' ' . pathinfo( $file, PATHINFO_FILENAME );
	}
	$id = citcom_fixture_sideload( $url, $title );
	if ( $id && $alt ) {
		update_post_meta( $id, '_wp_attachment_image_alt', $alt );
	}
	return $id;
}

/**
 * Serialised self-closing ACF block.
 */
function citcom_fixture_block( string $name, array $data ): string {
	return '<!-- wp:' . $name . ' ' . serialize_block_attributes(
		array(
			'name' => $name,
			'data' => $data,
			'mode' => 'preview',
		)
	) . ' /-->';
}

/**
 * Focus point value as the citcom_focuspoint field stores it.
 */
function citcom_fixture_focus( int $id, $top, $left ): array {
	return array(
		'id'   => $id,
		'top'  => (string) $top,
		'left' => (string) $left,
	);
}

/**
 * Append blocks to a page once (skipped when the first block name is already in the content).
 */
function citcom_fixture_append( string $slug, string $marker, string $blocks ): void {
	$page = get_page_by_path( $slug );
	if ( ! $page ) {
		WP_CLI::warning( "Page /$slug/ not found; run tools/local-fixture.php first." );
		return;
	}
	if ( str_contains( $page->post_content, $marker ) ) {
		WP_CLI::log( "/$slug/ already has $marker." );
		return;
	}
	wp_update_post(
		array(
			'ID'           => $page->ID,
			'post_content' => wp_slash( $page->post_content . "\n\n" . $blocks ),
		)
	);
	WP_CLI::log( "/$slug/ extended with $marker." );
}

/**
 * Swiper block data from an extracted staging swiper section.
 */
function citcom_fixture_swiper( array $s ): string {
	$ids = array();
	foreach ( $s['images'] as $img ) {
		$id = citcom_fixture_image( $img['url'], $img['alt'] );
		if ( $id ) {
			$ids[] = $id;
		}
	}
	$attr = static function ( string $name ) use ( $s ): string {
		return preg_match( '/data-' . $name . '="([^"]*)"/', $s['attrs'], $m ) ? $m[1] : '';
	};
	// data-speed = speed / 2 * 1000; data-delay = delay * 1000.
	$speed = (int) round( (float) $attr( 'speed' ) / 1000 * 2 );
	$delay = (int) round( (float) $attr( 'delay' ) / 1000 );

	$data = array_merge(
		array(
			'swiper_type'                          => 'images',
			'_swiper_type'                         => 'field_66fb0251508b0',
			'image_slides'                         => $ids,
			'_image_slides'                        => 'field_66fb02e5508b1',
			'slides_per_view'                      => '',
			'_slides_per_view'                     => 'field_66fb21f2422a1',
			'slides_per_view_number_of_slides_xl'  => '2',
			'_slides_per_view_number_of_slides_xl' => 'field_66fb222e422a3',
			'slides_per_view_number_of_slides_lg'  => '3',
			'_slides_per_view_number_of_slides_lg' => 'field_66fb23f941682',
			'slides_per_view_number_of_slides_md'  => '6',
			'_slides_per_view_number_of_slides_md' => 'field_66fb242c41683',
			'slides_per_view_number_of_slides_sm'  => '12',
			'_slides_per_view_number_of_slides_sm' => 'field_66fb243e41684',
			'center_align_slides'                  => 'true' === $attr( 'center' ) ? '1' : '0',
			'_center_align_slides'                 => 'field_66fb24d741687',
			'loop_slides'                          => 'true' === $attr( 'loop' ) ? '1' : '0',
			'_loop_slides'                         => 'field_66fb255941689',
			'auto_play'                            => 'true' === $attr( 'autoplay' ) ? '1' : '0',
			'_auto_play'                           => 'field_66fb25aa4168a',
			'delay'                                => (string) $delay,
			'_delay'                               => 'field_66fb26e04168b',
			'speed'                                => (string) $speed,
			'_speed'                               => 'field_66fb33ce799c5',
			'navigation_arrows'                    => '0',
			'_navigation_arrows'                   => 'field_66fb27a74168c',
			'pagination_dots'                      => '0',
			'_pagination_dots'                     => 'field_66fb27c74168d',
			'scrollbar'                            => '0',
			'_scrollbar'                           => 'field_66fb27db4168e',
			'reverse_direction'                    => 'true' === $attr( 'reversedirection' ) ? '1' : '0',
			'_reverse_direction'                   => 'field_670c342615102',
		),
		citcom_fixture_section_settings( $s['classes'] )
	);
	return citcom_fixture_block( 'citcom/swiper', $data );
}

/*
 * 0. Site Settings: the six service shapes (SVGs from the staging home page) that
 * services-showcase inlines.
 */
$shape_ids = array();
foreach ( array( 'creative', 'development', 'marketing', 'events', 'print', 'video' ) as $shape ) {
	$file = __DIR__ . '/fixtures/shapes/' . $shape . '.svg';
	if ( file_exists( $file ) ) {
		$shape_ids[ $shape ] = citcom_fixture_upload( $file, 'Service shape ' . $shape );
	}
}
if ( $shape_ids ) {
	update_field( 'field_66fbeae51583d', $shape_ids, 'option' );
	WP_CLI::log( 'Service shapes: ' . implode( ', ', $shape_ids ) );
}

/*
 * 1. About Us: CitDot cards (staff) and the two logo swipers.
 */
if ( ! empty( $citcom_p2['citdot_cards'] ) ) {
	$rows = array();
	foreach ( $citcom_p2['citdot_cards']['cards'] as $card ) {
		$img_id = ! empty( $card['image']['url'] ) ? citcom_fixture_image( $card['image']['url'], $card['image']['alt'] ) : 0;
		$rows[] = array(
			'citdot_style'           => array( 'field_670c06b455a9f', 'staff' ),
			'staff'                  => array( 'field_670c071c55aa1', '' ),
			'staff_name'             => array( 'field_670c076f55aa2', $card['name'] ),
			'staff_job_title'        => array( 'field_670c079455aa3', $card['job_title'] ),
			'staff_linkedin_profile' => array( 'field_670c07aa55aa4', $card['linkedin'] ),
			'staff_description'      => array( 'field_670c07d955aa7', $card['description'] ),
			'staff_profile_picture'  => array( 'field_670c07f955aa9', citcom_fixture_focus( $img_id, $card['image']['top'] ?? 50, $card['image']['left'] ?? 50 ) ),
		);
	}
	$about_blocks = citcom_fixture_block(
		'citcom/citdot-cards',
		array_merge(
			citcom_fixture_repeater( 'citdot_card', 'field_670c067355a9d', $rows ),
			citcom_fixture_section_settings( $citcom_p2['citdot_cards']['classes'] )
		)
	);
	foreach ( array( 'swiper_about-1', 'swiper_about-2' ) as $key ) {
		if ( ! empty( $citcom_p2[ $key ] ) ) {
			$about_blocks .= "\n\n" . citcom_fixture_swiper( $citcom_p2[ $key ] );
		}
	}
	citcom_fixture_append( 'about-us', 'wp:citcom/citdot-cards', $about_blocks );
}

/*
 * 2. Contact Us: contact map. The form id is the staging Forminator form; without
 * Forminator locally the shortcode prints as text.
 */
if ( ! empty( $citcom_p2['contact_map'] ) ) {
	$c    = $citcom_p2['contact_map'];
	$data = array_merge(
		array(
			'snazzy_map'         => '',
			'_snazzy_map'        => 'field_670d887335b95',
			'mobile_snazzy_map'  => '',
			'_mobile_snazzy_map' => 'field_670da33f230de',
			'show_socials'       => ! empty( $c['show_socials'] ) ? '1' : '0',
			'_show_socials'      => 'field_670d890535b98',
			'form'               => (string) ( $c['form'] ?? '' ),
			'_form'              => 'field_670d892735b99',
			'contact_title'      => $c['title'],
			'_contact_title'     => 'field_670d88e735b96',
			'contact_message'    => $c['message'],
			'_contact_message'   => 'field_670d88f535b97',
		),
		citcom_fixture_section_settings( $c['classes'] )
	);
	citcom_fixture_append( 'contact-us', 'wp:citcom/contact-map', citcom_fixture_block( 'citcom/contact-map', $data ) );
}

/*
 * 3. Home Classic: video, services showcase and the client logo swiper from staging /home/.
 */
$home_blocks = array();
if ( ! empty( $citcom_p2['video'] ) ) {
	$v         = $citcom_p2['video'];
	$video_id  = citcom_fixture_sideload( $v['src'], 'Citcom show reel Dec 2025 v3' );
	$poster_id = ! empty( $v['poster'] ) ? citcom_fixture_sideload( $v['poster'], 'CitCom showreel poster' ) : 0;
	$home_blocks[] = citcom_fixture_block(
		'citcom/video',
		array_merge(
			array(
				'source'                      => 'wp',
				'_source'                     => 'field_66fa65fe8f982',
				'video'                       => (string) $video_id,
				'_video'                      => 'field_66fa66258f983',
				'player_options'              => '',
				'_player_options'             => 'field_66fa6d66a64b9',
				'player_options_options'      => $v['options'],
				'_player_options_options'     => 'field_66fa6d7ba64ba',
				'player_options_video_poster' => (string) $poster_id,
				'_player_options_video_poster' => 'field_66fa7565c4741',
				'citdot_container'            => ! empty( $v['citdot'] ) ? '1' : '0',
				'_citdot_container'           => 'field_66faa4c98bc5d',
			),
			citcom_fixture_section_settings( $v['classes'] )
		)
	);
}
if ( ! empty( $citcom_p2['services_showcase'] ) ) {
	$keys = array(
		'creative'    => array( 'field_66fbdd5ee8323', 'field_66fbdd31e8321', 'field_66fbdd74e8324', 'field_66fbde87e8325' ),
		'development' => array( 'field_66fbe1b4f9f72', 'field_66fbe1b5f9f75', 'field_66fbe1b5f9f76', 'field_66fbe1b5f9f77' ),
		'marketing'   => array( 'field_66fbe1eaf9f79', 'field_66fbe1eaf9f7c', 'field_66fbe1eaf9f7d', 'field_66fbe1eaf9f7e' ),
		'events'      => array( 'field_66fbe20cf9f80', 'field_66fbe20cf9f83', 'field_66fbe20cf9f84', 'field_66fbe20cf9f85' ),
		'print'       => array( 'field_66fbe21ff9f87', 'field_66fbe21ff9f8a', 'field_66fbe21ff9f8b', 'field_66fbe21ff9f8c' ),
		'video'       => array( 'field_66fbe27af9f8e', 'field_66fbe27af9f91', 'field_66fbe27af9f92', 'field_66fbe27af9f93' ),
	);
	$data = array();
	foreach ( $keys as $service => list( $group_key, $image_key, $text_key, $link_key ) ) {
		$s      = $citcom_p2['services_showcase']['services'][ $service ] ?? array();
		$img_id = ! empty( $s['image']['url'] ) ? citcom_fixture_image( $s['image']['url'], $s['image']['alt'] ) : 0;
		$link   = $s['link'] ?? array();
		// Links point at the local service pages.
		$link['url'] = home_url( wp_parse_url( $link['url'] ?? '/services/' . $service . '/', PHP_URL_PATH ) );

		$data[ $service ]                       = '';
		$data[ '_' . $service ]                 = $group_key;
		$data[ $service . '_showcase_image' ]   = citcom_fixture_focus( $img_id, $s['image']['top'] ?? 50, $s['image']['left'] ?? 50 );
		$data[ '_' . $service . '_showcase_image' ] = $image_key;
		$data[ $service . '_card_text' ]        = $s['text'] ?? '';
		$data[ '_' . $service . '_card_text' ]  = $text_key;
		$data[ $service . '_card_link' ]        = $link;
		$data[ '_' . $service . '_card_link' ]  = $link_key;
	}
	$home_blocks[] = citcom_fixture_block( 'citcom/services-showcase', array_merge( $data, citcom_fixture_section_settings( $citcom_p2['services_showcase']['classes'] ) ) );
}
if ( ! empty( $citcom_p2['swiper_home-1'] ) ) {
	$home_blocks[] = citcom_fixture_swiper( $citcom_p2['swiper_home-1'] );
}
if ( $home_blocks ) {
	$home_id = citcom_fixture_page(
		'Home Classic',
		'home-classic',
		citcom_fixture_page_header(
			array(
				'title'    => 'Home Classic',
				'type'     => 'pattern',
				'bg-color' => '#eff1f3',
				'pattern'  => 'persian',
			)
		) . "\n\n" . implode( "\n\n", $home_blocks )
	);
	WP_CLI::log( "Home Classic page: $home_id" );
}

/*
 * 4. Results: stats and the Trustindex widget that replaces the google_reviews section.
 */
$results_blocks = array();
if ( ! empty( $citcom_p2['stats'] ) ) {
	$st   = $citcom_p2['stats'];
	$hex  = array(
		'primary'   => '#1C0221',
		'secondary' => '#9AD14D',
		'default'   => '#D8DBE2',
		'slate'     => '#2A4747',
		'persian'   => '#17A398',
		'carrot'    => '#F9A03F',
		'white'     => '#FFFFFF',
	);
	$rows = array();
	foreach ( $st['stats'] as $row ) {
		$rows[] = array(
			'append'          => array( 'field_670ad3979f2e3', $row['append'] ),
			'stat'            => array( 'field_670ad3cb9f2e4', $row['stat'] ),
			'prepend'         => array( 'field_670ad3d89f2e5', $row['prepend'] ),
			'supporting_text' => array( 'field_670ad41d9f2e8', $row['supporting_text'] ),
			'stat_color'      => array( 'field_670ad44b9f2e9', $hex[ $row['stat_color'] ] ?? '#9AD14D' ),
		);
	}
	$results_blocks[] = citcom_fixture_block(
		'citcom/stats',
		array_merge(
			array(
				'title'         => $st['title'],
				'_title'        => 'field_670ad4cc9f2eb',
				'opening_text'  => $st['opening_text'],
				'_opening_text' => 'field_670ad4d89f2ec',
			),
			citcom_fixture_repeater( 'number_stats', 'field_670ad36d9f2e2', $rows ),
			citcom_fixture_section_settings( $st['classes'] )
		)
	);
}
if ( ! empty( $citcom_p2['google_reviews'] ) ) {
	$results_blocks[] = citcom_fixture_block(
		'citcom/trustindex',
		array_merge(
			array(
				'trustindex_code'  => '[trustindex no-registration=google]',
				'_trustindex_code' => 'field_citcom_trustindex_code',
			),
			citcom_fixture_section_settings( $citcom_p2['google_reviews']['classes'] )
		)
	);
}
if ( $results_blocks ) {
	$results_id = citcom_fixture_page(
		'Results',
		'results',
		citcom_fixture_page_header(
			array(
				'title'    => 'Results',
				'type'     => 'pattern',
				'bg-color' => '#eff1f3',
				'pattern'  => 'persian',
			)
		) . "\n\n" . implode( "\n\n", $results_blocks )
	);
	WP_CLI::log( "Results page: $results_id" );
}

/*
 * 5. Services: "Creative" with its five child services and the sub-services block.
 */
if ( ! empty( $citcom_p2['sub_services'] ) ) {
	$ss       = $citcom_p2['sub_services'];
	$existing = get_page_by_path( 'creative', OBJECT, 'service' );
	$creative = $existing ? (int) $existing->ID : (int) wp_insert_post(
		array(
			'post_type'   => 'service',
			'post_title'  => 'Creative',
			'post_name'   => 'creative',
			'post_status' => 'publish',
		)
	);

	$child_ids = array();
	$by_slug   = array();
	foreach ( $ss['rows'] as $row ) {
		$by_slug[ $row['slug'] ] = $row;
	}
	foreach ( $ss['nav'] as $slug ) {
		$row = $by_slug[ $slug ] ?? array(
			'slug'     => $slug,
			'title'    => ucwords( str_replace( '-', ' ', $slug ) ),
			'excerpt'  => '',
			'cs_tags'  => array(),
			'gallery'  => array(),
		);
		$found = get_page_by_path( 'creative/' . $slug, OBJECT, 'service' );
		$id    = $found ? (int) $found->ID : (int) wp_insert_post(
			array(
				'post_type'   => 'service',
				'post_title'  => $row['title'],
				'post_name'   => $slug,
				'post_status' => 'publish',
				'post_parent' => $creative,
			)
		);
		if ( ! $id ) {
			continue;
		}
		$gallery = array();
		foreach ( $row['gallery'] as $img ) {
			$img_id = citcom_fixture_image( $img['url'], $img['alt'] );
			if ( $img_id ) {
				$gallery[] = $img_id;
			}
		}
		$terms = array();
		foreach ( $row['cs_tags'] as $tag ) {
			$term = term_exists( $tag, 'cs-tag' );
			if ( ! $term ) {
				$term = wp_insert_term( ucwords( str_replace( '-', ' ', $tag ) ), 'cs-tag', array( 'slug' => $tag ) );
			}
			if ( ! is_wp_error( $term ) ) {
				$terms[] = (int) $term['term_id'];
			}
		}
		update_field( 'field_670d0f707d675', $gallery, $id );
		update_field( 'field_670d0fb97d676', $row['excerpt'], $id );
		update_field( 'field_670d0ffb7d677', $terms, $id );
		$child_ids[] = $id;
	}

	$content = citcom_fixture_page_header(
		array(
			'title'    => 'Creative',
			'type'     => 'pattern',
			'bg-color' => '#eff1f3',
			'pattern'  => 'persian',
		)
	) . "\n\n" . citcom_fixture_block(
		'citcom/sub-services',
		array_merge(
			array(
				'select_sub_services'  => $child_ids,
				'_select_sub_services' => 'field_670d082986a53',
			),
			citcom_fixture_section_settings( $ss['classes'] )
		)
	);
	wp_update_post(
		array(
			'ID'           => $creative,
			'post_content' => wp_slash( $content ),
		)
	);
	WP_CLI::log( "Creative service: $creative with " . count( $child_ids ) . ' children.' );
}

flush_rewrite_rules( false );
WP_CLI::success( 'Phase 2 fixture done: /about-us/, /contact-us/, /home-classic/, /results/, /services/creative/.' );
