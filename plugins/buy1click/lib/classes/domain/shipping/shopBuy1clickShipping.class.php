<?php


class shopBuy1clickShipping
{
	private $id;
	/** @var shopBuy1clickShippingPlugin */
	private $plugin;
	private $name;
	private $description;
	private $logo;
	private $status;
	private $sort;
	private $available;
	/** @var shopBuy1clickShippingRate[] */
	private $rates;
	private $custom_fields;
	private $error;
	
	public function getId()
	{
		return $this->id;
	}
	
	public function setId($id)
	{
		$this->id = $id;
	}
	
	public function getPlugin()
	{
		return $this->plugin;
	}
	
	public function setPlugin($plugin)
	{
		$this->plugin = $plugin;
	}
	
	public function getName()
	{
		return $this->name;
	}
	
	public function setName($name)
	{
		$this->name = $name;
	}
	
	public function getDescription()
	{
		return $this->description;
	}
	
	public function setDescription($description)
	{
		$this->description = $description;
	}
	
	public function getLogo()
	{
		return $this->logo;
	}
	
	public function setLogo($logo)
	{
		$this->logo = $logo;
	}
	
	public function getStatus()
	{
		return $this->status;
	}
	
	public function setStatus($status)
	{
		$this->status = $status;
	}
	
	public function getSort()
	{
		return $this->sort;
	}
	
	public function setSort($sort)
	{
		$this->sort = $sort;
	}
	
	public function getAvailable()
	{
		return $this->available;
	}
	
	public function setAvailable($available)
	{
		$this->available = $available;
	}
	
	public function getRates()
	{
		return $this->rates;
	}
	
	public function setRates($rates)
	{
		$this->rates = $rates;
	}
	
	public function getCustomFields()
	{
		return $this->custom_fields;
	}
	
	public function setCustomFields($custom_fields)
	{
		$this->custom_fields = $custom_fields;
	}
	
	public function getError()
	{
		return $this->error;
	}
	
	public function setError($error)
	{
		$this->error = $error;
	}
	
	public function toArray()
	{
		$array = array(
			'id' => $this->getID(),
			'plugin' => $this->getPlugin()->toArray(),
			'name' => $this->getName(),
			'description' => $this->getDescription(),
			'logo' => $this->getLogo(),
			'status' => $this->getStatus(),
			'sort' => $this->getSort(),
			'available' => $this->getAvailable(),
			'custom_fields' => $this->getCustomFields(),
			'error' => $this->getError(),
		);
		
		$rates = null;
		
		if (is_array($this->getRates()))
		{
			$rates = array();
			
			foreach ($this->getRates() as $i => $rate)
			{
				$rates[$i] = $rate->toArray();
			}
		}
		
		$array['rates'] = $rates;
		
		return $array;
	}
}