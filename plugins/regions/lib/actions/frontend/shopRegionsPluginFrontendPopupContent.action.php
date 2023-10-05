<?php

class shopRegionsPluginFrontendPopupContentAction extends waViewAction
{
	public function execute()
	{
		$settings = new shopRegionsSettings();

		$view_vars = array(
			'current' => $this->getCurrentCityArray(),
		);

		$window_sort = shopRegionsCityModel::getSortColumnByWindowSort($settings->window_sort);
		$order_by = $settings->window_group_by_letter_enable ? 'name' : $window_sort;

		$collection = $this->getCollection($order_by);

		$view_vars['cities_by_region'] = $collection->getGroupedBy('region_name');

		$first_group = reset($view_vars['cities_by_region']);
		$window_is_empty = $first_group && $first_group->getCustomAttribute('is_only', false)
			? count($first_group->getCitiesAssoc()) == 0
			: false;
		unset($first_group);


		$view_vars['all_cities_by_column'] = array();
		$view_vars['popular_cities_by_column'] = array();
		$view_vars['count_all_cities'] = 0;

		if (!$window_is_empty && !$settings->window_regions_sidebar_enable)
		{
			$count_columns = $settings->window_columns;
			if (!$count_columns)
			{
				$count_columns = 3;
			}

			$view_vars['all_cities_by_column'] = $collection->orderBy($order_by)->getGroupByColumnAndLetterAssoc($count_columns);
			$view_vars['count_all_cities'] = $collection->count();

			if ($settings->window_popular_enable)
			{
				$view_vars['popular_cities_by_column'] = $collection
					->popularOnly()
					->orderBy($window_sort)
					->getGroupByColumnAssoc($count_columns);
			}
		}

		$view_vars['settings'] = $settings->get();
		$view_vars['is_empty'] = $window_is_empty;

		$this->view->assign('regions', $view_vars);
	}

	private function getCurrentCityArray()
	{
		$routing = new shopRegionsRouting();

		$current_city = $routing->getCurrentCity();

		$current_city_array = array(
			'id' => '',
			'region_code' => '',
			'country_iso3' => '',
		);

		if ($current_city)
		{
			$current_city->getCountryName();
			$current_city_array = $current_city->toArray(false, false);
		}

		return $current_city_array;
	}

	/**
	 * @param $order_by
	 * @return shopRegionsCityCollection
	 */
	public function getCollection($order_by)
	{
		$collection = new shopRegionsCityCollection();

		$collection
			->enabledOnly()
			->withStorefrontOnly()
			->leftJoinRegion()
			->orderBy($order_by);

		return $collection;
	}
}