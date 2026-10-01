<?php
/**
 * Local fixture for the Phase 2 blocks sub-services, trustindex, stats,
 * video, swiper, contact-map, services-showcase, citdot-cards and the eight
 * diner blocks. Run after
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
 * - /                   the diner home page (staging /), set as the front page
 * - /diner-extras/      diner variants staging does not use (sample content)
 * - five blog posts and the sidebar block widgets (blog listing and blog post)
 * - /services/ (archive template, six core services), /blog/ (posts page), /packages/
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
	$v             = $citcom_p2['video'];
	$video_id      = citcom_fixture_sideload( $v['src'], 'Citcom show reel Dec 2025 v3' );
	$poster_id     = ! empty( $v['poster'] ) ? citcom_fixture_sideload( $v['poster'], 'CitCom showreel poster' ) : 0;
	$home_blocks[] = citcom_fixture_block(
		'citcom/video',
		array_merge(
			array(
				'source'                       => 'wp',
				'_source'                      => 'field_66fa65fe8f982',
				'video'                        => (string) $video_id,
				'_video'                       => 'field_66fa66258f983',
				'player_options'               => '',
				'_player_options'              => 'field_66fa6d66a64b9',
				'player_options_options'       => $v['options'],
				'_player_options_options'      => 'field_66fa6d7ba64ba',
				'player_options_video_poster'  => (string) $poster_id,
				'_player_options_video_poster' => 'field_66fa7565c4741',
				'citdot_container'             => ! empty( $v['citdot'] ) ? '1' : '0',
				'_citdot_container'            => 'field_66faa4c98bc5d',
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

		$data[ $service ]                           = '';
		$data[ '_' . $service ]                     = $group_key;
		$data[ $service . '_showcase_image' ]       = citcom_fixture_focus( $img_id, $s['image']['top'] ?? 50, $s['image']['left'] ?? 50 );
		$data[ '_' . $service . '_showcase_image' ] = $image_key;
		$data[ $service . '_card_text' ]            = $s['text'] ?? '';
		$data[ '_' . $service . '_card_text' ]      = $text_key;
		$data[ $service . '_card_link' ]            = $link;
		$data[ '_' . $service . '_card_link' ]      = $link_key;
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
		$row   = $by_slug[ $slug ] ?? array(
			'slug'    => $slug,
			'title'   => ucwords( str_replace( '-', ' ', $slug ) ),
			'excerpt' => '',
			'cs_tags' => array(),
			'gallery' => array(),
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

/*
 * 6. The diner home page (staging /): hero, intro, menu, story, wall, reviews and
 * guest check, set as the static front page. A second page, /diner-extras/, holds
 * what the staging home page does not use: the light story split, the intro stamp
 * and the reviews placeholder (summary badge and cards) shown when no Trustindex
 * code is set. Its review content is sample text.
 */

/**
 * Stamp group data for a diner block.
 *
 * @param string $prefix Field key prefix, e.g. "story" for field_diner_story_stamp_on.
 * @param array  $stamp  enabled / line_1 / line_2 / line_3.
 */
function citcom_fixture_diner_stamp( string $prefix, array $stamp ): array {
	return array(
		'stamp'          => '',
		'_stamp'         => 'field_diner_' . $prefix . '_stamp',
		'stamp_enabled'  => ! empty( $stamp['enabled'] ) ? '1' : '0',
		'_stamp_enabled' => 'field_diner_' . $prefix . '_stamp_on',
		'stamp_line_1'   => (string) ( $stamp['line_1'] ?? '' ),
		'_stamp_line_1'  => 'field_diner_' . $prefix . '_stamp_1',
		'stamp_line_2'   => (string) ( $stamp['line_2'] ?? '' ),
		'_stamp_line_2'  => 'field_diner_' . $prefix . '_stamp_2',
		'stamp_line_3'   => (string) ( $stamp['line_3'] ?? '' ),
		'_stamp_line_3'  => 'field_diner_' . $prefix . '_stamp_3',
	);
}

/**
 * ACF link value pointing at the same path on the local site.
 */
function citcom_fixture_local_link( string $url, string $title, string $target = '' ): array {
	return array(
		'title'  => $title,
		'url'    => '' === $url ? '' : home_url( (string) wp_parse_url( $url, PHP_URL_PATH ) ),
		'target' => $target,
	);
}

