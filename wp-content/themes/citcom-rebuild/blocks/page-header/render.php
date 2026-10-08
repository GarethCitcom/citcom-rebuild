<?php
/**
 * citcom/page-header
 *
 * Mirrors flexPageHeader() in the original theme (templates/flexFunctions/
 * page_header.php) so the existing page-header SCSS applies unchanged.
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

$type            = ! empty( $fields['type'] ) ? (string) $fields['type'] : 'pattern';
$section_classes = $attrs['classes'] . ' type-' . $type;
$title_bg        = 'bg-default';

if ( 'pattern' === $type ) {
	$bg_colour = citcom_choice_label( 'bg-color', $fields['bg-color'] ?? '' );
	$bg_colour = '' !== $bg_colour ? $bg_colour : 'default_lighter';
	$pattern   = ! empty( $fields['pattern'] ) ? (string) $fields['pattern'] : 'persian';

	$section_classes .= ' pattern pattern-' . $pattern . ' bg-' . $bg_colour;
	$title_bg         = 'bg-' . $bg_colour;
}

// Kept from the original: it tested for "service" while the field stores
// "services", so the service-* class never reached the live site. Adding it
// would change the rendered output.
if ( 'service' === $type && ! empty( $fields['service'] ) ) {
	$section_classes .= ' service-' . $fields['service'];
}

$bg_image_id   = 0;
$bg_image_top  = '50';
$bg_image_left = '50';
if ( 'image' === $type && ! empty( $fields['header_image'] ) ) {
	$header_image = $fields['header_image'];
	if ( is_array( $header_image ) ) {
		$bg_image_id   = (int) ( $header_image['id'] ?? $header_image['ID'] ?? 0 );
		$bg_image_top  = ! empty( $header_image['top'] ) ? (string) $header_image['top'] : '50';
		$bg_image_left = ! empty( $header_image['left'] ) ? (string) $header_image['left'] : '50';
	} elseif ( is_numeric( $header_image ) ) {
		$bg_image_id = (int) $header_image;
	}
}

$extra_heading = '';
if ( is_category() || is_tag() || is_tax( 'cs-tag' ) ) {
	$queried = get_queried_object();
	if ( $queried && isset( $queried->name ) ) {
		$extra_heading = ': <strong>' . esc_html( $queried->name ) . '</strong>';
	}
}

$title = (string) ( $fields['title'] ?? '' );
if ( '' === $title && $is_preview ) {
	$title = __( 'Page Header', 'citcom' );
}

$breadcrumb = ! empty( $fields['breadcrumb'] );
$index      = citcom_block_index();

?>

<section <?php echo $attrs['anchor_attr']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="section-padding flex-page_header <?php echo esc_attr( trim( $section_classes ) ); ?> d-flex align-items-center position-relative" data-index="<?php echo (int) $index; ?>">

	<div class="page-header w-100">
		<?php if ( 'image' === $type ) : ?>
			<div class="header-bg position-absolute z-0 start-0 top-0 end-0 bottom-0">
				<?php echo the_image( $bg_image_id, 'img-bg z-n1 object-fit-cover position-absolute top-0 start-0 w-100 h-100', 'style="object-position: ' . esc_attr( $bg_image_left ) . '% ' . esc_attr( $bg_image_top ) . '%;" sizes="100vw"' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
			<div class="pattern position-absolute z-1 start-0 top-0 end-0 bottom-0" data-aos="zoom-out"></div>
		<?php endif; ?>
		<div class="container-xl">
			<h1 class="page-header-title position-relative z-2 rounded-1 <?php echo esc_attr( $title_bg ); ?>" data-aos="slide-right"><?php echo esc_html( $title ); ?><?php echo $extra_heading; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h1>
		</div>
	</div>

</section>

<?php if ( $breadcrumb ) : ?>

	<section class="breadcrumb-container pt-3 pb-2">
		<div class="container-xl">
			<div data-aos="fade-down">
				<?php get_breadcrumb(); ?>
			</div>
		</div>
	</section>

<?php endif; ?>
