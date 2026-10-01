<?php
/**
 * Front-end and editor asset loading, ported from functions/styles-scripts.php.
 *
 * Compiled files come from build/ (wp-scripts). Per-block CSS is declared in each
 * block.json and loaded by WordPress only when the block is on the page.
 *
 * @package citcom
 */

defined( 'ABSPATH' ) || exit;

/**
 * Version string for a build asset: the wp-scripts hash when present, else the
 * theme version.
 *
 * @param string $handle Entry name, e.g. 'theme'.
 * @return string
 */
function citcom_asset_version( string $handle ): string {
	$asset_file = CITCOM_THEME_DIR . '/build/' . $handle . '.asset.php';
	if ( file_exists( $asset_file ) ) {
		$asset = include $asset_file;
		if ( ! empty( $asset['version'] ) ) {
			return (string) $asset['version'];
		}
	}
	return CITCOM_THEME_VERSION;
}

add_filter(
	'wp_resource_hints',
	function ( $urls, $relation_type ) {
		if ( 'preconnect' === $relation_type ) {
			$urls[] = array(
				'href'        => 'https://code.jquery.com',
				'crossorigin' => 'anonymous',
			);
			$urls[] = array(
				'href'        => 'https://kit.fontawesome.com',
				'crossorigin' => 'anonymous',
			);
			$urls[] = array(
				'href'        => 'https://use.typekit.net',
				'crossorigin' => 'anonymous',
			);
			$urls[] = array(
				'href'        => 'https://p.typekit.net',
				'crossorigin' => 'anonymous',
			);
		}
		if ( 'dns-prefetch' === $relation_type ) {
			$urls[] = 'https://www.googletagmanager.com';
			$urls[] = 'https://ajax.googleapis.com';
		}
		return $urls;
	},
	10,
	2
);

// Adobe Fonts (Typekit kit dom1odt), preloaded so it does not block render.
add_action(
	'wp_head',
	function () {
		// phpcs:disable WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet -- preload with a stylesheet fallback, which wp_enqueue_style() cannot print.
		echo '<link rel="preload" href="https://use.typekit.net/dom1odt.css" as="style" onload="this.onload=null;this.rel=\'stylesheet\'">';
		echo '<noscript><link rel="stylesheet" href="https://use.typekit.net/dom1odt.css"></noscript>';
		// phpcs:enable
	},
	5
);

add_action(
	'wp_head',
	function () {
		echo '<style>@font-face{font-display:swap;}</style>';
	},
	9
);

// Priority 1: the theme stylesheet has to come before the block stylesheets, as the
// flexContent partials came last in the old single stylesheet. WordPress prints block
// styles where its placeholder was enqueued (wp_enqueue_scripts, priority 10).
add_action(
	'wp_enqueue_scripts',
	function () {
		wp_enqueue_style( 'theme-style', CITCOM_THEME_URI . '/build/theme.css', array(), citcom_asset_version( 'theme' ), 'all' );
	},
	1
);

