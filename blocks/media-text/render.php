<?php
/**
 * citcom/media-text
 *
 * Mirrors flexMediaText() (templates/flexFunctions/media_text.php). The old
 * layout's "text" clone of the block editor field is now the block's inner
 * blocks. As before, a "video" media type renders no media: the old template
 * only had an image branch.
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

$media_type = ! empty( $fields['media_type'] ) ? (string) $fields['media_type'] : 'image';

/**
 * Focuspoint value to id / left / top with the old defaults.
 *
 * @param mixed $image Field value.
 * @return array{id:int,left:string,top:string}|null
 */
$citcom_focus = static function ( $image ): ?array {
	if ( is_array( $image ) && ! empty( $image['id'] ?? $image['ID'] ?? 0 ) ) {
		return array(
			'id'   => (int) ( $image['id'] ?? $image['ID'] ),
			'left' => (string) ( $image['left'] ?? '50' ),
			'top'  => (string) ( $image['top'] ?? '50' ),
		);
	}
	if ( is_numeric( $image ) && (int) $image > 0 ) {
		return array(
			'id'   => (int) $image,
			'left' => '50',
			'top'  => '50',
		);
	}
	return null;
};

$img_1       = null;
$img_2       = null;
$img_1_scale = '100';
$img_2_scale = '100';
$double      = '';
if ( 'image' === $media_type ) {
	$img_1       = $citcom_focus( $fields['image'] ?? null );
	$img_2       = $citcom_focus( $fields['image_2'] ?? null );
	$img_1_scale = (string) ( $fields['image_1_scale'] ?? '100' );
	$img_2_scale = (string) ( $fields['image_2_scale'] ?? '100' );
	$double      = $img_2 ? 'double' : '';
}

$setting_align         = ! empty( $fields['align'] ) ? (string) $fields['align'] : 'media_left';
$setting_citdot        = ! empty( $fields['citdot_style'] ) ? 'citdot' : 'overflow-hidden';
$justify               = 'media_right' === $setting_align ? 'start' : 'end';
$extra_padding         = ! empty( $fields['extra_padding'] ) ? 'container-padding' : '';
$extra_padding_media   = ! empty( $fields['extra_padding'] ) ? 'extra-padding' : '';
$extra_padding_section = ! empty( $fields['extra_padding'] ) ? 'section-extra-padding' : '';

if ( $is_preview ) {
	$inner = '<InnerBlocks allowedBlocks="' . esc_attr( wp_json_encode( citcom_editor_allowed_blocks() ) ) . '" template="' . esc_attr( wp_json_encode( array( array( 'core/paragraph' ) ) ) ) . '" />';
} else {
	$inner = wp_kses_post( citdotLists( $content ) );
}

?>

<section <?php echo $attrs['anchor_attr']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="section-padding flex-media_text <?php echo esc_attr( $attrs['classes'] ); ?> position-relative d-flex align-items-center <?php echo esc_attr( $extra_padding_section ); ?> <?php echo esc_attr( $setting_align ); ?>" data-index="<?php echo (int) $index; ?>">

	<div class="media position-absolute <?php echo esc_attr( $setting_citdot ); ?> <?php echo esc_attr( $extra_padding_media ); ?>" data-aos="grayscale">
		<div class="media-container">
			<?php if ( 'image' === $media_type && $img_1 ) : ?>
				<?php echo the_image( $img_1['id'], 'img-bg z-n1 object-fit-cover position-absolute top-0 h-100 ' . $double, 'style="object-position: ' . esc_attr( $img_1['left'] ) . '% ' . esc_attr( $img_1['top'] ) . '%; transform: scale(' . esc_attr( $img_1_scale ) . '%);"' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php if ( $img_2 ) : ?>
					<?php echo the_image( $img_2['id'], 'img-bg double z-n1 object-fit-cover position-absolute top-0 h-100 end-0', 'style="object-position: ' . esc_attr( $img_2['left'] ) . '% ' . esc_attr( $img_2['top'] ) . '%; transform: scale(' . esc_attr( $img_2_scale ) . '%);"' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php endif; ?>
			<?php endif; ?>
		</div>
	</div>

	<div class="container-xl <?php echo esc_attr( $extra_padding ); ?>">
		<div class="row align-items-center justify-content-<?php echo esc_attr( $justify ); ?>">
			<div class="col-12 col-md-5 flex-editor">
				<div data-aos="fade-up">
					<?php echo $inner; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</div>
		</div>
	</div>

</section>
