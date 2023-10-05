<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopQuickorderPlugin extends shopPlugin
{

    public function frontendHead()
    {
        //Настройки
        $settings = shopQuickorderPluginHelper::getSettings();

        if (empty($settings['status'])) {
            return '';
        }

        $head = "";

        // CSS стили
        $generator = new shopQuickorderPluginGenerator();
        $generator->prepareStyles();
        $css = shopQuickorderPluginGenerator::getCss();
        $head .= $css['google_fonts'] ? $css['google_fonts'] : '';

        if ($css['inline_css'] || !empty($settings['css'])) {
            $head .= '<style id="quickorder-inline-styles" data-inline-css="' . ($css['inline_css'] ? 1 : 0) . '">';
            if ($css['inline_css']) {
                $head .= $css['inline_css'];
            }
            if (!empty($settings['css'])) {
                $head .= $settings['css'];
            }
            $head .= '</style>';
        }

        // Приводим настройки минимальной суммы к нужному формату
        if (!empty($settings['minimal']['price'])) {
            $settings['minimal']['price'] = shop_currency($settings['minimal']['price'], null, null, false);
        }
        if (!empty($settings['minimal']['product_sum'])) {
            $settings['minimal']['product_sum'] = shop_currency($settings['minimal']['product_sum'], null, null, false);
        }

        $is_debug = waSystemConfig::isDebug();
        // Подключение скриптов всплывающего окна
        if (empty($settings['product']['hide_button']) || empty($settings['cart']['hide_button'])) {
            $this->addCss('js/dialog/jquery.dialog.' . (!$is_debug ? 'min.' : '') . 'css');
            $this->addJs('js/dialog/jquery.dialog.' . (!$is_debug ? 'min.' : '') . 'js');
        }

        $helper = self::getClass('helper');
        $this->addJs('js/frontend.' . (!$is_debug ? 'min.' : '') . 'js');
        $this->addCss('css/frontend.' . (!$is_debug ? 'min.' : '') . 'css');

        $plugin_id = $this->getId();

        $head .= "<script>" .
            "jQuery(document).ready(function($) {" .
            "$.quickorder.init({" .
            "version:'" . $this->getVersion() . "'," .
            "isDebug:'" . (int) $is_debug . "'," .
            "isMobile:'" . (waRequest::isMobile() ? 1 : 0) . "'," .
            "messages:" . $helper->getLocaleMessages() . "," .
            "currency:" . json_encode($helper->getCurrencyInfo()) . "," .
            "usingPlugins:" . $helper->usingPlugins() . "," .
            "contactUpdate:" . (!empty($settings['contact_update']) ? 1 : 0) . "," .
            "popupClose:" . (!empty($settings['popup_close']) ? 1 : 0) . "," .
            "replace78:" . (!empty($settings['replace_78']) ? 1 : 0) . "," .
            "mobileStabDelay:'" . ifempty($settings, 'mobile_device_stab_delay', 500) . "'," .
            "minimal:" . (!empty($settings['minimal']) ? json_encode($settings['minimal']) : '') . "," .
            "productButton:'[data-quickorder-product-button]'," .
            "cartButton:'[data-quickorder-cart-button]'," .
            "analytics:" . json_encode($helper->getAnalytics($settings)) . "," .
            "urls:{" .
            "getProductSkus:'" . wa()->getRouteUrl('shop/frontend/getProductSkus', array('plugin' => $plugin_id)) . "'," .
            "shipping:'" . wa()->getRouteUrl('shop/frontend/updateShipping', array('plugin' => $plugin_id)) . "'," .
            "update:'" . wa()->getRouteUrl('shop/frontend/update', array('plugin' => $plugin_id)) . "'," .
            "load:'" . wa()->getRouteUrl('shop/frontend/load', array('plugin' => $plugin_id)) . "'," .
            "payment:'" . wa()->getRouteUrl('shop/frontend/payment', array('plugin' => $plugin_id)) . "'," .
            "send:'" . wa()->getRouteUrl('shop/frontend/send', array('plugin' => $plugin_id)) . "'," .
            "service:'" . wa()->getRouteUrl('shop/frontend/updateService', array('plugin' => $plugin_id)) . "'," .
            "cartSaveUrl:{" .
            "shop:'" . $helper->cleanUrl(wa()->getRouteUrl('shop/frontendCart', array('action' => 'save'), true)) . "'," .
            "plugin:'" . $helper->cleanUrl(wa()->getRouteUrl('shop/frontend/cartSave', array(), true)) . "'" .
            "}," .
            "cartDeleteUrl:{" .
            "shop:'" . $helper->cleanUrl(wa()->getRouteUrl('shop/frontendCart', array('action' => 'delete'), true)) . "'," .
            "plugin:'" . $helper->cleanUrl(wa()->getRouteUrl('shop/frontend/cartDelete', array(), true)) . "'" .
            "}," .
            "cartAddUrl:{" .
            "shop:'" . $helper->cleanUrl(wa()->getRouteUrl('shop/frontendCart', array('action' => 'add'), true)) . "'," .
            "plugin:'" . $helper->cleanUrl(wa()->getRouteUrl('shop/frontend/cartAdd', array(), true)) . "'" .
            "}" .
            "}" .
            "});" .
            "});" .
            "</script>";

        return $head;
    }

    /**
     * Used for output scripts in other apps
     *
     * @return string
     * @throws waException
     */
    private static function getHead()
    {
        $instance = wa('shop')->getPlugin('quickorder');
        //Настройки
        $settings = shopQuickorderPluginHelper::getSettings();
        $head = "";

        // CSS стили
        $generator = new shopQuickorderPluginGenerator();
        $generator->prepareStyles();
        $css = shopQuickorderPluginGenerator::getCss();
        $head .= $css['google_fonts'] ? $css['google_fonts'] : '';

        if ($css['inline_css'] || !empty($settings['css'])) {
            $head .= '<style id="quickorder-inline-styles" data-inline-css="' . ($css['inline_css'] ? 1 : 0) . '">';
            if ($css['inline_css']) {
                $head .= $css['inline_css'];
            }
            if ($settings['css']) {
                $head .= $settings['css'];
            }
            $head .= '</style>';
        }

        // Приводим настройки минимальной суммы к нужному формату
        if (!empty($settings['minimal']['price'])) {
            $settings['minimal']['price'] = shop_currency($settings['minimal']['price'], null, null, false);
        }
        if (!empty($settings['minimal']['product_sum'])) {
            $settings['minimal']['product_sum'] = shop_currency($settings['minimal']['product_sum'], null, null, false);
        }

        $is_debug = waSystemConfig::isDebug();
        // Подключение скриптов всплывающего окна
        if (empty($settings['product']['hide_button']) || empty($settings['cart']['hide_button'])) {
            $head .= "<link rel='stylesheet' href='" . wa()->getAppStaticUrl('shop') . "plugins/quickorder/js/dialog/jquery.dialog." . (!$is_debug ? 'min.' : '') . "css" . (!$is_debug ? "?v=" . time() : '') . "'>";
            $head .= "<script src='" . wa()->getAppStaticUrl('shop') . "plugins/quickorder/js/dialog/jquery.dialog." . (!$is_debug ? 'min.' : '') . "js" . (!$is_debug ? "?v=" . time() : '') . "'></script>";
        }

        $helper = self::getClass('helper');

        $head .= "<script src='" . wa()->getAppStaticUrl('shop') . "plugins/quickorder/js/frontend." . (!$is_debug ? 'min.' : '') . "js" . (!$is_debug ? "?v=" . time() : '') . "'></script>";
        $head .= "<link rel='stylesheet' href='" . wa()->getAppStaticUrl('shop') . "plugins/quickorder/css/frontend." . (!$is_debug ? 'min.' : '') . "css" . (!$is_debug ? "?v=" . time() : '') . "'>";

        $plugin_id = $instance->getId();

        $head .= "<script>" .
            "jQuery(document).ready(function($) {" .
            "$.quickorder.init({" .
            "version:'" . $instance->getVersion() . "'," .
            "isDebug:'" . (int) $is_debug . "'," .
            "isMobile:'" . (waRequest::isMobile() ? 1 : 0) . "'," .
            "messages:" . $helper->getLocaleMessages() . "," .
            "currency:" . json_encode($helper->getCurrencyInfo()) . "," .
            "usingPlugins:" . $helper->usingPlugins() . "," .
            "contactUpdate:" . (!empty($settings['contact_update']) ? 1 : 0) . "," .
            "popupClose:" . (!empty($settings['popup_close']) ? 1 : 0) . "," .
            "replace78:" . (!empty($settings['replace_78']) ? 1 : 0) . "," .
            "minimal:" . (!empty($settings['minimal']) ? json_encode($settings['minimal']) : '') . "," .
            "productButton:'[data-quickorder-product-button]'," .
            "cartButton:'[data-quickorder-cart-button]'," .
            "analytics:" . json_encode($helper->getAnalytics($settings)) . "," .
            "urls:{" .
            "getProductSkus:'" . wa()->getRouteUrl('shop/frontend/getProductSkus', array('plugin' => $plugin_id)) . "'," .
            "shipping:'" . wa()->getRouteUrl('shop/frontend/updateShipping', array('plugin' => $plugin_id)) . "'," .
            "update:'" . wa()->getRouteUrl('shop/frontend/update', array('plugin' => $plugin_id)) . "'," .
            "load:'" . wa()->getRouteUrl('shop/frontend/load', array('plugin' => $plugin_id)) . "'," .
            "send:'" . wa()->getRouteUrl('shop/frontend/send', array('plugin' => $plugin_id)) . "'," .
            "service:'" . wa()->getRouteUrl('shop/frontend/updateService', array('plugin' => $plugin_id)) . "'," .
            "cartSaveUrl:{" .
            "shop:'" . $helper->cleanUrl(wa()->getRouteUrl('shop/frontendCart', array('action' => 'save'), true)) . "'," .
            "plugin:'" . $helper->cleanUrl(wa()->getRouteUrl('shop/frontend/cartSave', array(), true)) . "'" .
            "}," .
            "cartDeleteUrl:{" .
            "shop:'" . $helper->cleanUrl(wa()->getRouteUrl('shop/frontendCart', array('action' => 'delete'), true)) . "'," .
            "plugin:'" . $helper->cleanUrl(wa()->getRouteUrl('shop/frontend/cartDelete', array(), true)) . "'" .
            "}," .
            "cartAddUrl:{" .
            "shop:'" . $helper->cleanUrl(wa()->getRouteUrl('shop/frontendCart', array('action' => 'add'), true)) . "'," .
            "plugin:'" . $helper->cleanUrl(wa()->getRouteUrl('shop/frontend/cartAdd', array(), true)) . "'" .
            "}" .
            "}" .
            "});" .
            "});" .
            "</script>";

        return $head;
    }

    public function frontendCart()
    {
        //Настройки
        $settings = shopQuickorderPluginHelper::getSettings();

        if (!empty($settings['status']) && ((!empty($settings['shared_display_settings']) && !empty($settings['product']['use_hook'])) || (empty($settings['shared_display_settings']) && !empty($settings['cart']['use_hook'])))) {
            return self::cartButton();
        }
    }

    public function frontendOrderCartVars(&$params)
    {
        return array('bottom' => $this->frontendCart());
    }

    public function frontendProduct($p)
    {
        //Настройки
        $settings = shopQuickorderPluginHelper::getSettings();

        if (!empty($settings['status']) && !empty($settings['product']['use_hook']) && self::getClass('helper')->isAvailable($p)) {
            $output = array('menu' => '', 'block_aux' => '', 'block' => '');
            $output['cart'] = self::button($p, false, true);
            return $output;
        }
    }

    /**
     * Get quickorder button or form html
     *
     * @param int|array|shopProduct $product
     * @param bool $ignore_availablility
     * @param mixed $force_type
     * @return string
     */
    public static function button($product = array(), $force_type = null, $ignore_availablility = false)
    {
        //Настройки
        $settings = shopQuickorderPluginHelper::getSettings();

        if (empty($settings['status']) || ($product && !$ignore_availablility && !self::getClass('helper')->isAvailable($product))) {
            return '';
        }

        // Получаем информацию о товаре
        // Если товар не передан с витрины, тогда считаем, что мы находимся на странице товара.
        if (!$product) {
            $storefront_view = wa()->getView();
            $product = $storefront_view->getVars('product');
        }
        $product = self::getClass('helper')->getProduct($product);
        if (!$product) {
            return '';
        }

        return self::getClass('form')->getButton($product, 'product', false, $force_type);
    }

    /**
     * Get quickorder cart html
     *
     * @param mixed $force_type
     *
     * @return string
     */
    public static function cartButton($force_type = null)
    {
        // Настройки
        $settings = shopQuickorderPluginHelper::getSettings();

        if (empty($settings['status'])) {
            return '';
        }

        return self::getClass('form')->getButton(array(), 'cart', false, $force_type);
    }

    /**
     * Show button in other applications
     *
     * @param int|array|shopProduct $product
     * @param mixed $force_type
     *
     * @return string
     */
    public static function external($product, $force_type = null)
    {
        // Если конструкцию решили вывести в магазине, используем для этого специальную функцию
        if (wa()->getApp() == 'shop') {
            return self::button($product, 'product', false, $force_type);
        }

        // Настройки
        $settings = shopQuickorderPluginHelper::getSettings();

        if (empty($settings['status']) || ($product && !self::getClass('helper')->isAvailable($product))) {
            return '';
        }

        // Получаем информацию о товаре
        $product = self::getClass('helper')->getProduct($product);
        if (!$product) {
            return '';
        }

        // Подключаем дополнительно скрипты, которые в Магазине выводятся во frontend_head
        return self::getHead() . self::getClass('form')->getButton($product, 'product', false, $force_type);
    }

    public function backendReports()
    {
        $menu_item = "
            <li>
                <a href=\"#/" . $this->getId() . "/\"><img src='" . $this->getPluginStaticUrl() . "img/" . $this->getId() . ".png?v=" . $this->getVersion() . "'> " . _wp("1 click") . "</a>
                <script>
                    $(function(){
                        $.reports." . $this->getId() . "Action = function(params){
                            var content=$(\"#reportscontent\");
                            content.html(\"<div class='block double-padded'>" . _wp('Loading') . "... <i class='icon16 loading'></i></div>\");
                            content.load(\"?plugin=" . $this->getId() . "&module=reports\"+this.getTimeframeParams()+(params ? '&'+params : ''));
                        };
                        $.reports.quickorderCartAction = function(params){
                            var content=$(\"#reportscontent\");
                            content.html(\"<div class='block double-padded'>" . _wp('Loading') . " ... <i class='icon16 loading'></i></div>\");
                            content.load(\"?plugin=quickorder&module=reports&\"+this.getTimeframeParams()+(params ? '&'+params : '')+'&qsource=cart');
                        };
                    });
                </script>
            </li>";

        return array('menu_li' => $menu_item);
    }

    public function backendOrder($order)
    {
        $result = array('title_suffix' => '', 'action_button' => '', 'action_link' => '', 'info_section' => '', 'aux_info' => '');
        $styles = "display: block;background: #aaa;color: #fff;padding: 0 5px;";
        if (!empty($order['params']['quickorder_product'])) {
            $result['aux_info'] = '<span style="' . $styles . '">' . _wp('Quick order of product') . '</span>';
        }
        if (!empty($order['params']['quickorder_cart'])) {
            $result['aux_info'] = '<span style="' . $styles . '">' . _wp('Quick order of cart') . '</span>';
        }

        return $result;
    }

    /**
     * Get class instance
     *
     * @param string $class_name
     * @return mixed
     */
    private static function getClass($class_name)
    {
        static $classes = array();

        if (isset($classes[$class_name])) {
            return $classes[$class_name];
        }

        switch ($class_name) {
            case 'form':
                $classes[$class_name] = new shopQuickorderPluginForm();
                break;
            case 'helper':
                $classes[$class_name] = new shopQuickorderPluginHelper();
                break;
            default:
                $classes[$class_name] = false;
        }

        return $classes[$class_name];
    }

    /**
     * Check plugin availability
     *
     * @return bool
     * @throws waException
     */
    public static function isEnable()
    {
        static $is_enable = null;
        if ($is_enable === null) {
            $plugins = wa()->getConfig()->getPlugins();
            $is_enable = (new waAppSettingsModel())->get('shop.quickorder', 'status') && isset($plugins['quickorder']);
        }
        return $is_enable;
    }

    /**
     * @deprecated 2.0
     */
    public static function getQuickorderSettings()
    {
        $settings = shopQuickorderPluginHelper::getSettings();
        if (empty($settings['status'])) {
            return array();
        }
        $is_cart = wa()->getView()->getVars('frontend_cart');
        $form_settings = !empty($settings['shared_display_settings']) ? $settings['product'] : $settings[$is_cart ? 'cart' : 'product'];
        $form_settings[$is_cart ? 'enable_frontend_cart_hook' : 'enable_frontend_product_hook'] = $form_settings['use_hook'];
        return $form_settings;
    }

    /**
     * @deprecated 2.0
     */
    public static function quickorderForm($product, $cancel_popup = false, $hide_quantity = null, $show_coupon = null)
    {
        return self::button($product, $cancel_popup ? 'form' : 'button');
    }

    /**
     * @deprecated 2.0
     */
    public static function submitCart($cancel_popup = false)
    {
        return self::cartButton($cancel_popup ? 'form' : 'button');
    }

}
