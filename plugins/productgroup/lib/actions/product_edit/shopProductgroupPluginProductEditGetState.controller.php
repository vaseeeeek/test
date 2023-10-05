<?php

class shopProductgroupPluginProductEditGetStateController extends waJsonController
{
	public function execute()
	{
		$this->response['success'] = false;
		$product_id = waRequest::get('product_id');

		$edit_controller = new shopProductgroupEditFormController();

		try
		{
			$this->response = array(
				'state' => $edit_controller->getState($product_id),
				'success' => true,
			);
		}
		catch (Exception $e)
		{
			$this->response['success'] = false;
		}
	}
}
