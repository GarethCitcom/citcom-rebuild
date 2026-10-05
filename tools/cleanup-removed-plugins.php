<?php
/**
 * Removes the data left behind by plugins that are no longer installed:
 * their tables, posts, post and user meta, options and scheduled events.
 * Written for the staging clean-up of 2026-10-05 (docs/03-phase3-brief.md)
 * and kept for the live cutover, where the same leftovers exist.
 *
 * Every pattern below belongs to a plugin that had already been deleted from
 * the site. Check that is still true where you run it, take a database export
 * first, and pass the site URL so it cannot run against the wrong site:
 *
 *   wp db export ~/pre-cleanup.sql
 *   wp --skip-themes --skip-plugins eval-file tools/cleanup-removed-plugins.php https://example.com
 *
 * Not touched here: the acfAllObjects_* caches and the raw flexible content
 * rows (`wp citcom migrate cleanup`), revisions, the bin, and anything owned
 * by a plugin that is still installed.
 *
 * @package citcom
 */

if ( ! defined( 'WP_CLI' ) ) {
	exit;
}

global $wpdb;
$out = array();

if ( empty( $args[0] ) || untrailingslashit( (string) $args[0] ) !== untrailingslashit( (string) get_option( 'siteurl' ) ) ) {
	WP_CLI::error( 'Pass the URL of this site as the first argument. This site is ' . get_option( 'siteurl' ) . '.' );
}

// 1. Tables whose plugin is gone.
$tables  = array(
	'actionscheduler_actions',
	'actionscheduler_claims',
	'actionscheduler_groups',
	'actionscheduler_logs', // Forminator's queue (every row was a forminator_* hook).
	'admin_columns',
	'desktop_mode_file_placements',
	'desktop_mode_file_tombstones',
	'desktop_mode_folders',
	'desktop_mode_folder_shares',
	'desktop_mode_share_user_decisions',
	'frmt_form_entry',
	'frmt_form_entry_meta',
	'frmt_form_reports',
	'frmt_form_views',
	'metpxw_smartcrawl_redirects',
	'smush_dir_images',
);
$dropped = 0;
foreach ( $tables as $t ) {
	$name = $wpdb->prefix . $t;
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $name ) ) === $name ) {
		$wpdb->query( $wpdb->prepare( 'DROP TABLE %i', $name ) );
		++$dropped;
	}
}
$out[] = "tables dropped: $dropped";

// 2. Forminator's form and quiz posts, and the customiser drafts in the bin.
$ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type IN ('forminator_forms','forminator_quizzes','forminator_polls') OR ( post_type = 'customize_changeset' AND post_status = 'trash' )" );
foreach ( $ids as $id ) {
	wp_delete_post( (int) $id, true );
}
$out[] = 'posts deleted: ' . count( $ids );

// 3. Post meta written by plugins that are gone, and five loose custom fields no field group defines any more.
$like = array( 'wp-smush-%', 'wp-smpro-%', '\_desktop\_mode\_%', 'ampforwp%', '\_yoast\_wpseo\_%', '\_coblocks\_%', 'onesignal\_%', 'forminator\_%' );
$keys = array(
	'_acf_to_content',
	'use_ampforwp_page_builder',
	'amp-page-builder',
	'inline_featured_image',
	'_product_image_gallery',
	'_primary_term_category',
	'header_image',
	'_header_image',
	'article_title',
	'_article_title',
	'side_image',
	'_side_image',
	'case_study_blog',
	'_case_study_blog',
	'page_sections',
	'_page_sections',
);
$n    = 0;
foreach ( $like as $pattern ) {
	$n += (int) $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE %s", $pattern ) );
}
foreach ( $keys as $key ) {
	// The loose custom fields only ever sat on blog posts; leave any other use of the name alone.
	$n += (int) $wpdb->query( $wpdb->prepare( "DELETE pm FROM {$wpdb->postmeta} pm JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE pm.meta_key = %s AND p.post_type IN ('post','attachment','page','revision')", $key ) );
}
$out[] = "post meta rows deleted: $n";

