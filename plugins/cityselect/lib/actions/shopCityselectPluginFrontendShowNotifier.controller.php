<?php
/**
 * Пользователю показано уведомление о выборе города
 *
 * User: Echo-company
 * Email: info@echo-company.ru
 * Site: https://www.echo-company.ru
 */

class shopCityselectPluginFrontendShowNotifierController extends waJsonController
{
    public function execute()
    {
        //Просто устанавливаем куку
        shopCityselectHelper::setCookie('cityselect__show_notifier', time());

        //Удаляем форсированный показ уведомления
        shopCityselectHelper::setCookie('cityselect__force_notify', null);
    }
}