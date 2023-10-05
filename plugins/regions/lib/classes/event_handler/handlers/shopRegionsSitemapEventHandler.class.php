<?php

class shopRegionsSitemapEventHandler extends shopRegionsEventHandler
{
	protected function actions()
	{
		return array(
			new shopRegionsSitemapShopPagesHandlerAction(),
		);
	}
}