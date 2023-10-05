<?php

class shopBrandPluginBackendGetBrandsListController extends shopBrandWaBackendJsonController
{
	public function execute()
	{
		$controller = new shopBrandBackendBrandsListController();

		$this->response = $controller->getState();
	}
}
