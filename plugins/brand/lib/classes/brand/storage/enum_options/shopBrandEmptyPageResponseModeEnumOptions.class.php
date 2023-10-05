<?php

/**
 * Class shopBrandEmptyPageResponseModeEnumOptions
 *
 * @property-read string $DEFAULT_200
 * @property-read string $DEFAULT_404
 * @property-read string $ERROR_404
 * @property-read string $DEFAULT
 */
class shopBrandEmptyPageResponseModeEnumOptions extends shopBrandEnumOptions
{
	protected function getOptionValues()
	{
		return array(
			'DEFAULT_200',
			'DEFAULT_404',
			'ERROR_404',
			'DEFAULT',
		);
	}
}
