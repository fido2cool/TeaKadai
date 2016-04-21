<?php
/**
 * Plugin Name: PeepSo Core: Moods
 * Plugin URI: https://peepso.com
 * Description: Add Moods capability to PeepSo, a Social Networking Plugin for WordPress
 * Author: PeepSo
 * Author URI: https://peepso.com
 * Version: 1.5.6
 * Copyright: (c) 2015 PeepSo, Inc. All Rights Reserved.
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: peepsomoods
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

class PeepSoMoods
{
	private static $_instance = NULL;

    public $moods = array();

	const PLUGIN_VERSION = '1.5.6';
	const PLUGIN_RELEASE = ''; //ALPHA1, BETA1, RC1, '' for STABLE
	const META_POST_MOOD = '_peepso_post_mood';
    const PLUGIN_NAME = 'MoodSo';
    const PLUGIN_SLUG = 'moodso';

	private $class_prefix = 'ps-emo-';

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

        // You can't call register_activation_hook() inside a function hooked to the 'plugins_loaded' or 'init' hooks
        register_activation_hook(__FILE__, array(&$this, 'activate'));
	}


	/*
	 * Return singleton instance of plugin
	 */
	public static function get_instance()
	{
		if (NULL === self::$_instance) {
            self::$_instance = new self();
        }

		return (self::$_instance);
	}

	/**
	 * Loads the translation file for the PeepSo plugin
	 */
	public function load_textdomain()
	{
		$path = str_ireplace(WP_PLUGIN_DIR, '', dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'language' . DIRECTORY_SEPARATOR;
		load_plugin_textdomain('moodso', FALSE, $path);
	}

	/*
	 * Initialize the PeepSoMoods plugin
	 */
	public function init()
	{
		if (is_admin()) {
			add_action('admin_init', array(&$this, 'check_peepso'));
		} else {
			add_action('wp_enqueue_scripts', array(&$this, 'enqueue_scripts'));
			add_action('wp_insert_post', array(&$this, 'save_mood'), 100);

			add_filter('peepso_postbox_interactions', array(&$this, 'insert_html'), 1);
			add_filter('peepso_activity_allow_empty_content', array(&$this, 'activity_allow_empty_content'), 10, 1);
			add_filter('the_content', array(&$this, 'add_mood_info'));
            add_filter('peepso_moods_mood_value', array(&$this, 'peepso_moods_mood_value'));
            add_filter('peepso_activity_content_attachments', array(&$this, 'get_mood_info'), 20, 1);
		}

        // initialize moods list
        $this->moods = array(
            1 => __('joyful', 'moodso'),
            2 => __('meh', 'moodso'),
            3 => __('love', 'moodso'),
            4 => __('flattered', 'moodso'),
            5 => __('crazy', 'moodso'),
            6 => __('cool', 'moodso'),
            7 => __('tired', 'moodso'),
            8 => __('confused', 'moodso'),
            9 => __('speechless', 'moodso'),
            10 => __('confident', 'moodso'),
            11 => __('relaxed', 'moodso'),
            12 => __('strong', 'moodso'),
            13 => __('happy', 'moodso'),
            14 => __('angry', 'moodso'),
            15 => __('scared', 'moodso'),
            16 => __('sick', 'moodso'),
            17 => __('sad', 'moodso'),
            18 => __('blessed', 'moodso')
        );
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

        return (TRUE);
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
	 * Load required styles and scripts
	 */
	public function enqueue_scripts()
	{
		wp_enqueue_style('peepso-moods', plugin_dir_url(__FILE__) . 'assets/css/peepsomoods.css', array('peepso'), self::PLUGIN_VERSION, 'all');
		wp_enqueue_script('peepso-moods', plugin_dir_url(__FILE__) . 'assets/js/peepsomoods.min.js', array('peepso', 'peepso-postbox'), self::PLUGIN_VERSION, TRUE);
	}


	/**
	 * This function inserts mood selection box on the post box
	 * @param array $out_html is the formated html code that get inserted in the postbox
	 */
	public function insert_html($out_html = array())
	{
		$mood_list = '';
		foreach ($this->moods as $id => $mood) {
			$mood_list .= "
				<li class='mood-list'>
					<a id='postbox-mood-{$id}' href='javascript:' data-option-value='{$id}' data-option-display-value='{$mood}'>
						<i class='ps-emoticon {$this->class_prefix}{$id}'></i><span>" . $mood . "</span>
					</a>
				</li>";
		}

		$mood_remove = __('Remove Mood', 'moodso');
		$mood_ux = '<div style="display:none">
				<input type="hidden" id="postbox-mood-input" name="postbox-mood-input" value="0" />
				<span id="mood-text-string">' . __(' feeling ', 'moodso') . '</span>
				</div>';

		$mood_data = array(
			'label' => __('Mood', 'moodso'),
			'id'	=> 'mood-tab',
			'class' => 'ps-list-item',
			'icon'	=> 'happy',
			'click' => 'return;',
			'title' => __('Mood settings for your post', 'moodso'),
			'extra' => "<ul id='postbox-mood' class='dropdown-menu ps-postbox-moods' style='display: none;'>
							{$mood_list}
							<li class='mood-list mood-list-bottom' style='display: none;'><button id='postbox-mood-remove'><i class='ps-icon-remove'></i>{$mood_remove}</button></li>
						</ul>{$mood_ux}"
		);

		$out_html['Mood'] = $mood_data;
		return ($out_html);
	}

	/**
	  * This function saves the mood data for the post
	  * @param $post_id is the ID assign to the posted content
	 */
	public function save_mood($post_id)
	{
		$input = new PeepSoInput();
		$mood = $input->post('mood');

		if (apply_filters('peepso_moods_apply_to_post_types', array(PeepSoActivityStream::CPT_POST)) &&	!empty($mood)) {
            update_post_meta($post_id, self::META_POST_MOOD, $input->post('mood'));
        }
	}

	/**
	 * Adds the selected Mood to the end of the post's content
	 * @param string $content The post content being filtered
	 * @param string $post The post instance
	 * @return string The modified post content, with any Mood information added
	 */
	public function add_mood_info($content, $post = NULL)
	{
		return ($content);
	}

	/**
	 * TODO: docblock
	 */
	public function get_mood_info($args)
	{
		$post_id = $args['post_id'];
		$post_mood_id = get_post_meta($post_id, self::META_POST_MOOD, TRUE);
		$post_mood = apply_filters('peepso_moods_mood_value', $post_mood_id);

		if (!empty($post_mood)) {
			$content  = '<span>';
			$content .= '<i class="ps-emoticon ' . $this->class_prefix . $post_mood_id . '"></i>';
			$content .= '<span>' . __(' feeling ', 'moodso') . ucwords($post_mood) . '</span>';
			$content .= '</span>';
			array_push($args['attachments'], $content);
		}

		return ($args);
	}

	/**
	 * Allows empty post content if a mood is set
	 * @param boolean $allowed Current state of the allow posting check
	 * @return boolean Rturns TRUE when mood information is present to indicate that a post with not content and a mood is publishable
	 */
	public function activity_allow_empty_content($allowed)
	{
		$input = new PeepSoInput();
		$mood = $input->post('mood');
		if (!empty($mood)) {
            $allowed = TRUE;
        }

		return ($allowed);
	}

    public function peepso_moods_mood_value($mood)
    {
        if(!$mood) {
            return;
        }

        if(array_key_exists($mood, $this->moods)) {
            return $this->moods[$mood];
        }

        return $mood."*";
    }
}

PeepSoMoods::get_instance();

// EOF
