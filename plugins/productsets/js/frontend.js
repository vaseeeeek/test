(function ($) {
    const CLASSES = {
        UNINITIALIZED_SET: '.productsets-wrap.not-inited',
        LOADING: 'is-loading',
        LOCKED: 'p-is-locked',
        DISABLED: 'p-disabled',
        SET: '.productsets-wrap',
        ALTERNATIVE_BLOCK: '.productsets-alternative',
        BUNDLE_WRAP: '.productsets-wrap-inner',
        ACTIVE_BUNDLE: '.slick-active .productsets-bundle',
        BUNDLE_ITEM: '.productsets-bundle-item',
        ITEM: '.productsets-item',
        ITEM_STATUS_CHECKBOX: '.productsets-checkbox input',
        ITEM_IMAGE: '.productsets-item-image',
        ITEM_NAME: '.productsets-item-name',
        ITEM_SKU: '.productsets-item-sku',
        ALTERNATIVE_ITEM: '.js-productsets-init-alt-slider',
        ACTIVE_ALTERNATIVE_ITEM: '.slick-active .productsets-item',
        SLIDE: '.slick-slide',
        INACTIVE: 'not-in-stock',
        DELETED: 'item-deleted',
        ONE_VARIANT: 'only-one-variant',
        QUANTITY_FLD: '.productsets-item-quantity input',
        PRICE_FLD: '.productsets-price',
        BADGE: '.productsets-badge span',
        BADGE_FLD: '.productsets-badge',
        COMPARE_PRICE_FLD: '.productsets-compare-price',
        TOTAL_PRICE: '.productsets-total-price',
        TOTAL_WRAP: '.productsets-total-wrap',
        TOTAL_COMPARE_PRICE: '.productsets-total-compare-price',
        TOTAL_DISCOUNT: '.productsets-total-discount',
        TOTAL_DISCOUNT_WRAP: '.productsets-total-discount-wrap',
        TOTAL_ITEMS: '.productsets-total-items',
        TOTAL_ITEM: '.productsets-total-item',
        TOTAL_ITEM_DELETE: '.pti-icon',
        BUY_BUTTON: '.productsets-bundle-buy',
        BUTTON_LOADING: '.productsets-loading',
        UNINITIALIZED_BUNDLE: '.productsets-bundle.not-inited',
        UNINITIALIZED_USERBUNDLE: '.productsets-userbundle-wrap.not-inited',
        USERBUNDLE_WRAP: '.productsets-userbundle-wrap',
        USERBUNDLE_TOTAL_WRAP: '.productsets-userbundle-total-wrap',
        USERBUNDLE_TOTAL: '.productsets-userbundle-total',
        USERBUNDLE_TOTAL_PRICE: '.productsets-userbundle-total-price',
        USERBUNDLE_TOTAL_DISCOUNT_WRAP: '.productsets-userbundle-saving-wrap',
        USERBUNDLE_TOTAL_DISCOUNT: '.productsets-userbundle-saving',
        USERBUNDLE_TOTAL_QUANTITY: '.productsets-userbundle-count',
        USERBUNDLE_INFO: '.productsets-userbundle-info',
        USERBUNDLE_BODY: '.productsets-userbundle-body',
        USERBUNDLE_ADD2CART_ITEM: '.productsets-userbundle-add',
        USERBUNDLE_ADD2CART_ITEM_TEXT: '.productsets-userbundle-add span',
        USERBUNDLE_THUMBS: '.productsets-userbundle-thumbs',
        USERBUNDLE_GROUP: '.productsets-userbundle-group-wrap',
        USERBUNDLE_BUY_BUTTON: '.productsets-userbundle-buy',
        BUNDLES_WRAP: '.productsets-bundles-wrap',
        SLICK_INITIALIZED: '.slick-initialized',
        CONTROLLER: '.productsets-controller',
        POPUP: '.productsets-popup',
        POPUP_CONTENT: '.productsets-popup-content',
        ERROR_BLOCK: '.productsets-error-block',
        SELECTED_USERITEM: '.productsets-item.locked, .productsets-item.added',
        FIXED: 'ps-fixed'
    };
    $.productsets = {
        init: function (options) {
            var that = this;

            that.initOptions(options);

            let sources = [{
                id: "igaponov-dialog-css",
                type: "css",
                uri: that.PLUGIN_URL + "js/dialog/jquery.dialog.min.css"
            }, {
                id: "igaponov-dialog-js",
                type: "js",
                uri: that.PLUGIN_URL + "js/dialog/jquery.dialog.min.js"
            }, {
                id: "slick-jquery-js",
                type: "js",
                uri: that.PLUGIN_URL + "js/slick/slick.min.js"
            }, {
                id: "translate-js",
                type: "js",
                uri: that.PLUGIN_URL + "js/translate.js"
            }, {
                id: "slick-jquery-css",
                type: "css",
                uri: that.PLUGIN_URL + "js/slick/slick.min.css"
            }, {
                id: "slick-theme-jquery-css",
                type: "css",
                uri: that.PLUGIN_URL + "js/slick/slick-theme.css"
            }];

            that.loadSources(sources).then(function () {
                /* Устанавливаем локализацию текста */
                if (typeof window.productsetsJsLocale === 'object') {
                    that.localeStrings = $.extend(that.localeStrings, window.productsetsJsLocale);
                }
                window.__ = puttext(that.localeStrings);

                /* Открытие всплывающей формы */
                $(document).on('click', '[data-productsets-userbundle-button]', function () {
                    that.show($(this));
                    return false;
                });

                /* Пытаемся подстроиться под "Быстрые просмотры" */
                $(document).on("change", "form", function () {
                    setTimeout(function () {
                        that.initSets();
                        that.resize();
                    }, 100);
                });

                /* Отслеживаем изменение экрана */
                $(window).resize(function () {
                    that.resize();
                });
                that.resize();

                that.initSets();
            });
        },
        /* Определяем ширину родительского элемента */
        resize: function () {
            $(CLASSES.SET).each(function () {
                var elem = $(this),
                    parentW = elem.parent().width();

                elem.removeClass('productsets320 productsets420 productsets520 productsets570 productsets650 productsets740 productsets850');
                if (parentW <= 320) {
                    elem.addClass("productsets320 productsets420 productsets520 productsets570 productsets650 productsets740 productsets850");
                } else if (parentW <= 420) {
                    elem.addClass("productsets420 productsets520 productsets570 productsets650 productsets740 productsets850");
                } else if (parentW <= 520) {
                    elem.addClass("productsets520 productsets570 productsets650 productsets740 productsets850");
                } else if (parentW <= 570) {
                    elem.addClass("productsets570 productsets650 productsets740 productsets850");
                } else if (parentW <= 650) {
                    elem.addClass("productsets650 productsets740 productsets850");
                } else if (parentW <= 740) {
                    elem.addClass("productsets740 productsets850");
                } else if (parentW > 740 && parentW <= 850) {
                    elem.addClass("productsets850");
                }
            });

            $(CLASSES.POPUP).each(function () {
                $(this).data('controller') && $(this).data('controller').position();
            })
        },
        show: function (btn) {
            var that = this;

            var data = {
                product_id: btn.data('productsets-product-id'),
                category_id: btn.data('category'),
                set_id: btn.data('productsets-set-id')
            };

            var xhr = false;
            new igaponovDialog({
                loadingContent: true,
                class: 'productsets-dialog',
                closeBtn: true,
                onOpen: function ($wrapper, dialog) {
                    xhr = $.post(that.urls['load'], data, function (response) {
                        if (response.status == 'ok' && response.data.length) {
                            $wrapper.addClass('loaded productsets-dialog-wrapper');
                            dialog.$block.html(response.data);
                            dialog.addCloseBtn();

                            that.initSets();
                        } else {
                            addEmptyBlock(dialog);
                        }
                    }).always(function () {
                        /* Если данные не загрузились */
                        if (!$wrapper.find(".productsets-wrap").length) {
                            addEmptyBlock(dialog);
                            $wrapper.removeClass('loaded');
                        }
                    });
                },
                onBgClick: function (e, d) {
                    closePopup(e, d);
                    if (void 0 !== btn.data('popup-close') || !d.$wrapper.hasClass('loaded')) {
                        d.close();
                    }
                },
                onBlockClick: function (e, dialog) {
                    /* Закрываем всплывающее окно успешного оформления или выбора артикулов при нажатии в любую иную область */
                    closePopup(e, dialog);
                    e.stopPropagation();
                },
                onClose: function () {
                    xhr && xhr.abort();
                }
            });

            /* Закрываем всплывающее окно успешного оформления или выбора артикулов при нажатии в любую иную область */
            function closePopup(e, dialog) {
                if (!$(e.target).closest(CLASSES.POPUP).length) {
                    var popup = dialog.$wrapper.find(CLASSES.POPUP).not('.' + CLASSES.LOADING);
                    if (popup.length) {
                        /* При наличии сообщения об успешном отправлении комплекта, закрываем окно пользовательского выбора набора  */
                        if (popup.hasClass('is-success')) {
                            that.closeUserbundlePopup(popup);
                        } else {
                            popup.data('controller').close();
                        }
                    }
                }
            }

            function addEmptyBlock(dialog) {
                dialog.$block.html(__('The set is empty'));
                dialog.$wrapper.addClass('empty-block');
            }
        },
        /* Закрываем окно пользовательского выбора набора */
        closeUserbundlePopup: function (anyElem) {
            let igapDialog = anyElem.closest('.productsets-dialog-wrapper');
            if (igapDialog.length) {
                igapDialog.data('igaponovDialog').close();
            }
        },
        initOptions: function (options) {
            var that = this;

            that.PLUGIN_URL = options.PLUGIN_URL || '';
            that.currency = options.currency || {};
            that.urls = options.urls || {};
            that.options = options || {};
            that.localeStrings = options.localeStrings || {};
        },
        initSets: function () {
            /* Инициализация комплектов */
            $(CLASSES.UNINITIALIZED_SET).each(function () {
                var $set = $(this);
                if ($set.data('controller') === undefined) {
                    new ProductsetsFrontendSet($set);
                }
            });
        },
        loadSources: function (sources) {
            var deferred = $.Deferred();

            loader(sources).then(function () {
                deferred.resolve();
            }, function (bad_sources) {
                if (console && console.error) {
                    console.error("Error loading resource", bad_sources);
                }
                deferred.reject(bad_sources);
            });

            return deferred.promise();

            function loader(sources) {
                var deferred = $.Deferred(),
                    counter = sources.length;

                var bad_sources = [];

                $.each(sources, function (i, source) {
                    switch (source.type) {
                        case "css":
                            loadCSS(source).then(onLoad, onError);
                            break;
                        case "js":
                            loadJS(source).then(onLoad, onError);
                            break;
                    }
                });

                return deferred.promise();

                function loadCSS(source) {
                    var deferred = $.Deferred(),
                        promise = deferred.promise();

                    var $link = $("#" + source.id);
                    if ($link.length) {
                        deferred.resolve(source);
                    } else {
                        $link = $("<link />", {
                            id: source.id,
                            rel: "stylesheet"
                        }).appendTo("head")
                            .data("promise", promise);

                        $link
                            .on("load", function () {
                                deferred.resolve(source);
                            }).on("error", function () {
                            deferred.reject(source);
                        });

                        $link.attr("href", source.uri);
                    }

                    if (typeof promise !== 'object' || (promise === 'object' && typeof promise.then !== "function")) {
                        promise = deferred.promise();
                    }
                    return promise;
                }

                function loadJS(source) {
                    var deferred = $.Deferred(),
                        promise = deferred.promise();

                    var $script = $("#" + source.id);
                    if ($script.length) {
                        deferred.resolve(source);
                    } else {
                        var script = document.createElement("script");
                        document.getElementsByTagName("head")[0].appendChild(script);

                        $script = $(script)
                            .attr("id", source.id)
                            .data("promise", promise);

                        $script
                            .on("load", function () {
                                deferred.resolve(source);
                            }).on("error", function () {
                            deferred.reject(source);
                        });

                        $script.attr("src", source.uri);
                    }

                    if (typeof promise !== 'object' || (promise === 'object' && typeof promise.then !== "function")) {
                        promise = deferred.promise();
                    }

                    return promise;
                }

                function onLoad(source) {
                    counter -= 1;
                    watcher();
                }

                function onError(source) {
                    bad_sources.push(source);
                    counter -= 1;
                    watcher();
                }

                function watcher() {
                    if (counter === 0) {
                        if (!bad_sources.length) {
                            deferred.resolve();
                        } else {
                            deferred.reject(bad_sources);
                        }
                    }
                }
            }
        },
        currencyFormat: function (number, no_html) {
            /* Format a number with grouped thousands

             +   original by: Jonas Raoni Soares Silva (http://www.jsfromhell.com)
             +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
             +	 bugfix by: Michael White (http://crestidg.com) */

            var i, j, kw, kd, km;
            var decimals = this.currency.frac_digits;
            var dec_point = this.currency.decimal_point;
            var thousands_sep = this.currency.thousands_sep;

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


            var number = km + kw + kd;
            var s = no_html ? this.currency.sign : this.currency.sign_html;
            if (!this.currency.sign_position) {
                return s + this.currency.sign_delim + number;
            } else {
                return number + this.currency.sign_delim + s;
            }
        }
    };

    var ProductsetsFrontendSet = (function () {

        ProductsetsFrontendSet = function ($set) {
            var that = this;

            that.xhrSkus = false;
            that.$set = $set;
            that.setId = $set.data('id');
            that.rubleSign = $set.data('ruble');
            that.rubleSign = (that.rubleSign == 'rub') ? 1 : 0;


            that.loading = '<i class="productsets-loading"></i>';
            that.validateParams = {
                popupErrors: 1,
                $set: that.$set
            };

            /**
             *  Тонкая настройка. Доступные параметры:
             *  - (float) stickyTopOffset - значение, которое будет прибавлено к верхнему отсутупу прилипающего блока.
             *  Удобно использовать, когда на сайте имеется фиксированный блок, который перекрывается плагином
             */

            that.extraParams = {
                stickyTopOffset: 150
            };
            if (typeof window.productsetsExtraParams === 'object') {
                that.extraParams = $.extend(that.extraParams, window.productsetsExtraParams);
            }

            that.initClass();
            that.bindEvents();

            that.$set.removeClass('not-inited');
        };

        ProductsetsFrontendSet.prototype.initClass = function () {
            var that = this;

            that.$set.data('controller', that);

            that.initBundles();
        };

        /* Инициализация наборов */
        ProductsetsFrontendSet.prototype.initBundles = function () {
            var that = this;

            that.initBundlesSlider();

            /* Инициализация наборов */
            that.$set.find(CLASSES.UNINITIALIZED_BUNDLE).each(function () {
                new ProductsetsFrontendBundle($(this));
            });

            /* Инициализация пользовательских наборов */
            that.$set.find(CLASSES.UNINITIALIZED_USERBUNDLE).each(function () {
                new ProductsetsFrontendUserBundle($(this));
            });
        };

        /* Слайдер наборов */
        ProductsetsFrontendSet.prototype.initBundlesSlider = function () {
            var that = this;

            that.$set.find(CLASSES.BUNDLES_WRAP).not(CLASSES.SLICK_INITIALIZED).each(function () {
                var $elem = $(this);
                $elem.on('init afterChange', function (e, slick) {
                    $elem.removeClass('pl0 pr0');
                    if ($elem.find('.productsets-next').hasClass('slick-disabled')) {
                        $elem.addClass('pr0');
                    } else if ($elem.find('.productsets-prev').hasClass('slick-disabled')) {
                        $elem.addClass('pl0');
                    } else if (!$elem.find('.productsets-next').length && !$elem.find('.productsets-prev').length) {
                        $elem.addClass('pl0 pr0');
                    }
                    setTimeout(function () {
                        that.update();
                        slick.setPosition();
                        $elem.find('.js-productsets-init-alt-slider').each(function () {
                            $(this).productsetsSlick('setPosition');
                        });
                    }, 30);
                }).productsetsSlick({
                    slidesToShow: 1,
                    slidesToScroll: 1,
                    infinite: false,
                    prevArrow: '<div class="productsets-prev" ' + $.productsets.options.attr.sliderWrapArrow + '><svg viewBox="0 0 20.3 32.2" class="productsets-icon aleft" ' + $.productsets.options.attr.sliderArrow + '>' +
                        '<use xlink:href="' + $.productsets.PLUGIN_URL + 'img/svg/arrows.svg#arrow-left"></use>' +
                        '</svg></div></div>',
                    nextArrow: '<div class="productsets-next" ' + $.productsets.options.attr.sliderWrapArrow + '><svg viewBox="0 0 20.3 32.2" class="productsets-icon aright" ' + $.productsets.options.attr.sliderArrow + '>' +
                        '    <use xlink:href="' + $.productsets.PLUGIN_URL + 'img/svg/arrows.svg#arrow-right"></use>' +
                        '  </svg></div>',
                    appendArrows: $elem,
                    draggable: false,
                    lazyLoad: 'ondemand',
                    cssEase: 'linear',
                    adaptiveHeight: true
                });
            });
        };

        ProductsetsFrontendSet.prototype.bindEvents = function () {
            var that = this;

            /* Оформление заказа */
            $(document).off('click', CLASSES.BUY_BUTTON).on('click', CLASSES.BUY_BUTTON, function () {
                that.submitForm($(this).closest(CLASSES.SET).find(CLASSES.ACTIVE_BUNDLE).data('controller'));
            });

            /* Закрываем всплывающее окно успешного оформления или выбора артикулов при нажатии в любую иную область */
            $(document).off('click.productsets-popup').on('click.productsets-popup', function (e) {
                if (!$(e.target).closest(CLASSES.POPUP).length) {
                    var popup = $(e.target).closest(CLASSES.SET).find(CLASSES.POPUP).not('.' + CLASSES.LOADING);
                    popup.length && popup.data('controller').close();
                }
            });

            /* Изменение количества товара */
            $(document).off('click', CLASSES.BUNDLES_WRAP + ' .js-productsets-quantity-trigger').on('click', CLASSES.BUNDLES_WRAP + ' .js-productsets-quantity-trigger', function () {
                const elem = this;
                that.changeQuantity(elem, $(elem).data('type') != 'minus');
            });
            /* Проверка измененного значения количества */
            $(document).off('change', CLASSES.BUNDLES_WRAP + ' ' + CLASSES.QUANTITY_FLD).on('change', CLASSES.BUNDLES_WRAP + ' ' + CLASSES.QUANTITY_FLD, function () {
                that.onQuantityInputChange($(this));
            });

            /* Появление окна для выбора другой вариации товара */
            $(document).off('click', CLASSES.BUNDLES_WRAP + ' .js-productsets-product-skus').on('click', CLASSES.BUNDLES_WRAP + ' .js-productsets-product-skus', function () {
                that.showSkusPopup($(this));
            });

            /* Закрытие всплывающего окна артикулов */
            that.$set.on('closeSkus', CLASSES.POPUP, function () {
                $(this).remove();
                that.$set.removeClass('p-loading');
                if (that.xhrSkus) {
                    that.xhrSkus.abort();
                }
                that.xhrSkus = false;
            });
        };

        ProductsetsFrontendSet.prototype.onScroll = function (sticky, block, stickyClass) {
            var that = this;

            var stickyOffset = sticky.not('.' + CLASSES.FIXED + (stickyClass ? '.' + stickyClass : '')).offset(),
                stickyHeight = sticky.outerHeight() + (stickyClass ? 20 : 0),
                blockHeight = block.height(),
                scroll = $(window).scrollTop() + that.extraParams.stickyTopOffset,
                scrollTo = stickyOffset.top + blockHeight - stickyHeight;

            if (scroll >= (stickyOffset.top) && scroll < scrollTo) {
                /* Чтобы не было скачков, создаем клона */
                if (!that.$set.find('.' + CLASSES.FIXED + (stickyClass ? '.' + stickyClass : '')).length) {
                    var clone = sticky.clone();
                    sticky.addClass('vhidden');
                    clone.width(sticky.outerWidth());
                    clone.height(stickyHeight);
                    if (that.extraParams.stickyTopOffset) {
                        clone.css('top', that.extraParams.stickyTopOffset);
                    }
                    clone.css('left', sticky.offset().left);
                    clone.addClass(CLASSES.FIXED + (stickyClass ? ' ' + stickyClass : ''));
                    that.$set.append(clone);
                }
            } else {
                sticky.removeClass('vhidden');
                that.$set.find('.' + CLASSES.FIXED + (stickyClass ? '.' + stickyClass : '')).remove();
            }
        };

        ProductsetsFrontendSet.prototype.submitForm = function (controller) {
            var that = this;

            if (!controller.is_locked) {

                that.lockController(controller);

                var validate = new ProductsetsValidate(void 0 !== controller.validateParams ? controller.validateParams : that.validateParams);
                var data = controller.collectData();
                if (!validate.validateSubmitData(data, controller)) {
                    that.unlockController(controller);
                    setTimeout(function () {
                        validate.display();
                        controller.reachGoal('submit_error');
                    }, 10);
                    return false;
                }

                $.post($.productsets.urls['buy'], data)
                    .done(function (response) {
                        if (response.status == 'ok') {
                            successMessage(response);
                        } else if (response.status == 'fail' && response.errors) {
                            errorMessage(response);
                        }
                    }, "json")
                    .fail(function (jqXHR, textStatus, errorThrown) {
                        errorMessage({
                            errors: [__('Something wrong!')]
                        });
                        if (console) {
                            console.error(jqXHR, textStatus, errorThrown);
                        }
                    })
                    .always(function () {
                        that.unlockController(controller);
                    });

                function successMessage(response) {
                    var type = controller.getType();
                    let $content = $('<svg class="static-popup-icon" ' + (type === 'bundle' ? $.productsets.options.attr.successPopupTickBundle : $.productsets.options.attr.successPopupTickUserBundle) + '><use xlink:href="' + $.productsets.PLUGIN_URL + 'img/svg/checkmark.svg#check"></use></svg>' +
                        __('Products has been successfully added to the cart') +
                        '<div class="productsets-popup-buttons">' +
                        '<span data-productsets-close ' + (type === 'bundle' ? $.productsets.options.attr.successPopupLinkBundle : $.productsets.options.attr.successPopupLinkUserBundle) + '>' + __('Continue shopping') + '</span>' +
                        '<a class="productsets-button" ' + (type === 'bundle' ? $.productsets.options.attr.successPopupButtonBundle : $.productsets.options.attr.successPopupButtonUserBundle) + ' href="' + $.productsets.urls.cartPage + '">' + __('Proceed to checkout') + '</a>'
                        + '</div>');

                    /* При нажатии на кнопку "Продолжить покупки" закрываем всплывающее окно пользовательского комплекта, если такое имеется */
                    $content.find('[data-productsets-close]').click(function () {
                        $.productsets.closeUserbundlePopup($(this));
                    });
                    let popupParams = {
                        $set: controller.$set,
                        content: $content,
                        class: 'static-popup is-success',
                        fixed: true,
                        isSuccess: true,
                        type: type
                    };
                    new ProductsetsPopup($.extend(popupParams, (void 0 !== controller.popupParams ? controller.popupParams : {})));
                    controller.reachGoal('submit');
                }

                function errorMessage(response) {
                    for (var errorId in response.errors) {
                        var error = response.errors[errorId];
                        validate.addError(error);
                    }
                    validate.display();
                    controller.reachGoal('submit_error');
                }
            }
        };

        ProductsetsFrontendSet.prototype.lockController = function (controller) {
            var that = this;
            var $submitBtn = controller.getSubmitBtn();

            controller.is_locked = 1;
            $submitBtn.addClass(CLASSES.DISABLED);
            controller.$set.addClass(CLASSES.LOCKED);
            if (!$submitBtn.find(CLASSES.BUTTON_LOADING).length) {
                $submitBtn.append(that.loading);
            }
            controller.reachGoal('locked');
        };

        ProductsetsFrontendSet.prototype.unlockController = function (controller) {
            var $submitBtn = controller.getSubmitBtn();

            controller.is_locked = 0;
            controller.$set.removeClass(CLASSES.LOCKED);
            $submitBtn.removeClass(CLASSES.DISABLED).find(CLASSES.BUTTON_LOADING).remove();
            controller.reachGoal('unlocked');
        };

        ProductsetsFrontendSet.prototype.reachGoal = function (target, params) {
            this.$set.trigger('productsets-' + target, params);
        };

        /* Появление окна для выбора другой вариации товара */
        ProductsetsFrontendSet.prototype.showSkusPopup = function (elem) {
            var that = this;

            var $product = elem.closest(CLASSES.ITEM);
            var controller = that.getActiveController(elem);

            new ProductsetsPopup({
                $set: that.$set,
                title: __('Select product sku'),
                url: $.productsets.urls['getProductSkus'],
                $activeElem: $product,
                postData: {
                    id: $product.data('id'),
                    ruble_sign: that.rubleSign,
                    settings: $product.data('settings'),
                    type: controller.$bundle.data('skus-popup')
                },
                extraData: [
                    {
                        name: 'product',
                        value: $product
                    },
                    {
                        name: 'parent',
                        value: $product.closest(CLASSES.CONTROLLER).data('controller')
                    },
                    {
                        name: 'rubleSign',
                        value: that.rubleSign
                    }
                ],
                type: controller.getType()
            });
        };

        /* Событие при изменении количества товара */
        ProductsetsFrontendSet.prototype.onQuantityInputChange = function ($input) {
            var that = this;

            var $field = $input,
                newValue = parseFloat($field.val());

            var minQuantity = $field.attr('data-min');
            if (minQuantity !== undefined && parseFloat(minQuantity) > newValue) {
                $field.val(minQuantity);
            }
            var maxQuantity = $field.attr('data-max');
            if (maxQuantity !== undefined && parseFloat(maxQuantity) < newValue) {
                $field.val(maxQuantity);
            }

            if (newValue <= 0 || isNaN(newValue)) {
                newValue = minQuantity || 1;
                $field.val(newValue);
            }

            that.getActiveController($field).update();
        };

        /* Получаем активный контроллер, исходя из затронутого поля */
        ProductsetsFrontendSet.prototype.getActiveController = function ($anyField) {
            return $anyField.closest(CLASSES.CONTROLLER).data('controller');
        };

        /* Изменение количества товара */
        ProductsetsFrontendSet.prototype.changeQuantity = function (elem, type) {
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
                var minQuantity = $field.attr('data-min');
                if (minQuantity !== undefined && parseFloat(minQuantity) > newValue) {
                    newValue = parseFloat(minQuantity);
                }
            }
            if (newValue <= 0) {
                newValue = $field.data("min") || 1;
            }
            $field.val(newValue).trigger('change');
        };

        ProductsetsFrontendSet.prototype.update = function ($bundle) {
            var that = this;

            that.$bundle = $bundle || that.$set.find(CLASSES.ACTIVE_BUNDLE);
            that.type = that.getBundleType();

            if (that.$bundle.length) {
                that.initBundle();
                that.updatePrices();
                that.afterExecute();
            }
        };

        ProductsetsFrontendSet.prototype.getBundleType = function () {
            return this.$bundle.is('.productsets-bundle') ? 'bundle' : 'userbundle';
        };

        ProductsetsFrontendSet.prototype.initBundle = function () {
            var that = this;

            that.bundleSettings = that.$bundle.data('settings');
            that.initVars();
            if (that.type == 'bundle') {
                that.resetItems();
                that.collectItems();
            }
        };

        ProductsetsFrontendSet.prototype.collectItems = function (withOriginalData) {
            var that = this;

            withOriginalData = withOriginalData || 0;
            if (withOriginalData) {
                that.items = [];
                that.bundleItems = [];
            }

            that.$bundle.find(CLASSES.BUNDLE_ITEM).not('.ps-ignore').each(function () {
                var $item = that.getActiveItem($(this));
                if ($item.length) {
                    if (!that.isInactive($item)) {
                        var data = that.getData($item, withOriginalData);
                        that.items.push(data);
                        that.bundleItems.push(data);

                        var price = data['quantity'] * data['price'];
                        /* Сумма цен без просчета скидок */
                        that.dirtyTotal += price;
                        /* Сумма зачеркнутых цен */
                        that.totalComparePrice += (data['compare_price'] > 0 ? data['quantity'] * data['compare_price'] : price);
                    } else {
                        that.bundleItems.push(that.getData($item, true));
                        if (!withOriginalData) {
                            var settings = $item.data('settings');
                            /* Если товара нет в наличии, а его присутствие необходимо для начисления скидок, тогда обнуляем скидки */
                            if (settings.discount_required !== undefined) {
                                that.collectItems(true);
                                that.resetDiscounts = true;
                                return false;
                            }
                        }
                    }
                }
            });
            that.dirtyDiscount = that.totalComparePrice - that.dirtyTotal;
        };

        ProductsetsFrontendSet.prototype.initVars = function () {
            var that = this;

            that.dirtyTotal = 0;
            that.dirtyDiscount = 0;
            that.commonDiscount = 0;
            that.discount = 0;
            that.total = 0;
            that.totalComparePrice = 0;
            that.activeController = that.$bundle.data('controller');
            that.isChangePrices = that.activeController.$set.data('change-price');
            that.discountRounding = that.activeController.$set.data('round') || 'not';
            that.items = that.type == 'bundle' ? [] : that.activeController.items;
            that.bundleItems = [];
            that.allItems = {};
            that.resetDiscounts = false;
        };

        ProductsetsFrontendSet.prototype.resetItems = function () {
            var that = this;
            that.resetDiscounts = true;

            that.$bundle.find(CLASSES.BUNDLE_ITEM).each(function () {
                var $item = that.getActiveItem($(this));
                if ($item.length) {
                    if (!that.isInactive($item)) {
                        var data = that.getData($item, true);
                        that.items.push(data);

                        var price = data['quantity'] * data['price'];
                        /* Сумма цен без просчета скидок */
                        that.dirtyTotal += price;
                        /* Сумма зачеркнутых цен */
                        that.totalComparePrice += (data['compare_price'] > 0 ? data['quantity'] * data['compare_price'] : price);
                    }
                }
            });

            that.updatePrices();
            that.initVars();
        };

        ProductsetsFrontendSet.prototype.updateTotalValues = function () {
            var that = this;

            that.dirtyTotal = that.totalComparePrice = that.dirtyDiscount = 0;

            var items = that.getItems();
            for (var i in items) {
                var item = items[i];

                var price = item['quantity'] * item['price'];
                /* Сумма цен без просчета скидок */
                that.dirtyTotal += price;
                /* Сумма зачеркнутых цен */
                that.totalComparePrice += (item['compare_price'] > 0 ? item['quantity'] * item['compare_price'] : price);
            }

            that.dirtyTotal -= that.commonDiscount;
            if (that.dirtyTotal < 0) {
                that.dirtyTotal = 0;
            }
            that.dirtyDiscount = that.totalComparePrice - that.dirtyTotal;
        };

        ProductsetsFrontendSet.prototype.updatePrices = function (all) {
            let that = this;

            all = all || 0;

            that.updateDiscount(all);
            that.updateTotalPrice();

            let items = all ? that.getAllItems() : that.getItems();

            if (all || that.getItemsLength()) {
                for (let i in items) {
                    let itemData = items[i];
                    let $item = itemData['obj'];

                    /* Меняем основную цену */
                    updateItemPrice($item, itemData);
                    /* Меняем зачеркнутую цену */
                    updateItemComparePrice($item, itemData);
                    /* Меняем наклейки */
                    updateItemBadge($item, itemData);
                }
            }

            that.resetPricesVars();

            function updateItemPrice($item, data) {
                let price = that.isChangePrices ? data['price'] : data['original_price'];
                $item.find(CLASSES.PRICE_FLD)
                    .data('price', data['price'])
                    .data('original-price', data['original_price'])
                    .attr('data-price', data['price'])
                    .attr('data-original-price', data['original_price'])
                    .html($.productsets.currencyFormat(price, that.rubleSign));
            }

            function updateItemComparePrice($item, data) {
                let $compareFld = $item.find(CLASSES.COMPARE_PRICE_FLD);
                let price = that.isChangePrices ? data['price'] : data['original_price'];
                let comparePrice = that.isChangePrices ? data['compare_price'] : data['original_compare_price'];
                $compareFld
                    .data('price', data['compare_price'])
                    .data('original-price', data['original_compare_price'])
                    .attr('data-price', data['compare_price'])
                    .attr('data-original-price', data['original_compare_price'])
                    .html($.productsets.currencyFormat(comparePrice, that.rubleSign));
                if (comparePrice > 0 && comparePrice > price) {
                    $compareFld.addClass('ps-show-compare-price').show();
                } else {
                    $compareFld.removeClass('ps-show-compare-price').hide();
                }
            }

            function updateItemBadge($item, data) {
                let badgeDiscount = that.calculateBadgeDiscount(data);
                $item.find(CLASSES.BADGE).text('- ' + badgeDiscount + '%');
                if (badgeDiscount > 0) {
                    $item.find(CLASSES.BADGE_FLD).show();
                } else {
                    $item.find(CLASSES.BADGE_FLD).hide();
                }
            }
        };

        ProductsetsFrontendSet.prototype.resetPricesVars = function () {
            let that = this;

            that.dirtyTotal = 0;
            that.dirtyDiscount = 0;
            that.commonDiscount = 0;
            that.discount = 0;
            that.total = 0;
            that.totalComparePrice = 0;
        };

        ProductsetsFrontendSet.prototype.updateTotalPrice = function () {
            let that = this;

            let total = that.getTotal();
            let comparePrice = that.getComparePrice();

            if (total >= comparePrice) {
                comparePrice = 0;
            }

            that.activeController.getTotalPriceFld().html($.productsets.currencyFormat(total, that.rubleSign));
            that.activeController.getTotalComparePriceFld().html(comparePrice ? $.productsets.currencyFormat(comparePrice, that.rubleSign) : '');
        };

        ProductsetsFrontendSet.prototype.updateDiscount = function (all) {
            let that = this;

            let discount = that.getDiscount(all);
            let discountFld = that.activeController.getDiscountFld();
            let discountFldWrap = that.activeController.getDiscountFldWrap();
            discountFld.html($.productsets.currencyFormat(discount, that.rubleSign));
            if (discount <= 0) {
                discountFldWrap.addClass('vhidden');
            } else {
                discountFldWrap.removeClass('vhidden');
            }
        };
        ProductsetsFrontendSet.prototype.afterExecute = function () {
            const that = this;
            typeof that.activeController.afterUpdate == 'function' && that.activeController.afterUpdate();
        };

        ProductsetsFrontendSet.prototype.calculateBadgeDiscount = function (data) {
            const that = this;

            let badgeDiscount = 0;
            const price = that.isChangePrices ? data['price'] : data['original_price'];
            const comparePrice = that.isChangePrices ? data['compare_price'] : data['original_compare_price'];
            if (comparePrice > 0) {
                badgeDiscount = (100 * (1 - price / comparePrice));
                if (badgeDiscount > 0) {
                    if ((badgeDiscount > 99 && badgeDiscount < 100) || badgeDiscount < 1) {
                        badgeDiscount = badgeDiscount.toFixed(2);
                    } else {
                        badgeDiscount = badgeDiscount.toFixed();
                    }
                }
            }
            return badgeDiscount;
        };

        ProductsetsFrontendSet.prototype.getTotal = function () {
            var that = this;

            that.total = that.dirtyTotal;
            return that.total;
        };

        ProductsetsFrontendSet.prototype.getItems = function () {
            return this.items;
        };

        ProductsetsFrontendSet.prototype.getBundleItems = function () {
            return this.bundleItems;
        };

        ProductsetsFrontendSet.prototype.getItemsLength = function () {
            return Object.keys(this.items).length;
        };

        ProductsetsFrontendSet.prototype.getDiscount = function (all) {
            var that = this;

            if (!that.resetDiscounts) {
                /* Добавляем общую скидку на заказ. Она не распределяется по товарам */
                if (that.bundleSettings.discount_type == 'common' && that.bundleSettings.currency !== '%' && that.bundleSettings.frontend_discount > 0) {
                    that.commonDiscount += parseFloat(that.bundleSettings.frontend_discount);
                }

                /* Считаем скидки в зависимости от наличия товаров в комплекте */
                if (that.bundleSettings.discount_type == 'avail') {
                    that.getAvailDiscount(all);
                }
            }

            that.commonDiscount = that.round(that.commonDiscount);

            /* Пересчитываем товары, чтобы обновить данные о скидах */
            that.updateTotalValues();
            that.discount += that.dirtyDiscount;

            return that.discount;
        };
        ProductsetsFrontendSet.prototype.getComparePrice = function () {
            var that = this;

            return that.totalComparePrice;
        };

        ProductsetsFrontendSet.prototype.getData = function ($item, getOriginalData, bundleSettings) {
            var that = this;

            var settings = $item.data('settings');

            var data = {
                sku_id: $item.data('sku-id'),
                obj: $item,
                settings: settings
            };

            getOriginalData = getOriginalData || 0;
            bundleSettings = bundleSettings || that.bundleSettings;

            /* Получаем количество товара */
            var quantity = settings.quantity || 1;
            if (settings.choose_quantity) {
                var quantityFld = $item.find(CLASSES.QUANTITY_FLD);
                quantity = quantityFld.val();
                if (quantityFld.data('max') !== undefined && quantityFld.data('max') && quantity > parseFloat(quantityFld.data('max'))) {
                    quantity = parseFloat(quantityFld.data('max'));
                }
            }
            data['quantity'] = parseFloat(quantity);

            /* Цена */
            var priceFld = $item.find(CLASSES.PRICE_FLD);
            data['original_price'] = parseFloat(priceFld.attr('data-original-price'));
            data['price'] = getOriginalData ? data['original_price'] : parseFloat(priceFld.attr('data-price'));
            /* Зачеркнутая цена */
            var $comparePriceFld = $item.find(CLASSES.COMPARE_PRICE_FLD);
            data['original_compare_price'] = parseFloat($comparePriceFld.attr('data-original-price'));
            data['compare_price'] = getOriginalData ? data['original_compare_price'] : parseFloat($comparePriceFld.attr('data-price'));

            /* Скидка */
            data['discount'] = !getOriginalData ? that.getItemDiscount(data['original_price'], settings, bundleSettings) : 0;
            /* Если имеется скидка, а цена товара не отличается от исходной, меняем основную цену */
            if (data['discount'] > 0 && data['price'] === data['original_price']) {
                if (data['compare_price'] <= 0) {
                    data['compare_price'] = data['price'];
                }
                data['price'] = data['original_price'] - data['discount'];
                if (data['price'] < 0) {
                    data['price'] = 0;
                    data['discount'] = data['original_price'];
                }
            }

            return data;
        };

        ProductsetsFrontendSet.prototype.getItemDiscount = function (price, itemSettings, bundleSettings) {
            var that = this;

            if (bundleSettings.discount_type == 'common' && bundleSettings.currency == '%') {
                return that.getCommonDiscount(price, bundleSettings.frontend_discount, bundleSettings.currency);
            } else if (bundleSettings.discount_type == 'each') {
                return that.getCommonDiscount(price, itemSettings.frontend_discount, itemSettings.currency);
            }
            return 0;
        };

        ProductsetsFrontendSet.prototype.getCommonDiscount = function (price, discount, currency) {
            if (currency === '%') {
                return this.round(price * discount / 100);
            } else {
                return this.round(discount);
            }
        };

        ProductsetsFrontendSet.prototype.round = function (value) {
            const decimalAdjust = function (type, value, exp) {
                if (typeof exp === 'undefined' || +exp === 0) {
                    return Math[type](value);
                }
                value = +value;
                exp = +exp;
                if (isNaN(value) || !(typeof exp === 'number' && exp % 1 === 0)) {
                    return NaN;
                }
                value = value.toString().split('e');
                value = Math[type](+(value[0] + 'e' + (value[1] ? (+value[1] - exp) : -exp)));
                value = value.toString().split('e');
                return +(value[0] + 'e' + (value[1] ? (+value[1] + exp) : exp));
            }

            switch (this.discountRounding) {
                case 'ceil':
                    return Math.ceil(value);
                case 'floor':
                    return Math.floor(value);
                case 'round':
                    return Math.round(value);
                case 'tens':
                    return decimalAdjust(value, 1);
                case 'hund':
                    return decimalAdjust(value, 2);
                case 'dec1':
                    return decimalAdjust(value, -1);
                case 'dec2':
                    return decimalAdjust(value, -2);
                default:
                    return value;
            }
        }

        ProductsetsFrontendSet.prototype.getAvailDiscount = function (all) {
            var that = this;

            if (that.bundleSettings.avail_discount_type == 'every') {
                that.getAvailEveryDiscount();
            } else {
                that.getAvailCommonDiscount(all);
            }
        };

        ProductsetsFrontendSet.prototype.getAvailCommonDiscount = function (calculateForAllItems) {
            var that = this;

            calculateForAllItems = calculateForAllItems || 0;

            var items = calculateForAllItems ? that.getAllItems() : that.getItems();

            var count = that.getItemsLength() - 1;

            if (count !== -1) {
                /* Считаем значения скидок для каждого активного товара в наборе */
                let chainDetails = that.setAvailCommonChainDiscount(count, items, calculateForAllItems);

                /* Если есть скидка, пересчитываем общее значение */
                if (chainDetails.discountValue > 0 && !chainDetails.discountEach && chainDetails.discountCurrency !== '%') {
                    that.commonDiscount += chainDetails.discountValue;
                    /* Если используется фиксированная скидки для комплекта, отображаем цены всех товаров, которые были до этой скидки */
                    if (calculateForAllItems) {
                        that.setAvailCommonChainDiscount(that.getLastPercantageChainValue(count), items, calculateForAllItems);
                    }
                }
            } else if (calculateForAllItems) {
                that.changeAllPrices();
            }
        };

        ProductsetsFrontendSet.prototype.getLastPercantageChainValue = function (activeItemsCount) {
            var that = this;

            var count = activeItemsCount;
            if (count !== -1 && that.bundleSettings.chain !== undefined && that.bundleSettings.chain.value !== undefined && that.bundleSettings.chain.value.length) {
                for (let i = count - 1; i >= 0; i--) {
                    let discountCurrency = that.getAvailDiscountCurrency(i);
                    let discountEach = that.getAvailDiscountEach(i);
                    if (discountCurrency == '%' || discountEach) {
                        return i;
                    }
                }
            }
            return -1;
        };

        ProductsetsFrontendSet.prototype.setAvailCommonChainDiscount = function (activeItemsCount, items, calculateForAllItems) {
            var that = this;

            var discountValue = 0, discountCurrency = 0, discountEach = 0;
            var count = activeItemsCount;
            if (count !== -1 && that.bundleSettings.chain !== undefined && that.bundleSettings.chain.value !== undefined && that.bundleSettings.chain.value.length) {
                /* Не даем общему количеству активных товаров уйти за пределы доступных скидочных позиций */
                var maxCount = that.bundleSettings.chain.value.length - 1;
                if (count > maxCount) {
                    count = maxCount;
                }
                /* Получаем значение скидки: процент или валюта */
                discountValue = that.getAvailDiscountValue(count);
                discountCurrency = that.getAvailDiscountCurrency(count);
                discountEach = that.getAvailDiscountEach(count);

                /* Если есть скидка, пересчитываем общее значение */
                if ((discountValue > 0 || calculateForAllItems) && (discountCurrency == '%' || discountEach)) {
                    $.each(items, function (i, item) {
                        var discount = discountCurrency == '%' ? discountValue * item['original_price'] / 100 : discountValue;
                        discount = that.round(discount);
                        that.changePrices(i, discount, calculateForAllItems);
                    });
                }
            }
            return { discountValue: discountValue, discountCurrency: discountCurrency, discountEach: discountEach };
        };

        ProductsetsFrontendSet.prototype.getAvailDiscountValue = function (chainKey) {
            var that = this;

            return parseFloat((that.bundleSettings.chain.frontend_value !== undefined && that.bundleSettings.chain.frontend_value[chainKey]) ? that.bundleSettings.chain.frontend_value[chainKey] : 0);
        };

        ProductsetsFrontendSet.prototype.getAvailDiscountCurrency = function (chainKey) {
            var that = this;

            return (that.bundleSettings.chain.currency !== undefined && that.bundleSettings.chain.currency[chainKey]) ? that.bundleSettings.chain.currency[chainKey] : 0;
        };

        ProductsetsFrontendSet.prototype.getAvailDiscountEach = function (chainKey) {
            var that = this;

            return parseInt((that.bundleSettings.chain.each !== undefined && that.bundleSettings.chain.each[chainKey]) ? that.bundleSettings.chain.each[chainKey] : 0);
        };

        ProductsetsFrontendSet.prototype.getAllItems = function () {
            var that = this;

            if (!Object.keys(that.allItems).length) {
                that.$bundle.find(CLASSES.ITEM).each(function () {
                    var data = that.getData($(this));
                    that.allItems['i' + data.sku_id] = data;
                });
            }
            return that.allItems;
        };

        ProductsetsFrontendSet.prototype.changePrices = function (itemKey, discount, calculateForAllItems) {
            var that = this;

            var item = calculateForAllItems ? that.allItems[itemKey] : that.items[itemKey];
            if ((discount > 0 || calculateForAllItems) && item !== undefined) {
                item['compare_price'] = item['original_compare_price'] > 0 ? item['original_compare_price'] : item['original_price'];
                item['price'] = item['original_price'] - discount;

                if (item['price'] < 0) {
                    item['price'] = 0;
                    item['discount'] = item['original_price'];
                }

                if (item['compare_price'] === item['price']) {
                    item['compare_price'] = 0;
                }
                if (calculateForAllItems) {
                    that.allItems[itemKey] = item;
                } else {
                    that.items[itemKey] = item;
                }
            }
        };

        ProductsetsFrontendSet.prototype.changeAllPrices = function (discount) {
            var that = this;

            discount = discount || 0;
            discount = that.round(discount);

            var items = that.getAllItems();
            $.each(items, function (i) {
                that.changePrices(i, discount, 1);
            });
        };

        ProductsetsFrontendSet.prototype.getAvailEveryDiscount = function () {
            var that = this;

            var items = that.getItems();

            for (var i in items) {
                var item = items[i];

                /* Получаем значение скидки: процент или валюта */
                var discountValue = that.getAvailDiscountValue(i);
                var discountCurrency = that.getAvailDiscountCurrency(i);

                if (discountValue > 0) {
                    var discount = discountCurrency == '%' ? discountValue * item['original_price'] / 100 : discountValue;
                    that.changePrices(i, discount);
                }
            }
        };

        ProductsetsFrontendSet.prototype.isItemHasAlternative = function ($bundleItem) {
            return $bundleItem.is(CLASSES.ALTERNATIVE_ITEM);
        };

        ProductsetsFrontendSet.prototype.isInactive = function ($item) {
            return $item.hasClass(CLASSES.INACTIVE) || $item.hasClass(CLASSES.DELETED);
        };

        ProductsetsFrontendSet.prototype.getActiveItem = function ($bundleItem) {
            var that = this;

            if (that.isItemHasAlternative($bundleItem)) {
                var $activeAltItem = $bundleItem.find(CLASSES.ACTIVE_ALTERNATIVE_ITEM);
                if (!$activeAltItem.length && $bundleItem.data('active-slide') !== undefined) {
                    $activeAltItem = $bundleItem.find(CLASSES.SLIDE).eq($bundleItem.data('active-slide')).find(CLASSES.ITEM);
                }
                return $activeAltItem;
            } else {
                return $bundleItem.find('>' + CLASSES.ITEM);
            }
        };

        ProductsetsFrontendSet.prototype.lock = function () {
            this.$set.addClass(CLASSES.LOADING);
        };

        ProductsetsFrontendSet.prototype.unlock = function () {
            this.$set.removeClass(CLASSES.LOADING);
        };

        return ProductsetsFrontendSet;

    })();

    var ProductsetsValidate = (function () {

        ProductsetsValidate = function (params) {
            var that = this;

            that.errors = [];
            that.$set = params.$set;
            that.$errorBlock = that.$set.find(CLASSES.ERROR_BLOCK);
            that.options = params;

            that.initClass();
        };

        ProductsetsValidate.prototype = {
            initClass: function () {
                this.reset();
            },
            reset: function () {
                var that = this;

                that.$errorBlock.html('').hide();
                that.$set.find(CLASSES.POPUP).remove();
            },
            addError: function (message) {
                this.errors.push(message);
            },
            display: function () {
                var that = this;

                if (that.errors.length) {
                    var commonErrors = [];
                    for (var i in that.errors) {
                        commonErrors.push(that.errors[i]);
                    }
                    if (commonErrors.length) {
                        if (that.options.popupErrors) {
                            new ProductsetsPopup({
                                $set: that.$set,
                                content: '<svg class="static-popup-icon"><use xlink:href="' + $.productsets.PLUGIN_URL + 'img/svg/cross.svg#cross"></use></svg>' +
                                    commonErrors.join('<br>'),
                                class: 'static-popup is-error',
                                fixed: true
                            });
                        } else {
                            that.$errorBlock.html(commonErrors.join('<br>')).show();
                        }
                    }
                }
            },
            validateSubmitData: function (data, controller) {

                /* Проверяем наличие товаров в комплекте */
                if (!data.items.length) {
                    this.addError(__('The set is empty'));
                    return false;
                }

                /* Проверяем минимальное количество товаров */
                if (data.type === 'userbundle' && void 0 !== controller.bundleSettings.min) {
                    let min = parseFloat(controller.bundleSettings.min);
                    if (data.items.length < min) {
                        this.addError(__('Minimal quantity of products is') + ' ' + min);
                        return false;
                    }
                }

                /* Проверяем максимальное количество товаров */
                if (data.type === 'userbundle' && void 0 !== controller.bundleSettings.max) {
                    let max = parseFloat(controller.bundleSettings.max);
                    if (data.items.length > max) {
                        this.addError(__('Maximal quantity of products is') + ' ' + max);
                        return false;
                    }
                }

                return true;
            }
        };

        return ProductsetsValidate;

    })(jQuery);

    var ProductsetsPopup = (function () {

        /**
         * @param {object} params - Available options
         * @param {string} params.url - if exists, load content by url,
         * @param {object} params.$set - current set,
         * @param {object} params.$activeElem - active clicked element. Uses for popup positioning,
         * @param {object} params.postData - post data to the url
         * @param {string} params.title - popup title
         * @param {string} params.class - popup class
         * @param {string} params.content - popup content
         * @param {string} params.freezeBody - add overflow to body
         * @param {bool} params.fixed - should popup be fixed
         * @param {object[]} params.extraData - data, which will be added to popup data by .data()
         * @param {string} params.extraData[].name - name of the parameter
         * @param {string} params.extraData[].value - value of the parameter
         */
        ProductsetsPopup = function (params) {
            var that = this;

            that.$set = params.$set;
            that.$popup = '';
            that.options = params;
            that.xhrSkus = false;
            that.loaded = 0;

            that.initClass();
            that.bindEvents();
        };

        ProductsetsPopup.prototype = {
            initClass: function () {
                var that = this;
                var deferred;
                that.options.inited = 1;

                that.close();
                if (that.options.url) {
                    deferred = that.loadByUrl();
                } else {
                    deferred = $.Deferred().resolve();
                    that.show();
                }
                that.options.inited = 0;
                deferred.done(function () {
                    that.$popup.removeClass(CLASSES.LOADING);
                    if (that.options.freezeBody) {
                        $('body').addClass('productsets-popup-opened');
                    }
                });
            },
            bindEvents: function () {
                var that = this;

                that.$popup.find('[data-productsets-close]').click(function () {
                    that.close();
                });
            },
            loadByUrl: function () {
                var that = this;

                that.show();
                that.xhrSkus = $.post(that.options.url, that.options.postData, function (response) {
                    that.show(response);
                });

                return that.xhrSkus;
            },
            show: function (content) {
                var that = this;
                if (!that.$popup.length) {

                    var template = "" +
                        "    <div class=\"productsets-popup is-loading\" " + that.getAppearanceAttr('block') + ">" +
                        "        <div class=\"productsets-popup-head\" " + that.getAppearanceAttr('head') + ">" +
                        (that.options.title ? that.options.title : '') +
                        "           <span data-productsets-close " + that.getAppearanceAttr('close') + "></span>" +
                        "        </div>" +
                        "        <div class=\"productsets-popup-content\" " + that.getAppearanceAttr('content') + ">" +
                        "            <span class=\"productsets-loading2\"></span>" +
                        "        </div>" +
                        "    </div>";
                    that.$popup = $(template);
                    if (that.options.class) {
                        that.$popup.addClass(that.options.class);
                    }
                    that.$set.addClass('p-loading').append(that.$popup);
                    that.bindEvents();
                    that.addExtraData();
                    that.$popup.data('controller', that);
                }

                if (content) {
                    that.$popup.find(CLASSES.POPUP_CONTENT).html(content);
                } else if (that.options.content) {
                    that.$popup.find(CLASSES.POPUP_CONTENT).html(that.options.content);
                }

                that.position();
            },
            getAppearanceAttr: function (elem) {
                var that = this;
                var attr = '';
                if (that.options.type !== undefined) {
                    switch (elem) {
                        case "block":
                            if (that.options.type === 'bundle') {
                                attr = that.options.isSuccess ? 'successPopupBundleBlock' : 'skusPopupBundleBlock';
                            } else {
                                attr = that.options.isSuccess ? 'successPopupUserBundleBlock' : 'skusPopupUserBundleBlock';
                            }
                            break;
                        case "head":
                            if (that.options.type === 'bundle') {
                                attr = that.options.isSuccess ? '' : 'skusPopupBundleHeader';
                            } else {
                                attr = that.options.isSuccess ? '' : 'skusPopupUserBundleHeader';
                            }
                            break;
                        case "close":
                            if (that.options.type === 'bundle') {
                                attr = that.options.isSuccess ? 'successPopupCloseBundle' : 'skusPopupBundleClose';
                            } else {
                                attr = that.options.isSuccess ? 'successPopupCloseUserBundle' : 'skusPopupUserBundleClose';
                            }
                            break;
                        case "content":
                            if (that.options.type === 'bundle') {
                                attr = that.options.isSuccess ? 'successPopupContentBundle' : 'skusPopupBundleContent';
                            } else {
                                attr = that.options.isSuccess ? 'successPopupContentUserBundle' : 'skusPopupUserBundleContent';
                            }
                            break;
                    }
                }
                return typeof $.productsets.options.attr[attr] !== 'undefined' ? $.productsets.options.attr[attr] : ''
            },
            addExtraData: function () {
                var that = this;

                that.$popup.data('controller', that);
                if (that.options.extraData) {
                    $.each(that.options.extraData, function (i, v) {
                        that.$popup.data(v.name, v.value);
                    });
                }
            },
            position: function () {
                var that = this;

                var cssStyles = {
                    top: '50px'
                };
                if (that.options.$activeElem) {
                    cssStyles['top'] = that.options.$activeElem.offset().top - that.$set.offset().top;
                }
                if (that.options.fixed) {
                    delete cssStyles['top'];
                    that.$popup.addClass('productsets-popup-fixed');
                    cssStyles['margin-top'] = (-1) * (that.$popup.outerHeight() / 2);
                }
                cssStyles['margin-left'] = (-1) * Math.max(0, that.$popup.outerWidth() / 2) + "px";

                that.$popup.css(cssStyles);
            },
            close: function () {
                var that = this;
                that.$set.find(CLASSES.POPUP).remove();
                that.$set.removeClass('p-loading');
                if (that.xhrSkus) {
                    that.xhrSkus.abort();
                }
                that.xhrSkus = false;

                if (that.options.freezeBody) {
                    $('body').removeClass('productsets-popup-opened');
                }

                if (that.options.isSuccess && that.$set.data('reload') && !that.options.inited) {
                    location.reload();
                }
            }

        };

        return ProductsetsPopup;

    })(jQuery);

    /*
    * Обязательные методы и переменные класса
    *
    * that.is_locked +
    * that.collectData() +
    * that.reachGoal() +
    * that.validateParams *
    * that.popupParams *
    * */
    var ProductsetsFrontendBundle = (function () {

        ProductsetsFrontendBundle = function (bundle) {
            var that = this;

            /* DOM */
            that.$bundle = bundle;
            that.$set = that.$bundle.closest(CLASSES.SET);
            that.setController = that.$set.data('controller');

            /* VARS */
            that.is_locked = 0;

            that.$bundle.data('controller', that);

            that.initClass();
            that.bindEvents();

        };

        ProductsetsFrontendBundle.prototype.initClass = function () {
            var that = this;

            that.initSliders();

            that.$bundle.removeClass('not-inited');
        };

        ProductsetsFrontendBundle.prototype.getType = function () {
            return 'bundle';
        };

        ProductsetsFrontendBundle.prototype.bindEvents = function () {
            var that = this;

            /* Удаление товара через итоговый блок (3 тип отображения ) */
            $(document).off('click', CLASSES.TOTAL_ITEM_DELETE).on('click', CLASSES.TOTAL_ITEM_DELETE, function () {
                const $item = $(this).closest(CLASSES.TOTAL_ITEM)
                const itemIndex = $item.data('index');
                const items = $item.closest(CLASSES.SET).data('controller').getBundleItems();
                if (items.length && typeof items[itemIndex] !== 'undefined') {
                    items[itemIndex].obj.find(CLASSES.ITEM_STATUS_CHECKBOX).prop('checked', !!items[itemIndex].obj.hasClass(CLASSES.DELETED)).change();
                }
            });

            /* Всплывающее окно с альтернативными товарами для 4 типа отображения */
            $(document).off('click', '.js-productsets-show-alternative').on('click', '.js-productsets-show-alternative', function () {
                const $bundleItem = $(this).closest(CLASSES.BUNDLE_ITEM)
                let content = $bundleItem.find(CLASSES.ALTERNATIVE_BLOCK).html();
                let popupParams = {
                    $set: $bundleItem.closest(CLASSES.SET),
                    content: content,
                    class: 'alternative-popup',
                    fixed: true,
                    type: that.getType(),
                    freezeBody: true,
                    title: __('Select another product'),
                    extraData: [
                        {
                            name: 'product',
                            value: $bundleItem
                        }
                    ]
                };
                setTimeout(function () {
                    new ProductsetsPopup(popupParams);
                }, 50)
            });

            /* Осуществляем выбор альтернативного товара из всплывающего окна */
            $(document).off('click', '.js-select-alternative-item').on('click', '.js-select-alternative-item', function () {
                const $selectedProduct = $(this);
                const $popup = $selectedProduct.closest(CLASSES.POPUP);
                const $product = $popup.data('product');

                if ($product) {
                    const $clone = $product.find(CLASSES.ITEM).clone();
                    const $selectedItem = $selectedProduct.find(CLASSES.ITEM);
                    $product.find('>' + CLASSES.ITEM).remove();
                    $product.prepend($selectedProduct.html());
                    $product.attr('data-id', $selectedItem.attr('data-id')).attr('data-sku-id', $selectedItem.attr('data-sku-id'));
                    $clone.find('.productsets-item-image').removeAttr('style');
                    $product.find('.productsets-alternative ' + CLASSES.ITEM + '[data-id="' + $selectedItem.attr('data-id') + '"][data-sku-id="' + $selectedItem.attr('data-sku-id') + '"]')
                        .replaceWith($clone.prop('outerHTML'));
                }
                $popup.data('controller').close();
                that.setController.update($product.closest(CLASSES.CONTROLLER));
            });

            $(document).off('click', '.js-select-alternative-item .productsets-item-link').on('click', '.js-select-alternative-item .productsets-item-link', function (e) {
                e.preventDefault();
            });

            /* Исключение товара из набора */
            $(document).off('change', CLASSES.ITEM_STATUS_CHECKBOX).on('change', CLASSES.ITEM_STATUS_CHECKBOX, function () {
                var $input = $(this);
                var $item = $input.closest(CLASSES.ITEM);
                if (!$input.prop('checked')) {
                    $item.addClass('item-deleted');
                } else {
                    $item.removeClass('item-deleted');
                }
                that.setController.update($item.closest(CLASSES.CONTROLLER));
            });

            /* Фиксированная шапка */
            if (that.$set.hasClass('ps-sticky') && that.$set.find(CLASSES.TOTAL_WRAP).data('position') === 'top') {
                $(window).scroll(function () {
                    var controller = that.$set.data('controller');
                    if (controller !== undefined) {
                        controller.onScroll(that.$set.find(CLASSES.TOTAL_WRAP), that.$set.find(CLASSES.BUNDLE_WRAP));
                    }
                });
            }
        };

        ProductsetsFrontendBundle.prototype.collectData = function () {
            var that = this;

            var data = {
                set_id: that.$set.data('id'),
                bundle_id: that.$bundle.data('bundle-id'),
                include_product: that.$bundle.data('include-product'),
                items: []
            };

            var controller = that.$set.data('controller');
            if (controller !== undefined) {
                /* Актуализируем данные набора */
                that.update();
                var items = controller.getItems();
                $.each(items, function (i, item) {
                    data.items.push({ sku_id: item.sku_id, quantity: item.quantity, _id: item.settings._id });
                });
            }
            return data;
        };

        ProductsetsFrontendBundle.prototype.update = function () {
            var that = this;

            var controller = that.$set.data('controller');
            if (controller !== undefined) {
                controller.update(that.$bundle);
            }
        };

        ProductsetsFrontendBundle.prototype.reachGoal = function (target) {
            var that = this;

            var controller = that.$set.data('controller');
            if (controller !== undefined) {
                controller.reachGoal(target, [that.$bundle]);
            }
        };

        ProductsetsFrontendBundle.prototype.initSliders = function () {
            var that = this;

            /* Слайдер альтернативных товаров */
            that.$bundle.find('.js-productsets-init-alt-slider').each(function () {
                const $altElem = $(this);
                const sliderType = $altElem.data('slider-type') || 'vertical';
                const params = {
                    vertical: true,
                    slidesToShow: 1,
                    slidesToScroll: 1,
                    infinite: false,
                    prevArrow: '<div class="productsets-alt-prev"><svg viewBox="0 0 32.2 20.3" class="productsets-icon atop" ' + $.productsets.options.attr.sliderArrow + '>' +
                        '<use xlink:href="' + $.productsets.PLUGIN_URL + 'img/svg/arrows.svg#arrow-up"></use>' +
                        '</svg></div></div>',
                    nextArrow: '<div class="productsets-alt-next"><svg viewBox="0 0 32.2 20.3" class="productsets-icon adown" ' + $.productsets.options.attr.sliderArrow + '>' +
                        '    <use xlink:href="' + $.productsets.PLUGIN_URL + 'img/svg/arrows.svg#arrow-down"></use>' +
                        '  </svg></div>',
                    appendArrows: $altElem,
                    draggable: false,
                    lazyLoad: 'ondemand',
                    adaptiveHeight: true,
                    cssEase: 'linear'
                };

                if (sliderType === 'horizontal') {
                    params['vertical'] = false;
                    params['prevArrow'] = '<div class="productsets-alt-prev"><svg viewBox="0 0 32.2 20.3" class="productsets-icon aleft" ' + $.productsets.options.attr.sliderArrow + '>' +
                        '<use xlink:href="' + $.productsets.PLUGIN_URL + 'img/svg/arrows.svg#arrow-left"></use>' +
                        '</svg></div></div>';
                    params['nextArrow'] = '<div class="productsets-alt-next"><svg viewBox="0 0 32.2 20.3" class="productsets-icon aright" ' + $.productsets.options.attr.sliderArrow + '>' +
                        '    <use xlink:href="' + $.productsets.PLUGIN_URL + 'img/svg/arrows.svg#arrow-right"></use>' +
                        '  </svg></div>';
                }
                $altElem.on('init afterChange', function (event, slick, currentSlide) {
                    $altElem.data('active-slide', currentSlide);
                    that.update();
                    that.$set.find(CLASSES.BUNDLES_WRAP).productsetsSlick('setPosition');
                }).productsetsSlick(params);
            });
        };

        ProductsetsFrontendBundle.prototype.afterUpdate = function () {
            var that = this;

            /* Обновляем состояние слайдера альтернативных товаров */
            that.$bundle.find('.js-productsets-init-alt-slider').trigger('resize');

            /* Третий тип отображения итогового блока */
            that.updateTotalBlockItems();
        };

        ProductsetsFrontendBundle.prototype.updateTotalBlockItems = function () {
            const that = this;

            const totalItemsBlock = that.$bundle.find(CLASSES.TOTAL_ITEMS);
            if (totalItemsBlock.length) {
                let html = '';
                const controller = that.$set.data('controller');
                if (controller !== undefined) {
                    let items = controller.getBundleItems();

                    let hasDelete = 0;
                    $.each(items, function (index, item) {
                        html += that.addItemToTotalBlock(item, index, controller);
                        if (item.settings.delete_product) {
                            hasDelete = 1;
                        }
                    });
                    if (!hasDelete) {
                        totalItemsBlock.addClass('hide-delete');
                    } else {
                        totalItemsBlock.removeClass('hide-delete');
                    }
                }
                totalItemsBlock.html(html);
                that.$set.find(CLASSES.BUNDLES_WRAP).productsetsSlick('setPosition');
            }
        };

        ProductsetsFrontendBundle.prototype.addItemToTotalBlock = function (item, index, controller) {
            let html = '<div class="productsets-total-item' + (item.obj.hasClass(CLASSES.DELETED) ? ' psi-deleted' : '') + '" data-index="' + index + '">' +
                '<div class="pti-name"><span class="pti-title">' + item.obj.find(CLASSES.ITEM_NAME).text() + '</span>' +
                (item.obj.find(CLASSES.ITEM_SKU).length ? ' <span class="pti-sku">(' + item.obj.find(CLASSES.ITEM_SKU).text() + ')</span>' : '') +
                '</div>' +
                '<div class="pti-price">' + $.productsets.currencyFormat(item['price'], controller.rubleSign) + '</div>' +
                '<div class="pti-quantity">x ' + item['quantity'] + '</div>';
            if (item.settings.delete_product) {
                html += '<div class="pti-icon" title="' + __('delete') + '"><svg viewBox="0 0 32 32"><use xlink:href="' + $.productsets.PLUGIN_URL + 'img/svg/cross.svg#cross"></use></svg></div>';
            } else {
                html += '<div class="pti-icon empty-delete-block"></div>';
            }
            html += '</div>';
            return html;
        }

        ProductsetsFrontendBundle.prototype.getDiscountFld = function () {
            return this.$set.find(CLASSES.TOTAL_DISCOUNT);
        };

        ProductsetsFrontendBundle.prototype.getDiscountFldWrap = function () {
            return this.$set.find(CLASSES.TOTAL_DISCOUNT_WRAP);
        };

        ProductsetsFrontendBundle.prototype.getTotalPriceFld = function () {
            return this.$set.find(CLASSES.TOTAL_PRICE);
        };

        ProductsetsFrontendBundle.prototype.getTotalComparePriceFld = function () {
            return this.$set.find(CLASSES.TOTAL_WRAP).find(CLASSES.TOTAL_COMPARE_PRICE);
        };

        ProductsetsFrontendBundle.prototype.getSubmitBtn = function () {
            return this.$set.find(CLASSES.BUY_BUTTON);
        };

        return ProductsetsFrontendBundle;
    })();

    /*
    * Обязательные методы и переменные класса
    *
    * that.is_locked +
    * that.collectData() +
    * that.reachGoal() +
    * that.validateParams *
    * that.popupParams *
    * */
    var ProductsetsFrontendUserBundle = (function () {

        ProductsetsFrontendUserBundle = function (bundle) {
            var that = this;

            /* DOM */
            that.$bundle = bundle;
            that.$set = that.$bundle.closest(CLASSES.SET);
            that.$visibleItems = that.$bundle.find(CLASSES.ITEM);
            that.$totalDiscountWrap = that.$bundle.find(CLASSES.USERBUNDLE_TOTAL_DISCOUNT_WRAP);
            that.$totalDiscount = that.$bundle.find(CLASSES.USERBUNDLE_TOTAL_DISCOUNT);
            that.$add2cart = that.$bundle.find(CLASSES.USERBUNDLE_INFO);
            that.$loader = that.$bundle.find(CLASSES.BUTTON_LOADING);
            that.$thumbs = that.$bundle.find(CLASSES.USERBUNDLE_THUMBS);

            /* VARS */
            that.setController = that.$set.data('controller');
            that.is_locked = 0;
            that.on_init = 1;
            that.popupParams = {
                $activeElem: that.getSubmitBtn()
            };

            that.items = {};
            that.groups = {};
            that.itemsQty = 0;
            that.bundleSettings = that.$bundle.data('settings');

            that.imageMinEmpty = '<svg viewBox="0 0 468.069 468.069"><use xlink:href="' + $.productsets.PLUGIN_URL + 'img/svg/square.svg#square"></use></svg>';

            that.$bundle.data('controller', that);

            that.initClass();
            that.bindEvents();
        };

        ProductsetsFrontendUserBundle.prototype.initClass = function () {
            var that = this;

            that.initGroups();

            that.update();

            that.$bundle.removeClass('not-inited');
            that.$add2cart.show();
            that.$loader.hide();
            that.on_init = 0;
        };

        ProductsetsFrontendUserBundle.prototype.initGroups = function () {
            var that = this;

            that.$bundle.find(CLASSES.USERBUNDLE_GROUP).each(function () {
                let $group = $(this);
                that.groups[$group.data('id')] = {
                    obj: $group,
                    settings: $group.data('settings')
                };
            });
        };
        ProductsetsFrontendUserBundle.prototype.collectData = function () {
            var that = this;

            var data = {
                set_id: that.$set.data('id'),
                type: that.getType(),
                include_product: that.$bundle.data('include-product'),
                items: []
            };

            var items = that.getItems();
            $.each(items, function (i, item) {
                data.items.push({ sku_id: item.sku_id, quantity: item.quantity, _id: item.settings._id });
            });
            return data;
        };

        ProductsetsFrontendUserBundle.prototype.reachGoal = function (target) {
            var that = this;

            if (that.setController !== undefined) {
                that.setController.reachGoal(target, [that.$bundle]);
            }
        };

        ProductsetsFrontendUserBundle.prototype.getType = function () {
            return 'userbundle';
        };

        ProductsetsFrontendUserBundle.prototype.update = function () {
            var that = this;

            that.updateItems();

            that.setController.update(that.$bundle);
        };

        ProductsetsFrontendUserBundle.prototype.afterUpdate = function () {
            var that = this;

            var totalQty = that.getTotalQty();
            that.$set.find(CLASSES.USERBUNDLE_TOTAL_QUANTITY).text(__('1 product', '{n} products', totalQty, { n: totalQty }));

            if (that.bundleSettings.show_thumbs) {
                that.updateThumbs();
            }

            /* Рассчитываем скидки для групп */
            if (that.bundleSettings.discount_type == 'each') {
                that.calculateGroupDiscount();
                that.setController.updatePrices();
            }
            /* Считаем скидки в зависимости от наличия товаров в комплекте для всех товаров */
            else if (that.bundleSettings.discount_type == 'avail') {
                that.setController.updatePrices(true);
            }
        };

        /* Рассчитываем скидки для групп */
        ProductsetsFrontendUserBundle.prototype.calculateGroupDiscount = function () {
            var that = this;

            let groupsAvailableDiscounts = {};
            let totalDiscount = 0;

            $.each(that.getItems(), function (i, item) {
                /* Если товар находится в группе, считаем его скидку */
                if (void 0 !== item.additional && void 0 !== item.additional.group_id && void 0 !== that.groups[item.additional.group_id]) {
                    let groupSettings = that.groups[item.additional.group_id].settings;
                    /* Общая скидка для группы в валюте */
                    if (groupSettings.discount_type == 'common' && groupSettings.currency !== '%') {
                        /* Запоминаем размер скидки для всей группы */
                        if (void 0 === groupsAvailableDiscounts[item.additional.group_id]) {
                            groupsAvailableDiscounts[item.additional.group_id] = parseFloat(groupSettings.frontend_discount);
                        }

                        /* Считаем допустимую скидку для каждого товара */
                        let itemDiscount = 0;
                        if (item.price * item.quantity < groupsAvailableDiscounts[item.additional.group_id]) {
                            itemDiscount = item.price * item.quantity;
                            groupsAvailableDiscounts[item.additional.group_id] -= itemDiscount;
                        } else {
                            itemDiscount = groupsAvailableDiscounts[item.additional.group_id];
                            groupsAvailableDiscounts[item.additional.group_id] = 0;
                        }
                        totalDiscount += parseFloat(itemDiscount);
                    }
                }
            });
            /* Если были скидки по группам, добавляем к значению общей скидки для набора */
            if (totalDiscount) {
                that.setController.commonDiscount += totalDiscount;
            }
        };

        /* Обновляем товары */
        ProductsetsFrontendUserBundle.prototype.updateItems = function () {
            var that = this;

            let items = that.getItems();
            that.itemsQty = 0;
            that.items = {};
            for (let i in items) {
                var data = items[i];
                if (items[i].obj.length) {
                    data = that.setController.getData(items[i].obj, 0, that.bundleSettings);
                    if (void 0 !== items[i].additional) {
                        data['additional'] = items[i].additional;
                    }
                    that.items['i' + data.sku_id] = data;
                }
                that.itemsQty += data.quantity;
            }
            that.saveItems();
        };

        /* Сохранение выбранных товаров в набор */
        ProductsetsFrontendUserBundle.prototype.saveItems = function () {
            var that = this;

            that.$bundle.data('items', that.items);
        };

        /* Получение всех выбранных товаров */
        ProductsetsFrontendUserBundle.prototype.getItems = function () {
            var that = this;

            // При первой инициализации добавляем обязательные товары в набор
            if (that.on_init) {
                var selected = that.$visibleItems.filter(CLASSES.SELECTED_USERITEM);
                selected.each(function () {
                    that.addItem($(this));
                });
            } else if (that.$bundle.data('items')) {
                return that.$bundle.data('items');
            }
            return that.items;
        };

        ProductsetsFrontendUserBundle.prototype.getItemsLength = function () {
            return Object.keys(this.items).length;
        };

        /* Добавление товара в набор */
        ProductsetsFrontendUserBundle.prototype.addItem = function ($item, $group) {
            var that = this;

            var data = that.setController.getData($item, 0, that.bundleSettings);
            if ($group && $group.length) {
                data['additional'] = {
                    group_id: $group.data('id')
                };
            }
            that.items['i' + data.sku_id] = data;
            that.saveItems();
        };

        /* Удаление товара из набора */
        ProductsetsFrontendUserBundle.prototype.removeItem = function ($item) {
            var that = this;

            that.items['i' + $item.data('sku-id')] !== undefined && delete that.items['i' + $item.data('sku-id')];
            that.saveItems();
        };

        /* Обновляем миниатюры выбранных товаров */
        ProductsetsFrontendUserBundle.prototype.updateThumbs = function () {
            var that = this;

            let items = that.getItems();
            that.$thumbs.html('');
            for (let i in items) {
                let $item = items[i].obj;
                let $imageBlock = $item.find(CLASSES.ITEM_IMAGE);
                let image = $imageBlock.find('img').clone();
                image.attr('src', $imageBlock.attr('data-src'));
                that.$thumbs.append(image);
            }
            /* Отображаем минимальное количество товаров, необходимых для комплекта */
            if (void 0 !== that.bundleSettings.min) {
                let min = parseFloat(that.bundleSettings.min);
                let itemsLength = that.getItemsLength();
                if (itemsLength < min) {
                    for (var i = 0; i < (min - itemsLength); i++) {
                        that.$thumbs.append(that.imageMinEmpty);
                    }
                }
            }
        };

        /* Общее количество выбранных товаров */
        ProductsetsFrontendUserBundle.prototype.getTotalQty = function () {
            return this.itemsQty;
        };

        /* Добавление товара в набор */
        ProductsetsFrontendUserBundle.prototype.addItemToBundle = function ($item) {
            var that = this;

            let $group = $item.closest(CLASSES.USERBUNDLE_GROUP);

            that.addItem($item, $group);
            let $addBtn = $item.addClass('added').find(CLASSES.USERBUNDLE_ADD2CART_ITEM);
            $item.find(CLASSES.USERBUNDLE_ADD2CART_ITEM_TEXT).text($addBtn.data('added'));

            /* Для групп, где запрещен множественный выбор, устанавливаем флаг, что имеется отмеченный пункт. С других пунктов снимает активность */
            if (!$group.data('multiple')) {
                $item.siblings(CLASSES.SELECTED_USERITEM).each(function () {
                    that.removeItemFromBundle($(this));
                });
                $group.addClass(CLASSES.ONE_VARIANT);
            }

            /* Если количество товаров в наборе превышает максимальное значение, скрываем кнопки добавления новых товаров */
            if (void 0 !== that.bundleSettings.max) {
                let max = parseFloat(that.bundleSettings.max);
                if (that.getItemsLength() >= max) {
                    that.hideAdd2BundleButtons();
                }
            }
        };

        /* Удаление товара из набора */
        ProductsetsFrontendUserBundle.prototype.removeItemFromBundle = function ($item) {
            var that = this;

            that.removeItem($item);
            let $addBtn = $item.removeClass('added').find(CLASSES.USERBUNDLE_ADD2CART_ITEM);
            $item.find(CLASSES.USERBUNDLE_ADD2CART_ITEM_TEXT).text($addBtn.data('add'));

            /* Если количество товаров в наборе не превышает максимальное значение, отображаем кнопки добавления новых товаров */
            let max = void 0 !== that.bundleSettings.max && that.bundleSettings.max !== '' ? parseFloat(that.bundleSettings.max) : 999999;
            if (that.getItemsLength() < max) {
                that.showAdd2BundleButtons();
            } else {
                that.hideAdd2BundleButtons();
            }

            /* Для групп, где запрещен множественный выбор, удаляем флаг, что имеются отмеченные пункты. */
            let $group = $item.closest(CLASSES.USERBUNDLE_GROUP);
            if (!$group.data('multiple') && !$item.siblings(CLASSES.SELECTED_USERITEM).length) {
                $group.removeClass(CLASSES.ONE_VARIANT);
            }
        };

        /* Скрываем кнопку добавления товара в набор для всех неактивных товаров */
        ProductsetsFrontendUserBundle.prototype.hideAdd2BundleButtons = function ($group) {
            var that = this;

            if (!$group) {
                that.$bundle.find(CLASSES.USERBUNDLE_GROUP).each(function () {
                    hideItems($(this));
                });
            } else {
                hideItems($(this));
            }

            /* Скрываем кнопки, если у группы разрешен множественный выбор или же там, где он запрещен, не выбрано ни одного варианта */
            function hideItems($group) {
                if ($group.data('multiple') || (!$group.data('multiple') && !$group.find(CLASSES.SELECTED_USERITEM).length)) {
                    $group.find(CLASSES.ITEM).not(CLASSES.SELECTED_USERITEM).find(CLASSES.USERBUNDLE_ADD2CART_ITEM).css('visibility', 'hidden');
                }
            }
        };

        /* Отображаем кнопку добавления товара в набор для всех неактивных товаров */
        ProductsetsFrontendUserBundle.prototype.showAdd2BundleButtons = function ($group) {
            var that = this;

            if (!$group) {
                that.$bundle.find(CLASSES.USERBUNDLE_GROUP).each(function () {
                    showItems($(this));
                });
            } else {
                showItems($group);
            }

            function showItems($group) {
                $group.find(CLASSES.USERBUNDLE_ADD2CART_ITEM).css('visibility', 'visible');
            }
        };

        ProductsetsFrontendUserBundle.prototype.bindEvents = function () {
            var that = this;

            /* Оформление заказа для всплывающего окна */
            that.$bundle.off('click', CLASSES.USERBUNDLE_BUY_BUTTON).on('click', CLASSES.USERBUNDLE_BUY_BUTTON, function () {
                that.setController.submitForm(that);
            });
            /* Оформление заказа. Это не лишний код! */
            $(document).off('click', CLASSES.USERBUNDLE_BUY_BUTTON).on('click', CLASSES.USERBUNDLE_BUY_BUTTON, function () {
                that.setController.submitForm(that);
            });

            /* Изменение количества товара */
            that.$set.find(CLASSES.USERBUNDLE_WRAP + " .js-productsets-quantity-trigger").click(function () {
                const elem = this;
                that.setController.changeQuantity(elem, $(elem).data('type') != 'minus');
            });
            /* Проверка измененного значения количества */
            that.$set.find(CLASSES.USERBUNDLE_WRAP + ' ' + CLASSES.QUANTITY_FLD).change(function () {
                that.setController.onQuantityInputChange($(this));
            });

            /* Появление окна для выбора другой вариации товара */
            that.$set.find(CLASSES.USERBUNDLE_WRAP + ' .js-productsets-product-skus').click(function () {
                that.setController.showSkusPopup($(this));
            });

            /* Добавление/удаление товара из набора */
            that.$bundle.find('.js-productsets-userbundle-add').click(function () {
                let $item = $(this).closest(CLASSES.ITEM);
                if ($item.is('.added')) {
                    that.removeItemFromBundle($item);
                } else {
                    that.addItemToBundle($item);
                }

                that.update();

                return false;
            });

            if (that.$set.hasClass('ps-sticky')) {
                /* Фиксированная шапка для всплывающего окна */
                $('.ig-dialog-wrap.productsets-dialog-wrapper .w-dialog-wrapper').scroll(function () {
                    that.onScroll($(this));
                });
                /* Фиксированная шапка */
                $(window).scroll(function () {
                    if (that.setController !== undefined) {
                        that.setController.onScroll(that.$bundle.find(CLASSES.USERBUNDLE_TOTAL_WRAP), that.$bundle.find(CLASSES.USERBUNDLE_BODY), 'fixed-userbundle');
                    }
                });
            }
        };

        ProductsetsFrontendUserBundle.prototype.onScroll = function ($wrapper) {
            var that = this;

            var sticky = that.$bundle.find(CLASSES.USERBUNDLE_TOTAL_WRAP).not('.ps-fixed.fixed-userbundle'),
                stickyOffset = sticky.offset(),
                popupDialog = $wrapper.find('.productsets-dialog'),
                stickyHeight = sticky.outerHeight() + 20,
                scroll = $(window).scrollTop();
            if (scroll >= stickyOffset.top) {
                /* Чтобы не было скачков, создаем клона */
                if (!that.$bundle.find('.ps-fixed.fixed-userbundle').length) {
                    var clone = sticky.clone();
                    clone.width(popupDialog.outerWidth());
                    clone.height(stickyHeight);
                    clone.css('left', popupDialog.offset().left);
                    clone.addClass('ps-fixed fixed-userbundle');
                    that.$bundle.append(clone);
                }
            } else {
                that.$bundle.find('.ps-fixed.fixed-userbundle').remove();
            }
        };

        ProductsetsFrontendUserBundle.prototype.getDiscountFld = function () {
            return this.$totalDiscount;
        };

        ProductsetsFrontendUserBundle.prototype.getDiscountFldWrap = function () {
            return this.$totalDiscountWrap;
        };

        ProductsetsFrontendUserBundle.prototype.getTotalPriceFld = function () {
            return this.$set.find(CLASSES.USERBUNDLE_TOTAL_PRICE);
        };

        ProductsetsFrontendUserBundle.prototype.getTotalComparePriceFld = function () {
            return this.$set.find(CLASSES.TOTAL_COMPARE_PRICE);
        };

        ProductsetsFrontendUserBundle.prototype.getSubmitBtn = function () {
            return this.$set.find(CLASSES.USERBUNDLE_BUY_BUTTON);
        };

        return ProductsetsFrontendUserBundle;
    })();

})(jQuery);