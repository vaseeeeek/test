$.saleskuPluginProductElements.Image = function(root_element) {
    return root_element.find('.imgfix').find('img');
};
$.saleskuPluginProductElements._Selectors.compare_price = 's';
$.saleskuPluginProductElements._Selectors.compare_price_html = '<s></s><br>';
$.saleskuPluginProductElements.set('ComparePrice',  function(root_element) {
    return this.Price(root_element).find(this._Selectors.compare_price);
});
saleskuPluginProduct.prototype.setComparePrice = function (compare_price) {
    if (compare_price) {
        $compare_price = this.getElements().ComparePrice();
        if (!$compare_price.length) {
            $compare_price = $(this.getElements().Selectors().compare_price_html);
            this.getElements().Price().prepend($compare_price);
        }
        $compare_price.html(this.currencyFormat(compare_price)).show();
    } else {
        this.getElements().Price().find('br').remove();
        this.getElements().ComparePrice().remove();
    }
};