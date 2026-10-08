<?php
/**
 * citcom/video
 *
 * Mirrors flexVideo() (templates/flexFunctions/video.php). The player markup
 * is what src/js/vlite.js expects: a .vlite element with data-options and,
 * for autoplay, the volume toggle button.
 *
 * @var array  $block      Block settings and attributes.
 * @var string $content    Inner HTML (unused).
 * @var bool   $is_preview True in the editor.
 * @var int    $post_id    Post the block is saved to.
 *
 * @package citcom
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'extractYouTubeID' ) ) {
	/**
	 * YouTube video id from a watch URL.
	 *
	 * @param string $link URL.
	 * @return string
	 */
	function extractYouTubeID( $link ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- the old theme's name.
		$video_id = explode( '?v=', $link );
		if ( empty( $video_id[1] ) ) {
			$video_id = explode( '/v/', $link );
		}
		if ( empty( $video_id[1] ) ) {
			$video_id = explode( 'youtu.be/', $link );
		}
		$video_id = explode( '&', $video_id[1] ?? '' );
		return (string) $video_id[0];
	}
}

$fields = get_fields() ?: array();
$attrs  = citcom_section_attrs( $block, $fields );
$index  = citcom_block_index();

$video_bg_color = 'white';
if ( ( $fields['background_colour'] ?? 'default' ) === 'choose' ) {
	$video_bg_color = citcom_choice_label( 'background_color', $fields['background_color'] ?? '' ) ?: 'white';
}

$source     = ! empty( $fields['source'] ) ? (string) $fields['source'] : 'wp';
$poster     = false;
$path       = '';
$youtube_id = '';
if ( 'wp' === $source ) {
	$video = $fields['video'] ?? null;
	if ( is_array( $video ) ) {
		$path = esc_url( (string) ( $video['url'] ?? '' ) );
		if ( ! empty( $video['sizes']['xx_large'] ) ) {
			$poster = esc_url( $video['sizes']['xx_large'] );
		}
	} elseif ( is_numeric( $video ) ) {
		$path = esc_url( (string) wp_get_attachment_url( (int) $video ) );
	}
} else {
	$youtube_id = extractYouTubeID( esc_url( (string) ( $fields['youtube_link'] ?? '' ) ) );
}

$selected_options       = (array) ( $fields['player_options']['options'] ?? array() );
$default_player_options = array( 'controls', 'autoplay', 'bigPlay', 'loop', 'muted' );
$player_options         = array();
foreach ( $default_player_options as $option ) {
	$player_options[ $option ] = in_array( $option, $selected_options, true );
}
if ( ! empty( $fields['player_options']['video_poster'] ) ) {
	$custom_poster = $fields['player_options']['video_poster'];
	$poster        = esc_url( is_array( $custom_poster ) ? ( $custom_poster['url'] ?? '' ) : (string) $custom_poster );
}
if ( $poster ) {
	$player_options['poster'] = $poster;
}
$player_options['autoHide']      = true;
$player_options['playsinline']   = true;
$player_options['autoHideDelay'] = 2000;
$player_options_json             = wp_json_encode( $player_options, JSON_UNESCAPED_SLASHES );
// As rendered on the old site.

$video_section_id = uniqid();
$citdot           = ! empty( $fields['citdot_container'] ) ? 'citdot' : 'rounded-4 overflow-hidden';
$autoplay         = $player_options['autoplay'];

citcom_preview_clip_paths( (bool) $is_preview );
?>

<section <?php echo $attrs['anchor_attr']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="section-padding flex-video <?php echo esc_attr( $attrs['classes'] ); ?>" data-index="<?php echo (int) $index; ?>">

	<div class="video w-100">
		<div class="container-xl position-relative">
			<?php if ( $autoplay ) : ?>
				<button id="player-<?php echo esc_attr( $video_section_id ); ?>-volume" class="btn btn-link btn-volume unmute">
					<i class="fa-regular fa-volume"></i>
					<i class="fa-regular fa-volume-slash"></i>
				</button>
			<?php endif; ?>
			<div id="video-<?php echo esc_attr( $video_section_id ); ?>" class="<?php echo esc_attr( $citdot ); ?> v-vlite-container v-vlite-lazy" data-aos="blur">
				<?php if ( 'wp' === $source ) : ?>
					<video playsinline preload="none" id="player-<?php echo esc_attr( $video_section_id ); ?>" class="vlite" src="<?php echo esc_url( $path ); ?>" data-options="<?php echo esc_attr( $player_options_json ); ?>"></video>
				<?php else : ?>
					<div id="player-<?php echo esc_attr( $video_section_id ); ?>" class="vlite" data-youtube-id="<?php echo esc_attr( $youtube_id ); ?>" data-options="<?php echo esc_attr( $player_options_json ); ?>"></div>
				<?php endif; ?>
			</div>
		</div>
	</div>

</section>
<style>
	<?php
	echo '#video-' . esc_attr( $video_section_id ) . '{background-color: var(--brand-' . esc_attr( $video_bg_color ) . ');}';
	echo '#video-' . esc_attr( $video_section_id ) . ' .v-vlite.v-video{background-color: var(--brand-' . esc_attr( $video_bg_color ) . ');}';
	?>
</style>
