<?php


class shopBuy1clickShippingPlugin
{
	private $id;
	private $external;
	private $icon;
	private $img;
	/** @var waShipping */
	private $wa_shipping;
	
	public function getId()
	{
		return $this->id;
	}
	
	public function setId($id)
	{
		$this->id = $id;
	}
	
	public function getExternal()
	{
		return $this->external;
	}
	
	public function setExternal($external)
	{
		$this->external = $external;
	}
	
	public function getIcon()
	{
		return $this->icon;
	}
	
	public function setIcon($icon)
	{
		$this->icon = $icon;
	}
	
	public function getImg()
	{
		return $this->img;
	}
	
	public function setImg($img)
	{
		$this->img = $img;
	}

	public function getWaShipping()
	{
		return $this->wa_shipping;
	}

	public function setWaShipping(waShipping $wa_shipping)
	{
		$this->wa_shipping = $wa_shipping;
	}

	public function toArray()
	{
		return array(
			'id' => $this->getId(),
			'external' => $this->getExternal(),
			'icon' => $this->getIcon(),
			'img' => $this->getImg(),
		);
	}
}