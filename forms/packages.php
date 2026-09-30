<?php
/**
 * Packages enquiry (was Forminator form 219819, "Packages Form").
 *
 * Shown by the "form" type of citcom/cta on /packages/ and its four sub-pages.
 * "classic" variant: this form used Forminator's own design, not the Bootstrap
 * restyling (colours in src/scss/elements/_forms.scss).
 *
 * @package citcom
 */

defined( 'ABSPATH' ) || exit;

return array(
	'title'      => 'Packages Form',
	'legacy_ids' => array( 219819 ),
	'variant'    => 'classic',
	'submit'     => 'Enquire Now',
	'success'    => 'Thank you for contacting us, we will be in touch shortly.',
	'rows'       => array(
		array(
			array(
				'name'             => 'name',
				'type'             => 'text',
				'label'            => 'Name',
				'required'         => true,
				'required_message' => 'This field is required. Please input your name.',
				'autocomplete'     => 'name',
			),
			array(
				'name'             => 'email',
				'type'             => 'email',
				'label'            => 'Email Address',
				'required'         => true,
				'required_message' => 'This field is required. Please input a valid email.',
				'autocomplete'     => 'email',
			),
		),
		array(
			array(
				'name'     => 'packages',
				'type'     => 'checkboxes',
				'label'    => 'Which packages are you interested in?',
				'required' => true,
				'options'  => array(
					'website'  => 'Website Packages',
					'branding' => 'Branding Packages',
					'retainer' => 'Creative Retainer',
					'hosting'  => 'Website Hosting and Management',
				),
			),
			array(
				'name'  => 'message',
				'type'  => 'textarea',
				'label' => 'Any extra information you wish to provide',
			),
		),
	),
	// Staging sent this form to two named people; set them in Site Settings > Forms.
	'recipients' => array( 'general' ),
);
