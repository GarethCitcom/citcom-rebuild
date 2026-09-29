<?php
/**
 * Nav walker producing the original theme's header and footer menu markup.
 *
 * Ported 1:1 from functions/lib/bs4Navwalker.php (class bs4Navwalker). The class
 * name is kept as an alias so `new bs4navwalker()` in ported templates still works.
 *
 * @package citcom
 */

defined( 'ABSPATH' ) || exit;

/**
 * Bootstrap style nav walker with the citcom dropdown / mobile slide markup.
 */
class Citcom_Nav_Walker extends Walker_Nav_Menu {

	/**
	 * Open a sub-menu wrapper.
	 *
	 * @param string   $output Output by reference.
	 * @param int      $depth  Depth.
	 * @param stdClass $args   wp_nav_menu() args.
	 */
	public function start_lvl( &$output, $depth = 0, $args = null ) {
		$indent  = str_repeat( "\t", $depth );
		$output .= "\n$indent<div class=\"citcom-dropdown-menu-container\"><div class=\"citcom-dropdown-menu d-flex flex-column gap-3 gap-lg-2 py-3 py-lg-2 d$depth\"><button type=\"button\" class=\"mob-slide-return nav-link nav-link-lg citcom-nav-link rounded-pill focus-ring focus-ring-primary\"><i class=\"fa-light fa-chevron-left fa-fw\"></i></button>\n";
	}

	/**
	 * Close a sub-menu wrapper.
	 *
	 * @param string   $output Output by reference.
	 * @param int      $depth  Depth.
	 * @param stdClass $args   wp_nav_menu() args.
	 */
	public function end_lvl( &$output, $depth = 0, $args = null ) {
		$indent  = str_repeat( "\t", $depth );
		$output .= "$indent</div></div>\n";
	}

	/**
	 * Open a menu item.
	 *
	 * @param string   $output            Output by reference.
	 * @param WP_Post  $data_object       Menu item.
	 * @param int      $depth             Depth.
	 * @param stdClass $args              wp_nav_menu() args.
	 * @param int      $current_object_id Current item ID.
	 */
	public function start_el( &$output, $data_object, $depth = 0, $args = null, $current_object_id = 0 ) {
		$item   = $data_object;
		$indent = ( $depth ) ? str_repeat( "\t", $depth ) : '';

		$classes   = empty( $item->classes ) ? array() : (array) $item->classes;
		$classes[] = 'menu-item-' . $item->ID . ' page-item-' . $item->object_id;

		$case_study_archive_url = get_post_type_archive_link( 'case-study' );
		$services_archive_url   = get_post_type_archive_link( 'service' );

		$class_names = join( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item, $args, $depth ) );

		$class_names .= ' nav-item';

		if ( in_array( 'menu-item-has-children', $classes, true ) ) {
			$class_names .= ' citcom-dropdown';
		}

		if ( in_array( 'current-menu-item', $classes, true ) ) {
			$class_names .= ' active';
		}

		if ( 0 === $depth && in_array( 'current-menu-parent', $classes, true ) ) {
			$class_names .= ' active';
		}

		if ( is_single() && 'case-study' === get_post_type() && $item->url === $case_study_archive_url ) {
			$class_names .= ' active';
		}
		if ( is_single() && 'service' === get_post_type() && $item->url === $services_archive_url ) {
			$class_names .= ' active';
		}

		$class_names = $class_names ? ' class="' . esc_attr( $class_names ) . '"' : '';

		$id      = apply_filters( 'nav_menu_item_id', 'menu-item-' . $item->ID, $item, $args, $depth );
		$id_name = esc_attr( $args->menu_id ?? '' );
		$id      = $id ? ' id="' . esc_attr( $id ) . '"' : '';

		if ( 0 === $depth ) {
			$output .= $indent . '<li' . $id . $class_names . '>';
		}

