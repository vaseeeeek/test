<?php


class shopRegionsCityReplacesSet extends shopRegionsReplacesSet
{
	public function getReplaces()
	{
		$routing = new shopRegionsRouting();
		$city = $routing->getCurrentCity();

		if (!$city)
		{
			return array();
		}

		$settings = new shopRegionsSettings();
		$params = $settings->getParams();
		$city_params = array();

		if ($city instanceof shopRegionsCity)
		{
			foreach ($params as $_param)
			{
				$param = shopRegionsParam::build($_param);

				if ($param instanceof shopRegionsParam)
				{
					$city_params[$param->getID()] = $city->getParam($param->getID());
				}
			}
		}

		$replaces = array(
			new shopRegionsMetaReplacesSet($this->view),
			new shopRegionsVariable('region_name', $city->getName()),
			new shopRegionsVariable('region_phone', $city->getPhone()),
			new shopRegionsVariable('region_email', $city->getEmail()),
			new shopRegionsVariable('region_schedule', $city->getSchedule()),
			new shopRegionsVariable('region_area', $city->getAreaName()),
			new shopRegionsVariable('region_country', $city->getCountryName()),
		);

		foreach ($city_params as $param_id => $value)
		{
			$replaces[] = new shopRegionsVariable('region_field_'.$param_id, $value);
		}

		return $replaces;
	}
}