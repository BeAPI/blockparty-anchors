<?php

namespace Blockparty\Anchors;

class Anchors {

	private const CACHE_GROUP = 'blockparty-anchors-list';

	/**
	 * Tracks the next anchor index to resolve during frontend rendering.
	 *
	 * @var array<int, int>
	 */
	private static array $anchor_render_indexes = [];

	/**
	 * Register plugin hooks.
	 *
	 * @return void
	 */
	public static function register_hooks(): void {
		add_action( 'clean_post_cache', [ self::class, 'clear_post_cache' ] );
		add_action( 'wp', [ self::class, 'reset_anchor_render_indexes' ] );
		add_filter( 'the_content', [ self::class, 'maybe_reset_anchor_render_indexes_on_the_content' ], 0 );
	}

	/**
	 * Delete cached anchors when a post or template changes.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return void
	 */
	public static function clear_post_cache( int $post_id ): void {
		$generation = wp_cache_get( 'generation', self::CACHE_GROUP );
		$generation = is_numeric( $generation ) ? ( (int) $generation ) + 1 : 1;
		wp_cache_set( 'generation', $generation, self::CACHE_GROUP );

		unset( self::$anchor_render_indexes[ $post_id ] );
	}

	/**
	 * Reset render indexes at the start of the main query lifecycle.
	 *
	 * @return void
	 */
	public static function reset_anchor_render_indexes(): void {
		self::$anchor_render_indexes = [];
	}

	/**
	 * Reset render indexes before classic the_content rendering passes.
	 *
	 * Skips nested the_content calls from blocks such as core/post-content inside
	 * an FSE template, so template + post anchors keep a single document order.
	 *
	 * @param string $content Post content.
	 *
	 * @return string Unchanged content.
	 */
	public static function maybe_reset_anchor_render_indexes_on_the_content( string $content ): string {
		$current_filters = $GLOBALS['wp_current_filter'] ?? [];

		if ( in_array( 'render_block', $current_filters, true ) ) {
			return $content;
		}

		$post = get_post();

		if ( $post instanceof \WP_Post ) {
			unset( self::$anchor_render_indexes[ $post->ID ] );
		}

		return $content;
	}

	/**
	 * Resolve a HTML-friendly slug from block attributes.
	 *
	 * @param array $attributes Block attributes.
	 *
	 * @return string
	 */
	public static function get_slug_from_attributes( array $attributes ): string {
		if ( ! empty( $attributes['slug'] ) ) {
			return (string) $attributes['slug'];
		}

		if ( empty( $attributes['title'] ) ) {
			return '';
		}

		return sanitize_title( $attributes['title'] );
	}

	/**
	 * Ensure a slug is unique among anchors already assigned on the page.
	 *
	 * @param string              $slug           Candidate slug.
	 * @param array<string, true> $assigned_slugs Slugs already assigned.
	 *
	 * @return string
	 */
	private static function make_unique_slug( string $slug, array &$assigned_slugs ): string {
		if ( empty( $slug ) ) {
			return '';
		}

		$unique_slug = $slug;
		$suffix      = 2;

		while ( isset( $assigned_slugs[ $unique_slug ] ) ) {
			$unique_slug = $slug . '-' . $suffix;
			++$suffix;
		}

		$assigned_slugs[ $unique_slug ] = true;

		return $unique_slug;
	}

	/**
	 * Build a cache key for the current post and active template.
	 *
	 * @param \WP_Post $post Post object.
	 *
	 * @return string
	 */
	private static function get_cache_key( \WP_Post $post ): string {
		$generation = wp_cache_get( 'generation', self::CACHE_GROUP );

		if ( ! is_numeric( $generation ) ) {
			$generation = 1;
			wp_cache_set( 'generation', $generation, self::CACHE_GROUP );
		}

		$template_segment = '';
		global $_wp_current_template_id;

		if ( ! empty( $_wp_current_template_id ) ) {
			$template_segment = (string) $_wp_current_template_id;
		}

		return $post->ID . ':' . $template_segment . ':' . (int) $generation;
	}

	/**
	 * Resolve the block markup that represents the full frontend document.
	 *
	 * Prefer the active FSE template when available, otherwise the post content.
	 *
	 * @param \WP_Post $post Post object.
	 *
	 * @return string
	 */
	private static function get_document_content( \WP_Post $post ): string {
		if ( in_array( $post->post_type, [ 'wp_template', 'wp_template_part' ], true ) ) {
			return (string) $post->post_content;
		}

		$template_content = self::get_active_template_content( $post );

		if ( '' !== $template_content ) {
			return $template_content;
		}

		return (string) $post->post_content;
	}

	/**
	 * Resolve the active block template content for the current request.
	 *
	 * @param \WP_Post $post Queried post used as fallback to resolve a custom template.
	 *
	 * @return string
	 */
	private static function get_active_template_content( \WP_Post $post ): string {
		global $_wp_current_template_content;

		if ( is_string( $_wp_current_template_content ) && '' !== $_wp_current_template_content ) {
			return $_wp_current_template_content;
		}

		if ( ! function_exists( 'wp_is_block_theme' ) || ! wp_is_block_theme() ) {
			return '';
		}

		$slug = get_page_template_slug( $post );

		if ( empty( $slug ) || ! function_exists( 'get_block_template' ) ) {
			return '';
		}

		// Custom page templates are stored as "{theme}//{slug}".
		$template = get_block_template( get_stylesheet() . '//' . $slug );

		if ( $template && ! empty( $template->content ) ) {
			return (string) $template->content;
		}

		return '';
	}

