<?php
/**
 * Shortcodes the site content relies on (functions/theme_content/shortcode.php).
 *
 * @package citcom
 */

defined( 'ABSPATH' ) || exit;

add_shortcode(
	'google_reviews',
	function () {
		return '<script src="https://static.elfsight.com/platform/platform.js" async></script>
<div class="elfsight-app-521e9abe-cc94-48d9-a8af-616726273ef5" data-elfsight-app-lazy></div>';
	}
);

add_shortcode(
	'youtube_gallery',
	function () {
		return '<script src="https://static.elfsight.com/platform/platform.js" async></script>
<div class="elfsight-app-521e9abe-cc94-48d9-a8af-616726273ef5" data-elfsight-app-lazy></div>';
	}
);

add_shortcode(
	'chatcom',
	function () {
		return '<iframe src="https://aiserve247.com/aiserve247/200736ba40bc48b08e6fed58cd7e5d49" width="100%" height="496" style="background: #ffffff;overflow: hidden;border-radius: 20px;max-width: 56rem;margin:0 auto;"></iframe>';
	}
);
