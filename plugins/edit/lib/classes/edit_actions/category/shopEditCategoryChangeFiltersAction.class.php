<?php

class shopEditCategoryChangeFiltersAction extends shopEditLoggedAction
{
	private $category_filters;
	private $categories_filters_is_applied_to_children;

	public function __construct($category_filters, $categories_filters_is_applied_to_children)
	{
		parent::__construct();

		$this->category_filters = $category_filters;
		$this->categories_filters_is_applied_to_children = $categories_filters_is_applied_to_children;
	}

	protected function execute()
	{
		$current_datetime = date('Y-m-d H:i:s');
		$category_filters = $this->category_filters;
		$categories_filters_is_applied_to_children = $this->categories_filters_is_applied_to_children;

		$category_model = new shopCategoryModel();

		$filter_before_changes = $category_model->select('id,filter')->fetchAll('id', true);

		foreach ($category_filters as $category_id => $filter)
		{
			$filter_prepared = implode(',', $filter);

			//$is_the_same_filter = $category_model->select('id')
			//	->where('id = :id', array('id' => $category_id))
			//	->where('filter = :filter', array('filter' => $filter_prepared))
			//	->fetchField();
			//if ($is_the_same_filter)
			//{
			//	continue;
			//}

			$category_model->updateById($category_id, array(
				'filter' => $filter_prepared,
				'edit_datetime' => $current_datetime,
			));
			//$affected_category_ids[$category_id] = $category_id;
		}

		$query = $category_model
			->select('id,filter,left_key,right_key')
			->order('left_key ASC')
			->query();

		$update_children_filter_sql = '
UPDATE shop_category
SET filter = :filter, edit_datetime = :edit_datetime
WHERE left_key > :left_key AND right_key < :right_key
';

		foreach ($query as $category)
		{
			$category_id = $category['id'];

			if (
				!array_key_exists($category_id, $categories_filters_is_applied_to_children)
				|| !$categories_filters_is_applied_to_children[$category_id]
			)
			{
				continue;
			}

			$update_query_params = $category;
			$update_query_params['edit_datetime'] = $current_datetime;

			$category_model->exec($update_children_filter_sql, $update_query_params);
		}

		$affected_category_ids = array();
		foreach ($category_model->select('id,filter')->query() as $row)
		{
			$category_id = $row['id'];
			if (!array_key_exists($category_id, $filter_before_changes))
			{
				continue;
			}

			if ($filter_before_changes[$category_id] != $row['filter'])
			{
				$affected_category_ids[$category_id] = $category_id;
			}
		}

		return array(
			'category_filters' => $this->category_filters,
			'categories_filters_is_applied_to_children' => $this->categories_filters_is_applied_to_children,
			'affected_category_ids' => array_values($affected_category_ids),
			'affected_categories_count' => count($affected_category_ids),
		);
	}

	protected function getAction()
	{
		return $this->action_options->CATEGORY_CHANGE_FILTERS;
	}
}