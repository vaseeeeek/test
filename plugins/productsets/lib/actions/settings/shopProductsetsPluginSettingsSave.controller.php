<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopProductsetsPluginSettingsSaveController extends waJsonController
{

    public function execute()
    {
        $settings = waRequest::post('settings');

        foreach ($settings as $name => $value) {
            (new waAppSettingsModel())->set('shop.productsets', $name, is_array($value) ? json_encode($value) : $value);
        }
    }

}
