<?php

/**
 * User: Echo-company
 * Email: info@echo-company.ru
 * Site: https://www.echo-company.ru
 */
class shopCityselectCookiesModel extends waModel
{
    public $table = "shop_cityselect__cookies";

    public static function pushCookies($key, $data)
    {
        $model = new self();
        return $model->insert(array('key' => $key, 'data' => json_encode($data)));
    }

    public static function popCookies($key)
    {
        $result = array();
        $model = new self();
        $data = $model->getByField('key', $key);

        if (!empty($data)) {
            $result = json_decode($data['data'], true);
            $model->deleteById($data['id']);
        }

        return $result;
    }
}