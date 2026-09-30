<?php
/**
 * Theme forms (replaces Forminator, decided 2026-09-30).
 *
 * - A form is a PHP file in forms/ that returns its definition; the file name
 *   is the form's slug. citcom_render_form() prints it (inc/forms-fields.php,
 *   with the field types, validation and variants), the REST route
 *   citcom/v1/forms/<slug> receives it.
 * - Every submission is validated on the server, stored as a private
 *   "citcom_submission" post (Form submissions in wp-admin), emailed to the
 *   recipients set in Site Settings > Forms, and, where the form says so, the
 *   sender is added to Mailchimp (inc/mailchimp.php).
 * - Stored submissions are deleted after CITCOM_FORMS_RETENTION_DAYS days by a
 *   daily cron event.
 * - Spam: a honeypot field, a minimum time on the page, and a per-address rate
 *   limit (five submissions in ten minutes). No nonce: the pages are cached, so a nonce would expire in the HTML.
 * - Old content keeps working: blocks and [forminator_form id="..."] shortcodes
 *   that carry a Forminator form id are mapped to the theme form through each
 *   definition's legacy_ids.
 *
 * @package citcom
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'CITCOM_FORMS_RETENTION_DAYS' ) ) {
	define( 'CITCOM_FORMS_RETENTION_DAYS', 30 );
}

// Forminator's shortcodes, which old content may still carry.
const CITCOM_FORMINATOR_SHORTCODES = array( 'forminator_form', 'forminator_quiz', 'forminator_poll' );

/**
 * All form definitions, keyed by slug.
 *
 * @return array<string,array>
 */
function citcom_forms(): array {
	static $forms = null;
	if ( null !== $forms ) {
		return $forms;
	}
	$forms = array();
	foreach ( (array) glob( CITCOM_THEME_DIR . '/forms/*.php' ) as $file ) {
		$form = include $file;
		if ( is_array( $form ) && ! empty( $form['rows'] ) ) {
			$form['slug']           = basename( $file, '.php' );
			$forms[ $form['slug'] ] = $form;
		}
	}
	$forms = (array) apply_filters( 'citcom_forms', $forms );
	return $forms;
}

/**
 * A form definition by slug or by the id of the Forminator form it replaced.
 *
 * @param string|int $id Slug, or a legacy Forminator form id.
 * @return array|null
 */
function citcom_form( $id ): ?array {
	$forms = citcom_forms();
	$id    = is_scalar( $id ) ? (string) $id : '';
	if ( isset( $forms[ $id ] ) ) {
		return $forms[ $id ];
	}
	if ( ctype_digit( $id ) ) {
		foreach ( $forms as $form ) {
			if ( in_array( (int) $id, array_map( 'intval', (array) ( $form['legacy_ids'] ?? array() ) ), true ) ) {
				return $form;
			}
		}
	}
	return null;
}

/**
 * The input fields of a form (everything but html rows), keyed by name.
 *
 * @param array $form Form definition.
 * @return array<string,array>
 */
function citcom_form_fields( array $form ): array {
	$fields = array();
	foreach ( (array) $form['rows'] as $row ) {
		foreach ( (array) $row as $field ) {
			if ( 'html' !== ( $field['type'] ?? 'text' ) && ! empty( $field['name'] ) ) {
				$fields[ $field['name'] ] = $field;
			}
		}
	}
	return $fields;
}

/**
 * Slug => title, for the "Form" select in the blocks.
 *
 * @return array<string,string>
 */
function citcom_form_choices(): array {
	$choices = array();
	foreach ( citcom_forms() as $slug => $form ) {
		$choices[ $slug ] = (string) ( $form['title'] ?? $slug );
	}
	return $choices;
}

