<?php

/**
 * User: Echo-company
 * Email: info@echo-company.ru
 * Site: https://www.echo-company.ru
 */
class shopCityselectHelper
{

    protected static $location = null;

    /**
     * Получение определенной локации
     *
     * @return array массив с данными
     */
    public static function getLocation()
    {

        if (!self::$location) {
            self::$location = self::detectLocation();
        }

        return self::$location;
    }


    public static function detectLocation()
    {
        $result = array();

        //Первое смотри куки! пофиг на все остальное, куки это то что пользователь выбрал вручную
        $result['city'] = waRequest::cookie('cityselect__city', '', waRequest::TYPE_STRING_TRIM);
        $result['region'] = waRequest::cookie('cityselect__region', '', waRequest::TYPE_STRING_TRIM);
        $result['zip'] = waRequest::cookie('cityselect__zip', '', waRequest::TYPE_STRING_TRIM);
        $result['constraints_street'] = waRequest::cookie('cityselect__constraints_street', '', waRequest::TYPE_STRING_TRIM);

        $result['country'] = 'rus';//Работаем только с Россией

        //Отправная точка город
        if (!empty($result['city'])) {
            return $result;
        }

        //TODO: Смотрим не указывал ли пользователь что в корзине

        //TODO: Смотрим не авторизован ли пользователь, пока не отлажено
//        if (wa()->getUser()->isAuth()) {
//
//            $contact = wa()->getUser();
//
//            $user_address = $contact->get('address.shipping');
//
//            if ((!empty($user_address)) && (!empty($user_address[0]))) {
//
//                $address = $user_address[0]['data'];
//
//                if ((!empty($address['city'])) && (!empty($address['region'])) &&
//                    (!empty($address['country'])) && ($address['country'] == 'rus')) {
//
//                    $result['city'] = $address['city'];
//                    $result['region'] = $address['region'];
//
//                    if (!empty($address['zip'])){
//                        $result['zip'] = $address['zip'];
//                    }
//                    return $result;
//                };
//            };
//        }

        //По умолчанию
        $settings = shopCityselectPlugin::loadRouteSettings();

        $result['city'] = $settings['default_city'];
        $result['region'] = $settings['default_region'];
        $result['zip'] = $settings['default_zip'];
        $result['need_detect'] = true;

        return $result;
    }

    public static function externalPlugins($result)
    {
        $plugins = wa('shop')->getConfig()->getPlugins();
        if ((isset($plugins['regions'])) && (class_exists('shopRegionsCityModel'))) {

            $city_model = new shopRegionsCityModel();

            $find_region = $city_model->getByField(array('country_iso3' => 'rus', 'region_code' => $result['region'], 'name' => $result['city'], 'is_enable' => 1));

            if (!empty($find_region)) {
                $result['shop_regions'] = $find_region;
            }

        }

        return $result;
    }

    public static function setCookie($name, $value)
    {
        $expires = time() + 12 * 30 * 86400; // 1 год
        wa()->getResponse()->setCookie($name, $value, $expires);
    }

