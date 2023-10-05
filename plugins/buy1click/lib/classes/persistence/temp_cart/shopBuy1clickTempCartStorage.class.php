<?php


interface shopBuy1clickTempCartStorage
{
	public function update(shopBuy1clickTempCart $temp_cart);
	
	public function garbageCollection();
}