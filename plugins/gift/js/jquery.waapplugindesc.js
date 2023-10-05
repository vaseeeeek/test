(function($){

	$.fn.waapplugindesc = function(options){
		
		var settings = $.extend({
				show:'<i class="icon10 darr"></i>Показать описание плагина',
				hide:'<i class="icon10 darr"></i>Скрыть описание плагина'
			}, options),
			self = this;
		
		return this.each(function(){
			var desc = $('<div />').addClass('desc').html(self.html()).hide(),
				btn = $('<a />').addClass('inline-link desc-btn').html(settings.show);
			btn.click(function(){
				var self = $(this);
				desc.toggle();
				if ( desc.is(':visible') ){
					self.html(settings.hide);
					$('i',self).removeClass('darr').addClass('uarr');
				}else{
					self.html(settings.show);
					$('i',self).removeClass('uarr').addClass('darr');
				}
				return false;
			})
			self.html('').append(desc).append(btn);
		});
		
	}
})(jQuery)