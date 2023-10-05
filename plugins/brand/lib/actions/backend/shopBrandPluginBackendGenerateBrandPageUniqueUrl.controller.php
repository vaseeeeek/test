<?php

class shopBrandPluginBackendGenerateBrandPageUniqueUrlController extends shopBrandWaBackendJsonController
{
	public function execute()
	{
		$this->response['success'] = false;

		$page_id = waRequest::get('page_id');
		$page_url = waRequest::get('page_url');
		$page_name = waRequest::get('page_name');

		$storage = new shopBrandPageStorage();
		$url = $storage->getUniqueUrl($page_name, $page_id);

		$this->response['url'] = $url;
		$this->response['success'] = true;
	}
}
