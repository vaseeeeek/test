<?php

class shopRegionsBackendMenuEventHandler extends shopRegionsEventHandler
{
	protected function actions()
	{
		return array(
			new shopRegionsBackendMenuItemHandlerAction(),
			new shopRegionsBackendHideStorefrontLinksHandlerAction(),
		);
	}
}