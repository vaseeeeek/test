<?php

$config_path = wa()->getConfig()->getConfigPath('plugins/listfeatures/settlement_features.php', true, 'shop');
if (is_readable($config_path)) {
    $config = is_array($config = include $config_path) ? $config : null;
    if ($config) {
        $new_config = array();
        foreach ($config as $settlement => $feature_ids) {
            $feature_array = array();
            foreach ($feature_ids as $feature_id) {
                $feature_array[$feature_id] = array();
            }
            $new_config[$settlement] = array(
                1 => array(
                    'features' => $feature_array
                )
            );
        }
        $asm = new waAppSettingsModel();
        $asm->set('shop.listfeatures', 'features', serialize($new_config));
    }
    waFiles::delete(dirname($config_path));
}
waFiles::delete(wa()->getAppPath('plugins/listfeatures/lib/config/uninstall.php', 'shop'));
