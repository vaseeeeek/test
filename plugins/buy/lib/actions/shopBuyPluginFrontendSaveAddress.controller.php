<?php


class shopBuyPluginFrontendSaveAddressController extends waJsonController
{
    /**
     * @var waContact
     */
    protected $contact;

    public function execute()
    {

        if (wa()->getUser()->isAuth()) {
            $this->contact = wa()->getUser();
        } else {
            $data = wa()->getStorage()->get('shop/checkout');
            $this->contact = isset($data['contact']) ? $data['contact'] : null;
        }

        if (!$this->contact) {
            $this->contact = wa()->getUser();
        }


        if($customer = waRequest::post('customer', array(), waRequest::TYPE_ARRAY_TRIM)) {

            if(!empty($customer['custom_address'])) {
                $ca = ifempty($customer['custom_address'], array());
                $customer['address.shipping'] = ifempty($customer['address.shipping'], array());
                $customer['address.shipping']['country'] = 'rus';

                foreach ($ca as $n => $v) {
                    if(in_array($n, array('city', 'region', 'zip'))) {
                        $customer['address.shipping'][$n] = $v;
                    }
                    $this->contact->set($n, $v);
                }

                $customer['address.shipping']['street'] =
                    ifempty($ca['street_name'], '').
                    (empty($ca['house']) ? '' : ', д. '.$ca['house']).
                    (empty($ca['korp']) ? '' : ', корп. '.$ca['korp']).
                    (empty($ca['apt']) ? '' : ', кв. '.$ca['apt'])
                ;
            }
            if(!empty($customer['address.shipping'])) {
                $customer['address.shipping']['lat'] = '';
                $customer['address.shipping']['lng'] = '';
                $this->setCustomerAddress($customer['address.shipping']);
            }
            $this->saveContact();
        }

        $address = $this->contact->get('address.shipping');
        if(!empty($address[0])) {
            $address = $address[0];
        }
        $city = !empty($address['data']) && !empty($address['data']['city']) ? $address['data']['city'] : '';

        $city_valid = [];
        if(class_exists('shopShippinginfoPlugin')) {
            try {

                /**
                 * @var $plugin shopShippinginfoPlugin
                 */
                $plugin = wa('shop')->getPlugin('shippinginfo');
                foreach ($plugin->getSettings('shipping') as $shipping) {
                    if(!empty($shipping['free_city'])) {
                        $city_valid[$shipping['id']] = $plugin->checkCity($city, $shipping['id']);
                    }
                }

            } catch (Exception $e) {
                $city_valid = [];
            }
        }

        $this->response = compact('address', 'city', 'city_valid');
    }


    protected function setCustomerAddress($_address)
    {

        //На основе документации https://developers.webasyst.ru/cookbook/contacts-app-integration/
        $address = $this->contact->get('address.shipping');
        $address = reset($address);

        $plugin = wa('shop')->getPlugin('buy');
        if(!empty($_address['city']) && ($replace_city = $plugin->getSettings('replace_city'))) {
            $prefixes = preg_split('/\.? +/u', $replace_city, -1, PREG_SPLIT_NO_EMPTY);
            foreach ($prefixes as $prefix) {
                $_address['city'] = preg_replace('@^'.$prefix.'( +|\. +|\.)@ui', '', $_address['city']);
                $_address['city'] = trim($_address['city']);
            }

            $_address['city'] = preg_replace('/[^0-9a-zа-я- ]/ui', '', $_address['city']);

            if(preg_match('/москва/ui', $_address['city'])) {
                $_address['region'] = '77';
            }
            if(preg_match('/Санкт-Петербург/ui', $_address['city'])) {
                $_address['region'] = '78';
            }
        }


        foreach ($_address as $f => $v) {
            if(in_array($f, array('city', 'region', 'zip'))) {
                wa()->getResponse()->setCookie('cityselect__'.$f, $v, time() + 12 * 30 * 86400);
            }
            $address['data'][$f] = $v;
        }


        $cart = new shopCart();
        $code = $cart->getCode();
        if($code && class_exists('shopShippinginfoCache')) {
            $cache = new shopShippinginfoCache();
            $cache->delete($code);
        }

        $this->contact->set('address.shipping', array($address));


    }

    protected function saveContact()
    {
        if (wa()->getUser()->isAuth()) {
            $this->contact->save();
        } else {
            $data = wa()->getStorage()->get('shop/checkout', array());
            $data['contact'] = $this->contact;
            wa()->getStorage()->set('shop/checkout', $data);
        }
    }
}