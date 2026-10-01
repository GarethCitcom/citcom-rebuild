<?php
/**
 * Theme setup: supports, menus, sidebars, image sizes, head clean-up, rewrites and
 * the small third-party integrations the original theme carried in functions/.
 *
 * @package citcom
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'after_setup_theme',
	function () {
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
		add_theme_support( 'editor-styles' );
		add_editor_style( array( 'build/theme.css', 'build/editor.css' ) );

		register_nav_menus(
			array(
				'primary_menu'  => __( 'Primary Menu', 'citcom' ),
				'footer_menu_1' => __( 'Footer Menu 1', 'citcom' ),
				'footer_menu_2' => __( 'Footer Menu 2', 'citcom' ),
			)
		);

		// Media sizes from functions/lib/lazy-image.php.
		add_image_size( 'lquip', 100, 100 );
		add_image_size( 'x_large', 1400, 1400 );
		add_image_size( 'xx_large', 1920, 1920 );

		add_post_type_support( 'page', 'page-attributes' );
	},
	0
);

add_filter(
	'intermediate_image_sizes_advanced',
	function ( $sizes ) {
		unset( $sizes['1536x1536'], $sizes['2048x2048'] );
		return $sizes;
	}
);

add_action(
	'widgets_init',
	function () {
		$sidebars = array(
			'blog_sidebar'   => 'Blog listings sidebar',
			'post_sidebar'   => 'Post page sidebar',
			'search_sidebar' => 'Search sidebar',
		);
		foreach ( $sidebars as $id => $name ) {
			register_sidebar(
				array(
					'name'          => $name,
					'id'            => $id,
					'before_widget' => '<div>',
					'after_widget'  => '</div>',
					'before_title'  => '<h2 class="rounded">',
					'after_title'   => '</h2>',
				)
			);
		}
	}
);

/*
 * Head clean-up (functions/misc.php).
 */
remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'wp_generator' );
remove_action( 'wp_head', 'wp_shortlink_wp_head' );
remove_action( 'wp_head', 'feed_links', 2 );
remove_action( 'wp_head', 'feed_links_extra', 3 );

add_action(
	'init',
	function () {
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	}
);

add_filter(
	'tiny_mce_plugins',
	function ( $plugins ) {
		return is_array( $plugins ) ? array_diff( $plugins, array( 'wpemoji' ) ) : array();
	}
);

/*
 * Query behaviour (functions/theme_content/query-vars.php).
 */
add_filter(
	'query_vars',
	function ( $vars ) {
		$vars[] = 'citcom';
		return $vars;
	}
);

add_action(
	'pre_get_posts',
	function ( $query ) {
		if ( $query->is_main_query() && ( $query->is_home() || $query->is_archive() ) ) {
			$query->set( 'ignore_sticky_posts', 1 );
		}
	}
);

/*
 * Blog URL structure (functions/theme_content/url-structure.php).
 * Posts link to /blog/{category}/{slug}; these rewrite rules make /blog/... resolve.
 */
add_action(
	'generate_rewrite_rules',
	function ( $wp_rewrite ) {
		$feed              = '(feed|rdf|rss|rss2|atom)';
		$rules             = array(
			'(.?.+?)/page/?([0-9]{1,})/?$'               => 'index.php?pagename=$matches[1]&paged=$matches[2]',
			'blog/([^/]+)/?$'                            => 'index.php?post_type=post&name=$matches[1]',
			'blog/[^/]+/attachment/([^/]+)/?$'           => 'index.php?post_type=post&attachment=$matches[1]',
			'blog/[^/]+/attachment/([^/]+)/trackback/?$' => 'index.php?post_type=post&attachment=$matches[1]&tb=1',
			'blog/[^/]+/attachment/([^/]+)/feed/' . $feed . '/?$' => 'index.php?post_type=post&attachment=$matches[1]&feed=$matches[2]',
			'blog/[^/]+/attachment/([^/]+)/' . $feed . '/?$' => 'index.php?post_type=post&attachment=$matches[1]&feed=$matches[2]',
			'blog/[^/]+/attachment/([^/]+)/comment-page-([0-9]{1,})/?$' => 'index.php?post_type=post&attachment=$matches[1]&cpage=$matches[2]',
			'blog/[^/]+/attachment/([^/]+)/embed/?$'     => 'index.php?post_type=post&attachment=$matches[1]&embed=true',
			'blog/[^/]+/embed/([^/]+)/?$'                => 'index.php?post_type=post&attachment=$matches[1]&embed=true',
			'blog/([^/]+)/embed/?$'                      => 'index.php?post_type=post&name=$matches[1]&embed=true',
			'blog/[^/]+/([^/]+)/embed/?$'                => 'index.php?post_type=post&attachment=$matches[1]&embed=true',
			'blog/([^/]+)/trackback/?$'                  => 'index.php?post_type=post&name=$matches[1]&tb=1',
			'blog/([^/]+)/feed/' . $feed . '/?$'         => 'index.php?post_type=post&name=$matches[1]&feed=$matches[2]',
			'blog/([^/]+)/' . $feed . '/?$'              => 'index.php?post_type=post&name=$matches[1]&feed=$matches[2]',
			'blog/page/([0-9]{1,})/?$'                   => 'index.php?post_type=post&paged=$matches[1]',
			'blog/[^/]+/page/?([0-9]{1,})/?$'            => 'index.php?post_type=post&name=$matches[1]&paged=$matches[2]',
			'blog/([^/]+)/page/?([0-9]{1,})/?$'          => 'index.php?post_type=post&name=$matches[1]&paged=$matches[2]',
			'blog/([^/]+)/comment-page-([0-9]{1,})/?$'   => 'index.php?post_type=post&name=$matches[1]&cpage=$matches[2]',
			'blog/([^/]+)(/[0-9]+)?/?$'                  => 'index.php?post_type=post&name=$matches[1]&page=$matches[2]',
			'blog/[^/]+/([^/]+)/?$'                      => 'index.php?post_type=post&attachment=$matches[1]',
			'blog/[^/]+/([^/]+)/trackback/?$'            => 'index.php?post_type=post&attachment=$matches[1]&tb=1',
			'blog/[^/]+/([^/]+)/feed/' . $feed . '/?$'   => 'index.php?post_type=post&attachment=$matches[1]&feed=$matches[2]',
			'blog/[^/]+/([^/]+)/' . $feed . '/?$'        => 'index.php?post_type=post&attachment=$matches[1]&feed=$matches[2]',
			'blog/[^/]+/([^/]+)/comment-page-([0-9]{1,})/?$' => 'index.php?post_type=post&attachment=$matches[1]&cpage=$matches[2]',
		);
		$wp_rewrite->rules = $rules + $wp_rewrite->rules;
	}
);

