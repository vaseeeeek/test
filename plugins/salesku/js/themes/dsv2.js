saleskuPluginProduct.prototype.setComparePrice = function (compare_price) {
    if (compare_price) {
        $compare_price = this.getElements().ComparePrice();
        if (!$compare_price.length) {
            $compare_price = $(this.getElements().Selectors().compare_price_html);
            this.getElements().Price().after($compare_price);
        }
        $compare_price.html('<span class="compare-inner">'+this.currencyFormat(compare_price,true)+'</span>').show();
    } else {
        this.getElements().ComparePrice().remove();
    }
};

saleskuPluginProduct.prototype.before_binds.set_fly_view_type = function(self) {
    var $product =  self.getElements().root_element;
    var list = $product.closest('.salesku_plugin_product_list');
    $('.product-view').find('button').click(function() {
        if($(this).data('view') === 'list') {
            $product.find('.salesku_options').removeClass('fly');
        } else {
            $product.find('.salesku_options').addClass('fly');
        }
    });
    if(list.hasClass('list')) {
        $product.find('.salesku_options').removeClass('fly');
    } else {
        $product.find('.salesku_options').addClass('fly');
    }
};