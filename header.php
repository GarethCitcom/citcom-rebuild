<!DOCTYPE html>
<html <?php language_attributes(); ?> class="no-js no-animation">

<head>
	<meta charset="utf-8" />
	<meta http-equiv="x-ua-compatible" content="ie=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, minimum-scale=1">

	<title>
		<?php

		$citcom_post_type = get_query_var( 'post_type' );
		$citcom_post_id   = get_the_ID();
		$citcom_options   = citcom_get_cached_options();

		// Archives take their SEO title from the template post that renders them.
		if ( is_post_type_archive( 'case-study' ) ) {
			$citcom_post_id = $citcom_options['case_study_archive'];
		}
		if ( is_post_type_archive( 'service' ) ) {
			$citcom_post_id = $citcom_options['services_archive'];
		}
		if ( is_tax( 'cs-tag' ) && 'case-study' === $citcom_post_type ) {
			$citcom_post_id = $citcom_options['case_studies_tag_archive'];
		}
		if ( is_category() ) {
			$citcom_post_id = $citcom_options['category_archive'];
		}
		if ( is_tag() ) {
			$citcom_post_id = $citcom_options['tag_archive'];
		}

		$citcom_seo_title = false;
		if ( function_exists( 'smartcrawl_get_value' ) ) {
			$citcom_seo_title = smartcrawl_get_value( 'title', $citcom_post_id );
		}

		if ( $citcom_seo_title ) {
			echo esc_html( $citcom_seo_title );
		} elseif ( is_front_page() ) {
			echo 'CitCom.';
		} else {
			wp_title( '| CitCom.', true, 'right' );
		}

		?>
	</title>

	<!-- Preconnect to external domains for faster loading -->
	<link rel="preconnect" href="https://code.jquery.com" crossorigin>
	<link rel="preconnect" href="https://kit.fontawesome.com" crossorigin>
	<link rel="dns-prefetch" href="https://ajax.googleapis.com">

	<?php wp_head(); ?>

	<!-- Google Tag Manager -->
	<script type="text/plain" data-cookie-consent="tracking">
		(function(w, d, s, l, i) {
			w[l] = w[l] || [];
			w[l].push({
				'gtm.start': new Date().getTime(),
				event: 'gtm.js'
			});
			var f = d.getElementsByTagName(s)[0],
				j = d.createElement(s),
				dl = l != 'dataLayer' ? '&l=' + l : '';
			j.async = true;
			j.src =
				'https://www.googletagmanager.com/gtm.js?id=' + i + dl;
			f.parentNode.insertBefore(j, f);
		})(window, document, 'script', 'dataLayer', 'GTM-KQQPQS8');
	</script>
	<!-- End Google Tag Manager -->

</head>

