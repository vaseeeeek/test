/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */
if (typeof jQuery === 'undefined') {
    var script = document.createElement('script');
    script.src = "https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js";
    document.getElementsByTagName('head')[0].appendChild(script);
}

var FlexdiscountPluginFrontend = (function ($) {

    FlexdiscountPluginFrontend = function (options) {
        var that = this;

        /* DOM */
        that.$couponResultField = $(".flexdiscount-coup-result");
        that.$couponForm = $(".flexdiscount-form");

        /* DYNAMIC VARS */
        that.urls = options.urls || {};
        that.couponCode = options.couponCode || '';
        that.locale = options.locale || '';
        that.settings = options.settings || {};
        that.shopVersion = parseFloat(options.shopVersion) || 0;
        that.loaderType = options.loaderType || 'loader1';
        that.hideDefaultAffiliateBlock = parseInt(options.hideDefaultAffiliateBlock) || 0;
        that.addAffiliateBlock = parseInt(options.addAffiliateBlock) || 0;
        that.updateInfoblocks = parseInt(options.updateInfoblocks);
        that.addActiveDiscountsBlock = parseInt(options.addActiveDiscountsBlock) || 0;
        that.ss8forceUpdate = parseInt(options.ss8forceUpdate) || 0;
        that.ss8UpdateAfterPayment = options.ss8UpdateAfterPayment || false;

        /* VARS */
        that.productIds = {};
        that.wrapElements = ['.dialog', '.modal', '.s-dialog-wrapper'];
        that.cartTotalElements = ['.cart-total', '.js-cart-total', '#cart-total'];
        that.cartAffiliateBlock = ['.affiliate', '.s-affiliate-hint-wrapper', '.affiliate-text', '.bonus-count'];
        that.$affiliateHolder = $('.fl-affiliate-holder');
        that.$affiliateParentBlock = {};
        that.xhr = that.xhrCart = {};
        that.qtyTimer = null;
        that.cartTimer = null;
        that.messages = {
            'ru_RU': {
                "Coupon code is correct": "Купон активирован",
                "Coupon code is incorrect": "Купон не существует"
            }
        };
        that.isOnestepCheckout = that.shopVersion >= 8 && $("#js-order-cart").length;

        /* INIT */
        that.initClass();
    };

    FlexdiscountPluginFrontend.prototype.initClass = function () {
        var that = this;

        /* Отображение действующего купона */
        if (that.$couponForm.length && that.couponCode !== '') {
            that.$couponForm.find(".flexdiscount-coupon-code").val(that.couponCode);
            that.$couponForm.find(".flexdiscount-coup-del-block").show();
        }

        /* Прячем блок с бонусами, если их значение равно нулю */
        that.findAffiliateParent();
        if (that.hideDefaultAffiliateBlock) {
            that.$affiliateParentBlock.remove();
        } else {
            if (that.$affiliateHolder.length && that.$affiliateHolder.hasClass('fl-hide-block')) {
                that.$affiliateParentBlock.hide().find(".icon16-flexdiscount").replaceWith(0);
            }
        }
        that.bindEvents();
        if (that.isOnestepCheckout) {
            setTimeout(function () {
                that.initSS8();
            }, 500);
        }
    };

    FlexdiscountPluginFrontend.prototype.initSS8 = function () {
        var that = this;

        /* Если мы на странице оформления в 1 шаг */
        if ($("#js-order-page").length || $('#js-order-cart').length) {
            var Cart = $('#js-order-cart').data('controller');

            /* Дополнительная форма купонов */
            if (that.settings.enable_frontend_cart_hook !== undefined
                && parseInt(that.settings.enable_frontend_cart_hook)
                && typeof Cart !== 'undefined') {
                that.$couponForm = $(that.settings.coupon_form);
                if (Cart.$coupon_section.length) {
                    Cart.$coupon_section.append(that.$couponForm);
                } else {
                    Cart.$outer_wrapper.find('.wa-cart-details .wa-column-content').prepend('<div class="wa-coupon-section"></div>');
                    Cart.$outer_wrapper.find('.wa-coupon-section').append(that.$couponForm);
                }

                /* Отображение действующего купона */
                if (that.$couponForm.length && that.couponCode !== '') {
                    that.$couponForm.find(".flexdiscount-coupon-code").val(that.couponCode);
                    that.$couponForm.find(".flexdiscount-coup-del-block").show();
                }
            }

            that.bindSS8();

            /* Обновляем форму, чтобы скидки были актуальные. В некоторых случаях это необходимо. */
            if (that.ss8forceUpdate) {
                that.updateOnestepForm();
            }
        }
    };

    FlexdiscountPluginFrontend.prototype.updateOnestepForm = function () {
        var Form = $('#js-order-form').data('controller');
        if (typeof Form !== 'undefined') {
            Form.update();
        }
    };

    FlexdiscountPluginFrontend.prototype.bindSS8 = function () {
        var that = this;

        /* При изменении блока с корзиной обновить все блоки ГС */
        $(document).on('wa_order_cart_rendered', '#js-order-cart', function () {
            that.cartChange();
        });

        $(document).on('wa_order_form_changed', '#js-order-form', function () {
            that.cartChange();
        });

        /* Если используется Оплата в условиях и Доставка в целях, делаем двойное обновление корзины */
        if (that.ss8UpdateAfterPayment) {
            var stop = false;

            $(document).on('wa_order_form_payment_changed', '#js-order-form', function () {
                var activeShipping = $('#js-order-form .wa-type-wrapper.is-active');
                if (!stop && activeShipping.length) {
                    setTimeout(function () {
                        if ($.active === 0 && $('#wa-step-payment-section').is(':visible')) {
                            that.updateOnestepForm();
                            stop = true;
                        }
                    }, 100);
                } else {
                    stop = false;
                }
            });
        }
    };

    FlexdiscountPluginFrontend.prototype.bindEvents = function () {
        var that = this;

        /* Отправка купона */
        $(document).on('click', ".flexdiscount-submit-button", function () {
            var btn = $(this);
            var coupon = btn.closest(".flexdiscount-form").find(".flexdiscount-coupon-code");
            var couponCode = $.trim(coupon.val());
            btn.attr("disabled", "disabled");
            that.addLoading(btn);
            $.post(that.urls.couponAddUrl, { coupon: couponCode }, function (response) {
                var form = btn.closest("form"),
                    cartUrl = form.length ? (form.attr('data-flexdiscount-action') !== undefined ? form.attr('data-flexdiscount-action') : form.attr('action')) : '';
                cartUrl = (typeof cartUrl !== 'undefined' && cartUrl !== '' && cartUrl !== 'about:blank') ? cartUrl : location.href;
                btn.removeAttr("disabled");
                if (that.$couponResultField.length) {
                    if (response.status == 'ok') {
                        that.$couponResultField.removeClass("flexdiscount-error").html(that.translate('Coupon code is correct') + " <i class='icon16-flexdiscount loading'></i>");
                        location.href = cartUrl;
                    } else {
                        that.$couponResultField.addClass("flexdiscount-error").html(that.translate('Coupon code is incorrect'));
                    }
                    that.removeLoading();
                } else {
                    location.href = cartUrl;
                }
            }, "json");
            return false;
        });

        /* Удаление купона */
        $(document).on('click', ".flexdiscount-coupon-delete", function () {
            var btn = $(this);
            if (!that.hasLoading(btn)) {
                that.addLoading(btn);
                $.post(that.urls.deleteUrl, function () {
                    btn.closest(".flexdiscount-coup-del-block").hide().closest(".flexdiscount-form").find(".flexdiscount-coupon-code").val('');
                    location.reload();
                }, "json");
            }
            return false;
        });

        /* При изменении данных формы  производим обновление всех блоков со скидками */
        $(document).on("change", "form, .flexdiscount-product-form", function () {
            var form = $(this),
                productId = form.find("input[name='product_id']");
            if (productId.length) {
                that.productIds[productId.val()] = form;
                clearTimeout(form.data('flexdiscount-timer'));
                form.data('flexdiscount-timer', setTimeout(function () {
                    if (!$.isEmptyObject(that.productIds)) {
                        var pids = that.productIds;
                        that.productIds = {};
                        that.updateProductRules(pids);
                    }
                }, 600));
            }
        });

        /* Ловим изменение кол-ва при нажатии на кнопки */
        $(document).on("click", "a.plus, a.minus, .inc_cart, .dec_cart, .inc_product, .dec_product.dec_product", function () {
            var btn = $(this);
            clearTimeout(that.qtyTimer);
            that.qtyTimer = setTimeout(function () {
                btn.closest("form").change();
            }, 100);
        });

        /* При изменении блока с корзиной обновить все блоки */
        $(document).on('wa_order_cart_rendered', '#js-order-cart', function () {
            that.cartChange();
        });

        /* Отслеживаем изменение корзины */
        if (!that.isOnestepCheckout) {
            $(document).ajaxComplete(function (e, jqXHR, options) {
                    if (typeof options !== 'undefined' && typeof options.url !== 'undefined' &&
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
                }
            );
        }

    };

    /* Вспомогательная функция, определяющая заканчивается ли строка str строкой searchStr. Работает в ослике */
    FlexdiscountPluginFrontend.prototype.endsWith = function (str, searchStr) {
        return str.substring(str.length - searchStr.length, str.length) === searchStr;
    };

    /* Обновить блоки Гибких скидок для товаров */
    FlexdiscountPluginFrontend.prototype.updateProductRules = function (productIds) {
        var that = this;

        if (!that.updateInfoblocks) {
            return;
        }

        /* Собираем типы отображения */
        function collectDiscountTypes(blocks) {
            var types = {};
            if (blocks.length) {
                blocks.each(function () {
                    var block = $(this);
                    var sku = block.hasClass("f-update-sku") ? 'find' : block.attr('data-sku-id');
                    var viewType = block.attr('data-view-type') !== undefined ? block.attr('data-view-type') : 0;
                    if (typeof (types[sku]) === 'undefined') {
                        types[sku] = {};
                    }
                    types[sku][viewType] = block.hasClass('flexdiscount-available-discount') && block.attr('data-filter-by') !== undefined ? block.attr('data-filter-by') : viewType;
                    that.addLoader(block);
                });
            }
            return types;
        }

        var firstProduct = null,
            count = 0,
            xhr = {}, availableBlocks = {}, pdBlocks = {}, priceBlocks = {}, denyBlocks = {}, data = {};

        /* Формируем данные для отправки */
        $.each(productIds, function (productId, form) {
            if (firstProduct === null) {
                firstProduct = productId;
            }

            var quantity = form.find("input[name='quantity']").val();
            quantity = quantity || 1;
            var wrap = that.findWrap(form);

            /* Доступные скидки */
            availableBlocks[productId] = wrap.find(".flexdiscount-available-discount.product-id-" + productId);
            var availTypes = collectDiscountTypes(availableBlocks[productId]);

            /* Действующие скидки */
            pdBlocks[productId] = wrap.find(".flexdiscount-product-discount.product-id-" + productId);
            var pdTypes = collectDiscountTypes(pdBlocks[productId]);

            /* Цена со скидкой */
            priceBlocks[productId] = wrap.find(".flexdiscount-price-block.product-id-" + productId);
            var priceTypes = collectDiscountTypes(priceBlocks[productId]);

            /* Запрещающие скидки */
            denyBlocks[productId] = wrap.find(".flexdiscount-deny-discount.product-id-" + productId);
            var denyTypes = collectDiscountTypes(denyBlocks[productId]);

            data[productId] = {
                quantity: quantity,
                available_discounts: availTypes,
                p_discounts: pdTypes,
                price_blocks: priceTypes,
                deny_discounts: denyTypes,
                params: form.serialize()
            };

            /* Прерываем предыдущие запросы */
            if (that.xhr[productId] !== undefined && !$.isEmptyObject(that.xhr[productId]) && that.xhr[productId].readyState != 4) {
                that.xhr[productId].abort();
            }
            count++;
        });

        if (count === 1) {
            xhr = that.xhr;
        }

        xhr[firstProduct] = $.ajax({
            type: 'post',
            url: that.urls.updateDiscountUrl,
            cache: false,
            dataType: "json",
            beforeSend: function (jqXHR) {
                if (count === 1) {
                    that.xhr[firstProduct] = jqXHR;
                }
            },
            data: {
                products: data
            },
            success: function (response) {
                if (response.status == 'ok' && response.data && typeof response.data.products !== 'undefined') {
                    $.each(response.data.products, function (product_id, product) {

                        /* Доступные скидки */
                        that.updateDiscountBlocks(availableBlocks[product_id], 'available_discounts', product, product_id, response);

                        /* Действующие скидки */
                        that.updateDiscountBlocks(pdBlocks[product_id], 'p_discounts', product, product_id, response);

                        /* Цена со скидкой */
                        that.updateDiscountBlocks(priceBlocks[product_id], 'price_blocks', product, product_id, response);

                        /* Запрещающие скидки */
                        that.updateDiscountBlocks(denyBlocks[product_id], 'deny_discounts', product, product_id, response);

                    });
                } else {
                    that.removeLoader($(document));
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                if (textStatus !== 'abort') {
                    if (console) {
                        console.log(jqXHR, textStatus, errorThrown);
                    }
                    that.removeLoader($(document));
                }
            },
            complete: function () {
                delete xhr[firstProduct];
            }
        });
    };

    /* Обновить все выведенные блоки Гибких скидок на странице  */
    FlexdiscountPluginFrontend.prototype.updateAllRules = function (wrap) {
        var that = this;

        if (!that.updateInfoblocks) {
            return;
        }

        /* Формируем данные для отправки */
        function createPostData(blocks) {
            var data = {};
            if (blocks.length) {
                blocks.each(function () {
                    var block = $(this);
                    var blockType = block.hasClass("flexdiscount-available-discount") ? 'available_discounts' : (block.hasClass("flexdiscount-product-discount") ? 'p_discounts' : (block.hasClass("flexdiscount-price-block") ? 'price_blocks' : (block.hasClass("flexdiscount-deny-discount") ? 'deny_discounts' : '')));
                    var productId = block.attr('data-product-id') !== undefined ? parseInt(block.attr('data-product-id')) : 0;
                    if (productId > 0 && blockType !== '') {
                        if (typeof (data[productId]) === 'undefined') {
                            var wrap = that.findWrap(block, '');
                            var form = wrap.length ? wrap.find('form') : block;
                            data[productId] = {
                                quantity: form.find("input[name='quantity']").length ? form.find("input[name='quantity']").val() : 1,
                                available_discounts: {},
                                p_discounts: {},
                                price_blocks: {},
                                deny_discounts: {},
                                params: form.serialize()
                            };
                        }
                        var sku = block.hasClass("f-update-sku") ? 'find' : block.attr('data-sku-id');
                        var viewType = block.attr('data-view-type') !== undefined ? block.attr('data-view-type') : 0;
                        if (typeof (data[productId][blockType][sku]) === 'undefined') {
                            data[productId][blockType][sku] = {};
                        }

                        data[productId][blockType][sku][viewType] = block.hasClass('flexdiscount-available-discount') && block.attr('data-filter-by') !== undefined ? block.attr('data-filter-by') : viewType;
                        that.addLoader(block);
                    }
                });
            }
            return data;
        }

        wrap = wrap || $(document);

        var availableBlocks = wrap.find(".flexdiscount-available-discount");
        var pdBlocks = wrap.find(".flexdiscount-product-discount");
        var priceBlocks = wrap.find(".flexdiscount-price-block");
        var denyBlocks = wrap.find(".flexdiscount-deny-discount");
        var allBlocks = availableBlocks.add(pdBlocks).add(priceBlocks).add(denyBlocks);

        var data = createPostData(allBlocks);

        /* Прерываем предыдущие запросы */
        if (!$.isEmptyObject(that.xhr)) {
            that.xhr.readyState != 4 && that.xhr.abort();
        }

        that.xhr = $.ajax({
            type: 'post',
            url: that.urls.updateDiscountUrl,
            cache: false,
            dataType: "json",
            data: {
                products: data
            },
            success: function (response) {
                if (response.status == 'ok' && response.data && typeof response.data.products !== 'undefined') {
                    $.each(response.data.products, function (product_id, product) {
                        /* Доступные скидки */
                        that.updateDiscountBlocks(availableBlocks.filter(".product-id-" + product_id), 'available_discounts', product, product_id, response);

                        /* Действующие скидки */
                        that.updateDiscountBlocks(pdBlocks.filter(".product-id-" + product_id), 'p_discounts', product, product_id, response);

                        /* Цена со скидкой */
                        that.updateDiscountBlocks(priceBlocks.filter(".product-id-" + product_id), 'price_blocks', product, product_id, response);

                        /* Запрещающие скидки */
                        that.updateDiscountBlocks(denyBlocks.filter(".product-id-" + product_id), 'deny_discounts', product, product_id, response);

                    });
                } else {
                    that.removeLoader($(document));
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                if (textStatus !== 'abort') {
                    if (console) {
                        console.log(jqXHR, textStatus, errorThrown);
                    }
                    that.removeLoader($(document));
                }
            },
            complete: function () {
                that.xhr = {};
            }
        });
    };

    FlexdiscountPluginFrontend.prototype.removeLoading = function () {
        var loading = $(".icon16-flexdiscount.loading");
        loading.length && loading.remove();
    };

    FlexdiscountPluginFrontend.prototype.addLoading = function (elem) {
        elem.after("<i class='icon16-flexdiscount loading'></i>");
    };

    FlexdiscountPluginFrontend.prototype.hasLoading = function (elem) {
        return !!elem.next(".icon16-flexdiscount.loading").length;
    };

    FlexdiscountPluginFrontend.prototype.hideLoading = function (elem) {
        elem.next(".icon16-flexdiscount.loading").remove();
    };

    /* Добавление иконки загрузки для блока */
    FlexdiscountPluginFrontend.prototype.addLoader = function (block) {
        var that = this;

        that.removeLoader(block);
        switch (that.loaderType) {
            case 'loader1':
                var loader = '<div class="align-center flexdiscount-loader" style="display: block"><i class="flexdiscount-big-loading"></i></div>';
                if (block.find(".flexdiscount-interactive").length) {
                    block.find(".flexdiscount-interactive").hide().before(loader);
                } else {
                    block.children().hide();
                    block.append(loader);
                }
                break;
            case 'loader2':
                block.addClass("fl-is-loading");
                break;
            case 'loader3':
                block.addClass("fl-is-loading fl-loader-2");
                break;

        }
    };

    /* Удаление иконки загрузки для блока */
    FlexdiscountPluginFrontend.prototype.removeLoader = function (block) {
        var that = this;

        if (that.loaderType == 'loader1') {
            if (block.find(".flexdiscount-interactive").length) {
                block.find(".flexdiscount-interactive").show();
            } else {
                block.children().show();
            }
            block.find(".flexdiscount-loader").remove();
        } else {
            block.removeClass("fl-is-loading fl-loader-2");
        }
    };

    /* Обновляем блоки свежими данными */
    FlexdiscountPluginFrontend.prototype.updateDiscountBlocks = function (blocks, block_id, product, product_id, response) {
        var that = this;

        if (blocks && blocks.length) {
            blocks.each(function () {
                var block = $(this);
                var sku_id = block.hasClass("f-update-sku") ? response.data.product_skus[product_id] : block.attr('data-sku-id');
                var viewType = block.attr('data-view-type') !== undefined ? block.attr('data-view-type') : 0;
                if (product[sku_id] !== undefined
                    && product[sku_id][block_id] !== undefined
                    && product[sku_id][block_id][viewType] !== undefined) {
                    block.html($(product[sku_id][block_id][viewType]).html());
                    if ($(product[sku_id][block_id][viewType]).hasClass('flexdiscount-show')) {
                        block.css('display', '');
                    } else if ($(product[sku_id][block_id][viewType]).hasClass('flexdiscount-hide')) {
                        block.hide();
                    }
                    if (block.hasClass("f-update-sku")) {
                        block.attr("data-sku-id", sku_id);
                    }
                }
                that.removeLoader(block);
            });
        }
    };

    /* Поиск элемента-обертки для товара */
    FlexdiscountPluginFrontend.prototype.findWrap = function (form, defVal) {
        var that = this;

        var wrap = form.closest(".flexdiscount-product-wrap, .igaponov-product-wrap" + (that.wrapElements.length ? ', ' + that.wrapElements.join(',') : ''));
        if (!wrap.length) {
            var autowrap = form.closest('.s-products-list, .product-list, .lazy-wrapper, ul.thumbnails, .product-thumbs, .product-list');
            if (autowrap.length) {
                wrap = $("> li, > div, > tr", autowrap);
            }
            if (wrap.length) {
                wrap.addClass('igaponov-product-wrap');
            }
        }
        return wrap.length ? wrap : (typeof defVal !== 'undefined' ? defVal : $(document));
    };

    /* Вызывать метод после изменения товаров в корзине */
    FlexdiscountPluginFrontend.prototype.cartChange = function () {
        var that = this;

        /* Обновляем примененные скидки и бонусы */
        function refreshCartBlocks(blocks) {
            var userDiscounts = $(".flexdiscount-user-discounts, .flexdiscount-user-affiliate");
            if (userDiscounts.length && (blocks.discounts !== undefined || blocks.affiliate !== undefined)) {
                userDiscounts.each(function () {
                    var elem = $(this),
                        type = elem.hasClass("flexdiscount-user-discounts") ? 'discounts' : 'affiliate';

                    /* Пропускаем блоки плагина Купить в 1 клик */
                    if (type == 'discounts' && elem.parent('.quickorder-fl-ad').length) {
                        return true;
                    }

                    if (blocks[type][elem.attr("data-view-type")] !== undefined) {
                        elem.replaceWith(blocks[type][elem.attr("data-view-type")]);
                    }
                });
            }
            that.removeLoader(userDiscounts);
        }

        var items = $(".flexdiscount-cart-item"),
            fields = $(".flexdiscount-cart-price"), // Deprecated. TODO удалить
            userDiscounts = $(".flexdiscount-user-discounts, .flexdiscount-user-affiliate"),
            data = {
                fields: {}, // Deprecated. TODO удалить
                items: {},
                user_discounts: {},
                affiliate_block: {}
            },
            stop = 1;
        that.hideLoading(items);
        that.hideLoading(fields); // Deprecated. TODO удалить

        clearTimeout(that.cartTimer);
        that.cartTimer = setTimeout(function () {

            if (items.length) {
                items.each(function (i) {
                    var updateField = $(this);
                    if (updateField.length) {
                        updateField.addClass("c" + i);
                        if (typeof data['items'][updateField.data("cart-id")] === 'undefined') {
                            data['items'][updateField.data("cart-id")] = {};
                        }
                        var params = updateField.data("params") || {};
                        data['items'][updateField.data("cart-id")][updateField.data("block-id")] = {
                            "field": updateField.data("field"),
                            "params": params,
                        };
                        !params.remove_loader && that.addLoading(updateField);
                    }
                });
                stop = 0;
            }

            // Deprecated. TODO удалить
            if (fields.length) {
                fields.each(function (i) {
                    var updateField = $(this);
                    if (updateField.length) {
                        updateField.addClass("c" + i);
                        if (typeof data['fields'][updateField.data("cart-id")] === 'undefined') {
                            data['fields'][updateField.data("cart-id")] = {};
                        }
                        data['fields'][updateField.data("cart-id")]["c" + i] = {
                            "mult": updateField.data("mult"),
                            "html": updateField.data("html"),
                            "format": updateField.data("format")
                        };
                    }
                });
                that.addLoading(fields);
                stop = 0;
            }

            if (userDiscounts.length) {
                userDiscounts.each(function () {
                    var elem = $(this);
                    var type = elem.hasClass("flexdiscount-user-discounts") ? 'user_discounts' : 'affiliate_block';

                    /* Пропускаем блоки плагина Купить в 1 клик */
                    if (type == 'discounts' && elem.parent('.quickorder-fl-ad').length) {
                        return true;
                    }

                    data[type][elem.attr('data-view-type')] = elem.attr('data-view-type');
                    that.addLoader(elem);
                });
                stop = 0;
            }

            /* Заменяем стандартное поле бонусов на загрузчик для дальнейшего обновления бонусов*/
            if (that.$affiliateParentBlock.length && !that.hideDefaultAffiliateBlock && !that.isOnestepCheckout) {
                that.$affiliateParentBlock.each(function (i, elem) {
                    elem = $(elem);
                    var strongElem = elem.find('strong');
                    if (strongElem.length) {
                        strongElem.html(strongElem.html().replace(/\+\s*\d+\.?\d*/, "<i class=\"icon16-flexdiscount loading fl-replace-affiliate\"><\/i>"));
                        stop = 0;
                    } else {
                        if (elem.text().search(/\+\s*\d+\.?\d*/) !== -1) {
                            elem.html(elem.html().replace(/\+\s*\d+\.?\d*/, "<i class=\"icon16-flexdiscount loading fl-replace-affiliate\"><\/i>"));
                            stop = 0;
                        }
                    }
                });
            }

            if (!stop) {
                /* Прерываем предыдущие запросы */
                if (!$.isEmptyObject(that.xhrCart)) {
                    that.xhrCart.readyState != 4 && that.xhrCart.abort();
                }

                that.xhrCart =
                    $.ajax({
                        type: 'post',
                        url: that.urls.refreshCartUrl,
                        cache: false,
                        dataType: "json",
                        data: { data: data },
                        success: function (response) {
                            that.hideLoading(fields); // Deprecated. TODO удалить
                            that.hideLoading(items);
                            if (response.status == 'ok' && response.data) {

                                // Deprecated. TODO удалить
                                if (typeof response.data.fields !== 'undefined') {
                                    $.each(response.data.fields, function (i, v) {
                                        var $elem = $(v.elem);
                                        $elem.removeClass(v.removeClass).html($(v.price).html());
                                        $elem.data('price', v.clear_price).attr('data-price', v.clear_price);
                                        $elem.data('product-price', v.clear_product_price).attr('data-product-price', v.clear_product_price);
                                    });
                                }

                                if (typeof response.data.items !== 'undefined') {
                                    $.each(response.data.items, function (blockId, html) {
                                        $('.flexdiscount-cart-item[data-block-id="' + blockId + '"]').replaceWith(html);
                                    });
                                }
                                if (typeof response.data.blocks !== 'undefined') {
                                    refreshCartBlocks(response.data.blocks);
                                }
                                if ($('.fl-replace-affiliate').length && !that.hideDefaultAffiliateBlock) {
                                    if (typeof response.data.clear_affiliate !== 'undefined' && response.data.clear_affiliate !== 0) {
                                        $('.fl-replace-affiliate').siblings('.fl-affiliate-holder').remove().end().replaceWith('+' + response.data.clear_affiliate + '<span class="fl-affiliate-holder" style="display:none"></span>');
                                        that.findAffiliateParent().show();
                                    } else {
                                        $('.fl-replace-affiliate').siblings('.fl-affiliate-holder').remove().end().replaceWith('+0 <span class="fl-affiliate-holder" style="display:none"></span>');
                                        that.findAffiliateParent().hide();
                                    }
                                }
                            }
                            $(document).trigger('flexdiscount-cart-updated');
                        },
                        error: function (jqXHR, textStatus, errorThrown) {
                            if (textStatus !== 'abort') {
                                if (console) {
                                    console.log(jqXHR, textStatus, errorThrown);
                                }
                                if ($('.fl-replace-affiliate').length && !that.hideDefaultAffiliateBlock) {
                                    that.findAffiliateParent().hide();
                                }
                            }
                        },
                        complete: function () {
                            that.xhrCart = {};
                        }
                    });
            }
        }, 100);
    };

    FlexdiscountPluginFrontend.prototype.findAffiliateParent = function () {
        var that = this;

        that.$affiliateParentBlock = $();

        if (that.isOnestepCheckout) {
            var Cart = $('#js-order-cart').data('controller');
            if (Cart !== undefined && Cart.$affiliate_section.length) {
                that.$affiliateParentBlock = Cart.$affiliate_section.find('#wa-affiliate-order-bonus');
            }
        } else {
            var defaultAffiliateBlock = that.cartAffiliateBlock.length ? $("" + that.cartAffiliateBlock.join(',')) : '';
            $('.fl-affiliate-holder').each(function () {
                var parent = $(this).closest(defaultAffiliateBlock);
                if (!parent.length) {
                    parent = $(this).parent();
                    if (parent.is('strong')) {
                        parent = parent.parent();
                    }
                }
                that.$affiliateParentBlock = that.$affiliateParentBlock.add(parent);
            });
        }
        return that.$affiliateParentBlock;
    };

    FlexdiscountPluginFrontend.prototype.translate = function (message) {
        var that = this;
        if (typeof that.messages[that.locale] !== 'undefined' && that.messages[that.locale][message]) {
            return that.messages[that.locale][message];
        }
        return message;
    };

    return FlexdiscountPluginFrontend;

})
(jQuery);
