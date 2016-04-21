<?php

class PeepSoConfigSectionAppearance extends PeepSoConfigSectionAbstract
{
	public static $css_overrides = array(
		'appearance-avatars-square',
	);

	// Builds the groups array
	public function register_config_groups()
	{
		$this->context='left';
		$this->_group_profiles();

		$this->context='right';
		$this->_group_general();
		$this->_group_members();
	}

	private function _group_profiles()
	{
		// Display Name Style
		$options = array(
			'real_name' => __('Real Names', 'peepso'),
			'username' => __('Usernames', 'peepso'),
		);

		$this->args('options', $options);

		$this->set_field(
			'system_display_name_style',
			__('Display Name Style', 'peepso'),
			'select'
		);

		// Allow User To Override Name Setting
		$this->set_field(
				'system_override_name',
				__('Let Users Override This Setting', 'peepso'),
				'yesno_switch'
		);

		$this->args('default', 1);
		// Allow User To Change Username
		$this->set_field(
				'allow_username_change',
				__('Let Users Change Usernames', 'peepso'),
				'yesno_switch'
		);

		// Allow Profile Deletion
		$this->set_field(
			'site_registration_allowdelete',
			__('Allow Profile Deletion', 'peepso'),
			'yesno_switch'
		);

		/** AVATARS **/
		// # Separator Avatars
		$this->set_field(
			'separator_avatars',
			__('Avatars', 'peepso'),
			'separator'
		);

		// Use Square Avatars
		$this->set_field(
			'appearance-avatars-square',
			__('Use square avatars', 'peepso'),
			'yesno_switch'
		);

		// Use Peepso Avatars
		$this->set_field(
			'system_use_peepso_avatars',
			__('Use PeepSo avatars everywhere', 'peepso'),
			'yesno_switch'
		);

		// Use Gravatar Avatars
		$this->set_field(
			'appearance_use_gravatar',
			__('Allow Gravatar avatars', 'peepso'),
			'yesno_switch'
		);


		// Build Group
		$this->set_group(
			'appearance_profile',
			__('User Profiles', 'peepso')
		);
	}

	private function _group_general()
	{
		// Primary CSS Template
		$options = array(
			'' => __('Light', 'peepso'),
		);

		$dir =  plugin_dir_path(__FILE__).'/../templates/css';

		$dir = scandir($dir);
		$from_key	= array( 'template-', '.css' );
		$to_key		= array( '' );

		$from_name	= array( '_', '-' );
		$to_name 	= array( ' ',' ' );

		foreach($dir as $file){
			if('template-' == substr($file, 0, 9)) {

				$key=str_replace($from_key, $to_key, $file);
				$name=str_replace($from_name, $to_name, $key);
				$options[$key]=ucwords($name);
			}
		}

		$this->args('options', $options);

		$this->set_field(
			'site_css_template',
			__('Primary CSS Template', 'peepso'),
			'select'
		);


		// Show "Powered By Peepso" Link
		$this->set_field(
			'system_show_peepso_link',
			__('Show "Powered by PeepSo" link', 'peepso'),
			'yesno_switch'
		);

		// Build Group
		$this->set_group(
			'appearance_general',
			__('General', 'peepso')
		);
	}

	private function _group_members()
	{
		// Default Sorting
		$options = array(
			'' => __('Alphabetical', 'peepso'),
			'peepso_last_activity' => __('Recently online', 'peepso'),
			'registered' => __('Latest members', 'peepso'),
		);

		$this->args('options', $options);

		$this->set_field(
			'site_memberspage_default_sorting',
			__('Default Sorting', 'peepso'),
			'select'
		);

		// Build Group
		$this->set_group(
			'appearance_members',
			__('Members page', 'peepso')
		);
	}
}