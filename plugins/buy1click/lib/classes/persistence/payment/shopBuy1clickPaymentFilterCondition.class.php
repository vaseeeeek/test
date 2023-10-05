<?php


class shopBuy1clickPaymentFilterCondition
{
	private $is_filter_allow = false;
	private $is_filter_current_storefront = false;
	private $is_filter_by_shipping = false;
	private $shipping_id;
	private $is_filter_by_payment_ids = false;
	private $payment_ids = array();
	private $is_filter_delpayfilter = false;
	/** @var shopBuy1clickCart */
	private $delpayfilter_cart;
	private $is_filter_checkcustomer = false;
	
	public function isFilterAllow()
	{
		return $this->is_filter_allow;
	}
	
	public function filterAllow()
	{
		$this->is_filter_allow = true;
		
		return $this;
	}
	
	public function isFilterCurrentStorefront()
	{
		return $this->is_filter_current_storefront;
	}

	public function filterCurrentStorefront()
	{
		$this->is_filter_current_storefront = true;

		return $this;
	}

	public function isFilterByShipping()
	{
		return $this->is_filter_by_shipping;
	}

	public function getShippingID()
	{
		return $this->shipping_id;
	}

	public function filterByShippingID($shipping_id)
	{
		$this->is_filter_by_shipping = true;
		$this->shipping_id = $shipping_id;

		return $this;
	}

	public function isFilterByPaymentIDs()
	{
		return $this->is_filter_by_payment_ids;
	}

	public function getPaymentIDs()
	{
		return $this->payment_ids;
	}

	public function filterByPaymentIDs($ids)
	{
		$this->is_filter_by_payment_ids = true;
		$this->payment_ids = $ids;
	}
	
	public function isFilterDelpayfilter()
	{
		return $this->is_filter_delpayfilter;
	}
	
	public function getDelpayfilterCart()
	{
		return $this->delpayfilter_cart;
	}
	
	public function filterDelpayfilter(shopBuy1clickCart $cart)
	{
		$this->is_filter_delpayfilter = true;
		$this->delpayfilter_cart = $cart;
		
		return $this;
	}
	
	public function isFilterCheckcustomer()
	{
		return $this->is_filter_checkcustomer;
	}
	
	public function filterCheckcustomer()
	{
		$this->is_filter_checkcustomer = true;
		
		return $this;
	}
}