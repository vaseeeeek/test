$.saleskuPluginProductElements._Selectors.compare_price = '.product-compare-price';
$.saleskuPluginProductElements._Selectors.compare_price_html = '<span class="product-compare-price"></span>';
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