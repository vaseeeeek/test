<?php

class shopEditBrandToggleClientSortingAction extends shopEditLoggedAction
{
	private $brand_selection;
	private $toggle;

	public function __construct(shopEditBrandSelection $brand_selection, $toggle)
	{
		$this->brand_selection = $brand_selection;
		$this->toggle = $toggle;

		parent::__construct();
	}

	protected function execute()
	{
		$brand_storage = new shopEditBrandStorage();

		$affected_brand_ids = $brand_storage->toggleEnableClientSorting($this->brand_selection, $this->toggle);

		return array(
			'brand_selection' => $this->brand_selection->assoc(),
			'toggle' => $this->toggle ? '1' : '0',
			'affected_brands_count' => count($affected_brand_ids),
			'affected_brand_ids' => $affected_brand_ids,
		);
	}

	protected function getAction()
	{
		return $this->action_options->BRAND_TOGGLE_CLIENT_SORTING;
	}
}
