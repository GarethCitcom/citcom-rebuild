<?php
/**
 * Phase 3 content migration: ACF flexible content rows become citcom/* blocks.
 *
 * Loaded under WP-CLI only (functions.php); the command is inc/cli-migrate.php
 * and the plan is docs/03-phase3-brief.md. Everything here is a plain function
 * so each step can be tried with `wp eval` and tested against the legacy
 * fixture (tools/local-fixture-legacy.php).
 *
 * Source: the raw ACF values of a post, read from the ACF Extended single
 * `acf` meta row (or from individual postmeta rows when a post has none).
 * A flexible content row is stored flat, as `blocks_{i}_{field}`; the ACF
 * Extended layout settings modal adds `blocks_{i}_layout_settings_{field}`.
 *
 * Target: one serialised block per row. The block's own field group and the
 * Section settings group(s) attached to it (through their block location
 * rules) decide which values are copied, under the field names and keys of
 * the new theme, so the block comment holds exactly what the editor would
 * have stored: flattened repeaters and groups, `_name` key twins, focal
 * points as arrays, and no fields hidden by conditional logic.
 *
 * @package citcom
 */

defined( 'ABSPATH' ) || exit;

// The flexible content field, and its layouts => blocks.
const CITCOM_MIGRATE_FLEX_FIELD = 'blocks';
const CITCOM_MIGRATE_LAYOUTS    = array(
	'page_header'       => 'citcom/page-header',
	'media_text'        => 'citcom/media-text',
	'video'             => 'citcom/video',
	'swiper'            => 'citcom/swiper',
	'services_showcase' => 'citcom/services-showcase',
	'editor'            => 'citcom/editor',
	'google_reviews'    => 'citcom/trustindex',
	'display_posts'     => 'citcom/display-posts',
	'cta'               => 'citcom/cta',
	'quote'             => 'citcom/quote',
	'stats'             => 'citcom/stats',
	'citdot_cards'      => 'citcom/citdot-cards',
	'sub_services'      => 'citcom/sub-services',
	'contact_map'       => 'citcom/contact-map',
	'gallery'           => 'citcom/gallery',
	'icons'             => 'citcom/icons',
	'shortcode'         => 'citcom/shortcode',
	'diner_hero'        => 'citcom/diner-hero',
	'diner_intro'       => 'citcom/diner-intro',
	'diner_menu'        => 'citcom/diner-menu',
	'diner_story'       => 'citcom/diner-story',
	'diner_story_light' => 'citcom/diner-story-light',
	'diner_wall'        => 'citcom/diner-wall',
	'diner_reviews'     => 'citcom/diner-reviews',
	'diner_guestcheck'  => 'citcom/diner-guestcheck',
);

// Post types that carried the flexible content field.
const CITCOM_MIGRATE_POST_TYPES = array( 'page', 'service', 'case-study', 'landing-page', 'template' );

// The field groups the layout settings modal cloned (full, and simple for cta).
const CITCOM_MIGRATE_SETTINGS_GROUPS = array( 'group_65f8774c86a9d', 'group_6790f33456b40' );

// Layouts whose block editor clone becomes the block's inner blocks.
const CITCOM_MIGRATE_INNER_LAYOUTS = array( 'editor', 'media_text' );

// Pages retired in the review (docs/pages-and-forms-review.xlsx): id => slug.
const CITCOM_MIGRATE_RETIRED = array(
	218753 => 'comp',
	219133 => 'marketing-agreement',
	218516 => 'suite',
	219465 => 'test-about-us-updates',
	218718 => 'citcom-autumn-event',
	219758 => 'ihd-ojdiudsa',
	219260 => 'junior-developer-quiz',
	218743 => 'thank-you-rsvp',
	219935 => 'wc2026',
);

// Old sidebar block names => new.
const CITCOM_MIGRATE_WIDGET_BLOCKS = array( 'latest-posts', 'newsletter-signup', 'posts-search', 'related-posts', 'top-blog-posts' );

/**
 * Every raw ACF value of a post, name => value.
 *
 * ACF Extended's "ultra" performance mode keeps them in one `acf` meta row;
 * a post without that row (saved before the mode was on) has them as
 * individual postmeta rows.
 *
 * @param int $post_id Post id.
 * @return array<string,mixed>
 */
