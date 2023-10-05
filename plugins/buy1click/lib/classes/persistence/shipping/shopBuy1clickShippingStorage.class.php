<?php


interface shopBuy1clickShippingStorage
{
	/**
	 * @return shopBuy1clickShipping[]
	 */
	public function getAll();

	/**
	 * @param $shipping_id
	 * @return shopBuy1clickShipping
	 */
	public function getByID($shipping_id);
}