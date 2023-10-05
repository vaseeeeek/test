<?php

/**
 * Class shopBrandProductSortEnumOptions
 *
 * @property-read string $MANUAL
 * @property-read string $NAME
 * @property-read string $PRICE_ASC
 * @property-read string $PRICE_DESC
 * @property-read string $RATING_ASC
 * @property-read string $RATING_DESC
 * @property-read string $TOTAL_SALES_ASC
 * @property-read string $TOTAL_SALES_DESC
 * @property-read string $COUNT
 * @property-read string $CREATE_DATETIME
 * @property-read string $STOCK_WORTH
 */
class shopBrandProductSortEnumOptions extends shopBrandEnumOptions
{
	/**
	 * @return string[]
	 */
	protected function getOptionValues()
	{
		return array(
			'MANUAL',
			'NAME',
			'PRICE_ASC',
			'PRICE_DESC',
			'RATING_ASC',
			'RATING_DESC',
			'TOTAL_SALES_ASC',
			'TOTAL_SALES_DESC',
			'COUNT',
			'CREATE_DATETIME',
			'STOCK_WORTH',
		);
	}
}