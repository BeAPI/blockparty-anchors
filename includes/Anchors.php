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
	}

	/**
	 * Delete cached anchors for a post.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return void
	 */
	public static function clear_post_cache( int $post_id ): void {
		wp_cache_delete( $post_id, self::CACHE_GROUP );
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
	 * Return anchor blocks from post content.
	 *
	 * @param \WP_Post $post Post object.
	 *
	 * @return array<int, array{slug: string, title: string}> Ordered anchors.
	 */
	public static function get_from_post( \WP_Post $post ): array {
		$found = false;
		$data  = wp_cache_get( $post->ID, self::CACHE_GROUP, false, $found );

		if ( $found && is_array( $data ) ) {
			return $data;
		}

		$anchors_data   = [];
		$assigned_slugs = [];
		self::collect_from_blocks( parse_blocks( $post->post_content ), $anchors_data, $assigned_slugs );

		wp_cache_set( $post->ID, $anchors_data, self::CACHE_GROUP );

		return $anchors_data;
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
		$anchors = self::get_from_post( $post );
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
	 * @param array              $blocks         Parsed blocks.
	 * @param array<int, array{slug: string, title: string}> $anchors_data   Collected anchors.
	 * @param array<string, true> $assigned_slugs Slugs already assigned.
	 *
	 * @return void
	 */
	private static function collect_from_blocks( array $blocks, array &$anchors_data, array &$assigned_slugs ): void {
		foreach ( $blocks as $block ) {
			if ( 'blockparty/anchor' === ( $block['blockName'] ?? '' ) ) {
				$attrs = wp_parse_args(
					$block['attrs'] ?? [],
					[
						'title' => '',
						'slug'  => '',
					]
				);

				$slug = self::get_slug_from_attributes( $attrs );

				if ( ! empty( $attrs['title'] ) && ! empty( $slug ) ) {
					$anchors_data[] = [
						'slug'  => self::make_unique_slug( $slug, $assigned_slugs ),
						'title' => $attrs['title'],
					];
				}
			}

			if ( ! empty( $block['innerBlocks'] ) ) {
				self::collect_from_blocks( $block['innerBlocks'], $anchors_data, $assigned_slugs );
			}
		}
	}
}
