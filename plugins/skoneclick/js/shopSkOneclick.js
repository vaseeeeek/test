if (!window.shopSkOneclick) {

    shopSkOneclick = (function ($) {

        'use strict';

        var shopSkOneclick = function (params) {

            this.init(params);

        };

        shopSkOneclick.prototype = {

            _config: {
                block: '.js-sk-oneclick-block',
                content: '.js-sk-oneclick-content',
                yandexId: '',
                yandexOpen: '',
                yandexSend: '',
                yandexError: '',
                googleOpenCategory: '',
                googleOpenAction: '',
                googleSendCategory: '',
                googleSendAction: '',
                googleErrorCategory: '',
                googleErrorAction: ''
            },

            init: function (params) {

                var that = this;

                that.params = $.extend({}, that._config, params);

                that.initElements();

                if(!that.elements.block.size()){
                    return false;
                }

                that.onEventOpen();

                that.onEventCLose();

            },

            initElements: function(){
                var that = this,
                    elements = {};

                elements.block = $(that.params.block);
                elements.content = $(that.params.content);

                that.elements = elements;

            },

            initForm: function(){
                var that = this,
                    elements = that.elements;

                elements.form = elements.content.find(".js-sk-oneclick-form");
                elements.formField = elements.form.find(".wa-field").not(".wa-field-address").add(".wa-field-address .field");
                elements.cart = elements.content.find(".js-sk-oneclick-cart");
                elements.counter = elements.content.find(".js-sk-oneclick-counter");
                elements.counterInput = elements.content.find(".js-sk-oneclick-counter-input");
                elements.cartError = elements.content.find(".js-sk-oneclick-cart-error");
                elements.cartItem = elements.content.find(".js-sk-oneclick-cart-item");
                elements.preload = elements.content.find(".js-sk-oneclick-preloader-form");
                elements.formError = elements.content.find(".js-sk-oneclick-form-error");
                elements.services = elements.content.find(".js-sk-oneclick-cart-service");

                elements.discount = elements.form.find(".js-sk-oneclick-final-discount");
                elements.discountPrice = elements.form.find(".js-sk-oneclick-final-discount-price");
                elements.totalPrice = elements.form.find(".js-sk-oneclick-total-price");

                elements.coupon = elements.form.find(".js-sk-oneclick-final-coupon");
                elements.couponLink = elements.coupon.find(".js-sk-oneclick-final-coupon-link");
                elements.couponInput = elements.coupon.find(".js-sk-oneclick-final-coupon-input");
                elements.couponBlock = elements.coupon.find(".js-sk-oneclick-final-coupon-block");
                elements.couponApply = elements.coupon.find(".js-sk-oneclick-final-coupon-apply");
                elements.couponClear = elements.coupon.find(".js-sk-oneclick-final-coupon-clear");

            },

            initCounters: function(){
                var that = this,
                    elements = that.elements,
                    counter = elements.counter,
                    currentOld = 1;

                counter.each(function(){
                    var element = $(this),
                        input = element.find(".js-sk-oneclick-counter-input"),
                        minus = element.find(".js-sk-oneclick-counter-left"),
                        plus = element.find(".js-sk-oneclick-counter-right");

                    minus.on("click", function(){
                        currentOld = parseInt(input.val());
                        var current = currentOld - 1;
                        if(current <= 1) current = 1;
                        input.val(current);
                        that.onReloadCart(input, currentOld);
                    });

                    plus.on("click", function(){
                        currentOld = parseInt(input.val());
                        var current = currentOld + 1;
                        input.val(current);
                        that.onReloadCart(input, currentOld);
                    });

                    input.on('focusin', function(){
                        currentOld = $(this).val();
                    });

                    input.on("change", function(){
                        var current = parseInt(input.val());
                        if(isNaN(parseInt(current))){
                            input.val("1");
                            currentOld = 1;
                        }
                        that.onReloadCart(input, currentOld);
                    })

                });

            },

            initServices: function(){
                var that = this,
                    elements = that.elements;

                elements.services.find("[type=checkbox]").change(function(){
                    that.onReloadCart();
                });

            },

            runMask: function(){
                var that = this,
                    elements = that.elements,
                    reg = /#/g;

                if(typeof that.fields !== "undefined"){
                    $.each(that.fields, function(index, item){
                        if(typeof item.mask !== "undefined" && item.mask){
                            var input = elements.form.find("input[name='customer[phone]']");
                            if(input.size() && !input.val()){
                                input.skmask(item.mask.replace(reg, "9"));
                            }
                        }
                    });
                }
            },

            onEventOpen: function(){
                var that = this,
                    elements = that.elements;

                elements.block.on("event-open", function(object, params){
                    that.requestForm(params);
                    that.sendEventYandex(that.params.yandexOpen);
                    that.sendEventGoogle(that.params.googleOpenCategory, that.params.googleOpenAction)
                })

            },

            onEventCLose: function(){
                var that = this,
                    elements = that.elements;

                elements.block.on("event-close", function(object, params){
                    elements.content.html("");
                })
            },

            requestForm: function(params){
                var that = this,
                    elements = that.elements;

                that.params.type = params.type;

                var request = params.form.serialize() + "&type=" + params.type + "&check=skonclick_plugin";

                $.post(that.params.urlRequest, request, function(resp){
                    if(resp.status == "fail"){
                        elements.block.trigger("event-load");
                        elements.content.html(resp.errors[0]);
                    }else if(resp.status == "ok"){
                        elements.block.trigger("event-load");
                        elements.content.html(resp.data.content);
                        that.initForm();
                        that.runStyler();
                        that.initCounters();
                        that.initServices();
                        that.onSubmit();
                        that.runCoupon();
                        that.runMask();
                    }
                }, "json")
            },

            runCoupon: function(){
                var that = this,
                    elements = that.elements;

                elements.couponLink.on("click", function(){
                    elements.coupon.addClass("_show-coupon");
                });

                elements.couponApply.on("click", function(){
                    that.onReloadCart();
                });

                elements.couponClear.on("click", function(){
                    elements.coupon.removeClass("_is-coupon").removeClass("_show-coupon");
                    elements.couponInput.val("");
                    that.onReloadCart();

                });
            },

            onReloadCart: function(input, old){
                var that = this,
                    elements = that.elements;

                that.removeError();

                $.post(that.params.urlRequest, elements.form.serialize() + "&reload=1" + "&type=" + that.params.type + "&check=skonclick_plugin", function(resp){
                    if(resp.status == "fail"){
                        elements.cartError.text(resp.errors[0]).show();
                        if(typeof input !== "undefined" && typeof old != "undefined"){
                            input.val(old);
                        }
                    }else if(resp.status == "ok"){
                        $.each(resp.data.items, function(index, item){
                            var row = that.elements.cartItem.filter("[data-sku-id='" + item.sku_id + "']"),
                                total = row.find(".js-sk-oneclick-cart-total");

                            total.html(item.full_price);
                        });
                        if(parseInt(resp.data.discount_numeric)){
                            elements.discount.show();
                        }else{
                            elements.discount.hide();
                        }
                        if(parseInt(resp.data.discount_numeric) && resp.data.coupon_code){
                            elements.coupon.addClass("_is-coupon");
                        }else{
                            elements.coupon.removeClass("_is-coupon").removeClass("_show-coupon");
                        }
                        elements.discountPrice.html("- " + resp.data.discount);
                        elements.totalPrice.html(resp.data.total);
                    }
                })
            },

            onSubmit: function(){
                var that = this,
                    elements = that.elements,
                    process = false;

                that.elements.form.on("submit", function(e){
                    e.preventDefault();

                    if(process) return false;
                    process = true;

                    elements.preload.addClass("_show");

                    that.removeError();

                    that.sendEventYandex(that.params.yandexSend);
                    that.sendEventGoogle(that.params.googleSendCategory, that.params.googleSendAction);

                    $.post(that.params.urlSave, that.elements.form.serialize() + "&check=skonclick_plugin", function(resp){

                        if(resp.status == "fail"){
                            if(typeof resp.errors.cart !== "undefined" && resp.errors.cart){
                                elements.cartError.text(resp.errors.cart).show();
                            }
                            if(resp.errors.console !== "undefined" && resp.errors.console){
                                console.log(resp.errors.console);
                            }
                            if(resp.errors.validate !== "undefined" && resp.errors.validate){
                                $.each(resp.errors.validate, function(index, item){
                                    index = parseInt(index);
                                    var element = elements.formField.eq(index);
                                    if(!element.size()){
                                        console.log("Не найдено поле ошибки");
                                        return true;
                                    }
                                    element.addClass("_error");
                                    if(element.find(".wa-value").size()){
                                        element.find(".wa-value").append('<em class="wa-error-msg">' + item + '</em>');
                                    }else{
                                        element.append('<em class="wa-error-msg">' + item + '</em>');
                                    }
                                })
                            }
                            if(resp.errors.form !== "undefined" && resp.errors.form){
                                elements.formError.text(resp.errors.form[0]).show();
                            }
                            that.sendEventYandex(that.params.yandexError);
                            that.sendEventGoogle(that.params.googleErrorCategory, that.params.googleErrorAction);

                            process = false;

                        }else if(resp.status == "ok"){
                            elements.content.html(resp.data.content);
                            that.onClose();
                            if(that.params.type == "cart"){
                                setTimeout(function(){
                                    window.location = window.location;
                                }, 3000);
                            }


                        }else{
                            console.log("Произошла неизвестная ошибка");
                            that.sendEventYandex(that.params.yandexError);
                            that.sendEventGoogle(that.params.googleErrorCategory, that.params.googleErrorAction);
                        }

                        elements.preload.removeClass("_show");

                    }, "json");

                })
            },

            removeError: function(){
                var that = this,
                    elements = that.elements;

                elements.cartError.hide();
                elements.formField.removeClass("_error");
                elements.formField.find(".wa-error-msg").detach();
                elements.formError.html("").hide();
            },

            runStyler: function(){
                var that = this,
                    elements = that.elements;

                if(typeof $.fn.styler !== "undefined"){
                    elements.form.find('input[type="checkbox"], input[type="radio"], .js-select').styler();
                }
            },

            sendEventYandex: function(target){
                var that = this;

                if(that.params.yandexId && target){
                    if(typeof window['yaCounter' + that.params.yandexId] !== "undefined"){
                        var counter = window['yaCounter' + that.params.yandexId];
                        if(typeof counter !== "undefined" && typeof counter.reachGoal !== "undefined"){
                            counter.reachGoal(target);
                        }
                    }
                }
            },

            sendEventGoogle: function(category, action){
                var that = this;

                if(category && action){
                    if(typeof ga !== "undefined"){
                        ga('send', 'event', category, action);
                    }
                }
            },

            onClose: function(){
                var that = this;

                that.elements.block.on("click", ".js-sk-oneclick-close", function(){
                    that.elements.block.trigger("run-close")
                });
            }
        };

        return shopSkOneclick;

    })(jQuery);

}

