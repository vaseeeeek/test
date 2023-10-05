<?php

class shopRegionsMetaOptimizerHandlerAction implements shopRegionsIHandlerAction
{
	public function execute($handler_params)
	{
		$routing = new shopRegionsRouting();
		$current_city = $routing->getCurrentCity();

		if ($current_city)
		{
			$optimizer_set = new shopRegionsCityOptimizerSet();
			$optimizer_set->execute();
		}

		return '';
	}
}