<?php


interface shopBuy1clickContactInfoStorage
{
	/**
	 * @return shopBuy1clickContactInfo
	 */
	public function getCurrent();

	public function store(shopBuy1clickContactInfo $contact_info);
}