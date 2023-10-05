var ProductsetsProdDialogBackend = (function ($) {

    ProductsetsProdDialogBackend = function (options) {
        var that = this;

        /* DOM */
        that.$wrap = options.wrap;
        that.productsBlock = that.$wrap.find(".s-product-block");
        that.selectedProductsBlock = that.$wrap.find('.selected-products');
        that.$showMore = that.$wrap.find('.s-show-more-products');

        /* VARS */
        that.loader = '<i class="icon16 loading"></i>';
        that.items = options.items || [];

        /* INIT */
        that.initClass();
        that.bindEvents();
    };

    ProductsetsProdDialogBackend.prototype.initClass = function () {
        var that = this;

        that.initAutocomplete();

        /* Сортировка товаров */
        that.$wrap.find('.selected-products').sortable({
            distance: 5,
            opacity: 0.75,
            items: '.selected-products__item',
            handle: '.selected-products__item-inner',
            cursor: 'move',
            tolerance: 'pointer'
        });

        /* Заполняем товары, которые уже выбраны у комплекта */
        $.each(that.items, function(i,v) {
           that.addProductToSelected(v, true, true);
        });
    };

    ProductsetsProdDialogBackend.prototype.bindEvents = function () {
        var that = this;

        /* Получение товаров */
        that.$wrap.find('.f-get-products').on('click', function () {
            that.getProducts($(this));
        });

        /* Сворачивание/разворачивание меню */
        that.$wrap.find(".f-collapse").click(function () {
            var elem = $(this);
            var i = elem.find("i");

            if (i.hasClass('darr')) {
                i.removeClass('darr').addClass('rarr');
            } else {
                i.removeClass('rarr').addClass('darr');
            }
            elem.next().toggle();
        });

        /* Отбражение всех выбранных товаров. Нажатие "Показать еще" */
        that.$wrap.find('.js-show-more-products').on('click', function () {
            that.selectedProductsBlock.addClass('show-all');
            that.$showMore.hide();
        });
    };

    ProductsetsProdDialogBackend.prototype.initEvents = function () {
        var that = this;

        /* Выбор варианта товара */
        that.$wrap.find(".s-product-block tr:not(.table-inside) > td").off('click').on('click', function () {
            var td = $(this);
            var tr = td.closest("tr");
            if (td.hasClass("f-skus")) {
                that.dialogShowSkus(td, tr, tr.attr("data-id"));
            } else {
                that.addProductToSelected(tr);
            }
        });
        that.$wrap.find('.f-get-products').off('click').on('click', function () {
            that.getProducts($(this));
        });
    };

    /* Поиск товаров */
    ProductsetsProdDialogBackend.prototype.initAutocomplete = function () {
        var that = this;

        var jqxhr = null;
        var elem = that.$wrap.find(".product-autocomplete");

        elem.on('keyup', function () {

            jqxhr && jqxhr.abort();

            clearTimeout(elem.data('autocomplete-timer'));
            elem.data('autocomplete-timer', setTimeout(function () {
                elem.addClass('ui-autocomplete-loading');
                var findInSkus = that.productsBlock.find(".f-autocomplete-skus").prop("checked");
                var url = '?plugin=productsets&action=autocomplete' + (findInSkus ? '&with_skus=1' : '');
                jqxhr = $.get(url, {term: elem.val()}, function (response) {
                    if (response) {
                        /* Скрываем предыдущие результаты поиска */
                        that.productsBlock.find('.js-search-result').hide();

                        var products = "";
                        $.each(response, function (i, v) {
                            v.findInSkus = findInSkus;
                            products += tmplPs('tmpl-products-tr', v);
                        });
                        var html = tmplPs('tmpl-products-table', {rows: products, end: 1});
                        if (that.productsBlock.find('.s-search').length) {
                            that.productsBlock.find('.s-search').html(html).show();
                        } else {
                            $("<div />", {class: 's-search js-search-result'}).html(html).appendTo(that.productsBlock);
                        }
                        that.initEvents();
                        that.$wrap.find(".sidebar .selected").removeClass('selected');
                        ResultBlock.Content = that.productsBlock.find(".s-search");
                        ResultBlock.highlightSelectedProducts(findInSkus);
                    }
                }, "json").always(function () {
                    elem.removeClass('ui-autocomplete-loading');
                });
            }, 500));
        });
    };

    /* Добавляем товары в список выбранных. При повторном нажатии удаляем */
    ProductsetsProdDialogBackend.prototype.addProductToSelected = function (block, clearData, notSelect) {
        var that = this;
        var dataInfo = clearData ? block : block.data(),
            selectedProduct = that.selectedProductsBlock.find('.selected-products__item[data-sku-id="' + dataInfo.skuId + '"][data-type="' + dataInfo.type + '"]');
        if (!selectedProduct.length) {
            that.selectedProductsBlock.append($(tmplPs('tmpl-selected-product', dataInfo)).data('product', dataInfo));
            that.addSelected(dataInfo);
        } else {
            that.selectedProductsBlock.find('.selected-products__item[data-sku-id="' + dataInfo.skuId + '"][data-type="' + dataInfo.type + '"]').remove();
        }
        if (!notSelect) {
            ResultBlock.highlightSelectedProducts();
        }
        that.toggleShowMore();
    };

    ProductsetsProdDialogBackend.prototype.addSelected = function (data) {
        var that = this;
        /* Если необходимо добавить основной товар, тогда удаляем все артикулы */
        if (data.type == 'product') {
            that.selectedProductsBlock.find('.selected-products__item[data-id="' + data.id + '"][data-type="sku"]').remove();
        }
        /* Если необходимо добавить артикулы, тогда удаляем основной товар */
        else {
            that.selectedProductsBlock.find('.selected-products__item[data-id="' + data.id + '"][data-type="product"]').remove();
        }
    };

    /* Появление/исчезания ссылки "Показать еще" при добавлении товаров */
    ProductsetsProdDialogBackend.prototype.toggleShowMore = function () {
        var that = this;

        if (!that.selectedProductsBlock.hasClass('show-all')) {
            var count = that.getSelectedProducts().length;
            if (count > 12) {
                that.$showMore.show().find('.indicator').text(count - 12);
            } else {
                that.$showMore.hide();
            }
        }
    };

    /* Получить список выбранных товаров */
    ProductsetsProdDialogBackend.prototype.getSelectedProducts = function () {
        var that = this;
        return $.makeArray(that.selectedProductsBlock.find('.selected-products__item').map(function () {
            return $(this).data('product');
        }));
    };

    /* События при клике на тело блока */
    ProductsetsProdDialogBackend.prototype.initJSEvents = function (event) {
        var that = this;

        var elem = $(event.target);
        /* Удаление выбранных товаров */
        if (elem.is('.js-delete-product')) {
            elem.closest(".selected-products__item").remove();
            ResultBlock.highlightSelectedProducts();
            that.toggleShowMore();
        }
    };

    /* Получаем список товаров */
    ProductsetsProdDialogBackend.prototype.getProducts = function (elem) {
        ResultBlock.update(elem);

        /* Загружаем данные о товарах */
        if (!ResultBlock.Content.length || ResultBlock.isPaging) {
            if (!ResultBlock.isLoading()) {
                ResultBlock.showLoading();
                ResultBlock.find();
            }
        }
        /* Если товары уже были найдены, отображаем их */
        else if (ResultBlock.Content.length && !ResultBlock.isPaging) {
            ResultBlock.Content.show();
            ResultBlock.highlightSidebar();
            ResultBlock.highlightSelectedProducts();
            ResultBlock.scrollToTop();
        }
    };

    /* Получение списка вариантов товара */
    ProductsetsProdDialogBackend.prototype.dialogShowSkus = function (btn, tr, productId) {
        var that = this;

        var tableInside = tr.next(".table-inside");

        if (tableInside.length) {
            toogleTable(tableInside);
        } else if (!isLoading()) {
            addLoading();
            $.post("?plugin=productsets&action=getProductSkus", {id: productId}, function (response) {
                createTableInside(response);
                removeLoading();
                that.initEvents();
                ResultBlock.highlightSelectedProducts();
            });
        }

        function toogleTable(table) {
            if (!table.is(":visible")) {
                table.show().find("div").slideDown();
            } else {
                table.find("div").slideUp(function () {
                    table.hide();
                });
            }
        }

        function isLoading() {
            return btn.find(".loading").length;
        }

        function addLoading() {
            btn.find("i").removeClass("view-thumb-list").addClass("loading");
        }

        function removeLoading() {
            btn.find("i").removeClass("loading").addClass("view-thumb-list");
        }

        function createTableInside(response) {
            var html = "";
            if (response.status == 'ok' && response.data) {
                var rows = "";
                $.each(response.data, function (i, v) {
                    rows += tmplPs('tmpl-products-tr', $.extend({inside: 1}, v));
                });
                html = tmplPs('tmpl-products-table-inside', {rows: rows});
            }
            tr.after(html);
            toogleTable(tr.next(".table-inside"));
        }
    };

    return ProductsetsProdDialogBackend;

})(jQuery);

