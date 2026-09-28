<?php
/**
 * Bootstrap breadcrumb, ported from functions/theme_content/bs-breadcrumb.php.
 *
 * @var array $args { home: bool }
 *
 * @package citcom
 */

global $post;

$citcom_home = ! isset( $args['home'] ) || $args['home'];

$citcom_blog_page = get_option( 'page_for_posts' );
$citcom_blog_link = get_permalink( $citcom_blog_page );
$citcom_blog_name = get_the_title( $citcom_blog_page );

$citcom_options       = citcom_get_cached_options();
$citcom_cs_link       = get_post_type_archive_link( 'case-study' );
$citcom_cs_name       = get_the_title( $citcom_options['case_study_archive'] );
$citcom_services_link = get_post_type_archive_link( 'service' );
$citcom_services_name = get_the_title( $citcom_options['services_archive'] );

$citcom_archive_type = get_archive_post_type();

?>

<nav style="--bs-breadcrumb-divider: '/';" aria-label="breadcrumb">
	<ol class="breadcrumb mb-0">
		<?php if ( $citcom_home ) : ?>
			<li class="breadcrumb-item"><a href="<?php echo esc_url( home_url() ); ?>"><i class="fa-regular fa-house-blank"></i></a></li>
		<?php endif; ?>
		<?php if ( is_singular( 'post' ) || is_category() || is_tag() ) : ?>
			<li class="breadcrumb-item"><a href="<?php echo esc_url( $citcom_blog_link ); ?>"><?php echo esc_html( $citcom_blog_name ); ?></a></li>
		<?php endif; ?>
		<?php if ( is_singular( 'case-study' ) || is_tax( 'cs-tag' ) ) : ?>
			<li class="breadcrumb-item"><a href="<?php echo esc_url( $citcom_cs_link ); ?>"><?php echo esc_html( $citcom_cs_name ); ?></a></li>
		<?php endif; ?>
		<?php if ( is_singular( 'service' ) ) : ?>
			<li class="breadcrumb-item"><a href="<?php echo esc_url( $citcom_services_link ); ?>"><?php echo esc_html( $citcom_services_name ); ?></a></li>
		<?php endif; ?>
		<?php
		if ( is_category() || is_tag() || is_tax( 'cs-tag' ) ) :
			$citcom_term_type = is_category() ? 'Category: ' : 'Tag: ';
			$citcom_term_name = $citcom_term_type . get_queried_object()->name;
			?>
			<li class="breadcrumb-item active" aria-current="page"><?php echo esc_html( $citcom_term_name ); ?></li>
		<?php endif; ?>
		<?php
		if ( is_singular( 'post' ) && has_category( '', $post->ID ) ) :
			$citcom_cats     = get_the_category();
			$citcom_category = $citcom_cats[0];
			?>
			<li class="breadcrumb-item"><a href="<?php echo esc_url( get_term_link( $citcom_category ) ); ?>"><?php echo esc_html( $citcom_category->name ); ?></a></li>
		<?php endif; ?>
		<?php
		if ( isset( $post ) && $post->post_parent ) :
			$citcom_crumbs    = array();
			$citcom_parent_id = $post->post_parent;
			while ( $citcom_parent_id ) {
				$citcom_page      = get_post( $citcom_parent_id );
				$citcom_crumbs[]  = '<li class="breadcrumb-item"><a href="' . esc_url( get_permalink( $citcom_page->ID ) ) . '">' . esc_html( get_the_title( $citcom_page->ID ) ) . '</a></li>';
				$citcom_parent_id = $citcom_page->post_parent;
			}
			echo implode( '', array_reverse( $citcom_crumbs ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		endif;
		?>
		<?php if ( is_singular( 'post' ) || is_page() || is_singular( 'case-study' ) || is_singular( 'service' ) ) : ?>
			<li class="breadcrumb-item active" aria-current="page"><?php the_title(); ?></li>
		<?php endif; ?>
		<?php if ( is_home() ) : ?>
			<li class="breadcrumb-item active" aria-current="page"><?php echo esc_html( $citcom_blog_name ); ?></li>
		<?php endif; ?>
		<?php if ( is_archive() && 'service' === $citcom_archive_type ) : ?>
			<li class="breadcrumb-item active" aria-current="page"><?php echo esc_html( $citcom_services_name ); ?></li>
		<?php endif; ?>
		<?php if ( is_archive() && 'case-study' === $citcom_archive_type ) : ?>
			<li class="breadcrumb-item active" aria-current="page"><?php echo esc_html( $citcom_cs_name ); ?></li>
		<?php endif; ?>
		<?php if ( is_search() ) : ?>
			<li class="breadcrumb-item active" aria-current="page">Search Results for... <em><?php the_search_query(); ?></em></li>
		<?php endif; ?>
	</ol>
</nav>
