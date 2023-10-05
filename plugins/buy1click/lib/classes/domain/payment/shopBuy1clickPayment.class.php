<?php


class shopBuy1clickPayment
{
	private $id;
	/** @var shopBuy1clickPaymentPlugin */
	private $plugin;
	private $name;
	private $description;
	private $logo;
	private $status;
	private $sort;
	private $available;
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
		return array(
			'id' => $this->getID(),
			'name' => $this->getName(),
			'description' => $this->getDescription(),
			'logo' => $this->getLogo(),
			'status' => $this->getStatus(),
			'sort' => $this->getSort(),
			'available' => $this->getAvailable(),
			'error' => $this->getError(),
		);
	}
}