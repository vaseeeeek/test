/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */
if (typeof jQuery === 'undefined') {
    var script = document.createElement('script');
    script.src = "https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js";
    document.getElementsByTagName('head')[0].appendChild(script);
}

$.quickorder = {
    features: {},
    messages: {},
    init: function (options) {
        var that = this;

        that.options = options;
        that.messages = options.messages;

        if (console && options.isDebug == '1') {
            console.log('* * * Quickorder plugin.Version ' + options.version + ' * * *\nen - https://www.webasyst.com/store/plugin/shop/quickorder/\nru - https://www.webasyst.ru/store/plugin/shop/quickorder/');
        }

        that.loadForms();

        /* Открытие всплывающей формы */
        $(document).on('click', that.options.productButton + ',' + that.options.cartButton, function () {
            that.show($(this));
            return false;
        });

        /* Отслеживаем изменения форм */
        $(document).on("change", "form", function (e) {
            that.onFormChange($(this));
        });

        /* Отслеживаем изменение экрана */
        $(window).resize(function () {
            that.quickorderResize();
        });
        that.quickorderResize();

        /* Инициируем проверку доступности кнопки */
        if ($('[data-quickorder-product-button], [data-quickorder-cart-button]').length) {
            $("input[name='product_id']").each(function () {
                that.onFormChange($(this).closest('form'));
            });
        }
    },
    /* Определяем ширину родительского элемента */
    quickorderResize: function () {
        $('[data-quickorder-product-button], [data-quickorder-cart-button], [data-quickorder-inline-form]').each(function () {
            var elem = $(this),
                parentW = elem.parent().width();

            if (parentW <= 600) {
                elem.addClass("quickorder600 quickorder760");
            } else if (parentW <= 760) {
                elem.addClass("quickorder760");
            } else {
                elem.removeClass('quickorder760 quickorder600');
            }
        });

        $(".quickorder-popup").css('marginLeft', (-1) * Math.max(0, $(".quickorder-popup").outerWidth() / 2) + "px");
    },
    onFormChange: function (form) {
        var that = this;

        var productId = form.find("input[name='product_id']").val(),
            btn = $(that.options.productButton + '[data-quickorder-product-id="' + productId + '"]');
        /* Если найдена кнопка Купить в 1 клик для соответствующего товара, проверяем доступность */
        if (productId && btn.length) {
            setTimeout(function () {
                that.checkAvailability(form, productId, btn);
            }, 100);
        }
    },
    show: function (btn) {
        var that = this;
        var type = btn.is(that.options.productButton) ? 'product' : 'cart';
        var data = {};
        var form = $(document).find("input[name='product_id'][value='" + btn.data('quickorder-product-id') + "']").closest('form');
        data[type] = {};
        data[type][btn.data('quickorder-sku-id')] = {
            product_id: btn.data('quickorder-product-id'),
            params: form.serialize(),
            inline: 0,
            /* Проверяем случай, когда для вызова корзины используется только атрибут и на сайте не подключены стили */
            add_css: (type == 'cart' && (!$("#quickorder-inline-styles").length)) || !$("#quickorder-inline-styles").data('inline-css') ? 1 : 0
        };

        new igaponovDialog({
            loadingContent: true,
            onOpen: function ($wrapper, dialog) {
                $.post(that.options.urls['load'], data, function (response) {
                    if (response.status == 'ok' && response.data.length) {
                        var v = response.data[0];
                        $wrapper.addClass('q-loaded');
                        dialog.$block.html(v.html);

                        /* Подключаем недостающие CSS стили */
                        if (typeof v.css !== 'undefined' && v.css !== '') {
                            if ($("#quickorder-inline-styles").length) {
                                $("#quickorder-inline-styles").replaceWith(v.css);
                            } else {
                                $('head').append(v.css);
                            }
                        }

                        that.initForm(dialog);
                        if (window.grecaptcha && window.onloadWaRecaptchaCallback) {
                            window.onloadWaRecaptchaCallback();
                        }
                    } else {
                        dialog.$block.html("<div class='quickorder-form-content quickorder-center quickorder-empty-cart'>" + $.quickorder.translate('The shopping cart is empty') + "</div>");
                    }
                }).always(function () {
                    /* Если данные не загрузились */
                    if (!$wrapper.find(".quickorder-product").length) {
                        dialog.$block.html("<div class='quickorder-form-content quickorder-center quickorder-empty-cart'>" + $.quickorder.translate('The shopping cart is empty') + "</div>");
                        $wrapper.removeClass('q-loaded');
                    }
                });
            },
            onBgClick: function (e, d) {
                if (that.options.popupClose || !d.$wrapper.hasClass('q-loaded')) {
                    d.close();
                }
            }
        });

    },
    /* Проверяем доступность товара. При необходимости скрываем кнопку */
    checkAvailability: function (form, productId, btn) {
        if (form.find(":radio[name='sku_id']").length) {
            radioSkusAvail(form.find(":radio[name='sku_id']:checked"), btn);
        } else if (form.find(".quickorder-sku-feature").length || form.find("[name^='features[']").length) {
            inlineSkusAvail(form, btn);
        }

        function radioSkusAvail(sku, btn) {
            if (sku.data('disabled')) {
                btn.hide();
            } else {
                btn.attr('data-quickorder-sku-id', sku.val()).css('display', btn.attr('data-button-display') == 'inline' ? 'inline-block' : 'table');
            }
        }

        function inlineSkusAvail(form, btn) {
            /* Проверяем какой из скриптов для вывода характеристик используется */
            if (typeof sku_features !== 'undefined' && $.quickorder.features !== undefined && $.quickorder.features[productId] === undefined) {
                $.quickorder.features[productId] = sku_features;
            }
            if ($.quickorder.features === undefined || $.quickorder.features[productId] === undefined) {
                if (btn.data('features') !== undefined) {
                    $.quickorder.features[productId] = btn.data('features');
                } else {
                    return false;
                }
            }

            var key = "";
            var inlineSkus = form.find(".quickorder-sku-feature").length ? form.find(".quickorder-sku-feature") : form.find("[name^='features[']");
            inlineSkus.filter("select, :checked, :hidden").each(function () {
                key += $(this).data('feature-id') + ':' + $(this).val() + ';';
            });
            var sku = $.quickorder.features[productId][key];
            if (sku) {
                if (sku.available) {
                    btn.attr('data-quickorder-sku-id', sku.id).css('display', btn.attr('data-button-display') == 'inline' ? 'inline-block' : 'table');
                } else {
                    btn.hide();
                }
            } else {
                btn.hide();
            }
        }
    },
    /* Подгрузка форм */
    loadForms: function () {
        var that = this;

        if ($('.quickorder-temp-container').length) {
            var forms = { 'product': {}, 'cart': {} };
            $('.quickorder-temp-container').each(function () {
                var container = $(this);
                forms[container.data('type')][container.data('sku-id')] = {
                    product_id: container.data('product-id'),
                    inline: container.data('inline')
                };
            });
            $.post(that.options.urls['load'], forms, function (response) {
                if (response.status == 'ok' && response.data.length) {
                    $(response.data).each(function (i, v) {
                        $('.quickorder-temp-container[data-type="' + v.type + '"][data-sku-id="' + v.sku_id + '"][data-product-id="' + v.product_id + '"]').replaceWith(v.html);
                    });
                }
            }).always(function () {
                if ($('.quickorder-temp-container').length) {
                    if (console) {
                        console.log('Quickorder plugin. Deleted forms: ' + $('.quickorder-temp-container').length + '\nCheck log files. This is bad behaviour my friend');
                    }
                }
                $('.quickorder-temp-container').remove();
                that.initForm();
            });
        }
    },
    initForm: function (dialog) {
        var that = this;

        /* Инициализация плагина для форм, встроенных на страницу */
        $(".quickorder-form.not-inited").each(function () {
            var form = $(this);
            new QuickorderPluginFrontend({
                form: form,
                urls: that.options.urls,
                currency: that.options.currency,
                minimal: that.options.minimal,
                usingPlugins: that.options.usingPlugins,
                dialog: dialog,
                analytics: that.options.analytics[(form.data('data-quickorder-cf') !== undefined ? 'cart' : 'product')]
            });
        });
    },
    translate: function (message) {
        var that = this;
        if (that.messages[message]) {
            return that.messages[message];
        }
        return message;
    },
    currencyFormat: function (number, no_html) {
        var that = this;

        // Format a number with grouped thousands
        //
        // +   original by: Jonas Raoni Soares Silva (http://www.jsfromhell.com)
        // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
        // +	 bugfix by: Michael White (http://crestidg.com)

        var i, j, kw, kd, km;
        var decimals = that.options.currency.frac_digits;
        var dec_point = that.options.currency.decimal_point;
        var thousands_sep = that.options.currency.thousands_sep;

        // input sanitation & defaults
        if (isNaN(decimals = Math.abs(decimals))) {
            decimals = 2;
        }
        if (dec_point == undefined) {
            dec_point = ",";
        }
        if (thousands_sep == undefined) {
            thousands_sep = ".";
        }

        i = parseInt(number = (+number || 0).toFixed(decimals)) + "";

        if ((j = i.length) > 3) {
            j = j % 3;
        } else {
            j = 0;
        }

        km = (j ? i.substr(0, j) + thousands_sep : "");
        kw = i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + thousands_sep);
        kd = (decimals && (number - i) ? dec_point + Math.abs(number - i).toFixed(decimals).replace(/-/, 0).slice(2) : "");

        number = km + kw + kd;
        var s = no_html ? that.options.currency.sign : that.options.currency.sign_html;
        if (!that.options.currency.sign_position) {
            return s + that.options.currency.sign_delim + number;
        } else {
            return number + that.options.currency.sign_delim + s;
        }
    }
};