if ( ! empty( $citcom_p2['diner'] ) ) {
	$d        = $citcom_p2['diner'];
	$settings = citcom_fixture_section_settings( '' );
	$diner    = array();

	// Hero: the sign is an MP4 on staging.
	$sign_id = ! empty( $d['hero']['sign'] ) ? citcom_fixture_sideload( $d['hero']['sign'], 'CitCom diner sign' ) : 0;
	$diner[] = citcom_fixture_block(
		'citcom/diner-hero',
		array_merge(
			array(
				'heading'       => $d['hero']['heading'],
				'_heading'      => 'field_diner_hero_heading',
				'sign_image'    => $sign_id ? (string) $sign_id : '',
				'_sign_image'   => 'field_diner_hero_sign',
				'show_texture'  => ! empty( $d['hero']['texture'] ) ? '1' : '0',
				'_show_texture' => 'field_diner_hero_texture',
				'button'        => array(
					'title'  => $d['hero']['button'],
					'url'    => '#feeling-peckish',
					'target' => '',
				),
				'_button'       => 'field_diner_hero_button',
			),
			$settings
		)
	);

	$diner[] = citcom_fixture_block(
		'citcom/diner-intro',
		array_merge(
			array(
				'heading'         => $d['intro']['heading'],
				'_heading'        => 'field_diner_intro_heading',
				'heading_accent'  => $d['intro']['accent'],
				'_heading_accent' => 'field_diner_intro_accent',
				'content'         => $d['intro']['content'],
				'_content'        => 'field_diner_intro_content',
			),
			citcom_fixture_diner_stamp( 'intro', $d['intro']['stamp'] ),
			$settings
		)
	);

	$card_rows = array();
	foreach ( $d['menu']['cards'] as $card ) {
		$card_rows[] = array(
			'label'       => array( 'field_diner_menu_label', $card['label'] ),
			'image'       => array( 'field_diner_menu_image', (string) citcom_fixture_image( $card['image'], $card['alt'] ) ),
			'image_scale' => array( 'field_diner_menu_scale', (string) $card['scale'] ),
			'image_tilt'  => array( 'field_diner_menu_tilt', (string) $card['tilt'] ),
			'link'        => array( 'field_diner_menu_card_link', citcom_fixture_local_link( $card['link'], $card['label'], $card['target'] ) ),
		);
	}
	$bleed_start = ! empty( $d['menu']['bleed']['start'] ) ? citcom_fixture_image( $d['menu']['bleed']['start']['url'], $d['menu']['bleed']['start']['alt'] ) : 0;
	$bleed_end   = ! empty( $d['menu']['bleed']['end'] ) ? citcom_fixture_image( $d['menu']['bleed']['end']['url'], $d['menu']['bleed']['end']['alt'] ) : 0;
	$diner[]     = citcom_fixture_block(
		'citcom/diner-menu',
		array_merge(
			array(
				'heading'      => $d['menu']['heading'],
				'_heading'     => 'field_diner_menu_heading',
				'button'       => citcom_fixture_local_link( $d['menu']['button']['url'], $d['menu']['button']['title'] ),
				'_button'      => 'field_diner_menu_button',
				'bleed_start'  => $bleed_start ? (string) $bleed_start : '',
				'_bleed_start' => 'field_diner_menu_bleed_a',
				'bleed_end'    => $bleed_end ? (string) $bleed_end : '',
				'_bleed_end'   => 'field_diner_menu_bleed_b',
			),
			citcom_fixture_repeater( 'cards', 'field_diner_menu_cards', $card_rows ),
			$settings
		)
	);

	$diner[] = citcom_fixture_block(
		'citcom/diner-story',
		array_merge(
			array(
				'heading'  => $d['story']['heading'],
				'_heading' => 'field_diner_story_heading',
				'content'  => $d['story']['content'],
				'_content' => 'field_diner_story_content',
				'button'   => citcom_fixture_local_link( $d['story']['button']['url'], $d['story']['button']['title'] ),
				'_button'  => 'field_diner_story_button',
				'image'    => '',
				'_image'   => 'field_diner_story_image',
			),
			citcom_fixture_diner_stamp( 'story', $d['story']['stamp'] ),
			$settings
		)
	);

	$logo_rows = array();
	foreach ( $d['wall']['logos'] as $logo ) {
		$logo_rows[] = array(
			'image' => array( 'field_diner_wall_logo_image', (string) citcom_fixture_image( $logo['url'], 'Client wall of fame ' . $logo['name'] ) ),
			'name'  => array( 'field_diner_wall_logo_name', $logo['name'] ),
		);
	}
	$diner[] = citcom_fixture_block(
		'citcom/diner-wall',
		array_merge(
			array(
				'heading'         => $d['wall']['heading'],
				'_heading'        => 'field_diner_wall_heading',
				'heading_script'  => $d['wall']['script'],
				'_heading_script' => 'field_diner_wall_heading_script',
			),
			citcom_fixture_repeater( 'logos', 'field_diner_wall_logos', $logo_rows ),
			citcom_fixture_diner_stamp( 'wall', array() ),
			$settings
		)
	);

	$reviews_base = array(
		'heading'               => $d['reviews']['heading'],
		'_heading'              => 'field_diner_reviews_heading',
		'summary'               => '',
		'_summary'              => 'field_diner_reviews_summary',
		'summary_label'         => 'Excellent',
		'_summary_label'        => 'field_diner_reviews_summary_label',
		'summary_rating'        => '5',
		'_summary_rating'       => 'field_diner_reviews_summary_rating',
		'summary_review_count'  => '20',
		'_summary_review_count' => 'field_diner_reviews_summary_count',
		'summary_link'          => '',
		'_summary_link'         => 'field_diner_reviews_summary_link',
	);
	$diner[]      = citcom_fixture_block(
		'citcom/diner-reviews',
		array_merge(
			$reviews_base,
			array(
				'trustindex_code'  => $d['reviews']['trustindex_code'],
				'_trustindex_code' => 'field_diner_reviews_trustindex',
			),
			citcom_fixture_repeater( 'reviews', 'field_diner_reviews_cards', array() ),
			$settings
		)
	);

	$diner[] = citcom_fixture_block(
		'citcom/diner-guestcheck',
		array_merge(
			array(
				'heading'       => $d['guestcheck']['heading'],
				'_heading'      => 'field_diner_guestcheck_heading',
				'card_heading'  => $d['guestcheck']['card_heading'],
				'_card_heading' => 'field_diner_guestcheck_card_heading',
				'card_content'  => $d['guestcheck']['card_content'],
				'_card_content' => 'field_diner_guestcheck_card_content',
				'card_image'    => '',
				'_card_image'   => 'field_diner_guestcheck_card_image',
				'form'          => (string) $d['guestcheck']['form'],
				'_form'         => 'field_diner_guestcheck_form',
			),
			citcom_fixture_diner_stamp( 'guestcheck', $d['guestcheck']['stamp'] ),
			$settings
		)
	);

	$diner_id = citcom_fixture_page( 'CitCom Creative Diner', 'creative-diner', implode( "\n\n", $diner ) );
	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $diner_id );
	WP_CLI::log( "Diner home page: $diner_id (set as the front page)" );

	// Extras: the variants the staging home page does not use.
	$sample_reviews = array();
	foreach ( array( 'Sample Reviewer One', 'Sample Reviewer Two', 'Sample Reviewer Three', 'Sample Reviewer Four' ) as $n => $name ) {
		$sample_reviews[] = array(
			'avatar'      => array( 'field_diner_reviews_card_avatar', '' ),
			'name'        => array( 'field_diner_reviews_card_name', $name ),
			'time_ago'    => array( 'field_diner_reviews_card_time', ( $n + 2 ) . ' months ago' ),
			'rating'      => array( 'field_diner_reviews_card_rating', 3 === $n ? '4' : '5' ),
			'verified'    => array( 'field_diner_reviews_card_verified', 2 === $n ? '0' : '1' ),
			'review_text' => array( 'field_diner_reviews_card_text', 'Sample review text for the local fixture. It runs long enough to wrap over a few lines inside the card, as a real review would.' ),
			'link'        => array(
				'field_diner_reviews_card_link',
				0 === $n ? array(
					'title'  => 'Read more',
					'url'    => 'https://www.google.com/',
					'target' => '_blank',
				) : '',
			),
		);
	}
	$extras    = array(
		citcom_fixture_block(
			'citcom/diner-intro',
			array_merge(
				array(
					'heading'         => $d['intro']['heading'],
					'_heading'        => 'field_diner_intro_heading',
					'heading_accent'  => $d['intro']['accent'],
					'_heading_accent' => 'field_diner_intro_accent',
					'content'         => $d['intro']['content'],
					'_content'        => 'field_diner_intro_content',
				),
				citcom_fixture_diner_stamp(
					'intro',
					array(
						'enabled' => true,
						'line_1'  => 'Est.',
						'line_2'  => '1987',
						'line_3'  => 'Kidderminster',
					)
				),
				$settings
			)
		),
		citcom_fixture_block(
			'citcom/diner-story-light',
			array_merge(
				array(
					'heading'  => $d['story']['heading'],
					'_heading' => 'field_diner_story_light_heading',
					'content'  => $d['story']['content'],
					'_content' => 'field_diner_story_light_content',
					'button'   => citcom_fixture_local_link( $d['story']['button']['url'], $d['story']['button']['title'] ),
					'_button'  => 'field_diner_story_light_button',
					'image'    => '',
					'_image'   => 'field_diner_story_light_image',
				),
				citcom_fixture_diner_stamp( 'story_light', array() ),
				$settings
			)
		),
		citcom_fixture_block(
			'citcom/diner-reviews',
			array_merge(
				$reviews_base,
				array(
					'trustindex_code'  => '',
					'_trustindex_code' => 'field_diner_reviews_trustindex',
				),
				citcom_fixture_repeater( 'reviews', 'field_diner_reviews_cards', $sample_reviews ),
				$settings
			)
		),
	);
	$extras_id = citcom_fixture_page( 'Diner extras', 'diner-extras', implode( "\n\n", $extras ) );
	WP_CLI::log( "Diner extras page: $extras_id" );
}

