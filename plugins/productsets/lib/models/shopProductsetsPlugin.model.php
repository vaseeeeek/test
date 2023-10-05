<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopProductsetsPluginModel extends waModel
{

    protected $table = 'shop_productsets';
    private $set_id;

    public function __construct($id = null)
    {
        parent::__construct();
        if ($id) {
            $this->set_id = (int) $id;
        }
    }

    /**
     * Get all sets
     *
     * @param array $filter
     * @return array
     */
    public function getSets($filter = array())
    {
        $formatted_sets = array();

        $sql = "SELECT ps.* FROM {$this->table} ps WHERE 1";

        $is_frontend = wa()->getEnv() == 'frontend';
        if ($is_frontend) {
            $sql .= " AND ps.status = '1'";

            if (!empty($filter['ids'])) {
                $sql .= " AND ps.id IN ('" . implode("','", $this->escape((array) $filter['ids'], 'int')) . "')";
            }

            $sql .= ' ORDER BY ps.sort ASC';
        } else {
            if (isset($filter['status'])) {
                $sql .= " AND ps.status = '" . (int) $filter['status'] . "'";
            }

            if (!empty($filter['id'])) {
                $sql .= " AND ps.id = '" . (int) $filter['id'] . "'";
            }

            $sql .= ' ORDER BY ps.sort ASC';

            if (isset($filter['limit'])) {
                $sql .= " LIMIT " . $filter['limit']['offset'] . "," . $filter['limit']['length'];
            }
        }

        $sets = $this->query($sql)->fetchAll('id');
        if ($sets) {
            $pbm = new shopProductsetsBundlePluginModel();
            $pum = new shopProductsetsUserbundlePluginModel();
            $sm = new shopProductsetsSettingsPluginModel();
            $validation = new shopProductsetsPluginValidation();

            $set_ids = array_keys($sets);

            // Ограничения по витринам для комплектов
            $storefront_limitations = (new shopProductsetsPluginHelper())->getSetStorefrontLimitations($set_ids);

            // Дополнительные параметры
            $params = (new shopProductsetsParamsPluginModel())->getParams($set_ids);

            foreach ($sets as $set) {
                // Проверка ограничения по витринам
                if ($is_frontend && !$validation->isSetAvailableForStorefront($set['id'], $storefront_limitations)) {
                    continue;
                }

                $formatted_sets[$set['id']] = array(
                    'id' => $set['id'],
                    'general' => $set
                );

                // Бандлы
                $formatted_sets[$set['id']]['bundle'] = $pbm->getItemsBySetId($set['id']);

                // Бандлы для пользователей
                $formatted_sets[$set['id']]['user_bundle'] = $pum->getUserItemsBySetId($set['id']);

                // Настройки
                $formatted_sets[$set['id']]['settings'] = $sm->getSettings((int) $set['id'], null, true);

                // Дополнительные параметры
                $formatted_sets[$set['id']]['params'] = ifset($params, $set['id'], []);
            }
        }
        return $formatted_sets;
    }

    /**
     * Get information about one set
     *
     * @param int $id
     * @return array
     */
    public function getSet($id)
    {
        $set = array();
        if ($general = $this->getById($id)) {
            $set['id'] = $id;

            if (wa()->getEnv() == 'frontend') {
                // Проверка статуса
                if (!$general['status']) {
                    return array();
                }

                // Проверка ограничения по витринам
                $storefront_limitations = (new shopProductsetsPluginHelper())->getSetStorefrontLimitations($set['id']);
                if (!(new shopProductsetsPluginValidation())->isSetAvailableForStorefront($set['id'], $storefront_limitations)) {
                    return [];
                }
            }

            $set['general'] = $general;
            // Бандлы
            $pbm = new shopProductsetsBundlePluginModel();
            $set['bundle'] = $pbm->getItemsBySetId($id);

            // Бандлы для пользователей
            $pum = new shopProductsetsUserbundlePluginModel();
            $set['user_bundle'] = $pum->getUserItemsBySetId($id);

            // Настройки
            $sm = new shopProductsetsSettingsPluginModel();
            $set['settings'] = $sm->getSettings($id, null, true);

            // Дополнительные параметры
            $set['params'] = (new shopProductsetsParamsPluginModel())->getParams($id);

            // Витрины
            $stm = new shopProductsetsStorefrontPluginModel();
            $storefronts = $stm->getByField('productsets_id', $id, true);
            $set['storefront'] = array('all' => 1);
            if ($storefronts) {
                $set['storefront'] = array();
                foreach ($storefronts as $storefront) {
                    $set['storefront'][$storefront['storefront']] = $storefront['storefront'];
                    $set['storefront_operator'] = $storefront['operator'];
                }
            }
        }
        return $set;
    }

    /**
     * Get missing data for the set.
     * Use this function after getSets(..., $full_info), where param $full_info = false
     *
     * @param array $set
     * @return array
     */
    public function fillMissingData($set)
    {
        if (empty($set['bundle']) && !empty($set['settings']['bundle_status'])) {
            // Бандлы
            $pbm = new shopProductsetsBundlePluginModel();
            $set['bundle'] = $pbm->getItemsBySetId($set['id']);
        }
        if (empty($set['user_bundle']) && !empty($set['settings']['user_bundle_status'])) {
            // Бандлы для пользователей
            $pum = new shopProductsetsUserbundlePluginModel();
            $set['user_bundle'] = $pum->getUserItemsBySetId($set['id']);
        }

        return $set;
    }

    /**
     * Insert / update data
     *
     * @param array $data
     * @return int|false
     */
    public function save($data)
    {
        if ($this->set_id) {
            $this->updateById($this->set_id, $data);
            return $this->set_id;
        } else {
            return $this->insert($data);
        }
    }

    public function duplicate($set_id)
    {
        $model = new shopProductsetsPluginModel();
        if ($set = $model->getSet($set_id)) {
            unset($set['general']['id']);
            $set['general']['status'] = 0;
            $set['general']['name'] .= ' ' . _wp('(copy)');
            // Основные данные
            $id = $model->insert($set['general']);
            $set['id'] = $set['general']['id'] = $id;

            // Комплекты
            $bundle_model = new shopProductsetsBundlePluginModel();
            $bundles = $bundle_model->getByField('productsets_id', $set_id, true);
            if ($bundles) {
                $bundle_ids = [];
                foreach ($bundles as $k => $bundle) {
                    $bundle_id = $bundle['id'];
                    $bundle['productsets_id'] = $set['id'];
                    unset($bundle['id']);
                    $new_id = $bundle_model->insert($bundle);
                    $bundle_ids[$bundle_id] = $new_id;
                }

                $bundle_item_model = new shopProductsetsBundleItemPluginModel();
                $bundle_items = $bundle_item_model->getByField('bundle_id', array_keys($bundle_ids), true);
                if ($bundle_items) {
                    $bundle_item_ids = $find_parent = [];
                    foreach ($bundle_items as $k => $bundle_item) {
                        $bundle_item_id = $bundle_item['id'];
                        $parent_id = $bundle_item['parent_id'];
                        unset($bundle_item['id']);
                        if ($parent_id && !empty($bundle_item_ids[$parent_id])) {
                            $bundle_item['parent_id'] = $bundle_item_ids[$parent_id];
                        }
                        $bundle_item['bundle_id'] = $bundle_ids[$bundle_item['bundle_id']];
                        $new_item_id = $bundle_item_model->insert($bundle_item);
                        if (!empty($bundle_item['settings'])) {
                            $bundle_item_model->updateById($new_item_id, ['settings' => str_replace('"_id":"' . $bundle_item_id . '"', '"_id":"' . $new_item_id . '"', $bundle_item['settings'])]);
                        }
                        if ($parent_id && empty($bundle_item_ids[$parent_id])) {
                            $find_parent[$new_item_id] = $parent_id;
                        }
                        $bundle_item_ids[$bundle_item_id] = $new_item_id;
                    }
                    if ($find_parent) {
                        foreach ($find_parent as $id => $parent_id) {
                            $new_parent_id = !empty($bundle_item_ids[$parent_id]) ? $bundle_item_ids[$parent_id] : 0;
                            $bundle_item_model->updateById($id, ['parent_id' => $new_parent_id]);
                        }
                    }
                }
            }

            // Комплекты пользователя
            $userbundle_model = new shopProductsetsUserbundlePluginModel();
            $userbundle = $userbundle_model->getByField('productsets_id', $set_id);
            if ($userbundle) {
                $userbundle_id = $userbundle['id'];
                $userbundle['productsets_id'] = $set['id'];
                unset($userbundle['id']);
                $new_userbundle_id = $userbundle_model->insert($userbundle);

                $userbundle_item_model = new shopProductsetsUserbundleItemPluginModel();
                $userbundle_items = $userbundle_item_model->getByField('bundle_id', $userbundle_id, true);
                if ($userbundle_items) {
                    foreach ($userbundle_items as $k => $bundle_item) {
                        $bundle_item_id = $bundle_item['id'];
                        unset($bundle_item['id']);
                        $bundle_item['bundle_id'] = $new_userbundle_id;
                        $new_item_id = $userbundle_item_model->insert($bundle_item);
                        if (!empty($bundle_item['settings'])) {
                            $userbundle_item_model->updateById($new_item_id, ['settings' => str_replace('"_id":"' . $bundle_item_id . '"', '"_id":"' . $new_item_id . '"', $bundle_item['settings'])]);
                        }
                    }
                }

                $group_model = new shopProductsetsUserbundleGroupPluginModel();
                $groups = $group_model->getByField('userbundle_id', $userbundle_id, true);
                if ($groups) {
                    $groups_ids = [];
                    foreach ($groups as $k => $group) {
                        $group_id = $group['id'];
                        $group['userbundle_id'] = $new_userbundle_id;
                        unset($group['id']);
                        $new_group_id = $group_model->insert($group);
                        $groups_ids[$group_id] = $new_group_id;
                    }

                    $group_item_model = new shopProductsetsUserbundleGroupItemPluginModel();
                    $group_items = $group_item_model->getByField('group_id', array_keys($groups_ids), true);
                    if ($group_items) {
                        foreach ($group_items as $k => $group_item) {
                            unset($group_item['id']);
                            $group_item['group_id'] = $groups_ids[$group_item['group_id']];
                            $group_item_model->insert($group_item);
                        }
                    }
                }
            }

            // Настройки
            $this->duplicateTable(new shopProductsetsSettingsPluginModel(), $set_id, $set['id']);
            // Витрины
            $this->duplicateTable(new shopProductsetsStorefrontPluginModel(), $set_id, $set['id']);
            // Дополнительные параметры
            $this->duplicateTable(new shopProductsetsParamsPluginModel(), $set_id, $set['id']);

            return $model->getSet($set['id']);
        }
        return [];
    }

    /**
     * Duplicate table data
     *
     * @param shopProductsetsModel $model
     * @param int $from_id
     * @param int $to_id
     */
    private function duplicateTable($model, $from_id, $to_id)
    {
        $data = $model->getByField('productsets_id', $from_id, true);
        if ($data) {
            $new_data = [];
            foreach ($data as $k => $d) {
                $new_data[$k] = $d;
                $new_data[$k]['productsets_id'] = $to_id;
            }
            $model->multipleInsert($new_data);
        }
    }

    /**
     * Delete
     *
     * @param array[int]|int $ids
     * @return boolean
     */
    public function delete($ids)
    {
        if (!empty($ids)) {
            if (is_array($ids)) {
                $set_ids = "IN ('" . implode("','", $this->escape($ids, 'int')) . "')";
            } else {
                $set_ids = "='" . (int) $ids . "'";
            }
            $bundle_model = new shopProductsetsBundlePluginModel();
            $bundle_item_model = new shopProductsetsBundleItemPluginModel();
            $cart_model = new shopProductsetsCartPluginModel();
            $cart_items_model = new shopProductsetsCartItemsPluginModel();
            $settings_model = new shopProductsetsSettingsPluginModel();
            $params_model = new shopProductsetsParamsPluginModel();
            $storefront_model = new shopProductsetsStorefrontPluginModel();
            $userbundle_model = new shopProductsetsUserbundlePluginModel();
            $userbundle_item_model = new shopProductsetsUserbundleItemPluginModel();
            $userbundle_group_model = new shopProductsetsUserbundleGroupPluginModel();
            $userbundle_group_item_model = new shopProductsetsUserbundleGroupItemPluginModel();

            $sql = "DELETE ps, psbm, psbim, pscm, pscim, pssm, psstm, psum, psuim, psugm, psugim  FROM {$this->table} ps
                LEFT JOIN {$bundle_model->getTableName()} psbm ON ps.id = psbm.productsets_id
                LEFT JOIN {$bundle_item_model->getTableName()} psbim ON psbm.id = psbim.bundle_id
                LEFT JOIN {$cart_model->getTableName()} pscm ON ps.id = pscm.productsets_id
                LEFT JOIN {$cart_items_model->getTableName()} pscim ON pscm.id = pscim.cart_id
                LEFT JOIN {$settings_model->getTableName()} pssm ON ps.id = pssm.productsets_id
                LEFT JOIN {$params_model->getTableName()} pspm ON ps.id = pspm.productsets_id
                LEFT JOIN {$storefront_model->getTableName()} psstm ON ps.id = psstm.productsets_id
                LEFT JOIN {$userbundle_model->getTableName()} psum ON ps.id = psum.productsets_id
                LEFT JOIN {$userbundle_item_model->getTableName()} psuim ON psum.id = psuim.bundle_id
                LEFT JOIN {$userbundle_group_model->getTableName()} psugm ON psum.id = psugm.userbundle_id
                LEFT JOIN {$userbundle_group_item_model->getTableName()} psugim ON psugm.id = psugim.group_id
                WHERE ps.id $set_ids";
            return $this->exec($sql);
        }
        return true;
    }

}
