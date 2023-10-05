<?php

class shopProductgroupPluginMassEditAddToGroupController extends waJsonController
{
	public function execute()
	{
		$this->response['success'] = false;

		$save_state_json = waRequest::post('save_state_json');
		$save_state = json_decode($save_state_json, true);

		if (!is_array($save_state))
		{
			return;
		}

		$group_id = $save_state['group_id'];
		$group_products = $save_state['product_group']['products'];
		if (!$group_id)
		{
			return;
		}

		$controller = new shopProductgroupMassEditController();

		try
		{
			$controller->addProductsToGroup($group_id, $group_products);
		}
		catch (Exception $e)
		{
			return;
		}

		$this->response['success'] = true;
	}
}