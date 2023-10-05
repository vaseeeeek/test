<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopAutobadgePlugin extends shopPlugin
{
    private static $prepared_products = [];

    private static $profile;

    public function __construct($info)
    {
        parent::__construct($info);
        if (self::$profile === null && shopAutobadgeProfile::isEnabled()) {
            self::$profile = new shopAutobadgeProfile();
            ini_set('memory_limit', -1);
        }
    }

    public function frontendHead()
    {
        shopAutobadgeHelper::getPlugin($this);

        // Настройки
        $settings = shopAutobadgeHelper::getSettings();

        // CSS стили
        $css = shopAutobadgeGenerator::getCss();

        $is_debug = waSystemConfig::isDebug();

        $this->addJs('js/jquerycountdowntimer/jquery.countdownTimer.min.js');
        $this->addJs('js/frontend.' . (!$is_debug ? 'min.' : '') . 'js');
        $this->addCss('css/frontend.' . (!$is_debug ? 'min.' : '') . 'css');

        $head = $css['google_fonts'] ? $css['google_fonts'] : '';
        $inline_css = shopAutobadgeGenerator::getCssArray();
        if (!empty($settings['delay_loading'])) {
            $css['inline_css'] .= '.autobadge-pl{opacity:0;}';
        }
        $head .= $css['inline_css'] ? '<style class="autobadge-inline-css" data-targets="' . ($inline_css ? implode(',', array_keys($inline_css)) : '') . '">' . $css['inline_css'] . '</style>' : '';

        // Урл для обновления наклеек
        $update = wa('shop')->getRouteUrl('shop/frontend/updateBadge');
        // Если имеются наклейки №5, получаем их настройки, чтобы на витрине иметь возможность изменять размер в зависимости от контейнера
        $js_settings = shopAutobadgeCore::getJsSettings();
        $head .= "<script>";
        $head .= "(function($){";
        $head .= "$(function(){";
        $head .= "$.autobadgeFrontend.init({";
        $head .= "update:'" . $update . "',";
        $head .= "forceParentVisible:'" . (!empty($settings['parent_visible']) ? true : false) . "',";
        $head .= "delayLoading:'" . (!empty($settings['delay_loading']) ? true : false) . "',";
        $head .= "delayLoadingAjax:'" . (!empty($settings['delay_loading_ajax']) ? true : false) . "',";
        $head .= "forceParentRelative:'" . (!empty($settings['parent_relative']) || !isset($settings['parent_relative']) ? true : false) . "',";
        $head .= "showLoader:'" . (!empty($settings['show_loader']) || !isset($settings['show_loader']) ? true : false) . "'";
        $head .= ($js_settings ? ',settings:' . json_encode($js_settings) : '');
        $head .= "});";
        $head .= "});";
        $head .= "})(jQuery);";
        $head .= "</script>";
        return $head;
    }

    public function routing($params = array())
    {
        static $stop = 0;

        $settings = shopAutobadgeHelper::getSettings();

        if (!$stop && wa('shop')->getEnv() == 'frontend' && (!empty($settings['frontend_products']) || !isset($settings['frontend_products'])) && !$this->stopExecute()) {
            $stop = 1;
            $event_params = array("products" => array(), "skus" => array());
            $this->frontendProducts($event_params);
        }

        return parent::routing($params);
    }

    public function frontendProducts(&$params)
    {
        $settings = shopAutobadgeHelper::getSettings();
        if (wa('shop')->getEnv() == 'frontend' && isset($params['products']) && (!empty($settings['frontend_products']) || !isset($settings['frontend_products']))) {

            // Профилируем
            if (self::$profile) {
                $hook_before = self::$profile->log('frontend_products', 'Before validating plugins');
                self::$profile->stop($hook_before);
            }

            // Прекращаем работу для некоторых методов и плагинов
            if ($this->stopExecute()) {
                return;
            }

            // Профилируем
            if (self::$profile) {
                $hook_after = self::$profile->log('frontend_products', 'After validating plugins');
                self::$profile->stop($hook_after);
            }

            // Фильтры
            $filters = shopAutobadgeHelper::getFilters();
            if (!$filters) {
                return;
            }

            static $order = null;
            static $in_process = 0;

            // Когда мы пытаемся получить содержимое заказа,  при выполнении: 
            // $shopCart = new shopCart(); $items = $shopCart->items(false);
            // Происходит вызов frontend_products. Это загонит нас в рекурсию, если не делать проверки.
            if (!$order && !$in_process) {
                $in_process = 1;
                $order = shopAutobadgeWorkflow::getOrder();
                // Формируем массив из товаров и артикулов. Он требуется, чтобы понимать с каким набором товаром мы работаем.
                // Если нам попались товары, лежащие в корзине, их не трогаем
                if (!empty($order['order']['items'])) {
                    $order['order']['products'] = $order['order']['skus'] = array();
                    foreach ($order['order']['items'] as $it) {
                        $order['order']['products'][$it['product_id']] = $it['product_id'];
                        $order['order']['skus'][$it['sku_id']] = $it['sku_id'];
                    }
                }
            } elseif ($order && !empty($params['products'])) {

                // Профилируем
                if (self::$profile) {
                    $hook_get_badges = self::$profile->log('frontend_products', 'Get badges');
                }

                if (empty($settings['delay_loading_ajax']) || waRequest::isXMLHttpRequest()) {
                    // Выполняем предварительные настройки скрипта
                    self::preExecute($params);
                }
                foreach ($params['products'] as $k => &$p) {
                    $p = shopAutobadgeHelper::getBadgesData($p, isset(self::$prepared_products[$p['id']]) ? self::$prepared_products[$p['id']] : [], $filters);
                }

                // Профилируем
                if (self::$profile) {
                    if (!empty($hook_get_badges)) {
                        self::$profile->stop($hook_get_badges);
                    }
                }
                (new shopAutobadgeHelper())->destruct();
            }
        }
    }

    public static function prepareProducts($products)
    {
        $settings = shopAutobadgeHelper::getSettings();
        $delay_loading_ajax = !empty($settings['delay_loading_ajax']) && !waRequest::isXMLHttpRequest();
        // Если не используется отложенная загрузка, выполняем расчеты
        if (!$delay_loading_ajax) {
            self::preExecute(array('products' => $products));
        }
    }

    public static function getBadges($product)
    {
        // Если товар обрабатывался, возвращаем его результат
        if (isset($product['autobadge'])) {
            return $product;
        }
        // Получаем товар
        $p = is_int($product) ? new shopProduct($product) : $product;

        // Если товар не добавлен в массив всех товаров, добавляем его туда
        $data_class = new shopAutobadgeData();
        $shop_products = $data_class->getShopProducts();
        if (!isset($shop_products[$p['id']])) {
            $data_class->setShopProducts(array($p));
        }
        // Получаем дефолтные настройки наклеек
        (new shopAutobadgeGenerator())->getDefaultRibbonSettings();
        $product = shopAutobadgeHelper::getBadgesData($p, isset(self::$prepared_products[$p['id']]) ? self::$prepared_products[$p['id']] : [], shopAutobadgeHelper::getFilters());

        return $product;
    }

    private static function preExecute($params)
    {
        if (!empty($params['products'])) {
            $plugins = wa('shop')->getConfig()->getPlugins();
            // Необходимо для корректного расчета скидок по категориям, спискам и тд.
            $is_flexdiscount_enabled = isset($plugins['flexdiscount']) && shopDiscounts::isEnabled('flexdiscount')
                && version_compare($plugins['flexdiscount']['version'], '4', '>=')
                && shopFlexdiscountHelper::getSettings('frontend_prices');
            if ($is_flexdiscount_enabled) {
                $fl_data_class = new shopFlexdiscountData();
                $fl_data_class->setShopProducts($params['products']);
            }

            self::$prepared_products = shopAutobadgeHelper::fixPrices($params['products']);

            // Сохраняем список всех товаров, с которыми предстоит работать
            $data_class = new shopAutobadgeData();
            $data_class->setShopProducts($params['products']);

            // Получаем дефолтные настройки наклеек
            $generator = new shopAutobadgeGenerator();
            $generator->getDefaultRibbonSettings();
        }
    }

    /**
     * Stop executing if some callers are heavy or unnecessary
     *
     * @return bool
     */
    private function stopExecute()
    {
        $settings = shopAutobadgeHelper::getSettings();
        $ignored_callers = [];
        $ignore_plugins = array('cart/add', 'cart/save');

        // Список игнорируемых плагинов
        if (!empty($settings['ignore_plugins'])) {
            $ignore_plugins = array_merge($ignore_plugins, $settings['ignore_plugins']);
        } elseif (!isset($settings['ignore_plugins'])) {
            // Список всех плагинов
            $plugins = wa('shop')->getConfig()->getPlugins();
            unset($plugins['autobadge']);
            $ignore_plugins = array_merge($ignore_plugins, array_keys($plugins));
        }
        // Список игнорируемых методов
        if (!empty($settings['ignore_methods'])) {
            $ignored_callers = $settings['ignore_methods'];
        } elseif (!isset($settings['ignore_methods'])) {
            $ignored_callers = array('shopcartitemsmodel::total', 'shopcart::discount', 'shopcart::items');
        }

        // Отключаем плагин на странице оформления/корзины
        if (!empty($settings['cart_disable'])) {
            $cart_ids = ['cart', 'order'];
            $url_last_part = end(ref(explode('/', rtrim(wa('shop')->getConfig()->getCurrentUrl(), '/'))));
            if (in_array($url_last_part, $cart_ids)) {
                return true;
            }
        }

        foreach ($this->getBacktraceData(15) as $backtrace) {
            if (!empty($backtrace['caller']) && (in_array($backtrace['caller'], $ignored_callers) || $this->strposa($backtrace['caller'], $ignore_plugins))) {
                return true;
            }
        }

        $active_plugin = waRequest::param('plugin', '');
        if (in_array($active_plugin, $ignore_plugins)) {
            return true;
        } elseif (!$active_plugin) {
            // Для плагинов, которые генерируют выгрузки, необходимо анализировать адрес, откуда пришел запрос, потому что в коде
            // нигде не указывается, что запрос исходит от плагина
            $request_url = wa('shop')->getConfig()->getRequestUrl(false, true);
            foreach ($ignore_plugins as $pl) {
                if (strpos($request_url, $pl) !== false) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Works lke strpos but $needle can be an array
     *
     * @param string $string
     * @param array $needle
     * @param int $offset
     * @return bool
     */
    private function strposa($string, $needle, $offset = 0)
    {
        if (!is_array($needle)) $needle = array($needle);
        foreach ($needle as $query) {
            $query = 'shop' . $query;
            if (strpos($string, $query, $offset) !== false) return true;
        }
        return false;
    }

    /**
     * Get backtrace
     *
     * @param int $level
     * @return array
     */
    private function getBacktraceData($level = 1)
    {
        $data = [];
        $level = intval($level);
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $level);
        foreach ($backtrace as $k => $item) {
            $data[$k] = [
                'method' => strtolower($item['function']),
                'caller' => ''
            ];
            if (array_key_exists('class', $item)) {
                $data[$k]['caller'] .= strtolower($item['class']) . '::';
            }
            if (array_key_exists('function', $item)) {
                $data[$k]['caller'] .= strtolower($item['function']);
            }
        }
        return $data;
    }

}
