<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopProductsetsPluginBackendActions extends waJsonActions
{
    /**
     * Get product skus in popup window of choosing products
     */
    public function getProductSkusAction()
    {
        $id = waRequest::post("id", 0, waRequest::TYPE_INT);
        $product = new shopProduct($id);
        if ($product) {
            $skus = $product->getSkus();
            $images = $product->getImages();
            $primary_currency = wa()->getConfig()->getCurrency(true);
            foreach ($skus as &$p) {
                $p['sku_id'] = $p['id'];
                $p['id'] = $p['product_id'];
                // Генерируем ссылки для изображений
                if ($p['image_id']) {
                    $p['image'] = shopImage::getUrl($images[$p['image_id']], '96x96');
                }
                $p['sku_name'] = waString::escapeAll($p['name']);
                $p['name'] = waString::escapeAll($product['name']);
                $p['price'] = shop_currency($p['primary_price']);
                if ($p['compare_price']) {
                    $p['compare_price'] = shop_currency($p['compare_price'], $product['currency'], $primary_currency);
                }
                $p['stocks'] = shopHelper::getStockCountIcon($p['count']);
                $p['stocks_with_text'] = shopHelper::getStockCountIcon($p['count'], null, true);
                unset($p);
            }
            $this->response = $skus;
        }
    }

    public function getProductsAction()
    {
        $products_per_page = 50;
        $id = waRequest::post("id");
        $type = waRequest::post("type");
        $page = waRequest::post("page", 1, waRequest::TYPE_INT);
        if ($page < 1) {
            $page = 1;
        }
        $offset = ($page - 1) * $products_per_page;

        $hash = ($type == 'sets' ? 'set' : ($type == 'types' ? 'type' : 'category')) . '/' . $id;
        $collection = new shopProductsCollection($hash);
        $this->response['products'] = $collection->getProducts('*', $offset, $products_per_page);
        if ($this->response['products']) {
            foreach ($this->response['products'] as &$p) {
                // Генерируем ссылки для изображений
                if ($p['image_id']) {
                    $p['image'] = shopImage::getUrl(array('id' => $p['image_id'], 'filename' => $p['image_filename'], 'product_id' => $p['id'], 'ext' => $p['ext']), '96x96');
                }
                $p['label'] = $p['name'];
                $p['stocks'] = shopHelper::getStockCountIcon($p['count']);
                $p['price'] = shop_currency_html($p['price']);
                if ($p['compare_price'] > 0) {
                    $p['compare_price'] = shop_currency_html($p['compare_price']);
                }
                $p['stocks'] = shopHelper::getStockCountIcon($p['count']);
                $p['stocks_with_text'] = shopHelper::getStockCountIcon($p['count'], null, true);
                unset($p['skus']);
            }
        }
        $count = $collection->count();
        $max_page = ceil($count / $products_per_page);
        if ($max_page == $page) {
            $this->response['end'] = true;
        } else {
            $this->response['end'] = false;
            $this->response['page'] = $page + 1;
        }
    }

    /**
     * Load product categories, product sets, product types in Display tab
     */
    public function loadDataAction()
    {
        $data = new shopProductsetsData();
        $type = waRequest::get('type', null);

        $result = array(
            'categories' => array(),
            'sets' => array(),
            'types' => array()
        );

        switch ($type) {
            case 'categories':
                $result['categories'] = $data->getCategoryData()->toHtmlSelectOptions();
                break;
            case 'sets':
                $result['sets'] = $data->getSetData()->toHtmlSelectOptions();
                break;
            case 'types':
                $result['types'] = $data->getTypeData()->toHtmlSelectOptions();
                break;
            default:
                $result['categories'] = $data->getCategoryData()->toHtmlSelectOptions();
                $result['sets'] = $data->getSetData()->toHtmlSelectOptions();
                $result['types'] = $data->getTypeData()->toHtmlSelectOptions();
        }

        $this->response = $result;
    }

    /**
     * Update status of sets in the list
     */
    public function updateStatusAction()
    {
        $status = waRequest::post('status', 0, waRequest::TYPE_INT);
        $id = waRequest::post('id', '', waRequest::TYPE_INT);
        if ($id) {
            if ((new shopProductsetsPluginModel())->updateById($id, array("status" => $status))) {
                $this->response = $status;
            } else {
                $this->errors = 1;
            }
        }
    }

    /**
     * Duplicate sets
     */
    public function duplicateAction()
    {
        $id = waRequest::post('id', '', waRequest::TYPE_INT);
        if ($id) {
            if ($set = (new shopProductsetsPluginModel())->duplicate($id)) {
                $view = new waSmarty3View(wa());
                $view->assign('set', $set);
                $this->response = $view->fetch(wa()->getAppPath('plugins/productsets/templates/actions/backend/include.backend.set.row.html'));
            } else {
                $this->errors = 1;
            }
        }
    }

    /**
     * Delete sets
     */
    public function deleteAction()
    {
        $ids = waRequest::post('ids');
        if ($ids) {
            if ((new shopProductsetsPluginModel())->delete($ids)) {
                $this->response = 1;
            } else {
                $this->errors = 1;
            }
        }
    }

    /**
     * Sort sets in the list
     */
    public function sortSetsAction()
    {
        $ids = waRequest::post('ids');
        if ($ids && is_array($ids)) {
            foreach ($ids as $k => $id) {
                (new shopProductsetsPluginModel())->updateById($id, ['sort' => $k]);
            }
        }
    }

    /**
     * Save frontend template
     */
    public function saveTemplateAction()
    {
        $key = waRequest::post('key');
        $template = waRequest::post('template');

        $copy_template = wa()->getDataPath('plugins/productsets/templates/frontend/' . $key . '.html', false, 'shop');
        waFiles::write($copy_template, $template);
    }

    /**
     * Restore original frontend template
     */
    public function restoreTemplateAction()
    {
        $id = waRequest::post('id');

        $templates = (new shopProductsetsPluginHelper())->getTemplates();
        if (isset($templates[$id]) && !empty($templates[$id]['changed'])) {
            waFiles::delete($templates[$id]['changed'], true);
            $this->response = file_get_contents($templates[$id]['path']);
        }
    }

    /**
     * Import appearance settings from other sets
     */
    public function importAppearanceAction()
    {
        $id = waRequest::post('id');
        $ids = waRequest::post('ids');

        $set = (new shopProductsetsPluginModel())->getSet($id);
        if ($set && !empty($set['settings'][$id]['appearance'])) {
            if (!$ids) {
                $this->response = [
                    'bundle' => !empty($set['settings'][$id]['appearance']['bundle']) ? json_decode($set['settings'][$id]['appearance']['bundle']) : '',
                    'userbundle' => !empty($set['settings'][$id]['appearance']['userbundle']) ? json_decode($set['settings'][$id]['appearance']['userbundle']) : ''
                ];
            } else {
                $settings_model = new shopProductsetsSettingsPluginModel();
                $sql = "INSERT INTO {$settings_model->getTableName()} (`productsets_id`, `field`, `ext`, `value`) VALUES ";
                foreach ($ids as $productsets_id) {
                    $sql .= "('{$productsets_id}', 'appearance', 'bundle', '" . (!empty($set['settings'][$id]['appearance']['bundle']) ? $settings_model->escape($set['settings'][$id]['appearance']['bundle']) : '') . "'),";
                    $sql .= "('{$productsets_id}', 'appearance', 'userbundle', '" . (!empty($set['settings'][$id]['appearance']['userbundle']) ? $settings_model->escape($set['settings'][$id]['appearance']['userbundle']) : '') . "'),";
                    $sql .= "('{$productsets_id}', 'appearance_settings', 'bundle', '" . (!empty($set['settings'][$id]['appearance_settings']['bundle']) ? $settings_model->escape($set['settings'][$id]['appearance_settings']['bundle']) : '') . "'),";
                    $sql .= "('{$productsets_id}', 'appearance_settings', 'userbundle', '" . (!empty($set['settings'][$id]['appearance_settings']['userbundle']) ? $settings_model->escape($set['settings'][$id]['appearance_settings']['userbundle']) : '') . "'),";
                    $sql .= "('{$productsets_id}', 'appearance', 'use_important', '" . (!empty($set['settings'][$id]['appearance']['use_important']) ? 1 : 0) . "'),";
                    $sql .= "('{$productsets_id}', 'layout', 'bundle', '" . (!empty($set['settings'][$id]['layout']['bundle']) ? $settings_model->escape(json_encode($set['settings'][$id]['layout']['bundle'])) : '') . "'),";
                    $sql .= "('{$productsets_id}', 'layout', 'userbundle', '" . (!empty($set['settings'][$id]['layout']['userbundle']) ? $settings_model->escape(json_encode($set['settings'][$id]['layout']['userbundle'])) : '') . "'),";
                }
                $sql = substr($sql, 0, -1);
                $sql .= " ON DUPLICATE KEY UPDATE `value` = VALUES(value)";
                $settings_model->exec($sql);
            }
        }
    }
}