<body <?php body_class(); ?>>

	<?php

	// Landing pages can hide the header (Landing page setting field group).
	$citcom_header_hidden = '';
	if ( is_singular( 'landing-page' ) && ! get_field( 'show_header' ) ) {
		$citcom_header_hidden = 'hidden';
	}

	?>

	<!-- Google Tag Manager (noscript) -->
	<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-KQQPQS8" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
	<!-- End Google Tag Manager (noscript) -->

	<div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasmenu" aria-labelledby="Navigation">
		<div class="offcanvas-header bg-dark text-light pe-5" data-bs-theme="dark">
			<div class="offcanvas-title" id="Navigation">
				<a class="header-logo d-block focus-ring focus-ring-secondary rounded-4" href="<?php echo esc_url( home_url() ); ?>">
					<span class="visually-hidden">Home</span><?php echo citcom_logo( 'light_default' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
			</div>
			<button type="button" class="btn-close fs-2" data-bs-dismiss="offcanvas" aria-label="Close">
			</button>
		</div>
		<div class="offcanvas-body bg-default_lighter d-flex flex-column">
			<?php
			wp_nav_menu(
				array(
					'menu'            => 'Primary',
					'theme_location'  => 'primary_menu',
					'container'       => 'nav',
					'container_id'    => 'primary-mobile-menu',
					'container_class' => '',
					'menu_id'         => 'mobile-nav',
					'menu_class'      => 'nav h-100 rounded-pill nav-pills flex-column',
					'depth'           => 2,
					'fallback_cb'     => 'Citcom_Nav_Walker::fallback',
					'walker'          => new Citcom_Nav_Walker(),
				)
			);
			?>
			<div class="offcanvas-end py-3 mt-3 d-flex flex-column justify-content-end gap-4 flex-fill">
				<div class="search-citcom-mob w-100">
					<form class="site-search" action="/">
						<input class="search-input bg-default_lighter border-default form-control form-control-lg rounded-pill" name="s" type="text" placeholder="Search ..." aria-label="Search the site">
						<input type="submit" value="Search" class="visually-hidden" />
					</form>
				</div>
				<button type="button" data-bs-toggle="modal" data-bs-target="#send-brief-modal" class="btn btn-secondary btn-lg text-center rounded-pill fw-bold">Send a brief</button>
			</div>
		</div>
	</div>
	<div class="header-fixer <?php echo esc_attr( $citcom_header_hidden ); ?>"></div>
	<header id="header" class="citcom-header bg-default_lighter <?php echo esc_attr( $citcom_header_hidden ); ?>">

		<div class="container-fluid d-flex gap-2 gap-lg-4 align-items-stretch justify-content-between justify-content-sm-start position-relative">
			<div class="left me-auto">
				<a class="header-logo d-block btn btn-link rounded-pill" href="<?php echo esc_url( home_url() ); ?>">
					<span class="visually-hidden">Home</span>
					<?php echo citcom_logo( 'dark' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</a>
			</div>
			<div class="center d-flex gap-3 align-items-center justify-content-between justify-content-lg-center flex-fill position-relative z-2 flex-row-reverse flex-lg-row ps-lg-0 ps-3">
				<div class="offcanvas-menu-btn d-lg-none">
					<button type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasmenu" aria-controls="offcanvasmenu" class="citcom-btn citcom-btn-secondary citcom-btn-lg focus-ring focus-ring-secondary text-nowrap">
						<div class="citcom-btn-bg"></div>
						<span class="citcom-btn-icon">
							<svg class="svg-inline--fa fa-bars fa-fw" aria-hidden="true" focusable="false" data-prefix="fal" data-icon="bars" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" style="height: 1.15rem;">
								<path d="M0 80c0-8.8 7.2-16 16-16l416 0c8.8 0 16 7.2 16 16s-7.2 16-16 16L16 96C7.2 96 0 88.8 0 80zM0 240c0-8.8 7.2-16 16-16l416 0c8.8 0 16 7.2 16 16s-7.2 16-16 16L16 256c-8.8 0-16-7.2-16-16zM448 400c0 8.8-7.2 16-16 16L16 416c-8.8 0-16-7.2-16-16s7.2-16 16-16l416 0c8.8 0 16 7.2 16 16z" />
							</svg>
						</span>
						Menu
					</button>
				</div>
				<div class="citcom-nav rounded-pill bg-default d-none d-lg-block w-100">
					<?php
					wp_nav_menu(
						array(
							'menu'            => 'Primary',
							'theme_location'  => 'primary_menu',
							'container'       => 'nav',
							'container_id'    => 'primary-menu',
							'container_class' => ' w-100',
							'menu_id'         => 'header-nav',
							'menu_class'      => 'nav w-100 rounded-pill nav-pills nav-justified flex-nowrap',
							'depth'           => 2,
							'fallback_cb'     => 'Citcom_Nav_Walker::fallback',
							'walker'          => new Citcom_Nav_Walker(),
						)
					);
					?>
					<div class="citcom-nav-pill"></div>
				</div>
				<div class="search-citcom d-none d-sm-flex align-self-stretch citcom-input-btn citcom-input-btn-default focus-ring focus-ring-default">
					<div class="citcom-btn-bg"></div>
					<span class="citcom-btn-icon">
						<svg class="svg-inline--fa fa-magnifying-glass fa-fw" aria-hidden="true" focusable="false" data-prefix="fal" data-icon="fa-magnifying-glass" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" style="height: 1.15rem;">
							<path d="M384 208A176 176 0 1 0 32 208a176 176 0 1 0 352 0zM343.3 366C307 397.2 259.7 416 208 416C93.1 416 0 322.9 0 208S93.1 0 208 0S416 93.1 416 208c0 51.7-18.8 99-50 135.3L507.3 484.7c6.2 6.2 6.2 16.4 0 22.6s-16.4 6.2-22.6 0L343.3 366z" />
						</svg>
					</span>
					<form class="site-search" action="/">
						<input class="search-input position-absolute form-control rounded-pill" name="s" type="text" placeholder="Search ..." aria-label="Search the site">
						<input type="submit" value="Search" class="visually-hidden" />
					</form>
				</div>
			</div>
			<div class="right d-none d-md-flex align-items-center gap-3 ps-lg-3 ms-auto position-relative z-1 blur-transition">
				<a href="/send-us-a-brief" class="citcom-btn citcom-btn-secondary citcom-btn-lg focus-ring focus-ring-secondary text-nowrap">
					<div class="citcom-btn-bg"></div>
					<span class="citcom-btn-icon">
						<svg class="svg-inline--fa fa-paper-plane-top fa-fw" aria-hidden="true" focusable="false" data-prefix="fal" data-icon="paper-plane-top" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" data-fa-i2svg="" style="height: 1.15rem;">
							<path fill="currentColor" d="M3.4 78.3c-6-12-3.9-26.5 5.3-36.3s23.5-12.7 35.9-7.5l448 192c11.8 5 19.4 16.6 19.4 29.4s-7.6 24.4-19.4 29.4l-448 192c-12.3 5.3-26.7 2.3-35.9-7.5s-11.3-24.3-5.3-36.3L92.2 256 3.4 78.3zM120 272L32 448 442.7 272 120 272zm322.7-32L32 64l88 176 322.7 0z"></path>
						</svg>
					</span>
					Send us a brief
				</a>
			</div>
		</div>
	</header>
