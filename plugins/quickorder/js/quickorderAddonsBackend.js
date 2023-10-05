var QuickorderPluginAddons = (function ($) {

    QuickorderPluginAddons = function (options) {
        var that = this;

        /* DOM */
        that.$wrap = options.wrap;

        /* INIT */
        that.bindEvents();
    };

    QuickorderPluginAddons.prototype.bindEvents = function () {
        var that = this;

        /* Переключение блока "Информация свернута" */
        that.$wrap.find('.f-collapse-fl').change(function () {
            var checkbox = this;
            if (!checkbox.checked) {
                $(checkbox).next().hide();
            } else {
                $(checkbox).next().css('display', 'inline-block');
            }
        });
    };

    return QuickorderPluginAddons;

})(jQuery);