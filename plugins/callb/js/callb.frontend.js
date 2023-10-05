/**
 * callb.frontend.js
 * Module callbFrontend
 */

/*global $, callbFrontend */

var callbFrontend = (function () { "use strict";
	//---------------- BEGIN MODULE SCOPE VARIABLES ---------------
	var
		$callbackUrl, $callbSettings, $localeSettings,
		onIdinhtmlClick, removeCallbForm, checkPrivacyCheckbox, onFormSubmit, initModule;
	//----------------- END MODULE SCOPE VARIABLES ----------------

	//--------------------- BEGIN DOM METHODS ---------------------
	removeCallbForm = function () {
		$('.call-b-bg, .call-b-form').remove();
	};

	checkPrivacyCheckbox = function () {
		if ($callbSettings.privacy_status === 'on' && $callbSettings.privacy_checkbox_status === 'on') {        	
			if ($('#callb-privacy-agreed').is(':checked')) {
				$('#call-b-submit').prop('disabled', false);
			} else {
				$('#call-b-submit').prop('disabled', true);
			}
		}
	};
	//--------------------- END DOM METHODS -----------------------

	//------------------- BEGIN EVENT HANDLERS --------------------
	onIdinhtmlClick = function (event) {
		event.preventDefault();

		removeCallbForm();

		var bg = $('<div/>');
		var form = $('<form />');
		var formTop = $(document).scrollTop() + $(window).height()/2 - $callbSettings.style_form_height/2;
		var callbPrivacyAgreedBlock = '';
		var callbPrivacyAgreedCheckboxChecked = '';
		var callbPrivacyAgreedCheckboxBlock = '';

		if ($callbSettings.privacy_status === 'on') {
			if ($callbSettings.privacy_checkbox_checked === 'checked') { 
				callbPrivacyAgreedCheckboxChecked = 'checked="checked"';
			}

			if ($callbSettings.privacy_checkbox_status === 'on') { 
				callbPrivacyAgreedCheckboxBlock = '<input type="hidden" value="0" name="callb-privacy-agreed" /><input type="checkbox" value="1" name="callb-privacy-agreed" id="callb-privacy-agreed" ' + callbPrivacyAgreedCheckboxChecked + ' /> ';
			}

			callbPrivacyAgreedBlock = '<div class="call-b-input callb-privacy-agreed-wrapper"><label for="callb-privacy-agreed">' +
			callbPrivacyAgreedCheckboxBlock + '<span>' + $callbSettings.privacy_text + '</span> <a href="' + $callbSettings.privacy_link_url + '" target="_blank">' + $callbSettings.privacy_link_text + '</a>' +
			'</label></div>';
		}

		bg.addClass('call-b-bg').css('height', ($(document).height())+'px');
		form.addClass('call-b-form').css({
			'background': '#' + $callbSettings.style_form_background,
			'height': $callbSettings.style_form_height + 'px',
			'width': $callbSettings.style_form_width + 'px',
			'top' : formTop+'px'
		}).prepend(
			'<div class="call-b-header" style="background: #' + $callbSettings.style_header_background + '; color: #' + $callbSettings.style_header_text_color + ';">' + $callbSettings.text_header_title + '<span id="call-b-close-x">x</span></div>' +
			'<div class="call-b-input"><input type="text" name="callb-name" placeholder="' + $callbSettings.text_name_placeholder + '" value="" /></div>' +
			'<div class="call-b-input"><input type="text" name="callb-phone" placeholder="' + $callbSettings.text_phone_placeholder + '" value="" /></div>' +
			'<div class="call-b-input"><textarea name="comment" placeholder="' + $callbSettings.text_comment_placeholder + '"></textarea></div>' +
			callbPrivacyAgreedBlock +
			'<div class="call-b-input"><input id="call-b-submit" type="submit" value="' + $callbSettings.text_submit_button + '" style="background: #' + $callbSettings.style_submit_background + '; color: #' + $callbSettings.style_submit_text_color + '; height: ' + $callbSettings.style_submit_height + 'px; width: ' + $callbSettings.style_submit_width + 'px" /></div>'
		);

		$('body').prepend(form).prepend(bg);

		checkPrivacyCheckbox();

		$('.call-b-form input[name="callb-name"]').focus();

		if ($callbSettings.phone_masked_input.length > 0 ) {
			$('.call-b-form input[name="callb-phone"]').mask($callbSettings.phone_masked_input);
		}

		if ($callbSettings.comment_status !== 'on') {
			$('textarea[name="comment"]').parent('.call-b-input').hide();
		}
	};

	onFormSubmit = function (event) {
		event.preventDefault();

		var n = $('.call-b-input').find('input[name="callb-name"]').val();
		var p = $('.call-b-input').find('input[name="callb-phone"]').val();
		var c = $('.call-b-input').find('textarea[name="comment"]').val();
		var err = $('<div/>');
		var currentUrl = window.location.href;

		$('.call-b-error').remove();
		$('.call-b-input').find('input[name="callb-name"], input[name="callb-phone"]').removeClass('call-b-inp-err');

		if ( n.length > 0 && p.length > 0 ) {
			$.post($callbackUrl, { "name": n, "phone": p, "comment": c, "url": currentUrl }, function (response) {
				$('.call-b-form').css('height', '290px');

				if (response.data.status === true) {
					$('.call-b-input').remove();
					$('.call-b-form').append(
						'<p class="call-b-ok" style="color: #' + $callbSettings.style_thanks_text_color + ';">' + $callbSettings.text_thanks_message + ' ' + response.data.name + ',</p>' +
						'<p class="call-b-ok" style="color: #' + $callbSettings.style_thanks_text_color + ';">' + $callbSettings.text_more_thanks_message + '</p>' +
						'<div class="call-b-input"><input id="call-b-close" type="button" value=\"' + $localeSettings.text_close + '\" style="background: #' + $callbSettings.style_close_ok_background + '; height: ' + $callbSettings.style_submit_height + 'px; width: ' + $callbSettings.style_submit_width + 'px;" /></div>'
					);
				} else {
					$('.call-b-input').remove();
					$('.call-b-form').append(
						'<p class="call-b-ok margins">' + $localeSettings.error_sendmail + '</p>' +
						'<div class="call-b-input"><input class="call-b-close-error" id="call-b-close" type="button" value=\"' + $localeSettings.text_close + '\" style="background: #' + $callbSettings.style_close_error_background + '; height: ' + $callbSettings.style_submit_height + 'px; width: ' + $callbSettings.style_submit_width + 'px;" /></div>'
					);
				}
			}, "json");
		} else {
			if ( !(n.length > 0) ) {
				$('.call-b-input').find('input[name="callb-name"]').focus();
			} else if ( !(p.length > 0) ) {
				$('.call-b-input').find('input[name="callb-phone"]').focus();
			}
			if ( !(n.length > 0) ) {
				$('.call-b-input').find('input[name="callb-name"]').addClass('call-b-inp-err');
			}
			if ( !(p.length > 0) ) {
				$('.call-b-input').find('input[name="callb-phone"]').addClass('call-b-inp-err');
			}
			err.addClass('call-b-error').text($localeSettings.error_name_phone);
			$('.call-b-form').append( err );
		}
	};
	//------------------- END EVENT HANDLERS ----------------------

	//------------------- BEGIN PUBLIC METHODS --------------------
	initModule = function (callbackUrl, pluginSettings, localeSettings) {		
		$callbackUrl    = callbackUrl;
		$callbSettings  = pluginSettings;
		$localeSettings = localeSettings;

		$(document).on('click', $callbSettings.id_in_html, onIdinhtmlClick);

		$(document).on('click', '.call-b-bg, #call-b-close-x, #call-b-close', removeCallbForm);

		$(document).keyup(function(event) {
			if (event.keyCode == 27) { // close callb form when esc key is pressed
				removeCallbForm();
			}
		});

		$(document).on('submit', '.call-b-form', onFormSubmit);

		$(document).on('change', '#callb-privacy-agreed', checkPrivacyCheckbox);
	};

	return {
		initModule: initModule
	};
	//------------------- END PUBLIC METHODS ----------------------
}());