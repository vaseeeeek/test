<?php


interface shopBuy1clickShippingRateStorage
{
	/**
	 * @param shopBuy1clickShippingRateCondition $condition
	 * @param $error
	 * @return shopBuy1clickShippingRate[]
	 */
	public function getByCondition(shopBuy1clickShippingRateCondition $condition, &$error);
}