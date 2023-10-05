<?php


class shopBuy1clickShippingRate
{
	private $id;
	private $rate;
	private $compare_rate;
	private $currency;
	private $name;
	private $comment;
	private $est_delivery;
	
	public function getId()
	{
		return $this->id;
	}
	
	public function setId($id)
	{
		$this->id = $id;
	}
	
	public function getRate()
	{
		return $this->rate;
	}
	
	public function setRate($rate)
	{
		$this->rate = $rate;
	}
	
	public function getCompareRate()
	{
		return $this->compare_rate;
	}
	
	public function setCompareRate($compare_rate)
	{
		$this->compare_rate = $compare_rate;
	}
	
	public function getCurrency()
	{
		return $this->currency;
	}
	
	public function setCurrency($currency)
	{
		$this->currency = $currency;
	}
	
	public function getName()
	{
		return $this->name;
	}
	
	public function setName($name)
	{
		$this->name = $name;
	}
	
	public function getComment()
	{
		return $this->comment;
	}
	
	public function setComment($comment)
	{
		$this->comment = $comment;
	}
	
	public function getEstDelivery()
	{
		return $this->est_delivery;
	}
	
	public function setEstDelivery($est_delivery)
	{
		$this->est_delivery = $est_delivery;
	}
	
	public function toArray()
	{
		$array = array(
			'id' => $this->getId(),
			'rate' => $this->getRate(),
			'compare_rate' => $this->getCompareRate(),
			'currency' => $this->getCurrency(),
			'name' => $this->getName(),
			'comment' => $this->getComment(),
			'est_delivery' => $this->getEstDelivery(),
		);
		$array['rate_currency'] = shop_currency($array['rate'], $array['currency']);
		$array['rate_currency_html'] = shop_currency_html($array['rate'], $array['currency']);
		$array['compare_rate_currency'] = shop_currency($array['compare_rate'], $array['currency']);
		$array['compare_rate_currency_html'] = shop_currency_html($array['compare_rate'], $array['currency']);
		
		return $array;
	}
}