<?php

/**
 * Class shopBrandBrandsPageStorefrontTemplateLayout
 *
 * @property string $brands_page_meta_h1
 * @property string $brands_page_meta_title
 * @property string $brands_page_meta_description
 * @property string $brands_page_meta_keywords
 * @property string $brands_page_description
 * @property string $brands_page_additional_description
 */
class shopBrandBrandsPageStorefrontTemplateLayout extends shopBrandPropertyAccess
{
	public function getTemplateLayout()
	{
		return new shopBrandTemplateLayout(array(
			'meta_title' => $this->brands_page_meta_title,
			'meta_description' => $this->brands_page_meta_description,
			'meta_keywords' => $this->brands_page_meta_keywords,
			'h1' => $this->brands_page_meta_h1,
			'description' => $this->brands_page_description,
			'additional_description' => $this->brands_page_additional_description,
		));
	}
}