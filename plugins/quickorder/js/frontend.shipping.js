/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

var QuickorderPluginFrontendShipping = (function ($) {

    QuickorderPluginFrontendShipping = function (parent) {
        var that = this;

        /* VARS */
        that.parent = parent;
        that.xhr = null;
        that.timer = null;

        /* INIT */
        that.initClass();
    };

    QuickorderPluginFrontendShipping.prototype.responseCallback = function (shipping_id, data) {
        var that = this;

        var name = 'rate_id[' + shipping_id + ']';
        var shippingMethod = that.parent.$form.find(".quickorder-shipping-" + shipping_id);
        shippingMethod.addClass('fetched');
        if (typeof (data) != 'string') {
            shippingMethod.removeClass('q-method-error').find('input:radio').removeAttr('disabled');
        }
        if (typeof (data) == 'string') {
            shippingMethod.addClass('q-method-error').find('input[name="' + name + '"]').remove();
            shippingMethod.find('select[name="' + name + '"]').remove();
            var el = shippingMethod.find('.quickorder-shipping-rate');
            if (el.hasClass('error')) {
                el.find('em').html(data);
            } else {
                el.find('.quickorder-shipping-price, .quickorder-grey').hide();
                el.addClass('error').append($('<em class="error"></em>').html(data));
            }
        } else if (data.length > 1) {
            shippingMethod.find('input[name="' + name + '"]').remove();
            var select = shippingMethod.find('select[name="' + name + '"]');
            var html = '<select class="quickorder-shipping-rates" style="display: block" name="' + name + '">';
            for (var i = 0; i < data.length; i++) {
                var r = data[i];
                var rateValue = that.parent.$form.data('ruble-sign') ? r.rate_html : (Array.isArray(r.rate) ? $.quickorder.currencyFormat(r.rate[1], 1) : $.quickorder.currencyFormat(r.rate, 1))
                html += '<option data-rate="' + that.escapeHTML(rateValue) + '" data-comment="' + (r.comment || '') + '" data-est_delivery="' + (r.est_delivery || '') + '" value="' + r.id + '">' + r.name + ' (' + rateValue + ')</option>';
            }
            html += '</select>';
            if (select.length) {
                var selected = select.val();
                select.remove();
            } else {
                var selected = false;
            }
            select = $(html);
            shippingMethod.find("> label").append(select);
            if (selected) {
                select.val(selected);
            }
            select.trigger('change', 1);
            shippingMethod.removeClass('q-method-error').find('.quickorder-shipping-rate').removeClass('error').find('.quickorder-shipping-price').show();
            shippingMethod.find('.quickorder-shipping-rate em.error').remove();
        } else {
            shippingMethod.find('select[name="' + name + '"]').remove();
            var input = shippingMethod.find('input[name="' + name + '"]');
            if (input.length) {
                input.val(data[0].id);
            } else {
                shippingMethod.find("label").append('<input type="hidden" name="' + name + '" value="' + data[0].id + '">');
            }
            shippingMethod.find(".quickorder-shipping-price").html(data[0].rate_html ? data[0].rate_html : '');
            shippingMethod.find(".quickorder-est_delivery").html(data[0].est_delivery ? data[0].est_delivery : '');
            shippingMethod.find('.quickorder-shipping-rate').removeClass('error').find('.quickorder-shipping-price').show();
            if (data[0].est_delivery) {
                shippingMethod.find(".quickorder-est_delivery").show();
            } else {
                shippingMethod.find(".quickorder-est_delivery").hide();
            }
            shippingMethod.removeClass('q-method-error');
            if (data[0].comment) {
                shippingMethod.find(".quickorder-shipping-comment").html(data[0].comment).show();
            } else {
                shippingMethod.find(".quickorder-shipping-comment").hide();
            }
            shippingMethod.find('.quickorder-shipping-rate em.error').remove();
        }
    };

    QuickorderPluginFrontendShipping.prototype.initClass = function () {
        var that = this;

        that.bindEvents();

        /* Обновляем данные о доставке, если присутствуют внешние сервисы */
        var externalShippingMethods = that.parent.$form.find('.q-shipping.quickorder-external').not('.fetched');
        if (externalShippingMethods.length) {
            that.parent.lock();
            var data = that.parent.collectData();
            data = data.add({
                name: 'shipping_id',
                value: $.makeArray(externalShippingMethods.map(function () {
                    return $(this).data('id');
                }))
            });
            $.get(that.parent.urls['shipping'], data, function (response) {
                for (var shipping_id in response.data) {
                    that.responseCallback(shipping_id, response.data[shipping_id]);
                }
                that.parent.update(null, true);
            }, "json").always(function () {
                that.parent.unlock();
            });
        }
    };

    QuickorderPluginFrontendShipping.prototype.bindEvents = function () {
        var that = this;

        /* Выбор методов доставки */
        that.parent.$form.off('change', '.quickorder-shipping-methods input:radio').on('change', '.quickorder-shipping-methods input:radio', function () {
            var btn = $(this);
            if (btn.is(':checked') && !btn.data('ignore')) {
                that.parent.$form.find(".quickorder-shipping-methods .quickorder-methods-form, .quickorder-shipping-rates").hide();
                that.parent.$form.find('.quickorder-shipping-methods .f-quickorder-method').removeClass('selected');
                btn.closest('.f-quickorder-method').addClass('selected').find('.quickorder-methods-form, .quickorder-shipping-rates').show();
                if (btn.data('changed')) {
                    btn.closest('.f-quickorder-method').find('.quickorder-methods-form').find('input,select').data('ignore', 1).change().removeData('ignore');
                    btn.removeData('changed');
                }
                that.parent.update(null, true);
            }
        });

        /* Изменение данных доставки */
        that.parent.$form.off('change', '.wa-address input, .wa-address select').on('change', '.wa-address input, .wa-address select', function () {
            var elem = $(this);
            if (elem.data('ignore')) {
                return true;
            }
            var shipping_id = that.parent.$form.find("input[name=shipping_id]:checked").val();
            var loaded_flag = false;
            setTimeout(function () {
                if (!loaded_flag) {
                    var priceBlock = that.parent.$form.find(".quickorder-shipping-" + shipping_id + " .quickorder-shipping-price");
                    priceBlock.empty().show();
                    if (!priceBlock.find(".quickorder-loading").length) {
                        priceBlock.append(' <i class="quickorder-loading"></i>');
                    }
                }
            }, 300);
            var v = elem.val();
            var name = elem.attr('name').replace(/customer_\d+/, '');
            that.parent.$form.find(".quickorder-shipping-methods input:radio").each(function () {
                var input = $(this);
                if (input.val() != shipping_id) {
                    var el = input.closest('li').find('[name="customer_' + input.val() + name + '"]');
                    if (el.attr('type') != 'hidden') {
                        el.val(v);
                        input.data('changed', 1);
                    }
                }
            });

            if (that.xhr) {
                that.xhr.abort();
            }

            clearTimeout(that.timer);
            that.timer = setTimeout(function () {
                that.parent.lock();
                that.xhr = $.post(that.parent.urls['shipping'], that.parent.collectData(), function (response) {
                    loaded_flag = true;
                    that.responseCallback(shipping_id, response.data);
                    that.parent.update(null, true);
                }, "json").always(function () {
                    that.parent.unlock();
                });
            }, 600);
        });


        /* Изменение вариантов доставки у метода */
        that.parent.$form.off('change', '.quickorder-shipping-methods .quickorder-shipping-rates').on('change', '.quickorder-shipping-methods .quickorder-shipping-rates', function (e, not_check) {
            var elem = $(this);
            var opt = elem.children('option:selected');
            var div = elem.closest('.f-quickorder-method');
            div.find('.quickorder-shipping-price').html(opt.data('rate'));
            if (!not_check) {
                div.find('input:radio').prop('checked', true);
            }
            div.find('.quickorder-est_delivery').html(opt.data('est_delivery'));
            if (opt.data('est_delivery')) {
                div.find('.quickorder-est_delivery').show();
            } else {
                div.find('.quickorder-est_delivery').hide();
            }
            if (opt.data('comment')) {
                div.find('.quickorder-shipping-comment').html(opt.data('comment')).show();
            } else {
                div.find('.quickorder-shipping-comment').empty().hide();
            }
            that.parent.update(null, true);
        });
    };

    QuickorderPluginFrontendShipping.prototype.escapeHTML = function (string) {
        var entityMap = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;',
            '/': '&#x2F;',
            '`': '&#x60;',
            '=': '&#x3D;'
        };

        return String(string).replace(/[&<>"'`=\/]/g, function (s) {
            return entityMap[s];
        });
    };

    return QuickorderPluginFrontendShipping;

})(jQuery);