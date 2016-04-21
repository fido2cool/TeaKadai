(function( root, $, factory ) {

    var PsLocation = factory( root, $ );
    pslocation = new PsLocation();

})( window, jQuery, function( window, $ ) {

function PsLocation()
{
	this.coords = null;
	this.$places_container = null;
	this.$input_search = null;
	this.marker = null;
	this.map = null;
	this.selected_place = null;
	this._search_service = null;
	this._latLang = null;
	this.last_selected_place = null;
	this.location_selected = false;
	this.can_submit = false;
}

/**
 * Initializes this instance's container and selector reference to a postbox instance.
 * Called on postbox.js _load_addons()
 */
PsLocation.prototype.init = function()
{
	if (_.isNull(this.$postbox))
		return;

	var that = this;

	ps_observer.add_filter("peepso_postbox_can_submit", function(can_submit) {
		can_submit.soft.push( that.can_submit );
		return can_submit;
	}, 30, 1);

	$(this.$postbox).on("click", "#location-tab a", function() {
		that.toggle_input();
	});

	this.$input_search = $("#postbox_loc_search", this.$postbox);
	this.$container = $("#pslocation", this.$postbox);
	this.$postboxcontainer = this.$postbox.$textarea.parent();
	this.$places_container = $(".ps-postbox-locations", this.$container);

	// Add delay 15 seconds before call 'location_search()' to give user enough time to type new location manually
	// It's important because 'location_search()' will trigger 'click' event to draw map using first location
	var timer = null;
	this.$input_search.on("keyup", function() {
		var t = this;
		clearTimeout(timer);
		var $loading = $("<li>" + $("#pslocation-search-loading").html() + "</li>");
		that.$places_container.html($loading);
		timer = setTimeout(function() {
			that.location_search($(t).val());
		}, 1500);
	});

	ps_observer.add_filter(this.$postbox.selector + "-postbox_req", function(req, other) {
		return that.postbox_request(req, other);
	}, 10, 1);

	this.$postbox.on("postbox.post_cancel postbox.post_saved", function(evt, request, response) {
		that.postbox_cancel_saved(request, response);
	});

	this.$select_location = $(".ps-location-action .ps-add-location", this.$container);
	this.$remove_location = $(".ps-location-action .ps-remove-location", this.$container);

	this.$select_location.on("click", function(e) { e.preventDefault(); that.on_select_location(); });
	this.$remove_location.on("click", function(e) { e.preventDefault(); that.on_remove_location(); });

	$(this.$postbox).on("peepso.interaction-hide", "#location-tab a", function() {
		that.$container.addClass("hidden");
	});

	ps_observer.add_filter("peepso_postbox_addons_update", function(list) {
		if ( that.location_selected ) {
			list.unshift("<b>" + that.location_selected + "</b>");
		}
		return list;
	}, 10, 1);
};

/**
 * Adds the selected location/place when Post button is clicked and before submitted
 * @param {object} postbox request object
 * @param {mixed} other currently not in used
 */
PsLocation.prototype.postbox_request = function(req, other)
{
	if (null !== this.selected_place) {
		req.location = {
			name: this.selected_place.name,
			latitude: this.selected_place.geometry.location.lat(),
			longitude: this.selected_place.geometry.location.lng()
		};
	}
	return (req);

	ps_observer.add_filter(this.$postbox.selector + "-postbox_req", function(req, other) {
		if (null !== that.selected_place) {
			req.location = {
				name: that.selected_place.name,
				latitude: that.selected_place.geometry.location.lat(),
				longitude: that.selected_place.geometry.location.lng()
			};
		}
		return (req);
	}, 10, 1);
}

/**
 * Called after postbox is saved or cancelled
 * @param {object} request Postbox request object - available only for after saved
 * @param {object} response Postbox response - available only for after saved
 */
PsLocation.prototype.postbox_cancel_saved = function(request, response)
{
	/*
	if ('undefined' !== typeof(request)) {
		if (1 === response.success)
			psmessage.hide().show("", response.notices[0]).fade_out(psmessage.fade_time);
		else if (1 === response.has_errors)
			psmessage.show('', response.errors[0]);
	}
	*/

	this.$container.addClass("hidden");
	this.$input_search.val("");
	this.$remove_location.hide();
	//this.$select_location.hide();
	this.$select_location.show();
	this.$postboxcontainer.find("span#postlocation").remove();
	this.selected_place = null;
	this.location_selected = false;
	this.can_submit = false;
	this.$postbox.on_change();
}

/**
 * Defines the postbox this instance is running on.
 * Called on postbox.js _load_addons()
 * @param {object} postbox This refers to the parent postbox object which this plugin may inherit, override, and manipulate its input boxes and behavior
 */
PsLocation.prototype.set_postbox = function(postbox)
{
	this.$postbox = postbox;
};

/**
 * Searches for a location using the google API
 * @param {string} query The location to search for.
 * @param {function} success_callback Function to run after the search is complete.
 */
PsLocation.prototype.location_search = function(query, success_callback)
{
	var that = this;

	if (_.isEmpty(query)) {
		this.draw_map(this._latLang);
		return;
	}

	this.get_search_service().textSearch({
			query: query,
			location: this._latLang,
			radius: 50000
		},
		function(results, status) {
			that.set_places(results, status);

			// Uses first location to draw map
			if ( !that.$select_location.is(":visible") ) {
				that.$places_container.find("li").first().trigger("click");
			}

			if (typeof(Function) === typeof(success_callback))
				success_callback();
		}
	);
};

/**
 * Sets the location value and appends the location name to the postbox.
 */
PsLocation.prototype.on_select_location = function()
{
	if (null === this.selected_place)
		this.selected_place = this.last_selected_place;

	this.$select_location.hide();
	this.$remove_location.show();

	this.$container.addClass("hidden");

	this.location_selected = $("#pslocation-in-text").text() + ((null !== this.selected_place) ? this.selected_place.name : '');
	this.can_submit = true;
	this.$postbox.on_change();
};

/**
 * Removes the location value and name on the postbox
 */
PsLocation.prototype.on_remove_location = function()
{
	this.$select_location.show();
	this.$remove_location.hide();

	this.selected_place = null;
	this.$postboxcontainer.find("span#postlocation").remove();
	this.$container.addClass("hidden");

	this.location_selected = false;
	this.can_submit = false;
	this.$postbox.on_change();
};

/**
 * Toggles the display of the location UI.
 */
PsLocation.prototype.toggle_input = function()
{
	this.$container.toggleClass("hidden");

	this.$input_search.val("");
	this.location = null;

	if (!this.$container.hasClass("hidden")) {
		var that = this;
		this.load_library(function() {
			that.shown();
		}.bind(that));
	}
};

/**
 * Fires after the location UI is shown and asks the user for geolocation information.
 */
PsLocation.prototype.shown = function()
{
	var that = this;
	this.$input_search.focus();

	// Only draw the map once per page load
	if (false === _.isEmpty(this.map))
		return;

	// Try HTML5 geolocation
	if (navigator.geolocation) {
		navigator.geolocation.getCurrentPosition(function(position) {
			that.coords = position.coords;
			var location = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);
			that.draw_map(location);
		}, function() {
			// User denied to share location
			that.draw_default_map();
		});
	} else if (geoPosition.init()) { // Old browser doesn't support Geolocation
		$("#pslocation .ps-postbox-loading", this.$postbox).show();

		geoPosition.getCurrentPosition(
			function(loc) { // User allowed to share location, draw a new map based on geolocation
				that.coords = loc.coords;
				// set coordinates
				var location = new google.maps.LatLng(loc.coords.latitude, loc.coords.longitude);

				that.draw_map(location);
			},
			function() { // denied to share location
				// Draw a default map first.
				that.draw_default_map();
			}
		);
	} else { // Draw a default map first.
		this.draw_default_map();
	}
};

/**
 * Uses the user's current location to draw the map
 */
PsLocation.prototype.draw_default_map = function()
{
	var location = new google.maps.LatLng(0, 0);
	this.draw_map(location);
};

/**
 * Draws the google map
 * @param {object} location The default center/marker coordinates(latitude and longitude) of google.maps.LatLng object used to render maps
 * @param {boolean} search_nearby If true, search nearby places/locations. Default is true.
 */
PsLocation.prototype.draw_map = function(location, search_nearby)
{
	if (false === _.isBoolean(search_nearby))
		search_nearby = true;

	if (false === (location instanceof google.maps.LatLng))
		return;

	var $map = $("#pslocation-map", this.$postbox);

	$("#pslocation .ps-postbox-loading", this.$postbox).hide();
	$map.show();
	this.$input_search.removeAttr('disabled');

	var that = this;
	this._latLang = location;

	var mapOptions = {
		center: location,
		zoom: 15,
		draggable: false,
		scrollwheel: false,
		disableDefaultUI: true
	};

	ps_observer.apply_filters("ps_location_before_draw_map", $("#pslocation", this.$postbox));

	// Draw map
	if (_.isEmpty(this.map)) {
		this.map = new google.maps.Map(document.querySelector($map.selector),
			mapOptions);

		// Draw marker
		this.marker = new google.maps.Marker({
			position: mapOptions.center,
			map: this.map,
			title:"You are here (more or less)"
		});
	} else {
		this.set_map_center(this._latLang);
	}

	if (search_nearby) {
		// Search nearby places, default action
		var request = {
			location: this._latLang,
			types: [ "establishment" ],
			rankBy: google.maps.places.RankBy.DISTANCE
		};

		this.get_search_service().nearbySearch(request, function(results, status) {
			that.set_places(results, status);
			if ( !that.$select_location.is(":visible") ) {
				that.$places_container.find("li").first().trigger("click");
			}
		});
	}
};

/**
 * Returns an instance of the google places service
 */
PsLocation.prototype.get_search_service = function()
{
	if (_.isEmpty(this.search_service))
		this._search_service = new google.maps.places.PlacesService(this.map);

	return (this._search_service);
};

/**
 * Renders the retrieved places to the dropdown.
 * @param {array} results for google maps places
 * @param {int} status of google maps search
 */
PsLocation.prototype.set_places = function(results, status)
{
	var that = this;
	this.$places_container.find("li").remove();

	if (status === google.maps.places.PlacesServiceStatus.OK) {
 		for (var i = 0; i < results.length; i++)
			this.add_place(results[i]);
	}

	$("li", this.$places_container).on("click", function() {
		$(".ps-location-action", this.$container).show();
		that.$select_location.show();
		that.$remove_location.hide();
	});
};

/**
 * Adds the place to the search list.
 * @param {object} place Contains the details of the place/location in google.maps.Map object which represents a single option in the search result
 */
PsLocation.prototype.add_place = function(place)
{
	if (!_.isEmpty(place.formatted_address))
		place.vicinity = place.formatted_address;

	if (_.isEmpty(place.vicinity))
		return;

	var that = this;

	var $li = $("<li></li>");
	$li.append("<p class='reset-gap'>" + place.name + "</p>");

	$li.append("<span>" + place.vicinity + "</span>");

	this.$places_container.append($li);

	$li.on("click", function() {
		that.set_map_center(place.geometry.location);
		that.$input_search.val(place.name);
		that.selected_place = place;
		that.last_selected_place = that.selected_place;
	});
};

/**
 * Draw a marker and center the view point to the location
 * @param {object} location A google latlang instance.
 */
PsLocation.prototype.set_map_center = function(location)
{
	this.map.setCenter(location);
	this.marker.setPosition(location);
};

/**
 * TODO: docblock
 */
PsLocation.prototype.load_library = function(callback)
{
	if (this.gmap_is_loaded) {
		callback();
		return;
	}

	this.load_library_callbacks || (this.load_library_callbacks = []);
	this.load_library_callbacks.push( callback );

	if (this.gmap_is_loading) {
		return;
	}

	this.gmap_is_loading = true;

	var script = document.createElement('script');
	var api_key = peepsogeolocationdata.api_key;
	var that = this;

	script.type = 'text/javascript';
	script.src = 'https://maps.googleapis.com/maps/api/js?libraries=places' +
		(api_key ? ('&key=' + api_key) : '') +
		'&callback=ps_gmap_callback';

	window.ps_gmap_callback = function() {
		that.gmap_is_loaded = true;
		that.gmap_is_loading = false;
		while (that.load_library_callbacks.length) {
			( that.load_library_callbacks.shift() )();
		}
		delete window.ps_gmap_callback;
	};

	document.body.appendChild(script);
};

/**
 * TODO: docblock
 */
PsLocation.prototype.show_map = function( lat, lng, name ) {
	peepso.lightbox([{ content: '<div class="ps-js-mapct" style="width:700px;height:400px;display:inline-block" />' }], {
		simple: true,
		afterchange: $.proxy(function( lightbox ) {
			this.load_library(function() {
				var mapct = lightbox.$container.find( '.ps-js-mapct' );
				var location = new google.maps.LatLng( lat, lng );
				var map = new google.maps.Map( mapct[0], {
					center: location,
					zoom: 14
				});

				var marker = new google.maps.Marker({
					position: location,
					map: map
				});
			});
		}, this )
	});
};

/**
 * Adds a new PsLocation object to a postbox instance.
 * @param {array} addons An array of addons to plug into the postbox.
 */
ps_observer.add_filter('peepso_postbox_addons', function(addons) {
	addons.push(new PsLocation);
	return addons;
}, 10, 1);

//
return PsLocation;

});
// EOF
