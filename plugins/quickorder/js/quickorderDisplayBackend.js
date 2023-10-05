var QuickorderPluginDisplay = (function ($) {

    QuickorderPluginDisplay = function (options) {
        var that = this;

        /* DOM */
        that.$wrap = options.wrap;

        /* DYNAMIC VARS */
        that.fields = options.fields || null;
        that.shippingFields = options.shippingFields || null;
        that.paymentFields = options.paymentFields || null;
        that.tab = options.tab || 'product';

        /* INIT */
        that.initClass();
        that.initFields();
        that.bindEvents();
    };

    QuickorderPluginDisplay.prototype.initClass = function () {
        var that = this;

        /* Сортировка контактных полей */
        that.$wrap.find('.f-form-fields').sortable({
            distance: 5,
            opacity: 0.75,
            items: '> .field',
            handle: '.sort',
            cursor: 'move',
            tolerance: 'pointer'
        });
    };

    QuickorderPluginDisplay.prototype.bindEvents = function () {
        var that = this;

        /* Скрытие/отображение кнопки в разделе "Отображение" */
        that.$wrap.find('.f-hide-button-checkbox').change(function () {
            var checkbox = this;
            if (checkbox.checked) {
                $(checkbox).closest('.field-group').find('.f-display-button-settings').slideUp();
            } else {
                $(checkbox).closest('.field-group').find('.f-display-button-settings').slideDown();
            }
        });

        /* Добавление полей контакта, доставки, оплаты */
        that.$wrap.find('.f-add-form-field').click(function () {
            var elem = $(this);
            elem.closest('.field-group').find('.f-form-fields').append(tmpl(elem.data('tmpl')));
            that.disableActiveFields(elem.closest('.field-group').find('.f-form-fields'))
        });

        /* Сделать выбранные поля недоступными для повторного выбора при изменении типа поля */
        that.$wrap.find('.f-form-fields').change(function () {
            that.disableActiveFields($(this));
        });
        that.$wrap.on('click', '.f-delete-form-fields', function () {
            var elem = $(this),
                formFields = elem.closest('.field-group').find('.f-form-fields');
            elem.closest('.field').remove();
            that.disableActiveFields(formFields);
        });

        /* Дополнительное поле для телефона */
        $(document).off('change', '.f-contact-field-type').on('change', '.f-contact-field-type', function () {
            var select = $(this);
            if ($(':selected', select).val() == 'phone') {
                if (!select.next('.f-contact-field-extra').length) {
                    select.after(tmpl('tmpl-field-extra'));
                    select.closest('.field').find('input[name="placeholder"]').after(tmpl('tmpl-field-extra-2'));
                } else {
                    select.next().show();
                    select.closest('.field').find('.f-contact-field-extra2').show();
                }
            } else {
                select.next('.f-contact-field-extra').hide();
                select.closest('.field').find('.f-contact-field-extra2').hide()
            }
        });

        /* Появление дополнительных настроек контактных полей */
        $(document).off('click', '.f-contact-field-additional').on('click', '.f-contact-field-additional', function () {
            $(this).hide().closest('.field').find(".f-contact-additional-field").show();
        });

        /* Скрытие/появление названия поля у контактных полей */
        $(document).off('change', '.f-contact-hide-name').on('change', '.f-contact-hide-name', function () {
            var checkbox = this;
            if (checkbox.checked) {
                $(checkbox).closest('.field').find('.f-contact-field-name').slideUp().parent().removeClass('width25');
            } else {
                $(checkbox).closest('.field').find('.f-contact-field-name').slideDown().parent().addClass('width25');
            }
        });
    };

    /* Сделать выбранные поля недоступными для повторного выбора */
    QuickorderPluginDisplay.prototype.disableActiveFields = function (container) {
        var disabledFields = $.map($.makeArray(container.find('select')), function (field) {
            return field.value;
        });
        container.find('select option').prop('disabled', false);
        $.each(disabledFields, function (i, v) {
            container.find('select option[value="' + v + '"]').prop('disabled', true);
        });
    };

    /* Заполнение полей контактной информации, доставки, оплаты */
    QuickorderPluginDisplay.prototype.initFields = function () {
        var that = this;

        _initFields(that.fields, 'contact');
        _initFields(that.shippingFields, 'shipping');
        _initFields(that.paymentFields, 'payment');

        function _initFields(fields, type) {
            var preparedFields = that.prepareFields(fields),
                container = that.$wrap.find('.f-' + that.tab + '-' + type + '-fields');

            container.html(tmpl('tmpl-' + type + '-fields', {fields: preparedFields}));
            that.disableActiveFields(container);
        }
    };

    QuickorderPluginDisplay.prototype.prepareFields = function (fields) {
        var data = [];
        for (var i in fields) {
            if (fields[i].length) {
                var field = {};
                for (var j in fields[i]) {
                    var obj = fields[i][j];
                    field[obj.name] = obj.value;
                }
                data.push(field);
            }
        }
        return data;
    };


    return QuickorderPluginDisplay;

})(jQuery);