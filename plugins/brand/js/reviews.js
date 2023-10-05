jQuery(function($) {
	window.brandReviews = {
		init: function() {
			var self = this;

			var $form = $('#brand-add-form, #brand-add-form').first();
			var $form_div = $('#brand-add-form-wrapper, #brand-add-form-wrapper').first();
			var $add_review_button = $('.js-brand-reviews-add-review, .js-brand-reviews-add-review');
			var $add_review_result_message = $(".js-brand-submit-result, .js-brand-submit-result");
			this.initRate();

			$add_review_button.on('click', function() {
				$form.trigger('reset');
				$('.js-brand-review-input').val(0);

				$('.js-brand-review-stars').each(function() {
					$(this).attr('data-rate', 0);

					$(this).find('i').each(function() {
						$(this).removeClass('star');
						$(this).addClass('star-empty');
					});
				});

				$add_review_result_message.empty();

				$add_review_button.hide();
				$form_div.show();

				self.initSubmit($form, $add_review_result_message);
			});

			$form.on('input', 'input, textarea', function () {
				var $field = $(this);
				$field.closest('.wa-value').find('.wa-error-msg').remove();
			});

			// $rate_input.rateWidget({
			// 	onUpdate: function() {}
			// });
		},


		initRate: function() {
			$.each($('.js-brand-review-stars'), function(e) {
				var $star = $(this);

				$star.rateWidget({
					onUpdate: function(rate) {
						$star
							.closest('.js-brand-review-wrapper')
							.find('.js-brand-review-input')
							.val(rate);
					},
					withClearAction: false
				});
			});
		},

		initSubmit: function($form, $add_review_result_message) {
			$form.on('submit', function (e) {
				e.preventDefault();

				var msg = '';
				var error = false;
				var form_data = $form.serializeArray();

				$.ajax({
					url: brand_reviews_params.add_review_url,
					data: form_data,
					dataType: "json",
					type: "post",
					success: function(response) {
						if(response.status === 'fail') {
							if(console) {
								console.log(response);
							}

							if (typeof(response.errors) !== 'undefined') {
								$.each(response.errors, function(i, val) {
									if (i === 'captcha' && val) {
										msg += val + '<br/>';
										$('.wa-captcha-refresh').click();
										return;
									} else {
										var $field = $form.find('[name="review['+i+']"]');
										$field.closest('.wa-value').append('<em class="wa-error-msg">'+val+'</em>');
									}
								});
							}

							$add_review_result_message.css('color', '#ef4814');
							error = true;
						} else {
							msg += response.data.msg;
							$add_review_result_message.css('color', 'green');
						}
					},
					complete: function(jqXHR, textStatus) {
						if(textStatus !== 'success') {
							error = true;
							msg = 'Что-то пошло не так ...';
							if(console) {
								console.log(jqXHR, textStatus);
							}
						}

						$add_review_result_message.html(msg);
						$add_review_result_message.show();
						setTimeout(function(){
							$add_review_result_message.empty();
							$add_review_result_message.hide();
							$add_review_result_message.css('border', '');
							if(!error) {
								location.reload(true);
							}
						}, 1500);
					}
				});

				return false;
			})
		},
	};


	window.brandReviews.init();
});