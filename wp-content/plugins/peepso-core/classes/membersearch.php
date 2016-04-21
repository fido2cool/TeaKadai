<?php

class PeepSoMemberSearch implements PeepSoAjaxCallback
{
	protected static $_instance = NULL;
	private $_member_query = NULL;

	public $template_tags = array(
		'found_members',
		'get_next_member',
		'show_member'
	);

	/*
	 * return singleton instance
	 */
	public static function get_instance()
	{
		if (NULL === self::$_instance)
			self::$_instance = new self();
		return (self::$_instance);
	}


	/**
	 * Search for users matching the query.
	 * @param  PeepSoAjaxResponse $resp
	 */
	public function search(PeepSoAjaxResponse $resp)
	{
		$input = new PeepSoInput();

		// is this members listing page?
		$is_page = intval($input->get('is_page', 0));

		$args = array();
		$args_pagination = array();
		$page = $input->get_int('page', 1);

		// Sorting and filtering if in page view (not toolbar search)
		if ($is_page) {

			// Sorting
			$order_by	= $input->get('order_by', 	NULL);
			$order		= $input->get('order', NULL);

			if( NULL !== $order_by && strlen($order_by) ) {
				if('ASC' !== $order && 'DESC' !== $order) {
					$order = 'DESC';
				}

				$args['orderby']= $order_by;
				$args['order']	= $order;
			}

			// Additional peepso specific filters

			// Avatar only
			$peepso_args['avatar_custom'] = (int) $input->get('peepso_avatar', 0);
			if ( 1 !== $peepso_args['avatar_custom'] ) {
				unset( $peepso_args['avatar_custom'] );
			}

			// Gender filter
			$peepso_args['meta_gender'] = strtolower($input->get('peepso_gender', ''));
			if ( !in_array( $peepso_args['meta_gender'], array('m','f') ) ) {
				unset( $peepso_args['meta_gender'] );
			}

			if( is_array($peepso_args) && count($peepso_args)) {
				$args['_peepso_args'] = $peepso_args;
			}
		}


		// Even if we perform the search in the toolbar, we want only the 1 page of results
		$limit = intval(PeepSo::get_option('site_activity_posts', 20));
		$resp->set('page', $page);
		$args_pagination['offset'] = ($page-1)*$limit;
		$args_pagination['number'] = $limit;

		// Merge pagination args and run the query to grab paged results
		$args = array_merge($args, $args_pagination);
		$query = stripslashes_deep($input->get('query', ''));
		$query_results = new PeepSoUserSearch($args, PeepSo::get_user_id(), $query);
		$members_page = count($query_results->results);
		$members_found = $query_results->total;

		$resp->set('members_page', $members_page);
		$resp->set('members_found', $members_found);

			if (count($query_results->results) > 0) {

				foreach ($query_results->results as $user_id) {
					$buttons = apply_filters('peepso_member_notification_buttons', array(), $user_id);

					if ($is_page) {

						ob_start();

						echo '<div id="" class="ps-members-item-wrapper">';
						echo '<div id="" class="ps-members-item">';
						peepso('memberSearch', 'show-member', new PeepSoUser($user_id));
						echo '</div>';
						echo '</div>';

						$members[] = ob_get_contents();

						ob_end_clean();

					} else {

						$notifications[] = PeepSoTemplate::exec_template(
							'members',
							'search-popover-item',
							array('user_id' => $user_id, 'buttons' => $buttons),
							TRUE
						);

					}
				}

				$resp->success(TRUE);
				if ($is_page) {
					$resp->set('members', $members);
				} else {
					$view_all_text = __('View All', 'peepso');
					if ($members_page < $members_found) {
						$view_all_text = sprintf( __('View %d more', 'peepso'), $members_found - $members_page );
					}
					$resp->set('notifications', $notifications);
					$resp->set('view_all_text', $view_all_text);
				}

			} else {
				$resp->success(FALSE);
				$resp->error(__('No users found.', 'peepso'));
			}
	}

	/**
	 * Sets the _member_query variable to use is template tags
	 * @param PeepSoUserSearch $query
	 */
	public function set_member_query(PeepSoUserSearch $query)
	{
		$this->_member_query = $query;
	}

	/**
	 * Return TRUE/FALSE if the user has friends
	 * @return boolean
	 */
	public function found_members()
	{
		if (is_null($this->_member_query))
			return FALSE;

		return (count($this->_member_query) > 0);
	}

	/**
	 * Iterates through the $_member_query and returns the current member in the loop.
	 * @return PeepSoUser A PeepSoUser instance of the current member in the loop.
	 */
	public function get_next_member()
	{
		if (is_null($this->_member_query))
			return FALSE;

		return $this->_member_query->get_next();
	}