add_filter(
	'post_link',
	function ( $post_link, $post ) {
		$post = get_post( $post );
		if ( $post && 'post' === $post->post_type ) {
			$cat = get_the_category( $post->ID );
			if ( $cat ) {
				return home_url( '/blog/' . $cat[0]->slug . '/' . $post->post_name );
			}
			return home_url( '/blog/' . $post->post_name );
		}
		return $post_link;
	},
	1,
	2
);

add_action( 'after_switch_theme', 'flush_rewrite_rules' );

/*
 * Media: set title, alt, caption and description from the filename on upload
 * (functions/lib/auto-alt.php).
 */
add_action(
	'add_attachment',
	function ( $post_id ) {
		if ( ! wp_attachment_is_image( $post_id ) ) {
			return;
		}
		$title = get_post( $post_id )->post_title;
		$title = preg_replace( '%\s*[-_\s]+\s*%', ' ', $title );
		$title = ucwords( strtolower( $title ) );
		update_post_meta( $post_id, '_wp_attachment_image_alt', $title );
		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_title'   => $title,
				'post_excerpt' => $title,
				'post_content' => $title,
			)
		);
	}
);

/*
 * WPConsent: preference cookie renamed to citcom_preferences
 * (functions/lib/wpconsent.php and delete-wpconsent-cookie.php).
 */
add_action(
	'init',
	function () {
		if ( isset( $_COOKIE['wpconsent_preferences'] ) ) {
			setcookie(
				'wpconsent_preferences',
				'',
				array(
					'expires'  => time() - YEAR_IN_SECONDS,
					'path'     => '/',
					'domain'   => COOKIE_DOMAIN ? COOKIE_DOMAIN : '',
					'secure'   => is_ssl(),
					'httponly' => false,
					'samesite' => 'Lax',
				)
			);
			unset( $_COOKIE['wpconsent_preferences'] );
		}
	}
);

add_filter(
	'wpconsent_frontend_js_data',
	function ( $data ) {
		$data['cookie_name'] = 'citcom_preferences';
		return $data;
	}
);

add_action(
	'wp_enqueue_scripts',
	function () {
		if ( ! wp_script_is( 'wpconsent-frontend-js', 'registered' ) ) {
			return;
		}
		wp_add_inline_script(
			'wpconsent-frontend-js',
			'(function(){if(typeof WPConsent==="undefined")return;var _get=WPConsent.getCookie,_set=WPConsent.setCookie;WPConsent.getCookie=function(n){return _get.call(this,n==="wpconsent_preferences"?"citcom_preferences":n);};WPConsent.setCookie=function(n,v,d){return _set.call(this,n==="wpconsent_preferences"?"citcom_preferences":n,v,d);};var oldVal=_get.call(WPConsent,"wpconsent_preferences");var newVal=_get.call(WPConsent,"citcom_preferences");if(oldVal&&!newVal){var dur=window.wpconsent&&wpconsent.consent_duration?parseInt(wpconsent.consent_duration,10):365;_set.call(WPConsent,"citcom_preferences",oldVal,dur);_set.call(WPConsent,"wpconsent_preferences","",-1);}})();',
			'after'
		);
	},
	20
);

/*
 * Admin: keep the Menus screen highlighted (functions/theme_content/wp-admin.php).
 */
add_action(
	'admin_head',
	function () {
		$screen = get_current_screen();
		if ( ! $screen || 'nav-menus' !== $screen->id ) {
			return;
		}
		echo '<script>jQuery(function($){var r=$("#navs");r.addClass("current");r.find(".menu-top").addClass("current");});</script>';
	},
	0
);
