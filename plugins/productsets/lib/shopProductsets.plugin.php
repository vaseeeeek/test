<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopProductsetsPlugin extends shopPlugin
{

    public function frontendHead()
    {
        $helper = new shopProductsetsPluginHelper();
        // Информация о валюте
        $currency_info = $helper->getCurrencyInfo();

        $is_debug = waSystemConfig::isDebug();

        // CSS стили
        $inline_css = (new waRuntimeCache('productsets_css'))->get();

        $this->addJs('js/frontend.' . (!$is_debug ? 'min.' : '') . 'js');
        $this->addCss('css/frontend.' . (!$is_debug ? 'min.' : '') . 'css');

        $plugin_id = $this->getId();

        $view = new waSmarty3View(wa());
        $view->assign('plugin_url', $this->getPluginStaticUrl());
        $view->assign('urls', [
            'buy' => wa()->getRouteUrl('shop/frontend/productsetsBuy', array('plugin' => $plugin_id)),
            'load' => wa()->getRouteUrl('shop/frontend/load', array('plugin' => $plugin_id)),
            'getProductSkus' => wa()->getRouteUrl('shop/frontend/getProductSkus', array('plugin' => $plugin_id)),
            'cartPage' => (class_exists('shopCheckoutViewHelper') ? (new shopCheckoutViewHelper())->cartUrl() : wa()->getRouteUrl('shop/frontend/cart'))
        ]);
        $view->assign('currency_info', $currency_info);
        $view->assign('css', $inline_css);
        $view->assign('locale_strings', (new shopProductsetsPluginHelper())->getJsLocaleStrings(false));

        return $view->fetch($this->path . '/templates/actions/frontend/include.head.html');
    }

    public function cartDelete()
    {
        // Если из корзины удалили все товары, обнуляем данные о комплектах
        if (!(new shopCart())->count()) {
            (new shopProductsetsPluginCart())->deleteByCode();
        }
    }

    public function orderCalculateDiscount($params)
    {
        static $workflow;

        $result = array(
            'discount' => 0,
            'description' => '',
            'items' => array()
        );

        // Вычисляем все скидки
        if (!empty($params['order']['items']) && wa()->getEnv() == 'frontend') {

            if ($workflow === null || waRequest::param('igaponov_force_calculate')) {
                $workflow = (new shopProductsetsPluginCore())->calculateDiscount($params['order']['items']);
            }

            $workflow_data = $workflow;
            $result['discount'] = $workflow_data['total_discount'];
            if ($workflow_data['products']) {
                foreach ($params['order']['items'] as $item_id => $item) {
                    // Работаем только с товарами
                    if (empty($item['type']) || (!empty($item['type']) && $item['type'] == 'product')) {
                        if (isset($workflow_data['products'][$item['sku_id']])) {
                            $workflow_product = $workflow_data['products'][$item['sku_id']];
                            // Манипуляции с максимальным значением скидки необходимы на случай, если в корзине несколько позиций с одинаковыми sku_id
                            // В таком случае скидка на артикул распределяется по всем позициям
                            $max_discount = $item['price'] * $item['quantity'];
                            $result['items'][$item_id] = array(
                                'description' => '',
                                'discount' => $max_discount < $workflow_product['total_discount'] ? $max_discount : $workflow_product['total_discount']
                            );
                            $workflow_data['products'][$item['sku_id']]['total_discount'] -= $result['items'][$item_id]['discount'];
                            $result['discount'] -= $result['items'][$item_id]['discount'];
                        }
                    }
                }

                // Описание скидок
                if (!empty($workflow_data['cart_ids'])) {
                    $view = new waSmarty3View(wa());
                    $view->assign('workflow', $workflow_data);
                    $view->assign('items', $params['order']['items']);
                    $result['description'] .= $view->fetch($this->path . '/templates/actions/frontend/include.order.calculate.discount.html');
                }
            }

            $result['discount'] = $result['discount'] < 0 ? 0 : $result['discount'];

            return $result;
        }
    }

    public function backendOrders()
    {
        return ['sidebar_section' => '<link rel="stylesheet" href="' . $this->getPluginStaticUrl(true) . 'css/orders.css' . '">'];
    }

    public function backendProducts()
    {
        $view = new waSmarty3View(wa());
        $view->assign('plugin', $this);
        $view->assign('localeStrings', (new shopProductsetsPluginHelper())->getJsLocaleStrings());
        $view->assign('count', (new shopProductsetsPluginModel())->countAll());

        return array(
            'sidebar_top_li' => $view->fetch($this->path . '/templates/actions/backend/include.backend.products.html')
        );
    }

    public function productDelete($params)
    {
        (new shopProductsetsPluginCleaner())->deleteProduct($params);
    }

    public function productSkuDelete($sku)
    {
        (new shopProductsetsPluginCleaner())->deleteProductSku($sku);
    }

    public function categoryDelete($category)
    {
        (new shopProductsetsPluginCleaner())->deleteCategory($category);
    }

    public function setDelete($set)
    {
        (new shopProductsetsPluginCleaner())->deleteSet($set);
    }

    public function frontendCategory($category)
    {
        return (new shopProductsetsPluginDisplay())->show(null, [
            'category' => $category,
            'is_category_hook' => true
        ]);
    }

    public function frontendProduct($product)
    {
        $output = array('cart' => '', 'block_aux' => '', 'block' => '');
        $result = (new shopProductsetsPluginDisplay())->show($product, [
            'is_product_hook' => true
        ]);
        if (is_array($result)) {
            $output = array_merge($output, $result);
        }

        return $output;
    }

    public function orderActionCreate()
    {
        (new shopProductsetsPluginCleaner())->afterOrderCreate();
    }

    /**
     * @param null|int $set_id
     * @param null|array $product
     * @return string
     * @deprecated
     *
     * Use {shopProductsetsPluginHelper::show()}
     *
     */
    public static function showSet($set_id = null, $product = null)
    {
        if (gettype($set_id) == 'object') {
            $product = $set_id;
            $set_id = null;
        } else {
            $product = ($product && gettype($product) == 'object') ? $product : null;
            $set_id = $set_id ? (int) $set_id : null;
        }
        if (isset($product['data'])) {
            $product = $product['data'];
        }
        return shopProductsetsPluginHelper::show($set_id, $product);
    }

}
