$.saleskuPluginProductElements._Selectors.compare_price = '.compare-at-price';
$.saleskuPluginProductElements._Selectors.compare_price_html =  '<s class="compare-at-price nowrap"></s>';
$.saleskuPluginProductsPool.setOff('#product-list .row.lazy-wrapper.margin-top');// Отключаем плагин в узком списке

$.saleskuPluginProductsPool.bindOff('.showtype .fa-align-justify', '#product-list .row.lazy-wrapper'); // Отключаем для узкого списка
$.saleskuPluginProductsPool.bindOn('.showtype .fa-th-list', '#product-list .row.lazy-wrapper');  // Включаем для табличного вида
$.saleskuPluginProductsPool.bindOn('.showtype .fa-th-large', '#product-list .row.lazy-wrapper');  // Включаем для широкого списка


$.saleskuPluginProductElements.Image = function(root_element) {
    return root_element.find('.zoom-image').find('img');
};
$.saleskuPluginProductElements._Selectors.compare_price_html = '<s></s>';
$.saleskuPluginProductElements.Price = function(root_element) {
    var price = false;
    var self = this;
    root_element.find(self._Selectors.price).each(function () {
        if(!$(this).closest(self._Selectors.skus).hasClass(self._Selectors.skus.replace(/\./g, ''))) {
            price = $(this).find('span');
        }
    });
    if(!price) {
        console.log('Salesku: Не найден элемент цены продукта!');
    }
    return price;
};
$.saleskuPluginProductElements.ComparePrice = function(root_element) {
    return this.Price(root_element).parent().find('s');
};
saleskuPluginProduct.prototype.setComparePrice = function (compare_price) {
    if (compare_price) {
        $compare_price = this.getElements().ComparePrice();
        if (!$compare_price.length) {
            $compare_price = $(this.getElements().Selectors().compare_price_html);
            this.getElements().Price().after($compare_price);
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
        this.getElements().Price().html(this.currencyFormat(price, true));
    }
};