$.saleskuPluginProductsPool.setOff('#product-list .product-list.products_view_compact');// Отключаем плагин в узком списке

$.saleskuPluginProductsPool.bindOff('.catalog_toolbar .view_compact', '#product-list .product-list'); // Отключаем для узкого списка
$.saleskuPluginProductsPool.bindOn('.catalog_toolbar .view_grid', '#product-list .product-list');  // Включаем для табличного вида
$.saleskuPluginProductsPool.bindOn('.catalog_toolbar .view_list', '#product-list .product-list');  // Включаем для широкого списка

$.saleskuPluginProductElements.Image = function(root_element) {
    return root_element.find('.img_middle_in').find('img');
};