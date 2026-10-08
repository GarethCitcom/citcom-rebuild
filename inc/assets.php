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
			// The image CDN: images are not CORS requests, so no crossorigin here.
			$urls[] = array( 'href' => 'https://spcdn.shortpixel.ai' );
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
		foreach ( citcom_typekit_font_urls() as $url ) {
			echo '<link rel="preload" href="' . esc_url( $url ) . '" as="font" type="font/woff2" crossorigin>';
		}
		echo '<link rel="preload" href="https://use.typekit.net/dom1odt.css" as="style" onload="this.onload=null;this.rel=\'stylesheet\'">';
		echo '<noscript><link rel="stylesheet" href="https://use.typekit.net/dom1odt.css"></noscript>';
		// phpcs:enable
	},
	5
);

/**
 * The kit's woff2 files, for preloading: the kit CSS loads without blocking,
 * so without this the fonts are only discovered once it has arrived and the
 * text repaints late, a layout shift on every page. Read from the kit CSS
 * and kept for a day, so a republished kit is picked up by itself.
 *
 * @return string[] Empty when the kit CSS cannot be read.
 */
function citcom_typekit_font_urls(): array {
	$urls = get_transient( 'citcom_typekit_fonts' );
	if ( is_array( $urls ) ) {
		return $urls;
	}
	$urls     = array();
	$response = wp_remote_get( 'https://use.typekit.net/dom1odt.css', array( 'timeout' => 5 ) );
	if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
		preg_match_all( '#url\("(https://use\.typekit\.net/af/[^"]+)"\)\s*format\("woff2"\)#', wp_remote_retrieve_body( $response ), $matches );
		$urls = array_values( array_unique( $matches[1] ) );
	}
	set_transient( 'citcom_typekit_fonts', $urls, $urls ? DAY_IN_SECONDS : HOUR_IN_SECONDS );
	return $urls;
}

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

		// No jQuery on the front end since Phase 4 (docs/jquery-usage.md); plugins
		// that need it enqueue WordPress's own copy themselves.
		wp_enqueue_script(
			'outdated',
			CITCOM_THEME_URI . '/assets/vendor/outdatedbrowser.min.js',
			array(),
			CITCOM_THEME_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);

		wp_enqueue_script(
			'theme-script-main',
			CITCOM_THEME_URI . '/build/theme.js',
			array(),
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

	/*
	 * Stylesheets. With a critical CSS file for the template (assets/critical/,
	 * tools/critical-css.mjs: the rules its first screen needs), it is inlined and
	 * every stylesheet of the theme loads without blocking the first paint:
	 * preload, then a swap to a stylesheet when it arrives, with a noscript
	 * fallback. Without it the theme stylesheet stays render-blocking and only
	 * other plugins' stylesheets are deferred.
	 */
	add_action(
		'wp_head',
		function () {
			$css = citcom_critical_css();
			if ( '' !== $css ) {
				echo '<style id="citcom-critical">' . $css . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the theme's own built CSS.
			}
		},
		1
	);

	add_filter(
		'style_loader_tag',
		function ( $tag, $handle = '' ) {
			$theme = str_starts_with( (string) $handle, 'citcom-' ) || false !== strpos( $tag, 'build/theme.css' );
			$async = str_replace( " rel='stylesheet'", " rel=\"preload\" as=\"style\" onload=\"this.onload=null;this.rel='stylesheet'\"", $tag ); // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet -- rewrites an enqueued tag.
			if ( $theme ) {
				if ( '' === citcom_critical_css() ) {
					return false !== strpos( $tag, 'build/theme.css' ) ? str_replace( " rel='stylesheet'", ' rel="preload" as="style"', $tag ) . $tag : $tag; // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet -- rewrites an enqueued tag.
				}
				return $async . '<noscript>' . $tag . '</noscript>'; // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet -- rewrites an enqueued tag.
			}
			return $async; // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet -- rewrites an enqueued tag.
		},
		10,
		2
	);
}

/**
 * The template type of the current request, for the critical CSS file name
 * (assets/critical/<template>.css, docs/critical-urls.txt).
 *
 * @return string
 */
function citcom_critical_template(): string {
	if ( is_front_page() ) {
		return 'home';
	}
	if ( is_post_type_archive( 'service' ) ) {
		return 'service-archive';
	}
	if ( is_singular( 'service' ) ) {
		return 'service';
	}
	if ( is_post_type_archive( 'case-study' ) || is_tax( 'cs-tag' ) ) {
		return 'case-study-archive';
	}
	if ( is_singular( 'case-study' ) ) {
		return 'case-study';
	}
	if ( is_home() || is_category() || is_tag() || is_search() || is_date() || is_author() ) {
		return 'blog';
	}
	if ( is_singular( 'post' ) ) {
		return 'post';
	}
	if ( is_singular( 'landing-page' ) ) {
		return 'landing-page';
	}
	return 'page';
}

/**
 * The critical CSS for this request, read once. Empty when there is no file
 * for the template, in which case the stylesheets stay render-blocking.
 *
 * @return string
 */
function citcom_critical_css(): string {
	static $css = null;
	if ( null === $css ) {
		$file = CITCOM_THEME_DIR . '/assets/critical/' . citcom_critical_template() . '.css';
		$css  = file_exists( $file ) ? trim( (string) file_get_contents( $file ) ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local theme file.
		$css  = str_replace( '__THEME__', CITCOM_THEME_URI, $css );
	}
	return $css;
}
