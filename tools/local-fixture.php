<?php
/**
 * Local fixture for Phase 1 verification. Run from the WordPress root:
 *
 *   wp eval-file wp-content/themes/citcom-rebuild/tools/local-fixture.php
 *
 * Creates, on the local site only: the Site Settings logos, accreditations and
 * socials (fetched from staging), the three menus, and two pages that hold a
 * citcom/page-header block plus one core paragraph: "Contact Us" (pattern header,
 * like staging /contact-us/) and "About Us" (image header, like staging /about-us/).
 * Re-running reuses anything that already exists.
 *
 * @package citcom
 */

if ( ! defined( 'WP_CLI' ) ) {
	exit;
}

require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/nav-menu.php';

$citcom_staging = 'https://citcomstaging.mystagingwebsite.com';

// Allow SVG uploads for the logos (the Pressable sites do this with Safe SVG).
add_filter(
	'upload_mimes',
	function ( $mimes ) {
		$mimes['svg'] = 'image/svg+xml';
		return $mimes;
	}
);
add_filter(
	'wp_check_filetype_and_ext',
	function ( $data, $file, $filename ) {
		if ( str_ends_with( strtolower( $filename ), '.svg' ) ) {
			return array(
				'ext'             => 'svg',
				'type'            => 'image/svg+xml',
				'proper_filename' => false,
			);
		}
		return $data;
	},
	10,
	3
);

/**
 * Attachment ID by title, or 0.
 */
function citcom_fixture_attachment( string $title ): int {
	$found = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'title'          => $title,
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);
	return $found ? (int) $found[0] : 0;
}

/**
 * Sideload a remote file into the media library once.
 */
function citcom_fixture_sideload( string $url, string $title ): int {
	$existing = citcom_fixture_attachment( $title );
	if ( $existing ) {
		return $existing;
	}
	$tmp = download_url( $url );
	if ( is_wp_error( $tmp ) ) {
		WP_CLI::warning( "Download failed: $url " . $tmp->get_error_message() );
		return 0;
	}
	$id = media_handle_sideload(
		array(
			'name'     => basename( wp_parse_url( $url, PHP_URL_PATH ) ),
			'tmp_name' => $tmp,
		),
		0,
		$title
	);
	if ( is_wp_error( $id ) ) {
		WP_CLI::warning( "Sideload failed: $url " . $id->get_error_message() );
		return 0;
	}
	wp_update_post(
		array(
			'ID'         => $id,
			'post_title' => $title,
		)
	);
	return (int) $id;
}

/**
 * Upload a file from tools/fixtures once.
 */
function citcom_fixture_upload( string $path, string $title ): int {
	$existing = citcom_fixture_attachment( $title );
	if ( $existing ) {
		return $existing;
	}
	$tmp = wp_tempnam( basename( $path ) );
	copy( $path, $tmp );
	$id = media_handle_sideload(
		array(
			'name'     => basename( $path ),
			'tmp_name' => $tmp,
		),
		0,
		$title
	);
	if ( is_wp_error( $id ) ) {
		WP_CLI::warning( "Upload failed: $path " . $id->get_error_message() );
		return 0;
	}
	wp_update_post(
		array(
			'ID'         => $id,
			'post_title' => $title,
		)
	);
	return (int) $id;
}

/**
 * Create (or reuse) a menu with the given items and assign it to a location.
 *
 * @param array $items Each: [ 'title' => ..., 'url' => ... | 'page' => post ID, 'children' => [...], 'menu_id' => ACF Menu ID ].
 */
