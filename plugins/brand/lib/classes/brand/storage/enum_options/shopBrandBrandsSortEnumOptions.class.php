<?php

/**
 * Class shopBrandBrandsSortEnumOptions
 *
 * @property-read $SORT
 * @property-read $NAME
 */
class shopBrandBrandsSortEnumOptions extends shopBrandEnumOptions
{
	protected function getOptionValues()
	{
		return array(
			'SORT',
			'NAME',
		);
	}
}