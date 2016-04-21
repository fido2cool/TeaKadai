(function( root, $, factory ) {

	var PsMember = factory( root, $ );
	ps_member = new PsMember();

})( window, jQuery, function( window, $ ) {

/**
 * User managements.
 * @class PsMember
 */
function PsMember() {
}

/**
 * Block specific user.
 * @param {number} user_id User ID to be blocked.
 * @param {HTMLElement=} elem Block button.
 */
PsMember.prototype.block_user = function( user_id, elem ) {
	if ( this.blocking_user ) {
		return;
	}

	if ( elem ) {
		elem = $( elem );
		elem.find('img').css('display', 'inline');
	}

	this.blocking_user = true;
	peepso.postJson('activity.blockuser', { uid: peepsodata.currentuserid, user_id: user_id }, $.proxy(function( json ) {
		this.blocking_user = false;
		if ( json.success ) {
			$( '.ps-js-focus--' + user_id ).find('.ps-focus-actions, .ps-focus-actions-mobile').html( json.data.actions );
			ps_observer.apply_filters('ps_member_user_blocked', user_id, json.data );
			psmessage.show(json.data.header, json.data.message, psmessage.fade_time);
			if (json.data.redirect) {
				setTimeout(function() {
					window.location = json.data.redirect;
				}, Math.min( 1000, psmessage.fade_time));
			}
		}
	}, this ));
};

/**
 * Unblock specific user.
 * @param {number} user_id User ID to be unblocked.
 * @param {HTMLElement=} elem Unblock button.
 */
PsMember.prototype.unblock_user = function( user_id, elem ) {
	if ( this.unblocking_user ) {
		return;
	}

	if ( elem ) {
		elem = $( elem );
		elem.find('img').css('display', 'inline');
	}

	this.unblocking_user = true;
	peepso.postJson('activity.unblockuser', { uid: peepsodata.currentuserid, user_id: user_id }, $.proxy(function( json ) {
		this.unblocking_user = false;
		if ( json.success ) {
			jQuery('.ps-js-focus--' + user_id ).find('.ps-focus-actions, .ps-focus-actions-mobile').html( json.data.actions );
			ps_observer.apply_filters('ps_member_user_unblocked', user_id, json.data );
			psmessage.show( json.data.header, json.data.message, psmessage.fade_time );
		}
	}, this ));
};

/**
 * Ban specific user.
 * @param {number} user_id User ID to be banned.
 * @param {HTMLElement=} elem Ban button.
 */
PsMember.prototype.ban_user = function( user_id, elem ) {
	if ( this.banning_user ) {
		return;
	}

	if ( elem ) {
		elem = $( elem );
		elem.find('img').css('display', 'inline');
	}

	this.banning_user = true;
	peepso.postJson('activity.set_ban_status', { user_id: user_id, ban_status: 1 }, $.proxy(function( json ) {
		this.banning_user = false;
		if ( json.success ) {
			ps_observer.apply_filters('ps_member_user_banned', user_id, json.data );
			psmessage.show( json.data.header, json.data.message, psmessage.fade_time );
			setTimeout(function() {
				window.location.reload();
			}, Math.min( 1000, psmessage.fade_time));
		}
	}, this ));
};

/**
 * Unban specific user.
 * @param {number} user_id User ID to be unbanned.
 * @param {HTMLElement=} elem Unban button.
 */
PsMember.prototype.unban_user = function( user_id, elem ) {
	if ( this.unbanning_user ) {
		return;
	}

	if ( elem ) {
		elem = $( elem );
		elem.find('img').css('display', 'inline');
	}

	this.unbanning_user = true;
	peepso.postJson('activity.set_ban_status', { user_id: user_id, ban_status: 0 }, $.proxy(function( json ) {
		this.unbanning_user = false;
		if ( json.success ) {
			ps_observer.apply_filters('ps_member_user_unbanned', user_id, json.data );
			psmessage.show( json.data.header, json.data.message, psmessage.fade_time );
			setTimeout(function() {
				window.location.reload();
			}, Math.min( 1000, psmessage.fade_time));
		}
	}, this ));
};

return PsMember;

});