var QuickorderPluginFrontend = (function ($) {

    QuickorderPluginFrontend = function (options) {
        var that = this;

        /* DOM */
        that.$form = options.form;
        that.$submitBtn = that.$form.find('[data-quickorder-submit-button]');
        that.$submitField = that.$submitBtn.closest('.quickorder-submit');
        that.$totalFld = that.$form.find('[data-quickorder-total]');
        that.$totalData = that.$form.find('[data-quickorder-total-data]');
        that.$discountFld = that.$form.find('[data-quickorder-discount]');
        that.$affiliateBlock = that.$form.find('.quickorder-affiliate-wrap');

        /* VARS */
        that.dialog = options.dialog || '';
        that.urls = options.urls;
        that.xhr = false;
        that.xhrSkus = false;
        that.timer = false;
        that.is_locked = 0;
        that.is_autosubmit = 0;
        that.minimal = options.minimal;
        that.usingPlugins = options.usingPlugins;
        that.analytics = options.analytics;
        that.loading = " <i class=\"quickorder-loading\"></i>";

        /* INIT */
        that.initClass();
    };

    QuickorderPluginFrontend.prototype.initClass = function () {
        var that = this;

        /* Инициализация скриптов доставки */
        if (that.$form.find('.quickorder-shipping-methods').length) {
            new QuickorderPluginFrontendShipping(that);
        }

        that.bindEvents();
        setTimeout(function () {
            that.initPhoneMask();
        }, 100);
        that.bindCart();

        that.$form.removeClass('not-inited');
        $.quickorder.quickorderResize();

        that.reachGoal('inited');
        if (!that.$form.data('quickorder-inline-form')) {
            that.reachGoal('fopen');
        }
    };

    QuickorderPluginFrontend.prototype.bindEvents = function () {
        var that = this;

        /* Отображение всего списка услуг */
        that.$form.find('.quickorder-js-show-more').click(function () {
            $(this).closest('.quickorder-row').hide().siblings().show();
        });

        /* Увеличение количества товара */
        that.$form.find('.quickorder-js-increase').click(function () {
            that.changeQuantity(this, true);
        });
        /* Уменьшение количества товара */
        that.$form.find('.quickorder-js-decrease').click(function () {
            that.changeQuantity(this, false);
        });
        /* Проверка измененного значение количества */
        that.$form.find('input[name="quickorder_product[quantity]"]').change(function () {
            var $field = $(this),
                newValue = parseFloat($field.val());
            if (newValue <= 0 || isNaN(newValue)) {
                newValue = $field.data("min") || 1;
                $field.val(newValue).trigger('change');
            }
            var maxQuantity = $field.attr('data-max');
            if (maxQuantity !== undefined && parseFloat(maxQuantity) < newValue) {
                $field.val(maxQuantity).trigger('change');
            }
            that.update();
        });

        /* Выбор услуг */
        that.$form.on('click', ".quickorder-service input[type=checkbox]", function () {
            var checkbox = $(this);
            var obj = that.$form.find('.quickorder-service select[name="quickorder_product[service_variant][' + checkbox.val() + ']"]');
            if (obj.length) {
                if (checkbox.is(':checked')) {
                    obj.removeAttr('disabled');
                } else {
                    obj.attr('disabled', 'disabled');
                }
            }
            that.update();
        });
        that.$form.on('change', ".quickorder-service select", function () {
            var select = $(this);
            select.closest(".quickorder-service").find(":checkbox").attr('data-price', select.find(":selected").attr('data-price'));
            that.update();
        });

        /* Появление окна для выбора другой вариации товара */
        that.$form.find('.js-quickorder-product-skus').click(function () {
            that.showSkusPopup($(this));
        });

        /* Закрытие всплывающего окна артикулов */
        that.$form.on('closeSkus', '.quickorder-popup', function () {
            $(this).remove();
            that.$form.removeClass('q-loading');
            if (that.xhrSkus) {
                that.xhrSkus.abort();
            }
            that.xhrSkus = false;
            that.updateCartItems();
        });

        /* Удаление товара */
        that.$form.find('.js-quickorder-remove').click(function () {
            var btn = $(this);
            btn.closest('.quickorder-product').fadeOut(function () {
                var form = btn.closest('.quickorder-form'),
                    callback = null;
                $(this).remove();
                var emptyCart = !form.find('.quickorder-product').length;
                if (emptyCart) {
                    form.find('.quickorder-form-content').addClass('quickorder-center quickorder-empty-cart').html($.quickorder.translate('The shopping cart is empty') + ' ' + that.loading);
                    form.find('.quickorder-form-footer').remove();
                    callback = function () {
                        location.reload();
                    }
                }
                that.update(callback);
            });
        });

        /* Принудительное обновление цен */
        that.$form.on('updatePrice', function () {
            that.update();
        });

        /* Выбор методов оплаты */
        that.$form.on('change', '.quickorder-payment-methods input:radio', function () {
            var btn = $(this);
            that.$form.find('.quickorder-payment-methods .f-quickorder-method').removeClass('selected');
            if (btn.is(':checked')) {
                that.$form.find(".quickorder-payment-methods .wa-form").hide();
                btn.closest('.f-quickorder-method').addClass('selected').find('.wa-form').show();
            }
            if (that.usingPlugins) {
                that.update();
            }
        });

        if (that.dialog && parseInt($.quickorder.options.isMobile)) {
            that.$form.on('focus', ':text, textarea', function (e) {
                e.preventDefault(); e.stopPropagation();
                const elem = $(this);
                const $dialogWrapper = that.dialog.$wrapper.find('.w-dialog-wrapper');
                setTimeout(function () {
                    const top = elem.offset().top + $dialogWrapper.scrollTop() - $dialogWrapper.offset().top - 30;
                    $dialogWrapper.scrollTop(top);
                }, parseInt($.quickorder.options.mobileStabDelay));
            });
        }

        /* Обновление информации при изменении контактных полей */
        var updateFields = [];

        updateFields.push('input[name="quickorder[coupon]"]');
        if ($.quickorder.options.contactUpdate !== undefined && $.quickorder.options.contactUpdate === 1) {
            updateFields.push('.quickorder-fields input, .quickorder-fields select, .quickorder-fields textarea');
        }
        updateFields.push('[data-quickorder-replace-shipping]');
        that.$form.on('change', updateFields.join(','), function () {
            var elem = $(this);
            if (elem.is('[data-quickorder-replace-shipping]')) {
                elem.attr('data-quickorder-replace-flag', 1);
            }
            that.update();
        });

        /* Удаление ошибок с полей */
        that.$form.on('focus', '.quickorder-error', function () {
            $(this).removeClass('quickorder-error').siblings('.quickorder-error-popup').remove();
        });

        /* Показ активных скидок. Гибкие скидки */
        that.$form.find('.quickorder-js-show-ad').click(function () {
            $(this).parent().hide();
            that.$form.find('.quickorder-fl-ad').show();
        });

        /* Использование/отмена бонусов */
        that.$form.on('click', '.quickorder-js-affiliate', function () {
            $(this).next().val(1);
            that.update();
        });

        /* Печатные формы */
        that.$form.on('submit', '.quickorder-success-payment-block form', function () {
            let $form = $(this);
            $form.find(':submit').after(that.loading);
            $.post(that.urls['payment'], $form.serializeArray(), function (response) {
                $form.find('.quickorder-loading').remove();
                if (response) {
                    let wo = window.open();
                    wo.document.open().write(response);
                    wo.document.close();
                }
            });
            return false;
        });

        /* Проверяем наличие флага для автоотправления формы */
        that.$form.on('quickorder-unlocked', function () {
            setTimeout(function () {
                if (!that.is_locked && that.is_autosubmit) {
                    that.submitForm();
                }
            }, 300);
        });

        that.$submitBtn.click(function () {
            that.submitForm();
        });

        /* Обновляем данные в корзине при изменении состава заказа */
        $(document).off('quickorder-updated', '.quickorder-form').on('quickorder-updated', '.quickorder-form', function () {
            that.updateCartItems();
        });
        $(document).off('wa_order_cart_reloaded.quickorder', '#js-order-cart').on('wa_order_cart_reloaded.quickorder', '#js-order-cart', function () {
            that.isCartForm() && $.quickorder.loadForms();
        });
        $(document).off('wa_order_cart_changed.quickorder', '#js-order-cart').on('wa_order_cart_changed.quickorder', '#js-order-cart', function () {
            if (that.isCartForm() && that.isInlineForm()) {
                that.$form.addClass('quickorder-temp-container q-loading').attr('data-type', 'cart').attr('data-sku-id', 0).attr('data-product-id', 0).attr('data-inline', '');
                $.quickorder.loadForms()
            }
        });
    };

    /* Изменяем состав корзины при изменении данных во вспдывающем окне К1К */
    QuickorderPluginFrontend.prototype.updateCartItems = function () {
        var that = this;

        if (that.isCartForm() && $('#js-order-cart').length) {
            let Cart = $('#js-order-cart').data('controller');
            if (Cart !== undefined) {
                Cart.reload();
            }
            var Form = $('#js-order-form').data('controller');
            if (typeof Form !== 'undefined') {
                Form.update();
            }
        }
    };

    /* Определяем, что за форма перед нами: товара или всей корзины */
    QuickorderPluginFrontend.prototype.isCartForm = function () {
        return this.$form.attr('data-quickorder-cf') !== undefined;
    };

    /* Определяем, что за форма перед нами: кнопка или встроенная на страницу */
    QuickorderPluginFrontend.prototype.isInlineForm = function () {
        return this.$form.attr('data-quickorder-inline-form') !== undefined;
    };

    /* Отслеживаем изменение корзины */
    QuickorderPluginFrontend.prototype.bindCart = function () {
        var that = this;

        $(document).ajaxComplete(function (e, jqXHR, options) {
            if (typeof options !== 'undefined' &&
                (
                    that.endsWith(that.urls.cartSaveUrl.shop, options.url)
                    || that.endsWith(that.urls.cartAddUrl.shop, options.url)
                    || that.endsWith(that.urls.cartDeleteUrl.shop, options.url)
                    || that.endsWith(that.urls.cartSaveUrl.plugin, options.url)
                    || that.endsWith(that.urls.cartAddUrl.plugin, options.url)
                    || that.endsWith(that.urls.cartDeleteUrl.plugin, options.url)
                )
            ) {

                that.cartChange();
            }
            return true;
        });
    };

    /* Событие изменения стандартной корзины */
    QuickorderPluginFrontend.prototype.cartChange = function () {
        var that = this;

        if ($(".quickorder-form").length) {
            $(".quickorder-form").addClass('q-loading');

            /* Обновляем данные формы быстрого заказа */
            $.post(that.urls['load'], { cart: { 0: { inline: 1 } } }, function (response) {
                if (response.status == 'ok' && response.data.length) {
                    var v = response.data[0];
                    $(".quickorder-form").each(function () {
                        $(this).replaceWith(v.html).removeClass('q-loading');
                    });
                }
            }).always(function () {
                $.quickorder.initForm();
            });
        }
    };

    /* Вспомогательная функция, определяющая заканчивается ли строка str строкой searchStr. Работает в ослике */
    QuickorderPluginFrontend.prototype.endsWith = function (str, searchStr) {
        return str.substring(str.length - searchStr.length, str.length) === searchStr;
    };

    /* Инициализация маски для телефона */
    QuickorderPluginFrontend.prototype.initPhoneMask = function () {
        var that = this;

        var field = that.$form.find("input[data-type='phone'][data-extra]");
        if (field.length) {
            var mask = field.data('extra');
            const options = {
                onKeyPress: function (val) {
                    if ($.quickorder.options.replace78) {
                        /* Replace first 78 to 7 */
                        setTimeout(function () {
                            let cleanPhone = val.replace(/\D/g, '');
                            if (cleanPhone.length > 1 && parseInt(cleanPhone.substring(0, 2)) === 78) {
                                cleanPhone = cleanPhone.substring(0, 1) + cleanPhone.substring(2)
                                field.val(cleanPhone).trigger('input');
                                field[0].selectionStart = field[0].selectionEnd = 10000;
                            }
                        }, 0);
                    }
                },
                translation: {
                    'Z': {
                        pattern: /0/,
                        fallback: '0',
                    }
                }
            };
            field.quickorderMask(mask, options);
        }
    };

    /* Обновление данных формы */
    QuickorderPluginFrontend.prototype.update = function (callback, ignoreShipping, afterCallback) {
        var that = this;
        if (that.xhr) {
            that.xhr.abort();
        }

        /* Обновляем цены товаров */
        if (that.$form.data('flexdiscount-pr')) {
            /* Удаляем иконку загрузки, если она была */
            that.$form.find('.quickorder-product .quickorder-loading').remove();
            that.$form.find('.quickorder-product .quickorder-price').html(that.loading);
        } else {
            that.updatePrice();
        }

        clearTimeout(that.timer);
        that.timer = setTimeout(function () {
            that.lock();
            that.xhr = $.post(that.urls['update'], that.collectData(), function (response) {
                if (response && response.status == 'ok' && response.data) {
                    that.unlock();

                    if (callback) {
                        callback.call(that);
                    } else {
                        /* Замена "Итого" */
                        if (that.$totalFld.length) {
                            that.$totalFld.html($.quickorder.currencyFormat(response.data.total, !that.$form.data('ruble-sign')))
                        }
                        that.$totalData.attr('data-total', response.data.total);

                        /* Замена "Скидки" */
                        if (that.$discountFld.length) {
                            if (parseFloat(response.data.discount) <= 0) {
                                that.$discountFld.closest('.quickorder-row').hide();
                            } else {
                                that.$discountFld.html($.quickorder.currencyFormat(response.data.discount, !that.$form.data('ruble-sign'))).closest('.quickorder-row').show();
                            }
                        }
                        /* Заменяем методы доставки */
                        if (response.data.shipping !== '' && !ignoreShipping) {
                            filterDelpay(response.data, 'shipping');
                            new QuickorderPluginFrontendShipping(that);
                        }
                        /* Заменяем методы оплаты */
                        if (response.data.payment !== '') {
                            filterDelpay(response.data, 'payment');
                        }

                        /* Замена бонусов */
                        if (response.data.affiliate !== '') {
                            that.$affiliateBlock.html(response.data.affiliate);
                        }

                        /* Гибкие скидки */
                        flexdiscountCallback(response.data);

                        /* Имеется ли ошибка в купонах */
                        const isCouponValid = parseInt(response.data.is_coupon_valid);
                        if (isCouponValid) {
                            that.$form.find('.quickorder-coupon-error').addClass('quickorder-hidden');
                            if (isCouponValid === 1) {
                                that.$form.find('.quickorder-coupon-success').removeClass('quickorder-hidden');
                            } else {
                                that.$form.find('.quickorder-coupon-success').addClass('quickorder-hidden');
                            }
                        } else {
                            that.$form.find('.quickorder-coupon-success').addClass('quickorder-hidden');
                            that.$form.find('.quickorder-coupon-error').removeClass('quickorder-hidden');
                        }

                        /* Заменяем данные корзины  в шаблоне */
                        if ($('[data-quickorder-cart-price]').length) {
                            $('[data-quickorder-cart-price]').html($.quickorder.currencyFormat(response.data.total, !that.$form.data('ruble-sign')));
                        }
                        if ($('[data-quickorder-cart-count]').length) {
                            $('[data-quickorder-cart-count]').text(response.data.quantity);
                        }
                    }
                    that.reachGoal('updated');
                }
            }, "json").always(function () {
                that.xhr = false;
                that.unlock();
                if (afterCallback) {
                    afterCallback.call(that);
                }
            });
        }, 600);

        function filterDelpay(response, type) {
            var methods = type == 'shipping' ? $(response.shipping) : $(response.payment);
            var previous = that.$form.find('.q-' + type).first().data('id');
            var touched = [];
            methods.find('.f-quickorder-method').each(function () {
                var method = $(this);
                var currentMethod = that.$form.find('.quickorder-' + type + '-' + method.data('id'));
                previous = method.data('id');
                touched.push(previous);
                if (currentMethod.length) {
                    if (response.replace_shipping || (response.replace_shipping_plugins && response.replace_shipping_plugins.indexOf(method.data('plugin')) !== -1)) {
                        currentMethod.replaceWith(method);
                    }
                    /* Скрываем метод, отфильтрованный плагином delpayfilter */
                    if (method.data('hide')) {
                        currentMethod.hide().find(':radio:checked').prop('checked', false);
                    } else if (method.data('ignore')) {
                        /* Пропускаем этот метод для доставки. Для оплаты отображаем метод */
                        if (type == 'payment') {
                            currentMethod.show();
                        }
                    }
                }
                /* Добавляем метод после предыдущего обработанного */
                else if (that.$form.find('.quickorder-' + type + '-' + previous).length) {
                    that.$form.find('.quickorder-' + type + '-' + previous).after(method);
                }
                /* Добавляем метод в конец списка */
                else {
                    that.$form.find('.quickorder-' + type + '-methods').append(method);
                }
            });

            /* Скрываем поле оплаты, если используется стандартный фильтр в зависимости от методов оплаты */
            if (type == 'payment' && touched.length < that.$form.find('.quickorder-payment-methods .f-quickorder-method').length) {
                that.$form.find('.quickorder-payment-methods .f-quickorder-method').each(function () {
                    if ($.inArray(parseInt($(this).attr('data-id')), touched) === -1) {
                        $(this).hide();
                    }
                })
            }
        }

        function flexdiscountCallback(response) {
            /* Активные скидки */
            if (response.user_discounts) {
                /* Обновляем блок с активными скидками */
                var flActiveD = that.$form.find('.quickorder-fl-ad');
                flActiveD.html(response.user_discounts.html);
                if (response.user_discounts.collapse) {
                    flActiveD.hide();
                } else {
                    flActiveD.show();
                }
                if (response.user_discounts.html !== '') {
                    that.$form.find('.quickorder-js-show-ad').parent().show();
                } else {
                    that.$form.find('.quickorder-js-show-ad').parent().hide();
                }
            }
            /* Цены со скидкой */
            if (response.prices) {
                $.each(response.prices, function (sku_id, price) {
                    var product = that.$form.find('.quickorder-product[data-sku-id="' + sku_id + '"]');
                    if (product.length) {
                        product.find('.quickorder-price').attr('data-price', price);
                    }
                });
                that.updatePrice();
            }
        }
    };

    /* Обновление цен */
    QuickorderPluginFrontend.prototype.updatePrice = function () {
        var that = this;

        that.$form.find('.quickorder-product').each(function () {
            var product = $(this);
            var priceBlock = product.find('.quickorder-price');
            var comparePriceBlock = product.find('.quickorder-compare-price');
            var price = parseFloat(priceBlock.attr('data-price'));
            var originalPrice = priceBlock.attr('data-original-price') !== undefined ? parseFloat(priceBlock.attr('data-original-price')) : 0;
            var comparePrice = comparePriceBlock.length ? parseFloat(comparePriceBlock.attr('data-price')) : 0;
            var quantity = parseFloat(product.find('input[name="quickorder_product[quantity]"]').val());
            var multiply = parseInt(that.$form.attr('data-multiply')) === 1 ? quantity : 1;

            if (originalPrice > price) {
                comparePrice = originalPrice;
                if (!comparePriceBlock.length) {
                    priceBlock.before('<span class="quickorder-compare-price" data-price="originalPrice"></span>');
                }
            }

            price = price * multiply;
            comparePrice = comparePrice * multiply;

            /* Перебираем услуги */
            product.find(':checkbox:checked').each(function () {
                price += parseFloat($(this).attr('data-price')) * multiply;
                if (comparePrice > 0) {
                    comparePrice += parseFloat($(this).attr('data-price')) * multiply;
                }
            });

            priceBlock.html($.quickorder.currencyFormat(price, !that.$form.data('ruble-sign')));


            if (comparePrice > 0) {
                comparePriceBlock.html($.quickorder.currencyFormat(comparePrice, !that.$form.data('ruble-sign'))).show();
            } else {
                comparePriceBlock.hide();
            }
        });
    };

    /* Отправление формы */
    QuickorderPluginFrontend.prototype.submitForm = function () {
        var that = this;

        if (!that.is_locked) {

            var validate = new QuickorderValidate(that);
            if (validate.validate()) {
                validate.display();
                return false;
            }

            that.lock();
            $.post(that.urls['send'], that.collectData(), function (response) {
                if (response) {
                    if (response.status == 'ok' && response.data) {
                        that.reachGoal('submit', response.data.order_id);

                        /* Выполнение кода аналитики */
                        if (typeof response.data.analytics !== 'undefined' && response.data.analytics !== '') {
                            that.$form.append(response.data.analytics);
                        }
                        /* Перенаправляем на страницу успешного оформления в случае работы с корзиной */
                        if (typeof response.data.redirect !== 'undefined') {
                            window.location = response.data.redirect;
                            that.$submitBtn.replaceWith($.quickorder.translate('Wait, please... Redirecting') + ' ' + that.loading);
                        }
                        /* Отображаем сообщение об успешном оформлении заказа */
                        else if (typeof response.data.html !== 'undefined') {
                            try {
                                that.$form.find('.quickorder-form-content').html(response.data.html);
                            } catch (e) {

                            }
                            that.$form.find('.quickorder-form-footer').remove();
                        }
                    } else if (response.status == 'fail' && response.errors) {
                        that.reachGoal('submit_error');
                        validate.clean();
                        for (var errorId in response.errors) {
                            var error = response.errors[errorId],
                                errorFld = that.$form.find('[name^="quickorder_fields[' + errorId + ']"]');
                            if (errorFld.length) {
                                validate.addError(that.$form.find('[name^="quickorder_fields[' + errorId + ']"]'), error);
                            } else if (errorId == 'terms' && that.$form.find('.quickorder-terms-error').length) {
                                that.$form.find('.quickorder-terms-error').show();
                            } else if (error) {
                                validate.addError(null, error);
                            }
                            /* Если пришел флаг на обновление капчи */
                            if (errorId === 'update_captcha' && that.$form.find('.wa-captcha-img').length) {
                                that.$form.find('.wa-captcha-img').click();
                            }
                        }
                        validate.display();
                    }
                }
            }, "json").fail(function () {
                that.reachGoal('submit_error');
            }).always(function () {
                that.unlock();
                that.removeNotice();
                that.is_autosubmit = 0;
            });
        } else {
            that.addNotice($.quickorder.translate('Wait, please..'));
            that.is_autosubmit = 1;
        }
    };

    QuickorderPluginFrontend.prototype.addNotice = function (message) {
        var that = this;

        that.removeNotice();
        that.$submitField.prepend("<div class='quickorder-notice'>" + message + "</div>");
    };

    QuickorderPluginFrontend.prototype.removeNotice = function () {
        var that = this;

        that.$submitField.find('.quickorder-notice').remove();
    };

    /* Формируем данные для отправки */
    QuickorderPluginFrontend.prototype.collectData = function () {
        var that = this;

        var formData = $([]);

        /* Товары */
        var products = [];
        that.$form.find('.quickorder-product').each(function () {
            products.push($(this).find("input,select").serialize());
        });
        formData = formData.add({
            name: 'products',
            value: products.length ? JSON.stringify(products) : ''
        });

        /* Доставка */
        var activeShipping = that.$form.find("input[name='shipping_id']:checked");
        if (activeShipping.length) {
            var activeShippingBlock = that.$form.find(".quickorder-shipping-" + activeShipping.val());
            /* Shiptor добавляет скрытое поле. Из-за этого некорректно выбираются ПВЗ */
            activeShippingBlock.find('.quickorder-shipping-rates:hidden').prop('disabled', true);
            formData = formData.add(activeShippingBlock.find("input,textarea,select").serializeArray());
            activeShippingBlock.find('.quickorder-shipping-rates:hidden').prop('disabled', false);
        }

        /* Оплата */
        var activePayment = that.$form.find("input[name='quickorder[payment_id]']:checked");
        if (activePayment.length) {
            formData = formData.add(that.$form.find(".quickorder-payment-" + activePayment.val()).find("input,textarea,select").serializeArray());
        }

        formData = formData.add(that.$form.find("[name*=quickorder]").not("[name^=quickorder_product]").add(that.$form.find("[data-quickorder-captcha] input, [data-quickorder-captcha] textarea")).serializeArray());
        formData = formData.add({
            name: 'qformtype',
            value: that.$form.attr("data-quickorder-pf") !== undefined ? 'product' : 'cart'
        });
        if (that.$form.find('[data-quickorder-replace-flag]').length) {
            formData = formData.add({ name: 'replace_shipping', value: 1 });
            that.$form.find('[data-quickorder-replace-flag]').removeAttr('data-quickorder-replace-flag');
        }

        return formData;
    };

    QuickorderPluginFrontend.prototype.lock = function () {
        var that = this;

        that.is_locked = 1;
        that.$submitBtn.addClass('q-disabled');
        that.$form.addClass('q-is-locked');
        if (!that.$submitBtn.find(".quickorder-loading").length) {
            that.$submitBtn.append(that.loading);
        }
        if (that.$discountFld.length) {
            that.$discountFld.append(that.loading);
        }
        if (that.$totalFld.length) {
            that.$totalFld.append(that.loading);
        }
        if (that.$form.find('[data-quickorder-replace-flag]').length) {
            that.$form.find('.quickorder-shipping-wrap, .quickorder-payment-wrap').addClass("quickorder-temp-blocked");
        }
    };

    QuickorderPluginFrontend.prototype.unlock = function () {
        var that = this;

        that.is_locked = 0;
        that.$form.removeClass('q-is-locked');
        that.$submitBtn.removeClass('q-disabled').find(".quickorder-loading").remove();
        if (that.$discountFld.length) {
            that.$discountFld.find(".quickorder-loading").remove();
        }
        if (that.$totalFld.length) {
            that.$totalFld.find(".quickorder-loading").remove();
        }
        that.$form.find('.quickorder-temp-blocked').removeClass('quickorder-temp-blocked');
        that.reachGoal('unlocked');
    };

    /* Появление окна для выбора другой вариации товара */
    QuickorderPluginFrontend.prototype.showSkusPopup = function (elem) {
        var that = this;

        $('.quickorder-popup').trigger('closeSkus');

        var template = "" +
            "    <div class=\"quickorder-popup is-loading\">" +
            "        <div class=\"quickorder-popup-head\">" + $.quickorder.translate('Select product sku') + " <span onclick='$(this).closest(\".quickorder-popup\").trigger(\"closeSkus\");' data-quickorder-close></span></div>" +
            "        <div class=\"quickorder-popup-content\">" +
            "            <span class=\"quickorder-loading2\"></span>" +
            "        </div>" +
            "    </div>";
        var $template = $(template);
        that.$form.addClass('q-loading').append($template);

        $template.css({
            'top': elem.position().top,
            'marginLeft': (-1) * Math.max(0, $template.outerWidth() / 2) + "px"
        }).data('form', that.$form).data('serviceUrl', that.urls['service']).data('sku-id', elem.closest('.quickorder-product').attr('data-sku-id'));

        that.xhrSkus = $.post(that.urls['getProductSkus'], {
            id: elem.data('id'),
            'ruble_sign': that.$form.data('ruble-sign'),
            'image_size': that.$form.data('image-size')
        }, function (response) {
            $template.removeClass('is-loading').find(".quickorder-popup-content").html(response);
            $template.css({ 'marginLeft': (-1) * Math.max(0, $template.outerWidth() / 2) + "px" });
        });
    };

    QuickorderPluginFrontend.prototype.changeQuantity = function (elem, type) {
        var $field = $(elem).siblings('input[type="text"]');
        var value = parseFloat($field.val());
        var newValue;
        var step = $field.data('step') || 1;

        if (type) {
            newValue = value + step;
            var maxQuantity = $field.attr('data-max');
            if (maxQuantity !== undefined && parseFloat(maxQuantity) < newValue) {
                newValue = parseFloat(maxQuantity);
            }
        } else {
            newValue = value - step;
        }
        if (newValue <= 0) {
            newValue = $field.data("min") || 1;
        }
        $field.val(newValue).trigger('change');
    };

    QuickorderPluginFrontend.prototype.reachGoal = function (target, param) {
        var that = this;

        /* Яндекс метрика */
        if (that.analytics.ya_counter) {
            var yaCounter = window['yaCounter' + that.analytics.ya_counter];
            var yaTarget = that.analytics['ya_' + target];
            if (yaTarget) {
                if (typeof ym !== 'undefined') {
                    ym(that.analytics.ya_counter, 'reachGoal', yaTarget);
                } else if (yaCounter) {
                    yaCounter.reachGoal(yaTarget);
                }
            }
        }

        /* Google analytics */
        var gaCategory = that.analytics['ga_category_' + target];
        var gaAction = that.analytics['ga_action_' + target];
        if (window.ga && (gaCategory || gaAction)) {
            var tracker = '';
            if ($.isFunction(ga.getAll)) {
                tracker = ga.getAll()[0];
            }
            if (tracker) {
                tracker.send('event', gaCategory, gaAction);
            } else {
                window.ga('send', {
                    hitType: "event", eventCategory: gaCategory, eventAction: gaAction
                });
            }
        }
        that.$form.trigger('quickorder-' + target, [param]);
    };

    return QuickorderPluginFrontend;

})(jQuery);

