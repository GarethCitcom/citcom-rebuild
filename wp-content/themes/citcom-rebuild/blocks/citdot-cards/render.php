<?php
/**
 * citcom/citdot-cards
 *
 * Mirrors flexCitdotCards() with citDotStaff() and citDotValue()
 * (templates/flexFunctions/citdot_cards.php). Card styles come from
 * src/scss/elements/_card.scss in the main stylesheet.
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
$cards  = is_array( $fields['citdot_card'] ?? null ) ? $fields['citdot_card'] : array();

citcom_preview_clip_paths( (bool) $is_preview );
?>

<section <?php echo $attrs['anchor_attr']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="section-padding flex-citdot_cards <?php echo esc_attr( $attrs['classes'] ); ?>" data-index="<?php echo (int) $index; ?>">

	<div class="container-xl">
		<div class="row row-gap-4 justify-content-center">
			<?php
			foreach ( $cards as $card ) :
				$style = (string) ( $card['citdot_style'] ?? '' );

				if ( 'staff' === $style ) :
					$staff   = is_array( $card['staff'] ?? null ) ? $card['staff'] : array();
					$picture = is_array( $staff['profile_picture'] ?? null ) ? $staff['profile_picture'] : array();
					$img_id  = (int) ( $picture['id'] ?? 0 );
					$top     = ! empty( $picture['top'] ) ? (float) $picture['top'] : 50;
					$left    = ! empty( $picture['left'] ) ? (float) $picture['left'] : 50;
					$name    = (string) ( $staff['name'] ?? '' );
					?>

					<div class="col-12 col-sm-6 col-lg-4">
						<div class="citdot-card citdot staff position-relative" data-aos="flip-right">
							<div class="profile-bg position-absolute z-0 start-0 top-0 end-0 bottom-0">
								<?php echo the_image( $img_id, 'img-bg z-n1 object-fit-cover position-absolute top-0 start-0 w-100 h-100', 'style="object-position: ' . $left . '% ' . $top . '%;"' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</div>
							<div class="profile-info">
								<div class="d-flex gap-2 justify-content-between align-items-start">
									<div class="name-job">
										<p class="lead lh-sm mb-0"><strong class="fw-800"><?php echo esc_html( $name ); ?></strong></p>
										<p class="lh-1 fw-400 mb-0"><?php echo esc_html( (string) ( $staff['job_title'] ?? '' ) ); ?></p>
									</div>
									<a href="<?php echo esc_url( (string) ( $staff['linkedin_profile'] ?? '' ) ); ?>" class="d-block rounded-2 focus-ring focus-ring-light" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-custom-class="citcom-tooltip" data-bs-title="<?php echo esc_attr( $name ); ?>'s LinkedIn Profile" target="_blank">
										<div class="citdot btn btn-light btn-icon text-persian">
											<i class="fa-brands fa-linkedin-in fa-fw"></i>
										</div>
									</a>
								</div>
								<div class="description">
									<div class="lh-sm pt-3 pb-2">
										<?php echo wp_kses_post( (string) ( $staff['description'] ?? '' ) ); ?>
									</div>
								</div>
							</div>
						</div>
					</div>

					<?php
				elseif ( 'value' === $style ) :
					$value = is_array( $card['value'] ?? null ) ? $card['value'] : array();
					?>

					<div class="col-12 col-sm-6 col-lg-4">
						<div class="citdot-card citdot bg-primary text-light pattern pattern-opac value position-relative px-5 py-4" data-aos="flip-left">
							<div class="header-icon pt-3 pb-4 d-flex align-items-center justify-content-center">
								<div class="citdot-outline p-2">
									<i class="<?php echo esc_attr( (string) ( $value['icon'] ?? '' ) ); ?> fa-3x fa-fw"></i>
								</div>
							</div>
							<div class="body text-center">
								<?php echo wp_kses_post( (string) ( $value['content'] ?? '' ) ); ?>
							</div>
						</div>
					</div>

					<?php
				endif;
			endforeach;
			if ( ! $cards && $is_preview ) {
				echo '<p class="text-center"><em>' . esc_html__( 'Add CitDot cards in the block settings.', 'citcom' ) . '</em></p>';
			}
			?>
		</div>
	</div>

</section>
