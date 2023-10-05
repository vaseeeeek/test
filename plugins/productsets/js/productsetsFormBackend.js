var ProductsetsPlugin = (function ($) {

    ProductsetsPlugin = function (options) {
        var that = this;

        /* DOM */
        that.$form = options.form;
        that.$tabContent = that.$form.find('.f-tab-content');
        that.$submitBtn = that.$form.find('#f-productsets-save');
        that.$outerWrap = $('.productsets-plugin-wrap');

        /* DYNAMIC VARS */
        that.storage = new $.store();
        that.hash = options.hash || location.href;
        that.lang = options.lang || 'en';
        that.params = options.setParams;

        /* VARS */
        that.loader = '<i class="icon16 loading"></i>';
        that.isLocked = 0;

        /* INIT */
        that.initClass();
        that.bindEvents();
        that.initSave();
    };

    ProductsetsPlugin.prototype.initClass = function () {
        var that = this;

        /* IButton switcher */
        that.$form.find('.switcher.not-inited').each(function () {
            $(this).iButton({
                labelOn: "", labelOff: "", className: 'mini', init: function (checkbox) {
                    checkbox.removeClass("not-inited");
                }
            }).change(function () {
                var iButtonBlock = $(this).closest('.ibutton-checkbox');
                var onLabelSelector = iButtonBlock.find('.switcher-on-label'),
                    offLabelSelector = iButtonBlock.find('.switcher-off-label');
                var additinalField = iButtonBlock.closest('.field').find('.onopen');
                if (!additinalField.length) {
                    additinalField = iButtonBlock.closest('.field').siblings('.onopen');
                }
                if (!this.checked) {
                    if (additinalField.length) {
                        if (!additinalField.hasClass('f-revert')) {
                            additinalField.hide();
                        } else {
                            additinalField.css('display', 'inline-block');
                        }
                    }
                    onLabelSelector.addClass('unselected');
                    offLabelSelector.removeClass('unselected');
                } else {
                    if (additinalField.length) {
                        if (!additinalField.hasClass('f-revert')) {
                            additinalField.css('display', 'inline-block');
                        } else {
                            additinalField.hide();
                        }
                    }
                    onLabelSelector.removeClass('unselected');
                    offLabelSelector.addClass('unselected');
                }
            });
        });

        if (Object.keys(that.params).length) {
            that.$form.find('.f-tab-general .f-set-parameters').html(tmpl('tmpl-general-params', { params: that.params }));
        }

        /* Redactor */
        that.initRedactor();
    };

    ProductsetsPlugin.prototype.initRedactor = function () {
        var that = this;

        $('.f-redactor:not(.inited)', that.$form).each(function () {
            initSingleRedactor($(this));
        });

        function initSingleRedactor($block) {
            $block.redactor({
                minHeight: $block.data('height') || 250,
                toolbarFixed: false,
                maxHeight: 250,
                lang: that.lang,
                buttons: ['html', 'format', 'bold', 'italic', 'underline', 'deleted', 'lists',
                    'image', 'video', 'table', 'link', 'alignment',
                    'horizontalrule', 'fontcolor', 'fontsize', 'fontfamily'],
                plugins: ['source', 'fontcolor', 'fontfamily', 'alignment', 'fontsize', 'table', 'video', 'imagemanager'],
                imageUpload: '?plugin=productsets&action=upload',
                imageUploadParam: 'image',
                multipleImageUpload: false,
                imageUploadFields: '[name="_csrf"]:first',
                focus: false,
                callbacks: {
                    init: function () {
                        this.$textarea.addClass('inited');
                    }
                }
            });
        }
    };

    ProductsetsPlugin.prototype.bindEvents = function () {
        var that = this;

        /* Переключение вкладок настроек */
        that.$form.find('.f-tab a').click(function () {
            var tab = $(this);
            tab.parent().addClass('selected').siblings().removeClass("selected");
            that.$tabContent.find('.f-tab-' + tab.data('tab')).show().siblings().hide();
            if (!tab.hasClass('inited')) {
                that.$form.trigger('productsets-tab-' + tab.data('tab') + '-inited');
            }
        });

        // Появление / исчезание блоков
        $(document).off('click', ".f-toggle-html").on('click', ".f-toggle-html", function () {
            var btn = $(this);
            btn.next().toggle();
            btn.data('hide') && btn.hide();
        });

        that.$form.find(".editable").click(function () {
            $(this).hide().next().show();
        });

        /* Переключение блоков на странице "Внешнего вида" */
        that.$form.find('.f-shared-settings-tabs a').click(function () {
            var a = $(this),
                tab = a.closest('.f-shared-settings-tabs').data('tab'),
                currentPage = a.data('page');

            a.parent().addClass('selected').siblings().removeClass('selected');
            that.$form.find('.f-' + tab + '-' + currentPage).show().addClass('active').siblings().hide().removeClass('active');
        });
        that.$form.on('productsets-tab-appearance-inited', function () {
            that.$form.find('.f-appearance-element:not(.f-element-children)').change();
        });

        /* Добавление дополнительных параметров */
        $(document).off('click', ".js-add-param").on('click', ".js-add-param", function () {
            $(this).closest('.value').find('.f-set-parameters').append(tmpl('tmpl-single-param', { name: '', value: '' }))
        });

        /* Удаление дополнительных параметров */
        $(document).off('click', ".js-remove-param").on('click', ".js-remove-param", function () {
            $(this).closest('.f-params-param').remove();
        });
    };

    /* Сохранение данных */
    ProductsetsPlugin.prototype.initSave = function () {
        var that = this,
            successTimeout = null;

        that.$submitBtn.on("click", function () {
            that.$form.trigger("submit");
            return false;
        });

        that.$form.on("submit", onSubmit);

        function onSubmit(event) {
            event.preventDefault();

            var formData = getData();
            request(formData);
        }

        function request(data) {
            removeLoading();
            addLoading();
            that.lock();
            errorText();
            successTimeout && clearTimeout(successTimeout);
            var href = "?plugin=productsets&action=bundleSave";
            $.post(href, data, function (response) {
                if (typeof response !== 'undefined' && response.status == 'fail' && response.errors) {
                    errorText(response.errors);
                } else {
                    successMessage(response);
                }
            }, "json").always(function () {
                that.unlock();
                removeLoading();
            });
        }

        function addLoading() {
            that.$submitBtn.after("<i class='icon16 loading'></i>");
        }

        function removeLoading() {
            that.$submitBtn.next("i").remove();
        }

        function successMessage(response) {

            /* Обновляем содержимое вкладок, чтобы последующие сохранения были с актуальными данными */
            if (response.data && response.data.id) {
                reinitTabs(response.data);
                $.wa.setHash('#/productsets/edit/' + response.data.id);
            }

            setTimeout(function () {
                that.$submitBtn.after("<i class='icon16 yes'></i>");
                successTimeout = setTimeout(function () {
                    removeLoading();
                }, 3000);
            }, 100);
        }

        function errorText(text) {
            text = text || '';
            that.$form.find('.errormsg').html(text);
        }

        /* Поля input, select, textarea */
        function getFields(block) {
            var fields = block.find('input, select, textarea');

            var fieldsToChange = fields.filter('[data-name]');
            fieldsToChange.each(function () {
                var elem = $(this);
                elem.attr('data-old-name', elem.attr('name')).attr('name', elem.attr('data-name'));
            });

            var data = fields.serializeObject();

            fieldsToChange.each(function () {
                var elem = $(this);
                elem.attr('name', elem.attr('data-old-name'));
            });

            return data;
        }

        function getSettings() {
            return that.$form.find("[name^=settings]").serializeObject();
        }

        function getBundleData() {
            var data = [];
            that.$form.find(".f-tab-bundle").find('.bundle').each(function () {
                var bundle = {};
                var block = $(this);
                /* Блокируем альтернативные товары для корректной выборки */
                block.find(".bundle__alternative").find('input, select, textarea').prop('disabled', true);

                /* Настройки комплекта */
                bundle['id'] = block.find("> .bundle__id").val();

                bundle['settings'] = getFields(block.find('.bundle__settings'));

                /* Товары, входящие в комплект */
                bundle['items'] = [];
                block.find(".bundle__items:not(.bundle__alternative-items) > div > .bundle__item").each(function () {
                    var item = $(this);
                    var itemData = getItemData(item);

                    /* Разблокируем альтернативные товары для корректной выборки */
                    item.find(".bundle__alternative").find('input, select, textarea').prop('disabled', false);
                    itemData['alternative'] = [];
                    item.find(".bundle__alternative .bundle__item").each(function () {
                        itemData['alternative'].push(getItemData($(this)));
                    });

                    bundle['items'].push(itemData);
                });

                /* Активный товар */
                var activeProduct = block.find('.bundle__item-active');
                if (activeProduct.length) {
                    bundle['active'] = getFields(activeProduct);
                }

                data.push(bundle);
            });

            return data;
        }

        function getItemData(item) {
            return {
                id: item.data('id'),
                _id: item.find(".bundle__item_id").length ? item.find(".bundle__item_id").val() : 0,
                sku_id: item.data('sku-id'),
                type: item.data('type'),
                item: getFields(item)
            };
        }

        function getUserBundleData() {
            var data = { groups: [] },
                tabContent = that.$form.find(".f-tab-user_bundle");

            data['id'] = tabContent.find(".bundle__id").val();

            /* Группы */
            tabContent.find('.usergroup').each(function () {
                var group = {};
                var block = $(this);

                group['id'] = block.find('.usergroup__id').val();
                /* Настройки группы */
                group['settings'] = getFields(block.find('.usergroup__settings'));
                /* Название группы и множественный выбор */
                group['settings']['name'] = block.find('.usergroup__legend-input').val();
                group['settings']['multiple'] = block.find('.usergroup__multiple-choice_checkbox').prop('checked') ? 1 : 0;

                /* Товары, входящие в комплект */
                group['items'] = [];
                block.find(".bundle__items:not(.bundle__alternative-items) > div > .bundle__item").each(function () {
                    group['items'].push(getItemData($(this)));
                });

                /* Категории и др, входящие в комплект */
                group['types'] = [];
                block.find(".usergroup__field").each(function () {
                    group['types'].push(getFields($(this)));
                });

                data['groups'].push(group);
            });

            /* Активный товар */
            var activeProduct = tabContent.find('.bundle__item-active');
            if (activeProduct.length) {
                data['active'] = getFields(activeProduct);
            }

            /* Обязательные товары */
            data['required'] = [];
            tabContent.find(".bundle__alternative-items .bundle__item").each(function () {
                data['required'].push(getItemData($(this)));
            });

            /* Общие настройки */
            data['settings'] = getFields(tabContent.find('.userbundle-settings'));

            return data;
        }

        /* Поля внешнего вида */
        function getAppearance(tab) {
            var appearance = {};

            that.$form.find('.f-appearance-' + tab + ' .dynamicAppearance-block').each(function () {
                var dynamicBlock = $(this);
                appearance[dynamicBlock.data('type')] = dynamicBlock.dynamicAppearance('getAppearance');
            });
            return appearance;
        }

        /* Настройки внешнего вида */
        function getAppearanceSettings(tab) {
            var appearance = {};
            that.$form.find('.f-appearance-' + tab + ' .dynamicAppearance-block').each(function () {
                var dynamicBlock = $(this);
                appearance[dynamicBlock.data('type')] = dynamicBlock.dynamicAppearance('getSettings');
            });
            return appearance;
        }

        function getData() {
            var general = getFields(that.$form.find(".f-tab-general")),
                bundle = getBundleData(),
                text = getFields(that.$form.find(".f-text-blocks")),
                userBundle = getUserBundleData(),
                display = getFields(that.$form.find(".f-tab-display")),
                other = getFields(that.$form.find(".f-tab-other")),
                appearance = {
                    'bundle': getAppearance('bundle'),
                    'userbundle': getAppearance('userbundle'),
                },
                appearanceSettings = {
                    'bundle': getAppearanceSettings('bundle'),
                    'userbundle': getAppearanceSettings('userbundle')
                },
                settings = getSettings();

            var data = [{ name: 'id', value: that.$form.find(".f-tab-general input[name='id']").val() }]
                .concat({
                    name: 'bundle_status',
                    value: that.$form.find(".f-tab-bundle").find('.f-tab-status').prop('checked') ? 1 : 0
                })
                .concat({
                    name: 'user_bundle_status',
                    value: that.$form.find(".f-tab-user_bundle").find('.f-tab-status').prop('checked') ? 1 : 0
                })
                .concat({ name: 'settings[general]', value: JSON.stringify(general) })
                .concat({ name: 'settings[bundle]', value: JSON.stringify(bundle) })
                .concat({ name: 'settings[user_bundle]', value: JSON.stringify(userBundle) })
                .concat({ name: 'settings[display]', value: JSON.stringify(display) })
                .concat({ name: 'settings[text]', value: JSON.stringify(text) })
                .concat({ name: 'settings[other]', value: JSON.stringify(other) })
                .concat({ name: 'settings[settings]', value: JSON.stringify(settings) })
                .concat({ name: 'settings[appearance][bundle]', value: JSON.stringify(appearance['bundle']) })
                .concat({ name: 'settings[appearance][userbundle]', value: JSON.stringify(appearance['userbundle']) })
                .concat({
                    name: 'settings[appearance_settings][bundle]',
                    value: JSON.stringify(appearanceSettings['bundle'])
                })
                .concat({
                    name: 'settings[appearance_settings][userbundle]',
                    value: JSON.stringify(appearanceSettings['userbundle'])
                })
                .concat({
                    name: 'settings[appearance][use_important]',
                    value: that.$form.find(".f-appearance-use-important").prop('checked') ? 1 : 0
                });

            return data;
        }

        function reinitTabs(response) {

            /* Вкладка "Наборы" */
            var bundleTab = that.$tabContent.find('.f-tab-bundle');
            bundleTab.find('.f-bundles').html('');
            $.productsets.bundleJS = new ProductsetsBundlePlugin({
                wrap: bundleTab,
                bundle: typeof response.bundle !== 'undefined' ? response.bundle : ''
            });
            that.initRedactor();
        }
    };

    /* Запретить сохранение данных */
    ProductsetsPlugin.prototype.lock = function () {
        var that = this;

        that.isLocked = 1;
        that.$submitBtn.prop('disabled', true);
    };

    /* Разрегить сохранение данных */
    ProductsetsPlugin.prototype.unlock = function () {
        var that = this;

        that.isLocked = 0;
        that.$submitBtn.prop('disabled', false);
    };

    /* Очистка мусора со страницы */
    ProductsetsPlugin.prototype.clean = function () {
        $(".colorpicker, #ui-datepicker-div").remove();
    };

    return ProductsetsPlugin;

})(jQuery);

