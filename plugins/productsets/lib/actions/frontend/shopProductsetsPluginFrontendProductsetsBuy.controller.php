<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopProductsetsPluginFrontendProductsetsBuyController extends shopProductsetsPluginJsonController
{

    private $items = array();
    private $set = array();
    private $bundle_id = 0;

    public function execute()
    {
        $this->bundle_id = waRequest::post('bundle_id', 0);
        $this->items = waRequest::post('items');

        $set_id = waRequest::post('set_id', 0);
        $type = waRequest::post('type', 'bundle');

        $this->set = (new shopProductsetsPluginModel())->getSet($set_id);

        if ($this->set && $this->items) {
            if ($type == 'userbundle') {
                $this->processUserBundle();
            } else {
                $this->processBundle();
            }
        } else {
            $this->errors[] = _wp('Set is empty');
        }
    }

    private function processBundle()
    {
        if (isset($this->set['bundle']['b' . $this->bundle_id])) {
            $bundle = $this->set['bundle']['b' . $this->bundle_id];
            $sku_ids = waUtils::getFieldValues($this->items, 'sku_id');

            // Проверка доступности набора
            if (!(new shopProductsetsPluginValidation())->isBundleAvailable($bundle)) {
                $this->errors[] = _wp('Set is not available');
                return;
            }
            $data = (new shopProductsetsData())->getProductData($sku_ids)->toArray();

            $include_product = !waRequest::post('include_product', 0, waRequest::TYPE_INT) ? false : (!empty($bundle['settings']['active_product']));
            $this->addItemsToCart($data, $include_product);
            $this->saveItems($this->set['id'], $this->bundle_id, $include_product);
        }
    }

    private function processUserBundle()
    {
        // Проверка доступности набора
        if (!(new shopProductsetsPluginValidation())->isBundleAvailable($this->set['user_bundle'], 'userbundle', $this->set['id'])) {
            $this->errors[] = _wp('Set is not available');
            return;
        }

        // Проверка минимального количества товаров
        if (!empty($this->set['user_bundle']['settings']['min']) && count($this->items) < $this->set['user_bundle']['settings']['min']) {
            $this->errors[] = _wp('Minimal quantity of products is') . ' '.$this->set['user_bundle']['settings']['min'];
            return;
        }

        // Проверка максимального количества товаров
        if (!empty($this->set['user_bundle']['settings']['max']) && count($this->items) > $this->set['user_bundle']['settings']['max']) {
            $this->errors[] = _wp('Maximal quantity of products is') . ' '.$this->set['user_bundle']['settings']['max'];
            return;
        }

        $sku_ids = waUtils::getFieldValues($this->items, 'sku_id');

        $data = (new shopProductsetsData())->getProductData($sku_ids)->toArray();

        $include_product = !waRequest::post('include_product', 0, waRequest::TYPE_INT) ? false : (!empty($this->set['user_bundle']['settings']['active_product']));
        $this->addItemsToCart($data, $include_product);
        $this->saveItems($this->set['id'], 0, $include_product);
    }

    /**
     * Add items to the cart
     *
     * @param array $products_data
     * @param bool $has_active_item
     */
    private function addItemsToCart($products_data, $has_active_item = false)
    {
        $shop_cart = new shopProductsetsPluginCart();

        $cart_items_quantity = $shop_cart->getItemsQuantityBySkuIds(array_keys($products_data));

        foreach ($this->items as $k => $item) {
            // Определяем, достаточно ли у товара количества для заказа
            $count = $products_data[$item['sku_id']]['count'];
            // Если товар уже добавлен в корзину
            if (isset($cart_items_quantity[$item['sku_id']])) {
                $quantity = $cart_items_quantity[$item['sku_id']]['quantity'] + $item['quantity'];
                // Если необходимо учитывать остатки
                if (!wa()->getSetting('ignore_stock_count')) {
                    // Если не хватает остатков, чтобы добавить полное кол-во в корзину
                    if ($count !== null && $quantity > $count) {
                        // Проверяем, возможно у товара достаточно остатков и можно дополнить товар в корзине до состава набора
                        if ($count >= $item['quantity']) {
                            $quantity = $item['quantity'];
                        } else {
                            unset($this->items[$k]);
                            continue;
                        }
                    }
                }
                $shop_cart->setQuantity($cart_items_quantity[$item['sku_id']]['id'], $quantity);
            } else {
                $quantity = $item['quantity'];
                // Если необходимо учитывать остатки
                if (!wa()->getSetting('ignore_stock_count')) {
                    if ($count !== null && $count < $item['quantity']) {
                        unset($this->items[$k]);
                        continue;
                    }
                }
                $shop_cart->addItem(array(
                    'type' => 'product',
                    'product_id' => $products_data[$item['sku_id']]['product_id'],
                    'sku_id' => $item['sku_id'],
                    'quantity' => $quantity
                ));
            }
            $this->items[$k]['product_id'] = $products_data[$item['sku_id']]['product_id'];
            $this->items[$k]['sort'] = $k;
            $this->items[$k]['bundle_item_id'] = $item['_id'];
            // Запоминаем активный товар
            if ($has_active_item && $k === 0) {
                $this->items[$k]['is_active'] = 1;
            }
        }
    }

    /**
     * @param int $set_id
     * @param int $bundle_id
     * @param bool $has_active_item
     */
    private function saveItems($set_id, $bundle_id, $has_active_item)
    {
        if ($this->items) {
            (new shopProductsetsPluginCart())->saveItems($this->items, $set_id, $bundle_id, $has_active_item);
        }
    }

}