/*
 * 7. Blog posts and the sidebar block widgets, as staging has them: the blog
 * listing sidebar (search, top posts, newsletter) and the blog post sidebar
 * (related posts, newsletter, latest posts). Post titles and categories are the
 * ones staging lists; the post content is sample text.
 */
$blog_posts = array(
	array( 'Why we bet on WooCommerce', 'why-we-bet-on-woocommerce', 'Development', '2026-09-09' ),
	array( 'How World Events Are Shaping the Future of AI Search', 'how-world-events-are-shaping-the-future-of-ai-search', 'AISO', '2026-07-13' ),
	array( 'The Digital Jigsaw: A Beginner\'s Guide to the Tech Stack Behind a Modern Website', 'the-digital-jigsaw-a-beginners-guide-to-the-tech-stack-behind-a-modern-website', 'Development', '2026-06-23' ),
	array( 'The Rise of Black Friday in the UK', 'the-rise-of-black-friday-in-the-uk', 'Marketing', '2025-09-29' ),
	array( 'Introducing CitCom.', 'introducing-citcom', 'News', '2024-10-22' ),
);
$blog_ids   = array();
foreach ( $blog_posts as list( $blog_title, $blog_slug, $blog_cat, $blog_date ) ) {
	$cat = term_exists( $blog_cat, 'category' );
	if ( ! $cat ) {
		$cat = wp_insert_term( $blog_cat, 'category' );
	}
	$found   = get_page_by_path( $blog_slug, OBJECT, 'post' );
	$blog_id = $found ? (int) $found->ID : (int) wp_insert_post(
		wp_slash(
			array(
				'post_type'     => 'post',
				'post_title'    => $blog_title,
				'post_name'     => $blog_slug,
				'post_status'   => 'publish',
				'post_date'     => $blog_date . ' 09:00:00',
				'post_content'  => '<!-- wp:paragraph --><p>Sample post content for the local fixture. The real article lives on staging.</p><!-- /wp:paragraph -->',
				'post_category' => is_wp_error( $cat ) ? array() : array( (int) $cat['term_id'] ),
			)
		)
	);
	if ( $blog_id ) {
		$blog_ids[ $blog_slug ] = $blog_id;
	}
}

