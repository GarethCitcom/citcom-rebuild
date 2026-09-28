<?php
/**
 * Block registration, the citcom block category, the editor allow-list and the
 * shared section helpers every citcom/* block uses.
 *
 * @package citcom
 */

defined( 'ABSPATH' ) || exit;

/**
 * Names of every citcom block shipped in blocks/.
 *
 * @return string[]
 */
function citcom_block_names(): array {
	static $names = null;
	if ( null === $names ) {
		$names = array();
		foreach ( glob( CITCOM_THEME_DIR . '/blocks/*/block.json' ) ?: array() as $block_json ) {
			$meta = json_decode( (string) file_get_contents( $block_json ), true ); // phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown
			if ( ! empty( $meta['name'] ) ) {
				$names[] = $meta['name'];
			}
		}
	}
	return $names;
}

add_action(
	'init',
	function () {
		if ( ! function_exists( 'acf_register_block_type' ) ) {
			return; // ACF Pro missing: nothing to register.
		}
		foreach ( glob( CITCOM_THEME_DIR . '/blocks/*/block.json' ) ?: array() as $block_json ) {
			register_block_type( dirname( $block_json ) );
		}
	}
);

add_filter(
	'block_categories_all',
	function ( $categories ) {
		array_unshift(
			$categories,
			array(
				'slug'  => 'citcom',
				'title' => __( 'Citcom', 'citcom' ),
				'icon'  => null,
			)
		);
		return $categories;
	}
);

/**
 * The editor offers citcom/* blocks plus a small core allow-list.
 * Blog posts keep the full core library.
 */
add_filter(
	'allowed_block_types_all',
	function ( $allowed, $context ) {
		if ( ! empty( $context->post ) && 'post' === $context->post->post_type ) {
			return $allowed;
		}

		$core = array(
			'core/paragraph',
			'core/heading',
			'core/list',
			'core/list-item',
			'core/image',
			'core/buttons',
			'core/button',
			'core/table',
			'core/separator',
			'core/spacer',
			'core/group',
			'core/columns',
			'core/column',
		);

		return array_merge( citcom_block_names(), $core );
	},
	10,
	2
);

/**
 * Running index of citcom sections rendered on this request, mirroring the
 * data-index attribute the flexible content loop emitted.
 *
 * @return int
 */
function citcom_block_index(): int {
	static $index = -1;
	++$index;
	return $index;
}

/**
 * Label for a choice field value.
 *
 * Swatch fields return their label (a class name such as "default_lighter") when
 * the swatch field type is installed. When it is not, the raw hex key comes back;
 * this maps it through the field's choices so the rendered class is right either
 * way.
 *
 * @param string $field_name Field name.
 * @param mixed  $value      Value from get_field().
 * @return string
 */
function citcom_choice_label( string $field_name, $value ): string {
	if ( is_array( $value ) ) {
		$value = $value['label'] ?? $value['value'] ?? '';
	}
	$value = (string) $value;
	if ( '' === $value ) {
		return '';
	}
	$field = get_field_object( $field_name );
	if ( $field && ! empty( $field['choices'] ) && isset( $field['choices'][ $value ] ) ) {
		return (string) $field['choices'][ $value ];
	}
	return $value;
}

/**
 * Section settings to wrapper attributes, exactly as layoutSettings() did.
 *
 * @param array $block  ACF block array.
 * @param array $fields get_fields() result for the block.
 * @return array{classes:string,anchor:string,anchor_attr:string}
 */
function citcom_section_attrs( array $block, array $fields ): array {
	$classes = '';

	$gradient = $fields['gradient_with_pattern'] ?? 'none';
	if ( ! empty( $gradient ) && 'none' !== $gradient ) {
		$classes .= ' ' . $gradient;
	} else {
		if ( ( $fields['background_colour'] ?? 'default' ) !== 'default' ) {
			$classes .= ' bg-' . strtolower( citcom_choice_label( 'background_color', $fields['background_color'] ?? '' ) );
		}
		if ( ! empty( $fields['shape_pattern'] ) ) {
			$classes .= ' pattern pattern-opac';
		}
	}

	if ( ( $fields['text_colour'] ?? 'default' ) !== 'default' ) {
		$classes .= ' text-' . strtolower( citcom_choice_label( 'text_color', $fields['text_color'] ?? '' ) );
	}

	if ( ! empty( $fields['section_padding'] ) && 'default' !== $fields['section_padding'] ) {
		$classes .= ' ' . $fields['section_padding'];
	}

	if ( ! empty( $block['className'] ) ) {
		$classes .= ' ' . $block['className'];
	}

	$anchor = (string) ( $fields['anchor_name'] ?? '' );
	if ( '' === $anchor && ! empty( $block['anchor'] ) ) {
		$anchor = (string) $block['anchor'];
	}

	return array(
		'classes'     => trim( preg_replace( '/\s+/', ' ', $classes ) ),
		'anchor'      => $anchor,
		'anchor_attr' => '' !== $anchor ? 'id="' . esc_attr( $anchor ) . '"' : '',
	);
}
