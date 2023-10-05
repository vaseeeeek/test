$(function(){
	//Размер и положение окна с пояснением
	var tooltip_init = function (target, tooltip) {
   		tooltip.css('max-width', 475);

	  	if ((tooltip.outerWidth() * 1.5) > $(window).width()) {
	   		tooltip.css('max-width', $(window).width() / 2);
	  	}
	  
	  	var pos_left = target.offset().left + (target.outerWidth() / 2) - tooltip.outerWidth() + 25;
	  	var pos_top = target.offset().top + target.outerHeight() + 10;
	 
	  	if (pos_left < 0) {
	        pos_left = target.offset().left + (target.outerWidth() / 2) - 25;
	        tooltip.find('.fiwex-tooltip-tail').css('left',15);
	    } else {
	        tooltip.find('.fiwex-tooltip-tail').css('left', tooltip.width() - 35);
	    }
	  
	    tooltip.css({
	        'left': pos_left,
	        'top': pos_top
	    });
    };
  
    //Кнопка редактировать
    $('a.edit_feat').each(function () {
        $(this).click(function (e) {
            $('div.fiwex-tooltip-content').hide();
            if ($(this).parent().find('.loading').length == 0) {
                var id = $(this).parent().parent().data('feat_id');
                var feat_name = $(this).parent().parent().find('.td_feat_name').text();
	            var target = $(this);

                $(this).after('<i class="icon16 loading" style="margin-left: 20px;"></i>');
                $.post('?plugin=fiwex&module=getexplanations', {feature_id: id, table: 'feature'}, function (response) {
                    $('#fiwex-feat-editing-dialog').waDialog({
	                    'title': 'Редактировать пояснение',
	                    'buttons': '<input type="submit" class="button green" value="Сохранить">или<a href="javascript:void(0);" class="cancel">Отмена</a>',
	 
	                    'width':'400px',
	                    'height': '300px',
	 
	                    onLoad: function () {
	                        $(this).find('.feat-name').text(feat_name);
	                        $(this).find('.explanations-edit').val(response.data.content);
	                        $('tr[data-feat_id="'+id+'"]').find('.loading').remove();
	                    },
	 
	                    onSubmit:function (d) {
	                        var reg_str = /[А-Я,а-я,A-Z,a-z,0-9]+/;
	                        var text = d.find('.explanations-edit').val();
	                        d.find('.dialog-buttons').find('input[type="submit"]').before('<i class="icon16 loading"></i>');

	                        if ((reg_str.test(text)) || (text == '')) {
	                            $.post('?plugin=fiwex&module=savexplanations',{text: text, table:'feature', id: id},function (response) {
	                                if (response.data.state) {
		                                target.parent().parent().data('explanation',text);
		                                d.trigger('close');
		                            } else {
		                                alert('Не удалось сохранить данные');
		                                d.trigger('close');
		                            }
	                            },'json')
	                        } else {
	                            alert('Некорректный ввод данных!');
	                            d.find('.dialog-buttons').find('.loading').remove();
	                        }
	                        return false;
	                    }
	                });
                },'json');
            }
            e.stopPropagation();
        });
    });

    //Клик на иконке подсказки
    $('td span.fiwex-popup-hint').click(function (e) {
        if ($(this).parent().find('.loading').length == 0) {
            $(this).parent().append('<i class="icon16 loading"></i>');
            var tooltip = $('div.fiwex-tooltip-content');
            var target = $(this);
            var title = target.parent().find('.td_feat_name').text();
            var id = parseInt($(this).parent().parent().data('feat_id'));
            var content = '';

            tooltip.appendTo('body');
            if (!target.parent().parent().data('explanation')) {
	            $.post('?plugin=fiwex&module=getexplanations', {'table': 'feature', 'feature_id': id}, function (response) {
	                if (response.data.content) {
	                    content = response.data.content;
	                } else {
	                    content = "<h3 style='color: red;'>Для данного пункта нет описания</h3>";
	                }

	                target.parent().parent().data('explanation', content);
	                target.parent().find('.loading').remove();
	                tooltip.find('div.fiwex-tooltip-title').empty().append(title);
                    tooltip.find('div.fiwex-tooltip-body').empty().append(content);
	                tooltip_init(target, tooltip);
	                tooltip.show();
	            },'json');
	        } else {
	            content = target.parent().parent().data('explanation');
	            tooltip.find('div.fiwex-tooltip-title').empty().append(title);
                tooltip.find('div.fiwex-tooltip-body').empty().append(content);
	            tooltip_init(target, tooltip);
	            tooltip.show();
	            target.parent().find('.loading').remove();
	        }
        }

        //Резайзинг
	    $(window).off('resize.tooltip').on('resize.tooltip', function () {
	        tooltip_init(target, tooltip);
	    });

        e.stopPropagation();
    });
  
    //Клик на закрыть облако подсказки
    $('div.fiwex-tooltip-close').click(function () {
        $(this).parent().hide();
    });
  
    //Закрытие облака пояснения нажатием ESC
    $(document).off('keydown.tooltip_close').on('keydown.tooltip_close',function (e) {
        if (e.which == 27) {
	        $('div.fiwex-tooltip-content').hide();
	    }
	    e.stopPropagation();
    });

    //Клик на характеристике
    $('#feature_list').find('tr').find('a.feat_name').click(function (e) {
        if ($(this).parent().children('ul').length == 0) {
            if ($(this).parent().find('.loading').length == 0) {
                var id = parseInt($(this).parent().parent().data('feat_id'));
                var str='';
	            var target = $(this);

                $(this).before('<i class="icon16 loading"></i>');
                $.post('?plugin=fiwex&module=getfeaturevalues', {feature_id: id}, function (response) {
                    str += '<ul class="menu-v with-icons">';

                    for (var i = 0; i<response.data.data.length; i++) {
                        str += '<li data-feat_val_id="' + response.data.data[i]['id'] + '"><a href="javascript: void(0);" style="display: inline-block; color: black;" class="feat-val">' +
                            response.data.data[i]['value'] + '</a>' +
	                    '<span class="fiwex-popup-hint">?</span><a class="edit_feat_val" href="javascript: void(0);" style="display: inline-block; margin-left: 15px;"><i class="icon16 edit"></i></a></li>';
                    }

                    str += '</ul>';
	                target.parent().append(str);
	
	                //Клик редактирования на значении характеристики
                    $('a.edit_feat_val').click(function (e) {
	                    $('div.fiwex-tooltip-content').hide();
	                    if ($(this).parent().find('.loading').length == 0) {
	                        var id = parseInt($(this).parent().data('feat_val_id'));
                            var feat_values_name = $(this).parent().find('a.feat-val').text();
	                        var feat_name = $(this).parent().parent().parent().find('.td_feat_name').text();
		                    var target = $(this);

	                        $(this).after('<i class="icon16 loading" style="margin-left: 15px;"></i>');
	                        $.post('?plugin=fiwex&module=getexplanations', {'feature_id': id, 'table': 'feature_values'}, function (response) {
	                            $('#fiwex-feat-values-editing-dialog').waDialog({
	                                'title': 'Редактировать пояснение',
	                                'buttons': '<input type="submit" class="button green" value="Сохранить">или<a href="javascript:void(0);" class="cancel">Отмена</a>',
	   
	                                'width': '400px',
	                                'height': '300px',
	   
	                                onLoad: function () {
	                                    $(this).find('.feat-name').text(feat_name);
	                                    $(this).find('.feat-values-name').text(feat_values_name);
	                                    $(this).find('.explanations-edit').val(response.data.content);
		                                $('li[data-feat_val_id="' + id + '"]').find('.loading').remove();
	                                },
	   
	                                onSubmit: function (d) {
	                                    var reg_str = /[А-Я,а-я,A-Z,a-z,0-9]+/;
	                                    var text = d.find('.explanations-edit').val();

	                                    d.find('.dialog-buttons').find('input[type="submit"]').before('<i class="icon16 loading"></i>');

		                                if((reg_str.test(text)) || text == '') {
		                                    $.post('?plugin=fiwex&module=savexplanations', {'text': text, 'table':'feature_values', id: id}, function (response) {
		                                        if (response.data.state) {
			                                        target.parent().data('explanation', text);
			                                        d.trigger('close');
			                                    } else {
			                                        alert('Не удалось сохранить данные!');
			                                        d.trigger('close');
			                                    }
		                                    },'json');
		                                } else {
		                                    alert('Некорректный ввод данных!');
		                                }

                                        return false;
	                                }
	                            });
	                        },'json');
	                    }
                        e.stopPropagation();
                    });

	                $('tr[data-feat_id="' + id + '"]').find('.loading').remove();
                },'json');
            }
        } else {
            $(this).parent().children('ul').remove();
            $('div.fiwex-tooltip-content').hide();
        }
  
        //Клик на иконке пояснения в значении характеристики
	    $(document).off('click.feat_val_popup').on('click.feat_val_popup', 'li span.fiwex-popup-hint', function (e) {
	        if ($(this).parent().parent().find('.loading').length == 0) {
	            $(this).after('<i class="icon16 loading" style="margin-left: 20px;"></i>');
	            var tooltip = $('div.fiwex-tooltip-content');
	            var target = $(this);
	            var title = target.parent().find('a.feat-val').text();
	            var id = parseInt($(this).parent().data('feat_val_id'));
	            var content = '';

	            tooltip.appendTo('body');
	            if (!target.parent().data('explanation')) {
	                $.post('?plugin=fiwex&module=getexplanations', {'table': 'feature_values', 'feature_id': id}, function (response) {
	                    if (response.data.content) {
		                    content = response.data.content;
		                } else {
		                    content = '<h3 style="color: red;">Для данного пункта нет описания</h3>';
		                }
		
		                target.parent().data('explanation', content);
		                tooltip.find('div.fiwex-tooltip-title').empty().append(title);
		                tooltip.find('div.fiwex-tooltip-body').empty().append(content);
		                tooltip_init(target, tooltip);
		                tooltip.show();
		                target.parent().find('.loading').remove();
	                },'json');
	            } else {
	                content = target.parent().data('explanation');
	                tooltip.find('div.fiwex-tooltip-title').empty().append(title);
	                tooltip.find('div.fiwex-tooltip-body').empty().append(content);
	                tooltip_init(target, tooltip);
	                tooltip.show();
		            target.parent().find('.loading').remove();
	            }
	        }

            //Резайзинг
	        $(window).off('resize.tooltip').on('resize.tooltip', function () {
	            tooltip_init(target, tooltip);
	        });
	        e.stopPropagation();
	    });
        e.stopPropagation();
    });
 
    //Вкладка настроек контента активна
    $('ul.tabs').find('li.wm-explanations-content').click(function () {
        $(this).parent().find('li').removeClass('selected');
        $(this).addClass('selected');
        $('#wm-explanations-content').show();
        $('#wm-explanations-view').hide();
        $('#wm-explanations-view').find('style[data-presence="1"]').remove();
        $('#wm-explanations-view').find('#wm-preview-panel-content').hide();
        $('div.fiwex-tooltip-content').hide();
    });

    //сохраняем в data первоначальные стили
    $('a.wm-save-explanations-view').data('old-content',$('#wm-tooltip-view').val());
  
    //Вкладка внешнего вида пояснений активна
    $('ul.tabs').find('li.wm-explanations-view').click(function () {
        $(this).parent().find('li').removeClass('selected');
        $(this).addClass('selected');
        $('#wm-explanations-content').hide();
        $('#wm-explanations-view').show();
        $('div.fiwex-tooltip-content').hide();
    });
 
    //Вкл/Выкл плагин
    $('#wm-fiwex-enable-flag').click(function () {
        var st = parseInt($(this).data('enable'));
        var target = $(this);

        if ($(this).parent().find('.loading').length == 0) {
            $(this).parent().append('<i class="icon16 loading"></i>');
            $.post("?plugin=fiwex&module=trigger", {state: st}, function (response) {
                if (response.data.state == 0) {
	                target.addClass('red').removeClass('green').text('Плагин отключен');
	                target.data('enable','0');
	            } else if (response.data.state == 1) {
	                target.addClass('green').removeClass('red').text('Плагин включен');
	                target.data('enable','1');
	            }
	            target.parent().find('.loading').remove();
            },'json');
        }
  
    });
 
    //Клик на сохранить настройки стилей
    $('.wm-save-explanations-view').click(function () {
        var ct = $('#wm-tooltip-view').val();
        var target = $(this);

        if (target.parent().find('.loading').length == 0) {
            target.parent().append('<i class="icon16 loading"></i>');
            $.post('?plugin=fiwex&module=savestyle', {content: ct}, function (response) {
                if (response.data.state) {
                    target.data('old-content',ct);
	                $('#wm-fiwex-tooltip-style').empty().append(ct);
                    target.addClass('green').removeClass('yellow');
                    target.parent().find('.loading').remove();
                }
            },'json');
        }
    });
 
    //Клик на препросмотре
    $('#wm-preview-panel').click(function () {
        var content = $('#wm-tooltip-view').val();
        var tag = '<style type="text/css" rel="stylesheet" data-presence=1>\n';
        tag += content;
        tag += '\n</style>';

        if ($('style[data-presence="1"]').length == 0) {
            $(this).after(tag);
	        $('#wm-preview-panel-content').show();
        } else {
            $('#wm-explanations-view').find('style[data-presence="1"]').remove();
	        $('#wm-preview-panel-content').hide();
	        $('div.fiwex-tooltip-content').hide();
        }
    });
 
    $('#wm-preview-panel-content').find('.fiwex-popup-hint').click(function () {
        var target = $(this);
        var tooltip = $('div.fiwex-tooltip-content');
        tooltip.appendTo('body');
   
        tooltip.find('div.fiwex-tooltip-close').show();
        tooltip.find('div.fiwex-tooltip-title').empty().append('Заголовок пояснения');
        tooltip.find('div.fiwex-tooltip-body').empty().append('Данный блок содержит ваше пояснение');
        tooltip_init(target, tooltip);
        tooltip.show();

        //Ресайзинг
	    $(window).off('resize.tooltip').on('resize.tooltip', function () {
	        tooltip_init(target, tooltip);
	    });
    });
 
    $('#wm-tooltip-view').focus(function () {
        var old_content = $('a.wm-save-explanations-view').data('old-content');
        var target = $(this);
        target.off('keyup.edit_content').on('keyup.edit_content', function () {
            var new_content = target.val();
            if (old_content != new_content) {
                $('a.wm-save-explanations-view').addClass('yellow').removeClass('green');
	            $('#wm-preview-panel-content').hide();
	            $('div.fiwex-tooltip-content').hide();
	            $('#wm-explanations-view').find('style[data-presence="1"]').remove();
            } else if (old_content == new_content) {
                $('a.wm-save-explanations-view').addClass('green').removeClass('yellow');
	            $('#wm-preview-panel-content').hide();
	            $('div.fiwex-tooltip-content').hide();
	            $('#wm-explanations-view').find('style[data-presence="1"]').remove();
            }
        });
    });
 
    //Сброс до начального значения
    $('#wm-fiwex-reset').click(function () {
        var path = $(this).data('path');
        if ($(this).parent().find('.loading').length == 0) {
            $(this).parent().append('<i class="icon16 loading"></i>');
            target = $(this);

            $.post("?plugin=fiwex&module=savestyle", {clear: 1}, function(response) {
                if (response.data.style) {
                    $('#wm-tooltip-view').val(response.data.style);
	                $('#wm-fiwex-tooltip-style').empty().append(response.data.style);
                    $('.wm-save-explanations-view').data('old-content', response.data.style);
	                $(".wm-save-explanations-view").addClass('green').removeClass('yellow');
	                $('#wm-preview-panel-content').hide();
	                $('div.fiwex-tooltip-content').hide();
	                $('#wm-explanations-view').find('style[data-presence="1"]').remove();
                    target.parent().find('.loading').remove();
                }
            },'json');
        }
    });
});
