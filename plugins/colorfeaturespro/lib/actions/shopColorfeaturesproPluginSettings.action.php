<?php

class shopColorfeaturesproPluginSettingsAction extends waViewAction
{

    public function codeToHex($feature)
    {
        $colorValueClass = new shopColorValue($feature);
        return $colorValueClass->hex;
    }

    public function execute()
    {
        $pluginSetting = shopColorfeaturesproPluginSettings::getInstance();
        $settings = $pluginSetting->getSettings();
        $pluginSetting->getSettingsCheck($settings);
        $colorModel = new shopColorfeaturesproPluginFeaturesModel();
        $featureModel = new shopFeatureModel();
        $featureNames = $featureModel->getAll('id');
        $colorFeaturesRaw = $colorModel->getAllValues();
        $colorFeatures = [];
        foreach ($colorFeaturesRaw as $feature) {
            $data['code'] = $feature['code'];
            $data['hex'] = $this->codeToHex($feature);
            $data['style'] = $feature['style'];
            $data['value'] = $feature['value'];
            $data['id'] = $feature['id'];
            $colorFeatures[$feature['feature_id']]['name'] = $featureNames[$feature['feature_id']]['name'];
            $colorFeatures[$feature['feature_id']]['values'][] = $data;
        }
        $this->view->assign("settings", $settings);
        $this->view->assign("colorFeatures", $colorFeatures);
    }
}
