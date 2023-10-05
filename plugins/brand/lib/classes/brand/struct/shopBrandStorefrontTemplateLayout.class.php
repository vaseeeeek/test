<?php

/**
 * Class shopBrandBrandPage
 *
 * @property string $storefront
 * @property int $page_id
 * @property int $brand_id
 * @property string $meta_title
 * @property string $meta_description
 * @property string $meta_keywords
 * @property string $h1
 * @property string $description
 * @property string $additional_description
 * @property string $content
 */
class shopBrandStorefrontTemplateLayout extends shopBrandPropertyAccess
{
	public function isEmpty()
	{
		$meta_fields = array(
			'meta_title',
			'meta_description',
			'meta_keywords',
			'h1',
			'description',
			'additional_description',
			'content',
		);

		foreach ($meta_fields as $meta_field)
		{
			$value = $this->$meta_field;
			if (is_string($value) && strlen($value) > 0)
			{
				return false;
			}
		}

		return true;
	}
}