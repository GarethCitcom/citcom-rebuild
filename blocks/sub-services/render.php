<?php
/**
 * citcom/sub-services
 *
 * Mirrors flexSubServices() (templates/flexFunctions/sub_services.php): a
 * sticky pill nav of the chosen services and a service row for each, from
 * template-parts/service-card.php.
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

$sub_services = array_filter( array_map( 'intval', (array) ( $fields['select_sub_services'] ?? array() ) ) );
$nav_bg       = ( $fields['background_colour'] ?? 'default' ) === 'choose' ? citcom_choice_label( 'background_color', $fields['background_color'] ?? '' ) : 'light';

global $post;
$citcom_previous_post = $post;

?>

<section <?php echo $attrs['anchor_attr']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="section-padding flex-sub_services <?php echo esc_attr( $attrs['classes'] ); ?>" data-index="<?php echo (int) $index; ?>">

	<nav id="sub-services-nav" class="d-none d-md-flex bg-<?php echo esc_attr( $nav_bg ); ?>">
		<div class="container-xl">
			<ul class="nav w-100 rounded-pill nav-pills nav-pills-secondary nav-justified flex-nowrap bg-light">

				<?php
				$i = 0;
				foreach ( $sub_services as $service ) :
					$post = get_post( $service ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
					if ( ! $post ) {
						continue;
					}
					setup_postdata( $post );
					$active = 0 === $i ? 'active' : '';
					?>

					<li class="nav-item text-truncate">
						<a class="sub-link nav-link rounded-pill py-2 py-lg-3 px-4 text-truncate <?php echo esc_attr( $active ); ?>" aria-current="page" id="menu-<?php echo esc_attr( $post->post_name ); ?>" href="#<?php echo esc_attr( $post->post_name ); ?>"><?php the_title(); ?></a>
					</li>

					<?php
					wp_reset_postdata();
					$i++;
				endforeach;
				?>

			</ul>
		</div>
	</nav>
	<div class="hide-border d-none d-md-block bg-<?php echo esc_attr( $nav_bg ); ?>"></div>

	<div class="container-xl">
		<div id="sub-services-list">

			<?php
			foreach ( $sub_services as $service ) :
				$post = get_post( $service ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
				if ( ! $post ) {
					continue;
				}
				setup_postdata( $post );
				get_template_part( 'template-parts/service-card' );
				wp_reset_postdata();
			endforeach;
			if ( ! $sub_services && $is_preview ) {
				echo '<p><em>' . esc_html__( 'Select the services to list in the block settings.', 'citcom' ) . '</em></p>';
			}
			?>

		</div>
	</div>

</section>

<?php
$post = $citcom_previous_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
if ( $post ) {
	setup_postdata( $post );
}
