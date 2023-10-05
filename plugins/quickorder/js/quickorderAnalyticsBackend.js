var QuickorderPluginAnalytics = (function ($) {

    QuickorderPluginAnalytics = function (options) {
        var that = this;

        /* DOM */
        that.$wrap = options.wrap;

        /* INIT */
        that.bindEvents();
    };

    QuickorderPluginAnalytics.prototype.bindEvents = function () {
        var that = this;

        /* Скрытие/отображение кнопки в разделе "Отображение" */
        that.$wrap.find('.f-analytics-yaecom').change(function () {
            var checkbox = this;
            if (!checkbox.checked) {
                $(checkbox).closest('.field-group').find('.f-analytics-yecom-settings').slideUp();
            } else {
                $(checkbox).closest('.field-group').find('.f-analytics-yecom-settings').slideDown();
            }
        });
    };

    return QuickorderPluginAnalytics;

})(jQuery);