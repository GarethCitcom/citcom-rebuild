<?php
/**
 * Legacy fixture for testing the Phase 3 migration locally.
 *
 * Staging holds the real content in the old theme's ACF flexible content
 * field; this site has no copy of it. This script makes one from the block
 * fixture pages: for each page given, it creates a `legacy-<slug>` copy with
 * an empty post_content and the same sections stored the OLD way, as rows of
 * the flexible content field `blocks` (ACF Extended single `acf` meta row,
 * layout settings in the `layout_settings` clone, editor content in the block
 * editor clone). ACF writes the rows itself, so the storage format is the
 * real one.
 *
 * The old field groups are read from the reference theme's acf-json directory
 * at run time, never copied into this repo; the diner layouts (defined in PHP
 * in the old theme) are rebuilt from this theme's diner blocks, whose field
 * keys are the same.
 *
 * Run from the WordPress root:
 *
 *   wp eval-file wp-content/themes/citcom-rebuild/tools/local-fixture-legacy.php "<reference acf-json dir>" about-us,contact-us
 *
 * Then `wp citcom migrate run --post=<legacy ids>` and compare each
 * /legacy-<slug>/ with /<slug>/ (tools/legacy-compare.mjs).
 *
 * @package citcom
 */

if ( ! defined( 'WP_CLI' ) ) {
	exit;
}

$citcom_ref   = isset( $args[0] ) ? rtrim( (string) $args[0], '/\\' ) : '';
$citcom_slugs = isset( $args[1] ) ? array_filter( array_map( 'trim', explode( ',', (string) $args[1] ) ) ) : array( 'about-us', 'contact-us' );

if ( '' === $citcom_ref || ! file_exists( $citcom_ref . '/group_66f9a66681625.json' ) ) {
	WP_CLI::error( 'First argument: the reference theme\'s acf-json directory (with group_66f9a66681625.json).' );
}

require_once CITCOM_THEME_DIR . '/inc/migrate.php';

/*
 * Helpers.
 */

/**
 * The block's flat data as the nested value ACF's update_field() takes.
 *
 * @param array<int,array> $fields Field definitions.
 * @param array<string,mixed> $data   Block data.
 * @param string              $prefix Prefix at this level.
 * @return array<string,mixed>
 */
function citcom_legacy_nest( array $fields, array $data, string $prefix = '' ): array {
	$value = array();
	foreach ( $fields as $field ) {
		$name = (string) ( $field['name'] ?? '' );
		$type = (string) ( $field['type'] ?? '' );
		if ( '' === $name || in_array( $type, array( 'tab', 'message', 'accordion', 'acfe_column' ), true ) ) {
			continue;
		}
		$key = $prefix . $name;
		if ( 'repeater' === $type ) {
			if ( ! isset( $data[ $key ] ) ) {
				continue;
			}
			$rows = array();
			for ( $i = 0; $i < (int) $data[ $key ]; $i++ ) {
				$rows[] = citcom_legacy_nest( $field['sub_fields'] ?? array(), $data, $key . '_' . $i . '_' );
			}
			$value[ $name ] = $rows;
			continue;
		}
		if ( 'group' === $type ) {
			if ( ! array_key_exists( $key, $data ) ) {
				continue;
			}
			$value[ $name ] = citcom_legacy_nest( $field['sub_fields'] ?? array(), $data, $key . '_' );
			continue;
		}
		if ( array_key_exists( $key, $data ) ) {
			$value[ $name ] = $data[ $key ];
		}
	}
	return $value;
}

/**
 * A parsed citcom/* block as an old flexible content row.
 *
 * @param array<string,mixed> $block From parse_blocks().
 * @return array<string,mixed>|null
 */
