<?php

class shopBrandPluginBackendBrandCreateAction extends shopBrandBackendBrandFormAction
{
	protected function getBrand()
	{
		$attributes = array(
			'id' => '',
			'name' => '',
			'url' => '',
			'image' => '',
			'description_short' => '',
			'product_sort' => '',
			'filter' => '',
			'is_shown' => true,
			'enable_client_sorting' => true,
			'sort' => '',
		);

		return new shopBrandBrand($attributes);
	}
}