var QuickorderValidate = (function () {

    QuickorderValidate = function (parent) {
        var that = this;

        that.parent = parent;
        that.$form = parent.$form;
        that.minimal = parent.minimal;
        that.$errorBlock = that.$form.find('.quickorder-error-block');
        that.errors = [];
        that.termsError = 0;

        that.findErrors(that.$form);
    };

    /* Выполнение всех проверок */
    QuickorderValidate.prototype.findErrors = function () {
        var that = this;

        that.requiredFilter();
        that.maskFilter();
        that.shippingFilter();
        that.paymentFilter();
        that.minimalFilter();
    };

    /* Имеются ли ошибки после выполнения всех проверок */
    QuickorderValidate.prototype.validate = function () {
        var that = this;

        that.cleanScreen();

        return !!(that.errors.length || that.termsError);
    };

    /* Очистка ошибок */
    QuickorderValidate.prototype.clean = function () {
        var that = this;

        that.cleanScreen();
        that.errors = [];
        that.termsError = 0;
    };

    /* Скрытие ошибок */
    QuickorderValidate.prototype.cleanScreen = function () {
        var that = this;

        that.$form.find(".quickorder-error").removeClass("quickorder-error");
        that.$form.find('.quickorder-error-popup').remove();
        that.$form.find('.quickorder-terms-error').hide();
        that.$errorBlock.hide();
    };

    /* Отображение ошибок */
    QuickorderValidate.prototype.display = function () {
        var that = this;

        if (that.errors.length) {
            var commonErrors = [];
            for (var i in that.errors) {
                var v = that.errors[i];
                if (v.field) {
                    v.field.addClass('quickorder-error');
                    that.showError(v.field, v.message);
                } else {
                    commonErrors.push(v.message);
                }
            }
            if (commonErrors.length) {
                that.$errorBlock.html(commonErrors.join('<br>')).show();
            }
        }
        if (that.termsError) {
            that.$form.find('.quickorder-terms-error').show();
        }
    };

    /* Отображение ошибок */
    QuickorderValidate.prototype.showError = function (field, message) {
        if (!field.siblings('.quickorder-error-popup').length) {
            field.after('<div class="quickorder-error-popup"><div>' + message + '</div></div>');
        }
    };

    /* Добавления поля и сообщения об ошибке в массив */
    QuickorderValidate.prototype.addError = function (elem, message) {
        var that = this;

        that.errors.push({ field: elem, message: message });
    };

    /* Проверка обязательных полей, капчи, соглашения */
    QuickorderValidate.prototype.requiredFilter = function () {
        var that = this;

        /*  Обязательные поля */
        var requiredErrors = 0;
        that.$form.find('.quickorder-required').each(function () {
            var element = $(this).find(':input,select,textarea');
            var value = element.val();
            if ($.trim(value) == '') {
                that.addError(element, $.quickorder.translate('Field is required'));
                requiredErrors = 1;
            }
        });
        if (requiredErrors) {
            that.addError(null, $.quickorder.translate('Fill in required fields'));
        }

        /* Проверка наличия товаров в заказе */
        if (!that.$form.find('.quickorder-product').length) {
            that.addError(null, $.quickorder.translate('Your order is empty'));
        }

        /* Капча */
        var captcha = that.$form.find('[data-quickorder-captcha]');
        var captchaField = captcha.find('input,textarea');
        if (captcha.length && captchaField.val() == '') {
            that.addError(null, $.quickorder.translate('Fill in captcha field'));
        }

        /* Принятие условий соглашения */
        var terms = that.$form.find('input[name="quickorder[terms]"]');
        if (terms.length && !terms.prop('checked')) {
            that.termsError = 1;
            if (!that.$form.find('.quickorder-terms-error').length) {
                that.addError(null, $.quickorder.translate('Terms and agreement'));
            }
        }
    };

    /* Проверка маски телефона */
    QuickorderValidate.prototype.maskFilter = function () {
        var that = this;

        var phone = that.$form.find("input[data-type='phone']");
        if (phone.length && phone.data('extra') !== undefined && phone.val() !== '') {
            phone.trigger('input');
            var pattern = phone.data("extra");
            pattern = "^" + pattern.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, "\\$&").replace(/\s/g, '\s').replace(/[0]/g, '\\d').replace(/[Z]/g, '0') + "$";
            try {
                var regexp = new RegExp(pattern);
                if (!regexp.test(phone.val())) {
                    that.addError(phone, $.quickorder.translate('Phone format is not correct.<br>Use this one:') + ' ' + phone.data('extra'));
                    that.addError(null, $.quickorder.translate('Fix the errors above'));
                }
            } catch (e) {
                that.addError(phone, $.quickorder.translate('Phone format is not correct.<br>Use this one: ') + phone.data('extra'));
                if (console) {
                    console.log('Quickorder plugin. Problems with phone mask. Full error info:');
                    console.log(e);
                }
            }
        }
    };

    /* Проверка методов доставки */
    QuickorderValidate.prototype.shippingFilter = function () {
        var that = this;

        if (that.$form.find('.quickorder-shipping-methods').length) {
            var checked = that.$form.find('.quickorder-shipping-methods :radio:checked');
            if (!checked.length || checked.closest('.f-quickorder-method').hasClass('q-method-error')) {
                that.addError(null, $.quickorder.translate('Shipping method has errors. Please, fix them.'));
            }
        }
    };

    /* Проверка методов оплаты */
    QuickorderValidate.prototype.paymentFilter = function () {
        var that = this;

        if (that.$form.find('.quickorder-payment-methods').length) {
            var checked = that.$form.find('.quickorder-payment-methods :radio:checked');
            if (!checked.length || checked.closest('.f-quickorder-method').hasClass('q-method-error')) {
                that.addError(null, $.quickorder.translate('Payment method has errors. Please, fix them.'));
            }
        }
    };

    /* Минимальная стоимость */
    QuickorderValidate.prototype.minimalFilter = function () {
        var that = this;

        if (that.minimal) {
            /* Минимальная сумма */
            var total = parseFloat(that.parent.$totalData.attr('data-total'));
            if (that.minimal.price !== undefined && parseFloat(that.minimal.price) > total) {
                that.addError(null, $.quickorder.translate('Minimal sum of order is %s').replace('%s', $.quickorder.currencyFormat(parseFloat(that.minimal.price), !that.$form.data('ruble-sign'))));
            }

            /* Минимальная сумма каждого товара */
            if (that.minimal.product_sum !== undefined) {
                var minimalProductSum = parseFloat(that.minimal.product_sum);
                that.$form.find('.quickorder-product').each(function () {
                    var product = $(this);
                    var productSum = parseFloat(product.find('[name="quickorder_product[quantity]"]')) * parseFloat(product.find('.quickorder-price').attr("data-price"));
                    if (minimalProductSum > productSum) {
                        that.addError(null, $.quickorder.translate('Minimal sum of each product is') + ' ' + $.quickorder.currencyFormat(minimalProductSum, !that.$form.data('ruble-sign')));
                        return false;
                    }
                });
            }

            /* Минимальное общее количество товаров */
            if (that.minimal.total_quantity !== undefined) {
                var totalQuantity = 0.0;
                that.$form.find('[name="quickorder_product[quantity]"]').each(function () {
                    totalQuantity += parseFloat($(this).val());
                });
                if (parseFloat(that.minimal.total_quantity) > totalQuantity) {
                    that.addError(null, $.quickorder.translate('Minimal quantity of products is') + ' ' + parseFloat(that.minimal.total_quantity));
                }
            }

            /* Минимальное количество каждого товара */
            if (that.minimal.product_quantity !== undefined) {
                var minimalProductQuantity = parseFloat(that.minimal.product_quantity);
                that.$form.find('[name="quickorder_product[quantity]"]').each(function () {
                    if (minimalProductQuantity > parseFloat($(this).val())) {
                        that.addError(null, $.quickorder.translate('Minimal quantity of each product is') + ' ' + minimalProductQuantity);
                        return false;
                    }
                });
            }
        }
    };

    return QuickorderValidate;

})(jQuery);

