<?php

class shopBrandPluginBackendGenerateBrandUniqueUrlController extends shopBrandWaBackendJsonController
{
	public function execute()
	{
		$this->response['success'] = false;

		$brand_id = waRequest::get('brand_id');
		$brand_url = waRequest::get('brand_url');
		$brand_name = waRequest::get('brand_name');

		$storage = new shopBrandBrandStorage();

		$url = $storage->getUniqueUrl($brand_name, $brand_id);

		$this->response['url'] = $url;
		$this->response['success'] = true;
	}
}
