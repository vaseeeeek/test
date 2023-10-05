<?php
class shopSaleskuPluginView {

    /**
     * @var null| waTreme
     */
    protected static $theme = null;
    /**
     * @var null|shopSaleskuPluginTemplates
     */
    protected static $templates = null;

    /**
     * Возвращает объект шаблонов фронтенда для плагина
     * @return null|shopSaleskuPluginTemplates
     */
    public static function getTemplates() {
        if(self::$templates==null) {
            self::$templates = new shopSaleskuPluginTemplates(shopSaleskuPlugin::getPluginSettings());
        }
        return self::$templates;
    }

    /**
     * Метод возвращает строкой скрипты и данные инициализации плагина на витрине для товарного предложения
     * @param $_product Массив или объект продукта с подготовленными данными плагина
     * @return string
     */
    protected static function getScript($_product) {
        $product_data = array();
        $product = $_product[self::getKey()];
        /// Данные продукта основные
        $product_data['uid'] = $product->getUid();
        $product_data['currency'] = $product['currency_info'];
        $skus = $product['skus'];
        $sku_type = $product['sku_type'];
        if(count($skus)>1 or $sku_type ) {
            $product_data['services']  = $product['sku_services'];
        } else {
            // Если один артикул, надо передать его цену, иначе ломается подсчет из-за отсутствия в теге атрибута data-price
            $sku = $skus[$product['sku_id']];
            $product_data['sku']  = $sku;
        }
        if($sku_type) {
            $sku_features_selectable = $product['sku_features_selectable'];
            foreach ($sku_features_selectable as $sku_index => $item) {
                if(!isset($skus[$item['id']])) {
                    unset($sku_features_selectable[$sku_index]);
                }
            }
            $product_data['features']  = $sku_features_selectable;
        } elseif (count($skus) > 1) {
            $product_data['skus'] = $skus;
        }
        $product_data['type_id'] =  $product['type_id'];

        // Данные из настроек
        $settings = shopSaleskuPlugin::getPluginSettings();
        $product_data['debug'] = $settings['debug'];
        $product_data['sku_image'] = $settings['sku_image'];
        $product_data['smartsku'] = $settings->getSmartSkuSettings()->getSettings();
        $related_sku_settings =  $settings->getRelatedSkuSettings();
        $product_data['related_sku'] =  $related_sku_settings[$product['type_id']];
        // Костыль для фиьтров некоторых тем дизайна, скрипты раньше печати выполнялись
        if(waRequest::isXMLHttpRequest()) {
            return  '<span id="salesku-id-'.$product->getUid().'" class="salesku-id"></span><script type="text/javascript">(function($) {$(document).ready(function(){if(typeof $.saleskuPluginProductsPool == "object") {setTimeout(function(){$.saleskuPluginProductsPool.addProduct('.$product_data['uid'].','.json_encode($product_data).')}, 100);}})})(jQuery);</script>';
        } else {
            return  '<span id="salesku-id-'.$product->getUid().'" class="salesku-id"></span><script type="text/javascript">(function($) {$(document).ready(function(){if(typeof $.saleskuPluginProductsPool == "object") {$.saleskuPluginProductsPool.addProduct('.$product_data['uid'].','.json_encode($product_data).');}})})(jQuery);</script>';
        }

    }

    /**
     * Метод выводит html код показа опций и характеристик продукта
     * @param $product принимает объект или массив продукта
     * @return mixed|string|void
     */
    public static function displayOptions(&$product) {
        self::getProductData($product);
        if(!shopSaleskuPlugin::isAction() || !$product['salesku']->isAction()) {
            return '';
        }
        $html = self::fetch($product, self::getTemplates()->getTemplate('options'));
        $html .= self::getScript($product);
        return $html;
    }

    /**
     * Метод выводит html код показа Складов продукта
     * @param $product  - объект или массив продукта
     * @return mixed|string|void
     */
    public static function displayStocks(&$product) {
        $settings = self::getSettings();
        self::getProductData($product);
        if(!shopSaleskuPlugin::isAction() || !empty($settings['hide_stocks']) || !$product['salesku']->isAction('stocks')) {
            return '';
        }
        return self::fetch($product, self::getTemplates()->getTemplate('stocks'));
    }

