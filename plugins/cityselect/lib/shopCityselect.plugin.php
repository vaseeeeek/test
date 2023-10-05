<?php

/**
 * Основной класс плагина
 *
 * User: Echo-company
 * Email: info@echo-company.ru
 * Site: https://www.echo-company.ru
 */
class shopCityselectPlugin extends shopPlugin
{

    /**
     * Всегда имееется Helper под рукой
     * @var shopCityselectHelper
     */
    public $helper;

    public static $instance = null;

    public static $enable_install = null;

    public function __construct($info)
    {
        parent::__construct($info);

        $class_helper = 'shop' . ucfirst($this->id) . 'Helper';
        $this->helper = new $class_helper();

        if (is_null(self::$instance)) {
            self::$instance = $this;
        }
    }

    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = wa('shop')->getPlugin('cityselect');
        }
        return self::$instance;
    }


    public function getCssJs($settings, $route = false)
    {
        $version = $this->getVersion();

        //Пользователь изменил CSS/JS
        if ($settings['user_css']) {

            if (!empty($settings['js'])) {
                $js = wa()->getDataUrl($settings['js'], true, 'shop');
            } else {
                $js = wa()->getAppStaticUrl('shop') . $this->getUrl('js/frontend.min.js?v=' . $version, true);
            }

            if (!empty($settings['css'])) {
                $css = wa()->getDataUrl($settings['css'], true, 'shop');
            } else {
                $css = wa()->getAppStaticUrl('shop') . $this->getUrl('css/frontend.min.css?v=' . $version, true);
            }
        } else {
            $css = wa()->getAppStaticUrl('shop') . $this->getUrl('css/frontend.min.css?v=' . $version, true);
            $js = wa()->getAppStaticUrl('shop') . $this->getUrl('js/frontend.min.js?v=' . $version, true);
        }

        //Подключаем плагин DaData suggestion
        $dadata = '';
        if ($settings['enable_plugin']) {
            $dadata_css = wa()->getAppStaticUrl('shop') . $this->getUrl('css/suggestions.min.css?v=' . $version, true);
            $dadata_js = wa()->getAppStaticUrl('shop') . $this->getUrl('js/jquery.suggestions.min.js?v' . $version, true);
            $dadata = "<link href='$dadata_css' rel='stylesheet'><script src='$dadata_js'></script>";
        }

        $url = self::getBaseUrl($route);
        $lib = wa()->getAppStaticUrl('shop') . 'plugins/cityselect/js/fancybox/';
        $token = htmlspecialchars($settings['token']);

        $bounds = empty($settings['bounds']) ? 'city' : $settings['bounds'];

        $show_notifier = shopCityselectHelper::getNotifierType($settings);

        $in_checkout = (!empty($settings['in_checkout'])) ? 1 : 0;

        $plugin_dp = (!empty($settings['plugin_dp'])) ? 1 : 0;

        $result = "$dadata<link href='$css' rel='stylesheet'><script src='$js'></script>";

        //Параметры для редиректов
        $params = waRequest::param();
        $params['cityselect__url'] = wa()->getRouting()->getCurrentUrl();
        $params['cityselect__url'] = empty($params['cityselect__url']) ? '' : $params['cityselect__url'];

        //Отключение автоопределения
        $disable_auto = (!empty($settings['disable_auto'])) ? 1 : 0;

        $result .= "<script>function init_shop_cityselect(){
shop_cityselect.location=" . json_encode(shopCityselectHelper::getLocation()) . ";
shop_cityselect.route_params=" . json_encode($params) . ";
shop_cityselect.init('$token','$url','$lib','$bounds','$show_notifier',$in_checkout,$plugin_dp,$disable_auto);
        } if (typeof shop_cityselect !== 'undefined') { init_shop_cityselect() } else { $(document).ready(function () { init_shop_cityselect() }) }</script>";

        return $result;
    }

    /**
     * Хук => Подключаем css js в зависимости от витрины
     */
    public function frontendHead()
    {
        $settings = self::loadRouteSettings();

        if ($settings['enable']) {

            //Редиректы пока отключены из за большого кол-ва сложностей
//            $status = wa()->getResponse()->getStatus();
//            if ((!shopCityselectHelper::isBot()) && (!$status || $status == 200)) {
//                $this->checkRedirect();
//            }

            return $this->getCssJs($settings);
        }
    }

    /**
     * Хук => Вывод выбора города
     */
    public function frontendHeader()
    {
        $settings = self::loadRouteSettings();
        if ($settings['enable'] && ($settings['hook'] == 'header')) {
            return self::showCity(false, 'header');
        }
    }

    /**
     * Получить адрес поселения по md5
     * @param $route
     * @return string
     */
    public static function getBaseUrl($route)
    {
        if ((wa()->getApp() != 'shop') || ($route !== false)) {

            $routes = wa()->getRouting()->getByApp('shop');

            foreach ($routes as $domain => $a_routes) {
                foreach ($a_routes as $a_route) {
                    if (md5($domain . '/' . $a_route['url']) == $route) {
                        return wa('shop')->getRouteUrl('shop/frontend', array('domain' => $domain, 'module' => 'frontend'), false, $domain, $a_route['url']);
                    }
                }
            }
        }

        //По умолчанию определяем автоматическаи
        return wa('shop')->getRouteUrl('shop/frontend', array(), false);
    }


    /**
     * Показать город
     *
     * @param bool $route
     * @param bool $by_hook
     * @return string
     * @throws waException
     */
    public static function showCity($route = false, $by_hook = false)
    {

        //Проверяем включение плагина при вызове статичного метода
        $plugins = wa('shop')->getConfig()->getPlugins();
        if (!isset($plugins['cityselect'])) return '';

        $settings = self::loadRouteSettings($route);
        if ($settings['enable']) {
            $view = wa()->getView();

            $settings['by_hook'] = $by_hook;

            //Вызов за пределами приложения Магазин
            if (wa()->getApp() != 'shop') {
                $plugin = wa('shop')->getPlugin('cityselect');
                echo $plugin->getCssJs($settings, $route);
            }

            //Уведомления
            $view->assign('show_notifier', shopCityselectHelper::getNotifierType($settings));

            //Геолокация
            $view->assign('location', shopCityselectHelper::getLocation());

            //Настройки
            $view->assign('settings', $settings);

            $view->assign('current_theme', waRequest::getTheme());

            $view->assign('base_url', self::getBaseUrl($route));

            //для правильной локализации
            waLocale::loadByDomain(array('shop', 'cityselect'));
            waSystem::pushActivePlugin('cityselect', 'shop');

            $template_file = '';

            //Пользователь указал свои шаблоны
            if ($settings['user_template']) {

                if (!empty($settings['template'])) {
                    $template_file = wa()->getDataPath($settings['template'], false, 'shop');
                }

                //Если какая ошибка, используем системный шаблон
                if ((empty($settings['template'])) || (!is_readable($template_file))) {
                    $template_file = wa()->getAppPath('plugins/cityselect/templates/actions/frontend/template.html', 'shop');
                }

            } else {
                $template_file = wa()->getAppPath('plugins/cityselect/templates/actions/frontend/template.html', 'shop');
            }

            //Рендерим шаблон
            $html = $view->fetch($template_file);

            //для правильной локализации
            waSystem::popActivePlugin();

            return $html;
        }

    }


    /**
     * Сохраниен настроек, с поддержкой мультивитринности
     * @param array $settings
     * @return void
     * @throws Exception
     */
    public function saveSettings($settings = array())
    {
        $current_id = waRequest::post('current_id', false);
        $current = waRequest::post('current');

        // Обработка полей
        if (($current_id !== false) && (!empty($current))) {
            $cities = array();
            if (!empty($current['cities'])) {
                foreach ($current['cities']['city'] as $key => $name) {
                    $field = array();
                    $field['city'] = $name;
                    $field['region'] = $current['cities']['region'][$key];
                    $field['zip'] = $current['cities']['zip'][$key];
                    $field['bold'] = (int)$current['cities']['bold'][$key];
                    $cities[] = $field;
                }
            }
            $current['cities'] = $cities;

            if (empty($current['notifier_custom'])) {
                $current['notifier_custom'] = array();
            }
        }

        //Добавляем значения по умолчнию чтобы не было Warning при сохранении
        $default_config = $this->getSettingsConfig();
        unset($default_config['routes']); //Удаляем настройки витрин
        $settings += $default_config;

        if (($current_id !== false) && (!empty($current))) {
            $settings['routes'] = $this->getSettings('routes');

            //Проверяем не было ли раньше CSS JS
            if (!empty($settings['routes'][$current_id])) {
                $old = $settings['routes'][$current_id];

                if (!empty($old['css'])) {
                    $path = wa()->getDataPath($old['css'], true, 'shop');
                    waFiles::delete($path, true);
                }

                if (!empty($old['js'])) {
                    $path = wa()->getDataPath($old['js'], true, 'shop');
                    waFiles::delete($path, true);
                }
            }

            //Сохраняем CSS JS
            $file_css = 'plugins/' . $this->id . '/css/frontend_' . uniqid() . '.css';
            file_put_contents(wa()->getDataPath($file_css, true, 'shop'), $current['css']);
            $current['css'] = $file_css;

            $file_js = 'plugins/' . $this->id . '/js/frontend_' . uniqid() . '.js';
            file_put_contents(wa()->getDataPath($file_js, true, 'shop'), $current['js']);
            $current['js'] = $file_js;

            //Обрабатываем шаблоны для поселения
            if (!empty($settings['templates'])) {
                $templates = explode(',', $settings['templates']);
                foreach ($templates as $template) {

                    $file = 'plugins/' . $this->id . '/templates/' . $template . '_' . $current_id . '.html';
                    file_put_contents(wa()->getDataPath($file, false, 'shop'), $current[$template]);
                    $current[$template] = $file;
                }
            }

            $settings['routes'][$current_id] = $current;
        }

        parent::saveSettings($settings);
    }

    public static function detectRoute()
    {
        $routing = wa()->getRouting();
        $domain = $routing->getDomain(null, true);
        $a_route = $routing->getRoute();
        return md5($domain . '/' . $a_route['url']);
    }

    /**
     * Загрузка настроек в зависимости от витрины
     * @param bool $route
     * @return mixed
     */
    public static function loadRouteSettings($route = false)
    {
        $plugin = self::getInstance();

        //По умолчанию определяем автоматическаи
        if ($route === false) {
            $route = self::detectRoute();
        }

        $settings = $plugin->getSettings();
        $routes = $settings['routes'];

        $current = (isset($routes[$route])) ? $routes[$route] : $routes[0];
        $current['route'] = $route;
        return $current;
    }

    public function frontendCheckout()
    {

        $settings = self::loadRouteSettings();

        if (($settings['enable']) && (!empty($settings['in_checkout']))) {
            return "<script>if (typeof shop_cityselect !== 'undefined') { shop_cityselect.initWaAddress() } else { document.addEventListener('DOMContentLoaded', function() { shop_cityselect.initWaAddress()})}</script>";
        }
    }

    public function getHumanVersion()
    {
        return $this->info['version'];
    }

    public function getVariablesTypes()
    {
        $model = new shopCityselectVariablesTypeModel();
        return $model->getAll();
    }

    public function getPath()
    {
        return $this->path;
    }

    /**
     * Возвращает значение переменных
     * @param $name
     * @param bool $route зарезервировано, пока не используется
     * @param bool $raw формат вывода, html или массив с даными о переменной для разработчиков
     * @return array|string отформатированное значение в соответствие с типом переменной или массив с данными о переменной ксли $raw=true
     */
    public static function variable($name, $route = false, $raw = false)
    {
        $result = '';
        $raw_result = array('value' => '');

        try {

            $name = trim($name);

            if (empty($name)) {
                throw  new Exception('Пустое имя переменной');
            }

            //Получаем тип переменной
            $model_type = new shopCityselectVariablesTypeModel();
            $type = $model_type->getByField('code', $name);

            if (empty($type)) {
                throw  new Exception('Не найдет тип переменной: ' . $name);
            }
            $raw_result['type'] = $type;

            //Определяем город
            $location = shopCityselectHelper::getLocation();
            $raw_result['location'] = $location;

            //Ищем подходящие переменные
            $variables_model = new shopCityselectVariablesModel();

            $variable = $variables_model->findVariable($name, $location);

            if (!empty($variable)) {
                $raw_result['value'] = !empty($variable['value']) ? $variable['value'] : '';
                $type['value'] = $raw_result['value'];
            }

            $view = wa()->getView();
            foreach ($type as $key => $value) {
                if ($key == 'template') {
                    continue;
                }
                $view->assign($key, $value);
            }
            $html = $view->fetch('string:' . $type['template']);

            $raw_result['html'] = $html;
            $result = $html;


        } catch (Exception $e) {

            if (!$raw) {
                return $result;
            } else {
                $raw_result['status'] = 'fail';
                $raw_result['error'] = $e->getMessage();
                return $raw_result;
            }

        }


        return $raw ? $raw_result : $result;
    }

    /**
     * Получение переменных для текущего местоположения
     * @return mixed
     */
    public static function variables()
    {

        //Определяем город и регион
        $location = shopCityselectHelper::getLocation();

        $variables_model = new shopCityselectVariablesModel();

        $variables = $variables_model->findVariables($location['region'], $location['city']);
        $variables = $variables_model->parseVariables($variables, false);

        return $variables;
    }

    public function checkRedirect()
    {

        $location = shopCityselectHelper::getLocation();
        if (empty($location['city']) || !empty($location['need_detect'])) {
            return;
        }

        $redirect = shopCityselectHelper::detectRedirect($location);
        if (empty($redirect)) {
            return;
        }

        $redirect_url = shopCityselectHelper::getRedirectPageUrl($redirect, waRequest::param(), wa()->getRouting()->getCurrentUrl());

        wa()->getResponse()->redirect($redirect_url);

    }

    public function backendOrders()
    {
        $settings = $this->getSettings();
        $token = $settings['routes'][0]['token'];
        if ((!empty($settings['in_admin'])) && (!empty($token))) {
            $this->addCss('css/suggestions.min.css');
            $this->addCss('css/backend.css');

            $this->addJs('js/jquery.suggestions.min.js');
            $this->addJs('js/backend.min.js');
        }
    }

    public function backendCustomers()
    {
        $this->backendOrders();

        $script = $this->backendOrderEdit();
        return array(
            'sidebar_top_li' => $script
        );
    }

    public function backendOrderEdit()
    {
        $settings = $this->getSettings();
        $token = $settings['routes'][0]['token'];
        if ((!empty($settings['in_admin'])) && (!empty($token))) {
            $bounds = $settings['routes'][0]['bounds'];
            return "<script>shop_cityselect__backend.init('$token','$bounds')</script>";
        }
    }

    public function addressAutocomplete()
    {
        $settings = self::loadRouteSettings();
        if (($settings['enable']) && (!empty($settings['in_checkout']))) {
            return array(array('city' => ' '));
        }
    }

    public function checkoutRenderRegion($data)
    {
        $settings = self::loadRouteSettings();
        $location = shopCityselectHelper::getLocation();

        if (($settings['enable']) && (!empty($settings['in_checkout'])) && (!empty($location['city']))) {

            $view = wa()->getView();
            $view->assign('location', $location);
            $view->assign('base_url', self::getBaseUrl(false));

            $template_file = wa()->getAppPath('plugins/cityselect/templates/actions/frontend/checkout_render_region.html', 'shop');

            return $view->fetch($template_file);
        }

    }


    public static function isEnable()
    {
        $settings = self::loadRouteSettings();
        return ((self::enableInstall('cityselect')) && (!empty($settings['enable'])));
    }


    public static function enableInstall($id)
    {
        if (is_null(self::$enable_install)) {
            $plugins = wa('shop')->getConfig()->getPlugins();
            self::$enable_install = !isset($plugins[$id]) ? false : true;
        }

        return self::$enable_install;
    }
}