function citcom_fixture_menu( string $name, string $location, array $items ): int {
	$menu = wp_get_nav_menu_object( $name );
	if ( ! $menu ) {
		$menu_id = wp_create_nav_menu( $name );
	} else {
		$menu_id = (int) $menu->term_id;
	}
	if ( is_wp_error( $menu_id ) ) {
		WP_CLI::warning( "Menu failed: $name" );
		return 0;
	}

	if ( empty( wp_get_nav_menu_items( $menu_id ) ) ) {
		$add = function ( array $item, int $parent = 0 ) use ( &$add, $menu_id ) {
			$args = array(
				'menu-item-title'     => $item['title'],
				'menu-item-status'    => 'publish',
				'menu-item-parent-id' => $parent,
			);
			if ( ! empty( $item['page'] ) ) {
				$args['menu-item-type']      = 'post_type';
				$args['menu-item-object']    = 'page';
				$args['menu-item-object-id'] = (int) $item['page'];
			} else {
				$args['menu-item-type'] = 'custom';
				$args['menu-item-url']  = $item['url'];
			}
			$item_id = wp_update_nav_menu_item( $menu_id, 0, $args );
			if ( is_wp_error( $item_id ) ) {
				return;
			}
			if ( ! empty( $item['menu_id'] ) && function_exists( 'update_field' ) ) {
				update_field( 'field_67166ccfa0f49', $item['menu_id'], $item_id );
			}
			foreach ( $item['children'] ?? array() as $child ) {
				$add( $child, (int) $item_id );
			}
		};
		foreach ( $items as $item ) {
			$add( $item );
		}
	}

	$locations              = (array) get_theme_mod( 'nav_menu_locations' );
	$locations[ $location ] = $menu_id;
	set_theme_mod( 'nav_menu_locations', $locations );

	return (int) $menu_id;
}

/**
 * Create (or reuse) a page with block content.
 */
function citcom_fixture_page( string $title, string $slug, string $content ): int {
	$existing = get_page_by_path( $slug, OBJECT, 'page' );
	$args     = array(
		'post_type'    => 'page',
		'post_title'   => $title,
		'post_name'    => $slug,
		'post_status'  => 'publish',
		'post_content' => $content,
	);
	if ( $existing ) {
		$args['ID'] = $existing->ID;
		return (int) wp_update_post( $args );
	}
	return (int) wp_insert_post( $args );
}

/**
 * Serialised citcom/page-header block with the section settings at their defaults.
 */
function citcom_fixture_page_header( array $fields ): string {
	$defaults = array(
		'title'                 => '',
		'_title'                => 'field_66f9a9b4a42ac',
		'type'                  => 'pattern',
		'_type'                 => 'field_66f9b8f3ee6bd',
		'service'               => 'creative',
		'_service'              => 'field_66f9b9b0ee6bf',
		'bg-color'              => '#eff1f3',
		'_bg-color'             => 'field_66f9b53ba42ae',
		'pattern'               => 'persian',
		'_pattern'              => 'field_66f9a9d0a42ad',
		'header_image'          => array(
			'id'   => '',
			'top'  => '',
			'left' => '',
		),
		'_header_image'         => 'field_66f9a98fa42ab',
		'breadcrumb'            => '0',
		'_breadcrumb'           => 'field_6707d4f898015',
		'section_padding'       => 'default',
		'_section_padding'      => 'field_6604094e9b92e',
		'background_colour'     => 'default',
		'_background_colour'    => 'field_66f9ca9dd01a6',
		'background_color'      => '#FFFFFF',
		'_background_color'     => 'field_65f89df411402',
		'shape_pattern'         => '0',
		'_shape_pattern'        => 'field_670ac988ea441',
		'gradient_with_pattern' => 'none',
		'_gradient_with_pattern' => 'field_670aca45ea442',
		'text_colour'           => 'default',
		'_text_colour'          => 'field_66f9cb01b4db8',
		'text_color'            => '#1C0221',
		'_text_color'           => 'field_65f89f5b11403',
		'anchor_name'           => '',
		'_anchor_name'          => 'field_6616e84d4614e',
	);
	$block = array(
		'name' => 'citcom/page-header',
		'data' => array_merge( $defaults, $fields ),
		'mode' => 'preview',
	);
	return '<!-- wp:citcom/page-header ' . wp_json_encode( $block, JSON_UNESCAPED_SLASHES ) . ' /-->';
}