function citcom_migrate_meta( int $post_id ): array {
	$acf = get_post_meta( $post_id, 'acf', true );
	if ( is_array( $acf ) && array_key_exists( CITCOM_MIGRATE_FLEX_FIELD, $acf ) ) {
		return $acf;
	}
	$meta = array();
	foreach ( (array) get_post_meta( $post_id ) as $key => $values ) {
		if ( 'acf' === $key || ! is_array( $values ) || ! array_key_exists( 0, $values ) ) {
			continue;
		}
		$meta[ $key ] = maybe_unserialize( $values[0] );
	}
	return $meta;
}


/**
 * The flexible content rows of a post, from its raw values.
 *
 * Each row: index, layout, disabled (ACF 6.5 layout meta or the older ACF
 * Extended toggle), title (a renamed layout) and values (the row's own
 * values with the `blocks_{i}_` prefix removed).
 *
 * @param array<string,mixed> $meta From citcom_migrate_meta().
 * @return array<int,array<string,mixed>>
 */
function citcom_migrate_rows( array $meta ): array {
	$layouts = $meta[ CITCOM_MIGRATE_FLEX_FIELD ] ?? null;
	if ( ! is_array( $layouts ) ) {
		return array();
	}

	$layout_meta = $meta[ '_' . CITCOM_MIGRATE_FLEX_FIELD . '_layout_meta' ] ?? array();
	$disabled    = is_array( $layout_meta ) && ! empty( $layout_meta['disabled'] ) ? array_map( 'intval', (array) $layout_meta['disabled'] ) : array();
	$renamed     = is_array( $layout_meta ) && ! empty( $layout_meta['renamed'] ) ? (array) $layout_meta['renamed'] : array();

	$rows = array();
	foreach ( array_values( $layouts ) as $i => $layout ) {
		$prefix = CITCOM_MIGRATE_FLEX_FIELD . '_' . $i . '_';
		$values = array();
		foreach ( $meta as $key => $value ) {
			if ( str_starts_with( (string) $key, $prefix ) ) {
				$values[ substr( (string) $key, strlen( $prefix ) ) ] = $value;
			}
		}
		$rows[] = array(
			'index'    => $i,
			'layout'   => (string) $layout,
			'disabled' => in_array( $i, $disabled, true ) || ! empty( $values['acfe_flexible_toggle'] ),
			'title'    => (string) ( $renamed[ $i ] ?? $values['acfe_flexible_layout_title'] ?? '' ),
			'values'   => $values,
		);
	}
	return $rows;
}

/**
 * The field groups a block reads: its own, and the Section settings group(s)
 * located on it. Settings values come from the old row's `layout_settings_`
 * clone, the block's own from the row itself.
 *
 * @param string $block Block name.
 * @return array<int,array{fields:array,source:string}>
 */
function citcom_migrate_block_groups( string $block ): array {
	static $cache = array();
	if ( isset( $cache[ $block ] ) ) {
		return $cache[ $block ];
	}
	$groups = array();
	foreach ( acf_get_field_groups( array( 'block' => $block ) ) as $group ) {
		$groups[] = array(
			'fields' => acf_get_fields( $group ) ?: array(),
			'source' => in_array( $group['key'], CITCOM_MIGRATE_SETTINGS_GROUPS, true ) ? 'layout_settings_' : '',
		);
	}
	$cache[ $block ] = $groups;
	return $groups;
}

/**
 * Whether a field's conditional logic is met by the sibling values, as the
 * editor evaluates it. Hidden fields are not stored.
 *
 * @param array<string,mixed> $field  Field.
 * @param array<string,array> $by_key Sibling fields by key.
 * @param array<string,mixed> $values Raw row values.
 * @param string              $source Prefix of this level's values.
 * @return bool
 */
