<?php

/**
 * Установка города
 *
 * User: Echo-company
 * Email: info@echo-company.ru
 * Site: https://www.echo-company.ru
 */

class shopCityselectPluginFrontendSetCityController extends waJsonController
{

    public function execute()
    {
        //Установить город определенный по умолчанию
        $set_default = waRequest::post('set_default', 0, waRequest::TYPE_INT);
        $constraints_street = '';

        if ($set_default) {

            $location = shopCityselectHelper::getLocation();

            $city = $location['city'];
            $region = $location['region'];
            $zip = $location['zip'];
        } else {
            $city = waRequest::post('city', '', waRequest::TYPE_STRING_TRIM);
            $settlement = waRequest::post('settlement', '', waRequest::TYPE_STRING_TRIM);
            $region = waRequest::post('region_kladr_id', '', waRequest::TYPE_STRING_TRIM);
            $zip = waRequest::post('postal_code', '', waRequest::TYPE_STRING_TRIM);

            $region = substr($region, 0, 2);

            if (!empty($settlement)) {
                $city = $settlement;
            }

            //Кладр и Фиас
            $kladr_id = waRequest::post('kladr_id', '', waRequest::TYPE_STRING_TRIM);
            shopCityselectHelper::setCookie('cityselect__kladr_id', $kladr_id);

            $fias_id = waRequest::post('fias_id', '', waRequest::TYPE_STRING_TRIM);
            shopCityselectHelper::setCookie('cityselect__fias_id', $fias_id);

            //Ограничение для улицы
            $settlement_kladr_id = waRequest::post('settlement_kladr_id', '', waRequest::TYPE_STRING_TRIM);
            $city_kladr_id = waRequest::post('city_kladr_id', '', waRequest::TYPE_STRING_TRIM);


            if (!empty($city_kladr_id)) {
                $constraints_street = $city_kladr_id;
            }

            if (!empty($settlement_kladr_id)) {
                $constraints_street = $settlement_kladr_id;
            }

            shopCityselectHelper::setCookie('cityselect__constraints_street', $constraints_street);
        }

        $this->response = shopCityselectHelper::setCity($city, $region, $zip);

        if (!empty($constraints_street)) {
            $this->response['constraints_street'] = $constraints_street;
        }

        $this->response['set_default'] = $set_default;

        if (!$set_default) {
            $this->response['request'] = waRequest::post();
        }

        $settings = shopCityselectPlugin::loadRouteSettings();

        if (!empty($settings['plugin_dp'])) {
            wa()->getResponse()->setCookie('dp_plugin_country', 'rus');
            wa()->getResponse()->setCookie('dp_plugin_region', $region);
            wa()->getResponse()->setCookie('dp_plugin_city', $city);
            wa()->getResponse()->setCookie('dp_plugin_zip', $zip);
        }

        $variables_model = new shopCityselectVariablesModel();

        $variables = $variables_model->findVariables($region, $city);
        $variables = $variables_model->parseVariables($variables, true);

        $this->response['variables'] = $variables;

        $this->response['redirect'] = shopCityselectHelper::detectRedirect(array('city' => $city, 'region' => $region));

        $params = waRequest::post('route_params', array(), waRequest::TYPE_ARRAY);

        // Проверяем разрешен ли редирект на странице  оформления заказа
        $disable_redirect = false;
        $in_order = (($params['action'] == 'order') || ($params['action'] == 'checkout'));

        //Плагин "Заказ в 1 шаг"
        if ((!empty($params['plugin'])) && ($params['plugin'] == 'buy1step')) {
            $in_order = true;
        }

        if ((!empty($settings['disable_order_redirect'])) && ($in_order)) {
            $disable_redirect = true;
        }

        if ((!empty($this->response['redirect'])) && (!$disable_redirect)) {

            //Подготавливаемся к переходу
            list($domain, $route) = explode('/', $this->response['redirect'], 2);

            $save_url = wa('shop')->getRouteUrl('shop/frontend/saveCookie', array('plugin' => 'cityselect'), true, $domain, $route);
            $save_url = str_replace(array('http:', 'https:'), '', $save_url);

            $save_data = array('key' => shopCityselectHelper::pushCookies());

            //Сохраняем куки
            $this->response['save_cookie'] = $save_url;
            if (waRequest::cookie('cityselect__show_notifier', false)) {
                $save_data['cityselect__show_notifier'] = waRequest::cookie('cityselect__show_notifier');
            }

            $this->response['save_data'] = http_build_query($save_data);

            //Определяем переход 
            $current_url = empty($params['cityselect__url']) ? '' : $params['cityselect__url'];
            $this->response['redirect_url'] = shopCityselectHelper::getRedirectPageUrl($this->response['redirect'], $params, $current_url);
        }
    }
}