function citcom_legacy_row( array $block ): ?array {
	$layout = array_search( $block['blockName'], CITCOM_MIGRATE_LAYOUTS, true );
	if ( false === $layout ) {
		return null;
	}
	$data = (array) ( $block['attrs']['data'] ?? array() );
	$row  = array( 'acf_fc_layout' => $layout );
	foreach ( citcom_migrate_block_groups( (string) $block['blockName'] ) as $group ) {
		$nested = citcom_legacy_nest( $group['fields'], $data );
		if ( '' !== $group['source'] ) {
			$row['layout_settings'] = $nested;
		} else {
			$row = array_merge( $row, $nested );
		}
	}
	if ( 'google_reviews' === $layout ) {
		unset( $row['trustindex_code'] );
	}
	if ( isset( $row['form'] ) && '' !== $row['form'] ) {
		// The old post_object held the Forminator form's post id.
		$form        = citcom_form( (string) $row['form'] );
		$row['form'] = $form && ! empty( $form['legacy_ids'] ) ? (string) $form['legacy_ids'][0] : '';
	}
	if ( in_array( $layout, CITCOM_MIGRATE_INNER_LAYOUTS, true ) ) {
		$row['content'] = trim( serialize_blocks( $block['innerBlocks'] ?? array() ) );
	}
	if ( ! empty( $block['attrs']['metadata']['name'] ) ) {
		$row['acfe_flexible_layout_title'] = (string) $block['attrs']['metadata']['name'];
	}
	return $row;
}

/*
 * 1. Build the rows from the block fixture pages, while ACF still holds the
 *    blocks' own field definitions (see step 2).
 */
$citcom_sources = array();
foreach ( $citcom_slugs as $citcom_slug ) {
	$citcom_source = null;
	foreach ( CITCOM_MIGRATE_POST_TYPES as $citcom_type ) {
		$citcom_found = get_posts(
			array(
				'post_type'      => $citcom_type,
				'name'           => $citcom_slug,
				'post_status'    => 'any',
				'posts_per_page' => 1,
			)
		);
		if ( $citcom_found ) {
			$citcom_source = $citcom_found[0];
			break;
		}
	}
	if ( ! $citcom_source ) {
		WP_CLI::warning( "No post with slug $citcom_slug." );
		continue;
	}

	$citcom_rows = array();
	foreach ( parse_blocks( $citcom_source->post_content ) as $citcom_parsed ) {
		if ( empty( $citcom_parsed['blockName'] ) ) {
			continue;
		}
		$citcom_row = citcom_legacy_row( $citcom_parsed );
		if ( $citcom_row ) {
			$citcom_rows[] = $citcom_row;
		} else {
			WP_CLI::warning( "$citcom_slug: block {$citcom_parsed['blockName']} has no old layout, skipped." );
		}
	}
	$citcom_sources[] = array(
		'slug'   => $citcom_slug,
		'source' => $citcom_source,
		'rows'   => $citcom_rows,
	);
}

/*
 * 2. Register the old field groups: the flexible content group with the diner
 *    layouts appended, and the block editor group the editor layouts clone.
 *    The old sub-fields share their keys with the blocks' fields, and ACF
 *    resolves sub-fields by key, so the blocks' field groups are taken out of
 *    ACF's local store first (for the rest of this run only). The rows were
 *    built above while they were still there.
 */
$citcom_flex   = json_decode( (string) file_get_contents( $citcom_ref . '/group_66f9a66681625.json' ), true );
$citcom_editor = json_decode( (string) file_get_contents( $citcom_ref . '/group_66042a32b1be4.json' ), true );
if ( ! is_array( $citcom_flex ) || ! is_array( $citcom_editor ) ) {
	WP_CLI::error( 'Could not read the reference field groups.' );
}

/**
 * Take a field and its sub-fields out of ACF's local store.
 *
 * @param array<string,mixed> $field Field.
 * @return void
 */
function citcom_legacy_forget_field( array $field ): void {
	foreach ( $field['sub_fields'] ?? array() as $sub_field ) {
		citcom_legacy_forget_field( $sub_field );
	}
	acf_remove_local_field( $field['key'] );
}

