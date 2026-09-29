<?php
/**
 * citcom/contact-map
 *
 * Mirrors flexContactMap() (templates/flexFunctions/contact_map.php).
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

$show_socials = ! empty( $fields['show_socials'] );
$form_id      = (int) ( $fields['form'] ?? 0 );
$desktop_map  = ! empty( $fields['snazzy_map'] ) ? html_entity_decode( (string) $fields['snazzy_map'] ) : '<iframe src="https://snazzymaps.com/embed/650882" width="100%" height="100%" style="border:none;"></iframe>';
$mobile_map   = ! empty( $fields['mobile_snazzy_map'] ) ? html_entity_decode( (string) $fields['mobile_snazzy_map'] ) : '<iframe src="https://snazzymaps.com/embed/650914" width="100%" height="100%" style="border:none;"></iframe>';
$social_icons = (array) citcom_get_option( 'social_icon_links' );

citcom_preview_clip_paths( (bool) $is_preview );
?>

<section <?php echo $attrs['anchor_attr']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="section-padding flex-contact_map <?php echo esc_attr( $attrs['classes'] ); ?>" data-index="<?php echo (int) $index; ?>">

	<div class="container-xl">
		<div class="row g-0 position-relative py-5 justify-content-center justify-content-md-end">
			<div id="snazzy-map" class="map"></div>
			<div class="col-12 col-md-7 col-lg-6 col-xl-5">
				<div class="contact-block grad-slate text-light p-5 rounded-3">
					<h2 class="lh-1" data-aos="fade-up"><?php echo esc_html( (string) ( $fields['contact_title'] ?? '' ) ); ?></h2>
					<p class="lh-sm" data-aos="fade-up"><?php echo esc_html( (string) ( $fields['contact_message'] ?? '' ) ); ?></p>
					<?php if ( $show_socials ) : ?>
						<h3 data-aos="fade-up"><strong>Find us on ...</strong></h3>
						<div class="social-icons d-flex gap-3 justify-content-start" data-aos="fade-left">
							<?php foreach ( $social_icons as $social ) : ?>
								<?php
								if ( empty( $social['link']['url'] ) ) {
									continue;
								}
								?>
								<a href="<?php echo esc_url( $social['link']['url'] ); ?>" class="d-block rounded-2 focus-ring focus-ring-light" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-custom-class="citcom-tooltip" data-bs-title="<?php echo esc_attr( $social['link']['title'] ?? '' ); ?>" target="_blank">
									<div class="citdot btn btn-light btn-icon">
										<i class="<?php echo esc_attr( ( $social['icon'] ?? '' ) . ' fa-fw text-slate' ); ?>"></i>
									</div>
								</a>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
					<div class="form pt-4" data-aos="fade">
						<?php
						if ( $form_id ) {
							echo do_shortcode( '[forminator_form id="' . $form_id . '"]' );
						} elseif ( $is_preview ) {
							echo '<p><em>' . esc_html__( 'Choose a Forminator form in the block settings.', 'citcom' ) . '</em></p>';
						}
						?>
					</div>
				</div>
			</div>
		</div>
	</div>

</section>

<?php if ( ! $is_preview ) : ?>
<script>
	var mapDesktop = '<?php echo str_replace( array( "\\", "'", "\n", "\r" ), array( '\\\\', "\\'", '', '' ), $desktop_map ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- embed HTML from the code editor field, as before ?>';
	var mapMobile = '<?php echo str_replace( array( "\\", "'", "\n", "\r" ), array( '\\\\', "\\'", '', '' ), $mobile_map ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>';
	if (window.innerWidth < 768) {
		document.getElementById("snazzy-map").innerHTML = mapMobile;
	} else {
		document.getElementById("snazzy-map").innerHTML = mapDesktop;
	}
</script>
<?php endif; ?>
