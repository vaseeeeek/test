<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopQuickorderPluginGeo extends shopQuickorderPluginSypexgeo
{
    private $api;

    public function __construct()
    {
        $this->api = new shopQuickorderPluginSypexgeo();
    }

    /**
     * Get country, region and city
     *
     * @return array|bool
     * @throws waException
     */
    public function getAddress()
    {
        $sypex_info = $this->api->getCityFull(waRequest::getIp());

        if (!$sypex_info) {
            return false;
        }
        $iso2letter = strtolower(ifset($sypex_info, 'country', 'iso', 'not_found'));

        $country = (new waCountryModel())->select('iso3letter')->where('iso2letter = ?', $iso2letter)->fetchField();

        $region_name_ru = strtolower(ifset($sypex_info, 'region', 'name_ru', 'not_found'));
        $region_name_en = strtolower(ifset($sypex_info, 'region', 'name_en', 'not_found'));
        $region = (new waRegionModel())->select('code')->where('country_iso3 = s:country AND name IN (s:name1, s:name2)', array('country' => $country, 'name1' => $region_name_ru, 'name2' => $region_name_en))->fetchField();

        $city_en = ifset($sypex_info, 'city', 'name_en', '');
        $city_ru = ifset($sypex_info, 'city', 'name_ru', '');

        return array(
            'country' => $country,
            'region' => $region,
            'city' => waLocale::getLocale() == 'ru_RU' ? $city_ru : $city_en
        );
    }
}