var ResultBlock = (function () {
    return {
        init: function (dialog) {
            this.dialog = dialog;
        },
        update: function (activeElement) {
            this.type = activeElement.data('type');
            this.id = activeElement.data("id");
            this.page = activeElement.attr("data-page");
            this.blockClass = this.type + '-' + this.id;
            this.activeElement = {
                obj: activeElement,
                type: activeElement.hasClass("button") ? 'button' : 'link'
            };
            ResultBlock.Content = ResultBlock.dialog.productsBlock.find("." + ResultBlock.blockClass);
            ResultBlock.MoreButton = ResultBlock.Content.find(".f-more");
            this.isPaging = this.page !== undefined;
            /* Скрываем предыдущие результаты поиска */
            !this.isPaging && this.dialog.productsBlock.find('.js-search-result').hide();
        },
        isLoading: function () {
            if (ResultBlock.activeElement.type == 'button') {
                return ResultBlock.activeElement.obj.hasClass("is-loading");
            } else {
                return this.dialog.productsBlock.hasClass("is-loading");
            }
        },
        showLoading: function () {
            ResultBlock.activeElement.obj.after("<i class='icon16 loading'></i>");
            if (ResultBlock.activeElement.type == 'button') {
                ResultBlock.activeElement.obj.addClass("is-loading");
            } else {
                this.dialog.productsBlock.addClass("is-loading")
            }
        },
        hideLoading: function () {
            this.dialog.productsBlock.removeClass("is-loading");
            ResultBlock.activeElement.obj.next(".loading").remove();
            if (ResultBlock.activeElement.type == 'button') {
                ResultBlock.activeElement.obj.removeClass('is-loading');
            }
        },
        highlightSidebar: function () {
            this.dialog.$wrap.find(".sidebar .selected").removeClass('selected');
            ResultBlock.activeElement.obj.closest("li").addClass("selected");
        },
        highlightSelectedProducts: function (ignoreType) {
            if (ResultBlock.Content) {
                ResultBlock.Content.find('.selected').removeClass('selected').end().find('.has-selected-sku').removeClass('has-selected-sku');
                $.each(ResultBlock.dialog.getSelectedProducts(), function (i, v) {
                    ResultBlock.Content.find('tr[data-sku-id="' + v.skuId + '"]' + (!ignoreType ? '[data-type="' + v.type + '"]' : '')).addClass('selected');
                    if (v.type == 'sku') {
                        ResultBlock.Content.find('tr[data-sku-id="' + v.skuId + '"][data-type="product"]').addClass('has-selected-sku');
                    }
                });
            }
        },
        scrollToTop: function () {
            if (ResultBlock.activeElement.type !== 'button') {
                this.dialog.$wrap.find(".w-dialog-wrapper").scrollTop(0);
            }
        },
        createContent: function (html) {
            $("<div />", {class: ResultBlock.blockClass + ' js-search-result'}).html(html).appendTo(this.dialog.productsBlock);
        },
        createNotFoundContent: function () {
            var html = "<div class='bordered margin-block block align-center'>" + $__('Products not found') + "</div>";
            this.createContent(html);
        },
        appendToContent: function (html, response) {
            ResultBlock.Content.find("tbody").append(html);
            ResultBlock.MoreButton.remove();
            if (!response.data.end) {
                ResultBlock.Content.find("table").after(
                    tmplPs('tmpl-products-more', {
                        type: ResultBlock.type,
                        id: ResultBlock.id,
                        page: (typeof response.data.page !== 'undefined' ? response.data.page : '1')
                    })
                );
            }
        },
        find: function () {
            $.post("?plugin=productsets&action=getProducts", {
                id: ResultBlock.id,
                type: ResultBlock.type,
                page: ResultBlock.page
            }, function (response) {
                ResultBlock.processResponse(response);
            }).done(function () {
                ResultBlock.highlightSidebar();
                ResultBlock.Content.show();
            }).always(function () {
                ResultBlock.hideLoading();
                ResultBlock.scrollToTop();
            });
        },
        processResponse: function (response) {
            if (response.status == 'ok' && response.data && response.data.products) {
                var products = "";
                $.each(response.data.products, function (i, v) {
                    products += tmplPs('tmpl-products-tr', v);
                });
                if (products) {
                    if (!ResultBlock.isPaging && !ResultBlock.Content.length) {
                        var html = tmplPs('tmpl-products-table', {
                            rows: products,
                            end: response.data.end,
                            type: ResultBlock.type,
                            id: ResultBlock.id,
                            page: (typeof response.data.page !== 'undefined' ? response.data.page : '1')
                        });
                        ResultBlock.createContent(html);
                    }
                    /* Подрузка данных */
                    else {
                        ResultBlock.appendToContent(products, response);
                    }
                } else {
                    ResultBlock.createNotFoundContent();
                }
                ResultBlock.Content = ResultBlock.dialog.productsBlock.find("." + ResultBlock.blockClass);
                ResultBlock.MoreButton = ResultBlock.Content.find(".f-more");

                ResultBlock.highlightSelectedProducts();
                ResultBlock.dialog.initEvents();
            } else {
                ResultBlock.createNotFoundContent();
            }
        }
    }
})();