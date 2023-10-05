// Отключение до загрузки
$.saleskuPluginProductsPool.setOff('#product-list .product-list.rows'); // Отключаем для списка строчного вида до загрузки функционала плагина
// При нажатии на изменение вида
$.saleskuPluginProductsPool.bindOff('.viewswitch .rowswitch', '#product-list .product-list'); // Отключаем для списка строчного вида
$.saleskuPluginProductsPool.bindOn('.viewswitch  .compactswitch', '#product-list .product-list');  // Включаем для табличного вида
$.saleskuPluginProductElements.Image = function(root_element) {
    if(root_element.find('.image').find('img.secimg').length==1) {
        return root_element.find('.image').find('img.secimg');
    } else {
        return root_element.find('.image').find('img.b-lazy');
    }
};
$.saleskuPluginProductElements.OriginalImage = function(root_element)  {
    if(root_element.find('.image').find('img.secimg').length==1) {
        return root_element.find('.image').find('img.secimg').data('src');
    } else {
        return root_element.find('.image').find('img.b-lazy').data('src');
    }
};
saleskuPluginProduct.prototype.before_binds.removeInputPicker = function(self) {
    var sel = self.getElements().Selectors();
    self.getForm().find(sel.skus_button).picker('destroy');
    self.getForm().find(sel.skus_button).each(function() {
        $(this).closest('label').addClass('picker-label');
    });

};

