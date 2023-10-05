<?php


interface shopBuy1clickSessionStorage
{
	/**
	 * @param $code
	 * @return shopBuy1clickSession
	 */
	public function getByCode($code);
	
	public function store(shopBuy1clickSession $session);
}