var ProductsetsUserBundlePlugin = (function ($) {

    ProductsetsUserBundlePlugin = function (options) {
        var that = this;

        /* DOM */
        that.$wrap = options.wrap;

        /* VARS */
        that.userbundle = options.userbundle || '';

        /* INIT */
        that.initData();
        that.initClass();
        that.bindEvents();
    };

    ProductsetsUserBundlePlugin.prototype.initClass = function () {
        var that = this;

        setTimeout(function () {
            /* Сортировка наборов */
            that.$wrap.find('.f-user-groups').sortable({
                distance: 5,
                opacity: 0.75,
                items: '.bundle',
                handle: '>.bundle__sort .sort',
                cursor: 'move',
                tolerance: 'pointer'
            });

            /* Сортировка обязательных товаров */
            that.$wrap.find('.bundle__alternative-items').sortable({
                distance: 5,
                opacity: 0.75,
                items: '>div >.bundle__item',
                handle: '.bundle__item-sort .sort',
                cursor: 'move',
                tolerance: 'pointer'
            });
        }, 500);

        that.initDiscounts();
        that.initFileupload();
    };

    ProductsetsUserBundlePlugin.prototype.initData = function () {
        var that = this;
        if (that.userbundle) {
            var discountType = that.$wrap.find(".userbundle-discount").val();
            /* Активный товар */
            if (that.userbundle['active'] !== undefined && !$.isEmptyObject(that.userbundle['active'])) {
                that.$wrap.find('.f-user-groups').prepend($.productsets.bundleJS.addActiveProduct(discountType, that.userbundle['active']['settings']));
            }
            /* Обязательные товары */
            if (that.userbundle['required'] !== undefined && !$.isEmptyObject(that.userbundle['required'])) {
                for (var i in that.userbundle['required']) {
                    var requiredP = tmplPs('tmpl-bundle-item', {
                        item: that.userbundle['required'][i],
                        discount_type: discountType
                    });
                    requiredP = $(requiredP);
                    requiredP.find('.bundle__alternative, .bundle__item-settings_delete-product, .bundle__item-settings_discount-required').remove();
                    that.$wrap.find('.bundle__alternative-items > div').append(requiredP);
                }
            }
            /* Группы */
            if (that.userbundle['groups'] !== undefined && !$.isEmptyObject(that.userbundle['groups'])) {
                $.each(that.userbundle['groups'], function (id, group) {
                    that.$wrap.find('.f-user-groups').append(tmplPs('tmpl-group', group));
                });
                that.initSortable();
            }
            /* Подгрузка типов */
            if (that.$wrap.find('.f-chosen.hidden').length) {
                $.post("?plugin=productsets&action=loadData", function (response) {
                    if (response.status == 'ok' && response.data) {
                        that.$wrap.find('.f-chosen.hidden').each(function () {
                            var select = $(this),
                                type = select.data('load');
                            if (typeof response.data[type] !== 'undefined') {
                                select.removeClass('hidden').append(response.data[type]).val(select.data('value'));
                                that.initChosen(select);
                            }
                        });
                    }
                }, 'json');
            }
        }
    };

    ProductsetsUserBundlePlugin.prototype.initDiscounts = function () {
        var that = this;

        /* Добавление цепочек скидок */
        if (that.userbundle) {
            var discountType = that.$wrap.find(".userbundle-discount").val();
            if (discountType == 'avail' && that.userbundle['settings']['chain']) {
                for (var i in that.userbundle['settings']['chain']['value']) {
                    that.$wrap.find('.bundle__avail-variant-inner').append(tmplPs('tmpl-discount-chain', {
                        value: that.userbundle['settings']['chain']['value'][i],
                        currency: that.userbundle['settings']['chain']['currency'][i],
                        i: i,
                        length: that.userbundle['settings']['chain']['value'].length
                    }));
                }
            }
        }
        if (!that.$wrap.find('.bundle__chain-link').length) {
            that.$wrap.find('.bundle__avail-variant-inner').append(tmplPs('tmpl-discount-chain', {first: 1}));
        }
    };

    ProductsetsUserBundlePlugin.prototype.bindEvents = function () {
        var that = this;

        /* Добавление группы */
        $(document).off('click', ".js-add-group").on('click', ".js-add-group", function () {
            that.$wrap.find('.f-user-groups').append(tmplPs('tmpl-group'));
            that.$wrap.find('.js-change-usergroup-display').change();
            that.$wrap.find('.js-change-usergroup-discount').change();
            that.initSortable();
            that.initFileupload();
        });

        /* Добавление категорий, списков, типов товаров */
        $(document).off('click', ".js-add-type").on('click', ".js-add-type", function () {
            var btn = $(this);
            var type = btn.data('type');
            var group = btn.closest('.usergroup__fieldset');
            var loadedBlock = group.find('.f-chosen[data-load="' + type + '"]').not('.hidden').first();
            var typesBlock = group.find('.usergroup__types');
            /* Если блок существует */
            if (loadedBlock.length) {
                var cloneBlock = loadedBlock.closest('.field').clone();
                var select = cloneBlock.find('select');
                select.val('');
                cloneBlock.find('.f-usergroup-type-number').val(10);
                cloneBlock.find('.chosen-container').remove();
                typesBlock.append(cloneBlock);
                that.initChosen(cloneBlock.find('.f-chosen'));
            }
            /* Если блока не существует и не происходит загрузка */
            else if (!group.find('.f-chosen.hidden[data-load="' + type + '"]').length) {
                var discountType = that.$wrap.find(".userbundle-discount").val() !== 'common' ? group.closest('.usergroup').find('.usergroup__discount').val() : 'common';
                typesBlock.append(tmplPs('tmpl-usergroup-type', {type: type, discount_type: discountType}));
                setTimeout(function () {
                    $.post("?plugin=productsets&action=loadData&type=" + type, function (response) {
                        if (response.status == 'ok' && response.data && typeof response.data[type] !== 'undefined') {
                            var select = group.find('.f-chosen.hidden[data-load="' + type + '"]').removeClass('hidden').append(response.data[type]);
                            that.initChosen(select);
                        }
                    }, 'json');
                }, 100);
            }
        });

        /* Изменение кол-ва товаров отображаемых  категории */
        $(document).off('click', '.f-usergroup-type-field').on('click', '.f-usergroup-type-field', function () {
            var btn = $(this);
            if (btn.val() !== '') {
                btn.closest('.usergroup__field').find('.f-usergroup-type-number').removeClass("hidden");
            } else {
                btn.closest('.usergroup__field').find('.f-usergroup-type-number').addClass("hidden");
            }
        });

        /* Изменение названия группы */
        $(document).off('click', '.usergroup__fieldset legend:not(.onedit)').on('click', '.usergroup__fieldset legend:not(.onedit)', function () {
            $(this).find('.usergroup__legend-form').css('display', 'flex').focus().prev('.usergroup__legend-name').hide();
        });

        /* Сохранение нового названия у группы */
        $(document).off('click', '.usergroup__legend-submit').on('click', '.usergroup__legend-submit', function () {
            var btn = $(this);
            var fieldset = btn.closest('.usergroup__fieldset');
            var val = fieldset.find(".usergroup__legend-input").val();
            fieldset.find(".usergroup__legend-name").text(val ? val : $__('Group name')).show();
            fieldset.find('.usergroup__legend-form').hide();
            return false;
        });

        /* Удаление элемента у группы */
        $(document).off('click', '.js-delete-usergroup-item').on('click', '.js-delete-usergroup-item', function () {
            $(this).closest(".usergroup__field").remove();
        });

        /* Появление настроек группы */
        $(document).off('click', '.js-show-group-settings').on('click', '.js-show-group-settings', function () {
            $(this).closest(".usergroup").find('.usergroup__settings').toggleClass('hidden');
        });

        /* Изменение скидок у группы */
        $(document).off('change', '.js-usergroup-discount-change').on('change', '.js-usergroup-discount-change', function () {
            var select = $(this),
                discount = select.val(),
                settings = select.closest('.usergroup__settings'),
                group = select.closest(".usergroup");

            if (discount == 'common') {
                settings.find('.usergroup__discount_common').show();
                group.find('.bundle__discount').hide();
            } else {
                settings.find('.usergroup__discount_common').hide();
                group.find('.bundle__discount').css('display', 'inline-block');
            }
        });

        /* Изменение общей системы скидок */
        that.$wrap.find('.js-change-usergroup-discount').change(function () {
            switch ($(this).val()) {
                case 'common':
                    that.$wrap.find('.bundle__total-discount').show().end().find('.usergroup__setting_each-discount, .bundle__avail-variant_toggle, .bundle__discount').hide();
                    break;
                case 'each':
                    that.$wrap.find('.bundle__total-discount, .bundle__avail-variant_toggle').hide();
                    that.$wrap.find(".usergroup").each(function () {
                        var group = $(this);
                        if (group.find('.usergroup__discount').val() == 'each') {
                            group.find('.bundle__discount').css('display', 'inline-block');
                        }
                    });
                    that.$wrap.find('.bundle__item-active .bundle__discount, .bundle__alternative .bundle__discount, .usergroup__setting_each-discount').show();
                    break;
                case 'avail':
                    that.$wrap.find('.bundle__total-discount, .bundle__avail-variant_toggle, .usergroup__setting_each-discount').hide();
                    that.$wrap.find('.bundle__avail-variant_toggle').show();
                    break;
            }
        });
        that.$wrap.find('.js-change-usergroup-discount').change();

        /* Изменение активного товара */
        that.$wrap.find('.js-change-user-active-product').change(function () {
            var checkbox = $(this);

            if (checkbox.prop('checked')) {
                /* Добавляем активный товар в набор */
                that.$wrap.find('.f-user-groups').prepend($.productsets.bundleJS.addActiveProduct(that.$wrap.find(".userbundle-discount").val()));
            } else {
                /* Удаляем активный товар из набора */
                that.$wrap.find('.bundle__item-active').remove();
            }
        });

        /* Изменение отображения в группах */
        $(document).off('change', '.js-change-usergroup-display').on('change', '.js-change-usergroup-display', function () {
            if ($(this).val() == 'popup') {
                that.$wrap.find('.f-usergroup-display-popup').show();
            } else {
                that.$wrap.find('.f-usergroup-display-popup').hide();
            }
        });
        that.$wrap.find('.js-change-usergroup-display').change();

        /* Удаление изображения у группы */
        $(document).off('click', '.js-reset-image').on('click', '.js-reset-image', function () {
            $(this).hide().closest('.usergroup__setting').find('.f-usergroup-image-src').val('').siblings('.usergroup__setting_image').hide();
        });

        /* Создание всплывающего окна для выбора обязательных товаров */
        $(document).off('click', ".js-add-required-products").on('click', ".js-add-required-products", function () {
            $.productsets.bundleJS.openProductsDialog(that.$wrap.find('.bundle__alternative'), function (bundle, div) {
                /* Для альтернативных товаров удаляем лишние настройки */
                div.find('.bundle__alternative, .bundle__item-settings_delete-product, .bundle__item-settings_discount-required').remove();
                bundle.find(".bundle__alternative-items").html(div);
            });
        });
    };

    ProductsetsUserBundlePlugin.prototype.initSortable = function () {
        var that = this;
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
            /* Сортировка категорий внутри набора */
            that.$wrap.find('.usergroup__types').sortable({
                distance: 5,
                opacity: 0.75,
                items: '.usergroup__field',
                handle: '.sort',
                cursor: 'move',
                tolerance: 'pointer'
            });
        }, 300);
    };

    ProductsetsUserBundlePlugin.prototype.initChosen = function (elem) {
        elem.on('chosen:ready', function () {
            $(this).closest('.usergroup__field').find('.f-temp-loading').remove();
        }).chosen({no_results_text: $__('Oops, nothing found!'), disable_search_threshold: 10, width: '400px'});
    };

    /* Загрузка изображений */
    ProductsetsUserBundlePlugin.prototype.initFileupload = function () {
        var that = this;

        $.each(that.$wrap.find('.fileupload'), function () {
            var field = $(this);
            var progressField = field.siblings(".progressfield-block");
            if (field.data('blueimpFileupload') === undefined) {
                field.fileupload({
                    autoUpload: true,
                    dataType: 'json',
                    url: "?plugin=productsets&module=backend&action=usergroupUpload",
                    progressall: function (e, data) {
                        var progress = parseInt(data.loaded / data.total * 100, 10);
                        progressField.find(".progressbar-inner").css('width', progress + '%');
                    },
                    formData: {

                    },
                    submit: function (e, data) {
                        progressField.removeClass("hidden").html("<div class=\"progressbar green small float-left\" style=\"width: 70%;\"><div class=\"progressbar-outer\"><div class=\"progressbar-inner\" style=\"width: 0;\"></div></div></div><i class=\"icon16 loading\" style=\"margin: 7px 0 0 5px;\"></i><br clear=\"left\" />");
                    },
                    done: function (e, data) {
                        var response = data._response.result;
                        if (response && response.status == 'ok') {
                            progressField.addClass("hidden");
                            if (response.data.filelink) {
                                var fieldBlock = data.fileInputClone;
                                var settingBlock = fieldBlock.closest(".usergroup__setting");
                                settingBlock.find('.usergroup__setting_image').attr("src", response.data.filelink).show();
                                fieldBlock.siblings('.f-usergroup-image-src').val(response.data.filelink);
                                settingBlock.find('.usergroup__setting_reset_image').hide();
                            }
                        } else {
                            progressField.html("<span class=\"red\">" + response.errors + "</span>");
                        }
                    },
                    fail: function (e, data) {
                        progressField.html("<span class=\"red\">" + $__("Upload failed") + "</span>");
                    }
                });
            }
        });
    };

    return ProductsetsUserBundlePlugin;

})(jQuery);