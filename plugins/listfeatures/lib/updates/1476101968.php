<?php

/**
 * Update plugin settings: change "$domain_url/$route_url" strings in 'name' field to md5 hashes of "$domain_id/$route_url" strings
 * to ensure that settings can be saved for storefront URLs of ANY lentgh
 * and to keep settings for existing sites on domain name changes (domain ID is now used instead of its URL).
 */
try {
    if (!($routing = include wa()->getConfig()->getPath('config', 'routing')) || !is_array($routing)) {
        throw new Exception();
    }

    $model = new waModel();
    $domain_ids = $model->query(
        'SELECT
            id,
            name
        FROM site_domain
        WHERE name IN(s:domains)',
        array(
            'domains' => array_keys($routing),
        )
    )->fetchAll('name', true);

    if (!$domain_ids) {
        throw new Exception();
    }

    $settlement_hashes = array();
    foreach ($routing as $domain => $routes) {
        if (!is_array($routes)) {
            continue;
        }
        foreach ($routes as $route) {
            if (empty($route['url'])) {
                continue;
            }
            $settlement_hashes[$domain.'/'.$route['url']] = md5($domain_ids[$domain].'/'.$route['url']);
        }
    }

    $asm = new waAppSettingsModel();
    $app_id = 'shop.listfeatures';
    $settings = $asm->getByField(array(
        'app_id' => $app_id
    ), true);

    if (!is_array($settings)) {
        throw new Exception();
    }

    $new_settings = array();
    $old_settings_keys = array();

    foreach ($settings as $setting) {
        if (!isset($settlement_hashes[$setting['name']])) {
            continue;
        }
        $new_settings[] = array(
            'app_id' => $app_id,
            'name'   => $settlement_hashes[$setting['name']],
            'value'  => $setting['value'],
        );
        $old_settings_keys[] = $setting['name'];
    }

    $asm->deleteByField(array(
        'name' => $old_settings_keys,
    ));
    $asm->multipleInsert($new_settings);
} catch (Exception $e) {
    //
}


/**
 * The same for features data table: change settlement strings to hashes
 */
try {
    wa('shop');

    $features_data_model = new shopListfeaturesPluginFeatureModel();
    $features_data = $features_data_model->getAll();

    if (!$features_data) {
        throw new Exception();
    }

    $features_data_model->exec('TRUNCATE '.$features_data_model->getTableName());

    if (empty($settlement_hashes)) {
        throw new Exception();
    }

    $new_features_data = array();
    foreach ($features_data as $feature_data) {
        if (!isset($settlement_hashes[$feature_data['settlement']])) {
            continue;
        }
        $new_data = $feature_data;
        $new_data['settlement'] = $settlement_hashes[$feature_data['settlement']];
        $new_features_data[] = $new_data;
    }

    $features_data_model->multipleInsert($new_features_data);
} catch (Exception $e) {
    //
}
