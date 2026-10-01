<?php
/**
 * Convert editor HTML (what the old acfe_block_editor field stored) into block
 * markup for InnerBlocks. Used by the local fixture now and by the Phase 3
 * migration for editor and media_text content.
 *
 * Handles paragraphs, headings, lists, images, quotes, separators, buttons and
 * spacers; anything else is kept as a core/html block so nothing is lost.
 *
 * @package citcom
 */

defined( 'ABSPATH' ) || exit;

/**
 * HTML to serialised blocks.
 *
 * @param string $html Editor HTML.
 * @return string Block markup.
 */
function citcom_html_to_blocks( string $html ): string {
	$html = trim( $html );
	if ( '' === $html ) {
		return '';
	}

	$doc = new DOMDocument();
	libxml_use_internal_errors( true );
	$doc->loadHTML( '<?xml encoding="utf-8" ?><div id="citcom-root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
	libxml_clear_errors();

	$root = $doc->getElementById( 'citcom-root' );
	if ( ! $root ) {
		return '<!-- wp:html -->' . $html . '<!-- /wp:html -->';
	}

	$blocks = array();
	foreach ( $root->childNodes as $node ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		if ( XML_TEXT_NODE === $node->nodeType ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$text = trim( $node->textContent ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			if ( '' !== $text ) {
				$blocks[] = '<!-- wp:paragraph --><p>' . $text . '</p><!-- /wp:paragraph -->';
			}
			continue;
		}
		if ( ! $node instanceof DOMElement ) {
			continue;
		}
		$blocks[] = citcom_html_node_to_block( $node, $doc );
	}

	return implode( "\n\n", array_filter( $blocks ) );
}

/**
 * Inner HTML of a node.
 *
 * @param DOMNode     $node Node.
 * @param DOMDocument $doc  Document.
 * @return string
 */
function citcom_html_inner( DOMNode $node, DOMDocument $doc ): string {
	$out = '';
	foreach ( $node->childNodes as $child ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$out .= $doc->saveHTML( $child );
	}
	return trim( $out );
}

/**
 * One top-level element to a block.
 *
 * @param DOMElement  $el  Element.
 * @param DOMDocument $doc Document.
 * @return string
 */
function citcom_html_node_to_block( DOMElement $el, DOMDocument $doc ): string {
	$tag     = strtolower( $el->tagName ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	$classes = preg_split( '/\s+/', trim( (string) $el->getAttribute( 'class' ) ) ) ?: array();
	$inner   = citcom_html_inner( $el, $doc );
	$outer   = $doc->saveHTML( $el );

	$align = '';
	foreach ( $classes as $class ) {
		if ( preg_match( '/^has-text-align-(left|center|right)$/', $class, $m ) ) {
			$align = $m[1];
		}
	}

	switch ( $tag ) {
		case 'p':
			if ( '' === trim( wp_strip_all_tags( $inner ) ) && false === strpos( $inner, '<' ) ) {
				return '';
			}
			$attrs = $align ? ' {"align":"' . $align . '"}' : '';
			return '<!-- wp:paragraph' . $attrs . ' --><p class="wp-block-paragraph' . ( $align ? ' has-text-align-' . $align : '' ) . '">' . $inner . '</p><!-- /wp:paragraph -->';

		case 'h1':
		case 'h2':
		case 'h3':
		case 'h4':
		case 'h5':
		case 'h6':
			$level = (int) substr( $tag, 1 );
			$attrs = array();
			if ( 2 !== $level ) {
				$attrs[] = '"level":' . $level;
			}
			if ( $align ) {
				$attrs[] = '"textAlign":"' . $align . '"';
			}
			$attr_json = $attrs ? ' {' . implode( ',', $attrs ) . '}' : '';
			return '<!-- wp:heading' . $attr_json . ' --><' . $tag . ' class="wp-block-heading' . ( $align ? ' has-text-align-' . $align : '' ) . '">' . $inner . '</' . $tag . '><!-- /wp:heading -->';

		case 'ul':
		case 'ol':
			$items = '';
			foreach ( $el->childNodes as $li ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				if ( $li instanceof DOMElement && 'li' === strtolower( $li->tagName ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					$items .= '<!-- wp:list-item --><li>' . citcom_html_inner( $li, $doc ) . '</li><!-- /wp:list-item -->';
				}
			}
			$ordered = 'ol' === $tag ? ' {"ordered":true}' : '';
			return '<!-- wp:list' . $ordered . ' --><' . $tag . ' class="wp-block-list">' . $items . '</' . $tag . '><!-- /wp:list -->';

		case 'hr':
			return '<!-- wp:separator --><hr class="wp-block-separator has-alpha-channel-opacity"/><!-- /wp:separator -->';

		case 'figure':
			if ( in_array( 'wp-block-image', $classes, true ) ) {
				$img = $el->getElementsByTagName( 'img' )->item( 0 );
				$id  = 0;
				if ( $img ) {
					foreach ( preg_split( '/\s+/', (string) $img->getAttribute( 'class' ) ) as $class ) {
						if ( preg_match( '/^wp-image-(\d+)$/', $class, $m ) ) {
							$id = (int) $m[1];
						}
					}
				}
				$attrs = array();
				if ( $id ) {
					$attrs[] = '"id":' . $id;
				}
				foreach ( array(
					'aligncenter' => 'center',
					'alignleft'   => 'left',
					'alignright'  => 'right',
					'alignwide'   => 'wide',
					'alignfull'   => 'full',
				) as $class => $value ) {
					if ( in_array( $class, $classes, true ) ) {
						$attrs[] = '"align":"' . $value . '"';
					}
				}
				foreach ( $classes as $class ) {
					if ( preg_match( '/^size-(\w+)$/', $class, $m ) ) {
						$attrs[] = '"sizeSlug":"' . $m[1] . '"';
					}
				}
				$extra = array_values( array_diff( $classes, array( 'wp-block-image', 'aligncenter', 'alignleft', 'alignright', 'alignwide', 'alignfull' ) ) );
				$extra = array_filter( $extra, fn( $c ) => ! preg_match( '/^size-/', $c ) );
				if ( $extra ) {
					$attrs[] = '"className":"' . implode( ' ', $extra ) . '"';
				}
				$attr_json = $attrs ? ' {' . implode( ',', $attrs ) . '}' : '';
				// Rebuild the figure the way core/image saves it (no srcset, loading or
				// decoding attributes), or the editor flags the block as invalid.
				$figure_classes = array( 'wp-block-image' );
				foreach ( $classes as $class ) {
					if ( 'wp-block-image' !== $class ) {
						$figure_classes[] = $class;
					}
				}
				$src      = $img ? $img->getAttribute( 'src' ) : '';
				$alt      = $img ? $img->getAttribute( 'alt' ) : '';
				$img_html = '<img src="' . esc_url( $src ) . '" alt="' . esc_attr( $alt ) . '"' . ( $id ? ' class="wp-image-' . $id . '"' : '' ) . '/>';
				return '<!-- wp:image' . $attr_json . ' --><figure class="' . esc_attr( implode( ' ', $figure_classes ) ) . '">' . $img_html . '</figure><!-- /wp:image -->';
			}//end if
			if ( in_array( 'wp-block-video', $classes, true ) ) {
				return '<!-- wp:video -->' . $outer . '<!-- /wp:video -->';
			}
			return '<!-- wp:html -->' . $outer . '<!-- /wp:html -->';

		case 'blockquote':
			$style = in_array( 'is-style-plain', $classes, true ) ? ' {"className":"is-style-plain"}' : '';
			$body  = '';
			foreach ( $el->childNodes as $child ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				if ( $child instanceof DOMElement && 'p' === strtolower( $child->tagName ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					$body .= '<!-- wp:paragraph --><p>' . citcom_html_inner( $child, $doc ) . '</p><!-- /wp:paragraph -->';
				} elseif ( $child instanceof DOMElement && 'cite' === strtolower( $child->tagName ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					$body .= '<cite>' . citcom_html_inner( $child, $doc ) . '</cite>';
				}
			}
			return '<!-- wp:quote' . $style . ' --><blockquote class="wp-block-quote' . ( $style ? ' is-style-plain' : '' ) . '">' . $body . '</blockquote><!-- /wp:quote -->';

		case 'div':
			if ( in_array( 'wp-block-buttons', $classes, true ) ) {
				$buttons = '';
				foreach ( $el->getElementsByTagName( 'a' ) as $a ) {
					$buttons .= '<!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="' . esc_url( $a->getAttribute( 'href' ) ) . '">' . citcom_html_inner( $a, $doc ) . '</a></div><!-- /wp:button -->';
				}
				return '<!-- wp:buttons --><div class="wp-block-buttons">' . $buttons . '</div><!-- /wp:buttons -->';
			}
			if ( in_array( 'wp-block-spacer', $classes, true ) ) {
				$h = (int) preg_replace( '/\D/', '', (string) $el->getAttribute( 'style' ) );
				return '<!-- wp:spacer {"height":"' . ( $h ?: 100 ) . 'px"} --><div style="height:' . ( $h ?: 100 ) . 'px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->';
			}
			return '<!-- wp:html -->' . $outer . '<!-- /wp:html -->';

		default:
			return '<!-- wp:html -->' . $outer . '<!-- /wp:html -->';
	}//end switch
}
