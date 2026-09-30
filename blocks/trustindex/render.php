<?php
/**
 * citcom/trustindex
 *
 * A Trustindex widget in the standard section. Replaces the old google_reviews
 * layout, which printed an Elfsight embed that is no longer used; the section
 * wrapper is the same as flexGoogleReviews() had. The widget itself comes from
 * citcom_trustindex_embed() in inc/shortcodes.php.
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

$code = trim( (string) ( $fields['trustindex_code'] ?? '' ) );
if ( '' === $code ) {
	$code = citcom_trustindex_default();
}
$kind = citcom_trustindex_kind( $code );

?>

<section <?php echo $attrs['anchor_attr']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="section-padding flex-trustindex <?php echo esc_attr( $attrs['classes'] ); ?>" data-index="<?php echo (int) $index; ?>">

	<div class="container-xl">
		<div data-aos="blur-sm">
			<?php if ( $is_preview ) : ?>
				<?php // The widget is drawn by Trustindex's loader script, which does not run in the editor canvas. ?>
				<div class="text-center p-4 border rounded-3">
					<p class="mb-1"><strong><?php esc_html_e( 'Trustindex widget', 'citcom' ); ?></strong></p>
					<p class="mb-0"><code><?php echo esc_html( $code ); ?></code></p>
					<?php if ( '' === $kind ) : ?>
						<p class="mb-0 mt-2 text-danger"><?php esc_html_e( 'This is not a Trustindex shortcode or embed snippet, so nothing will show on the page.', 'citcom' ); ?></p>
					<?php elseif ( 'shortcode' === $kind && '' === citcom_trustindex_embed( $code ) ) : ?>
						<p class="mb-0 mt-2 text-danger"><?php esc_html_e( 'The Trustindex plugin is not active on this site, so nothing will show on the page.', 'citcom' ); ?></p>
					<?php else : ?>
						<p class="mb-0 mt-2"><em><?php esc_html_e( 'The widget shows on the page itself.', 'citcom' ); ?></em></p>
					<?php endif; ?>
				</div>
			<?php else : ?>
				<?php echo citcom_trustindex_embed( $code ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trustindex shortcode output or a rebuilt loader script ?>
			<?php endif; ?>
		</div>
	</div>

</section>
