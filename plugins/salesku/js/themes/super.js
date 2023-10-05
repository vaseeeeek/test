/* Ищем элемент картинки */
$.saleskuPluginProductElements.Image = function(root_element) {
    if(root_element.find('.image').find('img')) {
        return root_element.find('.image').find('img');
    };
};
/* Перед сменой ищем оригинал и пишем в спец переменную  */
$.saleskuPluginProductElements.OriginalImage = function(root_element)  {
    if(root_element.find('.image').find('img:first').length==1) {
        return root_element.find('.image').find('img:first').attr('data-src');
    } else {
        return '';
    }
};
/* Этот метод как раз все выставляет на основе тех   */
saleskuPluginProduct.prototype.setSkuImage = function (sku_data) {
    if(this.sku_image == '1') {
        var image_obj = this.getElements().Image(); /* $.saleskuPluginProductElements.Image */
        // Сохраняем оригинал
        if(!this.getElements().root_element.data('salesku-original-image')) {
            this.getElements().root_element.data('salesku-original-image', this.getElements().OriginalImage()); /* $.saleskuPluginProductElements.OriginalImage */
        }
        if(typeof(sku_data) == 'object' && sku_data.hasOwnProperty('image')) {
            image_obj.attr('src', sku_data.image);
        } else {
            image_obj.attr('src', this.getElements().root_element.data('salesku-original-image'));
        }
        retinajs();
    }
};