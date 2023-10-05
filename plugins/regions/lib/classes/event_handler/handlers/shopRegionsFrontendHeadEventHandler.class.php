<?php

class shopRegionsFrontendHeadEventHandler extends shopRegionsEventHandler
{
	protected function actions()
	{
		return array(
			new shopRegionsMetaOptimizerHandlerAction(),
			new shopRegionsInitShippingFormChangeMonitoringHandlerAction(),
		);
	}
}