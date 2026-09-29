<?php
/*
Template Name: WC 2026 Sweepstake Hub
Template Post Type: landing-page
*/

/**
 * Landing page template for the WC 2026 sweepstake plugin's hub. The
 * [wc_sweepstake] shortcode comes from the wc2026-sweepstake plugin.
 *
 * @package citcom
 */

get_header();
?>
<div class="wc2026-sweepstake-hub">
	<?php echo do_shortcode( '[wc_sweepstake]' ); ?>
</div>
<?php
get_footer();
