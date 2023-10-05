var Product = (function ($) {

    Product = function (form, options) {
        var self = this;

        self.$form = $(form);
        self.$add2cart = self.$form.find(".productsets-add2cart");
        self.$skusBlock = self.$form.find(".productsets-skus");
        self.$selectTypeSkus = self.$form.find(".productsets-sku-feature");
        self.$alreadyCheckedBlock = self.$form.find('.f-productsets-sku-checked');
        self.$stocks = self.$form.find(".productsets-stocks div");
        self.$mainImage = self.$form.find('.productsets-main-image');
        self.$notInStockBlock = self.$form.find(".productsets-sku-no-stock");

        self.$skuPrice = self.$add2cart.find(".f-productsets-price");
        self.$button = self.$add2cart.find("input[type=button]");
        self.$skuComparePrice = self.$add2cart.find('.f-productsets-compare-price');

        self.$product = self.$form.data('product');
        self.$productSku = self.$product.find('.productsets-item-sku');
        self.$productPrice = self.$product.find('.productsets-price');
        self.$productComparePrice = self.$product.find('.productsets-compare-price');
        self.$productImage = self.$product.find('.productsets-item-image');
        self.$productQuantityInput = self.$product.find('.productsets-item-quantity input');
        self.$productBadge = self.$product.find('.productsets-badge');

        self.parent = self.$form.data('parent');
        self.rubleSign = self.$form.data('rubleSign');
        self.popupController = self.$form.data('controller');

        for (var k in options) {
            this[k] = options[k];
        }

        self.activeSku = null;

        self.bindEvents();
        self.initClass();
    };

    Product.prototype.initClass = function () {
        var self = this;

        self.initHideskus();

        /* Инициируем выбор артикулов */
        var $initial_cb = self.$skusBlock.find("input[type=radio]:checked:not(:disabled)");
        if (!$initial_cb.length) {
            self.$skusBlock.find("input[type=radio]:not(:disabled):first").prop('checked', true).click();
        } else {
            $initial_cb.click();
        }

        self.$selectTypeSkus.first().change();
        self.$form.find('.inline-select a.selected').click();

        if (!self.$skusBlock.find("input:radio:checked").length) {
            self.$skusBlock.find("input:radio:enabled:first").attr('checked', 'checked');
        }
    };

    /* Инициализация встроенного плагина "Скрытие несуществующих артикулов" */
    Product.prototype.initHideskus = function () {
        $.hideskusProductsetsPlugin.locale = $.productsets.options.locale;
        $.hideskusProductsetsPlugin.init();
    };

    Product.prototype.bindEvents = function () {
        var self = this;

        /* Выбор артикулов из списка radio */
        self.$skusBlock.find("input[type=radio]").click(function () {
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
        self.$selectTypeSkus.change(function () {
            var sku = self.getSku();

            /* Плагин "Скрытие несуществующих артикулов" */
            if (typeof $.hideskusProductsetsPlugin !== 'undefined') {
                var key = "";
                self.$selectTypeSkus.each(function () {
                    key += $(this).data('feature-id') + ':' + $(this).val() + ';';
                });
                if (!$.hideskusProductsetsPlugin.start(self, key)) {
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
                    self.$stocks.hide();
                    self.$notInStockBlock.show();
                    self.$alreadyCheckedBlock.hide();
                }
                self.$skuPrice.data('price', sku.price);
                self.updatePrice(sku.price, sku.compare_price);
                self.buttonStatus(sku.available);
            } else {
                self.$stocks.hide();
                self.$notInStockBlock.show();
                self.buttonStatus(0);
                self.$skuComparePrice.hide();
                self.$skuPrice.empty();
            }
        });

        self.$form.find('.inline-select a').click(function () {
            const that = $(this);
            const $block = that.closest('.inline-select');
            $block.find('a.selected').removeClass('selected').find(".fa-check").remove();
            that.addClass('selected');
            $block.hasClass("color") && that.prepend("<i class='fa fa-check'></i>")
            $block.find('.productsets-sku-feature').val(that.data('value')).change();
            return false;
        });

        /* Обработка выбора артикула */
        self.$button.click(function () {
            if (!self.$button.attr('disabled')) {
                self.initSubmit();
            }
        });
    };

    Product.prototype.initSubmit = function () {
        var that = this;

        /* Если артикул в наличии и имеется информация о нем */
        if (that.activeSku) {

            /* Если такой товар существует и он не дублируется */
            if (that.$product.length && that.$product.attr('data-sku-id') !== that.activeSku.id) {

                /* Меняем наименование артикула */
                if (that.activeSku.name) {
                    that.$productSku.text(that.activeSku.name).css('display', 'inline-block');
                } else {
                    that.$productSku.hide();
                }

                /* Изменяем зачеркнутую цену */
                that.$productComparePrice.attr('data-price', that.activeSku.compare_price).attr('data-original-price', that.activeSku.compare_price)
                    .html(that.currencyFormat(that.activeSku.compare_price, that.rubleSign));
                if (that.activeSku.compare_price <= 0) {
                    that.$productPrice.removeClass('productsets-color-price');
                    that.$productComparePrice.hide();
                } else {
                    that.$productPrice.addClass('productsets-color-price');
                    that.$productComparePrice.show();
                }

                /* Изменяем цену товара */
                that.$productPrice.attr('data-price', that.activeSku.price).attr('data-original-price', that.activeSku.original_price)
                    .html(that.currencyFormat(that.activeSku.price, that.rubleSign));

                /* Изменяем наклейку со скидкой */
                var discount = 0;
                if (that.activeSku.compare_price !== 0 && that.activeSku.compare_price > that.activeSku.price) {
                    discount = (100 * (1 - that.activeSku.price / that.activeSku.compare_price));
                    if (discount > 0) {
                        if (discount > 99 || discount < 1) {
                            discount = discount.toFixed(2);
                        } else {
                            discount = discount.toFixed();
                        }
                    }
                }
                if (discount) {
                    that.$productBadge.show();
                } else {
                    that.$productBadge.hide();
                }

                /* Изменяем ID артикула */
                that.$product.attr('data-sku-id', that.activeSku.id).data('sku-id', that.activeSku.id);

                /* Изменяем изображение */
                if (that.$productImage.length && that.activeSku.image) {
                    if (!that.$product.is('.productsets-userbundle-item')) {
                        that.$productImage.width(that.$productImage.find('img').width());
                    }
                    that.$productImage.addClass('productsets-image-loading').attr('data-src', that.activeSku.image);
                    $('<img>').attr('src', that.activeSku.image).load(function () {
                        that.$productImage.removeClass('productsets-image-loading').find('img').attr('src', that.activeSku.image)
                            .removeAttr('width').removeAttr('height');
                    }).each(function () {
                        //ensure image load is fired. Fixes opera loading bug
                        if (this.complete) {
                            $(this).trigger('load');
                        }
                    });
                }

                if (that.$productQuantityInput.length) {
                    that.changeMaxQuantity(that.$productQuantityInput, that.activeSku.count);
                }

                /* Обновляем данные набора */
                that.parent.update();
                setTimeout(function () {
                    that.parent.setController.updatePrices(true);
                }, 0);
            }
        }

        /* Закрываем всплывающее окно */
        that.popupController.close();

    };

    /* Изменяем максимальное количество товара */
    Product.prototype.changeMaxQuantity = function (elem, value) {
        var min = elem.attr('data-min');
        if (min && parseFloat(min) > value) {
            elem.attr('data-min', value);
        }
        elem.attr('data-max', value).trigger('change');
    };

    /* Получаем информацию об артикуле, когда выбор происходит из списков select */
    Product.prototype.getSku = function () {
        var self = this;

        var key = "";
        self.$selectTypeSkus.each(function () {
            key += $(this).data('feature-id') + ':' + $(this).val() + ';';
        });
        return self.features[key];
    };

    /* Меняем изображения при смене артикулов */
    Product.prototype.changeImage = function (imageId) {
        var that = this;

        var image = that.$form.find(".productsets-image-" + imageId).length ? that.$form.find(".productsets-image-" + imageId) : (that.$form.find(".productsets-image-default").length ? that.$form.find(".productsets-image-default") : null);
        if (image) {
            that.$mainImage.html(image.html()).attr('data-large', image.data('large'));
        }
    };

    /* Меняем цены при смене артикулов */
    Product.prototype.updatePrice = function (price, compare_price) {
        var that = this;

        if (price === undefined) {
            var input_checked = that.$skusBlock.find("input:radio:checked");
            if (input_checked.length) {
                price = parseFloat(input_checked.data('price'));
                compare_price = parseFloat(input_checked.data('compare-price'));
            } else {
                price = parseFloat(that.$skuPrice.data('product-price'));
            }
        }
        if (compare_price) {
            if (!that.$skuComparePrice.length) {
                that.$add2cart.prepend('<span data-price="" class="productsets-compare-price f-productsets-compare-price productsets-nowrap"></span>');
            }
            that.$skuComparePrice.attr('data-price', compare_price).html(that.currencyFormat(compare_price, that.rubleSign)).show();
        } else {
            that.$skuComparePrice.hide().attr('data-price', '').html('');
        }

        that.$skuPrice.html(that.currencyFormat(price, that.rubleSign)).attr('data-price', price);
    };

    /* Обновляем информацию об остатках и складах */
    Product.prototype.updateStocks = function (sku_id) {
        var that = this;

        that.$stocks.hide();
        that.$form.find(".productsets-sku-" + sku_id + "-stock").show();
    };

    /* Меняем активность кнопки */
    Product.prototype.buttonStatus = function (status, title) {
        var that = this;

        if (status) {
            that.$button.removeAttr('disabled').removeAttr('title');
            that.updateActiveSku();
            /* Если выбранный артикул уже имеется в корзине, тогда не даем его повторно выбрать */
            if (that.$product.attr('data-sku-id') == that.activeSku.id) {
                that.buttonStatus(0, __('This product is already selected'));
            } else {
                that.$alreadyCheckedBlock.hide();
            }
        } else {
            that.$button.attr('disabled', 'disabled').attr('title', title || __('Product with the selected option combination is not available for purchase'));
            if (title) {
                that.$alreadyCheckedBlock.text(title).show();
            }
            that.activeSku = null;
        }
    };

    /* Изменяем данные активного артикула */
    Product.prototype.updateActiveSku = function () {
        var that = this;

        that.activeSku = {
            'price': that.$skuPrice.attr('data-price'),
            'original_price': that.$skuPrice.attr('data-price'),
            'compare_price': that.$skuComparePrice.attr('data-price') ? that.$skuComparePrice.attr('data-price') : 0
        };
        if (that.$skusBlock.find("input[type=radio]").length) {
            var checkedInput = that.$skusBlock.find("input[type=radio]:checked");
            that.activeSku['id'] = checkedInput.val();
            that.activeSku['count'] = checkedInput.data('count') !== undefined && checkedInput.data('count') !== '' ? checkedInput.data('count') : '';
            that.activeSku['name'] = checkedInput.siblings('.f-productsets-popup-sku-name').html();
        } else if (that.$selectTypeSkus.length) {
            var sku = that.getSku();
            if (sku && sku.available) {
                that.activeSku['id'] = sku.id;
                that.activeSku['name'] = that.getEscapedText(sku.name);
                that.activeSku['count'] = sku.count;
            }
        }
        that.activeSku['image'] = that.$mainImage.attr('data-large');
    };

    Product.prototype.getEscapedText = function (bad_string) {
        return $("<div>").text(bad_string).html();
    };

    Product.prototype.currencyFormat = function (number, no_html) {
        // Format a number with grouped thousands
        //
        // +   original by: Jonas Raoni Soares Silva (http://www.jsfromhell.com)
        // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
        // +	 bugfix by: Michael White (http://crestidg.com)

        var i, j, kw, kd, km;
        var decimals = this.currency.frac_digits;
        var dec_point = this.currency.decimal_point;
        var thousands_sep = this.currency.thousands_sep;

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
        //kd = (decimals ? dec_point + Math.abs(number - i).toFixed(decimals).slice(2) : "");
        kd = (decimals && (number - i) ? dec_point + Math.abs(number - i).toFixed(decimals).replace(/-/, 0).slice(2) : "");


        var number = km + kw + kd;
        var s = no_html ? this.currency.sign : this.currency.sign_html;
        if (!this.currency.sign_position) {
            return s + this.currency.sign_delim + number;
        } else {
            return number + this.currency.sign_delim + s;
        }
    };

    return Product;

})(jQuery);

$.hideskusProductsetsPlugin={inlineItem:"",inlineRow:"",locale:"",selectedClass:"selected",disabledClass:"hideskus-disabled",disabledRadioClass:"hideskus-radio-disabled",hiddenOptionClass:"hideskus-option-hide",hideNotInStock:0,gotoVisible:1,delayStart:1,force:1,messages:{ru_RU:{Empty:"Отсутствует","Not in stock":"Нет в наличии"}},translate:function(e){return void 0!==this.messages[this.locale]&&this.messages[this.locale][e]?this.messages[this.locale][e]:e},init:function(e){this.hideNotInStock=void 0!==e&&void 0!==e.hide_not_in_stock?parseInt(e.hide_not_in_stock):0,this.gotoVisible=void 0!==e&&void 0!==e.go_to_available?parseInt(e.go_to_available):1,this.delayStart=void 0!==e&&void 0!==e.delay?parseInt(e.delay):1,this.delayStart&&($(document).on("change",".productsets-sku-feature",function(){this.delayStart=0}),setTimeout(function(){$.hideskusProductsetsPlugin.delayStart&&$(".productsets-sku-feature").length&&$(".productsets-sku-feature:first").change()},1e3)),$(function(){var e=$('input:radio[name="productsets[sku_id]"]');if(e.length){var i=0,s={},t={};e.each(function(){var e=$(this),a=e.closest("form").find("input[name='product_id']").val();if(e.prop("disabled")||1==e.data("disabled")){var l=e.closest("li").length?e.closest("li"):e.closest("div");$.hideskusProductsetsPlugin.hideNotInStock?(l.hide(),e.is(":checked")&&(i=1)):l.addClass($.hideskusProductsetsPlugin.disabledRadioClass).attr("title",$.hideskusProductsetsPlugin.translate("Not in stock"))}else void 0===t[a]&&(s[a]=e,t[a]=1)}),(i||$.hideskusProductsetsPlugin.gotoVisible)&&($.isEmptyObject(s)||setTimeout(function(){$.each(s,function(){$(this).click()})},800))}})},setOptions:function(e){this.inlineItem=void 0!==e&&void 0!==e.inlineItem?e.inlineItem:"",this.inlineRow=void 0!==e&&void 0!==e.inlineRow?e.inlineRow:"",this.selectedClass=void 0!==e&&void 0!==e.selectedClass?e.selectedClass:"selected",this.disabledClass=void 0!==e&&void 0!==e.disabledClass?e.disabledClass:"hideskus-disabled",this.disabledRadioClass=void 0!==e&&void 0!==e.disabledRadioClass?e.disabledRadioClass:"hideskus-radio-disabled"},start:function(e,i){function s(e,i){return i=i||!1,o.inlineItem&&e.closest(o.inlineItem).length?i?e.closest(o.inlineItem).siblings(o.inlineItem).andSelf():e.closest(o.inlineItem).siblings(o.inlineItem):o.inlineItem&&e.siblings(o.inlineItem).length?e.siblings(o.inlineItem):e.siblings("a").length?(o.inlineItem="a",e.siblings("a")):e.parent("label").length?(o.inlineItem="label",i?e.parent().siblings("label").andSelf():e.parent().siblings("label")):{}}function t(e,i){void 0===k[i=i||"disable"][e]&&(k[i][e]=0),k[i][e]++}function a(e,i){if(void 0!==k[i=i||"disable"][e])return k[i][e];var s=0;for(var t in k[i])-1!==t.indexOf(e)&&(s+=k[i][t]);return s||-1}function l(e){return i.split(";").length!==e.split(";").length}function d(e,s,t){!l(e)&&i==e&&t&&(p.findVisible[e]={path:e,skuFeat:s}),p.skuFeat[s.id]={path:e,skuFeat:s}}function n(e,i){i?(void 0===e.attr("data-old-title")&&e.attr("data-old-title",void 0!==e.attr("title")?e.attr("title"):""),e.attr("title",i)):void 0!==e.attr("data-old-title")?e.attr("title",e.attr("data-old-title")):e.removeAttr("title")}var o=this,r=void 0!==e.form?e.form:void 0!==e.$form?e.$form:$(e).closest("form").length?$(e).closest("form"):"",u=void 0!==e.features?e.features:"undefined"!=typeof sku_features?sku_features:"";if(!r||!u)return console&&console.warn('Plugin "hideskus". \r\n'+(r?"":"Form not exists.")+(u?"":"Features not exist.")),!0;var c,f=(r=r instanceof jQuery?r:$(r)).find(".productsets-sku-feature:first"),h=f.data("feature-id")+":"+f.val()+";",p={findVisible:{},skuFeat:{}},v=0,b={},k={disable:{},hide:{}},m={},g={},C={};if(i=i||(c="",r.find(".productsets-sku-feature").each(function(){var e=$(this);c+=e.data("feature-id")+":"+e.val()+";"}),c),r.find(".productsets-sku-feature").each(function(e){var i,t=$(this),a=t.data("feature-id");if(void 0!==C[a])return!0;var l=t.is("input");if(C[a]=1,l){var d=t.siblings(".temp-block"),n=d.length?d:t.parent().find(".temp-block").length?t.parent().find(".temp-block"):"";n.length&&n.remove()}else t.prop("disabled",!1).find(".temp-block").remove();i={id:a,isInput:l,isRadio:t.is("input:radio"),fields:l?s(t,!0):t.find("option").length?t.find("option"):{}},0===e&&(m=i),g.children=i,g=i}),function e(s,r,c,f){var h=r,p=-1!==i.indexOf(h),v=!1;return b[f]=1,$.each(c.fields,function(k,m){var g=void 0!==(m=$(m)).data("value")?m.data("value"):c.isRadio?m.find("input").val():m.val();if(r+=c.id+":"+g+";",void 0!==c.children){var C=e(s,r,c.children,f+1);v||(b[f]=C.hideLimit),r+=C.key}var I=h+c.id+":"+g+";",y=a(I,"hide"),P=a(I)+(-1!==y?y:0),O=P>=b[f]&&p,_=y>=b[f]&&p,R=P<b[f]&&p,S=y<b[f]&&p,w=1==b[f]&&p;!l(I)&&!u[r]||_?(_||t(h,"hide"),(w||_)&&(m.hide(),c.isInput||m.addClass(o.hiddenOptionClass),d(I,c,1))):O||u[r]&&!u[r].available?(O||t(h,o.hideNotInStock?"hide":"disable"),(w||O)&&(o.hideNotInStock?(m.hide(),c.isInput||m.addClass(o.hiddenOptionClass)):(m.show(),c.isInput||m.removeClass(o.hiddenOptionClass)),c.isInput?(m.addClass(o.disabledClass),n(m,$.hideskusProductsetsPlugin.translate("Not in stock"))):(m.addClass(o.disabledClass),-1!==i.indexOf(I)&&m.closest("select").addClass(o.disabledClass)),d(I,c,o.hideNotInStock?1:o.gotoVisible?1:o.force?1:0))):(R||w||S)&&(c.isInput?(m.show().removeClass(o.disabledClass),n(m)):(m.show().removeClass(o.disabledClass).removeClass(o.hiddenOptionClass),-1!==i.indexOf(I)&&m.closest("select").removeClass(o.disabledClass))),r=h,v=!0}),{key:r,hideLimit:void 0!==c.children?b[f]*c.fields.length:c.fields.length}}(h,"",m,0),!$.isEmptyObject(p.findVisible))if(function(){if(!$.isEmptyObject(p.findVisible)){var e=function(i){function t(e){var i=e.split(";");for(var t in i)if(""!=i[t]){var a=i[t].split(":"),l=r.find(".productsets-sku-feature[name='productsets[features]["+a[0]+"]']");if(l.is("input:radio")&&(l=r.find(".productsets-sku-feature[name='productsets[features]["+a[0]+"]'][value='"+a[1]+"']")),l.is("input")){var d=o.inlineRow?l.closest(o.inlineRow):l.parent().parent(),n=s(l);if(n.removeClass(o.selectedClass),l.is("input:radio")?(d.find(".productsets-sku-feature").prop("checked",!1),l.prop("checked",!0)):(l.attr("value",a[1]),n.find("[data-value='"+a[1]+"']").addClass(o.selectedClass),n.filter("[data-value='"+a[1]+"']").addClass(o.selectedClass)),l.parent(".color").length){var u=l.parent(".color").find(".fa-check");if(u.length){var c=l.siblings("[data-value='"+a[1]+"']");c.find(".fa-check").length||(u.remove(),c.append('<i class="fa fa-check"></i>'))}}}else l.find("option").removeClass(o.selectedClass).prop("selected",!1).siblings("[value='"+a[1]+"']").addClass(o.selectedClass).prop("selected",!0),l.val(a[1])}}var a=i.substring(0,i.lastIndexOf(":")),d=2===a.split(":").length,n=null;if(""===a)return!1;for(var c in u)if(!l(c)&&-1!==c.indexOf(a)){if(u[c].available)return t(c),!(v=1);null===n&&c!==i&&d&&(n=c)}return null===n||o.gotoVisible||o.force?e(a):(t(n),!(v=1))};$.each(p.findVisible,function(i,s){e(s.path)})}}(),v){if(v)return f.change(),!1}else $.each(p.skuFeat,function(e,i){if(!i.skuFeat.fields.is(":visible")){var s=i.skuFeat.fields.first(),t=s.clone(),a=t.find("span").length?t.find("span"):t;t.addClass("temp-block "+o.disabledClass).css("display","inline-block"),""!==a.text()&&a.html($.hideskusProductsetsPlugin.translate("Empty")),s.after(t.prop("outerHTML"))}});return!(o.force=0)}};