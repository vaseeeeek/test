<?php

class shopRegionsBackendProductsEventHandler extends shopRegionsEventHandler
{
	protected function actions()
	{
		return array(
			new shopRegionsBackendHideProductsStorefrontLinksHandlerAction(),
		);
	}
}