(function($) {
	$('#c-transfer-no').click(function(e) {
		$('#complex_shop_complex_transfer').prop('checked', false);
		$('#c-transfer-block').hide();
		
		$.get('?plugin=complex&action=disableTransferBlock');
		
		e.preventDefault();
	});
	
	$('#c-transfer-yes').click(function(e) {
		var dat = $(this);
		if(!dat.hasClass('loading')) {
			dat.addClass('loading');
			
			$.get('?plugin=complex&action=transferDialog', function(data) {
				dat.removeClass('loading');
				
				var dialog = $.wa.dialogCreate('complex-transfer', {
				content: data,
				buttons: $('<div/>').append($('<input type="submit" class="button green" value="' + $_('Transfer settings and prices') + '">').click(function() {
					var dot = $(this);
					var f = $('#complex-transfer-form');
					if(!dot.hasClass('disabled')) {
						dot.addClass('disabled');
						
						$.eventStream.prefix = 'complex';
						$.eventStream.init({
							module: 'transfer',
							action: 'default',
							data: f.serialize(),
							params: {
								ms: 100,
								count: 100,
								texts: [$_('Transfering of prices and settings...'), $_('End of transfering...')]
							},
							on: {
								Create: function() {
									$('#complex-transfer-info').hide();
									$('#progressbar-complex').show();

									$('#progressbar-complex span.status').html($_('Preparing for transfer...'));
								},
								Error: function() {
									$('.progressbar').hide();
								},
								Done: function(e, data) {
									$('#c-transfer-block').hide();
								}
							}
						});
					}
				})).append(' ' + $_('or') + ' ').append($('<a href="javascript: //">' + $_('cancel') + '</a>').click(function() { $.wa.dialogHide(); }))
				}).addClass('no-overflow width450px height200px');

				$.wa.waCenterDialog(dialog);
			});
		}
		
		e.preventDefault();
	});
})(jQuery);