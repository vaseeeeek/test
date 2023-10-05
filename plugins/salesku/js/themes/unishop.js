saleskuPluginProduct.prototype.getFormIndicator = function () {
    return this.getElements().CartButton().attr('data-href');
};

saleskuPluginProduct.prototype.removeFormIndicator = function () {
    var button = this.getElements().CartButton();
    button.removeAttr('data-href');
    button.removeClass('js-product-card-dialog');
    button .addClass('js-submit-form');

};
saleskuPluginProduct.prototype.setFormIndicator = function () {
    var button = this.getElements().CartButton();
    button .addClass('js-product-card-dialog');
    button .removeClass('js-submit-form');
    button.attr('data-href', this.form_action_data);
};

saleskuPluginProduct.prototype.before_binds.removeInputPicker = function(self) {
    var sel = self.getElements().Selectors();
    setTimeout(function(){  self.getForm().find(sel.skus_button).styler('destroy'); },'200');

};
/*
 При дублировании старой цены
 $.saleskuPluginProductElements._Selectors.compare_price ='.old-price';
 $.saleskuPluginProductElements._Selectors.compare_price_html= '<span class="old-price nowrap"></span>';
* */
$.saleskuPluginProductElements._Selectors.compare_price ='.old-price';
$.saleskuPluginProductElements._Selectors.compare_price_html= '<span class="old-price nowrap"></span>';