<?php
/**
 * citcom/cta
 *
 * Mirrors flexCTA() (templates/flexFunctions/cta.php). As in the original, the
 * section ignores the Section settings colour classes: it is always the
 * gradient (persian for sign-up, slate otherwise) with white text; only the
 * anchor is used.
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

$type_of_cta  = ! empty( $fields['type_of_cta'] ) ? (string) $fields['type_of_cta'] : 'button';
$grad_pattern = 'sign-up' === $type_of_cta ? 'grad-persian' : 'grad-slate';
$cta_content  = wp_kses_post( (string) ( $fields['content'] ?? '' ) );
$social_icons = (array) citcom_get_option( 'social_icon_links' );
$button       = is_array( $fields['button'] ?? null ) ? $fields['button'] : array();
$button_2     = is_array( $fields['button_2'] ?? null ) ? $fields['button_2'] : array();
$form_id      = (int) ( $fields['form'] ?? 0 );

/**
 * One social icon link, as the footer and the old template print it.
 *
 * @param array  $social     Repeater row (icon, link).
 * @param string $icon_class Extra classes on the <i>.
 */
$citcom_social_link = static function ( array $social, string $icon_class ): void {
	if ( empty( $social['link']['url'] ) ) {
		return;
	}
	$title = $social['link']['title'] ?? '';
	?>
	<a href="<?php echo esc_url( $social['link']['url'] ); ?>" aria-label="<?php echo esc_attr( $title ); ?>" class="d-block rounded-2 focus-ring focus-ring-light" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-custom-class="citcom-tooltip" data-bs-title="<?php echo esc_attr( $title ); ?>" target="_blank">
		<div class="citdot btn btn-light btn-icon">
			<i class="<?php echo esc_attr( ( $social['icon'] ?? '' ) . ' ' . $icon_class ); ?>"></i>
		</div>
	</a>
	<?php
};

citcom_preview_clip_paths( (bool) $is_preview );
?>

<section <?php echo $attrs['anchor_attr']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="section-padding flex-cta <?php echo esc_attr( $grad_pattern ); ?> text-white" data-index="<?php echo (int) $index; ?>">

	<div class="container-xl">
		<?php if ( 'sign-up' === $type_of_cta ) : ?>
			<div class="signup-popup text-center w-100" data-bs-toggle="modal" data-bs-target="#signup-newsletter-modal">
				<div class="signup-text" data-aos="fade-up">
					<?php echo $cta_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
				<div class="input-group signup mt-3 mb-2 mx-auto bg-light rounded-pill" data-aos="zoom-in">
					<input type="text" class="form-control form-control-lg rounded-start-pill ps-4" placeholder="Enter your email address…." aria-label="Recipient's username" aria-describedby="button-addon2">
					<button class="btn btn-primary btn-lg rounded-pill px-4" type="button" id="button-addon2">Submit</button>
				</div>
			</div>
		<?php endif; ?>
		<?php if ( 'form' === $type_of_cta ) : ?>
			<div class="row align-items-center justify-content-between">
				<div class="col-12 col-md-5">
					<div data-aos="fade-up">
						<?php echo $cta_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
					<h3 data-aos="fade-up"><strong>Find us on ...</strong></h3>
					<div class="social-icons d-flex gap-3 justify-content-center justify-content-md-start" data-aos="fade-left">
						<?php
						foreach ( $social_icons as $social ) {
							$citcom_social_link( (array) $social, 'fa-fw text-slate' );
						}
						?>
					</div>
				</div>
				<div class="col-12 col-md-6" data-aos="fade">
					<?php
					if ( $form_id ) {
						echo do_shortcode( '[forminator_form id="' . $form_id . '"]' );
					} elseif ( $is_preview ) {
						echo '<p><em>' . esc_html__( 'Choose a Forminator form in the block settings.', 'citcom' ) . '</em></p>';
					}
					?>
				</div>
			</div>
		<?php endif; ?>
		<?php if ( 'social' === $type_of_cta ) : ?>
			<div class="row justify-content-center justify-content-md-between flex-column flex-md-row">
				<div class="col-12 col-md-6 text-center text-md-start" data-aos="fade-up">
					<?php echo $cta_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
				<div class="col-12 col-md-6">
					<div class="social-icons d-flex gap-3 justify-content-center justify-content-md-end" data-aos="fade-right">
						<?php
						foreach ( $social_icons as $social ) {
							$citcom_social_link( (array) $social, 'fa-lg fa-fw text-persian' );
						}
						?>
					</div>
				</div>
			</div>
		<?php endif; ?>
		<?php if ( 'button' === $type_of_cta ) : ?>
			<div class="row">
				<div class="col-12 text-center text-sm-start">
					<div data-aos="fade-up">
						<?php echo $cta_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
					<div class="cta-buttons d-flex flex-column flex-sm-row gap-3 mt-4" data-aos="fade-left">
						<?php if ( ! empty( $button['url'] ) || $is_preview ) : ?>
							<a href="<?php echo esc_url( $button['url'] ?? '#' ); ?>" target="<?php echo esc_attr( $button['target'] ?? '' ); ?>" class="btn btn-secondary rounded-pill px-4 text-nowrap">
								<?php echo esc_html( $button['title'] ?? __( 'Button', 'citcom' ) ); ?>
							</a>
						<?php endif; ?>
						<?php if ( ! empty( $button_2['url'] ) ) : ?>
							<a href="<?php echo esc_url( $button_2['url'] ); ?>" target="<?php echo esc_attr( $button_2['target'] ?? '' ); ?>" class="btn btn-outline-secondary rounded-pill px-4 text-nowrap">
								<?php echo esc_html( $button_2['title'] ?? '' ); ?>
							</a>
						<?php endif; ?>
					</div>
				</div>
			</div>
		<?php endif; ?>
	</div>

</section>
