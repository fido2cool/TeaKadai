<?php

class PeepSoConfigSections extends PeepSoConfigSectionAbstract
{
	const SITE_ALERTS_SECTION = 'site_alerts_';

	public function register_config_groups()
	{
		$this->set_context('left');
		$this->_group_system();
		$this->_group_reporting();
		$this->_group_registration();
		$this->_group_activity();
		$this->_group_likes();

		$this->set_context('right');
		$this->_group_license();
		$this->_group_notifications();
		$this->_group_emails();

		# @todo #257 $this->config_groups[] = $this->_group_opengraph();
	}

	private function _group_emails()
	{
		// # Email Sender
		$this->args('validation', array('required','validate'));
		$this->args('data', array(
			'rule-min-length' => 1,
			'rule-max-length' => 64,
			'rule-message'    => __('Should be between 1 and 64 characters long.', 'peepso')
		));


		$this->set_field(
			'site_emails_sender',
			__('Email sender', 'peepso'),
			'text'
		);

		// # Admin Email
		$this->args('validation', array('required','validate'));
		$this->args('data', array(
			'rule-type'    => 'email',
			'rule-message' => __('Email format is invalid.', 'peepso')
		));
		$this->set_field(
			'site_emails_admin_email',
			__('Admin Email', 'peepso'),
			'text'
		);

		// # Copyright Text
		$this->args('raw', TRUE);

		$this->set_field(
			'site_emails_copyright',
			__('Copyright Text', 'peepso'),
			'textarea'
		);

		// # Number of mails to process per run
		$this->args('validation', array('required','validate'));

		// new javascript validation
		$this->args('data', array(
			'rule-type'    => 'int',
			'rule-min'     => 1,
			'rule-max'     => 1000,
			'rule-message' => __('Insert number between 1 and 1000.', 'peepso')
		));

		$this->args('int', TRUE);

		$this->set_field(
			'site_emails_process_count',
			__('Number of mails to process per run', 'peepso'),
			'text'
		);

		// Build Group
		$this->set_group(
			'emails',
			__('Emails', 'peepso'),
			__('These settings control the appearance of emails sent by PeepSo.', 'peepso')
		);
	}

	private function _group_notifications()
	{
		$profile = PeepSoProfile::get_instance();
		$alerts = $profile->get_alerts_definition();

		// Loop through available notifications and display yesno_switch
		foreach ($alerts as $key => $value) {

			if (!isset($value['items'])) {
				continue;
			}

			foreach ($value['items'] as $item) {
				$this->args('default', 1);

				$this->set_field(
					self::SITE_ALERTS_SECTION . $item['setting'],
					$item['label'],
					'yesno_switch'
				);
			}
		}

		$summary = __('Setting these to "YES" will cause PeepSo to generate an email or alert for the specific event.<br/>Setting these to "NO" means no Alert will be generated.<br/>Users can further control which Alerts they want to receive. For example, setting "Profile Likes" to "YES" means that PeepSo will generate a message for these Alerts, but a user can choose to ignore these Alerts.', 'peepso');
		$this->args('summary', $summary);

		// Build Group
		$this->set_group(
			'alerts',
			__('Notifications and Email Alerts', 'peepso'),
			__('These settings control what Alerts are created by PeepSo.', 'peepso')
		);
	}

	private function _group_reporting()
	{
		// # Enable Reporting
		$this->args('children',array('site_reporting_types'));
		$this->set_field(
			'site_reporting_enable',
			__('Enable Reporting', 'peepso'),
			'yesno_switch'
		);

		// # Predefined  Text
		$this->args('raw', TRUE);
		$this->args('multiple', TRUE);

		$this->set_field(
			'site_reporting_types',
			__('Predefined Text (Separated by a New Line)', 'peepso'),
			'textarea'
		);

		// # Build  Group
		$this->set_group(
			'report',
			__('Reporting', 'peepso'),
			__('These settings are used to control users\' ability to report inappropriate content.', 'peepso')
		);
	}

	private function _group_system()
	{
		/*
		// # Enable Social Sharing
		$this->set_field(
			'site_socialsharing_enable',
			__('Enable Social Sharing', 'peepso'),
			'yesno_switch'
		);
		*/

		// # Enable Repost
		$this->set_field(
			'site_repost_enable',
			__('Enable Repost', 'peepso'),
			'yesno_switch'
		);


		// # Redirect Successful Logins
		$options = array(
			'activity' => __('Activity Stream', 'peepso'),
			'profile' => __('Profile', 'peepso'),
		);

		$this->args('options', $options);

		$this->set_field(
			'site_frontpage_redirectlogin',
			__('Redirect Successful Logins', 'peepso'),
			'select'
		);

		// Build  Group
		$this->set_group(
			'system',
			__('System', 'peepso'),
			__('These settings are used to control system settings.', 'peepso')
		);
	}

