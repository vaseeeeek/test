<?php

class shopBrandPluginBackendBrandToggleController extends shopBrandWaBackendJsonController
{
	public function execute()
	{
		$this->response['success'] = false;

		$brand_id = waRequest::post('brand_id', 0, waRequest::TYPE_INT);
		$toggle = !!waRequest::post('is_enabled');

		if ($brand_id <= 0)
		{
			return;
		}

		$brand_storage = new shopBrandBrandStorage();

		$this->response['success'] = !!$brand_storage->toggleBrandIsShown($brand_id, $toggle);
	}
}
