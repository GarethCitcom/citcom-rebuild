<?php
/**
 * Send a brief (was Forminator form 218706, "Send a brief").
 *
 * Shown on /send-us-a-brief/, on the events service pages and in the brief
 * popup (footer.php).
 *
 * @package citcom
 */

defined( 'ABSPATH' ) || exit;

return array(
	'title'      => 'Send a brief',
	'legacy_ids' => array( 218706 ),
	'submit'     => 'Send brief',
	'success'    => 'Thank you for contacting us, we will be in touch shortly.',
	'rows'       => array(
		array(
			array(
				'name'         => 'first_name',
				'type'         => 'text',
				'label'        => 'First Name',
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
				'name'         => 'email',
				'type'         => 'email',
				'label'        => 'Email Address',
				'required'     => true,
				'autocomplete' => 'email',
			),
			array(
				'name'     => 'phone',
				'type'     => 'tel',
				'label'    => 'Phone',
				'required' => true,
			),
		),
		array(
			array(
				'name'     => 'company',
				'type'     => 'text',
				'label'    => 'Company name',
				'required' => true,
			),
			array(
				'name'     => 'job_title',
				'type'     => 'text',
				'label'    => 'Job Title',
				'required' => true,
			),
		),
		array(
			array(
				'name'        => 'brief',
				'type'        => 'textarea',
				'label'       => 'Short brief',
				'required'    => true,
				'placeholder' => 'Tell us in a few words what you\'re requirements are ...',
			),
		),
	),
	'recipients' => array( 'general' ),
);