$widget_blocks = array(
	'blog_sidebar' => array(
		citcom_fixture_block( 'citcom/posts-search', array() ),
		citcom_fixture_block(
			'citcom/top-blog-posts',
			array(
				'title'  => 'Top Blog Posts',
				'_title' => 'field_6706ea529eb87',
				'posts'  => array_map( 'strval', array_filter( array( $blog_ids['introducing-citcom'] ?? 0, $blog_ids['the-rise-of-black-friday-in-the-uk'] ?? 0 ) ) ),
				'_posts' => 'field_6706ea8c9eb88',
			)
		),
		citcom_fixture_block(
			'citcom/newsletter-signup',
			array(
				'title'  => 'Sign up for CitCom updates, news and trends…',
				'_title' => 'field_67085971d6f3a',
			)
		),
	),
	'post_sidebar' => array(
		citcom_fixture_block( 'citcom/related-posts', array() ),
		str_replace(
			'"mode":"preview"',
			'"mode":"preview","className":"mb-5 mt-4"',
			citcom_fixture_block(
				'citcom/newsletter-signup',
				array(
					'title'  => 'Sign up for CitCom updates, news and trends…',
					'_title' => 'field_67085971d6f3a',
				)
			)
		),
		citcom_fixture_block(
			'citcom/latest-posts',
			array(
				'title'  => 'Latest Posts',
				'_title' => 'field_670861105f832',
			)
		),
	),
);

