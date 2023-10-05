(function($) {
    'use sctrict'
    $.clicklite = {
        mask:'',
        url:'',
        yandex:'',
        policyCheckbox:'',
        currency:'',
        ecommerce:'',
        templates:'',
        skus:'',
        skuFeature:'',

        init: function(options, templates) {
            this.mask = options.mask || false;
            this.url = options.url || '/';
            this.yandex = options.yandex || '';
            this.policyCheckbox = options.policyCheckbox || false;
            this.currency = options.currency || '';
            this.ecommerce = options.ecommerce || false;
            this.templates = templates;

            this.initSkuFeature();
            this.initView();
            this.initSubmitProduct('clickliteForm');
            this.initSubmitProduct('clicklite');
            this.initClose();
            this.initCounter();

            if(this.mask) this.initMask();
        },
        getSku: function(skusSELECT) {
            var key = '';
            var sku = '';
            var skus = this.skus;
            var skuFeature = this.skuFeature;

            if(skusSELECT != null && Object.keys(skusSELECT).length == 1) {
                var first;
                for (var i in skusSELECT) {
                    first = skusSELECT[i];
                    break;
                }
                sku = first;
            } else if(skusSELECT != null) {
                if(skuFeature.length) {
                    skuFeature.each(function() {
                        key += $(this).data('feature-id') + ':' + $(this).val() + ';';
                    });
                    sku = skusSELECT[key];
                } else if(skus.length) {
                    key = skus.filter(':checked').val();
                    sku = skusSELECT[key];
                }
            }
            return sku;
        },
        initSkuFeature: function() {
            var that = this;
            var clickliteForm = $('.clickliteForm');
            var buttonView = $('.clicklite__buttonView');

            if (clickliteForm.length || buttonView.length) {
                that.skus = $('input[name="sku_id"]');
                that.skuFeature = $("[name^='features[']");

                var product = buttonView.data('product') || clickliteForm.find('.clickliteForm__button').data('product');
                var skusSELECT = product.skusSELECT;

                function skuFeatureHide() {
                    var sku = that.getSku(skusSELECT);
                    if (sku && sku.available) {
                        clickliteForm.show();
                        buttonView.show();
                        clickliteForm.find('.clickliteForm__sku').val(sku.id);
                    } else {
                        clickliteForm.hide();
                        buttonView.hide();
                    }
                }

                if (that.skus.length) {
                    that.skus.change(function () {
                        skuFeatureHide();
                    });
                    skuFeatureHide();
                }

                if (that.skuFeature.length) {
                    that.skuFeature.change(function () {
                        skuFeatureHide();
                    });
                    skuFeatureHide();
                }
            }
        },
        initView: function() {
            var that = this;

            $('body').on('click', '.clicklite__buttonView', function() {
                var product = $(this).data('product');
                var skuName = '';
                var price = '';
                var sku = that.getSku(product.skusSELECT);

                var clicklite = addFormGetClicklite();

                clicklite.find('.clicklite__img').html('<img src="'+product.image+'" alt="" />');
                clicklite.find('.clicklite__sku').val(product.sku_id);
                clicklite.find('.clicklite__id').val(product.id);
                clicklite.find('.clicklite__n').text(product.name);
                clicklite.find('.clicklite__price').data('price', product.price);
                clicklite.find('.clicklite__price').html(that.currencyFormat(product.price));
                clicklite.find('.clicklite__totalPrice').html(that.currencyFormat(product.price));

                if (sku) {
                    if (sku.available) {
                        skuName = sku.name;
                        price = sku.price;
                        clicklite.find('.clicklite__sku').val(sku.id);
                    } else {
                        clicklite.find('.clicklite__error').show();
                        clicklite.find('.clicklite__bid').hide();
                    }

                    if(skuName != '')
                        clicklite.find('.clicklite__variants').text(skuName);

                    if(price != '') {
                        clicklite.find('.clicklite__price').data('price', price);
                        clicklite.find('.clicklite__price').html(that.currencyFormat(price));
                        clicklite.find('.clicklite__quantity').val(1);
                        clicklite.find('.clicklite__totalPrice').html(that.currencyFormat(price));
                    }
                }

                that.yandexTarget('click');
                activeModalForm(clicklite);

                return false;
            });

            $('body').on('click', '.clickliteCart__buttonView', function() {
                var clicklite = addFormGetClicklite();

                clicklite.find('.clicklite__product').hide();
                clicklite.find('.clicklite__form')
                    .append('<input type="hidden" class="clicklite__cart" name="clicklite__cart" value="1">');

                that.yandexTarget('click');
                activeModalForm(clicklite);

                return false;
            });

            function addFormGetClicklite() {
                $('body').append(that.templates);
                var clicklite = $('.clicklite');

                clicklite.find('.clicklite__input').focusin(function(event) {
                    $(this).removeClass('reqprice__input_error');
                });

                if(that.policyCheckbox) {
                    clicklite.find('.clicklite__policyCheckbox').change(function(event) {
                        $(this).parent().removeClass('clicklite__politika_error');
                    });
                }

                return clicklite;
            }

            function activeModalForm(clicklite) {
                setTimeout(function() {
                    if(that.mask)
                        clicklite.find('.clicklite__input_phone').mask(that.mask);

                    clicklite.addClass('clicklite_active');
                    $('.clickliteW').addClass('clickliteW_active');
                    $('body').addClass('clickliteOver');
                }, 100);
            }
        },
        initSubmitProduct: function(cl) {
            var that = this;

            $('body').on('focusin', '.'+cl+'__input', function(event) {
                $(this).removeClass(cl+'__input_error');
            });

            if(that.policyCheckbox) {
                $('body').on('change', '.'+cl+'__policyCheckbox', function(event) {
                    $(this).parent().removeClass(cl+'__politika_error');
                });
            }

            $('body').on('submit', '.'+cl+' form', function(event) {
                var f = $(this);
                var error = false;

                $('.'+cl+'__input').each(function(index, el) {
                    if($(this).val().trim() == '') {
                        error = true;
                        $(this).addClass(cl+'__input_error');
                    }
                });

                var policyCheckbox;
                if(that.policyCheckbox && !f.find('.'+cl+'__policyCheckbox').is(':checked')) {
                    error = true;
                    f.find('.'+cl+'__politika').addClass(cl+'__politika_error');
                }

                if(!error)
                    that.createOrder(f, cl, error);

                return false;
            });
        },
        createOrder: function(f, cl, error) {
            var that = this;
            var btn = f.find('.'+cl+'__button');
            btn.addClass('clicklite__button_loading').attr('disabled','disabled');
            $.ajax({
                url: that.url + 'clicklite/order/',
                data: f.serialize() + '&clicklite__antispam=' + $('.'+cl+'__antispam').text(),
                dataType: "json",
                type: "post",
                success: function (response) {
                    if (response.status == 'ok' && response.data) {
                        if (response.data.status && response.data.message) {
                            $('.'+cl+'__bid').hide();
                            $('.'+cl+'__thankText').html(response.data.message).show().parent().show();

                            if(response.data.info)
                                that.ecommerceSet(response.data.info);

                            that.yandexTarget('send');

                            setTimeout(function() {
                                if(response.data.redirect)
                                    location.href = that.url + 'checkout/success/';
                            }, 500);

                        } else if (response.data.message) {
                            alert(response.data.message);
                            that.yandexTarget('fail');
                        }
                    }
                    btn.removeClass('clicklite__button_loading').removeAttr('disabled');
                },
                error: function () {
                    btn.removeClass('clicklite__button_loading').removeAttr('disabled');
                    that.yandexTarget('fail');
                }
            });
        },
        initClose: function() {
            $('body').on('click', '.clicklite__close,.clickliteW,.clicklite__buttonClose', function(event) {
                $('.clicklite').removeClass('clicklite_active');
                $('.clickliteW').removeClass('clickliteW_active');
                $('body').removeClass('clickliteOver');

                setTimeout(function() {
                    $('.clicklite,.clickliteW').remove();
                }, 300);
            });
        },
        initCounter: function() {
            var that = this;

            $('body').on('click', '.clicklite__counterMinus', function() {
                var counterInput = $(this).parent().find('.clicklite__quantity');
                var count = +counterInput.val()-1;
                if(count >= 1) {
                    counterInput.val(count);
                    updateCounter(count);
                }
                return false;
            });
            $('body').on('click', '.clicklite__counterPlus', function() {
                var counterInput = $(this).parent().find('.clicklite__quantity');
                var count = +counterInput.val() + 1;
                counterInput.val(count);
                updateCounter(count);
                return false;
            });
            $('body').on('change', '.clicklite__quantity', function() {
                var counterInput = $(this);
                var count = +counterInput.val();
                if(count < 1) {
                    counterInput.val(1);
                    updateCounter(1);
                    return false;
                }
                counterInput.val(count);
                updateCounter(count);
            });

            function updateCounter(count) {
                var price = +$('.clicklite__price').data('price');
                $('.clicklite__totalPrice').html(that.currencyFormat(price*count));
            }
        },
        currencyFormat: function(number, no_html) {
            // Format a number with grouped thousands
            //
            // +   original by: Jonas Raoni Soares Silva (http://www.jsfromhell.com)
            // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
            // +     bugfix by: Michael White (http://crestidg.com)

            var i, j, kw, kd, km;
            var decimals = this.currency.frac_digits;
            var dec_point = this.currency.decimal_point;
            var thousands_sep = this.currency.thousands_sep;

            // input sanitation & defaults
            if( isNaN(decimals = Math.abs(decimals)) ){
                decimals = 2;
            }
            if( dec_point == undefined ){
                dec_point = ",";
            }
            if( thousands_sep == undefined ){
                thousands_sep = ".";
            }

            i = parseInt(number = (+number || 0).toFixed(decimals)) + "";

            if( (j = i.length) > 3 ){
                j = j % 3;
            } else{
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
        },
        initMask: function() {
            $('.clickliteForm__input').mask(this.mask);
        },
        yandexTarget: function(target) {
            var yaCounter = this.yandex.counter;
            var target = this.yandex[target];

            if(yaCounter && target)
            {
                var yaCounter = window['yaCounter' + yaCounter];
                yaCounter.reachGoal(target);
            }
        },
        ecommerceSet: function($orderInfo) {
            if(this.ecommerce) {
                window.dataLayer = window.dataLayer || [];
                dataLayer.push({"ecommerce":JSON.parse($orderInfo)});
            }
        }
    }
})(jQuery);

/*
    jQuery Masked Input Plugin
    Copyright (c) 2007 - 2015 Josh Bush (digitalbush.com)
    Licensed under the MIT license (http://digitalbush.com/projects/masked-input-plugin/#license)
    Version: 1.4.1
*/
!function(a){"function"==typeof define&&define.amd?define(["jquery"],a):a("object"==typeof exports?require("jquery"):jQuery)}(function(a){var b,c=navigator.userAgent,d=/iphone/i.test(c),e=/chrome/i.test(c),f=/android/i.test(c);a.mask={definitions:{9:"[0-9]",a:"[A-Za-z]","*":"[A-Za-z0-9]"},autoclear:!0,dataName:"rawMaskFn",placeholder:"_"},a.fn.extend({caret:function(a,b){var c;if(0!==this.length&&!this.is(":hidden"))return"number"==typeof a?(b="number"==typeof b?b:a,this.each(function(){this.setSelectionRange?this.setSelectionRange(a,b):this.createTextRange&&(c=this.createTextRange(),c.collapse(!0),c.moveEnd("character",b),c.moveStart("character",a),c.select())})):(this[0].setSelectionRange?(a=this[0].selectionStart,b=this[0].selectionEnd):document.selection&&document.selection.createRange&&(c=document.selection.createRange(),a=0-c.duplicate().moveStart("character",-1e5),b=a+c.text.length),{begin:a,end:b})},unmask:function(){return this.trigger("unmask")},mask:function(c,g){var h,i,j,k,l,m,n,o;if(!c&&this.length>0){h=a(this[0]);var p=h.data(a.mask.dataName);return p?p():void 0}return g=a.extend({autoclear:a.mask.autoclear,placeholder:a.mask.placeholder,completed:null},g),i=a.mask.definitions,j=[],k=n=c.length,l=null,a.each(c.split(""),function(a,b){"?"==b?(n--,k=a):i[b]?(j.push(new RegExp(i[b])),null===l&&(l=j.length-1),k>a&&(m=j.length-1)):j.push(null)}),this.trigger("unmask").each(function(){function h(){if(g.completed){for(var a=l;m>=a;a++)if(j[a]&&C[a]===p(a))return;g.completed.call(B)}}function p(a){return g.placeholder.charAt(a<g.placeholder.length?a:0)}function q(a){for(;++a<n&&!j[a];);return a}function r(a){for(;--a>=0&&!j[a];);return a}function s(a,b){var c,d;if(!(0>a)){for(c=a,d=q(b);n>c;c++)if(j[c]){if(!(n>d&&j[c].test(C[d])))break;C[c]=C[d],C[d]=p(d),d=q(d)}z(),B.caret(Math.max(l,a))}}function t(a){var b,c,d,e;for(b=a,c=p(a);n>b;b++)if(j[b]){if(d=q(b),e=C[b],C[b]=c,!(n>d&&j[d].test(e)))break;c=e}}function u(){var a=B.val(),b=B.caret();if(o&&o.length&&o.length>a.length){for(A(!0);b.begin>0&&!j[b.begin-1];)b.begin--;if(0===b.begin)for(;b.begin<l&&!j[b.begin];)b.begin++;B.caret(b.begin,b.begin)}else{for(A(!0);b.begin<n&&!j[b.begin];)b.begin++;B.caret(b.begin,b.begin)}h()}function v(){A(),B.val()!=E&&B.change()}function w(a){if(!B.prop("readonly")){var b,c,e,f=a.which||a.keyCode;o=B.val(),8===f||46===f||d&&127===f?(b=B.caret(),c=b.begin,e=b.end,e-c===0&&(c=46!==f?r(c):e=q(c-1),e=46===f?q(e):e),y(c,e),s(c,e-1),a.preventDefault()):13===f?v.call(this,a):27===f&&(B.val(E),B.caret(0,A()),a.preventDefault())}}function x(b){if(!B.prop("readonly")){var c,d,e,g=b.which||b.keyCode,i=B.caret();if(!(b.ctrlKey||b.altKey||b.metaKey||32>g)&&g&&13!==g){if(i.end-i.begin!==0&&(y(i.begin,i.end),s(i.begin,i.end-1)),c=q(i.begin-1),n>c&&(d=String.fromCharCode(g),j[c].test(d))){if(t(c),C[c]=d,z(),e=q(c),f){var k=function(){a.proxy(a.fn.caret,B,e)()};setTimeout(k,0)}else B.caret(e);i.begin<=m&&h()}b.preventDefault()}}}function y(a,b){var c;for(c=a;b>c&&n>c;c++)j[c]&&(C[c]=p(c))}function z(){B.val(C.join(""))}function A(a){var b,c,d,e=B.val(),f=-1;for(b=0,d=0;n>b;b++)if(j[b]){for(C[b]=p(b);d++<e.length;)if(c=e.charAt(d-1),j[b].test(c)){C[b]=c,f=b;break}if(d>e.length){y(b+1,n);break}}else C[b]===e.charAt(d)&&d++,k>b&&(f=b);return a?z():k>f+1?g.autoclear||C.join("")===D?(B.val()&&B.val(""),y(0,n)):z():(z(),B.val(B.val().substring(0,f+1))),k?b:l}var B=a(this),C=a.map(c.split(""),function(a,b){return"?"!=a?i[a]?p(b):a:void 0}),D=C.join(""),E=B.val();B.data(a.mask.dataName,function(){return a.map(C,function(a,b){return j[b]&&a!=p(b)?a:null}).join("")}),B.one("unmask",function(){B.off(".mask").removeData(a.mask.dataName)}).on("focus.mask",function(){if(!B.prop("readonly")){clearTimeout(b);var a;E=B.val(),a=A(),b=setTimeout(function(){B.get(0)===document.activeElement&&(z(),a==c.replace("?","").length?B.caret(0,a):B.caret(a))},10)}}).on("blur.mask",v).on("keydown.mask",w).on("keypress.mask",x).on("input.mask paste.mask",function(){B.prop("readonly")||setTimeout(function(){var a=A(!0);B.caret(a),h()},0)}),e&&f&&B.off("input.mask").on("input.mask",u),A()})}})});
