$.saleskuPluginProductElements._Selectors.price = '.cat-item__price-amount,.prc__i_reg.price';
$.saleskuPluginProductElements._Selectors.compare_price_html = '<div class="prc__i prc__i_old"></div>';
$.saleskuPluginProductElements._Selectors.compare_price = '.prc__i.prc__i_old';

$.saleskuPluginProductElements.Price = function(root_element) {
    return  root_element.find(this._Selectors.price);
};
$.saleskuPluginProductElements._Selectors.image = '.item__image';
saleskuPluginProduct.prototype.setComparePrice = function (compare_price) {
    var self = this;
    if (compare_price) {
        this.getElements().ComparePrice().remove();
        $compare_price = this.getElements().ComparePrice();

        self.getElements().Price().each(function(){
            $(this).after($(self.getElements().Selectors().compare_price_html));
        });
        $compare_price = this.getElements().ComparePrice();
        $compare_price.html(this.currencyFormat(compare_price,true)).show();
    } else {
        this.getElements().ComparePrice().remove();
    }
};