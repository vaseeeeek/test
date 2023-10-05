$(document).ready(function () {   // Открытие скидки подписки
    $(".podarok").click(function(){
        $(".emailform").toggle();
        return false;
    });
}); 

function emailformShow() {
	//$.post(emailformGlobalFrontend + "emailformsubscribe/?action=formshow", function (res) { }, 'json');

	$.ajax({
		url: emailformGlobalFrontend + 'emailformsubscribe/?action=formshow',
		type: 'POST', 
		data: {'urlReferer':window.location.href},
		success: function(data1) {
			if (data1.data.result.show == 1) {
				alert_flag = false;

				$('#emailform_popup').show();
				if (typeof emailform_event_form_show == 'function') {
					emailform_event_form_show();
				}
			}
		}
	});
}

function isEmail(email) {
	var regex = /^(.)+\@((.)+\.)+(.{2,6})+$/;
	return regex.test(email);
}

$(function() {
	$('.emailform_popup').appendTo('body');
	
	if (window.emailformGlobalSelector !== undefined) {
		$(emailformGlobalSelector).on('click', function() {
			emailformShow();
		});
	}

	$('#emailform_popup .overlay, #emailform_popup .ef-slose').on('click', function() {
		$('#emailform_popup').hide();
	});

	$('.emailform .ef-name-value.ef-required').blur(function() {
		var name = $(this).val();
		if (!name) {
			$(this).addClass('ef-input-error');
		} else {
			$(this).removeClass('ef-input-error');
		}
	});

	$('.emailform .ef-email-value.ef-required').blur(function() {
		var email = $(this).val();
		if (!isEmail(email)) {
			$(this).addClass('ef-input-error');
		} else {
			$(this).removeClass('ef-input-error');
		}
	});

	$('.emailform .ef-phone-value.ef-required').blur(function() {
		var phone = $(this).val();
		if (!phone) {
			$(this).addClass('ef-input-error');
		} else {
			$(this).removeClass('ef-input-error');
		}
	});

	$('.emailform .ef-pdn-cbox.ef-required').on('click', function() {
		if (!$(this).is(':checked')) {
			$('.ef-pdn').addClass('ef-pdn-error');
			$(this).addClass('ef-input-error');
		} else {
			$('.ef-pdn').removeClass('ef-pdn-error');
			$(this).removeClass('ef-input-error');
		}
	});

	$('.emailform input[type="submit"]').on('click', function(event) {
		event.preventDefault();

		var send = true;
		var id = '#' + $(this).parents('.emailform').attr('id') + ' '; //current form
		//console.log(id);

	    if (typeof emailform_event_submit_click == 'function') {
	        emailform_event_submit_click();
	    }

		if ($(id+'.ef-pdn-cbox.ef-required').length) {
			var pdn = $(id+'.ef-pdn-cbox.ef-required');
			if (!$(pdn).is(':checked')) {
				send = false;
				$(id+'.ef-pdn').addClass('ef-pdn-error');
				$(pdn).addClass('ef-input-error');
			} else {
				$(id+'.ef-pdn').removeClass('ef-pdn-error');
				$(pdn).removeClass('ef-input-error');
			}
		}

		if ($(id+'.ef-name-value.ef-required').length) {
			var name = $(id+'.ef-name-value.ef-required').val();
			var nameEl = $(id+'.ef-name-value.ef-required');
			if (!name) {
				send = false;
				$(nameEl).addClass('ef-input-error');
			} else {
				$(nameEl).removeClass('ef-input-error');
			}
		}

		if ($(id+'.ef-email-value.ef-required').length) {
			var email = $(id+'.ef-email-value.ef-required').val();
			var emailEl = $(id+'.ef-email-value.ef-required');
			if (!isEmail(email)) {
				send = false;
				$(emailEl).addClass('ef-input-error');
			} else {
				$(emailEl).removeClass('ef-input-error');
			}
		}
		
		if ($(id+'.ef-phone-value.ef-required').length) {
			var phone = $(id+'.ef-phone-value.ef-required').val();
			var phoneEl = $(id+'.ef-phone-value.ef-required');
			if (!phone) {
				send = false;
				$(phoneEl).addClass('ef-input-error');
			} else {
				$(phoneEl).removeClass('ef-input-error');
			}
		}

		if (send == true) {

			var name = $(id+'.ef-name-value').val();
			var email = $(id+'.ef-email-value').val();
			var phone = $(id+'.ef-phone-value').val();

			var carts_url = $(id+'input[name="carts_url"]').val();
			var mailer_form_id = $(id+'input[name="mailer_form_id"]').val();
			var subscribe_url = $(id+'input[name="subscribe_url"]').val();

			$.ajax({
				url: emailformGlobalFrontend + "emailformsubscribe/?action=subscribe",
				type: "POST",
				data: {'name':name, 'email':email, 'phone':phone},
				beforeSend: function() {
					$(id+'.ef-plugin-template input[type="submit"]').attr('disabled', 'disabled');
				},
				success: function(data1) {
					if (typeof emailform_event_submit_success == 'function') {
						emailform_event_submit_success();
					}

					$(id+'.ef-error-div').remove();

					if (data1.data.is == 'success') {

						if (typeof emailform_event_subscribe_success == 'function') {
							emailform_event_subscribe_success();
						}

						if (email && isEmail(email) && mailer_form_id > 0 && subscribe_url) {
							$.post(subscribe_url, {
								'subscriber[name]' : name,
								'subscriber[email]' : email,
								'form_id' : mailer_form_id
							});
						}

						if (carts_url) {
							$.post(carts_url, {
								'customer' : {
									'name' : name,
									'phone' : phone, 
									'email' : email
								}
							});
						}

						$(id).addClass('ef-subscribe-success');
						$(id+'.ef-user-template').html(data1.data.template);
						$(id+'.ef-plugin-template').hide();

					} else if (data1.data.error == 'validation email error') {

						$(id+'.ef-email-value').addClass('ef-input-error');
						$("<div class='ef-error-div'>Неправильный адрес электронной почты.</div>").appendTo($(id+".ef-plugin-template"));
						$(id+'.ef-plugin-template input[type="submit"]').removeAttr('disabled', 'disabled');

					} else if (data1.data.error == 'email already added') {

						$(id+'.ef-email-value').addClass('ef-input-error');
						$("<div class='ef-error-div'>Адрес электронной почты уже добавлен на сайт.</div>").appendTo($(id+".ef-plugin-template"));
						$(id+'.ef-plugin-template input[type="submit"]').removeAttr('disabled', 'disabled');

					}
				}
			});	

		}
	});

});