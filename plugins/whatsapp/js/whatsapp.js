jQuery(document).ready(function ($) {
    $.whatsapp = {
        init: function (options) {
            var locale = options.locale || {};
            $.wa.locale = $.extend(true, $.wa.locale, locale);

            // Сохранение значений формы
            $("#fields-form input[type='submit']").click(function () {
                var btn = $(this);
                var form = btn.parents("form");
                var errormsg = form.find(".errormsg");
                errormsg.text("");

                btn.next("i.icon16").remove();
                btn.attr('disabled', 'disabled').after("<i class='icon16 loading temp-loader'></i>");
                $.ajax({
                    url: "?plugin=whatsapp&module=settings&action=save",
                    data: form.serializeArray(),
                    dataType: "json",
                    type: "post",
                    success: function (response) {
                        btn.removeAttr('disabled').next(".temp-loader").remove();
                        if (typeof response.errors != 'undefined') {
                            if (typeof response.errors.messages != 'undefined') {
                                $.each(response.errors.messages, function (i, v) {
                                    errormsg.append(v + "<br />");
                                });
                            }
                        } else if (response.status == 'ok' && response.data) {
                            btn.after("<i class='icon16 yes'></i>");
                        } else {
                            btn.after("<i class='icon16 no'></i>");
                        }
                    },
                    error: function () {
                        errormsg.text($_('Something wrong'));
                        btn.removeAttr('disabled').next(".temp-loader").remove();
                        btn.after("<i class='icon16 no'></i>");
                    }
                });
                return false;
            });

            $("#whatsapp-text").on('keyup keydown change', function () {
                var val = 200 - $(this).val().length;
                $("#whatsapp-text-change").text(val);
                if (val < 0 && !$("#whatsapp-text-change").hasClass("red")) {
                    $("#whatsapp-text-change").addClass("red");
                } else if (val > 0) {
                    $("#whatsapp-text-change").removeClass("red");
                }
            });
            $("#whatsapp-text").change();

            // IButton switcher
            $('.switcher').iButton({
                labelOn: "", labelOff: "", className: 'mini'
            }).change(function () {
                var onLabelSelector = '#' + this.id + '-on-label',
                    offLabelSelector = '#' + this.id + '-off-label';
                var additinalField = $(this).closest('.ibutton-checkbox').next('.onopen');
                if (!this.checked) {
                    if (additinalField.length) {
                        additinalField.hide();
                    }
                    $(onLabelSelector).addClass('unselected');
                    $(offLabelSelector).removeClass('unselected');
                } else {
                    if (additinalField.length) {
                        additinalField.css('display', 'inline-block');
                    }
                    $(onLabelSelector).removeClass('unselected');
                    $(offLabelSelector).addClass('unselected');
                }
            });

            this.colorOptions.init();
        },
        colorOptions: {
            farbtastic: '',
            color_picker: '',
            init: function () {
                this.color_picker = $('#s-colorpicker');
                this.farbtastic = $.farbtastic(this.color_picker);
                $('.s-color').each(function () {
                    var input = $(this);
                    $.whatsapp.colorOptions.initNewItem(input);
                });
                $(document).on("click", ".option-input", function () {
                    $.whatsapp.colorOptions.inputFocus($(this).next());
                });
            },
            callback: function (input, color) {
                input.next().find('i').css('background', color);
                input.val(color.substr(1));
            },
            initNewItem: function (input) {
                $('.s-color').css('opacity', 0.75).removeClass('color-selected');
                input.css('opacity', 1).addClass('color-selected');
                $.whatsapp.colorOptions.farbtastic.linkTo(function (color) {
                    $.whatsapp.colorOptions.callback(input, color);
                });
                var timer_id;
                input.unbind('keydown').bind('keydown', function () {
                    if (timer_id) {
                        clearTimeout(timer_id);
                    }
                    timer_id = setTimeout(function () {
                        $.whatsapp.colorOptions.farbtastic.setColor('#' + input.val());
                    }, 250);
                });
                input.focus(function () {
                    $.whatsapp.colorOptions.inputFocus(input);
                }).next().click(function () {
                    $.whatsapp.colorOptions.inputFocus(input);
                });
                $.whatsapp.colorOptions.farbtastic.setColor("#" + input.val());
            },
            inputFocus: function (input) {
                $('.s-color').css('opacity', 0.75).removeClass('color-selected');
                input.css('opacity', 1).addClass('color-selected');
                $.whatsapp.colorOptions.farbtastic.linkTo(function (color) {
                    $.whatsapp.colorOptions.callback(input, color);
                });
                $.whatsapp.colorOptions.farbtastic.setColor("#" + input.val());
            }
        }
    };
});