/**
 * Section settings block data from the old section's class list.
 *
 * @param string $classes The <section> class attribute from staging.
 * @return array<string,string>
 */
function citcom_fixture_section_settings( string $classes ): array {
	$hex = array(
		'primary'         => '#1C0221',
		'secondary'       => '#9AD14D',
		'default_darker'  => '#a2a4aa',
		'default'         => '#D8DBE2',
		'default_lighter' => '#eff1f3',
		'slate'           => '#2A4747',
		'persian'         => '#17A398',
		'carrot'          => '#F9A03F',
		'white'           => '#FFFFFF',
	);
	$data = array(
		'section_padding'       => 'default',
		'_section_padding'      => 'field_6604094e9b92e',
		'background_colour'     => 'default',
		'_background_colour'    => 'field_66f9ca9dd01a6',
		'background_color'      => '#FFFFFF',
		'_background_color'     => 'field_65f89df411402',
		'shape_pattern'         => '0',
		'_shape_pattern'        => 'field_670ac988ea441',
		'gradient_with_pattern' => 'none',
		'_gradient_with_pattern' => 'field_670aca45ea442',
		'text_colour'           => 'default',
		'_text_colour'          => 'field_66f9cb01b4db8',
		'text_color'            => '#1C0221',
		'_text_color'           => 'field_65f89f5b11403',
		'anchor_name'           => '',
		'_anchor_name'          => 'field_6616e84d4614e',
	);
	foreach ( preg_split( '/\s+/', $classes ) as $class ) {
		if ( in_array( $class, array( 'pt-0', 'pb-0', 'py-0' ), true ) ) {
			$data['section_padding'] = $class;
		} elseif ( preg_match( '/^bg-(\w+)$/', $class, $m ) && isset( $hex[ $m[1] ] ) ) {
			$data['background_colour'] = 'choose';
			$data['background_color']  = $hex[ $m[1] ];
		} elseif ( preg_match( '/^text-(\w+)$/', $class, $m ) && isset( $hex[ $m[1] ] ) ) {
			$data['text_colour'] = 'choose';
			$data['text_color']  = $hex[ $m[1] ];
		} elseif ( 'pattern-opac' === $class ) {
			$data['background_colour'] = 'choose';
			$data['shape_pattern']     = '1';
		} elseif ( preg_match( '/^grad-\w+$/', $class ) ) {
			$data['background_colour']     = 'choose';
			$data['gradient_with_pattern'] = $class;
		}
	}
	return $data;
}

/**
 * Raw editor HTML from staging as a core/html inner block, with the old
 * citdotLists() transforms reversed (the block applies them again on render).
 */
function citcom_fixture_inner_html( string $html ): string {
	$html = str_replace( '<li><span class="fa-li"><i class="fa-kit fa-citdot"></i></span>', '<li>', $html );
	$html = str_replace( '<ul class="fa-ul" style="--fa-li-width: 3rem;">', '<ul class="wp-block-list">', $html );
	$html = str_replace( 'wp-block-video v-vlite-container', 'wp-block-video', $html );
	$html = str_replace( 'wp-block-quote is-style-plain grad-persian text-light', 'wp-block-quote is-style-plain', $html );
	return '<!-- wp:html -->' . trim( $html ) . '<!-- /wp:html -->';
}

/**
 * Serialised citcom/editor and citcom/media-text blocks for the staging About
 * sections in tools/fixtures/about-sections.json (index 1 onwards).
 *
 * @return string[]
 */
