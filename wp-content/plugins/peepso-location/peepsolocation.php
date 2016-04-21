<?php
/**
 * Plugin Name: PeepSo Core: Location
 * Plugin URI: https://peepso.com
 * Description: Add Location capability to PeepSo, a Social Networking Plugin for WordPress
 * Author: PeepSo
 * Author URI: https://peepso.com
 * Version: 1.5.6
 * Copyright: (c) 2015 PeepSo, Inc. All Rights Reserved.
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: peepsoloction
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

class PeepSoLocation
{
	private static $_instance = NULL;

	const PLUGIN_VERSION = '1.5.6';
	const PLUGIN_RELEASE = ''; //ALPHA1, BETA1, RC1, '' for STABLE
	const SHORTCODE_TAG = 'peepso_geo';
    const PLUGIN_NAME = 'LocSo';
    const PLUGIN_SLUG = 'locso';

	/**
	 * Initialize all variables, filters and actions
	 */
	private function __construct()
	{
		if (is_admin()) {
            add_action('admin_init', array(&$this, 'check_peepso'));
        }

		add_action('plugins_loaded', array(&$this, 'load_textdomain'));
		add_action('peepso_init', array(&$this, 'init'));

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
		load_plugin_textdomain('locso', FALSE, $path);
	}

	/*
	 * Callback for 'peepso_init' action; initialize the PeepSoLocation plugin
	 */
	public function init()
	{
		// set up autoloading
		PeepSo::add_autoload_directory(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR);
		PeepSoTemplate::add_template_directory(plugin_dir_path(__FILE__));

		if (is_admin()) {
			// display configuration options for admin
			add_filter('peepso_admin_register_config_group-site', array(&$this, 'register_config_options'));
		} else {
			add_shortcode(self::SHORTCODE_TAG, array(&$this, 'shortcode_tag'));

			add_filter('peepso_postbox_interactions', array(&$this, 'postbox_interactions'), 2, 1);
			add_filter('peepso_activity_post_content', array(&$this, 'activity_post_content'), 30, 2);
			add_filter('peepso_activity_post_attachment', array(&$this, 'activity_post_attachment'), 70);
			add_filter('peepso_activity_allow_empty_content', array(&$this, 'activity_allow_empty_content'), 10, 1);
			add_filter('peepso_activity_content', array(&$this, 'trim_legacy_location_info'), 10, 1);
			add_filter('peepso_activity_content_attachments', array(&$this, 'get_location_info'), 10, 1);
			add_filter('peepso_messages_conversation_title', array(&$this, 'shortcode_tag_messages'), 10, 1);

			add_action('wp_enqueue_scripts', array(&$this, 'enqueue_scripts'));
			add_action('wp_insert_post', array(&$this, 'insert_post'), 30, 2);
		}
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
			add_action('admin_notices', array($this, 'disabled_notice'));
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
	 * Show message if peepsolocation can not be installed or run
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
	 * Enqueue the location plugin assets
	 */
	public function enqueue_scripts()
	{
		global $wp_query;
		$api_key = PeepSo::get_option('location_gmap_api_key');

		wp_localize_script('peepso', 'peepsogeolocationdata',
			array(
				'api_key' => $api_key
			)
		);

		// The aim of this library is to provide support for Geolocation mainly for old browsers
		wp_register_script('peepsolocation-position', plugin_dir_url(__FILE__) . 'assets/js/geoPosition.min.js', self::PLUGIN_VERSION, TRUE);
		wp_register_script('peepsolocation-js', plugin_dir_url(__FILE__) . 'assets/js/location.min.js', array('peepso', 'peepsolocation-position', 'jquery-ui-position', 'peepso-lightbox'), self::PLUGIN_VERSION, TRUE);
	}

	/**
	 * Add the Location option to post boxes
	 * @param  array $interactions An array of interactions available.
	 * @return array $interactions
	 */
	public function postbox_interactions($interactions = array())
	{
		wp_enqueue_script('peepsolocation-js');
		wp_enqueue_style('locso');

		$interactions['location'] = array(
			'label' => __('Location', 'locso'),
			'id' => 'location-tab',
			'class' => 'ps-list-item',
			'icon' => 'map-marker',
			'click' => 'return;',
			'title' => __('Set a Location for your post', 'locso'),
			'extra' => PeepSoTemplate::exec_template('location', 'interaction', NULL, TRUE),
		);

		return ($interactions);
	}

	/**
	 * `wp_insert_post` callback - Sets the location metadata to the post.
	 * @param  int $post_id The post ID to add the metadata in.
	 * @param  object $post The WP_Post object.
	 */
	public function insert_post($post_id, $post)
	{
		$input = new PeepSoInput();
		$location = $input->post('location', NULL);

		if (FALSE === is_null($location))
			update_post_meta($post_id, 'peepso_location', $location);
	}

	/**
	 * `peepso_activity_post_content` callback - Appends the location information to the post.
	 * @param  string $the_content Contents of the post ($post->post_content)
	 * @param  object $post_id
	 * @return string              The updated post content.
	 */
	public function activity_post_content($the_content, $post_id)
	{
		$post = get_post($post_id);

		if (in_array($post->post_type, apply_filters('peepso_location_apply_to_post_types', array(PeepSoActivityStream::CPT_POST)))) {
			$location = get_post_meta($post->ID, 'peepso_location', TRUE);

			if ($location) {
				$the_content .= ' <span>&mdash; ' . __('at', 'locso')
					. ' [' . self::SHORTCODE_TAG . ' lat="' . $location['latitude'] . '" lng="' . $location['longitude'] . '"]'
					. $location['name'] . '[/' . self::SHORTCODE_TAG . ']'
					. '</span>';
			}
		}

		return ($the_content);
	}

	/**
	 * Attaches a static google map to the post, based on peepso_location
	 */
	public function activity_post_attachment()
	{
		// global $post;

		// $location = get_post_meta($post->ID, 'peepso_location', TRUE);
		// if (is_array($location) && isset($location['latitude']) && isset($location['longitude'])) {
		// 	$coords = $location['latitude'] . ',' . $location['longitude'];
		// 	echo '
		// 	<div class="cstream-attachment">
		// 		<img data-coords="', $coords, '" src="http://maps.googleapis.com/maps/api/staticmap?center=', $coords, '&zoom=15&size=500x250&markers=color:red%7C', $coords, '" alt="" />
		// 	</div>';
		// }
	}

	/**
	 * Adds settings for the GMaps API key
	 */
	public function register_config_options($config_groups)
	{
		$section = 'location_';
		$gmap_api_key = array(
			'name' => $section . 'gmap_api_key',
			'label' => __('Google Maps API Key (v3)', 'locso'),
			'type' => 'text',
			'field_wrapper_class' => 'controls col-sm-8',
			'field_label_class' => 'control-label col-sm-4',
			'value' => PeepSoConfigSettings::get_instance()->get_option($section . 'gmap_api_key')
		);

		$config_groups[] = array(
			'name' => 'location',
			'title' => __('Locations Settings', 'locso'),
			'fields' => array($gmap_api_key),
			'context' => 'right'
		);

		return ($config_groups);
	}

	/**
	 * Allows empty post content if a location is set
	 // TODO: document parameter
	 * @param string $allowed
	 * @return boolean always returns TRUE
	 */
	public function activity_allow_empty_content($allowed)
	{
		$input = new PeepSoInput();
		$location = $input->post('location');
		if (!empty($location))
			$allowed = TRUE;
		return ($allowed);
	}

	/**
	 * Renders the geolocation name and link
	 * @return string
	 */
	public function shortcode_tag_messages($content = '')
	{
		// if the text has the shortcode
		if(stristr($content, self::SHORTCODE_TAG)) {

			// split it into 0 (post) and 1 (location)
			$content_split = explode('['.self::SHORTCODE_TAG, $content);

			// remove the dash character from post text
			// we don't start with this in case there was more than one &mdash; in the original text
			$content = explode('&mdash;',$content_split[0]);
			$content = trim($content[0]);

			// extract the location name from the second part
			$loc = explode(']', $content_split[1]);
			$loc = str_ireplace('[/'.self::SHORTCODE_TAG,'', $loc[1]);

			if(strlen($content) > 0) {
				$content .= " &mdash; ";
			}
			$content .= __('at', 'locso'). " " . $loc;
		}

		return $content;
	}

	/**
	 * Renders the geolocation name and link
	 * @return string
	 */
	public function shortcode_tag($atts, $content = '')
	{
		return '';
	}

	/**
	 * TODO: docblock
	 * @return string
	 */
	public function trim_legacy_location_info($content)
	{
		$pattern = ' <span>&mdash; ' . __('at', 'locso') . ' ';
		$content = str_replace($pattern, '', $content);
		return $content;
	}

	/**
	 * Get location info for particular post.
	 * @return array
	 */
	public function get_location_info($args)
	{
		$post_id = $args['post_id'];
		$location = get_post_meta($post_id, 'peepso_location', TRUE);

		if ($location) {
			$content  = '<span>';
			$content .= __('at', 'locso');
			$content .= ' <a href="javascript:" title="' . esc_attr($location['name']) . '" onclick="pslocation.show_map(';
			$content .= $location['latitude'] . ', ' . $location['longitude'] . ', \'' . esc_attr($location['name']) . '\');">';
			$content .= $location['name'];
			$content .= '</a>';
			$content .= '</span>';
			array_push($args['attachments'], $content);
		}

		return ($args);
	}
}

PeepSoLocation::get_instance();

// EOF
