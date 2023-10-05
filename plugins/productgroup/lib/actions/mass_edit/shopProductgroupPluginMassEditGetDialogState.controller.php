<?php

class shopProductgroupPluginMassEditGetDialogStateController extends waJsonController
{
	public function execute()
	{
		$this->response['success'] = false;

		$products_selection_json = waRequest::get('products_selection_json');
		$products_selection = json_decode($products_selection_json, true);
		if (!is_array($products_selection))
		{
			return;
		}

		$controller = new shopProductgroupMassEditController();

		try
		{
			$this->response['state'] = $controller->getDialogState($products_selection);
		}
		catch (Exception $e)
		{
			return;
		}

		$this->response['success'] = true;
	}
}