function citcom_migrate_visible( array $field, array $by_key, array $values, string $source ): bool {
	$logic = $field['conditional_logic'] ?? 0;
	if ( empty( $logic ) || ! is_array( $logic ) ) {
		return true;
	}
	foreach ( $logic as $group ) {
		$met = true;
		foreach ( (array) $group as $rule ) {
			$target = $by_key[ $rule['field'] ?? '' ] ?? null;
			if ( ! $target ) {
				// A rule on a field outside this level: ACF would hide nothing it cannot see.
				continue;
			}
			$value = $values[ $source . $target['name'] ] ?? null;
			if ( ! citcom_migrate_rule( (string) ( $rule['operator'] ?? '==' ), (string) ( $rule['value'] ?? '' ), $value ) ) {
				$met = false;
				break;
			}
		}
		if ( $met ) {
			return true;
		}
	}
	return false;
}

/**
 * One conditional logic rule.
 *
 * @param string $operator Rule operator.
 * @param string $expected Rule value.
 * @param mixed  $value    Stored value.
 * @return bool
 */
function citcom_migrate_rule( string $operator, string $expected, $value ): bool {
	$is_empty = null === $value || '' === $value || array() === $value || '0' === $value || 0 === $value;
	$matches  = is_array( $value ) ? in_array( $expected, array_map( 'strval', $value ), true ) : (string) $value === $expected;
	switch ( $operator ) {
		case '==':
			return $matches;
		case '!=':
			return ! $matches;
		case '==empty':
			return $is_empty;
		case '!=empty':
			return ! $is_empty;
		case '==contains':
			return is_array( $value ) ? $matches : false !== strpos( (string) $value, $expected );
		case '!=contains':
			return is_array( $value ) ? ! $matches : false === strpos( (string) $value, $expected );
		case '==pattern':
			return (bool) preg_match( '/' . str_replace( '/', '\/', $expected ) . '/', (string) ( is_array( $value ) ? implode( ',', $value ) : $value ) );
		case '>':
			return is_numeric( $value ) && (float) $value > (float) $expected;
		case '<':
			return is_numeric( $value ) && (float) $value < (float) $expected;
	}
	return true;
}

/**
 * Whether a stored value is empty: nothing, an empty string, 0, or an array
 * with nothing in it (an empty focal point, a group of empty fields).
 *
 * @param mixed $value Value.
 * @return bool
 */
function citcom_migrate_empty( $value ): bool {
	if ( is_array( $value ) ) {
		foreach ( $value as $item ) {
			if ( ! citcom_migrate_empty( $item ) ) {
				return false;
			}
		}
		return true;
	}
	return null === $value || '' === $value || '0' === $value || 0 === $value || false === $value;
}

/**
 * Copy the values of a list of fields from a row into block data, flattened
 * the way ACF blocks store them.
 *
 * Fields hidden by conditional logic are copied only when they hold a value.
 * The old editor kept a hidden sub-field's last value in the row and the old
 * templates read some of them regardless (a section pattern switched on, then
 * the background set back to default, still printed the pattern), so dropping
 * them would change pages. An empty hidden field is left out, as the block
 * editor would: ACF validates every key present, and an empty required one
 * would stop the page saving.
 *
 * @param array<int,array>    $fields Field definitions (with sub_fields).
 * @param array<string,mixed> $values Raw row values.
 * @param string              $source Prefix of the values at this level.
 * @param string              $target Prefix of the data keys at this level.
 * @param array<string,mixed> $data   Block data, by reference.
 * @return void
 */
