var ProductsetsBundlePlugin = (function ($) {

    ProductsetsBundlePlugin = function (options) {
        var that = this;

        /* DOM */
        that.$wrap = options.wrap;

        /* VARS */
        that.loader = '<i class="icon16 loading"></i>';
        that.bundle = options.bundle || '';
        that.products = options.products || {};

        /* INIT */
        that.initClass();
        that.bindEvents();
    };

    ProductsetsBundlePlugin.prototype.initClass = function () {
        var that = this;

        /* Сортировка наборов */
        that.$wrap.find('.f-bundles').sortable({
            distance: 5,
            opacity: 0.75,
            items: '.bundle',
            handle: '>.bundle__sort .sort',
            cursor: 'move',
            tolerance: 'pointer'
        });

        that.initData();
    };

    ProductsetsBundlePlugin.prototype.initData = function () {
        var that = this;

        if (that.bundle !== '') {

            $.each(that.bundle, function (i, bundle) {
                /* Создаем набор */
                that.$wrap.find('.f-bundles').append(tmplPs('tmpl-bundle', bundle));
            });

            setTimeout(function () {
                /* Сортировка товаров внутри набора */
                that.$wrap.find('.bundle__items:not(.bundle__alternative-items)').sortable({
                    distance: 5,
                    opacity: 0.75,
                    items: '>div >.bundle__item',
                    handle: '.bundle__item-sort .sort',
                    cursor: 'move',
                    tolerance: 'pointer'
                });
                that.initDatepicker();
                that.reinitSortable();
            }, 300);
        }

    };

    ProductsetsBundlePlugin.prototype.bindEvents = function () {
        var that = this;

        /* Смена типа скидок у комплекта */
        $(document).off('change', ".js-change-bundle-discount").on('change', ".js-change-bundle-discount", function () {
            var select = $(this),
                bundle = select.closest(".bundle");

            switch (select.val()) {
                case 'common':
                    bundle.find('.bundle__total-discount').show().end().find('.bundle__discount, .bundle__avail-variant_toggle').hide();
                    break;
                case 'each':
                    bundle.find('.bundle__discount').show().end().find('.bundle__total-discount, .bundle__avail-variant_toggle').hide();
                    break;
                case 'avail':
                    bundle.find('.bundle__avail-variant_toggle').show().end().find('.bundle__total-discount, .bundle__discount').hide();
                    break;
            }
        });

        /* Изменение активного товара */
        $(document).off('change', ".bundle__avail-variant_toggle :radio").on('change', ".bundle__avail-variant_toggle :radio", function () {
            var elem = $(this);

            if (elem.val() == 'every') {
                elem.closest('.bundle__settings').find('.bundle__avail-variant-inner').addClass('bundle__avail-variant-inner-every');
            } else {
                elem.closest('.bundle__settings').find('.bundle__avail-variant-inner').removeClass('bundle__avail-variant-inner-every');
            }
        });

        /* Изменение активного товара */
        $(document).off('change', ".js-change-active-product").on('change', ".js-change-active-product", function () {
            var checkbox = $(this),
                bundle = checkbox.closest('.bundle');

            if (checkbox.prop('checked')) {
                /* Добавляем активный товар в набор */
                bundle.find('.bundle__wrap').prepend(that.addActiveProduct(bundle.find('select[name*="discount_type"]').val()));
            } else {
                /* Удаляем активный товар из набора */
                bundle.find('.bundle__item-active').remove();
            }
        });

        /* Скрытие/появления блока с выбором времени жизни комплекта */
        $(document).off('change', ".js-bundle-lifetime").on('change', ".js-bundle-lifetime", function () {
            var checkbox = $(this);
            if (checkbox.prop('checked')) {
                checkbox.closest('.bundle__settings-item').find('.bundle__settings-lifetime').slideDown();
            } else {
                checkbox.closest('.bundle__settings-item').find('.bundle__settings-lifetime').slideUp();
            }
        });

        /* Добавление цепочки в скидках по наличию */
        $(document).off('click', ".js-add-discount-item").on('click', ".js-add-discount-item", function () {
            var parent = $(this).closest('.bundle__avail-variant');
            $(this).remove();
            parent.find('.bundle__avail-variant-inner').append(tmplPs('tmpl-discount-chain'));
        });

        /* Добавление набора */
        $(document).off('click', ".js-add-bundle").on('click', ".js-add-bundle", function () {
            that.$wrap.find('.f-bundles').append(tmplPs('tmpl-bundle', {uid: Date.now()}));
            setTimeout(function () {
                /* Сортировка товаров внутри набора */
                that.$wrap.find('.bundle__items:not(.bundle__alternative-items)').sortable({
                    distance: 5,
                    opacity: 0.75,
                    items: '>div >.bundle__item',
                    handle: '.bundle__item-sort .sort',
                    cursor: 'move',
                    tolerance: 'pointer'
                });
                that.initDatepicker();
                $.productsets.commonJS.initRedactor();
            }, 300);
        });

        /* Удаление цепочки в скидках по наличию */
        $(document).off('click', ".js-delete-discount-item").on('click', ".js-delete-discount-item", function () {
            $(this).closest('.bundle__chain-link').remove();
        });

        /* Создание всплывающего окна для выбора товара */
        $(document).off('click', ".js-add-product").on('click', ".js-add-product", function () {
            that.openProductsDialog($(this).closest('.bundle'));
        });

        /* Создание всплывающего окна для выбора альтеративных товаров */
        $(document).off('click', ".js-add-alt-product").on('click', ".js-add-alt-product", function () {
            that.openProductsDialog($(this).closest('.bundle__alternative'));
        });

        /* Удаление комплектующего */
        $(document).off('click', ".js-delete-bundle-item").on('click', ".js-delete-bundle-item", function () {
            $(this).closest(".bundle__item").remove();
        });

        /* Удаление комплекта */
        $(document).off('click', ".js-bundle-delete").on('click', ".js-bundle-delete", function () {
            $(this).closest(".bundle").remove();
        });

        /* Изменение цепочки скидок. Выбор чекбокса "на каждую позицию" */
        $(document).off('change', ".js-change-bundle-chain-each").on('change', ".js-change-bundle-chain-each", function () {
            var elem = $(this);
            if (elem.prop('checked')) {
                elem.prev().val('1');
            } else {
                elem.prev().val('0');
            }
        });
        /* Выбор валют в цепочке скидок. Скрытие/появление поля для выбора чекбокса "на каждую позицию" */
        $(document).off('change', ".js-select-chain-currency").on('change', ".js-select-chain-currency", function () {
            var elem = $(this);
            if (elem.val() == '%') {
                elem.closest('.bundle__chain-link').find('.bundle__chain-link-each').hide();
            } else {
                elem.closest('.bundle__chain-link').find('.bundle__chain-link-each').show();
            }
        });

    };

    /* Добавление активного товара */
    ProductsetsBundlePlugin.prototype.addActiveProduct = function (discountType, settings) {
        var activeBundleItem = tmplPs('tmpl-bundle-item', {
            item: {
                id: 0,
                skuId: 0,
                name: $__('* * * Active product * * *'),
                price: '',
                settings: settings ? settings : '',
                _id: settings && settings._id ? settings._id : 0
            },
            is_active: 1,
            discount_type: discountType,
            obj: ''
        });

        return activeBundleItem;
    };

    /* Инициализация полей для выбора даты */
    ProductsetsBundlePlugin.prototype.initDatepicker = function () {
        $(".f-datepicker").each(function () {
            var btn = $(this);
            if (btn.data('Zebra_DatePicker') !== undefined) {
                return true;
            }

            /* Форма выбора даты для расписания */
            var params = {};
            if ($.productsets.locale == 'ru_RU') {
                params["months"] = ["Январь", "Февраль", "Март", "Апрель", "Май", "Июнь", "Июль", "Август", "Сентябрь", "Октябрь", "Ноябрь", "Декабрь"];
                params["days_abbr"] = ["Вск", "Пон", "Вт", "Ср", "Чтв", "Пят", "Суб"];
                params["show_select_today"] = "Сегодня";
                params["lang_clear_date"] = "Очистить";
            }
            params["inside"] = false;
            params["show_clear_date"] = false;
            params["onSelect"] = function (formatDate, origDate, obj) {
                var dates = origDate.split("-");
                var scheduleBlock = $(this).closest(".schedule-time");
                if (dates[2] !== undefined) {
                    scheduleBlock.find(".schedule-day").val(function () {
                        return dates[2];
                    });
                }
                if (dates[1] !== undefined) {
                    scheduleBlock.find(".schedule-month").val(function () {
                        return dates[1];
                    });
                }
                if (dates[0] !== undefined) {
                    scheduleBlock.find(".schedule-year").val(function () {
                        return dates[0];
                    });
                }
            };
            btn.Zebra_DatePicker(params);
            btn.closest(".schedule-time").find('.schedule-select').each(function () {
                var select = $(this);
                if (select.data('value') !== undefined) {
                    select.val(select.data('value'));
                }
            });
        });
    };

    ProductsetsBundlePlugin.prototype.reinitSortable = function () {
        var that = this;

        that.$wrap.find('.bundle__alternative-items').not('.ui-sortable').sortable({
            distance: 5,
            opacity: 0.75,
            items: '>div >.bundle__item',
            handle: '.bundle__item-sort .sort',
            cursor: 'move',
            tolerance: 'pointer'
        });
    };

    /* Изменяем товары в наборе */
    ProductsetsBundlePlugin.prototype.updateBundleItems = function (bundle, itemsObj, callback) {
        var that = this;

        var div = $("<div />");

        $.each(itemsObj, function (i, v) {
            /* Если выбранного товара нет, тогда добавляем его */
            var item = bundle.find((bundle.is('.bundle__alternative') ? ".bundle__alternative-items " : ".bundle__items:not(.bundle__alternative-items) ") + '>div>.bundle__item[data-sku-id="' + v.skuId + '"][data-type="' + v.type + '"]');
            if (!item.length) {
                div.append(tmplPs('tmpl-bundle-item', {
                    item: v,
                    discount_type: bundle.is('.usergroup') ? (bundle.closest('.f-tab-user_bundle').find(".userbundle-discount").val() == 'each' ? bundle.find('select[name*="discount_type"]').val() : 'common') : (bundle.is('.bundle__alternative') ? bundle.closest('.bundle').find('select[name*="discount_type"]').val() : bundle.find('select[name*="discount_type"]').val()),
                    obj: JSON.stringify(v),
                    is_alternative: (bundle.is('.bundle__alternative') || bundle.is('.usergroup'))
                }));
                /* Удаляем лишние артикулы/товары одного продукта */
                bundle.find((bundle.is('.bundle__alternative') ? ".bundle__alternative-items " : ".bundle__items:not(.bundle__alternative-items) ") + '>div>.bundle__item[data-id="' + v.id + '"][data-type="' + (v.type == 'product' ? 'sku' : 'product') + '"]').remove()
            } else {
                div.append(item);
            }
        });
        if (callback) {
            callback.call(that, bundle, div);
        } else {
            bundle.find((bundle.is('.bundle__alternative') ? ".bundle__alternative-items" : ".bundle__items:not(.bundle__alternative-items)")).html(div);
        }

        that.reinitSortable();
    };

    /* Создание всплывающего окна для выбора товара */
    ProductsetsBundlePlugin.prototype.openProductsDialog = function (bundle, callback) {
        var that = this;

        var dialogJS;
        new igaponovDialog({
            url: '?plugin=productsets&module=dialog&action=products',
            class: 's-dialog-products',
            onOpen: function ($wrapper) {
                $.getScript($.productsets.url + "/js/productsetsProdDialogBackend.js", function () {
                    dialogJS = new ProductsetsProdDialogBackend({
                        wrap: $wrapper,
                        items: $.makeArray(bundle.find((bundle.is('.bundle__alternative') ? ".bundle__alternative-items " : ".bundle__items:not(.bundle__alternative-items) ") + ">div>.bundle__item").map(function () {
                            return $(this).data('product');
                        }))
                    });
                    ResultBlock.init(dialogJS);
                });
            },
            onSubmit: function($form, dialog) {
                dialog.close();
            },
            onBlockClick: function (event) {
                event.stopPropagation();
                dialogJS.initJSEvents(event);
            },
            onClose: function () {
                that.updateBundleItems(bundle, dialogJS.getSelectedProducts(), callback);
            }
        });
    };

    return ProductsetsBundlePlugin;

})(jQuery);