function citcom_fixture_about_sections(): array {
	$file = __DIR__ . '/fixtures/about-sections.json';
	if ( ! file_exists( $file ) ) {
		return array();
	}
	$sections = json_decode( (string) file_get_contents( $file ), true ) ?: array();
	$blocks   = array();

	foreach ( $sections as $section ) {
		$settings = citcom_fixture_section_settings( $section['classes'] ?? '' );
		$inner    = citcom_fixture_inner_html( (string) ( $section['content'] ?? '' ) );

		if ( 'editor' === $section['layout'] ) {
			$attrs    = array(
				'name' => 'citcom/editor',
				'data' => $settings,
				'mode' => 'preview',
			);
			$blocks[] = '<!-- wp:citcom/editor ' . wp_json_encode( $attrs, JSON_UNESCAPED_SLASHES ) . ' -->' . $inner . '<!-- /wp:citcom/editor -->';
		}

		if ( 'media_text' === $section['layout'] ) {
			$image_id = 0;
			$left     = '50';
			$top      = '50';
			if ( ! empty( $section['image']['url'] ) ) {
				$image_id = citcom_fixture_sideload( $section['image']['url'], $section['image']['alt'] ?: basename( $section['image']['url'] ) );
				if ( preg_match( '/([\d.]+)% ([\d.]+)%/', $section['image']['position'] ?? '', $m ) ) {
					$left = $m[1];
					$top  = $m[2];
				}
			}
			$data  = array_merge(
				array(
					'media_type'     => 'image',
					'_media_type'    => 'field_66fabf8c203c8',
					'image'          => array(
						'id'   => $image_id,
						'top'  => $top,
						'left' => $left,
					),
					'_image'         => 'field_66fabf2e203c6',
					'image_1_scale'  => (string) ( (int) ( $section['image']['scale'] ?? 100 ) ),
					'_image_1_scale' => 'field_66fae40b102da',
					// FocusPoint expects an array even when empty; a string makes its validate_value() fatal in the editor.
					'image_2'        => array(
						'id'   => '',
						'top'  => '',
						'left' => '',
					),
					'_image_2'       => 'field_66fac4a83b6df',
					'image_2_scale'  => '100',
					'_image_2_scale' => 'field_66fae451102db',
					'video'          => '',
					'_video'         => 'field_66fabf67203c7',
					'align'          => $section['align'] ?? 'media_left',
					'_align'         => 'field_66fac1aa255fd',
					'citdot_style'   => ! empty( $section['citdot'] ) ? '1' : '0',
					'_citdot_style'  => 'field_66fac5f268ac6',
					'extra_padding'  => ! empty( $section['extra_padding'] ) ? '1' : '0',
					'_extra_padding' => 'field_66faf3eedd6a2',
				),
				$settings
			);
			$attrs = array(
				'name' => 'citcom/media-text',
				'data' => $data,
				'mode' => 'preview',
			);
			$blocks[] = '<!-- wp:citcom/media-text ' . wp_json_encode( $attrs, JSON_UNESCAPED_SLASHES ) . ' -->' . $inner . '<!-- /wp:citcom/media-text -->';
		}
	}

	return $blocks;
}

/*
 * 1. Site Settings.
 */
$logo_dark          = citcom_fixture_upload( __DIR__ . '/fixtures/logo-dark.svg', 'Citcom logo dark' );
$logo_light         = citcom_fixture_upload( __DIR__ . '/fixtures/logo-light.svg', 'Citcom logo light' );
$logo_light_default = citcom_fixture_upload( __DIR__ . '/fixtures/logo-light-default.svg', 'Citcom logo light default' );

update_field( 'logo_dark', $logo_dark, 'option' );
update_field( 'logo_light', $logo_light, 'option' );
update_field( 'logo_light_default', $logo_light_default, 'option' );
update_field( 'logo_dark_default', $logo_dark, 'option' );

$accreditations = array(
	'2024/09/google-partner.png'               => 'Google Partner',
	'2024/09/cyber-essentials-plus.png'        => 'Cyber Essentials Plus',
	'2024/09/worcestershire-apprenticeships.png' => 'Worcestershire Apprenticeships',
	'2024/10/Chamber-logo.png'                 => 'Chamber Logo',
	'2024/09/nw-business-awards.png'           => 'Nw Business Awards',
	'2024/09/the-drum-rose-awards.png'         => 'The Drum Rose Awards',
);
$accreditation_ids = array();
foreach ( $accreditations as $path => $title ) {
	$id = citcom_fixture_sideload( $citcom_staging . '/wp-content/uploads/' . $path, $title );
	if ( $id ) {
		$accreditation_ids[] = $id;
	}
}
update_field( 'footer_logos', $accreditation_ids, 'option' );

