<?php

class shopProductgroupPluginProductGroupSaveProductsController extends waJsonController
{
	public function execute()
	{
		$this->response['success'] = false;

		$product_id = waRequest::post('product_id');
		$product_groups_json = waRequest::post('product_groups');

		$product_groups = json_decode($product_groups_json, true);
		if (!is_array($product_groups))
		{
			return;
		}

		$edit_controller = new shopProductgroupEditFormController();
		$this->response['new_state'] = $edit_controller->runSaveAction($product_id, $product_groups);

		$this->response['success'] = true;
	}
}