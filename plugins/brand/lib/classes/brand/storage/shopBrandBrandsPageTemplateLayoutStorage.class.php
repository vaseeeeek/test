<?php

class shopBrandBrandsPageTemplateLayoutStorage extends shopBrandStorage
{
	/**
	 * @param string $storefront
	 * @return shopBrandBrandsPageStorefrontTemplateLayout
	 */
	public function getMeta($storefront)
	{
		$meta_raw = $this->model
			->select('name,setting')
			->where('storefront = :storefront', array('storefront' => $storefront))
			->where('name IN (s:names)', array('names' => $this->getAvailableFields()))
			->fetchAll('name', true);

		return new shopBrandBrandsPageStorefrontTemplateLayout($this->prepareStorableForAccessible($meta_raw));
	}

	public function store($storefront, $meta_assoc)
	{
		$meta_raw = $this->prepareAccessibleToStorable($meta_assoc);

		$success = true;

		foreach ($meta_raw as $name => $setting)
		{
			$success = $this->model->insert(array(
				'storefront' => $storefront,
				'name' => $name,
				'setting' => $setting,
			), waModel::INSERT_ON_DUPLICATE_KEY_UPDATE) && $success;
		}

		return $success;
	}

	protected function accessSpecification()
	{
		$specification = new shopBrandDataFieldSpecificationFactory();

		return array(
			'brands_page_meta_h1' => $specification->string(''),
			'brands_page_meta_title' => $specification->string(''),
			'brands_page_meta_description' => $specification->string(''),
			'brands_page_meta_keywords' => $specification->string(''),
			'brands_page_description' => $specification->string(''),
			'brands_page_additional_description' => $specification->string(''),
		);
	}

	protected function dataModel()
	{
		return new shopBrandSettingsModel();
	}
}