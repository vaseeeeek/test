<?php

//remove PHP files
foreach (array(
    'shopListfeaturesPluginBackendLoc.action.php',
    'shopListfeaturesPluginBackendLoc.controller.php',
    'shopListfeaturesPluginFrontendFeatureValue.action.php',
    'shopListfeaturesPluginBackendFeatureOptions.action.php',
    'shopListfeaturesPluginBackendFeatureOptionsSave.controller.php',
    'shopListfeaturesPluginBackendSetDelete.controller.php',
    'shopListfeaturesPluginBackendSetOptions.action.php',
    'shopListfeaturesPluginBackendSettingsSave.controller.php',
    'shopListfeaturesPluginBackendSettlementOptions.action.php',
    'shopListfeaturesPluginBackendTemplateDelete.controller.php',
    'shopListfeaturesPluginBackendTemplateSave.controller.php',
    'shopListfeaturesPluginBackendTemplateView.controller.php',
    'shopListfeaturesPluginSettings.action.php',
) as $file) {
    waFiles::delete(wa()->getAppPath('plugins/listfeatures/lib/actions/'.$file, 'shop'));
}

//remove templates
foreach (array(
    'BackendDefaultTemplate.html',
    'BackendFeatureOptions.html',
    'BackendSetOptions.html',
    'BackendSettlementOptions.html',
) as $file) {
    waFiles::delete(wa()->getAppPath('plugins/listfeatures/templates/actions/backend/'.$file, 'shop'));
}

//add new table
$model = new waModel();
try {
    $model->exec('CREATE TABLE IF NOT EXISTS `shop_listfeatures_feature` (
      `settlement` varchar(255) NOT NULL,
      `set_id` int(10) UNSIGNED NOT NULL,
      `feature_id` int(10) UNSIGNED NOT NULL,
      `meta_keywords` text NOT NULL,
      `meta_description` text NOT NULL,
      PRIMARY KEY (`settlement`,`set_id`,`feature_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8;');
} catch (Exception $e) {
    //
}
