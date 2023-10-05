<?php

class shopRegionsFetchTemplateEventHandler extends shopRegionsEventHandler
{
	protected function actions()
	{
		return array(
			new shopRegionsGetPluginVariablesHandlerAction(),
		);
	}
}