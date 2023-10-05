<?php


interface shopBuy1clickShippingPluginStorage
{
	/**
	 * @param $shipping_id
	 * @return shopBuy1clickShippingPlugin
	 */
	public function getByShippingID($shipping_id);
}