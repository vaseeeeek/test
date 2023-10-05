<?php

class shopEditPluginCategoryInfoGetProductsCountController extends shopEditBackendJsonController
{
	public function execute()
	{
		$category_ids = waRequest::get('category_ids');
		if (!is_array($category_ids))
		{
			$this->errors['category_ids'] = 'Нужен массив id категорий';

			return;
		}

		$category_storage = new shopEditCategoryStorage();

		$this->response['categories_products_count'] = $category_storage->getCategoriesProductsCount($category_ids);
	}

	protected function stateIsRequired()
	{
		return false;
	}
}