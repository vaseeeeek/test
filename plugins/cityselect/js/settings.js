/**
 * Функционал настроек
 *
 * .i-settings__checkbox - для чекбоксов
 * .i-settings__select - Зависимые области от select
 * .i-settings__routes - изменение витрины
 * .i-settings__load - загрузка витрины
 * .i-settings__reset - загрузка сброса поселения
 * .i-settings__reset_css - сбросить CSS файл поселения
 * .i-settings__reset_js - сбросить JS файл поселения
 * .i-settings__reset_templates - сбросить шаблоны
 * .i-settings__tabs - вкладки
 * .i-settings__color - выбор цвета
 * .i-settings__upload - секция загрузки изображений
 * .i-settings__code - редактор кода CodeMirror
 * .i-settings__multi_checkbox - сбор множественных чекбоксов в одну строку
 * .i-settings__submenu - глобальные вкладки настроек
 */

function SettingsEchoCompany() {

    var self = this;

    self.saveCode = function () {
        $('.i-settings__code').each(function () {
            if ($(this).data('editor')) {
                $(this).data('editor').save();
            }
        });
        return true;
    };

    self.addItem = function (city, region, zip, bold) {

        bold = bold ? ' checked ' : '';
        var bold_id = bold ? '1' : '0';

        var html = $('#city_field__template').html();
        html = html.replace(new RegExp('%city%', 'g'), city);
        html = html.replace(new RegExp('%region%', 'g'), region);
        html = html.replace(new RegExp('%zip%', 'g'), zip);
        html = html.replace(new RegExp('%bold%', 'g'), bold);
        html = html.replace(new RegExp('%bold_id%', 'g'), bold_id);

        $('.i-settings__city_list').append(html).sortable('refresh');
    };

    self.refreshCode = function () {
        $('.i-settings__code').each(function () {
            if ($(this).data('editor')) {
                $(this).data('editor').refresh();

                //странный баг, срабоатывает на скрытых только со второго вызова
                $(this).data('editor').refresh();
            }
        });
    };

    self.initCode = function () {

        $('.i-settings__code').each(function () {

            var code = $(this);

            if (!code.data('editor')) {

                var mode = code.data('mode') ? code.data('mode') : "text/html";

                var editor = CodeMirror.fromTextArea(this, {
                    mode: mode,
                    tabMode: "indent",
                    height: "dynamic",
                    lineWrapping: true,
                    extraKeys: {
                        'Ctrl+S': function (e) {
                            alert(e);
                        }
                    }

                });

                code.data('editor', editor);

            }

        });

    };

    self.initColor = function (input) {

        //webAsyst standart color select
        var replacer = $('<span class="color-replacer">' +
            '<i class="icon16 color" style="background: #' + input.val().substr(1) + '"></i>' +
            '</span>').insertAfter(input);
        var picker = $('<div style="display:none;" class="color-picker"></div>').insertAfter(replacer);
        var farbtastic = $.farbtastic(picker, function (color) {
            replacer.find('i').css('background', color);
            input.val(color);
        });
        farbtastic.setColor('#' + input.val());
        replacer.click(function () {
            picker.slideToggle(200);
            return false;
        });

        var timer_id;
        input.unbind('keydown').bind('keydown', function () {
            if (timer_id) {
                clearTimeout(timer_id);
            }
            timer_id = setTimeout(function () {
                farbtastic.setColor(input.val());
            }, 250);
        });
    };

    //инициализация поселения
    self.initRoute = function () {
        $('.i-settings__color').each(function () {
            self.initColor($(this));
            self.initCode();
        });

        self.initCode();

        $.wa.errorHandler = function (xhr) {

        };

        $('.i-settings__city').suggestions({
            token: $('#current__token').val(),
            type: "ADDRESS",
            hint: false,
            bounds: "city",
            constraints: {
                label: ""
            },
            globals: false,
            formatSelected: function (suggestion) {
                return suggestion.data.city;
            },
            onSelect: function (suggestion) {
                $('.i-settings__zip').val(suggestion.data.postal_code);
                $('.i-settings__region').val(String(suggestion.data.region_kladr_id).substr(0, 2));
                $('.i-settings__add').click();
            }
        });

        $(".b-sortable").sortable({placeholder: "ui-state-highlight"});
        $(".b-sortable").disableSelection();
    };


    //Инициализация
    self.init = function () {

        //Сохранение по Ctrl+S
        CodeMirror.commands.save = function () {
            $('#plugins-settings-form input[type="submit"]').click();
        };


        //Предупреждение о цене подарка
        $('.i-settings__warning').on('change keyup', function (e) {
            var value = $(this).val();
            if ((value != '0') && (value != '')) {
                $('.i-settings__warning_message').stop(false, false).slideDown();
            } else {
                $('.i-settings__warning_message').stop(false, false).slideUp();
            }
        });

        //Цвета
        $('.i-settings__color').each(function () {
            self.initColor($(this));
        });

        //вкладки
        $(document).on('click', '.i-settings__tabs .tabs a', function (e) {

            $(this).closest('.tabs').find('.selected').removeClass('selected');
            $(this).parent().addClass('selected');

            var $tabs = $(this).closest('.i-settings__tabs');

            $tabs.find('.i-settings__tab').hide();

            var tab = $(this).data('tab');
            $tabs.find('.i-settings__tab[data-tab="' + tab + '"]').show();

            $.cookie($tabs.data('cookie'), tab, {expires: 356, path: '/'});

            $tabs.find('.i-settings__tab_current').val(tab);

            //Обновляем если есть редакторы
            self.refreshCode();

            e.preventDefault();
        });

        //checkbox
        $(document).off('change', '.i-settings__checkbox').on('change', '.i-settings__checkbox', function () {
            var val = ($(this).is(':checked')) ? 1 : 0;

            $(this).parent().find('input[type=hidden]').val(val);

            //Связанные параметры
            if ($(this).data('target')) {
                var relative = $('[data-relative=' + $(this).data('target') + ']');

                if (val === 1) {
                    relative.show();

                    //Обновляем если есть редакторы
                    self.refreshCode();

                } else {
                    relative.hide();
                }
            }
        });

        //Мультивитринность
        var $load = $('.i-settings__load');
        var $reset = $('.i-settings__reset');
        var $routes = $('.i-settings__routes');

        //Смена поселения
        $routes.change(function () {
            $load.html('<i class="icon16 loading"></i>');

            var val = $(this).val();

            if (val == '0') {
                $reset.hide();
            } else {
                $reset.show();
            }

            $.get($routes.data('url') + '&current_route=' + val, function (data) {
                $load.html(data);
                self.initRoute();
            });
        }).change();

        //Сброс поселений
        $reset.click(function (e) {
            e.preventDefault();
            if (confirm($reset.data('confirm'))) {
                $load.html('<i class="icon16 loading"></i>');
                $.get($routes.data('url') + '&reset=1&current_route=' + $routes.val(), function (data) {
                    $load.html(data);
                    self.initRoute();
                });
            }
        });

        //загрузка файлов
        $(document).on('change', '.i-settings__upload input[type=file]', function () {

            var upload_item = $(this);
            var upload = upload_item.closest('.i-settings__upload');
            var loading = upload.find('.i-settings__upload_loading');
            var error = upload.find('.i-settings__upload_error');


            var formData = new FormData();

            var $form = upload.closest('form');
            var csrf = $form.find('[name=_csrf]').val();
            formData.append('_csrf', csrf);

            formData.append('type', upload.data('type'));
            formData.append('file', upload_item[0].files[0]);

            upload_item.hide();
            error.hide();
            loading.show();

            $.ajax({
                url: upload.data('url'),
                data: formData,
                type: 'POST',
                contentType: false,
                processData: false,
                global: false,
                dataType: 'json',
                success: function (json) {
                    if (json.status == 'ok') {
                        upload.find('.i-settings__upload_value').val(json.url);
                        upload.find('.i-settings__upload_image').attr("src", json.url);
                        upload.find('.i-settings__upload_image_block').show();
                    } else {
                        error.show().html(error.data('error') + ": " + json.error);
                    }
                }
            }).fail(function () {
                error.show().html(error.data('error'));
            }).always(function () {
                upload_item.show().val('');
                loading.hide();
            })
        });

        $(document).on('click', '.i-settings__upload_delete', function (e) {
            e.preventDefault();
            var upload = $(this).closest('.i-settings__upload');
            upload.find('.i-settings__upload_value').val('');
            upload.find('.i-settings__upload_image').attr("src", '');
            upload.find('.i-settings__upload_image_block').hide();
        });

        //Переключатель по select
        $(document).on('change', '.i-settings__select', function (e) {
            var target = $(this).data('target');

            if (target) {
                $("." + target).hide();
                $("." + target + "--" + $(this).val()).show();
            }
        });

        //Множественные чекбоксы
        $(document).on('change', '.i-settings__multi_checkbox', function (e) {
            var $field = $(this).closest('.field');
            var value = $field.find('.i-settings__multi_checkbox:checked').map(function () {
                return this.value;
            }).get().join(',');
            $field.find('input[type="hidden"]').val(value);
        });

        $(document).off('click', '.i-settings__copy_code').on('click', '.i-settings__copy_code', function () {
            var el = $('<input class="b-settings__copy_code_input" style="font-weight: bold; vertical-align: baseline;min-width:0;border: 1px solid #ccc; padding: 1px; width:' + ($(this).width() + 2) + 'px !important" type="text" readonly="readonly" />').val($(this).text()).focus(function () {
                $(this).select();
            }).mouseup(function (e) {
                e.preventDefault();
            });
            $(this).replaceWith(el);
            el.select();
        });

        //Сброс CSS JS файлов
        $(document).off('click', '.i-settings__reset_css').on('click', '.i-settings__reset_css', function (e) {
            e.preventDefault();
            if (confirm($(this).data('confirm'))) {
                $load.html('<i class="icon16 loading"></i>');
                $.get($routes.data('url') + '&reset_css=1&current_route=' + $routes.val(), function (data) {
                    $load.html(data);
                    self.initRoute();
                });
            }
        });

        $(document).off('click', '.i-settings__reset_js').on('click', '.i-settings__reset_js', function (e) {
            e.preventDefault();
            if (confirm($(this).data('confirm'))) {
                $load.html('<i class="icon16 loading"></i>');
                $.get($routes.data('url') + '&reset_js=1&current_route=' + $routes.val(), function (data) {
                    $load.html(data);
                    self.initRoute();
                });
            }
        });

        //Сброс Шаблонов
        $(document).off('click', '.i-settings__reset_templates').on('click', '.i-settings__reset_templates', function (e) {
            e.preventDefault();
            if (confirm($(this).data('confirm'))) {
                $load.html('<i class="icon16 loading"></i>');
                $.get($routes.data('url') + '&reset_templates=' + $(this).data('templates') + '&current_route=' + $routes.val(), function (data) {
                    $load.html(data);
                    self.initRoute();
                });
            }
        });


        //Функции плагина
        $(document).on('click', '.i-cityselect-city__delete', function (e) {
            e.preventDefault();
            if (confirm($(this).data('confirm'))) {
                $(this).closest('.i-cityselect-city').remove();
                $(this).closest('.b-sortable').sortable('refresh');
            }
        });


        $(document).on('click', '.i-settings__add', function (e) {

            var city = $('.i-settings__city').val();
            var region = $('.i-settings__region').val();
            var zip = $('.i-settings__zip').val();

            if (city == '') {
                $('.i-settings__city').focus();
                return;
            }

            if (region == '') {
                $('.i-settings__region').focus();
                return;
            }

            self.addItem(city, region, zip, $('.i-settings__select').is(':checked'));

            $('.i-settings__city').val('');
            $('.i-settings__region').val('');
            $('.i-settings__zip').val('');

        });


        $(document).on('click', '.i-settings__submenu', function (e) {

            e.preventDefault();

            var $link = $(this);
            var active_class = 'i-settings__sub_content--active';

            var $active = $('.' + active_class);
            var $target = $('.i-settings__sub_content--' + $link.data('target'));

            if ($active.is($target)) {
                return;
            }

            $active.slideUp('fast', function () {
                $active.removeClass(active_class);
                $target.addClass(active_class).slideDown('fast');
            });

        });

        $(document).off('click', '.i-cityselect__delete_variables_type').on('click', '.i-cityselect__delete_variables_type', function (e) {
            e.preventDefault();
            var $link = $(this);

            if (!confirm($link.data('confirm'))) {
                return;
            }
            $.post('?plugin=cityselect&module=settings&action=deleteVariablesType', $link.data(), function () {
                $('.i-cityselect__variables_type--' + $link.data('id')).remove();
            });
        });


        $(document).off('click', '.i-cityselect__delete_region').on('click', '.i-cityselect__delete_region', function (e) {
            e.preventDefault();
            var $link = $(this);

            if (!confirm($link.data('confirm'))) {
                return;
            }
            $.post('?plugin=cityselect&module=settings&action=deleteRegion', $link.data(), function () {
                $('.i-cityselect__region--' + $link.data('id')).remove();
            });
        });


        $(document).off('click', '.i-cityselect__change_variables_type').on('click', '.i-cityselect__change_variables_type', function (e) {
            e.preventDefault();

            $.get('?plugin=cityselect&module=settings&action=editVariablesType', $(this).data(), function (response) {
                $(response).waDialog({
                    'onLoad': function () {
                        self.initCode();
                    },
                    'onSubmit': function (d) {
                        $.post('?plugin=cityselect&module=settings&action=saveVariablesType', $(this).serialize(), function (response) {

                            if (response.status != 'ok') {
                                $('.i-dialog__error').show();
                                $('.i-dialog__error_text').html(response.error);
                            } else {
                                var id = response.id;

                                var $find = $('.i-cityselect__variables_type--' + id);
                                if ($find.length) {
                                    $find.replaceWith(response.html);
                                } else {
                                    $('.i-cityselect__variables_type_list').append(response.html);
                                }
                                d.trigger('close');
                            }


                            // var $item = $(response);
                            // var id = $item.data('id');
                            //
                            // var $find = $('.i-cityselect__variables_type--' + id);
                            // if ($find.length) {
                            //     $find.replaceWith($item);
                            // } else {
                            //     $('.i-cityselect__variables_type_list').append($item);
                            // }
                            //
                            // d.trigger('close');
                        }, 'json');
                        return false;
                    },
                    'disableButtonsOnSubmit': true,
                    'buttons': '<button type="submit" class="button green">Сохранить</button> <input type="button" value="Закрыть" class="button cancel" /> <span class="i-dialog__error b-settings__dialog_error red" style="display: none"><i class="icon16 exclamation-red"></i> <span class="i-dialog__error_text"></span></span>',
                    'height': '240px',
                    'width': '420px'
                });
            });
        });

        $(document).off('change', '#cityselect__variables_type_code').on('change', '#cityselect__variables_type_code', function () {
            var $input = $(this);
            $('.i-settings__replace_code').text($input.val());
        });

        $(document).off('click', '.i-cityselect__change_region').on('click', '.i-cityselect__change_region', function (e) {
            e.preventDefault();

            $.get('?plugin=cityselect&module=settings&action=editRegion', $(this).data(), function (response) {
                $(response).waDialog({
                    'onLoad': function () {
                        self.initCode();

                        var token = $('#current__token').val();

                        if (!token) {
                            $('.i-cityselect__form_region_token').show();
                        } else {
                            $('.i-cityselect__form_region_token').hide();
                            $('#cityselect__region_city').suggestions({
                                token: $('#current__token').val(),
                                type: "ADDRESS",
                                hint: false,
                                bounds: "city",
                                constraints: {
                                    label: ""
                                },
                                globals: false,
                                formatSelected: function (suggestion) {
                                    return suggestion.data.city;
                                },
                                onSelect: function (suggestion) {
                                    $('#cityselect__region_region').val(String(suggestion.data.region_kladr_id).substr(0, 2));

                                    var data = {
                                        'id': $('.i-cityselect__form_region_id').val(),
                                        'region': $('#cityselect__region_region').val(),
                                        'city': $('#cityselect__region_city').val()
                                    };

                                    //Проверка региона
                                    $.post('?plugin=cityselect&module=settings&action=checkRegion', data, function (response) {
                                        if (response.status != 'ok') {
                                            $('.i-dialog__error').show();
                                            $('.i-dialog__error_text').html(response.error);
                                        } else {
                                            $('.i-dialog__error').hide();
                                        }
                                    }, 'json')
                                }
                            });
                        }

                    },
                    'onSubmit': function (d) {
                        $('.i-dialog__error').hide();

                        $.post('?plugin=cityselect&module=settings&action=saveRegion', $(this).serialize(), function (response) {
                            if (response.status != 'ok') {
                                $('.i-dialog__error').show();
                                $('.i-dialog__error_text').html(response.error);
                            } else {
                                var id = response.id;

                                var $find = $('.i-cityselect__region--' + id);
                                if ($find.length) {
                                    $find.replaceWith(response.html);
                                } else {
                                    $('.i-cityselect__regions_list').append(response.html);
                                }
                                d.trigger('close');
                            }

                        }, 'json');
                        return false;
                    },
                    'onClose': function () {
                        this.remove();
                    },
                    'disableButtonsOnSubmit': true,
                    'buttons': '<button type="submit" class="button green">Сохранить</button> <input type="button" value="Закрыть" class="button cancel" /> <span class="i-dialog__error b-settings__dialog_error red" style="display: none"><i class="icon16 exclamation-red"></i> <span class="i-dialog__error_text"></span></span>',
                    //'height': 'auto',
                    'width': '420px'
                });
            });
        });


    };


    //Запускаем  инициализацию
    $(document).ready(function () {
        self.init();
    });
}

var settings__echo_company = new SettingsEchoCompany();