function citcom_migrate_values( array $fields, array $values, string $source, string $target, array &$data ): void {
	$by_key = array();
	foreach ( $fields as $field ) {
		if ( ! empty( $field['key'] ) ) {
			$by_key[ $field['key'] ] = $field;
		}
	}
	foreach ( $fields as $field ) {
		$name = (string) ( $field['name'] ?? '' );
		$type = (string) ( $field['type'] ?? '' );
		if ( '' === $name || in_array( $type, array( 'tab', 'message', 'accordion', 'acfe_column' ), true ) ) {
			continue;
		}
		$src  = $source . $name;
		$dst  = $target . $name;
		$part = array();
		if ( 'repeater' === $type ) {
			if ( ! array_key_exists( $src, $values ) ) {
				continue;
			}
			$count              = is_array( $values[ $src ] ) ? count( $values[ $src ] ) : (int) $values[ $src ];
			$part[ $dst ]       = (string) $count;
			$part[ '_' . $dst ] = $field['key'];
			$sub_fields         = $field['sub_fields'] ?? array();
			for ( $i = 0; $i < $count; $i++ ) {
				citcom_migrate_values( $sub_fields, $values, $src . '_' . $i . '_', $dst . '_' . $i . '_', $part );
			}
		} elseif ( 'group' === $type ) {
			$part[ $dst ]       = '';
			$part[ '_' . $dst ] = $field['key'];
			citcom_migrate_values( $field['sub_fields'] ?? array(), $values, $src . '_', $dst . '_', $part );
		} else {
			if ( ! array_key_exists( $src, $values ) ) {
				continue;
			}
			$value = $values[ $src ];
			if ( 'citcom_focuspoint' === $type ) {
				$value = is_array( $value ) ? array(
					'id'   => $value['id'] ?? '',
					'top'  => $value['top'] ?? '',
					'left' => $value['left'] ?? '',
				) : array(
					'id'   => '',
					'top'  => '',
					'left' => '',
				);
			}
			$part[ $dst ]       = $value;
			$part[ '_' . $dst ] = $field['key'];
		}

		if ( ! citcom_migrate_visible( $field, $by_key, $values, $source ) ) {
			// Hidden: keep only when something is in it (the leaves, not the container keys).
			$leaves = array_filter(
				$part,
				static function ( $value, $key ) use ( $dst ): bool {
					return $key !== $dst && ! str_starts_with( (string) $key, '_' ) && ! citcom_migrate_empty( $value );
				},
				ARRAY_FILTER_USE_BOTH
			);
			if ( ! $leaves && ( 'repeater' === $type || 'group' === $type || citcom_migrate_empty( $part[ $dst ] ) ) ) {
				continue;
			}
		}
		foreach ( $part as $key => $value ) {
			$data[ $key ] = $value;
		}
	}
}

/**
 * Inner blocks from an old block editor (acfe_block_editor) value. The field
 * stored block markup; the rare classic HTML is converted (inc/blocks-convert.php).
 *
 * @param string   $content  Stored value.
 * @param string[] $warnings Warnings, by reference.
 * @return string
 */
function citcom_migrate_inner( string $content, array &$warnings ): string {
	$content = trim( $content );
	if ( '' === $content ) {
		return '';
	}
	if ( has_blocks( $content ) ) {
		return $content;
	}
	$warnings[] = 'note: classic HTML in an editor field converted to blocks';
	return citcom_html_to_blocks( wpautop( $content ) );
}

/**
 * One row as a serialised block, or null when the layout has no block.
 *
 * @param array<string,mixed> $row      From citcom_migrate_rows().
 * @param string[]            $warnings Warnings, by reference.
 * @return string|null
 */
function citcom_migrate_row_block( array $row, array &$warnings ): ?string {
	$layout = (string) $row['layout'];
	$block  = CITCOM_MIGRATE_LAYOUTS[ $layout ] ?? null;
	if ( ! $block ) {
		$warnings[] = sprintf( 'row %d: no block for layout "%s", dropped', $row['index'], $layout );
		return null;
	}
	$values = (array) $row['values'];
	$data   = array();
	foreach ( citcom_migrate_block_groups( $block ) as $group ) {
		citcom_migrate_values( $group['fields'], $values, $group['source'], '', $data );
	}

	// Layout-specific changes.
	if ( 'google_reviews' === $layout ) {
		// Elfsight is gone: the row becomes the default Trustindex widget.
		$data['trustindex_code']  = citcom_trustindex_default();
		$data['_trustindex_code'] = 'field_citcom_trustindex_code';
	}
	if ( isset( $data['form'] ) && '' !== $data['form'] && ! is_array( $data['form'] ) ) {
		// Forminator form post id => theme form slug.
		$form = citcom_form( $data['form'] );
		if ( $form ) {
			$data['form'] = $form['slug'];
		} else {
			$warnings[] = sprintf( 'row %d: form %s has no theme form', $row['index'], (string) $data['form'] );
		}
	}
	if ( 'shortcode' === $layout ) {
		// Listed so the staging report shows which shortcodes the content relies on.
		$warnings[] = sprintf( 'note: row %d: shortcode %s', $row['index'], trim( (string) ( $values['shortcode'] ?? '' ) ) );
	}
	if ( 'swiper' === $layout && 'images' !== (string) ( $values['swiper_type'] ?? 'images' ) ) {
		$warnings[] = sprintf( 'row %d: swiper type "%s" was never built, slides dropped', $row['index'], (string) $values['swiper_type'] );
	}

	$attrs = array(
		'name' => $block,
		'data' => $data,
		'mode' => 'preview',
	);
	if ( '' !== $row['title'] ) {
		$attrs['metadata'] = array( 'name' => $row['title'] );
	}

	if ( in_array( $layout, CITCOM_MIGRATE_INNER_LAYOUTS, true ) ) {
		$inner = citcom_migrate_inner( (string) ( $values['content'] ?? '' ), $warnings );
		return '<!-- wp:' . $block . ' ' . serialize_block_attributes( $attrs ) . ' -->' . ( '' !== $inner ? "\n" . $inner . "\n" : '' ) . '<!-- /wp:' . $block . ' -->';
	}
	return '<!-- wp:' . $block . ' ' . serialize_block_attributes( $attrs ) . ' /-->';
}

