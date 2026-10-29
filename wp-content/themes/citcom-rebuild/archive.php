<?php
/**
 * Archives render the blocks of a "template" post chosen in Site Settings >
 * Templates (case studies, case study tags, services); blog category and tag
 * archives fall back to the Posts page, as before.
 *
 * @package citcom
 */

get_header();

$citcom_options        = citcom_get_cached_options();
$citcom_page_for_posts = get_option( 'page_for_posts' );
$citcom_archive_type   = get_archive_post_type();

if ( is_archive() && 'case-study' === $citcom_archive_type ) {
	$citcom_page_for_posts = $citcom_options['case_study_archive'];
}
if ( is_tax( 'cs-tag' ) ) {
	$citcom_page_for_posts = $citcom_options['case_studies_tag_archive'];
}
if ( is_archive() && 'service' === $citcom_archive_type ) {
	$citcom_page_for_posts = $citcom_options['services_archive'];
}

?>

<main>
	<?php citcom_render_template_post( $citcom_page_for_posts ); ?>
</main>

<?php

get_footer();