// 4. User meta from the same plugins.
$n = 0;
foreach ( array( '\_desktop\_mode\_%', 'desktop\_mode\_%', 'hustle\_%', 'wd\_2fa\_%', 'wpdef\_%', '\_revisionary\_%', 'ac\_preferences\_%', 'wp\_ac\_preferences\_%' ) as $pattern ) {
	$n += (int) $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s", $pattern ) );
}
$out[] = "user meta rows deleted: $n";

// 5. Options. The acfAllObjects_* caches are not touched here: they go with `wp citcom migrate cleanup`.
$patterns = array(
	// Defender.
	'wd\_%',
	'wp\_defender%',
	'wp-defender%',
	'wpdef%',
	'defender\_%',
	'wpdefender%',
	// Smush, Hummingbird, Branda, Hustle, Forminator.
	'wp-smush%',
	'wp\_smush%',
	'wphb%',
	'ub\_%',
	'ultimatebranding%',
	'branda\_%',
	'hustle%',
	'forminator%',
	// WP All Import, The SEO Framework, Revisionary, Admin Columns.
	'wp-all-import%',
	'\_wpallimport%',
	'wpallimport%',
	'PMXI%',
	'PMGI%',
	'pmxi%',
	'wp\_all\_import%',
	'autodescription%',
	'the\_seo\_framework%',
	'tsf%',
	'rvy\_%',
	'revisionary\_%',
	'cpac\_%',
	'ac\_%',
	'\_ac\_colors%',
	'ac-deprecated%',
	// UpdraftPlus, Shipper, WP Migrate, SAML, OpenStation, Troy, the sweepstake, BlogVault.
	'updraft%',
	'shipper%',
	'wpmdb%',
	'mo\_%',
	'MO\_%',
	'saml\_%',
	'mosaml%',
	'\_desktop\_mode%',
	'desktop\_mode%',
	'troy\_client%',
	'wc2026%',
	'bv%',
	// Action Scheduler's own flags, so a future plugin that brings it creates its tables afresh.
	'schema-ActionScheduler%',
	'action\_scheduler\_%',
);
$names    = array(
	'wdfcore',
	'wdfcontent',
	'hardener_settings',
	'dir_smush_stats',
	'skip-smush-setup',
	'as_has_wp_comment_logs',
	'widget_hustle_module_widget',
	'widget_inc_opt_widget',
	'widget_forminator_widget',
	'widget_saml_login_widget',
	'connectors_ai_anthropic_api_key',
	'blc_email_storage',
	'duplicate_page_options',
	'mk_fm_close_fm_help_c',
	'theme_mods_twentytwentyfour',
	'wp_google_login_settings',
);
$found    = array();
foreach ( $patterns as $pattern ) {
	$found = array_merge( $found, $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE BINARY %s", $pattern ) ) );
}
$found = array_unique( array_merge( $found, $names ) );
// Belt and braces: never the options of anything still installed.
$protect = '/^(wd_un|wdp_un|wpmudev|wds|wp_smartcrawl|acf|options_|_options_|jetpack|jp_|fs_|fluent|trusted_ip_header)/';
$n       = 0;
foreach ( $found as $name ) {
	if ( preg_match( $protect, $name ) ) {
		continue;
	}
	if ( delete_option( $name ) ) {
		++$n;
	}
}
$out[] = "options deleted: $n";

// 6. Scheduled events of plugins that are gone.
$n = 0;
foreach ( (array) _get_cron_array() as $timestamp => $hooks ) {
	foreach ( array_keys( (array) $hooks ) as $hook ) {
		if ( preg_match( '/^(desktop_mode_|wpdef_|forminator|hustle|wphb|wp_smush|smush|updraft|shipper|wpmdb|troy_|wc2026)/', $hook ) ) {
			$n += (int) wp_unschedule_hook( $hook );
		}
	}
}
$out[] = "scheduled events removed: $n";

wp_cache_flush();
WP_CLI::log( implode( "\n", $out ) );