/**
 * Email addresses a submission goes to.
 *
 * None are in code. Site Settings > Forms holds a general and a marketing
 * address, which forms refer to by group name, and an optional list of
 * addresses per form that replaces the form's default groups. A form's
 * recipients_if groups (the marketing copy on a mailing list signup) are added
 * either way.
 *
 * @param array $form   Form definition.
 * @param array $values Validated values.
 * @return string[]
 */
function citcom_form_recipients( array $form, array $values ): array {
	$split = static function ( $list ): array {
		return array_values( array_filter( (array) preg_split( '/[\s,;]+/', (string) $list ), 'is_email' ) );
	};
	$group = static function ( string $name ) use ( $split ): array {
		return $split( 'marketing' === $name ? get_field( 'forms_marketing_recipient', 'option' ) : get_field( 'forms_recipient', 'option' ) );
	};

	$addresses = array();
	foreach ( (array) get_field( 'forms_recipients', 'option' ) as $row ) {
		if ( is_array( $row ) && ( $row['form'] ?? '' ) === $form['slug'] ) {
			$addresses = array_merge( $addresses, $split( $row['addresses'] ?? '' ) );
		}
	}
	if ( ! $addresses ) {
		foreach ( (array) ( $form['recipients'] ?? array( 'general' ) ) as $name ) {
			$addresses = array_merge( $addresses, $group( (string) $name ) );
		}
	}
	foreach ( (array) ( $form['recipients_if'] ?? array() ) as $field => $extra ) {
		if ( ! empty( $values[ $field ] ) ) {
			foreach ( (array) $extra as $name ) {
				$addresses = array_merge( $addresses, $group( (string) $name ) );
			}
		}
	}
	if ( ! $addresses ) {
		$addresses[] = (string) get_option( 'admin_email' );
	}
	return array_values( array_unique( (array) apply_filters( 'citcom_form_recipients', $addresses, $form, $values ) ) );
}

/**
 * The submitter's email address, if the form has an email field.
 */
function citcom_form_sender_email( array $form, array $values ): string {
	foreach ( citcom_form_fields( $form ) as $name => $field ) {
		if ( 'email' === ( $field['type'] ?? '' ) && ! empty( $values[ $name ] ) ) {
			return (string) $values[ $name ];
		}
	}
	return '';
}

/**
 * A short hash of the client address, for the rate limit only (never stored with a submission).
 */
function citcom_form_client_key(): string {
	$forwarded = isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ? explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) )[0] : '';
	$address   = trim( $forwarded ) ?: ( isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '' );
	return substr( wp_hash( $address ), 0, 20 );
}

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'citcom/v1',
			'/forms/(?P<form>[a-z0-9-]+)',
			array(
				'methods'             => 'POST',
				'callback'            => 'citcom_form_handle',
				'permission_callback' => '__return_true', // Public forms.
			)
		);
	}
);

