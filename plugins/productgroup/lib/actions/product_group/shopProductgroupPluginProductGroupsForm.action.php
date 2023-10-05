<?php

class shopProductgroupPluginProductGroupsFormAction extends waViewAction
{
	public function execute()
	{
		$product_id = waRequest::get('id');

		$edit_controller = new shopProductgroupEditFormController();

		$this->view->assign('state', $edit_controller->getState($product_id));
	}
}