$block_widgets    = get_option( 'widget_block', array() );
$sidebars_widgets = get_option( 'sidebars_widgets', array() );
$next_widget      = max( array_merge( array( 1 ), array_filter( array_keys( (array) $block_widgets ), 'is_int' ) ) ) + 1;
foreach ( $widget_blocks as $sidebar => $blocks ) {
	$existing = implode( ' ', array_map( static fn( $id ) => $block_widgets[ (int) str_replace( 'block-', '', $id ) ]['content'] ?? '', (array) ( $sidebars_widgets[ $sidebar ] ?? array() ) ) );
	if ( str_contains( $existing, 'wp:citcom/' ) ) {
		WP_CLI::log( "Sidebar $sidebar already has citcom block widgets." );
		continue;
	}
	$ids = array();
	foreach ( $blocks as $block_markup ) {
		$block_widgets[ $next_widget ] = array( 'content' => $block_markup );
		$ids[]                         = 'block-' . $next_widget;
		++$next_widget;
	}
	$sidebars_widgets[ $sidebar ] = $ids;
	WP_CLI::log( "Sidebar $sidebar: " . implode( ', ', $ids ) );
}
$block_widgets['_multiwidget'] = 1;
update_option( 'widget_block', $block_widgets );
update_option( 'sidebars_widgets', $sidebars_widgets );

/*
 * 8. The pages the main menu links to that had no local content: /services/
 * (the services archive template with the six core services), /blog/ (the posts
 * page) and /packages/. All copied from staging.
 */
