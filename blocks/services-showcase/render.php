<?php
/**
 * citcom/services-showcase
 *
 * Mirrors flexServicesShowcase() (templates/flexFunctions/services_showcase.php).
 * The expand-on-click behaviour lives in src/js/cards.js.
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

$services    = array( 'creative', 'development', 'marketing', 'events', 'print', 'video' );
$service_arr = array();
foreach ( $services as $service ) {
	$group                   = is_array( $fields[ $service ] ?? null ) ? $fields[ $service ] : array();
	$image                   = is_array( $group['showcase_image'] ?? null ) ? $group['showcase_image'] : array();
	$link                    = is_array( $group['card_link'] ?? null ) ? $group['card_link'] : array();
	$service_arr[ $service ] = array(
		'shape' => serviceShapeSVG( $service ),
		'name'  => ucfirst( $service ),
		'image' => $image,
		'text'  => (string) ( $group['card_text'] ?? '' ),
		'link'  => $link,
	);
}

$i = 1;
?>

<section <?php echo $attrs['anchor_attr']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="section-padding flex-services_showcase <?php echo esc_attr( $attrs['classes'] ); ?>" data-index="<?php echo (int) $index; ?>">

	<div class="container-xl">
		<div class="showcase" data-aos="fade-up">
			<?php foreach ( $service_arr as $service ) : ?>
				<div class="service position-relative overflow-hidden <?php echo 1 === $i ? 'open active' : ''; ?>">
					<?php
					if ( ! empty( $service['image']['id'] ) ) {
						echo wp_get_attachment_image(
							(int) $service['image']['id'],
							'large',
							false,
							array(
								'class'           => 'position-absolute top-0 start-0 w-100 h-100',
								'style'           => 'object-fit: cover; object-position: ' . (float) ( $service['image']['left'] ?? 50 ) . '% ' . (float) ( $service['image']['top'] ?? 50 ) . '%;',
								'data-spai-eager' => 'true',
							)
						);
					}
					?>
					<div class="footer">
						<div class="showcase-service-text">
							<p class="fs-4 fw-bold mb-0"><?php echo esc_html( $service['name'] ); ?></p>
							<p class="mb-0"><?php echo esc_html( $service['text'] ); ?></p>
						</div>
					</div>
					<a href="<?php echo esc_url( $service['link']['url'] ?? '' ); ?>" class="link rounded-2 focus-ring stretched-link" target="<?php echo esc_attr( $service['link']['target'] ?? '' ); ?>" aria-label="<?php echo esc_attr( $service['name'] ); ?>">
						<div class="rounded-circle btn btn-light btn-icon z-2">
							<i class="fa-solid fa-arrow-up-right fa-lg fa-fw"></i>
						</div>
					</a>
					<div class="closed-pill">
						<div class="name">
							<p class="fs-4 mb-0"><?php echo esc_html( $service['name'] ); ?></p>
						</div>
					</div>
					<div class="shape"><?php echo $service['shape']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inline SVG from the theme ?></div>
				</div>
				<?php $i++; ?>
			<?php endforeach; ?>
		</div>
	</div>

</section>
