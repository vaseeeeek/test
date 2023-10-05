<?php

class shopEditCategoryUpdateEmptyCategoryAction extends shopEditLoggedAction
{
	const ACTION_HIDE = 'HIDE';
	const ACTION_DELETE = 'DELETE';

	private $category_selection;
	private $action;

	public function __construct(shopEditCategorySelection $category_selection, $action)
	{
		$this->category_selection = $category_selection;
		$this->action = $action;

		parent::__construct();
	}

	protected function execute()
	{
		$category_storage = new shopEditCategoryStorage();

		$deleted_category_ids = array();
		$hidden_category_ids = array();

		if ($this->action == self::ACTION_DELETE)
		{
			//$deleted_category_ids = $category_storage->deleteEmptyCategories($this->category_selection, $this->consider_include_sub_categories);
			$deleted_category_ids = $category_storage->deleteEmptyCategories($this->category_selection, true);
		}
		elseif ($this->action == self::ACTION_HIDE)
		{
			$hidden_category_ids = $category_storage->hideEmptyCategories($this->category_selection);
		}

		return array(
			'category_selection' => $this->category_selection->assoc(),
			'action' => $this->action,
			//'consider_include_sub_categories' => $this->consider_include_sub_categories,
			'deleted_categories_count' => count($deleted_category_ids),
			'deleted_category_ids' => $deleted_category_ids,
			'hidden_categories_count' => count($hidden_category_ids),
			'hidden_category_ids' => $hidden_category_ids,
		);
	}

	protected function getAction()
	{
		return $this->action_options->CATEGORY_UPDATE_EMPTY_CATEGORY;
	}
}