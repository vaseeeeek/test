<?php

class shopListfeaturesPlugin extends shopPlugin
{
    public function frontendHead()
    {
        $this->addCss('css/frontend_features.css');
        $this->addJs('js/frontend_features.js');
    }

    protected static function getSettingsModel()
    {
        if (!(self::$app_settings_model instanceof shopListfeaturesPluginAppSettingsModel)) {
            self::$app_settings_model = new shopListfeaturesPluginAppSettingsModel();
        }
        return self::$app_settings_model;
    }

    public function saveSettings($settings = array())
    {
        $settlements = array_keys(shopListfeaturesPluginHelper::getSettlements());
        foreach ($settings as $name => $value) {
            if (is_null($value)) {
                unset($this->settings[$name]);
                $res = self::getSettingsModel()->del($this->getSettingsKey(), $name);
            } else {
                $this->settings[$name] = $value;
            }
        }

        self::getSettingsModel()->set($this->getSettingsKey(), $this->settings);
    }

    public static function display($product, $products, $set_id = 1)
    {
        if (wa('shop')->getConfig()->getPluginInfo('listfeatures')) {
            return shopListfeaturesPluginFeatures::display($product, $products, $set_id);
        }
    }
}
