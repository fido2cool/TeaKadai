(function( $, peepso, factory ) {

	factory( $, peepso );

})( jQuery || $, peepso, function( $, peepso ) {

function PsTags() {}

PsTags.prototype.init = function() {
	var _self = this;

	this.taggable_inputs = ps_observer.apply_filters("peepsotags_taggable_inputs",
		["#postbox-main textarea.ps-postbox-textarea"]
	);

	this.init_tags(this.taggable_inputs.join(","));

	// Separate comments, we need to add post ID to the request, to get comment participants
	this.init_tags_comments();
	$(document).on("peepso_tags_init_comments ps_activitystream_append ps_activitystream_loaded", function() {
		_self.init_tags_comments();
	});

	ps_observer.add_filter("postbox_req_edit", function(req, sel) {
		sel.ps_tagging("val", function(val) {
			req.post = val;
		});
		return (req);
	}, 10, 2);

	ps_observer.add_filter("comment_req", function(req, sel) {
		$(sel).ps_tagging("val", function(val) {
			req.content = val;
			req.post = val;
		});
		return (req);
	}, 10, 2);

	ps_observer.add_filter("comment_cancel", function(sel) {
		$(sel).ps_tagging("reset");
	}, 10, 2);

	ps_observer.add_filter("modalcomments.afterchange", function(lightbox) {
		if (lightbox && lightbox.$attachment) {
			lightbox.$attachment.find(".ps-comment-reply textarea").ps_tagging();
		}
	}, 10, 2);

	ps_observer.add_filter("caption_req", function(req, sel) {
		$(sel).ps_tagging("val", function(val) {
			req.description = val;
		});
		return (req);
	}, 10, 2);

	$("#peepso-wrap").on("comment.saved", function(e, post_id, sel, req) {
		$(sel).ps_tagging("reset");
		return;
	});

	$("#peepso-wrap").on("post_edit.shown , comment_edit.shown", function(e, post_id, html) {
		var textarea = html.find("textarea");
		_self.init_tags(textarea);
	});
};

PsTags.prototype.init_tags = function( selector ) {
	var focusFetch = false,
		focusAfter = false,
		taggable;

	// do when element get focus
	$(selector).one('focus.get_taggable', function() {
		focusFetch = true;
		peepso.getJson('tagsajax.get_taggable', {}, function(response) {
			if ( response.success ) {
				taggable = response.data.users;
			}
			if ( typeof focusAfter === 'function' ) {
				focusAfter( taggable || [] );
			}
			focusFetch = false;
		});
	});

	$(selector).ps_tagging({
		syntax: _.template( peepsotags.template ),
		parser: new RegExp( peepsotags.parser, 'gi' ),
		parser_groups: { id: 1, title: 2 },
		fetcher: function( query, callback ) {
			if ( taggable ) {
				callback( taggable, 'cache' );
				return;
			}
			if ( focusFetch ) {
				focusAfter = callback;
				return;
			}
			peepso.getJson('tagsajax.get_taggable', {}, function(response) {
				if ( response.success ) {
					taggable = response.data.users;
				}
				callback( taggable || [] );
			});
		}
	});
};

PsTags.prototype.init_tags_comments = function() {
	$('[data-type="stream-newcomment"] textarea[name="comment"]').each(function(index, elem) {
		var focusFetch = false,
			focusAfter = false,
			taggable;

		// do when element get focus
		$(elem).one('focus.get_taggable', function() {
			var req = { act_id: $(elem).data('act-id') };
			focusFetch = true;
			peepso.getJson('tagsajax.get_taggable', req, function(response) {
				if ( response.success ) {
					taggable = response.data.users;
				}
				if ( typeof focusAfter === 'function' ) {
					focusAfter( taggable || [] );
				}
				focusFetch = false;
			});
		});

		$(elem).ps_tagging({
			syntax: _.template( peepsotags.template ),
			parser: new RegExp( peepsotags.parser, 'gi' ),
			parser_groups: { id: 1, title: 2 },
			fetcher: function( query, callback ) {
				if ( taggable ) {
					callback( taggable, 'cache' );
					return;
				}
				if ( focusFetch ) {
					focusAfter = callback;
					return;
				}
				var req = { act_id: $(elem).data('act-id') };
				peepso.getJson('tagsajax.get_taggable', req, function(response) {
					if ( response.success ) {
						taggable = response.data.users;
					}
					callback( taggable || [] );
				});
			}
		});

		ps_observer.add_filter("comment_can_submit", function(obj) {
			var inst = $(obj.el).data("ps_tagging");
			obj.can_submit = !inst.dropdown_is_visible;
			return obj;
		}, 10, 1);

		$(elem).ps_autosize();
	});
};

/**
 * Force line-breaks on word-wrapped textarea value
 * http://stackoverflow.com/a/19743610/2526639
 // TODO: document the {sel} parameter
 * @param {object} The DOM element
 */
PsTags.prototype.apply_line_breaks = function (sel) {
	var oTextarea = sel;

	if (oTextarea.wrap) {
		oTextarea.setAttribute("wrap", "off");
	} else {
		oTextarea.setAttribute("wrap", "off");
/*		var newArea = oTextarea.cloneNode(true);
		newArea.value = oTextarea.value;
		oTextarea.parentNode.replaceChild(newArea, oTextarea);
		oTextarea = newArea; */
	}

	var strRawValue = oTextarea.value;
	oTextarea.value = "";
	var nEmptyWidth = oTextarea.scrollWidth;
	var nLastWrappingIndex = -1;

	// TODO: docblock
	function testBreak(strTest) {
		oTextarea.value = strTest;
		return oTextarea.scrollWidth > nEmptyWidth;
	}

	// TODO: docblock
    function findNextBreakLength(strSource, nLeft, nRight) {
		var nCurrent;
		if ("undefined" === typeof(nLeft)) {
			nLeft = 0;
			nRight = -1;
			nCurrent = 64;
		} else {
			if (-1 === nRight)
				nCurrent = nLeft * 2;
			else if (nRight - nLeft <= 1)
				return (Math.max(2, nRight));
			else
				nCurrent = nLeft + (nRight - nLeft) / 2;
		}
		var strTest = strSource.substr(0, nCurrent);
		var bLonger = testBreak(strTest);
		if (bLonger)
			nRight = nCurrent;
		else {
			if (nCurrent >= strSource.length)
				return (null);
			nLeft = nCurrent;
		}
		return (findNextBreakLength(strSource, nLeft, nRight));
	}

	var i = 0, j;
	var strNewValue = "";
	while (i < strRawValue.length) {
		var breakOffset = findNextBreakLength(strRawValue.substr(i));
		if (null === breakOffset) {
			strNewValue += strRawValue.substr(i);
			break;
		}
		nLastWrappingIndex = -1;
		var nLineLength = breakOffset - 1;
		for (j = nLineLength - 1; j >= 0; j--) {
			var curChar = strRawValue.charAt(i + j);
			if (" " === curChar || "-" === curChar || "+" === curChar) {
				nLineLength = j + 1;
				break;
			}
		}
		strNewValue += strRawValue.substr(i, nLineLength) + "\n";
		i += nLineLength;
	}

	oTextarea.value = strNewValue;
	oTextarea.setAttribute("wrap", "");
};

$(function() {
	var ps_tags = new PsTags();
	ps_tags.init();

	$(".ps-postbox-tab.interactions .ps-button-cancel").on("click", function() {
		$("#postbox-main textarea.ps-postbox-textarea").ps_tagging("reset");
	});

	$("#postbox-main .postbox-submit").on("click", function() {
		$("#postbox-main textarea.ps-postbox-textarea").ps_tagging("reset");
	});

	ps_observer.add_filter("#postbox-main-postbox_req", function(req) {
		$("#postbox-main textarea.ps-postbox-textarea").ps_tagging("val", function(val) {
			req.content = val;
		});
		return (req);
	}, 10, 1);
});

});
