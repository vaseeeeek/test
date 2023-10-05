(function ($, d) {
$.buy = $.buy || function(buy_params) {

    var currencyFormat = function (number, no_html) {
        // Format a number with grouped thousands
        //
        // +   original by: Jonas Raoni Soares Silva (http://www.jsfromhell.com)
        // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
        // +	 bugfix by: Michael White (http://crestidg.com)

        var i, j, kw, kd, km;
        var decimals = 2;
        var dec_point = ',';
        var thousands_sep = ' ';

        i = parseInt(number = (+number || 0).toFixed(decimals)) + "";


        if( (j = i.length) > 3 ){
            j = j % 3;
        } else{
            j = 0;
        }

        km = (j ? i.substr(0, j) + thousands_sep : "");
        kw = i.substr(j).replace(/(\d{ldelim}3})(?=\d)/g, "$1" + thousands_sep);
        //kd = (decimals ? dec_point + Math.abs(number - i).toFixed(decimals).slice(2) : "");
        kd = (decimals && (number - i) ? dec_point + Math.abs(number - i).toFixed(decimals).replace(/-/, 0).slice(2) : "");


        var number = km + kw + kd;

        return number + ' руб.';
    };


    var refreshTotal = function () {
        var $total = $('[data-field="total"]'),
            total = parseFloat($total.data('value')),
            shipping = $('.order-total').find('[data-id="shipping"]').find('.order-value').html(),
            $discount_payment = $('[data-field="discount-payment"]'),
            discount_payment = $discount_payment.is(':visible') ? parseFloat($discount_payment.data('value')) : 0,
            $discount_shipping = $('[data-field="discount-shipping"]'),
            discount_shipping = $discount_shipping.is(':visible') ? parseFloat($discount_shipping.data('value')) : 0,
            sum;

        if(isNaN(total)) {
            /* nothing to do */
            return;
        }
        shipping = parseFloat(shipping.replace(/[^0-9,]+/g,'').replace(',','.'));
        if(isNaN(shipping)) {
            shipping = 0;
        }
        if(isNaN(discount_payment)) {
            discount_payment = 0;
        }  else {
            discount_payment = Math.round(discount_payment);
        }

        if(isNaN(discount_shipping)) {
            discount_shipping = 0;
        }

        sum = total+shipping-discount_payment-discount_shipping;

        $total.html(currencyFormat(sum));
    };


    //
    if(buy_params.shipping_id) {
        var updateShiping = function () {
            $.get(buy_params.shipping_url, { shipping_id: buy_params.shipping_id }, function (response) {
                var result = false, sr;
                for (var shipping_id in response.data) {
                    sr = responseCallback(shipping_id, response.data[shipping_id]);
                    result = sr || result;
                }

                if(!result && $('#city').data('correct')) {
                    $('#city').data('correct', 0);
                    $.post('/buy/city', function () {
                        $(d).trigger('cityselect__set_city');
                    });
                }
                $('.checkout-order-process').prop('disabled', false);
                $(".checkout-options input:radio:checked").trigger('change');
            }, "json");
        };
        updateShiping();


        $(d).on('cityselect__set_city', function (event, data) {
            var $r = $('.shipping-method').find('.rate');
            $r.find('.price').html('Загрузка... <i class="icon16 loading"></i>').show();
            $r.find('.hint').hide();
            $('.checkout-order-process').prop('disabled', true);
            updateShiping();
        });
    }

    function responseCallback(shipping_id, data) {

        var $wrapper = $(".shipping-" + shipping_id),
            name = 'shipping_rate[' + shipping_id + ']',
            free_shipping = false;

        if(buy_params.shippinginfo && buy_params.shippinginfo[shipping_id] && !!buy_params.shippinginfo[shipping_id].free_status) {
            if(!!buy_params.cart_total && (buy_params.cart_total <= buy_params.shippinginfo[shipping_id].free_step)) {
                console.log(shipping_id, ' has free delivery');
                free_shipping = true;
            }
        }

        $wrapper.data('free_shipping', free_shipping);

        if (typeof(data) !== 'string') {
            $wrapper.find('input:radio').removeAttr('disabled');
            $wrapper.find('.jq-radio').removeClass('disabled');
        }

        console.log(data);

        if (
            (typeof(data) === 'string') ||
            (data[0].hasOwnProperty('rate') && (data[0].rate === null))
        ) {
            $wrapper.find('input[name="' + name + '"],select[name="' + name + '"]').remove();

            var el = $wrapper.find('.rate'),
                comment = typeof(data) === 'string' ? data : data[0].comment;
            if (el.hasClass('error')) {
                el.find('em').html(comment);
            } else {
                el.find('.price, .hint').hide();
                el.find('.error.comment').show().html(comment);
            }

            $wrapper.find('input:radio').prop('disabled', true);
            $wrapper.find('.jq-radio').addClass('disabled');
            return false;
        } else if (data.length > 1) {
            var selected = $wrapper.find('input[name="' + name + '"]').val();
            $wrapper.find('input[name="' + name + '"]').remove();

            var select = $wrapper.find('select[name="' + name + '"]');
            var html = '<select class="shipping-rates" name="' + name + '">';
            for (var i = 0; i < data.length; i++) {
                var r = data[i],
                    _rate = parseFloat(r.rate.replace(/[^\d.,]+/g, '').replace(',', '.')),
                    fs = free_shipping && (_rate <= buy_params.shippinginfo[shipping_id].free_step_shipping);
                html += '<option data-rate="' + (fs ? '<span class=\'free_delivery_price\'>бесплатно</span>' : r.rate) + '" data-original_rate="' + r.rate + '" data-comment="' + (r.comment || '') + '" data-est_delivery="' + (r.est_delivery || '') + '" value="' + r.id + '">' + r.name + (fs ? '' : ' (' + r.rate + ')' ) +'</option>';
            }
            html += '</select>';

            if (select.length) {
                selected = select.val();
                select.remove();
            }

            select = $(html);
            if(!selected || !select.find('[value="'+selected+'"]').length) {
                selected = data[0].id;
            }

            $(".shipping-" + shipping_id + " .h3").append(select);
            if (selected) {
                select.val(selected);
            }
            select.trigger('change', 1);
            $wrapper.find('.rate').removeClass('error').find('.price').show();
            $wrapper.find('.rate em.shipping-error').remove();
            //$wrapper.find('.error.comment').html('');
            return true;
        } else {
            $wrapper.find('select[name="' + name + '"]').remove();
            var input = $wrapper.find('input[name="' + name + '"]'),
                _rate = parseFloat(data[0].rate.replace(/[^\d.,]+/g, '').replace(',', '.'));
            if (input.length) {
                input.val(data[0].id);
            } else {
                $wrapper.find(".h3").append('<input type="hidden" name="' + name + '" value="' + data[0].id + '">');
            }
            $wrapper.find(".price").html(
                free_shipping && (_rate <= buy_params.shippinginfo[shipping_id].free_step_shipping)
                    ? '<span class="free_delivery_price">бесплатно</span>' : data[0].rate).data('original_rate', data[0].rate);
            $wrapper.find(".est_delivery").html(data[0].est_delivery);
            $wrapper.find('.rate').removeClass('error').find('.price').show();
            if (data[0].est_delivery) {
                $wrapper.find(".est_delivery").parent().show();
            } else {
                $wrapper.find(".est_delivery").parent().hide();
            }
            if (data[0].comment) {
                $wrapper.find(".comment").html(data[0].comment).show();
            } else {
                $wrapper.find(".comment").hide();
            }
            $wrapper.find('.rate em.shipping-error').remove();

            return data[0].rate !== null;
        }
    }



    $(".checkout-options").on('change', "select.shipping-rates", function (e, not_check) {
        var opt = $(this).children('option:selected');
        var li = $(this).closest('.shipping-method');
        li.find('.price').html(opt.data('rate')).data('original_rate', opt.data('original_rate'));
        if (!not_check) {
            li.find('input:radio').attr('checked', 'checked').trigger('change', 1);
        }
        li.find('.est_delivery').html(opt.data('est_delivery'));
        if (opt.data('est_delivery')) {
            li.find('.est_delivery').parent().show();
        } else {
            li.find('.est_delivery').parent().hide();
        }
        if (opt.data('comment')) {
            li.find('.comment').html('<br>' + opt.data('comment')).show();
        } else {
            li.find('.comment').empty().hide();
        }
    });


    $(".checkout-options input:radio").change(function () {
        if ($(this).is(':checked') && !$(this).data('ignore')) {
            var li = $(this).closest('.shipping-method'),
                rate = li.find('.rate .price').data('original_rate'),
                date = li.find('.est_delivery').html(),
                $shipping_row = $('.order-total').find('[data-id="shipping"]'),
                $discount_block = $('[data-field="discount-shipping"]').closest('li');


            if(rate) {
                $shipping_row.show();
                $shipping_row.find('.order-value').html(rate);
            } else {
                $shipping_row.hide();
                $shipping_row.find('.order-value').html('');
            }

            if(li.data('free_shipping') && /бесплатно/.test(li.find('.rate .price').html())) {
                $discount_block.show();
                $discount_block.find('.order-value')
                    .html(rate).data('value', rate.replace(/[^\d.,]+/g, '').replace(',', '.'));
                console.log(rate.replace(/[^\d.,]+/g, '').replace(',', '.'));
            } else {
                $discount_block.hide();
            }

            if(date) {
                $('[data-field="shipping-estimate-date"]').html(date)
                    .closest('[data-field="shipping-estimate-text"]').show();
            } else {
                $('[data-field="shipping-estimate-date"]').html('')
                    .closest('[data-field="shipping-estimate-text"]').hide();
            }



            $(".checkout-options .wa-address").hide();
            li.find('.wa-form').show();
            if ($(this).data('changed')) {
                li.find('.wa-form').find('input,select').data('ignore', 1).change().removeData('ignore');
                $(this).removeData('changed');
            }

            refreshTotal();
        }
    });

    $(".payment-options input:radio").change(function () {
        var block = $('[data-field="discount-payment"]').closest('li');
        if($(this).val() == 4) {
            block.show();
        } else {
            block.hide();
        }
        refreshTotal();
    }).filter(':checked').trigger('change');


    $('#customer_phone').inputmask({
        regex : '\\d\\d\\d\\) \\d\\d\\d-\\d\\d-\\d\\d',
        placeholder : "_",
        showMaskOnHover: false,
        showMaskOnFocus: true,
        clearMaskOnLostFocus: true,
        colorMask: false,
        jitMasking: true,
        onBeforeMask: function (value, opts) {
            var processedValue;
            processedValue = value.replace(/^7(\d{ldelim}9})/, '$1');

            return processedValue;
        }
    }).blur(function () {

        var $block = $(this).closest('.form-group'),
            $error = $block.find('.error'),
            val = $(this).val();

        if(!/\d\d\d\) \d\d\d-\d\d-\d\d/.test(val)) {
            if(!$error.length) {
                $error = $('<div class="error"/>');
                $block.append($error);
            }
            $error.html('Номер телефона должен состоять из 10 цифр, начиная с кода оператора');
        } else {
            $error.remove();
        }
    });
}

})(jQuery, document);