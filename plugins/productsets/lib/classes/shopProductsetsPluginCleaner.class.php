<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopProductsetsPluginCleaner
{
    /**
     * @param array $category
     */
    public function deleteCategory($category)
    {
        $this->deleteByType('category', $category);
    }

    /**
     * @param array $set
     */
    public function deleteSet($set)
    {
        $this->deleteByType('set', $set);
    }

    /**
     * Delete all useless data after order creation
     */
    public function afterOrderCreate()
    {
        $pc = new shopProductsetsCartPluginModel();
        $sci = new shopCartItemsModel();
        $data = $pc->select('id, productsets_id, code')->fetchAll();
        $cart_items = $sci->select("code")->fetchAll('code');
        if ($data) {
            $pscim = new shopProductsetsCartItemsPluginModel();
            $delete_ids = array();
            $code = waRequest::cookie('shop_cart');
            foreach ($data as $d) {
                if (!isset($cart_items[$d['code']]) || $d['code'] == $code) {
                    $delete_ids[] = $d['id'];
                }
            }
            if ($delete_ids) {
                $sql = "DELETE c, ci FROM {$pc->getTableName()} c
                        LEFT JOIN {$pscim->getTableName()} ci ON c.id=ci.cart_id
                        WHERE c.id IN (?)";
                $pc->exec($sql, [$delete_ids]);
            }
        }
    }

    /**
     * @param array $params
     */
    public function deleteProduct($params)
    {
        $this->deleteProductByType('product', $params);
    }

    /**
     * @param array $sku
     */
    public function deleteProductSku($sku)
    {
        $this->deleteProductByType('sku', $sku);
    }

    /**
     * Delete information about product or sku
     *
     * @param string $type - product|sku
     * @param array $data
     */
    private function deleteProductByType($type, $data)
    {
        if ($type == 'product' && !empty($data['ids'])) {
            $ids = (array) $data['ids'];
        } elseif ($type == 'sku') {
            $ids = (array) $data['id'];
        }
        if (!empty($ids)) {
            $field = $type . '_id';
            // Удаляем принадлежность к наборам
            (new shopProductsetsBundleItemPluginModel())->deleteByField($field, $ids);
            // Удаляем принадлежность к пользовательским наборам
            (new shopProductsetsUserbundleItemPluginModel())->deleteByField($field, $ids);
            // Удаляем принадлежность к пользовательским группам
            (new shopProductsetsUserbundleGroupItemPluginModel())->deleteByField($field, $ids);
            // Удаляем данные в корзине
            (new shopProductsetsCartItemsPluginModel())->deleteByField($field, $ids);
            if ($type == 'product') {
                // Удаляем привязку в Отображении
                $this->deleteSettingsByExt('products', $ids);
            }
        }
    }

    /**
     * Delete by type
     *
     * @param string $type - category|set
     * @param array $data
     */
    private function deleteByType($type, $data)
    {
        if ($type == 'category') {
            $userbundle_field = 'categories';
            $settings_field = ['categories', 'categories_sub'];
        } else {
            $settings_field = $userbundle_field = 'sets';
        }
        if (!empty($data)) {
            $ids = $this->getIdsByType($type, $data);

            // Удаляем привязку в пользовательских наборах
            $userbundle_cats = (new shopProductsetsUserbundleGroupItemPluginModel())->getByField(['type' => $userbundle_field], 'id');
            if ($userbundle_cats) {
                $delete_ids = [];
                foreach ($userbundle_cats as $data) {
                    $settings = json_decode($data['settings'], true);
                    if (!empty($settings[$userbundle_field]) && in_array($settings[$userbundle_field], $ids)) {
                        $delete_ids[] = $data['id'];
                    }
                }
                if ($delete_ids) {
                    (new shopProductsetsUserbundleGroupItemPluginModel())->deleteById($delete_ids);
                }
            }

            // Удаляем привязку в Отображении
            $this->deleteSettingsByExt($settings_field, $ids);
        }
    }

    /**
     * Delete/update settings by ext and ID's to delete
     *
     * @param int|array $ext
     * @param array $delete_ids
     */
    private function deleteSettingsByExt($ext, $delete_ids)
    {
        $settings_model = new shopProductsetsSettingsPluginModel();
        $display_settings = $settings_model->getByField(['ext' => $ext], true);
        if ($display_settings) {
            foreach ($display_settings as $data) {
                $type_ids = json_decode($data['value'], true);
                if (array_intersect($type_ids, $delete_ids)) {
                    $save_ids = array_diff($type_ids, $delete_ids);
                    unset($data['value']);
                    // Если удалены не все элементы, перезаписываем значения
                    if ($save_ids) {
                        $settings_model->updateByField($data, ['value' => json_encode($save_ids)]);
                    } else {
                        $settings_model->deleteByField($data);
                    }
                }
            }
        }
    }

    /**
     * Get ids by type
     *
     * @param string $type - category|set
     * @param array $data
     * @return array
     */
    private function getIdsByType($type, $data)
    {
        $ids = [];
        if ($type == 'category') {
            // Собираем ID подкатегорий
            $cm = new shopCategoryModel();
            $subcategories = $cm->descendants($data['id'], true)->fetchAll();
            if ($subcategories) {
                $ids = array();
                foreach ($subcategories as $s) {
                    $ids[] = $s['id'];
                }
            } else {
                $ids[] = $data['id'];
            }
        } else {
            $ids[] = $data['id'];
        }
        return $ids;
    }
}