$.hideskusQuickorderPlugin = {
    inlineItem: "",
    inlineRow: "",
    locale: "",
    selectedClass: "selected",
    disabledClass: "hideskus-disabled",
    disabledRadioClass: "hideskus-radio-disabled",
    hiddenOptionClass: "hideskus-option-hide",
    hideNotInStock: 0,
    gotoVisible: 1,
    delayStart: 1,
    force: 1,
    messages: { ru_RU: { Empty: "Отсутствует", "Not in stock": "Нет в наличии" } },
    translate: function (e) {
        return void 0 !== this.messages[this.locale] && this.messages[this.locale][e] ? this.messages[this.locale][e] : e
    },
    init: function (e) {
        this.hideNotInStock = void 0 !== e && void 0 !== e.hide_not_in_stock ? parseInt(e.hide_not_in_stock) : 0, this.gotoVisible = void 0 !== e && void 0 !== e.go_to_available ? parseInt(e.go_to_available) : 1, this.delayStart = void 0 !== e && void 0 !== e.delay ? parseInt(e.delay) : 1, this.delayStart && ($(document).on("change", ".quickorder-sku-feature", function () {
            this.delayStart = 0
        }), setTimeout(function () {
            $.hideskusQuickorderPlugin.delayStart && $(".quickorder-sku-feature").length && $(".quickorder-sku-feature:first").change()
        }, 1e3)), $(function () {
            var e = $('input:radio[name="quickorder[sku_id]"]');
            if (e.length) {
                var t = 0, a = {}, l = {};
                e.each(function () {
                    var e = $(this), i = e.closest("form").find("input[name='product_id']").val();
                    if (e.prop("disabled") || 1 == e.data("disabled")) {
                        var s = e.closest("li").length ? e.closest("li") : e.closest("div");
                        $.hideskusQuickorderPlugin.hideNotInStock ? (s.hide(), e.is(":checked") && (t = 1)) : s.addClass($.hideskusQuickorderPlugin.disabledRadioClass).attr("title", $.hideskusQuickorderPlugin.translate("Not in stock"))
                    } else void 0 === l[i] && (a[i] = e, l[i] = 1)
                }), (t || $.hideskusQuickorderPlugin.gotoVisible) && ($.isEmptyObject(a) || setTimeout(function () {
                    $.each(a, function () {
                        $(this).click()
                    })
                }, 800))
            }
        })
    },
    setOptions: function (e) {
        this.inlineItem = void 0 !== e && void 0 !== e.inlineItem ? e.inlineItem : "", this.inlineRow = void 0 !== e && void 0 !== e.inlineRow ? e.inlineRow : "", this.selectedClass = void 0 !== e && void 0 !== e.selectedClass ? e.selectedClass : "selected", this.disabledClass = void 0 !== e && void 0 !== e.disabledClass ? e.disabledClass : "hideskus-disabled", this.disabledRadioClass = void 0 !== e && void 0 !== e.disabledRadioClass ? e.disabledRadioClass : "hideskus-radio-disabled"
    },
    start: function (e, C) {
        function r(e, i) {
            return i = i || !1, S.inlineItem && e.closest(S.inlineItem).length ? i ? e.closest(S.inlineItem).siblings(S.inlineItem).andSelf() : e.closest(S.inlineItem).siblings(S.inlineItem) : S.inlineItem && e.siblings(S.inlineItem).length ? e.siblings(S.inlineItem) : e.siblings("a").length ? (S.inlineItem = "a", e.siblings("a")) : e.parent("label").length ? (S.inlineItem = "label", i ? e.parent().siblings("label").andSelf() : e.parent().siblings("label")) : {}
        }

        function I(e, i) {
            void 0 === l[i = i || "disable"][e] && (l[i][e] = 0), l[i][e]++
        }

        function y(e, i) {
            if (void 0 !== l[i = i || "disable"][e]) return l[i][e];
            var s = 0;
            for (var t in l[i]) -1 !== t.indexOf(e) && (s += l[i][t]);
            return s || -1
        }

        function O(e) {
            return C.split(";").length !== e.split(";").length
        }

        function _(e, i, s) {
            !O(e) && C == e && s && (a.findVisible[e] = { path: e, skuFeat: i }), a.skuFeat[i.id] = {
                path: e,
                skuFeat: i
            }
        }

        function R(e, i) {
            i ? (void 0 === e.attr("data-old-title") && e.attr("data-old-title", void 0 !== e.attr("title") ? e.attr("title") : ""), e.attr("title", i)) : void 0 !== e.attr("data-old-title") ? e.attr("title", e.attr("data-old-title")) : e.removeAttr("title")
        }

        var S = this,
            u = void 0 !== e.form ? e.form : void 0 !== e.$form ? e.$form : $(e).closest("form").length ? $(e).closest("form") : "",
            q = void 0 !== e.features ? e.features : "undefined" != typeof sku_features ? sku_features : "";
        if (!u || !q) return console && console.warn('Plugin "hideskus". \r\n' + (u ? "" : "Form not exists.") + (q ? "" : "Features not exist.")), !0;
        var i, s = (u = u instanceof jQuery ? u : $(u)).find(".quickorder-sku-feature:first"),
            t = s.data("feature-id") + ":" + s.val() + ";", a = { findVisible: {}, skuFeat: {} }, n = 0, w = {},
            l = { disable: {}, hide: {} }, o = {}, c = {}, f = {};
        if (C = C || (i = "", u.find(".quickorder-sku-feature").each(function () {
            var e = $(this);
            i += e.data("feature-id") + ":" + e.val() + ";"
        }), i), u.find(".quickorder-sku-feature").each(function (e) {
            var i, s = $(this), t = s.data("feature-id");
            if (void 0 !== f[t]) return !0;
            var a = s.is("input");
            if (f[t] = 1, a) {
                var l = s.siblings(".temp-block"),
                    d = l.length ? l : s.parent().find(".temp-block").length ? s.parent().find(".temp-block") : "";
                d.length && d.remove()
            } else s.prop("disabled", !1).find(".temp-block").remove();
            i = {
                id: t,
                isInput: a,
                isRadio: s.is("input:radio"),
                fields: a ? r(s, !0) : s.find("option").length ? s.find("option") : {}
            }, 0 === e && (o = i), c.children = i, c = i
        }), function f(h, v, k, p) {
            var b = v, m = -1 !== C.indexOf(b), g = !1;
            return w[p] = 1, $.each(k.fields, function (e, i) {
                var s = void 0 !== (i = $(i)).data("value") ? i.data("value") : k.isRadio ? i.find("input").val() : i.val();
                if (v += k.id + ":" + s + ";", void 0 !== k.children) {
                    var t = f(h, v, k.children, p + 1);
                    g || (w[p] = t.hideLimit), v += t.key
                }
                var a = b + k.id + ":" + s + ";", l = y(a, "hide"), d = y(a) + (-1 !== l ? l : 0), n = d >= w[p] && m,
                    o = l >= w[p] && m, r = d < w[p] && m, u = l < w[p] && m, c = 1 == w[p] && m;
                !O(a) && !q[v] || o ? (o || I(b, "hide"), (c || o) && (i.hide(), k.isInput || i.addClass(S.hiddenOptionClass), _(a, k, 1))) : n || q[v] && !q[v].available ? (n || I(b, S.hideNotInStock ? "hide" : "disable"), (c || n) && (S.hideNotInStock ? (i.hide(), k.isInput || i.addClass(S.hiddenOptionClass)) : (i.show(), k.isInput || i.removeClass(S.hiddenOptionClass)), k.isInput ? (i.addClass(S.disabledClass), R(i, $.hideskusQuickorderPlugin.translate("Not in stock"))) : (i.addClass(S.disabledClass), -1 !== C.indexOf(a) && i.closest("select").addClass(S.disabledClass)), _(a, k, S.hideNotInStock ? 1 : S.gotoVisible ? 1 : S.force ? 1 : 0))) : (r || c || u) && (k.isInput ? (i.show().removeClass(S.disabledClass), R(i)) : (i.show().removeClass(S.disabledClass).removeClass(S.hiddenOptionClass), -1 !== C.indexOf(a) && i.closest("select").removeClass(S.disabledClass))), v = b, g = !0
            }), { key: v, hideLimit: void 0 !== k.children ? w[p] * k.fields.length : k.fields.length }
        }(t, "", o, 0), !$.isEmptyObject(a.findVisible)) if (function () {
            if (!$.isEmptyObject(a.findVisible)) {
                var d = function (e) {
                    function i(e) {
                        var i = e.split(";");
                        for (var s in i) if ("" != i[s]) {
                            var t = i[s].split(":"),
                                a = u.find(".quickorder-sku-feature[name='quickorder[features][" + t[0] + "]']");
                            if (a.is("input:radio") && (a = u.find(".quickorder-sku-feature[name='quickorder[features][" + t[0] + "]'][value='" + t[1] + "']")), a.is("input")) {
                                var l = S.inlineRow ? a.closest(S.inlineRow) : a.parent().parent(), d = r(a);
                                if (d.removeClass(S.selectedClass), a.is("input:radio") ? (l.find(".quickorder-sku-feature").prop("checked", !1), a.prop("checked", !0)) : (a.attr("value", t[1]), d.find("[data-value='" + t[1] + "']").addClass(S.selectedClass), d.filter("[data-value='" + t[1] + "']").addClass(S.selectedClass)), a.parent(".color").length) {
                                    var n = a.parent(".color").find(".fa-check");
                                    if (n.length) {
                                        var o = a.siblings("[data-value='" + t[1] + "']");
                                        o.find(".fa-check").length || (n.remove(), o.append('<i class="fa fa-check"></i>'))
                                    }
                                }
                            } else a.find("option").removeClass(S.selectedClass).prop("selected", !1).siblings("[value='" + t[1] + "']").addClass(S.selectedClass).prop("selected", !0), a.val(t[1])
                        }
                    }

                    var s = e.substring(0, e.lastIndexOf(":")), t = 2 === s.split(":").length, a = null;
                    if ("" === s) return !1;
                    for (var l in q) if (!O(l) && -1 !== l.indexOf(s)) {
                        if (q[l].available) return i(l), !(n = 1);
                        null === a && l !== e && t && (a = l)
                    }
                    return null === a || S.gotoVisible || S.force ? d(s) : (i(a), !(n = 1))
                };
                $.each(a.findVisible, function (e, i) {
                    d(i.path)
                })
            }
        }(), n) {
            if (n) return s.change(), !1
        } else $.each(a.skuFeat, function (e, i) {
            if (!i.skuFeat.fields.is(":visible")) {
                var s = i.skuFeat.fields.first(), t = s.clone(), a = t.find("span").length ? t.find("span") : t;
                t.addClass("temp-block " + S.disabledClass).css("display", "inline-block"), "" !== a.text() && a.html($.hideskusQuickorderPlugin.translate("Empty")), s.after(t.prop("outerHTML"))
            }
        });
        return !(S.force = 0)
    }
};