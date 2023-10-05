<?php

class shopRegionsPluginVariablesMeta
{
	public static function getMeta()
	{
		$meta = array(
			'{$region.name}' => 'название региона',
			'{$region.phone}' => 'телефон',
			'{$region.email}' => 'e-mail',
			'{$region.schedule}' => 'график работы',
			'{$region.country}' => 'страна',
			'{$region.area}' => 'регион',
			'{$region.routing.storefront}' => 'витрина (example.com/shop/*)',
			'{$region.routing.domain}' => 'домен (example.com)',
			'{$region.routing.route_url}' => 'относительный url витрины (/shop/)',
			'{$region.routing.route}' => 'настройки витрины',
		);

		$settings = new shopRegionsSettings();
		$params = $settings->getParams();

		usort($params, array('shopRegionsPluginVariablesMeta', '_compareParamsIds'));
		foreach ($params as $param)
		{
			$meta["{\$region.field[{$param['id']}]}"] = $param['name'];
		}

		return $meta;
	}

	public static function _compareParamsIds($p1, $p2)
	{
		if ($p1['id'] == $p2['id'])
		{
			return 0;
		}

		return $p1['id'] < $p2['id'] ? -1 : 1;
	}
}