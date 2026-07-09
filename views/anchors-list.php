<?php
/**
 * Default Anchors List Block template
 *
 * @var array $args {
 * @type array $block_attributes
 * @type string $block_wrapper_attributes
 * @type array $anchors
 * @type bool $is_preview
 * }
 */

$anchors = $args['anchors'] ?? [];
if ( empty( $anchors ) ) {
	return;
}
?>
<div <?php echo $args['block_wrapper_attributes']; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="wp-block-blockparty-anchors-list__content">
		<div role="heading" aria-level="2" class="wp-block-blockparty-anchors-list__title"><?php esc_html_e( 'Quick access', 'blockparty-anchors' ); ?></div>
		<div class="wp-block-blockparty-anchors-list__scroll">
			<ul class="wp-block-blockparty-anchors-list__items no-list-style">
				<?php foreach ( $anchors as $anchor ) : ?>
					<li class="wp-block-blockparty-anchors-list__item">
						<?php
						printf(
							'<a href="#%s" class="wp-block-blockparty-anchors-list__link">%s</a>',
							esc_attr( $anchor['slug'] ?? '' ),
							esc_html( $anchor['title'] ?? '' )
						);
						?>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
</div>
