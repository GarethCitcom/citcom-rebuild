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
					<?php if ( is_home() ) : ?>
						<?php echo do_shortcode( '[forminator_form id="581"]' ); ?>
					<?php endif; ?>
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
				</div>
			</div>
		</div>
	</div>
</div>

<div id="outdated"></div>

<svg class="clipped-svg">
	<clipPath id="citdot" clipPathUnits="objectBoundingBox">
		<path d="M0.908,0.015 c0.052,0.011,0.081,0.041,0.085,0.115 c0.007,0.122,0.007,0.27,0.007,0.37 s0,0.248,-0.007,0.37 c-0.004,0.074,-0.033,0.104,-0.085,0.115 c-0.048,0.011,-0.254,0.015,-0.408,0.015 s-0.36,-0.004,-0.408,-0.015 C0.04,0.974,0.011,0.944,0.007,0.87 C0,0.748,0,0.6,0,0.5 S0,0.252,0.007,0.13 C0.011,0.056,0.04,0.026,0.092,0.015 C0.14,0.004,0.346,0,0.5,0 S0.86,0.004,0.908,0.015"></path>
	</clipPath>
</svg>

<svg class="clipped-svg">
	<clipPath id="corner-invert" clipPathUnits="objectBoundingBox">
		<path d="M1,0.08 V0.932 a0.051,0.074,0,0,1,-0.051,0.074 h-0.059 c-0.028,0,-0.051,-0.008,-0.051,-0.049 V0.938 a0.051,0.074,0,0,0,-0.051,-0.074 h-0.288 c-0.028,0,-0.051,-0.058,-0.051,-0.099 V0.314 a0.051,0.074,0,0,0,-0.051,-0.074 H0.055 a0.051,0.074,0,0,1,-0.051,-0.074 v-0.086 a0.051,0.074,0,0,1,0.051,-0.074 H0.953 A0.051,0.074,0,0,1,1,0.08"></path>
	</clipPath>
</svg>

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
