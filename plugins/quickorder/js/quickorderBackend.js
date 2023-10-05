var QuickorderPlugin = (function ($) {

    QuickorderPlugin = function (options) {
        var that = this;

        /* DOM */
        that.$form = options.form;
        that.$tabContent = that.$form.find('.f-tab-content');
        that.$storefrontSelect = that.$form.find('.f-storefront');
        that.$storefrontContent = that.$form.find('.f-storefront-content');
        that.$submitBtn = that.$form.find('#f-quickorder-save');


        /* DYNAMIC VARS */
        that.storage = new $.store();
        that.hash = options.hash || location.href;
        that.lang = options.lang || 'en';

        /* VARS */
        that.loader = '<i class="icon16 loading"></i>';
        that.isLocked = 0;

        /* INIT */
        that.initClass();
        that.bindEvents();
        that.initSave();

        that.loadStorefrontSettings();
    };

    QuickorderPlugin.prototype.initClass = function () {
        var that = this;
        that.reinit();
    };

    QuickorderPlugin.prototype.bindEvents = function () {
        var that = this;

        that.$storefrontSelect.change(function () {
            that.loadStorefrontSettings(this.value);
        });

        /* Переключение вкладок настроек */
        that.$storefrontContent.find('.f-tab a').click(function () {
            var tab = $(this);
            tab.parent().addClass('selected').siblings().removeClass("selected");
            that.$tabContent.find('.f-tab-' + tab.data('tab')).show().siblings().hide();
        });

        /* Разделение настроек для корзины и страницы товара */
        $(document).on('change', '.f-shared-settings', function () {
            var checkbox = this,
                tab = $(checkbox).data('tab');

            if (checkbox.checked) {
                that.$storefrontContent.find('.f-' + tab + '-shared-settings-tabs').hide();
                that.$storefrontContent.find('.f-' + tab + '-cart').hide();
                that.$storefrontContent.find('.f-' + tab + '-product').show().siblings().hide();
                that.$storefrontContent.find('.f-' + tab + '-shared-settings-tabs a[data-page="product"]').parent().addClass('selected').siblings().removeClass('selected');
            } else {
                that.$storefrontContent.find('.f-' + tab + '-shared-settings-tabs').show();
            }
        });
        $(document).on('click', '.f-shared-settings-tabs a', function () {
            var a = $(this),
                tab = $(a).closest('.f-shared-settings-tabs').data('tab');

            a.parent().addClass('selected').siblings().removeClass('selected');
            that.$storefrontContent.find('.f-' + tab + '-' + a.data('page')).show().siblings().hide();
        });

        /* Очищаем html страницу от модуля ColorPicker */
        $(".sidebar a").click(function () {
            that.clean();
        });
    };

    /* Сохранение данных */
    QuickorderPlugin.prototype.initSave = function () {
        var that = this,
            successTimeout = null,
            disabledOptions = null;

        that.$submitBtn.on("click", function () {
            that.$form.trigger("submit");
        });

        that.$form.on("submit", onSubmit);

        function onSubmit(event) {
            event.preventDefault();

            var formData = getData();
            request(formData);
        }

        function request(data) {
            if (!that.isLocked) {
                removeLoading();
                addLoading();
                that.lock();
                errorText();
                successTimeout && clearTimeout(successTimeout);
                var href = "?plugin=quickorder&module=settings&action=save";
                $.post(href, data, function (response) {
                    if (typeof response !== 'undefined' && response.status == 'fail' && response.errors) {
                        errorText(response.errors);
                    } else {
                        successMessage();
                    }
                }, "json").always(function () {
                    that.unlock();
                    removeLoading();
                    disabledOptions.prop('disabled', true);
                });
            }
        }

        function addLoading() {
            that.$submitBtn.after("<i class='icon16 loading'></i>");
        }

        function removeLoading() {
            that.$submitBtn.next("i").remove();
        }

        function successMessage() {
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

        /* Поля контактной информации, доставки, оплаты */
        function getFields(tab, type) {
            var fields = [];
            that.$form.find(".f-" + tab + "-" + type + "-fields .field").each(function () {
                fields.push($('input, select', this).serializeArray());
            });
            return fields;
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

        function getData() {
            disabledOptions = that.$form.find('option:disabled');
            that.$form.find('option:disabled').prop('disabled', false);
            var fields = {
                    'product': getFields('product', 'contact'),
                    'cart': getFields('cart', 'contact')
                },
                shipping = {
                    'product': getFields('product', 'shipping'),
                    'cart': getFields('cart', 'shipping')
                },
                payment = {
                    'product': getFields('product', 'payment'),
                    'cart': getFields('cart', 'payment')
                },
                appearance = {
                    'product': getAppearance('product'),
                    'cart': getAppearance('cart')
                };
            var $cssTemplate =$('#f-quickorder-css-template');
            $cssTemplate.val($cssTemplate.data('editor').getValue());

            var settings = that.$form.find('[name^=settings]').serializeArray();
            var data = settings
                .concat(that.$form.find('[name^=storefront_settings]').serializeArray())
                .concat({name: 'storefront_settings[fields][product]', value: JSON.stringify(fields['product'])})
                .concat({name: 'storefront_settings[fields][cart]', value: JSON.stringify(fields['cart'])})
                .concat({name: 'storefront_settings[shipping][product]', value: JSON.stringify(shipping['product'])})
                .concat({name: 'storefront_settings[shipping][cart]', value: JSON.stringify(shipping['cart'])})
                .concat({name: 'storefront_settings[payment][product]', value: JSON.stringify(payment['product'])})
                .concat({name: 'storefront_settings[payment][cart]', value: JSON.stringify(payment['cart'])})
                .concat({
                    name: 'storefront_settings[appearance][product]',
                    value: JSON.stringify(appearance['product'])
                })
                .concat({name: 'storefront_settings[appearance][cart]', value: JSON.stringify(appearance['cart'])});
            return data;
        }
    };

    /* Загружаем настройки витрины */
    QuickorderPlugin.prototype.loadStorefrontSettings = function (storefront) {
        var that = this;
        storefront = storefront || that.storage.get('quickorder-last-storefront-' + that.hash) || 'all';

        that.$storefrontSelect.prop('disabled', true).after(that.loader);
        that.$storefrontContent.addClass('hidden');
        that.lock();
        that.clean();
        that.$tabContent.load('?plugin=quickorder&module=settings&action=loadStorefront&storefront=' + storefront, function (response) {
            that.unlock();
            that.$storefrontContent.removeClass('hidden');
            that.$storefrontSelect.prop('disabled', false).next().remove();
            that.$storefrontContent.find('.f-tab:first a').click();
            that.reinit();
        });
    };

    QuickorderPlugin.prototype.reinit = function () {
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

        /* Redactor */
        $('.f-redactor:not(.inited)', that.$form).each(function () {
            var redactor = $(this);
            redactor.redactor({
                minHeight: redactor.data('height') || 250,
                toolbarFixed: false,
                maxHeight: 250,
                lang: that.lang,
                 buttons: ['html', 'format', 'bold', 'italic', 'underline', 'deleted', 'lists',
                     'image', 'video', 'table', 'link', 'alignment',
                     'horizontalrule', 'fontcolor', 'fontsize', 'fontfamily'],
                plugins: ['source', 'fontcolor', 'fontfamily', 'alignment', 'fontsize', 'table', 'video', 'imagemanager'],
                imageUpload: '?plugin=quickorder&module=settings&action=redactorImageUpload',
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
        });
    };

    /* Запретить сохранение данных */
    QuickorderPlugin.prototype.lock = function () {
        var that = this;

        that.isLocked = 1;
        that.$submitBtn.prop('disabled', true);
    };

    /* Разрешить сохранение данных */
    QuickorderPlugin.prototype.unlock = function () {
        var that = this;

        that.isLocked = 0;
        that.$submitBtn.prop('disabled', false);
    };

    /* Очистка мусора со страницы */
    QuickorderPlugin.prototype.clean = function () {
        $(".colorpicker, #ui-datepicker-div").remove();
    };

    return QuickorderPlugin;

})(jQuery);