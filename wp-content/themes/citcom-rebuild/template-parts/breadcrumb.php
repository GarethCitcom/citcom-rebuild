<?php
/**
 * Bootstrap breadcrumb, ported from functions/theme_content/bs-breadcrumb.php.
 *
 * The trail itself comes from citcom_breadcrumb_items() (inc/breadcrumb.php),
 * which the BreadcrumbList in inc/schema.php reads too.
 *
 * @var array $args { home: bool }
 *
 * @package citcom
 */

$citcom_items = citcom_breadcrumb_items( ! isset( $args['home'] ) || $args['home'] );

?>

<nav style="--bs-breadcrumb-divider: '/';" aria-label="breadcrumb">
	<ol class="breadcrumb mb-0">
		<?php foreach ( $citcom_items as $citcom_item ) : ?>
			<?php if ( ! empty( $citcom_item['icon'] ) ) : ?>
				<li class="breadcrumb-item"><a href="<?php echo esc_url( $citcom_item['url'] ); ?>"><i class="fa-regular fa-house-blank"></i></a></li>
			<?php elseif ( ! empty( $citcom_item['current'] ) ) : ?>
				<li class="breadcrumb-item active" aria-current="page"><?php echo isset( $citcom_item['html'] ) ? wp_kses_post( $citcom_item['html'] ) : esc_html( $citcom_item['name'] ); ?></li>
			<?php else : ?>
				<li class="breadcrumb-item"><a href="<?php echo esc_url( $citcom_item['url'] ); ?>"><?php echo esc_html( $citcom_item['name'] ); ?></a></li>
			<?php endif; ?>
		<?php endforeach; ?>
	</ol>
</nav>
