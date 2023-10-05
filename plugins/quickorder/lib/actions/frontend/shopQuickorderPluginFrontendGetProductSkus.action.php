<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopQuickorderPluginFrontendGetProductSkusAction extends waViewAction
{

    public function execute()
    {
        $product_id = waRequest::post('id');

        $product_model = new shopProductModel();
        $product = $product_model->getById($product_id);
        $stocks = (method_exists('shopHelper', 'getStocks') ? shopHelper::getStocks(true) : shopQuickorderPluginMigrate::getStocks());

        if ($product) {
            $product = new shopProduct($product, true);
            $this->prepareProduct($product);
            $settings = shopQuickorderPluginHelper::getSettings();

            $this->view->assign('currency_info', (new shopQuickorderPluginHelper())->getCurrencyInfo());
            $this->view->assign('stocks', $stocks);
            $this->view->assign('ruble_sign', waRequest::post('ruble_sign', 0));
            $this->view->assign('image_size', waRequest::post('image_size', 0));
            $this->view->assign('version', wa('shop')->getPlugin('quickorder')->getVersion());
            $this->view->assign('plugin_url', wa()->getAppStaticUrl('shop') . "plugins/quickorder");
            $this->view->assign('templates', (new shopQuickorderPluginHelper())->getTemplates($settings));
        }
    }

    protected function prepareProduct(shopProduct $product)
    {
        $skus = $product->skus;
        $has_virt_stocks = method_exists('shopHelper', 'fillVirtulStock');

        foreach ($skus as $sku_id => $sku) {
            // Compare price should be greater than price
            if ($sku['compare_price'] && ($sku['price'] >= $sku['compare_price'])) {
                $skus[$sku_id]['compare_price'] = 0.0;
            }
            if ($has_virt_stocks) {
                // Public virtual stock counts for each SKU
                if (!empty($skus[$sku_id]['stock'])) {
                    $skus[$sku_id]['stock'] = shopHelper::fillVirtulStock($skus[$sku_id]['stock']);
                }
            }
        }
        $product->skus = $skus;

        if ($this->appSettings('limit_main_stock')) {
            $stock_id = waRequest::param('stock_id');
            if ($stock_id) {
                $skus = $product->skus;
                $_update_flag = false;
                foreach ($skus as $sku_id => $sku) {
                    if (isset($sku['stock'][$stock_id])) {
                        $skus[$sku_id]['count'] = $sku['stock'][$stock_id];
                        $_update_flag = true;
                    }
                }
                if ($_update_flag) {
                    $product['skus'] = $skus;
                }
            }
        }

        if (!isset($product->skus[$product->sku_id])) {
            $_skus = $product->skus;
            $product->sku_id = $_skus ? key($_skus) : null;
        }
        if (!$product->skus) {
            $product->skus = array(
                null => array(
                    'name' => '',
                    'sku' => '',
                    'id' => null,
                    'available' => false,
                    'count' => 0,
                    'price' => null,
                    'compare_price' => null,
                    'stock' => array(),
                ),
            );
        }

        if ((float) $product->compare_price <= (float) $product->price) {
            $product->compare_price = 0;
        }

        $skus = $product->skus;
        foreach ($skus as $s_id => $s) {
            $skus[$s_id]['original_price'] = $s['price'];
            $skus[$s_id]['original_compare_price'] = $s['compare_price'];
        }
        $product['original_price'] = $product['price'];
        $product['original_compare_price'] = $product['compare_price'];
        $event_params = array(
            'products' => array(
                $product->id => &$product,
            ),
            'skus' => &$skus,
        );
        wa('shop')->event('frontend_products', $event_params);
        $product['skus'] = $skus;

        $public_stocks = waRequest::param('public_stocks');

        if (!empty($public_stocks)) {
            $count = $this->countOfSelectedStocks($public_stocks, $product->skus);
            if ($count === 0 && !$this->getConfig()->getGeneralSettings('ignore_stock_count')) {
                $product->status = 0;
            }
        }

        $this->view->assign('product', $product);

        if ($product->sku_type == shopProductModel::SKU_TYPE_SELECTABLE) {
            $features_selectable = $product->features_selectable;
            $this->view->assign('features_selectable', $features_selectable);

            $product_features_model = new shopProductFeaturesModel();
            $sku_features = $product_features_model->getSkuFeatures($product->id);

            $sku_selectable = array();
            foreach ($sku_features as $sku_id => $sf) {
                if (!isset($product->skus[$sku_id])) {
                    continue;
                }
                $sku_f = "";
                foreach ($features_selectable as $f_id => $f) {
                    if (isset($sf[$f_id])) {
                        $sku_f .= $f_id . ":" . $sf[$f_id] . ";";
                    }
                }
                $sku = $product->skus[$sku_id];
                $sku_selectable[$sku_f] = array(
                    'id' => $sku_id,
                    'name' => $sku['name'],
                    'price' => (float) shop_currency($sku['price'], $product['currency'], null, false),
                    'available' => $product->status && $sku['available'] &&
                        ($this->getConfig()->getGeneralSettings('ignore_stock_count') || $sku['count'] === null || $sku['count'] > 0),
                    'image_id' => (int) $sku['image_id'],
                );
                $sku_selectable[$sku_f]['count'] = $sku_selectable[$sku_f]['available'] && $this->getConfig()->getGeneralSettings('ignore_stock_count') && $sku['count'] > 0 ? $sku['count'] : '';

                if ($sku['compare_price']) {
                    $sku_selectable[$sku_f]['compare_price'] = (float) shop_currency($sku['compare_price'], $product['currency'], null, false);
                }
            }
            $product['sku_features'] = ifset($sku_features[$product->sku_id], array());
            $this->view->assign('sku_features_selectable', $sku_selectable);
        }
    }

    /**
     * @param $public_stocks
     * @param $skus
     * @return int|null
     */
    protected function countOfSelectedStocks($public_stocks, $skus)
    {
        $count = null;
        foreach ($skus as $sku) {
            foreach ($sku['stock'] as $key => $count_stock) {
                if (in_array($key, $public_stocks)) {
                    if ($count_stock === null) {
                        return null;
                    }
                    $count += $count_stock;
                }
            }
        }

        return $count;
    }

}