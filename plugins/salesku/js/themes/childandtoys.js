$.saleskuPluginProductElements._Selectors.compare_price = 's';
$.saleskuPluginProductElements._Selectors.compare_price_html = '<s></s>';
$.saleskuPluginProductElements.set('ComparePrice',  function(root_element) {
    return this.Price(root_element).find(this._Selectors.compare_price);
});
saleskuPluginProduct.prototype.setComparePrice = function (compare_price) {
    if (compare_price) {
        $compare_price = this.getElements().ComparePrice();
        if (!$compare_price.length) {
            $compare_price = $(this.getElements().Selectors().compare_price_html);
            this.getElements().Price().append($compare_price);
        }
        $compare_price.html(this.currencyFormat(compare_price)).show();
    } else {
        this.getElements().ComparePrice().remove();
    }
};

saleskuPluginProduct.prototype.removeFormIndicator = function () {
    this.getForm().removeAttr('data-preview');
    return this.getForm().removeAttr(this.getElements().Selectors().form_action_indicator);
};
saleskuPluginProduct.prototype.setFormIndicator = function () {
    this.getForm().attr('data-preview',this.form_action_data);
    return this.getForm().attr(this.getElements().Selectors().form_action_indicator , this.form_action_data);
};
/* PREVIEW ON */
saleskuPluginProduct.prototype.binds.preview  = function(self){
    var act = self.form_action_data;
    self.getForm().find(".image .preview a").click(function () {
        var f = $(this).closest("form");
        var d = $('#preview');
        var c = d.find('.cartpreview').empty();
        c.load(act, function () {
            d.show();
            if ((c.height() > c.find('form').height())) {
                c.css('bottom', 'auto');
            } else {
                c.css('bottom', 'auto');
            }
        });
        return false;
    });
};