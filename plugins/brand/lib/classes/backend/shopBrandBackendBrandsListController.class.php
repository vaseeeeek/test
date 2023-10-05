<?php

class shopBrandBackendBrandsListController
{
	public function getState()
	{
		$filter = $this->getFilter();
        $query = waRequest::get('query') ? waRequest::get('query') : '';
		$settings_storage = new shopBrandSettingsStorage();
		$settings = $settings_storage->getSettings();

		return array(
			'brand_list' => $this->getBrandList($filter, $query),
			'list_filter' => $this->prepareFilter($filter),
			'use_additional_description' => $settings->use_additional_description,
			'brand_products_url_template' => '?action=products#/products/hash=brand_plugin%2F%BRAND_ID%',
            'search_query' => $query,
		);
	}

	private function getBrandList(shopBrandBrandListFilter $filter, $query)
	{
		$storage = new shopBrandBackendBrandStorage();

		$all = $storage->getAllFiltered($filter, $query);
		$all_deleted = $storage->getAllDeleted();

		$all_assoc = array();
		foreach ($all as $brand)
		{
			$assoc = $brand->assoc();
			$assoc['image_url'] = $brand->getImageUrl(shopBrandImageStorage::SIZE_BACKEND_LIST);
			$assoc['is_deleted'] = false;

			$all_assoc[] = $assoc;
		}

		foreach ($all_deleted as $brand)
		{
			$assoc = $brand->assoc();
			$assoc['image_url'] = $brand->getImageUrl(shopBrandImageStorage::SIZE_BACKEND_LIST);
			$assoc['is_deleted'] = true;

			$all_assoc[] = $assoc;
		}

		unset($brand);

		return $all_assoc;
	}

	private function prepareFilter(shopBrandBrandListFilter $filter)
	{
		return $filter->assoc();
	}

	private function getFilter()
	{
		$filter_params_json = waRequest::get('list_filter_json');
		$filter_params = json_decode($filter_params_json, true);

		return new shopBrandBrandListFilter($filter_params);
	}
}
