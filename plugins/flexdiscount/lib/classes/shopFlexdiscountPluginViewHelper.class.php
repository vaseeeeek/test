<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopFlexdiscountPluginViewHelper extends waPluginViewHelper
{

    /**
     * Get information about cart item. Specify $field to get certain data.
     *
     * @param array $item
     * @param string $field = price|compare_price|discount
     * @param array $params = [
     *                  'class' => '', // Additional CSS class to block
     *                  'html_before' => '',
     *                  'html_after' => '',
     *                  'multiply' => 1, // Multiply price and quantity
     *                  'ruble_sign' => 'string', // Can be 'string'|'symbol'
     *                  'return_clear_value' => 0, // If set 1, than block will be static. HTML before/after will be ignored,
     *                  'ignore_hide' => 0, // Ignore default behaviour of hiding
     *                  'tag' => 'span', // Wrapper tag
     *                  'remove_loader' => 0, // Should we display loader or not
     *              ]
     * @return string
     */
    public function cartItem($item, $field, $params = [])
    {
        static $block_id = 0;

        $params += [
            'class' => '',
            'html_before' => '',
            'html_after' => '',
            'multiply' => 1,
            'ruble_sign' => 'string',
            'return_clear_value' => 0,
            'ignore_hide' => 0,
            'tag' => 'span',
            'remove_loader' => 0
        ];

        if (!empty($item)) {
            $item_info = $this->getCartItemDiscountInfo($item);

            if (!isset($item_info[$field])) {
                return '';
            }

            $clear_value = $item_info[$field] * ($params['multiply'] ? $item_info['quantity'] : 1);
            if ($params['return_clear_value']) {
                return $clear_value;
            }

            $functions = shopFlexdiscountApp::getFunction();

            $block_id += 1;

            $value = $params['ruble_sign'] === 'symbol' ? $functions->shop_currency_html($clear_value, true) : $functions->shop_currency($clear_value, true);

            $hide = 0;
            if (!$params['ignore_hide'] && $field !== 'price' && $clear_value <= 0) {
                $hide = 1;
            }

            $tag = waString::escapeAll(strip_tags($params['tag']));
            return '<' . $tag . ' class="flexdiscount-cart-item cart-id-' . $item['id'] . ($params['class'] ? ' ' . waString::escapeAll($params['class']) : '') . '" 
                          data-block-id="' . $block_id . '"
                          data-cart-id="' . $item['id'] . '"  
                          data-field="' . $field . '"
                          data-params="' . waString::escapeAll(json_encode($params)) . '"
                          ' . ($hide ? 'style="display: none"' : '') . '>' . $params['html_before'] . $value . $params['html_after'] . '</' . $tag . '>';
        }
        return '';
    }

    /**
     * Get discount information about cart items.
     * Use in multi-step checkout
     *
     * @param array $product
     * @return array
     */
    private function getCartItemDiscountInfo($product)
    {
        // Массив обработанных товаров
        $cart_products = shopFlexdiscountApp::get("runtime.cart_products", []);

        // Если плагин будет отключен, то выведется обычная цена.
        // Это сделано на случай, если пользователь заменит стандартный вывод цены в шаблоне
        if (!empty($product)) {
            $functions = shopFlexdiscountApp::getFunction();
            $price = $product_price = $functions->shop_currency($product['price'], $product['currency'], null, false);
            $quantity = !empty($product['quantity']) ? $product['quantity'] : 1;
            $total_discount = 0;
            if (shopDiscounts::isEnabled('flexdiscount')) {
                if (!isset($cart_products[$product['sku_id']])) {
                    // Получаем значения скидок для корзины
                    waRequest::setParam('igaponov_skip_frontend_products', 1);
                    $workflow = shopFlexdiscountApp::getOrder()->getOrderCalculateDiscount();
                    waRequest::setParam('igaponov_skip_frontend_products', 0);
                    // Если имеется скидка
                    if ($workflow['discount']) {

                        /* Создаем массив из ключей sku_id. Сейчас это item_id */
                        $workflow_products = [];
                        foreach ($workflow['products'] as $workflow_product) {
                            $workflow_products[$workflow_product['sku_id']] = $workflow_product;
                        }

                        // Если искомый товар содержится среди скидочных
                        if (!empty($workflow_products[$product['sku_id']])) {
                            $workflow_product = $workflow_products[$product['sku_id']];
                            // Вычисляем цену со скидкой
                            $price_with_discount = $price - $workflow_product['discount'];
                            if ($price_with_discount < 0) {
                                $price_with_discount = 0;
                            }
                            // Запоминаем обработанный товар
                            $cart_products[$product['sku_id']] = array(
                                "price" => $price_with_discount,
                                "quantity" => $quantity,
                                "total_discount" => $workflow_product['total_discount']
                            );
                            $price = $cart_products[$product['sku_id']]['price'];
                            $quantity = $cart_products[$product['sku_id']]['quantity'];
                            $total_discount = $workflow_product['total_discount'];

                            (new shopFlexdiscountApp())->set('runtime.cart_products', $cart_products);
                        }
                    }
                } else {
                    $price = $cart_products[$product['sku_id']]['price'];
                    $quantity = $cart_products[$product['sku_id']]['quantity'];
                    $total_discount = $cart_products[$product['sku_id']]['total_discount'];
                }
            }
            return [
                'price' => $price,
                'compare_price' => ($price < $product_price) ? $product_price : 0,
                'original_price' => $product_price,
                'discount' => $product_price - $price,
                'total_discount' => $total_discount,
                'quantity' => $quantity
            ];
        }
        return [];
    }
}