		$atts           = array();
		$atts['title']  = ! empty( $item->attr_title ) ? $item->attr_title : '';
		$atts['target'] = ! empty( $item->target ) ? $item->target : '';
		$atts['rel']    = ! empty( $item->xfn ) ? $item->xfn : '';
		$atts['href']   = ! empty( $item->url ) ? $item->url : '';
		$atts['class']  = '';

		if ( 0 === $depth ) {
			if ( 'footer-nav-1' === $id_name || 'footer-nav-2' === $id_name ) {
				$atts['class'] = 'nav-link focus-ring focus-ring-light rounded-pill d-flex text-nowrap align-items-center';
			} else {
				$atts['class'] = 'nav-link nav-link-lg citcom-nav-link focus-dashed rounded-pill px-4 d-flex text-nowrap h-100 align-items-center justify-content-lg-center';
			}
		}

		if ( 1 === $depth ) {
			$atts['class'] = 'citcom-dropdown-link';
		}

		if ( $depth < 2 && in_array( 'menu-item-has-children', $classes, true ) ) {
			$atts['class'] .= ' toggle-citcom-dropdown';
		}

		if ( $depth > 0 ) {
			$manual_class = array_values( $classes )[0] . ' ' . 'citcom-dropdown-item btn btn-default rounded-pill nav-link shadow-sm d' . $depth;
			foreach ( $classes as $class ) {
				$manual_class = $manual_class . ' ' . $class;
			}
			$atts['class'] = $manual_class;
		}

		if ( ( is_home() || is_category() || is_tag() ) && in_array( 'current_page_parent', $item->classes, true ) ) {
			array_push( $item->classes, 'active' );
			$atts['class'] .= ' temp-active';
		}

		if ( in_array( 'current-menu-item', $item->classes, true ) ) {
			$atts['class'] .= ' temp-active';
		}

		if ( is_single() && 'case-study' === get_post_type() && $item->url === $case_study_archive_url ) {
			$atts['class'] .= ' temp-active';
		}
		if ( is_single() && 'service' === get_post_type() && $item->url === $services_archive_url ) {
			$atts['class'] .= ' temp-active';
		}

		$atts = apply_filters( 'nav_menu_link_attributes', $atts, $item, $args, $depth );

		$attributes = '';
		foreach ( $atts as $attr => $value ) {
			if ( ! empty( $value ) ) {
				$value       = ( 'href' === $attr ) ? esc_url( $value ) : esc_attr( $value );
				$attributes .= ' ' . $attr . '="' . $value . '"';
			}
		}

		// "Menu ID" ACF field on nav items (group_67166cced6169), e.g. open_preferences_center.
		$menu_id = function_exists( 'get_field' ) ? get_field( 'id', $item->ID ) : '';
		if ( $menu_id ) {
			$attributes .= ' id="' . esc_attr( $menu_id ) . '"';
		}

		$item_output  = $args->before ?? '';
		$item_output .= '<a' . $attributes . '>';
		$item_output .= ( $args->link_before ?? '' ) . apply_filters( 'the_title', $item->title, $item->ID ) . ( $args->link_after ?? '' );
		$item_output .= '</a>';
		if ( 0 === $depth && in_array( 'menu-item-has-children', $classes, true ) ) {
			$item_output .= '<button type="button" class="btn btn-link rounded-pill mob-slide"><i class="fa-light fa-chevron-right fa-fw"></i></button>';
		}
		$item_output .= $args->after ?? '';

		$output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );
	}

	/**
	 * Close a menu item.
	 *
	 * @param string   $output      Output by reference.
	 * @param WP_Post  $data_object Menu item.
	 * @param int      $depth       Depth.
	 * @param stdClass $args        wp_nav_menu() args.
	 */
	public function end_el( &$output, $data_object, $depth = 0, $args = null ) {
		if ( 0 === $depth ) {
			$output .= "</li>\n";
		}
	}

	/**
	 * Fallback when no menu is assigned: output nothing rather than a page list.
	 *
	 * @param array $args wp_nav_menu() args.
	 * @return string
	 */
	public static function fallback( $args ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		return '';
	}
}

class_alias( 'Citcom_Nav_Walker', 'bs4navwalker' );
