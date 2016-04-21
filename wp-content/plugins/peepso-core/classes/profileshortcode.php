<?php

class PeepSoProfileShortcode
{
    public $save_success = FALSE;

    private static $_instance = NULL;

    private $err_message = NULL;
    private $url;
    private $can_access = TRUE;
    private $user_blocked = FALSE;

    private $view_user_id = NULL;					// user id of profile to view
    private $form_message = NULL;					// error message used in forms

    private $preference_tabs = array(
        'edit',
        'pref',
        'notifications',
        'blocked',
        'alerts',
    );

    const NONCE_NAME = 'profile-edit-form';

    public function __construct()
    {
        add_shortcode('peepso_profile', array(&$this, 'do_shortcode'));
        add_action('wp_enqueue_scripts', array(&$this, 'enqueue_scripts'));

        add_action('peepso_save_cover_form', array(&$this, 'save_cover_form'));
        add_action('peepso_save_profile_form', array(&$this, 'save_profile_form'));
        add_action('peepso_save_avatar_form', array(&$this, 'save_avatar_form'));
        add_action('peepso_save_preference_form', array(&$this, 'save_preference_form'));
        add_action('peepso_activity_dialogs', array(&$this, 'upload_dialogs'));
        add_action('peepso_save_alerts_form', array(&$this, 'save_alerts_form'));

        add_filter('peepso_page_title', array(&$this,'peepso_page_title'));
        add_filter('peepso_user_profile_id', array(&$this, 'user_profile_id'));

        add_action('peepso_profile_segment_about', array(&$this, 'peepso_profile_segment_about'));
        add_filter('peepso_profile_segment_menu_links', array(&$this, 'peepso_profile_segment_menu_links'));
        add_filter('peepso_page_title_profile_segment', array(&$this,'peepso_page_title_profile_segment'));
    }


    public function get_view_user_id()
    {
        return intval($this->view_user_id);
    }

    /*
     * return singleton instance of teh plugin
     */
    public static function get_instance()
    {
        PeepSoActivityShortcode::get_instance();					// need the Activity Stream
        if (NULL === self::$_instance)
            self::$_instance = new self();
        return (self::$_instance);
    }


    /*
     * Sets up the page for viewing. The combination of page and exta information
     * specifies which profile to view.
     * @param string $page The 'root' of the page, i.e. 'profile'
     * @param string $extra Optional specifier of extra data, i.e. 'username'
     */
    public function set_page($url)
    {
        if(!$url instanceof PeepSoUrlSegments) {
            return;
        }

        $this->url = $url;

        global $wp_query;

        if ($wp_query->is_404) {
            $virt = new PeepSoVirtualPage($this->url->get(0), $this->url->get(1));
        }

        if ($this->url->get(1)) {
            $user = get_user_by('slug', $this->url->get(1));

            if (FALSE === $user) {
                $this->view_user_id = PeepSo::get_user_id();
            } else {
                $this->view_user_id = $user->ID;
            }
        } else {
            $this->view_user_id = PeepSo::get_user_id();
        }

        if (0 === $this->view_user_id) {
            PeepSo::redirect(PeepSo::get_page('activity'));
        }

        $blk = new PeepSoBlockUsers();
        $user = new PeepSoUser($this->view_user_id);

        $this->user_blocked = $blk->is_user_blocking($this->view_user_id, PeepSo::get_user_id(), TRUE);
        $this->can_access = PeepSo::check_permissions($this->view_user_id, PeepSo::PERM_PROFILE_VIEW, PeepSo::get_user_id(), TRUE);

        $this->init();
    }

    /*
     * Filter for setting the user id of the page being viewed
     * @param int $id The assumed user id
     * @return int The modified user id, based on the profile page being viewed
     */
    public function user_profile_id($id)
    {
        // this uses the value set in the set_page() method
        if (FALSE !== $this->view_user_id)
            $id = $this->view_user_id;
        return ($id);
    }


    /*
     * shortcode callback for the Registration Page
     * @param array $atts Shortcode attributes
     * @param string $content Contents of the shortcode
     * @return string output of the shortcode
     */

    public function peepso_page_title( $title )
    {
        if( 'peepso_profile' == $title['title']) {

            $user = new PeepSoUser($this->get_view_user_id());
            $title['title'] = $user->get_display_name();
            $title['newtitle'] = $title['title'] . " - " . __('stream', 'PeepSo');

            foreach($this->preference_tabs as $tab) {
                if(isset($_GET[$tab])) {
                    $title['title'] = $user->get_display_name();
                    $title['newtitle'] = $title['title'] . " - " . __('preferences', 'PeepSo');
                }
            }

            if (isset($this->url) && $this->url instanceof PeepSoUrlSegments) {
                $segment = $this->url->get(2);
                if ($segment) {
                    $title['profile_segment'] = $segment;
                    $title = apply_filters('peepso_page_title_profile_segment', $title);
                }
            }


        }

        return $title;
    }