update_field(
	'social_icon_links',
	array(
		array(
			'icon' => 'fa-classic fa-brands fa-facebook-f',
			'link' => array(
				'title'  => 'Facebook',
				'url'    => 'https://www.facebook.com/Citizenccltd/',
				'target' => '_blank',
			),
		),
		array(
			'icon' => 'fa-classic fa-brands fa-instagram',
			'link' => array(
				'title'  => 'Instagram',
				'url'    => 'https://www.instagram.com/_citcom/',
				'target' => '_blank',
			),
		),
		array(
			'icon' => 'fa-classic fa-brands fa-linkedin-in',
			'link' => array(
				'title'  => 'LinkedIn',
				'url'    => 'https://www.linkedin.com/company/citizen-communication-media-ltd',
				'target' => '_blank',
			),
		),
		array(
			'icon' => 'fa-classic fa-brands fa-youtube',
			'link' => array(
				'title'  => 'YouTube',
				'url'    => 'https://www.youtube.com/channel/UCKN5pPJzsT-2zzULzeda_HQ/videos',
				'target' => '_blank',
			),
		),
		array(
			'icon' => 'fa-classic fa-brands fa-google',
			'link' => array(
				'title'  => 'Google Reviews',
				'url'    => 'https://www.google.com/search?q=Citizen+Communication+Media+Ltd',
				'target' => '_blank',
			),
		),
	),
	'option'
);

/*
 * 2. Pages.
 */
$header_image = citcom_fixture_sideload( $citcom_staging . '/wp-content/uploads/2024/11/IMG_0213-2-scaled.jpg', 'Img 0213 2' );

$paragraph = '<!-- wp:paragraph --><p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Praesent nibh nulla, gravida at dolor ac, pharetra vulputate justo. Sed neque lectus, mattis id erat sed, lobortis fermentum diam.</p><!-- /wp:paragraph -->';

// The cta block below copies staging /services/creative/ section 2 (Text & Button).
$cta_block = array(
	'name' => 'citcom/cta',
	'data' => array_merge(
		array(
			'type_of_cta'  => 'button',
			'_type_of_cta' => 'field_66fe71bd5bb02',
			'content'      => '<h2>Got a brief? Contact us today!</h2>',
			'_content'     => 'field_66fd5dae3ef00',
			'button'       => array(
				'title'  => 'Contact Us',
				'url'    => home_url( '/contact-us/' ),
				'target' => '_blank',
			),
			'_button'      => 'field_66fe73e75bb03',
			'button_2'     => '',
			'_button_2'    => 'field_66fe74175bb04',
			'form'         => '',
			'_form'        => 'field_66fe797df659f',
		),
		citcom_fixture_section_settings( '' )
	),
	'mode' => 'preview',
);

$contact_id = citcom_fixture_page(
	'Contact Us',
	'contact-us',
	citcom_fixture_page_header(
		array(
			'title'    => 'Contact Us',
			'type'     => 'pattern',
			'bg-color' => '#eff1f3',
			'pattern'  => 'persian',
		)
	) . "\n\n" . $paragraph . "\n\n" . '<!-- wp:citcom/cta ' . wp_json_encode( $cta_block, JSON_UNESCAPED_SLASHES ) . ' /-->'
);

/*
 * About Us reproduces staging sections 0 to 5 (page header, three editor sections,
 * two media_text sections) from tools/fixtures/about-sections.json, extracted from
 * the staging HTML. Editor content is kept as raw HTML in a core/html inner block
 * so the comparison tests the section blocks, not a content conversion.
 */
$about_content = citcom_fixture_page_header(
	array(
		'title'        => 'About Us',
		'type'         => 'image',
		'header_image' => array(
			'id'   => $header_image,
			'top'  => '54.49',
			'left' => '39.19',
		),
	)
);

