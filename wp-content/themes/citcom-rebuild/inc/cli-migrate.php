<?php
/**
 * `wp citcom migrate`: the Phase 3 content migration (inc/migrate.php).
 *
 * Steps, in the order the staging run uses them (docs/03-phase3-brief.md):
 *
 *   wp citcom migrate status
 *   wp citcom migrate acf-ui            # deactivate the ACF UI entries the theme replaces
 *   wp citcom migrate retire            # bin the retired pages
 *   wp citcom migrate run --dry-run     # report, change nothing
 *   wp citcom migrate run               # write the block content
 *   wp citcom migrate widgets           # rename the sidebar blocks
 *   wp citcom migrate rollback          # undo run (post_content only)
 *   wp citcom migrate cleanup           # after sign-off: old caches and raw rows
 *
 * Every step changes nothing with --dry-run.
 *
 * @package citcom
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

/**
 * Moves the old ACF flexible content into citcom/* blocks.
 */
class Citcom_Migrate_Command {

	/**
	 * Prepare WordPress for writes from the command line: no kses on the
	 * block markup (it would strip the embed code inside block attributes)
	 * and no rel attributes added to links the editors wrote.
	 *
	 * @return void
	 */
	private function prepare_writes(): void {
		kses_remove_filters();
		remove_filter( 'content_save_pre', 'wp_targeted_link_rel' );
	}

	/**
	 * The posts a command works on.
	 *
	 * @param array<string,mixed> $assoc_args Options.
	 * @return int[]
	 */
	private function posts( array $assoc_args ): array {
		if ( ! empty( $assoc_args['post'] ) ) {
			return array_map( 'intval', array_filter( explode( ',', (string) $assoc_args['post'] ), 'is_numeric' ) );
		}
		$types = ! empty( $assoc_args['type'] ) ? array_map( 'trim', explode( ',', (string) $assoc_args['type'] ) ) : CITCOM_MIGRATE_POST_TYPES;
		return citcom_migrate_candidates( $types );
	}

	/**
	 * How many posts still carry flexible content rows, and how many are migrated.
	 *
	 * ## EXAMPLES
	 *
	 *     wp citcom migrate status
	 *
	 * @param string[]            $args       Positional arguments.
	 * @param array<string,mixed> $assoc_args Options.
	 * @return void
	 */
	public function status( array $args, array $assoc_args ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- the WP-CLI signature.
		$rows = array();
		foreach ( CITCOM_MIGRATE_POST_TYPES as $type ) {
			$ids      = citcom_migrate_candidates( array( $type ) );
			$migrated = 0;
			$retired  = 0;
			foreach ( $ids as $id ) {
				if ( isset( CITCOM_MIGRATE_RETIRED[ $id ] ) ) {
					++$retired;
				} elseif ( metadata_exists( 'post', $id, '_citcom_migrated' ) ) {
					++$migrated;
				}
			}
			$rows[] = array(
				'type'     => $type,
				'posts'    => count( $ids ),
				'migrated' => $migrated,
				'retired'  => $retired,
				'pending'  => count( $ids ) - $migrated - $retired,
			);
		}
		WP_CLI\Utils\format_items( 'table', $rows, array( 'type', 'posts', 'migrated', 'retired', 'pending' ) );
		$widgets = citcom_migrate_widgets( true );
		WP_CLI::log( sprintf( 'Sidebar widgets: %d, %d still named acf/*.', $widgets['widgets'], $widgets['changed'] ) );
	}

