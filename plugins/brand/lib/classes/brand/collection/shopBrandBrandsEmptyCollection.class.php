<?php

class shopBrandBrandsEmptyCollection implements shopBrandIBrandsCollection
{
	/**
	 * @return shopBrandBrand[]
	 */
	public function getBrands()
	{
		return array();
	}

	/**
	 * @param bool $with_images_only
	 * @return shopBrandIBrandsCollection
	 */
	public function withImagesOnly($with_images_only = true)
	{
		return $this;
	}

	/**
	 * @param string|array $sort
	 * @param string $order
	 * @return shopBrandIBrandsCollection
	 */
	public function sort($sort, $order = 'ASC')
	{
		return $this;
	}

	/**
	 * @param bool $with_products_only
	 * @return shopBrandIBrandsCollection
	 */
	public function withProductsOnly($with_products_only = true)
	{
		return $this;
	}

	public function sortBrands(&$all_brands)
	{
	}

	/**
	 * @return array|null
	 */
	public function getBrandValueIds()
	{
		return array();
	}
}
