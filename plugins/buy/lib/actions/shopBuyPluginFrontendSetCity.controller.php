<?php

/**
 * Class shopBuyPluginFrontendSetCityController
 * @deprecated Уже не используется
 */
class shopBuyPluginFrontendSetCityController extends waJsonController
{
    public function execute()
    {
        $city = waRequest::post('city', '', waRequest::TYPE_STRING_TRIM);
        $region = waRequest::post('region', '', waRequest::TYPE_STRING_TRIM);
        $zip = waRequest::post('zip', '', waRequest::TYPE_STRING_TRIM);

        $city = preg_replace('/^(поселок|посёлок|пос|пгт|п|город|гор|г|деревня|дер|д)( |\. |\.)/ui', '', $city);

        $city = trim($city);

        if(preg_match('/москва/ui', $city)) {
            $region = '77';
        }
        if(preg_match('/Санкт-Петербург/ui', $city)) {
            $region = '78';
        }

        if(!$zip && !$city) {
            $location = shopCityselectHelper::getLocation();
            extract($location);
        }

        $region = substr($region, 0, 2);


        //Сохраняем в форму оформления заказа
        if (wa()->getUser()->isAuth()) {
            $contact = wa()->getUser();
        } else {
            $data = wa()->getStorage()->get('shop/checkout');
            $contact = isset($data['contact']) ? $data['contact'] : null;
        }

        if (!$contact) {
            $contact = wa()->getUser();
        }

        //На основе документации https://developers.webasyst.ru/cookbook/contacts-app-integration/
        $address = $contact->get('address.shipping');
        $address[0]['data']['country'] = 'rus';
        $address[0]['data']['city'] = $city;
        $address[0]['data']['region'] = $region;
        $address[0]['data']['zip'] = $zip;
        unset($address[0]['data']['lat']);
        unset($address[0]['data']['lng']);

        $contact->set('address.shipping', $address);

        //Данные у неавторизованного пользователя сохраняются в Storage
        if (wa()->getUser()->isAuth()) {
            $contact->save();
        } else {
            $data['contact'] = $contact;
            wa()->getStorage()->set('shop/checkout', $data);
        }

        $this->response = $address[0]['data'];
    }
}