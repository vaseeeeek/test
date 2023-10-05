var FlexdiscountPluginSettings = (function () {

    FlexdiscountPluginSettings = function (options) {
        const that = this;

        that.core = $.flexdiscount;
        that.$wrap = $(options.wrap);
        that.$form = that.$wrap.find('.flexdiscount-save-form');

        that.initClass(options);
        that.bindEvents();
    };

    FlexdiscountPluginSettings.prototype.initClass = function (options) {
        const that = this;

        that.core.initBase(options);
        $.shop.marketing !== undefined && $.shop.marketing.setTitle($__('Flexdiscount'));

        /* Активируем / деактивируем плагин */
        const pluginId = 'flexdiscount';
        /* IButton */
        that.$wrap.find('.js-change-global-status').iButton({
            labelOn: "",
            labelOff: "",
            className: 'mini'
        }).change(function () {
            const self = $(this);
            const enabled = self.is(':checked');
            if (enabled) {
                self.closest('.field-group').siblings().show(200);
                $('#discount-types a[rel="' + pluginId + '"] i.icon16').attr('class', 'icon16 status-blue-tiny');
            } else {
                self.closest('.field-group').siblings().hide(200);
                $('#discount-types a[rel="' + pluginId + '"] i.icon16').attr('class', 'icon16 status-gray-tiny');
            }
            $.post('?plugin=flexdiscount&action=handler', { data: 'pluginStatus', enable: enabled ? '1' : '0' });
        });
        that.core.initSwitcher(that.$wrap.find(".switcher"));
    };

    FlexdiscountPluginSettings.prototype.bindEvents = function () {
        const that = this;

        /* Сохранение настроек */
        that.$form.submit(function () {
            that.save($(this));
            return false;
        });

        /* Выбор плагина из списка для игнорирования */
        $(".plugin-list :checkbox").change(function () {
            const self = $(this);
            if (self.prop("checked")) {
                self.closest('li').addClass("selected");
            } else {
                self.closest('li').removeClass("selected");
            }
        });

        /* Отображение всего списка плагинов */
        that.$form.find('.js-show-more-plugins').click(function () {
            const self = $(this);
            self.siblings('.plugin-list').find('li').slideDown();
            self.remove();
        });

        /* Всплывающее окно системных настроек */
        that.$wrap.find('.js-system-settings').click(function () {
            that.showSystemSettings();
        });

        /* Всплывающее окно с блоками для редактирования */
        that.$form.find('.js-customize-block').click(function () {
            that.showCustomizeBlock($(this));
        });

        /* Скрытие/открытие содержимого параграфов */
        that.$form.find('.js-paragraph-visibilty').click(function () {
            const btn = $(this);
            const input = btn.next('input');
            const value = parseInt(input.val()) ? 0 : 1;
            input.val(value);
            btn.html(value ? "&minus;" : "&plus;").closest('h3').next('.field-group').slideToggle();
        });


        /* Выбор способа вывода стилей */
        that.$form.find(".js-styles-output input").change(function () {
            if ($(this).val() == 'helper') {
                $('.js-styles-helper').show();
            } else {
                $('.js-styles-helper').hide();
            }
        });

        $(document).on('click', '.js-revert-settings-template', function () {
            wa_editor.setValue($("#original-template").html());
        });
    };

    /* Сохранение настроек формы */
    FlexdiscountPluginSettings.prototype.save = function (form) {
        const that = this;

        if (!that.core.hasLoading($("#fixed-save-panel"))) {
            that.core.appendLoading($("#fixed-save-panel .block"));
            $.post("?plugin=flexdiscount&module=settings&action=save", form.serializeArray(), function (response) {
                const btn = form.find("input[type='submit']");
                if (response.status == 'ok') {
                    btn.after("<i class='icon16 yes'></i>");
                } else {
                    btn.after("<i class='icon16 cross'></i>");
                }
                that.core.removeLoading($("#fixed-save-panel"));
                setTimeout(function () {
                    btn.siblings("i").remove();
                }, 3000);
            });
        }
    };

    /* Всплывающее окно системных настроек */
    FlexdiscountPluginSettings.prototype.showSystemSettings = function () {
        const selector = 'settings-flexdiscount-dialog';
        const dialogParams = {
            loading_header: $__("Wait, please..."),
            'min-height': '270px',
            class: 'nopadded',
            url: '?plugin=flexdiscount&module=dialog&action=systemSettings',
            disableButtonsOnSubmit: true,
            buttons: "<input type='submit' class='button green' value='" + $__("Save") + "'> " + $__("or") + " <a class='cancel' href='#'>" + $__('close') + "</a>",
            onClose: function () {
                $("#" + selector).remove();
            },
            onSubmit: function (d) {
                const $submitBtn = d.find("input[type=submit]");
                const $icon = $("<i class='loading icon16'></i>");
                $submitBtn.next('i').remove();
                $submitBtn.after($icon);
                $.post("?plugin=flexdiscount&action=handler&data=saveSystemSettings", d.find("form").serializeArray(), function (response) {
                    $submitBtn.removeAttr('disabled');
                    $icon.removeClass('loading').addClass((response.status == 'ok' && response.data) ? 'yes' : 'no');
                    setTimeout(function () {
                        $icon.remove();
                    }, 3000);
                }, "json");
                return false;
            }
        };
        $("body").append("<div id='" + selector + "'></div>");
        $("#" + selector).waDialog(dialogParams);
    };

    /* Всплывающее окно с блоками для редактирования */
    FlexdiscountPluginSettings.prototype.showCustomizeBlock = function (btn) {
        const that = this;

        const type = btn.data("type");
        const dialogParams = {
            loading_header: $__("Wait, please..."),
            class: 'condition-dialog',
            width: '80%',
            height: '80%',
            url: '?plugin=flexdiscount&module=dialog&action=settingsCustomizeBlock&type=' + type,
            buttons: '<div class="align-center dialog-button-block"><input id="s-dialog-save-button" type="submit" value="' + $__("Save") + '" class="button green"></div>',
            onClose: function () {
                $("#customize-dialog").remove();
            },
            onSubmit: function (form) {
                var buttonBlock = form.find(".dialog-button-block");
                if (!that.core.hasLoading(buttonBlock)) {

                    waEditorUpdateSource({ 'id': 'template-textarea' });

                    form.find('.errormsg').html('');
                    that.core.appendLoading(buttonBlock);
                    $.post("?plugin=flexdiscount&action=handler", {
                        data: 'editSettings',
                        param: btn.data("param"),
                        value: wa_editor.getValue()
                    }, function (response) {
                        if (response.status == 'ok') {
                            buttonBlock.find("i").removeClass("loading").addClass("yes");
                            buttonBlock.find("input").removeClass("yellow red").addClass("green");
                        } else {
                            buttonBlock.find("i").removeClass("loading").addClass("no");
                            buttonBlock.find("input").removeClass("yellow green").addClass("red");
                            form.find('.errormsg').html($__('Something wrong. Check log files.'));
                        }
                        setTimeout(function () {
                            buttonBlock.find("i").remove();
                        }, 3000);
                    });
                }
                return false;
            }
        };
        $("body").append("<div id='customize-dialog'></div>");
        $("#customize-dialog").waDialog(dialogParams);
        return false;
    }

    return FlexdiscountPluginSettings;

})(jQuery);