/**
 * Block content for a post from its flexible content rows, with a report.
 *
 * @param int $post_id Post id.
 * @return array{content:string,rows:int,converted:int,disabled:int,warnings:string[]}
 */
function citcom_migrate_build( int $post_id ): array {
	$rows     = citcom_migrate_rows( citcom_migrate_meta( $post_id ) );
	$blocks   = array();
	$disabled = 0;
	$warnings = array();
	foreach ( $rows as $row ) {
		if ( $row['disabled'] ) {
			++$disabled;
			continue;
		}
		$block = citcom_migrate_row_block( $row, $warnings );
		if ( null !== $block ) {
			$blocks[] = $block;
		}
	}
	return array(
		'content'   => implode( "\n\n", $blocks ),
		'rows'      => count( $rows ),
		'converted' => count( $blocks ),
		'disabled'  => $disabled,
		'warnings'  => $warnings,
	);
}

/**
 * Migrate one post: build the block content and store it, keeping the old
 * post_content in `_citcom_legacy_content` for a rollback. The raw ACF values
 * are left alone until the cleanup step.
 *
 * @param int  $post_id Post id.
 * @param bool $dry_run Build only.
 * @return array<string,mixed> Report row.
 */
function citcom_migrate_post( int $post_id, bool $dry_run = true ): array {
	$post   = get_post( $post_id );
	$report = array(
		'id'        => $post_id,
		'type'      => $post ? $post->post_type : '',
		'title'     => $post ? $post->post_title : '',
		'rows'      => 0,
		'converted' => 0,
		'disabled'  => 0,
		'status'    => '',
		'warnings'  => array(),
		'content'   => '',
	);
	if ( ! $post ) {
		$report['status']     = 'missing';
		$report['warnings'][] = 'post not found';
		return $report;
	}
	if ( isset( CITCOM_MIGRATE_RETIRED[ $post_id ] ) ) {
		$report['status'] = 'retired';
		return $report;
	}

	$built  = citcom_migrate_build( $post_id );
	$report = array_merge( $report, $built );
	if ( 0 === $built['rows'] ) {
		$report['status'] = 'no rows';
		return $report;
	}
	if ( $dry_run ) {
		$report['status'] = 'dry run';
		return $report;
	}

	if ( ! metadata_exists( 'post', $post_id, '_citcom_legacy_content' ) ) {
		add_post_meta( $post_id, '_citcom_legacy_content', wp_slash( $post->post_content ), true );
	}
	$result = wp_update_post(
		array(
			'ID'           => $post_id,
			'post_content' => wp_slash( $built['content'] ),
		),
		true
	);
	if ( is_wp_error( $result ) ) {
		$report['status']     = 'error';
		$report['warnings'][] = $result->get_error_message();
		return $report;
	}
	update_post_meta( $post_id, '_citcom_migrated', current_time( 'mysql', true ) );

	// A page template from the old theme has no file here; WordPress would fall back anyway.
	$template = (string) get_post_meta( $post_id, '_wp_page_template', true );
	if ( '' !== $template && 'default' !== $template && ! isset( wp_get_theme()->get_page_templates( $post, $post->post_type )[ $template ] ) ) {
		delete_post_meta( $post_id, '_wp_page_template' );
		$report['warnings'][] = sprintf( 'page template "%s" reset to default', $template );
	}
	$report['status'] = 'migrated';
	return $report;
}

