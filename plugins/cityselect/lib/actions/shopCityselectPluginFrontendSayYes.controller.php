<?php
/**
 * Пользователь подтвержил выбор города
 *
 * User: Echo-company
 * Email: info@echo-company.ru
 * Site: https://www.echo-company.ru
 */

class shopCityselectPluginFrontendSayYesController extends waJsonController
{
    public function execute()
    {
        //Просто устанавливаем куку
        shopCityselectHelper::setCookie('cityselect__say_yes', time());
    }
}