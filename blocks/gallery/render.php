<?php
/**
 * citcom/gallery
 *
 * Mirrors flexGallery() (templates/flexFunctions/gallery.php), including its
 * object-position, which prints the focal point as "top% left%" (the axes the
 * other way round from the rest of the theme). The baseline was captured with
 * that, so it stays.
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

$style   = ! empty( $fields['style'] ) ? (string) $fields['style'] : 'style1';
$gallery = is_array( $fields['gallery'] ?? null ) ? $fields['gallery'] : array();

$layouts = array(
	'style1' => array(
		'row'   => 'row g-2 g-sm-1',
		'col_1' => 'col-sm-6 col-md-5',
		'col_2' => 'col-sm-6 col-md-7',
		'col_3' => 'col-sm-6 col-md-7',
		'col_4' => 'col-sm-6 col-md-5',
		'col_5' => 'col-sm-6 col-md-5',
		'col_6' => 'col-sm-6 col-md-7',
	),
	'style2' => array(
		'row'   => 'row g-2 g-sm-1',
		'col_1' => 'col-sm-6 col-md-4',
		'col_2' => 'col-sm-6 col-md-4',
		'col_3' => 'col-sm-6 col-md-4',
		'col_4' => 'col-sm-6 col-md-12',
		'col_5' => '',
		'col_6' => '',
	),
	'style3' => array(
		'row'   => 'row g-2 g-sm-1',
		'col_1' => 'col-sm-6 col-md-5',
		'col_2' => 'col-sm-6 col-md-7',
		'col_3' => 'col-sm-12 col-md-12',
		'col_4' => '',
		'col_5' => '',
		'col_6' => '',
	),
	'style4' => array(
		'row'   => 'row g-2 g-sm-1',
		'col_1' => 'col-sm-6 col-md-5 pb-md-1',
		'col_2' => 'col-md-12',
		'col_3' => 'col-md-12',
		'col_4' => '',
		'col_5' => '',
		'col_6' => '',
	),
	'style5' => array(
		'row'   => 'row g-2 g-sm-1',
		'col_1' => 'col-sm-6 col-md-7 pb-md-1',
		'col_2' => 'col-md-12',
		'col_3' => 'col-md-12',
		'col_4' => '',
		'col_5' => '',
		'col_6' => '',
	),
	'style6' => array(
		'row'   => 'row g-2 g-sm-1',
		'col_1' => 'col-sm-6 col-md-4',
		'col_2' => 'col-sm-6 col-md-4',
		'col_3' => 'col-sm-6 col-md-4',
		'col_4' => 'col-sm-6 col-md-6',
		'col_5' => 'col-sm-12 col-md-6',
		'col_6' => '',
	),
	'style7' => array(
		'row'   => 'row g-2 g-sm-1',
		'col_1' => 'col-sm-6 col-md-4',
		'col_2' => 'col-sm-6 col-md-4',
		'col_3' => 'col-sm-6 col-md-4',
		'col_4' => 'col-sm-6 col-md-4',
		'col_5' => 'col-sm-6 col-md-4',
		'col_6' => 'col-sm-6 col-md-4',
	),
);
$layout  = $layouts[ $style ] ?? $layouts['style1'];

$max_images = array(
	'style1' => 4,
	'style2' => 4,
	'style3' => 3,
	'style4' => 3,
	'style5' => 3,
	'style6' => 5,
	'style7' => 6,
);
$max        = $max_images[ $style ] ?? 6;

?>

<section <?php echo $attrs['anchor_attr']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="section-padding flex-gallery <?php echo esc_attr( $attrs['classes'] ); ?>" data-index="<?php echo (int) $index; ?>">

	<div class="container-xl">
		<div class="masonary">
			<div class="<?php echo esc_attr( $layout['row'] ); ?> h-100">
				<?php
				$i = 1;
				foreach ( $gallery as $row ) :
					if ( $i > $max ) {
						break;
					}
					$image = is_array( $row['image'] ?? null ) ? $row['image'] : array();
					$id    = (int) ( $image['id'] ?? 0 );
					$top   = (string) ( $image['top'] ?? '50' );
					$left  = (string) ( $image['left'] ?? '50' );

					if ( 2 === $i && ( 'style4' === $style || 'style5' === $style ) ) {
						echo '<div class="col"><div class="row g-1 h-100">';
					}
					?>
					<div class="col-12 <?php echo esc_attr( $layout[ 'col_' . $i ] ?? '' ); ?>">
						<div class="img-container bg-default rounded-3 overflow-hidden position-relative h-100 w-100">
							<?php echo the_image( $id, 'img-bg z-1 object-fit-cover  w-100 h-100', 'style="object-position: ' . esc_attr( $top ) . '% ' . esc_attr( $left ) . '%;"' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>
					</div>
					<?php
					if ( 3 === $i && ( 'style4' === $style || 'style5' === $style ) ) {
						echo '</div></div>';
					}
					++$i;
				endforeach;
				?>
				<?php if ( ! $gallery && $is_preview ) : ?>
					<p><em><?php esc_html_e( 'Add images in the block settings.', 'citcom' ); ?></em></p>
				<?php endif; ?>
			</div>
		</div>
	</div>

</section>