    public function do_shortcode($atts, $content)
    {
        echo "<!--shortcode";
        global $didshortcode;
        echo ++$didshortcode;
        echo "-->";

        PeepSo::set_current_shortcode('peepso_profile');
        $allow = apply_filters('peepso_access_content', TRUE, 'peepso_profile', PeepSo::MODULE_ID);
        if (!$allow) {
            echo apply_filters('peepso_access_message', NULL);
            return;
        }

        if(!isset($this->url) || !($this->url instanceof PeepSoUrlSegments)) {
            $this->url = new PeepSoUrlSegments();
        }

        $pro = PeepSoProfile::get_instance();
        $pro->set_user_id($this->view_user_id);

        // use get variables to determine exactly which profile template to run
        $ret = PeepSoTemplate::get_before_markup();

        if ($this->user_blocked)
            $ret .= '<div class="ps-alert ps-alert-danger">' . __('Sorry, you don\'t have permission to access the content of this page.', 'peepso') . '</div>';
        else if (FALSE === $this->can_access)
            $ret .= PeepSoTemplate::exec_template('profile', 'no-access', NULL, TRUE);
        else if (isset($_GET['blocked']))
            $ret .= PeepSoTemplate::exec_template('profile', 'profile-blocked', NULL, TRUE);
        else if (isset($_GET['edit'])) {
            $ret .= PeepSoTemplate::exec_template('profile', 'profile-edit', array('shortcode' => self::get_instance()), TRUE);
        }
        else if (isset($_GET['notifications'])) {
            $ret .= PeepSoTemplate::exec_template('profile', 'profile-notifications', NULL, TRUE);
        }
        else if (isset($_GET['pref'])) {
            $ret .= PeepSoTemplate::exec_template('profile', 'profile-preferences', array('shortcode' => self::get_instance()), TRUE);
        }
        else if (isset($_GET['alerts'])) {
            $ret .= PeepSoTemplate::exec_template('profile', 'profile-alerts', array('shortcode' => self::get_instance()), TRUE);
        }
        else if ($this->url->get(2)) {
            ob_start();
            do_action('peepso_profile_segment_' . $this->url->get(2), $this->url);
            $ret .= ob_get_clean();
        } else {
            $ret .= PeepSoTemplate::exec_template('profile', 'profile', NULL, TRUE);

            if ($this->view_user_id !== PeepSo::get_user_id()) {
                $usr = new PeepSoUser();
                $usr->add_view_count($this->view_user_id);
            }
        }
        $ret .= PeepSoTemplate::get_after_markup();

        if ($pro->can_edit()) {
            wp_enqueue_style('peepso-datepicker');
            wp_enqueue_style('peepso-fileupload');

            wp_enqueue_script('peepso-fileupload');
        }

        PeepSo::reset_query();

        return ($ret);
    }

    /*
     * Init callback. Checks for post operations
     */
    public function init()
    {
//PeepSo::log(__METHOD__.'()');
        if ('POST' === $_SERVER['REQUEST_METHOD']) {
            if (isset($_GET['cover']))
                do_action('peepso_save_cover_form', $this->view_user_id);
            else if (isset($_GET['edit']))
                do_action('peepso_save_profile_form', $this->view_user_id);
            else if (isset($_GET['avatar']))
                do_action('peepso_save_avatar_form', $this->view_user_id);
            else if (isset($_GET['pref']))
                do_action('peepso_save_preference_form', $this->view_user_id);
            else if (isset($_GET['alerts']))
                do_action('peepso_save_alerts_form', $this->view_user_id);
        }
    }

