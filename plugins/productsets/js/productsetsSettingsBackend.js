var ProductsetsSettingsPlugin = (function ($) {

    ProductsetsSettingsPlugin = function (options) {
        var that = this;

        /* DOM */
        that.$form = options.wrap;

        that.$submitBtn = that.$form.find(':submit');
        that.isLocked = 0;

        /* INIT */
        that.initClass();
        that.bindEvents();
    };

    ProductsetsSettingsPlugin.prototype.initClass = function () {
        var that = this;

        that.initSave();
    };

    ProductsetsSettingsPlugin.prototype.bindEvents = function () {
        var that = this;

        /* Редактирование шаблона */
        that.$form.find('.js-edit-templates').click(function () {
            new igaponovDialog({
                url: '?plugin=productsets&module=dialog&action=templates&id=' + $(this).data('id'),
                saveUrl: '?plugin=productsets&action=saveTemplate',
                onBlockClick: function (event) {
                    event.stopPropagation();
                    that.initDialogEvents(event);
                }
            });
        });

        /* Восстановление шаблона по умолчанию */
        that.$form.find('.js-restore-template').click(function () {
            that.restoreTemplate($(this));
        });
    };

    /* Восстановление шаблона по умолчанию */
    ProductsetsSettingsPlugin.prototype.restoreTemplate = function ($elem) {
        var that = this;

        if (!$elem.next('i').length) {
            $elem.after('<i class="icon16 loading"></i>');
            $.post('?plugin=productsets&action=restoreTemplate', {id: $elem.data('id')}, function (response) {
                if (response.status == 'ok') {
                    $elem.next('i').removeClass('loading').addClass('yes');
                    if ($elem.closest('.w-dialog-content').length) {
                        $elem.closest('.w-dialog-content').find('.attention-block').remove();
                        if (response.data.length) {
                            wa_editor.setValue(response.data);
                        }
                    } else {
                        $elem.closest('.field').find('.attention-block').remove();
                    }
                } else {
                    $elem.next('i').removeClass('loading').addClass('no');
                }
            }).always(function () {
                setTimeout(function () {
                    $elem.next('i').remove();
                }, 2000);
            });
        }
    };

    /* События при клике на тело всплывающего блока */
    ProductsetsSettingsPlugin.prototype.initDialogEvents = function (event) {
        var that = this;

        var elem = $(event.target);

        /* Восстановление шаблона по умолчанию */
        if (elem.is('.js-restore-template')) {
            that.restoreTemplate(elem);
        }
    };

    /* Сохранение данных */
    ProductsetsSettingsPlugin.prototype.initSave = function () {
        var that = this,
            successTimeout = null,
            disabledOptions = null;

        that.$submitBtn.on("click", function () {
            that.$form.trigger("submit");
        });

        that.$form.on("submit", onSubmit);

        function onSubmit(event) {
            event.preventDefault();

            var formData = getData();
            request(formData);
        }

        function request(data) {
            if (!that.isLocked) {
                removeLoading();
                addLoading();
                that.lock();
                errorText();
                successTimeout && clearTimeout(successTimeout);
                var href = "?plugin=productsets&module=settings&action=save";
                $.post(href, data, function (response) {
                    if (typeof response !== 'undefined' && response.status == 'fail' && response.errors) {
                        errorText(response.errors);
                    } else {
                        successMessage();
                    }
                }, "json").always(function () {
                    that.unlock();
                    removeLoading();
                    disabledOptions.prop('disabled', true);
                });
            }
        }

        function addLoading() {
            that.$submitBtn.after("<i class='icon16 loading'></i>");
        }

        function removeLoading() {
            that.$submitBtn.next("i").remove();
        }

        function successMessage() {
            setTimeout(function () {
                that.$submitBtn.after("<i class='icon16 yes'></i>");
                successTimeout = setTimeout(function () {
                    removeLoading();
                }, 3000);
            }, 100);
        }

        function errorText(text) {
            text = text || '';
            that.$form.find('.errormsg').html(text);
        }

        function getData() {
            let data = that.$form.serializeArray();
            return data;
        }

    };

    /* Запретить сохранение данных */
    ProductsetsSettingsPlugin.prototype.lock = function () {
        var that = this;

        that.isLocked = 1;
        that.$submitBtn.prop('disabled', true);
    };

    /* Разрешить сохранение данных */
    ProductsetsSettingsPlugin.prototype.unlock = function () {
        var that = this;

        that.isLocked = 0;
        that.$submitBtn.prop('disabled', false);
    };

    return ProductsetsSettingsPlugin;

})(jQuery);