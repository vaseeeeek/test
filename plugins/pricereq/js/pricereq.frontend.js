/**
 * pricereq.frontend.js
 * Module pricereqFrontend
 */

/*global $, pricereqFrontend */

var pricereqFrontend = (function ($) { "use strict";
	//---------------- BEGIN MODULE SCOPE VARIABLES ---------------
	var
		$pricereqUrl, $pricereqSettings, $localeSettings,
		onIdinhtmlClick, removePricereqForm, checkPrivacyCheckbox, onFormSubmit, initModule;
	//----------------- END MODULE SCOPE VARIABLES ----------------

	//--------------------- BEGIN DOM METHODS ---------------------
	removePricereqForm = function () {
		$('.price-req-bg, .price-req-form').remove();
	};

	checkPrivacyCheckbox = function () {
		if ($pricereqSettings.privacy_status === 'on' && $pricereqSettings.privacy_checkbox_status === 'on') {   	
			if ($('#price-req-privacy-agreed').is(':checked')) {
				$('#price-req-submit').prop('disabled', false);
			} else {
				$('#price-req-submit').prop('disabled', true);
			}
		}
	};
	//--------------------- END DOM METHODS -----------------------

	//------------------- BEGIN EVENT HANDLERS --------------------
	onIdinhtmlClick = function (event) {
		event.preventDefault();

		removePricereqForm();

		var bg = $('<div/>'),
			form = $('<form />'),
			formTop = $(document).scrollTop() + $(window).height()/2 - $pricereqSettings.style_form_height/2,
			productId = $(event.target).closest('form').find('input[name="product_id"]').val(),
			pricereqPrivacyAgreedBlock = '',
			pricereqPrivacyAgreedCheckboxChecked = '',
			pricereqPrivacyAgreedCheckboxBlock = '';

		if ($pricereqSettings.privacy_status === 'on') {
			if ($pricereqSettings.privacy_checkbox_checked === 'checked') { 
				pricereqPrivacyAgreedCheckboxChecked = 'checked="checked"';
			}

			if ($pricereqSettings.privacy_checkbox_status === 'on') { 
				pricereqPrivacyAgreedCheckboxBlock = '<input type="hidden" value="0" name="price-req-privacy-agreed" /><input type="checkbox" value="1" name="price-req-privacy-agreed" id="price-req-privacy-agreed" ' + pricereqPrivacyAgreedCheckboxChecked + ' /> ';
			}

			pricereqPrivacyAgreedBlock = '<div class="price-req-input price-req-privacy-agreed-wrapper"><label for="price-req-privacy-agreed">' +
			pricereqPrivacyAgreedCheckboxBlock + '<span>' + $pricereqSettings.privacy_text + '</span> <a href="' + $pricereqSettings.privacy_link_url + '" target="_blank">' + $pricereqSettings.privacy_link_text + '</a>' +
			'</label></div>';
		}

		bg.addClass('price-req-bg').css('height', ($(document).height())+'px');
		form.addClass('price-req-form').css({
			'background': '#' + $pricereqSettings.style_form_background,
			'height': $pricereqSettings.style_form_height + 'px',
			'width': $pricereqSettings.style_form_width + 'px',
			'top' : formTop+'px'
		}).prepend(
			'<div class="price-req-header" style="background: #' + $pricereqSettings.style_header_background + '; color: #' + $pricereqSettings.style_header_text_color + ';">' + $pricereqSettings.text_header_title + '<span id="price-req-close-x">x</span><input type="hidden" name="price-req-product-id" value="' + productId + '" /></div>' +
			'<div class="price-req-input"><input type="text" name="pricereq-name" placeholder="' + $pricereqSettings.text_name_placeholder + '" value="" /></div>' +
			'<div class="price-req-input"><input type="text" name="pricereq-phone" placeholder="' + $pricereqSettings.text_phone_placeholder + '" value="" /></div>' +
			'<div class="price-req-input"><input type="text" name="pricereq-email" placeholder="' + $pricereqSettings.text_email_placeholder + '" value="" /></div>' +
			'<div class="price-req-input"><textarea name="comment" placeholder="' + $pricereqSettings.text_comment_placeholder + '"></textarea></div>' +
			pricereqPrivacyAgreedBlock +
			'<div class="price-req-input"><input id="price-req-submit" type="submit" value="' + $pricereqSettings.text_submit_button + '" style="background: #' + $pricereqSettings.style_submit_background + '; color: #' + $pricereqSettings.style_submit_text_color + '; height: ' + $pricereqSettings.style_submit_height + 'px; width: ' + $pricereqSettings.style_submit_width + 'px" /></div>'
		);

		$('body').prepend(form).prepend(bg);

		checkPrivacyCheckbox();

		$('.price-req-form input[name="pricereq-name"]').focus();

		if ($pricereqSettings.phone_masked_input.length > 0) {
			$('.price-req-form input[name="pricereq-phone"]').mask($pricereqSettings.phone_masked_input);
		}

		if ($pricereqSettings.comment_status !== 'on') {
			$('textarea[name="comment"]').parent('.price-req-input').hide();
		}
	};

	onFormSubmit = function (event) {
		event.preventDefault();

		var n = $('.price-req-input').find('input[name="pricereq-name"]').val(),
			p = $('.price-req-input').find('input[name="pricereq-phone"]').val(),
			e = $('.price-req-input').find('input[name="pricereq-email"]').val(),
			c = $('.price-req-input').find('textarea[name="comment"]').val(),
			err = $('<div/>'),
			pId = $('.price-req-header').find('input[name="price-req-product-id"]').val();

		$('.price-req-error').remove();
		$('.price-req-input').find('input[name="pricereq-name"], input[name="pricereq-phone"]').removeClass('price-req-inp-err');

		if ( p.length > 0 || e.length > 0 ) {
			$.post($pricereqUrl, { "name": n, "phone": p, "email": e, "comment": c, "product_id": pId }, function (response) {
				$('.price-req-form').css('height', '290px');

				if (response.data.status === true) {
					$('.price-req-input').remove();
					$('.price-req-form').append(
						'<p class="price-req-ok" style="color: #' + $pricereqSettings.style_thanks_text_color + ';">' + $pricereqSettings.text_thanks_message + ' ' + response.data.name + ',</p>' +
						'<p class="price-req-ok" style="color: #' + $pricereqSettings.style_thanks_text_color + ';">' + $pricereqSettings.text_more_thanks_message + '</p>' +
						'<div class="price-req-input"><input id="price-req-close" type="button" value=\"' + $localeSettings.text_close + '\" style="background: #' + $pricereqSettings.style_close_ok_background + '; height: ' + $pricereqSettings.style_submit_height + 'px; width: ' + $pricereqSettings.style_submit_width + 'px;" /></div>'
					);
				} else {
					$('.price-req-input').remove();
					$('.price-req-form').append(
						'<p class="price-req-ok margins">' + $localeSettings.error_sendmail + '</p>' +
						'<div class="price-req-input"><input class="price-req-close-error" id="price-req-close" type="button" value=\"' + $localeSettings.text_close + '\" style="background: #' + $pricereqSettings.style_close_error_background + '; height: ' + $pricereqSettings.style_submit_height + 'px; width: ' + $pricereqSettings.style_submit_width + 'px;" /></div>'
					);
				}
			}, "json");
		} else {
			if ( !(p.length > 0) ) {
				$('.price-req-input').find('input[name="pricereq-phone"]').focus();
			} else if ( !(e.length > 0) ) {
				$('.price-req-input').find('input[name="pricereq-email"]').focus();
			}
			if ( !(p.length > 0) ) {
				$('.price-req-input').find('input[name="pricereq-phone"]').addClass('price-req-inp-err');
			}
			if ( !(e.length > 0) ) {
				$('.price-req-input').find('input[name="pricereq-email"]').addClass('price-req-inp-err');
			}
			err.addClass('price-req-error').text($localeSettings.error_name_phone);
			$('.price-req-form').append( err );
		}
	};
	//------------------- END EVENT HANDLERS ----------------------

	//------------------- BEGIN PUBLIC METHODS --------------------
	initModule = function (pricereqUrl, pluginSettings, localeSettings) {		
		$pricereqUrl      = pricereqUrl;
		$pricereqSettings = pluginSettings;
		$localeSettings   = localeSettings;

		$(document).on('click', $pricereqSettings.id_in_html, onIdinhtmlClick);

		$(document).on('click', '.price-req-bg, #price-req-close-x, #price-req-close', removePricereqForm);

		$(document).keyup(function(event) {
			if (event.keyCode == 27) { // close pricereq form when esc key is pressed
				removePricereqForm();
			}
		});

		$(document).on('submit', '.price-req-form', onFormSubmit);

		$(document).on('change', '#price-req-privacy-agreed', checkPrivacyCheckbox);
	};

	return {
		initModule: initModule
	};
	//------------------- END PUBLIC METHODS ----------------------
}(jQuery));