<?php

class shopEditBrandConverter
{
	/**
	 * @param shopBrandBrand $brand
	 * @return array
	 */
	public function toAssoc($brand)
	{
		$brand_assoc = $brand->assoc();

		return $brand_assoc;
	}

	/**
	 * @param shopBrandBrand[] $brands
	 * @return array
	 */
	public function allToAssoc($brands)
	{
		return array_map(array($this, 'toAssoc'), $brands);
	}
}