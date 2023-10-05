<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopQuickorderPluginFrontendLoadController extends waJsonController
{
    public function execute()
    {
        $product = waRequest::post('product');
        $cart = waRequest::post('cart');

        $form = new shopQuickorderPluginForm();
        if ($product) {
            $helper = new shopQuickorderPluginHelper();
            foreach ($product as $sku_id => $obj) {
                $product_id = (int) $obj['product_id'];
                $prod = $helper->getProduct($product_id);
                if (!$prod) {
                    return '';
                }

                $prod['sku_id'] = $sku_id;
                if (!empty($obj['params']) && is_string($obj['params'])) {
                    $prod = $this->parseParams($prod, $obj['params']);
                }

                $html = $form->getButton($prod, 'product', true, $obj['inline'] == '1' ? 'form' : ($obj['inline'] == '0' ? 'popup' : null));

                $css = $this->getInlineCss($obj);

                $this->response[] = array('type' => 'product', 'product_id' => $product_id, 'sku_id' => $sku_id, 'html' => $html, 'css' => $css);
            }
        }
        if ($cart) {
            $cart_obj = reset($cart);
            $html = $form->getButton(array(), 'cart', true, $cart_obj['inline'] == '1' ? 'form' : ($cart_obj['inline'] == '0' ? 'popup' : null));

            $css = $this->getInlineCss($cart_obj);

            $this->response[] = array('type' => 'cart', 'product_id' => 0, 'sku_id' => 0, 'html' => $html, 'css' => $css);
        }
    }

    private function parseParams($product, $str_params)
    {
        $params = array();
        parse_str($str_params, $params);
        if (!empty($params)) {
            $product['quantity'] = !empty($params['quantity']) && (float) $params['quantity'] > 0 ? (float) $params['quantity'] : 1;
            $product['sku_id'] = !empty($params['sku_id']) ? $params['sku_id'] : $product['sku_id'];

            // Проверяем, передан ли артикул товара. Если нет, то пытаемся определить его через данные форм
            $sku_id = 0;
            if (isset($params['sku_id'])) {
                $sku_id = $params['sku_id'];
            } else {
                if (isset($params['features'])) {
                    $sku_id = (new shopProductFeaturesModel())->getSkuByFeatures($product['id'], $params['features']);
                }
            }

            if (!$sku_id) {
                $sku_id = $product['sku_id'];
            }
            $product['sku_id'] = $sku_id;

            // Услуги
            if (!empty($params['services'])) {
                $settings = shopQuickorderPluginHelper::getSettings();
                $form_settings = $settings['product'];
                if (!empty($form_settings['product_services'])) {
                    $products = (new shopQuickorderPluginProductData())->prepareProducts(array($product), !empty($form_settings['product_services']), false, true);
                    if (!$products) {
                        return $product;
                    }
                    foreach ($products as $product) {
                        $product['services'] = array();
                        // Отмечаем активные услуги
                        foreach ($params['services'] as $service_id) {
                            if (isset($params['service_variant'][$service_id])) {
                                $product['services'][$service_id] = $params['service_variant'][$service_id];
                            }
                        }
                    }
                }
            }
        }

        return $product;
    }

    /**
     * Get inline CSS if necessary
     *
     * @param array $obj
     * @return string
     */
    private function getInlineCss($obj)
    {
        $css = "";
        if (!empty($obj['add_css'])) {
            $form_css = shopQuickorderPluginGenerator::getCss();
            $css .= $form_css['google_fonts'] ? $form_css['google_fonts'] : '';

            if ($form_css['inline_css']) {
                $css .= '<style id="quickorder-inline-styles" data-inline-css="1">' . $form_css['inline_css'] . '</style>';
            }
        }
        return $css;
    }
}