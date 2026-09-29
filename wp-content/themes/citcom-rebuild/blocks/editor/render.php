<?php
/**
 * citcom/editor
 *
 * Mirrors flexEditor() (templates/flexFunctions/editor.php). The old layout held
 * a Gutenberg HTML string in an acfe_block_editor field; this block holds the
 * same content as real inner blocks. On the front end the rendered inner
 * content passes through citdotLists() exactly as before.
 *
 * @var array  $block      Block settings and attributes.
 * @var string $content    Rendered inner blocks (front end).
 * @var bool   $is_preview True in the editor.
 * @var int    $post_id    Post the block is saved to.
 *
 * @package citcom
 */

defined( 'ABSPATH' ) || exit;

$fields = get_fields() ?: array();
$attrs  = citcom_section_attrs( $block, $fields );
$index  = citcom_block_index();

if ( $is_preview ) {
	$inner = '<InnerBlocks allowedBlocks="' . esc_attr( wp_json_encode( citcom_editor_allowed_blocks() ) ) . '" template="' . esc_attr( wp_json_encode( array( array( 'core/paragraph' ) ) ) ) . '" />';
} else {
	$inner = citdotLists( $content );
}

?>

<section <?php echo $attrs['anchor_attr']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="section-padding flex-editor <?php echo esc_attr( $attrs['classes'] ); ?>" data-index="<?php echo (int) $index; ?>">

	<div class="container-xl">
		<div class="editor flex-editor" data-aos="fade-up">
			<?php echo $inner; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
	</div>

</section>
