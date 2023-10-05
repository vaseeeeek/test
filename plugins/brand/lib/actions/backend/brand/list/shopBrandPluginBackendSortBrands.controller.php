<?php

class shopBrandPluginBackendSortBrandsController extends shopBrandWaBackendJsonController
{
	public function execute()
	{
		$this->response['success'] = false;

		$state_json = waRequest::post('state');
		$state = json_decode($state_json, true);
		if (!is_array($state))
		{
			return;
		}

		$brand_storage = new shopBrandBrandStorage();
		$brand_storage->setBrandsSort($state['brands_order']);

		$this->response['success'] = true;
	}
}
