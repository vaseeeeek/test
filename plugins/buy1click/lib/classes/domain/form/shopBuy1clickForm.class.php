<?php


class shopBuy1clickForm
{
	const TYPE_ITEM = 'item';
	const TYPE_CART = 'cart';
	
	private $type;
	/** @var shopBuy1clickSettings */
	private $settings;
	/** @var shopBuy1clickCart */
	private $cart;
	/** @var shopBuy1clickContactInfo */
	private $contact_info;
	/** @var shopBuy1clickShipping[] */
	private $shipping;
	/** @var shopBuy1clickPayment[] */
	private $payment;
	/** @var shopBuy1clickSession */
	private $session;
	/** @var shopBuy1clickOrder */
	private $order;
	/** @var shopBuy1clickConfirmationChannel */
	private $confirmation_channel;
	private $errors = array();
	
	public function getType()
	{
		return $this->type;
	}
	
	public function setType($type)
	{
		$this->type = $type;
	}
	
	public function getSettings()
	{
		return $this->settings;
	}
	
	public function setSettings($settings)
	{
		$this->settings = $settings;
	}
	
	public function getCart()
	{
		return $this->cart;
	}
	
	public function setCart($cart)
	{
		$this->cart = $cart;
	}
	
	public function getContactInfo()
	{
		return $this->contact_info;
	}
	
	public function setContactInfo($contact_info)
	{
		$this->contact_info = $contact_info;
	}
	
	public function getShipping()
	{
		return $this->shipping;
	}
	
	public function setShipping($shipping)
	{
		$this->shipping = $shipping;
	}
	
	public function getPayments()
	{
		return $this->payment;
	}
	
	public function setPayments($payment)
	{
		$this->payment = $payment;
	}
	
	public function getSession()
	{
		return $this->session;
	}
	
	public function setSession($session)
	{
		$this->session = $session;
	}
	
	public function getOrder()
	{
		return $this->order;
	}
	
	public function setOrder($order)
	{
		$this->order = $order;
	}
	
	public function getErrors()
	{
		return $this->errors;
	}
	
	public function setErrors($errors)
	{
		$this->errors = $errors;
	}

	public function getConfirmationChannel()
	{
		return $this->confirmation_channel;
	}

	public function setConfirmationChannel(shopBuy1clickConfirmationChannel $confirmation_channel)
	{
		$this->confirmation_channel = $confirmation_channel;
	}

	public function validate(shopBuy1clickFormValidator $validator)
	{
		$this->setErrors($validator->getErrors($this));
	}
}
