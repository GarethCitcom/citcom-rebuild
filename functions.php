<?php
/**
 * Citcom Rebuild theme bootstrap.
 *
 * Everything lives in inc/. Blocks register themselves from blocks/<name>/block.json
 * via inc/blocks.php. Compiled assets are read from build/ (see webpack.config.js).
 *
 * @package citcom
 */

defined( 'ABSPATH' ) || exit;

define( 'CITCOM_THEME_DIR', get_stylesheet_directory() );
define( 'CITCOM_THEME_URI', get_stylesheet_directory_uri() );
define( 'CITCOM_THEME_VERSION', wp_get_theme( get_stylesheet() )->get( 'Version' ) );

foreach ( array( 'helpers', 'class-citcom-nav-walker', 'setup', 'seo', 'assets', 'cpt', 'acf', 'blocks', 'shortcodes', 'diner', 'mailchimp', 'forms', 'forms-fields', 'ajax', 'blocks-convert' ) as $citcom_inc ) {
	require CITCOM_THEME_DIR . '/inc/' . $citcom_inc . '.php';
}
unset( $citcom_inc );

// The Phase 3 migration command (docs/03-phase3-brief.md) only exists on the command line.
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require CITCOM_THEME_DIR . '/inc/migrate.php';
	require CITCOM_THEME_DIR . '/inc/cli-migrate.php';
}
