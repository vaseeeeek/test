<?php

class shopBrandBrandsCollectionFactory
{
	/**
	 * @param shopBrandSettings $settings
	 * @return shopBrandIBrandsCollection
	 */
	public static function getBrandsCollection(shopBrandSettings $settings)
	{
		try
		{
			$storefront = shopBrandStorefront::getCurrent();

			$collection = new shopBrandBrandsCollection($storefront);
		}
		catch (waException $e)
		{
			return new shopBrandBrandsEmptyCollection();
		}

		$default_sort_option = $settings->brands_default_sort;

		$brands_page_sort_options = new shopBrandBrandsSortEnumOptions();
		if ($default_sort_option == $brands_page_sort_options->SORT)
		{
			$collection->sort('sort', 'ASC');
		}
		elseif ($default_sort_option == $brands_page_sort_options->NAME)
		{
			$collection->sort('name', 'ASC');
		}

		if ($settings->with_images_only)
		{
			$collection->withImagesOnly();
		}

		$collection->withProductsOnly();

		return $collection;
	}
}
