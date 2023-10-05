<?php

class shopEditPluginCategoryUpdateEmptyCategoryController extends shopEditBackendJsonController
{
	public function execute()
	{
		if (!isset($this->state['category_selection']) || !is_array($this->state['category_selection']))
		{
			$this->errors['category_selection'] = 'Нет параметра';
		}

		//if (!array_key_exists('consider_include_sub_categories', $this->state))
		//{
		//	$this->errors['consider_include_sub_categories'] = 'Нет параметра';
		//}

		if (!array_key_exists('action', $this->state))
		{
			$this->errors['action'] = 'Нет параметра';
		}

		if (count($this->errors) > 0)
		{
			return;
		}

		$category_selection = new shopEditCategorySelection($this->state['category_selection']);
		//$consider_include_sub_categories = !!$this->state['consider_include_sub_categories'];
		$action = $this->state['action'];

		$action = new shopEditCategoryUpdateEmptyCategoryAction($category_selection, $action);
		$action_result = $action->run();

		$categories_storage = new shopEditCategoryStorage();

		$this->response['log'] = $action_result->assoc();
		$this->response['categories'] = $categories_storage->getAllAssocForSettings();
	}
}