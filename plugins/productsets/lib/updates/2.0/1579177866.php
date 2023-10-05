<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

$model = new waModel();

// Переносим старые комплекты
try {
    $model->exec('SELECT 1 FROM shop_productsets_items LIMIT 1');
    $sets = [];
    foreach ($model->query('SELECT * FROM shop_productsets_items') as $r) {
        if (!isset($sets[$r['productsets_id']])) {
            $sets[$r['productsets_id']] = [
                'bundle' => [],
                'userbundle' => [],
                'display' => [],
            ];
        }
        switch ($r['type']) {
            case 'product':
                $sets[$r['productsets_id']]['display']['products'][$r['sku_id']] = $r['id'];
                break;
            case 'category':
                $sets[$r['productsets_id']]['display']['categories'][] = $r['id'];
                break;
            case 'set':
                $sets[$r['productsets_id']]['display']['sets'][] = $r['id'];
                break;
            case 'all':
                $sets[$r['productsets_id']]['display']['all'] = 1;
                break;
            case 'available':
                $sets[$r['productsets_id']]['userbundle'][] = [
                    'product_id' => $r['id'],
                    'sku_id' => $r['sku_id'],
                    'sort_id' => $r['sort_id'],
                ];
                break;
            case 'complete':
                $sets[$r['productsets_id']]['bundle'][] = [
                    'product_id' => $r['id'],
                    'sku_id' => $r['sku_id'],
                    'sort_id' => $r['sort_id'],
                ];
                break;
            case 'onrequest':
                $sets[$r['productsets_id']]['display']['ondemand'] = 1;
                break;
        }
    }
    if ($sets) {
        $productsets_model = new shopProductsetsPluginModel();
        $general_info = $productsets_model->getById(array_keys($sets));
        $bundle_model = new shopProductsetsBundlePluginModel();
        $bundle_item_model = new shopProductsetsBundleItemPluginModel();
        $userbundle_model = new shopProductsetsUserbundlePluginModel();
        $userbundle_item_model = new shopProductsetsUserbundleItemPluginModel();
        $userbundle_group_model = new shopProductsetsUserbundleGroupPluginModel();
        $userbundle_group_item_model = new shopProductsetsUserbundleGroupItemPluginModel();
        $settings_model = new shopProductsetsSettingsPluginModel();
        foreach ($sets as $set_id => $set) {
            if (!empty($general_info[$set_id])) {
                $set['general'] = $general_info[$set_id];

                // Комплекты
                if (!empty($set['bundle'])) {
                    $bundle_id = $bundle_model->insert([
                        'productsets_id' => $set_id,
                        'sort' => 0,
                        'settings' => json_encode([
                            "discount_type" => "common",
                            "avail_discount_type" => "common",
                            "discount" => $set['general']['discount'],
                            "currency" => $set['general']['currency'],
                            "active_product" => $set['general']['include_product'],
                        ])
                    ]);

                    foreach ($set['bundle'] as $item) {
                        $sort_id = !empty($set['general']['include_product']) ? $item['sort_id'] + 1 : $item['sort_id'];
                        $item_id = $bundle_item_model->insert([
                            'type' => 'sku',
                            'bundle_id' => $bundle_id,
                            'product_id' => $item['product_id'],
                            'sku_id' => $item['sku_id'],
                            'sort_id' => $sort_id
                        ]);
                        $bundle_item_model->updateById($item_id, [
                            'settings' => json_encode([
                                '_id' => $item_id,
                                'quantity' => '1',
                                'discount' => '',
                                'currency' => '%'
                            ])
                        ]);
                    }
                    // Активный товар
                    if (!empty($set['general']['include_product'])) {
                        $item_id = $bundle_item_model->insert([
                            'type' => 'product',
                            'bundle_id' => $bundle_id,
                            'product_id' => 0,
                            'sku_id' => 0,
                            'sort_id' => 0
                        ]);
                        $bundle_item_model->updateById($item_id, [
                            'settings' => json_encode([
                                '_id' => $item_id,
                                'quantity' => '1',
                                'discount' => '',
                                'currency' => '%'
                            ])
                        ]);
                    }
                    $settings_model->save([
                        'bundle_status' => '1'
                    ], $set_id);
                }

                // Комплекты пользователя
                if (!empty($set['userbundle']) && !empty($set['general']['usercreate'])) {
                    $userbundle_id = $userbundle_model->insert([
                        'productsets_id' => $set_id,
                        'settings' => json_encode([
                            "discount_type" => "common",
                            "discount" => $set['general']['discount'],
                            "currency" => $set['general']['currency'],
                            "active_product" => $set['general']['include_product'],
                            'show_thumbs' => 1,
                            'max' => !empty($set['general']['count']) ? $set['general']['count'] : 4
                        ])
                    ]);
                    // Активный товар
                    if ($set['general']['include_product']) {
                        $item_id = $userbundle_item_model->insert([
                            'bundle_id' => $userbundle_id,
                        ]);
                        $userbundle_item_model->updateById($item_id, [
                            'settings' => json_encode([
                                '_id' => $item_id,
                                'quantity' => '1',
                                'discount' => '',
                                'currency' => '%'
                            ])
                        ]);
                    }
                    // Создаем группу
                    $group_id = $userbundle_group_model->insert([
                        'userbundle_id' => $userbundle_id,
                        'settings' => json_encode([
                            'description' => '',
                            'display' => 'block',
                            'discount_type' => 'common',
                            'discount' => '',
                            'currency' => '%',
                            'image' => '',
                            'name' => '',
                            'multiple' => 1
                        ])
                    ]);
                    // Наполняем группу товарами
                    foreach ($set['userbundle'] as $item) {
                        $item_id = $userbundle_group_item_model->insert([
                            'group_id' => $group_id,
                            'product_id' => $item['product_id'],
                            'sku_id' => $item['sku_id'],
                            'sort_id' => $item['sort_id']
                        ]);
                        $userbundle_group_item_model->updateById($item_id, [
                            'settings' => json_encode([
                                'quantity' => '1',
                                'discount' => '',
                                'currency' => '%'
                            ])
                        ]);
                    }
                    $settings_model->save([
                        'user_bundle_status' => '1'
                    ], $set_id);
                }

                // Отображение
                if (!empty($set['display'])) {
                    $display_settings = [];
                    // По запросу
                    if (!empty($set['display']['ondemand'])) {
                        $settings_model->save([
                            'ondemand' => 'ondemand'
                        ], $set_id);
                    }

                    if (!empty($set['display']['sets'])) {
                        $display_settings['sets'] = $set['display']['sets'];
                    }
                    if (!empty($set['display']['categories'])) {
                        $display_settings['categories'] = $set['display']['categories'];
                    }
                    if (!empty($set['display']['products'])) {
                        foreach ($set['display']['products'] as $sku_id => $product_id) {
                            $display_settings['products']['s' . $sku_id] = $product_id;
                        }
                    }
                    if ($display_settings) {
                        $settings_model->save([
                            'product' => $display_settings
                        ], $set_id, true);
                    }
                }

                // Скрываем набор
                $productsets_model->updateById($set_id, ['status' => 0]);
            }
        }
    }
} catch (waDbException $e) {
}