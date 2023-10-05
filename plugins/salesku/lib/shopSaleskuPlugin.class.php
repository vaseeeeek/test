<?php
class shopSaleskuPlugin extends shopPlugin
{
    const APP = 'shop';
    const PLUGIN_ID = 'salesku';
    const CONFIG_FILE = 'config.php';
    const GENERAL_STOREFRONT = 'general';
    
    protected static $config = null;

    protected static $action = '';
    
    public static function getAppConfig()
    {
        return  wa('shop')->getConfig();
    }
    public static function getPluginSettings($storefront = null) {
       if(self::$config==null) {
           self::$config = new shopSaleskuPluginSettings($storefront);
       }
        return self::$config;
    }
    protected static function setAction($action) {
        self::$action = $action;
    }
    public function frontendHead() {
        if(self::isAction()) {
            return shopSaleskuPluginView::getHead();
        }
        return '';
    }
    public static function isAction() {
        /// Проверяем отключение в инсталлере
        $info = wa(self::APP)->getConfig()->getPluginInfo(self::PLUGIN_ID);
        if(!empty($info)) {
            $settings = self::getPluginSettings();
            if(waRequest::isMobile() && $settings['status_mobile']=='1') {
                return true;
            } elseif($settings['status']=='1') {
                return true;
            }
        }
        return false;
    }
    /**
     * Возвращает URL плагина от корня домена
     * @param bool $absolute
     * @return string
     */
    public static function getUrlStatic($absolute = false) {
        return wa()->getAppStaticUrl(self::APP, $absolute).'plugins/'.self::PLUGIN_ID.'/';
    }
    public function frontendProducts($data) {
        if(array_key_exists('products',$data) && !empty($data['products']) && !isset($data['plugin'])) {
           shopSaleskuPluginProductsPool::getPool()->addProducts($data['products']);

        }
    }
}
