<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopAutobadgePluginFrontendUpdateBadgeController extends shopAutobadgePluginJsonController
{

    public function execute()
    {
        $data = array('products' => array());
        $products = waRequest::post('products');
        // Список товаров, для которых необходимо обновить наклейки
        if ($products) {
            // Фильтры
            $filters = shopAutobadgeHelper::getFilters();
            // Выполняем предварительную обработку товаров
            $products = shopAutobadgeHelper::fixPrices($products);
            // Добавляем товары в общий список
            $data_class = new shopAutobadgeData();
            $data_class->setShopProducts($products);
            // Получаем дефолтные настройки наклеек
            $generator = new shopAutobadgeGenerator();
            $generator->getDefaultRibbonSettings();

            foreach ($products as $key => $product) {
                $data['products'][$key] = array('default' => '', 'autobadge' => '', 'css' => array('google_fonts' => '', 'inline_css' => ''));

                $product = shopAutobadgeHelper::getBadgesData($product, isset($products[$key]) ? $products[$key] : [], $filters);

                $data['products'][$key]['default'] = $product['badge'];
                $data['products'][$key]['autobadge'] = $product['autobadge'];
                $data['products'][$key]['type'] = $product['autobadge-type'];
                $data['products'][$key]['page'] = $product['autobadge-page'];
                $data['products'][$key]['product_id'] = $product['product_id'];

                // Добавляем стили на страницу
                $css = shopAutobadgeGenerator::getCss();
                $data['products'][$key]['css']['google_fonts'] = $css['google_fonts'] ? $css['google_fonts'] : '';
                $data['products'][$key]['css']['inline_css'] = shopAutobadgeGenerator::getCssArray();
                // Если имеются наклейки №5, получаем их настройки, чтобы на витрине иметь возможность изменять размер в зависимости от контейнера
                $data['js_settings'] = shopAutobadgeCore::getJsSettings();
            }
        }

        $this->response = $data;
    }

}