    /**
     * Метод выводит html код показа Сервисов продукта
     * @param $product  - объект или массив продукта
     * @return mixed|string|void
     */
    public static function displayServices(&$product) {
        $settings = self::getSettings();
        self::getProductData($product);
        if(!shopSaleskuPlugin::isAction() || !empty($settings['hide_services']) || !$product['salesku']->isAction('services')) {
            return '';
        }
        return self::fetch($product, self::getTemplates()->getTemplate('services'));
    }

    /**
     * Метод дополняет массив или объект продукта данными плагина
     * @param $product -объект или массив продукта
     */
    public static function getProductData(&$product) {
        if(!isset($product[self::getKey()])) {
            $product[self::getKey()] = new shopSaleskuPluginProduct($product);
        }
    }

    /**
     * Возвращает глобальный объект настроек плагина
     * @return null|shopSaleskuPluginSettings
     */
    protected static function getSettings() {
        return shopSaleskuPlugin::getPluginSettings();
    }

    /**
     *  Возвращает ключ массива данных плагина в продукте
     * @return string
     */
    protected static function getKey(){
        return shopSaleskuPlugin::PLUGIN_ID;
    }

    /**
     * Возвращает новый объект представления
     * @return waSmarty3View
     */
    protected static function getView() {
        return new waSmarty3View(wa());
    }

    /**
     * Компилирует шаблон представления и возвращает результат в виде html кода
     * @param $product - Объект или массив продукта
     * @param $template - Идентификатор шаблона в массиве шаблонов плагина
     * @return mixed|string|void
     */
    protected static function fetch($product, $template) {
        $view = self::getView();
        $view->assign('product', $product['salesku']);
        return $view->fetch($template);
    }

    /**
     *  Возвращает HTML код основной инициализации плагина на странице в тег HEAD
     * @return string
     */
    public static function getHead() {
        $settings = shopSaleskuPlugin::getPluginSettings();
        $pool_settings = array();
        $pool_settings['debug'] = $settings['debug'];
        $pool_settings['smart_sku_class_grey'] = 'salesku_plugin-feature-grey';
        $pool_settings['smart_sku_class_hide'] = 'salesku_plugin-feature-hide';
        // Настройки классов скрытия артикулов
        if(isset($settings['smart_sku_hide_style']) && !empty($settings['smart_sku_hide_style'])) {
            $pool_settings['smart_sku_class_grey'] = $settings['smart_sku_class_grey'];
            $pool_settings['smart_sku_class_hide'] = $settings['smart_sku_class_hide'];
        }

        $pool_settings['related_sku'] = $settings['related_sku'];
        $html = '';
        $html .= '<link href="'.shopSaleskuPlugin::getUrlStatic().'css/saleskuFrontend.css" rel="stylesheet" type="text/css">';
        if(isset($settings['style_default']) && $settings['style_default'] == 1) {
            $html .= '<link href="'.shopSaleskuPlugin::getUrlStatic().'css/saleskuDefaultFrontend.css" rel="stylesheet" type="text/css">';
        }
        if($settings['debug']=='1') {
            $html .='<script type="text/javascript" src="'.shopSaleskuPlugin::getUrlStatic().'js/saleskuPluginProductsPool.js"></script>'.
                '<script type="text/javascript" src="'.shopSaleskuPlugin::getUrlStatic().'js/saleskuPluginProduct.js"></script>';
        } else {
            $html .='<script type="text/javascript" src="'.shopSaleskuPlugin::getUrlStatic().'js/saleskuPluginProductsPool.min.js"></script>'.
                '<script type="text/javascript" src="'.shopSaleskuPlugin::getUrlStatic().'js/saleskuPluginProduct.min.js"></script>';
        }
        $html .= '<script>$.saleskuPluginProductsPool.setSettings('.json_encode($pool_settings).')</script>';
        $templates = self::getTemplates();
        $html .= $templates ->getThemeCorrection();
        if($settings['template_type'] != 'plugin') {
            $templates = self::getTemplates();
            $html .= $templates->getTemplate('js');
            $html .= $templates->getTemplate('css');

        }
        return $html;
    }
}