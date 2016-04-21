<?php
/**
 * Plugin Name: PeepSo Core: Tags
 * Plugin URI: https://peepso.com
 * Description: Add Tags capability to PeepSo, a Social Networking Plugin for WordPress
 * Author: PeepSo
 * Author URI: https://peepso.com
 * Version: 1.5.6
 * Copyright: (c) 2015 PeepSo, Inc. All Rights Reserved.
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: peepsotags
 * Domain Path: /language
 *
 * PeepSo is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * PeepSo is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY. See the
 * GNU General Public License for more details.
 */

class PeepSoTags
{
	private static $_instance = NULL;

	const PLUGIN_VERSION = '1.5.6';
	const PLUGIN_RELEASE = ''; //ALPHA1, BETA1, RC1, '' for STABLE
	const SHORTCODE_TAG = 'peepso_tag';
	const MODULE_ID = 7;
    const PLUGIN_NAME = 'TagSo';
    const PLUGIN_EDD = 'tagso';
    const PLUGIN_SLUG = 'tagso';

	/**
	 * Initialize all variables, filters and actions
	 */
	private function __construct()
	{
		add_action('peepso_init', array(&$this, 'init'));

        if (is_admin()) {
            add_action('admin_init', array(&$this, 'check_peepso'));
        }

		add_action('plugins_loaded', array(&$this, 'load_textdomain'));
		register_activation_hook(__FILE__, array(&$this, 'activate'));
	}

	/*
	 * retrieve singleton class instance
	 * @return instance reference to plugin
	 */
	public static function get_instance()
	{
		if (NULL === self::$_instance)
			self::$_instance = new self();
		return (self::$_instance);
	}

	/**
	 * Loads the translation file for the PeepSo plugin
	 */
	public function load_textdomain()
	{
		$path = str_ireplace(WP_PLUGIN_DIR, '', dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'language' . DIRECTORY_SEPARATOR;
		load_plugin_textdomain('tagso', FALSE, $path);
	}

	/*
	 * Initialize the PeepSoTags plugin
	 */
	public function init()
	{
		// set up autoloading
		PeepSo::add_autoload_directory(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR);
		PeepSoTemplate::add_template_directory(plugin_dir_path(__FILE__));

		if (is_admin()) {
            add_action('admin_init', array(&$this, 'check_peepso'));
			add_filter('peepso_config_email_messages', array(&$this, 'config_email_tags'));
			add_filter('peepso_config_email_messages_defaults', array(&$this, 'config_email_messages_defaults'));
		} else {
			add_shortcode(self::SHORTCODE_TAG, array(&$this, 'shortcode_tag'));

			add_action('wp_enqueue_scripts', array(&$this, 'enqueue_scripts'));
			add_action('peepso_activity_after_add_post', array(&$this, 'after_save_post'), 10, 2);
		}

		// used by Profile page UI to configure alerts and notifications setting
		add_filter('peepso_profile_alerts', array(&$this, 'profile_alerts'), 10, 1);
	}

    /**
     * Check if PeepSo is installed and activated
     * Prevent from activating self if peepso is not installed
     * Prevent from activating self if peepso version is not compatible
     */
    public function check_peepso()
    {
        if (!class_exists('PeepSo'))
        {
            if (is_plugin_active(plugin_basename(__FILE__))) {
                // deactivate the plugin
                deactivate_plugins(plugin_basename(__FILE__));
                // display notice for admin
                add_action('admin_notices', array(&$this, 'disabled_notice'));
                if (isset($_GET['activate']))
                    unset($_GET['activate']);
            }
            return (FALSE);
        }

		// run core version comparison
		if( defined('PeepSo::PLUGIN_RELEASE') ) {
			$this->version_check = PeepSo::check_version_compat(self::PLUGIN_VERSION, self::PLUGIN_RELEASE);
		} else {
			$this->version_check = PeepSo::check_version_compat(self::PLUGIN_VERSION);
		}

        // if it's not OK, render an error/warning
        if( 1 != $this->version_check['compat'] ) {

            add_action('admin_notices', array(&$this, 'version_notice'));

            // only if it's a total failure, disable the plugin
            if( 0 == $this->version_check['compat'] ) {
                deactivate_plugins(plugin_basename(__FILE__));
                if (isset($_GET['activate']))
                    unset($_GET['activate']);

                return (FALSE);
            }
        }

        return (TRUE);
    }

    public function version_notice()
    {
        PeepSo::version_notice(self::PLUGIN_NAME, self::PLUGIN_SLUG, $this->version_check);
    }

    /*
     * Called on first activation
     */
    public function activate()
    {
        if (!$this->check_peepso()) {
            return (FALSE);
        }

		PeepSo::log('PeepSoTags::activate() called');
		require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'install' . DIRECTORY_SEPARATOR . 'activate.php');
		$install = new PeepSoTagsInstall();
		$res = $install->plugin_activation();
		if (FALSE === $res) {
			// error during installation - disable
			deactivate_plugins(plugin_basename(__FILE__));
		}
		return (TRUE);
	}


	/**
	 * Check for existence of the PeepSo class
	 */
	private function peepso_exist()
	{
		return (class_exists('PeepSo'));
	}