/**
 * Receive a submission.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function citcom_form_handle( WP_REST_Request $request ): WP_REST_Response {
	$form = citcom_form( (string) $request['form'] );
	if ( ! $form ) {
		return new WP_REST_Response(
			array(
				'success' => false,
				'message' => __( 'This form is not available.', 'citcom' ),
			),
			404
		);
	}

	$raw     = (array) $request->get_body_params();
	$success = array(
		'success' => true,
		'message' => (string) ( $form['success'] ?? __( 'Thank you for contacting us, we will be in touch shortly.', 'citcom' ) ),
	);

	// Spam traps. A filled honeypot gets the normal thank-you and is dropped. A
	// submission within 1.5 seconds of the page loading is refused with a message,
	// so a quick person (autofill) can simply send again.
	if ( '' !== (string) ( $raw['citcom_hp'] ?? '' ) ) {
		return new WP_REST_Response( $success, 200 );
	}
	if ( (int) ( $raw['_elapsed'] ?? 0 ) < 1500 ) {
		return new WP_REST_Response(
			array(
				'success' => false,
				'message' => __( 'That was quick. Please press send again.', 'citcom' ),
			),
			429
		);
	}

	$limit_key = 'citcom_form_rate_' . citcom_form_client_key();
	$recent    = (int) get_transient( $limit_key );
	if ( $recent >= 5 ) {
		return new WP_REST_Response(
			array(
				'success' => false,
				'message' => __( 'Too many submissions. Please try again in a few minutes.', 'citcom' ),
			),
			429
		);
	}

	list( $values, $errors ) = citcom_form_validate( $form, $raw );
	if ( $errors ) {
		return new WP_REST_Response(
			array(
				'success' => false,
				'message' => __( 'Error: Your form is not valid, please fix the errors!', 'citcom' ),
				'errors'  => $errors,
			),
			422
		);
	}
	set_transient( $limit_key, $recent + 1, 10 * MINUTE_IN_SECONDS );

	$summary = citcom_form_summary( $form, $values );
	$sender  = citcom_form_sender_email( $form, $values );
	$page    = esc_url_raw( (string) ( $raw['_page'] ?? '' ) );

	// 1. Store.
	$lines = array();
	foreach ( $summary as $label => $value ) {
		$lines[] = $label . ': ' . $value;
	}
	$submission_id = wp_insert_post(
		wp_slash(
			array(
				'post_type'    => 'citcom_submission',
				'post_status'  => 'private',
				'post_title'   => wp_strip_all_tags( ( $form['title'] ?? $form['slug'] ) . ( $sender ? ': ' . $sender : '' ) ),
				'post_content' => implode( "\n", $lines ),
				'meta_input'   => array(
					'_citcom_form'   => $form['slug'],
					'_citcom_fields' => $summary,
					'_citcom_page'   => $page,
				),
			)
		)
	);
	$submission_id = is_wp_error( $submission_id ) ? 0 : (int) $submission_id;

	// 2. Email.
	$rows = '';
	foreach ( $summary as $label => $value ) {
		$rows .= '<tr><th align="left" valign="top" style="padding:6px 12px 6px 0;">' . esc_html( $label ) . '</th><td style="padding:6px 0;">' . nl2br( esc_html( $value ) ) . '</td></tr>';
	}
	$body    = '<p>' . esc_html( sprintf( 'New entry from the %s form.', $form['title'] ?? $form['slug'] ) ) . '</p><table cellpadding="0" cellspacing="0">' . $rows . '</table>'
		. ( $page ? '<p>Sent from: ' . esc_html( $page ) . '</p>' : '' )
		. '<p>' . esc_html( sprintf( 'A copy is kept in Form submissions in WordPress for %d days.', CITCOM_FORMS_RETENTION_DAYS ) ) . '</p>';
	$headers = array( 'Content-Type: text/html; charset=UTF-8' );
	if ( $sender ) {
		$headers[] = 'Reply-To: ' . $sender;
	}
	$mailed = wp_mail(
		citcom_form_recipients( $form, $values ),
		sprintf( 'New Form Entry #%d for %s', $submission_id, $form['title'] ?? $form['slug'] ),
		$body,
		$headers
	);

	// 3. Mailchimp, when the form asks for it and its condition is met.
	$mailchimp = '';
	$settings  = $form['mailchimp'] ?? null;
	if ( is_array( $settings ) && $sender && ( true === ( $settings['when'] ?? true ) || ! empty( $values[ (string) $settings['when'] ] ) ) ) {
		$merge = array();
		foreach ( (array) ( $settings['merge'] ?? array() ) as $tag => $source ) {
			$merge[ $tag ] = is_callable( $source ) ? (string) $source( $values ) : (string) ( $values[ $source ] ?? '' );
		}
		$mailchimp = citcom_mailchimp_subscribe( $sender, array_filter( $merge, 'strlen' ), $settings );
	}

	if ( $submission_id ) {
		update_post_meta( $submission_id, '_citcom_mail', $mailed ? 'sent' : 'failed' );
		if ( '' !== $mailchimp ) {
			update_post_meta( $submission_id, '_citcom_mailchimp', $mailchimp );
		}
	}

	// Stored or mailed: the enquiry is not lost, so the sender gets the thank-you.
	if ( ! $submission_id && ! $mailed ) {
		return new WP_REST_Response(
			array(
				'success' => false,
				'message' => __( 'Sorry, something went wrong and your message was not sent. Please try again or email us.', 'citcom' ),
			),
			500
		);
	}
	return new WP_REST_Response( $success, 200 );
}

/*
 * Stored submissions.
 */
