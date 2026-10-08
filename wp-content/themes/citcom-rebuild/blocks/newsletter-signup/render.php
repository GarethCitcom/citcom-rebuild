<?php
/**
 * citcom/newsletter-signup (was the acf/newsletter-signup block, blocks/newsletter-signup.php).
 *
 * Panel that opens the newsletter signup modal printed by footer.php.
 *
 * @var array  $block      Block settings and attributes.
 * @var string $content    Inner HTML (unused).
 * @var bool   $is_preview True in the editor.
 * @var int    $post_id    Post the block is saved to.
 *
 * @package citcom
 */

defined( 'ABSPATH' ) || exit;

$classes = '';
if ( ! empty( $block['className'] ) ) {
	$classes .= ' ' . $block['className'];
}
if ( $is_preview ) {
	$classes .= ' preview-block';
}

$title = (string) get_field( 'title' );

?>

<div class="signup-widget<?php echo esc_attr( $classes ); ?>">

	<?php if ( $is_preview ) : ?>

		<div style="background: #1C0221; color: #9AD14D; border-radius: 50rem; padding: 0.25rem 1.5rem;">
			<h4 style="display:flex;font-family: forma-djr-micro, sans-serif;">Newsletter signup <span class="dashicons dashicons-edit-large" style="margin-left:auto;"></span></h4>
		</div>

	<?php else : ?>

		<div class="signup-popup grad-persian text-light rounded-3 p-4 mb-3 position-relative" data-bs-toggle="modal" data-bs-target="#signup-newsletter-modal">
			<h4 class="signup-text"><?php echo esc_html( $title ); ?></h4>
			<div class="input-group signup mt-3 mb-2 mx-auto bg-light rounded-pill">
				<input type="text" class="form-control rounded-start-pill ps-4" placeholder="Email" aria-label="Recipient's username" aria-describedby="button-addon2">
				<button class="btn btn-primary rounded-pill px-4" type="button" id="button-addon2">Submit</button>
			</div>
		</div>

	<?php endif; ?>

</div>