    /*
     * Function called when saving cover photo
     */
    public function save_cover_form($id)
    {
        if (FALSE === PeepSo::check_permissions($this->view_user_id, PeepSo::PERM_PROFILE_EDIT, PeepSo::get_user_id())) {
            $this->err_message = __('You do not have enough permissions.', 'peepso');
            return (FALSE);
        } else {
            $input = new PeepSoInput();

            // verify a valid user id
            $user_id = $input->post_int('user_id');
            if (0 === $user_id)
                return (FALSE);

            if (isset($_FILES['filedata'])) {
                $allowed_mime_types = apply_filters(
                    'peepso_profiles_cover_mime_types',
                    array(
                        'image/jpeg',
                        'image/png'
                    )
                );

                if (!in_array($_FILES['filedata']['type'], $allowed_mime_types)) {
                    $this->err_message = __('The file type you uploaded is not allowed.', 'peepso');
                    return (FALSE);
                }

                if (empty($_FILES['filedata']['tmp_name'])) {
                    $this->err_message = __('The file you uploaded is either missing or too large.', 'peepso');
                    return (FALSE);
                }

                $user = new PeepSoUser($user_id);
                $user->move_cover_file($_FILES['filedata']['tmp_name']);
                return (TRUE);
            } else {
                $this->err_message = __('No file uploaded.', 'peepso');
                return (FALSE);
            }

        }
    }

    /*
     * Function called when saving avatar image
     *
     */
    public function save_avatar_form()
    {
        if (FALSE === PeepSo::check_permissions($this->view_user_id, PeepSo::PERM_PROFILE_EDIT, PeepSo::get_user_id())) {
            $this->err_message = __('You do not have enough permissions.', 'peepso');
            return (FALSE);
        } else {
            $input = new PeepSoInput();

            // verify a valid user id
            $user_id = $input->post_int('user_id');
            if (0 === $user_id) {
                $this->err_message = __('No user defined.', 'peepso');
                return (FALSE);
            }

            if (isset($_FILES['filedata'])) {
                $allowed_mime_types = apply_filters(
                    'peepso_profiles_avatar_mime_types',
                    array(
                        'image/jpeg',
                        'image/png'
                    )
                );

                if (empty($_FILES['filedata']['tmp_name'])) {
                    $this->err_message = __('The file you uploaded is either missing or too large.', 'peepso');
                    return (FALSE);
                }

                if (!in_array($_FILES['filedata']['type'], $allowed_mime_types)) {
                    $this->err_message = __('The file type you uploaded is not allowed.', 'peepso');
                    return (FALSE);
                }

                $user = new PeepSoUser($user_id);
                $user->move_avatar_file($_FILES['filedata']['tmp_name']);
//PeepSo::log('  - file: ' . $_FILES['filedata']['tmp_name']);
                return (TRUE);
            } else {
                $this->err_message = __('No file uploaded.', 'peepso');
                return (FALSE);
            }
        }
    }

    /*
     * Function called when saving an Edit Profile form
     * @param int $id The user id for the form save operation
     */
    public function save_profile_form($id)
    {
        // check permissions
        if (PeepSo::check_permissions($this->view_user_id, PeepSo::PERM_PROFILE_EDIT, PeepSo::get_user_id())) {
            $input = new PeepSoInput();

            $nonce = $input->post('-form-id');
            if (FALSE === wp_verify_nonce($nonce, self::NONCE_NAME))
                return;

            // verify that authkey field is empty
            $authkey = $input->post('authkey');
            if (!empty($authkey))
                return;

            // verify a valid user id
            $user_id = $input->post_int('user_id');
            if (0 === $user_id) {
				return;
			}

            $profile = PeepSoProfile::get_instance();
            $profile->set_user_id($user_id);

            $edit_form = apply_filters('peepso_profile_edit_form_fields', array(), $user_id);

            add_filter('peepso_form_validate_after', array(&$profile, 'change_password_validate_after'), 10, 2);

            $form = PeepSoForm::get_instance();
            $form->add_fields($edit_form);
            $form->map_request();

            if (!$form->validate())
                return (FALSE);

            // create a user instance for this user
            $user = new PeepSoUser($user_id);

            foreach (array_keys($_POST) as $field) {
				// @since 1.6 usermeta storage
				$user->profile_fields->set($field, $input->post($field));
            }

            // update password
            $change_password = $input->post('change_password');

            if (!empty($change_password)) {
				wp_set_password($change_password, $user_id);
			}

            // update the WordPress user information
            global $wpdb;
            $new_username = $input->post('user_nicename');
            $old_username =  $user->get_username();

            $username_changed = FALSE;
            if( $new_username != $old_username ) {
                $username_changed = TRUE;
            }

            // TODO: need to check if $new_username is already used and not allow the change - `user_login` does not have a UNIQUE index!
            // TODO: the check for and updating of the `user_login` need to be within a transaction to ensure no duplicate names
            $data_user = array('user_login' => $new_username);
            $ret_update_user = $wpdb->update($wpdb->users, $data_user, array('ID' => $user_id));
            // TODO: don't return, allow the rest of the form contents to be updated and return a form validation error message
            // TODO: $wpdb->update() returns the number of rows updated (1 in this case) or FALSE on error -- not a WP_Error instance

            $data = array('ID' => $user_id);
            $props = array('first_name', 'last_name', 'user_url', 'description');
            foreach ($props as $prop) {
                if (isset($_POST[$prop]))
                    $data[$prop] = $input->post_raw($prop);
            }
            $data['display_name'] = $input->post('first_name') . ' ' . $input->post('last_name');
            $data['nickname']     = $new_username;
            $data['user_nicename']= $new_username;
            $ret = wp_update_user($data);

            if (!is_wp_error($ret)) {

                do_action('peepso_profile_after_save', $user_id);

                if( TRUE === $username_changed ) {
                    PeepSo::redirect(PeepSo::get_page('activity'));
                    die();
                }

                $profile->add_message(__('Changes successfully saved.', 'peepso'));
            }

            remove_filter('peepso_form_validate_after', array(&$profile, 'change_password_validate_after'), 10);
        }
    }