	private function _group_opengraph()
	{
		// # Enable Open Graph
		$this->set_field(
			'site_opengraph_enable',
			__('Enable Open Graph', 'peepso'),
			'yesno_switch'
		);

		// Open Graph Image
		$this->set_field(
			'site_opengraph_image',
			__('Image', 'peepso'),
			'yesno_switch'
		);

		// Open Graph Description
		$this->set_field(
			'site_opengraph_description',
			__('Description', 'peepso'),
			'text'
		);

		$this->set_group(
			'opengraph',
			__('Open Graph', 'peepso'),
			__('Open Graph Description', 'peepso')
		);
	}

	private function _group_registration()
	{
		/** GENERAL **/
		// Enable Account Verification
		$this->set_field(
			'site_registration_enableverification',
			__('Enable Account Verification', 'peepso'),
			'yesno_switch'
		);

		// Enable Secure Mode For Registration
		$this->set_field(
			'site_registration_enable_ssl',
			__('Enable Secure Mode for Registration', 'peepso'),
			'yesno_switch'
		);

		$summary = __('Setting "Enable Account Verification" to YES will send verification emails to new users when they Register. An Administrator will then need to approve the user before they can use the site. On approval, users will receive another email letting them know they can use the site.<br />Setting "Enable Account Verification" to NO, users will be automatically validated upon registration and can use the site immediately.', 'peepso');
		$this->set_field(
				'site_registration_enableverification_description',
				$summary,
				'message'
		);

		/** RECAPTCHA **/
		// # Separator Recaptcha
		$this->set_field(
			'separator_recaptcha',
			__('ReCaptcha', 'peepso'),
			'separator'
		);

		// # Enable ReCaptcha
		$this->set_field(
			'site_registration_recaptcha_enable',
			__('Enable ReCaptcha', 'peepso'),
			'yesno_switch'
		);

		// # ReCaptcha Site Key
		$this->set_field(
			'site_registration_recaptcha_sitekey',
			__('Site Key', 'peepso'),
			'text'
		);

		// # ReCaptcha Secret Key
		$this->set_field(
			'site_registration_recaptcha_secretkey',
			__('Secret Key', 'peepso'),
			'text'
		);

		// # Message ReCaptcha Description
		$this->set_field(
			'site_registration_recaptcha_description',
			__('Google ReCaptcha is a great way to keep spamming bots away from your website.<br><strong>Get ReCaptcha keys <a href="https://www.google.com/recaptcha/" target="_blank">here</a></strong>.','peepso'),
			'message'
		);

		/** T&C **/

		// # Separator Terms & Conditions
		$this->set_field(
			'separator_terms',
			__('Terms & Conditions', 'peepso'),
			'separator'
		);

		// # Enable Terms & Conditions
		$this->set_field(
			'site_registration_enableterms',
			__('Enable Terms &amp; Conditions', 'peepso'),
			'yesno_switch'
		);

		// # Terms & Conditions Text
		$this->args('raw', TRUE);

		$this->set_field(
			'site_registration_terms',
			__('Terms &amp; Conditions', 'peepso'),
			'textarea'
		);

		/** CUSTOM TEXT **/

		// # Separator Callout
		$this->set_field(
				'separator_callout',
				__('Customize text', 'peepso'),
				'separator'
		);

		// # Callout Header
		$this->set_field(
				'site_registration_header',
				__('Callout Header', 'peepso'),
				'text'
		);

		// # Callout Text
		$this->set_field(
				'site_registration_callout',
				__('Callout Text', 'peepso'),
				'text'
		);

		// # Button Text
		$this->set_field(
				'site_registration_buttontext',
				__('Button Text', 'peepso'),
				'text'
		);


		/** WORDPRESS SOCIAL LOGIN**/



		// # Separator WSL
		$this->set_field(
				'separator_wsl',
				__('WordPress Social Login', 'peepso'),
				'separator'
		);

		$wsl =' <a href="plugin-install.php?tab=plugin-information&plugin=wordpress-social-login&TB_iframe=true&width=750&height=500" class="thickbox">Wordpress Social Login</a> ';

		// # message WSL
		$this->set_field(
				'message_wsl',
				sprintf(__('Requires %s to be installed and properly configured. This is a third party plugin, so use on your own risk.', 'peepso'), $wsl),
				'message'
		);

		if( defined('WORDPRESS_SOCIAL_LOGIN_ABS_PATH') ) {
			// # Enable WSL
			$this->set_field(
					'wsl_enable',
					__('Enable WordPress Social Login', 'peepso'),
					'yesno_switch'
			);
		} else {
			$this->set_field(
					'message_wsl_missing',
					sprintf(__('%s not found! Please install the plugin to see the configuration setting.', 'peepso'), $wsl),
					'message'
			);
		}



		// Build Group

		#$this->args('summary', $summary);

		$this->set_group(
			'registration',
			__('Registration', 'peepso'),
			__('These settings allow you to customize the Registration process.', 'peepso')
		);
	}