foreach ( citcom_fixture_about_sections() as $section ) {
	$about_content .= "\n\n" . $section;
}

$about_id = citcom_fixture_page( 'About Us', 'about-us', $about_content );

/*
 * 2b. Case studies and the "Case Studies" template post, so /case-studies/ can be
 * compared with staging: the six cards on staging page 1 (tools/fixtures/
 * case-studies.json) plus one older filler so "Load more" appears, six per page.
 */
update_option( 'posts_per_page', 6 );

$cs_file = __DIR__ . '/fixtures/case-studies.json';
$cs_ids  = array();
if ( file_exists( $cs_file ) ) {
	$cs_items = json_decode( (string) file_get_contents( $cs_file ), true ) ?: array();
	$when     = strtotime( '2026-09-01 12:00:00' );
	foreach ( $cs_items as $i => $item ) {
		$existing = get_page_by_path( $item['slug'], OBJECT, 'case-study' );
		$cs_id    = $existing ? (int) $existing->ID : (int) wp_insert_post(
			array(
				'post_type'   => 'case-study',
				'post_title'  => html_entity_decode( $item['title'], ENT_QUOTES | ENT_HTML5 ),
				'post_name'   => $item['slug'],
				'post_status' => 'publish',
				'post_date'   => gmdate( 'Y-m-d H:i:s', $when - $i * DAY_IN_SECONDS ),
			)
		);
		if ( ! $cs_id ) {
			continue;
		}
		$cs_ids[] = $cs_id;
		$term_ids = array();
		foreach ( $item['tags'] as $tag ) {
			$term = term_exists( $tag['slug'], 'cs-tag' );
			if ( ! $term ) {
				$term = wp_insert_term( $tag['name'], 'cs-tag', array( 'slug' => $tag['slug'] ) );
			}
			if ( ! is_wp_error( $term ) ) {
				$term_ids[] = (int) $term['term_id'];
			}
		}
		wp_set_object_terms( $cs_id, $term_ids, 'cs-tag' );
		if ( ! empty( $item['image'] ) && ! get_post_thumbnail_id( $cs_id ) ) {
			$thumb = citcom_fixture_sideload( $item['image'], $item['alt'] ?: $item['title'] );
			if ( $thumb ) {
				set_post_thumbnail( $cs_id, $thumb );
			}
		}
	}
	if ( ! get_page_by_path( 'fixture-filler-case-study', OBJECT, 'case-study' ) ) {
		wp_insert_post(
			array(
				'post_type'   => 'case-study',
				'post_title'  => 'Fixture filler case study',
				'post_name'   => 'fixture-filler-case-study',
				'post_status' => 'publish',
				'post_date'   => '2025-01-01 12:00:00',
			)
		);
	}
}

$cs_template = get_page_by_path( 'case-studies-archive', OBJECT, 'template' );
$cs_template_content = citcom_fixture_page_header(
	array(
		'title'    => 'Case Studies',
		'type'     => 'pattern',
		'bg-color' => '#eff1f3',
		'pattern'  => 'persian',
		'breadcrumb' => '1',
	)
) . "\n\n" . '<!-- wp:citcom/display-posts ' . wp_json_encode(
	array(
		'name' => 'citcom/display-posts',
		'data' => array_merge(
			array(
				'type_of_display'          => 'archive',
				'_type_of_display'         => 'field_670444d07937b',
				'number_of_posts_to_show'  => '3',
				'_number_of_posts_to_show' => 'field_670442b079378',
				'post_type'                => 'case-study',
				'_post_type'               => 'field_670444347937a',
				'posts'                    => '',
				'_posts'                   => 'field_6704432279379',
				'posts_cs'                 => '',
				'_posts_cs'                => 'field_670973db20a89',
			),
			citcom_fixture_section_settings( '' )
		),
		'mode' => 'preview',
	),
	JSON_UNESCAPED_SLASHES
) . ' /-->';
$cs_template_args = array(
	'post_type'    => 'template',
	'post_title'   => 'Case Studies archive',
	'post_name'    => 'case-studies-archive',
	'post_status'  => 'publish',
	'post_content' => $cs_template_content,
);
if ( $cs_template ) {
	$cs_template_args['ID'] = $cs_template->ID;
	$cs_template_id         = (int) wp_update_post( $cs_template_args );
} else {
	$cs_template_id = (int) wp_insert_post( $cs_template_args );
}
update_field( 'case_study_archive', $cs_template_id, 'option' );
update_field( 'case_studies_tag_archive', $cs_template_id, 'option' );