    /*
     * Function called when saving an Edit Preference form
     * @param int $id The user id for the form save operation
     */
    public function save_preference_form($id)
    {
        // check permissions
        if (PeepSo::check_permissions($this->view_user_id, PeepSo::PERM_PROFILE_EDIT, PeepSo::get_user_id())) {
            $input = new PeepSoInput();

            $nonce = $input->post('-form-id');
            if (FALSE === wp_verify_nonce($nonce, 'profile-edit-preferences-form'))
                return;

            // verify that authkey field is empty
            $authkey = $input->post('authkey');
            if (!empty($authkey))
                return;

            // verify a valid user id
            $user_id = $input->post_int('user_id');
            if (0 === $user_id)
                return;

            $profile = PeepSoProfile::get_instance();
            $profile->set_user_id($user_id);

            // create a user instance for this user
            $user = new PeepSoUser($user_id);
            $data = $user->get_peepso_user();

            $edit_form = $profile->edit_preferences();

            $form = PeepSoForm::get_instance();
            $form->add_fields($edit_form['fields']);
            $_POST['profile_url'] = PeepSo::get_page('profile') .'?'. $user->get_nicename(FALSE) . '/';
            $form->map_request();

            if (!$form->validate())
                return (FALSE);

            $data['usr_profile_acc'] = $input->post_int('usr_profile_acc', PeepSo::ACCESS_MEMBERS);


            // update the peepso_user table with the post data
            $ret = $user->update_peepso_user($data);
            update_user_meta($user_id, 'peepso_hide_online_status', $input->post_int('hide_online_status', FALSE));
            if( 1 == get_user_meta($user_id, 'peepso_hide_online_status', TRUE) ) {
                delete_transient('peepso_'.$user_id.'_online');
            }
            update_user_meta($user_id, 'peepso_profile_post_acc', $input->post_int('peepso_profile_post_acc', 0));
            update_user_meta($user_id, 'peepso_is_profile_likable', $input->post_int('profile_likes', FALSE));
            update_user_meta($user_id, 'profile_display_name_as', $input->post('profile_display_name_as', FALSE));
            update_user_meta($user_id, 'peepso_gmt_offset', $input->post('gmt_offset', FALSE));

            if ($input->post_int('feeds_to_show') != PeepSo::get_option('site_activity_posts', 20))
                update_user_meta($user_id, 'peepso_feeds_to_show', $input->post_int('feeds_to_show'));


            if (!is_wp_error($ret)) {
                /**
                 * @param  int $user_id
                 * @param  PeepSoForm $form
                 */
                do_action('peepso_after_preference_update', $user_id, $form);
                $profile->add_message(__('Changes successfully saved.', 'peepso'));
            }
        }
    }

