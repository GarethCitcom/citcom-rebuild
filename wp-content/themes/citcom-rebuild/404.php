<?php
/**
 * Not found.
 *
 * @package citcom
 */

get_header();

?>

<main class="page-content">
	<section style="min-height:80vh;">
		<div class="container">
			<div class="row justify-content-center text-center py-5">
				<div class="col-12 col-sm-10 col-md-8 col-lg-6">
					<p class="grad-persian fw-bolder mb-5" style="-webkit-background-clip: text; color: transparent; font-weight: 800!important; font-size: 7rem;">OOPS!</p>
					<h1 class="text-primary fw-bold"><i class="fa-light fa-diamond-exclamation fa-lg mb-2"></i><br>404 <span class="fw-medium text-dark">ERROR</span></h1>

					<p class="fw-bold">Sorry but the page you are looking for was not found.</p>
					<p>Click <a href="#" onclick="window.history.back();">here</a> to go back to the previous page or click below to go to the home page.</p>
					<a href="/" class="btn btn-primary px-4 rounded-pill shadow mt-3">Home Page</a>
				</div>
			</div>
		</div>
	</section>
</main>

<?php

get_footer();
