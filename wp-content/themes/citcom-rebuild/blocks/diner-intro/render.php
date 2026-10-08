<?php
/**
 * citcom/diner-intro
 *
 * Mirrors flexDinerIntro() (templates/flexFunctions/diner_intro.php): the
 * "Serving bold ideas..." ribbon and the credentials stamp. The stamp here is
 * the older, section-scoped copy of the markup, not diner_stamp().
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

$heading        = (string) ( $fields['heading'] ?? '' );
$heading_accent = (string) ( $fields['heading_accent'] ?? '' );
$body           = (string) ( $fields['content'] ?? '' );
$stamp          = is_array( $fields['stamp'] ?? null ) ? $fields['stamp'] : array();
$show_stamp     = ! empty( $stamp['enabled'] );

?>

<section <?php echo $attrs['anchor_attr']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="flex-diner_intro diner-dots <?php echo esc_attr( $attrs['classes'] ); ?>" data-index="<?php echo (int) $index; ?>">

	<?php if ( $heading ) : ?>
		<?php
		// AOS sets its own transform, so it goes on the wrapper and the tilt stays on the ribbon.
		?>
		<div class="diner-intro-ribbon-wrap" data-aos="fade-up">
			<div class="diner-intro-ribbon">
				<?php
				// Drawn as two layers holding identical content so they wrap identically:
				// the first carries the strips with its text hidden, the second lays the
				// type over the top. fw-same stops the theme dropping spans in a heading
				// to 700; the design sets this in Extra Bold.
				$full = $heading . ( $heading_accent ? ' ' . $heading_accent : '' );
				?>
				<h2 class="diner-intro-heading">
					<span class="diner-intro-heading-strips fw-same" aria-hidden="true"><span class="diner-intro-heading-slab diner-intro-heading-mark fw-same"><?php echo esc_html( $full ); ?></span></span>
					<span class="diner-intro-heading-type fw-same"><span class="diner-intro-heading-slab fw-same"><span class="diner-intro-heading-ink fw-same"><?php echo esc_html( $heading ); ?></span>
					<?php
					if ( $heading_accent ) :
						?>
						<span class="diner-intro-heading-accent fw-same"><?php echo esc_html( $heading_accent ); ?></span><?php endif; ?></span></span>
				</h2>
			</div>
		</div>
	<?php endif; ?>

	<?php if ( $body ) : ?>
		<div class="diner-intro-copy" data-aos="fade-up">
			<?php echo wp_kses_post( wpautop( $body ) ); ?>
		</div>
	<?php endif; ?>

	<?php if ( $show_stamp ) : ?>
		<div class="diner-intro-stamp">
			<div class="diner-intro-stamp-inner">
				<?php if ( ! empty( $stamp['line_1'] ) ) : ?>
					<span class="diner-intro-stamp-over"><?php echo esc_html( $stamp['line_1'] ); ?></span>
				<?php endif; ?>
				<?php if ( ! empty( $stamp['line_2'] ) ) : ?>
					<span class="diner-intro-stamp-figure"><?php echo esc_html( $stamp['line_2'] ); ?></span>
				<?php endif; ?>
				<?php if ( ! empty( $stamp['line_3'] ) ) : ?>
					<span class="diner-intro-stamp-caption"><?php echo esc_html( $stamp['line_3'] ); ?></span>
				<?php endif; ?>
			</div>
		</div>
	<?php endif; ?>

</section>
