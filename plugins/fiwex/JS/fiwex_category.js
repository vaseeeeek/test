$(function () {
    var url = $('#shop_path_for_fiwex_dump').data('path');
    var expflag_url = $('#shop_path_for_fiwex_expflag').data('path');

    //Расставляем иконки подсказок
    $.post(expflag_url, {}, function (response) {
	    for (var i=0; i<response.data.feat_full_data.length; i++) {
	        if ($('span.fiwex-feat[data-feat_id="'+response.data.feat_full_data[i]+'"]').find('.fiwex-popup-hint').length == 0) {
	   		    $('span.fiwex-feat[data-feat_id="'+response.data.feat_full_data[i]+'"]')
                .append('<span class="fiwex-popup-hint" style="display: inline;" data-type="feat">?</span>');

	            $('span.fiwex-feat[data-feat_id="'+response.data.feat_full_data[i]+'"]').find('span.fiwex-popup-hint').click(function(e){
	                showTooltip($(this));
		            e.stopPropagation();
	            });
	        }
	    }
   
        $('span.fiwex-feat-val').each(function () {
	        if (($(this).data('feat_val_id') in response.data.feat_val_data) && ($(this).data('fiwex-parent_id') in response.data.feat_id)) {
		        if ($(this).find('.fiwex-popup-hint').length == 0) {
                    $(this).append('<span class="fiwex-popup-hint" style="display: inline;" data-type="feat_val">?</span>');
                    $(this).find('span.fiwex-popup-hint').click(function (e) {
                        showTooltip($(this));
                        e.stopPropagation();
                        e.preventDefault();
                    });
                }
	        }
	    });
    },'json');


    //клик на иконке подсказки
    function showTooltip(input_target) {
        var target = input_target;
	    var tooltip = $('div.fiwex-tooltip-content');
	    var id;
	    var table='';
	    var title='';
        var content = '';

	    tooltip.appendTo('body');

	    if (target.data('type') == 'feat') {
	        id = parseInt(target.parent().data('feat_id'));
	        table = 'feature';
	    } else if(target.data('type') == 'feat_val') {
	        id = parseInt(target.parent().data('feat_val_id'));
	        table = 'feature_values';
	    }

	    //Формирование размеров и позиции подсказки
	    var tooltip_init = function () {
	        tooltip.css('max-width', 475);
	        if ((tooltip.outerWidth()*1.5) > $(window).width()) {
	            tooltip.css('max-width', $(window).width()/2);
	        }
	  
	        var pos_left = target.offset().left + (target.outerWidth()/2)-tooltip.outerWidth()+25;
	        var pos_top = target.offset().top + target.outerHeight()+10;
	 
	        if (pos_left < 0) {
	            pos_left = target.offset().left + (target.outerWidth() / 2) - 25;
	            tooltip.find('.fiwex-tooltip-tail').css('left',15);
	        } else {
	            tooltip.find('.fiwex-tooltip-tail').css('left',tooltip.width()-35);
	        }

	        tooltip.find('.fiwex-tooltip-body').empty().append(content);
	        tooltip.find('.fiwex-tooltip-title').empty().append(title);
	        tooltip.css({
	            'left' : pos_left,
	            'top' : pos_top
	        });
	    };
	 
	    if (!target.data('explanation')) {
	        if (target.parent().find('.loading').length == 0) {
	            target.parent().append('<i class="icon16 loading" style="position: static;"></i>')
	        }

	        $.post(url, {feature_id: id, table: table}, function (response) {
	            target.data('explanation', response.data.explanation);
	            target.data('title', response.data.title);
	            content = response.data.explanation;
	            title = response.data.title;
	            tooltip_init();
	            tooltip.show();
	            target.parent().find('.loading').remove();
	        },'json');
	    } else {
	        content = target.data('explanation');
	        title = target.data('title');
	        tooltip_init();
	        tooltip.show();
	    }

	    //Ресайзинг
	    $(window).off('resize.tooltip').on('resize.tooltip',tooltip_init);
   
        //Закрытие по клику и нажатию на ESC
        $(document).off('mousedown.tooltip_close keydown.tooltip_close').on('mousedown.tooltip_close keydown.tooltip_close',function (e) {
            if (e.which == 27) {
	            tooltip.hide();
	        }

	        if ((e.pageX<tooltip.offset().left) || (e.pageX>(tooltip.offset().left+tooltip.width())) || (e.pageY>(tooltip.offset().top+tooltip.height())) || (e.pageY<tooltip.offset().top)) {
	            tooltip.hide();
	        }
        });
    }
 
    //Клик на иконке закрыть
	$('.fiwex-tooltip-close').click(function () {
        $(this).parent().hide();
    });
    
});