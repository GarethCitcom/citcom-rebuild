<?php
/**
 * citcom/quote
 *
 * Mirrors flexQuote() (templates/flexFunctions/quote.php). As in the original,
 * the section has no section-padding class, ignores the Section settings and
 * only takes the anchor.
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

$quote  = (string) ( $fields['quote'] ?? '' );
$source = (string) ( $fields['source'] ?? '' );

?>

<section <?php echo $attrs['anchor_attr']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="flex-quote grad-persian overflow-hidden" data-index="<?php echo (int) $index; ?>">

	<div class="container-xl">
		<blockquote class="wp-block-quote mb-0 is-style-plain text-light" data-aos="fade">
			<?php echo wp_kses_post( $quote ); ?>
			<cite><?php echo wp_kses_post( $source ); ?></cite>
		</blockquote>
	</div>

</section>
