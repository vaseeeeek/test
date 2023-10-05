<?php

class shopEditCategorySetDefaultSortingAction extends shopEditLoggedAction
{
	private $category_selection;
	private $sorting;

	public function __construct(shopEditCategorySelection $category_selection, $sorting)
	{
		parent::__construct();

		$this->category_selection = $category_selection;
		$this->sorting = $sorting;
	}

	protected function execute()
	{
		$category_storage = new shopEditCategoryStorage();
		$affected_category_ids = $category_storage->updateDefaultSorting($this->category_selection, $this->sorting);

		return array(
			'category_selection' => $this->category_selection->assoc(),
			'sort_products' => $this->sorting,
			'affected_categories_count' => count($affected_category_ids),
			'affected_category_ids' => $affected_category_ids,
		);
	}

	protected function getAction()
	{
		return $this->action_options->CATEGORY_SET_DEFAULT_SORTING;
	}
}