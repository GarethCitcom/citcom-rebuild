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
		'header_image'          => '',
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
	) . "\n\n" . $paragraph
);

$about_id = citcom_fixture_page(
	'About Us',
	'about-us',
	citcom_fixture_page_header(
		array(
			'title'        => 'About Us',
			'type'         => 'image',
			'header_image' => array(
				'id'   => $header_image,
				'top'  => '54.49',
				'left' => '39.19',
			),
		)
	) . "\n\n" . $paragraph
);

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
