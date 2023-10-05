<?php
/**
 * Сохранение адреса в корзине
 *
 * User: Echo-company
 * Email: info@echo-company.ru
 * Site: https://www.echo-company.ru
 */

class shopCityselectPluginFrontendSaveAddressController extends waJsonController
{
    public function execute()
    {


        //Не устанавливается индекс если он не запрашивается вместе с городом
        $city = waRequest::post('city', '', waRequest::TYPE_STRING_TRIM);
        $region = waRequest::post('region', '', waRequest::TYPE_STRING_TRIM);
        $zip = waRequest::post('zip', '', waRequest::TYPE_STRING_TRIM);


        $this->response = shopCityselectHelper::setCity($city, $region, $zip);

//        $data = wa()->getStorage()->get('shop/checkout');
//
//        if (empty($data['order'])) {
//            $data['order'] = array();
//        }
//
//        $input = !empty($data['order']['region']) ? $data['order']['region'] : array();
//
//        $input['country'] = 'rus';
//        $input['region'] = $region;
//        $input['city'] = $city;
//        $input['zip'] = $zip;
//
//        $details = !empty($data['order']['details']) ? $data['order']['details'] : array();
//
//        if (empty($details['shipping_address'])) {
//            $details['shipping_address'] = array();
//        }
//
//        $details['shipping_address']['zip'] = $zip;
//
//        $data['order']['region'] = $input;
//        $data['order']['details'] = $details;
//
//        wa()->getStorage()->set('shop/checkout', $data);
//
//        $this->response = $data;
    }
}