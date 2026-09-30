<?php
/**
 * Site footer, ported 1:1 from the original footer.php.
 *
 * @package citcom
 */

$citcom_social_icons  = citcom_get_option( 'social_icon_links' );
$citcom_footer_hidden = '';
if ( is_singular( 'landing-page' ) && ! get_field( 'show_footer' ) ) {
	$citcom_footer_hidden = 'hidden';
}

?>

<footer class="overflow-hidden <?php echo esc_attr( $citcom_footer_hidden ); ?>">
	<div class="bg-citdot ratio ratio-1x1 bg-primary citdot position-absolute fixed-top z-1"></div>
	<div class="container-xl position-relative z-2">
		<a class="footer-logo d-block btn btn-link rounded-pill focus-ring focus-ring-light" href="<?php echo esc_url( home_url() ); ?>">
			<span class="visually-hidden">Home</span>
			<?php echo citcom_logo( 'light' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</a>
		<div class="row mt-4 justify-content-between">
			<div class="col-12 col-lg-7 text-light order-2 order-md-1">
				<div class="row justify-content-between text-center text-md-start">
					<div class="col-12 col-md-6 text-light order-2 order-md-1">
						<ul class="fa-ul lh-sm ms-0">
							<li class="py-2 ps-2"><a href="mailto:info@citcom.co.uk" class="link-offset-2 link-underline-secondary link-underline-opacity-0 link-underline-opacity-100-hover focus-ring focus-ring-light rounded-pill"><span class="fa-li"><i class="fa-light fa-envelope text-secondary fa-lg"></i></span>info@citcom.co.uk</a></li>
							<li class="py-2 ps-2"><a href="tel:01299872450" class="link-offset-2 link-underline-secondary link-underline-opacity-0 link-underline-opacity-100-hover focus-ring focus-ring-light rounded-pill"><span class="fa-li"><i class="fa-light fa-phone text-secondary fa-lg"></i></span>01299 872450</a></li>
							<li class="py-2 ps-2"><span class="fa-li"><i class="fa-light fa-location-dot text-secondary fa-lg"></i></span>Unit 7, The Towers, Foley Avenue,<br>Kidderminster, DY11 7PG</li>
						</ul>
						<p><small>VAT Number 834 0308 57<br><strong>© Citizen Communication Media Ltd.</strong></small></p>
					</div>
					<div class="col-12 col-md-5 text-light order-1 order-md-2">
						<div class="row row-cols-1 row-cols-md-2 mb-5 mb-md-0">
							<div class="col">
								<?php
								wp_nav_menu(
									array(
										'menu'            => 'Footer 1',
										'theme_location'  => 'footer_menu_1',
										'container'       => 'nav',
										'container_id'    => 'footer-menu-1',
										'container_class' => 'footer-menu bold',
										'menu_id'         => 'footer-nav-1',
										'menu_class'      => 'nav flex-md-column justify-content-center justify-content-md-start',
										'depth'           => 1,
										'fallback_cb'     => 'Citcom_Nav_Walker::fallback',
										'walker'          => new Citcom_Nav_Walker(),
									)
								);
								?>
							</div>
							<div class="col ">
								<?php
								wp_nav_menu(
									array(
										'menu'            => 'Footer 2',
										'theme_location'  => 'footer_menu_2',
										'container'       => 'nav',
										'container_id'    => 'footer-menu-2',
										'container_class' => 'footer-menu',
										'menu_id'         => 'footer-nav-2',
										'menu_class'      => 'nav flex-md-column justify-content-center justify-content-md-start',
										'depth'           => 1,
										'fallback_cb'     => 'Citcom_Nav_Walker::fallback',
										'walker'          => new Citcom_Nav_Walker(),
									)
								);
								?>
							</div>
						</div>

					</div>
				</div>
			</div>
			<div class="col-12 col-md-5 col-lg-4 text-light text-center text-md-start order-1 order-md-2 mb-5 mb-md-0">
				<p class="mb-0">Sign up for Citcom updates, news and trends…</p>
				<div class="input-group signup mt-3 mb-4 mx-auto bg-light rounded-pill" onclick="window.location.href='/sign-up-for-our-monthly-marketing-round-up-newsletter/'" style="cursor: pointer;">
					<input type="text" class="form-control rounded-start-pill ps-4" placeholder="Enter your email address…." aria-label="Recipient's username" aria-describedby="button-addon2">
					<button class="btn btn-secondary rounded-pill px-4" type="button" id="button-addon2">Submit</button>
				</div>
				<div class="social-icons d-flex gap-3 justify-content-center justify-content-md-start">
					<?php foreach ( (array) $citcom_social_icons as $citcom_social ) : ?>
						<?php
						if ( empty( $citcom_social['link']['url'] ) ) {
							continue;
						}
						$citcom_social_title = $citcom_social['link']['title'] ?? '';
						?>
						<a href="<?php echo esc_url( $citcom_social['link']['url'] ); ?>" aria-label="<?php echo esc_attr( $citcom_social_title ); ?>" class="d-block rounded-2 focus-ring focus-ring-light" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-custom-class="citcom-tooltip" data-bs-title="<?php echo esc_attr( $citcom_social_title ); ?>" target="_blank">
							<div class="citdot btn btn-light btn-icon">
								<i class="<?php echo esc_attr( $citcom_social['icon'] ?? '' ); ?> fa-fw"></i>
							</div>
						</a>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	</div>
</footer>
<div class="bg-light footer-end py-2 <?php echo esc_attr( $citcom_footer_hidden ); ?>">
	<div class="container-xl position-relative z-2">
		<div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
			<?php
			foreach ( (array) citcom_get_option( 'footer_logos' ) as $citcom_accreditation_id ) {
				echo the_image( $citcom_accreditation_id, 'accreditation', '', 'cred' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			?>
		</div>
	</div>
</div>

<div id="signup-newsletter-modal" class="modal fade" tabindex="-1" aria-labelledby="signup-newsletter-modal" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
		<div class="modal-content">
			<div class="modal-container position-relative bg-default p-4">
				<div class="modal-body">
					<button type="button" class="btn btn-outline-primary p-1 lh-1 position-absolute top-0 end-0" data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark fa-fw"></i></button>
					<p class="h3 mb-4 lh-1 w-75">Sign up for our Monthly Marketing<br>Round-Up Newsletter</p>
					<?php // The old theme only loaded this form on the blog listing, so the popup opened empty everywhere else. ?>
					<?php echo citcom_render_form( 'newsletter' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in citcom_render_form() ?>
				</div>
			</div>
		</div>
	</div>
</div>

<div id="send-brief-modal" class="modal fade" tabindex="-1" aria-labelledby="send-brief-modal" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
		<div class="modal-content">
			<div class="modal-container position-relative bg-default p-4">
				<div class="modal-body">
					<button type="button" class="btn btn-outline-primary p-1 lh-1 position-absolute top-0 end-0" data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark fa-fw"></i></button>
					<p class="h3 mb-4 lh-1 w-75">Send us a brief</p>
					<?php // The old theme had this form commented out, so the popup opened empty. ?>
					<?php echo citcom_render_form( 'brief' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in citcom_render_form() ?>
				</div>
			</div>
		</div>
	</div>
</div>

<div id="outdated"></div>

<?php echo citcom_svg_clip_paths(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

<div class="clipped"></div>

<div id="wp-scripts">

	<?php wp_footer(); ?>

</div>

<!-- Trustindex verified -->
<script defer async src='https://cdn.trustindex.io/loader-cert.js?62151d265fe0109be77674ec245'></script>

<script>
	function addLoadEvent(func) {
		var oldonload = window.onload;
		if (typeof window.onload != 'function') {
			window.onload = func;
		} else {
			window.onload = function() {
				if (oldonload) {
					oldonload();
				}
				func();
			}
		}
	}
	//call plugin function after DOM ready
	addLoadEvent(function() {
		if (typeof outdatedBrowser === 'function') {
			outdatedBrowser({
				bgColor: '#f25648',
				color: '#ffffff',
				lowerThan: 'objectFit',
				languagePath: 'assets/js/lang/en.html'
			})
		}
	});
</script>

</body>

</html>
