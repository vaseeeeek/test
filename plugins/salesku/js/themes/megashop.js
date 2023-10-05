// Отключение до загрузки
$.saleskuPluginProductsPool.setOff('#product-list .product-list.list-big'); // Отключаем для узкого списка до загрузки функционала плагина
$.saleskuPluginProductsPool.setOff('#product-list .product-list.list-small');// Отключаем для широкого списка до загрузки функционала плагина
// При нажатии на изменение вида
$.saleskuPluginProductsPool.bindOff('.product-view-btns .list-big', '#product-list .product-list'); // Отключаем для узкого списка
$.saleskuPluginProductsPool.bindOff('.product-view-btns .list-small', '#product-list .product-list'); // Отключаем для широкого списка
$.saleskuPluginProductsPool.bindOn('.product-view-btns .thumbs', '#product-list .product-list');  // Включаем для табличного вида

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
