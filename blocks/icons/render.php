<?php
/**
 * citcom/icons
 *
 * Mirrors flexIcons() (templates/flexFunctions/icons.php).
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

$number_of_columns = (string) ( $fields['number_of_columns'] ?? '3' );
$justify           = 'justify-content-center';
$col_class         = '';
switch ( $number_of_columns ) {
	case '3':
		$col_class = 'col-sm-6 col-md-4';
		break;
	case '4':
		$col_class = 'col-md-6 col-lg-3';
		break;
	case '5':
		$col_class = 'col-sm-6 col-md-4 col-lg-2';
		$justify   = 'justify-content-between';
		break;
}

$icon_columns = is_array( $fields['icon_columns'] ?? null ) ? $fields['icon_columns'] : array();

citcom_preview_clip_paths( (bool) $is_preview );
?>

<section <?php echo $attrs['anchor_attr']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="section-padding flex-icons <?php echo esc_attr( $attrs['classes'] ); ?>" data-index="<?php echo (int) $index; ?>">

	<div class="container-xl">
		<div class="row g-3 row-gap-5 <?php echo esc_attr( $justify ); ?>">
			<?php
			foreach ( $icon_columns as $icon ) :
				$icon_class = '';
				switch ( $icon['icon_style'] ?? 'primary' ) {
					case 'primary':
						$icon_class = 'citdot bg-primary text-secondary';
						break;
					case 'secondary':
						$icon_class = 'citdot bg-secondary text-primary';
						break;
					case 'primary-outline':
						$icon_class = 'citdot-outline-primary text-primary';
						break;
					case 'secondary-outline':
						$icon_class = 'citdot-outline text-secondary';
						break;
				}
				$align = (string) ( $icon['align'] ?? 'center' );
				?>
				<div class="col-12 <?php echo esc_attr( $col_class ); ?> text-<?php echo esc_attr( $align ); ?>">
					<div class="icon-col d-flex justify-content-<?php echo esc_attr( $align ); ?>">
						<div class="<?php echo esc_attr( $icon_class ); ?> p-3">
							<i class="<?php echo esc_attr( (string) ( $icon['icon'] ?? '' ) ); ?> fa-3x fa-fw"></i>
						</div>
					</div>
					<div class="supporting-text mt-3 px-4 text-<?php echo esc_attr( $align ); ?> flex-editor">
						<?php echo wp_kses_post( (string) ( $icon['supporting_text'] ?? '' ) ); ?>
					</div>
				</div>
			<?php endforeach; ?>
			<?php if ( ! $icon_columns && $is_preview ) : ?>
				<p><em><?php esc_html_e( 'Add icon columns in the block settings.', 'citcom' ); ?></em></p>
			<?php endif; ?>
		</div>
	</div>

</section>
