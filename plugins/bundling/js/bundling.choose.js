'use strict';

(function($) {
	$.bundlingSetAction = function(e) {
		$.bundlingChooser.action = $(this).data('action');
		$.bundlingChooser.actionText = $(this).data('action') == 'set-up' ? $_('Select bundles for this products.') : $_('Select for what products set up those as bundles.');
		$.bundlingChooser.actionDoText = $(this).data('action') == 'set-up' ? $_('Setup as bundles') : $_('Setup previously chosen products as bundles');
		$.bundlingDialog.trigger('close');
		$('#maincontent').prepend($('<div id="bundling-chooser-helper"/>').css({
			position: 'fixed',
			right: '10px',
			bottom: '10px',
			width: '250px',
			background: '#fff',
			padding: '10px',
			zIndex: 1,
			border: '2px solid #ffe000'
		}).html(`<h1 style="background: #ffed63; padding: 5px; margin: -10px -10px 10px -10px; font-size: 20px;">${$_('Product Bundles')}</h1>${$_('Waiting products')}: <b>${$.bundlingChooser.count}</b>.<br/><br/>${$.bundlingChooser.actionText}<div class="select-this" style="display: none; margin-top: 10px; background: #eee; padding: 5px;"></div><div style="margin-top: 10px;" align="right"><a class="cancel" href="#"><i class="icon16 no"></i>${$_('Cancel')}</a></div>`));
	
		$(document).off('change', '#s-content input[type="checkbox"]');
		
		if($('.s-select-all').is(':checked'))
			$('.s-select-all').trigger('click').prop('checked', false);
		else
			$('#s-content input[type="checkbox"]:checked').trigger('click').prop('checked', false);
			
		$(document).on('change', '#s-content input[type="checkbox"]', function() {
			if(Object.keys($.bundlingChooser).length) {
				let products = $.product_list.getSelectedProducts(true);
				let ch = $('#bundling-chooser-helper .select-this');
				ch.hide().html('');
				
				if(products.count) {
					ch.append(`${$_('Selected products')}: <b>${products.count}</b>`).append($('<ul class="menu-v with-icons"/>')
						.append(`<li><a class="set" href="#"><i class="icon16 bundling"></i>${$.bundlingChooser.actionDoText}</a></li>`)
					).show();
				}
			}
		});
		
		$(document).off('click', '#bundling-chooser-helper .cancel').on('click', '#bundling-chooser-helper .cancel', function(e) {
			$.bundlingChooser = {};
			$('#bundling-chooser-helper').remove();
		
			e.preventDefault();
		});
		
		$(document).off('click', '#bundling-chooser-helper .set').on('click', '#bundling-chooser-helper .set', function(e) {
			let second_products = $.product_list.getSelectedProducts(true);

			if(second_products.count == 0) {
				alert($_('No selected products. Maybe you have leave the page where you had selected them.'));
			} else {
				let chooser = $.bundlingChooser;
				$.bundlingChooser = {};
				$('#bundling-chooser-helper').remove();
				
				let first_products = '';
				if(chooser.hash)
					first_products = 'first_hash=' + chooser.hash;
				else
					first_products = 'first_products=' + chooser.product_ids.join(',');
				
				$('#bundling-dialog').waDialog({
					width: 900,
					height: 550,
					url: `?plugin=bundling&module=dialog&action=set&set=${chooser.action}&${first_products}&` + $.param(second_products.serialized),
					buttons: '<input id="bundling-submit" type="submit" value="' + $_('Save') + '" class="button green"/> ' + $_('or') + ' <a href="#" class="cancel">' + $_('close') + '</a>',
					disableButtonsOnSubmit: false,
					onSubmit: function(d) {
						$('#bundling-submit').addClass('loading').prop('disabled', true);
						$.post('?plugin=bundling&module=setupBundles', $(this).serialize(), function(r) {
							$('#bundling-submit').removeClass('loading');
							if(r.status == 'ok') {
								$('#bundling-submit').parent('div').find('.cancel').after('<div style="display: inline-block;"><i class="icon16 yes"></i></div>');
							} else {
								$('#bundling-submit').prop('disabled', false);
								alert(r.errors[0][0]);
							}
						}, 'json');
						
						return false;
					},
					onClose: function() {
						$(this).remove();
					}
				});
			}
			
			e.preventDefault();
		});
		
		e.preventDefault();
	}
})(jQuery);