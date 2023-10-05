$.saleskuPluginProductElements._Selectors.price = '.products__price';
$.saleskuPluginProductElements._Selectors.compare_price = '.products__price-old';
$.saleskuPluginProductElements._Selectors.compare_price_html =  '<span class="products__price-old"></span>';

saleskuPluginProduct.prototype.setComparePrice = function (compare_price) {
    if (compare_price) {
        $compare_price = this.getElements().ComparePrice();
        if (!$compare_price.length) {
            $compare_price = $(this.getElements().Selectors().compare_price_html);
            this.getElements().Price().append($compare_price);
        }
        $compare_price.html(this.currencyFormat(compare_price,true)).show();
    } else {
        this.getElements().ComparePrice().remove();
    }
};


saleskuPluginProduct.prototype.setPrice = function (price, data_price) {
    if(this.getElements().Price().length>0) {
        if(data_price) {
            this.getElements().Price().data('price', String(data_price));
        }
        var quantity =   this.getElements().Quantity();
        if(quantity.length>0) {
            var q = parseFloat(quantity.val()) > 0? parseFloat(quantity.val()) : 1;
            price = q*price;
        }
        this.getElements().Price().html('<span class="products__price-new price">'+this.currencyFormat(price)+'</span>');
    }
};