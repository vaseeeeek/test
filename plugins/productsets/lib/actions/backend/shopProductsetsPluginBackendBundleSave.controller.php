<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopProductsetsPluginBackendBundleSaveController extends waJsonController
{
    private $item_model;
    private $user_item_model;
    private $group_item_model;

    public function preExecute()
    {
        $this->item_model = new shopProductsetsBundleItemPluginModel();
        $this->user_item_model = new shopProductsetsUserbundleItemPluginModel();
        $this->group_item_model = new shopProductsetsUserbundleGroupItemPluginModel();
    }

    public function execute()
    {
        if ($post_settings = waRequest::post('settings')) {
            $id = waRequest::post('id', 0, waRequest::TYPE_INT);
            $settings = array(
                'general' => json_decode($post_settings['general'], true),
                'bundle' => json_decode($post_settings['bundle'], true),
                'user_bundle' => json_decode($post_settings['user_bundle'], true),
                'display' => json_decode($post_settings['display'], true),
                'text' => json_decode($post_settings['text'], true),
                'appearance' => $post_settings['appearance'],
                'appearance_settings' => $post_settings['appearance_settings'],
                'other' => json_decode($post_settings['other'], true),
                'settings' => json_decode($post_settings['settings'], true),
            );

            $settings['params'] = ifset($settings['general'], 'params', []);
            unset($settings['general']['params']);

            $psm = new shopProductsetsPluginModel($id);

            try {

                // Сохраняем основные данные комплекта
                if ($id = $psm->save($settings['general'])) {

                    $pbm = new shopProductsetsBundlePluginModel();
                    $settings_model = new shopProductsetsSettingsPluginModel();

                    // Активность комплектов
                    $settings_model->save([
                        'bundle_status' => waRequest::post('bundle_status', 0, waRequest::TYPE_INT),
                        'user_bundle_status' => waRequest::post('user_bundle_status', 0, waRequest::TYPE_INT),
                    ], $id);

                    // Текстовые блоки
                    $settings_model->save($settings['text'], $id);

                    // Бандлы
                    if ($settings['bundle']) {
                        foreach ($settings['bundle'] as $k => $b) {
                            $bundle = $this->prepareBundleData($b, $id, $k);
                            $bundle_id = $pbm->save($bundle);
                            // Сохраняем активный товар
                            if (!empty($b['active'])) {
                                $active = $this->prepareActiveProduct($b['active'], $bundle_id);
                                $this->item_model->save($active);
                            }
                            // Сохраняем товары
                            if (!empty($b['items'])) {
                                $this->saveItems($b['items'], $bundle_id, ref(!empty($b['active']) ? 1 : 0));
                            }

                            $this->item_model->clean($bundle_id);
                        }
                        $pbm->clean($id);
                    } else {
                        $pbm->deleteBySetId($id);
                    }

                    // Бандлы для пользователей
                    $pum = new shopProductsetsUserbundlePluginModel();
                    if ($settings['user_bundle']) {
                        $user_bundle = $this->prepareBundleData($settings['user_bundle'], $id);
                        $user_bundle_id = $pum->save($user_bundle);
                        $sort_id = 0;

                        // Сохраняем активный товар
                        if (!empty($settings['user_bundle']['active'])) {
                            $active = $this->prepareActiveProduct($settings['user_bundle']['active'], $user_bundle_id);
                            $this->user_item_model->save($active);
                            $sort_id = 1;
                        }

                        // Сохраняем обязательные товары
                        if (!empty($settings['user_bundle']['required'])) {
                            $this->saveItems($settings['user_bundle']['required'], $user_bundle_id, $sort_id, 'userbundle');
                        }

                        // Сохраняем группы
                        $pugm = new shopProductsetsUserbundleGroupPluginModel();
                        if (!empty($settings['user_bundle']['groups'])) {
                            foreach ($settings['user_bundle']['groups'] as $k => $gr) {
                                $group = array(
                                    'id' => !empty($gr['id']) ? $gr['id'] : 0,
                                    'userbundle_id' => $user_bundle_id,
                                    'sort_id' => $k,
                                    'settings' => json_encode($gr['settings'], JSON_UNESCAPED_UNICODE)
                                );
                                $group_id = $pugm->save($group);

                                // Сохраняем товары
                                if (!empty($gr['items'])) {
                                    $this->saveItems($gr['items'], $group_id, $sort_id, 'group');
                                }

                                // Сохраняем типы
                                if (!empty($gr['types'])) {
                                    $this->saveTypes($gr['types'], $group_id, $sort_id);
                                }
                                $this->group_item_model->clean($group_id);
                            }
                        }

                        $pugm->clean($user_bundle_id);
                        $this->user_item_model->clean($user_bundle_id);
                    } else {
                        $pum->deleteBySetId($id);
                    }

                    // Формируем массив для передачи в шаблон
                    $data_class = (new shopProductsetsData())->getProductData();
                    $set = $psm->getSet($id);
                    $sku_ids = $data_class->collectProductSkuIds($set, true);

                    // Отображение
                    if ($settings['display']) {
                        // Витрины сохраняем отдельно
                        $storefronts = array('all' => 1);
                        if (isset($settings['display']['storefronts'])) {
                            $storefronts = $settings['display']['storefronts'];
                            unset($settings['display']['storefronts']);
                        }
                        $stm = new shopProductsetsStorefrontPluginModel();
                        $stm->save($id, $storefronts, $settings['display']['storefront_operator']);
                        unset($settings['display']['storefront_operator']);

                        // Добавляем к отображению товары, которые используются в наборах
                        if (!empty($settings['display']['show_on_set_products']) && $sku_ids) {
                            foreach ($sku_ids as $s_id => $p_id) {
                                $settings['display']['product']['products']['s' . $s_id] = $p_id;
                            }
                        }

                        $settings_model->deleteDisplaySettings($id);
                        $settings_model->save($settings['display'], $id, true);
                    }

                    // Внешний вид
                    if ($settings['appearance']) {
                        $settings_model->save(['appearance' => $settings['appearance']], $id);
                    }
                    if ($settings['appearance_settings']) {
                        $settings_model->save(['appearance_settings' => $settings['appearance_settings']], $id);
                    }


                    // Остальные настойки
                    if ($settings['other']) {
                        $settings_model->deleteOtherSettings($id);
                        $other_settings = ['other' => $settings['other']];
                        $settings_model->save($other_settings, $id, true);
                    }

                    // Другие настройки
                    if ($settings['settings']) {
                        // Настройки макета
                        if (!empty($settings['settings']['settings']['layout'])) {
                            $settings_model->save(['layout' => $settings['settings']['settings']['layout']], $id, true);
                            unset($settings['settings']['settings']['layout']);
                        }
                        $settings_model->save($settings['settings']['settings'], $id);
                    }

                    // Дополнительные параметры
                    (new shopProductsetsParamsPluginModel())->save($id, $settings['params']);

                    if ($sku_ids) {
                        $set = (new shopProductsetsProductData(array_keys($sku_ids)))->normalizeProducts($set);
                    }
                    $this->response = $set;
                }
            } catch (Exception $e) {
                waLog::log($e->getMessage(), 'shop_productsets.log');
                $this->errors['messages'][] = _wp('Something wrong. Contact plugin developer');
            }
        }
    }

    /**
     * Save bundle items and alternative items
     *
     * @param array $items
     * @param int $bundle_id
     * @param int $sort_id
     * @param int $parent_id
     * @param string $type - bundle|userbundle|group
     */
    private function saveItems($items, $bundle_id, &$sort_id, $type = 'bundle', $parent_id = 0)
    {
        foreach ($items as $k => $item) {
            $bundle_item = array(
                'id' => !empty($item['_id']) ? $item['_id'] : 0,
                'bundle_id' => $bundle_id,
                'product_id' => $item['id'],
                'sku_id' => $item['sku_id'],
                'parent_id' => $parent_id,
                'type' => $item['type'],
                'sort_id' => $sort_id,
                'settings' => json_encode($item['item'], JSON_UNESCAPED_UNICODE)
            );
            if ($type == 'group') {
                unset($bundle_item['bundle_id'], $bundle_item['parent_id']);
                $bundle_item['group_id'] = $bundle_id;
            }
            $sort_id++;
            switch ($type) {
                case 'userbundle':
                    $model = $this->user_item_model;
                    break;
                case 'group':
                    $model = $this->group_item_model;
                    break;
                default:
                    $model = $this->item_model;
            }
            $item_id = $model->save($bundle_item);
            if (!empty($item['alternative'])) {
                $this->saveItems($item['alternative'], $bundle_id, $sort_id, $type, $item_id);
            }
        }
    }

    private function saveTypes($types, $group_id, &$sort_id)
    {
        foreach ($types as $k => $item) {
            $bundle_item = array(
                'id' => !empty($item['_id']) ? $item['_id'] : 0,
                'group_id' => $group_id,
                'product_id' => ifset($item['id'], 0),
                'sku_id' => ifset($item['sku_id'], 0),
                'type' => $item['type'],
                'sort_id' => $sort_id,
                'settings' => json_encode($item, JSON_UNESCAPED_UNICODE)
            );
            $sort_id++;
            $this->group_item_model->save($bundle_item);
        }
    }

    /**
     * Prepare active product for saving
     *
     * @param array $product
     * @param int $bundle_id
     * @return array
     */
    private function prepareActiveProduct($product, $bundle_id)
    {
        return array(
            'id' => !empty($product['_id']) ? $product['_id'] : 0,
            'bundle_id' => $bundle_id,
            'settings' => json_encode($product, JSON_UNESCAPED_UNICODE),
            'sort_id' => 0
        );
    }

    /**
     * Prepare bundle data for saving
     *
     * @param array $bundle
     * @param int $productsets_id
     * @param int $sort_id
     * @return array
     */
    private function prepareBundleData($bundle, $productsets_id, $sort_id = 0)
    {
        // Подготавливаем дополнительные параметры
        $params = [];
        if (!empty($bundle['settings']['params']['name'])) {
            foreach ($bundle['settings']['params']['name'] as $k => $param) {
                if (!empty($param) && isset($bundle['settings']['params']['value'][$k])) {
                    $params[$param] = $bundle['settings']['params']['value'][$k];
                }
            }
        }
        $bundle['settings']['params'] = $params;

        return array(
            'id' => !empty($bundle['id']) ? $bundle['id'] : 0,
            'productsets_id' => $productsets_id,
            'sort_id' => $sort_id,
            'settings' => json_encode($bundle['settings'], JSON_UNESCAPED_UNICODE)
        );
    }
}