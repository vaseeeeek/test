$.saleskuPluginProductElements._Selectors.price = '.salesku_plugin-price';
$.saleskuPluginProductElements._Selectors.image = '.l-image-box';

saleskuPluginProduct.prototype.after_binds = {
    'megashop_form' : function(self) {
        self.getElements().root_element.removeClass('c-product_has-multi-skus');
        self.getElements().root_element.find('.c-product_has-multi-skus').removeClass('c-product_has-multi-skus');
    }
};


saleskuPluginProduct.prototype.setComparePrice = function (compare_price) {
    if (compare_price) {
        $compare_price = this.getElements().ComparePrice();
        if (!$compare_price.length) {
            $compare_price = $(this.getElements().Selectors().compare_price_html);
            this.getElements().Price().after($compare_price);
        }
        $compare_price.html(this.currencyFormat(compare_price)).show();
    } else {
        this.getElements().ComparePrice().remove();
    }
};