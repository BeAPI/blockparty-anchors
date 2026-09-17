<?php
/**
 * WP-CLI command to migrate beapi/anchor content.
 *
 * @package Blockparty\Anchors
 */

namespace Blockparty\Anchors\Cli;

use Blockparty\Anchors\Migration\AnchorBlockMigrator;
use WP_CLI;
use WP_CLI_Command;
use WP_Query;

/**
 * Blockparty Anchors WP-CLI commands.
 */
class MigrateFromAnchorBlockCommand extends WP_CLI_Command {

	/**
	 * Migrate beapi/anchor and beapi/anchor-list blocks to blockparty/anchor and blockparty/anchors-list.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Report changes without updating the database.
	 *
	 * [--post-type=<post-types>]
	 * : Comma-separated list of post types. Default: any public post type that shows in the REST API, plus wp_block.
	 *
	 * [--posts-per-page=<number>]
	 * : Batch size. Default: 100.
	 *
	 * ## EXAMPLES
	 *
	 *     wp blockparty-anchors migrate-from-anchor-block --dry-run
	 *     wp blockparty-anchors migrate-from-anchor-block --post-type=post,page
	 *     wp blockparty-anchors migrate-from-anchor-block --url=https://example.com/
	 *
	 * @subcommand migrate-from-anchor-block
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 * @return void
	 */
	public function __invoke( $args, $assoc_args ): void {
		$dry_run        = (bool) WP_CLI\Utils\get_flag_value( $assoc_args, 'dry-run', false );
		$posts_per_page = (int) ( $assoc_args['posts-per-page'] ?? 100 );
		$post_types     = $this->resolve_post_types( $assoc_args['post-type'] ?? '' );

		if ( empty( $post_types ) ) {
			WP_CLI::error( 'No post types to migrate.' );
		}

		WP_CLI::log(
			sprintf(
				'Migrating site %1$d (%2$s)%3$s — post types: %4$s',
				get_current_blog_id(),
				home_url( '/' ),
				$dry_run ? ' [dry-run]' : '',
				implode( ', ', $post_types )
			)
		);

		$migrator       = new AnchorBlockMigrator();
		$posts_updated  = 0;
		$posts_scanned  = 0;
		$page           = 1;
		$max_pages      = 1;
		$total_migrated = 0;
		$total_skipped  = 0;

		while ( $page <= $max_pages ) {
			$query = new WP_Query(
				[
					'post_type'              => $post_types,
					'post_status'            => 'any',
					'posts_per_page'         => $posts_per_page,
					'paged'                  => $page,
					'orderby'                => 'ID',
					'order'                  => 'ASC',
					'ignore_sticky_posts'    => true,
					'no_found_rows'          => false,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				]
			);

			$max_pages = (int) $query->max_num_pages;

			if ( ! $query->have_posts() ) {
				break;
			}

			foreach ( $query->posts as $post ) {
				++$posts_scanned;

				if ( ! is_string( $post->post_content ) || '' === $post->post_content ) {
					continue;
				}

				if ( false === strpos( $post->post_content, 'beapi/anchor' ) ) {
					continue;
				}

				$before_migrated = $migrator->migrated;
				$before_skipped  = $migrator->skipped;
				$new_content     = $migrator->migrate_content( $post->post_content );

				$delta_migrated  = $migrator->migrated - $before_migrated;
				$delta_skipped   = $migrator->skipped - $before_skipped;
				$total_migrated += $delta_migrated;
				$total_skipped  += $delta_skipped;

				if ( null === $new_content ) {
					continue;
				}

				++$posts_updated;

				WP_CLI::log(
					sprintf(
						'%1$s post %2$d (%3$s) — blocks migrated: %4$d, skipped: %5$d',
						$dry_run ? '[dry-run]' : '[update]',
						(int) $post->ID,
						$post->post_type,
						$delta_migrated,
						$delta_skipped
					)
				);

				if ( ! $dry_run ) {
					$this->persist_post_content( (int) $post->ID, $post->post_type, $new_content, $delta_migrated, $delta_skipped );
				}
			}

			++$page;
			wp_reset_postdata();
		}

		WP_CLI::success(
			sprintf(
				'Done. Posts scanned: %1$d, posts %2$s: %3$d, anchor blocks migrated: %4$d, skipped: %5$d.',
				$posts_scanned,
				$dry_run ? 'that would update' : 'updated',
				$posts_updated,
				$total_migrated,
				$total_skipped
			)
		);
	}

	/**
	 * Save migrated content, with a content-only fallback for orphaned page templates.
	 *
	 * @param int    $post_id         Post ID.
	 * @param string $post_type       Post type.
	 * @param string $new_content     Unslashed post content.
	 * @param int    $delta_migrated  Blocks migrated for this post.
	 * @param int    $delta_skipped   Blocks skipped for this post.
	 * @return void
	 */
	private function persist_post_content( int $post_id, string $post_type, string $new_content, int $delta_migrated, int $delta_skipped ): void {
		// wp_update_post() expects slashed data; without wp_slash(),
		// block comment escapes like \u002d (for "--") become bare "u002d".
		$updated = wp_update_post(
			[
				'ID'           => $post_id,
				'post_content' => wp_slash( $new_content ),
			],
			true
		);

		if ( ! is_wp_error( $updated ) ) {
			return;
		}

		if ( 'invalid_page_template' === $updated->get_error_code() ) {
			$fallback_ok = $this->update_post_content_fallback( $post_id, $new_content );

			if ( $fallback_ok ) {
				WP_CLI::log(
					sprintf(
						'[update-fallback] post %1$d (%2$s) — blocks migrated: %3$d, skipped: %4$d',
						$post_id,
						$post_type,
						$delta_migrated,
						$delta_skipped
					)
				);
				return;
			}
		}

		WP_CLI::warning(
			sprintf(
				'Failed to update post %1$d: %2$s',
				$post_id,
				$updated->get_error_message()
			)
		);
	}

	/**
	 * Update post_content only, bypassing page template validation.
	 *
	 * @param int    $post_id     Post ID.
	 * @param string $new_content Unslashed post content.
	 * @return bool
	 */
	private function update_post_content_fallback( int $post_id, string $new_content ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Content-only fallback when wp_update_post() rejects an orphaned _wp_page_template.
		$result = $wpdb->update(
			$wpdb->posts,
			[
				'post_content' => $new_content,
			],
			[
				'ID' => $post_id,
			],
			[ '%s' ],
			[ '%d' ]
		);

		if ( false === $result ) {
			return false;
		}

		clean_post_cache( $post_id );

		return true;
	}

	/**
	 * Resolve target post types.
	 *
	 * @param string $raw Comma-separated post types from CLI.
	 * @return string[]
	 */
	private function resolve_post_types( string $raw ): array {
		if ( '' !== trim( $raw ) ) {
			$types = array_filter( array_map( 'trim', explode( ',', $raw ) ) );

			return array_values( $types );
		}

		$types = get_post_types(
			[
				'public'       => true,
				'show_in_rest' => true,
			],
			'names'
		);

		$types[] = 'wp_block';
		$types   = array_unique( array_values( $types ) );

		return $types;
	}
}