$.fn.extend({
    productsetsAutocomplete: function (options) {
        return this.each(function () {
            var $this, autocomplete;
            $this = $(this);
            autocomplete = $this.data('productsetsAutocomplete');
            if (!(autocomplete instanceof ProductsetsAutocomplete)) {
                $this.data('productsetsAutocomplete', new ProductsetsAutocomplete($this, options));
            }
        });
    }
});

$.fn.serializeObject = function () {

    var self = this,
        json = {},
        push_counters = {},
        patterns = {
            "validate": /^[a-zA-Z][a-zA-Z0-9_]*(?:\[(?:\d*|[a-zA-Z0-9_]+)\])*$/,
            "key": /[a-zA-Z0-9_]+|(?=\[\])/g,
            "push": /^$/,
            "fixed": /^\d+$/,
            "named": /^[a-zA-Z0-9_]+$/
        };


    this.build = function (base, key, value) {
        base[key] = value;
        return base;
    };

    this.push_counter = function (key) {
        if (push_counters[key] === undefined) {
            push_counters[key] = 0;
        }
        return push_counters[key]++;
    };

    $.each($(this).serializeArray(), function () {
        var k,
            keys = this.name.match(patterns.key),
            merge = this.value,
            reverse_key = this.name;

        while ((k = keys.pop()) !== undefined) {

            // adjust reverse_key
            reverse_key = reverse_key.replace(new RegExp("\\[" + k + "\\]$"), '');

            // push
            if (k.match(patterns.push)) {
                merge = self.build([], self.push_counter(reverse_key), merge);
            }

            // fixed
            else if (k.match(patterns.fixed)) {
                merge = self.build([], k, merge);
            }

            // named
            else if (k.match(patterns.named)) {
                merge = self.build({}, k, merge);
            }
        }

        json = $.extend(true, json, merge);
    });

    return json;
};

