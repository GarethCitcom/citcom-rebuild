<?php
/**
 * citcom/shortcode
 *
 * Mirrors flexShortcode() (templates/flexFunctions/shortcode.php).
 *
 * @var array  $block      Block settings and attributes.
 * @var string $content    Inner HTML (unused).
 * @var bool   $is_preview True in the editor.
 * @var int    $post_id    Post the block is saved to.
 *
 * @package citcom
 */

defined( 'ABSPATH' ) || exit;

$fields = get_fields() ?: array();
$attrs  = citcom_section_attrs( $block, $fields );
$index  = citcom_block_index();

$before    = (string) ( $fields['before'] ?? '' );
$after     = (string) ( $fields['after'] ?? '' );
$shortcode = (string) ( $fields['shortcode'] ?? '' );

?>

<section <?php echo $attrs['anchor_attr']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="section-padding flex-shortcode <?php echo esc_attr( $attrs['classes'] ); ?>" data-index="<?php echo (int) $index; ?>">

	<div class="container-xl">
		<?php if ( $before ) : ?>
			<div class="before mb-4"><?php echo $before; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- editor HTML, as before ?></div>
		<?php endif; ?>
		<?php
		if ( $shortcode ) {
			echo do_shortcode( $shortcode );
		} elseif ( $is_preview ) {
			echo '<p><em>' . esc_html__( 'Enter a shortcode in the block settings.', 'citcom' ) . '</em></p>';
		}
		?>
		<?php if ( $after ) : ?>
			<div class="after mt-4"><?php echo $after; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
		<?php endif; ?>
	</div>

</section>
