<?php

$settings = shopSaleskuPlugin::getPluginSettings(shopSaleskuPlugin::GENERAL_STOREFRONT);
$data = array();
$data['settings'] = $settings->getDefault();
// Ставим связанные артикулы для всех типов продуктов
$data['product_type_settings'] = array();
$product_types = $settings->getProductTypeSettings()->getProductTypes();
$product_types_default = $settings->getProductTypeSettings()->getDefault();
if(is_array($product_types) && !empty($product_types)) {
    foreach ($product_types as $v) {
        $data['product_type_settings'][$v['id']] = $product_types_default;
    }
}
// Ставим вид харастеристик
$features_settings = $settings->getFeaturesSettings()->getFeaturesSelectable();
if(is_array($features_settings) && !empty($features_settings)) {
    $data['feature_settings'] = array();
    $default_feature_settings = $settings->getFeaturesSettings()->getDefault();
    foreach ($features_settings as $v) {
        $data['feature_settings'][$v['id']] = $default_feature_settings;
    }
}
$settings->save($data);

