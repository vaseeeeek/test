<?php


class shopBuy1clickOrder
{
	private $id;
	private $items;
	private $subtotal;
	/** @var shopBuy1clickContactInfo */
	private $contact_info;
	/** @var shopBuy1clickShipping */
	private $shipping;
	/** @var shopBuy1clickShippingRate */
	private $shipping_rate;
	private $shipping_total;
	private $shipping_custom_fields = array();
	/** @var shopBuy1clickPayment */
	private $payment;
	private $coupon;
	private $discount;
	private $discount_description;
	private $comment;
	private $fee_amount = 0;
	private $fee_name = '';

	public function getId()
	{
		return $this->id;
	}
	
	public function setId($id)
	{
		$this->id = $id;
	}
	
	public function getItems()
	{
		return $this->items;
	}
	
	public function setItems($items)
	{
		$this->items = $items;
	}
	
	public function getSubtotal()
	{
		return $this->subtotal;
	}
	
	public function setSubtotal($subtotal)
	{
		$this->subtotal = $subtotal;
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
	
	public function getShippingRate()
	{
		return $this->shipping_rate;
	}
	
	public function setShippingRate($shipping_rate)
	{
		$this->shipping_rate = $shipping_rate;
	}
	
	public function getShippingTotal()
	{
		return $this->shipping_total;
	}
	
	public function setShippingTotal($shipping_total)
	{
		$this->shipping_total = $shipping_total;
	}

	public function getShippingCustomFields()
	{
		return $this->shipping_custom_fields;
	}

	public function setShippingCustomFields($shipping_custom_fields)
	{
		$this->shipping_custom_fields = $shipping_custom_fields;
	}
	
	public function getPayment()
	{
		return $this->payment;
	}
	
	public function setPayment($payment)
	{
		$this->payment = $payment;
	}
	
	public function getCoupon()
	{
		return $this->coupon;
	}
	
	public function setCoupon($coupon)
	{
		$this->coupon = $coupon;
	}
	
	public function getDiscount()
	{
		return $this->discount;
	}
	
	public function setDiscount($discount)
	{
		$this->discount = $discount;
	}
	
	public function getDiscountDescription()
	{
		return $this->discount_description;
	}
	
	public function setDiscountDescription($discount_description)
	{
		$this->discount_description = $discount_description;
	}
	
	public function getTotal()
	{
		return $this->getSubtotal() - $this->getDiscount() + $this->getShippingTotal() + $this->getIncreasePluginFeeAmount();
	}
	
	public function getComment()
	{
		return $this->comment;
	}
	
	public function setComment($comment)
	{
		$this->comment = $comment;
	}

	public function getIncreasePluginFeeAmount()
	{
		return $this->fee_amount;
	}

	public function getIncreasePluginFeeName()
	{
		return $this->fee_name;
	}

	public function setIncreasePluginFee($fee_amount, $fee_name)
	{
		$this->fee_amount = $fee_amount;
		$this->fee_name = $fee_name;
	}
	
	public function toArray()
	{
		$array = array(
			'id' => $this->getId(),
			'items' => $this->getItems(),
			'subtotal' => $this->getSubtotal(),
			'shipping_total' => $this->getShippingTotal(),
			'coupon' => $this->getCoupon(),
			'discount' => $this->getDiscount(),
			'discount_description' => $this->getDiscountDescription(),
			'increase_plugin_fee' => array(
				'amount' => $this->getIncreasePluginFeeAmount(),
				'name' => $this->getIncreasePluginFeeName(),
			),
			'total' => $this->getTotal(),
			'comment' => $this->getComment(),
		);
		$currency_fields = array(
			'subtotal',
			'shipping_total',
			'discount',
			'total',
		);
		
		if ($this->getShipping())
		{
			$array['shipping'] = $this->getShipping()->toArray();
		}
		
		if ($this->getShippingRate())
		{
			$array['shipping_rate'] = $this->getShippingRate()->toArray();
		}
		
		if ($this->getPayment())
		{
			$array['payment'] = $this->getPayment()->toArray();
		}
		
		if ($this->getContactInfo())
		{
			$array['contact_info'] = $this->getContactInfo()->toArray();
		}

		foreach ($currency_fields as $field)
		{
			$array["{$field}_currency"] = shop_currency($array[$field], true);
			$array["{$field}_currency_html"] = shop_currency_html($array[$field], true);
		}
		$fee_amount = $this->getIncreasePluginFeeAmount();
		$array['increase_plugin_fee']['amount_currency'] = shop_currency($fee_amount, true);
		$array['increase_plugin_fee']['amount_currency_html'] = shop_currency_html($fee_amount, true);

		return $array;
	}
}
