var QuickorderPluginTemplates = (function ($) {

    QuickorderPluginTemplates = function (options) {
        var that = this;

        /* DOM */
        that.$wrap = options.wrap;
        that.storefront = options.storefront;

        /* INIT */
        that.bindEvents();
    };

    QuickorderPluginTemplates.prototype.bindEvents = function () {
        var that = this;

        /* Восстановление шаблона по умолчанию */
        that.$wrap.find('.js-restore-template').click(function () {
            that.restoreTemplate($(this));
        });

        /* Редактирование шаблона */
        that.$wrap.find('.js-edit-templates').click(function () {
            new igaponovDialog({
                url: '?plugin=quickorder&module=dialog&action=templates&id=' + $(this).data('id') + '&storefront=' + that.storefront,
                saveUrl: '?plugin=quickorder&action=saveTemplate',
                onBlockClick: function (event) {
                    event.stopPropagation();
                    that.initDialogEvents(event);
                }
            });
        });

    };

    /* Восстановление шаблона по умолчанию */
    QuickorderPluginTemplates.prototype.restoreTemplate = function ($elem) {
        var that = this;

        if (!$elem.next('i').length) {
            $elem.after('<i class="icon16 loading"></i>');
            $.post('?plugin=quickorder&action=restoreTemplate', { id: $elem.data('id'), storefront: that.storefront, delete: $elem.data('delete') }, function (response) {
                if (response.status == 'ok') {
                    $elem.next('i').removeClass('loading').addClass('yes');
                    if ($elem.closest('.w-dialog-content').length) {
                        $elem.closest('.w-dialog-content').find('.attention-block').remove();
                        if (response.data.length) {
                            $('#' + $elem.closest('form').data('id')).data('editor').setValue(response.data);
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
    QuickorderPluginTemplates.prototype.initDialogEvents = function (event) {
        var that = this;

        var elem = $(event.target);

        /* Восстановление шаблона по умолчанию */
        if (elem.is('.js-restore-template')) {
            that.restoreTemplate(elem);
        }
    };

    return QuickorderPluginTemplates;

})(jQuery);