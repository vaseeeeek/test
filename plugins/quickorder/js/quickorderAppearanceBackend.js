var QuickorderPluginAppearance = (function ($) {

    QuickorderPluginAppearance = function (options) {
        var that = this;

        /* DOM */
        that.$wrap = options.wrap;
        that.$toolbar = options.toolbar;

        /* VARS */
        that.tab = options.tab;
        that.appearance = options.appearance;

        /* INIT */
        that.initClass();
        that.bindEvents();
        that.initAppearance();
    };

    QuickorderPluginAppearance.prototype.initClass = function () {
        var that = this;

        /* Создаем блоки управления внешним видом */
        that.$wrap.find('.f-appearance-element option').each(function () {
            var option = $(this);
            var blockClass = "f-" + option.val() + "-appearance";
            that.$toolbar.append("<div data-type=\"" + option.val() + "\" class=\"" + blockClass + "\"></div>");

            /* Инициализируем панель управления внешним видом */
            var params = {};
            if (option.data('hover') !== undefined) {
                params['showHover'] = true;
            }
            if (option.data('plugins') !== undefined) {
                params['plugins'] = option.data('plugins').split(',');
            }
            if (option.data('live') !== undefined) {
                params['liveBlock'] = that.$wrap.find('.f-appearance-live-block .' + option.data('live'));
            }
            that.$toolbar.find('.' + blockClass).dynamicAppearance(params);
        });
    };

    QuickorderPluginAppearance.prototype.bindEvents = function () {
        var that = this;

        /* Выбор элемента внешнего вида */
        that.$wrap.find('.f-appearance-element').change(function () {
            var appearanceBlock = that.$toolbar.find(".f-" + $(this).val() + "-appearance");
            appearanceBlock.show().siblings().hide();
            if (appearanceBlock.find('.f-dynamicappearance-toolbar select').length === 1) {
                appearanceBlock.find('.f-dynamicappearance-toolbar select').change();
            }
        });
        that.$wrap.find('.f-appearance-element').change();

        /* Сброс всех стилей внешнего вида */
        that.$wrap.find('.f-clear-appearance').click(function () {
            that.$toolbar.find('.dynamicAppearance-block').dynamicAppearance('reset');
        });

        /* Изменение макета контактных полей */
        that.$wrap.find('.f-appearance-layouts input').change(function () {
            that.$wrap.find('.quickorder-fields').removeAttr('data-quickorder-layout1').removeAttr('data-quickorder-layout2').attr('data-quickorder-layout' + $(this).val(), 1);
        });

        /* Выбор темы оформления */
        that.$wrap.find('.f-default-styles').change(function () {
            var appearance = $(':selected', this).data('appearance');
            if (appearance) {
                $.each(appearance, function (type, styles) {
                    var block = that.$toolbar.find('.f-' + type + '-appearance.dynamicAppearance-block');
                    if (block.length) {
                        block.dynamicAppearance('setAppearance', styles);
                    }
                })
            }
        });

    };

    /* Устанавливаем сохраненные стили */
    QuickorderPluginAppearance.prototype.initAppearance = function () {
        var that = this;
        if (that.appearance) {
            if (typeof that.appearance === 'string') {
                that.appearance = JSON.parse(that.appearance);
            }
            $.each(that.appearance, function (type, appearance) {
                var block = that.$toolbar.find('.f-' + type + '-appearance.dynamicAppearance-block');
                if (block.length) {
                    block.dynamicAppearance('setAppearance', appearance);
                }
            });
        }
    };

    return QuickorderPluginAppearance;

})(jQuery);