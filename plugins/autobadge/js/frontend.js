/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */
if (typeof jQuery === 'undefined') {
    var script = document.createElement('script');
    script.src = "https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js";
    document.getElementsByTagName('head')[0].appendChild(script);
}

(function ($) {
    $.autobadgeFrontend = {
        update: '',
        xhr: {},
        settings: {},
        wrapElements: ['.dialog', '.modal', '.s-dialog-wrapper'],
        autosizeElements: [],
        forceParentVisible: false,
        delayLoading: false,
        delayLoadingAjax: false,
        forceParentRelative: true,
        showLoader: true,
        ids: [],
        init: function (options) {
            var self = this;

            this.update = options.update || '';
            this.delayLoading = options.delayLoading || false;
            this.delayLoadingAjax = options.delayLoadingAjax || false;
            this.settings = options.settings || {};

            /* Счетчик */
            this.initCountdown();

            /* При изменении данных формы производим обновление всех блоков с наклейками */
            $(document).on("change", "form", function () {
               self.onFormChange($(this));
            });
            /* Ловим события, чтобы обновить наклейки во всплывающих окнах */
            $(document).on('click change', 'input[name="sku_id"], .sku-feature', function (e) {
                $(this).closest("form").change();
            });
            /* Ловим изменение кол-ва при нажатии на кнопки */
            var qtyTimer;
            $(document).on("click", "a.plus, a.minus, .inc_cart, .dec_cart, .inc_product, .dec_product.dec_product", function () {
                var that = $(this);
                clearTimeout(qtyTimer);
                qtyTimer = setTimeout(function () {
                    that.closest("form").change();
                }, 100);
            });

            /* Автоматическое позиционирование и размер */
            var timer;
            $(window).resize(function () {
                $.autobadgeFrontend.delayAutosize(200, timer);
            });
            $(document).on('mousedown touchend', '.s-sorting-list a, .select-view > span, .view-select > span, #select-view li a, .product-view button, .catalog-view span, .product-view .view a, .f-view, .showvariant a' + ($.autobadgeFrontend.autosizeElements.length ? ', ' + $.autobadgeFrontend.autosizeElements.join(',') : ''), function () {
                $.autobadgeFrontend.delayAutosize(450, timer);
            });

            if (!this.delayLoadingAjax) {
                /* Проводим дополнительную работу с наклейками */
                setTimeout(function () {
                    $.autobadgeFrontend.workupBadges();
                }, 800);
            } else {
                /* При отложенной подгрузке производим подгрузку наклеек */
                $('form').each(function () {
                    self.onFormChange($(this));
                });
            }
        },
        onFormChange: function(form) {
            var that = this;

            var productId = form.find("input[name='product_id']");
            if (productId.length) {
                that.ids.push({
                    id: productId.val(),
                    form: form
                });
                clearTimeout(form.data('autobadge-timer'));
                form.data('autobadge-timer', setTimeout(function () {
                    if (that.ids.length) {
                        $.autobadgeFrontend.updateProductRules(that.ids);
                        that.ids = [];
                    }
                }, 400));
            }
        },
        /* Обновить наклеки для конкретного товара */
        updateProductRules: function (products) {
            /* Собираем данные наклеек */
            var data = {},
                firstProduct = null,
                count = 0,
                historyP = {},
                xhr = {};
            $.each(products, function (k, obj) {
                var productId = obj.id,
                    form = obj.form;
                var quantity = form.find("input[name='quantity']").val(),
                    wrap = $.autobadgeFrontend.findWrap(form),
                    productBadges = wrap.find('.autobadge-pl.product-id-' + productId);
                $.each(productBadges, function (i) {
                    var productBadge = $(this),
                        textBlocks = productBadge.find('.badge-text-block'),
                        page = productBadge.attr('data-page') !== undefined ? productBadge.attr('data-page') : 'category',
                        type = productBadge.attr('data-type') !== undefined ? productBadge.attr('data-type') : 'default',
                        key = productId + '-' + page + '-' + type + '-' + k + '-' + i;
                    if (firstProduct === null) {
                        firstProduct = key;
                    }

                    quantity = quantity || 1;

                    /* Данные для отправки */
                    data[key] = {
                        product_id: productId,
                        quantity: quantity,
                        params: form.serialize(),
                        autobadgePage: page,
                        autobadgeType: type
                    };
                    /* Данные для дальнейшего завершения запроса */
                    historyP[key] = {
                        wrap: wrap,
                        textBlocks: textBlocks
                    };

                    /* Прерываем предыдущие запросы */
                    if ($.autobadgeFrontend.xhr[key] !== undefined && !$.isEmptyObject($.autobadgeFrontend.xhr[key]) && $.autobadgeFrontend.xhr[key].readyState != 4) {
                        $.autobadgeFrontend.xhr[key].abort();
                    }
                    $.autobadgeFrontend.addLoader(textBlocks);
                    count++;
                });
            });

            if (count === 1) {
                xhr = $.autobadgeFrontend.xhr;
            }
            xhr[firstProduct] = $.ajax({
                type: 'post',
                url: $.autobadgeFrontend.update,
                cache: false,
                dataType: "json",
                beforeSend: function (jqXHR, settings) {
                    if (count === 1) {
                        $.autobadgeFrontend.xhr[firstProduct] = jqXHR;
                    }
                },
                data: {
                    products: data
                },
                success: function (response) {
                    if (response.status == 'ok' && response.data) {
                        if (typeof response.data.products !== 'undefined') {
                            $.each(response.data.products, function (key, badge) {
                                /* Удаляем старые наклейки, добавляем новые */
                                var blocks = historyP[key].wrap.find('.autobadge-pl.product-id-' + badge.product_id + '[data-page="' + badge.page + '"][data-type="' + badge.type + '"]');
                                var delayLoadedClass = blocks.hasClass('autobadge-delay-loaded');
                                if (blocks.length) {
                                    var parent = blocks.parent();
                                    blocks.remove();
                                    if (badge.autobadge !== '') {
                                        parent.append(badge.autobadge);
                                        if (delayLoadedClass) {
                                            parent.find('.autobadge-pl.product-id-' + badge.product_id + '[data-page="' + badge.page + '"][data-type="' + badge.type + '"]').addClass('autobadge-delay-loaded');
                                        }
                                    }
                                }
                                /* Меняем дефолтную наклейку */
                                historyP[key].wrap.find(".autobadge-default.product-id-" + badge.product_id).replaceWith(badge.default);
                                /* Добавляем стили */
                                $.autobadgeFrontend.editBadgeCss(badge.css);
                            });
                            /* Подгружаем настройки наклеек №5 */
                            if (typeof response.data.js_settings !== 'undefined' && !$.isEmptyObject(response.data.js_settings)) {
                                if ($.autobadgeFrontend.settings === undefined) {
                                    $.autobadgeFrontend.settings = response.data.js_settings;
                                } else {
                                    $.each(response.data.js_settings, function (i, v) {
                                        if ($.autobadgeFrontend.settings[i] === undefined) {
                                            $.autobadgeFrontend.settings[i] = v;
                                        }
                                    });
                                }
                            }
                            $.autobadgeFrontend.reinit();
                        } else {
                            $.autobadgeFrontend.removeTextblocksLoader(historyP);
                        }
                    } else {
                        $.autobadgeFrontend.removeTextblocksLoader(historyP);
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    if (textStatus !== 'abort') {
                        if (console) {
                            console.log(jqXHR, textStatus, errorThrown);
                        }
                        $.autobadgeFrontend.removeTextblocksLoader(historyP);
                    }
                },
                complete: function () {
                    delete xhr[firstProduct];
                }
            });
        },
        /* Автоматическое позиционирование и размер */
        autoSize: function () {
            var completedBadges = {},
                tailCss = '';
            $(".autoposition-h, .autoposition-w, .adjust-w").each(function () {
                var that = $(this);
                if (that.hasClass('autoposition-h')) {
                    that.css('marginTop', (-1) * that.outerHeight() / 2 + (that.attr('data-mtop') !== undefined ? parseFloat(that.attr('data-mtop')) : 0));
                }
                if (that.hasClass('autoposition-w')) {
                    that.css('marginLeft', (-1) * that.outerWidth() / 2 + (that.attr('data-mleft') !== undefined ? parseFloat(that.attr('data-mleft')) : 0));
                }
                if (that.hasClass('adjust-w')) {
                    var parentW = that.offsetParent().outerWidth(),
                        objClass = that.attr('data-badge-class') + '-' + parentW;
                    if (completedBadges[objClass] === undefined && typeof $.autobadgeFrontend.settings[that.attr('data-badge-class')] !== 'undefined') {
                        completedBadges[objClass] = 1;
                        tailCss += $.autobadgeFrontend.generateTailCss(that, $.autobadgeFrontend.settings[that.attr('data-badge-class')], parentW);
                    }
                    if (that.attr('data-orig-class') === undefined) {
                        that.attr('data-orig-class', that.attr('class'));
                    }
                    that.toggleClass();
                    that.attr('data-width', parentW).addClass(that.attr('data-orig-class') + ' s-' + parentW);
                }
            });
            /* Добавляем общий блок со стилями наклеек для хвостов */
            $(".autobadge-tail-css").remove();
            tailCss !== '' && $("head").append('<style class="autobadge-tail-css">' + tailCss + '</style>');
            /* Позиционируем текст наклеек по центру */
            $(".badge-text-block").each(function () {
                var that = $(this),
                    parent = that.closest('.autobadge-pl'),
                    badgeId = parent.attr('data-badge-id');
                if (!that.children().length || parent[0].style.height == 'auto' || badgeId == 'ribbon-6' || badgeId == 'ribbon-4') {
                    return true;
                }
                that.css('marginTop', (-1) * that.height() / 2);
            });
        },
        /* Создаем стили для хвостов */
        generateTailCss: function (badge, styles, parentW) {
            var css = '',
                className = 'autobadge-pl.' + badge.attr('data-badge-class') + '.s-' + parentW,
                tail = styles.additional.all_tails,
                badgeW = badge[0].style.width,
                tailSize = $.autobadgeFrontend.getTailSize(parentW, badgeW.substring(0, badgeW.length - 1)),
                borderWidth = styles.border !== undefined ? (-1) * parseInt(styles.border.width) : 0,
                doubleSize = ($.inArray('bottom_left', styles.additional.all_tails.position) === -1 && $.inArray('top_left', styles.additional.all_tails.position) === -1) || ($.inArray('bottom_right', styles.additional.all_tails.position) === -1 && $.inArray('top_right', styles.additional.all_tails.position) === -1),
                tailWidth = Math.ceil((Math.abs(tailSize) / 2 + (styles.border !== undefined ? parseInt(styles.border.width) / 2 : 0))) * (doubleSize ? 2 : 1),
                offset = (-1) * tailWidth * 2 + borderWidth;
            for (var i in tail.position) {
                switch (tail.position[i]) {
                    case 'top_right':
                        css += '.' + className + ':after{';
                        css += 'top' + ':' + offset + 'px;';
                        css += 'right' + ':' + borderWidth + 'px;';
                        break;
                    case 'top_left':
                        css += '.' + className + ':before{';
                        css += 'top' + ':' + offset + 'px;';
                        css += 'left' + ':' + borderWidth + 'px;';
                        break;
                    case 'bottom_right':
                        css += '.' + className + ' .autobadge-pl-tail:after{';
                        css += 'bottom' + ':' + offset + 'px;';
                        css += 'right' + ':' + borderWidth + 'px;';
                        break;
                    case 'bottom_left':
                        css += '.' + className + ' .autobadge-pl-tail:before{';
                        css += 'bottom' + ':' + offset + 'px;';
                        css += 'left' + ':' + borderWidth + 'px;';
                        break;
                }
                css += 'border-width:' + tailWidth + 'px;';
                css += '}';
            }
            css += this.changePosition(className, styles, tailWidth);
            return css;
        },
        getTailSize: function (contW, badgeW) {
            return Math.ceil((1 - badgeW / 100) * contW / 4) * 2;
        },
        /* Расположение наклейки с хвостами */
        changePosition: function (className, styles, tailWidth) {
            var css = '';
            if (styles.position !== undefined && styles.position.value !== undefined) {
                /* Выбор одного из вариантов */
                var parts = styles.position.value ? styles.position.value.split('_') : ['top', 'right'];
                for (var i in parts) {
                    switch (parts[i]) {
                        case 'top':
                            css += 'top:';
                            if (typeof tailWidth !== 'undefined' && ($.inArray('top_left', styles.additional.all_tails.position) !== -1 || $.inArray('top_right', styles.additional.all_tails.position) !== -1)) {
                                css += 2 * tailWidth + 'px !important';
                            }
                            break;
                        case 'bottom':
                            css += 'bottom:';
                            if (typeof tailWidth !== 'undefined' && ($.inArray('bottom_left', styles.additional.all_tails.position) !== -1 || $.inArray('bottom_right', styles.additional.all_tails.position) !== -1)) {
                                css += 2 * tailWidth + 'px !important';
                            }
                            break;
                    }
                }
                if (css) {
                    css = '.' + className + '{' + css + '}';
                }
            }
            return css;
        },
        /* Изменяем CSS */
        editBadgeCss: function (css) {
            /* Если есть Google fonts, проверяем, стоит ли подключать новые шрифты */
            if (css.google_fonts !== '') {
                var googleFBlock = $(".autobadge-goog-f");
                var curGoogFonts = googleFBlock.length ? googleFBlock.attr("data-fonts").split(',') : '';
                if (curGoogFonts !== '') {
                    var newFonts = $(css.google_fonts).attr("data-fonts").split(',');
                    for (var i in newFonts) {
                        if ($.inArray(newFonts[i], curGoogFonts) === -1) {
                            curGoogFonts.push(newFonts[i]);
                        }
                    }
                    googleFBlock.attr('data-fonts', curGoogFonts.join(',')).attr('href', 'https://fonts.googleapis.com/css?family=' + curGoogFonts.join('%7C'));
                } else {
                    $("head").append(css.google_fonts);
                }
            }
            /* Встроенные стили */
            if (!$.isEmptyObject(css.inline_css)) {
                /* Добавляем общий блок со стилями наклеек */
                if (!$(".autobadge-inline-css").length) {
                    $("head").append('<style class="autobadge-inline-css" data-targets=""></style>');
                }
                var inlineCssBlock = $(".autobadge-inline-css");
                var curCss = inlineCssBlock.attr("data-targets").split(','),
                    cssStyles = "",
                    targets = [];
                if (curCss !== '') {
                    $.each(css.inline_css, function (i, v) {
                        if (v === '') {
                            return true;
                        }
                        if ($.inArray(i, curCss) === -1) {
                            cssStyles += v;
                            curCss.push(i);
                        }
                    });
                    inlineCssBlock.append(cssStyles).attr("data-targets", curCss.join(','));
                } else {
                    $.each(css.inline_css, function (i, v) {
                        if (v === '') {
                            return true;
                        }
                        cssStyles += v;
                        targets.push(i);
                    });
                    inlineCssBlock.append(cssStyles).attr("data-targets", targets.join(','));
                }
            }
        },
        /* Добавление иконки загрузки для наклейки */
        addLoader: function (block) {
            if (!$.autobadgeFrontend.showLoader) {
                return false;
            }
            this.removeLoader(block);
            var loader = '<i class="icon16-autobadge loading-icon"></i>';
            block.children().hide();
            block.append(loader);
        },
        /* Удаление иконки загрузки */
        removeLoader: function (block) {
            if (!$.autobadgeFrontend.showLoader) {
                return false;
            }
            block.children().show();
            block.find(".icon16-autobadge.loading-icon").remove();
        },
        removeTextblocksLoader: function (historyP) {
            $.each(historyP, function (i, v) {
                $.autobadgeFrontend.removeLoader(v.textBlocks);
            });
        },
        reinit: function () {
            /* Счетчик */
            this.initCountdown();
            $.autobadgeFrontend.workupBadges();
        },
        /* Счетчик */
        initCountdown: function () {
            if ($.fn.countdowntimerAB) {
                $('.autobadge-countdown').each(function () {
                    var that = $(this);
                    if (!that.hasClass("inited")) {
                        var $this = $(this).html('');
                        var id = ($this.attr('id') || 'autobadge-countdown' + ('' + Math.random()).slice(2));
                        $this.attr('id', id);
                        var start = $this.data('start').replace(/-/g, '/');
                        var end = $this.data('end').replace(/-/g, '/');
                        $this.countdowntimerAB({
                            startDate: start,
                            dateAndTime: end,
                            size: 'medium'
                        });
                        $(this).removeClass('icon16-autobadge loading-icon').addClass("inited");
                    }
                });
            }
        },
        /* Отложенное обновление размера и позиции наклеек */
        delayAutosize: function (time, timer) {
            clearTimeout(timer);
            timer = setTimeout(function () {
                $.autobadgeFrontend.autoSize();
            }, time);
        },
        /* Дополнительная обработка наклеек */
        workupBadges: function () {
            var loadedCssClasses = $(".autobadge-inline-css").attr('data-targets');
            loadedCssClasses = loadedCssClasses ? loadedCssClasses.split(',') : [];
            var updateProducts = [];

            $(".autobadge-pl").not('.autobadge-inited').each(function () {
                var that = $(this);
                var parent = that.parent();
                /* Установление position: relative для родителя */
                if ($.autobadgeFrontend.forceParentRelative && !parent.hasClass('autobadge-positioned')) {
                    var position = parent.css('position');
                    if (position !== 'absolute' && position !== 'fixed' && position !== 'relative') {
                        parent.css('position', 'relative').addClass("autobadge-positioned");
                    }
                }
                /* Установление overflow: visible для родителя */
                if ($.autobadgeFrontend.forceParentVisible) {
                    parent = that.offsetParent();
                    if (!parent.hasClass("autobadge-visible") && parent.css('overflow') == 'hidden') {
                        parent.css('overflow', 'visible').addClass("autobadge-visible");
                    }
                }

                /* Проверяем, были ли подгружены все необходимые стили для наклейки */
                if (that.data('load-css') && $.inArray('autobadge-pl.' + that.data('load-css'), loadedCssClasses) === -1) {
                    updateProducts.push({
                        id: that.data('product-id'),
                        form: that.parent()
                    });
                }

                that.addClass("autobadge-inited");
            });


            $.autobadgeFrontend.autoSize();
            /* Отложенная загрузка наклеек, чтобы избежать дергание при позиционировании */
            if ($.autobadgeFrontend.delayLoading) {
                $(".autobadge-pl").not('.autobadge-delay-loaded').animate({opacity: '1'}, 500, function () {
                    $(this).addClass('autobadge-delay-loaded');
                });
            }

            /* Подгружаем недостающие CSS стили */
            if (Object.keys(updateProducts).length) {
                $.autobadgeFrontend.updateProductRules(updateProducts);
            }
        },
        /* Поиск элемента-обертки для товара */
        findWrap: function (form) {
            var wrap = form.closest(".flexdiscount-product-wrap, .igaponov-product-wrap" + ($.autobadgeFrontend.wrapElements.length ? ', ' + $.autobadgeFrontend.wrapElements.join(',') : ''));
            if (!wrap.length) {
                var autowrap = form.closest('.s-products-list, .product-list, .lazy-wrapper, ul.thumbnails, .product-thumbs, .product-list');
                if (autowrap.length) {
                    wrap = $("> li, > div, > tr", autowrap);
                }
                if (wrap.length) {
                    wrap.addClass('igaponov-product-wrap');
                }
            }
            return wrap.length ? wrap : $(document);
        }
    };
})(jQuery);