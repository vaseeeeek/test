(function($) {
    $.products.brandsAction = function () {
        this.load('?plugin=productbrands', function () {
            $("#s-sidebar li.selected").removeClass('selected');
            $("#s-productbrands").addClass('selected');
        });
    }
    $.products.brandAction = function (id) {
        this.load('?plugin=productbrands&action=edit&id=' + id, function () {
            if (!$("#s-productbrands").hasClass('selected')) {
                $("#s-sidebar li.selected").removeClass('selected');
                $("#s-productbrands").addClass('selected');
            }
        });
    }
})(jQuery);