    public static function setCity($city, $region, $zip = '')
    {

        $result = array();

        //Сперва устанавливаем свои куки
        self::setCookie('cityselect__city', $city);
        self::setCookie('cityselect__region', $region);
        self::setCookie('cityselect__zip', $zip);


        //Сохраняем в форму оформления заказа
        if (wa()->getUser()->isAuth()) {
            $contact = wa()->getUser();
        } else {
            $data = wa()->getStorage()->get('shop/checkout');
            $contact = isset($data['contact']) ? $data['contact'] : null;
        }

        if (!$contact) {
            $contact = wa()->getUser();
        }

        //На основе документации https://developers.webasyst.ru/cookbook/contacts-app-integration/
        $address = $contact->get('address.shipping');
        $address[0]['data']['country'] = 'rus';
        $address[0]['data']['city'] = $city;
        $address[0]['data']['region'] = $region;
        if (!empty($zip)) {
            $address[0]['data']['zip'] = $zip;
        }

        //Для Shop-script 8
        $is_ss8 = class_exists('shopCheckoutRegionStep');

        $contact->set('address.shipping', $address);

        //Данные у неавторизованного пользователя сохраняются в Storage
        if (wa()->getUser()->isAuth()) {
            $contact->save();
        } else {
            $data['contact'] = $contact;
            wa()->getStorage()->set('shop/checkout', $data);
        }

        if ($is_ss8) {
            $data = wa()->getStorage()->get('shop/checkout');

            if (empty($data['order'])) {
                $data['order'] = array();
            }

            $input = !empty($data['order']['region']) ? $data['order']['region'] : array();

            $input['country'] = 'rus';
            $input['region'] = $region;
            $input['city'] = $city;
            $input['zip'] = $zip;

            $data['order']['region'] = $input;

            //Не проканало, разработчики webasyst, как установить zip для шага Shipping https://yadi.sk/i/64G0CXAIUBrjag ?
            $details = !empty($data['order']['details']) ? $data['order']['details'] : array();

            if (empty($details['shipping_address'])) {
                $details['shipping_address'] = array('zip' => $zip);
            } else {
                $details['shipping_address']['zip'] = $zip;
            }

            $data['order']['details'] = $details;

            wa()->getStorage()->set('shop/checkout', $data);

        }

        $result['city'] = $city;
        $result['region'] = $region;
        if (!empty($zip)) {
            $result['zip'] = $zip;
        }
        $result['country'] = 'rus';

        //Интеграция с другими плагинами
        $result = self::externalPlugins($result);

        return $result;
    }

    public static function getNotifierType($settings)
    {

        $show_notifier = 'auto';

        //настройка вывода уведомлений
        if (!empty($settings['notifier'])) {

            $show_notifier = 'none';

            if (($settings['notifier'] != 'none') && (!empty($settings['notifier_custom']))) {

                //Вывод уведомления на определенных страницах
                $current_action = waRequest::param('action', '');

                //Регистрация
                $current_url = wa()->getRouting()->getCurrentUrl();
                if (strpos($current_url, 'signup/') !== false) {
                    $current_action = 'signup';
                }

                //Пользователю уже показывали уведомление
                $is_showed_notifier = waRequest::cookie('cityselect__show_notifier', '', waRequest::TYPE_STRING_TRIM);

                foreach ($settings['notifier_custom'] as $need_action) {
                    if ($current_action == $need_action) {
                        if (empty($is_showed_notifier)) {
                            $show_notifier = 'force';
                        } else {
                            $show_notifier = 'auto';
                        }
                        break;
                    }
                }
            }

        }

        //Уведомление после перенаправление на витрину
        if ($show_notifier == 'auto') {

            if (waRequest::cookie('cityselect__force_notify', false)) {
                $show_notifier = 'force';
            }

        }

        return $show_notifier;
    }


    //////////////////////////////////////////////////////////////////////////////////////
    /// Общие функции EchoHelper
    //////////////////////////////////////////////////////////////////////////////////////

    public static function isAjax()
    {
        return waRequest::isXMLHttpRequest();
    }


    /**
     * Возвращает список витрин
     *
     * @param string $app_id идентификатор приложения
     * @return array
     */
    public function getRoutes($app_id = 'shop')
    {
        return self::staticGetRoutes($app_id);
    }

    public static function staticGetRoutes($app_id = 'shop')
    {
        $result = array();
        $domain_routes = wa()->getRouting()->getByApp($app_id);
        foreach ($domain_routes as $domain => $routes) {
            foreach ($routes as $key => $route) {
                $route_url = $domain . '/' . $route['url'];
                $result[md5($route_url)] = $route_url;
            }
        }
        return $result;
    }


    /**
     * Выполнение SMARTY шаблона
     * @param $str
     * @return string
     */
    public static function executeSmarty($str)
    {
        $view = wa()->getView();
        return $view->fetch('string:' . $str);
    }

