function shopCityselectBackend() {

    var that = this;

    that.token = '';
    that.bounds = 'city';

    that.find = {
        country: '[name$="country]"]',
        region: '[name$="region]"]',
        city: '[name$="city]"]',
        zip: '[name$="zip]"]',
        street: '[name$="street]"]'
    };


    that.color_timer = 0;

    that.addColor = function ($field) {
        clearTimeout(that.color_timer);

        $field.addClass('b-cityselect__green');
        that.color_timer = setTimeout(function () {
            $('.b-cityselect__green').removeClass('b-cityselect__green');
        }, 1000);
    };

    that.initAddress = function ($address) {

        $address.addClass('i-cityselect__wa--init');

        var $country = $address.find(that.find.country);
        var $city = $address.find(that.find.city);
        var $street = $address.find(that.find.street);
        var $postcode = $address.find(that.find.zip);

        //Присутсвует город
        if ($city.length) {

            $city.suggestions({
                token: that.token, type: "ADDRESS", hint: false, bounds: that.bounds, constraints: {label: ''},
                formatSelected: function (suggestion) {
                    return suggestion.data.settlement ? suggestion.data.settlement : suggestion.data.city;
                },
                onSelect: function (suggestion) {

                    //Поле регион может меняться
                    var $region = $city.closest('.i-cityselect__wa--init').find(that.find.region);
                    if ($region.length && suggestion.data.region_kladr_id) {
                        $region.val(String(suggestion.data.region_kladr_id).substr(0, 2)).change();
                        that.addColor($region);
                    }

                    //Устанавливаем индекс
                    if (suggestion.data.postal_code && $postcode.length) {
                        $postcode.val(suggestion.data.postal_code);
                        that.addColor($postcode);
                    }

                    //Устанавливаем ограничение на улицы
                    if ($street.length) {

                        var sgt = $street.suggestions();
                        var locations = {};
                        if (suggestion.data.kladr_id) {
                            locations.kladr_id = suggestion.data.kladr_id;
                        } else {
                            if (suggestion.data.settlement) {
                                locations.settlement = suggestion.data.settlement;
                            } else {
                                locations.city = suggestion.data.city;
                            }
                        }

                        sgt.setOptions({
                            constraints: {
                                locations: locations
                            }
                        });
                    }
                }
            });
        }

        if ($street.length) {
            that.initStreet($street, $city, $postcode);
        }

    };

    that.initStreet = function ($street, $city, $postcode) {

        //выясняем ограничения
        var locations = {};

        if ($city.length) {
            locations.city = String($city.val()).trim();
        }

        $street.suggestions({
            token: that.token,
            type: "ADDRESS",
            constraints: {
                label: '',
                locations: locations
            },
            restrict_value: true,
            onSelect: function onSelect(suggestion) {
                if (suggestion.data.postal_code && $postcode.length) {
                    $postcode.val(suggestion.data.postal_code);
                    that.addColor($postcode);
                }
            }
        });
    };

    that.initFields = function () {
        $("#order-edit-form .field-address").not('.i-cityselect__wa--init').each(function () {
            that.initAddress($(this));
        });
        $("#new-customer-form .field-address").not('.i-cityselect__wa--init').each(function () {
            that.initAddress($(this));
        })
    };

    that.init = function (token, bounds) {

        that.token = token;
        that.bounds = bounds;

        that.initFields();
    };

    $(document).on('afterReload', function () {
        that.initFields();
    });

    //Rewrite page settings click
    $(document).ajaxSuccess(function (event, xhr, settings) {

        //Page settings
        if (settings.url.indexOf('?module=customers&action=add') != -1) {
            that.initFields();
        }
    });

}

//Глобальная переменная для доступа из вне
var shop_cityselect__backend = new shopCityselectBackend();