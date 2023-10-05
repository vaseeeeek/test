// Отключение до загрузки
$.saleskuPluginProductsPool.setOff('.sellerslist .owl-stage');// Отключаем плагин с слайдере продуктов на главной, криво работало
$.saleskuPluginProductsPool.setOff('#product-list .product-list.short-list');// В категории в виде узкого списка отключаем
// При нажатии на изменение вида
$.saleskuPluginProductsPool.bindOff('#select-view li.short-list', '#product-list .product-list'); // Отключаем для узкого списка
$.saleskuPluginProductsPool.bindOn('#select-view li.list', '#product-list .product-list'); // Включаем для широкого списка
$.saleskuPluginProductsPool.bindOn('#select-view li.thumbs', '#product-list .product-list');  // Включаем для табличного вида

