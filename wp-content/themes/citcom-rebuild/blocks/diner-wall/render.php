<?php
/**
 * citcom/diner-wall
 *
 * Mirrors flexDinerWall() (templates/flexFunctions/diner_wall.php): a staggered
 * wall of client logos. Each frame image is a finished composite exported from
 * the design, so the block only places them: three rows (5 / 4 / 3 in the order
 * added) with the heading in the middle of the second row. The stamp field is
 * kept but not rendered, as decided on the old site (2026-08-20).
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

$heading        = (string) ( $fields['heading'] ?? '' );
$heading_script = (string) ( $fields['heading_script'] ?? '' );
$logos          = is_array( $fields['logos'] ?? null ) ? $fields['logos'] : array();

// The row split is fixed: the middle row's heading slot is a specific design position.
$row_1 = array_slice( $logos, 0, 5 );
$row_2 = array_slice( $logos, 5, 4 );
$row_3 = array_slice( $logos, 9 );

$render_frame = static function ( $logo ) {
	if ( empty( $logo['image'] ) || ! is_array( $logo['image'] ) ) {
		return;
	}
	$alt = ! empty( $logo['name'] ) ? ' alt="' . esc_attr( $logo['name'] ) . '"' : '';
	echo the_image( $logo['image']['id'] ?? $logo['image']['ID'] ?? 0, 'diner-wall-frame', $alt ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
};

// Three pre-textured star crops bundled with the theme (fixed decoration).
$sparkle_base     = CITCOM_THEME_URI . '/assets/img/diner/sparkle-';
$sparkle_variants = 3;

?>

<section <?php echo $attrs['anchor_attr']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="flex-diner_wall diner-dots <?php echo esc_attr( $attrs['classes'] ); ?>" data-index="<?php echo (int) $index; ?>">

	<?php
	// Loosely scattered around the wall; cycles the 3 rotation variants so adjacent stars differ.
	foreach ( array( 1, 2, 3, 4, 5, 6 ) as $i ) :
		$variant = ( ( $i - 1 ) % $sparkle_variants ) + 1;
		?>
		<img src="<?php echo esc_url( $sparkle_base . $variant . '.png' ); ?>" class="diner-wall-star diner-wall-star-<?php echo (int) $i; ?>" alt="" aria-hidden="true">
	<?php endforeach; ?>

	<div class="diner-wall-rows">

		<?php if ( $row_1 ) : ?>
			<div class="diner-wall-row diner-wall-row-1" data-aos="fade-up">
				<?php
				foreach ( $row_1 as $logo ) {
					$render_frame( $logo );
				}
				?>
			</div>
		<?php endif; ?>

		<?php if ( $row_2 || $heading ) : ?>
			<div class="diner-wall-row diner-wall-row-2" data-aos="fade-up">
				<?php
				foreach ( array_slice( $row_2, 0, 2 ) as $logo ) {
					$render_frame( $logo );
				}
				?>

				<?php if ( $heading || $heading_script ) : ?>
					<div class="diner-wall-heading-block">
						<?php if ( $heading ) : ?>
							<h2 class="diner-wall-heading"><?php echo esc_html( $heading ); ?></h2>
						<?php endif; ?>
						<?php if ( $heading_script ) : ?>
							<p class="diner-wall-script-row">
								<span class="diner-wall-dash" aria-hidden="true"></span>
								<span class="diner-wall-script"><?php echo esc_html( $heading_script ); ?></span>
								<span class="diner-wall-dash" aria-hidden="true"></span>
							</p>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<?php
				foreach ( array_slice( $row_2, 2 ) as $logo ) {
					$render_frame( $logo );
				}
				?>
			</div>
		<?php endif; ?>

		<?php if ( $row_3 ) : ?>
			<div class="diner-wall-row diner-wall-row-3" data-aos="fade-up">
				<?php
				foreach ( $row_3 as $logo ) {
					$render_frame( $logo );
				}
				?>
			</div>
		<?php endif; ?>

	</div>

</section>
