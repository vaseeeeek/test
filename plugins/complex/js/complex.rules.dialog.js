(function($) {
	$(document).off('click', '.condition-mode a').on('click', '.condition-mode a', function(e) {
		var mode = $(this).data('mode');
		
		$(this).closest('.condition-mode').find('li.selected').removeClass('selected');
		$(this).closest('li').addClass('selected');
		$(this).closest('ul').find('input').val(mode);
		$(this).closest('.condition-group').attr('class', 'condition-group ' + mode);
		
		e.preventDefault();
	});
	
	$(document).off('click', '.add-condition a').on('click', '.add-condition a', function(e) {
		$.complexRules.addConditionCallback($(this));
		e.preventDefault();
	});
	
	$(document).off('click', '.condition .remove a').on('click', '.condition .remove a', function(e) {
		$.complexRules.removeConditionCallback($(this));
		e.preventDefault();
	});
})(jQuery);