<?php
/**
 * Client satisfaction survey (was Forminator form 219727, "CitCom Client Survey").
 *
 * Shown on /info/client-survey/ through a shortcode section. "classic" variant:
 * this form used Forminator's own design. Name and email only show, and are
 * only required, when the first question is answered "Yes".
 *
 * @package citcom
 */

defined( 'ABSPATH' ) || exit;

return array(
	'title'      => 'CitCom Client Survey',
	'legacy_ids' => array( 219727 ),
	'variant'    => 'classic',
	'submit'     => 'Submit',
	'success'    => 'Thank you for contacting us, we will be in touch shortly.',
	'rows'       => array(
		array(
			array(
				'name'     => 'details',
				'type'     => 'checkboxes',
				'label'    => 'By continuing to fill out our Client Satisfaction Survey, you agree to providing your name and email address. If you prefer to remain completely anonymous, please click below',
				'required' => true,
				'options'  => array(
					'yes'       => 'Yes, happy to include my details',
					'anonymous' => 'No, I’d prefer to stay anonymous, please',
				),
			),
		),
		array(
			array(
				'name'             => 'name',
				'type'             => 'text',
				'label'            => 'Name',
				'required'         => true,
				'required_message' => 'This field is required. Please input your name.',
				'autocomplete'     => 'name',
				'show_if'          => array(
					'field' => 'details',
					'value' => 'yes',
				),
			),
			array(
				'name'             => 'email',
				'type'             => 'email',
				'label'            => 'Email Address',
				'required'         => true,
				'required_message' => 'This field is required. Please input a valid email.',
				'autocomplete'     => 'email',
				'show_if'          => array(
					'field' => 'details',
					'value' => 'yes',
				),
			),
		),
		array(
			array(
				'name'     => 'services',
				'type'     => 'checkboxes',
				'label'    => 'Q1. Which services have you used with us? (Tick all that apply)',
				'required' => true,
				'options'  => array(
					'marketing'   => 'Marketing (PPC, SEO, Strategy, Social Media)',
					'creative'    => 'Creative (Design, Artwork, Creative)',
					'development' => 'Development (Websites, Games, Landing Pages)',
					'print'       => 'Print (Marketing Materials, Branded Merchandise)',
					'events'      => 'Events (Stand Design, Event Support)',
					'video'       => 'Video (Film, Photography, Animation)',
				),
			),
		),
		array(
			array(
				'name'     => 'satisfaction',
				'type'     => 'radio',
				'label'    => 'Q2. Overall, how satisfied are you with our services? (Tick one)',
				'required' => true,
				'options'  => array(
					'very-satisfied'    => '⭐ Very satisfied',
					'satisfied'         => '🙂 Satisfied',
					'neutral'           => '😐 Neutral',
					'dissatisfied'      => '🙁 Dissatisfied',
					'very-dissatisfied' => '😞 Very dissatisfied',
				),
			),
		),
		array(
			array(
				'name'     => 'score_reasons',
				'type'     => 'checkboxes',
				'label'    => 'Q3. What best explains your score? (Tick all that apply)',
				'required' => true,
				'other'    => true,
				'options'  => array(
					'quality'        => 'The quality of work met or exceeded expectations',
					'communication'  => 'Communication was clear and easy',
					'on-time'        => 'Projects were delivered on time',
					'understood'     => 'The team understood our needs well',
					'value'          => 'Good value for money',
					'delays'         => 'There were delays or missed deadlines',
					'unclear'        => 'Communication could have been clearer',
					'below-expected' => 'The outcome didn’t fully meet expectations',
				),
			),
		),
		array(
			array(
				'name'     => 'experience',
				'type'     => 'checkboxes',
				'label'    => 'Q4. Which statement best describes your experience working with us? (Tick all that apply)',
				'required' => true,
				'options'  => array(
					'smooth'    => 'Everything runs smoothly',
					'supported' => 'We feel well supported and guided throughout projects',
					'proactive' => 'Communication is good, but could be more proactive',
					'timelines' => 'Delivery is strong, but timelines could improve',
					'chase'     => 'We sometimes need to chase or clarify things',
				),
			),
		),
		array(
			array(
				'name'     => 'deadlines',
				'type'     => 'radio',
				'label'    => 'Q5. How would you rate our ability to meet deadlines? (Tick one)',
				'required' => true,
				'options'  => array(
					'always'    => 'Always on time',
					'mostly'    => 'Mostly on time',
					'sometimes' => 'Sometimes late',
					'often'     => 'Often late',
				),
			),
		),
		array(
			array(
				'name'     => 'reasons',
				'type'     => 'checkboxes',
				'label'    => 'Q6. What is the main reason you continue to work with us? (Tick all that apply)',
				'required' => true,
				'other'    => true,
				'options'  => array(
					'quality'       => 'Quality of work',
					'reliability'   => 'Reliability / trust',
					'speed'         => 'Speed of delivery',
					'ease'          => 'Ease of working with the team',
					'understanding' => 'Understanding of our business',
					'value'         => 'Value for money',
					'range'         => 'Range of services offered',
					'relationship'  => 'Personal relationship',
				),
			),
		),
		array(
			array(
				'name'        => 'improve',
				'type'        => 'textarea',
				'label'       => 'Q7. Is there anything you’d like us to improve or do more of?',
				'description' => 'Feel free to add any thoughts, big or small - we value all feedback!',
			),
		),
	),
	// Staging sent this form to two named people; set them in Site Settings > Forms.
	'recipients' => array( 'general' ),
);
