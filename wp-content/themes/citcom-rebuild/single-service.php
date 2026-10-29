<?php
/**
 * Single service: the blocks in post_content provide every section.
 *
 * @package citcom
 */

get_header();

?>

<main>
	<?php
	while ( have_posts() ) {
		the_post();
		the_content();
	}
	?>
</main>

<?php

get_footer();
