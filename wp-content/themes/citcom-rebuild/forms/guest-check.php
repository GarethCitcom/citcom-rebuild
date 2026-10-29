<?php
/**
 * Diner guest check form (was Forminator form 220023, "diner-guest-check-form").
 *
 * Rendered by citcom/diner-guestcheck in the "plain" variant, which the block
 * styles itself.
 *
 * @package citcom
 */

defined( 'ABSPATH' ) || exit;

return array(
	'title'         => 'Diner guest check form',
	'legacy_ids'    => array( 220023 ),
	'submit'        => 'Send to the kitchen',
	'success'       => 'Thank you for contacting us, we will be in touch shortly.',
	'rows'          => array(
		array(
			array(
				'name'         => 'first_name',
				'type'         => 'text',
				'label'        => 'Name',
				'required'     => true,
				'autocomplete' => 'given-name',
			),
			array(
				'name'         => 'last_name',
				'type'         => 'text',
				'label'        => 'Last Name',
				'required'     => true,
				'autocomplete' => 'family-name',
			),
		),
		array(
			array(
				'name'     => 'company',
				'type'     => 'text',
				'label'    => 'Company Name',
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
			'FNAME'   => 'first_name',
			'LNAME'   => 'last_name',
			'MMERGE3' => 'company',
		),
		'tags'        => array( 'Diner Mailing List' ),
		'permissions' => array( '8efa2d4074' ),
	),
);
