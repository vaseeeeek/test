<?php

class shopEditBrandSetDefaultSortingAction extends shopEditLoggedAction
{
	private $brand_selection;
	private $sorting;

	public function __construct(shopEditBrandSelection $brand_selection, $sorting)
	{
		$this->brand_selection = $brand_selection;
		$this->sorting = $sorting;

		parent::__construct();
	}

	protected function execute()
	{
		$brand_storage = new shopEditBrandStorage();

		$affected_brand_ids = $brand_storage->updateDefaultSorting($this->brand_selection, $this->sorting);

		return array(
			'brand_selection' => $this->brand_selection->assoc(),
			'sort_products' => $this->sorting,
			'affected_brands_count' => count($affected_brand_ids),
			'affected_brand_ids' => $affected_brand_ids,
		);
	}

	protected function getAction()
	{
		return $this->action_options->BRAND_CHANGE_DEFAULT_SORTING;
	}
}