var ProductsetsAutocomplete = (function ($) {

    ProductsetsAutocomplete = function (elem, options) {
        var that = this;

        that.elem = elem;
        that.parent = that.elem.parent();
        that.url = options.url;
        that.minLength = options.minLength || 3;
        that.delay = options.delay || 300;
        that.onSearch = (options["onSearch"] || false);
        that.onSelect = (options["onSelect"] || false);
        that.$resultBlock = (options.$resultBlock || '');

        that.initClass();
    };

    ProductsetsAutocomplete.prototype.initClass = function () {
        var that = this;

        // Автозаполнение
        that.elem.autocomplete({
            minLength: that.minLength,
            delay: that.delay,
            autoFocus: true,
            source: function (request, response) {
                var skus = that.parent.find('.f-autocomplete-skus').prop("checked");
                $.ajax({
                    url: that.url + (skus ? '&with_skus=1' : ''),
                    type: "GET",
                    data: request,
                    dataType: "JSON",
                    minLength: that.minLength,
                    success: function (data) {
                        response($.map(data, function (el) {
                            return el;
                        }));
                    }
                });
            },
            select: function (event, ui) {
                if (!that.onSelect) {
                    if (!that.$resultBlock.length) {
                        if (!that.parent.find(".productsets-autocomplete-result").length) {
                            that.$resultBlock = $("<div class='productsets-autocomplete-result mb' />");
                            that.parent.append(that.$resultBlock);
                        } else {
                            that.$resultBlock = that.parent.find(".productsets-autocomplete-result");
                        }
                    }

                    var table = that.$resultBlock.find('table');
                    if (!table.length) {
                        table = $("<table class='zebra'></table>");
                        that.$resultBlock.html(table);
                    }
                    if (!table.find('.item-product-' + ui.item.id + '-' + ui.item.sku_id).length) {
                        table.append(tmplPs('tmpl-display-autocomplete-item', ui.item));
                    }
                    that.elem.val('');
                } else {
                    that.onSelect(event, ui);
                }

                return false;
            }
        });
    };

    return ProductsetsAutocomplete;
})(jQuery);