add_action(
	'wp_enqueue_scripts',
	function () {
		global $wp_query;

		// jQuery from the CDN, in the footer, as before (removal tracked in docs/jquery-usage.md).
		wp_deregister_script( 'jquery' );
		wp_enqueue_script( 'jquery', 'https://code.jquery.com/jquery-3.7.1.min.js', array(), null, array( 'in_footer' => true ) ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion

		wp_enqueue_script(
			'outdated',
			CITCOM_THEME_URI . '/assets/vendor/outdatedbrowser.min.js',
			array( 'jquery' ),
			CITCOM_THEME_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);

		wp_enqueue_script(
			'theme-script-main',
			CITCOM_THEME_URI . '/build/theme.js',
			array( 'jquery' ),
			citcom_asset_version( 'theme' ),
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
		wp_localize_script(
			'theme-script-main',
			'citcomAjax',
			array(
				'siteurl' => trailingslashit( site_url() ),
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'ebbon_nonce' ),
				'query'   => $wp_query ? $wp_query->query : array(),
			)
		);
	},
	0
);

// Shared diner rules (src/scss/diner.scss). Each diner block lists this handle
// before its own stylesheet in block.json, so it loads once, only with a diner block.
add_action(
	'init',
	function () {
		$file = CITCOM_THEME_DIR . '/build/diner.css';
		wp_register_style( 'citcom-diner', CITCOM_THEME_URI . '/build/diner.css', array(), file_exists( $file ) ? (string) filemtime( $file ) : CITCOM_THEME_VERSION );
		wp_style_add_data( 'citcom-diner', 'path', $file );
		// Lets WordPress inline it like the block styles.
	},
	5
);

// Block stylesheets that end up linked (not inlined) get WordPress's own version by
// default; use the file time instead so a new build is never served from cache.
add_filter(
	'style_loader_src',
	function ( $src, $handle ) {
		if ( ! str_starts_with( (string) $handle, 'citcom-' ) || false === strpos( (string) $src, '/build/' ) ) {
			return $src;
		}
		$path = CITCOM_THEME_DIR . strstr( (string) strtok( (string) $src, '?' ), '/build/' );
		return file_exists( $path ) ? add_query_arg( 'ver', filemtime( $path ), remove_query_arg( 'ver', $src ) ) : $src;
	},
	10,
	2
);

// Editor canvas stylesheet: AOS neutralised, clip paths, InnerBlocks areas.
// Linked into the iframe by enqueue_block_assets, so url(#citdot) stays local.
add_action(
	'enqueue_block_assets',
	function () {
		if ( ! is_admin() ) {
			return;
		}
		wp_enqueue_style( 'citcom-editor-canvas', CITCOM_THEME_URI . '/assets/admin/editor-canvas.css', array(), CITCOM_THEME_VERSION );
	}
);

// Font Awesome kit, async in the footer.
add_action(
	'wp_footer',
	function () {
		echo '<script src="https://kit.fontawesome.com/4a7ba1b0a5.js" crossorigin="anonymous" async></script>'; // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- the kit loader needs crossorigin and async, printed as the old theme did.
	},
	1
);

// Swap the no-js / no-animation classes once the page has loaded.
add_action(
	'wp_footer',
	function () {
		echo '<script>document.documentElement.className=document.documentElement.className.replace("no-js","js");document.documentElement.classList.remove("no-animation");</script>';
	},
	999
);

if ( ! is_admin() ) {
	// Core block CSS and theme.json global styles are not used on the front end:
	// the theme stylesheet styles core blocks itself.
	add_action(
		'wp_enqueue_scripts',
		function () {
			wp_dequeue_style( 'wp-block-library' );
			wp_dequeue_style( 'wp-block-library-theme' );
			wp_dequeue_style( 'classic-theme-styles' );
			wp_dequeue_style( 'wc-block-style' );
			wp_dequeue_style( 'global-styles' );
		},
		100
	);

	// Preconnect hints for footer scripts (kept from the original head output).
	add_action(
		'wp_head',
		function () {
			global $wp_scripts;
			if ( ! $wp_scripts ) {
				return;
			}
			foreach ( $wp_scripts->queue as $handle ) {
				if ( ! isset( $wp_scripts->registered[ $handle ] ) ) {
					continue;
				}
				$script = $wp_scripts->registered[ $handle ];
				if ( isset( $script->extra['group'] ) && 1 === $script->extra['group'] && $script->src ) {
					$source = $script->src . ( $script->ver ? "?ver={$script->ver}" : '' );
					echo "<link rel='preconnect' href='" . esc_url( $source ) . "' as='script' />\n";
				}
			}
		},
		2
	);

	// Non-theme stylesheets load as preload + onload swap; the theme stylesheet is
	// emitted as preload followed by the real link so it stays render-blocking.
	// Block stylesheets (citcom-*) are normally inlined by WordPress; when a page
	// goes over the inline limit and one is linked instead, it stays render-blocking
	// too, so a section never paints unstyled.
	add_filter(
		'style_loader_tag',
		function ( $tag, $handle = '' ) {
			if ( str_starts_with( (string) $handle, 'citcom-' ) ) {
				return $tag;
			}
			if ( false !== strpos( $tag, 'build/theme.css' ) ) {
				return str_replace( " rel='stylesheet'", ' rel="preload" as="style"', $tag ) . $tag; // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet -- rewrites an enqueued tag.
			}
			return str_replace( " rel='stylesheet'", ' rel="preload" as="style" onload="this.onload=null;this.rel=\'stylesheet\'"', $tag ); // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet -- rewrites an enqueued tag.
		},
		10,
		2
	);
}//end if
