var ProductsetsDisplayPlugin = (function ($) {

    ProductsetsDisplayPlugin = function (options) {
        var that = this;

        /* DOM */
        that.$wrap = options.wrap;
        that.$form = that.$wrap.closest('#productsets-form');

        /* VARS */
        that.products = options.products || '';

        /* INIT */
        that.initClass();
        that.bindEvents();
        that.initData();
    };

    ProductsetsDisplayPlugin.prototype.initClass = function () {
        var that = this;

        /* Поиск товаров */
        that.$wrap.find('.f-autocomplete').productsetsAutocomplete({
            url: '?plugin=productsets&action=autocomplete'
        });

        /* Витрины */
        that.initChosen(that.$wrap.find('.f-chosen-storefront'));
    };

    ProductsetsDisplayPlugin.prototype.initData = function () {
        var that = this;

        if (that.products) {
            that.$wrap.find('.f-products-block').append(tmplPs('tmpl-display-fill-products', {products: that.products}));
        }
    };

    ProductsetsDisplayPlugin.prototype.bindEvents = function () {
        var that = this;

        /* Подгрузка категорий, списков, товаров во вкладке "Отображение" */
        that.$form.on('productsets-tab-display-inited', function () {
            that.$form.find('.f-tab a[data-tab="display"]').addClass('inited');
            $.post("?plugin=productsets&action=loadData", function (response) {
                if (response.status == 'ok' && response.data) {
                    $.each(response.data, function (type, data) {
                        that.$wrap.find('.f-chosen[data-load="' + type + '"]').each(function () {
                            var select = $(this);
                            select.removeClass('hidden').html(data);
                            var value = select.data('value');
                            if (value !== undefined) {
                                value += '';
                                select.val(value.split(','));
                            }
                            that.initChosen(select);
                        });
                    });
                }
            }, 'json');
        });

        /* Выбор radio вариантов */
        that.$wrap.find(":radio").change(function () {
            that.OnRadioChange($(this));
        });

        /* Валидация поля "По запросу" */
        that.$wrap.find('.js-ondemand-value').on('input', function (e) {
            var elem = $(this);
            var value = elem.val();
            var helper = that.$wrap.find('.f-ondemand-change');
            value = value.replace(/[0-9\s]/, '');
            elem.val(value);

            if (value) {
                helper.show().find('code').html('{shopProductsetsPluginHelper::show(\''+value+'\')}');
            } else {
                helper.hide();
            }
        });
    };

    /* Выбор radio вариантов */
    ProductsetsDisplayPlugin.prototype.OnRadioChange = function (radio) {
        if (radio.prop('checked') && radio.data('block')) {
            radio.closest('.field').find('.radio-custom[data-block="' + radio.data('block') + '"]').show();
        } else {
            radio.closest('.field').find('.radio-custom').hide();
        }
    };

    ProductsetsDisplayPlugin.prototype.initChosen = function (elem) {
        elem.on('chosen:ready', function () {
            $(this).closest('.value').find('.f-temp-loading').remove();
        }).chosen({no_results_text: $__('Oops, nothing found!'), disable_search_threshold: 10, width: '95%'});
    };

    return ProductsetsDisplayPlugin;

})(jQuery);