if(typeof jQuery.fn.skmask === "undefined"){
    !function(a){"function"==typeof define&&define.amd?define(["jquery"],a):a("object"==typeof exports?require("jquery"):jQuery)}(function(a){var b,c=navigator.userAgent,d=/iphone/i.test(c),e=/chrome/i.test(c),f=/android/i.test(c);a.skmask={definitions:{9:"[0-9]",a:"[A-Za-z]","*":"[A-Za-z0-9]"},autoclear:!0,dataName:"rawMaskFn",placeholder:"_"},a.fn.extend({caret:function(a,b){var c;if(0!==this.length&&!this.is(":hidden"))return"number"==typeof a?(b="number"==typeof b?b:a,this.each(function(){this.setSelectionRange?this.setSelectionRange(a,b):this.createTextRange&&(c=this.createTextRange(),c.collapse(!0),c.moveEnd("character",b),c.moveStart("character",a),c.select())})):(this[0].setSelectionRange?(a=this[0].selectionStart,b=this[0].selectionEnd):document.selection&&document.selection.createRange&&(c=document.selection.createRange(),a=0-c.duplicate().moveStart("character",-1e5),b=a+c.text.length),{begin:a,end:b})},unskmask:function(){return this.trigger("unskmask")},skmask:function(c,g){var h,i,j,k,l,m,n,o;if(!c&&this.length>0){h=a(this[0]);var p=h.data(a.skmask.dataName);return p?p():void 0}return g=a.extend({autoclear:a.skmask.autoclear,placeholder:a.skmask.placeholder,completed:null},g),i=a.skmask.definitions,j=[],k=n=c.length,l=null,a.each(c.split(""),function(a,b){"?"==b?(n--,k=a):i[b]?(j.push(new RegExp(i[b])),null===l&&(l=j.length-1),k>a&&(m=j.length-1)):j.push(null)}),this.trigger("unskmask").each(function(){function h(){if(g.completed){for(var a=l;m>=a;a++)if(j[a]&&C[a]===p(a))return;g.completed.call(B)}}function p(a){return g.placeholder.charAt(a<g.placeholder.length?a:0)}function q(a){for(;++a<n&&!j[a];);return a}function r(a){for(;--a>=0&&!j[a];);return a}function s(a,b){var c,d;if(!(0>a)){for(c=a,d=q(b);n>c;c++)if(j[c]){if(!(n>d&&j[c].test(C[d])))break;C[c]=C[d],C[d]=p(d),d=q(d)}z(),B.caret(Math.max(l,a))}}function t(a){var b,c,d,e;for(b=a,c=p(a);n>b;b++)if(j[b]){if(d=q(b),e=C[b],C[b]=c,!(n>d&&j[d].test(e)))break;c=e}}function u(){var a=B.val(),b=B.caret();if(o&&o.length&&o.length>a.length){for(A(!0);b.begin>0&&!j[b.begin-1];)b.begin--;if(0===b.begin)for(;b.begin<l&&!j[b.begin];)b.begin++;B.caret(b.begin,b.begin)}else{for(A(!0);b.begin<n&&!j[b.begin];)b.begin++;B.caret(b.begin,b.begin)}h()}function v(){A(),B.val()!=E&&B.change()}function w(a){if(!B.prop("readonly")){var b,c,e,f=a.which||a.keyCode;o=B.val(),8===f||46===f||d&&127===f?(b=B.caret(),c=b.begin,e=b.end,e-c===0&&(c=46!==f?r(c):e=q(c-1),e=46===f?q(e):e),y(c,e),s(c,e-1),a.preventDefault()):13===f?v.call(this,a):27===f&&(B.val(E),B.caret(0,A()),a.preventDefault())}}function x(b){if(!B.prop("readonly")){var c,d,e,g=b.which||b.keyCode,i=B.caret();if(!(b.ctrlKey||b.altKey||b.metaKey||32>g)&&g&&13!==g){if(i.end-i.begin!==0&&(y(i.begin,i.end),s(i.begin,i.end-1)),c=q(i.begin-1),n>c&&(d=String.fromCharCode(g),j[c].test(d))){if(t(c),C[c]=d,z(),e=q(c),f){var k=function(){a.proxy(a.fn.caret,B,e)()};setTimeout(k,0)}else B.caret(e);i.begin<=m&&h()}b.preventDefault()}}}function y(a,b){var c;for(c=a;b>c&&n>c;c++)j[c]&&(C[c]=p(c))}function z(){B.val(C.join(""))}function A(a){var b,c,d,e=B.val(),f=-1;for(b=0,d=0;n>b;b++)if(j[b]){for(C[b]=p(b);d++<e.length;)if(c=e.charAt(d-1),j[b].test(c)){C[b]=c,f=b;break}if(d>e.length){y(b+1,n);break}}else C[b]===e.charAt(d)&&d++,k>b&&(f=b);return a?z():k>f+1?g.autoclear||C.join("")===D?(B.val()&&B.val(""),y(0,n)):z():(z(),B.val(B.val().substring(0,f+1))),k?b:l}var B=a(this),C=a.map(c.split(""),function(a,b){return"?"!=a?i[a]?p(b):a:void 0}),D=C.join(""),E=B.val();B.data(a.skmask.dataName,function(){return a.map(C,function(a,b){return j[b]&&a!=p(b)?a:null}).join("")}),B.one("unskmask",function(){B.off(".skmask").removeData(a.skmask.dataName)}).on("focus.skmask",function(){if(!B.prop("readonly")){clearTimeout(b);var a;E=B.val(),a=A(),b=setTimeout(function(){B.get(0)===document.activeElement&&(z(),a==c.replace("?","").length?B.caret(0,a):B.caret(a))},10)}}).on("blur.skmask",v).on("keydown.skmask",w).on("keypress.skmask",x).on("input.skmask paste.skmask",function(){B.prop("readonly")||setTimeout(function(){var a=A(!0);B.caret(a),h()},0)}),e&&f&&B.off("input.skmask").on("input.skmask",u),A()})}})});
}