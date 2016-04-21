(function($) {

	// Check license.
	function checkLicense() {
		var statuses = $(".license_status_check");
		var plugins = {};

		if (!statuses.length) {
			return;
		}

		statuses.each(function() {
			var el = $(this);
			plugins[ el.attr("id") ] = el.data("plugin-name");
		});

		function periodicalCheckLicense() {
			$PeepSo.postJson("adminConfigLicense.check_license", { plugins: plugins }, function(json) {
				var valid, details, prop, icon;
				if (json.success) {
					valid   = json.data && json.data.valid || {};
					details = json.data && json.data.details || {};
					for ( prop in valid ) {

						if (+valid[prop]) {
							icon = '<i class="ace-icon fa fa-check bigger-110" style="color:green"></i>';
							$("#error_" + prop).hide();
						} else {
							icon = '<i class="ace-icon fa fa-times bigger-110" style="color:red"></i>';
							$("#error_" + prop).show();
						}

						if (details[prop]) {
							icon = icon
								+ '<p class="peepso_license_details" style="clear:both;font-size:9px">'
								+ details[prop]['diff']
								+ '</p>';
						}

						statuses.filter("#" + prop).html( icon );
					}
				}
			});
		}

		periodicalCheckLicense();
		setInterval(function() {
			periodicalCheckLicense();
		}, 1000 * 30 );
	}

	$(document).ready(function() {
		var $limit_comments = $("input[name='site_activity_limit_comments']");
		// Handle toggling of limit comments readonly state
		if ($limit_comments.size() > 0) {
			$limit_comments.on("change", function() {
				if ($(this).is(":checked")) {
					$("input[name='site_activity_comments_allowed']").removeAttr('readonly');
				} else {
					$("input[name='site_activity_comments_allowed']").attr('readonly', 'readonly');
				}
			}).trigger("change");
		}

		checkLicense();

		// Handle reset all emails button
		var $resetCheck = $('#reset-check');
		if ($resetCheck.length) {
			var $resetDo = $('#reset-do');
			$resetCheck.on('click', function() {
				this.checked ? $resetDo.removeAttr('disabled') : $resetDo.attr('disabled', 'disabled');
			});
			$resetDo.on('click', function(e) {
				e.preventDefault();
				e.stopPropagation();
				var location = window.location.href;
				location = location.replace('&reset=1', '');
				window.location = location + '&reset=1';
			});
		}

		// Highlight only active submenu
		var url = window.location.href;
		if ( url.indexOf('page=peepso_config') > -1 ) {
			var $root = $('#toplevel_page_peepso li.current ul.wp-submenu-wrap');
			var index = 0;
			if ( url.indexOf('tab=appearance') > -1 ) {
				index = 1;
			} else if ( url.indexOf('tab=email') > -1 ) {
				index = 2;
			} else if ( url.indexOf('tab=advanced') > -1 ) {
				index = 3;
			}
			$root.find('a').eq( index ).parent().siblings().find('a').css({
				color: 'rgba(240,245,250,.7)',
				fontWeight: 'normal'
			}).on('mouseenter', function() {
				$( this ).css({ color: '#00b9eb' });
			}).on('mouseleave', function() {
				$( this ).css({ color: 'rgba(240,245,250,.7)' });
			});
		}
	});

	var errors = {};
	var changed = {};

	function initCheck() {
		var $inputs = $('input[type=text].validate');
		$inputs.off('keyup.validate').on('keyup.validate', function() {
			var $el = $(this);
			checkValue( $el, $el.data() );
		}).trigger('keyup');
	}

	var checkValueTimer = false;
	function checkValue($el, data) {
		clearTimeout(checkValueTimer);
		checkValueTimer = setTimeout(function() {
			if (data.ruleType === 'int') {
				checkNumber($el, data);
			} else if (data.ruleType === 'email') {
				checkEmail($el, data);
			} else {
				checkString($el, data);
			}
		}, 300 );
	}

	function checkString($el, data) {
		var val = $el.val(),
			name = $el.attr('name');

		data.ruleMinLength = +data.ruleMinLength;
		data.ruleMaxLength = +data.ruleMaxLength;

		if ( data.ruleMinLength && val.length < data.ruleMinLength ) {
			showError($el, data);
			errors[ name ] = true;
		} else if ( data.ruleMaxLength && val.length > data.ruleMaxLength ) {
			showError($el, data);
			errors[ name ] = true;
		} else {
			hideError($el, data);
			errors[ name ] = false;
			delete errors[ name ];
		}
		toggleSubmitButton();
	}

	function checkNumber($el, data) {
		var val = +$el.val(),
			name = $el.attr('name');

		data.ruleMin = +data.ruleMin;
		data.ruleMax = +data.ruleMax;

		if ( data.ruleMin && val < data.ruleMin ) {
			showError($el, data);
			errors[ name ] = true;
		} else if ( data.ruleMax && val > data.ruleMax ) {
			showError($el, data);
			errors[ name ] = true;
		} else {
			hideError($el, data);
			errors[ name ] = false;
			delete errors[ name ];
		}
		toggleSubmitButton();
	}

	function checkEmail($el, data) {
		var val = $el.val();
		// http://data.iana.org/TLD/tlds-alpha-by-domain.txt
		// http://stackoverflow.com/questions/201323/using-a-regular-expression-to-validate-an-email-address
		var re = /^([*+!.&#$¦\'\\%\/0-9a-z^_`{}=?~:-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,15})$/i;
		if ( !re.test(val) ) {
			showError($el, data);
			errors[ name ] = true;
		} else {
			hideError($el, data);
			errors[ name ] = false;
			delete errors[ name ];
		}
		toggleSubmitButton();
	}

	function showError($el, data) {
		var $error;
		if ( !$el.hasClass('error') ) {
			if ( data.ruleMessage ) {
				$error = $el.next('.validate-error');
				if ( !$error.length ) {
					$error = $([
						'<div class="validate-error tooltip bottom">',
						'<div class="tooltip-arrow"></div>',
						'<div class="tooltip-inner">',
						data.ruleMessage,
						'</div></div>'
					].join(''));
					$error.insertAfter($el);
				}
			}
			$el.addClass('error');
		}
	}

	function hideError($el, data) {
		if ( $el.hasClass('error') ) {
			$el.removeClass('error');
		}
	}

	var toggleSubmitTimer = false;
	function toggleSubmitButton() {
		clearTimeout( toggleSubmitTimer );
		toggleSubmitTimer = setTimeout( _toggleSubmitButton, 300 );
	}

	function _toggleSubmitButton() {
		var error = false,
			prop;

		for ( prop in errors ) {
			if ( errors[ prop ] ) {
				error = true;
				break;
			}
		}

		var $submit = $('#peepso button[type=submit]');
		if ( error ) {
			$submit.attr('disabled', 'disabled');
		} else {
			$submit.removeAttr('disabled');
		}
	}

	var toggleEditWarningTimer = false;
	function toggleEditWarning() {
		clearTimeout( toggleEditWarningTimer );
		toggleEditWarningTimer = setTimeout( _toggleEditWarning, 300 );
	}

	function _toggleEditWarning() {
		$('#edit_warning').show();
	}

	// Form validation.
	$(function() {
		initCheck();
		$('#peepso button[type=reset]').on('click', function() {
			setTimeout( initCheck, 100 );
			setTimeout( function() {
				$('#edit_warning').hide();
			}, 1000 );
		});

		// Show notice if any of the form fields are changed.
		$('#peepso').find('input[type=text], textarea').on('keyup', function() {
			toggleEditWarning();
		});

		$('#peepso').find('input[type=checkbox], select').on('change', function() {
			toggleEditWarning();
		});
	});

})(jQuery);
