<?php
/**
 * Post types and taxonomy, moved from the ACF UI JSON (post_type_*.json,
 * taxonomy_*.json) into PHP with identical slugs, rewrites and supports.
 *
 * Differences from the JSON: `editor` support is on (and show_in_rest stays true)
 * so the block editor is available. Registered at init priority 5 so ACF
 * (priority 6) sees the keys as taken and skips its own copies; the ACF UI
 * entries in the database are deactivated as the first step of the Phase 3 staging
 * run, when this theme is activated there (see docs/00-discovery.md).
 *
 * @package citcom
 */

defined( 'ABSPATH' ) || exit;

/**
 * Build the labels array from singular/plural names the way ACF's UI did.
 *
 * @param string $singular  e.g. "Case Study".
 * @param string $plural    e.g. "Case Studies".
 * @param string $lower_s   e.g. "case study".
 * @param string $lower_p   e.g. "case studies".
 * @param array  $overrides Any label to override.
 * @return array<string,string>
 */
function citcom_post_type_labels( string $singular, string $plural, string $lower_s, string $lower_p, array $overrides = array() ): array {
	return array_merge(
		array(
			'name'                     => $plural,
			'singular_name'            => $singular,
			'menu_name'                => $plural,
			'all_items'                => 'All ' . $plural,
			'edit_item'                => 'Edit ' . $singular,
			'view_item'                => 'View ' . $singular,
			'view_items'               => 'View ' . $plural,
			'add_new_item'             => 'Add New ' . $singular,
			'add_new'                  => 'Add New ' . $singular,
			'new_item'                 => 'New ' . $singular,
			'parent_item_colon'        => 'Parent ' . $singular . ':',
			'search_items'             => 'Search ' . $plural,
			'not_found'                => 'No ' . $lower_p . ' found',
			'not_found_in_trash'       => 'No ' . $lower_p . ' found in Trash',
			'archives'                 => $singular . ' Archives',
			'attributes'               => $singular . ' Attributes',
			'insert_into_item'         => 'Insert into ' . $lower_s,
			'uploaded_to_this_item'    => 'Uploaded to this ' . $lower_s,
			'filter_items_list'        => 'Filter ' . $lower_p . ' list',
			'filter_by_date'           => 'Filter ' . $lower_p . ' by date',
			'items_list_navigation'    => $plural . ' list navigation',
			'items_list'               => $plural . ' list',
			'item_published'           => $singular . ' published.',
			'item_published_privately' => $singular . ' published privately.',
			'item_reverted_to_draft'   => $singular . ' reverted to draft.',
			'item_scheduled'           => $singular . ' scheduled.',
			'item_updated'             => $singular . ' updated.',
			'item_link'                => $singular . ' Link',
			'item_link_description'    => 'A link to a ' . $lower_s . '.',
		),
		$overrides
	);
}