add_action(
	'init',
	function () {
		register_post_type(
			'citcom_submission',
			array(
				'labels'              => array(
					'name'               => __( 'Form submissions', 'citcom' ),
					'singular_name'      => __( 'Form submission', 'citcom' ),
					'edit_item'          => __( 'Form submission', 'citcom' ),
					'search_items'       => __( 'Search submissions', 'citcom' ),
					/* translators: %d: number of days */
					'not_found'          => sprintf( __( 'No submissions in the last %d days.', 'citcom' ), CITCOM_FORMS_RETENTION_DAYS ),
					'not_found_in_trash' => __( 'No submissions in the bin.', 'citcom' ),
					'menu_name'          => __( 'Form submissions', 'citcom' ),
				),
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_rest'        => false,
				'exclude_from_search' => true,
				'publicly_queryable'  => false,
				'has_archive'         => false,
				'rewrite'             => false,
				'menu_icon'           => 'dashicons-email-alt',
				'menu_position'       => 26,
				'supports'            => array( 'title' ),
				'capability_type'     => 'page',
				'map_meta_cap'        => true,
				'capabilities'        => array( 'create_posts' => 'do_not_allow' ),
			)
		);

		if ( ! wp_next_scheduled( 'citcom_forms_purge' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'citcom_forms_purge' );
		}
	}
);

/**
 * Retention: delete submissions older than CITCOM_FORMS_RETENTION_DAYS, for good.
 */
add_action(
	'citcom_forms_purge',
	function () {
		$old = get_posts(
			array(
				'post_type'      => 'citcom_submission',
				'post_status'    => 'any',
				'posts_per_page' => 200,
				'fields'         => 'ids',
				'date_query'     => array(
					array(
						'before' => CITCOM_FORMS_RETENTION_DAYS . ' days ago',
						'column' => 'post_date_gmt',
					),
				),
			)
		);
		foreach ( $old as $id ) {
			wp_delete_post( (int) $id, true );
		}
	}
);

add_action(
	'switch_theme',
	function () {
		wp_clear_scheduled_hook( 'citcom_forms_purge' );
	}
);

add_filter(
	'manage_citcom_submission_posts_columns',
	function () {
		return array(
			'cb'             => '<input type="checkbox" />',
			'title'          => __( 'Submission', 'citcom' ),
			'citcom_form'    => __( 'Form', 'citcom' ),
			'citcom_status'  => __( 'Email / Mailchimp', 'citcom' ),
			'date'           => __( 'Received', 'citcom' ),
			'citcom_expires' => __( 'Deleted on', 'citcom' ),
		);
	}
);

add_action(
	'manage_citcom_submission_posts_custom_column',
	function ( $column, $post_id ) {
		if ( 'citcom_form' === $column ) {
			$form = citcom_form( (string) get_post_meta( $post_id, '_citcom_form', true ) );
			echo esc_html( $form['title'] ?? (string) get_post_meta( $post_id, '_citcom_form', true ) );
		} elseif ( 'citcom_status' === $column ) {
			$mail = (string) get_post_meta( $post_id, '_citcom_mail', true );
			echo esc_html( 'sent' === $mail ? __( 'Email sent', 'citcom' ) : __( 'Email failed', 'citcom' ) );
			$mailchimp = (string) get_post_meta( $post_id, '_citcom_mailchimp', true );
			if ( '' !== $mailchimp ) {
				echo '<br>' . esc_html( $mailchimp );
			}
		} elseif ( 'citcom_expires' === $column ) {
			echo esc_html( wp_date( get_option( 'date_format' ), (int) get_post_time( 'U', true, $post_id ) + CITCOM_FORMS_RETENTION_DAYS * DAY_IN_SECONDS ) );
		}
	},
	10,
	2
);

