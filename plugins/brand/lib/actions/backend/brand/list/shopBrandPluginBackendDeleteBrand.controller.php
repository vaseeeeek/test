<?php

class shopBrandPluginBackendDeleteBrandController extends shopBrandWaBackendJsonController
{
	public function execute()
	{
		$brand_id = waRequest::post('brand_id');

		if (!wa_is_int($brand_id) || !($brand_id > 0))
		{
			return;
		}

		$brand_storage = new shopBrandBackendBrandStorage();
		$brand_storage->deleteById($brand_id);
	}
}
