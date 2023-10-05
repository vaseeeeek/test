(function ($) {
    $.fn.serializeAllArray = function () {
        var obj = {};

        $('.form_field',this).each(function () {
            obj[this.name] = $(this).val();
        });

        return $.param(obj);
    }
})(jQuery);

(function($) {
    'use sctrict'
    $.pluginsBackend = {
        init: function() {
            this.initSave();
        },
        initSave: function() {
            $('.plugins__save').click(function() {
                var btn = $(this);
                var form = btn.closest('form');
                var errormsg = form.find('.errormsg');
                errormsg.text('');
                btn.next("i.icon16").remove();
                btn.attr('disabled', 'disabled').after('<i style="vertical-align:middle" class="icon16 loading"></i>');
                $.ajax({
                    url: "?plugin=colorfeaturespro&module=settings&action=save",
                    data: form.serializeArray(),
                    dataType: "json",
                    type: "post",
                    success: function(response) {
                        console.log(response);
                        btn.removeAttr('disabled').next().remove();
                        if (typeof response.errors !== 'undefined') {
                            console.log(11111);
                            if (typeof response.errors.messages != 'undefined') {
                                $.each(response.errors.messages, function(i, v) {
                                    errormsg.append(v + "<br />");
                                });
                            }
                        } else if (response.status === 'ok' && response.data) {
                            btn.after('<i style="vertical-align:middle" class="icon16 yes"></i>');

                        } else {
                            btn.after('<i style="vertical-align:middle" class="icon16 no"></i>');
                        }
                    },
                    error: function() {
                        errormsg.text($_('Что-то не так.'));
                        btn.removeAttr('disabled').next().remove();
                        btn.after('<i style="vertical-align:middle" class="icon16 no"></i>');
                    }
                });
                return false;
            });

        },

    }

    $.pluginsBackend.init();
})(jQuery);