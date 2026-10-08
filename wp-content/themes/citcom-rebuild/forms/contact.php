<?php
/**
 * Contact form (was Forminator form 623, "Contact Form").
 *
 * Used by citcom/contact-map and the "form" type of citcom/cta. The company
 * name only shows, and is only required, once the mailing list box is ticked.
 *
 * @package citcom
 */

defined( 'ABSPATH' ) || exit;

return array(
	'title'         => 'Contact Form',
	'legacy_ids'    => array( 623 ),
	'submit'        => 'Send Message',
	'success'       => 'Thank you for contacting us, we will be in touch shortly.',
	'rows'          => array(
		array(
			array(
				'name'     => 'name',
				'type'     => 'text',
				'label'    => 'Name',
				'required' => true,
			),
		),
		array(
			array(
				'name'         => 'email',
				'type'         => 'email',
				'label'        => 'Email',
				'required'     => true,
				'autocomplete' => 'email',
			),
		),
		array(
			array(
				'name'        => 'message',
				'type'        => 'textarea',
				'label'       => 'Message',
				'placeholder' => 'Enter your message...',
			),
		),
		array(
			array(
				'name'  => 'mailing_list',
				'type'  => 'checkbox',
				'label' => 'Sign up to our mailing list',
			),
		),
		array(
			array(
				'name'     => 'company',
				'type'     => 'text',
				'label'    => 'Company name',
				'required' => true,
				'show_if'  => 'mailing_list',
			),
		),
		array(
			array(
				'type' => 'html',
				'html' => '<p class="lh-sm">You can unsubscribe at any time by clicking the link in the footer of our emails. For information about our privacy practices, please visit our website.</p>',
			),
		),
	),
	// Enquiries go to the general address; the marketing address is copied in on a mailing list signup.
	'recipients'    => array( 'general' ),
	'recipients_if' => array( 'mailing_list' => array( 'marketing' ) ),
	'mailchimp'     => array(
		'when'        => 'mailing_list',
		'merge'       => array(
			// One "Name" field: the first word goes to FNAME, the rest to LNAME.
			'FNAME'   => static function ( array $values ): string {
				return (string) strtok( trim( (string) $values['name'] ), ' ' );
			},
			'LNAME'   => static function ( array $values ): string {
				$parts = preg_split( '/\s+/', trim( (string) $values['name'] ), 2 );
				return (string) ( $parts[1] ?? '' );
			},
			'MMERGE3' => 'company',
		),
		'tags'        => array( 'Website Signup' ),
		'permissions' => array( '8efa2d4074' ),
	),
);
