<?php

class shopBrandPluginBackendBrandEditAction extends shopBrandBackendBrandFormAction
{
	/**
	 * @return null|shopBrandBrand
	 * @throws waException
	 */
	protected function getBrand()
	{
		$brand_id = waRequest::get('brand_id');

		$brand_storage = new shopBrandBrandStorage();
		$brand = $brand_storage->getById($brand_id);

		if (!$brand)
		{
			throw new waException("Can't get brand with id [{$brand_id}]", 404);
		}

		return $brand;
	}
}