	/**
	 * Resolve template part markup from a core/template-part block.
	 *
	 * @param array $attributes Template part block attributes.
	 *
	 * @return string
	 */
	private static function get_template_part_content( array $attributes ): string {
		$slug = $attributes['slug'] ?? '';

		if ( '' === $slug || ! function_exists( 'get_block_template' ) ) {
			return '';
		}

		$theme    = $attributes['theme'] ?? get_stylesheet();
		$template = get_block_template( $theme . '//' . $slug, 'wp_template_part' );

		if ( $template && ! empty( $template->content ) ) {
			return (string) $template->content;
		}

		return '';
	}

	/**
	 * Load every anchor block from the frontend document in order.
	 *
	 * @param \WP_Post $post Post object.
	 *
	 * @return array<int, array{slug: string, title: string}> Ordered anchors.
	 */
	private static function load_anchors_from_post( \WP_Post $post ): array {
		$cache_key = self::get_cache_key( $post );
		$found     = false;
		$data      = wp_cache_get( $cache_key, self::CACHE_GROUP, false, $found );

		if ( $found && is_array( $data ) ) {
			return $data;
		}

		$anchors_data   = [];
		$assigned_slugs = [];
		self::collect_from_blocks(
			parse_blocks( self::get_document_content( $post ) ),
			$anchors_data,
			$assigned_slugs,
			$post
		);

		wp_cache_set( $cache_key, $anchors_data, self::CACHE_GROUP );

		return $anchors_data;
	}

	/**
	 * Return anchors suitable for the quick-access navigation list.
	 *
	 * @param \WP_Post $post Post object.
	 *
	 * @return array<int, array{slug: string, title: string}> Ordered anchors.
	 */
	public static function get_from_post( \WP_Post $post ): array {
		$anchors = self::load_anchors_from_post( $post );

		return array_values(
			array_filter(
				$anchors,
				static function ( array $anchor ): bool {
					return ! empty( $anchor['title'] ) && ! empty( $anchor['slug'] );
				}
			)
		);
	}

	/**
	 * Resolve the unique slug for the next anchor block rendered on the frontend.
	 *
	 * Anchor blocks are rendered in document order, so a per-post render index can
	 * map each instance to the slug computed during collection.
	 *
	 * @param \WP_Post $post       Post object.
	 * @param array    $attributes Block attributes.
	 *
	 * @return string
	 */
	public static function get_unique_slug_for_anchor_render( \WP_Post $post, array $attributes ): string {
		$anchors = self::load_anchors_from_post( $post );
		$post_id = $post->ID;

		if ( ! isset( self::$anchor_render_indexes[ $post_id ] ) ) {
			self::$anchor_render_indexes[ $post_id ] = 0;
		}

		$index = self::$anchor_render_indexes[ $post_id ]++;

		if ( isset( $anchors[ $index ] ) ) {
			return $anchors[ $index ]['slug'];
		}

		return self::get_slug_from_attributes( $attributes );
	}

	/**
	 * Recursively collect anchors from parsed blocks.
	 *
	 * Expands FSE structural blocks so anchors in templates, template parts, and
	 * post content are discovered in the same order as frontend rendering.
	 *
	 * @param array               $blocks         Parsed blocks.
	 * @param array<int, array{slug: string, title: string}> $anchors_data Collected anchors.
	 * @param array<string, true> $assigned_slugs Slugs already assigned.
	 * @param \WP_Post            $post           Queried post for post-content expansion.
	 *
	 * @return void
	 */
	private static function collect_from_blocks( array $blocks, array &$anchors_data, array &$assigned_slugs, \WP_Post $post ): void {
		foreach ( $blocks as $block ) {
			$name = $block['blockName'] ?? '';

			if ( 'blockparty/anchor' === $name ) {
				$attrs = wp_parse_args(
					$block['attrs'] ?? [],
					[
						'title' => '',
						'slug'  => '',
					]
				);

				$slug = self::get_slug_from_attributes( $attrs );

				$anchors_data[] = [
					'slug'  => ! empty( $slug ) ? self::make_unique_slug( $slug, $assigned_slugs ) : '',
					'title' => (string) $attrs['title'],
				];
			}

			if ( 'core/post-content' === $name ) {
				// Avoid recursive expansion when the "post" is itself a template.
				if ( ! in_array( $post->post_type, [ 'wp_template', 'wp_template_part' ], true ) ) {
					self::collect_from_blocks(
						parse_blocks( (string) $post->post_content ),
						$anchors_data,
						$assigned_slugs,
						$post
					);
				}
				continue;
			}

			if ( 'core/template-part' === $name ) {
				$part_content = self::get_template_part_content( $block['attrs'] ?? [] );

				if ( '' !== $part_content ) {
					self::collect_from_blocks(
						parse_blocks( $part_content ),
						$anchors_data,
						$assigned_slugs,
						$post
					);
				}
				continue;
			}

			if ( ! empty( $block['innerBlocks'] ) ) {
				self::collect_from_blocks( $block['innerBlocks'], $anchors_data, $assigned_slugs, $post );
			}
		}
	}
}
