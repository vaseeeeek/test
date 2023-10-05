<?php

class shopDpIntegrationRegionsPlugin extends shopDpIntegration
{
	protected $plugin_id = 'regions';

	public function getLocation()
	{
		if(class_exists('shopRegionsRouting')) {
			$regions_routing = new shopRegionsRouting();
			$current_city = $regions_routing->getCurrentCity();

			if($current_city) {
				return array(
					'country' => $current_city->getCountryIso3(),
					'region' => $current_city->getRegionCode(),
					'city' => $current_city->getName()
				);
			}
		}

		return null;
	}
}