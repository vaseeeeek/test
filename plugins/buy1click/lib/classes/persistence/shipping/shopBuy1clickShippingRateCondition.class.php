<?php


class shopBuy1clickShippingRateCondition
{
	/** @var shopBuy1clickShipping */
	private $shipping;
	/** @var shopBuy1clickContactInfoShippingAddress */
	private $shipping_address;
	/** @var shopBuy1clickCart */
	private $cart;
	
	public function getShipping()
	{
		return $this->shipping;
	}
	
	public function setShipping($shipping)
	{
		$this->shipping = $shipping;
	}

	public function getShippingAddress()
	{
		return $this->shipping_address;
	}

	/**
	 * @param $shipping_address
	 * @return $this
	 */
	public function setShippingAddress($shipping_address)
	{
		$this->shipping_address = $shipping_address;

		return $this;
	}

	public function getTotal()
	{
		if (!isset($this->cart))
		{
			return 0;
		}

		return $this->cart->getTotal();
	}

	public function getItems()
	{
		if (!isset($this->cart))
		{
			return array();
		}

		return $this->cart->getItems();
	}

	/**
	 * @param shopBuy1clickCart $cart
	 * @return $this
	 */
	public function setCart(shopBuy1clickCart $cart)
	{
		$this->cart = $cart;

		return $this;
	}
	
	public function getHash()
	{
		return md5(json_encode(array(
			'cart' => $this->cart->getCode(),
			'total' => $this->getTotal(),
			'items' => $this->getItems(),
			'shipping_address' => $this->getShippingAddress(),
		)));
	}
}