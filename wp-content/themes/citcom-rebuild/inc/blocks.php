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
			$meta = json_decode( (string) file_get_contents( $block_json ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local theme file.
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
			return;
			// ACF Pro missing: nothing to register.
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
 * Core blocks allowed inside citcom/editor and citcom/media-text: the set the
 * old acfe_block_editor field allowed (plus list-item, column and separator).
 *
 * @return string[]
 */
function citcom_editor_allowed_blocks(): array {
	return array(
		'core/paragraph',
		'core/heading',
		'core/list',
		'core/list-item',
		'core/quote',
		'core/image',
		'core/buttons',
		'core/button',
		'core/table',
		'core/separator',
		'core/spacer',
		'core/group',
		'core/columns',
		'core/column',
		'core/media-text',
		'core/video',
		'core/embed',
		'core/shortcode',
	);
}

/**
 * The editor offers citcom/* blocks plus the core allow-list above.
 * Blog posts keep the full core library.
 */
add_filter(
	'allowed_block_types_all',
	function ( $allowed, $context ) {
		if ( ! empty( $context->post ) && 'post' === $context->post->post_type ) {
			return $allowed;
		}
		return array_merge( citcom_block_names(), citcom_editor_allowed_blocks() );
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

/**
 * Texturize as the old theme did.
 *
 * The old layouts were rendered from templates, outside the_content, so text
 * and textarea field values were printed untouched; only WYSIWYG fields were
 * texturized (acf_the_content). Blocks render inside the_content, where
 * wptexturize would now curl every quote in every ACF field. So: texturize
 * core blocks (the InnerBlocks that replaced the WYSIWYG fields), leave ACF
 * block output alone, and keep the default for classic content.
 */
add_action(
	'init',
	function () {
		remove_filter( 'the_content', 'wptexturize' );
	}
);
add_filter(
	'the_content',
	function ( $content ) {
		return has_blocks( $content ) ? $content : wptexturize( $content );
	},
	8
	// Before do_blocks (9), so has_blocks() still sees the block comments.
);
add_filter(
	'render_block',
	function ( $html, $block ) {
		return str_starts_with( (string) ( $block['blockName'] ?? '' ), 'core/' ) ? wptexturize( $html ) : $html;
	},
	10,
	2
);

/*
 * Core block styles inside the sections.
 *
 * The old site printed its editor content from a cache built when the page was
 * saved, so its core blocks never rendered during a page view and WordPress
 * never loaded their stylesheets, their block-supports rules (the
 * wp-container-* layout classes) or their per-block global styles (the 2em
 * gap of core/columns). The theme stylesheet styles those blocks itself.
 * The inner blocks of citcom/editor and citcom/media-text render during the
 * page view now, and WordPress would add all three: columns gain 1.5em of gap,
 * images sit differently on their baseline. So what those inner blocks enqueue
 * while they render is taken out again. Blog posts and the sidebar widgets
 * keep their core styles, as they had them before.
 */
if ( ! is_admin() ) {
	add_filter(
		'pre_render_block',
		function ( $pre_render, $parsed_block ) {
			if ( in_array( $parsed_block['blockName'] ?? '', array( 'citcom/editor', 'citcom/media-text' ), true ) ) {
				citcom_core_block_styles_snapshot(
					array(
						'styles' => wp_styles()->queue,
						'rules'  => array_keys( WP_Style_Engine_CSS_Rules_Store::get_store( 'block-supports' )->get_all_rules() ),
					)
				);
			}
			return $pre_render;
		},
		10,
		2
	);
	add_filter(
		'render_block',
		function ( $html, $block ) {
			if ( ! in_array( $block['blockName'] ?? '', array( 'citcom/editor', 'citcom/media-text' ), true ) ) {
				return $html;
			}
			$before = citcom_core_block_styles_snapshot();
			if ( null === $before ) {
				return $html;
			}
			foreach ( array_diff( wp_styles()->queue, $before['styles'] ) as $handle ) {
				if ( str_starts_with( (string) $handle, 'wp-block-' ) ) {
					wp_dequeue_style( $handle );
				}
			}
			$store = WP_Style_Engine_CSS_Rules_Store::get_store( 'block-supports' );
			foreach ( array_diff( array_keys( $store->get_all_rules() ), $before['rules'] ) as $selector ) {
				$store->remove_rule( $selector );
			}
			return $html;
		},
		10,
		2
	);
}

/**
 * Hold, then hand back, what was enqueued before a section's inner blocks
 * rendered. Sections do not nest, so one slot is enough.
 *
 * @param array<string,array>|null $snapshot Pass to store; omit to take it back.
 * @return array<string,array>|null
 */
function citcom_core_block_styles_snapshot( ?array $snapshot = null ): ?array {
	static $held = null;
	if ( null !== $snapshot ) {
		$held = $snapshot;
		return null;
	}
	$taken = $held;
	$held  = null;
	return $taken;
}
