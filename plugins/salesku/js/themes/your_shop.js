// Отключение до загрузки
$.saleskuPluginProductsPool.setOff('#product-list .product-list.table-small');// Отключаем для узкого списка до загрузки функционала плагина
// При нажатии на изменение вида
$.saleskuPluginProductsPool.bindOff('.sort-view .fa-list', '#product-list .product-list'); // Отключаем для узкого списка
$.saleskuPluginProductsPool.bindOn('.sort-view .fa-th-large', '#product-list .product-list');  // Включаем для табличного вида
$.saleskuPluginProductsPool.bindOn('.sort-view .fa-th', '#product-list .product-list');  // Включаем для мини табличного вида
$.saleskuPluginProductsPool.bindOn('.sort-view .fa-th-list', '#product-list .product-list');  // Включаем для широкого списка

$.saleskuPluginProductElements._Selectors.compare_price_html =  '<span class="compare-price"></span>';
$.saleskuPluginProductElements.ComparePrice = function(root_element) {
    return this.Form(root_element).find('.compare-price');
};
$.saleskuPluginProductElements._Selectors.image = '.product-image';