<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopQuickorderPluginFrontendUpdateServiceController extends waJsonController
{
    public function execute()
    {
        // Тип формы: товар, корзина
        $type = waRequest::post('qformtype', 'product');

        $product_id = waRequest::post('id');
        $sku_id = waRequest::post('sku_id');
        $services_str = waRequest::post('services');

        $parse_str = $services = array();
        parse_str($services_str, $parse_str);
        if (isset($parse_str['quickorder_product'])) {
            $services = $parse_str['quickorder_product'];
        }

        if ($product_id && $sku_id) {
            // Получаем информацию о товаре
            $product = (new shopQuickorderPluginHelper())->getProduct((int) $product_id);
            if (!$product) {
                return;
            }
            $product['sku_id'] = $sku_id;

            $settings = shopQuickorderPluginHelper::getSettings();
            $form_settings = !empty($settings['shared_display_settings']) ? $settings['product'] : $settings[$type];

            if (!empty($form_settings['product_services'])) {
                // Товар
                $products = (new shopQuickorderPluginProductData())->prepareProducts(array($product), !empty($form_settings['product_services']), false, true);

                if (!$products) {
                    return;
                }

                if (!empty($services['services'])) {
                    foreach ($products as $product) {
                        $product['active_services'] = array();
                        // Отмечаем активные услуги
                        foreach ($services['services'] as $service_id) {
                            if (isset($services['service_variant'][$service_id])) {
                                $product['active_services'][$service_id] = $services['service_variant'][$service_id];
                            }
                        }
                    }
                }

                $view = new waSmarty3View(wa());
                $view->assign('settings', $form_settings);
                $view->assign('product', $product);
                $this->response = $view->fetch(wa()->getAppPath('plugins/quickorder/templates/actions/frontend/include.product.service.html', 'shop'));
            }
        }
    }

}