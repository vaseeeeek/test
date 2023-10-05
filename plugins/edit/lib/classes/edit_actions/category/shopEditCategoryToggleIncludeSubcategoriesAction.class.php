<?php

class shopEditCategoryToggleIncludeSubcategoriesAction extends shopEditLoggedAction
{
	private $category_selection;
	private $toggle;

	/**
	 * @param shopEditCategorySelection $category_selection
	 * @param boolean $toggle
	 */
	public function __construct(shopEditCategorySelection $category_selection, $toggle)
	{
		$this->category_selection = $category_selection;
		$this->toggle = $toggle;

		parent::__construct();
	}

	protected function execute()
	{
		$category_storage = new shopEditCategoryStorage();

		$affected_category_ids = $category_storage->toggleIncludeSubCategories($this->category_selection, $this->toggle);

		return array(
			'category_selection' => $this->category_selection->assoc(),
			'toggle' => $this->toggle ? '1' : '0',
			'affected_categories_count' => count($affected_category_ids),
			'affected_category_ids' => $affected_category_ids,
		);
	}

	protected function getAction()
	{
		return $this->action_options->CATEGORY_TOGGLE_INCLUDE_SUBCATEGORIES;
	}
}