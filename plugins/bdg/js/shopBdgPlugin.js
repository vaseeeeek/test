(function($){

$.shopBdgPlugin = {
	interval: 30,
	transparency: 0.7,
	init: function(options){
		if ( options != undefined ){
			this.interval = (options.interval != undefined) ? options.interval : 30;
			this.transparency = (options.transparency != undefined) ? options.transparency : 0.7;
		}
		if ( $('.badge_, .-b-').parent('.corner').size() > 0 ){
			$('.badge_, .-b-').unwrap();
		}
		this.position();
	},
	position: function(){
		var self = this;
		$('.badge_, .-b-').parent().each(function(){
			var i = 0;
			$('.badge_, .-b-',this).each(function(){
				if ( $(this).is('.-b-') )
					self.setColor($(this));
				$(this).css('top',i+'px');
				i += self.interval;
			})
		})
	},
	setColor: function(b){
		if ( b.size() ){
			var t = b.text().trim(),
				tm = '<span>'+t+'</span><i></i>',
				color = b.attr('c');
			if ( $('span',b).size() == 0 )
				b.html(tm);
			var s = $('span',b),
				i = s.next('i'),
				tr = this.transparency-1,
				color = $.color.parse(color).scale('rgb',0.9).add('a',tr).toString(),
				sc = $.color.parse(color).scale('rgb',0.9).add('a',tr).toString(),
				sci = $.color.parse(color).scale('rgb',0.7).add('a',tr).toString();

			s.css('background-image','linear-gradient(to bottom, '+color+' 0%, '+sc+' 100%)');
			//s.css('background-color',color);
			i.css('border-color',sci);
		}
	},
}

})(jQuery);