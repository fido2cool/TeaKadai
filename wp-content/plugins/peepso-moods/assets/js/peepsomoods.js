/**
* Javascript code to handle mood events
*/

function PsMoods()
{
	this.$postbox = null;
	this.$mood = null;
	this.$mood_remove = null;
	this.$mood_dropdown_toggle = null;
	this.mood_selected = false;
	this.can_submit = false;
}

/**
 * Defines the postbox this instance is running on.
 * Called on postbox.js _load_addons()
 * @param {object} postbox This refers to the parent postbox object which this plugin may inherit, override, and manipulate its input boxes and behavior
 */
PsMoods.prototype.set_postbox = function(postbox)
{
    this.$postbox = postbox;
};

/**
 * Initializes this instance's container and selector reference to a postbox instance.
 * Called on postbox.js _load_addons()
 */
PsMoods.prototype.init = function()
{
	if (_.isUndefined(this.$postbox))
		return;

	var _self = this;

	ps_observer.add_filter("peepso_postbox_can_submit", function(can_submit) {
		can_submit.soft.push( _self.can_submit );
		return can_submit;
	}, 20, 1);

	this.$mood = jQuery("#postbox-mood", this.$postbox);
	this.$mood_dropdown_toggle = jQuery("#mood-tab .interaction-icon-wrapper > a", this.$postbox);
	this.$mood_remove = jQuery("#postbox-mood-remove", this.$postbox);

	// Add click event on all mood links
	this.$mood.on("click", "li.mood-list > a", function(e) {
		_self.select_mood(e);
	});

	// Add click event on remove mood
	this.$mood_remove.on("click", function() {
		_self.remove_mood();
	});

	this.$mood_dropdown_toggle.on("click", function() {
		_self.$mood.toggle();
	});

	this.$mood_dropdown_toggle.on("peepso.interaction-hide", function() {
		_self.$mood.hide();
	});

	// close the moods popup when click outside area
	jQuery(document).on("click", function(e) {
		var mood = jQuery("#mood-tab", _self.$postbox);
		if (!mood.is(e.target) && 0 === mood.has(e.target).length)
			_self.$mood.hide();
	});

	// close the moods popup when done with the post
	this.$postbox.on("postbox.post_cancel postbox.post_saved", function() {
		_self.remove_mood();
	});

	// This handles adding the selected mood to the postbox_req variable before submitting to server
	ps_observer.add_filter(this.$postbox.selector + "-postbox_req", function(req) {
		return _self.set_mood(req);
	}, 10, 1);

	ps_observer.add_filter("peepso_postbox_addons_update", function(list) {
		var mood, html;
		if ( _self.mood_selected ) {
			mood = _self.mood_selected;
			html = '<i class="ps-emoticon ' + mood[0] + '"></i> <b>' + mood[1] + '</b>';
			list.push(html);
		}

		return list;
	}, 10, 1);
};

/**
 * Sets #postbox-mood when user clicks a mood icon
 * @param {object} e Click event
 */
PsMoods.prototype.select_mood = function(e)
{
	var a                 = jQuery(e.target).closest("a");
	var btn               = jQuery("#mood-tab", this.$postbox);
	var input             = jQuery("#postbox-mood-input", this.$postbox);
	var placeHolder       = btn.find("a");
	var menu              = a.closest("#postbox-mood");
	var $postboxcontainer = this.$postbox.$textarea.parent();
	var $moodremovewrap   = this.$mood_remove.parent();

	var icon = a.find("i").attr("class");
	var label = jQuery("#mood-text-string").text() + a.attr("data-option-display-value");

	input.val(a.attr("data-option-value"));
	jQuery($moodremovewrap).show();
	menu.hide();

	this.mood_selected = [ icon, label ];
	this.can_submit = true;
	this.$postbox.on_change();
};

/**
 * Clear #postbox-mood-input when user clicks remove mood button
 */
PsMoods.prototype.remove_mood = function()
{
	jQuery("span#postmood", this.$postbox.$textarea.parent()).remove();
	jQuery("#postbox-mood-input", this.$postbox).val("");

	this.$mood_remove.parent().hide();
	this.$mood.hide();
	this.mood_selected = false;
	this.can_submit = false;
	this.$postbox.on_change();
};

/**
 * Adds the selected mood to the postbox_req variable
 * @param {object} req postbox request
 * @return {object} req Returns modified request with mood value
 */
PsMoods.prototype.set_mood = function(req)
{
	if ("undefined" === typeof(req.mood))
		req.mood = "";

	req.mood = jQuery("#postbox-mood-input", this.$postbox).val();
	return (req);
};

/**
 * Adds a new PsMoods object to the PostBox instance.
 * @param {array} addons An array of addons that are being pluged in to the PostBox.
 */
ps_observer.add_filter('peepso_postbox_addons', function(addons) {
    addons.push(new PsMoods);
    return (addons);
}, 10, 1);

// EOF
