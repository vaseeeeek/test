<?php

class shopRegionsRoutingEventHandler extends shopRegionsEventHandler
{
	protected function actions()
	{
		return array(
			new shopRegionsExtendPageRoutingHandlerAction(),
			new shopRegionsUpdateCurrentRouteParamsHandlerAction(),
			new shopRegionsUpdateCurrentContactShippingHandlerAction(),
		);
	}

	protected function aggregateHandleResults($accumulator, $plugin_routes)
	{
		if (is_array($plugin_routes))
		{
			foreach ($plugin_routes as $rule => $route)
			{
				$accumulator[$rule] = $route;
			}
		}

		return $accumulator;
	}

	protected function aggregatorInitialValue()
	{
		return $this->getInitialHandlerParams();
	}
}