<?php
/**
 * Free SEO audit request (was Forminator form 218841, "SEO Audit").
 *
 * Shown by the "form" type of citcom/cta on /info/free-seo-audit/. The website
 * address was a free text field in the old form (a repurposed phone field), so
 * it stays free text here.
 *
 * @package citcom
 */

defined( 'ABSPATH' ) || exit;

return array(
	'title'      => 'SEO Audit',
	'legacy_ids' => array( 218841 ),
	'submit'     => 'Request Free Audit',
	'success'    => 'Thank you for contacting us, we will be in touch shortly.',
	'rows'       => array(
		array(
			array(
				'name'             => 'first_name',
				'type'             => 'text',
				'label'            => 'First Name',
				'required'         => true,
				'required_message' => 'This field is required. Please input your first name.',
				'placeholder'      => 'E.g. John',
				'autocomplete'     => 'given-name',
			),
			array(
				'name'             => 'last_name',
				'type'             => 'text',
				'label'            => 'Last Name',
				'required'         => true,
				'required_message' => 'This field is required. Please input your last name.',
				'placeholder'      => 'E.g. Doe',
				'autocomplete'     => 'family-name',
			),
		),
		array(
			array(
				'name'             => 'email',
				'type'             => 'email',
				'label'            => 'Email Address',
				'required'         => true,
				'required_message' => 'This field is required. Please input a valid email.',
				'placeholder'      => 'E.g. john@doe.com',
				'autocomplete'     => 'email',
			),
		),
		array(
			array(
				'name'             => 'website',
				'type'             => 'text',
				'label'            => 'Website Address',
				'required'         => true,
				'required_message' => 'This field is required. Please input your website address.',
				'placeholder'      => 'E.g. https://www.......',
			),
		),
		array(
			array(
				'name'        => 'issue',
				'type'        => 'select',
				'label'       => 'What is your biggest website issue?',
				'placeholder' => 'What is your biggest website issue?',
				'options'     => array(
					'low-visitors' => 'Low visitor numbers',
					'declining'    => 'Declining visitors',
					'no-leads'     => 'No sales/leads',
					'broken'       => 'Broken website',
				),
			),
		),
	),
	// Staging sent this form to one named person; set them in Site Settings > Forms.
	'recipients' => array( 'general' ),
);
