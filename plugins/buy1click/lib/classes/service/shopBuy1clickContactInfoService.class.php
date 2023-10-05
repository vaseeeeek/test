<?php


class shopBuy1clickContactInfoService
{
	private $contact_info_storage;
	
	public function __construct(shopBuy1clickContactInfoStorage $contact_info_storage)
	{
		$this->contact_info_storage = $contact_info_storage;
	}
	
	/**
	 * @return shopBuy1clickContactInfo
	 */
	public function getCurrent()
	{
		return $this->contact_info_storage->getCurrent();
	}
	
	public function store(shopBuy1clickContactInfo $contact_info)
	{
		$this->contact_info_storage->store($contact_info);
	}
}