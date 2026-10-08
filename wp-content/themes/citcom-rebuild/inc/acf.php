<?php
/**
 * ACF configuration: JSON load/save paths, the Site Settings options page and the
 * ACF Extended settings the original theme relied on.
 *
 * acf-json/ holds the shared and non-block field groups. Each block keeps its own
 * group in blocks/<name>/fields.json; ACF loads it from there and saves it back
 * there when the group is edited in wp-admin.
 *
 * @package citcom
 */

defined( 'ABSPATH' ) || exit;

/**
 * Directory of every block that ships a fields.json.
 *
 * @return string[] Absolute paths.
 */
function citcom_block_field_dirs(): array {
	static $dirs = null;
	if ( null === $dirs ) {
		$dirs = array();
		foreach ( glob( CITCOM_THEME_DIR . '/blocks/*/fields.json' ) ?: array() as $file ) {
			$dirs[] = dirname( $file );
		}
	}
	return $dirs;
}

/**
 * Field group key stored in a block's fields.json.
 *
 * @param string $dir Block directory.
 * @return string
 */
function citcom_block_field_group_key( string $dir ): string {
	$json = json_decode( (string) file_get_contents( $dir . '/fields.json' ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local theme file.
	return (string) ( $json['key'] ?? '' );
}

add_filter(
	'acf/settings/load_json',
	function ( $paths ) {
		$paths[] = CITCOM_THEME_DIR . '/acf-json';
		return array_merge( $paths, citcom_block_field_dirs() );
	}
);

add_filter( 'acf/settings/save_json', fn() => CITCOM_THEME_DIR . '/acf-json' );

// A group that came from a block folder is saved back to that folder ...
add_filter(
	'acf/json/save_paths',
	function ( $paths, $post ) {
		$key = $post['key'] ?? '';
		if ( ! $key ) {
			return $paths;
		}
		foreach ( citcom_block_field_dirs() as $dir ) {
			if ( citcom_block_field_group_key( $dir ) === $key ) {
				return array( $dir );
			}
		}
		return $paths;
	},
	10,
	2
);

// ... under the name fields.json.
add_filter(
	'acf/json/save_file_name',
	function ( $filename, $post, $load_path ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$key = $post['key'] ?? '';
		foreach ( citcom_block_field_dirs() as $dir ) {
			if ( citcom_block_field_group_key( $dir ) === $key ) {
				return 'fields.json';
			}
		}
		return $filename;
	},
	10,
	3
);

// The theme's own field types.
add_action(
	'acf/include_field_types',
	function () {
		require_once CITCOM_THEME_DIR . '/inc/class-citcom-field-focuspoint.php';
	}
);

// Block editor fixes for the swatch mu-plugin field (see assets/admin/editor-fields.js).
add_action(
	'acf/input/admin_enqueue_scripts',
	function () {
		wp_enqueue_script( 'citcom-editor-fields', CITCOM_THEME_URI . '/assets/admin/editor-fields.js', array( 'acf-input', 'jquery' ), CITCOM_THEME_VERSION, true );
	}
);

// Site Settings options page (was ui_options_page_65eafdab6ac5b.json).
add_action(
	'acf/init',
	function () {
		if ( ! function_exists( 'acf_add_options_page' ) ) {
			return;
		}
		acf_add_options_page(
			array(
				'page_title'      => 'Site Settings',
				'menu_title'      => 'Site Settings',
				'menu_slug'       => 'theme-settings',
				'capability'      => 'edit_posts',
				'position'        => 3,
				'icon_url'        => 'dashicons-admin-customizer',
				'redirect'        => false,
				'update_button'   => 'Update',
				'updated_message' => 'Options Updated',
				'autoload'        => true,
			)
		);
	}
);

// ACF Extended (functions/acf/acf-extended.php). Performance mode is kept as on
// the current site so existing single-meta values stay readable until Phase 3.
add_filter( 'acf/settings/remove_wp_meta_box', '__return_false', 20 );

add_action(
	'acfe/init',
	function () {
		if ( function_exists( 'acfe_update_setting' ) ) {
			acfe_update_setting(
				'modules/performance',
				array(
					'engine' => 'ultra',
					'ui'     => true,
					'mode'   => 'production',
				)
			);
		}
		acf_update_setting( 'acfe/modules/post_types', false );
		acf_update_setting( 'acfe/modules/options_pages', false );
	}
);

// Admin notice when ACF Pro is missing: the theme cannot render blocks without it.
add_action(
	'admin_notices',
	function () {
		if ( function_exists( 'acf_register_block_type' ) ) {
			return;
		}
		echo '<div class="notice notice-error"><p>' . esc_html__( 'Citcom Rebuild needs ACF Pro 6.8 or later. Blocks will not render until it is active.', 'citcom' ) . '</p></div>';
	}
);