	/**
	 * Show message if peepsomood can not be installed or run
	 */
	public function disabled_notice()
	{
		echo '<div class="error fade">';
		echo
		'<strong>' , self::PLUGIN_NAME , ' ' ,
		__('plugin requires the PeepSo plugin to be installed and activated.', 'peepso'),
		' <a href="plugin-install.php?tab=plugin-information&amp;plugin=peepso-core&amp;TB_iframe=true&amp;width=772&amp;height=291" class="thickbox">',
		__('Get it now!', 'peepso'),
		'</a>',
		'</strong>';
		echo '</div>';
	}

	/**
	 * Registers the needed scripts and styles
	 */
	public function enqueue_scripts()
	{
		wp_register_script('peepsotags-tagging', plugin_dir_url(__FILE__) . 'assets/js/tagging.min.js', array('peepso', 'jquery', 'peepso-underscore'), self::PLUGIN_VERSION, TRUE);
		wp_register_script('peepsotags', plugin_dir_url(__FILE__) . 'assets/js/peepsotags.min.js', array('peepsotags-tagging', 'peepso-observer'), self::PLUGIN_VERSION, TRUE);

		wp_enqueue_script('peepsotags');

		wp_localize_script('peepsotags', 'peepsotags', array(
			'parser' => $this->get_tag_parser(),
			'template' => $this->get_tag_template()
		));
	}

	/**
	 * Returns the regular expression that matches the markup for the @ character.
	 * @return string
	 */
	public function get_tag_parser()
	{
		return (apply_filters('peepso_tags_parser', '\[peepso_tag id=(\d+)\]([^\]]+)\[\/peepso_tag\]'));
	}

	/**
	 * Returns the template used to render the layout as key/value pairs.
	 * @return string
	 */
	public function get_tag_template()
	{
		return (apply_filters('peepso_tags_template', '[peepso_tag id=<%= id %>]<%= title %>[/peepso_tag]'));
	}

	/**
	 * Renders the User's display name and profile link
	 * @return string
	 */
	public function shortcode_tag($atts, $content = '')
	{
		if (!isset($atts['id']) && empty($atts['id']))
			return;

		$user = new PeepSoUser($atts['id']);
		$name = $content;

		$display_name = $user->get_display_name();

		// check if provided name is part on the display name string
		if (FALSE === strpos($display_name, $name)) {
			$name = $display_name;
		}

		return (sprintf('<a href="%s" title="%s">%s</a>', $user->get_profileurl(), $display_name, $name));
	}

	/**
	 * Fires once a post has been saved.
	 * @param int $post_id Post ID.
	 * @param int $act_id  The activity ID.
	 */
	public function after_save_post($post_id, $act_id)
	{
		$post_obj = get_post($post_id);
		$match = preg_match_all('/' . $this->get_tag_parser() . '/i', $post_obj->post_content, $matches);

		if ($match) {
			global $post;

			$activity = PeepSoActivity::get_instance();
			// TODO: not always successful. Should check return value
			$post_act = $activity->get_activity($act_id);

			$post = $post_obj;
			setup_postdata($post);

			$user_author = new PeepSoUser($post->post_author);
			$data = array('permalink' => peepso('activity', 'post-link', FALSE));
			$from_fields = $user_author->get_template_fields('from');

			$user_ids = $matches[1];

			$notifications = new PeepSoNotifications();

			$_notification = __('Tagged you in a post', 'tagso');
			foreach ($user_ids as $user_id) {
				$user_id = intval($user_id);

				// If self don't send the notification
				if (intval($post->post_author) === $user_id)
					continue;

				// Check access
				if (!PeepSo::check_permissions($user_id, PeepSo::PERM_POST_VIEW, intval($post->post_author)))
					continue;

				$user_owner = new PeepSoUser($user_id);
				$data = array_merge($data, $from_fields, $user_owner->get_template_fields('user'));
				// TODO: need to use an editable email message, not a constant string
				// SpyDroid: the constant string is an email subject and not an editable email message, the template for editable email is the 4th parameter 'tagged'
				PeepSoMailQueue::add_message($user_id, $data, __('You Were Tagged in a Post', 'tagso'), 'tagged', 'tag', self::MODULE_ID);

				$notifications->add_notification(intval($post->post_author), $user_id, $_notification, 'tag', self::MODULE_ID, $post_id);
			}
		}
	}

	/**
	 * Add the User Tagged Email to the list of editable emails on the config page
	 * @param  array $emails Array of editable emails
	 * @return array
	 */
	// TODO: move this into a PeepSoTaggingAdmin class
	public function config_email_tags($emails)
	{
		$emails['email_tagged'] = array(
			'title' => __('User Tagged Email', 'tagso'),
			'description' => __('This will be sent to a user when a tagged in post.', 'tagso')
		);

		return ($emails);
	}

	public function config_email_messages_defaults( $emails )
	{
		require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . '/install' . DIRECTORY_SEPARATOR . 'activate.php');
		$install = new PeepSoTagsInstall();
		$defaults = $install->get_email_contents();

		return array_merge($emails, $defaults);
	}

	/**
	 * Append profile alerts definition for peepsotags. Used on profile?alerts page
	 // TODO: document parameters
	 */
	public function profile_alerts($alerts)
	{
		$alerts['tags'] = array(
				'title' => __('Tags', 'peepso'),
				'items' => array(
					array(
						'label' => __('You were Tagged in a Post', 'tagso'),
						'setting' => 'tag',
					)
				),
		);
		// NOTE: when adding new items here, also add settings to /install/activate.php site_alerts_ sections
		return ($alerts);
	}
}

PeepSoTags::get_instance();

// EOF
