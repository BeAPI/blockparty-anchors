<?php
/**
 * Default Dynamic Block template
 *
 * @var array $args {
 *      @type array $block_attributes
 *      @type string $block_wrapper_attributes
 *      @type bool $is_preview
 * }
 */
?>
<span <?php echo $args['block_wrapper_attributes']; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> aria-hidden="true" id="<?php echo esc_attr( $args['block_attributes']['slug'] ); ?>"></span>
