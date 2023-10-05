var ProductsetsAppearancePlugin = (function ($) {

    ProductsetsAppearancePlugin = function (options) {
        var that = this;

        /* DOM */
        that.$wrap = options.wrap;
        that.$outerWrap = $('.productsets-plugin-wrap');
        that.$toolbar = options.toolbar;

        /* VARS */
        that.appearance = options.appearance;

        /* INIT */
        that.initClass();
        that.bindEvents();
        that.initAppearance();
    };

    ProductsetsAppearancePlugin.prototype.initClass = function () {
        var that = this;

        /* Создаем блоки управления внешним видом */
        that.$wrap.find('.f-appearance-element option:not([data-skip])').each(function () {
            var option = $(this);
            var blockClass = "f-" + option.val() + "-appearance";
            that.$toolbar.append("<div data-type=\"" + option.val() + "\" class=\"" + blockClass + "\"></div>");

            /* Инициализируем панель управления внешним видом */
            var params = {
                'border-radius:type': 'all'
            };
            if (option.data('hover') !== undefined) {
                params['showHover'] = true;
            }
            if (option.data('plugins') !== undefined) {
                params['plugins'] = option.data('plugins').split(',');
            }
            if (option.data('hover-plugins') !== undefined) {
                params['hoverPlugins'] = option.data('hover-plugins').split(',');
            }
            if (option.data('dependency') !== undefined) {
                var parts = option.data('dependency').split('|');
                var elements = parts[0];
                var plugin = parts[1];
                params['pluginDependency'] = { [plugin]: elements };
            }
            if (option.data('live') !== undefined) {
                params['liveBlock'] = that.$wrap.find('.f-appearance-live-block .' + option.data('live'));
                if (option.data('before') !== undefined) {
                    params['pseudoBefore'] = 1;
                }
                if (option.data('after') !== undefined) {
                    params['pseudoAfter'] = 1;
                }
            }
            that.$toolbar.find('.' + blockClass).dynamicAppearance(params);
        });
    };

    ProductsetsAppearancePlugin.prototype.bindEvents = function () {
        var that = this;

        /* Выбор элемента внешнего вида */
        that.$wrap.find('.f-appearance-element').change(function () {
            var select = $(this);
            var val = select.val();

            /* Появление настроек */
            var appearanceBlock = that.$toolbar.find(".f-" + val + "-appearance");
            appearanceBlock.show().siblings().hide();
            if (appearanceBlock.find('.f-dynamicappearance-toolbar select').length === 1) {
                appearanceBlock.find('.f-dynamicappearance-toolbar select').change();
            }

            /* Появление превью */
            var appearanceLiveBlock = that.$wrap.find(".f-appearance-live-block .f-appearance-element-" + val);
            appearanceLiveBlock.show().siblings().hide();
            if (!appearanceLiveBlock.length && !select.hasClass('f-element-children')) {
                that.$wrap.find('.f-appearance-element-main').show().siblings().hide();
            }

            /* Появление дочерних пунктов настроек */
            if (that.$wrap.find('.f-element-children[data-element="' + val + '"]').length) {
                that.$wrap.find('.f-element-children').hide();
                that.$wrap.find('.f-element-children[data-element="' + val + '"]').show().change();
            } else if (!select.is('.f-element-children')) {
                that.$wrap.find('.f-element-children').hide();
            }
        });
        that.$wrap.find('.f-appearance-element:not(.f-element-children)').change();

        /* Сброс всех стилей внешнего вида */
        that.$wrap.find('.f-clear-appearance').click(function () {
            that.$toolbar.find('.dynamicAppearance-block').dynamicAppearance('reset');
        });

        /* Окно настроек макета */
        that.$wrap.find('.js-layout-settings, .js-close-layout-popup').click(function () {
            that.$outerWrap.toggleClass('productsets-layout-open');
        });

        /* Закрытие окна с настройками макета */
        $(document).off("click", clickWatcher).on("click", clickWatcher);

        function clickWatcher(event) {
            const $layoutPopup = $('.s-appearance-tab.active .productsets-layout-popup');
            const is_target = $layoutPopup.length && ($(event.target).is($layoutPopup) || $.contains($layoutPopup[0], event.target));
            const is_open_link = $(event.target).is('.js-layout-settings');

            if (!is_target && !is_open_link && that.$outerWrap.hasClass('productsets-layout-open')) {
                that.$outerWrap.removeClass('productsets-layout-open');
            }
        }

        /* Отображение зависимых полей */
        that.$wrap.find('.js-block-with-dependency').change(function () {
            const elem = $(this);
            elem.closest('.productsets-layout-popup')
                .find('.field[data-dependency-value][data-name="' + elem.data('name') + '"]').hide()
                .filter('[data-dependency-value*="' + elem.val() + '"][data-name="' + elem.data('name') + '"]').show();
        });

    };

    /* Устанавливаем сохраненные стили */
    ProductsetsAppearancePlugin.prototype.initAppearance = function () {
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

    return ProductsetsAppearancePlugin;

})(jQuery);