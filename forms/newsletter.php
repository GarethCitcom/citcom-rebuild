<?php
/**
 * Newsletter signup (was Forminator form 581, "Newsletter Signup").
 *
 * Shown in the newsletter popup (footer.php). Every submission subscribes: the
 * consent box is required.
 *
 * @package citcom
 */

defined( 'ABSPATH' ) || exit;

return array(
	'title'      => 'Newsletter Signup',
	'legacy_ids' => array( 581 ),
	'submit'     => 'Subscribe',
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
		),
		array(
			array(
				'name'     => 'company',
				'type'     => 'text',
				'label'    => 'Company',
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
				'name'        => 'consent',
				'type'        => 'checkbox',
				'label'       => 'Sign me up',
				'group_label' => 'Marketing Permissions',
				'required'    => true,
			),
		),
		array(
			array(
				'type' => 'html',
				'html' => '<p class="lh-1"><small>You can unsubscribe at any time by clicking the link in the footer of our emails. For information about our privacy practices, please visit our website.</small></p> <p class="lh-1"><small>We use Mailchimp as our marketing platform. By clicking below to subscribe, you acknowledge that your information will be transferred to Mailchimp for processing.&nbsp;<a rel="noopener" target="_blank" href="https://mailchimp.com/legal/terms">Learn more about Mailchimp\'s privacy practices here.</a></small></p>',
			),
		),
	),
	'recipients' => array( 'marketing' ),
	'mailchimp'  => array(
		'when'        => true,
		'merge'       => array(
			'FNAME'   => 'first_name',
			'LNAME'   => 'last_name',
			'MMERGE3' => 'company',
			'MMERGE5' => 'job_title',
		),
		'tags'        => array( 'Website Signup' ),
		// "Sign me up!" in the Monthly Marketing Round-Up group.
		'interests'   => array( '97867c40f1' ),
		'permissions' => array( '8efa2d4074' ),
	),
);
