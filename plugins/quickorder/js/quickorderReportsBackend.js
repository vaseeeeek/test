var QuickorderPluginReports = (function ($) {

    QuickorderPluginReports = function (options) {
        var that = this;

        /* DOM */
        that.$wrap = $("#quickorder-reports-content");

        /* VARS */
        that.title = options.title;
        that.requestParams = options.requestParams;

        /* INIT */
        that.initClass();
        that.bindEvents();
    };

    QuickorderPluginReports.prototype.initClass = function () {
        var that = this;

        /* Устанавливаем заголовок страницы */
        document.title = that.title;

        var action_url = '?plugin=quickorder&module=reports';
        var $storefront_selector = $('#s-products-report-storefront-selector').removeAttr('id');

        // Move selector into the top menu wrapper
        $('#s-reports-custom-controls').empty().append($storefront_selector.closest('ul'));

        // Reload page when user changes something in the selector
        $storefront_selector.change(function () {
            $storefront_selector.after('<i class="icon16 loading"></i>');
            var data = {};
            data[$storefront_selector.attr('name')] = $storefront_selector.val();
            $.post(action_url, $.extend({}, that.requestParams, data), function (r) {
                $('#reportscontent').html(r);
            });
        });

        // Human-readable period description in page header
        var $timeframe_selected_li = $('#mainmenu .s-reports-timeframe .selected');
        if ($timeframe_selected_li.length && $timeframe_selected_li.data('timeframe') != 'custom') {
            $('#period-description').html($timeframe_selected_li.find('a').html());
        }
    };

    QuickorderPluginReports.prototype.bindEvents = function () {
        var that = this;

        /* Изменение источника */
        that.$wrap.find('.js-q-module').click(function () {
            that.$wrap.find('.js-q-module').parent().removeClass('selected');
            $(this).parent().addClass('selected');
            $.wa.setHash(that.getHash());
            return false;
        });

        /* Изменение страницы отображения */
        that.$wrap.find('.js-q-page').click(function () {
            that.$wrap.find('.js-q-page').parent().removeClass('selected');
            $(this).parent().addClass('selected');
            $.wa.setHash(that.getHash());
            return false;
        });

        /* Изменение порядка сортировки */
        that.$wrap.find('.js-q-change-order').click(function () {
            that.$wrap.find('.js-order-selected').removeClass('js-order-selected');
            $(this).addClass('js-order-selected');
            var hash = that.getHash();
            $.wa.setHash(hash);
            return false;
        });

        /* Изменение кол-ва товаров */
        that.$wrap.find('#limit-selector').change(function () {
            var hash = that.getHash();
            $.wa.setHash(hash);
            return false;
        });
    };

    QuickorderPluginReports.prototype.getHash = function () {
        var that = this;

        var hash = '#/quickorder/';
        var join = 0;
        that.$wrap.find('.js-menu .selected').each(function (i) {
            if (i === 0) {
                hash = $(this).find('a').attr('href');
            } else {
                hash += 'page=' + $(this).find('a').data('page');
                join = 1;
            }
        });
        if (that.$wrap.find('.js-order-selected').length) {
            hash += (join ? '&' : '') + 'sort=' + that.$wrap.find('.js-order-selected').data('sort');
        }
        hash += '&limit=' + that.$wrap.find('#limit-selector').val() + '/';
        return hash;
    };

    return QuickorderPluginReports;

})(jQuery);