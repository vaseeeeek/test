<?php

interface shopBrandIBrandsCollection
{
	/**
	 * @return shopBrandBrand[]
	 */
	public function getBrands();

	/**
	 * @param bool $with_images_only
	 * @return shopBrandIBrandsCollection
	 */
	public function withImagesOnly($with_images_only = true);

	/**
	 * @param string|array $sort
	 * @param string $order
	 * @return shopBrandIBrandsCollection
	 */
	public function sort($sort, $order = 'ASC');

	/**
	 * @param bool $with_products_only
	 * @return shopBrandIBrandsCollection
	 */
	public function withProductsOnly($with_products_only = true);

	public function sortBrands(&$all_brands);

	/**
	 * @return array|null
	 * @throws waException
	 */
	public function getBrandValueIds();
}
