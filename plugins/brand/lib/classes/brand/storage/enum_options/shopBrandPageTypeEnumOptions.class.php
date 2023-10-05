<?php

/**
 * Class shopBrandPageTypeEnumOptions
 *
 * @property-read string $PAGE
 * @property-read string $CATALOG
 * @property-read string $REVIEWS
 */
class shopBrandPageTypeEnumOptions extends shopBrandEnumOptions
{
	/**
	 * @return string[]
	 */
	protected function getOptionValues()
	{
		return array(
			'PAGE',
			'CATALOG',
			'REVIEWS',
		);
	}
}