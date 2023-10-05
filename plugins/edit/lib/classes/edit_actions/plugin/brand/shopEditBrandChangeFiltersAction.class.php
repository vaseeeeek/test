<?php

class shopEditBrandChangeFiltersAction extends shopEditLoggedAction
{
	private $mode;
	private $all_brands_features_selection;
	private $brands_filter;

	public function __construct($mode, $all_brands_features_selection, $brands_filter)
	{
		$this->mode = $mode;
		$this->all_brands_features_selection = $all_brands_features_selection;
		$this->brands_filter = $brands_filter;

		parent::__construct();
	}

	protected function execute()
	{
		// todo constants
		if ($this->mode === 'ALL')
		{
			$affected_brand_ids = $this->updateAllFilters();
		}
		elseif ($this->mode === 'PERSONAL')
		{
			$affected_brand_ids = $this->updatePersonalFilters();
		}
		else
		{
			throw new waException();
		}

		return array(
			'brands_filter' => $this->brands_filter,
			'affected_brand_ids' => $affected_brand_ids,
			'affected_brands_count' => count($affected_brand_ids),
		);
	}

	protected function getAction()
	{
		return $this->action_options->BRAND_CHANGE_FILTERS;
	}

	private function updateAllFilters()
	{
		$storage = new shopBrandBrandStorage();

		$affected_brand_ids = array();

		$filter = array();
		foreach ($this->all_brands_features_selection['feature_elements'] as $feature_element)
		{
			if ($feature_element['is_selected'])
			{
				$filter[] = $feature_element['feature']['id'];
			}
		}

		foreach ($storage->getAll() as $brand)
		{
			if ($this->arraysAreEqual($brand->filter, $filter))
			{
				continue;
			}

			$affected_brand_ids[] = $brand->id;
			$brand->filter = $filter;

			$storage->store($brand->assoc());
		}

		return $affected_brand_ids;
	}

	private function updatePersonalFilters()
	{
		$storage = new shopBrandBrandStorage();

		$affected_brand_ids = array();

		foreach ($this->brands_filter as $brand_id => $filter)
		{
			$brand = $storage->getById($brand_id);
			if (!$brand || $this->arraysAreEqual($brand->filter, $filter))
			{
				continue;
			}

			$affected_brand_ids[] = $brand->id;
			$brand->filter = $filter;

			$storage->store($brand->assoc());
		}

		return $affected_brand_ids;
	}

	private function arraysAreEqual($a1, $a2)
	{
		if (!is_array($a1) || !is_array($a2))
		{
			return false;
		}

		if (count($a1) !== count($a2))
		{
			return false;
		}

		foreach ($a1 as $index => $val)
		{
			if ($val != $a2[$index])
			{
				return false;
			}
		}

		return true;
	}
}
