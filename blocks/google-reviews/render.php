<?php
/**
 * citcom/google-reviews
 *
 * Mirrors flexGoogleReviews() (templates/flexFunctions/google_reviews.php).
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

?>

<section <?php echo $attrs['anchor_attr']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="section-padding flex-google_reviews <?php echo esc_attr( $attrs['classes'] ); ?>" data-index="<?php echo (int) $index; ?>">

	<div class="container-xl">
		<div data-aos="blur-sm">
			<?php if ( $is_preview ) : ?>
				<p class="text-center"><em><?php esc_html_e( 'Google reviews widget (Elfsight) renders on the front end.', 'citcom' ); ?></em></p>
			<?php else : ?>
				<script src="https://static.elfsight.com/platform/platform.js" async></script>
				<div class="elfsight-app-521e9abe-cc94-48d9-a8af-616726273ef5" data-elfsight-app-lazy></div>
			<?php endif; ?>
		</div>
	</div>

</section>
