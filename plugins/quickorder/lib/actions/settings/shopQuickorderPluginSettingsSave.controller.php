<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopQuickorderPluginSettingsSaveController extends waJsonController
{

    public function execute()
    {
        $settings = waRequest::post('settings');
        $storefront_settings = waRequest::post('storefront_settings');

        $plugin_status = ifempty($settings, 'status', 0);
        $storefront = ifempty($settings, 'storefront', 'all');

        // Статус плагина
        (new waAppSettingsModel())->set('shop.quickorder', 'status', $plugin_status);

        // Сохраняем настройки
        $model = new shopQuickorderPluginSettingsModel();
        $model->set($storefront, $storefront_settings);
    }

}
