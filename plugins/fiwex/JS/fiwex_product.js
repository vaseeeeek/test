((function ($) {
    var
        options = {
            'url': null,
            'expflag_url': null
        };

    //Расставляем иконки подсказок
    function setIconDescription() {
        var
            i,
            target_feat_items,
            target_feat_val_items,
            target_element,
            tmp_text,
            tmp_index;

        $.post(options.expflag_url, {'page': 'product'}, function(response){
            if (response.data.feat_full_data.length > 0) {
                target_feat_items = $('span.fiwex-feat');

                if (target_feat_items.length > 0) {
                    for (i in response.data.feat_full_data) {
                        target_element = $('span.fiwex-feat[data-feat_id="'+response.data.feat_full_data[i]+'"]');
                        if (target_element.find('.fiwex-popup-hint').length == 0) {
                            target_element.append('<span class="fiwex-popup-hint" style="display: inline; margin-right: 10px;" data-type="feat">?</span>');
                        }
                    }
                }
            }

            target_feat_val_items = $('span.fiwex-feat-val');
            if (target_feat_val_items.length > 0) {
                for (i in response.data.feat_id) {
                    target_element = $('span.fiwex-feat-val[data-fiwex-parent_id="' + i + '"]');
                    tmp_index = parseInt(i);
                    target_element.each(function () {
                        tmp_text = $(this).text();
                        tmp_text = $.trim(tmp_text);

                        //Если значение характеристики имеет пояснение, то добавляем иконку
                        if ($.inArray(tmp_text, response.data.features_values[tmp_index]) > -1) {
                            if ($(this).find('span.fiwex-popup-hint').length == 0) {
                                $(this).append('<span class="fiwex-popup-hint" style="display: inline;" data-type="feat_val">?</span>');
                            }
                        }
                    });
                }
            }

            setClickDescriptionAction();

        }, 'json');
    }

    //Обработка клика на иконке подсказки
    function setClickDescriptionAction() {
        $(document).find('span.fiwex-popup-hint').off('click').on('click', function (e) {
            showTooltip($(this));
            e.stopPropagation();
        });
    }

    //Вывод окна с подсказкой
    function showTooltip(input_target) {
        var
           target = input_target,
           tooltip = $('div.fiwex-tooltip-content'),
           id, //Идентификатор характеристики
           table,
           title, //Заголовок всплывающего окна
           content, //Содержимое всплывающего окна
           product_id,
           index;

        tooltip.appendTo('body');

        if (target.data('type') == 'feat') {
            id = parseInt(target.parent().data('feat_id'));
            table = 'feature';
            product_id = 0;
            index = 0;
        } else if (target.data('type') == 'feat_val') {
            table = 'feature_values_unknown_id';
            product_id = parseInt(target.parent().data('product_id'));
            id = parseInt(target.parent().data('fiwex-parent_id'));
            index = $('span.fiwex-feat-val[data-fiwex-parent_id="' + id + '"]').index(target.parent());
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

            $.post(options.url, {'feature_id': id, 'table': table, 'product_id': product_id, 'index': index}, function (response) {
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

    //Закрытие окна с подсказкой при нажатии на крестик
    function hideTooltip() {
        $(document).on('click', '.fiwex-tooltip-close', function (response) {
            $(this).parent().hide();
        });
    }

    window['fiwex_product_module'] = {
        init: function (input_options) {
            $.extend(options, input_options);
            setIconDescription();
            hideTooltip();
        }
    };
})(jQuery));