	private function _group_activity()
	{
		// # Separator Callout
		$this->set_field(
				'separator_general',
				__('General', 'peepso'),
				'separator'
		);

		// # Maximum size of Post
		$this->args('validation', array('required', 'numeric'));
		$this->args('data', array('min'=>100,'max'=>300));
		$this->args('int', TRUE);

		$this->set_field(
			'site_status_limit',
			__('Maximum size of Post', 'peepso'),
			'text'
		);

		// # Number of Posts
		$this->args('validation', array('required', 'numeric'));
		$this->args('int', TRUE);

		$this->set_field(
			'site_activity_posts',
			__('Number of Posts', 'peepso'),
			'text'
		);

		// # Open Links In New Tab
		$this->set_field(
				'site_activity_open_links_in_new_tab',
				__('Open links in new tab', 'peepso'),
				'yesno_switch'
		);

		// # Hide Activity Stream From Guests
		$this->set_field(
				'site_activity_hide_stream_from_guest',
				__('Hide Activity Stream from Non-logged in Users', 'peepso'),
				'yesno_switch'
		);

		// # Separator Comments
		$this->set_field(
				'separator_comments',
				__('Comments', 'peepso'),
				'separator'
		);

		// # Number Of Comments To Display
		$this->args('validation', array('required', 'numeric'));

		$this->set_field(
			'site_activity_comments',
			__('Number of Comments to display', 'peepso'),
			'text'
		);

		// # Limit Number Of Comments Per Post
		$this->args('descript', __('Select "No" for unlimited comments', 'peepso'));

		$this->set_field(
			'site_activity_limit_comments',
			__('Limit Number of Comments per Post', 'peepso'),
			'yesno_switch'
		);

		// # Maximum Number Of Comments Allowed Per Post
		$this->args('validation', array('required', 'numeric'));
		$this->args('int', TRUE);
		$this->set_field(
			'site_activity_comments_allowed',
			__('Maximum number of Comments allowed per post', 'peepso'),
			'text'
		);

		/* READMORE */

		// # Separator Readmore
		$this->set_field(
				'separator_readmore',
				__('Read more', 'peepso'),
				'separator'
		);

		// # Show Read More After N Characters
		$this->args('default', 1000);
		$this->args('validation', array('required', 'numeric'));

		$this->set_field(
			'site_activity_readmore',
			__("Show 'read more' after: [n] characters", 'peepso'),
			'text'
		);


		// # Redirect To Single Post View
		$this->args('default', 2000);
		$this->args('validation', array('required', 'numeric'));

		$this->set_field(
			'site_activity_readmore_single',
			__('Redirect to single post view when post is longer than: [n] words', 'peepso'),
			'text'
		);

		// # Separator Profile
		$this->set_field(
				'separator_profile',
				__('Profile Posts', 'peepso'),
				'separator'
		);

		// # Who can post on "my profile" page
		$privacy = PeepSoPrivacy::get_instance();
		$privacy_settings = apply_filters('peepso_privacy_access_levels', $privacy->get_access_settings());

		$options = array();

		foreach($privacy_settings as $key => $value) {
			$options[$key] = $value['label'];
		}

		// Remove site guests & rename "only me"
		unset($options[PeepSo::ACCESS_PUBLIC]);
		$options[PeepSo::ACCESS_PRIVATE] .= __(' (profile owner)', 'peepso');

		$this->args('options', $options);

		$this->set_field(
				'site_profile_posts',
				__('Who can post on "my profile" page', 'peepso'),
				'select'
		);

		$this->args('default', 1);
		$this->set_field(
				'site_profile_posts_override',
				__('Let users override this setting', 'peepso'),
				'yesno_switch'
		);


		// Build Group
		$this->set_group(
			'activity',
			__('Activity', 'peepso'),
			__('These settings control how many posts and comments will be displayed in the Activity Stream, as well as "read more" settings and permissions to post on another user\'s profiles.', 'peepso')
		);
	}

	private function _group_likes()
	{
		// # Profile Likes
		$this->set_field(
			'site_likes_profile',
			__('Profile', 'peepso'),
			'yesno_switch'
		);

		// Apply filters
		$this->fields = apply_filters('peepso_admin_like_settings', $this->fields);

		// Build Group
		$this->set_group(
			'likes',
			__('Likes Rating', 'peepso'),
			__('These settings control what content the "Like" feature is enabled on.', 'peepso')
		);
	}

	private function _group_license()
	{
		// Get all licensed PeepSo products
		$products = apply_filters('peepso_license_config', array());

		if (0 === count($products)) {
			return (NULL);
		}

		// Loop through the list and build fields
		foreach ($products as $prod) {

			// label contains some extra HTML for  license checking AJAX to hook into
            $label = $prod['plugin_name'] . ' ' . $prod['plugin_version']
				. ' <span class="license_status_check" id="' . $prod['plugin_slug'] . '" data-plugin-name="'.$prod['plugin_edd'].'"><img src="images/loading.gif"></span>';

			$this->set_field(
				'site_license_'.$prod['plugin_slug'],
				$label,
				'text'
			);
		}

		// Build Group
		$this->set_group(
			'license',
			__('License Key Configuration', 'peepso'),
			'<a name="licensing"></a>' . __('This is where you configure the license keys for each PeepSo add-on. You can find your license numbers on <a target="_blank" href="http://peepso.com/my-account/">My Orders</a> page. Please copy them here and click SAVE at the bottom of this page.', 'peepso')
		);
	}
}

// EOF
