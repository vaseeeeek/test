(function($){

	$.shop.bdgPluginBackend = {
		options: [],
		color: '',
		init: function(options){
			var self = this;
			this.options = options;
			$('#product-list').bind('append_product_list', function(e,products){
				self.getBadges(e,products)
			});
			
			var bb = $('[data-action="set-badge"]:first').closest('.block');
			if ( bb.size()>0 ){
				bb.after($('#shop-bdg-plugin-block'));
				bb.hide();
			}
			
			$('#badges').on('click','a',function(){
				var that = $(this),
					li = that.closest('li'),
					id = li.data('id'),
					products = $.product_list.getSelectedProducts(true),
					ids = [];
				self.color = li.data('color');
				$('#badges').next().text('');
				if (!products.count)
					$('#badges').next().text('Отметьте товары');
				else{
					that.find('.icon16').removeAttr('style').addClass('loading');
					self.saveBadges(that,id,products,0);
				}
				return false;
			});
			
			$('#product-badges').on('click','a',function(){
				var that = $(this),
					s = $('.bdg-status',that),
					status = s.is('.status-green') ? 1 : 0,
					p = {
						product_id: $('#product-badges').data('product-id'),
						id: that.closest('li').data('id'),
						single: 1
					};
					if ( !s.is('.status-gray') ){
						s.removeClass('status-green').removeClass('status-red').addClass('status-gray');
						$.post('?plugin=bdg&module=save',p,function(r){
							if ( r.status == 'ok' ){
								if ( status )
									s.removeClass('status-gray').addClass('status-red');
								else
									s.removeClass('status-gray').addClass('status-green');
							}
						},'json');
					}
				return false;
			})
		},
		getBadges: function(e,products){
			var self = this,
				ids = [];
			for (var i=0; i<products.length;i++)
				ids[i] = products[i].id;
			
			$.post('?plugin=bdg&module=badges',{ ids:ids },function(response){
				self.setBadgesToList(response.data.badges,ids);
			},'json');
		},
		setBadgesToList: function(badges,ids){
			var self = this;
			if ( badges ) {
				var colunm = 0;
				$('#product-list tr.header th').each(function(i){
					if ( $(this).html().indexOf('sort=name')+1 )
						colunm = i;
				})
				$('#product-list tr.product').each(function(){
					var product_id = $(this).data('product-id');
					if ( $.inArray(product_id,ids) != -1 )
						$(this).find('.badge-clr').remove();
					if ( product_id > 0 )
						for (var i=0;i<badges.length;i++)
							if ( badges[i].product_id == product_id )
								self.setBadgesToItem($(this),badges[i].badge_ids,colunm);
				})
			}
		},
		setBadgesToItem: function(item,badge_ids,c){
			var a = item.find('td:eq('+c+') a'),
				h = item.find('td:eq('+c+') a .shortener');
			for ( var i=0;i<badge_ids.length;i++ ) {
				var color = $('#badge-item-'+badge_ids[i]).data('color'),
					s = $('<span />').addClass('badge-clr').css('background',color);
				if ( h.size()>0 && 0)
					h.before(s);
				else
					a.after(s);
			}
		},
		saveBadges: function(that,id,products,offset){
			var self = this;
			$.post('?plugin=bdg&module=save',{ id:id,data:products.serialized,offset:offset },function(){
				if ( offset+50 < products.count ){
					self.bar((offset+50)/products.count);
					self.saveBadges(that,id,products,offset+50);
				}else
					self.collectList(that);
			})
		},
		collectList: function(that){
			var self = this,
				ids = [];
			$('#product-list tr.product').each(function(i){
				ids[i] = $(this).data('product-id');
			})
			$.post('?plugin=bdg&module=badges',{ ids:ids },function(response){
				self.setBadgesToList(response.data.badges,ids);
				that.find('.icon16').removeClass('loading').css('background',self.color);
				self.bar(1);
			},'json');
		},
		bar: function(p){
			var b = $('#bar'),
				w = b.width();
			$('.progress',b).css('width',Math.ceil(w*p)).text(Math.ceil(100*p)+'%');
			if ( p == 1 )
				setTimeout(function(){
					$('.progress',b).css('width',0);
				},1000);
		}
	}

})(jQuery)