	/**
	 * Displays the member.
	 * @param  PeepSoUser $member A PeepSoUser instance of the member to be displayed.
	 */
	public function show_member($member)
	{
		$online = '';
		if (get_transient('peepso_'.$member->get_id().'_online')) {
			$online = '<span class="ps-member-is-online icon-circle"></span>';
		}

		echo '<div class="ps-members-item-avatar"><div class="ps-avatar">
				<a href="' . $member->get_profileurl() . '">
					<img alt="' . $member->get_display_name() . '"
					src="' . $member->get_avatar() . '" class="ps-name-tips"></a>' .
				'</div>
			</div>
			<div class="ps-members-item-body">
				<a href="' , $member->get_profileurl(), '" class="ps-members-item-title" title="', $member->get_display_name(), '" alt="', $member->get_display_name(), '">'
					, $online , $member->get_display_name() ,
				'</a><span class="ps-members-item-status">';

		do_action('peepso_after_member_thumb', $member->get_id());

		echo '</span></div>';


		$this->member_options($member->get_id());
		$this->member_buttons($member->get_id());
	}

	/**
	 * Displays a dropdown menu of options available to perform on a certain user based on their member status.
	 * @param int $user_id The current member in the loop.
	 */
	public static function member_options($user_id, $profile = FALSE)
	{
		if( PeepSo::get_user_id() == $user_id ) {
			return array();
		}

		$options = array();

		/*$blk = new PeepSoBlockUsers();

		if ($blk->is_user_blocking(PeepSo::get_user_id(), $user_id)) {

			$options['unblock'] = array(
				'label' => __('Unblock User', 'peepso'),
				'click' => 'profile.unblock_user(' . $user_id . ', this); return false;',
				'title' => __('Allow this user to see all of your activities', 'peepso'),
				'icon' => 'lock',        // @todo icon
			);

		} else {
		*/
		$options['block'] = array(
			'label' => __('Block User', 'peepso'),
			'click' => 'ps_member.block_user(' . $user_id . ', this); return false;',
			'title' => __('This user will be blocked from all of your activities', 'peepso'),
			'icon' => 'remove',
		);

		// ban/unban only available for admin role
		if( FALSE != PeePso::is_admin())
		{
			// ban
			$options['ban'] = array(
				'label' => __('Ban', 'peepso'),
				'click' => 'ps_member.ban_user(' . $user_id . ', this); return false;',
				'icon' => 'minus-sign',
			);

			// "unban" is only available from profile page
			if( FALSE !== $profile )
			{
				$options['unban'] = array(
					'label' => __('Unban', 'peepso'),
					'click' => 'ps_member.unban_user(' . $user_id . ', this); return false;',
					'icon' => 'plus-sign',
				);

				// check ban status
				$user = new PeepSoUser($user_id);
				if( 'ban' == $user->get_user_role()) {
					unset( $options['ban'] );
				} else {
					unset( $options['unban'] );
				}
			}
		}

		$options = apply_filters('peepso_member_options', $options, $user_id);

		if (0 === count($options))
			// if no options to display, exit
			return;

		$member_options = '';
		foreach ($options as $name => $data) {
			$member_options .= '<li';

			if (isset($data['li-class']))
				$member_options .= ' class="' . $data['li-class'] . '"';
			if (isset($data['extra']))
				$member_options .= ' ' . $data['extra'];

			$member_options .= '><a href="#" ';
			if (isset($data['click']))
				$member_options .= ' onclick="' . esc_js($data['click']) . '" ';
			$member_options .= ' ">';

			$member_options .= '<i class="ps-icon-' . $data['icon'] . '"></i><span>' . $data['label'] . '</span>' . PHP_EOL;
			$member_options .= '</a></li>' . PHP_EOL;
		}

		if( FALSE === $profile) {
			echo PeepSoTemplate::exec_template('members', 'member-options', array('member_options' => $member_options), TRUE);
		} else {
			echo PeepSoTemplate::exec_template('profile', 'profile-options', array('profile_options' => $member_options), TRUE);
		}
	}

	/**
	 * Displays a available buttons to perform on a certain user based on their member status.
	 * @param int $user_id The current member in the loop.
	 */
	public static function member_buttons($user_id)
	{
		if( $user_id == PeepSo::get_user_id() ) {
			return;
		}

		$buttons = apply_filters('peepso_member_buttons', array(), $user_id);

		if (0 === count($buttons)) {
			// if no buttons to display, exit
			return;
		}

		$member_buttons = '';
		foreach ($buttons as $name => $data) {
			$member_buttons .= '<button';

			if (isset($data['class']))
				$member_buttons .= ' class="' . $data['class'] . '"';
			if (isset($data['extra']))
				$member_buttons .= ' ' . $data['extra'];
			if (isset($data['click']))
				$member_buttons .= ' onclick="' . esc_js($data['click']) . '" ';

			$member_buttons .= ' ">';

			if (isset($data['icon']))
				$member_buttons .= '<i class="ps-icon-' . $data['icon'] . '"></i> ';
			if (isset($data['label']))
				$member_buttons .= '<span>' . $data['label'] . '</span>';

			if (isset($data['loading']))
				$member_buttons .= ' <img style="margin-left:2px;display:none" src="' . PeepSo::get_asset('images/ajax-loader.gif') .'" alt=""></span>';

			$member_buttons .= '</button>' . PHP_EOL;
		}

		echo PeepSoTemplate::exec_template('members', 'member-buttons', array('member_buttons' => $member_buttons, 'user_id' => $user_id), TRUE);
	}
}

// EOF