/**
 * Posts with flexible content rows (and the retired ones, reported as such).
 *
 * @param string[] $types Post types.
 * @return int[]
 */
function citcom_migrate_candidates( array $types = CITCOM_MIGRATE_POST_TYPES ): array {
	$ids = get_posts(
		array(
			'post_type'              => $types,
			'post_status'            => array( 'publish', 'draft', 'private', 'pending', 'future' ),
			'posts_per_page'         => -1, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page -- a one-off migration.
			'fields'                 => 'ids',
			'orderby'                => 'ID',
			'order'                  => 'ASC',
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
		)
	);
	return array_values(
		array_filter(
			array_map( 'intval', $ids ),
			static function ( int $id ): bool {
				return isset( CITCOM_MIGRATE_RETIRED[ $id ] ) || ! empty( citcom_migrate_rows( citcom_migrate_meta( $id ) ) );
			}
		)
	);
}

/**
 * Restore a migrated post's old post_content.
 *
 * @param int $post_id Post id.
 * @return string Status.
 */
function citcom_migrate_rollback_post( int $post_id ): string {
	if ( ! metadata_exists( 'post', $post_id, '_citcom_legacy_content' ) ) {
		return 'not migrated';
	}
	$legacy = (string) get_post_meta( $post_id, '_citcom_legacy_content', true );
	$result = wp_update_post(
		array(
			'ID'           => $post_id,
			'post_content' => wp_slash( $legacy ),
		),
		true
	);
	if ( is_wp_error( $result ) ) {
		return 'error: ' . $result->get_error_message();
	}
	delete_post_meta( $post_id, '_citcom_legacy_content' );
	delete_post_meta( $post_id, '_citcom_migrated' );
	return 'restored';
}

/**
 * Sidebar widgets: the old acf/* sidebar blocks are citcom/* now.
 *
 * @param bool $dry_run Report only.
 * @return array{widgets:int,changed:int}
 */
function citcom_migrate_widgets( bool $dry_run = true ): array {
	$option  = get_option( 'widget_block', array() );
	$changed = 0;
	$total   = 0;
	if ( is_array( $option ) ) {
		foreach ( $option as $i => $widget ) {
			if ( ! is_array( $widget ) || ! isset( $widget['content'] ) ) {
				continue;
			}
			++$total;
			$content = (string) $widget['content'];
			foreach ( CITCOM_MIGRATE_WIDGET_BLOCKS as $slug ) {
				$content = str_replace(
					array( '<!-- wp:acf/' . $slug . ' ', '"name":"acf/' . $slug . '"' ),
					array( '<!-- wp:citcom/' . $slug . ' ', '"name":"citcom/' . $slug . '"' ),
					$content
				);
			}
			if ( $content !== $widget['content'] ) {
				++$changed;
				$option[ $i ]['content'] = $content;
			}
		}
		if ( $changed && ! $dry_run ) {
			update_option( 'widget_block', $option );
		}
	}
	return array(
		'widgets' => $total,
		'changed' => $changed,
	);
}

/**
 * ACF UI entries the theme now provides in PHP or JSON: the post types,
 * taxonomy and options page registered in inc/cpt.php and inc/acf.php, and
 * the old field groups that have no JSON in this theme (the flexible content
 * group and the block editor clone). They are deactivated, not deleted.
 *
 * @param bool $dry_run Report only.
 * @return array<int,array{id:int,type:string,title:string,key:string,action:string}>
 */
