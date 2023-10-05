<?php

/*
 * Class shopPricereqPluginBackendActions
 * @author Max Severin <makc.severin@gmail.com>
 */
class shopPricereqPluginBackendActions extends waViewActions {

    public function defaultAction() {
        $this->setLayout(new shopPricereqPluginBackendLayout());

    	$app_settings_model = new waAppSettingsModel();
        $settings = $app_settings_model->get(array('shop', 'pricereq'));

    	$limit = (int)$settings['pricereq_request_limit'];
    	$page = waRequest::get('page', 1, 'int');
    	if ($page < 1) {
            $page = 1;
        }
        $offset = ($page - 1) * $limit;

        $model = new shopPricereqPluginRequestModel();
        $price_requests = $model->getPriceRequests($offset, $limit, $settings['show_done']);
        $count = $model->countAll($settings['show_done']);

        foreach ($price_requests as $id => $r) {
            $product = new shopProduct($r['product_id']);
            $product['frontend_url'] = wa()->getRouteUrl('shop/frontend/product', array(
                'product_url' => $product['url'],
            ), true);
            $product['image_src'] = shopImage::getUrl(array("product_id" => $product['id'], "id" => $product['image_id'], "ext" => $product['ext']), '48x48');

            $price_requests[$id]['product'] = $product;
        }

        $pages_count = ceil((float)$count / $limit);
        $this->view->assign('pages_count', $pages_count);

        $this->view->assign('pricereq_settings', $settings);
        $this->view->assign('price_requests', $price_requests);
        $this->view->assign('price_requests_count', $count);
        
        $this->getResponse()->setTitle( _wp('Price request') );
    }

}