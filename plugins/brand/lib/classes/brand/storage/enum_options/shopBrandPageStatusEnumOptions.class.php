<?php

/**
 * Class shopBrandPageStatusEnumOptions
 *
 * @property-read string $PUBLISHED
 * @property-read string $DRAFT
 */
class shopBrandPageStatusEnumOptions extends shopBrandEnumOptions
{
	/**
	 * @return string[]
	 */
	protected function getOptionValues()
	{
		return array(
			'PUBLISHED',
			'DRAFT',
		);
	}
}