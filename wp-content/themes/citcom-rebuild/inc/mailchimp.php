<?php
/**
 * Mailchimp: the one integration the theme forms need.
 *
 * The API key comes from the CITCOM_MAILCHIMP_API_KEY constant (wp-config.php)
 * or, failing that, Site Settings > Forms; the audience id from Site Settings
 * (default: the audience the old Forminator add-on used). Without a key the
 * form still works and the submission records that the signup was skipped.
 *
 * @package citcom
 */

defined( 'ABSPATH' ) || exit;

/**
 * API key and audience id.
 *
 * @return array{key:string,list:string}
 */
function citcom_mailchimp_config(): array {
	$key  = defined( 'CITCOM_MAILCHIMP_API_KEY' ) ? (string) CITCOM_MAILCHIMP_API_KEY : (string) get_field( 'mailchimp_api_key', 'option' );
	$list = (string) get_field( 'mailchimp_audience_id', 'option' );
	return array(
		'key'  => trim( $key ),
		'list' => trim( $list ) ?: 'a4ffec5bd1',
	);
}

/**
 * Add or update an audience member.
 *
 * Subscribes straight away (no double opt-in), as the Forminator add-on was
 * set to; the forms ask for consent with a tick box before this runs.
 *
 * @param string $email    Address.
 * @param array  $merge    Merge fields, e.g. FNAME, LNAME, MMERGE3.
 * @param array  $settings tags (names), interests (ids), permissions (marketing permission ids).
 * @return string A short status for the stored submission.
 */
function citcom_mailchimp_subscribe( string $email, array $merge, array $settings ): string {
	$config = citcom_mailchimp_config();
	if ( '' === $config['key'] || false === strpos( $config['key'], '-' ) ) {
		return 'Mailchimp: skipped, no API key set';
	}
	$server = substr( $config['key'], strrpos( $config['key'], '-' ) + 1 );
	$member = 'https://' . $server . '.api.mailchimp.com/3.0/lists/' . rawurlencode( $config['list'] ) . '/members/' . md5( strtolower( $email ) );
	$auth   = array(
		'Authorization' => 'Basic ' . base64_encode( 'citcom:' . $config['key'] ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- HTTP basic auth
		'Content-Type'  => 'application/json',
	);

	$body = array(
		'email_address' => $email,
		'status_if_new' => 'subscribed',
		'status'        => 'subscribed',
	);
	if ( $merge ) {
		$body['merge_fields'] = $merge;
	}
	foreach ( (array) ( $settings['interests'] ?? array() ) as $interest ) {
		$body['interests'][ $interest ] = true;
	}
	foreach ( (array) ( $settings['permissions'] ?? array() ) as $permission ) {
		$body['marketing_permissions'][] = array(
			'marketing_permission_id' => $permission,
			'enabled'                 => true,
		);
	}

	$response = wp_remote_request(
		$member,
		array(
			'method'  => 'PUT',
			'timeout' => 8,
			'headers' => $auth,
			'body'    => wp_json_encode( $body ),
		)
	);
	if ( is_wp_error( $response ) ) {
		return 'Mailchimp: failed, ' . $response->get_error_message();
	}
	$code = (int) wp_remote_retrieve_response_code( $response );
	if ( $code < 200 || $code >= 300 ) {
		$detail = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		return 'Mailchimp: failed, ' . ( is_array( $detail ) ? (string) ( $detail['title'] ?? $code ) : (string) $code );
	}

	$tags = array();
	foreach ( (array) ( $settings['tags'] ?? array() ) as $tag ) {
		$tags[] = array(
			'name'   => $tag,
			'status' => 'active',
		);
	}
	if ( $tags ) {
		wp_remote_post(
			$member . '/tags',
			array(
				'timeout' => 8,
				'headers' => $auth,
				'body'    => wp_json_encode( array( 'tags' => $tags ) ),
			)
		);
	}

	return 'Mailchimp: subscribed';
}
