<?php

/**
 * Собственный список пунктов выдачи
 */

class shopDpPointsStoreIntegration extends shopDpPointsIntegration implements shopDpPointsIntegrationInterface
{
	protected $params = array(
		'style' => 3,
		'actuality' => shopDpPointsIntegration::UNLIMITED
	);

	public function takePoints($key = null)
	{
		$env = new shopDpEnv();
		$storefront_id = $env->getCurrentStorefront();

		$search_params = $this->getSearchParams();
		if(empty($search_params['city_name'])) {
			return false;
		}

		$hash = "{$search_params['country_code']}:{$search_params['region_code']}:{$search_params['city_name']}";

		$params = array(
			'search_hash' => $hash
		);

		$points_model = $this->getPointsModel();
		$initial_points = $points_model->getCustomPoints($this->id, $params);
		$points = array();

		$storefront_group = $env->getStorefrontGroup($storefront_id);

		if(array_key_exists($storefront_id, $initial_points)) {
			$points = $initial_points[$storefront_id];
		} elseif(array_key_exists("group:$storefront_group", $initial_points)) {
			$points = $initial_points["group:$storefront_group"];
		} elseif(array_key_exists('*', $initial_points)) {
			$points = $initial_points['*'];
		}

		return $points;
	}
}