$citcom_diner_layouts = array();
foreach ( CITCOM_MIGRATE_LAYOUTS as $citcom_layout => $citcom_block ) {
	$citcom_group = acf_get_field_group( 'group_citcom_' . $citcom_layout );
	if ( ! $citcom_group ) {
		continue;
	}
	$citcom_fields = acf_get_fields( $citcom_group ) ?: array();
	if ( str_starts_with( $citcom_layout, 'diner_' ) ) {
		$citcom_diner_layouts[ 'layout_' . $citcom_layout ] = array(
			'key'                         => 'layout_' . $citcom_layout,
			'name'                        => $citcom_layout,
			'label'                       => $citcom_group['title'],
			'display'                     => 'block',
			'sub_fields'                  => $citcom_fields,
			'min'                         => '',
			'max'                         => '',
			'acfe_flexible_settings'      => array( 'group_65f8774c86a9d' ),
			'acfe_flexible_settings_size' => 'large',
		);
	}
	foreach ( $citcom_fields as $citcom_field ) {
		citcom_legacy_forget_field( $citcom_field );
	}
	acf_remove_local_field_group( $citcom_group['key'] );
}
acf_get_store( 'fields' )->reset();

foreach ( $citcom_flex['fields'] as &$citcom_field ) {
	if ( 'flexible_content' === $citcom_field['type'] ) {
		$citcom_field['layouts'] = array_merge( $citcom_field['layouts'], $citcom_diner_layouts );
	}
}
unset( $citcom_field );

$citcom_editor['location'] = array();
acf_add_local_field_group( $citcom_editor );
acf_add_local_field_group( $citcom_flex );

/*
 * 3. Write the legacy copies.
 */
$citcom_made = array();
foreach ( $citcom_sources as $citcom_item ) {
	$citcom_slug   = $citcom_item['slug'];
	$citcom_source = $citcom_item['source'];
	$citcom_rows   = $citcom_item['rows'];

	$citcom_legacy_slug = 'legacy-' . $citcom_slug;
	$citcom_existing    = get_posts(
		array(
			'post_type'      => $citcom_source->post_type,
			'name'           => $citcom_legacy_slug,
			'post_status'    => 'any',
			'posts_per_page' => 1,
		)
	);
	$citcom_args        = array(
		'post_type'    => $citcom_source->post_type,
		'post_title'   => $citcom_source->post_title,
		'post_name'    => $citcom_legacy_slug,
		'post_status'  => 'publish',
		'post_parent'  => $citcom_source->post_parent,
		'post_content' => '',
	);
	if ( $citcom_existing ) {
		$citcom_args['ID'] = $citcom_existing[0]->ID;
		$citcom_id         = (int) wp_update_post( $citcom_args );
	} else {
		$citcom_id = (int) wp_insert_post( $citcom_args );
	}
	// Start clean, so a re-run does not keep rows from a previous shape.
	delete_post_meta( $citcom_id, 'acf' );
	delete_post_meta( $citcom_id, '_citcom_migrated' );
	delete_post_meta( $citcom_id, '_citcom_legacy_content' );
	update_field( 'blocks', $citcom_rows, $citcom_id );

	$citcom_stored = get_post_meta( $citcom_id, 'acf', true );
	$citcom_made[] = array(
		'source'    => $citcom_source->ID,
		'legacy'    => $citcom_id,
		'slug'      => $citcom_legacy_slug,
		'rows'      => count( $citcom_rows ),
		'meta_keys' => is_array( $citcom_stored ) ? count( $citcom_stored ) : 0,
	);
}

WP_CLI\Utils\format_items( 'table', $citcom_made, array( 'source', 'legacy', 'slug', 'rows', 'meta_keys' ) );
WP_CLI::success( 'Legacy copies written. Next: wp citcom migrate run --post=' . implode( ',', array_column( $citcom_made, 'legacy' ) ) );
