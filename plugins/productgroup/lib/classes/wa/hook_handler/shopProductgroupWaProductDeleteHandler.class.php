<?php

class shopProductgroupWaProductDeleteHandler
{
	public function handle($event_params)
	{
		if (
			!is_array($event_params)
			|| !array_key_exists('ids', $event_params) || !is_array($event_params['ids']) || count($event_params['ids']) === 0
		)
		{
			return;
		}

		$product_group_storage = shopProductgroupPluginContext::getInstance()->getProductGroupStorage();

		$deleted_product_ids = $event_params['ids'];

		$product_group_storage->handleProductsDelete($deleted_product_ids);
	}
}