if ( ! empty( $citcom_p2['services_archive'] ) ) {
	$sa       = $citcom_p2['services_archive'];
	$core_ids = array();
	foreach ( $sa['rows'] as $row ) {
		$found = get_page_by_path( $row['slug'], OBJECT, 'service' );
		$id    = $found ? (int) $found->ID : (int) wp_insert_post(
			array(
				'post_type'   => 'service',
				'post_title'  => $row['title'],
				'post_name'   => $row['slug'],
				'post_status' => 'publish',
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
		$core_ids[] = $id;
	}

	$services_content  = citcom_fixture_page_header(
		array(
			'title'    => $sa['title'],
			'type'     => 'pattern',
			'bg-color' => '#eff1f3',
			'pattern'  => 'secondary',
		)
	) . "\n\n" . citcom_fixture_block(
		'citcom/sub-services',
		array_merge(
			array(
				'select_sub_services'  => $core_ids,
				'_select_sub_services' => 'field_670d082986a53',
			),
			citcom_fixture_section_settings( $sa['classes'] )
		)
	);
	$services_template = get_page_by_path( 'services-archive', OBJECT, 'template' );
	$services_args     = array(
		'post_type'    => 'template',
		'post_title'   => 'Services archive',
		'post_name'    => 'services-archive',
		'post_status'  => 'publish',
		'post_content' => wp_slash( $services_content ),
	);
	if ( $services_template ) {
		$services_args['ID'] = $services_template->ID;
		$services_id         = (int) wp_update_post( $services_args );
	} else {
		$services_id = (int) wp_insert_post( $services_args );
	}
	update_field( 'services_archive', $services_id, 'option' );
	WP_CLI::log( "Services archive template: $services_id with " . count( $core_ids ) . ' services.' );
}

// Service pages with no content yet get a header and a note, so the "Find out more"
// links from the service rows do not land on an empty page.
foreach ( get_posts(
	array(
		'post_type'      => 'service',
		'posts_per_page' => -1,
	)
) as $service_post ) {
	if ( '' !== trim( $service_post->post_content ) ) {
		continue;
	}
	wp_update_post(
		array(
			'ID'           => $service_post->ID,
			'post_content' => wp_slash(
				citcom_fixture_page_header(
					array(
						'title'    => $service_post->post_title,
						'type'     => 'pattern',
						'bg-color' => '#eff1f3',
						'pattern'  => 'persian',
					)
				) . '

' . citcom_fixture_editor_block( '<!-- wp:paragraph --><p>Placeholder for the local fixture. The real content of this service page arrives with the Phase 3 migration.</p><!-- /wp:paragraph -->' )
			),
		)
	);
}

// Blog: the posts page, with the listing (and its sidebar) and the newsletter CTA.
$blog_content = citcom_fixture_page_header(
	array(
		'title'    => 'Blog',
		'type'     => 'pattern',
		'bg-color' => '#eff1f3',
		'pattern'  => 'carrot',
	)
) . "\n\n" . citcom_fixture_block(
	'citcom/display-posts',
	array_merge(
		array(
			'type_of_display'  => 'archive',
			'_type_of_display' => 'field_670444d07937b',
			'post_type'        => 'post',
			'_post_type'       => 'field_670444347937a',
		),
		citcom_fixture_section_settings( '' )
	)
) . "\n\n" . citcom_fixture_block(
	'citcom/cta',
	array(
		'type_of_cta'  => 'sign-up',
		'_type_of_cta' => 'field_66fe71bd5bb02',
		'content'      => '<h3>Sign up to our newsletter</h3>',
		'_content'     => 'field_66fd5dae3ef00',
		'anchor_name'  => '',
		'_anchor_name' => 'field_6790f3345a787',
	)
);
$blog_page_id = citcom_fixture_page( 'Blog', 'blog', $blog_content );
update_option( 'page_for_posts', $blog_page_id );
WP_CLI::log( "Blog (posts page): $blog_page_id" );

// Packages: five media and text sections, the reviews widget and the social CTA.
if ( ! empty( $citcom_p2['packages'] ) ) {
	$packages_content = citcom_fixture_page_header(
		array(
			'title'    => $citcom_p2['packages']['title'],
			'type'     => 'pattern',
			'bg-color' => '#eff1f3',
			'pattern'  => 'secondary',
		)
	);
	foreach ( citcom_fixture_about_sections( 'packages-sections.json' ) as $section ) {
		$packages_content .= "\n\n" . $section;
	}
	$packages_content .= "\n\n" . citcom_fixture_block(
		'citcom/trustindex',
		array_merge(
			array(
				'trustindex_code'  => '[trustindex no-registration=google]',
				'_trustindex_code' => 'field_citcom_trustindex_code',
			),
			citcom_fixture_section_settings( '' )
		)
	);
	$packages_content .= "\n\n" . citcom_fixture_block(
		'citcom/cta',
		array(
			'type_of_cta'  => $citcom_p2['packages']['cta']['type'] ?? 'social',
			'_type_of_cta' => 'field_66fe71bd5bb02',
			'content'      => $citcom_p2['packages']['cta']['content'] ?? '',
			'_content'     => 'field_66fd5dae3ef00',
			// The old Forminator form id, left as the migration will find it: it resolves to the theme's packages form.
			'form'         => $citcom_p2['packages']['cta']['form'] ?? '219819',
			'_form'        => 'field_66fe797df659f',
			'anchor_name'  => '',
			'_anchor_name' => 'field_6790f3345a787',
		)
	);
	$packages_id       = citcom_fixture_page( 'CitCom Packages', 'packages', $packages_content );
	WP_CLI::log( "Packages page: $packages_id" );
}

// Forms test page: the three forms that live on /info/ landing pages on staging, in
// the sections that hold them there. The shortcodes keep their Forminator ids, as
// the migrated content will.
$forms_shortcode = static function ( string $shortcode, string $classes ): string {
	return citcom_fixture_block(
		'citcom/shortcode',
		array_merge(
			array(
				'shortcode'  => $shortcode,
				'_shortcode' => 'field_671a540044e70',
			),
			citcom_fixture_section_settings( $classes )
		)
	);
};
$forms_test      = citcom_fixture_page_header(
	array(
		'title'    => 'Forms test',
		'type'     => 'pattern',
		'bg-color' => '#eff1f3',
		'pattern'  => 'persian',
	)
) . "\n\n" . $forms_shortcode( '[forminator_form id="219727"]', 'bg-default_lighter' )
	. "\n\n" . $forms_shortcode( '[forminator_form id="219725"]', 'bg-default_lighter text-primary pt-0' )
	. "\n\n" . citcom_fixture_block(
		'citcom/cta',
		array(
			'type_of_cta'  => 'form',
			'_type_of_cta' => 'field_66fe71bd5bb02',
			'content'      => '<h2><strong>Free SEO audit</strong></h2><p>The SEO audit form, as on /info/free-seo-audit/.</p>',
			'_content'     => 'field_66fd5dae3ef00',
			'form'         => '218841',
			'_form'        => 'field_66fe797df659f',
			'anchor_name'  => '',
			'_anchor_name' => 'field_6790f3345a787',
		)
	);
$forms_test_id   = citcom_fixture_page( 'Forms test', 'forms-test', $forms_test );
WP_CLI::log( "Forms test page: $forms_test_id" );
// Any other menu link that still has no local content (policies, package sub-pages)
// gets a placeholder, so nothing in the menus leads to a 404.
$placeholders = 0;
foreach ( wp_get_nav_menus() as $nav_menu ) {
	foreach ( (array) wp_get_nav_menu_items( $nav_menu->term_id ) as $menu_item ) {
		if ( ! str_starts_with( $menu_item->url, home_url( '/' ) ) ) {
			continue;
		}
		$path     = trim( (string) wp_parse_url( $menu_item->url, PHP_URL_PATH ), '/' );
		$segments = array_values( array_filter( explode( '/', $path ) ) );
		if ( ! $segments || in_array( $segments[0], array( 'case-studies', 'blog' ), true ) ) {
			continue;
		}
		$is_service = 'services' === $segments[0] && count( $segments ) > 1;
		$type       = $is_service ? 'service' : 'page';
		$lookup     = $is_service ? implode( '/', array_slice( $segments, 1 ) ) : $path;
		if ( 'services' === $path || get_page_by_path( $lookup, OBJECT, $type ) ) {
			continue;
		}
		$parent_path = implode( '/', array_slice( $is_service ? array_slice( $segments, 1 ) : $segments, 0, -1 ) );
		$parent      = '' !== $parent_path ? get_page_by_path( $parent_path, OBJECT, $type ) : null;
		$title       = html_entity_decode( $menu_item->title, ENT_QUOTES | ENT_HTML5 );
		wp_insert_post(
			array(
				'post_type'    => $type,
				'post_title'   => $title,
				'post_name'    => end( $segments ),
				'post_parent'  => $parent ? $parent->ID : 0,
				'post_status'  => 'publish',
				'post_content' => wp_slash(
					citcom_fixture_page_header(
						array(
							'title'    => $title,
							'type'     => 'pattern',
							'bg-color' => '#eff1f3',
							'pattern'  => 'persian',
						)
					) . '

' . citcom_fixture_editor_block( '<!-- wp:paragraph --><p>Placeholder for the local fixture. The real content of this page arrives with the Phase 3 migration.</p><!-- /wp:paragraph -->' )
				),
			)
		);
		++$placeholders;
	}
}
WP_CLI::log( "Placeholder pages created: $placeholders" );

flush_rewrite_rules( false );
WP_CLI::success( 'Phase 2 fixture done: / (diner home), /about-us/, /contact-us/, /home-classic/, /results/, /services/, /services/creative/, /blog/, /packages/, /diner-extras/, five blog posts and the sidebar widgets.' );