/*
 * 3. Menus (custom links mirror the staging structure).
 */
$home = untrailingslashit( home_url() );

citcom_fixture_menu(
	'Primary',
	'primary_menu',
	array(
		array(
			'title'    => 'Services',
			'url'      => $home . '/services/',
			'children' => array(
				array( 'title' => 'Creative', 'url' => $home . '/services/creative/' ),
				array( 'title' => 'Development', 'url' => $home . '/services/development/' ),
				array( 'title' => 'Events', 'url' => $home . '/services/events/' ),
				array( 'title' => 'Marketing', 'url' => $home . '/services/marketing/' ),
				array( 'title' => 'Print', 'url' => $home . '/services/print/' ),
				array( 'title' => 'Video Production', 'url' => $home . '/services/video/' ),
				array( 'title' => 'ChatCom – AI Support', 'url' => $home . '/services/chatcom-ai-support/' ),
			),
		),
		array( 'title' => 'Our Work', 'url' => $home . '/case-studies/' ),
		array(
			'title'    => 'Packages',
			'url'      => $home . '/packages/',
			'children' => array(
				array( 'title' => 'Branding Packages', 'url' => $home . '/packages/branding-packages/' ),
				array( 'title' => 'Website Packages', 'url' => $home . '/packages/website-packages/' ),
				array( 'title' => 'Creative Retainers', 'url' => $home . '/packages/creative-retainers/' ),
				array( 'title' => 'Hosting & Management', 'url' => $home . '/packages/hosting-management/' ),
			),
		),
		array(
			'title'    => 'About Us',
			'page'     => $about_id,
			'children' => array(
				array( 'title' => 'Blog', 'url' => $home . '/blog/' ),
			),
		),
		array( 'title' => 'Contact', 'page' => $contact_id ),
	)
);

citcom_fixture_menu(
	'Footer 1',
	'footer_menu_1',
	array(
		array( 'title' => 'Services', 'url' => $home . '/services/' ),
		array( 'title' => 'Our Work', 'url' => $home . '/case-studies/' ),
		array( 'title' => 'Blog', 'url' => $home . '/blog/' ),
		array( 'title' => 'About Us', 'page' => $about_id ),
		array( 'title' => 'Contact Us', 'page' => $contact_id ),
	)
);

citcom_fixture_menu(
	'Footer 2',
	'footer_menu_2',
	array(
		array( 'title' => 'AI Usage Policy', 'url' => $home . '/ai-usage-policy/' ),
		array( 'title' => 'Terms of Use', 'url' => $home . '/terms-of-use/' ),
		array( 'title' => 'Marketing Agreement', 'url' => $home . '/marketing-agreement/' ),
		array( 'title' => 'DEO Policy', 'url' => $home . '/edipolicy/' ),
		array( 'title' => 'Environmental Policy', 'url' => $home . '/environmentalpolicy/' ),
		array( 'title' => 'Cookie Preferences', 'url' => '#', 'menu_id' => 'open_preferences_center' ),
	)
);

WP_CLI::success(
	sprintf(
		'Fixture ready. Logos %d/%d/%d, accreditations %d, header image %d, pages %s and %s.',
		$logo_dark,
		$logo_light,
		$logo_light_default,
		count( $accreditation_ids ),
		$header_image,
		get_permalink( $contact_id ),
		get_permalink( $about_id )
	)
);
