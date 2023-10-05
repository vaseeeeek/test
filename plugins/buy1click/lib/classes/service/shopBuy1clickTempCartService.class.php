<?php


class shopBuy1clickTempCartService
{
	private $temp_cart_storage;
	
	public function __construct(shopBuy1clickTempCartStorage $temp_cart_storage)
	{
		$this->temp_cart_storage = $temp_cart_storage;
		$this->garbageCollection();
	}
	
	public function update(shopBuy1clickTempCart $temp_cart)
	{
		$this->temp_cart_storage->update($temp_cart);
	}
	
	public function garbageCollection()
	{
		$this->temp_cart_storage->garbageCollection();
	}
}