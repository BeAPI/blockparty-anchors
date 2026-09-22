<?php
/**
 * Converts beapi/anchor content to blockparty/anchor.
 *
 * @package Blockparty\Anchors
 */

namespace Blockparty\Anchors\Migration;

/**
 * Turns old beapi/anchor markup into blockparty/anchor blocks.
 */
class AnchorBlockMigrator {

	/**
	 * How many blocks were converted.
	 *
	 * @var int
	 */
	public int $migrated = 0;

	/**
	 * How many blocks could not be converted.
	 *
	 * @var int
	 */
	public int $skipped = 0;

	/**
	 * Entry point: rewrite post content, or return null if nothing to do.
	 *
	 * @param string $content Post content.
	 * @return string|null
	 */
	public function migrate_content( string $content ): ?string {
		if ( false === strpos( $content, 'beapi/anchor' ) ) {
			return null;
		}

		$new = serialize_blocks( $this->migrate_blocks( parse_blocks( $content ) ) );

		return $new === $content ? null : $new;
	}

	/**
	 * Walk every block. Replace old anchor blocks. Recurse into groups/columns/etc.
	 *
	 * @param array $blocks Parsed blocks.
	 * @return array
	 */
	private function migrate_blocks( array $blocks ): array {
		$out = [];

		foreach ( $blocks as $block ) {
			$name = $block['blockName'] ?? '';

			if ( 'beapi/anchor' === $name ) {
				$out[] = $this->convert_block( $block, 'blockparty/anchor' );
				continue;
			}

			if ( 'beapi/anchor-list' === $name ) {
				$out[] = $this->convert_block( $block, 'blockparty/anchors-list' );
				continue;
			}

			if ( ! empty( $block['innerBlocks'] ) ) {
				$block['innerBlocks'] = $this->migrate_blocks( $block['innerBlocks'] );
			}

			$out[] = $block;
		}

		return $out;
	}

	/**
	 * Convert one old block to a self-closing blockparty block, keeping attributes.
	 *
	 * @param array  $block    Old parsed block.
	 * @param string $new_name New block name.
	 * @return array
	 */
	private function convert_block( array $block, string $new_name ): array {
		++$this->migrated;

		$attrs = is_array( $block['attrs'] ?? null ) ? $block['attrs'] : [];

		return [
			'blockName'    => $new_name,
			'attrs'        => $attrs,
			'innerBlocks'  => [],
			'innerHTML'    => '',
			'innerContent' => [],
		];
	}
}
