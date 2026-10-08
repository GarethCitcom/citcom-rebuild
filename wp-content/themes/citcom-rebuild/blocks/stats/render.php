<?php
/**
 * citcom/stats
 *
 * Mirrors flexStats() (templates/flexFunctions/stats.php), including the
 * per-stat inline style that animates the counter with a CSS @property.
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

$title        = (string) ( $fields['title'] ?? '' );
$opening_text = (string) ( $fields['opening_text'] ?? '' );
$number_stats = is_array( $fields['number_stats'] ?? null ) ? $fields['number_stats'] : array();


?>

<section <?php echo $attrs['anchor_attr']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="section-padding flex-stats <?php echo esc_attr( $attrs['classes'] ); ?>" data-index="<?php echo (int) $index; ?>">

	<div class="container-xl">
		<?php if ( $title ) : ?>
			<p class="h2 text-center text-sm-start" data-aos="fade-up"><?php echo esc_html( $title ); ?></p>
		<?php endif; ?>
		<?php if ( $opening_text ) : ?>
			<div class="opening-text text-center text-sm-start" data-aos="fade-up">
				<?php echo wp_kses_post( $opening_text ); ?>
			</div>
		<?php endif; ?>
		<div class="row justify-content-around mt-5 row-gap-4">
			<?php
			foreach ( $number_stats as $stat ) :
				$i = citcom_counter( 'stat' );
				// Unique on the page, also with several stats blocks.
				$value = (int) ( $stat['stat'] ?? 0 );
				$color = citcom_choice_label( 'stat_color', $stat['stat_color'] ?? 'secondary' );
				?>
				<div class="col-12 col-sm-6 col-md-3 text-center">
					<div class="stat" data-aos="zoom-in">
						<p class="h2 mb-2 lh-1 text-<?php echo esc_attr( $color ); ?>"><?php echo esc_html( (string) ( $stat['append'] ?? '' ) ); ?><span id="stat-<?php echo (int) $i; ?>" class="animate-stat fw-same" data-aos="stat"></span><?php echo esc_html( (string) ( $stat['prepend'] ?? '' ) ); ?>
						<p class="lead lh-sm"><?php echo esc_html( (string) ( $stat['supporting_text'] ?? '' ) ); ?></p>
					</div>
					<style>
						#stat-<?php echo (int) $i; ?> {

							transition: --num-stat-<?php echo (int) $i; ?> 3s cubic-bezier(0.25, 1, 0.5, 1);
							counter-set: num var(--num-stat-<?php echo (int) $i; ?>);
						}

						#stat-<?php echo (int) $i; ?>.aos-animate {
							--num-stat-<?php echo (int) $i; ?>: <?php echo (int) $value; ?>;
						}

						@property --num-stat-<?php echo (int) $i; ?> {
							syntax: "<integer>";
							initial-value: 0;
							inherits: false;
						}
					</style>
				</div>
			<?php endforeach; ?>
		</div>
	</div>

</section>
