<?php

/**
 * Class PeepSoProfileFields
 *
 *
 * This class aims to handle all fields considered "PeepSo profile fields":
 *
 * - getting values
 * - setting values
 * - running accessibility checks against privacy settings
 *
 *
 * The fields considered "profile fields" in core PeepSo are:
 *
 * [[ Wordpress defaults ]]
 *
 * first_name
 * last_name
 * description
 * user_url
 *
 * [[ PeepSo defaults ]]
 *
 * peepso_user_field_gender
 * peepso_user_field_birthdate
 *
 * [[ Accessibility flags ]]
 *
 * peepso_user_field_description_acc
 * peepso_user_field_user_url_acc
 * peepso_user_field_gender_acc
 * peepso_user_field_birthdate_acc
 *
 * [[ More ]]
 *
 * Additional fields can be attached with hooks
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 */

class PeepSoProfileFields
{
	/** VARS **/

	private $id;
	private $peepso_user;
	private $wp_user;

	const META_FIELD_KEY = 'peepso_user_field_';

	// List of keys in usermeta storing user information
	public $meta_keys = array(
			'gender',
			'birthdate',
	);

	// List of keys in usermeta storing access levels to fields
	public $meta_keys_acc = array(
			'description_acc',
			'user_url_acc',
			'gender_acc',
			'birthdate_acc',
	);

	public $wp_defaults = array(
			'user_url',
			'description',
	);

	/** INIT **/

	/**
	 * PeepSoProfileFields constructor.
	 *
	 * We want this to be called only from and accessed only via PeepSoUser
	 *
	 * @param PeepSoUser $peepso_user $this of calling PeepSoUser instance
	 */
	public function __construct( PeepSoUser &$peepso_user )
	{
		$this->id = $peepso_user->get_id();
		$this->user = $peepso_user;
		$this->_wp_user();
		$this->create_user();

		$this->meta_keys 	= apply_filters('peepso_profile_fields_keys', 		$this->meta_keys);
		$this->meta_keys_acc= apply_filters('peepso_profile_fields_keys_acc', 	$this->meta_keys_acc);
	}

	/** GET & SET **/

	/**
	 * Return any usermeta, with privacy check
	 * @since 1.6
	 * @reason Custom Profile Fields
	 *
	 * @param string 	$key 		the meta key
	 * @param bool|TRUE $check_acc	whether to perform privacy check
	 * @return bool|mixed
	 */
	public function get($key, $check_acc = TRUE)
	{
		$this->_wp_user();

		if(TRUE === $check_acc && !$this->user->is_accessible($key, $this->get_acc($key))) {
			return FALSE;
		}

		// we are usually handling internal PeepSo meta here
		$key = $this->_meta_key_add($key);

		if (isset($this->wp_user->$key)) {
			return ($this->wp_user->$key);
		}

		// fallback 1: to  wordpress defaults
		$key = $this->_meta_key_trim($key);
		if (isset($this->wp_user->$key)) {
			return ($this->wp_user->$key);
		}

		// fallback 2: to legacy value
		$ret_legacy = $this->get_legacy_field($this->_meta_key_trim($key), $check_acc);
		if(add_user_meta($this->id, $this->_meta_key_add($key), $ret_legacy, true)) {
			return $ret_legacy;
		}

		return FALSE;
	}

	/**
	 * Save any valid user meta
	 *
	 * @param 	string	$key
	 * @param	string	$value
	 * @return	bool|int
	 */
	public function set($key, $value)
	{
		if ( !$this->_check($key) ) {
			return FALSE;
		}

		// use ints for the _acc fields
		if (substr($key, -4) == '_acc') {
			$value = intval($value);
		}

		$key = $this->_meta_key_add($key);
		return update_user_meta($this->id, $key, $value);
	}

	public function create_user()
	{
		$keys = array_merge($this->meta_keys, $this->meta_keys_acc);

		foreach($keys as $key) {
			$this->get($key);
		}
	}

	/**
	 * Provides fallback if PeepSo has been freshly upgraded
	 * Will pull data from legacy peepso_users columns and copy them over to the new meta keys
	 *
	 * @param 	string	$key
	 * @param 	bool|TRUE $check_acc
	 * @return 	bool|mixed
	 */
	private function get_legacy_field($key, $check_acc = TRUE)
	{
		$this->_wp_user();

		$col_name = 'usr_' . $key;
		$acc_name = $col_name . '_acc';			// name of access column in peepso_users table

		if ($check_acc) {
			// if there's an access column, check it
			// $this->user->peepso_user is NOT a typo
			if (isset($this->user->peepso_user[$acc_name])) {
				if (!$this->user->is_accessible($key))
					return (FALSE);
			}
		}

		if (isset($this->wp_user->$key)) {
			return ($this->wp_user->$key);
		}

		return (FALSE);
	}

	/** UTILS **/

	/**
	 * Make sure $this->wp_user is properly set-up
	 */
	private function _wp_user()
	{
		if( FALSE == $this->wp_user) {
			$this->wp_user = get_user_by('id', $this->id);
		}
	}

	/**
	 * Attach self::meta_key_add to the key
	 *
	 * @param 	string	$key
	 * @return 	string
	 */
	private function _meta_key_add( $key )
	{
		return self::META_FIELD_KEY . $this->_meta_key_trim( $key );
	}



	/**
	 * Remove self::meta_key_add from the key
	 *
	 * @param	string	$key
	 * @return 	string
	 */
	private function _meta_key_trim( $key )
	{
		return str_ireplace(self::META_FIELD_KEY, '', $key);
	}

	/** VALIDATION & ACCESS **/

	/**
	 * Check if given meta key is a profile field
	 *
	 * @param $key
	 * @return bool
	 */
	private function _check( $key )
	{
		$key = $this->_meta_key_trim($key);
		$ret = (in_array($key, $this->meta_keys) || in_array($key, $this->meta_keys_acc)) ? TRUE : FALSE;
		return $ret;
	}

	public function get_acc( $key )
	{
		$key = $this->_meta_key_add($key);
		$key_acc = $key.'_acc';

		if( $this->_check($key_acc) ) {
			$ret = $this->get($key_acc, FALSE);
			#var_dump($ret);
			return $ret;
		}

		return FALSE;
	}

	public function dump()
	{
		echo "<h1>meta_keys_acc</h1>";
		foreach ($this->meta_keys_acc as $k) {
			echo "<pre>$k:</pre>".var_dump($this->get($k));
		}

		$wp_defaults = array(
			'user_url',
			'description',
	);
	}
}
// EOF