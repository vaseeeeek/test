<?php
/**
 * Сохранение кукие при переходу между витрин
 *
 * User: Echo-company
 * Email: info@echo-company.ru
 * Site: https://www.echo-company.ru
 */

class shopCityselectPluginFrontendSaveCookieController extends waJsonController
{
    public function execute()
    {

        $key = waRequest::get('key', '', waRequest::TYPE_STRING);

        if (empty($key)) {
            return;
        }

        $cookies = shopCityselectCookiesModel::popCookies($key);
        if (!empty($cookies)) {
            foreach ($cookies as $key => $value) {
                shopCityselectHelper::setCookie($key, $value);
            }
        }
    }
}