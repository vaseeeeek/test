<?php

class shopBrandPluginBackendAction extends shopBrandBackendAction
{
	public function execute()
	{
		$this->setTemplate('BackendBrandList');

		$controller = new shopBrandBackendBrandsListController();

		$this->view->assign($controller->getState());
	}
}
