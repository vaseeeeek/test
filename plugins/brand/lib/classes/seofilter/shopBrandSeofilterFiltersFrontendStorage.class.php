<?php

class shopBrandSeofilterFiltersFrontendStorage
{
	/** @var shopSeofilterIFiltersStorage */
	private $storage;

	private $seofilter_version;

	public function __construct()
	{
		$this->storage = null;

		$info = wa('shop')->getConfig()->getPluginInfo('seofilter');
		$version = is_array($info) && array_key_exists('version', $info)
			? $info['version']
			: '0.0';

		$this->seofilter_version = $version;

		if ($version === '0.0')
		{
			return;
		}

		if (!class_exists('shopSeofilterBasicSettingsModel') || !class_exists('shopSeofilterPluginSettings'))
		{
			return;
		}

		$settings = shopSeofilterBasicSettingsModel::getSettings();
		if (!$settings->is_enabled)
		{
			return;
		}

		if (version_compare($version, '2.9', '>') && class_exists('shopSeofilterFiltersFrontendStorage'))
		{
			$this->storage = new shopSeofilterFiltersFrontendStorage();
		}
		elseif (version_compare($version, '2.6', '>') && class_exists('shopSeofilterFiltersStorage'))
		{
			$this->storage = new shopSeofilterFiltersStorage();
		}
	}

	/**
	 * @param $storefront
	 * @param $category_id
	 * @param $filter_params
	 * @param $currency
	 * @return shopSeofilterFilter|null
	 */
	public function getByFilterParams($storefront, $category_id, $filter_params, $currency)
	{
		if (!$this->storage)
		{
			return null;
		}

		if (version_compare($this->seofilter_version, '2.9', '>') && class_exists('shopSeofilterFiltersFrontendStorage'))
		{
			return $this->storage->getByFilterParams($storefront, $category_id, $filter_params, $currency);
		}
		else
		{
			return $this->storage->getByFilter($storefront, $category_id, $filter_params, $currency);
		}
	}

	public function filterHaveProducts($storefront, $category_id, shopSeofilterFilter $filter)
	{
		if (class_exists('shopSeofilterFrontendCategoryInteractor')) {
			$interactor = new shopSeofilterFrontendCategoryInteractor();

			return $interactor->isFilterHaveProducts($storefront, $category_id, $filter);
		}

		return true;
	}
}