    public static function createVariableDB($full)
    {

        $model = new waModel();

        if ($full) {
            //Создаем таблицу shop_cityselect__variables_type
            $sql = "CREATE TABLE IF NOT EXISTS `shop_cityselect__variables_type` (
              `id` int NOT NULL AUTO_INCREMENT,
              `code` varchar(255) NOT NULL,
              `name` varchar(255) DEFAULT NULL,
              `template` text,
              PRIMARY KEY (`id`),
              KEY `code` (`code`)
            ) DEFAULT CHARSET=utf8;";
            $model->exec($sql);

            //Создаем таблицу shop_cityselect__region
            $sql = "CREATE TABLE IF NOT EXISTS `shop_cityselect__region` (
              `id` int NOT NULL AUTO_INCREMENT,
              `region` int NOT NULL,
              `city` varchar(255) NOT NULL,
              PRIMARY KEY (`id`),
              KEY `region` (`region`,`city`)
            ) DEFAULT CHARSET=utf8;";
            $model->exec($sql);

            $sql = "CREATE TABLE IF NOT EXISTS `shop_cityselect__variables` (
              `id` int NOT NULL AUTO_INCREMENT,
              `type_id` int NOT NULL,
              `region_id` int NOT NULL,
              `value` text,
              PRIMARY KEY (`id`),
              KEY `type_id` (`type_id`),
              KEY `region_id` (`region_id`)
            ) DEFAULT CHARSET=utf8;";

            $model->exec($sql);

        }

        //Данные по умолчанию
        $sql = 'INSERT INTO `shop_cityselect__variables_type` (`id`, `code`, `name`, `template`) VALUES
            (1, \'phone\', \'Телефон\', \'<a class="i-cityselect__var--{$code|escape}" href="tel:+{$value|regex_replace:\'\'/[^0-9]+/\'\':\'\'\'\'}">{$value}</a>\'),
            (2, \'email\', \'Email\', \'<a class="i-cityselect__var--{$code|escape}" href="mailto:{$value|escape}">{$value}</a>\'),
            (3, \'address\', \'Адрес\', \'<div class="i-cityselect__var--{$code|escape}">{$value}</div>\'),
            (4, \'workhours\', \'Часы работы\', \'<span class="i-cityselect__var--{$code|escape}">{$value}</span>\');';
        $model->exec($sql);

        $sql = 'INSERT INTO `shop_cityselect__region` (`id`, `region`, `city`) VALUES(1, 0, \'\')';
        $model->exec($sql);

    }

    public static function detectRedirect($location)
    {
        $find = array();

        $find['region'] = (int)$location['region'];
        $find['city'] = trim($location['city']);
        $region_model = new shopCityselectRegionModel();

        $find_default = $region_model->findDefault();
        $find_default_region = $region_model->findDefaultRegion($find['region']);
        $find_region = $region_model->findRegion($find);

        $result = '';
        if ((!empty($find_region)) && (!empty($find_region['redirect']))) {
            $result = $find_region['redirect'];
        } elseif ((!empty($find_default_region)) && (!empty($find_default_region['redirect']))) {
            $result = $find_default_region['redirect'];
        } elseif ((!empty($find_default)) && (!empty($find_default['redirect']))) {
            $result = $find_default['redirect'];
        }

        //проверяем нахождение на текущей витрине
        if (!empty($result)) {

            $routing = wa()->getRouting();
            $domain = $routing->getDomain(null, true);
            $a_route = $routing->getRoute();
            $current_route = $domain . '/' . $a_route['url'];

            if ($result == $current_route) {
                $result = '';
            }
        }

        return $result;
    }

    public static function pushCookies()
    {
        $key = uniqid();

        $cookies = waRequest::cookie();

        $storage = wa()->getStorage();
        $options = $storage->getOptions();
        $session_name = !empty($options['session_name']) ? $options['session_name'] : session_name();
        $cookies[$session_name] = session_id();

        shopCityselectCookiesModel::pushCookies($key, $cookies);

        return $key;
    }

    public static function getRedirectPageUrl($redirect_storefront, $params, $current_url)
    {

        list($domain, $route) = explode('/', $redirect_storefront, 2);

        $path = 'shop/' . (empty($params['module']) ? 'frontend' : $params['module']);

        if (!empty($params['action'])) {
            $path .= "/" . $params['action'];
        }

        $routes = wa('shop')->getRouting()->getByApp('shop', $domain);
        $redirected_params = $params;
        if (!empty($routes)) {
            foreach ($routes as $domain_route) {
                if ($domain_route['url'] == $route) {
                    $redirected_params = $domain_route;
                    break;
                }

            }
        }

        if (($path == 'shop/frontend/page') || ($path == 'shop/frontend')) {
            $url = '//' . $domain . '/' . str_replace('*', $current_url, $route);
            return $url;
        }

        //Категории
        if ($path == 'shop/frontend/category') {

            //Отсутсвует параметр необходимый для генерации URL
            if (empty($params['category_url'])) {

                //Если нет данных перенаправляем на главную
                if (empty($params['category_id'])) {
                    $path = 'shop/frontend';
                } else {

                    $category_model = new shopCategoryModel();
                    $find_category = $category_model->getById($params['category_id']);
                    if (empty($find_category)) {
                        $path = 'shop/frontend';
                    } else {
                        $params['category_url'] = $find_category[$redirected_params['url_type'] == 1 ? 'url' : 'full_url'];
                    }
                }
            }
        }

        if ((!empty($params['plugin'])) && ($params['plugin'] == 'buy1step')) {
            unset($params['plugin'], $params['_']);
        }

        unset($params['app']);
        //wa_dump(wa('shop')->getRouteUrl($path, $params, true, $domain, $route), $path, $params);
        return wa('shop')->getRouteUrl($path, $params, true, $domain, $route);
    }

    /**
     * Функция определяет по поискового робота при помощи user-agent
     * @return bool
     */
    public static function isBot()
    {
        $user_agent = waRequest::server('HTTP_USER_AGENT');

        $bots = array(
            // Yandex
            'YandexBot', 'YandexAccessibilityBot', 'YandexMobileBot', 'YandexDirectDyn', 'YandexScreenshotBot',
            'YandexImages', 'YandexVideo', 'YandexVideoParser', 'YandexMedia', 'YandexBlogs', 'YandexFavicons',
            'YandexWebmaster', 'YandexPagechecker', 'YandexImageResizer', 'YandexAdNet', 'YandexDirect',
            'YaDirectFetcher', 'YandexCalendar', 'YandexSitelinks', 'YandexMetrika', 'YandexNews',
            'YandexNewslinks', 'YandexCatalog', 'YandexAntivirus', 'YandexMarket', 'YandexVertis',
            'YandexForDomain', 'YandexSpravBot', 'YandexSearchShop', 'YandexMedianaBot', 'YandexOntoDB',
            'YandexOntoDBAPI', 'YandexTurbo', 'YandexVerticals',

            // Google
            'Googlebot', 'Googlebot-Image', 'Mediapartners-Google', 'AdsBot-Google', 'APIs-Google',
            'AdsBot-Google-Mobile', 'AdsBot-Google-Mobile', 'Googlebot-News', 'Googlebot-Video',
            'AdsBot-Google-Mobile-Apps',

            // Other
            'Mail.RU_Bot', 'bingbot', 'Accoona', 'ia_archiver', 'Ask Jeeves', 'OmniExplorer_Bot', 'W3C_Validator',
            'WebAlta', 'YahooFeedSeeker', 'Yahoo!', 'Ezooms', 'Tourlentabot', 'MJ12bot', 'AhrefsBot',
            'SearchBot', 'SiteStatus', 'Nigma.ru', 'Baiduspider', 'Statsbot', 'SISTRIX', 'AcoonBot', 'findlinks',
            'proximic', 'OpenindexSpider', 'statdom.ru', 'Exabot', 'Spider', 'SeznamBot', 'oBot', 'C-T bot',
            'Updownerbot', 'Snoopy', 'heritrix', 'Yeti', 'DomainVader', 'DCPbot', 'PaperLiBot', 'StackRambler',
            'msnbot', 'msnbot-media', 'msnbot-news'
        );

        foreach ($bots as $bot) {
            if (stripos($user_agent, $bot) !== false) {
                return true;
            }
        }

        return false;
    }
}