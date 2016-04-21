<?php
require_once(PeepSo::get_plugin_dir() . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'install.php');
/*
 * Performs installation process
 * @package PeepSoTags
 * @author PeepSo
 */
class PeepSoTagsInstall extends PeepSoInstall
{
	protected $default_config = array(
		'site_alerts_tag' => 1,
	);

	/*
	 * called on plugin activation; performs all installation tasks
	 */
	public function plugin_activation()
	{
		PeepSo::log('PeepSoTagsInstall::plugin_activation() was called');
		parent::plugin_activation();

		return (TRUE);
	}

	public function get_email_contents()
	{
		$emails = array(
			'email_tagged' => "Hello {userfirstname},

{fromfirstname} tagged you in a post!

You can view the post here:
{permalink}

Thank you.",

		);

		return $emails;
	}
}
