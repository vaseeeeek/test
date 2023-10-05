<?php


class shopBuy1clickSessionService
{
	private $session_storage;
	
	public function __construct(shopBuy1clickSessionStorage $session_storage)
	{
		$this->session_storage = $session_storage;
	}
	
	/**
	 * @param $code
	 * @return shopBuy1clickSession
	 */
	public function getByCode($code)
	{
		return $this->session_storage->getByCode($code);
	}
	
	public function store(shopBuy1clickSession $session)
	{
		$this->session_storage->store($session);
	}
}