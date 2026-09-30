<?php
/**
 * Free technical audit (was Forminator form 219725, "Free Technical Audit").
 *
 * Shown on /info/zero-click-era/ through a shortcode section. Its labels are
 * bolder and its required stars red: that was this form's own custom CSS
 * (src/scss/elements/_forms.scss).
 *
 * @package citcom
 */

defined( 'ABSPATH' ) || exit;

return array(
	'title'      => 'Free Technical Audit',
	'legacy_ids' => array( 219725 ),
	'submit'     => 'Submit',
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
				'name'             => 'job_title',
				'type'             => 'text',
				'label'            => 'Job Title',
				'required'         => true,
				'required_message' => 'This field is required. Please enter text.',
			),
		),
		array(
			array(
				'name'             => 'email',
				'type'             => 'email',
				'label'            => 'Email Address',
				'required'         => true,
				'required_message' => 'This field is required. Please input a valid email.',
				'autocomplete'     => 'email',
			),
			array(
				'name'             => 'company',
				'type'             => 'text',
				'label'            => 'Company Name',
				'required'         => true,
				'required_message' => 'This field is required. Please enter text.',
			),
		),
		array(
			array(
				'name'             => 'website',
				'type'             => 'url',
				'label'            => 'Website',
				'required'         => true,
				'required_message' => 'This field is required. Please input a valid URL',
			),
		),
		array(
			array(
				'name'        => 'cms',
				'type'        => 'select',
				'label'       => 'Current CMS (Content Management System)',
				'required'    => true,
				'placeholder' => 'Please select an option',
				'default'     => 'wordpress',
				'options'     => array(
					'wordpress'   => 'WordPress',
					'squarespace' => 'Squarespace',
					'wix'         => 'Wix',
					'shopify'     => 'Shopify',
					'magento'     => 'Magento',
					'custom'      => 'Custom Build',
					'other-cms'   => 'Other',
				),
			),
		),
		array(
			array(
				'name'    => 'frustration',
				'type'    => 'select',
				'label'   => 'What is your biggest digital frustration right now?',
				'default' => 'speed',
				'options' => array(
					'speed'      => 'Slow site speed',
					'mobile'     => 'Poor mobile experience',
					'rankings'   => 'Dropping search rankings',
					'conversion' => 'Low conversion rates',
					'design'     => 'Outdated design',
				),
			),
		),
		array(
			array(
				'name'  => 'audience',
				'type'  => 'text',
				'label' => 'Who is your primary audience?',
			),
		),
		array(
			array(
				'name'        => 'answer',
				'type'        => 'textarea',
				'label'       => '"Shaping the Answer"',
				'description' => 'In one sentence, what is the definitive question your brand should be the answer to?',
			),
		),
		array(
			array(
				'name'      => 'ai_ready',
				'type'      => 'range',
				'label'     => 'On this scale, how "AI-ready" do you feel your website structure is?',
				'required'  => true,
				'min'       => 1,
				'max'       => 100,
				'step'      => 1,
				'default'   => 50,
				'min_label' => 'We’re invisible to AI',
				'max_label' => 'We are the definitive source of truth',
			),
		),
	),
	// Staging sent this form to one named person; set them in Site Settings > Forms.
	'recipients' => array( 'general' ),
);