function citcom_migrate_acf_ui( bool $dry_run = true ): array {
	$rows  = array();
	$done  = array();
	$posts = get_posts(
		array(
			'post_type'      => array( 'acf-post-type', 'acf-taxonomy', 'acf-ui-options-page', 'acf-field-group' ),
			'post_status'    => array( 'publish', 'acf-disabled' ),
			'posts_per_page' => -1, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page -- a one-off migration.
			'orderby'        => 'ID',
			'order'          => 'ASC',
		)
	);
	foreach ( $posts as $post ) {
		$key    = (string) $post->post_name;
		$action = 'deactivate';
		if ( 'acf-field-group' === $post->post_type && acf_get_local_field_group( $key ) ) {
			$action = 'keep (theme JSON)';
		} elseif ( 'acf-disabled' === $post->post_status ) {
			$action = 'already inactive';
		}
		if ( 'deactivate' === $action && ! $dry_run ) {
			wp_update_post(
				array(
					'ID'          => $post->ID,
					'post_status' => 'acf-disabled',
				)
			);
			$done[] = $post->ID;
		}
		$rows[] = array(
			'id'     => $post->ID,
			'type'   => $post->post_type,
			'title'  => $post->post_title,
			'key'    => $key,
			'action' => $action,
		);
	}
	if ( ! empty( $done ) ) {
		// Remembered for citcom_migrate_acf_ui_undo().
		update_option( 'citcom_migrate_acf_ui', array_values( array_unique( array_merge( (array) get_option( 'citcom_migrate_acf_ui', array() ), $done ) ) ), false );
	}
	return $rows;
}

/**
 * Reactivate what citcom_migrate_acf_ui() deactivated (for a rollback to the
 * old theme).
 *
 * @return int[] Ids reactivated.
 */
function citcom_migrate_acf_ui_undo(): array {
	$ids = array_map( 'intval', (array) get_option( 'citcom_migrate_acf_ui', array() ) );
	foreach ( $ids as $id ) {
		wp_update_post(
			array(
				'ID'          => $id,
				'post_status' => 'publish',
			)
		);
	}
	delete_option( 'citcom_migrate_acf_ui' );
	return $ids;
}

/**
 * Bin the retired pages. Each id must still carry the expected slug.
 *
 * @param bool $dry_run Report only.
 * @return array<int,array{id:int,slug:string,action:string}>
 */
function citcom_migrate_retire( bool $dry_run = true ): array {
	$rows = array();
	foreach ( CITCOM_MIGRATE_RETIRED as $id => $slug ) {
		$post   = get_post( $id );
		$action = 'bin';
		if ( ! $post ) {
			$action = 'not found';
		} elseif ( $post->post_name !== $slug ) {
			$action = 'skipped: slug is "' . $post->post_name . '"';
		} elseif ( 'trash' === $post->post_status ) {
			$action = 'already in the bin';
		} elseif ( ! $dry_run ) {
			$action = wp_trash_post( $id ) ? 'binned' : 'error';
		}
		$rows[] = array(
			'id'     => $id,
			'slug'   => $slug,
			'action' => $action,
		);
	}
	return $rows;
}

/**
 * After sign-off: drop the old theme's caches and the raw flexible content
 * values, and the rollback copies.
 *
 * @param bool $dry_run Report only.
 * @return array{options:int,posts:int,legacy_copies:int}
 */
function citcom_migrate_cleanup( bool $dry_run = true ): array {
	global $wpdb;
	$options = (array) $wpdb->get_col( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'acfAllObjects\_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- no API lists options by prefix.
	$posts   = 0;
	foreach ( citcom_migrate_candidates() as $post_id ) {
		if ( isset( CITCOM_MIGRATE_RETIRED[ $post_id ] ) ) {
			continue;
		}
		$acf = get_post_meta( $post_id, 'acf', true );
		if ( ! is_array( $acf ) ) {
			continue;
		}
		$kept = array_filter(
			$acf,
			static function ( $key ): bool {
				return ! preg_match( '/^_?' . CITCOM_MIGRATE_FLEX_FIELD . '(_|$)/', (string) $key );
			},
			ARRAY_FILTER_USE_KEY
		);
		if ( count( $kept ) !== count( $acf ) ) {
			++$posts;
			if ( ! $dry_run ) {
				update_post_meta( $post_id, 'acf', $kept );
			}
		}
	}
	$legacy = (array) $wpdb->get_col( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_citcom_legacy_content'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- a count across posts.
	if ( ! $dry_run ) {
		foreach ( $options as $name ) {
			delete_option( $name );
		}
		delete_post_meta_by_key( '_citcom_legacy_content' );
	}
	return array(
		'options'       => count( $options ),
		'posts'         => $posts,
		'legacy_copies' => count( $legacy ),
	);
}