add_action(
	'add_meta_boxes_citcom_submission',
	function () {
		add_meta_box(
			'citcom-submission',
			__( 'Submitted details', 'citcom' ),
			function ( $post ) {
				echo '<table class="widefat striped"><tbody>';
				foreach ( (array) get_post_meta( $post->ID, '_citcom_fields', true ) as $label => $value ) {
					echo '<tr><th style="width:25%">' . esc_html( (string) $label ) . '</th><td>' . nl2br( esc_html( (string) $value ) ) . '</td></tr>';
				}
				$page = (string) get_post_meta( $post->ID, '_citcom_page', true );
				if ( $page ) {
					echo '<tr><th>' . esc_html__( 'Sent from', 'citcom' ) . '</th><td>' . esc_html( $page ) . '</td></tr>';
				}
				echo '</tbody></table>';
			},
			'citcom_submission',
			'normal',
			'high'
		);
	}
);

add_action(
	'admin_notices',
	function () {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen && 'citcom_submission' === $screen->post_type ) {
			/* translators: %d: number of days */
			echo '<div class="notice notice-info"><p>' . esc_html( sprintf( __( 'Submissions are deleted automatically %d days after they are received.', 'citcom' ), CITCOM_FORMS_RETENTION_DAYS ) ) . '</p></div>';
		}
	}
);

/*
 * Blocks: the "Form" field is a select of the theme forms. Values saved as
 * Forminator ids before the migration still resolve, and show as the right
 * choice when the block is edited.
 */
foreach ( array( 'field_66fe797df659f', 'field_670d892735b99', 'field_diner_guestcheck_form', 'field_citcom_forms_recipients_form' ) as $citcom_form_field_key ) {
	add_filter(
		'acf/load_field/key=' . $citcom_form_field_key,
		function ( $field ) {
			$field['choices'] = citcom_form_choices();
			return $field;
		}
	);
	add_filter(
		'acf/load_value/key=' . $citcom_form_field_key,
		function ( $value ) {
			$form = citcom_form( is_array( $value ) ? '' : $value );
			return $form ? $form['slug'] : $value;
		}
	);
}
unset( $citcom_form_field_key );

/*
 * Shortcodes. [citcom_form id="contact"] is the theme's own; [forminator_form
 * id="623"] in existing content renders the theme form that replaced that id.
 * Forminator shortcodes with no theme form (the retired RSVP form, the quizzes,
 * polls) print nothing, whether or not Forminator is still active.
 */
add_shortcode(
	'citcom_form',
	function ( $atts ) {
		return citcom_render_form( (string) ( $atts['id'] ?? '' ) );
	}
);

add_filter(
	'pre_do_shortcode_tag',
	function ( $output, $tag, $atts ) {
		if ( ! in_array( $tag, CITCOM_FORMINATOR_SHORTCODES, true ) || false !== $output ) {
			return $output;
		}
		$form = 'forminator_form' === $tag ? citcom_form( (string) ( is_array( $atts ) ? $atts['id'] ?? '' : '' ) ) : null;
		return $form ? citcom_render_form( $form['slug'] ) : '';
	},
	10,
	3
);

add_action(
	'init',
	function () {
		// Without Forminator the tags would print as text; the filter above decides what they output.
		foreach ( CITCOM_FORMINATOR_SHORTCODES as $tag ) {
			if ( ! shortcode_exists( $tag ) ) {
				add_shortcode( $tag, '__return_empty_string' );
			}
		}
	},
	20
);
