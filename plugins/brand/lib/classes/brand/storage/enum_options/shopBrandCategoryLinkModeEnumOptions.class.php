<?php

/**
 * Class shopBrandCategoryLinkModeEnumOptions
 *
 * @property-read string $RAW
 * @property-read string $SEOFILTER_ONLY
 * @property-read string $SEOFILTER_IF_EXISTS
 */
class shopBrandCategoryLinkModeEnumOptions extends shopBrandEnumOptions
{
	protected function getOptionValues()
	{
		return array(
			'RAW',
			'SEOFILTER_ONLY',
			'SEOFILTER_IF_EXISTS',
		);
	}
}