    /*
     * Enqueues needed css and javascript files for the Profile page
     */
    public function enqueue_scripts()
    {
        $input = new PeepSoInput();
//PeepSo::log(__METHOD__ . '()');
        $load = array(
            'peepso-window' => 'js/window-1.0.js~jquery',
//			'peepso-bootstrap' => 'js/bootstrap.js~jquery',
//			'peepso-toolkit' => 'js/toolkit.js~jquery',
            'peepso-form' => 'js/form.js~jquery,peepso-profile,peepso-datepicker',
            'peepso-dropdown' => 'js/dropdown.js~jquery,peepso-profile',

            'peepso-slimscroll' => 'js/jquery.slimscroll.js~jquery',
            'peepso-backbone' => 'js/backbone.js~jquery',
//			'peeoso-require' => 'js/require.js~jquery',
            'peepso-validate' => 'js/validate-1.5.js~jquery',

            'peepso-profile' => 'js/profile.js~jquery',
        );

        if (PeepSo::is_admin() || PeepSo::get_user_id() == $this->view_user_id) {
            $load['peepso-crop'] = 'js/crop.js~jquery,peepso-hammer';
            $load['peepso-profileavatar'] = 'js/profile-edit.js~jquery,peepso-crop';
        }

        if ($input->get_exists('notifications'))
            $load['peepso-profile-notification'] = 'js/profile-notification.js~jquery,peepso';
        if ($input->get_exists('blocked'))
            $load['peepso-blocks'] = 'js/profile-blocks.js~jquery,peepso';
        if ($input->get_exists('alerts'))
            $load['peepso-profile-alerts'] = 'js/profile-alerts.js~jquery,peepso';

        wp_enqueue_script('jquery-ui-widget');
        wp_enqueue_script('jquery-ui-draggable');
        foreach ($load as $handle => $data) {
            $parts = explode('~', $data, 2);
            $deps = explode(',', $parts[1]);

            wp_register_script($handle, PeepSo::get_asset($parts[0]), $deps, PeepSo::PLUGIN_VERSION, TRUE);
            wp_enqueue_script($handle);
        }

        wp_localize_script('peepso-profile', 'peepsoprofiledata', array(
            'label_confirm_block_user' => 'Are you sure want to block this user?'
        ));

        wp_register_script('peepso-resize', PeepSo::get_asset('js/jquery.autosize.min.js'),
            array('jquery'), PeepSoActivityStream::PLUGIN_VERSION, TRUE);

        wp_enqueue_script('peepso-posttabs');

        wp_register_style('peepso-datepicker', PeepSo::get_asset('css/datepicker.css'), NULL, PeepSo::PLUGIN_VERSION, 'all');
    }


    /**
     * Returns TRUE of FALSE whether an err_message has been set.
     * @return boolean
     */
    public function has_error()
    {
        return (NULL !== $this->err_message);
    }


    /**
     * Returns the error message as a string.
     * @return string The error message.
     */
    public function get_error_message()
    {
        return ($this->err_message);
    }

    /**
     * callback - peepso_activity_dialogs
     * Renders the dialog boxes for uploading profile and cover photo.
     */
    public function upload_dialogs()
    {
        wp_enqueue_style('peepso-fileupload');
        PeepSoTemplate::exec_template('profile', 'dialog-profile-avatar');
        PeepSoTemplate::exec_template('profile', 'dialog-profile-cover');
    }

    /*
     * Function called when saving an Emails and Notifications form
     * @param int $user_id The user id for the form save operation
     */
    public function save_alerts_form($user_id)
    {
        if (PeepSo::get_user_id() != $this->view_user_id && !PeepSo::is_admin())
            return (FALSE);

        $input = new PeepSoInput();

        $profile = PeepSoProfile::get_instance();

        $sanitized_fields = array();
        $fields = $profile->get_alerts_form_fields();
        foreach ($fields as $field) {
            if ('custom' === $field['type'])
                if (isset($field['fields']) && is_array($field['fields'])) {
                    foreach ($field['fields'] as $subfield) {
                        if ('label' !== $subfield['type'] && (isset($subfield['name']) && '__' !== substr($subfield['name'], 0, 2)))
                            $sanitized_fields[] = $subfield;
                    }
                }
                else
                    $sanitized_fields[] = $field;
        }
        $unchecked_fields = array();
        foreach ($sanitized_fields as $field) {
            $field_value = $input->post_int($field['name']);
            if (1 !== $field_value)
                $unchecked_fields[] = $field['name'];
        }
        $ret = update_user_meta($user_id, 'peepso_notifications', $unchecked_fields);
        if (TRUE === $ret)
            $profile->add_message(__('Changes successfully saved.', 'peepso'));
    }


    public function peepso_profile_segment_about()
    {
        echo PeepSoTemplate::exec_template('profile', 'profile-about');
    }

    public function peepso_profile_segment_menu_links($links)
    {
        $links[2][] = array(
            'href' => 'about',
            'title'=> __('About', 'peepso'),
            'id' => 'about',
            'icon' => 'pencil'
        );

        ksort($links);
        return $links;
    }


    public function peepso_page_title_profile_segment( $title )
    {
        if ( 'about' === $title['profile_segment']) {
            $title['newtitle'] = $title['title'] . " - ". __('about', 'friendso');
        }

        return $title;
    }
}

// EOF
