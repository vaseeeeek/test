var FlexdiscountPluginMarketing = (function ($) {
    const CLASSES = {
        CHOSEN: '.chosen-container',
        COUPON: '.s-coupon-wrapper',
        COUPON_LIST: '.s-coupons-list',
        COUPON_SETTINGS_LINK: '.f-coupon-settings-link',
        LOAD_COUPONS_BUTTON: '.js-load-coupons',
        COUPON_CHECKBOX: '.s-checkbox',
        AUTOCOMPLETE: '.js-autocomplete'
    };

    FlexdiscountPluginMarketing = function (options) {
        let that = this;

        that.$wrapper = options.$wrapper;

        that.localeStrings = options.localeStrings || {};
        that.ruleType = options.ruleType;
        that.options = options;

        $.ig_locale = $.extend(that.localeStrings, $.ig_locale);
        if (typeof $__ === "undefined") { window.$__ = puttext($.ig_locale); }

        that.chosenParams = {disable_search_threshold: 10, no_results_text: $__("No result text")};

        that.initClass();
        that.bindEvents();
    };

    FlexdiscountPluginMarketing.prototype.initClass = function () {
        let that = this;

        that.initChosen();

        if (that.getRuleType() === 'coupons') {
            that.initCoupons();
        }
    };

    FlexdiscountPluginMarketing.prototype.initChosen = function () {
        let that = this;

        let $chosenFields = that.$wrapper.find(CLASSES.CHOSEN);
        $chosenFields.length && $chosenFields.chosen(that.chosenParams);
    };

    FlexdiscountPluginMarketing.prototype.getRuleType = function () {
        let that = this;

        if (that.ruleType === 'flexdiscount-coupons') {
            return 'coupons';
        }

        return 'rules';
    };

    FlexdiscountPluginMarketing.prototype.bindEvents = function () {
        let that = this;

        /* Подгрузка купонов */
        that.$wrapper.find(CLASSES.LOAD_COUPONS_BUTTON).click(function () {
            let btn = $(this);
            let page = btn.attr('data-page');
            if (!btn.next('i').length) {
                btn.after('<i class="icon16 loading"></i>');
                $.post(that.urls['load_coupons'], {page: page, ignore: that.getCouponIds()}, function (response) {
                    btn.next('i').remove();
                    if (response.status == 'ok' && Object.keys(response.data).length) {
                        $.each(response.data, function (i, coupon) {
                            that.addCoupon(coupon, 0);
                        });
                        btn.attr('data-page', parseInt(page) + 1);
                    } else {
                        btn.remove();
                    }
                });
            }
        });
    };

    FlexdiscountPluginMarketing.prototype.initCoupons = function () {
        let that = this;

        that.urls = that.options["urls"];
        that.templates = that.options["templates"];

        /* Инициализация автоподгрузки */
        let $field = that.$wrapper.find(CLASSES.AUTOCOMPLETE);
        if ($field.length) {
            initAutocomplete($field);
        }

        function initAutocomplete($field) {
            $field.autocomplete({
                source: function (request, response) {
                    $.ajax({
                        url: that.urls["autocomplete"] + getCouponIdsString(),
                        dataType: "json",
                        data: {
                            term: request.term
                        },
                        success: function (data) {
                            response(data);
                        }
                    });
                },
                appendTo: that.$wrapper,
                minLength: 0,
                focus: function () {
                    return false;
                },
                select: function (event, ui) {
                    that.addCoupon(ui.item.data, 1);
                    $field.val("");
                    return false;
                }
            });

            function getCouponIdsString() {
                var coupon_ids = that.getCouponIds(),
                    result = "";

                if (coupon_ids.length) {
                    $.each(coupon_ids, function (i, id) {
                        result += "&coupon_id[]=" + id;
                    });
                }

                return result;

            }
        }
    };

    /* ID всех купонов на странице */
    FlexdiscountPluginMarketing.prototype.getCouponIds = function (coupon_data) {
        let that = this;

        let result = [];

        that.$wrapper.find(CLASSES.COUPON).each(function () {
            let $coupon = $(this),
                id = $coupon.data("id") + "";

            if (id.length) {
                result.push(id);
            }
        });

        return result;
    };

    /* Добавление купона в общий список */
    FlexdiscountPluginMarketing.prototype.addCoupon = function (coupon_data, isChecked) {
        let that = this;

        let expire_html = (coupon_data.expire_datetime_string ? that.templates["coupon_expire"].replace("%expire%", coupon_data.expire_datetime_string) : "");
        let status_html = (coupon_data.status_string ? coupon_data.status_string : "");
        let fl_id_html = (coupon_data.fl_id ? coupon_data.fl_id : "");

        isChecked = isChecked || 0;

        var template = that.templates["coupon"]
            .replace(/%coupon_id%/g, coupon_data.id)
            .replace("%code%", coupon_data.code)
            .replace("%expire%", expire_html)
            .replace("%status%", status_html)
            .replace("%fl_id%", fl_id_html);
        template = $(template);
        if (fl_id_html) {
            template.find(CLASSES.COUPON_SETTINGS_LINK).show();
        }
        template.find(CLASSES.COUPON_CHECKBOX).prop('checked', isChecked);

        if (!that.$wrapper.find(CLASSES.COUPON + '[data-id="' + coupon_data.id + '"]').length) {
            that.$wrapper.find(CLASSES.COUPON_LIST).append(template);
        }
    };

    return FlexdiscountPluginMarketing;
})(jQuery);