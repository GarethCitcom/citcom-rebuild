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
		echo '<link rel="preload" href="https://use.typekit.net/dom1odt.css" as="style" onload="this.onload=null;this.rel=\'stylesheet\'">';
		echo '<noscript><link rel="stylesheet" href="https://use.typekit.net/dom1odt.css"></noscript>';
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

add_action(
	'wp_enqueue_scripts',
	function () {
		wp_enqueue_style( 'theme-style', CITCOM_THEME_URI . '/build/theme.css', array(), citcom_asset_version( 'theme' ), 'all' );
	}
);

add_action(
	'wp_enqueue_scripts',
	function () {
		global $wp_query;

		// jQuery from the CDN, in the footer, as before (removal tracked in docs/jquery-usage.md).
		wp_deregister_script( 'jquery' );
		wp_enqueue_script( 'jquery', 'https://code.jquery.com/jquery-3.7.1.min.js', array(), null, array( 'in_footer' => true ) ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion

		wp_enqueue_script( 'outdated', CITCOM_THEME_URI . '/assets/vendor/outdatedbrowser.min.js', array( 'jquery' ), null, array( 'in_footer' => true, 'strategy' => 'defer' ) ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion

		wp_enqueue_script(
			'theme-script-main',
			CITCOM_THEME_URI . '/build/theme.js',
			array( 'jquery' ),
			citcom_asset_version( 'theme' ),
			array( 'in_footer' => true, 'strategy' => 'defer' )
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

// Font Awesome kit, async in the footer.
add_action(
	'wp_footer',
	function () {
		echo '<script src="https://kit.fontawesome.com/4a7ba1b0a5.js" crossorigin="anonymous" async></script>';
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
	add_filter(
		'style_loader_tag',
		function ( $tag ) {
			if ( false !== strpos( $tag, 'build/theme.css' ) ) {
				return str_replace( " rel='stylesheet'", ' rel="preload" as="style"', $tag ) . $tag;
			}
			return str_replace( " rel='stylesheet'", ' rel="preload" as="style" onload="this.onload=null;this.rel=\'stylesheet\'"', $tag );
		}
	);
}
