<?php

class shopEditPluginCategoryInfoGetFilterController extends shopEditBackendJsonController
{
	public function execute()
	{
		$category_id = waRequest::get('category_id');
		if (!($category_id > 0))
		{
			$this->errors['category_id'] = 'Нужен id категории';

			return;
		}

		$category_storage = new shopEditCategoryStorage();

		$this->response['filters'] = $category_storage->getCategoryFilter($category_id);
	}

	protected function stateIsRequired()
	{
		return false;
	}
}