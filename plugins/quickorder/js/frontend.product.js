var Product = (function ($) {

    Product = function (form, options) {
        var self = this;

        self.form = $(form);
        self.quickorderForm = self.form.data('form');
        self.add2cart = self.form.find(".quickorder-add2cart");
        self.button = self.add2cart.find("input[type=button]");
        for (var k in options) {
            this[k] = options[k];
        }

        self.activeSku = null;

        self.bindEvents();
        self.initClass();
    };

    Product.prototype.initClass = function () {
        var self = this;

        /* Инициируем выбор артикулов */
        var $initial_cb = self.form.find(".quickorder-skus input[type=radio]:checked:not(:disabled)");
        if (!$initial_cb.length) {
            self.form.find(".quickorder-skus input[type=radio]:not(:disabled):first").prop('checked', true).click();
        } else {
            $initial_cb.click();
        }

        self.form.find(".quickorder-sku-feature:first").change();

        if (!self.form.find(".quickorder-skus input:radio:checked").length) {
            self.form.find(".quickorder-skus input:radio:enabled:first").attr('checked', 'checked');
        }
    };

    Product.prototype.bindEvents = function () {
        var self = this;

        /* Выбор артикулов из списка radio */
        self.form.find(".quickorder-skus input[type=radio]").click(function () {
            var that = $(this);
            /* Изменяем изображение */
            if (that.data('image-id')) {
                self.changeImage(that.data('image-id'));
            }
            self.updateStocks(that.val());
            self.updatePrice();
            self.buttonStatus(!that.data('disabled'));
        });

        /* Выбор артикулов из списка select */
        self.form.find(".quickorder-sku-feature").change(function () {
            var sku = self.getSku();

            /* Плагин "Скрытие несуществующих артикулов" */
            if (typeof $.hideskusQuickorderPlugin !== 'undefined') {
                var key = "";
                self.form.find(".quickorder-sku-feature").each(function () {
                    key += $(this).data('feature-id') + ':' + $(this).val() + ';';
                });
                if (!$.hideskusQuickorderPlugin.start(self, key)) {
                    return false;
                }
            }

            if (sku) {
                /* Изменяем изображение */
                if (sku.image_id) {
                    self.changeImage(sku.image_id);
                }
                self.updateStocks(sku.id);
                if (!sku.available) {
                    self.form.find(".quickorder-stocks div").hide();
                    self.form.find(".quickorder-sku-no-stock").show();
                }
                self.add2cart.find(".f-quickorder-price").data('price', sku.price);
                self.updatePrice(sku.price, sku.compare_price);
                self.buttonStatus(sku.available);
            } else {
                self.form.find(".quickorder-stocks div").hide();
                self.form.find(".quickorder-sku-no-stock").show();
                self.buttonStatus(0);
                self.add2cart.find(".f-quickorder-compare-price").hide();
                self.add2cart.find(".f-quickorder-price").empty();
            }
        });

        /* Обработка выбора артикула */
        self.button.click(function () {
            if (!self.button.attr('disabled')) {
                self.initSubmit();
            }
        });
    };

    Product.prototype.initSubmit = function () {
        var that = this;

        /* Если артикул в наличии и имеется информация о нем */
        if (that.activeSku) {

            /* Находим строку товара, откуда было вызвано всплывающее окно */
            var skuRow = that.quickorderForm.find('.quickorder-product[data-sku-id="' + that.form.data('sku-id') + '"]');

            /* Если такой товар существует и он не дублируется */
            if (skuRow.length && !that.quickorderForm.find('.quickorder-product[data-sku-id="' + that.activeSku.id + '"]').length) {

                /* Меняем наименование артикула */
                if (that.activeSku.name) {
                    skuRow.find('.quickorder-sku-name').text(that.activeSku.name).css('display', 'inline-block');
                } else {
                    skuRow.find('.quickorder-sku-name').hide();
                }

                /* Изменяем зачеркнутую цену */
                skuRow.find('.quickorder-compare-price').attr('data-price', that.activeSku.compare_price);

                /* Изменяем цену товара */
                skuRow.find('.quickorder-price').attr('data-price', that.activeSku.price).attr('data-original-price', that.activeSku.original_price);

                /* Изменяем ID артикула */
                skuRow.find('input[name="quickorder_product[sku_id]"]').val(that.activeSku.id);
                skuRow.attr('data-sku-id', that.activeSku.id);

                /* Изменяем изображение */
                var formImage = skuRow.find('.quickorder-image');
                if (formImage.length) {
                    formImage.width(formImage.find('img').width()).addClass('quickorder-image-loading');
                    $('<img>').attr('src', that.activeSku.image).load(function () {
                        formImage.find('img').attr('src', that.activeSku.image).removeAttr('width').removeAttr('height').parent().removeClass('quickorder-image-loading').width('1%');
                    }).each(function () {
                        //ensure image load is fired. Fixes opera loading bug
                        if (this.complete) {
                            $(this).trigger('load');
                        }
                    });
                }

                /* Заменяем услуги у товара */
                if (skuRow.find('.quickorder-services').length) {
                    skuRow.addClass("q-is-loading");
                    $.post(that.form.data('serviceUrl'), {
                        qformtype: that.quickorderForm.attr("data-quickorder-pf") !== undefined ? 'product' : 'cart',
                        id: skuRow.data('product-id'),
                        sku_id: that.activeSku.id,
                        services: skuRow.find('.quickorder-services').find('input, select').serialize()
                    }, function (response) {
                        if (response.status == 'ok' && response.data !== '') {
                            skuRow.find('.quickorder-services').replaceWith(response.data);
                        }
                    }, "json").always(function () {
                        skuRow.removeClass("q-is-loading");
                        that.changeMaxQuantity(skuRow.find('input[name="quickorder_product[quantity]"]'), that.activeSku.count);
                    });
                } else {
                    that.changeMaxQuantity(skuRow.find('input[name="quickorder_product[quantity]"]'), that.activeSku.count);
                }
            }
        }

        /* Закрываем всплывающее окно */
        that.form.trigger('closeSkus');

    };

    /* Изменяем максимальное количество товара */
    Product.prototype.changeMaxQuantity = function (elem, value) {
        elem.attr('data-max', value).trigger('change');
    };

    /* Получаем информацию об артикуле, когда выбор происходит из списков select */
    Product.prototype.getSku = function () {
        var self = this;

        var key = "";
        self.form.find(".quickorder-sku-feature").each(function () {
            key += $(this).data('feature-id') + ':' + $(this).val() + ';';
        });
        return self.features[key];
    };

    /* Меняем изображения при смене артикулов */
    Product.prototype.changeImage = function (imageId) {
        var that = this;

        var image = that.form.find(".quickorder-image-" + imageId);
        if (image.length) {
            that.form.find('.quickorder-main-image').html(image.html()).attr('data-large', image.data('large'));
        }
    };

    /* Меняем цены при смене артикулов */
    Product.prototype.updatePrice = function (price, compare_price) {
        var that = this;

        if (price === undefined) {
            var input_checked = that.form.find(".quickorder-skus input:radio:checked");
            if (input_checked.length) {
                price = parseFloat(input_checked.data('price'));
                compare_price = parseFloat(input_checked.data('compare-price'));
            } else {
                price = parseFloat(that.add2cart.find(".f-quickorder-price").data('product-price'));
            }
        }
        if (compare_price) {
            if (!that.add2cart.find(".f-quickorder-compare-price").length) {
                that.add2cart.prepend('<span data-price="" class="quickorder-compare-price f-quickorder-compare-price quickorder-nowrap"></span>');
            }
            that.add2cart.find(".f-quickorder-compare-price").attr('data-price', compare_price).html($.quickorder.currencyFormat(compare_price, !that.quickorderForm.data('ruble-sign'))).show();
        } else {
            that.add2cart.find(".f-quickorder-compare-price").hide().attr('data-price', '').html('');
        }

        that.add2cart.find(".f-quickorder-price").html($.quickorder.currencyFormat(price, !that.quickorderForm.data('ruble-sign'))).attr('data-price', price);
    };

    /* Обновляем информацию об остатках и складах */
    Product.prototype.updateStocks = function (sku_id) {
        var that = this;

        that.form.find(".quickorder-stocks div").hide();
        that.form.find(".quickorder-sku-" + sku_id + "-stock").show();
    };

    /* Меняем активность кнопки */
    Product.prototype.buttonStatus = function (status, title) {
        var that = this;

        if (status) {
            that.button.removeAttr('disabled').removeAttr('title').show();
            that.updateActiveSku();
            /* Если выбранный артикул уже имеется в корзине, тогда не даем его повторно выбрать */
            if (that.quickorderForm.find('.quickorder-product[data-sku-id="' + that.activeSku.id + '"]').length) {
                that.buttonStatus(0, 'This product is already selected');
            } else {
                that.form.find('.f-quickorder-sku-checked').hide();
            }
        } else {
            that.button.hide().attr('disabled', 'disabled').attr('title', $.quickorder.translate(title || 'Product with the selected option combination is not available for purchase'));
            if (title) {
                that.form.find('.f-quickorder-sku-checked').text($.quickorder.translate(title)).show();
            }
            that.activeSku = null;
        }
    };

    /* Изменяем данные активного артикула */
    Product.prototype.updateActiveSku = function () {
        var that = this;

        that.activeSku = {
            'price': that.add2cart.find(".f-quickorder-price").attr('data-price'),
            'original_price': that.add2cart.find(".f-quickorder-price").attr('data-price'),
            'compare_price': that.add2cart.find(".f-quickorder-compare-price").attr('data-price') ? that.add2cart.find(".f-quickorder-compare-price").attr('data-price') : 0
        };
        if (that.form.find(".quickorder-skus input[type=radio]").length) {
            var checkedInput = that.form.find(".quickorder-skus input[type=radio]:checked");
            that.activeSku['id'] = checkedInput.val();
            that.activeSku['count'] = checkedInput.data('count') !== undefined && checkedInput.data('count') !== '' ? checkedInput.data('count') : '';
            that.activeSku['name'] = checkedInput.siblings('.f-quickorder-popup-sku-name').html();
        } else if (that.form.find(".quickorder-sku-feature").length) {
            var sku = that.getSku();
            if (sku && sku.available) {
                that.activeSku['id'] = sku.id;
                that.activeSku['name'] = that.getEscapedText(sku.name);
                that.activeSku['count'] = sku.count;
            }
        }
        that.activeSku['image'] = that.form.find('.quickorder-main-image').attr('data-large');
    };

    Product.prototype.getEscapedText = function (bad_string) {
        return $("<div>").text(bad_string).html();
    };

    return Product;

})(jQuery);