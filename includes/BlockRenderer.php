<?php

namespace Blockparty\Anchors;

class BlockRenderer {

	/**
	 * Render anchor block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content    Block content.
	 * @param \WP_Block $block     Block instance.
	 *
	 * @return string
	 */
	public static function render( $attributes, $content, $block ): string {
		$slug = Anchors::get_slug_from_attributes( $attributes );

		if ( empty( $slug ) ) {
			return '';
		}

		$attributes['slug'] = $slug;

		$classnames = [];

		/**
		 * Filter block's classnames.
		 *
		 * @param array     $classnames List of CSS classes.
		 * @param array     $attributes Block attributes.
		 * @param \WP_Block $block      Block instance.
		 */
		$classnames = apply_filters( 'blockparty/anchor/classnames', $classnames, $attributes, $block );
		$classnames = array_map( 'sanitize_html_class', $classnames );

		/**
		 * Filter block's template slug.
		 *
		 * Template must be located in the theme as it will be loaded via `get_template_part`.
		 *
		 * @param string    $template_slug Block template slug.
		 * @param array     $attributes    Block attributes.
		 * @param \WP_Block $block         Block instance.
		 */
		$template_slug = apply_filters( 'blockparty/anchor/template_slug', 'components/gutenberg/anchor', $attributes, $block );

		/**
		 * Filter block's template name.
		 *
		 * @param string    $template_name Block template name.
		 * @param string    $template_slug Block template slug.
		 * @param array     $attributes    Block attributes.
		 * @param \WP_Block $block         Block instance.
		 */
		$template_name = apply_filters( 'blockparty/anchor/template_name', '', $template_slug, $attributes, $block );

		/**
		 * Filter block's template args.
		 *
		 * @param array     $template_args Template arguments.
		 * @param string    $template_slug Block template slug.
		 * @param string    $template_name Block template name.
		 * @param array     $attributes    Block attributes.
		 * @param \WP_Block $block         Block instance.
		 */
		$template_args = apply_filters(
			'blockparty/anchor/template_args',
			[
				'block_attributes'         => $attributes,
				'block_wrapper_attributes' => get_block_wrapper_attributes( [ 'class' => implode( ' ', $classnames ) ] ),
				'is_preview'               => isset( $_GET['is_block_editor'] ), //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			],
			$template_slug,
			$template_name,
			$attributes,
			$block
		);

		ob_start();
		$rendered      = get_template_part( $template_slug, $template_name, $template_args );
		$block_content = ob_get_clean();

		if ( false !== $rendered ) {
			return $block_content;
		}

		ob_start();
		load_template( BLOCKPARTY_ANCHORS_DIR . 'views/anchor.php', false, $template_args );

		return ob_get_clean();
	}

	/**
	 * Render anchors list block.
	 *
	 * @param array     $attributes Block attributes.
	 * @param string    $content    Block content.
	 * @param \WP_Block $block      Block instance.
	 *
	 * @return string
	 */
	public static function render_list( $attributes, $content, $block ): string {
		$current_post = get_post();

		if ( ! ( $current_post instanceof \WP_Post ) ) {
			return '';
		}

		$classnames = [];

		/**
		 * Filter block's classnames.
		 *
		 * @param array     $classnames List of CSS classes.
		 * @param array     $attributes Block attributes.
		 * @param \WP_Block $block      Block instance.
		 */
		$classnames = apply_filters( 'blockparty/anchors_list/classnames', $classnames, $attributes, $block );
		$classnames = array_map( 'sanitize_html_class', $classnames );

		/**
		 * Filter block's template slug.
		 *
		 * Template must be located in the theme as it will be loaded via `get_template_part`.
		 *
		 * @param string    $template_slug Block template slug.
		 * @param array     $attributes    Block attributes.
		 * @param \WP_Block $block         Block instance.
		 */
		$template_slug = apply_filters( 'blockparty/anchors_list/template_slug', 'components/gutenberg/anchors-list', $attributes, $block );

		/**
		 * Filter block's template name.
		 *
		 * @param string    $template_name Block template name.
		 * @param string    $template_slug Block template slug.
		 * @param array     $attributes    Block attributes.
		 * @param \WP_Block $block         Block instance.
		 */
		$template_name = apply_filters( 'blockparty/anchors_list/template_name', '', $template_slug, $attributes, $block );

		/**
		 * Filter block's template args.
		 *
		 * @param array     $template_args Template arguments.
		 * @param string    $template_slug Block template slug.
		 * @param string    $template_name Block template name.
		 * @param array     $attributes    Block attributes.
		 * @param \WP_Block $block         Block instance.
		 */
		$template_args = apply_filters(
			'blockparty/anchors_list/template_args',
			[
				'block_attributes'         => $attributes,
				'block_wrapper_attributes' => get_block_wrapper_attributes( [ 'class' => implode( ' ', $classnames ) ] ),
				'is_preview'               => isset( $_GET['is_block_editor'] ), //phpcs:ignore WordPress.Security.NonceVerification.Recommended
				'anchors'                  => Anchors::get_from_post( $current_post ),
			],
			$template_slug,
			$template_name,
			$attributes,
			$block
		);

		ob_start();
		$rendered      = get_template_part( $template_slug, $template_name, $template_args );
		$block_content = ob_get_clean();

		if ( false !== $rendered ) {
			return $block_content;
		}

		ob_start();
		load_template( BLOCKPARTY_ANCHORS_DIR . 'views/anchors-list.php', false, $template_args );

		return ob_get_clean();
	}
}