add_action(
	'init',
	function () {
		$supports = array( 'title', 'editor', 'thumbnail', 'custom-fields', 'revisions' );

		register_post_type(
			'case-study',
			array(
				'labels'              => citcom_post_type_labels( 'Case Study', 'Case Studies', 'case study', 'case studies' ),
				'public'              => true,
				'hierarchical'        => false,
				'exclude_from_search' => false,
				'publicly_queryable'  => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_admin_bar'   => true,
				'show_in_nav_menus'   => true,
				'show_in_rest'        => true,
				'menu_position'       => 9,
				'menu_icon'           => 'dashicons-awards',
				'supports'            => $supports,
				'taxonomies'          => array( 'cs-tag' ),
				'has_archive'         => 'case-studies',
				'rewrite'             => array(
					'slug'       => 'case-studies',
					'with_front' => true,
					'feeds'      => false,
					'pages'      => true,
				),
				'query_var'           => true,
				'can_export'          => true,
				'delete_with_user'    => false,
			)
		);

		register_post_type(
			'service',
			array(
				'labels'              => citcom_post_type_labels( 'Service', 'Services', 'service', 'services' ),
				'public'              => true,
				'hierarchical'        => true,
				'exclude_from_search' => false,
				'publicly_queryable'  => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_admin_bar'   => true,
				'show_in_nav_menus'   => true,
				'show_in_rest'        => true,
				'menu_position'       => 21,
				'menu_icon'           => 'dashicons-cart',
				'supports'            => array_merge( $supports, array( 'page-attributes' ) ),
				'has_archive'         => 'services',
				'rewrite'             => array(
					'slug'       => 'services',
					'with_front' => true,
					'feeds'      => false,
					'pages'      => true,
				),
				'query_var'           => true,
				'can_export'          => true,
				'delete_with_user'    => false,
			)
		);

		register_post_type(
			'landing-page',
			array(
				'labels'              => citcom_post_type_labels(
					'Landing Page',
					'Landing Pages',
					'landing page',
					'landing pages',
					array( 'not_found_in_trash' => 'No landing pages found in the bin' )
				),
				'public'              => true,
				'hierarchical'        => false,
				'exclude_from_search' => true,
				'publicly_queryable'  => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_admin_bar'   => true,
				'show_in_nav_menus'   => false,
				'show_in_rest'        => true,
				'menu_icon'           => 'dashicons-airplane',
				'supports'            => $supports,
				'has_archive'         => false,
				'rewrite'             => array(
					'slug'       => 'info',
					'with_front' => true,
					'feeds'      => false,
					'pages'      => true,
				),
				'query_var'           => 'info',
				'can_export'          => true,
				'delete_with_user'    => false,
			)
		);

		register_post_type(
			'template',
			array(
				'labels'              => citcom_post_type_labels( 'Template', 'Templates', 'template', 'templates' ),
				'public'              => true,
				'hierarchical'        => false,
				'exclude_from_search' => true,
				'publicly_queryable'  => false,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_admin_bar'   => true,
				'show_in_nav_menus'   => false,
				'show_in_rest'        => true,
				'menu_position'       => 30,
				'menu_icon'           => 'dashicons-forms',
				'supports'            => array( 'title', 'editor', 'custom-fields', 'revisions' ),
				'has_archive'         => false,
				'rewrite'             => false,
				'query_var'           => true,
				'can_export'          => true,
				'delete_with_user'    => false,
			)
		);

		register_taxonomy(
			'cs-tag',
			array( 'case-study' ),
			array(
				'labels'             => array(
					'name'                       => 'Case Study Tags',
					'singular_name'              => 'Case Study Tag',
					'menu_name'                  => 'Case Study Tags',
					'all_items'                  => 'All Case Study Tags',
					'edit_item'                  => 'Edit Case Study Tag',
					'view_item'                  => 'View Case Study Tag',
					'update_item'                => 'Update Case Study Tag',
					'add_new_item'               => 'Add New Case Study Tag',
					'new_item_name'              => 'New Case Study Tag Name',
					'search_items'               => 'Search Case Study Tags',
					'popular_items'              => 'Popular Case Study Tags',
					'separate_items_with_commas' => 'Separate case study tags with commas',
					'add_or_remove_items'        => 'Add or remove case study tags',
					'choose_from_most_used'      => 'Choose from the most used case study tags',
					'not_found'                  => 'No case study tags found',
					'no_terms'                   => 'No case study tags',
					'items_list_navigation'      => 'Case Study Tags list navigation',
					'items_list'                 => 'Case Study Tags list',
					'back_to_items'              => '← Go to case study tags',
					'item_link'                  => 'Case Study Tag Link',
					'item_link_description'      => 'A link to a case study tag',
				),
				'public'             => true,
				'publicly_queryable' => true,
				'hierarchical'       => false,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'show_in_nav_menus'  => true,
				'show_in_rest'       => true,
				'show_tagcloud'      => true,
				'show_in_quick_edit' => true,
				'show_admin_column'  => false,
				'capabilities'       => array(
					'manage_terms' => 'manage_categories',
					'edit_terms'   => 'manage_categories',
					'delete_terms' => 'manage_categories',
					'assign_terms' => 'edit_posts',
				),
				'rewrite'            => array(
					'slug'         => 'case-study-tag',
					'with_front'   => true,
					'hierarchical' => false,
				),
				'query_var'          => 'cs-tag',
				'sort'               => false,
			)
		);
	},
	5
);
