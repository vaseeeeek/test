<?php

class shopEditPluginCategoryChangeFiltersController extends shopEditBackendJsonController
{
	public function execute()
	{
		if (!isset($this->state['category_filters']) || !is_array($this->state['category_filters']))
		{
			$this->errors['category_filters'] = 'Нет параметра';
		}

		if (!isset($this->state['categories_filters_is_applied_to_children']) || !is_array($this->state['categories_filters_is_applied_to_children']))
		{
			$this->errors['categories_filters_is_applied_to_children'] = 'Нет параметра';
		}

		if (count($this->errors) > 0)
		{
			return;
		}

		$category_filters = $this->state['category_filters'];
		$categories_filters_is_applied_to_children = $this->state['categories_filters_is_applied_to_children'];

		$action = new shopEditCategoryChangeFiltersAction($category_filters, $categories_filters_is_applied_to_children);
		$action_result = $action->run();

		$this->response = array(
			'categories_filter' => $this->getCategoriesFilter(),
		);

		$this->response['log'] = $action_result->assoc();
	}

	/**
	 * @return array
	 */
	private function getCategoriesFilter()
	{
		$category_model = new shopCategoryModel();
		$categories_filter = array();
		$query = $category_model->select('id,filter')->query();
		foreach ($query as $row)
		{
			$category_id = $row['id'];
			$filters_raw = trim($row['filter']);

			$categories_filter[$category_id] = strlen($filters_raw) == 0
				? array()
				: explode(',', $filters_raw);
		}

		return $categories_filter;
	}
}