	/**
	 * Write the block content of every post that has flexible content rows.
	 *
	 * ## OPTIONS
	 *
	 * [--post=<ids>]
	 * : Only these post ids, comma separated.
	 *
	 * [--type=<types>]
	 * : Only these post types, comma separated. Default: all five.
	 *
	 * [--dry-run]
	 * : Build and report, write nothing.
	 *
	 * [--force]
	 * : Migrate again posts already migrated (the source rows are untouched by a run).
	 *
	 * [--show]
	 * : Print the generated block content of each post.
	 *
	 * [--report=<file>]
	 * : Also write the report as JSON to this file.
	 *
	 * ## EXAMPLES
	 *
	 *     wp citcom migrate run --dry-run
	 *     wp citcom migrate run --post=17 --dry-run --show
	 *     wp citcom migrate run --type=page
	 *
	 * @param string[]            $args       Positional arguments.
	 * @param array<string,mixed> $assoc_args Options.
	 * @return void
	 */
	public function run( array $args, array $assoc_args ): void {
		$dry_run = ! empty( $assoc_args['dry-run'] );
		$force   = ! empty( $assoc_args['force'] );
		$show    = ! empty( $assoc_args['show'] );
		if ( ! $dry_run ) {
			$this->prepare_writes();
		}
		$ids  = $this->posts( $assoc_args );
		$rows = array();
		foreach ( $ids as $id ) {
			if ( ! $force && ! $dry_run && metadata_exists( 'post', $id, '_citcom_migrated' ) ) {
				$rows[] = array(
					'id'        => $id,
					'type'      => get_post_type( $id ),
					'title'     => get_the_title( $id ),
					'rows'      => '',
					'converted' => '',
					'disabled'  => '',
					'status'    => 'already migrated',
					'warnings'  => '',
				);
				continue;
			}
			$report = citcom_migrate_post( $id, $dry_run );
			if ( $show ) {
				WP_CLI::log( "\n===== {$report['id']} {$report['title']} ({$report['type']})\n" . $report['content'] . "\n" );
			}
			$report['warnings'] = implode( '; ', $report['warnings'] );
			unset( $report['content'] );
			$rows[] = $report;
		}
		WP_CLI\Utils\format_items( 'table', $rows, array( 'id', 'type', 'title', 'rows', 'converted', 'disabled', 'status', 'warnings' ) );
		if ( ! empty( $assoc_args['report'] ) ) {
			file_put_contents( (string) $assoc_args['report'], wp_json_encode( $rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- a report file chosen on the command line.
			WP_CLI::log( 'Report written to ' . $assoc_args['report'] );
		}
		$done = count( array_filter( $rows, static fn( $r ) => 'migrated' === $r['status'] ) );
		$warn = count( array_filter( $rows, static fn( $r ) => '' !== preg_replace( '/note: [^;]*(; )?/', '', (string) $r['warnings'] ) ) );
		WP_CLI::success( sprintf( '%d posts considered, %d %s, %d with warnings.', count( $rows ), $dry_run ? count( array_filter( $rows, static fn( $r ) => 'dry run' === $r['status'] ) ) : $done, $dry_run ? 'buildable' : 'migrated', $warn ) );
	}

	/**
	 * Rename the old acf/* sidebar blocks in the block widgets to citcom/*.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Report only.
	 *
	 * @param string[]            $args       Positional arguments.
	 * @param array<string,mixed> $assoc_args Options.
	 * @return void
	 */
	public function widgets( array $args, array $assoc_args ): void {
		$dry_run = ! empty( $assoc_args['dry-run'] );
		$result  = citcom_migrate_widgets( $dry_run );
		WP_CLI::success( sprintf( '%d block widgets, %d %s.', $result['widgets'], $result['changed'], $dry_run ? 'to rename' : 'renamed' ) );
	}

	/**
	 * Deactivate the ACF UI post types, taxonomy, options page and the field
	 * groups the theme does not ship as JSON (they are registered in PHP or
	 * replaced by blocks). Nothing is deleted.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Report only.
	 *
	 * [--undo]
	 * : Reactivate what an earlier run deactivated (before going back to the old theme).
	 *
	 * @subcommand acf-ui
	 *
	 * @param string[]            $args       Positional arguments.
	 * @param array<string,mixed> $assoc_args Options.
	 * @return void
	 */
	public function acf_ui( array $args, array $assoc_args ): void {
		if ( ! empty( $assoc_args['undo'] ) ) {
			$ids = citcom_migrate_acf_ui_undo();
			WP_CLI::success( sprintf( '%d ACF UI entries reactivated.', count( $ids ) ) );
			return;
		}
		$rows = citcom_migrate_acf_ui( ! empty( $assoc_args['dry-run'] ) );
		WP_CLI\Utils\format_items( 'table', $rows, array( 'id', 'type', 'title', 'key', 'action' ) );
	}

	/**
	 * Bin the pages retired in the review.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Report only.
	 *
	 * @param string[]            $args       Positional arguments.
	 * @param array<string,mixed> $assoc_args Options.
	 * @return void
	 */
	public function retire( array $args, array $assoc_args ): void {
		$rows = citcom_migrate_retire( ! empty( $assoc_args['dry-run'] ) );
		WP_CLI\Utils\format_items( 'table', $rows, array( 'id', 'slug', 'action' ) );
	}

	/**
	 * Put the old post_content back on migrated posts. The ACF values were
	 * never changed by a run, so the old theme reads them as before.
	 *
	 * ## OPTIONS
	 *
	 * [--post=<ids>]
	 * : Only these post ids, comma separated.
	 *
	 * @param string[]            $args       Positional arguments.
	 * @param array<string,mixed> $assoc_args Options.
	 * @return void
	 */
	public function rollback( array $args, array $assoc_args ): void {
		$this->prepare_writes();
		$rows = array();
		foreach ( $this->posts( $assoc_args ) as $id ) {
			$rows[] = array(
				'id'     => $id,
				'title'  => get_the_title( $id ),
				'status' => citcom_migrate_rollback_post( $id ),
			);
		}
		WP_CLI\Utils\format_items( 'table', $rows, array( 'id', 'title', 'status' ) );
	}

	/**
	 * After sign-off: delete the old theme's acfAllObjects_* option caches,
	 * remove the raw flexible content rows from each post's ACF values and
	 * drop the rollback copies.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Report only.
	 *
	 * [--yes]
	 * : Do not ask for confirmation.
	 *
	 * @param string[]            $args       Positional arguments.
	 * @param array<string,mixed> $assoc_args Options.
	 * @return void
	 */
	public function cleanup( array $args, array $assoc_args ): void {
		$dry_run = ! empty( $assoc_args['dry-run'] );
		if ( ! $dry_run ) {
			WP_CLI::confirm( 'This removes the old flexible content values and the rollback copies. Continue?', $assoc_args );
		}
		$result = citcom_migrate_cleanup( $dry_run );
		WP_CLI::success( sprintf( '%d acfAllObjects options, %d posts with raw rows, %d rollback copies %s.', $result['options'], $result['posts'], $result['legacy_copies'], $dry_run ? 'found' : 'removed' ) );
	}
}

WP_CLI::add_command( 'citcom migrate', 'Citcom_Migrate_Command' );
