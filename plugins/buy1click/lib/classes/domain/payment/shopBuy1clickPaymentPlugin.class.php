<?php


class shopBuy1clickPaymentPlugin
{
	private $id;
	private $icon;
	private $allowed_currency;
	private $is_allow_any_currency;
	
	public function getId()
	{
		return $this->id;
	}
	
	public function setId($id)
	{
		$this->id = $id;
	}
	
	public function getIcon()
	{
		return $this->icon;
	}
	
	public function setIcon($icon)
	{
		$this->icon = $icon;
	}
	
	public function getAllowedCurrency()
	{
		return $this->allowed_currency;
	}
	
	public function setAllowedCurrency($allowed_currency)
	{
		$this->allowed_currency = $allowed_currency;
	}
	
	public function isAllowAnyCurrency()
	{
		return $this->is_allow_any_currency;
	}
	
	public function setIsAllowAnyCurrency($is_allow_any_currency)
	{
		$this->is_allow_any_currency = $is_allow_any_currency;
	}
	
	public function toArray()
	{
		return array(
			'id' => $this->getId(),
			'icon' => $this->getIcon(),
			'allowed_currency' => $this->getAllowedCurrency(),
			'is_allow_any_currency' => $this->isAllowAnyCurrency(),
		);
	}
}