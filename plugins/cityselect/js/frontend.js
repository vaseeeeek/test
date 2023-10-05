/**
 * Выбор города
 *
 * @class shopCityselectFrontend
 *
 * @global shop_cityselect
 *
 * @function onSetCity - callback функция для изменения пришедших данных
 *
 * @event cityselect__set_city - вызывается после смены города
 *
 * @example
 *
 * //Изменение массива данных перед обновлением данных в корзине и оформление заказа
 * shop_cityselect.onSetCity = function(date){ ... ; return data}
 *
 * //Свои действия при изменение города
 * $(document).on('cityselect__set_city', function (event, data) {...});
 *
 */
function shopCityselectFrontend() {

    var that = this;

    that.token = '';
    that.url = '';
    that.lib = '';
    that.bounds = 'city';

    //отключение автоопределения
    that.disable_auto = 0;

    that.show_notifier = 'auto';

    that.$notifiers = null;
    that.notifier_type = 'notifier';
    //modal - для разработки, если установлен bootstrap 4

    that.is_open_modal = false;
    that.modal_type = false;
    that.remodal_instance = null;

    that.is_detect = false;
    that.by_detect = false;

    //Включать подсказки в формах адреса
    that.in_checkout = true;

    //Оформление заказа в корзине
    that.in_order = false;

    //Включать подсказки в произвольных формах
    that.in_custom_form = '.wa-signup-form-fields,.quickorder-form,#storequickorder .wa-form';
    that.in_custom_city = '';

    //Интеграция с плагином dp
    that.plugin_dp = false;
    that.is_redirecting = false;

    //Текущая локация
    that.location = null;

    that.find = {
        country: '[name$="country]"]',
        region: '[name$="region]"]',
        city: '[name$="city]"]',
        zip: '[name$="zip]"]',
        street: '[name$="street]"]'
    };

    that.save_street = null;

    that.init = function (token, url, lib, bounds, show_notifier, in_checkout, plugin_dp, disable_auto) {
        that.token = token;
        that.url = url;
        that.lib = lib;
        that.bounds = bounds;
        that.show_notifier = show_notifier;
        that.in_checkout = !!in_checkout;
        that.plugin_dp = !!plugin_dp;
        that.disable_auto = !!disable_auto;
        if (that.show_notifier == 'force') {
            $(document).ready(function () {
                that.showNotifiers();
            })
        }

        $(document).ready(function () {

            if (that.in_checkout) {
                that.initWaAddress();
            }

            if (that.in_custom_form || that.in_custom_city) {
                setInterval(function () {
                    that.initWaCustom();
                }, 500);
                that.initWaCustom();
            }

            that.themeSupport();
        });

        if (that.plugin_dp) {

            //Перехватываем вызов окна выбора города
            $(document).on('click', '.js-dp-city-select', function (e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                that.changeCity();
                return false;
            });
        }

    };


    that.initStreet = function ($street, $city, $postcode, $region) {

        //выясняем ограничения
        var locations = {};
        if (!!that.location.constraints_street) {
            locations.kladr_id = that.location.constraints_street;
        } else {
            if ($city.length) {
                locations.city = String($city.val()).trim();
            }

            if ($region && $region.length) {
                locations.kladr_id = $region.val();
            }
        }

        $street.suggestions({
            token: that.token,
            type: "ADDRESS",
            scrollOnFocus: false,
            constraints: {
                label: '',
                locations: locations
            },
            restrict_value: true,
            onSelect: function onSelect(suggestion) {

                if (suggestion.data.postal_code && $postcode.length) {
                    $postcode.val(suggestion.data.postal_code);
                }

                //Смена города (актуально для городов регионов)
                if (suggestion.data.settlement) {
                    if (suggestion.data.settlement != $city.val()) {
                        $city.val(suggestion.data.settlement);
                        that.updateStreetConstraints(suggestion, $street);
                        that.setCity(suggestion.data, $city);
                    }
                } else if (suggestion.data.city) {
                    if (suggestion.data.city != $city.val()) {
                        $city.val(suggestion.data.city);
                        that.updateStreetConstraints(suggestion, $street);
                        that.setCity(suggestion.data, $city);
                    }
                }

                $street.change();
            }
        });
    };

    that.initCity = function ($city) {

        $city.addClass('i-cityselect__custom-city--init');

        if ($city.length) {

            $city.suggestions({
                token: that.token, type: "ADDRESS", hint: false, bounds: that.bounds, constraints: {label: ''},
                scrollOnFocus: false,
                formatSelected: function (suggestion) {
                    return suggestion.data.settlement ? suggestion.data.settlement : suggestion.data.city;
                },
                onSelect: function (suggestion) {
                    if (!$city.data('cityselect-not-set')) {
                        that.by_detect = false;
                        that.setCity(suggestion.data);
                    }
                }
            });
        }
    };

    that.updateStreetConstraints = function (suggestion, $street) {
        var sgt = $street.suggestions();
        if (sgt) {

            var locations = {};

            //Ограничение по населенному пункту
            if (suggestion.data.settlement) {
                locations.kladr_id = suggestion.data.settlement_kladr_id;
            } else {
                locations.kladr_id = suggestion.data.city_kladr_id;
            }

            sgt.setOptions({
                constraints: {
                    locations: locations
                }
            });

            sgt.fixData();
        }
    };

    that.initAddress = function ($address) {
        $address.addClass('i-cityselect__wa--init');

        var $country = $address.find(that.find.country);
        var $city = $address.find(that.find.city);
        var $street = $address.find(that.find.street);
        var $postcode = $address.find(that.find.zip);
        var $region = $address.find(that.find.region);

        //Присутсвует город
        if ($city.length) {

            var $city_params = {
                token: that.token, type: "ADDRESS", hint: false, bounds: that.bounds, constraints: {label: ''},
                formatSelected: function (suggestion) {
                    return suggestion.data.settlement ? suggestion.data.settlement : suggestion.data.city;
                },
                onSelect: function (suggestion) {

                    //Поле регион может меняться
                    $region = $city.closest('.i-cityselect__wa--init').find(that.find.region);
                    if ($region.length && suggestion.data.region_kladr_id) {

                        //Смена региона
                        var new_region = String(suggestion.data.region_kladr_id).substr(0, 2);

                        if ($region.val() != new_region) {
                            $region.val(new_region).change();

                            //Устанавливаем приоритет города
                            var sgt = $city.suggestions();
                            sgt.setOptions({geoLocation: {kladr_id: new_region}});

                        }
                    }

                    //Устанавливаем индекс
                    if (suggestion.data.postal_code && $postcode.length) {
                        $postcode.val(suggestion.data.postal_code);
                    }

                    //Устанавливаем ограничение на улицы
                    if ($street.length) {
                        that.updateStreetConstraints(suggestion, $street);
                    }

                    if (!$address.data('cityselect-not-set')) {
                        that.by_detect = false;
                        that.setCity(suggestion.data, $city);
                    }
                }
            };

            if ($region.length) {
                $city_params.geoLocation = {kladr_id: String($region.val()).substr(0, 2)}
            }

            $city.suggestions($city_params);
        }

        if ($street.length) {
            that.initStreet($street, $city, $postcode, $region);
        }

    };


    that.initWaAddress = function () {
        $('.wa-address,.wa-field-address').not('.i-cityselect__wa--init').each(function () {
            that.initAddress($(this));
        });

        $('.wa-step-region-section').not('.i-cityselect__wa--init').each(function () {
            that.initRegionAddress($(this));
        });

        $('.wa-step-details-section').not('.i-cityselect__wa--init').each(function () {
            that.initDetailsAddress($(this));
        });
    };

    that.themeSupport = function () {

        //Выгодная покупка ()
        var $profitbuy_icon = $('.base-menu .b-cityselect__wrapper--profitbuy i.fa,.base-menu .b-cityselect__wrapper--profitbuy1 i.fa');
        if ($profitbuy_icon.length) {
            $profitbuy_icon.replaceWith('<i class="material-icons">&#xE55F;</i>');
        }

        var need_resize = false;

        //InCart
        var $incart__check = $('.b-cityselect__wrapper--incart,.b-cityselect__wrapper--incart1,.b-cityselect__wrapper--incart2');
        if ($incart__check.length) {
            $incart__check.find('.b-cityselect__city i').replaceWith('<div class="info-settings__icon"><svg class="icon icon-location" width="16" height="14"><use xlink:href="#icon-location"></use></svg></div>');
            need_resize = true;
        }

        //InCart сверху
        $incart__check = $('.top-bar__info-settings .b-cityselect__wrapper');
        if ($incart__check.length) {
            $incart__check.addClass('info-settings__btn info-settings__btn--account info-settings__btn--signed');
            $incart__check.find('.b-cityselect__city .i-cityselect__city').addClass('info-settings__text');
            $incart__check.find('.b-cityselect__city').removeClass('b-cityselect__city').addClass('info-settings__btn-inner');
            need_resize = true;
        }

        if (need_resize) {
            $('.top-menu__list').trigger('resize');
        }

    };

    that.closeModal = function () {

        if (that.modal_type == 'fancybox') {
            $.fancybox.close(true);
        }

        if (that.modal_type == 'remodal') {
            if (that.remodal_instance) {
                that.remodal_instance.close();

                //destroy нужен для совместимости с плагином stek35
                if ($('.stek35-container').length) {
                    that.remodal_instance.destroy();
                    that.remodal_instance = null;
                }
            }
        }

        if (that.modal_type == 'magnificPopup') {
            $.magnificPopup.instance.close();
        }

        that.modal_type = false;
    };

    //Для Shop-script 8
    that.initRegionAddress = function ($address) {

        $address.addClass('i-cityselect__wa--init');

        var $country = $address.find(that.find.country);
        var $region = $address.find(that.find.region);
        var $city = $address.find(that.find.city);
        var $postcode = $address.find(that.find.zip);

        //Обрабатываем страну
        if ($country.length) {

            //Если не Россия то не обрабатываем адрес
            if ($country.val() != 'rus') {
                return false;
            }

        }

        //Присутсвует город
        if ($city.length) {

            var $city_params = {
                token: that.token, type: "ADDRESS", hint: false, bounds: that.bounds, constraints: {label: ''},
                scrollOnFocus: false, triggerSelectOnBlur: false,
                formatSelected: function (suggestion) {
                    return suggestion.data.settlement ? suggestion.data.settlement : suggestion.data.city;
                },
                onSelect: function (suggestion) {
                    //Поле регион может меняться
                    var $region = $city.closest('.i-cityselect__wa--init').find(that.find.region);
                    if ($region.length && suggestion.data.region_kladr_id) {

                        //Смена региона
                        var new_region = String(suggestion.data.region_kladr_id).substr(0, 2);

                        if ($region.val() != new_region) {
                            $region.val(new_region);

                            //Устанавливаем приоритет города
                            var sgt = $city.suggestions();
                            sgt.setOptions({geoLocation: {kladr_id: new_region}});
                        }

                    }

                    //Устанавливаем индекс
                    if (suggestion.data.postal_code && $postcode.length) {
                        $postcode.val(suggestion.data.postal_code);//.change();
                    }

                    that.by_detect = false;
                    that.setCity(suggestion.data, $city);

                }
            };

            if ($region.length) {
                $city_params.geoLocation = {kladr_id: String($region.val()).substr(0, 2)}
            }

            $city.suggestions($city_params);
        }
    };

    that.initDetailsAddress = function ($address) {
        $address.addClass('i-cityselect__wa--init');

        //Поле улицы
        var $street = $address.find(that.find.street);

        if ($street.length) {

            //ищем город ограничитель
            var $city = $('.wa-step-region-section .js-city-field');

            //ищем индекс
            var $postcode = $address.find(that.find.zip);

            that.initStreet($street, $city, $postcode)
        }
    };

    that.initChangeInput = function () {

        $(".i-cityselect__input").suggestions({
            token: that.token,
            type: "ADDRESS",
            hint: false,
            bounds: that.bounds,
            scrollOnFocus: false,
            constraints: {
                label: ""
            },
            formatSelected: function (suggestion) {
                return suggestion.data.city;
            },
            onSelect: function (suggestion) {
                that.by_detect = false;
                that.setCity(suggestion.data, $(this));
                that.closeModal();
                that.hideNotifiers();
            }
        });
    };

    that.openByFancybox = function (url) {
        that.modal_type = 'fancybox';

        $.fancybox.open({
            type: 'ajax',
            href: url, //fancybox 2
            src: url, //fancybox 3
            afterShow: function () {
                that.initChangeInput()
            }, afterClose: function () {
                that.modal_type = false;
            }
        });
    };

    that.openModal = function (url) {

        //Конкретная тема
        var $profitbuy__check = $('.b-cityselect__wrapper--profitbuy,.b-cityselect__wrapper--profitbuy1,.b-cityselect__wrapper--profitbuy2');

        if ($profitbuy__check.length) {
            return that.openByFancybox(url);
        }

        //Присутсвует reModal
        if ($.remodal) {

            that.modal_type = 'remodal';

            //Удаляем Remodal и DOM
            if (that.remodal_instance) {
                that.remodal_instance.destroy();
                that.remodal_instance = null;
            }

            if ($('#cityselect__change').length) {
                $('#cityselect__change').remove();
            }

            $.get(url, function (response) {
                $('body').append(response);

                that.remodal_instance = $('#cityselect__change').on('opened', function () {
                    that.initChangeInput();
                }).on('closed', function () {
                    //Удаляем instance при закрытие
                    if (that.remodal_instance) {
                        that.remodal_instance.destroy();
                        that.remodal_instance = null;
                    }
                }).remodal();

                that.remodal_instance.open();

            })
        } else if ($.fancybox) {
            that.openByFancybox(url);
        } else if ($.magnificPopup) {

            that.modal_type = 'magnificPopup';

            $.magnificPopup.open({
                type: 'ajax',
                overflowY: 'scroll',
                closeBtnInside: true,
                items: {
                    src: url
                },
                callbacks: {
                    parseAjax: function (mfpResponse) {
                        mfpResponse.data = '<div class="b-cityselect__mfp">' + mfpResponse.data + '</div>';
                    },
                    ajaxContentAdded: function () {
                        that.initChangeInput();
                    }
                }
            });

        } else {

            var $link_css = $('<link/>', {
                rel: 'stylesheet',
                type: 'text/css',
                href: that.lib + "jquery.fancybox.css"
            });
            $link_css.appendTo('head');
            $link_css[0].onload = function () {
                $.getScript(that.lib + "jquery.fancybox.pack.js", function () {
                    that.openByFancybox(url);
                });
            };
        }
    };

    that.updateCheckout = function (data) {


        //ТОлько основные данные
        if (data.country) {
            $('[name$="[address.shipping][country]"]').val(data.country).change();
            $('.js-country-field').val(data.country);
        }

        if (data.region) {
            $('[name$="[address.shipping][region]"]').val(data.region).change();
            $('.js-region-field').val(data.region);
        }

        if (data.city) {
            $('[name$="[address.shipping][city]"]').val(data.city).change();
            $('.js-city-field').val(data.city);

            $('[name$="[address.shipping][zip]"]').val(data.zip ? data.zip : '').change();
            $('[name$="[shipping_address][zip]"]').val(data.zip ? data.zip : '').change();
            $('.js-zip-field').val(data.zip ? data.zip : '')
        }

        $('.js-city-field').trigger("region_change");

    };

    that.updateVariables = function (data) {

        if (!data.variables) {
            return;
        }

        for (var i in data.variables) {
            var variable = data.variables[i];

            var custom_change = false;

            try {
                var function_name = String(variable.code);
                function_name = 'updateVariable' + function_name.charAt(0).toUpperCase() + function_name.slice(1);

                if (that[function_name]) {
                    that[function_name](variable);
                    custom_change = true;
                }

            } catch (e) {
                console.log(e);
            }

            if (!custom_change) {
                $('.i-cityselect__var--' + variable['code']).replaceWith(variable.html);
            }

        }

    };

    that.changeCity = function () {
        that.openModal(that.url + 'shop_cityselect/change_city');
    };

    that.setCity = function (data, $target) {

        if (data !== false) {
            var city = data.settlement ? data.settlement : data.city;
            $('.i-cityselect__city').html(city);
        } else {
            data = {'set_default': 1}
        }

        data.route_params = that.route_params;

        $.post(that.url + 'shop_cityselect/set_city', data, function (response) {
            if (response.status && response.status === 'ok') {

                that.location = response.data;

                that.updateVariables(response.data);

                if (that.onSetCity) {
                    response.data = that.onSetCity(response.data);
                }

                if (response.data) {
                    that.updateCheckout(response.data);
                }

                //Если объект не передан или не сущестует
                if ((!$target) || (!$target.length) || (!$target.closest('body').length)) {
                    $target = $(document);
                }

                //Обработка перенаправления
                if (response.data.redirect) {

                    that.is_redirecting = true;

                    //Если по определению города
                    if (that.by_detect) {

                        //Отключаем показ
                        that.show_notifier = 'none';

                    }

                    if (response.data.save_cookie) {

                        var img = document.createElement('img');

                        img.onload = function () {
                            window.location.href = response.data.redirect_url;
                        };

                        img.onerror = function () {
                            window.location.href = response.data.redirect_url;
                        };

                        var src = response.data.save_cookie + '?t=' + Math.random() + '&' + response.data.save_data;

                        if (that.by_detect) {
                            src += '&cityselect__force_notify=1';
                        }

                        img.src = src;
                    }

                }

                if ((that.by_detect) && (that.show_notifier != 'none')) {
                    that.showNotifiers();
                }

                $target.trigger('cityselect__set_city', response.data);
            }
        });
    };

    //Смещает уведомление чтобы не выходило за край
    that.moveNotifier = function ($notifier, delta) {
        $notifier.data('delta-right', delta);
        $notifier.css('transform', 'translateX(' + delta + 'px)');
        $notifier.find('.b-cityselect__notifier_triangle').css('transform', 'translateX(' + (-delta) + 'px)');
    };

    //Проверка выхода окна уведомления за край экрана
    that.boundsNotifiers = function () {
        if (that.$notifiers && that.$notifiers.length) {
            that.$notifiers.each(function () {
                var $notifier = $(this);

                //Если смещение уже есть, снимаем его
                if ($notifier.data('delta-right')) {
                    that.moveNotifier($notifier, 0);
                }

                var right = $notifier.offset().left + $notifier.outerWidth(true) + 1;
                if (right > $(window).width()) {
                    var delta = $(window).width() - right;
                    that.moveNotifier($notifier, delta)
                }
            })
        }
    };

    //Показать уведомление
    that.showNotifiers = function () {
        if (that.notifier_type == 'modal') {
            $('.i-cityselect__notifier--modal').first().appendTo('body').modal();
        } else {
            that.$notifiers = $('.b-cityselect__notifier');
            that.$notifiers.show();
            that.boundsNotifiers();
        }

        $.post(that.url + 'shop_cityselect/show_notifier');
    };

    that.hideNotifiers = function () {
        if (that.notifier_type == 'modal') {
            $('.i-cityselect__notifier--modal').modal('hide');
        } else {
            if (that.$notifiers && that.$notifiers.length) {
                that.$notifiers.hide();
            } else {
                $('.b-cityselect__notifier').hide();
            }
        }
    };

    that.detectCity = function () {
        var serviceUrl = "https://suggestions.dadata.ru/suggestions/api/4_1/rs/detectAddressByIp";
        var params = {
            type: "GET",
            contentType: "application/json",
            headers: {
                "Authorization": "Token " + that.token
            }
        };
        return $.ajax(serviceUrl, params);
    };

    that.findAddressByFiasId = function (fias_id) {
        var serviceUrl = "https://suggestions.dadata.ru/suggestions/api/4_1/rs/findById/address";
        var request = {
            "query": fias_id
        };
        var params = {
            type: "POST",
            contentType: "application/json",
            headers: {
                "Authorization": "Token " + that.token
            },
            data: JSON.stringify(request)
        };

        return $.ajax(serviceUrl, params);
    };

    that.detect = function () {

        //защита от двойного определения
        if (that.is_detect) {
            return;
        }

        that.is_detect = true;

        //Отключение автоопределение и перенаправления
        if (that.disable_auto) {
            return;
        }


        that.detectCity().done(function (response) {

            that.by_detect = true;

            //Не удалось определить город
            if (!response.location || !response.location.data) {

                //false - установить город по умолчанию
                that.setCity(false);

            } else {

                var locationData = response.location.data;

                that.findAddressByFiasId(locationData.fias_id).done(function (response) {
                    if (response.suggestions[0]) {
                        locationData = response.suggestions[0].data;
                    }
                    that.setCity(locationData);

                });
            }
        });
    };

    that.setLocation = function ($address) {
        var $country = $address.find(that.find.country);
        var $region = $address.find(that.find.region);
        var $city = $address.find(that.find.city);
        var $postcode = $address.find(that.find.zip);


        if (that.location.country) {
            $country.val(that.location.country);
        }

        if (that.location.region) {
            $region.val(that.location.region);
        }

        if (that.location.city) {
            $city.val(that.location.city);
        }

        if (that.location.zip) {
            $postcode.val(that.location.zip);
        }
    };

    that.initWaCustom = function () {

        if (that.in_custom_form) {
            $(that.in_custom_form).not('.i-cityselect__wa--init').each(function () {
                that.initAddress($(this));
                that.setLocation($(this));
            });
        }

        if (that.in_custom_city) {
            $(that.in_custom_city).not('.i-cityselect__custom-city--init').each(function () {

                var $city = $(this);
                that.initCity($city);

                if (that.location.city) {
                    $city.val(that.location.city);
                }
            });
        }
    };

    $(document).on('click', '.i-cityselect__city_yes', function (e) {
        e.preventDefault();
        that.hideNotifiers();

        $.post(that.url + 'shop_cityselect/say_yes');
    });

    $(document).on('click', '.i-cityselect__city_no', function (e) {
        e.preventDefault();
        that.hideNotifiers();

        //вызываем выбор города
        $('.i-cityselect__city_change').first().click();
    });

    $(document).on('click', '.i-cityselect__city_change', function (e) {
        e.preventDefault();
        that.hideNotifiers();
        that.changeCity();
    });

    $(document).on('click', '.i-cityselect__change_close', function (e) {
        e.preventDefault();
        that.closeModal();
    });

    $(document).on('click', '.i-cityselect__set_city', function (e) {
        e.preventDefault();
        that.by_detect = false;
        that.setCity($(this).data(), $(this));
        that.closeModal();
    });

    //Интеграция с другими плагинами
    $(document).on('cityselect__set_city', function (e) {
        if (that.plugin_dp && window.shop_dp && !that.is_redirecting && !that.by_detect && !$(e.target).closest('.quickorder-form').length) {
            location.reload();
        }
    });

    //Обновление данных произвольных формы
    $(document).on('cityselect__set_city', function (event, data) {
        if (that.in_custom_form) {
            $(that.in_custom_form).each(function () {
                var $form = $(this);
                $form.find(that.find.country).val(data.country || '');
                $form.find(that.find.region).val(data.region || '');
                $form.find(that.find.city).val(data.city || '');
                $form.find(that.find.zip).val(data.zip || '');
            });
        }

        if (that.in_custom_city) {
            $(that.in_custom_city).each(function () {
                $(this).val(data.city || '');
            });
        }
    });


    $(window).resize(function () {
        that.boundsNotifiers();
    });

    $(document).on('wa_order_form_ready', function (e) {
        if (that.in_checkout) {
            that.initWaAddress();
        }
    });

    $(document).on('wa_order_form_region_changed', function () {
        if (that.in_checkout) {
            that.initWaAddress();
        }
    });

    $(document).on('wa_order_form_details_changed', function () {
        if (that.in_checkout) {
            that.initWaAddress();
        }
    });
}

//Глобальная переменная для доступа из вне
var shop_cityselect = new shopCityselectFrontend();